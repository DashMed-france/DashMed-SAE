import random
from datetime import datetime, timedelta

# Configuration
OUTPUT_FILE = '/Users/pgp_667/Developer/GitHub/DashMed-SAE/database/dashmed_patient_data.sql'
START_DATE = datetime(2025, 12, 1, 8, 0, 0)
END_DATE = datetime(2025, 12, 27, 20, 0, 0)
INTERVAL_HOURS = 4

# Patient Definitions (Based on dashmed_inserts.sql)
# 1-12: ICU (En réanimation) - Full monitoring
# 13-24: Discharged (Sorti) - Historical data only (e.g., first week)
# 25-30: Deceased (Décédé) - Data until death (random day)

PATIENTS = []

# ICU Patients (1-12)
for i in range(1, 13):
    PATIENTS.append({'id': i, 'type': 'ICU', 'start': START_DATE, 'end': END_DATE})

# Discharged Patients (13-24) - Simulate they were there for 5-10 days then left
for i in range(13, 25):
    days_stayed = random.randint(5, 15)
    end_dt = START_DATE + timedelta(days=days_stayed)
    if end_dt > END_DATE: end_dt = END_DATE
    PATIENTS.append({'id': i, 'type': 'Standard', 'start': START_DATE, 'end': end_dt})

# Deceased Patients (25-30) - Simulate they died after 3-20 days
for i in range(25, 31):
    days_alive = random.randint(3, 20)
    end_dt = START_DATE + timedelta(days=days_alive)
    if end_dt > END_DATE: end_dt = END_DATE
    PATIENTS.append({'id': i, 'type': 'ICU_Critical', 'start': START_DATE, 'end': end_dt})

# Parameters Definition
PARAMS = {
    'SpO2': {'min': 95, 'max': 100, 'unit': '%', 'chart': 'line'},
    'FC': {'min': 60, 'max': 100, 'unit': 'bpm', 'chart': 'line'},
    'PAS': {'min': 110, 'max': 140, 'unit': 'mmHg', 'chart': 'line'},
    'PAD': {'min': 60, 'max': 90, 'unit': 'mmHg', 'chart': 'line'},
    'PAM': {'min': 75, 'max': 105, 'unit': 'mmHg', 'chart': 'line'}, # Often calculated
    'FR': {'min': 12, 'max': 20, 'unit': 'cpm', 'chart': 'line'},
    'T°': {'min': 36.5, 'max': 37.5, 'unit': '°C', 'chart': 'line'},
    
    # ICU / Advanced
    'PEP': {'min': 5, 'max': 10, 'unit': 'cmH2O', 'chart': 'line'},
    'EtCO2': {'min': 35, 'max': 45, 'unit': 'mmHg', 'chart': 'line'},
    'FiO2': {'min': 21, 'max': 40, 'unit': '%', 'chart': 'line'},
    'Vt': {'min': 400, 'max': 500, 'unit': 'mL', 'chart': 'line'},
    'Pcrête': {'min': 15, 'max': 25, 'unit': 'cmH2O', 'chart': 'line'},
    'Pplat': {'min': 12, 'max': 20, 'unit': 'cmH2O', 'chart': 'line'},
    'PVC': {'min': 8, 'max': 12, 'unit': 'mmHg', 'chart': 'line'},
    
    # Metabolic / Neuro
    'Diurèse': {'min': 50, 'max': 150, 'unit': 'mL/h', 'chart': 'bar'},
    'Glycémie': {'min': 0.8, 'max': 1.2, 'unit': 'g/L', 'chart': 'line'},
    'Lactates': {'min': 0.5, 'max': 1.5, 'unit': 'mmol/L', 'chart': 'line'},
    'GCS': {'min': 15, 'max': 15, 'unit': '/15', 'chart': 'value'},
}

# Values Generator
def get_value(param, patient_type, previous_val=None):
    base_min = PARAMS[param]['min']
    base_max = PARAMS[param]['max']
    
    # Adjust based on patient condition
    if patient_type == 'ICU_Critical':
        if param == 'SpO2': base_min, base_max = 85, 92
        if param == 'FC': base_min, base_max = 100, 130
        if param == 'PAS': base_min, base_max = 80, 100
        if param == 'Lactates': base_min, base_max = 2.0, 5.0
        if param == 'GCS': base_min, base_max = 3, 8
    elif patient_type == 'ICU':
        if param == 'SpO2': base_min, base_max = 92, 98
        if param == 'GCS': base_min, base_max = 9, 14
    
    # Random walk or plain random
    if previous_val is None:
        val = random.uniform(base_min, base_max)
    else:
        change = (base_max - base_min) * 0.1
        val = previous_val + random.uniform(-change, change)
        val = max(base_min - (base_max-base_min)*0.2, min(base_max + (base_max-base_min)*0.2, val))
    
    # Rounding
    if param in ['FC', 'PEP', 'Vt', 'GCS', 'Diurèse', 'Pcrête', 'Pplat']:
        return int(round(val))
    else:
        return round(val, 2)

# Generate Data
sql_statements = []

head = """SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
START TRANSACTION;

INSERT INTO `patient_data` (`id_patient`,`parameter_id`,`value`,`timestamp`,`alert_flag`,`created_by`,`archived`) VALUES
"""

rows = []

for p in PATIENTS:
    current_time = p['start']
    last_values = {}
    
    while current_time <= p['end']:
        # Determine strict set of parameters based on type
        current_params = ['SpO2', 'FC', 'PAS', 'PAD', 'T°', 'FR']
        
        if p['type'] in ['ICU', 'ICU_Critical']:
             current_params += ['PAM', 'PEP', 'EtCO2', 'FiO2', 'Vt', 'Pcrête', 'Pplat', 'PVC', 'Diurèse', 'Glycémie', 'Lactates', 'GCS']
        else:
             # Standard ward / Discharged
             current_params += ['Diurèse', 'Glycémie']

        for param in current_params:
            val = get_value(param, p['type'], last_values.get(param))
            last_values[param] = val
            
            # Logic consistencies
            if param == 'PAM' and 'PAS' in last_values and 'PAD' in last_values:
                # PAM ~= (PAS + 2*PAD) / 3
                calc_pam = (last_values['PAS'] + 2 * last_values['PAD']) / 3
                val = round(calc_pam + random.uniform(-2, 2), 2)
                last_values['PAM'] = val
            
            # Alert Flag (Basic logic)
            alert = 0
            if param == 'SpO2' and val < 90: alert = 1
            if param == 'FC' and (val > 120 or val < 50): alert = 1
            if param == 'GCS' and val < 9: alert = 1
            
            timestamp_str = current_time.strftime('%Y-%m-%d %H:%M:%S')
            rows.append(f"({p['id']}, '{param}', {val}, '{timestamp_str}', {alert}, 1, 0)")
        
        current_time += timedelta(hours=INTERVAL_HOURS)

CHUNK_SIZE = 2000
with open(OUTPUT_FILE, 'w') as f:
    f.write(head)
    
    for i in range(0, len(rows), CHUNK_SIZE):
        chunk = rows[i:i+CHUNK_SIZE]
        if i > 0:
            f.write("INSERT INTO `patient_data` (`id_patient`,`parameter_id`,`value`,`timestamp`,`alert_flag`,`created_by`,`archived`) VALUES\n")
        
        f.write(",\n".join(chunk))
        f.write(";\n\n")

    f.write("COMMIT;\n")

print(f"Generated {len(rows)} data points to {OUTPUT_FILE}")
