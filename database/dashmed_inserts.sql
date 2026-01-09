-- dashmed_inserts.sql
-- Inserts de test (volumétrie large) pour DashMed
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
START TRANSACTION;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `patient_data`;
TRUNCATE TABLE `consultations`;
TRUNCATE TABLE `patients`;
TRUNCATE TABLE `users`;
TRUNCATE TABLE `professions`;
TRUNCATE TABLE `parameter_reference`;
TRUNCATE TABLE `chart_types`;
TRUNCATE TABLE `parameter_chart_allowed`;
TRUNCATE TABLE `user_parameter_order`;
TRUNCATE TABLE `user_parameter_chart_pref`;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO `professions` (`id_profession`, `label_profession`) VALUES
  (1, 'Médecin urgentiste'),
  (2, 'Anesthésiste-réanimateur'),
  (3, 'Chirurgien général'),
  (4, 'Chirurgien orthopédique'),
  (5, 'Cardiologue'),
  (6, 'Pneumologue'),
  (7, 'Neurologue'),
  (8, 'Gastro-entérologue'),
  (9, 'Néphrologue'),
  (10, 'Gynécologue-obstétricien'),
  (11, 'Pédiatre'),
  (12, 'Oncologue'),
  (13, 'Radiologue'),
  (14, 'Médecin interniste'),
  (15, 'Médecin généraliste'),
  (16, 'Infectiologue'),
  (17, 'Hématologue'),
  (18, 'Psychiatre'),
  (19, 'Médecin du travail'),
  (20, 'Proctologue');

INSERT INTO `users` (`first_name`, `last_name`, `email`, `password`, `admin_status`, `birth_date`, `id_profession`) VALUES
  ('Jules', 'RIBBE', 'julesribbe@gmail.com', '$2y$12$9N1omkw4wQGTXbJQTN.5Qe2vvfVgik8s5iYKwBYJ5DrBkqIFf8YCy', 1, '2005-12-07', 20),
  ('Maxence', 'Torchin', 'max148t@gmail.com', '$2y$10$a2E/KW0SqCPMP1MlBFHilepKpfQHid.4R8YIZs4g4tZMicPTYYfTC', 1, '2006-12-04', 20),
  ('Sophie','Bernard','sophie.bernard@med.example','$2y$10$dummyhash02xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',0,'1979-07-11',3),
  ('Hugo','Martin','hugo.martin@dashmed.fr','$2y$10$dummyhash03xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',0,'1981-10-16',4),
  ('Nina','Petit','nina.petit@hospital.local','$2y$10$dummyhash04xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',0,'1983-01-21',5),
  ('Camille','Dubois','camille.dubois@med.example','$2y$10$dummyhash05xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',0,'1985-04-26',6),
  ('Lucas','Moreau','lucas.moreau@dashmed.fr','$2y$10$dummyhash06xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',0,'1987-07-03',7),
  ('Emma','Laurent','emma.laurent@hospital.local','$2y$10$dummyhash07xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',0,'1989-10-08',8),
  ('Lina','Simon','lina.simon@med.example','$2y$10$dummyhash08xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',0,'1991-01-13',9),
  ('Adam','Michel','adam.michel@dashmed.fr','$2y$10$dummyhash09xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',0,'1993-04-18',10),
  ('Tom','Lefevre','tom.lefevre@hospital.local','$2y$10$dummyhash10xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',0,'1995-07-23',11),
  ('Ines','Garcia','ines.garcia@med.example','$2y$10$dummyhash11xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',0,'1997-10-28',12),
  ('Noah','Roux','noah.roux@dashmed.fr','$2y$10$dummyhash12xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',0,'1999-01-05',13),
  ('Lea','Fournier','lea.fournier@hospital.local','$2y$10$dummyhash13xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',0,'2001-04-10',14),
  ('Louis','Girard','louis.girard@med.example','$2y$10$dummyhash14xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',0,'1975-07-15',15);

