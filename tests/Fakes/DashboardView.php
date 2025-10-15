<?php
declare(strict_types=1);
namespace modules\views;

class dashboardView
{
    public static bool $wasShown = false;
    public function show(): void { self::$wasShown = true; }
}
