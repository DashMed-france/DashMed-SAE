<?php
declare(strict_types=1);

namespace modules\controllers;

/**
 * Redéfinition de la fonction PHP `header()` uniquement pour les tests.
 * ---------------------------------------------------------------
 * Objectif :
 *  - Empêcher les vraies redirections HTTP pendant les tests.
 *  - Lancer une exception `RuntimeException` simulant une redirection.
 *
 * Exemple : `header('Location: /?page=homepage')`
 * devient une exception `RuntimeException('REDIRECT:Location: /?page=homepage')`.
 *
 * Cela permet à PHPUnit de capturer la redirection et de la vérifier sans quitter le test.
 */
function header(string $string, bool $replace = true, ?int $code = null): void
{
    throw new \RuntimeException('REDIRECT:' . $string);
}

namespace modules\tests\controllers;

use PHPUnit\Framework\TestCase;
use modules\controllers\logoutController;

realpath(__DIR__ . '/../../modules/controllers/logoutController.php');

/**
 * Tests PHPUnit du contrôleur Logout
 * ----------------------------------
 * Ces tests valident le comportement de `logoutController`
 * en simulant la déconnexion d’un utilisateur.
 *
 * Objectifs :
 *  - Vérifier la destruction complète de la session.
 *  - S’assurer que la redirection vers la page d’accueil est bien envoyée.
 *  - Tester le comportement avec et sans session préexistante.
 *
 * Méthodologie :
 *  - La fonction `header()` est redéfinie pour lancer une exception simulant une redirection.
 *  - Les exceptions `RuntimeException` servent à vérifier le contenu de la redirection.
 *  - La session est recréée et vidée à chaque test pour garantir l’isolation.
 */
final class LogoutControllerTest extends TestCase
{
    /**
     * Prépare un environnement propre avant chaque test :
     *  - Démarre une session si nécessaire.
     *  - Réinitialise `$_SESSION` et `$_POST`.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Démarre une session si aucune n’est encore active
        if (\session_status() === \PHP_SESSION_NONE) {
            @\session_start();
        }

        // Vide toutes les données de session et POST
        $_SESSION = [];
        $_POST    = [];
    }

    /**
     * Vérifie que la méthode `get()` :
     *  - détruit correctement la session,
     *  - et envoie une redirection vers la page d’accueil.
     *
     * Étapes :
     *  1) Initialise une session simulant un utilisateur connecté.
     *  2) Appelle `logoutController->get()`.
     *  3) Capture la redirection simulée via une exception.
     *  4) Vérifie que :
     *     - la redirection est correcte,
     *     - la session est complètement vidée.
     *
     * @return void
     */
    public function testGet_DestroysSession_And_RedirectsToHomepage(): void
    {
        // Simule une session utilisateur existante
        $_SESSION['email'] = 'user@example.com';
        $_SESSION['role']  = 'doctor';

        // Instancie le contrôleur à tester
        $controller = new logoutController();

        try {
            // Exécute la méthode de déconnexion
            $controller->get();

            // Si aucune exception n’est lancée, c’est une erreur
            $this->fail('Une redirection était attendue');
        } catch (\RuntimeException $e) {
            // Vérifie que l’exception contient bien la redirection attendue
            $this->assertStringContainsString(
                'REDIRECT:Location: /?page=homepage',
                $e->getMessage(),
                'La redirection attendue est absente.'
            );

            // Vérifie que la session a bien été vidée
            $this->assertSame([], $_SESSION, 'La session doit être vide après logout.');
        }
    }

    /**
     * Vérifie que `get()` fonctionne même si aucune session
     * n’était préalablement démarrée ou remplie.
     *
     * Étapes :
     *  1) Vide complètement la session.
     *  2) Exécute `logoutController->get()`.
     *  3) Capture la redirection simulée via exception.
     *  4) Vérifie que la session reste vide et la redirection est correcte.
     *
     * @return void
     */
    public function testGet_Works_WithoutPreStartedSession(): void
    {
        // Vide explicitement la session pour simuler un contexte sans connexion
        $_SESSION = [];

        // Instancie le contrôleur
        $controller = new logoutController();

        try {
            // Exécute la méthode get()
            $controller->get();

            // Si aucune redirection simulée n’est détectée, c’est une erreur
            $this->fail('Une redirection était attendue');
        } catch (\RuntimeException $e) {
            // Vérifie que la redirection est correcte
            $this->assertStringContainsString(
                'REDIRECT:Location: /?page=homepage',
                $e->getMessage(),
                'La redirection attendue est absente.'
            );

            // Vérifie que la session reste vide après appel
            $this->assertSame([], $_SESSION, 'La session doit rester vide.');
        }
    }
}
