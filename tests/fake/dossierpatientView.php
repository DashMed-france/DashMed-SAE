<?php
declare(strict_types=1);

namespace modules\views\pages;

/**
 * Fake view class for the Patient Record (Dossier Patient).
 * Used in unit tests to track if the view has been triggered.
 */
final class dossierpatientView
{
    /** @var bool Static flag to check if the view was displayed during a test. */
    public static bool $shown = false;

    /**
     * Simulates the display of the view by setting the shown flag to true.
     * * @return void
     */
    public function show(): void
    {
        self::$shown = true;
    }
}