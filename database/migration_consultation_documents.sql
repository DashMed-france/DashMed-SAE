-- =============================================================
-- Migration: Add consultation_documents table
-- Date: 2026-02-25
-- =============================================================

CREATE TABLE IF NOT EXISTS `consultation_documents` (
    `id_document`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_consultation`   INT UNSIGNED NOT NULL,
    `filename`          VARCHAR(255) NOT NULL        COMMENT 'Nom affiché à l''utilisateur',
    `stored_filename`   VARCHAR(255) NOT NULL        COMMENT 'Nom unique sur disque (UUID.pdf)',
    `mime_type`         VARCHAR(100) NOT NULL DEFAULT 'application/pdf',
    `file_size`         INT UNSIGNED NOT NULL        COMMENT 'Taille en octets',
    `uploaded_by`       INT UNSIGNED NOT NULL,
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id_document`),
    INDEX `ix_doc_consultation` (`id_consultation`),

    CONSTRAINT `fk_doc_consultation`
        FOREIGN KEY (`id_consultation`) REFERENCES `consultations` (`id_consultations`)
            ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `fk_doc_user`
        FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id_user`)
            ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
