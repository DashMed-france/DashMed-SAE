<?php

namespace modules\views\pages;

if (!class_exists('modules\views\pages\DashboardView')) {
    class DashboardView
    {
        public static $shown = false;
        public function __construct($cP, $cF, $rooms, $metrics, $pData, $charts)
        {
        }
        public function show()
        {
            echo "Dashboard View Mock";
            self::$shown = true;
        }
    }
} else {
    if (property_exists('modules\views\pages\DashboardView', 'shown')) {
        \modules\views\pages\DashboardView::$shown = false;
    }
}
