import { byId, pretty, setText, renderSnapshot, renderBrowser, renderComparison } from './render.js';

const initial = JSON.parse(byId('initial-snapshot').textContent);
let displayed = initial;
let evidenceLabel = 'Initial navigation';
let lastProbe = null;
setText('initial-json', pretty(initial));
renderSnapshot(initial, evidenceLabel);
renderBrowser(null);

function status(message, type = '') {
  setText('probe-status', message); byId('probe-status').className = `status small ${type}`;
}

function requestOptions(allowReserved = false) {
  const url = new URL(byId('path').value, window.location.origin);
  if (url.origin !== window.location.origin || url.username || url.password) throw new Error('Only same-origin paths are allowed; URL credentials are not allowed.');
  const method = byId('method').value;
  const requestedHeaders = JSON.parse(byId('request-headers').value || '{}');
  if (requestedHeaders === null || Array.isArray(requestedHeaders) || typeof requestedHeaders !== 'object') throw new Error('Headers must be a JSON object with string names and values.');
  const headers = new Headers();
  for (const [name, value] of Object.entries(requestedHeaders)) {
    if (typeof value !== 'string') throw new Error(`The value of ${name} must be text.`);
    if (!allowReserved && (/^(host|cookie|cookie2|connection|content-length|origin|referer|accept-encoding|transfer-encoding|upgrade|keep-alive|te|trailer|expect|date|dnt|via|access-control-request-headers|access-control-request-method)$/i.test(name) || /^(sec-|proxy-)/i.test(name))) throw new Error(`${name} is reserved by the browser. Use curl to test reserved headers.`);
    headers.set(name, value);
  }
  const body = ['GET', 'HEAD'].includes(method) ? undefined : byId('request-body').value;
  return { url: url.href, method, headers, body };
}

function isSnapshot(value) {
  return value?.schema_version === 1 && typeof value.backend?.name === 'string' && Array.isArray(value.request?.headers) && Array.isArray(value.origin_response?.headers);
}

byId('method').addEventListener('change', () => { byId('request-body').disabled = ['GET', 'HEAD'].includes(byId('method').value); });
document.querySelectorAll('[data-path]').forEach((button) => button.addEventListener('click', () => {
  byId('path').value = button.dataset.path;
  byId('method').value = 'GET'; byId('request-body').disabled = true;
  status(`Path prepared: ${button.dataset.path}. Press “Run probe” to send it.`);
}));
byId('header-filter').addEventListener('input', () => renderSnapshot(displayed, evidenceLabel, byId('header-filter').value));

byId('probe-form').addEventListener('submit', async (event) => {
  event.preventDefault();
  let options;
  try { options = requestOptions(); } catch (error) { status(error.message, 'error'); return; }
  byId('run-probe').disabled = true; status('Request in progress…');
  const start = performance.now();
  try {
    const response = await fetch(options.url, { method: options.method, headers: options.headers, body: options.body, credentials: 'same-origin', redirect: 'follow' });
    const text = await response.text();
    let payload = null;
    try { payload = JSON.parse(text); } catch { payload = null; }
    const snapshot = isSnapshot(payload) ? payload : null;
    const observation = { status: response.status, status_text: response.statusText, url: response.url, redirected: response.redirected, duration_ms: Math.round(performance.now() - start), headers: [...response.headers].map(([name, value]) => ({ name, value })), body: text, request_id: snapshot?.request.id ?? null };
    lastProbe = { requested: { url: options.url, method: options.method, headers: [...options.headers], body: options.body ?? null }, snapshot, browser: observation };
    if (snapshot) {
      displayed = snapshot; evidenceLabel = 'Latest new probe';
      renderSnapshot(snapshot, evidenceLabel, byId('header-filter').value);
    } else {
      evidenceLabel = 'Initial navigation · probe has no origin manifest'; displayed = initial;
      renderSnapshot(initial, evidenceLabel, byId('header-filter').value);
    }
    renderBrowser(observation); renderComparison(snapshot, observation);
    status(`Probe complete: HTTP ${response.status}. ${snapshot ? `Origin: ${snapshot.backend.name}.` : 'Non-diagnostic or empty body; no origin reference is available for comparison.'}`, 'success');
  } catch (error) {
    status(`The response could not be observed: ${error.message}. This may be a network, TLS, CORS or browser-policy error.`, 'error');
  } finally { byId('run-probe').disabled = false; }
});

byId('show-initial').addEventListener('click', () => {
  displayed = initial; evidenceLabel = 'Initial navigation';
  renderSnapshot(initial, evidenceLabel, byId('header-filter').value);
  renderBrowser(null); renderComparison(null, null); status('Showing the initial navigation. The latest probe remains in the export.');
});

byId('export-json').addEventListener('click', () => {
  const blob = new Blob([pretty({ initial_navigation: initial, last_probe: lastProbe, exported_at: new Date().toISOString(), browser_visible_cookies: document.cookie })], { type: 'application/json' });
  const url = URL.createObjectURL(blob); const link = document.createElement('a');
  link.href = url; link.download = `http-lab-${initial.request.id}.json`; link.click();
  setTimeout(() => URL.revokeObjectURL(url), 1000); status('Diagnostic exported locally without redaction.');
});

const shellQuote = (value) => `'${String(value).replaceAll("'", "'\\''")}'`;
byId('copy-curl').addEventListener('click', async () => {
  try {
    const options = requestOptions(true);
    const parts = ['curl', '--include', '--location'];
    if (options.method === 'HEAD') parts.push('--head');
    else parts.push('--request', shellQuote(options.method));
    options.headers.forEach((value, name) => parts.push('--header', shellQuote(`${name}: ${value}`)));
    if (options.body !== undefined) parts.push('--data-raw', shellQuote(options.body));
    parts.push(shellQuote(options.url));
    const command = parts.join(' ');
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(command); status('curl command copied. It reproduces the configured probe, not every browser-generated header.');
    } else {
      window.prompt('Copy the configured probe as a curl command:', command);
      status('curl command is ready to copy.');
    }
  } catch (error) { status(error.message, 'error'); }
});
