<?php

namespace controllers\pages;

use PHPUnit\Framework\TestCase;
use modules\controllers\pages\MedicalProcedureController;
use modules\models\ConsultationModel;
use modules\models\PatientModel;
use modules\models\UserModel;
use modules\services\PatientContextService;

require_once __DIR__ . '/../../../app/controllers/pages/MedicalProcedureController.php';

/**
 * Class MedicalProcedureControllerTest | Tests du Contrôleur de Procédures Médicales
 *
 * Unit tests for MedicalProcedureController.
 * Tests unitaires pour MedicalProcedureController.
 *
 * @package Tests\Controllers\Pages
 * @author DashMed Team
 */
class MedicalProcedureControllerTest extends TestCase
{
    private $pdoMock;
    private $consultationModelMock;
    private $patientModelMock;
    private $userModelMock;
    private $contextServiceMock;

    /**
     * Setup test environment.
     * Configuration de l'environnement de test.
     */
    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(\PDO::class);
        $this->consultationModelMock = $this->createMock(ConsultationModel::class);
        $this->patientModelMock = $this->createMock(PatientModel::class);
        $this->userModelMock = $this->createMock(UserModel::class);
        $this->contextServiceMock = $this->createMock(PatientContextService::class);

        $_SESSION = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $_SESSION['email'] = 'test@example.com';
        $_SESSION['user_id'] = 1;
        $_SESSION['first_name'] = 'Jean';
        $_SESSION['last_name'] = 'Test';
        $_SESSION['profession_label'] = 'Mededin';
    }

    private function createController()
    {
        $controller = new MedicalProcedureController($this->pdoMock);

        $ref = new \ReflectionClass($controller);

        $p1 = $ref->getProperty('consultationModel');
        $p1->setAccessible(true);
        $p1->setValue($controller, $this->consultationModelMock);

        $p2 = $ref->getProperty('patientModel');
        $p2->setAccessible(true);
        $p2->setValue($controller, $this->patientModelMock);

        $p3 = $ref->getProperty('userModel');
        $p3->setAccessible(true);
        $p3->setValue($controller, $this->userModelMock);

        $p4 = $ref->getProperty('contextService');
        $p4->setAccessible(true);
        $p4->setValue($controller, $this->contextServiceMock);

        return $controller;
    }

    /**
     * Test GET shows view with consultations.
     * Teste que GET affiche la vue avec les consultations.
     */
    public function testGetShowViewWithConsultations()
    {
        $this->contextServiceMock->method('getCurrentPatientId')->willReturn(10);

        $c1 = new class {
            public function getId()
            {
                return 101;
            }
            public function getDate()
            {
                return '2023-01-01';
            }
            public function getTitle()
            {
                return 'Titre 1';
            }
            public function getType()
            {
                return 'Type 1';
            }
            public function getDoctorId()
            {
                return 1;
            }
            public function getDoctor()
            {
                return 'Dupont';
            }
            public function getNote()
            {
                return 'Note 1';
            }
            public function getDocument()
            {
                return 'Doc.pdf';
            }
        };

        $this->consultationModelMock->method('getConsultationsByPatientId')
            ->with(10)
            ->willReturn([$c1]);

        $this->userModelMock->method('getAllDoctors')->willReturn([
            ['id_user' => 1, 'first_name' => 'Jean', 'last_name' => 'Dupont']
        ]);
        $this->userModelMock->method('getById')->willReturn(['admin_status' => 0]);

        $controller = $this->createController();

        ob_start();
        try {
            $controller->get();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        $output = ob_get_clean();


        $this->assertStringContainsString('Titre 1', $output);
        $this->assertStringContainsString('Dupont', $output);
    }
}
