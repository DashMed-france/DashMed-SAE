<?php

namespace modules\models;

if (!class_exists('modules\models\PatientModel')) {
    class PatientModel
    {
        public function __construct($pdo = null)
        {
        }
        public function getAllRoomsWithPatients()
        {
            return [];
        }
        public function findById($pid)
        {
            return [];
        }
        public function getPatientIdByRoom($roomId)
        {
        }
    }
}
