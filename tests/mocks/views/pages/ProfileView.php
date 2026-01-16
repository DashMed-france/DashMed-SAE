<?php

namespace modules\views\pages;

if (!class_exists('modules\views\pages\ProfileView')) {
    class ProfileView
    {
        public function show($u, $p, $m)
        {
            echo "ProfileView";
        }
    }
}
