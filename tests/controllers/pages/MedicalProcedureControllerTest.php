<?php

namespace controllers\pages;

use PHPUnit\Framework\TestCase;
use modules\controllers\pages\MedicalProcedureController;
use modules\models\ConsultationModel;
use modules\models\PatientModel;
use modules\models\UserModel;
use modules\services\PatientContextService;

// Le contrôleur inclut la vraie vue. On ne peut pas la mocker globalement.
require_once __DIR__ . '/../../../app/controllers/pages/MedicalProcedureController.php';

class MedicalProcedureControllerTest extends TestCase
{
    private $pdoMock;
    private $consultationModelMock;
    private $patientModelMock;
    private $userModelMock;
    private $contextServiceMock;

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

        // Mock session user
        $_SESSION['email'] = 'test@example.com';
        $_SESSION['user_id'] = 1;
        $_SESSION['first_name'] = 'Jean'; // Pour sidebar potentiellement
        $_SESSION['last_name'] = 'Test'; // Pour sidebar potentiellement
        $_SESSION['profession_label'] = 'Mededin'; // Pour sidebar
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

    public function testGetShowViewWithConsultations()
    {
        $this->contextServiceMock->method('getCurrentPatientId')->willReturn(10);

        // Mock consultation object avec toutes les méthodes attendues par la vue
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

        // On capture la sortie car la vue fait des echo et include
        ob_start();
        try {
            $controller->get();
        } catch (\Throwable $e) {
            ob_end_clean();
            // Si la vue plante à cause des includes (sidebar/searchbar), on ignore SI c'est juste un warning
            // Mais si c'est fatal, le test échoue.
            // Si sidebar demande des fichiers non présents, ça peut planter.
            // On peut espérer que sidebar est incluse via dirname(__DIR__) donc relatif, ça devrait marcher si les fichiers existent.
            // Mais sidebar utilise peut-être Database ou Session.
            // Session est mockée. Database ? Si sidebar utilise Database::getInstance(), on a un problème si elle n'est pas chargée ou configurée.
            // Heureusement PasswordControllerTest a hacké Database::instance.
            // Mais ici MedicalProcedureControllerTest est Isolé? Non.
            // Si la sidebar appelle Database::getInstance(), il faut que Database soit prête.
            // J'ai injecté PDO dans MedicalProcedureController.
            // Mais sidebar pourrait faire `new SomeModel()` qui fait `Database::getInstance()`.

            // Pour l'instant, signalons l'erreur si elle survient.
            throw $e;
        }
        $output = ob_get_clean();

        // On vérifie que la sortie contient des éléments attendus
        $this->assertStringContainsString('Titre 1', $output);
        $this->assertStringContainsString('Dupont', $output);
    }
}
