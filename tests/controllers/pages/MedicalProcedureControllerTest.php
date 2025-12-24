<?php

namespace controllers\pages;

use modules\controllers\pages\MedicalProcedureController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

require_once __DIR__ . '/../../../app/controllers/pages/MedicalProcedureController.php';

/**
 * Classe de tests unitaires pour le contrôleur MedicalProcedureController.
 *
 * Teste les fonctionnalités d'affichage des actes médicaux du patient.
 *
 * @coversDefaultClass \modules\controllers\pages\MedicalProcedureController
 */
class MedicalProcedureControllerTest extends TestCase
{
    /**
     * Instance du contrôleur MedicalProcedureController à tester.
     *
     * @var MedicalProcedureController
     */
    private MedicalProcedureController $controller;

    /**
     * Prépare l'environnement de test.
     *
     * Configure la session et instancie le contrôleur.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $_SESSION = [];
        $this->controller = new MedicalProcedureController();
    }

    /**
     * Teste que la méthode isUserLoggedIn retourne false si l'email n'est pas en session.
     *
     * @covers ::isUserLoggedIn
     * @return void
     */
    public function testIsUserLoggedInReturnsFalseWhenNotLoggedIn(): void
    {
        $_SESSION = [];

        $reflection = new ReflectionClass(MedicalProcedureController::class);
        $method = $reflection->getMethod('isUserLoggedIn');
        $result = $method->invoke($this->controller);

        $this->assertFalse($result, "L'utilisateur ne devrait pas être connecté");
    }

    /**
     * Teste que la méthode isUserLoggedIn retourne true si l'email est en session.
     *
     * @covers ::isUserLoggedIn
     * @return void
     */
    public function testIsUserLoggedInReturnsTrueWhenLoggedIn(): void
    {
        $_SESSION['email'] = 'test@example.com';

        $reflection = new ReflectionClass(MedicalProcedureController::class);
        $method = $reflection->getMethod('isUserLoggedIn');
        $result = $method->invoke($this->controller);

        $this->assertTrue($result, "L'utilisateur devrait être connecté");
    }

    /**
     * Teste que getConsultations retourne un tableau non vide.
     *
     * @covers ::getConsultations
     * @return void
     */
    public function testGetConsultationsReturnsNonEmptyArray(): void
    {
        $reflection = new ReflectionClass(MedicalProcedureController::class);
        $method = $reflection->getMethod('getConsultations');
        $result = $method->invoke($this->controller);

        $this->assertIsArray($result, "Le résultat devrait être un tableau");
        $this->assertNotEmpty($result, "Le tableau des consultations ne devrait pas être vide");
    }

    /**
     * Teste que getConsultations retourne des objets Consultation valides.
     *
     * @covers ::getConsultations
     * @return void
     */
    public function testGetConsultationsReturnsValidConsultationObjects(): void
    {
        $reflection = new ReflectionClass(MedicalProcedureController::class);
        $method = $reflection->getMethod('getConsultations');

        $consultations = $method->invoke($this->controller);

        foreach ($consultations as $consultation) {
            $this->assertInstanceOf(
                \modules\models\Consultation::class,
                $consultation,
                "Chaque élément devrait être une instance de Consultation"
            );
        }
    }

    /**
     * Teste que le nombre de consultations retournées est correct.
     *
     * @covers ::getConsultations
     * @return void
     */
    public function testGetConsultationsReturnsExpectedCount(): void
    {
        $reflection = new ReflectionClass(MedicalProcedureController::class);
        $method = $reflection->getMethod('getConsultations');

        $consultations = $method->invoke($this->controller);

        $this->assertCount(3, $consultations, "Il devrait y avoir exactement 3 consultations");
    }

    /**
     * Nettoyage après chaque test.
     *
     * Réinitialise la session.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $_SESSION = [];
    }
}
