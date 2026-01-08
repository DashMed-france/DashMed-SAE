<?php

namespace modules\views\auth;

/**
 * Class MailerView | Vue Email
 *
 * Generates email content for DashMed notifications.
 * Génère le contenu HTML d'un email envoyé par la plateforme DashMed.
 *
 * Used primarily for password reset emails.
 * Utilisé principalement pour les emails de réinitialisation de mot de passe.
 *
 * @package DashMed\Modules\Views\Auth
 * @author DashMed Team
 * @license Proprietary
 */
class MailerView
{
    /**
     * Returns HTML content for password reset email.
     * Retourne le contenu HTML d'un email de réinitialisation de mot de passe.
     *
     * @param string $code Temporary code | Code temporaire.
     * @param string $link Reset link | Lien de réinitialisation.
     * @return string Full HTML content | Contenu HTML complet.
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
