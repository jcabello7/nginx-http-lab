<?php
declare(strict_types=1);

function snapshotJson(array $snapshot, bool $html = false): string
{
    $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR;
    if ($html) {
        $flags |= JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    }
    return json_encode($snapshot, $flags);
}

function controlledHeaders(): array
{
    $rows = [];
    foreach (headers_list() as $line) {
        $parts = explode(':', $line, 2);
        if (count($parts) === 2) {
            $rows[] = ['name' => $parts[0], 'value' => trim($parts[1])];
        }
    }
    return $rows;
}

function responseSnapshot(array $request, array $backend, string $contentType): array
{
    header('Content-Type: ' . $contentType);
    header('X-Debug-Backend: ' . $backend['name']);
    header('X-Debug-Request-Id: ' . $request['id']);
    return [
        'schema_version' => 1,
        'backend' => $backend,
        'request' => $request,
        'origin_response' => [
            'status' => http_response_code(),
            'headers' => controlledHeaders(),
            'scope' => 'Application-defined status and headers, before nginx and Cloudflare.',
            'notes' => [
                'Received headers are nginx/PHP interpretation, not original HTTP wire bytes; duplicates may be combined.',
                'Peer and TLS metadata describe the connection to nginx, not necessarily the original browser.',
                'The reference omits some nginx-added headers; do not automatically attribute every difference to Cloudflare.',
                'Fetch does not expose Set-Cookie. HttpOnly cookies are unavailable through document.cookie.',
                'Nothing is redacted. Binary request bodies are preserved as base64. Request limit: 1 MiB.',
            ],
        ],
    ];
}

function failFixture(string $message): void
{
    http_response_code(400);
    header('X-Debug-Fixture-Error: ' . $message);
}
