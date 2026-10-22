export const pretty = (value) => JSON.stringify(value, null, 2);
export const byId = (id) => document.getElementById(id);
export function setText(id, value) { byId(id).textContent = value; }

export function keyValues(id, entries) {
  const target = byId(id);
  target.replaceChildren();
  if (!entries.length) {
    const empty = document.createElement('p');
    empty.className = 'empty'; empty.textContent = 'No values.';
    target.append(empty); return;
  }
  const list = document.createElement('dl');
  entries.forEach(([name, value]) => {
    const row = document.createElement('div'); row.className = 'kv';
    const term = document.createElement('dt'); term.textContent = name;
    const detail = document.createElement('dd'); detail.textContent = String(value ?? '');
    row.append(term, detail); list.append(row);
  });
  target.append(list);
}

export function renderSnapshot(snapshot, label, filter = '') {
  const { backend, request } = snapshot;
  setText('backend-name', backend.name);
  setText('summary-backend', `${backend.name} / ${backend.server_port}`);
  setText('summary-request', `${request.method} ${request.request_uri}`);
  setText('summary-peer', `${backend.peer_addr}:${backend.peer_port}`);
  setText('summary-id', request.id);
  setText('evidence-label', label);
  const needle = filter.toLowerCase();
  keyValues('request-headers-view', request.headers.filter(({ name, value }) => `${name} ${value}`.toLowerCase().includes(needle)).map(({ name, value }) => [name, value]));
  setText('request-url', pretty({ method: request.method, request_uri: request.request_uri, path: request.path, received_at: request.received_at, query: request.query }));
  setText('request-cookies', pretty(request.cookies));
  setText('request-body-view', pretty(request.body));
  setText('request-connection', pretty({ connection: backend, forwarded_headers_not_verified: request.forwarded }));
}

export function renderBrowser(observation) {
  setText('browser-cookies', document.cookie || '(No cookies are accessible to JavaScript.)');
  if (!observation) {
    setText('client-code', 'No probe yet');
    keyValues('client-summary', []);
    setText('client-headers', 'Run a probe to inspect its response.');
    setText('client-body', 'No probe yet.'); return;
  }
  setText('client-code', String(observation.status));
  keyValues('client-summary', [['Status', `${observation.status} ${observation.status_text}`], ['Final URL', observation.url], ['Redirected', observation.redirected ? 'Yes; the reference corresponds to the final response.' : 'No'], ['Client time', `${observation.duration_ms} ms`], ['Transaction', observation.request_id ?? 'No origin manifest']]);
  setText('client-headers', pretty(observation.headers));
  setText('client-body', observation.body || '(Empty body; this can be normal for HEAD, 204 or 304.)');
}

function groupHeaders(rows) {
  const result = new Map();
  rows.forEach(({ name, value }) => {
    const key = name.toLowerCase();
    result.set(key, [...(result.get(key) ?? []), value]);
  });
  return result;
}

export function renderComparison(snapshot, observation) {
  const target = byId('comparison-body'); target.replaceChildren();
  if (!snapshot || !observation) {
    const row = document.createElement('tr'); const cell = document.createElement('td'); cell.colSpan = 4;
    cell.textContent = observation ? 'No recognizable manifest: this response cannot be compared with origin intent.' : 'Run a probe to compare the same transaction.';
    row.append(cell); target.append(row); setText('comparison-count', 'No comparison'); return;
  }
  const origin = groupHeaders(snapshot.origin_response.headers);
  const visible = new Map(observation.headers.map(({ name, value }) => [name.toLowerCase(), value]));
  const rows = [['HTTP status', String(snapshot.origin_response.status), String(observation.status), snapshot.origin_response.status === observation.status ? 'Same' : 'Changed', snapshot.origin_response.status === observation.status ? 'same' : 'changed']];
  for (const [name, values] of origin) {
    if (['set-cookie', 'set-cookie2'].includes(name)) {
      rows.push([name, values.join('\n'), 'Not exposed by Fetch', 'Not observable', 'unknown']); continue;
    }
    const actual = visible.get(name);
    const expected = values.join(', ');
    rows.push([name, expected, actual ?? '(Not visible)', actual === undefined ? 'Missing / not exposed' : actual === expected ? 'Same' : 'Changed', actual === undefined ? 'missing' : actual === expected ? 'same' : 'changed']);
  }
  for (const [name, value] of visible) {
    if (!origin.has(name)) rows.push([name, '(Outside the application reference)', value, 'nginx / proxy / transport', 'unknown']);
  }
  rows.forEach(([name, expected, actual, result, classification]) => {
    const row = document.createElement('tr'); row.dataset.result = classification;
    [name, expected, actual, result].forEach((value) => { const cell = document.createElement('td'); cell.textContent = value; row.append(cell); });
    target.append(row);
  });
  setText('comparison-count', `${rows.length} fields · same probe response`);
}
