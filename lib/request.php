<?php
declare(strict_types=1);

function requestSnapshot(): array
{
    $headers = [];
    $lookup = [];
    foreach (getallheaders() as $name => $value) {
        $headers[] = ['name' => $name, 'value' => $value];
        $lookup[strtolower($name)] = $value;
    }
    $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
    $pairs = [];
    foreach ($query === '' ? [] : explode('&', $query) as $pair) {
        $parts = explode('=', $pair, 2);
        $pairs[] = ['name' => urldecode($parts[0]), 'value' => urldecode($parts[1] ?? '')];
    }
    $raw = file_get_contents('php://input');
    if ($raw === false) {
        throw new RuntimeException('Could not read the request body.');
    }
    $utf8 = preg_match('//u', $raw) === 1;
    $json = json_decode($raw, true);
    $jsonValid = json_last_error() === JSON_ERROR_NONE;
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $forwarded = [];
    foreach ($lookup as $name => $value) {
        if (str_starts_with($name, 'cf-') || str_starts_with($name, 'x-forwarded-') || $name === 'forwarded') {
            $forwarded[$name] = $value;
        }
    }
    return [
        'id' => bin2hex(random_bytes(12)),
        'received_at' => gmdate('Y-m-d\TH:i:s\Z'),
        'method' => (string) $_SERVER['REQUEST_METHOD'],
        'request_uri' => $uri,
        'path' => explode('?', $uri, 2)[0],
        'query' => ['raw' => $query, 'pairs' => $pairs],
        'headers' => $headers,
        'cookies' => ['raw' => $lookup['cookie'] ?? '', 'parsed' => (object) $_COOKIE],
        'body' => [
            'byte_length' => strlen($raw),
            'encoding' => $utf8 ? 'utf-8' : 'base64',
            'text' => $utf8 ? $raw : null,
            'base64' => $utf8 ? null : base64_encode($raw),
            'json_valid' => $jsonValid,
            'json' => $jsonValid ? $json : null,
        ],
        'forwarded' => (object) $forwarded,
    ];
}

function backendSnapshot(): array
{
    return [
        'name' => (string) $_SERVER['DEBUG_BACKEND_NAME'],
        'app_version' => '1.1.0',
        'server_addr' => (string) $_SERVER['SERVER_ADDR'],
        'server_port' => (string) $_SERVER['SERVER_PORT'],
        'peer_addr' => (string) $_SERVER['REMOTE_ADDR'],
        'peer_port' => (string) $_SERVER['REMOTE_PORT'],
        'http_protocol' => (string) $_SERVER['SERVER_PROTOCOL'],
        'https' => ($_SERVER['HTTPS'] ?? '') === 'on',
        'tls_protocol' => (string) ($_SERVER['DEBUG_TLS_PROTOCOL'] ?? ''),
        'tls_cipher' => (string) ($_SERVER['DEBUG_TLS_CIPHER'] ?? ''),
        'sni' => (string) ($_SERVER['DEBUG_TLS_SNI'] ?? ''),
    ];
}
