"""
Script d'insertion asynchrone de données patient dans la base de données.
Lit les données depuis un fichier CSV et les insère de manière asynchrone.
Les insertions sont parallélisées par cycle.
"""
import asyncio
import csv
import os
import random
from datetime import datetime
from collections import defaultdict

import aiomysql
from dotenv import load_dotenv

# Configuration de base
CSV_FILE = 'patient_data.csv'
INSERT_DELAY_SECONDS = 1.0
DB_POOL_MIN_SIZE = 5
DB_POOL_MAX_SIZE = 20
DB_TABLE_NAME = 'patient_data'
DATETIME_FORMAT = '%Y-%m-%d %H:%M:%S'
GENERATED_CYCLES = 1000
REDEFINITION_INTERVAL = 100

CYCLE_PERIOD_SECONDS = 1
VOLATILITY_DEFAULT = 0.02
MIN_STEP_ABS = 0.1
SPIKE_PROB = 0.01
SPIKE_MULT = 4.0
ALERT_PROB = 0.1
MONITORING_PROB = 0.15
TARGET_CHANGE_PROB = 0.05

# Remplissage automatique des indicateurs absents du CSV
FILL_VALUES = True

# Colonnes de la table cible
DB_COLUMNS = [
    'id_patient',
    'parameter_id',
    'value',
    'timestamp',
    'alert_flag',
    'created_by',
    'archived'
]

load_dotenv()

DB_CONFIG = {
    'host': os.getenv('DB_HOST'),
    'user': os.getenv('DB_USER'),
    'password': os.getenv('DB_PASS'),
    'db': os.getenv('DB_NAME'),
}

# Listes de validation chargées depuis la BDD au démarrage
VALID_PATIENT_IDS = []
VALID_PARAMETERS = []
VALID_USER_IDS = []
PARAMETER_RANGES = {}  # {parameter_id: {'dm': display_min, 'dmx': display_max, 'nm': normal_min, 'nmx': normal_max, 'cm': critical_min, 'cmx': critical_max}}
PARAM_VOLATILITY = {}  # {parameter_id: volatility_float}

