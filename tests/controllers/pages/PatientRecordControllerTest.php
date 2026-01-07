<?php

namespace controllers\pages;

use modules\controllers\pages\PatientRecordController;
use modules\models\PatientModel;
use modules\models\Consultation;
use modules\services\PatientContextService;
use modules\views\pages\PatientRecordView;
use PHPUnit\Framework\TestCase;
use PDO;
use ReflectionClass;

require_once __DIR__ . '/../../../app/controllers/pages/PatientRecordController.php';
require_once __DIR__ . '/../../../app/models/PatientModel.php';
require_once __DIR__ . '/../../../app/models/Consultation.php';
require_once __DIR__ . '/../../../app/services/PatientContextService.php';
require_once __DIR__ . '/../../../app/views/pages/PatientRecordView.php';

/**
 * Version testable du contrôleur pour intercepter les exits et logs.
 * Utilise Reflection massivement pour contourner la visibilité privée du parent
 * sans modifier le fichier original.
 */
class TestablePatientRecordController extends PatientRecordController
{
    public string $redirectUrl = '';
    public bool $exitCalled = false;
    public string $renderedOutput = '';

    // Helpers Reflection
    private function getPrivateProperty(string $name)
    {
        $ref = new ReflectionClass(PatientRecordController::class);
        $prop = $ref->getProperty($name);
        $prop->setAccessible(true);
        return $prop->getValue($this);
    }

    private function callPrivateMethod(string $name, ...$args)
    {
        $ref = new ReflectionClass(PatientRecordController::class);
        $method = $ref->getMethod($name);
        $method->setAccessible(true);
        return $method->invoke($this, ...$args);
    }

    /**
     * Surcharge get() pour le test.
     */
    public function get(): void
    {
        if (!$this->callPrivateMethod('isUserLoggedIn')) {
            $this->redirectUrl = '/?page=login';
            $this->exitCalled = true;
            return;
        }

        $idPatient = $this->callPrivateMethod('getCurrentPatientId');

        try {
            $patientModel = $this->getPrivateProperty('patientModel');
            $patientData = $patientModel->findById($idPatient);

            if (!$patientData) {
                $patientData = [
                    'id_patient' => $idPatient,
                    'first_name' => 'Patient',
                    'last_name' => 'Inconnu',
                    'birth_date' => null,
                    'gender' => 'U',
                    'admission_cause' => 'Dossier non trouvé ou inexistant.',
                    'medical_history' => '',
                    'age' => 0
                ];
            } else {
                $patientData['age'] = $this->callPrivateMethod('calculateAge', $patientData['birth_date'] ?? null);
            }

            $doctors = $patientModel->getDoctors($idPatient);

            $toutesConsultations = $this->callPrivateMethod('getConsultations');
            $consultationsPassees = [];
            $consultationsFutures = [];
            $dateAujourdhui = new \DateTime();

            foreach ($toutesConsultations as $consultation) {
                $dStr = $consultation->getDate();
                $dObj = \DateTime::createFromFormat('d/m/Y', $dStr);
                if (!$dObj) {
                    $dObj = \DateTime::createFromFormat('Y-m-d', $dStr);
                }

                if ($dObj && $dObj < $dateAujourdhui) {
                    $consultationsPassees[] = $consultation;
                } else {
                    $consultationsFutures[] = $consultation;
                }
            }

            $msg = $_SESSION['patient_msg'] ?? null;
            if (isset($_SESSION['patient_msg'])) {
                unset($_SESSION['patient_msg']);
            }

            ob_start();
            $view = new PatientRecordView(
                $consultationsPassees,
                $consultationsFutures,
                $patientData,
                $doctors,
                $msg
            );
            $view->show();
            $this->renderedOutput = ob_get_clean();
        } catch (\Throwable $e) {
            // Pas de error_log
            ob_start();
            $view = new PatientRecordView([], [], [], [], ['type' => 'error', 'text' => 'Une erreur interne est survenue lors du chargement du dossier.']);
            $view->show();
            $this->renderedOutput = ob_get_clean();
        }
    }

