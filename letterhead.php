<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Emaqure Medical Centre | Letterhead (A4)</title>

  <style>
    /* Force A4 page with 12mm margins */
    @page { size: A4; margin: 12mm; }

    :root{
      --primary:#007bff;
      --ink:#111827;
      --muted:#6b7280;
      --sky:#87CEFA;
      --bg:#ffffff;
    }

    *{box-sizing:border-box}
    html,body{height:100%;margin:0;padding:0;background:var(--bg);font-family:Arial,Helvetica,sans-serif;-webkit-font-smoothing:antialiased;}
    body{color:var(--ink);font-size:15px;line-height:1.6;}

    /* Page container sized to A4 printable content area */
    .page {
      width: calc(210mm - 24mm);        /* A4 width minus left+right page margins */
      height: calc(297mm - 24mm);       /* A4 height minus top+bottom page margins */
      margin: 0 auto;
      padding: 0;
    }

    /* The sheet matches the printable area exactly; padding is INCLUDED in the height via border-box */
    .sheet {
      width: 100%;
      height: 100%;
      background: #fff;
      padding: 12mm;                    /* internal padding inside printable area */
      display: flex;
      flex-direction: column;
      position: relative;
      overflow: hidden;                 /* prevent overflow to additional pages */
      box-sizing: border-box;
      border-radius: 4px;
    }

    /* subtle on-screen shadow (not printed) */
    .sheet { box-shadow: 0 6px 18px rgba(0,0,0,0.06); }

    /* watermark */
    .watermark{
      position:absolute;
      left:50%;
      top:50%;
      transform:translate(-50%,-50%) rotate(-30deg);
      font-size:72px;
      font-weight:800;
      color:var(--primary);
      opacity:0.04;
      pointer-events:none;
      z-index:0;
      white-space:nowrap;
    }

    /* header */
    header.letterhead {
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      z-index:1;
      flex: 0 0 auto;
    }
    .logo img{max-height:62px; display:block;}
    .org-details { text-align:right; }
    .org-details h1{ margin:0; font-size:18px; color:#0d3f85; text-transform:uppercase; letter-spacing:.02em; }
    .org-details p{ margin:2px 0; font-size:13px; color:var(--muted); }

    /* sky-blue divider (print-safe) */
    .divider{
      height:6px;
      background:var(--sky);
      border-top:6px solid var(--sky);
      border-radius:3px;
      margin:10px 0 14px;
      z-index:1;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    /* meta row (small) */
    .meta {
      display:flex;
      gap:16px;
      align-items:flex-start;
      margin-bottom:8px;
      z-index:1;
      flex: 0 0 auto;
    }
    .meta .left, .meta .right { flex:1; }
    .meta .right { text-align:right; }

    .date-line {
      display:inline-block;
      min-width:160px;
      border-bottom:1px solid #111827;
      height:20px;
      vertical-align:middle;
    }

    /* main body area: fills available vertical space between header and footer */
    .body {
      z-index:1;
      flex: 1 1 auto;
      overflow: hidden;              /* ensure body doesn't force page overflow */
      display:flex;
      flex-direction:column;
    }

    .body .content {
      flex: 1 1 auto;
      border: none;
      background: transparent;
      outline: none;
      white-space: pre-wrap;
      font-size:16px;
      color:var(--ink);
      padding: 0;
      margin: 0;
      overflow: auto;               /* allow scrolling in small windows, but print will include visible area */
    }

    /* signature/stamp footer stuck to the bottom inside the printable area */
    .footer {
      z-index:1;
      flex: 0 0 auto;
      display:flex;
      justify-content:space-between;
      gap:12px;
      align-items:flex-end;
      margin-top:12px;
    }

    .sign-left { width: 55%; text-align:left; }
    .sign-line { border-top:1px solid #111827; padding-top:6px; min-height:36px; display:inline-block; }
    .sign-meta { font-size:13px; color:var(--muted); margin-top:6px; }

    .stamp { width:180px; text-align:center; border:1px dashed #cbd5e1; padding:10px; border-radius:4px; font-size:13px; color:var(--muted); }

    .actions { margin-top:10px; text-align:right; z-index:1; }
    .btn { background:var(--primary); color:#fff; border:none; padding:8px 12px; border-radius:6px; cursor:pointer; font-weight:700; }

    /* print adjustments */
    @media print {
      html,body{height:auto}
      body{ background:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
      .sheet{ box-shadow:none; border:none; padding:12mm; height: calc(297mm - 24mm); overflow: hidden; }
      .actions{ display:none !important; }
      .divider{ -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      /* ensure content prints within available space */
      .body .content{ overflow: visible; }
    }

    /* responsive small screen */
    @media (max-width:640px){
      .page{ width: calc(100% - 20px); height: auto;}
      .sheet{ padding:18px; min-height: auto; box-shadow:none; }
      .watermark{ display:none; }
      .meta{ flex-direction:column; }
      .stamp{ width:140px; }
      .sign-left{ width:100%; }
    }
  </style>
</head>
<body>
  <div class="page" role="document" aria-label="Emaqure Medical Centre letterhead (A4)">
    <div class="sheet">
      <div class="watermark" aria-hidden="true">EMAQURE</div>

      <header class="letterhead" role="banner">
        <div class="logo" aria-hidden="false">
          <img src="/hospital_system/assets/img/logo.png" alt="Emaqure Medical Centre logo" onerror="this.style.display='none'">
        </div>

        <div class="org-details" aria-hidden="false">
          <h1>Emaqure Medical Centre</h1>
          <p>Biashara Street, Opposite Old Naiwe School, Mlolongo</p>
          <p>Contact: +254793069565</p>
          <p>emaquremedicalcentre@gmail.com</p>
        </div>
      </header>

      <div class="divider" aria-hidden="true"></div>

      <section class="meta" aria-label="Document meta">
        <div class="left">
          <div><strong>Date:</strong> <span class="date-line" aria-hidden="true"></span></div>
          <div style="height:8px"></div>
          <div class="to-subject">
            <div><strong>To:</strong> <span class="date-line" style="width:55%;"></span></div>
            <div style="height:6px"></div>
            <div><strong>Subject:</strong> <span class="date-line" style="width:65%;"></span></div>
          </div>
        </div>

        <div class="right" aria-hidden="true">
          <div style="margin-bottom:10px;"><strong>Ref:</strong> <span class="date-line" style="width:120px;"></span></div>
        </div>
      </section>

      <main class="body" aria-label="Letter body">
        <div class="content" contenteditable="false" aria-placeholder="Write or print your content here"></div>
      </main>

      <footer class="footer" role="contentinfo">
        <div class="sign-left">
          <div class="sign-line" style="width:260px;"></div>
          <div class="sign-meta">Name: ____________________________</div>
          <div class="sign-meta">Designation: ______________________</div>
        </div>

        <div class="stamp" aria-hidden="true">Official Stamp</div>
      </footer>

      <div class="actions" aria-hidden="true">
        <button class="btn" type="button" onclick="window.print()">Print</button>
      </div>
    </div>
  </div>
</body>
</html>