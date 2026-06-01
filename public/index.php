<?php

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require $file;
});

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$projects = new App\ProjectManager(__DIR__ . '/../config/projects.json');

function jsonHeader(): void {
    header('Content-Type: application/json; charset=utf-8');
}

function err(int $code, string $msg): never {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

// ── Listar proyectos ──
if ($uri === '/api/projects' && $method === 'GET') {
    jsonHeader();
    echo json_encode($projects->getProjectList());
    exit;
}

// ── Validar token ──
if (preg_match('#^/api/projects/([^/]+)/unlock$#', $uri, $m) && $method === 'POST') {
    jsonHeader();
    $input = json_decode(file_get_contents('php://input'), true);
    $token = $input['token'] ?? '';
    if ($projects->isValidToken($m[1], $token)) {
        setcookie('token_' . $m[1], $token, [
            'expires' => time() + 30 * 86400,
            'path'    => '/',
            'samesite' => 'Strict',
        ]);
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Token inválido']);
    }
    exit;
}

// ── Archivos de un proyecto ──
if (preg_match('#^/api/projects/([^/]+)/files(?:/(.+))?$#', $uri, $m) && $method === 'GET') {
    jsonHeader();
    $projectId = $m[1];
    $filePath = $m[2] ?? null;
    $token = $_COOKIE['token_' . $projectId] ?? null;

    $cm = $projects->getContentManager($projectId, $token);
    if ($cm === null) {
        err(403, 'Acceso denegado o proyecto no encontrado');
    }

    if ($filePath === null) {
        echo json_encode(['files' => $cm->getFileList()]);
    } else {
        $file = $cm->getFile(rawurldecode($filePath));
        if ($file === null) err(404, 'Archivo no encontrado');
        echo json_encode($file);
    }
    exit;
}

// ── Unlock via URL (GET /{id}/...?token=xxx) ──
if ($method === 'GET' && !empty($_GET['token'])) {
    $p = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (preg_match('#^/([^/]+)#', $p, $m)) {
        $projId = $m[1];
        if ($projects->projectExists($projId)) {
            $token = $_GET['token'];
            if ($projects->isValidToken($projId, $token)) {
                setcookie('token_' . $projId, $token, [
                    'expires' => time() + 30 * 86400,
                    'path'    => '/',
                    'samesite' => 'Strict',
                ]);
            }
            $qs = $_GET;
            unset($qs['token']);
            $redirect = $p . (empty($qs) ? '' : '?' . http_build_query($qs));
            header('Location: ' . $redirect);
            exit;
        }
    }
}

// ── Archivos estáticos (imágenes, assets) desde content/ ──
if ($method === 'GET' && preg_match('#^/([^/]+)/(.+)$#', $uri, $m)) {
    $projId = $m[1];
    $relPath = rawurldecode($m[2]);

    if ($projects->projectExists($projId)) {
        $cm = $projects->getContentManager($projId, $_COOKIE['token_' . $projId] ?? null);
        if ($cm === null) {
            err(403, 'Acceso denegado');
        } else {
            $fullPath = $cm->resolveSafePath($relPath);
            if ($fullPath !== null) {
                $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                $imgExts = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'bmp', 'ico', 'tiff', 'tif', 'mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'];
                if (in_array($ext, $imgExts)) {
                    $mime = mime_content_type($fullPath);
                    if ($mime === false) $mime = 'application/octet-stream';
                    header('Content-Type: ' . $mime);
                    header('Cache-Control: public, max-age=31536000');
                    readfile($fullPath);
                    exit;
                }
            }
        }
    }
}

// ── Ruta por defecto / SPA con URL compartible ──
header('Content-Type: text/html; charset=utf-8');
readfile(__DIR__ . '/index.html');