INSERT INTO `patients` (`id_patient`, `first_name`, `last_name`, `email`, `birth_date`, `weight`, `height`, `gender`, `status`, `description`, `room_id`) VALUES
  (1, 'Marie','Dupont','marie.dupont1@patient.dashmed.fr','1945-01-01',50.00,150.00,'F','En réanimation','Accident de la route',1),
  (2, 'Jean','Durand','jean.durand2@patient.dashmed.fr','1948-08-12',52.30,151.70,'M','En réanimation','Insuffisance respiratoire aiguë',2),
  (3, 'Lucie','Martin','lucie.martin3@patient.dashmed.fr','1951-03-23',54.60,153.40,'F','En réanimation','Crise d''asthme sévère',3),
  (4, 'Olivier','Petit','olivier.petit4@patient.dashmed.fr','1954-10-06',56.90,155.10,'M','En réanimation','Traumatisme crânien',4),
  (5, 'Sarah','Nguyen','sarah.nguyen5@patient.dashmed.fr','1957-05-17',59.20,156.80,'F','En réanimation','Pneumonie sévère',5),
  (6, 'Paul','Robert','paul.robert6@patient.dashmed.fr','1960-12-28',61.50,158.50,'M','En réanimation','Sepsis',6),
  (7, 'Chloe','Richard','chloe.richard7@patient.dashmed.fr','1963-07-11',63.80,160.20,'F','En réanimation','Post-opératoire compliqué',7),
  (8, 'Antoine','Morel','antoine.morel8@patient.dashmed.fr','1966-02-22',66.10,161.90,'M','En réanimation','Détresse respiratoire',8),
  (9, 'Manon','Leroy','manon.leroy9@patient.dashmed.fr','1969-09-05',68.40,163.60,'F','En réanimation','Douleurs thoraciques',9),
  (10, 'Thomas','Roux','thomas.roux10@patient.dashmed.fr','1972-04-16',70.70,165.30,'M','En réanimation','Décompensation cardiaque',10),
  (11, 'Clara','Fournier','clara.fournier11@patient.dashmed.fr','1975-11-27',73.00,167.00,'F','En réanimation','Accident de la route',11),
  (12, 'Nathan','Girard','nathan.girard12@patient.dashmed.fr','1978-06-10',75.30,168.70,'M','En réanimation','Insuffisance respiratoire aiguë',12),
  (13, 'Julie','Andre','julie.andre13@patient.dashmed.fr','1981-01-21',77.60,170.40,'F','Sorti','Crise d''asthme sévère',NULL),
  (14, 'Arthur','Mercier','arthur.mercier14@patient.dashmed.fr','1984-08-04',79.90,172.10,'M','Sorti','Traumatisme crânien',NULL),
  (15, 'Eva','Blanc','eva.blanc15@patient.dashmed.fr','1987-03-15',82.20,173.80,'F','Sorti','Pneumonie sévère',NULL),
  (16, 'Adrien','Garnier','adrien.garnier16@patient.dashmed.fr','1990-10-26',84.50,175.50,'M','Sorti','Sepsis',NULL),
  (17, 'Lola','Chevalier','lola.chevalier17@patient.dashmed.fr','1993-05-09',86.80,177.20,'F','Sorti','Post-opératoire compliqué',NULL),
  (18, 'Kevin','Francois','kevin.francois18@patient.dashmed.fr','1996-12-20',89.10,178.90,'M','Sorti','Détresse respiratoire',NULL),
  (19, 'Nora','Legrand','nora.legrand19@patient.dashmed.fr','1999-07-03',91.40,180.60,'F','Sorti','Douleurs thoraciques',NULL),
  (20, 'Yanis','Gauthier','yanis.gauthier20@patient.dashmed.fr','2002-02-14',93.70,182.30,'M','Sorti','Décompensation cardiaque',NULL),
  (21, 'Iris','Perrin','iris.perrin21@patient.dashmed.fr','1945-09-25',96.00,184.00,'F','Sorti','Accident de la route',NULL),
  (22, 'Gael','Clement','gael.clement22@patient.dashmed.fr','1948-04-08',98.30,150.70,'M','Sorti','Insuffisance respiratoire aiguë',NULL),
  (23, 'Mael','Morin','mael.morin23@patient.dashmed.fr','1951-11-19',100.60,152.40,'F','Sorti','Crise d''asthme sévère',NULL),
  (24, 'Zoe','Nicolas','zoe.nicolas24@patient.dashmed.fr','1954-06-02',102.90,154.10,'M','Sorti','Traumatisme crânien',NULL),
  (25, 'Theo','Riviere','theo.riviere25@patient.dashmed.fr','1957-01-13',50.20,155.80,'F','Décédé','Pneumonie sévère',NULL),
  (26, 'Mila','Sanchez','mila.sanchez26@patient.dashmed.fr','1960-08-24',52.50,157.50,'M','Décédé','Sepsis',NULL),
  (27, 'Jade','Ramirez','jade.ramirez27@patient.dashmed.fr','1963-03-07',54.80,159.20,'F','Décédé','Post-opératoire compliqué',NULL),
  (28, 'Enzo','Bertrand','enzo.bertrand28@patient.dashmed.fr','1966-10-18',57.10,160.90,'M','Décédé','Détresse respiratoire',NULL),
  (29, 'Louise','Hernandez','louise.hernandez29@patient.dashmed.fr','1969-05-01',59.40,162.60,'F','Décédé','Douleurs thoraciques',NULL),
  (30, 'Rayan','Caron','rayan.caron30@patient.dashmed.fr','1972-12-12',61.70,164.30,'M','Décédé','Décompensation cardiaque',NULL);

