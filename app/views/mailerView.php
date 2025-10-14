<?php
/**
 * DashMed — Mailer View
 *
 * Génère le contenu HTML d’un email envoyé par la plateforme DashMed.
 * Cette vue est utilisée notamment pour les mails de réinitialisation
 * de mot de passe, contenant un code temporaire et un lien de validation.
 *
 * @package   DashMed\Modules\Views
 * @author    DashMed Team
 * @license   Proprietary
 */

namespace modules\views;

/**
 * Rendu du contenu des emails DashMed.
 *
 * Responsabilités :
 *  - Générer le message HTML à envoyer par email
 *  - Afficher le code de vérification de manière lisible
 *  - Fournir un lien direct pour la réinitialisation du mot de passe
 */
class mailerView
{
    /**
     * Retourne le contenu HTML d’un email de réinitialisation de mot de passe.
     *
     * L’email contient :
     *  - Un message de salutation
     *  - Un code de réinitialisation mis en avant
     *  - Une mention de validité temporelle (20 minutes)
     *  - Un lien cliquable pour poursuivre la procédure
     *
     * @param string $code Code temporaire envoyé à l’utilisateur
     * @param string $link Lien de réinitialisation du mot de passe
     *
     * @return string Contenu HTML complet de l’email
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