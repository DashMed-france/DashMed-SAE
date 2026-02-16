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
    $trim = strtolower(trim($path, '/'));

    $resourceRoutes = [
        'login' => ['modules\\controllers\\AuthController', 'login'],
        'signup' => ['modules\\controllers\\AuthController', 'signup'],
        'register' => ['modules\\controllers\\AuthController', 'signup'],
        'logout' => ['modules\\controllers\\AuthController', 'logout'],
        'password' => ['modules\\controllers\\AuthController', 'password'],
        'passwordreset' => ['modules\\controllers\\AuthController', 'password'],
        'passwordresetrequest' => ['modules\\controllers\\AuthController', 'password'],
        'forgot' => ['modules\\controllers\\AuthController', 'password'],
        'forgotpassword' => ['modules\\controllers\\AuthController', 'password'],

        'dashboard' => ['modules\\controllers\\PatientController', 'dashboard'],
        'monitoring' => ['modules\\controllers\\PatientController', 'monitoring'],
        'patientrecord' => ['modules\\controllers\\PatientController', 'record'],
        'dossierpatient' => ['modules\\controllers\\PatientController', 'record'],
        'medicalprocedure' => ['modules\\controllers\\PatientController', 'consultations'],

        'profile' => ['modules\\controllers\\UserController', 'profile'],
        'customization' => ['modules\\controllers\\UserController', 'customization'],

        'sysadmin' => ['modules\\controllers\\AdminController', 'panel'],
    ];

    if (isset($resourceRoutes[$trim])) {
        return $resourceRoutes[$trim];
    }

    if ($trim === '' || $trim === 'home' || $trim === 'homepage') {
        return ['modules\\controllers\\static\\HomepageController', null];
    }
    if ($trim === 'api_search') {
        return ['modules\\controllers\\api\\SearchController', null];
    }

    $parts = preg_split('~[/-]+~', $trim, -1, PREG_SPLIT_NO_EMPTY);
    if ($parts === false) {
        $parts = [];
    }
    $parts = array_map(fn(string $p): string => strtolower($p), $parts);
    $last = array_pop($parts);
    if ($last === null) {
        $last = '';
    }
    $last = ucfirst($last);
    $first = $parts[0] ?? '';

    if ($first === 'pages') {
        $studly = array_map(fn($p) => ucfirst($p), array_merge($parts, [$last]));
        $class = 'modules\\controllers\\' . implode('\\', $studly);
    } else {
        $class = 'modules\\controllers\\static\\' . $last . 'Controller';
    }

    return [$class, null];
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

if (!class_exists($ctrlClass) && $action === null) {
    if (str_starts_with($ctrlClass, 'modules\\controllers\\pages\\')) {
        $base = preg_replace('~^modules\\\\controllers\\\\pages\\\\~i', '', $ctrlClass);
        if (is_string($base)) {
            $base = str_replace('Controller', '', $base);
            $nestedCandidate = "modules\\controllers\\pages\\{$base}\\{$base}Controller";
            if (class_exists($nestedCandidate)) {
                $ctrlClass = $nestedCandidate;
            } else {
                $segments = explode('\\', $base);
                $leaf = end($segments);
                $staticFallback = "modules\\controllers\\pages\\static\\{$leaf}Controller";
                if (class_exists($staticFallback)) {
                    $ctrlClass = $staticFallback;
                }
            }
        }
    }
}

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
