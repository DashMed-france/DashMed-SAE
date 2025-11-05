<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../assets/includes/dev.php';

/**
 * Tests unitaires pour la classe dev (mode développement / production)
 *
 * Remarques :
 * - Ces tests n'appellent PAS dev::init() ni dev::loadEnv() afin d'éviter
 *   les effets de bord liés au système de fichiers (.env manquant).
 * - On manipule explicitement APP_DEBUG via putenv/$_ENV/$_SERVER pour
 *   couvrir les branches de isDebug(), getMode() et configurePhpErrorDisplay().
 */

use PHPUnit\Framework\TestCase;

final class DevTest extends TestCase
{
    /** Sauvegardes pour restauration après chaque test */
    private ?string $savedDisplayErrors = null;
    private ?string $savedDisplayStartupErrors = null;
    private int $savedErrorReporting = 0;

    private ?string $savedEnvAppDebug = null;   // getenv('APP_DEBUG')
    private ?string $savedSuperEnvAppDebug = null; // $_ENV['APP_DEBUG'] ?? null
    private ?string $savedServerAppDebug = null;   // $_SERVER['APP_DEBUG'] ?? null

    protected function setUp(): void
    {
        $this->savedDisplayErrors = ini_get('display_errors');
        $this->savedDisplayStartupErrors = ini_get('display_startup_errors');
        $this->savedErrorReporting = error_reporting();

        $this->savedEnvAppDebug      = getenv('APP_DEBUG') !== false ? (string)getenv('APP_DEBUG') : null;
        $this->savedSuperEnvAppDebug = $_ENV['APP_DEBUG']    ?? null;
        $this->savedServerAppDebug   = $_SERVER['APP_DEBUG'] ?? null;

        if (!class_exists('dev')) {
            $this->markTestSkipped('La classe "dev" est introuvable (autoload non chargé ?).');
        }

        $this->clearAppDebug();
    }

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
            putenv('APP_DEBUG='.$this->savedEnvAppDebug);
        }
        if ($this->savedSuperEnvAppDebug !== null) {
            $_ENV['APP_DEBUG'] = $this->savedSuperEnvAppDebug;
        }
        if ($this->savedServerAppDebug !== null) {
            $_SERVER['APP_DEBUG'] = $this->savedServerAppDebug;
        }
    }

    /** Helper : purge APP_DEBUG de tous les emplacements */
    private function clearAppDebug(): void
    {
        putenv('APP_DEBUG');
        unset($_ENV['APP_DEBUG'], $_SERVER['APP_DEBUG']);
    }

    /** Helper : positionne APP_DEBUG partout (getenv/$_ENV/$_SERVER) */
    private function setAppDebug(string $value): void
    {
        putenv('APP_DEBUG='.$value);
        $_ENV['APP_DEBUG']    = $value;
        $_SERVER['APP_DEBUG'] = $value;
    }

    /** Valeurs interprétées comme vraies par isDebug() */
    public static function trueValuesProvider(): array
    {
        return [
            ['1'],
            ['true'],
            ['on'],
            ['yes'],
            [' TRUE '],
            ['On'],
            ['YeS'],
        ];
    }

    /** Valeurs interprétées comme fausses par isDebug() */
    public static function falseValuesProvider(): array
    {
        return [
            ['0'],
            ['false'],
            ['off'],
            ['no'],
            [''],
            ['random'],
            ['  '],
        ];
    }

    /**
     * @dataProvider trueValuesProvider
     */
    public function test_isDebug_returns_true_for_truthy_values(string $val): void
    {
        $this->setAppDebug($val);
        $this->assertTrue(dev::isDebug(), "isDebug() devrait être TRUE pour '{$val}'");
    }

    /**
     * @dataProvider falseValuesProvider
     */
    public function test_isDebug_returns_false_for_falsy_values(string $val): void
    {
        $this->setAppDebug($val);
        $this->assertFalse(dev::isDebug(), "isDebug() devrait être FALSE pour '{$val}'");
    }

    public function test_isDebug_reads_from__ENV_when_getenv_is_empty(): void
    {
        putenv('APP_DEBUG');
        $_ENV['APP_DEBUG'] = 'yes';
        unset($_SERVER['APP_DEBUG']);

        $this->assertTrue(dev::isDebug(), 'isDebug() devrait utiliser $_ENV comme fallback');
    }

    public function test_getMode_matches_isDebug(): void
    {
        $this->setAppDebug('true');
        $this->assertSame('development', dev::getMode());

        $this->setAppDebug('0');
        $this->assertSame('production', dev::getMode());
    }

    public function test_configurePhpErrorDisplay_in_dev_mode(): void
    {
        $this->setAppDebug('1');
        dev::configurePhpErrorDisplay();

        $this->assertSame('1', ini_get('display_errors'), 'display_errors doit être activé en dev');
        $this->assertSame('1', ini_get('display_startup_errors'), 'display_startup_errors doit être activé en dev');
        $this->assertSame(E_ALL, error_reporting(), 'error_reporting doit être E_ALL en dev');
    }

    public function test_configurePhpErrorDisplay_in_prod_mode(): void
    {
        $this->setAppDebug('0');
        dev::configurePhpErrorDisplay();

        $this->assertSame('0', ini_get('display_errors'), 'display_errors doit être désactivé en prod');
        $this->assertSame('0', ini_get('display_startup_errors'), 'display_startup_errors doit être désactivé en prod');

        $level = error_reporting();
        $this->assertSame(0, $level & E_NOTICE, 'E_NOTICE doit être masqué en prod');
        // E_STRICT est fusionné à partir de PHP 7, mais on garde le test si défini
        if (defined('E_STRICT')) {
            $this->assertSame(0, $level & E_STRICT, 'E_STRICT doit être masqué en prod');
        }
        if (defined('E_DEPRECATED')) {
            $this->assertSame(0, $level & E_DEPRECATED, 'E_DEPRECATED doit être masqué en prod');
        }
    }
}
