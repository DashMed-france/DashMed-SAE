<?php

namespace modules\models\Monitoring;

if (!class_exists('modules\models\Monitoring\MonitorPreferenceModel')) {
    class MonitorPreferenceModel
    {
        public function __construct($pdo = null)
        {
        }
        public function getUserPreferences($uid)
        {
            return [];
        }
        public function getUserLayoutSimple($uid)
        {
            return [];
        }
        public function saveUserChartPreference($uid, $pid, $ctype)
        {
        }
    }
}
