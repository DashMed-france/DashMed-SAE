<?php

namespace modules\controllers\pages\static;

use modules\views\pages\static\sitemapView;

class SitemapController
{
    private sitemapView $view;

    public function __construct(?sitemapView $view = null)
    {
        $this->view = $view ?? new sitemapView();
    }

    public function get(): void
    {
        if ($this->isUserLoggedIn()) {
            $this->redirect('/?page=dashboard');
            return;
        }
        $this->view->show();
    }

    public function index(): void
    {
        $this->get();
    }

    private function isUserLoggedIn(): bool
    {
        return isset($_SESSION['email']);
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
