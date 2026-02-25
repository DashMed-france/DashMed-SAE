<?php

/**
 * Entry point of the application.
 *
 * Handles routing and initialization.
 *
 * @package DashMed
 * @author DashMed Team
 * @license Proprietary
 */

declare(strict_types=1);

date_default_timezone_set('Europe/Paris');

session_start();

$ROOT = dirname(__DIR__);
require $ROOT . '/vendor/autoload.php';
require $ROOT . '/assets/includes/Database.php';
require $ROOT . '/assets/includes/Mailer.php';
require $ROOT . '/assets/includes/Dev.php';

use assets\includes\Dev;
use assets\includes\Database;

Dev::init();

/**
 * Security: Validates that the active user session corresponds to an existing user in the database.
 *
 * If the user has been deleted or is invalid, the session is destroyed and they are redirected to login.
 */
if (isset($_SESSION['user_id'])) {
    try {
        $pdo = Database::getInstance();
        $userModel = new \modules\models\Repositories\UserRepository($pdo);
        $user = $userModel->getById((int) $_SESSION['user_id']);

        if (!$user) {
            session_unset();
            session_destroy();
            session_start();
            $_SESSION['error'] = "Votre compte a été supprimé ou est invalide. | Your account has been deleted or is invalid.";
            header('Location: /?page=login');
            exit;
        }
    } catch (Exception $e) {
        error_log("[Security] Session check failed: " . $e->getMessage());
    }
}

/**
 * Resolves a URL path to a [controller class, action method] pair.
 *
 * Uses the new resource-based controllers (AuthController, PatientController,
 * AdminController) with the old page-based controllers as a fallback.
 *
 * @param string $path URL path
 * @return array{0: string, 1: string|null} [FQCN, action|null]
 */
function resolveRoute(string $path): array
{
    $path = explode('?', $path)[0];

    $segments = preg_split('~[/-]+~', trim($path, '/'), -1, PREG_SPLIT_NO_EMPTY);
    if ($segments === false) {
        $segments = [];
    }
    $segments = array_map('strtolower', $segments);

    if (empty($segments) || $segments[0] === 'home' || $segments[0] === 'homepage') {
        return ['modules\\controllers\\StaticController', 'homepage'];
    }

    if (in_array($segments[0], ['login', 'connexion']))
        return ['modules\\controllers\\AuthController', 'login'];
    if (in_array($segments[0], ['signup', 'register', 'inscription']))
        return ['modules\\controllers\\AuthController', 'signup'];
    if (in_array($segments[0], ['logout', 'deconnexion']))
        return ['modules\\controllers\\AuthController', 'logout'];
    if (in_array($segments[0], ['password', 'forgot', 'motdepasse', 'oubli', 'passwordreset']))
        return ['modules\\controllers\\AuthController', 'password'];

    if ($segments[0] === 'dashboard')
        return ['modules\\controllers\\PatientController', 'dashboard'];
    if ($segments[0] === 'monitoring')
        return ['modules\\controllers\\PatientController', 'monitoring'];
    if (in_array($segments[0], ['patientrecord', 'dossierpatient']))
        return ['modules\\controllers\\PatientController', 'record'];
    if (in_array($segments[0], ['medicalprocedure', 'consultations']))
        return ['modules\\controllers\\PatientController', 'consultations'];

    if ($segments[0] === 'profile')
        return ['modules\\controllers\\UserController', 'profile'];
    if ($segments[0] === 'customization')
        return ['modules\\controllers\\UserController', 'customization'];

    if ($segments[0] === 'sysadmin')
        return ['modules\\controllers\\AdminController', 'panel'];

    if ($segments[0] === 'about')
        return ['modules\\controllers\\StaticController', 'about'];
    if (in_array($segments[0], ['legal', 'mentionslegales', 'legalnotice']))
        return ['modules\\controllers\\StaticController', 'legal'];
    if (in_array($segments[0], ['sitemap', 'plandusite']))
        return ['modules\\controllers\\StaticController', 'sitemap'];

    if ($segments[0] === 'api_search')
        return ['modules\\controllers\\api\\SearchController', null];
    if ($segments[0] === 'api_history')
        return ['modules\\controllers\\PatientController', 'apiHistory'];
    if ($segments[0] === 'api_live_metrics')
        return ['modules\\controllers\\PatientController', 'apiLiveMetrics'];
    if ($segments[0] === 'api_consultation_document')
        return ['modules\\controllers\\PatientController', 'apiDownloadDocument'];

    return ['RouteNotFound', null];
}

