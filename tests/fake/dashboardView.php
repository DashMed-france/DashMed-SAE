<?php
declare(strict_types=1);

namespace modules\views;

final class dashboardView
{
    public static bool $shown = false;

    public function show(): void
    {
        self::$shown = true;
    }
}
