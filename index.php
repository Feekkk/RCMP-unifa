<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RCMP UniFa — Financial Aid System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f1419 0%, #1a2332 40%, #16202a 100%);
            color: #fff;
            overflow-x: hidden;
        }
        .photo-bg {
            position: fixed;
            inset: 0;
            background: url("public/bgm.png") center center / cover no-repeat;
            filter: blur(7px);
            transform: scale(1.04);
            z-index: -3;
        }
        .bg-overlay {
            position: fixed;
            inset: 0;
            background: radial-gradient(circle at top, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.85));
            pointer-events: none;
            z-index: -2;
        }
        .warm-gradient {
            position: fixed;
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
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 2rem 4rem 3rem;
            max-width: 1200px;
            margin: 0 auto;
            row-gap: 4rem;
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
            padding-top: 2.5rem;
            border-top: 1px solid rgba(255,255,255,0.08);
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(0, 1.4fr);
            gap: 3rem;
            align-items: flex-start;
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
        }
    </style>
</head>
<body>
    <div class="photo-bg"></div>
    <div class="bg-overlay"></div>
    <div class="warm-gradient"></div>
    <div class="container">
        <header>
            <span class="logo">
                <img src="public/official-logo.png" alt="UniKL RCMP logo" class="logo-mark">
            </span>
            <div class="header-right">
                <nav>
                    <button class="nav-trigger" type="button" aria-expanded="false" aria-haspopup="true">— services</button>
                    <div class="nav-dropdown" role="menu">
                        <a href="#" role="menuitem">Financial Aid</a>
                        <a href="#" role="menuitem">Scholarships</a>
                        <a href="#" role="menuitem">Loans &amp; Bursaries</a>
                        <a href="#" role="menuitem">Application Status</a>
                    </div>
                </nav>
                <div class="auth-actions">
                    <a href="/login.php" class="auth-link auth-link--ghost">Login</a>
                    <a href="/register.php" class="auth-link auth-link--solid">Register</a>
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
                <a href="#" class="cta">Get Started</a>
            </div>
        </main>

        <section class="section-swf">
            <div>
                <div class="section-eyebrow">History</div>
                <h2 class="section-title">Student Welfare Fund (SWF)</h2>
                <p class="section-body">
                    The Student Welfare Fund (SWF) was established in [YEAR] to provide financial assistance to UniKL RCMP
                    students facing economic hardships. Our mission is to ensure that no deserving student is unable to complete
                    their education due to financial constraints.
                </p>
                <p class="section-body">
                    Since its inception, SWF has been a cornerstone of support, enabling thousands of students to continue
                    their academic journey with dignity.
                </p>
            </div>
            <div>
                <div class="section-side-heading">At a glance</div>
                <ul class="section-list">
                    <li><strong>Mission</strong>: To support UniKL students through equitable financial assistance.</li>
                    <li><strong>Milestones</strong>: Growing partnerships and increased fund availability year-on-year.</li>
                    <li><strong>Impact</strong>: Improved retention and graduation rates across faculties.</li>
                </ul>
                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-value">5,000+</div>
                        <div class="stat-label">Students Supported</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">RM 2.5M+</div>
                        <div class="stat-label">Funds Distributed</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">95%</div>
                        <div class="stat-label">Success Rate</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section-funding">
            <div class="funding-header">
                <div class="section-eyebrow">Funding</div>
                <h2 class="section-title">How Our Fund Raises Money</h2>
                <p class="section-body">
                    Our financial aid programs are supported by diverse funding sources working together to maximise impact.
                </p>
            </div>
            <div class="funding-grid">
                <article class="funding-card">
                    <h3 class="funding-title">University Budget Allocation</h3>
                    <p class="funding-body">Annual allocations dedicated to student welfare and retention.</p>
                </article>
                <article class="funding-card">
                    <h3 class="funding-title">Alumni Donations</h3>
                    <p class="funding-body">Generous contributions from alumni to support future generations.</p>
                </article>
                <article class="funding-card">
                    <h3 class="funding-title">Corporate Sponsorships</h3>
                    <p class="funding-body">Partnerships with industry to sponsor targeted aid.</p>
                </article>
                <article class="funding-card">
                    <h3 class="funding-title">Government Grants</h3>
                    <p class="funding-body">Grants and matching funds from government agencies.</p>
                </article>
                <article class="funding-card">
                    <h3 class="funding-title">Fundraising Events</h3>
                    <p class="funding-body">Community events and campaigns that drive contributions.</p>
                </article>
                <article class="funding-card">
                    <h3 class="funding-title">Student Contributions</h3>
                    <p class="funding-body">Optional contributions that strengthen the fund.</p>
                </article>
            </div>
        </section>

        <section class="section-programs">
            <div class="programs-header">
                <div class="section-eyebrow">Programs</div>
                <h2 class="section-title">Available Financial Aid Programs</h2>
                <p class="section-body">
                    Explore programs tailored to support a variety of student needs.
                </p>
            </div>
            <div class="program-grid">
                <article class="program-card">
                    <div>
                        <h3 class="program-title">Emergency Financial Assistance</h3>
                        <p class="program-body">Immediate support for students facing urgent financial difficulties.</p>
                    </div>
                    <a href="#" class="program-link"><span>Learn More</span></a>
                </article>
                <article class="program-card">
                    <div>
                        <h3 class="program-title">Tuition Fee Support</h3>
                        <p class="program-body">Partial coverage of tuition fees for eligible students.</p>
                    </div>
                    <a href="#" class="program-link"><span>Learn More</span></a>
                </article>
                <article class="program-card">
                    <div>
                        <h3 class="program-title">Book &amp; Study Material Grants</h3>
                        <p class="program-body">Grants to purchase textbooks and essential learning materials.</p>
                    </div>
                    <a href="#" class="program-link"><span>Learn More</span></a>
                </article>
                <article class="program-card">
                    <div>
                        <h3 class="program-title">Living Allowance Support</h3>
                        <p class="program-body">Assistance with living costs for students in need.</p>
                    </div>
                    <a href="#" class="program-link"><span>Learn More</span></a>
                </article>
                <article class="program-card">
                    <div>
                        <h3 class="program-title">Project/Research Funding</h3>
                        <p class="program-body">Funding for approved academic projects and research.</p>
                    </div>
                    <a href="#" class="program-link"><span>Learn More</span></a>
                </article>
                <article class="program-card">
                    <div>
                        <h3 class="program-title">Technology &amp; Equipment Aid</h3>
                        <p class="program-body">Support for laptops, software, and specialised equipment.</p>
                    </div>
                    <a href="#" class="program-link"><span>Learn More</span></a>
                </article>
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
