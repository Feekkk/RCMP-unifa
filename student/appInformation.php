<?php
session_start();
require_once __DIR__ . '/../database/database.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'student') {
    header('Location: ../auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Information — RCMP UniFa</title>
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
        .intro { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 1.5rem 1.75rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); font-size: 0.95rem; color: #4b5563; line-height: 1.6; }
        .info-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 1.5rem 1.75rem; margin-bottom: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .info-card h2 { font-family: 'Playfair Display', serif; font-size: 1.25rem; font-weight: 600; color: #111827; margin-bottom: 0.75rem; }
        .info-card p { font-size: 0.9rem; color: #4b5563; line-height: 1.6; margin-bottom: 0.75rem; }
        .info-card ul { margin: 0 0 0 1.25rem; font-size: 0.9rem; color: #374151; line-height: 1.7; }
        .info-card li { margin-bottom: 0.35rem; }
        .info-card .limit { display: inline-block; background: #f3f4f6; color: #111827; padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.85rem; font-weight: 500; margin-right: 0.35rem; }
        .cta-box { margin-top: 1.5rem; }
        .cta-box a { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.6rem 1.25rem; border-radius: 999px; background: #0f1419; color: #f9fafb; font-size: 0.875rem; font-weight: 600; text-decoration: none; transition: transform 0.1s, box-shadow 0.15s; }
        .cta-box a:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(15,20,25,0.3); }
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
                <a href="application.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Application form</a>
                <a href="appInformation.php" class="active"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Application info</a>
                <a href="applications.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>Application details</a>
                <a href="history.php"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>View history</a>
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

            <p class="intro">The Student Welfare Fund (SWF) provides financial assistance for eligible UniKL RCMP students. Below are the four main categories and what you need to know before applying.</p>

            <div class="info-card">
                <h2>1. Bereavement (Khairat)</h2>
                <p>Financial support when a student, parent, or sibling passes away. You will need your bank details and a death certificate.</p>
                <ul>
                    <li><span class="limit">RM 500</span> — Death of <strong>student</strong>: Bank name, bank account number, death certificate (upload).</li>
                    <li><span class="limit">RM 200</span> — Death of <strong>parent</strong>: Bank name, bank account number, death certificate (upload).</li>
                    <li><span class="limit">RM 100</span> — Death of <strong>sibling</strong>: Bank name, bank account number, death certificate (upload).</li>
                </ul>
            </div>

            <div class="info-card">
                <h2>2. Illness &amp; Injuries</h2>
                <p>Support for medical and injury-related costs within the stated limits.</p>
                <ul>
                    <li><span class="limit">Out-patient</span> — Limited to <strong>RM 30 per semester</strong> (max 2 claims per year). You need: clinic name, reason for visit, date &amp; time of visit, amount applied, bank details, and clinic receipt (upload).</li>
                    <li><span class="limit">In-patient</span> — Only if hospitalization cost <strong>exceeds</strong> the stipulated insurance annual limit (RM 20,000 per student). Support <strong>up to RM 1,000</strong>; amounts above RM 1,000 require SWF Campus committee approval. You need: reason for visit, check-in and check-out dates, amount applied, bank details, and report / discharge note / hospital bill (upload).</li>
                    <li><span class="limit">Injuries</span> — Support for <strong>injury support equipment up to RM 200</strong>. You need: amount applied, bank details, and hospital report plus receipt of purchase (upload).</li>
                </ul>
            </div>

            <div class="info-card">
                <h2>3. Emergency</h2>
                <p>One-off support for critical illness, natural disaster, or other emergencies as approved.</p>
                <ul>
                    <li><span class="limit">Critical illness</span> — Initial diagnosis with supporting documents, <strong>up to RM 200 per claim</strong>. You need: amount applied, bank details, supporting document (upload).</li>
                    <li><span class="limit">Natural disaster</span> — <strong>Limit RM 200</strong>. You must provide certified evidence (e.g. police report, photos). You need: case description, amount applied, bank details, supporting document (upload).</li>
                    <li><span class="limit">Others</span> — Emergency fund requests that do not fall under critical illness or natural disaster are <strong>subject to SWF Campus committee approval</strong>. You need: case description, amount applied, bank details, supporting document (upload).</li>
                </ul>
            </div>

            <div class="cta-box">
                <a href="application.php">Apply for fund →</a>
            </div>
        </div>
    </div>
</body>
</html>
