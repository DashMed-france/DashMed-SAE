<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../assets/includes/Dev.php';

/**
 * Unit tests for the Dev class (development / production mode)
 *
 * Notes:
 * - These tests do NOT call Dev::init() or Dev::loadEnv() to avoid
 *   side effects related to the file system (missing .env).
 * - We explicitly manipulate APP_DEBUG via putenv/$_ENV/$_SERVER to
 *   cover the branches of isDebug(), getMode() and configurePhpErrorDisplay().
 */

use PHPUnit\Framework\TestCase;

final class DevTest extends TestCase
{
    /**
     * Saved values for restoration after each test
     */
    private ?string $savedDisplayErrors = null;

    /**
     * @var string|null Saved display_startup_errors setting
     */
    private ?string $savedDisplayStartupErrors = null;

    /**
     * @var int Saved error_reporting level
     */
    private int $savedErrorReporting = 0;

    /**
     * @var string|null Saved APP_DEBUG from getenv()
     */
    private ?string $savedEnvAppDebug = null;

    /**
     * @var string|null Saved APP_DEBUG from $_ENV
     */
    private ?string $savedSuperEnvAppDebug = null;

    /**
     * @var string|null Saved APP_DEBUG from $_SERVER
     */
    private ?string $savedServerAppDebug = null;

    /**
     * Set up test environment before each test
     *
     * Saves current PHP configuration and environment variables
     * to restore them after the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->savedDisplayErrors = ini_get('display_errors');
        $this->savedDisplayStartupErrors = ini_get('display_startup_errors');
        $this->savedErrorReporting = error_reporting();

        $this->savedEnvAppDebug      = getenv('APP_DEBUG') !== false ? (string)getenv('APP_DEBUG') : null;
        $this->savedSuperEnvAppDebug = $_ENV['APP_DEBUG']    ?? null;
        $this->savedServerAppDebug   = $_SERVER['APP_DEBUG'] ?? null;

        if (!class_exists('Dev')) {
            $this->markTestSkipped('La classe "dev" est introuvable (autoload non chargé ?).');
        }

        $this->clearAppDebug();
    }

    /**
     * Tear down test environment after each test
     *
     * Restores PHP configuration and environment variables
     * to their original state.
     *
     * @return void
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
     * Helper method: Clear APP_DEBUG from all locations
     *
     * Removes APP_DEBUG from putenv, $_ENV, and $_SERVER
     *
     * @return void
     */
    private function clearAppDebug(): void
    {
        putenv('APP_DEBUG');
        unset($_ENV['APP_DEBUG'], $_SERVER['APP_DEBUG']);
    }

    /**
     * Helper method: Set APP_DEBUG in all locations
     *
     * Sets APP_DEBUG in putenv, $_ENV, and $_SERVER
     *
     * @param string $value The value to set for APP_DEBUG
     * @return void
     */
    private function setAppDebug(string $value): void
    {
        putenv('APP_DEBUG=' . $value);
        $_ENV['APP_DEBUG']    = $value;
        $_SERVER['APP_DEBUG'] = $value;
    }

    /**
     * Data provider: Values interpreted as true by isDebug()
     *
     * @return array<int, array<int, string>> Array of test cases
     */
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

    /**
     * Data provider: Values interpreted as false by isDebug()
     *
     * @return array<int, array<int, string>> Array of test cases
     */
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
     * Test that isDebug() returns true for truthy values
     *
     * @dataProvider trueValuesProvider
     * @param string $val The value to test
     * @return void
     */
    public function test_isDebug_returns_true_for_truthy_values(string $val): void
    {
        $this->setAppDebug($val);
        $this->assertTrue(Dev::isDebug(), "isDebug() devrait être TRUE pour '{$val}'");
    }

    /**
     * Test that isDebug() returns false for falsy values
     *
     * @dataProvider falseValuesProvider
     * @param string $val The value to test
     * @return void
     */
    public function test_isDebug_returns_false_for_falsy_values(string $val): void
    {
        $this->setAppDebug($val);
        $this->assertFalse(Dev::isDebug(), "isDebug() devrait être FALSE pour '{$val}'");
    }

    /**
     * Test that isDebug() reads from $_ENV when getenv is empty
     *
     * @return void
     */
    public function test_isDebug_reads_from__ENV_when_getenv_is_empty(): void
    {
        putenv('APP_DEBUG');
        $_ENV['APP_DEBUG'] = 'yes';
        unset($_SERVER['APP_DEBUG']);

        $this->assertTrue(Dev::isDebug(), 'isDebug() devrait utiliser $_ENV comme fallback');
    }

    /**
     * Test that getMode() matches isDebug() return value
     *
     * @return void
     */
    public function test_getMode_matches_isDebug(): void
    {
        $this->setAppDebug('true');
        $this->assertSame('development', Dev::getMode());

        $this->setAppDebug('0');
        $this->assertSame('production', Dev::getMode());
    }

    /**
     * Test that configurePhpErrorDisplay() sets correct values in development mode
     *
     * @return void
     */
    public function test_configurePhpErrorDisplay_in_dev_mode(): void
    {
        $this->setAppDebug('1');
        Dev::configurePhpErrorDisplay();

        $this->assertSame('1', ini_get('display_errors'), 'display_errors doit être activé en dev');
        $this->assertSame('1', ini_get('display_startup_errors'), 'display_startup_errors doit être activé en dev');
        $this->assertSame(E_ALL, error_reporting(), 'error_reporting doit être E_ALL en dev');
    }

    /**
     * Test that configurePhpErrorDisplay() sets correct values in production mode
     *
     * @return void
     */
    public function test_configurePhpErrorDisplay_in_prod_mode(): void
    {
        $this->setAppDebug('0');
        Dev::configurePhpErrorDisplay();

        $this->assertSame('0', ini_get('display_errors'), 'display_errors doit être désactivé en prod');
        $this->assertSame('0', ini_get('display_startup_errors'), 'display_startup_errors doit être désactivé en prod');

        $level = error_reporting();
        $this->assertSame(0, $level & E_NOTICE, 'E_NOTICE doit être masqué en prod');
        if (defined('E_STRICT')) {
            $this->assertSame(0, $level & E_STRICT, 'E_STRICT doit être masqué en prod');
        }
        if (defined('E_DEPRECATED')) {
            $this->assertSame(0, $level & E_DEPRECATED, 'E_DEPRECATED doit être masqué en prod');
        }
    }
}