<?php
declare(strict_types=1);

/**
 * Tests unitaires pour le contrôleur LogoutController (sans modifier le contrôleur)
 * ---------------------------------------------------------------------------------
 * Stratégie :
 *  - Le test génère un petit script PHP temporaire et l'exécute dans un sous-processus (PHP CLI).
 *  - Dans ce script, on surcharge header() dans le namespace du contrôleur pour capturer les en-têtes.
 *  - On utilise register_shutdown_function() pour écrire un fichier JSON (headers + état session)
 *    juste avant la fin DU sous-processus (quand exit() est appelé par le contrôleur).
 *  - Le test lit ce JSON et fait les assertions côté PHPUnit.
 */

namespace modules\tests\controllers;

use PHPUnit\Framework\TestCase;

final class logoutControllerTest extends TestCase
{
    /** Génère et exécute un runner externe qui appelle logoutController->get() */
    private function runLogoutInSubprocess(bool $startSessionBefore = true): array
    {
        $php = \PHP_BINARY; // exécutable PHP courant
        $dumpFile = \tempnam(\sys_get_temp_dir(), 'logout_dump_');
        if ($dumpFile === false) {
            $this->fail('Impossible de créer un fichier temporaire.');
        }
        // On veut un .json (plus lisible si tu ouvres le fichier)
        @\unlink($dumpFile);
        $dumpFile .= '.json';

        // Chemin absolu du contrôleur
        $controllerPath = \realpath(__DIR__ . '/../../app/controllers/LogoutController.php');
        $this->assertNotFalse($controllerPath, 'Fichier LogoutController.php introuvable');

        // Script runner : s’exécute DANS UN AUTRE PROCESSUS
        $runner = <<<'PHP'
<?php
declare(strict_types=1);

// 1) Surcharge header() dans le namespace du contrôleur pour capturer les en-têtes
namespace modules\controllers {
    $GLOBALS['__logout_headers'] = [];
    function header(string $string, bool $replace = true, ?int $code = null): void {
        $GLOBALS['__logout_headers'][] = $string;
    }
}

// 2) Retour au global pour utiliser sys_get_temp_dir etc.
namespace {
    // Paramètres reçus depuis le test via constantes
    $DUMP   = DUMP_FILE;
    $CTRL   = CTRL_FILE;
    $START  = START_SESSION_BEFORE;

    // 3) Préparation session
    if ($START) {
        if (\session_status() !== \PHP_SESSION_ACTIVE) {
            \session_id('TESTSESSID');
            \session_start();
        }
        $_SESSION = ['email' => 'user@example.com', 'role' => 'doctor'];
    } else {
        if (\session_status() === \PHP_SESSION_ACTIVE) {
            \session_write_close();
        }
        $_SESSION = [];
    }

    // 4) Enregistre une shutdown function qui dump l’état avant la fin du process (exit())
    \register_shutdown_function(function() use ($DUMP) {
        $data = [
            'headers'        => $GLOBALS['__logout_headers'] ?? [],
            'session_status' => \session_status(),
            'session'        => $_SESSION ?? null,
        ];
        @\file_put_contents($DUMP, \json_encode($data, JSON_PRETTY_PRINT));
    });

    // 5) Charge et exécute le contrôleur
    require_once $CTRL;
    $c = new \modules\controllers\logoutController();
    // ⚠️ Le contrôleur appelle exit(); ici c'est voulu : la shutdown function écrira le dump JSON.
    $c->get();
}
PHP;

        // On injecte les constantes nécessaires dans le runner (chemins + choix de session)
        $runnerWithParams =
            "<?php define('DUMP_FILE', " . \var_export($dumpFile, true) . ");"
            . "define('CTRL_FILE', " . \var_export($controllerPath, true) . ");"
            . "define('START_SESSION_BEFORE', " . ($startSessionBefore ? 'true' : 'false') . ");"
            . "?>\n" . $runner;

        // Écrit le runner sur disque
        $runnerFile = \tempnam(\sys_get_temp_dir(), 'logout_runner_');
        if ($runnerFile === false) {
            $this->fail('Impossible de créer le runner temporaire.');
        }
        \file_put_contents($runnerFile, $runnerWithParams);

        // Exécute le runner via PHP CLI
        // On n’utilise pas la sortie ; tout est dans le JSON dump.
        $cmd = '"' . $php . '" ' . \escapeshellarg($runnerFile);
        // Sur Windows, éviter les popups : pas nécessaire ici, exec suffit
        \exec($cmd, $out, $code); // $code sera 0 même si exit() a été appelé normalement

        // Nettoyage du runner
        @\unlink($runnerFile);

        // Lit le dump JSON
        $this->assertFileExists($dumpFile, "Le fichier de dump n'a pas été généré (exit avant shutdown ?).");
        $json = \file_get_contents($dumpFile);
        @\unlink($dumpFile);

        $this->assertIsString($json, 'Dump illisible.');
        $data = \json_decode($json, true);
        $this->assertIsArray($data, 'Dump JSON invalide.');

        return $data;
    }

    /**
     * Test 1 : session démarrée avant l’appel → doit être vidée + redirection vers /?page=homepage
     */
    public function test_logout_destroys_session_and_redirects_homepage(): void
    {
        $data = $this->runLogoutInSubprocess(true);

        // Assertions headers
        $this->assertArrayHasKey('headers', $data);
        $this->assertContains('Location: /?page=homepage', $data['headers'], 'Header Location manquant.');

        // Assertions session
        $this->assertArrayHasKey('session', $data);
        $this->assertIsArray($data['session']);
        $this->assertSame([], $data['session'], 'La session doit être vide après déconnexion.');
    }

    /**
     * Test 2 : aucune session démarrée avant → doit rester vide + redirection OK
     */
    public function test_logout_works_even_if_no_session_started(): void
    {
        $data = $this->runLogoutInSubprocess(false);

        $this->assertContains('Location: /?page=homepage', $data['headers'], 'Header Location manquant.');
        $this->assertSame([], $data['session'], 'La session doit rester vide.');
    }
}