INSERT INTO `parameter_reference`
(`parameter_id`, `display_name`, `category`, `unit`, `default_chart`, `description`,
 `normal_min`, `normal_max`, `critical_min`, `critical_max`, `display_min`, `display_max`)
VALUES
-- Ventilation (monitoring / ventilateur / gaz du sang)
('FR_m', 'Fréquence respiratoire mesurée', 'Ventilation', 'c/min', 'line',
 'Nombre de cycles respiratoires mesurés par minute',
 10.00, 30.00, 5.00, 40.00, 0.00, 60.00),

('FR_r', 'Fréquence respiratoire réglée', 'Ventilation', 'c/min', 'line',
 'Réglage de la fréquence respiratoire sur le ventilateur',
 10.00, 30.00, NULL, NULL, 0.00, 60.00),

('Vt_r', 'Volume courant réglé', 'Ventilation', 'mL', 'line',
 'Volume d’air délivré à chaque cycle ventilatoire (réglage ventilateur)',
 400.00, 700.00, 200.00, 900.00, 0.00, 1200.00),

('VT_m', 'Volume courant mesuré', 'Ventilation', 'mL', 'line',
 'Volume inspiré mesuré (volume courant réel)',
 400.00, 700.00, 200.00, 900.00, 0.00, 1200.00),

('VM_m', 'Volume minute mesuré', 'Ventilation', 'L/min', 'line',
 'Volume total ventilé par minute (VT x FR)',
 5.00, 10.00, 3.00, 15.00, 0.00, 25.00),

('PEP_r', 'PEP réglée', 'Ventilation', 'cmH2O', 'line',
 'Pression expiratoire positive (PEP/PEEP) réglée sur le ventilateur',
 5.00, 10.00, 0.00, 15.00, 0.00, 25.00),

('PEP_m', 'PEP mesurée', 'Ventilation', 'cmH2O', 'line',
 'Pression expiratoire positive (PEP/PEEP) mesurée',
 5.00, 10.00, 0.00, 15.00, 0.00, 25.00),

('FiO2_r', 'FiO2 réglée', 'Ventilation', '%', 'line',
 'Fraction inspirée en oxygène (FiO2) réglée sur le ventilateur',
 21.00, 100.00, NULL, NULL, 0.00, 100.00),

('FiO2_m', 'FiO2 mesurée', 'Ventilation', '%', 'line',
 'Fraction inspirée en oxygène (FiO2) mesurée',
 21.00, 100.00, NULL, NULL, 0.00, 100.00),

