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
        $primaryColor = '#0056b3';
        $backgroundColor = '#f4f7f6';
        $contentColor = '#ffffff';
        $textColor = '#333333';
        $mutedColor = '#888888';

        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Réinitialisation de mot de passe - DashMed</title>
    <style>
        @media only screen and (max-width: 600px) {
            .container { width: 100% !important; }
            .content { padding: 20px !important; }
        }
    </style>
</head>
<body style='margin: 0; padding: 0; font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif; " .
            "background-color: {$backgroundColor}; color: {$textColor};'>
    <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%' " .
            "style='background-color: {$backgroundColor}; padding: 40px 0;'>
        <tr>
            <td align='center'>
                <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='600' class='container' " .
            "style='background-color: {$contentColor}; border-radius: 8px; 
                box-shadow: 0 4px 6px rgba(0,0,0,0.1); overflow: hidden;'>
                    
                    <tr>
                        <td align='center' style='background-color: {$primaryColor}; padding: 30px 0;'>
                            <h1 style='color: #ffffff; margin: 0; font-size: 28px; letter-spacing: 1px;'>DashMed</h1>
                            <p style='color: #e0e0e0; margin: 5px 0 0 0; font-size: 14px;'>
                            Plateforme Médicale Sécurisée</p>
                        </td>
                    </tr>

                    <tr>
                        <td class='content' style='padding: 40px;'>
                            <h2 style='color: {$textColor}; margin-top: 0; font-size: 22px;'>
                            Réinitialisation de mot de passe</h2>
                            <p style='font-size: 16px; line-height: 1.5; color: #555555;'>
                                Bonjour,
                            </p>
                            <p style='font-size: 16px; line-height: 1.5; color: #555555;'>
                                Nous avons reçu une demande de réinitialisation de mot de passe pour votre compte 
                                DashMed.
                                Utilisez le code ci-dessous pour compléter la procédure. Ce code est valable pendant 
                                <strong>20 minutes</strong>.
                            </p>

                            <div style='background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; " .
            "padding: 15px; margin: 25px 0; text-align: center;'>
                                <span style='font-size: 32px; font-weight: bold; letter-spacing: 
                                5px; color: {$primaryColor};'>{$code}</span>
                            </div>

                            <p style='font-size: 16px; line-height: 1.5; color: #555555; text-align: center;'>
                                Ou cliquez directement sur le bouton ci-dessous :
                            </p>

                            <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%'>
                                <tr>
                                    <td align='center'>
                                        <a href='{$link}' style='display: inline-block; padding: 14px 28px; " .
            "background-color: {$primaryColor}; color: #ffffff; text-decoration: none; border-radius: 5px; " .
            "font-weight: bold; font-size: 16px; transition: background-color 0.3s;'>
                                            Réinitialiser mon mot de passe
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style='font-size: 14px; color: #999999; margin-top: 30px; font-style: italic;'>
                                Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet email en 
                                toute sécurité.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align='center' style='background-color: #eeeeee; padding: 20px; font-size: 12px; " .
            "color: {$mutedColor}; border-top: 1px solid #e0e0e0;'>
                            <p style='margin: 0;'>&copy; " . date('Y') . " 
                            DashMed. Tous droits réservés.</p>
                            <p style='margin: 5px 0 0 0;'>
                            Ceci est un message automatique, merci de ne pas y répondre.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
        ";
    }
}
