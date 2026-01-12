# DashMed - Script d'insertion de données patient

Script d'insertion asynchrone et parallèle de données patient dans une base de données MySQL.

## Fonctionnalités

- **Insertion asynchrone** : Utilisation de `aiomysql` pour des insertions non-bloquantes
- **Parallélisation par cycle** : Toutes les mesures d'un même instant sont insérées simultanément
- **Validation pré-insertion** : Vérification des clés étrangères (patients, paramètres, utilisateurs) avant insertion
- **Génération automatique** : Si le CSV n'existe pas, génère des données aléatoires pour tous les patients/paramètres
- **Remplissage automatique** : Génération de valeurs pour les indicateurs absents du CSV (optionnel)
- **Barre de progression** : Suivi en temps réel avec estimation du temps restant

## Prérequis

- Python 3.8+
- MySQL/MariaDB
- Packages Python :
  ```bash
  pip install aiomysql python-dotenv
  ```

## Configuration

### Variables d'environnement

Créer un fichier `.env` à la racine du projet :

```env
DB_HOST=localhost
DB_USER=votre_utilisateur
DB_PASS=votre_mot_de_passe
DB_NAME=dashmed
```

### Paramètres du script

Dans `main.py`, vous pouvez ajuster :

| Paramètre | Description | Valeur par défaut |
|-----------|-------------|-------------------|
| `CSV_FILE` | Fichier source des données | `patient_datas.csv` |
| `INSERT_DELAY_SECONDS` | Délai entre chaque cycle | `1.0` |
| `DB_POOL_MIN_SIZE` | Taille min du pool de connexions | `5` |
| `DB_POOL_MAX_SIZE` | Taille max du pool de connexions | `20` |
| `GENERATED_CYCLES` | Nombre de cycles à générer si pas de CSV | `100` |
| `FILL_VALUES` | Remplir les indicateurs manquants | `True` |

## Format du CSV

Le fichier CSV doit contenir les colonnes suivantes :

```csv
"id_patient","parameter_id","value","timestamp","alert_flag","created_by","archived"
"1","FC","75.5","2025-12-01 00:00:00","0","1","0"
"1","SpO2","98.2","2025-12-01 00:00:00","0","1","0"
```

| Colonne | Type | Description |
|---------|------|-------------|
| `id_patient` | int | ID du patient (doit exister dans `patients`) |
| `parameter_id` | string | Type de mesure (doit exister dans `parameter_reference`) |
| `value` | float | Valeur de la mesure |
| `timestamp` | datetime | Date/heure de la mesure (ignoré, remplacé par l'heure actuelle) |
| `alert_flag` | int | Indicateur d'alerte (0/1) |
| `created_by` | int | ID de l'utilisateur (doit exister dans `users`) |
| `archived` | int | Statut d'archivage (0/1) |

## Génération aléatoire (pas de CSV)

Si le fichier CSV spécifié dans `CSV_FILE` n'existe pas, le script génère automatiquement des données aléatoires :

1. Se connecte à la BDD pour récupérer :
    - Liste des patients depuis `patients`
    - Liste des paramètres avec `display_min` et `display_max` depuis `parameter_reference`
    - Liste des utilisateurs depuis `users`
2. Génère `GENERATED_CYCLES` enregistrements pour chaque combinaison patient/paramètre
3. Les valeurs sont générées aléatoirement dans la plage `[display_min, display_max]`

**Exemple avec 3 patients, 12 paramètres et 100 cycles :**
- Total = 3 × 12 × 100 = 3600 enregistrements générés

## Fonctionnalité FILL_VALUES

Quand `FILL_VALUES = True` et qu'un CSV est fourni, le script :

1. Récupère la liste des paramètres valides depuis `parameter_reference`
2. Récupère les plages `display_min` et `display_max` pour chaque paramètre
3. Détecte les indicateurs absents du CSV mais présents dans la BDD
4. Génère automatiquement des valeurs aléatoires dans les plages définies

**Exemple :**
- CSV contient uniquement : `Compliance_m`
- BDD contient : `FC`, `SpO2`, `Compliance_m`, `Temperature`
- Le script génère des valeurs pour : `FC`, `SpO2`, `Temperature`

Les valeurs sont générées dans la plage `[display_min, display_max]` de chaque paramètre.

## Utilisation

```bash
python main.py
```

### Sortie exemple

```
============================================================
INSERTION ASYNCHRONE PARALLÈLE
============================================================
[DEBUG] Démarrage: 2026-01-12 17:45:00

[DEBUG] Chargement du fichier CSV: patient_data.csv
[DEBUG] 8303261 lignes chargées

============================================================
DÉCOUVERTE DES DONNÉES VALIDES
============================================================
[INFO] 3 patients: [1, 2, 3]
[INFO] 12 paramètres: ['FC', 'SpO2', 'FR', ...]
[INFO] Plages de valeurs: {'FC': (40.0, 180.0), 'SpO2': (80.0, 100.0), ...}
[INFO] 5 utilisateurs: [1, 2, 3, 4, 5]

[INFO] FILL_VALUES actif: génération de valeurs pour 11 indicateurs manquants
[INFO] Indicateurs à remplir: {'FC', 'SpO2', 'FR', ...}

[DEBUG] 28800 cycles, 345600 enregistrements
[DEBUG] Délai entre cycles: 1.0s
[DEBUG] Temps estimé: 28800s
------------------------------------------------------------
[████████████████████████████████████████] 28800/28800 (100.0%) | ✓345000 ⊘500 ✗100 | Temps: 28800s | ETA: 0s

------------------------------------------------------------
[RESULT] Cycles: 28800
[RESULT] Succès: 345000
[RESULT] Ignorés: 500
[RESULT] Erreurs: 100
[RESULT] Durée: 28800.00s
[RESULT] Vitesse: 12.0 insert/s
```

## Structure de la base de données requise

### Table `patients`
```sql
CREATE TABLE patients (
    id_patient INT PRIMARY KEY,
    ...
);
```

### Table `parameter_reference`
```sql
CREATE TABLE parameter_reference (
    parameter_id VARCHAR(50) PRIMARY KEY,
    display_min DECIMAL(10,2),
    display_max DECIMAL(10,2),
    ...
);
```

### Table `users`
```sql
CREATE TABLE users (
    id_user INT PRIMARY KEY,
    ...
);
```

### Table `patient_data`
```sql
CREATE TABLE patient_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_patient INT,
    parameter_id VARCHAR(50),
    value DECIMAL(10,2),
    timestamp DATETIME,
    alert_flag TINYINT DEFAULT 0,
    created_by INT,
    archived TINYINT DEFAULT 0,
    FOREIGN KEY (id_patient) REFERENCES patients(id_patient),
    FOREIGN KEY (parameter_id) REFERENCES parameter_reference(parameter_id),
    FOREIGN KEY (created_by) REFERENCES users(id_user)
);
```

## Licence

Projet interne DashMed.
