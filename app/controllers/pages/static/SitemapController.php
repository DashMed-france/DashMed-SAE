<?php

namespace modules\controllers\pages\static;

use modules\views\pages\static\SitemapView;

class SitemapController
{
    private SitemapView $view;

    public function __construct(?SitemapView $view = null)
    {
        $this->view = $view ?? new SitemapView();
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
