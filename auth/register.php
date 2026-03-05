<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — RCMP UniFa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            font-family: 'DM Sans', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            color: #0f1419;
            background: #05070b;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .page-bg {
            position: fixed;
            inset: 0;
            background: url("../public/bgm.png") center center / cover no-repeat;
            filter: blur(10px);
            transform: scale(1.06);
            opacity: 0.85;
            z-index: -2;
        }
        .page-overlay {
            position: fixed;
            inset: 0;
            background: radial-gradient(circle at top, rgba(0,0,0,0.5), rgba(0,0,0,0.9));
            z-index: -1;
        }
        .shell {
            width: min(1080px, 100% - 3rem);
            border-radius: 26px;
            background: rgba(6, 10, 18, 0.96);
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 24px 80px rgba(0,0,0,0.7);
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(0, 1.1fr);
            overflow: hidden;
        }
        .shell-left {
            position: relative;
            padding: 1.75rem;
            background: radial-gradient(circle at top left, rgba(255,255,255,0.15), transparent 40%);
        }
        .shell-left-inner {
            position: relative;
            height: 100%;
            border-radius: 22px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.22);
        }
        .shell-left-photo {
            position: absolute;
            inset: 0;
            background: url("../public/bgm.png") center center / cover no-repeat;
            transform: scale(1.06);
            filter: saturate(1.1);
        }
        .shell-left-gradient {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(5,7,11,0.1) 0%, rgba(5,7,11,0.95) 80%);
        }
        .shell-left-content {
            position: relative;
            height: 100%;
            padding: 1.8rem 2.2rem 2.3rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: #fff;
        }
        .left-eyebrow {
            font-size: 0.7rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            opacity: 0.75;
        }
        .left-quote {
            margin-top: 0.6rem;
            font-family: 'Playfair Display', serif;
            font-size: 2.1rem;
            line-height: 1.2;
        }
        .left-sub {
            margin-top: 0.75rem;
            font-size: 0.9rem;
            line-height: 1.6;
            color: rgba(255,255,255,0.85);
        }
        .left-meta {
            font-size: 0.75rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.75);
        }
        .shell-right {
            padding: 2.25rem 2.5rem 2.4rem;
            background: #f9fafb;
        }
        .right-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.75rem;
        }
        .brand-mark {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #111827;
        }
        .brand-mark img {
            height: 28px;
            width: auto;
        }
        .brand-badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: #111827;
            color: #f9fafb;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        .form-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.9rem;
            margin-bottom: 0.3rem;
            color: #111827;
        }
        .form-subtitle {
            font-size: 0.9rem;
            color: #6b7280;
            margin-bottom: 1.4rem;
        }
        .grid-two {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }
        .field {
            margin-bottom: 1rem;
        }
        .field label {
            display: block;
            font-size: 0.8rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.4rem;
        }
        .field input,
        .field select,
        .field textarea {
            width: 100%;
            border-radius: 0.9rem;
            border: 1px solid #e5e7eb;
            padding: 0.75rem 0.9rem;
            font-size: 0.9rem;
            outline: none;
            background: #f9fafb;
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        }
        .field textarea {
            min-height: 90px;
            resize: vertical;
        }
        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            border-color: #111827;
            background: #ffffff;
            box-shadow: 0 0 0 1px #1118270d;
        }
        .field small {
            display: block;
            margin-top: 0.25rem;
            font-size: 0.75rem;
            color: #9ca3af;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            border-radius: 999px;
            padding: 0.8rem 1rem;
            font-size: 0.88rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            border: none;
            cursor: pointer;
            transition: transform 0.1s ease, box-shadow 0.1s ease, background 0.15s;
        }
        .btn-primary {
            background: #0f1419;
            color: #f9fafb;
            box-shadow: 0 12px 30px rgba(15,20,25,0.5);
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 40px rgba(15,20,25,0.6);
        }
        .form-footer {
            margin-top: 1.3rem;
            font-size: 0.8rem;
            color: #6b7280;
            text-align: center;
        }
        .form-footer a {
            color: #111827;
            text-decoration: none;
            font-weight: 500;
        }
        .form-footer a:hover {
            text-decoration: underline;
        }
        @media (max-width: 900px) {
            .shell {
                grid-template-columns: minmax(0, 1fr);
                border-radius: 22px;
            }
            .shell-left {
                display: none;
            }
            .shell-right {
                padding: 2rem 1.75rem 2.2rem;
            }
            .grid-two {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="page-bg"></div>
    <div class="page-overlay"></div>
    <div class="shell">
        <div class="shell-left">
            <div class="shell-left-inner">
                <div class="shell-left-photo"></div>
                <div class="shell-left-gradient"></div>
                <div class="shell-left-content">
                    <div>
                        <div class="left-eyebrow">Start your journey</div>
                        <div class="left-quote">Financial support tailored for UniKL students.</div>
                        <p class="left-sub">
                            Create your UniFa account to apply for assistance, track applications, and stay connected with the
                            RCMP Student Welfare Fund.
                        </p>
                    </div>
                    <div class="left-meta">RCMP UniFa &middot; Student Welfare Fund</div>
                </div>
            </div>
        </div>
        <div class="shell-right">
            <div class="right-header">
                <div class="brand-mark">
                    <img src="../public/official-logo.png" alt="UniKL RCMP">
                    RCMP UniFa
                </div>
                <span class="brand-badge">Create account</span>
            </div>
            <h1 class="form-title">Sign up</h1>
            <p class="form-subtitle">Fill in your details to register for the UniKL Financial Aid System.</p>

            <form action="" method="post" novalidate>
                <div class="field">
                    <label for="full_name">Full name</label>
                    <input type="text" id="full_name" name="full_name" placeholder="Enter your full name">
                </div>

                <div class="grid-two">
                    <div class="field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="name@student.unikl.edu.my">
                    </div>
                    <div class="field">
                        <label for="phone">Phone number</label>
                        <input type="tel" id="phone" name="phone" placeholder="+60 1X-XXXX XXXX">
                    </div>
                </div>

                <div class="field">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" placeholder="Current correspondence address"></textarea>
                </div>

                <div class="grid-two">
                    <div class="field">
                        <label for="bank_name">Bank name</label>
                        <input type="text" id="bank_name" name="bank_name" placeholder="e.g. Maybank, CIMB">
                    </div>
                    <div class="field">
                        <label for="bank_account">Bank account no.</label>
                        <input type="text" id="bank_account" name="bank_account" placeholder="Enter bank account number">
                        <small>Used for disbursement of approved financial aid.</small>
                    </div>
                </div>

                <div class="grid-two">
                    <div class="field">
                        <label for="role">Role</label>
                        <select id="role" name="role">
                            <option value="">Select role</option>
                            <option value="student">Student</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Create a password">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Create account</button>

                <div class="form-footer">
                    Already have an account?
                    <a href="login.php">Sign in</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>