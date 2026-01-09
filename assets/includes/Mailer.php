<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Class Mailer | Utilitaire d'envoi d'e-mails
 *
 * Wrapper for PHPMailer to handle email sending.
 * Surcouche de PHPMailer pour gérer l'envoi d'e-mails.
 *
 * Uses SMTP configuration from environment variables.
 * Utilise la configuration SMTP des variables d'environnement.
 *
 * @package DashMed\Assets\Includes
 * @author DashMed Team
 * @license Proprietary
 */
final class Mailer
{
    /** @var PHPMailer PHPMailer instance | Instance PHPMailer */
    private PHPMailer $m;

    /**
     * Constructor.
     * Constructeur.
     *
     * Initializes PHPMailer with SMTP config.
     * Initialise PHPMailer avec la configuration SMTP.
     *
     * @throws Exception If initialization fails | Si l'initialisation échoue.
     */
    public function __construct()
    {
        require_once __DIR__ . '/../../vendor/autoload.php';

        $host = $_ENV['SMTP_HOST'] ?? '';
        $user = $_ENV['SMTP_USER'] ?? '';
        $pass = $_ENV['SMTP_PASS'] ?? '';
        $port = (int) ($_ENV['SMTP_PORT'] ?? 465);
        $sec = strtolower($_ENV['SMTP_SECURE'] ?? 'ssl');

        $this->m = new PHPMailer(true);
        $this->m->isSMTP();
        $this->m->Host = $host;
        $this->m->SMTPAuth = true;
        $this->m->Username = $user;
        $this->m->Password = $pass;
        $this->m->Port = $port;
        $this->m->CharSet = 'UTF-8';

        if ($sec === 'ssl') {
            $this->m->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $this->m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        if (!empty($user)) {
            $this->m->setFrom($user, 'Support DashMed');
        }
    }

    /**
     * Sends an HTML email.
     * Envoie un e-mail HTML.
     *
     * @param string $to      Recipient email | Email du destinataire.
     * @param string $subject Email subject | Sujet de l'email.
     * @param string $html    HTML content | Contenu HTML.
     *
     * @return void
     * @throws Exception If send fails | Si l'envoi échoue.
     */
    public function send(string $to, string $subject, string $html): void
    {
        $this->m->clearAddresses();
        $this->m->isHTML(true);
        $this->m->addAddress($to);
        $this->m->Subject = $subject;
        $this->m->Body = $html;
        $this->m->AltBody = strip_tags($html);
        $this->m->send();
    }
}
