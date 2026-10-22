# HTTP Lab

A lightweight PHP and vanilla JavaScript HTTP diagnostic page for testing Cloudflare
and other reverse-proxy integrations against an origin server.

The inspector displays the request as seen by nginx/PHP, including headers, cookies,
query parameters, and the complete request body. It can also issue a new same-origin
browser probe and compare application-defined response status and headers against
the response headers visible to Fetch.

## Features

- Initial navigation snapshot kept separate from subsequent browser probes.
- Full, unredacted request headers, raw and parsed cookies, and request bodies up to
  1 MiB. Binary request bodies are represented as base64.
- Backend identity, internal server port, nginx peer address, HTTP version, TLS
  protocol/cipher, and SNI when supplied by the web server.
- Same-origin GET, HEAD, POST, PUT, PATCH, DELETE, and OPTIONS probes with custom
  browser-permitted headers and request bodies.
- Fixtures for custom response headers, debug cookies, status codes, local redirects,
  cache headers, and nginx liveness.
- Browser-side header/status comparison, JSON export, and a reproducible curl command.
- No external assets, frontend framework, telemetry, persistent history, or
  server-side diagnostic logging.

## Requirements

- PHP 8.1+ with PHP-FPM.
- nginx configured to route application paths to `index.php` and serve
  `/_debug/assets/` from the local `assets/` directory.
- Set `DEBUG_BACKEND_NAME` in the PHP-FPM environment to identify the origin.
  Optional `DEBUG_TLS_PROTOCOL`, `DEBUG_TLS_CIPHER`, and `DEBUG_TLS_SNI` values can
  be passed from nginx when the TLS connection terminates there.

This app does not configure nginx, TLS certificates, DNS, reverse proxies, or
Cloudflare. The deployment must keep the static asset path and PHP front controller
available on the intended test routes.

## Endpoints

| Path | Behavior |
|---|---|
| `/` | HTML inspector containing the initial request snapshot. |
| `/inspect` | JSON request and application-response snapshot. |
| `/test/headers` | Adds deterministic `X-Debug-*` response headers. A POST JSON body can define extra one-line `X-Debug-*` headers. |
| `/test/cookies?mode=set` | Sets `debug_lab_visible` and `debug_lab_httponly`. |
| `/test/cookies?mode=clear` | Clears only those two fixture cookies. |
| `/test/status/418` | Returns a selected HTTP status from 200 through 599. |
| `/test/redirect?to=/inspect` | Redirects to a local path only; external redirects are rejected. |
| `/test/cache?ttl=60` | Adds a cache policy for testing; TTL must be 0–3600 seconds. |
| `/healthz` | Returns `200 ok` when nginx is listening. It is nginx liveness, not PHP-FPM readiness. |

## Development smoke test

Run PHP's local server from the application directory and set a backend name:

```bash
DEBUG_BACKEND_NAME=local php -S 127.0.0.1:8080 -t . index.php
```

For full browser behavior, configure nginx/PHP-FPM so paths reach the front
controller and the local asset directory is served under `/_debug/assets/`.

## Browser visibility and comparison limits

The origin snapshot is created by the application before nginx and Cloudflare can
modify the response. It does not claim to capture all headers added by nginx or
later proxies. Browser Fetch cannot read `Set-Cookie`, and JavaScript cannot read
`HttpOnly` cookies. Such headers are reported as unobservable, not as removed.

The comparison applies only to the same browser probe. The browser may receive a
cached or transformed response, and another probe can reach a different backend.
Use DevTools, HAR, or curl when you need full response wire evidence.

## Unredacted debug warning

This tool intentionally exposes cookies, authorization headers, and complete
request bodies. Keep it on trusted test origins, do not enter real user credentials,
and do not expose it as a production application. Diagnostic pages use `no-store`,
but CDN rules can override origin cache headers. The cache fixture intentionally
includes unredacted diagnostic data and should only be used with fabricated values.
