<?php

/**
 * DashMed — Mailer View
 *
 * Generates the HTML content of an email sent by the DashMed platform.
 * This view is mainly used for password reset emails, containing a
 * temporary code and a validation link.
 *
 * @package   DashMed\Modules\Views
 * @author    DashMed Team
 * @license   Proprietary
 */

namespace modules\views\auth;

/**
 * Renders the content of DashMed emails.
 *
 * Responsibilities:
 *  - Generate the HTML message to be sent by email
 *  - Display the verification code in a readable format
 *  - Provide a direct link for password reset
 */
class MailerView
{
    /**
     * Returns the HTML content of a password reset email.
     *
     * The email contains:
     *  - A greeting message
     *  - A highlighted reset code
     *  - A time validity notice (20 minutes)
     *  - A clickable link to continue the procedure
     *
     * @param string $code Temporary code sent to the user.
     * @param string $link Password reset link.
     *
     * @return string Complete HTML content of the email.
     */
    public function show(string $code, string $link): string
    {
        return "
        <p>Bonjour,</p>
        <p>Votre code de réinitialisation est&nbsp;:
            <strong style='font-size:20px'>{$code}</strong>
        </p>
        <p>Ce code expire dans 20 minutes.</p>
        <p>
            Ou cliquez ici pour continuer :
            <a href='{$link}'>Réinitialiser le mot de passe</a>
        </p>
        ";
    }
}