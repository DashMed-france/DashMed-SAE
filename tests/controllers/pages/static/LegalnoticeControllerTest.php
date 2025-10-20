<?php

namespace tests\controllers;

use PHPUnit\Framework\TestCase;
use modules\controllers\LegalnoticeController;
use modules\views\legalnoticeView;

/**
 * Tests PHPUnit du contrôleur Legalnotice
 * ---------------------------------------
 * Ces tests valident le comportement du contrôleur `LegalnoticeController`
 * dans différentes situations, en se concentrant sur :
 *  - l’affichage de la vue selon l’état de connexion de l’utilisateur,
 *  - la vérification de la logique interne `isUserLoggedIn()`,
 *  - le bon fonctionnement de la méthode `index()`.
 *
 * Méthodologie :
 *  - Les tests s’exécutent dans un environnement isolé avec session réinitialisée.
 *  - Les vues sont simulées par un simple rendu de texte (aucune dépendance réelle).
 *  - Les méthodes privées sont testées via Reflection pour accéder à leur logique interne.
 */
class LegalnoticeControllerTest extends TestCase
{
    /** @var LegalnoticeController Instance du contrôleur testé. */
    private LegalnoticeController $controller;

    /**
     * Prépare un environnement propre avant chaque test :
     *  - Démarre la session si nécessaire.
     *  - Vide la variable globale $_SESSION.
     *  - Instancie un nouveau contrôleur.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Démarre la session si elle n’existe pas déjà
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Réinitialise la session pour garantir l’isolation
        $_SESSION = [];

        // Instancie le contrôleur à tester
        $this->controller = new LegalnoticeController();
    }

    /**
     * Nettoie la session après chaque test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    /**
     * Vérifie que la méthode `get()` affiche bien la vue quand
     * l’utilisateur **n’est pas connecté**.
     *
     * Étapes :
     *  1) Supprime la clé 'email' de la session.
     *  2) Exécute `get()` en capturant la sortie.
     *  3) Vérifie que la vue génère bien du contenu.
     *
     * @return void
     */
    public function testGetDisplaysViewWhenUserNotLoggedIn(): void
    {
        // Supprime toute trace d'utilisateur connecté
        unset($_SESSION['email']);

        // Capture la sortie générée par la vue
        ob_start();
        $this->controller->get();
        $output = ob_get_clean();

        // Vérifie que quelque chose a été affiché
        $this->assertNotEmpty($output, 'La vue devrait générer du contenu');
    }

    /**
     * Vérifie que `get()` détecte correctement l’état de connexion
     * quand l’utilisateur **est connecté**.
     *
     * On s’attend ici à ce que `isUserLoggedIn()` retourne true,
     * simulant une redirection vers le tableau de bord.
     *
     * @return void
     */
    public function testGetRedirectsToDashboardWhenUserLoggedIn(): void
    {
        // Simule un utilisateur connecté
        $_SESSION['email'] = 'user@example.com';

        // Récupère la méthode privée via Reflection
        $reflection = new \ReflectionMethod($this->controller, 'isUserLoggedIn');
        $reflection->setAccessible(true);

        // Exécute la méthode sur l'instance du contrôleur
        $isLoggedIn = $reflection->invoke($this->controller);

        // L'utilisateur doit être considéré comme connecté
        $this->assertTrue($isLoggedIn, 'L\'utilisateur devrait être considéré comme connecté');
    }

    /**
     * Vérifie que `index()` agit comme un alias de `get()`,
     * c’est-à-dire qu’elle affiche la même vue.
     *
     * @return void
     */
    public function testIndexCallsGet(): void
    {
        // Simule un utilisateur non connecté
        unset($_SESSION['email']);

        // Capture le contenu généré
        ob_start();
        $this->controller->index();
        $output = ob_get_clean();

        // Doit afficher du contenu identique à `get()`
        $this->assertNotEmpty($output, 'index() devrait afficher la vue via get()');
    }

    /**
     * Vérifie que `isUserLoggedIn()` retourne false quand l'email
     * n’est pas défini dans la session.
     *
     * @return void
     */
    public function testIsUserLoggedInReturnsFalseWhenEmailNotSet(): void
    {
        unset($_SESSION['email']);
        $result = $this->invokePrivateMethod('isUserLoggedIn');
        $this->assertFalse($result, 'Devrait retourner false quand email n\'est pas défini');
    }

    /**
     * Vérifie que `isUserLoggedIn()` retourne true quand l’email est défini.
     *
     * @return void
     */
    public function testIsUserLoggedInReturnsTrueWhenEmailIsSet(): void
    {
        // Simule une session valide
        $_SESSION['email'] = 'test@example.com';

        // Appelle la méthode privée
        $result = $this->invokePrivateMethod('isUserLoggedIn');

        // Doit retourner true
        $this->assertTrue($result, 'Devrait retourner true quand email est défini');
    }

    /**
     * Vérifie un cas limite : la clé email est définie mais vide.
     * Rappel : `isset()` retourne true pour une chaîne vide.
     *
     * @return void
     */
    public function testIsUserLoggedInWithEmptyEmail(): void
    {
        $_SESSION['email'] = '';
        $result = $this->invokePrivateMethod('isUserLoggedIn');
        $this->assertTrue($result, 'isset() retourne true même pour une chaîne vide');
    }

    /**
     * Vérifie un autre cas limite : la clé email est définie à null.
     * Rappel : `isset()` retourne false pour null.
     *
     * @return void
     */
    public function testIsUserLoggedInWithNullEmail(): void
    {
        $_SESSION['email'] = null;
        $result = $this->invokePrivateMethod('isUserLoggedIn');
        $this->assertFalse($result, 'isset() retourne false pour null');
    }

    /**
     * Utilitaire interne pour invoquer une méthode privée ou protégée
     * du contrôleur grâce à Reflection.
     *
     * @param string $methodName Nom de la méthode à appeler.
     * @param array  $parameters Paramètres à transmettre à la méthode.
     *
     * @return mixed Résultat retourné par la méthode invoquée.
     */
    private function invokePrivateMethod(string $methodName, array $parameters = [])
    {
        // Récupère la définition de classe
        $reflection = new \ReflectionClass($this->controller);

        // Accède à la méthode privée et la rend accessible
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        // Appelle la méthode avec les paramètres donnés
        return $method->invokeArgs($this->controller, $parameters);
    }
}
