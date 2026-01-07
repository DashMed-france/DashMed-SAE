<?php

namespace controllers\pages;

use modules\controllers\pages\MedicalProcedureController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

require_once __DIR__ . '/../../../app/controllers/pages/MedicalProcedureController.php';

/**
 * Unit test class for the MedicalProcedureController.
 *
 * Tests the features for displaying a patient's medical procedures.
 *
 * @coversDefaultClass \modules\controllers\pages\MedicalProcedureController
 */
class MedicalProcedureControllerTest extends TestCase
{
    /**
     * Instance of the MedicalProcedureController to test.
     *
     * @var MedicalProcedureController
     */
    private MedicalProcedureController $controller;

    /**
     * Prepares the test environment.
     *
     * Configures the session and instantiates the controller.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $_SESSION = [];
        $this->controller = new MedicalProcedureController();
    }

    /**
     * Tests that the isUserLoggedIn method returns false if the email is not in the session.
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
     * Tests that the isUserLoggedIn method returns true if the email is in the session.
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
     * Tests that getConsultations returns a non-empty array.
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
     * Tests that getConsultations returns valid Consultation objects.
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
     * Tests that the number of returned consultations is correct.
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
     * Cleanup after each test.
     *
     * Resets the session.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $_SESSION = [];
    }
}