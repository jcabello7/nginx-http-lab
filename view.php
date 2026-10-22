<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Inspect origin requests and browser-visible responses while testing Cloudflare integrations.">
  <title>HTTP Lab</title>
  <link rel="stylesheet" href="/_debug/assets/app.css">
  <script type="module" src="/_debug/assets/app.js"></script>
</head>
<body data-view-mode="basic">
<a class="skip-link" href="#main">Skip to inspector</a>
<div class="page">
  <header class="masthead">
    <div><h1>HTTP Lab</h1>
      <p class="muted">Inspect what reaches the origin and what the browser receives after the proxy.</p></div>
    <div class="identity"><span class="badge" id="backend-name">nginx_test1</span><span class="small muted">Origin inspection</span>
      <div class="view-mode" role="group" aria-label="View mode">
        <button type="button" id="view-mode-basic" data-view-mode="basic" aria-pressed="true" aria-controls="probe-panel outbound-panel comparison-panel initial-evidence">Basic</button>
        <button type="button" id="view-mode-advanced" data-view-mode="advanced" aria-pressed="false" aria-controls="probe-panel outbound-panel comparison-panel initial-evidence">Advanced</button>
      </div>
    </div>
  </header>
  <aside class="notice" aria-label="Diagnostic scope">
    <strong>Unredacted debug mode.</strong> Headers, cookies and bodies are shown in full. There is no persistent history or automatic upload of browser observations.
  </aside>
  <main id="main">
    <section class="summary-grid" aria-label="Origin connection summary">
      <div class="stat"><span class="small muted">BACKEND / INTERNAL PORT</span><strong id="summary-backend">—</strong></div>
      <div class="stat"><span class="small muted">REQUEST RECEIVED</span><strong id="summary-request">—</strong></div>
      <div class="stat"><span class="small muted">PEER CONNECTING TO NGINX</span><strong id="summary-peer">—</strong></div>
      <div class="stat"><span class="small muted">ORIGIN REQUEST ID</span><strong id="summary-id">—</strong></div>
    </section>

    <section id="probe-panel" class="card composer" aria-labelledby="composer-title" data-advanced-only hidden>
      <div class="section-heading"><div><p class="eyebrow">01 / PROBE</p><h2 id="composer-title">Send a new request</h2></div><span class="badge neutral">Same-origin</span></div>
      <p class="small muted">This probe is separate from the initial navigation and may reach a different backend. Only same-origin paths are allowed.</p>
      <form id="probe-form">
        <div class="request-line">
          <div><label for="method">Method</label><select id="method" name="method"><option>GET</option><option>POST</option><option>PUT</option><option>PATCH</option><option>DELETE</option><option>HEAD</option><option>OPTIONS</option></select></div>
          <div class="grow"><label for="path">Path and query string</label><input id="path" name="path" value="/inspect" spellcheck="false" autocomplete="off" required></div>
          <button class="primary" id="run-probe" type="submit">Run probe</button>
        </div>
        <div class="editor-grid">
          <div><label for="request-headers">Headers requested from the browser · JSON</label><textarea id="request-headers" rows="5" spellcheck="false">{"X-Debug-Test":"http-lab"}</textarea><p class="hint">Host, Cookie and other reserved headers cannot be set with Fetch. Use curl for those cases.</p></div>
          <div><label for="request-body">Body · unredacted text</label><textarea id="request-body" rows="5" spellcheck="false" placeholder='{"message":"debug"}' disabled></textarea><p class="hint">GET and HEAD do not send a body. Server limit: 1 MiB. Binary bodies are preserved as base64.</p></div>
        </div>
        <details><summary>Request and response fixtures</summary><div class="fixture-list">
          <button type="button" data-path="/inspect">Inspect request</button>
          <button type="button" data-path="/test/headers">Origin response headers</button>
          <button type="button" data-path="/test/cookies?mode=set">Set debug cookies</button>
          <button type="button" data-path="/test/cookies?mode=clear">Clear debug cookies</button>
          <button type="button" data-path="/test/status/418">HTTP 418 status</button>
          <button type="button" data-path="/test/redirect?to=/inspect">Local redirect</button>
          <button type="button" data-path="/test/cache?ttl=60">Cache 60 seconds</button>
          <button type="button" data-path="/healthz">Nginx liveness</button>
        </div><p class="hint">Buttons prepare a path; Run probe sends it. /healthz checks nginx, not PHP-FPM. Cookie fixtures only change debug_lab_visible and debug_lab_httponly. The cache fixture includes your full diagnostic snapshot; use test data only.</p></details>
      </form>
      <div class="toolbar"><button type="button" id="copy-curl">Copy probe as curl</button><button type="button" id="export-json">Export diagnostic JSON</button><button type="button" id="show-initial">Show initial navigation</button></div>
      <p id="probe-status" class="status small" role="status" aria-live="polite">Showing the initial navigation.</p>
    </section>

    <div class="results-grid">
      <section class="card" aria-labelledby="request-title">
        <div class="section-heading"><div><p class="eyebrow">02 / INBOUND</p><h2 id="request-title">Request received by the origin</h2></div><span id="evidence-label" class="badge neutral">Initial navigation</span></div>
        <p class="hint">nginx/PHP-interpreted request, not the original HTTP wire bytes. Duplicate headers may be combined.</p>
        <label for="header-filter">Filter received headers</label><input id="header-filter" type="search" placeholder="Host, CF-Ray, Cookie, X-Debug…">
        <div id="request-headers-view" class="key-values"></div>
        <details open><summary>URL, method and query</summary><pre id="request-url"></pre></details>
        <details open><summary>Cookies received · raw header and parsed values</summary><pre id="request-cookies"></pre></details>
        <details open><summary>Request body</summary><pre id="request-body-view"></pre></details>
        <details><summary>Actual connection and forwarding headers</summary><pre id="request-connection"></pre></details>
      </section>
      <section id="outbound-panel" class="card" aria-labelledby="client-title" data-advanced-only hidden>
        <div class="section-heading"><div><p class="eyebrow">03 / OUTBOUND</p><h2 id="client-title">Response visible to this browser</h2></div><span id="client-code" class="badge neutral">No probe yet</span></div>
        <p class="hint">Captured with Fetch in your browser. This is not a complete wire capture and is never uploaded automatically.</p>
        <div id="client-summary" class="key-values"></div>
        <details open><summary>Headers exposed to the browser</summary><pre id="client-headers">Run a probe to inspect its response.</pre></details>
        <details><summary>Final response body</summary><pre id="client-body">No probe yet.</pre></details>
        <details><summary>Cookies accessible through document.cookie</summary><pre id="browser-cookies"></pre><p class="hint">HttpOnly cookies are not visible here. The Cookie header received by the origin can still contain them.</p></details>
      </section>
    </div>

    <section id="comparison-panel" class="card" aria-labelledby="comparison-title" data-advanced-only hidden>
      <div class="section-heading"><div><p class="eyebrow">04 / COMPARISON</p><h2 id="comparison-title">Origin intent vs. browser observation</h2></div><span id="comparison-count" class="small muted">No comparison yet</span></div>
      <p id="comparison-note" class="hint">The reference covers application-defined status and headers, before nginx/Cloudflare. A difference alone does not identify which layer produced it.</p>
      <div class="table-scroll" role="region" aria-label="Header comparison" tabindex="0"><table><thead><tr><th scope="col">Field</th><th scope="col">Application reference</th><th scope="col">Visible to client</th><th scope="col">Result</th></tr></thead><tbody id="comparison-body"><tr><td colspan="4">Run a probe to compare the same transaction.</td></tr></tbody></table></div>
      <p class="hint">Fetch cannot read Set-Cookie, even when the browser applies it. Additional headers may come from nginx or the proxy. Use DevTools or curl to verify Set-Cookie.</p>
    </section>
    <details id="initial-evidence" class="card" data-advanced-only hidden><summary>Original navigation evidence · full JSON</summary><pre id="initial-json"></pre></details>
  </main>
  <footer class="small muted">HTTP Lab · Basic mode by default · Advanced request/response inspection</footer>
</div>
<script id="initial-snapshot" type="application/json"><?= $snapshotJson ?></script>
</body>
</html>
