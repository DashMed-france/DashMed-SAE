<?php

namespace modules\models;

if (!class_exists('modules\models\ConsultationModel')) {
    class ConsultationModel
    {
        public function __construct($pdo = null)
        {
        }
        public function getConsultationsByPatientId($pid)
        {
            return [];
        }
    }
}
