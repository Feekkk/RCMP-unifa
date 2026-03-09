<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RCMP UniFa — Financial Aid System</title>
    <link rel="icon" href="public/logo-unikl.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            background: #0f1419;
            color: #fff;
            overflow-x: hidden;
        }
        .home-hero {
            position: relative;
            overflow: hidden;
            isolation: isolate;
            background: linear-gradient(135deg, #0f1419 0%, #1a2332 40%, #16202a 100%);
        }
        .home-hero .photo-bg {
            position: absolute;
            inset: 0;
            background: url("public/bgm.png") center center / cover no-repeat;
            filter: blur(7px);
            transform: scale(1.04);
            z-index: -3;
        }
        .home-hero .bg-overlay {
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.85));
            pointer-events: none;
            z-index: -2;
        }
        .home-hero .warm-gradient {
            position: absolute;
            top: 0;
            right: 0;
            width: 55%;
            height: 100%;
            background: linear-gradient(90deg, transparent 0%, rgba(120, 80, 60, 0.35) 100%);
            pointer-events: none;
            z-index: -1;
        }
        .container {
            position: relative;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 4rem 3rem;
        }
        .home-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            row-gap: 4rem;
        }
        .page-container {
            display: flex;
            flex-direction: column;
            row-gap: 4rem;
            padding-top: 3rem;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            font-size: 1.25rem;
            font-weight: 600;
            letter-spacing: 0.02em;
        }
        .logo-mark {
            height: 70px;
            width: auto;
        }
        nav {
            position: relative;
        }
        .nav-trigger {
            background: none;
            border: none;
            color: #fff;
            font-family: inherit;
            font-size: 0.95rem;
            cursor: pointer;
            padding: 0.5rem 0;
            letter-spacing: 0.02em;
        }
        .nav-trigger:hover { opacity: 0.85; }
        .nav-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.5rem;
            min-width: 200px;
            background: rgba(20, 28, 38, 0.95);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            padding: 0.5rem 0;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px);
            transition: opacity 0.2s, transform 0.2s, visibility 0.2s;
        }
        nav:hover .nav-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .nav-dropdown a {
            display: block;
            color: #e8e8e8;
            text-decoration: none;
            padding: 0.6rem 1.25rem;
            font-size: 0.9rem;
            transition: background 0.15s, color 0.15s;
        }
        .nav-dropdown a:hover {
            background: rgba(255,255,255,0.06);
            color: #fff;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .auth-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
        }
        .auth-link {
            border-radius: 999px;
            padding: 0.45rem 1.1rem;
            text-decoration: none;
            font-weight: 500;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            font-size: 0.78rem;
        }
        .auth-link--ghost {
            color: rgba(255,255,255,0.8);
            border: 1px solid rgba(255,255,255,0.35);
        }
        .auth-link--solid {
            color: #0f1419;
            background: #ffffff;
            border: 1px solid #ffffff;
        }
        .auth-link--ghost:hover {
            background: rgba(255,255,255,0.12);
        }
        .auth-link--solid:hover {
            background: transparent;
            color: #ffffff;
        }
        main {
            flex: 1;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 3rem;
            padding-top: 3rem;
            padding-bottom: 1rem;
        }
        .hero-left {
            flex-shrink: 0;
        }
        .hero-title {
            font-size: clamp(3.5rem, 10vw, 7rem);
            font-weight: 700;
            line-height: 0.95;
            letter-spacing: -0.02em;
            text-transform: uppercase;
        }
        .hero-tagline {
            margin-top: 0.75rem;
            font-size: 1.1rem;
            font-weight: 500;
            color: rgba(255,255,255,0.9);
            letter-spacing: 0.01em;
        }
        .hero-right {
            max-width: 380px;
            text-align: right;
        }
        .hero-details {
            font-size: 1rem;
            line-height: 1.6;
            color: rgba(255,255,255,0.88);
            margin-bottom: 2rem;
        }
        .cta {
            display: inline-block;
            padding: 0.85rem 2rem;
            border: 1px solid rgba(255,255,255,0.9);
            border-radius: 999px;
            color: #0f1419;
            background: #fff;
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            letter-spacing: 0.02em;
            transition: background 0.2s, color 0.2s, border-color 0.2s;
        }
        .cta:hover {
            background: transparent;
            color: #fff;
        }
        .section-swf {
            margin-top: 0;
            padding: 3rem 3rem;
            border-radius: 1.5rem;
            background: rgba(255, 255, 255, 0.96);
            color: #0f1419;
            box-shadow: 0 18px 55px rgba(0, 0, 0, 0.35);
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(0, 1.4fr);
            gap: 3rem;
            align-items: flex-start;
        }
        .section-swf .section-eyebrow {
            color: rgba(15, 20, 25, 0.6);
        }
        .section-swf .section-title {
            color: #0f1419;
        }
        .section-swf .section-body {
            color: rgba(15, 20, 25, 0.85);
        }
        .timeline {
            margin-top: 1.25rem;
            display: grid;
            gap: 1rem;
        }
        .timeline-item {
            display: grid;
            grid-template-columns: 84px 1fr;
            gap: 1.1rem;
            padding: 1rem 1.1rem;
            border-radius: 1.1rem;
            border: 1px solid rgba(15, 20, 25, 0.10);
            background: rgba(15, 20, 25, 0.03);
        }
        .timeline-year {
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-size: 0.85rem;
            color: rgba(15, 20, 25, 0.75);
        }
        .timeline-title {
            font-weight: 650;
            letter-spacing: 0.01em;
            margin-bottom: 0.25rem;
        }
        .timeline-copy {
            font-size: 0.95rem;
            line-height: 1.65;
            color: rgba(15, 20, 25, 0.82);
        }
        .objective-cards {
            margin-top: 1rem;
            display: grid;
            gap: 0.9rem;
        }
        .objective-card {
            padding: 1rem 1.05rem;
            border-radius: 1.1rem;
            border: 1px solid rgba(15, 20, 25, 0.12);
            background: rgba(15, 20, 25, 0.03);
            display: grid;
            grid-template-columns: 42px 1fr;
            gap: 0.9rem;
        }
        .objective-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.92);
            background: linear-gradient(135deg, #0f1419 0%, #2d4770 100%);
            box-shadow: 0 10px 26px rgba(15, 20, 25, 0.22);
            user-select: none;
        }
        .objective-title {
            font-weight: 650;
            letter-spacing: 0.01em;
            margin-bottom: 0.2rem;
            color: #0f1419;
        }
        .objective-copy {
            font-size: 0.95rem;
            line-height: 1.65;
            color: rgba(15, 20, 25, 0.82);
        }
        .section-eyebrow {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: rgba(255,255,255,0.6);
            margin-bottom: 0.75rem;
        }
        .section-title {
            font-size: 1.8rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            margin-bottom: 1rem;
        }
        .section-body {
            font-size: 0.98rem;
            line-height: 1.7;
            color: rgba(255,255,255,0.9);
        }
        .section-body + .section-body {
            margin-top: 0.9rem;
        }
        .section-side-heading {
            font-size: 0.9rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.65);
            margin-bottom: 0.75rem;
        }
        .section-swf .section-side-heading {
            color: rgba(15, 20, 25, 0.65);
        }
        .section-list {
            list-style: none;
            font-size: 0.95rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 1.75rem;
        }
        .section-list li + li {
            margin-top: 0.4rem;
        }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1.1rem;
        }
        .stat-card {
            padding: 0.9rem 1rem;
            border-radius: 0.9rem;
            background: radial-gradient(circle at top left, rgba(255,255,255,0.12), rgba(10,14,20,0.9));
            border: 1px solid rgba(255,255,255,0.12);
        }
        .section-swf .stat-card {
            background: linear-gradient(145deg, rgba(15, 20, 25, 0.05), rgba(15, 20, 25, 0.02));
            border: 1px solid rgba(15, 20, 25, 0.12);
        }
        .section-swf .stat-value {
            color: #0f1419;
        }
        .section-swf .stat-label {
            color: rgba(15, 20, 25, 0.65);
        }
        .stat-value {
            font-size: 1.3rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            margin-bottom: 0.15rem;
        }
        .stat-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: rgba(255,255,255,0.75);
        }
        .section-funding {
            margin-top: 0;
            padding-top: 3rem;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        .funding-header {
            max-width: 520px;
            margin-bottom: 2rem;
        }
        .funding-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1.4rem;
        }
        .funding-card {
            padding: 1.1rem 1.2rem;
            border-radius: 1rem;
            background: linear-gradient(150deg, rgba(255,255,255,0.10), rgba(10,14,20,0.9));
            border: 1px solid rgba(255,255,255,0.12);
        }
        .funding-title {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.35rem;
        }
        .funding-body {
            font-size: 0.9rem;
            line-height: 1.6;
            color: rgba(255,255,255,0.9);
        }
        .section-programs {
            padding-top: 3rem;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        .programs-header {
            max-width: 520px;
            margin-bottom: 2rem;
        }
        .program-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1.6rem;
        }
        .committee-tree-wrap {
            position: relative;
            border-radius: 1.4rem;
            background: radial-gradient(circle at top left, rgba(255,255,255,0.12), rgba(10,14,20,0.92));
            border: 1px solid rgba(255,255,255,0.14);
            overflow: hidden;
        }
        .committee-tree-scroll {
            max-height: 520px;
            overflow-y: auto;
            padding: 1.3rem 1.25rem 1.7rem;
            scrollbar-gutter: stable;
        }
        .committee-tree-scroll::-webkit-scrollbar { width: 10px; }
        .committee-tree-scroll::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.18);
            border-radius: 999px;
            border: 2px solid rgba(10,14,20,0.9);
        }
        .committee-tree-scroll::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.06);
        }
        .committee-scroll-fade {
            pointer-events: none;
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 72px;
            background: linear-gradient(to bottom, rgba(10,14,20,0), rgba(10,14,20,0.88));
        }
        .committee-tree {
            display: grid;
            gap: 1.1rem;
        }
        .committee-level {
            display: grid;
            gap: 0.85rem;
        }
        .committee-level--center {
            justify-items: center;
        }
        .committee-level--row {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            align-items: stretch;
        }
        .committee-level--row2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .committee-connector {
            position: relative;
            height: 26px;
        }
        .committee-connector::before {
            content: "";
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 2px;
            transform: translateX(-50%);
            background: rgba(255,255,255,0.18);
        }
        .committee-node {
            position: relative;
            padding: 1rem 1.05rem 0.95rem;
            border-radius: 1.2rem;
            border: 1px solid rgba(255,255,255,0.16);
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            display: grid;
            grid-template-columns: 44px 1fr;
            gap: 0.9rem;
            align-items: start;
        }
        .committee-node::after {
            content: "";
            position: absolute;
            inset: -1px;
            border-radius: 1.2rem;
            background: radial-gradient(circle at top left, rgba(255,255,255,0.12), transparent 60%);
            pointer-events: none;
            opacity: 0.8;
        }
        .committee-node-badge {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            font-weight: 750;
            letter-spacing: 0.02em;
            color: rgba(255, 255, 255, 0.92);
            background: linear-gradient(135deg, #ffffff 0%, rgba(255,255,255,0.15) 55%, rgba(255,255,255,0.06) 100%);
            color: rgba(15, 20, 25, 0.92);
            box-shadow: 0 10px 26px rgba(0,0,0,0.35);
        }
        .committee-node-role {
            font-size: 1rem;
            font-weight: 650;
            letter-spacing: 0.01em;
            margin-bottom: 0.25rem;
            color: rgba(255,255,255,0.95);
        }
        .committee-node-note {
            font-size: 0.9rem;
            line-height: 1.6;
            color: rgba(255,255,255,0.78);
        }
        .committee-node--primary {
            background: linear-gradient(145deg, rgba(255,255,255,0.10), rgba(255,255,255,0.05));
            border-color: rgba(255,255,255,0.22);
        }
        .committee-node--primary .committee-node-badge {
            background: linear-gradient(135deg, #ffffff 0%, #b9d2ff 65%, rgba(255,255,255,0.20) 100%);
        }
        @media (max-width: 900px) {
            .committee-level--row {
                grid-template-columns: minmax(0, 1fr);
            }
            .committee-level--row2 {
                grid-template-columns: minmax(0, 1fr);
            }
        }
        .program-card {
            padding: 1.2rem 1.3rem 1.1rem;
            border-radius: 1rem;
            background: radial-gradient(circle at top left, rgba(255,255,255,0.13), rgba(10,14,20,0.9));
            border: 1px solid rgba(255,255,255,0.14);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .program-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
        }
        .program-body {
            font-size: 0.9rem;
            line-height: 1.6;
            color: rgba(255,255,255,0.9);
            margin-bottom: 0.9rem;
        }
        .program-link {
            align-self: flex-start;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .program-link span {
            font-size: 0.95em;
        }
        .program-link:hover {
            color: #ffffff;
        }
        footer {
            margin-top: 3rem;
            padding-top: 1.75rem;
            border-top: 1px solid rgba(255,255,255,0.08);
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.7);
        }
        .footer-logo {
            display: block;
            height: 40px;
            opacity: 0.9;
        }
        .footer-meta {
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        .footer-info {
            text-align: center;
            line-height: 1.6;
        }
        @media (max-width: 1100px) {
            .funding-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .program-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .committee-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 900px) {
            .container { padding: 1.5rem 1.5rem 2rem; }
            header { flex-direction: row; gap: 1rem; }
            .header-right { gap: 1rem; }
            .auth-actions { display: none; }
            main { flex-direction: column; align-items: flex-start; }
            .hero-right { text-align: left; max-width: 100%; }
            .section-swf {
                grid-template-columns: minmax(0, 1fr);
                gap: 2rem;
                padding: 2rem 1.5rem;
            }
            .stat-grid {
                grid-template-columns: repeat(3, minmax(0, 90px));
            }
            .section-funding {
                margin-top: 3rem;
            }
            .funding-grid {
                grid-template-columns: minmax(0, 1fr);
            }
            .program-grid {
                grid-template-columns: minmax(0, 1fr);
            }
            .committee-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>
</head>
<body>
    <section class="home-hero" id="home">
        <div class="photo-bg"></div>
        <div class="bg-overlay"></div>
        <div class="warm-gradient"></div>
        <div class="container home-container">
            <header>
                <span class="logo">
                    <img src="public/official-logo.png" alt="UniKL RCMP logo" class="logo-mark">
                </span>
                <div class="header-right">
                    <div class="auth-actions">
                        <a href="auth/login.php" class="auth-link auth-link--ghost">Login</a>
                        <a href="auth/register.php" class="auth-link auth-link--solid">Register</a>
                    </div>
                </div>
            </header>
            <main>
                <div class="hero-left">
                    <h1 class="hero-title">RCMP UniFa</h1>
                    <p class="hero-tagline">UniKL Financial Aid System — Supporting Student Success</p>
                </div>
                <div class="hero-right">
                    <p class="hero-details">Empowering UniKL students to achieve their academic dreams through comprehensive financial support.</p>
                    <a href="auth/login.php" class="cta">Get Started</a>
                </div>
            </main>
        </div>
    </section>

    <div class="container page-container">
        <section class="section-swf">
            <div>
                <div class="section-eyebrow">History</div>
                <h2 class="section-title">Student Welfare Fund (SWF)</h2>
                <p class="section-body">A quick timeline of how the fund began, evolved, and is managed today.</p>
                <div class="timeline" role="list">
                    <div class="timeline-item" role="listitem">
                        <div class="timeline-year">2005</div>
                        <div>
                            <div class="timeline-title">Established as TKS</div>
                            <div class="timeline-copy">Tabung Kebajikan Siswa (TKS) was established on 30 September 2005, endorsed and approved by UniKL management.</div>
                        </div>
                    </div>
                    <div class="timeline-item" role="listitem">
                        <div class="timeline-year">2017</div>
                        <div>
                            <div class="timeline-title">Rebranded to SWF</div>
                            <div class="timeline-copy">TKS was rebranded to Student Welfare Fund (SWF) on 12 December 2017 to strengthen welfare support for students.</div>
                        </div>
                    </div>
                    <div class="timeline-item" role="listitem">
                        <div class="timeline-year">2018</div>
                        <div>
                            <div class="timeline-title">Operational governance</div>
                            <div class="timeline-copy">Approved on TMM 30 January 2018 (TMM No.125 (2/2018)), with operations managed by the Campus Lifestyle Division and Campus Lifestyle Section.</div>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <div class="section-side-heading">SWF UniKLRCMP Objectives</div>
                <p class="section-body">Focused support designed to protect student wellbeing and help you stay on track academically.</p>
                <div class="objective-cards">
                    <div class="objective-card">
                        <div class="objective-icon">1</div>
                        <div>
                            <div class="objective-title">Emergency &amp; crisis support</div>
                            <div class="objective-copy">Fast assistance for urgent situations that impact safety, accommodation, food, or immediate necessities.</div>
                        </div>
                    </div>
                    <div class="objective-card">
                        <div class="objective-icon">2</div>
                        <div>
                            <div class="objective-title">Medical &amp; injury assistance</div>
                            <div class="objective-copy">Support related to medical conditions or injuries, including cases that require quick intervention or recovery time.</div>
                        </div>
                    </div>
                    <div class="objective-card">
                        <div class="objective-icon">3</div>
                        <div>
                            <div class="objective-title">Bereavement &amp; compassionate aid</div>
                            <div class="objective-copy">Help for students facing bereavement or family hardship, to reduce financial stress during difficult periods.</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section-funding">
            <div class="funding-header">
                <div class="section-eyebrow">Funding</div>
                <h2 class="section-title">Student Contribution SWF</h2>
                <p class="section-body">The fund collection is based on SWF fees collected from registered students.</p>
            </div>
            <div class="funding-grid">
                <article class="funding-card">
                    <h3 class="funding-title">Local student</h3>
                    <p class="funding-body">Fee is <strong>RM30.00</strong> every semester.</p>
                </article>
                <article class="funding-card">
                    <h3 class="funding-title">International student</h3>
                    <p class="funding-body">Fee is <strong>RM50.00</strong> every semester.</p>
                </article>
            </div>
        </section>

        <section class="section-programs">
            <div class="programs-header">
                <div class="section-eyebrow">Committee</div>
                <h2 class="section-title">SWF Campus Committee Members</h2>
                <p class="section-body">Committee structure and membership hierarchy for SWF at campus level.</p>
            </div>
            <div class="committee-tree-wrap">
                <div class="committee-tree-scroll" aria-label="Committee hierarchy">
                    <div class="committee-tree" role="list">
                        <div class="committee-level committee-level--center" role="listitem">
                            <div class="committee-node committee-node--primary">
                                <div class="committee-node-badge">1</div>
                                <div>
                                    <div class="committee-node-role">Head of Campus / Dean</div>
                                    <div class="committee-node-note">Chairperson</div>
                                </div>
                            </div>
                        </div>

                        <div class="committee-connector" aria-hidden="true"></div>

                        <div class="committee-level committee-level--row" role="listitem">
                            <div class="committee-node">
                                <div class="committee-node-badge">2</div>
                                <div>
                                    <div class="committee-node-role">Deputy Dean, SDCL</div>
                                    <div class="committee-node-note">Committee member</div>
                                </div>
                            </div>
                            <div class="committee-node">
                                <div class="committee-node-badge">3</div>
                                <div>
                                    <div class="committee-node-role">Campus Lifestyle Head</div>
                                    <div class="committee-node-note">Committee member</div>
                                </div>
                            </div>
                            <div class="committee-node">
                                <div class="committee-node-badge">4</div>
                                <div>
                                    <div class="committee-node-role">Representative of Finance and Administration Department</div>
                                    <div class="committee-node-note">Committee member</div>
                                </div>
                            </div>
                        </div>

                        <div class="committee-connector" aria-hidden="true"></div>

                        <div class="committee-level committee-level--row2" role="listitem">
                            <div class="committee-node">
                                <div class="committee-node-badge">5</div>
                                <div>
                                    <div class="committee-node-role">Executive, Campus Lifestyle Section or any designated staff</div>
                                    <div class="committee-node-note">Secretariat</div>
                                </div>
                            </div>
                            <div class="committee-node">
                                <div class="committee-node-badge">6</div>
                                <div>
                                    <div class="committee-node-role">President of Student Representative Committee or representative</div>
                                    <div class="committee-node-note">By invitation</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="committee-scroll-fade" aria-hidden="true"></div>
            </div>
        </section>

        <footer>
            <img src="public/rcmp-white.png" alt="UniKL RCMP" class="footer-logo">
            <div class="footer-meta">Universiti Kuala Lumpur &middot; Royal College of Medicine Perak</div>
            <div class="footer-info">
                Email: rcmp@unikl.edu.my &middot; Tel: +60 (0)5-123 4567<br>
                Location: Jalan Greentown, 30450 Ipoh, Perak, Malaysia<br>
                Office Hours: Monday &ndash; Friday, 8:00 AM &ndash; 5:00 PM
            </div>
        </footer>
    </div>
</body>
</html>