('PaO2', 'Pression artérielle en O2 (PaO2)', 'Ventilation', 'mmHg', 'line',
 'Oxygénation mesurée sur gaz du sang',
 80.00, 100.00, 60.00, 150.00, 0.00, 300.00),

('SpO2_m', 'Saturation pulsée en O2 (SpO2)', 'Ventilation', '%', 'line',
 'Saturation en oxygène mesurée par oxymétrie de pouls',
 90.00, 100.00, 85.00, 100.00, 50.00, 100.00),

('PaCO2', 'Pression artérielle en CO2 (PaCO2)', 'Ventilation', 'mmHg', 'line',
 'Ventilation alvéolaire / élimination du CO2 (gaz du sang)',
 32.00, 45.00, 25.00, 60.00, 0.00, 100.00),

('PaO2_FiO2', 'Rapport PaO2/FiO2', 'Ventilation', '-', 'line',
 'Indice d’oxygénation (baisse = insuffisance respiratoire)',
 300.00, 500.00, 0.00, 200.00, 0.00, 600.00),

('Pplat_m', 'Pression de plateau (Pplat)', 'Ventilation', 'cmH2O', 'line',
 'Pression de plateau (estimation de la pression alvéolaire)',
 NULL, 30.00, NULL, 35.00, 0.00, 40.00),

('Ppic_m', 'Pression de pic (Ppeak)', 'Ventilation', 'cmH2O', 'line',
 'Pression maximale des voies aériennes (pic inspiratoire)',
 NULL, 40.00, NULL, 50.00, 0.00, 60.00),

('Compliance_m', 'Compliance pulmonaire mesurée', 'Ventilation', 'mL/cmH2O', 'line',
 'Élasticité du système respiratoire (plus bas = poumon plus rigide)',
 30.00, 80.00, 10.00, 100.00, 0.00, 120.00),

-- Hémodynamique & Métabolique
('FC_m', 'Fréquence cardiaque', 'Hémodynamique & Métabolique', 'bpm', 'line',
 'Rythme cardiaque',
 60.00, 100.00, 40.00, 150.00, 0.00, 220.00),

('PA_m', 'Pression artérielle moyenne (PAM)', 'Hémodynamique & Métabolique', 'mmHg', 'line',
 'Pression artérielle moyenne (perfusion des organes)',
 65.00, 100.00, 50.00, 120.00, 0.00, 160.00),

('Diurese_h', 'Diurèse horaire', 'Hémodynamique & Métabolique', 'mL/h', 'bar',
 'Débit urinaire (surveillance perfusion rénale)',
 30.00, 100.00, 20.00, 200.00, 0.00, 500.00),

('Lactate', 'Lactate sanguin', 'Hémodynamique & Métabolique', 'mmol/L', 'line',
 'Marqueur d’hypoperfusion et de choc',
 0.50, 2.00, NULL, 4.00, 0.00, 15.00),

('Temp', 'Température corporelle', 'Hémodynamique & Métabolique', '°C', 'line',
 'État métabolique / infectieux',
 36.00, 37.80, 35.00, 39.00, 30.00, 42.00),

-- Neurologie
('GCS', 'Glasgow Coma Scale (GCS)', 'Neurologie', '/15', 'bar',
 'Évaluation du niveau de conscience (3 à 15)',
 13.00, 15.00, 3.00, 8.00, 3.00, 15.00),

('RASS', 'Richmond Agitation-Sedation Scale (RASS)', 'Neurologie', 'score', 'bar',
 'Échelle agitation/sédation (-5 à +4)',
 -1.00, 1.00, NULL, NULL, -5.00, 4.00),

('PIC', 'Pression intracrânienne (PIC)', 'Neurologie', 'mmHg', 'line',
 'Surveillance de l’hypertension intracrânienne',
 0.00, 20.00, NULL, 25.00, 0.00, 50.00),

('PPC', 'Pression de perfusion cérébrale (PPC)', 'Neurologie', 'mmHg', 'line',
 'PPC = PAM - PIC (objectif de perfusion cérébrale)',
 60.00, 70.00, 50.00, 90.00, 0.00, 120.00),

