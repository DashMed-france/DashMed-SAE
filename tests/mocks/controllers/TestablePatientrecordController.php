<?php

namespace controllers\pages;

use modules\controllers\pages\PatientrecordController;
use modules\views\pages\PatientrecordView;
use ReflectionClass;

require_once __DIR__ . '/../../../app/controllers/pages/PatientrecordController.php';
require_once __DIR__ . '/../../../app/views/pages/PatientrecordView.php';

/**
 * Class TestablePatientrecordController
 *
 * Testable version of the controller to intercept exits and logs.
 * Version testable du contrôleur pour intercepter les exits et logs.
 *
 * Uses Reflection to bypass private visibility without modifying original file.
 * Utilise Reflection massivement pour contourner la visibilité privée du parent
 * sans modifier le fichier original.
 */
class TestablePatientrecordController extends PatientrecordController
{
    public string $redirectUrl = '';
    public bool $exitCalled = false;
    public string $renderedOutput = '';

    // Helpers Reflection
    private function getPrivateProperty(string $name)
    {
        $ref = new ReflectionClass(PatientrecordController::class);
        $prop = $ref->getProperty($name);
        $prop->setAccessible(true);
        return $prop->getValue($this);
    }

    private function callPrivateMethod(string $name, ...$args)
    {
        $ref = new ReflectionClass(PatientrecordController::class);
        $method = $ref->getMethod($name);
        $method->setAccessible(true);
        return $method->invoke($this, ...$args);
    }

    /**
     * Override get() for testing.
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
                $patientData['age'] = $this->callPrivateMethod(
                    'calculateAge',
                    $patientData['birth_date'] ?? null
                );
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
            $view = new PatientrecordView(
                $consultationsPassees,
                $consultationsFutures,
                $patientData,
                $doctors,
                $msg
            );
            $view->show();
            $this->renderedOutput = ob_get_clean();
        } catch (\Throwable $e) {
            ob_start();
            $view = new PatientrecordView(
                [],
                [],
                [],
                [],
                ['type' => 'error', 'text' => 'Une erreur interne est survenue lors du chargement du dossier.']
            );
            $view->show();
            $this->renderedOutput = ob_get_clean();
        }
    }

    /**
     * Override post() for testing.
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
            $_SESSION['patient_msg'] =
                ['type' => 'error', 'text' => 'Merci de remplir tous les champs obligatoires.'];
            $this->redirectUrl = '/?page=dossierpatient';
            $this->exitCalled = true;
            return;
        }

        if ($birthDate !== '') {
            $dateObj = \DateTime::createFromFormat('Y-m-d', $birthDate);
            if (!$dateObj || $dateObj->format('Y-m-d') !== $birthDate) {
                $_SESSION['patient_msg'] =
                    ['type' => 'error', 'text' => 'Le format de la date de naissance est invalide.'];
                $this->redirectUrl = '/?page=dossierpatient';
                $this->exitCalled = true;
                return;
            }
            if ($dateObj > new \DateTime()) {
                $_SESSION['patient_msg'] =
                    ['type' => 'error', 'text' => 'La date de naissance ne peut pas être future.'];
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
                $_SESSION['patient_msg'] =
                    ['type' => 'success', 'text' => 'Dossier mis à jour avec succès.'];
            } else {
                $_SESSION['patient_msg'] =
                    ['type' => 'error', 'text' => 'Aucune modification détectée ou erreur de sauvegarde.'];
            }
        } catch (\Exception $e) {
            $_SESSION['patient_msg'] =
                ['type' => 'error', 'text' => 'Erreur technique lors de la sauvegarde.'];
        }

        $this->redirectUrl = '/?page=dossierpatient';
        $this->exitCalled = true;
    }

    public function publicCalculateAge($date)
    {
        return $this->callPrivateMethod('calculateAge', $date);
    }

    public function publicIsUserLoggedIn()
    {
        return $this->callPrivateMethod('isUserLoggedIn');
    }
}
