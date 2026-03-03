import re

with open('database/dashmed_patient_data.sql', 'r') as f:
    lines = f.readlines()

for i in range(len(lines)):
    if 'INSERT INTO' in lines[i]:
        # go back and replace the last comma with a semicolon
        for j in range(i-1, -1, -1):
            if lines[j].strip():
                if lines[j].strip().endswith(','):
                    lines[j] = lines[j].rstrip()[:-1] + ';\n'
                break

if lines[-1].strip().endswith(','):
    lines[-1] = lines[-1].rstrip()[:-1] + ';\n'

with open('database/dashmed_patient_data.sql', 'w') as f:
    f.writelines(lines)
