<?php

declare(strict_types=1);

// A1 stub: only /health. A2 replaces this with the Laravel 11 bootstrap.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($path === '/health' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(404);
header('Content-Length: 0');
