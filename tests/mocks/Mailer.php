<?php

namespace assets\includes;

/**
 * Mock of the Mailer class.
 * Mock de la classe Mailer.
 */
class Mailer
{
    private static ?Mailer $instance = null;

    public function __construct(mixed $config = null)
    {
        self::$instance = $this;
    }

    public function send(string $to, string $subject, string $body): bool
    {
        $GLOBALS['mailer_calls'][] = [
            'to' => $to,
            'subject' => $subject,
            'body' => $body
        ];
        return true;
    }

    public static function getInstance(): ?Mailer
    {
        return self::$instance;
    }
}
