<?php
declare(strict_types=1);

require __DIR__ . '/lib/request.php';
require __DIR__ . '/lib/response.php';
require __DIR__ . '/lib/fixtures.php';

http_response_code(200);
header_remove('X-Powered-By');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$path = explode('?', (string) $_SERVER['REQUEST_URI'], 2)[0];
$request = requestSnapshot();
$backend = backendSnapshot();
applyFixture($path, $request, $backend);
$accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
$jsonResponse = $path === '/inspect' || str_starts_with($path, '/test/') || str_contains($accept, 'application/json');
$snapshot = responseSnapshot($request, $backend, $jsonResponse ? 'application/json; charset=utf-8' : 'text/html; charset=utf-8');
if (in_array(http_response_code(), [204, 304], true) || $request['method'] === 'HEAD') {
    exit;
}
if ($jsonResponse) {
    echo snapshotJson($snapshot);
} else {
    $snapshotJson = snapshotJson($snapshot, true);
    require __DIR__ . '/view.php';
}
