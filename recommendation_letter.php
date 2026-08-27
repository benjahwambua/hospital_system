<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Emaqure Medical Centre | Recommendation Letter</title>
  <style>
    /* Page and sizing */
    @page { size: A4; margin: 12mm; }
    :root{
      --primary: #007bff;
      --ink: #111827;
      --muted: #6b7280;
      --bg: #f4f6f9;
      --sky: #87CEFA;
    }

    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0;
      font-family: Arial, Helvetica, sans-serif;
      color:var(--ink);
      background:var(--bg);
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
      /* slightly larger base so text prints bigger */
      font-size: 16px;
      line-height: 1.55;
    }

    /* Use mm-based widths so printed layout matches A4 */
    .container{
      width: calc(210mm - 24mm); /* A4 width minus page margins */
      max-width: calc(210mm - 24mm);
      margin: 0 auto;
      padding: 0;
    }

    .card{
      background:#fff;
      border-radius:6px;
      /* smaller shadow so print cleaner */
      box-shadow:0 6px 18px rgba(0,0,0,0.06);
      padding: 14mm; /* roomy but fits A4 */
      position:relative;
      overflow:hidden;
      min-height: auto;
    }

    .branding{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      margin-bottom:4px;
    }
    .branding .logo img{max-height:54px; display:block}
    .branding .hospital{
      text-align:right;
    }
    .hospital h1{
      margin:0;
      font-size:18px;
      color:#0d3f85;
      text-transform:uppercase;
      letter-spacing:0.02em;
    }
    .hospital p{
      margin:2px 0;
      font-size:13px;
      color:var(--muted);
    }

    /* sky blue divider: print-friendly */
    .divider{
      height:6px;
      background:var(--sky);
      border-radius:3px;
      margin:10px 0;
      border-top:6px solid var(--sky);
      box-sizing: border-box;
    }

    .letter-head{
      margin:6px 0 14px; /* slightly increased bottom space so body isn't squeezed */
    }

    /* "To whom it may concern" left, normal font */
    .letter-head .title{
      margin:0 0 6px 0;
      font-size:17px;
      font-weight:400;
      color:var(--ink);
      text-align:left;
      text-transform:none;
    }

    /* RE bold, underlined and left-aligned, black */
    .letter-head .ref{
      margin:0;
      color:var(--ink);
      font-size:16px;
      text-decoration:underline;
      text-align:left;
      text-transform:none;
      font-weight:700;
    }

    .content{
      margin-top:10px;
      line-height:1.6;
      font-size:16px; /* increased content size */
      color:var(--ink);
    }

    .content p{
      margin:10px 0;
    }

    /* signature block moved to left and compact */
    .signature-row{
      margin-top:14px;
      display:flex;
      justify-content:flex-start; /* left-aligned */
      align-items:flex-end;
    }
    .signature-block{
      width:260px;
      text-align:left;
      font-size:15px;
      color:var(--muted);
    }
    .sig-space{
      height:44px;
      border-bottom:1px solid var(--ink);
      margin-bottom:6px;
      width:220px;
    }
    .sig-name{
      font-weight:700;
      color:var(--ink);
      font-size:15px;
      margin-top:2px;
    }
    .sig-title{
      font-size:13px;
      color:var(--muted);
    }

    .footer-meta{
      margin-top:12px;
      border-top:1px dashed #e5e7eb;
      padding-top:10px;
      font-size:13px;
      color:var(--muted);
      display:flex;
      justify-content:flex-end;
      gap:12px;
      flex-wrap:wrap;
    }

    .actions{
      margin-top:12px;
      text-align:right;
    }
    .btn{
      border: none;
      background: var(--primary);
      color: #fff;
      padding:8px 14px;
      border-radius:6px;
      font-weight:700;
      cursor:pointer;
      font-size:14px;
    }

    /* watermark kept subtle */
    .watermark{
      position:absolute;
      left:50%;
      top:48%;
      transform:translate(-50%,-50%) rotate(-30deg);
      color:var(--primary);
      font-weight:800;
      opacity:0.04;
      font-size:72px;
      pointer-events:none;
      white-space:nowrap;
    }

    /* Print-specific adjustments */
    @media print{
      body{background:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact}
      .card{box-shadow:none; border:none; padding:12mm}
      .actions, .watermark{display:none !important}
      .divider{
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        background:var(--sky);
        border-top:6px solid var(--sky);
      }
      /* ensure font sizes remain large for print */
      body, .content { font-size: 16px; line-height:1.6 }
    }

    /* small screens */
    @media (max-width:640px){
      .branding{flex-direction:column; align-items:flex-start}
      .branding .hospital{width:100%; text-align:left}
      .signature-row{justify-content:flex-start}
      .signature-block{width:100%}
      .footer-meta{justify-content:flex-start}
      .card{padding:18px}
      body{font-size:15px}
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="card" role="document" aria-label="Recommendation letter from Emaqure Medical Centre">
      <div class="watermark" aria-hidden="true">EMAQURE</div>

      <div class="branding" role="presentation">
        <div class="logo">
          <img src="/hospital_system/assets/img/logo.png" alt="Emaqure Medical Centre logo" onerror="this.style.display='none'">
        </div>
        <div class="hospital" aria-hidden="false">
          <h1>Emaqure Medical Centre</h1>
          <p>Biashara Street, Opposite Old Naiwe School, Mlolongo</p>
          <p>Contact: +254793069565 | emaquremedicalcentre@gmail.com</p>
        </div>
      </div>

      <div class="divider" aria-hidden="true"></div>

      <div class="letter-head">
        <p class="title">To whom it may concern</p>
        <p class="ref">RE: RECOMMENDATION FOR Purity Musyoka</p>
      </div>

      <div class="content">
        <p>
          This letter serves to recommend <strong>Purity Musyoka</strong>, who undertook her student attachment at Emaqure Medical Centre from <strong>1 June 2026</strong> to <strong>31 August 2026</strong>.
        </p>

        <p>
          During her attachment period, Purity demonstrated a positive attitude towards learning, professionalism, commitment, and willingness to take on assigned responsibilities. She conducted herself respectfully and maintained good working relationships with staff, patients, and other stakeholders within the medical centre.
        </p>

        <p>
          Purity was eager to learn and readily accepted guidance from her supervisors and colleagues. She demonstrated good communication skills, reliability, and the ability to work effectively both independently and as part of a team. Her conduct throughout the attachment period was commendable, and she showed a genuine interest in gaining practical experience and developing her professional skills.
        </p>

        <p>
          We believe that the knowledge, skills, and experience she gained during her time at Emaqure Medical Centre will be valuable in her future academic and professional pursuits. We are therefore pleased to recommend Purity Musyoka for employment, further training, or any other professional opportunity for which she may be considered.
        </p>

        <p>
          We wish her every success in her future endeavours.
        </p>

        <!-- signature moved to the left inside the content -->
        <div class="signature-row" aria-label="Signature">
          <div class="signature-block" role="contentinfo" aria-label="Emaqure Director signature">
            <div class="sig-space" aria-hidden="true"></div>
            <div class="sig-name">Immaculate Kawira</div>
            <div class="sig-title">Emaqure Director</div>
          </div>
        </div>

        <div class="footer-meta" aria-hidden="false">
          <span>Date Issued: <strong>31 August 2026</strong></span>
        </div>

        <div class="actions" aria-hidden="true">
          <button class="btn" type="button" onclick="window.print()">Print Letter</button>
        </div>
      </div>
    </div>
  </div>
</body>
</html>