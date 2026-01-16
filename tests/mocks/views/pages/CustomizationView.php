<?php

namespace modules\views\pages;

if (!class_exists('modules\views\pages\CustomizationView')) {
    class CustomizationView
    {
        public function show($widgets, $hidden = [])
        {
            echo "CustomizationView Mock";
        }
    }
}
