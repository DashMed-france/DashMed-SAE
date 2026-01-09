<?php

declare(strict_types=1);

namespace modules\views\pages;

/**
 * Fake view for testing purposes.
 * Vue factice pour les tests.
 *
 * Used to mock the dashboardView in unit tests.
 * Utilisée pour simuler dashboardView dans les tests unitaires.
 */
final class dashboardView
{
    public static bool $shown = false;

    public function show(): void
    {
        self::$shown = true;
    }
}
