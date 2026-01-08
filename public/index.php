<?php

declare(strict_types=1);

/**
 * Entry point of the application.
 * Point d'entrée de l'application.
 *
 * Handles routing and initialization.
 * Gère le routage et l'initialisation.
 *
 * @package DashMed
 * @author DashMed Team
 * @license Proprietary
 */

session_start();

$ROOT = dirname(__DIR__);
require $ROOT . '/vendor/autoload.php';
require $ROOT . '/assets/includes/Database.php';
require $ROOT . '/assets/includes/Dev.php';
Dev::init();

/**
 * Determines the controller class based on the URL path.
 * Détermine la classe du contrôleur basée sur le chemin URL.
 *
 * @param string $path URL path | Chemin URL
 * @return string Fully qualified controller class name | Nom complet de la classe du contrôleur
 */
function pathToPage(string $path): string
{
    $trim = trim($path, '/');

    $routeAliases = [
        'monitoring' => 'controllers\\pages\\Monitoring\\Monitoring',
        'dossierpatient' => 'controllers\\pages\\PatientRecord',
    ];

    $lowerTrim = strtolower($trim);
    if (isset($routeAliases[$lowerTrim])) {
        return $routeAliases[$lowerTrim];
    }
    if ($trim === '' || $trim === 'home' || $trim === 'homepage') {
        return 'controllers\\pages\\static\\Homepage';
    }
    if (strtolower($trim) === 'monitoring') {
        return 'controllers\\pages\\Monitoring\\Monitoring';
    }
    if (strtolower($trim) === 'dossierpatient') {
        return 'controllers\\pages\\PatientRecord';
    }
    if (strtolower($trim) === 'api_search') {
        return 'controllers\\api\\Search';
    }
    $parts = preg_split('~[/-]+~', $trim, -1, PREG_SPLIT_NO_EMPTY);
    $parts = array_map(fn($p) => strtolower($p), $parts);
    $last = ucfirst(array_pop($parts));
    $first = $parts[0] ?? '';

    $authNames = [
        'login',
        'logout',
        'signup',
        'register',
        'password',
        'passwordreset',
        'passwordresetrequest',
        'forgot',
        'forgotpassword'
    ];

    if ($first === 'auth' || in_array(strtolower($last), $authNames, true)) {
        return 'controllers\\auth\\' . $last;
    }

    if ($first === 'pages') {
        $studly = array_map(fn($p) => ucfirst($p), array_merge($parts, [$last]));
        return 'controllers\\' . implode('\\', $studly);
    }

    return 'controllers\\pages\\' . $last;
}

/**
 * Resolves the requested path relative to the base URL.
 * Résout le chemin demandé par rapport à l'URL de base.
 *
 * @param string $baseUrl Base URL of the application | URL de base de l'application
 * @return string Cleaned request path | Chemin de requête nettoyé
 */
function resolveRequestPath(string $baseUrl = '/'): string
{
    $reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    if ($baseUrl !== '/' && str_starts_with($reqPath, $baseUrl)) {
        $reqPath = substr($reqPath, strlen($baseUrl));
    }
    $reqPath = '/' . ltrim($reqPath, '/');

    if (($reqPath === '/' || $reqPath === '') && isset($_GET['page'])) {
        $reqPath = '/' . trim((string) $_GET['page'], '/ ');
    }
    return $reqPath;
}

/**
 * Maps an HTTP method to a controller action name.
 * Mappe une méthode HTTP vers un nom d'action de contrôleur.
 *
 * @param string $method HTTP method (GET, POST, etc.) | Méthode HTTP
 * @return string Action method name | Nom de la méthode d'action
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

$BASE_URL = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
if ($BASE_URL === '' || $BASE_URL === '\\') {
    $BASE_URL = '/';
}

$reqPath = resolveRequestPath($BASE_URL);

if ($reqPath === '/' || $reqPath === '') {
    $target = rtrim($BASE_URL, '/') . '/?page=homepage';
    header('Location: ' . $target, true, 302);
    exit;
}

$Page = pathToPage($reqPath);

$primary = "modules\\{$Page}Controller";
$fallback = null;
$ctrlClass = $primary;

if (!class_exists($primary)) {
    if (str_starts_with($Page, 'controllers\\pages\\') && !str_starts_with($Page, 'controllers\\pages\\static\\')) {
        $base = preg_replace('~^controllers\\\\pages\\\\~i', '', $Page);
        $segments = explode('\\', $base);
        $leaf = end($segments);

        $nestedCandidate = "modules\\{$Page}\\{$leaf}Controller";
        if (class_exists($nestedCandidate)) {
            $ctrlClass = $nestedCandidate;
        } else {
            $fallback = "modules\\controllers\\pages\\static\\{$leaf}Controller";
            $ctrlClass = $fallback;
        }
    } else {
        $ctrlClass = $fallback ?? $primary;
    }
}
$httpAction = httpMethodToAction($_SERVER['REQUEST_METHOD'] ?? 'GET');

try {
    if (!class_exists($ctrlClass)) {
        http_response_code(404);
        (new \modules\views\pages\static\ErrorView())->
            show(404, details: Dev::isDebug() ? "404 — Contrôleur introuvable: {$ctrlClass}" : null);
        exit;
    }

    $controller = new $ctrlClass();

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
    (new \modules\views\pages\static\ErrorView())->
        show(code: 405, details: Dev::isDebug() ? "405 — Méthode non autorisée pour {$ctrlClass}" : null);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    (new \modules\views\pages\static\ErrorView())->
        show(500, details: Dev::isDebug() ? $e->getMessage() : null);
    exit;
}
