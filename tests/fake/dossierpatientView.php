<?php
declare(strict_types=1);

namespace modules\views\pages;

final class dossierpatientView
{
    public static bool $shown = false;

    public function show(): void
    {
        self::$shown = true;
    }
}
