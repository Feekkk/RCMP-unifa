<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RCMP UniFa — Financial Aid System</title>
    <link rel="icon" href="public/rcmp-white.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #06090e;
            --bg-surface: #0a0e14;
            --bg-panel: rgba(15, 20, 28, 0.65);
            --accent: #dcb38a;
            --accent-glow: rgba(220, 179, 138, 0.15);
            --text-primary: #ffffff;
            --text-secondary: #94a3b8;
            --border-light: rgba(255, 255, 255, 0.08);
            --border-highlight: rgba(255, 255, 255, 0.15);
            --shadow-glow: 0 0 30px var(--accent-glow);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg-base);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3, h4, .outfit-font {
            font-family: 'Outfit', sans-serif;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulseGlow {
            0% { opacity: 0.4; filter: blur(60px) scale(1); }
            100% { opacity: 0.7; filter: blur(80px) scale(1.1); }
        }

        .animate-in {
            animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
            opacity: 0;
        }
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 10;
        }

        /* Hero Section */
        .home-hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .home-hero .photo-bg {
            position: absolute;
            inset: -5%;
            background: url("public/bgm.png") center/cover no-repeat;
            filter: blur(12px) brightness(0.35) saturate(1.1);
            z-index: 1;
        }
        .home-hero .bg-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(6,10,16,0.2) 0%, var(--bg-base) 100%);
            z-index: 2;
        }
        .glow-orb {
            position: absolute;
            top: -20%;
            right: -10%;
            width: 70vw;
            height: 70vw;
            background: radial-gradient(circle, var(--accent-glow) 0%, transparent 60%);
            z-index: 3;
            animation: pulseGlow 8s alternate infinite ease-in-out;
            pointer-events: none;
        }
        .home-hero-content {
            flex: 1;
            display: flex;
            align-items: center;
            padding: 0; /* Remove unequal padding to perfectly center */
            z-index: 4;
        }

        /* Header */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem 0;
            z-index: 5;
            position: relative;
        }
        .logo-mark {
            height: 70px;
            width: auto;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3));
            transition: transform 0.3s;
        }
        .logo-mark:hover {
            transform: scale(1.03);
        }

        .auth-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .auth-link {
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 0.75rem 1.75rem;
            border-radius: 99px;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
        }
        .auth-link--ghost {
            color: var(--text-primary);
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border-light);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .auth-link--ghost:hover {
            background: rgba(255,255,255,0.08);
            border-color: var(--border-highlight);
            transform: translateY(-2px);
        }
        .auth-link--solid {
            background: var(--text-primary);
            color: var(--bg-base);
            box-shadow: 0 4px 15px rgba(255,255,255,0.1);
        }
        .auth-link--solid:hover {
            box-shadow: 0 8px 25px rgba(255,255,255,0.25);
            transform: translateY(-2px);
        }

        /* Hero Layout */
        .hero-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 4rem;
            align-items: center;
            width: 100%;
        }
        .hero-title {
            font-size: clamp(3.8rem, 8vw, 7.5rem);
            font-weight: 800;
            line-height: 0.95;
            text-transform: uppercase;
            background: linear-gradient(135deg, #ffffff 30%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.25rem;
            letter-spacing: -0.02em;
        }
        .hero-tagline {
            font-size: 1.2rem;
            color: var(--text-secondary);
            font-weight: 400;
            letter-spacing: 0.03em;
            margin-bottom: 0;
        }
        .hero-right-content {
            padding-bottom: 0.5rem;
            text-align: right;
        }
        .hero-details {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .cta {
            display: inline-flex;
            align-items: center;
            padding: 1rem 2.5rem;
            background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
            color: var(--bg-base);
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.05rem;
            border-radius: 99px;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3), inset 0 -2px 0 rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.4);
            position: relative;
            overflow: hidden;
        }
        .cta::after {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(220, 179, 138, 0), rgba(220, 179, 138, 0.3));
            opacity: 0; transition: opacity 0.3s;
        }
        .cta:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(255,255,255,0.15), inset 0 -2px 0 rgba(0,0,0,0.1);
        }
        .cta:hover::after { opacity: 1; }

        /* Page Layout */
        .page-gradient-wrap {
            position: relative;
            background: linear-gradient(180deg, var(--bg-base) 0%, var(--bg-surface) 50%, var(--bg-base) 100%);
        }
        .page-container {
            display: flex;
            flex-direction: column;
            gap: 6rem;
            padding: 6rem 0;
        }

        /* Glass Pane Component */
        .glass-pane {
            background: var(--bg-panel);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--border-light);
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.05);
            padding: 3.5rem;
            position: relative;
            overflow: hidden;
        }
        .glass-pane::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.12), transparent);
        }

        /* Section Headings */
        .section-eyebrow, .section-side-heading {
            font-family: 'Outfit', sans-serif;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.25em;
            color: var(--accent);
            margin-bottom: 0.75rem;
            font-weight: 700;
        }
        .section-title {
            font-size: 2.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary);
            letter-spacing: -0.01em;
        }
        .section-body {
            color: var(--text-secondary);
            font-size: 1.05rem;
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        /* Grid Layouts */
        .section-swf {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 4rem;
        }

        /* Timeline Items */
        .timeline { display: grid; gap: 1.25rem; }
        .timeline-item, .objective-card, .funding-card, .committee-node {
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--border-light);
            border-radius: 1rem;
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .timeline-item:hover, .objective-card:hover, .funding-card:hover, .committee-node:hover {
            background: rgba(255,255,255,0.04);
            border-color: var(--border-highlight);
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }
        .timeline-item {
            display: grid;
            grid-template-columns: 90px 1fr;
            gap: 1.5rem;
            padding: 1.25rem 1.5rem;
        }
        .timeline-year {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--accent);
            letter-spacing: 0.05em;
        }
        .timeline-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 1.15rem;
            margin-bottom: 0.25rem;
            color: var(--text-primary);
        }
        .timeline-copy {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* Objective Cards */
        .objective-cards { display: grid; gap: 1.25rem; }
        .objective-card {
            display: grid;
            grid-template-columns: 48px 1fr;
            gap: 1.25rem;
            padding: 1.25rem 1.5rem;
        }
        .objective-icon, .committee-node-badge {
            width: 48px; height: 48px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.02));
            border: 1px solid var(--border-light);
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--accent);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.1);
        }
        .objective-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 1.15rem;
            margin-bottom: 0.25rem;
            color: var(--text-primary);
        }
        .objective-copy {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        /* Funding Section */
        .funding-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
        }
        .funding-card {
            padding: 2.5rem 2rem;
            border-radius: 1.5rem;
        }
        .funding-card--primary {
            background: linear-gradient(145deg, rgba(220, 179, 138, 0.1), rgba(0,0,0,0));
            border-color: rgba(220, 179, 138, 0.3);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.1);
        }
        .funding-card--primary:hover {
            border-color: var(--accent);
            box-shadow: 0 20px 40px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.15);
        }
        .funding-pill {
            display: inline-block;
            padding: 0.45rem 1.1rem;
            border-radius: 99px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-weight: 700;
            margin-bottom: 1.5rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border-light);
            color: var(--text-primary);
        }
        .funding-card--primary .funding-pill {
            background: var(--accent);
            color: #000;
            border: none;
            box-shadow: 0 4px 10px rgba(220, 179, 138, 0.3);
        }
        .funding-price {
            display: flex;
            align-items: baseline;
            gap: 0.3rem;
            margin-bottom: 0.5rem;
            font-family: 'Outfit', sans-serif;
        }
        .funding-currency {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-secondary);
        }
        .funding-amount {
            font-size: 3.5rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: var(--text-primary);
        }
        .funding-period {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-weight: 500;
        }
        .funding-body {
            color: var(--text-secondary);
            font-size: 1.05rem;
            line-height: 1.6;
        }

        /* Committee Tree */
        .committee-tree {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
            padding: 1rem 0 3rem;
            overflow-x: auto;
        }
        .committee-level {
            display: flex;
            justify-content: center;
            gap: 2rem;
            width: 100%;
        }
        .committee-node {
            padding: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 1.2rem;
            flex: 1;
            max-width: 360px;
            min-width: 280px;
            border-radius: 1.25rem;
        }
        .committee-node--primary {
            background: linear-gradient(135deg, rgba(220, 179, 138, 0.15), rgba(255,255,255,0.02));
            border-color: rgba(220, 179, 138, 0.3);
        }
        .committee-node-badge {
            width: 44px; height: 44px;
            border-radius: 12px;
        }
        .committee-node--primary .committee-node-badge {
            background: var(--accent);
            color: #000;
            border: none;
        }
        .committee-node-role {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.35rem;
            line-height: 1.3;
            color: var(--text-primary);
        }
        .committee-node-note {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }
        .committee-connector {
            width: 2px;
            height: 32px;
            background: linear-gradient(to bottom, var(--border-highlight), transparent);
            margin: -2rem auto 0;
            position: relative;
        }
        .committee-connector::after {
            content: ''; position: absolute; top: 0; left: 50%;
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--border-highlight);
            transform: translate(-50%, -50%);
        }

        /* Footer */
        footer {
            padding: 4rem 2rem;
            text-align: center;
            border-top: 1px solid var(--border-light);
            position: relative;
            background: var(--bg-surface);
        }
        footer::before {
            content: '';
            position: absolute;
            top: 0; left: 50%; width: 50%; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(220, 179, 138, 0.3), transparent);
            transform: translateX(-50%);
        }
        .footer-logo {
            height: 55px;
            margin-bottom: 1.5rem;
            opacity: 0.9;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3));
        }
        .footer-meta {
            font-family: 'Outfit', sans-serif;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            font-size: 0.95rem;
            color: var(--text-primary);
            margin-bottom: 1.25rem;
            font-weight: 600;
        }
        .footer-info {
            color: var(--text-secondary);
            font-size: 1rem;
            line-height: 1.8;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .hero-grid { grid-template-columns: 1fr; text-align: center; }
            .hero-right-content { text-align: center; margin-top: 2rem; }
            .hero-tagline { margin: 0 auto; }
            .home-hero .glow-orb { top: -10%; left: 0; right: 0; width: 100vw; }
            .section-swf { grid-template-columns: 1fr; gap: 4rem; }
            .glass-pane { padding: 2.5rem 2rem; }
            .committee-level { flex-wrap: wrap; }
            .committee-connector { margin-top: -1.5rem; }
        }
        @media (max-width: 768px) {
            header { flex-direction: column; gap: 1.5rem; }
            .hero-title { font-size: 3.2rem; }
            .funding-grid { grid-template-columns: 1fr; }
            .committee-node { min-width: 100%; }
        }
    </style>