-- Biologie
('pH_art', 'pH artériel', 'Biologie', '-', 'line',
 'Équilibre acido-basique (gaz du sang)',
 7.32, 7.48, 7.10, 7.60, 6.80, 7.80),

('Na', 'Sodium (Na+)', 'Biologie', 'mmol/L', 'line',
 'Équilibre hydrique / osmolarité',
 135.00, 145.00, 120.00, 160.00, 100.00, 180.00),

('K', 'Potassium (K+)', 'Biologie', 'mmol/L', 'line',
 'Équilibre électrolytique (risque rythmique si dérive)',
 3.50, 5.00, 2.50, 6.00, 0.00, 8.00),

('Gly', 'Glycémie', 'Biologie', 'mmol/L', 'line',
 'Contrôle métabolique du glucose',
 3.50, 7.00, 2.50, 12.00, 0.00, 25.00),

('HCO3', 'Bicarbonates (HCO3-)', 'Biologie', 'mmol/L', 'line',
 'Composante métabolique de l’équilibre acido-basique',
 22.00, 28.00, 15.00, 35.00, 0.00, 45.00),

('SaO2', 'Saturation artérielle en O2 (SaO2)', 'Biologie', '%', 'line',
 'Saturation sur gaz du sang (différente de SpO2)',
 92.00, 98.00, 85.00, 100.00, 50.00, 100.00);

INSERT INTO `chart_types` (`chart_type`,`label`) VALUES
  ('line','Courbe'),
  ('bar','Histogramme'),
  ('scatter','Nuage de points'),
  ('bubble','Bulles'),
  ('pie','Camembert'),
  ('doughnut','Anneau'),
  ('radar','Radar'),
  ('polarArea','Aire polaire'),
  ('value','Valeur seule');

INSERT INTO `parameter_chart_allowed` (`parameter_id`,`chart_type`,`is_default`) VALUES
  ('SpO2_m','line',1),
  ('SpO2_m','bar',0),
  ('SpO2_m','scatter',0),
  ('SpO2_m','value',0),
  ('FR_m','line',1),
  ('FR_m','bar',0),
  ('FR_m','scatter',0),
  ('FR_m','value',0),
  ('PEP_m','line',1),
  ('PEP_m','bar',0),
  ('PEP_m','scatter',0),
  ('PEP_m','value',0),
  ('FC_m','line',1),
  ('FC_m','bar',0),
  ('FC_m','scatter',0),
  ('FC_m','value',0),
  ('Temp','line',1),
  ('Temp','bar',0),
  ('Temp','scatter',0),
  ('Temp','value',0),
  ('PA_m','line',1), ('PA_m','bar',0), ('PA_m','value',0),
  ('FiO2_m','line',1), ('FiO2_m','bar',0), ('FiO2_m','value',0),
  ('VT_m','line',1), ('VT_m','bar',0), ('VT_m','value',0),
  ('Ppic_m','line',1), ('Ppic_m','bar',0), ('Ppic_m','value',0),
  ('Pplat_m','line',1), ('Pplat_m','bar',0), ('Pplat_m','value',0),
  ('Diurese_h','bar',1), ('Diurese_h','line',0), ('Diurese_h','value',0),
  ('Gly','line',1), ('Gly','bar',0), ('Gly','value',0),
  ('GCS','value',1), ('GCS','line',0), ('GCS','bar',0),
  ('Lactate','line',1), ('Lactate','bar',0), ('Lactate','value',0);

