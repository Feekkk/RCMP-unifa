<?php
session_start();
require_once __DIR__ . '/../database/database.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'student') {
    header('Location: ../auth/login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$success = '';
$error = '';
$userBank = ['bank_name' => '', 'bank_account' => ''];
try {
    $stmt = $pdo->prepare('SELECT bank_name, bank_account FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $userBank['bank_name'] = $row['bank_name'] ?? '';
        $userBank['bank_account'] = $row['bank_account'] ?? '';
    }
} catch (PDOException $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = trim($_POST['category'] ?? '');
    $subtype = trim($_POST['subtype'] ?? '');
    $bank_name = trim($userBank['bank_name']);
    $bank_account = trim($userBank['bank_account']);
    $amount = trim($_POST['amount_applied'] ?? '');
    $clinic_name = trim($_POST['clinic_name'] ?? '');
    $reason_visit = trim($_POST['reason_visit'] ?? '');
    $visit_datetime = trim($_POST['visit_datetime'] ?? '');
    $checkin_date = trim($_POST['checkin_date'] ?? '');
    $checkout_date = trim($_POST['checkout_date'] ?? '');
    $case_description = trim($_POST['case_description'] ?? '');

    $amounts = ['bereavement' => ['student' => 500, 'parent' => 200, 'sibling' => 100]];
    if ($category === 'bereavement' && isset($amounts['bereavement'][$subtype])) {
        $amount = (string) $amounts['bereavement'][$subtype];
    }
    $valid = in_array($category, ['bereavement', 'illness', 'emergency']) && $subtype !== '' && $bank_name !== '' && $bank_account !== '';
    if ($category === 'illness' || $category === 'emergency') {
        $valid = $valid && $amount !== '' && is_numeric($amount);
    }
    $requiredDoc = null;
    if ($valid) {
        $key = $category . '_' . $subtype;
        $req = [
            'bereavement_student' => ['file' => 'death_certificate'],
            'bereavement_parent'  => ['file' => 'death_certificate'],
            'bereavement_sibling' => ['file' => 'death_certificate'],
            'illness_outpatient'  => ['clinic_name' => $clinic_name, 'reason_visit' => $reason_visit, 'visit_datetime' => $visit_datetime, 'file' => 'receipt_clinic'],
            'illness_inpatient'   => ['reason_visit' => $reason_visit, 'checkin_date' => $checkin_date, 'checkout_date' => $checkout_date, 'file' => 'documents'],
            'illness_injuries'    => ['file' => 'documents'],
            'emergency_critical'  => ['file' => 'supporting_doc'],
            'emergency_natural'   => ['case_description' => $case_description, 'file' => 'supporting_doc'],
            'emergency_others'    => ['case_description' => $case_description, 'file' => 'supporting_doc'],
        ];
        if (isset($req[$key])) {
            foreach ($req[$key] as $k => $v) {
                if ($k === 'file') {
                    $up = $_FILES[$v] ?? [];
                    $fname = is_array($up['name'] ?? null) ? ($up['name'][0] ?? '') : ($up['name'] ?? '');
                    if ($fname === '') { $requiredDoc = 'document'; break; }
                } else {
                    if (trim((string)$v) === '') { $requiredDoc = $k; break; }
                }
            }
        }
        if ($requiredDoc !== null) $valid = false;
    }
    if (!$valid) {
        if ($bank_name === '' || $bank_account === '') {
            $error = 'Please update your bank name and account in Profile first.';
        } elseif ($requiredDoc !== null) {
            $error = 'Please complete all required details and upload the required document.';
        } else {
            $error = 'Please fill required fields (category, subtype' . ($category !== 'bereavement' ? ', amount' : '') . ').';
        }
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO applications (user_id, category, subtype, amount_applied, bank_name, bank_account, status_id, clinic_name, reason_visit, visit_datetime, checkin_date, checkout_date, case_description, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([
                $userId,
                $category,
                $subtype,
                $amount ?: null,
                $bank_name,
                $bank_account,
                $clinic_name ?: null,
                $reason_visit ?: null,
                $visit_datetime ?: null,
                $checkin_date ?: null,
                $checkout_date ?: null,
                $case_description ?: null,
            ]);
            $appId = (int) $pdo->lastInsertId();

            $uploadDir = __DIR__ . '/../public/documents/' . date('Ym');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $docTypes = ['death_certificate', 'receipt_clinic', 'documents', 'supporting_doc'];
            foreach ($docTypes as $dt) {
                $up = $_FILES[$dt] ?? [];
                $fname = is_array($up['name'] ?? null) ? ($up['name'][0] ?? '') : ($up['name'] ?? '');
                $ferr = is_array($up['error'] ?? null) ? ($up['error'][0] ?? UPLOAD_ERR_NO_FILE) : ($up['error'] ?? UPLOAD_ERR_NO_FILE);
                $tmp = is_array($up['tmp_name'] ?? null) ? ($up['tmp_name'][0] ?? '') : ($up['tmp_name'] ?? '');
                if ($fname !== '' && $ferr === UPLOAD_ERR_OK && $tmp !== '') {
                    $ext = pathinfo($fname, PATHINFO_EXTENSION) ?: 'bin';
                    $ext = in_array(strtolower($ext), ['pdf', 'jpg', 'jpeg', 'png']) ? strtolower($ext) : 'bin';
                    $filename = uniqid('', true) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($fname));
                    $dest = $uploadDir . '/' . $filename;
                    if (move_uploaded_file($tmp, $dest)) {
                        $relPath = 'documents/' . date('Ym') . '/' . $filename;
                        $ins = $pdo->prepare('INSERT INTO document (application_id, file_path, document_type, created_at) VALUES (?, ?, ?, NOW())');
                        $ins->execute([$appId, $relPath, $dt]);
                    }
                }
            }

            header('Location: dashboard.php?submitted=1');
            exit;
        } catch (PDOException $e) {
            $error = 'Could not save application. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fund Application — RCMP UniFa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; min-height: 100vh; background: #f9fafb; color: #111827; }
        .page-bg { position: fixed; inset: 0; background: url("../public/bgm.png") center/cover no-repeat; filter: blur(12px); transform: scale(1.05); opacity: 0.15; z-index: -2; }
        .page-overlay { position: fixed; inset: 0; background: linear-gradient(180deg, rgba(249,250,251,0.7) 0%, #f9fafb 50%); z-index: -1; }
        .app { position: relative; z-index: 1; display: flex; min-height: 100vh; }
        .sidebar { width: 260px; flex-shrink: 0; background: #fff; border-right: 1px solid #e5e7eb; padding: 1.5rem 0; display: flex; flex-direction: column; }
        .sidebar-brand { padding: 0 1.25rem 1.5rem; border-bottom: 1px solid #e5e7eb; margin-bottom: 1rem; }
        .sidebar-brand img { height: 48px; width: auto; object-fit: contain; }
        .sidebar-nav { flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 0.75rem; padding: 0.7rem 1.25rem; color: #4b5563; text-decoration: none; font-size: 0.9rem; transition: background 0.15s, color 0.15s; }
        .sidebar-nav a:hover { background: #f3f4f6; color: #111827; }
        .sidebar-nav a.active { background: rgba(79, 70, 229, 0.08); color: #4f46e5; font-weight: 500; }
        .sidebar-nav a svg { width: 20px; height: 20px; flex-shrink: 0; }
        .sidebar-footer { padding: 1rem 1.25rem 0; border-top: 1px solid #e5e7eb; }
        .sidebar-logout { display: flex; align-items: center; gap: 0.75rem; padding: 0.7rem 1.25rem; color: #dc2626; text-decoration: none; font-size: 0.9rem; transition: background 0.15s, color 0.15s; }
        .sidebar-logout:hover { background: #fef2f2; color: #b91c1c; }
        .sidebar-logout svg { width: 20px; height: 20px; flex-shrink: 0; }
        .main-content { flex: 1; min-width: 0; padding: 1.5rem 2rem 2rem; overflow-x: hidden; }
        .page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
        .page-title { font-family: 'Playfair Display', serif; font-size: 1.75rem; font-weight: 600; color: #111827; margin: 0; }
        .btn-home { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; border-radius: 10px; background: #fff; color: #4b5563; font-size: 0.875rem; text-decoration: none; border: 1px solid #e5e7eb; transition: background 0.15s, color 0.15s; }
        .btn-home:hover { background: #f3f4f6; color: #111827; }
        .btn-home svg { width: 18px; height: 18px; }
        .form-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 1.75rem 2rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .form-card h3 { font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem; }
        .field { margin-bottom: 1rem; }
        .field label { display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.35rem; }
        .field label .note { font-weight: 400; color: #6b7280; }
        .field input, .field select, .field textarea { width: 100%; border-radius: 10px; border: 1px solid #e5e7eb; padding: 0.65rem 0.85rem; font-size: 0.9rem; outline: none; background: #fff; transition: border-color 0.15s; }
        .field input:focus, .field select:focus, .field textarea:focus { border-color: #4f46e5; }
        .field input[readonly] { background: #f9fafb; color: #6b7280; cursor: not-allowed; }
        .info-banner { padding: 0.85rem 1rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; margin-bottom: 1.5rem; font-size: 0.9rem; color: #1e40af; }
        .info-banner a { color: #1e40af; font-weight: 500; text-decoration: none; }
        .info-banner a:hover { text-decoration: underline; }
        .field textarea { min-height: 80px; resize: vertical; }
        .field-row { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; }
        .field-block { display: none; }
        .field-block.active { display: block; }
        .hint { font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem; }
        .btn-submit { display: inline-flex; align-items: center; justify-content: center; padding: 0.75rem 1.75rem; border-radius: 999px; background: #0f1419; color: #f9fafb; font-size: 0.9rem; font-weight: 600; border: none; cursor: pointer; transition: transform 0.1s, box-shadow 0.15s; margin-top: 0.5rem; }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(15,20,25,0.3); }
        .alert { padding: 0.75rem 1rem; border-radius: 10px; margin-bottom: 1rem; font-size: 0.9rem; }
        .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .page-footer { text-align: right; padding: 1rem 0; margin-top: 2rem; font-size: 0.8rem; color: #9ca3af; }
        @media (max-width: 768px) {
            .app { flex-direction: column; }
            .sidebar { width: 100%; flex-direction: row; flex-wrap: wrap; padding: 0.75rem 1rem; }
            .sidebar-brand { display: none; }
            .sidebar-nav { display: flex; flex-wrap: wrap; gap: 0.25rem; flex: 1; }
            .sidebar-nav a { padding: 0.5rem 0.75rem; }
            .sidebar-footer { border-top: none; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="page-bg"></div>
    <div class="page-overlay"></div>
    <div class="app">
        <aside class="sidebar">
            <div class="sidebar-brand"><img src="../public/official-logo.png" alt="RCMP UniFa"></div>
            <nav class="sidebar-nav">
                <a href="application.php" class="active"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Application form</a>
                <a href="appInformation.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Application info</a>
                <a href="history.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>View history</a>
                <a href="profile.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>Profile</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="sidebar-logout"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>Logout</a>
            </div>
        </aside>

        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Fund Application</h1>
                <a href="dashboard.php" class="btn-home"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>Home</a>
            </div>
            <p class="info-banner">Refer to <a href="appInformation.php">Application info</a> for further information about the application.</p>
            <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

            <form method="post" action="" enctype="multipart/form-data" id="fundForm">
                <div class="form-card">
                    <h3>1. Category</h3>
                    <div class="field">
                        <label for="category">Select category</label>
                        <select id="category" name="category" required>
                            <option value="">— Select —</option>
                            <option value="bereavement">Bereavement (Khairat)</option>
                            <option value="illness">Illness &amp; Injuries</option>
                            <option value="emergency">Emergency</option>
                        </select>
                    </div>
                </div>

                <div class="form-card" id="cardSubtype" style="display:none">
                    <h3>2. Type</h3>
                    <div class="field">
                        <label for="subtype">Select type</label>
                        <select id="subtype" name="subtype">
                            <option value="">— Select —</option>
                        </select>
                    </div>
                </div>

                <div class="form-card" id="cardFields" style="display:none">
                    <h3>3. Details &amp; documents</h3>

                    <div id="block-bereavement_student" class="field-block">
                        <p class="hint" style="margin-bottom:1rem">Student — RM 500. Provide bank details and death certificate.</p>
                        <div class="field"><label>Death certificate <span class="note">(upload, required)</span></label><input type="file" name="death_certificate" accept=".pdf,.jpg,.jpeg,.png" required></div>
                    </div>
                    <div id="block-bereavement_parent" class="field-block">
                        <p class="hint" style="margin-bottom:1rem">Parent — RM 200.</p>
                        <div class="field"><label>Death certificate <span class="note">(upload, required)</span></label><input type="file" name="death_certificate" accept=".pdf,.jpg,.jpeg,.png" required></div>
                    </div>
                    <div id="block-bereavement_sibling" class="field-block">
                        <p class="hint" style="margin-bottom:1rem">Sibling — RM 100.</p>
                        <div class="field"><label>Death certificate <span class="note">(upload, required)</span></label><input type="file" name="death_certificate" accept=".pdf,.jpg,.jpeg,.png" required></div>
                    </div>

                    <div id="block-illness_outpatient" class="field-block">
                        <p class="hint" style="margin-bottom:1rem">Out-patient — limited to RM 30/semester (max 2 claims/year).</p>
                        <div class="field"><label>Clinic name <span class="note">(required)</span></label><input type="text" name="clinic_name" placeholder="Clinic name" required></div>
                        <div class="field"><label>Reason for visit <span class="note">(required)</span></label><input type="text" name="reason_visit" placeholder="Reason" required></div>
                        <div class="field"><label>Date &amp; time of visit <span class="note">(required)</span></label><input type="datetime-local" name="visit_datetime" required></div>
                        <div class="field"><label>Clinic receipt <span class="note">(upload, required)</span></label><input type="file" name="receipt_clinic" accept=".pdf,.jpg,.jpeg,.png" required></div>
                    </div>
                    <div id="block-illness_inpatient" class="field-block">
                        <p class="hint" style="margin-bottom:1rem">In-patient — only if hospitalization exceeds insurance (annual limit RM20,000). Up to RM 1,000; above requires committee approval.</p>
                        <div class="field"><label>Reason for visit <span class="note">(required)</span></label><input type="text" name="reason_visit" placeholder="Reason" required></div>
                        <div class="field-row">
                            <div class="field"><label>Check-in date <span class="note">(required)</span></label><input type="date" name="checkin_date" required></div>
                            <div class="field"><label>Check-out date <span class="note">(required)</span></label><input type="date" name="checkout_date" required></div>
                        </div>
                        <div class="field"><label>Report / Discharge note / Hospital bill <span class="note">(upload, required)</span></label><input type="file" name="documents" accept=".pdf,.jpg,.jpeg,.png" required></div>
                    </div>
                    <div id="block-illness_injuries" class="field-block">
                        <p class="hint" style="margin-bottom:1rem">Injuries — support equipment up to RM 200.</p>
                        <div class="field"><label>Hospital report &amp; receipt <span class="note">(upload, required)</span></label><input type="file" name="documents" accept=".pdf,.jpg,.jpeg,.png" required></div>
                    </div>

                    <div id="block-emergency_critical" class="field-block">
                        <p class="hint" style="margin-bottom:1rem">Critical illness — initial diagnosis, up to RM 200 per claim.</p>
                        <div class="field"><label>Supporting document <span class="note">(upload, required)</span></label><input type="file" name="supporting_doc" accept=".pdf,.jpg,.jpeg,.png" required></div>
                    </div>
                    <div id="block-emergency_natural" class="field-block">
                        <p class="hint" style="margin-bottom:1rem">Natural disaster — limit RM 200. Include certified evidence.</p>
                        <div class="field"><label>Case description <span class="note">(required)</span></label><input type="text" name="case_description" placeholder="Brief description" required></div>
                        <div class="field"><label>Supporting document (e.g. police report, photos) <span class="note">(required)</span></label><input type="file" name="supporting_doc" accept=".pdf,.jpg,.jpeg,.png" required></div>
                    </div>
                    <div id="block-emergency_others" class="field-block">
                        <p class="hint" style="margin-bottom:1rem">Others — subject to SWF Campus committee approval.</p>
                        <div class="field"><label>Case description <span class="note">(required)</span></label><input type="text" name="case_description" placeholder="Brief description" required></div>
                        <div class="field"><label>Supporting document <span class="note">(upload, required)</span></label><input type="file" name="supporting_doc" accept=".pdf,.jpg,.jpeg,.png" required></div>
                    </div>

                    <div id="commonBank" class="field-block">
                        <hr style="margin:1.25rem 0;border:0;border-top:1px solid #e5e7eb">
                        <div class="field"><label for="bank_name">Bank name <span class="note">(from Profile)</span></label><input type="text" id="bank_name" name="bank_name" value="<?php echo htmlspecialchars($userBank['bank_name']); ?>" placeholder="e.g. Maybank, CIMB" readonly></div>
                        <div class="field"><label for="bank_account">Bank account number <span class="note">(from Profile)</span></label><input type="text" id="bank_account" name="bank_account" value="<?php echo htmlspecialchars($userBank['bank_account']); ?>" placeholder="Account number" readonly></div>
                        <div class="field" id="wrapAmount" style="display:none"><label for="amount_applied">Total amount applied (RM)</label><input type="number" id="amount_applied" name="amount_applied" step="0.01" min="0" placeholder="0.00"></div>
                    </div>

                    <button type="submit" class="btn-submit">Submit application</button>
                </div>
            </form>
            <footer class="page-footer">© University Kuala Lumpur Royal College of Medicine Perak</footer>
        </div>
    </div>

    <script>
    var subtypes = {
        bereavement: [
            { value: 'student', label: 'Student (RM 500)' },
            { value: 'parent', label: 'Parent (RM 200)' },
            { value: 'sibling', label: 'Sibling (RM 100)' }
        ],
        illness: [
            { value: 'outpatient', label: 'Out-patient (RM 30/semester, max 2 claims/year)' },
            { value: 'inpatient', label: 'In-patient (up to RM 1,000)' },
            { value: 'injuries', label: 'Injuries (equipment up to RM 200)' }
        ],
        emergency: [
            { value: 'critical', label: 'Critical illness (up to RM 200)' },
            { value: 'natural', label: 'Natural disaster (RM 200)' },
            { value: 'others', label: 'Others (committee approval)' }
        ]
    };

    var categoryEl = document.getElementById('category');
    var subtypeEl = document.getElementById('subtype');
    var cardSubtype = document.getElementById('cardSubtype');
    var cardFields = document.getElementById('cardFields');

    function setInputsDisabled(block, disabled) {
        if (!block) return;
        block.querySelectorAll('input, select, textarea').forEach(function(inp) { inp.disabled = disabled; });
    }

    function showSubtypes() {
        var cat = categoryEl.value;
        subtypeEl.innerHTML = '<option value="">— Select —</option>';
        cardSubtype.style.display = 'none';
        cardFields.style.display = 'none';
        document.querySelectorAll('.field-block').forEach(function(b) {
            b.classList.remove('active');
            setInputsDisabled(b, true);
        }); 
        if (!cat) return;
        var list = subtypes[cat];
        if (list) {
            list.forEach(function(o) {
                var opt = document.createElement('option');
                opt.value = o.value;
                opt.textContent = o.label;
                subtypeEl.appendChild(opt);
            });
            cardSubtype.style.display = 'block';
        }
    }

    function showBlock() {
        var cat = categoryEl.value;
        var sub = subtypeEl.value;
        cardFields.style.display = 'none';
        document.querySelectorAll('.field-block').forEach(function(b) {
            b.classList.remove('active');
            setInputsDisabled(b, true);
        });
        document.getElementById('wrapAmount').style.display = 'none';
        if (!cat || !sub) return;
        var id = 'block-' + cat + '_' + sub;
        var block = document.getElementById(id);
        if (block) {
            block.classList.add('active');
            setInputsDisabled(block, false);
            var commonBank = document.getElementById('commonBank');
            commonBank.classList.add('active');
            setInputsDisabled(commonBank, false);
            if (cat === 'illness' || cat === 'emergency') document.getElementById('wrapAmount').style.display = 'block';
            cardFields.style.display = 'block';
        }
    }

    categoryEl.addEventListener('change', function() {
        showSubtypes();
        showBlock();
    });
    subtypeEl.addEventListener('change', showBlock);
    </script>
</body>
</html>