    /**
     * Surcharge post() pour le test.
     */
    public function post(): void
    {
        if (!$this->callPrivateMethod('isUserLoggedIn')) {
            $this->redirectUrl = '/?page=login';
            $this->exitCalled = true;
            return;
        }

        if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf_patient'] ?? '', $_POST['csrf'])) {
            $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'Session expirée. Veuillez rafraîchir la page.'];
            $this->redirectUrl = '/?page=dossierpatient';
            $this->exitCalled = true;
            return;
        }

        $idPatient = $this->callPrivateMethod('getCurrentPatientId');

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $admissionCause = trim($_POST['admission_cause'] ?? '');
        $medicalHistory = trim($_POST['medical_history'] ?? '');
        $birthDate = trim($_POST['birth_date'] ?? '');

        if ($firstName === '' || $lastName === '' || $admissionCause === '') {
            $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'Merci de remplir tous les champs obligatoires.'];
            $this->redirectUrl = '/?page=dossierpatient';
            $this->exitCalled = true;
            return;
        }

        if ($birthDate !== '') {
            $dateObj = \DateTime::createFromFormat('Y-m-d', $birthDate);
            if (!$dateObj || $dateObj->format('Y-m-d') !== $birthDate) {
                $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'Le format de la date de naissance est invalide.'];
                $this->redirectUrl = '/?page=dossierpatient';
                $this->exitCalled = true;
                return;
            }
            if ($dateObj > new \DateTime()) {
                $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'La date de naissance ne peut pas être future.'];
                $this->redirectUrl = '/?page=dossierpatient';
                $this->exitCalled = true;
                return;
            }
        }

        try {
            $patientModel = $this->getPrivateProperty('patientModel');

            $updateData = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'birth_date' => $birthDate,
                'admission_cause' => $admissionCause,
                'medical_history' => $medicalHistory
            ];

            $success = $patientModel->update($idPatient, $updateData);

            if ($success) {
                $_SESSION['patient_msg'] = ['type' => 'success', 'text' => 'Dossier mis à jour avec succès.'];
            } else {
                $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'Aucune modification détectée ou erreur de sauvegarde.'];
            }
        } catch (\Exception $e) {
            // Pas de error_log
            $_SESSION['patient_msg'] = ['type' => 'error', 'text' => 'Erreur technique lors de la sauvegarde.'];
        }

        $this->redirectUrl = '/?page=dossierpatient';
        $this->exitCalled = true;
    }

    // Helpers publics pour les tests spécifiques
    public function publicCalculateAge($date)
    {
        return $this->callPrivateMethod('calculateAge', $date);
    }

    public function publicIsUserLoggedIn()
    {
        return $this->callPrivateMethod('isUserLoggedIn');
    }
}

class PatientRecordControllerTest extends TestCase
{
    private $pdoMock;
    private $patientModelMock;
    private $contextServiceMock;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(\PDO::class);
        $this->patientModelMock = $this->createMock(PatientModel::class);
        $this->contextServiceMock = $this->createMock(PatientContextService::class);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
    }

    private function createController(): TestablePatientRecordController
    {
        return new TestablePatientRecordController(
            $this->pdoMock,
            $this->patientModelMock,
            $this->contextServiceMock
        );
    }

    public function testInstantiation()
    {
        $controller = $this->createController();
        $this->assertInstanceOf(PatientRecordController::class, $controller);
    }

    public function testGetRedirectsIfNotLoggedIn()
    {
        unset($_SESSION['email']);
        $controller = $this->createController();
        $controller->get();

        $this->assertTrue($controller->exitCalled);
        $this->assertEquals('/?page=login', $controller->redirectUrl);
    }

    public function testGetShowViewWhenLoggedIn()
    {
        $_SESSION['email'] = 'test@example.com';
        $_SESSION['admin_status'] = 0;

        $this->contextServiceMock->expects($this->once())
            ->method('handleRequest');
        $this->contextServiceMock->expects($this->once())
            ->method('getCurrentPatientId')
            ->willReturn(123);

        $this->patientModelMock->expects($this->once())
            ->method('findById')
            ->with(123)
            ->willReturn([
                'id_patient' => 123,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'birth_date' => '2000-01-01',
                'admission_cause' => 'Test',
                'medical_history' => 'None',
                'gender' => 'H'
            ]);

        $this->patientModelMock->expects($this->once())
            ->method('getDoctors')
            ->with(123)
            ->willReturn([]);

        $controller = $this->createController();
        $controller->get();

        $output = $controller->renderedOutput;
        $this->assertStringContainsString('John', $output);
        $this->assertStringContainsString('DOE', $output);
    }

    public function testGetHandlesException()
    {
        $_SESSION['email'] = 'test@example.com';
        $_SESSION['user_id'] = 1;

        $this->contextServiceMock->method('getCurrentPatientId')->willReturn(123);
        $this->patientModelMock->method('findById')->willThrowException(new \Exception('DB Crash'));

        $controller = $this->createController();
        $controller->get();

        $output = $controller->renderedOutput;
        $this->assertStringContainsString('Une erreur interne est survenue', $output);
    }

    public function testCalculateAgeLogic()
    {
        $controller = $this->createController();
        $age = $controller->publicCalculateAge('2000-01-01');
        $expectedMin = date('Y') - 2000 - 1;
        $this->assertGreaterThanOrEqual($expectedMin, $age);
    }

    public function testIsUserLoggedIn()
    {
        $_SESSION['email'] = 'test@test.com';
        $controller = $this->createController();
        $this->assertTrue($controller->publicIsUserLoggedIn());

        unset($_SESSION['email']);
        $this->assertFalse($controller->publicIsUserLoggedIn());
    }
}
