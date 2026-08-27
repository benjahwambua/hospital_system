<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Emaqure Medical Centre | Sick Leave Certificate</title>
  <style>
    /* Page size */
    @page { size: A4; margin: 12mm; }

    :root {
      --primary: #007bff;
      --ink: #1f2937;
      --muted: #6b7280;
      --bg: #f4f6f9;
      --border: #e5e7eb;
    }

    * { box-sizing: border-box; }

    html, body { height: 100%; margin: 0; padding: 0; }

    body {
      font-family: Arial, Helvetica, sans-serif;
      color: var(--ink);
      background: var(--bg);
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      font-size: 17px;
      line-height: 1.6;
    }

    /* Use mm-based container so it maps well to A4 */
    .certificate-container {
      width: calc(210mm - 24mm); /* A4 width minus page margins */
      max-width: calc(210mm - 24mm);
      margin: 18px auto;
      padding: 0 10px;
    }

    .certificate-card {
      position: relative;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.06);
      overflow: hidden;
      padding: 14mm; /* roomy padding but fits A4 */
      display: flex;
      flex-direction: column;
      /* force the card to fill printable height so we can push signatures to bottom */
      min-height: calc(297mm - 24mm); /* A4 height minus page margins */
    }

    .watermark {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) rotate(-30deg);
      opacity: 0.04;
      font-size: 72px;
      font-weight: 700;
      color: var(--primary);
      pointer-events: none;
      z-index: 0;
      white-space: nowrap;
    }

    .branding,
    .certificate-header,
    .meta-grid,
    .certificate-body,
    .bottom-block,
    .actions {
      position: relative;
      z-index: 1;
    }

    .branding {
      display: flex;
      justify-content: space-between;
      gap: 16px;
      align-items: center;
      margin-bottom: 6px;
    }
    .hospital-logo img { max-height: 62px; width: auto; display: block; }
    .hospital-details { text-align: right; }
    .hospital-details h1 {
      margin: 0;
      font-size: 20px;
      text-transform: uppercase;
      color: #0d3f85;
      letter-spacing: .02em;
    }
    .hospital-details p {
      margin: 3px 0;
      font-size: 13px;
      color: #4b5563;
    }

    .certificate-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-bottom: 12px;
      border-bottom: 2px solid var(--primary);
      margin-bottom: 18px;
    }

    .certificate-title { margin: 0; color: var(--primary); font-size: 26px; }
    .certificate-ref { margin-top: 6px; font-size: 14px; color: var(--muted); }

    .status-pill {
      display: inline-block;
      border-radius: 999px;
      padding: 6px 12px;
      background: #d4edda;
      color: #155724;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
    }

    .meta-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 14px;
      background: #f8fafc;
      border: 1px solid #e6edf7;
      border-radius: 8px;
      padding: 14px;
      margin-bottom: 18px;
      font-size: 15px;
    }

    .meta-label {
      display: block;
      font-size: 12px;
      font-weight: 700;
      color: var(--muted);
      text-transform: uppercase;
      margin-bottom: 5px;
    }

    .meta-value { display: block; font-size: 15px; color: #111827; }

    .certificate-body {
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 14px;
      font-size: 17px;
    }

    .row {
      margin-bottom: 28px; /* increased spacing between paragraphs */
      line-height: 1.75;
      font-size: 17px;
    }

    .line {
      display: inline-block;
      min-height: 20px;
      border-bottom: 1px solid #111827;
      vertical-align: bottom;
      margin: 0 6px;
    }

    .w-xs { width: 70px; }
    .w-sm { width: 140px; }
    .w-md { width: 210px; }
    .w-lg { width: 280px; }
    .w-xl { width: 430px; }

    .note-box {
      border: 1px solid #d1d5db;
      border-radius: 8px;
      min-height: 95px;
      margin-top: 8px;
    }

    .signature-spacer { min-height: 14px; }

    /* bottom-block is pushed to the bottom of the card */
    .bottom-block {
      margin-top: auto;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
    }

    .signatures {
      margin-top: 18px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 18px;
      align-items: end;
    }

    .signature-line {
      border-top: 1px solid #111827;
      padding-top: 6px;
      text-align: center;
      font-size: 13px;
      color: #374151;
      min-height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .certificate-footer { display: none; }

    .actions {
      margin-top: 12px;
      padding-top: 12px;
      border-top: 1px solid #eee;
      text-align: right;
    }

    .btn {
      border: none;
      background: var(--primary);
      color: #fff;
      border-radius: 6px;
      padding: 8px 16px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
    }

    /* Print rules */
    @media print {
      body, .certificate-container { background: #fff !important; margin: 0; padding: 0; }
      .certificate-card { box-shadow: none !important; border: none !important; padding: 12mm !important; min-height: calc(297mm - 24mm); }
      .actions { display: none !important; }
      /* preserve blue header border on print */
      .certificate-header {
        border-bottom: 2px solid var(--primary);
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }

    /* Responsive small screens */
    @media (max-width: 640px) {
      .certificate-container { width: calc(100% - 20px); padding: 0 10px; }
      .certificate-card { padding: 18px; min-height: auto; }
      .meta-grid { grid-template-columns: 1fr; }
      .signatures { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="certificate-container">
    <div class="certificate-card">
      <div class="watermark" aria-hidden="true">EMAQURE</div>

      <div class="branding">
        <div class="hospital-logo">
          <img src="/hospital_system/assets/img/logo.png" alt="Hospital Logo" onerror="this.style.display='none'">
        </div>
        <div class="hospital-details">
          <h1>Emaqure Medical Centre</h1>
          <p>Biashara Street, Opposite Old Naiwe School, Mlolongo</p>
          <p>Contact: +254793069565</p>
          <p>emaquremedicalcentre@gmail.com</p>
        </div>
      </div>

      <div class="certificate-header">
        <div>
          <h2 class="certificate-title">Sick Leave Certificate</h2>
          <p class="certificate-ref">Certificate Ref #: <span class="line w-sm"></span></p>
        </div>
        <span class="status-pill">Official Medical Note</span>
      </div>

      <div class="meta-grid">
        <div>
          <span class="meta-label">Patient Name</span>
          <span class="meta-value"><span class="line w-lg"></span></span>
        </div>
        <div>
          <span class="meta-label">Date Issued</span>
          <span class="meta-value"><span class="line w-sm"></span></span>
        </div>
        <div>
          <span class="meta-label">Patient ID / Passport</span>
          <span class="meta-value"><span class="line w-md"></span></span>
        </div>
        <div>
          <span class="meta-label">Employer / Workplace</span>
          <span class="meta-value"><span class="line w-md"></span></span>
        </div>
      </div>

      <div class="certificate-body">
        <div class="row">
          This is to certify that the patient named above was examined and treated at Emaqure Medical Centre and is advised to be on sick leave from <span class="line w-sm"></span> to <span class="line w-sm"></span>.
        </div>

        <div class="row">
          Due to the condition of the patient and the effect of prescribed drugs, he/she is granted <span class="line w-sm"></span> days off duty with effect from the above said date. In case of any clarification you can contact us via the email or phone number provided above.
        </div>

        <div class="row">
          Due to doctor–patient confidentiality, the details of his/her sickness remain confidential. Please accord him/her the necessary assistance.
        </div>
      </div>

      <div class="bottom-block">
        <div class="signature-spacer" aria-hidden="true"></div>

        <div class="signatures" role="contentinfo" aria-label="Signatures and stamp">
          <div class="signature-line" style="text-align:left; padding-left:12px;">
            <!-- Left aligned signature area: blank for signer to fill -->
            <div style="width:260px;">
              <div style="height:36px;"></div>
              <div style="font-weight:700; color:#111827; font-size:15px; margin-top:6px;">Name: ____________________________</div>
              <div style="font-size:13px; color:var(--muted); margin-top:4px;">Designation: ______________________</div>
            </div>
          </div>

          <div class="signature-line" style="text-align:center;">
            <!-- Stamp area -->
            Official Stamp
          </div>
        </div>

        <div class="actions" aria-hidden="true">
          <button type="button" class="btn" onclick="window.print()">Print Certificate</button>
        </div>
      </div>
    </div>
  </div>
</body>
</html>