<?php

namespace modules\models\Monitoring;

if (!class_exists('modules\models\Monitoring\MonitorModel')) {
    class MonitorModel
    {
        public function __construct($pdo = null, $table = null)
        {
        }
        public function getLatestMetrics($patientId)
        {
            return [];
        }
        public function getRawHistory($patientId)
        {
            return [];
        }
        public function getAllChartTypes()
        {
            return [];
        }
    }
}