INSERT INTO `user_parameter_order` (`id_user`,`parameter_id`,`display_order`,`is_hidden`) VALUES
  (1,'SpO2_m',1,0),
  (1,'FR_m',2,0),
  (1,'PEP_m',3,0),
  (1,'FC_m',4,0),
  (1,'Temp',5,0),
  (1,'PA_m',8,0),
  (1,'GCS',10,0),
  (2,'SpO2_m',1,0),
  (2,'FR_m',2,0),
  (2,'PEP_m',3,0),
  (2,'FC_m',4,0),
  (2,'Temp',5,0),
  (2,'PA_m',8,0),
  (2,'GCS',10,0),
  (2,'FiO2_m',12,0),
  (2,'VT_m',13,0),
  (2,'Ppic_m',14,0),
  (2,'Pplat_m',15,0),
  (2,'Diurese_h',16,0),
  (2,'Gly',17,0),
  (2,'Lactate',18,0),
  (3,'SpO2_m',1,0),
  (3,'FR_m',2,0),
  (3,'PEP_m',3,0),
  (3,'FC_m',4,0),
  (3,'Temp',5,0),
  (4,'SpO2_m',1,0),
  (4,'FR_m',2,0),
  (4,'PEP_m',3,0),
  (4,'FC_m',4,0),
  (4,'Temp',5,0),
  (5,'SpO2_m',1,0),
  (5,'FR_m',2,0),
  (5,'PEP_m',3,0),
  (5,'FC_m',4,0),
  (5,'Temp',5,0),
  (6,'SpO2_m',1,0),
  (6,'FR_m',2,0),
  (6,'PEP_m',3,0),
  (6,'FC_m',4,0),
  (6,'Temp',5,0),
  (7,'SpO2_m',1,0),
  (7,'FR_m',2,0),
  (7,'PEP_m',3,1),
  (7,'FC_m',4,0),
  (7,'Temp',5,0),
  (8,'SpO2_m',1,0),
  (8,'FR_m',2,0),
  (8,'PEP_m',3,0),
  (8,'FC_m',4,0),
  (8,'Temp',5,0),
  (9,'SpO2_m',1,0),
  (9,'FR_m',2,0),
  (9,'PEP_m',3,0),
  (9,'FC_m',4,0),
  (9,'Temp',5,0),
  (10,'SpO2_m',1,0),
  (10,'FR_m',2,0),
  (10,'PEP_m',3,0),
  (10,'FC_m',4,0),
  (10,'Temp',5,0),
  (11,'SpO2_m',1,0),
  (11,'FR_m',2,0),
  (11,'PEP_m',3,0),
  (11,'FC_m',4,0),
  (11,'Temp',5,0),
  (12,'SpO2_m',1,0),
  (12,'FR_m',2,0),
  (12,'PEP_m',3,0),
  (12,'FC_m',4,0),
  (12,'Temp',5,0),
  (13,'SpO2_m',1,0),
  (13,'FR_m',2,0),
  (13,'PEP_m',3,0),
  (13,'FC_m',4,0),
  (13,'Temp',5,0),
  (14,'SpO2_m',1,0),
  (14,'FR_m',2,0),
  (14,'PEP_m',3,1),
  (14,'FC_m',4,0),
  (14,'Temp',5,0),
  (15,'SpO2_m',1,0),
  (15,'FR_m',2,0),
  (15,'PEP_m',3,0),
  (15,'FC_m',4,0),
  (15,'Temp',5,0);

INSERT INTO `user_parameter_chart_pref` (`id_user`,`parameter_id`,`chart_type`) VALUES
  (1,'SpO2_m','bar'),
  (1,'PEP_m','bar'),
  (2,'FR_m','bar'),
  (2,'SpO2_m','value'),
  (3,'Temp','scatter'),
  (4,'SpO2_m','bar'),
  (5,'FR_m','value'),
  (5,'Temp','bar'),
  (6,'Temp','scatter'),
  (7,'FR_m','scatter'),
  (7,'Temp','scatter'),
  (8,'SpO2_m','bar'),
  (8,'FC_m','scatter'),
  (9,'PEP_m','bar'),
  (9,'FR_m','scatter'),
  (10,'SpO2_m','bar'),
  (10,'FC_m','bar'),
  (11,'PEP_m','scatter'),
  (11,'Temp','scatter'),
  (12,'SpO2_m','value'),
  (12,'FC_m','value'),
  (13,'SpO2_m','value'),
  (14,'PEP_m','value'),
  (14,'Temp','scatter'),
  (15,'Temp','bar'),
  (15,'SpO2_m','bar');

COMMIT;
