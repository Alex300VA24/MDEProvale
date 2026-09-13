export function pdfLoadingHtml(title, message, hint = 'No cierres esta pestaña...') {
    const safeTitle = String(title).replace(/[<>&]/g, '');
    const safeMessage = String(message).replace(/[<>&]/g, '');
    const safeHint = String(hint).replace(/[<>&]/g, '');
    return `<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Generando PDF...</title>
<style>
  :root { color-scheme: light dark; }
  * { box-sizing: border-box; }
  body {
    margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
    background: #F1F5F9; color: #2D3748;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
    padding: 24px;
  }
  @media (prefers-color-scheme: dark) {
    body { background: #1a2233; color: #E8DCC8; }
    .card { background: #232d42 !important; border-color: #384258 !important; }
    .bar-track { background: #384258 !important; }
  }
  .card {
    width: 100%; max-width: 380px; background: #ffffff; border: 2px solid #E8DCC8;
    border-radius: 20px; padding: 32px 28px; text-align: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
  }
  .icon-wrap {
    width: 64px; height: 64px; margin: 0 auto 18px; border-radius: 50%;
    background: #DCFCE7; display: flex; align-items: center; justify-content: center;
  }
  .icon-wrap svg { width: 30px; height: 30px; animation: spin 1.1s linear infinite; }
  @keyframes spin { to { transform: rotate(360deg); } }
  h1 { font-size: 17px; font-weight: 800; margin: 0 0 6px; }
  p { font-size: 13px; margin: 0 0 20px; color: #6B7280; line-height: 1.5; }
  .bar-track { width: 100%; height: 8px; border-radius: 999px; background: #E8DCC8; overflow: hidden; }
  .bar-fill {
    height: 100%; width: 40%; border-radius: 999px; background: #166534;
    animation: indeterminate 1.4s ease-in-out infinite;
  }
  @keyframes indeterminate {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(350%); }
  }
  .hint { margin-top: 16px; font-size: 11px; color: #6B7280; }
  @media (prefers-reduced-motion: reduce) {
    .icon-wrap svg { animation: none; }
    .bar-fill { animation: none; width: 100%; opacity: 0.6; }
  }
</style>
</head>
<body>
  <div class="card" role="status" aria-live="polite">
    <div class="icon-wrap">
      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M21 12a9 9 0 1 1-9-9" stroke="#166534" stroke-width="2.5" stroke-linecap="round"/>
      </svg>
    </div>
    <h1>${safeTitle}</h1>
    <p>${safeMessage}</p>
    <div class="bar-track"><div class="bar-fill"></div></div>
    <div class="hint">${safeHint}</div>
  </div>
</body>
</html>`;
}
