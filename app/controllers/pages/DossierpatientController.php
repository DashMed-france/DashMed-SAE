use modules\views\pages\dossierpatientView;
use modules\models\ConsultationModel;
use Database;
use PDO;

// ... (keep class definition)

public function get(): void
{
if (!$this->isUserLoggedIn()) {
header('Location: /?page=login');
exit();
}
// TODO: récupérer dynamiquement l'ID du patient (route/session)
$idPatient = 1;

// Récupération des données patient
$patientData = $this->getPatientData($idPatient);

// Use Model directly instead of Service
$model = new ConsultationModel($this->pdo);
$toutesConsultations = $model->getConsultationsByPatientId($idPatient);

$dateAujourdhui = new \DateTime();
$consultationsPassees = [];
$consultationsFutures = [];

foreach ($toutesConsultations as $consultation) {
// Updated Date Parsing to be more robust for DB format (Y-m-d H:i:s)
try {
$dateConsultation = new \DateTime($consultation->getDate());
} catch (\Exception $e) {
$dateConsultation = new \DateTime(); // Fallback
}

if ($dateConsultation < $dateAujourdhui) { $consultationsPassees[]=$consultation; } else {
    $consultationsFutures[]=$consultation; } } $msg=$_SESSION['patient_msg'] ?? null; unset($_SESSION['patient_msg']);
    $view=new dossierpatientView($consultationsPassees, $consultationsFutures, $patientData, $msg); $view->show();
    }

    // ... (keep post, getPatientData, isUserLoggedIn)

    // Remove getConsultations method