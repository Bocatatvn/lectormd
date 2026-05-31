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
    header('Access-Control-Allow-Origin: *');
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

// ── Ruta por defecto / SPA con URL compartible ──
header('Content-Type: text/html; charset=utf-8');
readfile(__DIR__ . '/index.html');
