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
GENERATED_CYCLES = 100

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
PARAMETER_RANGES = {}  # {parameter_id: (display_min, display_max)}

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


def generate_random_data(num_cycles: int = GENERATED_CYCLES) -> list:
    """
    Génère des données aléatoires pour tous les patients et tous les paramètres.
    Utilisé quand le fichier CSV n'existe pas.
    """
    if not VALID_PATIENT_IDS or not VALID_PARAMETERS:
        print("[ERROR] Données BDD non découvertes")
        return []

    print(f"[INFO] Génération aléatoire: {len(VALID_PATIENT_IDS)} patients x {len(VALID_PARAMETERS)} paramètres x {num_cycles} cycles")

    data = []
    default_created_by = str(VALID_USER_IDS[0]) if VALID_USER_IDS else '1'

    for patient_id in VALID_PATIENT_IDS:
        for param_id in VALID_PARAMETERS:
            for _ in range(num_cycles):
                record = {
                    'id_patient': str(patient_id),
                    'parameter_id': param_id,
                    'value': str(generate_fill_value(param_id)),
                    'timestamp': '',
                    'alert_flag': '0',
                    'created_by': default_created_by,
                    'archived': '0'
                }
                data.append(record)

    print(f"[INFO] {len(data)} enregistrements générés")
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

    print(f"[DEBUG] {len(cycles)} cycles créés")
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

            # Récupération des paramètres avec leurs plages d'affichage
            await cur.execute("SELECT parameter_id, display_min, display_max FROM parameter_reference")
            params = await cur.fetchall()
            VALID_PARAMETERS = [p[0] for p in params]
            PARAMETER_RANGES = {p[0]: (float(p[1]), float(p[2])) for p in params if p[1] is not None and p[2] is not None}
            print(f"[INFO] {len(VALID_PARAMETERS)} paramètres: {VALID_PARAMETERS}")
            print(f"[INFO] Plages de valeurs: {PARAMETER_RANGES}")

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
    """Boucle principale qui gère le déroulement des cycles et le pool de connexions."""
    pool = await create_pool()
    await discover_valid_data(pool)

    # Si pas de données CSV, générer aléatoirement
    if data is None:
        print()
        print("[INFO] Aucun CSV fourni - Génération aléatoire des données")
        data = generate_random_data(GENERATED_CYCLES)
        if not data:
            print("[ERROR] Échec de la génération")
            pool.close()
            await pool.wait_closed()
            return

    # Organisation des données en cycles (après découverte des paramètres valides)
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

    try:
        for i, cycle in enumerate(cycles, start=1):
            # Exécution parallèle des insertions du cycle
            success, skip, error = await insert_cycle(pool, cycle, i)
            total_success += success
            total_skip += skip
            total_error += error

            # Mise à jour de l'affichage
            elapsed = (datetime.now() - start_time).total_seconds()
            print_progress_bar(i, len(cycles), total_success, total_skip, total_error, elapsed)

            # Pause entre les cycles pour simuler le temps réel
            if i < len(cycles):
                await asyncio.sleep(delay)

        print()

    finally:
        pool.close()
        await pool.wait_closed()

    end_time = datetime.now()
    duration = (end_time - start_time).total_seconds()

    # Statistiques finales
    print("-" * 60)
    print(f"[RESULT] Cycles: {len(cycles)}")
    print(f"[RESULT] Succès: {total_success}")
    print(f"[RESULT] Ignorés: {total_skip}")
    print(f"[RESULT] Erreurs: {total_error}")
    print(f"[RESULT] Durée: {duration:.2f}s")
    if duration > 0:
        print(f"[RESULT] Vitesse: {total_success / duration:.1f} insert/s")


async def main():
    """Point d'entrée du script : vérifie l'environnement et lance le process."""
    print("=" * 60)
    print("INSERTION ASYNCHRONE PARALLÈLE")
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
    else:
        print(f"[INFO] Fichier non trouvé: {CSV_FILE}")
        print(f"[INFO] Les données seront générées aléatoirement ({GENERATED_CYCLES} cycles)")
        data = None

    # Lancement du traitement asynchrone
    await insert_all_async(data, delay=INSERT_DELAY_SECONDS)

    print()
    print(f"[DEBUG] Fin: {datetime.now()}")
    print("=" * 60)


if __name__ == '__main__':
    asyncio.run(main())
