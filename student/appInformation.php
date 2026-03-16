<?php
session_start();
require_once __DIR__ . '/../database/database.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'student') {
    header('Location: ../auth/login.php');
    exit;
}

$announcements = [];
try {
    $stmt = $pdo->query("SELECT id FROM announcements WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Information — RCMP UniFa</title>
    <link rel="icon" href="../public/title-white.png" type="image/png">
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
        .nav-badge { margin-left: auto; font-size: 0.7rem; font-weight: 700; background: #6366f1; color: #fff; border-radius: 999px; padding: 0.1rem 0.45rem; }
        .main-content { flex: 1; min-width: 0; padding: 1.5rem 2rem 2rem; overflow-x: hidden; }
        .page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
        .page-title { font-family: 'Playfair Display', serif; font-size: 1.75rem; font-weight: 600; color: #111827; margin: 0; }
        .btn-home { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; border-radius: 10px; background: #fff; color: #4b5563; font-size: 0.875rem; text-decoration: none; border: 1px solid #e5e7eb; transition: background 0.15s, color 0.15s; }
        .btn-home:hover { background: #f3f4f6; color: #111827; }
        .btn-home svg { width: 18px; height: 18px; }
        .intro { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 1.5rem 1.75rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); font-size: 0.95rem; color: #4b5563; line-height: 1.6; }
        .tabs { display: inline-flex; gap: 0.35rem; padding: 0.3rem; border-radius: 999px; background: #e5e7eb; margin-bottom: 1.25rem; }
        .tabs button { border: none; background: transparent; padding: 0.3rem 0.9rem; border-radius: 999px; font-size: 0.8rem; color: #4b5563; cursor: pointer; }
        .tabs button.active { background: #111827; color: #fff; }
        .info-grid { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr); gap: 1.25rem; align-items: flex-start; }
        .info-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 1.5rem 1.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .info-card + .info-card { margin-top: 1.25rem; }
        .info-card h2 { font-family: 'Playfair Display', serif; font-size: 1.3rem; font-weight: 600; color: #111827; margin-bottom: 0.5rem; }
        .info-card p { font-size: 0.9rem; color: #4b5563; line-height: 1.6; margin-bottom: 0.75rem; }
        .info-card h3 { font-size: 0.95rem; font-weight: 600; color: #111827; margin: 0.9rem 0 0.4rem; }
        .info-card ul { margin: 0 0 0 1.25rem; font-size: 0.9rem; color: #374151; line-height: 1.7; }
        .info-card li { margin-bottom: 0.3rem; }
        .info-card .limit { display: inline-block; background: #eef2ff; color: #111827; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600; margin-right: 0.35rem; }
        .checklist { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 1.25rem 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .checklist h3 { font-size: 0.95rem; font-weight: 600; margin-bottom: 0.5rem; color: #111827; }
        .checklist ul { list-style: none; margin-left: 0; font-size: 0.85rem; color: #374151; }
        .checklist li { margin-bottom: 0.3rem; display: flex; gap: 0.4rem; }
        .checklist li span.bullet { width: 0.5rem; height: 0.5rem; border-radius: 999px; background: #4f46e5; margin-top: 0.4rem; flex-shrink: 0; }
        .cta-box { margin-top: 1.5rem; }
        .cta-box a { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.6rem 1.25rem; border-radius: 999px; background: #0f1419; color: #f9fafb; font-size: 0.875rem; font-weight: 600; text-decoration: none; transition: transform 0.1s, box-shadow 0.15s; }
        .cta-box a:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(15,20,25,0.3); }
        .page-footer { text-align: right; padding: 1rem 0; margin-top: 2rem; font-size: 0.8rem; color: #9ca3af; }
        @media (max-width: 768px) {
            .app { flex-direction: column; }
            .sidebar { width: 100%; flex-direction: row; flex-wrap: wrap; padding: 0.75rem 1rem; }
            .sidebar-brand { display: none; }
            .sidebar-nav { display: flex; flex-wrap: wrap; gap: 0.25rem; flex: 1; }
            .sidebar-nav a { padding: 0.5rem 0.75rem; }
            .sidebar-footer { border-top: none; padding: 0; }
            .info-grid { grid-template-columns: minmax(0, 1fr); }
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
                <a href="dashboard.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>Dashboard</a>
                <a href="appInformation.php" class="active"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Application info</a>
                <a href="application.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Application form</a>
                <a href="history.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>View history</a>
                <a href="annoucement.php">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                    Announcements
                    <?php if (count($announcements) > 0): ?>
                        <span class="nav-badge"><?php echo count($announcements); ?></span>
                    <?php endif; ?>
                </a>
                <a href="profile.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>Profile</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="sidebar-logout"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>Logout</a>
            </div>
        </aside>

        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Application Information</h1>
                <a href="dashboard.php" class="btn-home"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>Home</a>
            </div>

            <p class="intro">The Student Welfare Fund (SWF) provides financial assistance for eligible UniKL RCMP students. Choose a category below to see what documents and information you must prepare before you start the application form.</p>

            <div class="tabs" id="categoryTabs">
                <button type="button" data-target="tab-bereavement" class="active">Bereavement</button>
                <button type="button" data-target="tab-illness">Illness &amp; Injuries</button>
                <button type="button" data-target="tab-emergency">Emergency</button>
            </div>

            <div class="info-grid">
                <div>
                    <div class="info-card" id="tab-bereavement">
                        <h2>Bereavement (Khairat)</h2>
                        <p>Financial support when a student, parent, or sibling passes away.</p>
                        <h3>Sub‑categories &amp; limits</h3>
                        <ul>
                            <li><span class="limit">RM 500</span>Death of <strong>student</strong></li>
                            <li><span class="limit">RM 200</span>Death of <strong>parent</strong></li>
                            <li><span class="limit">RM 100</span>Death of <strong>sibling</strong></li>
                        </ul>
                        <h3>What you must prepare</h3>
                        <ul>
                            <li>Bank name and bank account number (from your Profile).</li>
                            <li>Official death certificate (scan or clear photo, PDF/JPG/PNG).</li>
                        </ul>
                    </div>

                    <div class="info-card" id="tab-illness" style="display:none">
                        <h2>Illness &amp; Injuries</h2>
                        <p>Support for medical and injury‑related costs within the SWF limits.</p>

                        <h3>Out‑patient</h3>
                        <p><span class="limit">RM 30 / semester</span>Maximum 2 claims per year.</p>
                        <h3>In‑patient</h3>
                        <p><span class="limit">Up to RM 1,000</span>Only if total hospitalization cost <strong>exceeds</strong> the insurance annual limit (RM 20,000). Above RM 1,000 requires committee approval.</p>
                        <h3>Injuries (support equipment)</h3>
                        <p><span class="limit">Up to RM 200</span>For injury support equipment (e.g. crutches, brace).</p>

                        <h3>What you must prepare</h3>
                        <ul>
                            <li>Clinic / hospital name.</li>
                            <li>Reason for visit / diagnosis.</li>
                            <li>Date and time of clinic visit or admission / discharge dates.</li>
                            <li>Amount you are applying for (RM).</li>
                            <li>Bank name and bank account number (from your Profile).</li>
                            <li>Receipts and documents (PDF/JPG/PNG):
                                <ul>
                                    <li>Out‑patient: clinic receipt.</li>
                                    <li>In‑patient: report / discharge note / hospital bill.</li>
                                    <li>Injuries: hospital report and receipt of equipment purchase.</li>
                                </ul>
                            </li>
                        </ul>
                    </div>

                    <div class="info-card" id="tab-emergency" style="display:none">
                        <h2>Emergency</h2>
                        <p>One‑off support for critical illness, natural disaster, or other emergencies.</p>

                        <h3>Critical illness</h3>
                        <p><span class="limit">Up to RM 200 / claim</span>For initial diagnosis, based on supporting medical documents.</p>

                        <h3>Natural disaster</h3>
                        <p><span class="limit">RM 200</span>For events such as flood, fire, etc. Certified evidence is required.</p>

                        <h3>Others</h3>
                        <p>Other emergency situations not covered above, <strong>subject to SWF Campus committee approval</strong>.</p>

                        <h3>What you must prepare</h3>
                        <ul>
                            <li>Short case description (what happened, when, where).</li>
                            <li>Amount you are applying for (RM).</li>
                            <li>Bank name and bank account number (from your Profile).</li>
                            <li>Supporting documents (PDF/JPG/PNG), for example:
                                <ul>
                                    <li>Medical report / memo (for critical illness).</li>
                                    <li>Police report, photos, official letters (for natural disaster).</li>
                                    <li>Any other evidence relevant to your case.</li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>

                <aside class="checklist">
                    <h3>Quick checklist before you apply</h3>
                    <ul>
                        <li><span class="bullet"></span><span>Your bank name and bank account number in <strong>Profile</strong> are correct.</span></li>
                        <li><span class="bullet"></span><span>You know which <strong>category &amp; sub‑type</strong> matches your situation.</span></li>
                        <li><span class="bullet"></span><span>You have clear <strong>scans/photos</strong> of all required documents (PDF/JPG/PNG).</span></li>
                        <li><span class="bullet"></span><span>You understand the <strong>maximum amount</strong> you can claim for your sub‑category.</span></li>
                        <li><span class="bullet"></span><span>You are ready to write a short <strong>description / reason</strong> for your application.</span></li>
                    </ul>
                </aside>
            </div>

            <div class="cta-box">
                <a href="application.php">Apply for fund →</a>
            </div>
            <footer class="page-footer">© University Kuala Lumpur Royal College of Medicine Perak</footer>
        </div>
    </div>
</body>
<script>
(function () {
    var tabs = document.querySelectorAll('#categoryTabs button');
    var panels = {
        'tab-bereavement': document.getElementById('tab-bereavement'),
        'tab-illness': document.getElementById('tab-illness'),
        'tab-emergency': document.getElementById('tab-emergency')
    };
    tabs.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = btn.getAttribute('data-target');
            tabs.forEach(function (b) { b.classList.toggle('active', b === btn); });
            Object.keys(panels).forEach(function (key) {
                if (panels[key]) {
                    panels[key].style.display = key === target ? '' : 'none';
                }
            });
        });
    });
})();
</script>
</html>