def load_csv_data(filepath: str):
    """Charge le CSV en mémoire sous forme de liste de dictionnaires."""
    print(f"[DEBUG] Chargement du fichier CSV: {filepath}")
    data = []
    with open(filepath, 'r', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        for row in reader:
            data.append(row)
    print(f"[DEBUG] {len(data)} lignes chargées")
    return data


def generate_fill_value(parameter_id: str) -> float:
    """Génère une valeur aléatoire dans la plage display_min/display_max récupérée de la BDD."""
    if parameter_id in PARAMETER_RANGES:
        min_val, max_val = PARAMETER_RANGES[parameter_id]
        return round(random.uniform(min_val, max_val), 2)
    return round(random.uniform(0, 100), 2)

def get_param_bounds(parameter_id: str) -> tuple[float, float]:
    """Retourne (display_min, display_max) pour le clamping."""
    if parameter_id in PARAMETER_RANGES:
        p = PARAMETER_RANGES[parameter_id]
        return p['dm'], p['dmx']
    return 0.0, 100.0


def clamp(x: float, mn: float, mx: float) -> float:
    return mn if x < mn else mx if x > mx else x


def next_smooth_value(parameter_id: str, current: float, target: float) -> float:
    """Calcule la prochaine valeur en gravitant vers une cible avec du bruit."""
    mn, mx = get_param_bounds(parameter_id)
    span = max(mx - mn, 1e-6)

    vol = PARAM_VOLATILITY.get(parameter_id, VOLATILITY_DEFAULT)
    step = max(span * vol, MIN_STEP_ABS)

    # delta aléatoire
    delta = random.uniform(-step, step)

    # rares spikes
    if random.random() < SPIKE_PROB:
        delta *= SPIKE_MULT

    # force de rappel vers la CIBLE choisie par la fonction de décision
    pull = (target - current) * 0.1  # 10% du chemin vers la cible à chaque pas

    nxt = current + delta + pull
    return round(clamp(nxt, mn, mx), 2)

def is_in_alert(parameter_id: str, value: float) -> int:
    """
    Détermine le niveau d'alerte selon les seuils BDD :
    0: Normal (dans normal_min/max)
    1: Surveillance (hors normal, mais dans critical)
    2: Alerte (hors critical)
    """
    if parameter_id not in PARAMETER_RANGES: return 0
    p = PARAMETER_RANGES[parameter_id]

    # Ordre de priorité : Alerte d'abord
    if (p['cm'] is not None and value < p['cm']) or (p['cmx'] is not None and value > p['cmx']):
        return 2

    if (p['nm'] is not None and value < p['nm']) or (p['nmx'] is not None and value > p['nmx']):
        return 1

    return 0

def get_new_target(parameter_id: str, mode: int = 0) -> float:
    """
    Décide d'une nouvelle valeur cible selon les seuils BDD et le mode.
    """
    if parameter_id not in PARAMETER_RANGES: return 50.0
    p = PARAMETER_RANGES[parameter_id]

    # Fallback si les seuils sont mal définis
    dm, dmx = p['dm'], p['dmx']
    nm, nmx = p['nm'] or dm, p['nmx'] or dmx
    cm, cmx = p['cm'] or nm - 5, p['cmx'] or nmx + 5

    if mode == 2:
        # Zone critique (Hors cm/cmx)
        if random.random() > 0.5:
            return random.uniform(cmx, dmx)
        else:
            return random.uniform(dm, cm)
    elif mode == 1:
        # Zone surveillance (Entre nm/nmx et cm/cmx)
        if random.random() > 0.5:
            return random.uniform(nmx, cmx)
        else:
            return random.uniform(cm, nm)
    else:
        # Zone normale (Milieu de nm/nmx)
        # On cible le centre 50% de la zone normale pour la stabilité
        safe_nm = nm + (nmx - nm) * 0.25
        safe_nmx = nmx - (nmx - nm) * 0.25
        return random.uniform(safe_nm, safe_nmx)

# Variables globales d'état pour la génération infinie
generation_states = {}
episode_count = 0

def init_generation_states():
    global generation_states, episode_count
    generation_states.clear()
    episode_count = 0
    for patient_id in VALID_PATIENT_IDS:
        for param_id in VALID_PARAMETERS:
            mn, mx = get_param_bounds(param_id)
            mid = (mn + mx) / 2
            generation_states[(patient_id, param_id)] = {
                'current': mid,
                'target': mid,
                'mode': 0
            }

def generate_batch(num_cycles: int = 100) -> list:
    """Génère un lot (batch) de cycles (par défaut 100) en utilisant l'état global."""
    global episode_count
    if not VALID_PATIENT_IDS or not VALID_PARAMETERS:
        return []

    data = []
    default_created_by = str(VALID_USER_IDS[0]) if VALID_USER_IDS else '1'

    for cycle_idx in range(num_cycles):
        if cycle_idx == 0:
            episode_count += 1
            print(f"\n[DÉCISION] Épisode {episode_count} (Génération de {num_cycles} cycles en arrière-plan)")

            for patient_id in VALID_PATIENT_IDS:
                alerts = []
                monitoring = []

                shuffled_params = list(VALID_PARAMETERS)
                random.shuffle(shuffled_params)

                crit_count = 0
                monit_count = 0

                for param_id in shuffled_params:
                    s = generation_states[(patient_id, param_id)]
                    roll = random.random()

                    if roll < ALERT_PROB and crit_count < 4:
                        s['mode'] = 2
                        crit_count += 1
                        alerts.append(param_id)
                    elif roll < ALERT_PROB + MONITORING_PROB and monit_count < 4:
                        s['mode'] = 1
                        monit_count += 1
                        monitoring.append(param_id)
                    else:
                        s['mode'] = 0

                    s['target'] = get_new_target(param_id, mode=s['mode'])

                if alerts or monitoring:
                    crit_list = ", ".join(sorted(alerts)) if alerts else "-"
                    monit_list = ", ".join(sorted(monitoring)) if monitoring else "-"
                    print(f"  PATIENT {str(patient_id):<3} | CRITIQUE: {crit_list:<45} | SURVEILLANCE: {monit_list}")

        for patient_id in VALID_PATIENT_IDS:
            for param_id in VALID_PARAMETERS:
                s = generation_states[(patient_id, param_id)]

                if cycle_idx != 0:
                    if random.random() < TARGET_CHANGE_PROB:
                        s['target'] = get_new_target(param_id, mode=s['mode'])

                nxt = next_smooth_value(param_id, s['current'], s['target'])
                s['current'] = nxt

                res_flag = min(is_in_alert(param_id, nxt), s['mode'])
                db_alert_flag = 1 if res_flag == 2 else 0

                record = {
                    'id_patient': str(patient_id),
                    'parameter_id': param_id,
                    'value': str(nxt),
                    'timestamp': '',
                    'alert_flag': str(db_alert_flag),
                    'created_by': default_created_by,
                    'archived': '0'
                }
                data.append(record)

    return data


def group_data_by_cycle(data):
    """
    Regroupe les données pour simuler des relevés simultanés.
    Chaque cycle contient le n-ième relevé de chaque paire (patient, paramètre).
    Si FILL_VALUES est actif, ajoute des valeurs générées pour les indicateurs absents du CSV.
    """
    grouped = defaultdict(list)
    for record in data:
        key = (record['id_patient'], record['parameter_id'])
        grouped[key].append(record)

    # Récupérer les patients et paramètres présents dans le CSV
    csv_patients = set(record['id_patient'] for record in data)
    csv_parameters = set(record['parameter_id'] for record in data)

    # Si FILL_VALUES est actif, ajouter les indicateurs manquants
    if FILL_VALUES and VALID_PARAMETERS and VALID_PATIENT_IDS:
        missing_params = set(VALID_PARAMETERS) - csv_parameters
        if missing_params:
            print(f"[INFO] FILL_VALUES actif: génération de valeurs pour {len(missing_params)} indicateurs manquants")
            print(f"[INFO] Indicateurs à remplir: {missing_params}")

            # Trouver un exemple de record pour copier created_by et autres métadonnées
            sample_record = data[0] if data else None
            default_created_by = sample_record.get('created_by', '1') if sample_record else '1'

            # Nombre de cycles basé sur la série la plus longue existante
            max_existing = max(len(records) for records in grouped.values()) if grouped else 1

            # Pour chaque patient présent dans le CSV, ajouter les indicateurs manquants
            for patient_id in csv_patients:
                for param_id in missing_params:
                    key = (patient_id, param_id)
                    for _ in range(max_existing):
                        filled_record = {
                            'id_patient': patient_id,
                            'parameter_id': param_id,
                            'value': str(generate_fill_value(param_id)),
                            'timestamp': '',
                            'alert_flag': '0',
                            'created_by': default_created_by,
                            'archived': '0'
                        }
                        grouped[key].append(filled_record)

    # Nombre de cycles basé sur la série la plus longue
    max_records = max(len(records) for records in grouped.values()) if grouped else 0

    cycles = []
    for i in range(max_records):
        cycle = []
        for key, records in grouped.items():
            if i < len(records):
                cycle.append(records[i])
        if cycle:
            cycles.append(cycle)

    # print(f"[DEBUG] {len(cycles)} cycles créés")  # Masqué pour l'édition infinie
    return cycles


async def create_pool():
    """Initialise le pool de connexions asynchrones MySQL."""
    print(f"[DEBUG] Connexion: {DB_CONFIG['host']} / {DB_CONFIG['db']}")

    pool = await aiomysql.create_pool(
        host=DB_CONFIG['host'],
        user=DB_CONFIG['user'],
        password=DB_CONFIG['password'],
        db=DB_CONFIG['db'],
        autocommit=True,
        minsize=DB_POOL_MIN_SIZE,
        maxsize=DB_POOL_MAX_SIZE
    )
    print("[DEBUG] Pool créé")
    return pool


async def discover_valid_data(pool: aiomysql.Pool):
    """Récupère les IDs valides et les plages de valeurs en BDD pour la validation pré-insertion."""
    global VALID_PATIENT_IDS, VALID_PARAMETERS, VALID_USER_IDS, PARAMETER_RANGES

    print()
    print("=" * 60)
    print("DÉCOUVERTE DES DONNÉES VALIDES")
    print("=" * 60)

    async with pool.acquire() as conn:
        async with conn.cursor() as cur:
            # Vérification de l'existence des patients
            await cur.execute("SELECT id_patient FROM patients ORDER BY id_patient")
            patients = await cur.fetchall()
            VALID_PATIENT_IDS = [p[0] for p in patients]
            print(f"[INFO] {len(VALID_PATIENT_IDS)} patients: {VALID_PATIENT_IDS}")

            # Récupération des paramètres avec TOUS les seuils
            await cur.execute("""
                SELECT parameter_id, display_min, display_max,
                       normal_min, normal_max, critical_min, critical_max
                FROM parameter_reference
            """)
            params = await cur.fetchall()
            VALID_PARAMETERS = [p[0] for p in params]
            PARAMETER_RANGES = {
                p[0]: {
                    'dm': float(p[1]) if p[1] is not None else 0.0,
                    'dmx': float(p[2]) if p[2] is not None else 100.0,
                    'nm': float(p[3]) if p[3] is not None else None,
                    'nmx': float(p[4]) if p[4] is not None else None,
                    'cm': float(p[5]) if p[5] is not None else None,
                    'cmx': float(p[6]) if p[6] is not None else None
                } for p in params
            }
            print(f"[INFO] {len(VALID_PARAMETERS)} paramètres chargés avec seuils BDD")

            # Vérification des utilisateurs (auteurs des données)
            await cur.execute("SELECT id_user FROM users ORDER BY id_user")
            users = await cur.fetchall()
            VALID_USER_IDS = [u[0] for u in users]
            print(f"[INFO] {len(VALID_USER_IDS)} utilisateurs: {VALID_USER_IDS}")

    print()


def validate_record(record):
    """Vérifie l'intégrité référentielle avant d'envoyer la requête à MySQL."""
    try:
        patient_id = int(record['id_patient'])
        if patient_id not in VALID_PATIENT_IDS:
            return False, f"Patient {patient_id} non trouvé"
    except (KeyError, ValueError) as e:
        return False, f"id_patient invalide: {e}"

    param_id = record.get('parameter_id', '')
    if param_id not in VALID_PARAMETERS:
        return False, f"Paramètre '{param_id}' non trouvé"

    try:
        created_by = int(record.get('created_by', 0))
        if created_by not in VALID_USER_IDS:
            return False, f"Utilisateur {created_by} non trouvé"
    except ValueError as e:
        return False, f"created_by invalide: {e}"

    return True, ""


async def insert_record(pool, record, timestamp):
    """Exécute une seule insertion dans la base de données."""
    is_valid, error_msg = validate_record(record)
    if not is_valid:
        return False, error_msg

    columns_str = ', '.join(DB_COLUMNS)
    placeholders = ', '.join(['%s'] * len(DB_COLUMNS))

    query = f"INSERT INTO {DB_TABLE_NAME} ({columns_str}) VALUES ({placeholders})"

    # Préparation du tuple de valeurs avec conversion de types
    values = (
        int(record['id_patient']),
        record['parameter_id'],
        float(record['value']),
        timestamp,
        int(record.get('alert_flag', 0)),
        int(record['created_by']),
        int(record.get('archived', 0))
    )

    try:
        async with pool.acquire() as conn:
            async with conn.cursor() as cur:
                await cur.execute(query, values)
                return True, f"P{record['id_patient']}-{record['parameter_id']}={record['value']}"
    except Exception as e:
        return False, f"Erreur: {e}"


async def insert_cycle(pool, cycle, cycle_num):
    """Insère tous les enregistrements d'un cycle en parallèle via asyncio.gather."""
    timestamp = datetime.now().strftime(DATETIME_FORMAT)

    # Création des coroutines pour chaque record du cycle
    tasks = [insert_record(pool, record, timestamp) for record in cycle]

    # Lancement simultané des insertions du cycle
    results = await asyncio.gather(*tasks, return_exceptions=True)

    success_count = 0
    skip_count = 0
    error_count = 0

    for i, result in enumerate(results):
        if isinstance(result, Exception):
            error_count += 1
        elif result[0]:
            success_count += 1
        else:
            # Distinction entre erreur MySQL et skip de validation
            if "non trouvé" in result[1] or "invalide" in result[1]:
                skip_count += 1
            else:
                error_count += 1

    return success_count, skip_count, error_count


def print_progress_bar(current, total, success, skip, error, elapsed):
    """Affiche une barre de progression avec statistiques et estimation du temps restant."""
    bar_length = 40
    progress = current / total if total > 0 else 0
    filled = int(bar_length * progress)
    bar = '█' * filled + '░' * (bar_length - filled)

    # Calcul de l'ETA (Estimated Time of Arrival)
    eta = (elapsed / current) * (total - current) if current > 0 else 0

    print(f"\r[{bar}] {current}/{total} ({progress*100:.1f}%) | "
          f"✓{success} ⊘{skip} ✗{error} | "
          f"Temps: {elapsed:.0f}s | ETA: {eta:.0f}s", end='', flush=True)


async def insert_all_async(data, delay: float = INSERT_DELAY_SECONDS):
    """Boucle d'insertion pour un jeu de données fini (test ou CSV)."""
    pool = await create_pool()
    await discover_valid_data(pool)

    if data is None:
        pool.close()
        await pool.wait_closed()
        return

    cycles = group_data_by_cycle(data)
    if not cycles:
        print("[ERROR] Aucun cycle")
        pool.close()
        await pool.wait_closed()
        return

    total_records = sum(len(cycle) for cycle in cycles)

    print(f"[DEBUG] {len(cycles)} cycles, {total_records} enregistrements")
    print(f"[DEBUG] Délai entre cycles: {delay}s")
    print(f"[DEBUG] Temps estimé: {len(cycles) * delay:.0f}s")
    print("-" * 60)

    total_success = 0
    total_skip = 0
    total_error = 0
    start_time = datetime.now()
    import time
    next_cycle_target = time.perf_counter()

    try:
        for i, cycle in enumerate(cycles, start=1):
            next_cycle_target += delay
            success, skip, error = await insert_cycle(pool, cycle, i)
            total_success += success
            total_skip += skip
            total_error += error

            elapsed = (datetime.now() - start_time).total_seconds()
            print_progress_bar(i, len(cycles), total_success, total_skip, total_error, elapsed)

            if i < len(cycles):
                sleep_time = next_cycle_target - time.perf_counter()
                if sleep_time > 0:
                    await asyncio.sleep(sleep_time)
                else:
                    if sleep_time < -0.5:
                        print(f"\n[WARNING] Retard important : {abs(sleep_time):.2f}s (La base est trop lente)")
        print()
    finally:
        pool.close()
        await pool.wait_closed()

    end_time = datetime.now()
    duration = (end_time - start_time).total_seconds()

    print("-" * 60)
    print(f"[RESULT] Cycles: {len(cycles)}")
    print(f"[RESULT] Succès: {total_success}")
    print(f"[RESULT] Ignorés: {total_skip}")
    print(f"[RESULT] Erreurs: {total_error}")
    print(f"[RESULT] Durée: {duration:.2f}s")
    if duration > 0:
        print(f"[RESULT] Vitesse: {total_success / duration:.1f} insert/s")


async def random_producer(batch_queue: asyncio.Queue, num_cycles=100):
    """Génère les données de façon asynchrone pour ne jamais bloquer l'insertion."""
    init_generation_states()
    while True:
        data = await asyncio.to_thread(generate_batch, num_cycles)
        cycles = group_data_by_cycle(data)
        await batch_queue.put(cycles)


async def insert_infinite_async(delay: float = INSERT_DELAY_SECONDS):
    """Boucle d'insertion infinie : génère et insère simultanément."""
    pool = await create_pool()
    await discover_valid_data(pool)

    if not VALID_PATIENT_IDS or not VALID_PARAMETERS:
        print("[ERROR] Données BDD non découvertes")
        pool.close()
        await pool.wait_closed()
        return

    # maxsize=2 correspond à l'énoncé : génère 2 en avance (dont 1 en attente dans la queue)
    batch_queue = asyncio.Queue(maxsize=2)
    # Lance le producteur en tâche de fond
    producer_task = asyncio.create_task(random_producer(batch_queue, REDEFINITION_INTERVAL))

    print(f"[DEBUG] Démarrage de la génération et insertion infinie en parallèle.")
    print(f"[DEBUG] Délai entre cycles: {delay}s")
    print("-" * 60)

    total_success = 0
    total_skip = 0
    total_error = 0
    start_time = datetime.now()
    import time
    next_cycle_target = time.perf_counter()
    global_cycle_idx = 0

    try:
        while True:
            cycles = await batch_queue.get()

            for cycle in cycles:
                global_cycle_idx += 1
                next_cycle_target += delay

                success, skip, error = await insert_cycle(pool, cycle, global_cycle_idx)
                total_success += success
                total_skip += skip
                total_error += error

                elapsed = (datetime.now() - start_time).total_seconds()
                print(f"\r[INSERT] Cycle {global_cycle_idx} | ✓{total_success} ⊘{total_skip} ✗{total_error} | Temps: {elapsed:.0f}s ", end='', flush=True)

                sleep_time = next_cycle_target - time.perf_counter()
                if sleep_time > 0:
                    await asyncio.sleep(sleep_time)
                else:
                    if sleep_time < -0.5:
                        print(f"\n[WARNING] Retard important : {abs(sleep_time):.2f}s (La base est trop lente)")
    except asyncio.CancelledError:
        print("\n[INFO] Arrêt de la boucle infinie demandé.")
    finally:
        producer_task.cancel()
        pool.close()
        await pool.wait_closed()


async def main():
    """Point d'entrée du script : vérifie l'environnement et lance le process."""
    print("=" * 60)
    print("INSERTION ASYNCHRONE PARALLÈLE INFINIE")
    print("=" * 60)
    print(f"[DEBUG] Démarrage: {datetime.now()}")
    print()

    # Vérification des variables d'environnement
    if not all(DB_CONFIG.values()):
        print("[ERROR] Configuration BDD incomplète!")
        return

    # Chargement des données CSV ou génération aléatoire
    if os.path.exists(CSV_FILE):
        data = load_csv_data(CSV_FILE)
        if not data:
            print("[ERROR] Aucune donnée dans le CSV")
            return
        await insert_all_async(data, delay=INSERT_DELAY_SECONDS)
    else:
        print(f"[INFO] Fichier CSV non trouvé: {CSV_FILE}")
        print(f"[INFO] Lancement de la génération aléatoire en continu.")
        await insert_infinite_async(delay=INSERT_DELAY_SECONDS)

    print()
    print(f"[DEBUG] Fin: {datetime.now()}")
    print("=" * 60)


if __name__ == '__main__':
    asyncio.run(main())
