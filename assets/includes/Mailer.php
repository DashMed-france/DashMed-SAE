<?php

/**
 * DashMed — Utilitaire d’envoi d’e-mails
 *
 * Cette classe est une légère surcouche de PHPMailer permettant de gérer
 * l’envoi d’e-mails sur l’ensemble de la plateforme DashMed
 * (ex. : réinitialisation de mot de passe, confirmation de compte).
 * Elle charge les identifiants SMTP depuis les variables d’environnement
 * et établit une connexion sécurisée via SSL ou TLS selon la configuration.
 *
 * @package   DashMed\assets\includes
 * @author    Équipe DashMed
 * @license   Propriétaire
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Fournit une abstraction pour l’envoi d’e-mails via PHPMailer.
 *
 * Responsabilités :
 *  - Initialiser PHPMailer avec la configuration SMTP issue des variables d’environnement.
 *  - Gérer le chiffrement SSL ou TLS selon la variable SMTP_SECURE.
 *  - Définir une adresse "From" par défaut pour tous les messages sortants.
 *  - Offrir une méthode send() simple pour envoyer des e-mails HTML.
 */
final class Mailer
{
    /**
     * Instance de PHPMailer utilisée pour l’envoi des e-mails.
     *
     * @var PHPMailer
     */
    private PHPMailer $m;

    /**
     * Initialise le Mailer et configure PHPMailer avec les identifiants SMTP.
     *
     * Lit la configuration depuis les variables d’environnement :
     *  - SMTP_HOST
     *  - SMTP_USER
     *  - SMTP_PASS
     *  - SMTP_PORT
     *  - SMTP_SECURE (ssl|tls)
     *
     * @throws Exception Si PHPMailer échoue à s’initialiser.
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
     * Envoie un e-mail HTML à un destinataire spécifié.
     *
     * @param string $to       Adresse e-mail du destinataire.
     * @param string $subject  Sujet de l’e-mail.
     * @param string $html     Contenu HTML du message.
     *
     * @return void
     * @throws Exception Si l’envoi du message échoue.
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
