<?php

declare(strict_types=1);

namespace assets\includes;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Class Mailer
 *
 * Wrapper for PHPMailer to handle email sending.
 *
 * Uses SMTP configuration from environment variables.
 *
 * @package DashMed\Assets\Includes
 * @author DashMed Team
 * @license Proprietary
 *
 * @access public
 */
final class Mailer
{
    /** @var PHPMailer PHPMailer instance */
    private PHPMailer $m;

    /**
     * Constructor.
     *
     * Initializes PHPMailer with SMTP config.
     *
     * @throws Exception If initialization fails.
     */
    public function __construct()
    {
        require_once __DIR__ . '/../../vendor/autoload.php';

        $host = isset($_ENV['SMTP_HOST']) && is_string($_ENV['SMTP_HOST']) ? $_ENV['SMTP_HOST'] : '';
        $user = isset($_ENV['SMTP_USER']) && is_string($_ENV['SMTP_USER']) ? $_ENV['SMTP_USER'] : '';
        $pass = isset($_ENV['SMTP_PASS']) && is_string($_ENV['SMTP_PASS']) ? $_ENV['SMTP_PASS'] : '';
        $portRaw = $_ENV['SMTP_PORT'] ?? 465;
        $port = is_numeric($portRaw) ? (int) $portRaw : 465;
        $secRaw = $_ENV['SMTP_SECURE'] ?? 'ssl';
        $sec = is_string($secRaw) ? strtolower($secRaw) : 'ssl';

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

        if ($user !== '') {
            $this->m->setFrom($user, 'Support DashMed');
        }
    }

    /**
     * Sends an HTML email.
     *
     * @param string $to      Recipient email.
     * @param string $subject Email subject.
     * @param string $html    HTML content.
     *
     * @return void
     * @throws Exception If send fails.
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
