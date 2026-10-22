# HTTP Lab

A lightweight PHP and vanilla JavaScript HTTP diagnostic page for testing Cloudflare
and other reverse-proxy integrations against an origin server.

The inspector displays the request as seen by nginx/PHP, including headers, cookies,
query parameters, and the complete request body. It can also issue a new same-origin
browser probe and compare application-defined response status and headers against
the response headers visible to Fetch.

## Features

- Basic mode by default: connection summary followed directly by the inbound
  request. The header's Basic/Advanced controls reveal the full probe and response
  comparison interface without discarding inputs or previous results. Reloading
  the page starts in Basic again.
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
- Pass `DEBUG_BACKEND_NAME` as an nginx FastCGI parameter to identify the origin.
  Optional `DEBUG_TLS_PROTOCOL`, `DEBUG_TLS_CIPHER`, and `DEBUG_TLS_SNI` values can
  be passed from nginx when the TLS connection terminates there.
- Disable PHP's `enable_post_data_reading` for these routes to preserve raw
  multipart bodies, and configure nginx's request-body limit to 1 MiB.

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

## Deployment smoke checks

After configuring nginx/PHP-FPM and your test hostname, check the served routes:

```bash
curl --include https://your-test-origin.example/healthz
curl --include https://your-test-origin.example/inspect
curl --include https://your-test-origin.example/test/status/418
```

The repository is application source, not a standalone web-server image. A bare
PHP development-server router does not provide the nginx asset aliases, FastCGI
metadata, or nginx-only liveness endpoint needed by the complete UI.

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
