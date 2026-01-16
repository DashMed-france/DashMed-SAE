<?php

namespace modules\views\pages\Monitoring;

if (!class_exists('modules\views\pages\Monitoring\MonitoringView')) {
    class MonitoringView
    {
        public $metrics;
        public $chartTypes;

        public function __construct($metrics, $chartTypes)
        {
            $this->metrics = $metrics;
            $this->chartTypes = $chartTypes;
        }

        public function show()
        {
            echo "View Shown";
        }
    }
}
