# HTTP Lab Design Contract

## 1. Identity
An operational HTTP inspector that prioritizes a truthful boundary between the
origin's observations and what the browser can actually see. Preserve the existing
gray background, white card, Arial headings, and monospace protocol values.

## 2. Color
Use the CSS variables defined in `assets/app.css`: canvas `#f4f6f8`, surface `#fff`,
ink `#0f172a`, body `#222`, muted `#475569`, line `#cbd5e1`, inset `#e5e7eb`, accent
`#1d4ed8`, success `#166534`, warning `#92400e`, and error `#b91c1c`. Never encode a
result through color alone.

## 3. Typography
Arial/system sans for controls and prose; Consolas/ui-monospace for HTTP values.
Base16px, secondary14px, metadata12px, h1 clamp24–32px, h2 20px, line-height1.5.

## 4. Layout
Four-pixel spacing scale,1320px maximum content width,16px mobile gutters. The
document owns vertical scrolling. The result grid stacks responsively; each grid
child can shrink, long values wrap, and the comparison table scrolls within its own
region on narrow screens.

## 5. Primitives
Cards, status badges, labeled fields, controls, key/value rows, and a response
comparison table use semantic HTML and visible focus styles. Empty, loading, error,
disabled, and long-value states are part of each relevant primitive.

## 6. Interaction
No decorative motion. Keep initial navigation evidence distinct from each new
same-origin browser probe. Use native keyboard controls and respect reduced motion.
Open in Basic mode on every page load: header, connection summary, and inbound
request only. A visible Basic/Advanced button group in the header reveals the
composer, browser response, comparison, and original JSON evidence in Advanced.
Switching modes only changes visibility; it preserves probe inputs and results.
Basic uses a full-width inbound card. Hidden panels are absent from keyboard focus.

## 7. Depth
Use the inherited light-card surface, subtle shadow, and inset code panels. No remote
assets, fonts, frameworks, gradients, or network-dependent decorations.

## 8. Accessibility and accepted limitations
Use semantic landmarks, labeled controls, keyboard focus, and a live status region.
Render untrusted values as text, never HTML. Nothing is redacted by design; do not
retain history or transmit browser observations automatically. Fetch cannot expose
Set-Cookie/HttpOnly and cannot set Host/Cookie. A probe may reach a different backend.
The app response reference is not a complete capture of nginx or proxy output.
