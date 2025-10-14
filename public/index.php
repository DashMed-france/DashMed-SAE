<?php
declare(strict_types=1);

session_start();

$ROOT = dirname(__DIR__);
require $ROOT . '/vendor/autoload.php';

function pathToPage(string $path): string {
    $trim = trim($path, '/');
    if ($trim === '' || $trim === 'home') return 'homepage';
    $parts = preg_split('~[/-]+~', $trim, -1, PREG_SPLIT_NO_EMPTY);
    $studly = array_map(fn($p) => ucfirst(strtolower($p)), $parts);
    return implode('', $studly);
}

function resolveRequestPath(string $baseUrl = '/'): string {
    $reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    if ($baseUrl !== '/' && str_starts_with($reqPath, $baseUrl)) {
        $reqPath = substr($reqPath, strlen($baseUrl));
    }
    $reqPath = '/' . ltrim($reqPath, '/');

    if (($reqPath === '/' || $reqPath === '') && isset($_GET['page'])) {
        $reqPath = '/' . trim((string)$_GET['page'], '/ ');
    }
    return $reqPath;
}

function httpMethodToAction(string $method): string {
    $m = strtolower($method);
    return match ($m) {
        'get'    => 'get',
        'post'   => 'post',
        'put'    => 'put',
        'patch'  => 'patch',
        'delete' => 'delete',
        'head'   => 'head',
        default  => 'get',
    };
}

$BASE_URL = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
if ($BASE_URL === '' || $BASE_URL === '\\') $BASE_URL = '/';

$reqPath = resolveRequestPath($BASE_URL);

/** REDIRECT ROOT → /homepage (quoi qu'il arrive) */
if ($reqPath === '/' || $reqPath === '') {
    $target = rtrim($BASE_URL, '/') . '/?page=homepage';
    header('Location: ' . $target, true, 302);
    exit;
}

$Page       = pathToPage($reqPath);
$ctrlClass  = "modules\\controllers\\{$Page}Controller";
$httpAction = httpMethodToAction($_SERVER['REQUEST_METHOD'] ?? 'GET');

try {
    if (!class_exists($ctrlClass)) {
        http_response_code(404);
        echo "404 — Contrôleur introuvable: {$ctrlClass}";
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
    echo "405 — Méthode non autorisée pour {$ctrlClass}";
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo $e->getMessage();
    echo "500 — Erreur serveur.";
    exit;
}
