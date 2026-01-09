SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

SET FOREIGN_KEY_CHECKS = 0;

-- Sécurité : triggers (au cas où le DROP TABLE ne s'est pas fait lors d'un précédent run interrompu)
DROP TRIGGER IF EXISTS `trg_check_room_occupation`;
DROP TRIGGER IF EXISTS `trg_check_room_occupation_update`;

-- Drops (views d'abord)
DROP VIEW IF EXISTS `view_consultations`;
DROP VIEW IF EXISTS `view_patient_indicator_status`;
DROP VIEW IF EXISTS `view_latest_patient_data`;

-- Drops (tables)
DROP TABLE IF EXISTS `consultations`;
DROP TABLE IF EXISTS `user_parameter_order`;
DROP TABLE IF EXISTS `user_parameter_chart_pref`;
DROP TABLE IF EXISTS `parameter_chart_allowed`;
DROP TABLE IF EXISTS `chart_types`;
DROP TABLE IF EXISTS `patient_data`;
DROP TABLE IF EXISTS `parameter_reference`;
DROP TABLE IF EXISTS `patients`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `professions`;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================
-- CRÉATION DES TABLES
-- =========================

-- Table professions
CREATE TABLE `professions` (
                               `id_profession` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                               `label_profession` VARCHAR(100) NOT NULL,

                               PRIMARY KEY (`id_profession`),
                               UNIQUE KEY `ux_profession_name` (`label_profession`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table users
CREATE TABLE `users` (
                         `id_user` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                         `first_name` VARCHAR(50) NOT NULL,
                         `last_name` VARCHAR(50) NOT NULL,
                         `email` VARCHAR(191) NOT NULL COMMENT 'Email unique, normalisé en minuscules',
                         `password` VARCHAR(255) NOT NULL COMMENT 'Hash du mot de passe',
                         `passcode_hash` VARCHAR(255) DEFAULT NULL COMMENT 'Hash du passcode (optionnel)',
                         `admin_status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=user, 1=admin',
                         `birth_date` DATE DEFAULT NULL COMMENT 'Date de naissance',
                         `id_profession` INT UNSIGNED DEFAULT NULL,
                         `reset_token` VARCHAR(128) DEFAULT NULL,
                         `reset_code_hash` VARCHAR(255) DEFAULT NULL,
                         `reset_expires` DATETIME DEFAULT NULL,
                         `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                         `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

                         PRIMARY KEY (`id_user`),
                         UNIQUE KEY `ux_users_email` (`email`),
                         INDEX `ix_users_profession` (`id_profession`),
                         CONSTRAINT `ck_admin_status_bool` CHECK (`admin_status` IN (0,1)),
                         CONSTRAINT `ck_users_first_name_format` CHECK (`first_name` REGEXP '^[[:alpha:]][[:alpha:] ''’\-]{0,49}$'),
                         CONSTRAINT `ck_users_last_name_format`  CHECK (`last_name`  REGEXP '^[[:alpha:]][[:alpha:] ''’\-]{0,49}$'),
                         CONSTRAINT `fk_users_profession`
                             FOREIGN KEY (`id_profession`) REFERENCES `professions` (`id_profession`)
                                 ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table patients
CREATE TABLE `patients` (
                            `id_patient` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                            `first_name` VARCHAR(50) NOT NULL,
                            `last_name` VARCHAR(50) NOT NULL,
                            `email` VARCHAR(191) NOT NULL COMMENT 'Adresse email unique du patient',
                            `birth_date` DATE NOT NULL COMMENT 'Date de naissance du patient',
                            `weight` DECIMAL(5,2) NOT NULL COMMENT 'Poids en kilogrammes (kg)',
                            `height` DECIMAL(5,2) NOT NULL COMMENT 'Taille en centimètres (cm)',
                            `gender` ENUM('M', 'F') NOT NULL COMMENT 'Sexe du patient (M/F)',
                            `status` ENUM('En réanimation', 'Sorti', 'Décédé') DEFAULT 'En réanimation',
                            `description` VARCHAR(1000) NULL COMMENT 'Raison de l''hospitalisation',
                            `room_id` INT UNSIGNED NULL COMMENT 'Chambre dediée à 1 seul patient',
                            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

                            PRIMARY KEY (`id_patient`),
                            UNIQUE KEY `ux_patients_email` (`email`),
                            INDEX `ix_patients_last_first` (`last_name`, `first_name`),
                            INDEX `ix_patients_room` (`room_id`),
                            CONSTRAINT `ck_birth_date_min` CHECK (`birth_date` >= '1900-01-01'),
                            CONSTRAINT `ck_weight_range` CHECK (`weight` BETWEEN 0.00 AND 500.00),
                            CONSTRAINT `ck_height_range` CHECK (`height` BETWEEN 30.00 AND 300.00),
                            CONSTRAINT `ck_room_range` CHECK (`room_id` BETWEEN 1 AND 20),
                            CONSTRAINT `ck_patients_first_name_format` CHECK (`first_name` REGEXP '^[[:alpha:]][[:alpha:] ''’\-]{0,49}$'),
                            CONSTRAINT `ck_patients_last_name_format`  CHECK (`last_name`  REGEXP '^[[:alpha:]][[:alpha:] ''’\-]{0,49}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table parameter_reference
CREATE TABLE `parameter_reference` (
                                       `parameter_id` VARCHAR(50) NOT NULL,
                                       `display_name` VARCHAR(100) NOT NULL,
                                       `category` VARCHAR(50) DEFAULT NULL,
                                       `unit` VARCHAR(20) DEFAULT NULL,
                                       `default_chart` VARCHAR(20) NOT NULL,
                                       `description` TEXT DEFAULT NULL,
                                       `normal_min` DECIMAL(15,2) DEFAULT NULL,
                                       `normal_max` DECIMAL(15,2) DEFAULT NULL,
                                       `critical_min` DECIMAL(15,2) DEFAULT NULL,
                                       `critical_max` DECIMAL(15,2) DEFAULT NULL,
                                       `display_min` DECIMAL(15,2) DEFAULT NULL,
                                       `display_max` DECIMAL(15,2) DEFAULT NULL,

                                       PRIMARY KEY (`parameter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table consultations
CREATE TABLE `consultations` (
                                 `id_consultations` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                 `id_patient`       INT UNSIGNED NOT NULL,
                                 `id_user`          INT UNSIGNED NOT NULL,
                                 `date`             DATETIME NOT NULL,
                                 `title`            VARCHAR(255) NOT NULL,
                                 `type`             VARCHAR(80)  NOT NULL,
                                 `note`             TEXT NULL,
                                 `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                 `updated_at`       DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

                                 PRIMARY KEY (`id_consultations`),

                                 INDEX `ix_consultations_patient_date` (`id_patient`, `date`),
                                 INDEX `ix_consultations_user_date` (`id_user`, `date`),

                                 CONSTRAINT `fk_consultations_patient`
                                     FOREIGN KEY (`id_patient`) REFERENCES `patients` (`id_patient`)
                                         ON DELETE CASCADE ON UPDATE CASCADE,

                                 CONSTRAINT `fk_consultations_user`
                                     FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`)
                                         ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ordre d’affichage des indicateurs par utilisateur
CREATE TABLE `user_parameter_order` (
                                        `id_user` INT UNSIGNED NOT NULL,
                                        `parameter_id` VARCHAR(50) NOT NULL,
                                        `display_order` INT UNSIGNED NOT NULL,
                                        `is_hidden` TINYINT(1) NOT NULL DEFAULT 0,
                                        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                                        PRIMARY KEY (`id_user`, `parameter_id`),
                                        UNIQUE KEY `ux_user_param_order_rank` (`id_user`, `display_order`),

                                        CONSTRAINT `fk_upo_user`
                                            FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`)
                                                ON DELETE CASCADE ON UPDATE CASCADE,

                                        CONSTRAINT `fk_upo_parameter`
                                            FOREIGN KEY (`parameter_id`) REFERENCES `parameter_reference` (`parameter_id`)
                                                ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Types de charts
CREATE TABLE `chart_types` (
                               `chart_type` VARCHAR(20) NOT NULL,
                               `label` VARCHAR(50) NOT NULL,
                               PRIMARY KEY (`chart_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `parameter_chart_allowed` (
                                           `parameter_id` VARCHAR(50) NOT NULL,
                                           `chart_type` VARCHAR(20) NOT NULL,
                                           `is_default` TINYINT(1) NOT NULL DEFAULT 0,

                                           PRIMARY KEY (`parameter_id`, `chart_type`),

                                           CONSTRAINT `fk_pca_parameter`
                                               FOREIGN KEY (`parameter_id`) REFERENCES `parameter_reference` (`parameter_id`)
                                                   ON DELETE CASCADE ON UPDATE CASCADE,

                                           CONSTRAINT `fk_pca_chart_type`
                                               FOREIGN KEY (`chart_type`) REFERENCES `chart_types` (`chart_type`)
                                                   ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `user_parameter_chart_pref` (
                                             `id_user` INT UNSIGNED NOT NULL,
                                             `parameter_id` VARCHAR(50) NOT NULL,
                                             `chart_type` VARCHAR(20) NOT NULL,
                                             `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                                             PRIMARY KEY (`id_user`, `parameter_id`),

                                             CONSTRAINT `fk_upcp_user`
                                                 FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`)
                                                     ON DELETE CASCADE ON UPDATE CASCADE,

                                             CONSTRAINT `fk_upcp_allowed`
                                                 FOREIGN KEY (`parameter_id`, `chart_type`)
                                                     REFERENCES `parameter_chart_allowed` (`parameter_id`, `chart_type`)
                                                     ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table patient_data
CREATE TABLE `patient_data` (
                                `id_patient` INT UNSIGNED NOT NULL,
                                `parameter_id` VARCHAR(50) NOT NULL,
                                `value` DECIMAL(15,2) DEFAULT NULL,
                                `timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                `alert_flag` TINYINT(1) DEFAULT 0 COMMENT '1 si valeur critique',
                                `created_by` INT UNSIGNED DEFAULT NULL,
                                `archived` TINYINT(1) DEFAULT 0, -- à voir pour la partie historique des données

                                PRIMARY KEY (`id_patient`, `parameter_id`, `timestamp`),
                                INDEX `ix_patient_param_time` (`id_patient`, `parameter_id`, `timestamp`),

                                CONSTRAINT `fk_patient_data_patient`
                                    FOREIGN KEY (`id_patient`) REFERENCES `patients` (`id_patient`)
                                        ON DELETE CASCADE ON UPDATE CASCADE,

                                CONSTRAINT `fk_patient_data_parameter`
                                    FOREIGN KEY (`parameter_id`) REFERENCES `parameter_reference` (`parameter_id`)
                                        ON DELETE CASCADE ON UPDATE CASCADE,

                                CONSTRAINT `fk_patient_data_user`
                                    FOREIGN KEY (`created_by`) REFERENCES `users` (`id_user`)
                                        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================
-- TRIGGERS
-- =========================
DELIMITER $$

CREATE TRIGGER `trg_check_room_occupation`
    BEFORE INSERT ON `patients`
    FOR EACH ROW
BEGIN
    DECLARE room_count INT;

    IF NEW.status = 'En réanimation' AND NEW.room_id IS NOT NULL THEN
        SELECT COUNT(*) INTO room_count
        FROM `patients`
        WHERE room_id = NEW.room_id AND status = 'En réanimation';

        IF room_count > 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Chambre déjà occupée par un patient actif';
        END IF;
    END IF;
END$$

CREATE TRIGGER `trg_check_room_occupation_update`
    BEFORE UPDATE ON `patients`
    FOR EACH ROW
BEGIN
    DECLARE room_count INT;

    IF NEW.status = 'En réanimation' AND NEW.room_id IS NOT NULL THEN
        SELECT COUNT(*) INTO room_count
        FROM `patients`
        WHERE room_id = NEW.room_id
          AND status = 'En réanimation'
          AND id_patient != NEW.id_patient;

        IF room_count > 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Chambre déjà occupée par un patient actif';
        END IF;
    END IF;

    IF NEW.status IN ('Sorti','Décédé') THEN
        SET NEW.room_id = NULL;
    END IF;
END$$

DELIMITER ;

-- =========================
-- CRÉATION DES VUES (APRÈS tables)
-- =========================

CREATE OR REPLACE VIEW `view_consultations` AS
SELECT
    `consultations`.`id_consultations`,
    `consultations`.`id_patient`,
    `consultations`.`id_user`,
    `users`.`last_name`,
    `consultations`.`date`,
    `consultations`.`title`,
    `consultations`.`type`,
    `consultations`.`note`
FROM `consultations`
         JOIN `users` ON `users`.`id_user` = `consultations`.`id_user`;

CREATE OR REPLACE VIEW `view_latest_patient_data` AS
SELECT pd.*
FROM `patient_data` pd
         JOIN (
    SELECT `id_patient`, `parameter_id`, MAX(`timestamp`) AS max_ts
    FROM `patient_data`
    GROUP BY `id_patient`, `parameter_id`
) m
              ON m.`id_patient` = pd.`id_patient`
                  AND m.`parameter_id` = pd.`parameter_id`
                  AND m.max_ts = pd.`timestamp`;

CREATE OR REPLACE VIEW `view_patient_indicator_status` AS
SELECT
    l.`id_patient`,
    l.`parameter_id`,
    l.`value`,
    l.`timestamp`,
    CASE
        WHEN l.`value` IS NULL THEN 'unknown'
        WHEN (
            l.`alert_flag` = 1
                OR (pr.`critical_min` IS NOT NULL AND l.`value` < pr.`critical_min`)
                OR (pr.`critical_max` IS NOT NULL AND l.`value` > pr.`critical_max`)
            ) THEN 'critical'
        WHEN (
            (pr.`normal_min` IS NOT NULL AND l.`value` < pr.`normal_min`)
                OR (pr.`normal_max` IS NOT NULL AND l.`value` > pr.`normal_max`)
            ) THEN 'warning'
        ELSE 'normal'
        END AS `status`,
    CASE
        WHEN l.`value` IS NULL THEN -1
        WHEN (
            l.`alert_flag` = 1
                OR (pr.`critical_min` IS NOT NULL AND l.`value` < pr.`critical_min`)
                OR (pr.`critical_max` IS NOT NULL AND l.`value` > pr.`critical_max`)
            ) THEN 2
        WHEN (
            (pr.`normal_min` IS NOT NULL AND l.`value` < pr.`normal_min`)
                OR (pr.`normal_max` IS NOT NULL AND l.`value` > pr.`normal_max`)
            ) THEN 1
        ELSE 0
        END AS `priority`
FROM `view_latest_patient_data` l
         JOIN `parameter_reference` pr ON pr.`parameter_id` = l.`parameter_id`;

COMMIT;
