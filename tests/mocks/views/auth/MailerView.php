<?php

namespace modules\views\auth;

class MailerView
{
    public function show(string $code, string $link): string
    {
        return '<html><body>Code: ' . $code . ' Link: ' . $link . '</body></html>';
    }
}
