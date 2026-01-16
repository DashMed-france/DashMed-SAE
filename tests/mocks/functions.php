<?php

namespace modules\controllers\auth;

use RuntimeException;

/**
 * Override of PHP `header()` function for testing purposes only.
 * Redéfinition de la fonction PHP `header()` uniquement pour les tests.
 */
function header(string $string): void
{
    throw new RuntimeException('REDIRECT:' . $string);
}
