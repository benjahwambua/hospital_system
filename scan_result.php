<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emaqure Medical Centre | Scan Findings & Results</title>
    <style>
        :root {
            --primary: #007bff;
            --ink: #1f2937;
            --muted: #6b7280;
            --bg: #f4f6f9;
            --border: #e5e7eb;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; color: var(--ink); background: var(--bg); }
        .report-container { max-width: 980px; margin: 30px auto; padding: 0 20px; }
        .report-card { background:#fff; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.08); padding:30px; min-height:260mm; display:flex; flex-direction:column; }
        .hospital-branding { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; gap:20px; }
        .hospital-logo img { max-height: 75px; width:auto; display:block; }
        .hospital-details { text-align:right; }
        .hospital-details h2 { margin:0; font-size:22px; text-transform:uppercase; color:#0d3f85; }
        .hospital-details p { margin:2px 0; font-size:13px; color:#555; }
        .report-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; padding-bottom:18px; border-bottom:2px solid var(--primary); }
        .report-title { margin:0; font-size:28px; color:var(--primary); }
        .report-sub { font-size:14px; color:var(--muted); margin-top:6px; }
        .meta-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px; margin-bottom:24px; padding:18px; background:#f8fafc; border:1px solid #e6edf7; border-radius:8px; }
        .meta-label { font-size:12px; font-weight:700; color:var(--muted); text-transform:uppercase; margin-bottom:5px; display:block; }
        .line { display:inline-block; min-height:20px; border-bottom:1px solid #111827; vertical-align:bottom; margin:0 4px; }
        .w-sm { width:120px; } .w-md { width:190px; } .w-lg { width:280px; }
        .section { border:1px solid var(--border); border-radius:10px; padding:18px; margin-bottom:16px; }
        .section h3 { margin:0 0 10px; font-size:16px; color:#111827; }
        .box { border:1px solid #d1d5db; border-radius:8px; min-height:130px; }
        .signature-spacer { min-height: 20px; }
        .bottom-block { margin-top:auto; }
        .footer { margin-top:24px; padding-top:12px; border-top:1px dashed #d1d5db; display:flex; justify-content:space-between; font-size:12px; color:var(--muted); }
        .actions { margin-top:18px; padding-top:16px; border-top:2px solid #eee; }
        .btn { border:none; background:var(--primary); color:#fff; border-radius:6px; padding:10px 20px; font-size:14px; font-weight:600; cursor:pointer; }
        @media print {
            .actions { display:none !important; }
            body, .report-container { margin:0 !important; padding:0 !important; background:#fff !important; }
            .report-container { width:100% !important; max-width:100% !important; padding:10mm !important; }
            .report-card { box-shadow:none !important; border:none !important; padding:0 !important; min-height:277mm !important; }
        }
    </style>
</head>
<body>
<div class="report-container">
    <div class="report-card">
        <div class="hospital-branding">
            <div class="hospital-logo"><img src="/hospital_system/assets/img/logo.png" alt="Hospital Logo" onerror="this.style.display='none'"></div>
            <div class="hospital-details">
                <h2>Emaqure Medical Centre</h2>
                <p>Biashara Street, Opposite Old Naiwe School, Mlolongo</p>
                <p>Contact: +254793069565</p>
                <p>emaquremedicalcentre@gmail.com</p>
            </div>
        </div>

        <div class="report-header">
            <div>
                <h1 class="report-title">Scan Findings & Results</h1>
                <p class="report-sub">Form Ref #: <span class="line w-sm"></span></p>
            </div>
            <div class="report-sub">Date Issued: <span class="line w-sm"></span></div>
        </div>

        <div class="meta-grid">
            <div><span class="meta-label">Patient Name</span><span class="line w-lg"></span></div>
            <div><span class="meta-label">Patient ID / File No.</span><span class="line w-md"></span></div>
            <div><span class="meta-label">Requested By</span><span class="line w-md"></span></div>
            <div><span class="meta-label">Scan Type</span><span class="line w-md"></span></div>
        </div>

        <div class="bottom-block">
        <div class="signature-spacer"></div>

        <section class="section">
            <h3>Radiologist / Sonographer Name & Signature</h3>
            <div style="margin-top:28px; border-top:1px solid #111827; padding-top:6px; font-size:12px; color:#374151;"></div>
        </section>

        <div class="footer">
            <span>Issued By: Emaqure Medical Centre</span>
            <span>For Clinical Filing and Employer/Referral Submission</span>
        </div>

        <div class="actions">
            <button type="button" class="btn" onclick="window.print()">Print Scan Form</button>
        </div>
        </div>
    </div>
</div>
</body>
</html>