<?php

namespace modules\services;

if (!class_exists('modules\services\MonitoringService')) {
    class MonitoringService
    {
        public function processMetrics($metrics, $raw = null, $prefs = null, $bool = null)
        {
            return [];
        }
    }
}