</head>
<body>
    <section class="home-hero" id="home">
        <div class="photo-bg"></div>
        <div class="bg-overlay"></div>
        <div class="glow-orb"></div>
        
        <div class="container" style="display: flex; flex-direction: column; flex: 1;">
            <header class="animate-in">
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
            
            <div class="home-hero-content">
                <div class="hero-grid">
                    <div class="hero-left-content animate-in delay-1">
                        <h1 class="hero-title">RCMP UniFa</h1>
                        <p class="hero-tagline">UniKL Financial Aid System — Supporting Student Success</p>
                    </div>
                    <div class="hero-right-content animate-in delay-2">
                        <p class="hero-details">Empowering UniKL students to achieve their academic dreams through comprehensive financial support.</p>
                        <a href="auth/login.php" class="cta">Get Started</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="page-gradient-wrap">
        <div class="container page-container">
            
            <section class="section-swf glass-pane animate-in delay-3">
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
                                <div class="timeline-copy">Approved on TMM 30 January 2018, with operations managed by the Campus Lifestyle Division.</div>
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
                                <div class="objective-copy">Support related to medical conditions or injuries, including cases that require quick intervention.</div>
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

            <section class="section-funding glass-pane animate-in delay-4">
                <div class="section-eyebrow">Funding</div>
                <h2 class="section-title">Student Contribution SWF</h2>
                <p class="section-body" style="max-width: 600px; margin-bottom: 3rem;">The fund collection is based on SWF fees collected from registered students each semester to continuously power this initiative.</p>
                
                <div class="funding-grid">
                    <article class="funding-card funding-card--primary">
                        <div class="funding-pill">Local student</div>
                        <div class="funding-price">
                            <span class="funding-currency">RM</span>
                            <span class="funding-amount">30.00</span>
                        </div>
                        <div class="funding-period">Every semester</div>
                        <p class="funding-body">SWF fee collected from registered local students each semester.</p>
                    </article>
                    <article class="funding-card">
                        <div class="funding-pill">International student</div>
                        <div class="funding-price">
                            <span class="funding-currency">RM</span>
                            <span class="funding-amount">50.00</span>
                        </div>
                        <div class="funding-period">Every semester</div>
                        <p class="funding-body">SWF fee collected from registered international students each semester.</p>
                    </article>
                </div>
            </section>

            <section class="section-programs glass-pane animate-in delay-4">
                <div class="section-eyebrow">Committee</div>
                <h2 class="section-title">SWF Campus Committee Members</h2>
                <p class="section-body" style="max-width: 600px; margin-bottom: 3rem;">Committee structure and membership hierarchy for SWF at campus level.</p>
                
                <div class="committee-tree" role="list">
                    <div class="committee-level" role="listitem">
                        <div class="committee-node committee-node--primary">
                            <div class="committee-node-badge">1</div>
                            <div>
                                <div class="committee-node-role">Head of Campus / Dean</div>
                                <div class="committee-node-note">Chairperson</div>
                            </div>
                        </div>
                    </div>

                    <div class="committee-connector" aria-hidden="true"></div>

                    <div class="committee-level" role="listitem">
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
                                <div class="committee-node-role">Rep. of Finance &amp; Admin</div>
                                <div class="committee-node-note">Committee member</div>
                            </div>
                        </div>
                    </div>

                    <div class="committee-connector" aria-hidden="true"></div>

                    <div class="committee-level" role="listitem" style="max-width: 800px; margin: 0 auto;">
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
                                <div class="committee-node-role">President of SRC or representative</div>
                                <div class="committee-node-note">By invitation</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
        
        <footer>
            <div class="container">
                <img src="public/rcmp-white.png" alt="UniKL RCMP" class="footer-logo">
                <div class="footer-meta">Universiti Kuala Lumpur &middot; Royal College of Medicine Perak</div>
                <div class="footer-info">
                    Email: rcmp@unikl.edu.my &nbsp;&middot;&nbsp; Tel: +60 (0)5-123 4567<br>
                    Location: Jalan Greentown, 30450 Ipoh, Perak, Malaysia<br>
                    Office Hours: Monday &ndash; Friday, 8:00 AM &ndash; 5:00 PM
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