/**
 * Resolves the requested path relative to the base URL.
 *
 * @param string $baseUrl Base URL of the application
 * @return string Cleaned request path
 */
function resolveRequestPath(string $baseUrl = '/'): string
{
    $rawUri = $_SERVER['REQUEST_URI'] ?? '/';
    if (!is_string($rawUri)) {
        $rawUri = '/';
    }
    $parsed = parse_url($rawUri, PHP_URL_PATH);
    $reqPath = is_string($parsed) ? $parsed : '/';

    if ($baseUrl !== '/' && str_starts_with($reqPath, $baseUrl)) {
        $reqPath = (string) substr($reqPath, strlen($baseUrl));
    }
    $reqPath = '/' . ltrim($reqPath, '/');

    if ($reqPath === '/') {
        $page = $_GET['page'] ?? null;
        if (is_string($page) && $page !== '') {
            $reqPath = '/' . trim($page, '/ ');
        }
    }
    return $reqPath;
}

/**
 * Maps an HTTP method to a controller action name.
 *
 * @param string $method HTTP method (GET, POST, etc.)
 * @return string Action method name
 */
function httpMethodToAction(string $method): string
{
    $m = strtolower($method);
    return match ($m) {
        'get' => 'get',
        'post' => 'post',
        'put' => 'put',
        'patch' => 'patch',
        'delete' => 'delete',
        'head' => 'head',
        default => 'get',
    };
}

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/';
if (!is_string($scriptName)) {
    $scriptName = '/';
}
$BASE_URL = rtrim(dirname($scriptName), '/');
if ($BASE_URL === '' || $BASE_URL === '\\') {
    $BASE_URL = '/';
}

$reqPath = resolveRequestPath($BASE_URL);

if ($reqPath === '/' || $reqPath === '') {
    $target = rtrim($BASE_URL, '/') . '/?page=homepage';
    header('Location: ' . $target, true, 302);
    exit;
}


[$ctrlClass, $action] = resolveRoute($reqPath);



$rawMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!is_string($rawMethod)) {
    $rawMethod = 'GET';
}
$httpAction = httpMethodToAction($rawMethod);

try {
    if (!class_exists($ctrlClass)) {
        http_response_code(404);
        (new \modules\views\static\ErrorView())->
            show(404, details: Dev::isDebug() ? "404 — Contrôleur introuvable: {$ctrlClass}" : null);
        exit;
    }

    $controller = new $ctrlClass();

    if ($action !== null) {
        if (method_exists($controller, $action)) {
            $controller->{$action}();
            exit;
        }
        http_response_code(404);
        (new \modules\views\static\ErrorView())->
            show(404, details: Dev::isDebug() ? "404 — Action '{$action}' introuvable sur {$ctrlClass}" : null);
        exit;
    }

    if (method_exists($controller, $httpAction)) {
        $controller->{$httpAction}();
        exit;
    }

    if (method_exists($controller, 'index')) {
        $controller->index();
        exit;
    }

    http_response_code(405);
    header('Allow: GET, POST, PUT, PATCH, DELETE, HEAD');
    (new \modules\views\static\ErrorView())->
        show(405, details: Dev::isDebug() ? "405 — Méthode non autorisée pour {$ctrlClass}" : null);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    (new \modules\views\static\ErrorView())->
        show(500, details: Dev::isDebug() ? $e->getMessage() : null);
    exit;
}
