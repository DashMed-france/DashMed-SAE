<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use assets\includes\Dev;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../assets/includes/Dev.php';

/**
 * Class DevTest | Tests de la classe Dev
 *
 * Unit tests for development/production mode management.
 * Tests unitaires pour la gestion des modes développement/production.
 *
 * @package Tests
 * @author DashMed Team
 */
final class DevTest extends TestCase
{
    private ?string $savedDisplayErrors = null;
    private ?string $savedDisplayStartupErrors = null;
    private int $savedErrorReporting = 0;

    private ?string $savedEnvAppDebug = null;
    private ?string $savedSuperEnvAppDebug = null;
    private ?string $savedServerAppDebug = null;

    /**
     * Setup backup of environment variables.
     * Configuration de la sauvegarde des variables d'environnement.
     */
    protected function setUp(): void
    {
        $this->savedDisplayErrors = ini_get('display_errors');
        $this->savedDisplayStartupErrors = ini_get('display_startup_errors');
        $this->savedErrorReporting = error_reporting();

        $this->savedEnvAppDebug = getenv('APP_DEBUG') !== false ? (string) getenv('APP_DEBUG') : null;
        $this->savedSuperEnvAppDebug = $_ENV['APP_DEBUG'] ?? null;
        $this->savedServerAppDebug = $_SERVER['APP_DEBUG'] ?? null;



        $this->clearAppDebug();
    }

    /**
     * Restore environment after test.
     * Restauration de l'environnement après le test.
     */
    protected function tearDown(): void
    {
        if ($this->savedDisplayErrors !== null) {
            ini_set('display_errors', $this->savedDisplayErrors);
        }
        if ($this->savedDisplayStartupErrors !== null) {
            ini_set('display_startup_errors', $this->savedDisplayStartupErrors);
        }
        if ($this->savedErrorReporting !== 0) {
            error_reporting($this->savedErrorReporting);
        }

        $this->clearAppDebug();
        if ($this->savedEnvAppDebug !== null) {
            putenv('APP_DEBUG=' . $this->savedEnvAppDebug);
        }
        if ($this->savedSuperEnvAppDebug !== null) {
            $_ENV['APP_DEBUG'] = $this->savedSuperEnvAppDebug;
        }
        if ($this->savedServerAppDebug !== null) {
            $_SERVER['APP_DEBUG'] = $this->savedServerAppDebug;
        }
    }

    /**
     * Clears APP_DEBUG from all locations.
     * Purge APP_DEBUG de tous les emplacements.
     */
    private function clearAppDebug(): void
    {
        putenv('APP_DEBUG');
        unset($_ENV['APP_DEBUG'], $_SERVER['APP_DEBUG']);
    }

    /**
     * Sets APP_DEBUG everywhere.
     * Définit APP_DEBUG partout.
     */
    private function setAppDebug(string $value): void
    {
        putenv('APP_DEBUG=' . $value);
        $_ENV['APP_DEBUG'] = $value;
        $_SERVER['APP_DEBUG'] = $value;
    }

    /**
     * Data provider for truthy values.
     * Fournisseur de données pour les valeurs vraies.
     * @return array
     */
    public static function trueValuesProvider(): array
    {
        return [['1'], ['true'], ['on'], ['yes'], [' TRUE '], ['On'], ['YeS']];
    }

    /**
     * Data provider for falsy values.
     * Fournisseur de données pour les valeurs fausses.
     * @return array
     */
    public static function falseValuesProvider(): array
    {
        return [['0'], ['false'], ['off'], ['no'], [''], ['random'], ['  ']];
    }

    /**
     * @dataProvider trueValuesProvider
     */
    public function testIsDebugReturnsTrueForTruthyValues(string $val): void
    {
        $this->setAppDebug($val);
        $this->assertTrue(Dev::isDebug(), "isDebug() should be TRUE for '{$val}'");
    }

    /**
     * @dataProvider falseValuesProvider
     */
    public function testIsDebugReturnsFalseForFalsyValues(string $val): void
    {
        $this->setAppDebug($val);
        $this->assertFalse(Dev::isDebug(), "isDebug() should be FALSE for '{$val}'");
    }

    public function testIsDebugReadsFromEnvWhenGetenvIsEmpty(): void
    {
        putenv('APP_DEBUG');
        $_ENV['APP_DEBUG'] = 'yes';
        unset($_SERVER['APP_DEBUG']);

        $this->assertTrue(Dev::isDebug(), 'isDebug() should fallback to $_ENV');
    }

    public function testGetModeMatchesIsDebug(): void
    {
        $this->setAppDebug('true');
        $this->assertSame('development', Dev::getMode());

        $this->setAppDebug('0');
        $this->assertSame('production', Dev::getMode());
    }

    public function testConfigurePhpErrorDisplayInDevMode(): void
    {
        $this->setAppDebug('1');
        Dev::configurePhpErrorDisplay();

        $this->assertSame('1', ini_get('display_errors'));
        $this->assertSame('1', ini_get('display_startup_errors'));
        $this->assertSame(E_ALL, error_reporting());
    }

    public function testConfigurePhpErrorDisplayInProdMode(): void
    {
        $this->setAppDebug('0');
        Dev::configurePhpErrorDisplay();

        $this->assertSame('0', ini_get('display_errors'));
        $this->assertSame('0', ini_get('display_startup_errors'));

        $this->assertSame(0, error_reporting() & E_NOTICE);
    }
}
