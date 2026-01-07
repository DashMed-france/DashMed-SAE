<?php

/**
 * DashMed — Email Sending Utility
 *
 * This class is a lightweight wrapper for PHPMailer to handle
 * email sending across the DashMed platform
 * (e.g., password reset, account confirmation).
 * It loads SMTP credentials from environment variables
 * and establishes a secure connection via SSL or TLS based on configuration.
 *
 * @package   DashMed\assets\includes
 * @author    DashMed Team
 * @license   Proprietary
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Provides an abstraction for sending emails via PHPMailer.
 *
 * Responsibilities:
 * - Initialize PHPMailer with SMTP configuration from environment variables.
 * - Handle SSL or TLS encryption based on the SMTP_SECURE variable.
 * - Set a default "From" address for all outgoing messages.
 * - Provide a simple send() method for sending HTML emails.
 */
final class Mailer
{
    /**
     * PHPMailer instance used for sending emails.
     *
     * @var PHPMailer
     */
    private PHPMailer $m;

    /**
     * Initializes the Mailer and configures PHPMailer with SMTP credentials.
     *
     * Reads configuration from environment variables:
     * - SMTP_HOST
     * - SMTP_USER
     * - SMTP_PASS
     * - SMTP_PORT
     * - SMTP_SECURE (ssl|tls)
     *
     * @throws Exception If PHPMailer fails to initialize.
     */
    public function __construct()
    {
        require_once __DIR__ . '/../../vendor/autoload.php';

        $host = $_ENV['SMTP_HOST'] ?? '';
        $user = $_ENV['SMTP_USER'] ?? '';
        $pass = $_ENV['SMTP_PASS'] ?? '';
        $port = (int)($_ENV['SMTP_PORT'] ?? 465);
        $sec  = strtolower($_ENV['SMTP_SECURE'] ?? 'ssl');

        $this->m = new PHPMailer(true);
        $this->m->isSMTP();
        $this->m->Host       = $host;
        $this->m->SMTPAuth   = true;
        $this->m->Username   = $user;
        $this->m->Password   = $pass;
        $this->m->Port       = $port;
        $this->m->CharSet    = 'UTF-8';

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
     * Sends an HTML email to a specified recipient.
     *
     * @param string $to       Recipient's email address.
     * @param string $subject  Email subject.
     * @param string $html     HTML content of the message.
     *
     * @return void
     * @throws Exception If message delivery fails.
     */
    public function send(string $to, string $subject, string $html): void
    {
        $this->m->clearAddresses();
        $this->m->isHTML(true);
        $this->m->addAddress($to);
        $this->m->Subject = $subject;
        $this->m->Body    = $html;
        $this->m->AltBody = strip_tags($html);
        $this->m->send();
    }
}