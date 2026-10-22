<?php
declare(strict_types=1);

function applyFixture(string $path, array $request, array $backend): void
{
    if ($path === '/test/headers') {
        header('X-Debug-Transform: origin-value');
        header('X-Debug-Remove-Me: present-at-origin');
        header('X-Debug-Append: first', false);
        header('X-Debug-Append: second', false);
        $payload = $request['body']['json_valid'] ? $request['body']['json'] : null;
        $custom = is_array($payload) ? ($payload['response_headers'] ?? []) : [];
        if (!is_array($custom)) {
            failFixture('response_headers must be an object.');
            return;
        }
        foreach ($custom as $name => $value) {
            if (!is_string($name) || preg_match('/^X-Debug-[A-Za-z0-9-]{1,64}$/i', $name) !== 1 || !is_string($value)
                || strlen($value) > 4096 || preg_match('/[\r\n\x00]/', $value) === 1
                || in_array(strtolower($name), ['x-debug-backend', 'x-debug-request-id'], true)) {
                failFixture('Use single-line X-Debug-* headers; backend identity headers are reserved.');
                return;
            }
        }
        foreach ($custom as $name => $value) {
            header($name . ': ' . $value);
        }
    } elseif ($path === '/test/cookies') {
        $mode = $_GET['mode'] ?? 'set';
        if (!is_string($mode) || !in_array($mode, ['set', 'clear'], true)) {
            failFixture('mode must be set or clear.');
            return;
        }
        $value = $_GET['value'] ?? 'debug-value';
        if (!is_string($value) || strlen($value) > 1024) {
            failFixture('value must be text up to 1024 bytes.');
            return;
        }
        foreach (['visible' => false, 'httponly' => true] as $suffix => $httpOnly) {
            setcookie('debug_lab_' . $suffix, $mode === 'clear' ? '' : $value, [
                'expires' => $mode === 'clear' ? time() - 3600 : time() + 3600,
                'path' => '/',
                'secure' => $backend['https'],
                'httponly' => $httpOnly,
                'samesite' => 'Lax',
            ]);
        }
    } elseif ($path === '/test/cache') {
        $ttl = filter_var($_GET['ttl'] ?? '60', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 3600]]);
        if ($ttl === false) {
            failFixture('ttl must be an integer from 0 to 3600.');
            return;
        }
        header('Cache-Control: public, max-age=' . $ttl . ', s-maxage=' . $ttl);
        header('X-Debug-Cache-Fixture: contains-unredacted-request-data');
    } elseif (str_starts_with($path, '/test/status/')) {
        if (preg_match('#^/test/status/([2-5][0-9]{2})$#', $path, $match) !== 1) {
            failFixture('Expected an HTTP status code from 200 to 599.');
            return;
        }
        http_response_code((int) $match[1]);
    } elseif ($path === '/test/redirect') {
        $to = $_GET['to'] ?? '/inspect?redirected=1';
        if (!is_string($to) || !str_starts_with($to, '/') || str_starts_with($to, '//')
            || preg_match('/[\x00-\x20\x7f\\\\]/', $to) === 1) {
            failFixture('to must be a local path without control characters or backslashes.');
            return;
        }
        header('Location: ' . $to, true, 302);
    }
}
