<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrantGate | Login / Sign Up</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --green: #00c853;
            --green-glow: rgba(0, 200, 83, 0.35);
            --green-dim: rgba(0, 200, 83, 0.08);
            --dark-bg: #0f172a;
            --darker: #0b1121;
            --card-bg: rgba(15, 23, 42, 0.7);
            --card-border: rgba(255, 255, 255, 0.06);
            --fg: #ffffff;
            --muted: #64748b;
            --input-bg: rgba(255, 255, 255, 0.04);
            --input-border: rgba(255, 255, 255, 0.08);
            --input-focus: rgba(0, 200, 83, 0.25);
            --danger: #ff4757;
            --warning: #ffc93c;
            --success: #00c853;
            --radius: 16px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--dark-bg);
            color: var(--fg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* ========== ANIMATED BACKGROUND ========== */
        .bg-canvas {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.45;
            animation: blobDrift 14s ease-in-out infinite alternate;
        }

        .blob-1 {
            width: 600px; height: 600px;
            background: radial-gradient(circle, var(--green), transparent 70%);
            top: -20%; left: -15%;
            animation-duration: 16s;
        }

        .blob-2 {
            width: 450px; height: 450px;
            background: radial-gradient(circle, #00e676, transparent 70%);
            bottom: -20%; right: -10%;
            animation-duration: 20s;
            animation-delay: -5s;
        }

        .blob-3 {
            width: 350px; height: 350px;
            background: radial-gradient(circle, #1de9b6, transparent 70%);
            top: 40%; left: 55%;
            opacity: 0.2;
            animation-duration: 22s;
            animation-delay: -10s;
        }

        @keyframes blobDrift {
            0%   { transform: translate(0, 0) scale(1) rotate(0deg); }
            25%  { transform: translate(60px, -80px) scale(1.1) rotate(5deg); }
            50%  { transform: translate(-30px, 60px) scale(0.95) rotate(-3deg); }
            75%  { transform: translate(80px, 30px) scale(1.08) rotate(4deg); }
            100% { transform: translate(-50px, -40px) scale(1.02) rotate(-2deg); }
        }

        /* Subtle grid */
        .grid-overlay {
            position: fixed; inset: 0; z-index: 1; pointer-events: none;
            background-image:
                linear-gradient(rgba(0, 200, 83, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 200, 83, 0.02) 1px, transparent 1px);
            background-size: 70px 70px;
        }

        /* Noise */
        .noise {
            position: fixed; inset: 0; z-index: 2; pointer-events: none; opacity: 0.03;
            background: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        }

        /* Floating particles */
        .particles { position: fixed; inset: 0; z-index: 1; pointer-events: none; }

        .particle {
            position: absolute;
            background: var(--green);
            border-radius: 50%;
            opacity: 0;
            animation: floatUp linear infinite;
        }

        @keyframes floatUp {
            0%   { opacity: 0; transform: translateY(0) scale(0); }
            8%   { opacity: 0.7; transform: scale(1); }
            85%  { opacity: 0.5; }
            100% { opacity: 0; transform: translateY(-100vh) scale(0.5); }
        }

        /* Scan line moving across */
        .scan-line {
            position: fixed; left: 0; width: 100%; height: 2px; z-index: 2;
            background: linear-gradient(90deg, transparent, var(--green), transparent);
            opacity: 0.15;
            animation: scanMove 6s linear infinite;
            pointer-events: none;
        }

        @keyframes scanMove {
            0%   { top: -2px; }
            100% { top: 100%; }
        }

        /* ========== BACK LINK ========== */
        .back-link {
            position: fixed; top: 28px; left: 32px; z-index: 100;
            display: flex; align-items: center; gap: 8px;
            color: var(--muted); text-decoration: none; font-size: 14px;
            font-weight: 600; letter-spacing: 0.05em;
            transition: all 0.3s;
        }
        .back-link:hover { color: var(--green); transform: translateX(-4px); }
        .back-link i { font-size: 16px; }

        /* ========== LOGO ========== */
        .auth-logo {
            position: fixed; top: 24px; right: 32px; z-index: 100;
            font-family: 'Orbitron', sans-serif;
            font-size: 1.4rem; font-weight: 900; color: #fff;
        }
        .auth-logo span { color: var(--green); }

        /* ========== AUTH CARD ========== */
        .auth-wrapper {
            position: relative; z-index: 10;
            width: 100%; max-width: 460px; padding: 20px;
        }

        .auth-card {
            background: var(--card-bg);
            backdrop-filter: blur(50px) saturate(1.5);
            -webkit-backdrop-filter: blur(50px) saturate(1.5);
            border: 1px solid var(--card-border);
            border-radius: var(--radius);
            padding: 44px 38px;
            position: relative;
            overflow: hidden;
            animation: cardReveal 0.9s cubic-bezier(0.22, 1, 0.36, 1) forwards;
            opacity: 0;
            transform: translateY(50px) scale(0.95);
        }

        @keyframes cardReveal {
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Rotating glow border */
        .auth-card::before {
            content: '';
            position: absolute; inset: -2px;
            border-radius: calc(var(--radius) + 2px);
            background: conic-gradient(
                from var(--border-angle, 0deg),
                transparent 0%, var(--green) 8%, transparent 16%, transparent 100%
            );
            z-index: -1;
            opacity: 0;
            transition: opacity 0.6s;
            animation: spinBorder 5s linear infinite;
        }

        .auth-card:hover::before { opacity: 1; }

        @property --border-angle {
            syntax: '<angle>';
            initial-value: 0deg;
            inherits: false;
        }

        @keyframes spinBorder {
            to { --border-angle: 360deg; }
        }

        .auth-card::after {
            content: '';
            position: absolute; inset: 1px;
            border-radius: calc(var(--radius) - 1px);
            background: var(--card-bg);
            z-index: -1;
            backdrop-filter: blur(50px);
        }

        /* Decorative corner accents */
        .corner-accent {
            position: absolute; width: 60px; height: 60px; pointer-events: none; opacity: 0.25;
        }
        .corner-accent.tl { top: 0; left: 0; border-top: 2px solid var(--green); border-left: 2px solid var(--green); border-radius: var(--radius) 0 0 0; }
        .corner-accent.br { bottom: 0; right: 0; border-bottom: 2px solid var(--green); border-right: 2px solid var(--green); border-radius: 0 0 var(--radius) 0; }

        /* ========== TAB SWITCHER ========== */
        .tab-switcher {
            display: flex;
            background: rgba(255,255,255,0.03);
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 32px;
            position: relative;
            border: 1px solid var(--input-border);
        }

        .tab-slider {
            position: absolute;
            top: 4px; left: 4px;
            width: calc(50% - 4px);
            height: calc(100% - 8px);
            background: var(--green);
            border-radius: 9px;
            transition: transform 0.5s cubic-bezier(0.68, -0.25, 0.32, 1.25);
            box-shadow: 0 4px 24px var(--green-glow);
        }

        .tab-slider.right {
            transform: translateX(100%);
        }

        .tab-btn {
            flex: 1;
            padding: 12px 0;
            background: none; border: none;
            color: var(--muted);
            font-family: 'Orbitron', sans-serif;
            font-size: 13px; font-weight: 700;
            cursor: pointer;
            position: relative; z-index: 2;
            transition: color 0.35s;
            letter-spacing: 0.08em;
        }

        .tab-btn.active { color: #fff; }

        /* ========== FORM PANELS ========== */
        .form-container { position: relative; overflow: hidden; }

        .form-panel {
            transition: all 0.45s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .form-panel.hidden { display: none; }

        .form-panel.slide-out-left {
            animation: outLeft 0.35s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }
        .form-panel.slide-in-right {
            animation: inRight 0.4s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }
        .form-panel.slide-out-right {
            animation: outRight 0.35s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }
        .form-panel.slide-in-left {
            animation: inLeft 0.4s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }

        @keyframes outLeft {
            to { transform: translateX(-110%); opacity: 0; }
        }
        @keyframes inRight {
            from { transform: translateX(110%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes outRight {
            to { transform: translateX(110%); opacity: 0; }
        }
        @keyframes inLeft {
            from { transform: translateX(-110%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* ========== INPUT GROUPS ========== */
        .input-group {
            position: relative;
            margin-bottom: 26px;
        }

        .input-group input {
            width: 100%;
            padding: 16px 18px 16px 50px;
            background: var(--input-bg);
            border: 1.5px solid var(--input-border);
            border-radius: 12px;
            color: var(--fg);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            outline: none;
            transition: all 0.35s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .input-group input::placeholder { color: transparent; }

        .input-group input:focus {
            border-color: var(--green);
            background: var(--green-dim);
            box-shadow: 0 0 0 4px var(--input-focus), 0 4px 24px rgba(0,200,83,0.06);
        }

        .input-group .input-icon {
            position: absolute; left: 18px; top: 50%;
            transform: translateY(-50%);
            color: var(--muted); font-size: 15px;
            transition: color 0.3s, transform 0.3s;
            z-index: 2;
        }

        .input-group input:focus ~ .input-icon {
            color: var(--green);
            transform: translateY(-50%) scale(1.15);
        }

        .input-group .floating-label {
            position: absolute; left: 50px; top: 50%;
            transform: translateY(-50%);
            color: var(--muted); font-size: 14px;
            pointer-events: none;
            transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
            padding: 0 5px;
        }

        .input-group input:focus ~ .floating-label,
        .input-group input:not(:placeholder-shown) ~ .floating-label {
            top: 0; left: 14px;
            font-size: 11px; font-weight: 600;
            color: var(--green);
            background: linear-gradient(to bottom, transparent 48%, rgba(15,23,42,0.95) 48%);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        /* Password eye toggle */
        .pw-toggle {
            position: absolute; right: 16px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: var(--muted); cursor: pointer;
            font-size: 15px; transition: color 0.3s;
            z-index: 2;
        }
        .pw-toggle:hover { color: var(--fg); }

        /* Validation messages */
        .input-group .val-msg {
            position: absolute; bottom: -17px; left: 16px;
            font-size: 11px; font-weight: 500;
            opacity: 0; transform: translateY(-4px);
            transition: all 0.3s;
        }
        .input-group .val-msg.show { opacity: 1; transform: translateY(0); }
        .val-msg.error { color: var(--danger); }
        .val-msg.success { color: var(--success); }

        /* Password strength */
        .pw-strength {
            display: flex; gap: 5px;
            margin-top: 10px; margin-bottom: 2px;
            opacity: 0; transition: opacity 0.35s;
        }
        .pw-strength.visible { opacity: 1; }

        .pw-strength .bar {
            flex: 1; height: 3px; border-radius: 3px;
            background: rgba(255,255,255,0.06);
            transition: background 0.4s;
        }
        .pw-strength .bar.weak { background: var(--danger); }
        .pw-strength .bar.medium { background: var(--warning); }
        .pw-strength .bar.strong { background: var(--success); }

        .pw-label {
            font-size: 11px; color: var(--muted);
            margin-bottom: 10px; min-height: 16px;
            transition: color 0.3s;
        }

        /* ========== CHECKBOX ========== */
        .check-row {
            display: flex; align-items: center; gap: 10px;
            cursor: pointer; font-size: 13px; color: var(--muted);
            user-select: none; margin-bottom: 8px;
        }

        .check-row input { display: none; }

        .custom-check {
            width: 20px; height: 20px;
            border: 1.5px solid var(--input-border);
            border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
            flex-shrink: 0;
        }

        .custom-check i {
            font-size: 10px; color: #fff;
            opacity: 0; transform: scale(0) rotate(-45deg);
            transition: all 0.35s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .check-row input:checked ~ .custom-check {
            background: var(--green);
            border-color: var(--green);
            box-shadow: 0 0 12px var(--green-glow);
        }

        .check-row input:checked ~ .custom-check i {
            opacity: 1; transform: scale(1) rotate(0);
        }

        /* ========== EXTRAS ROW ========== */
        .extras-row {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 6px;
        }

        .forgot-link {
            font-size: 13px; color: var(--green);
            text-decoration: none; font-weight: 500;
            transition: opacity 0.3s;
        }
        .forgot-link:hover { opacity: 0.75; }

        /* ========== SUBMIT BUTTON ========== */
        .submit-btn {
            width: 100%; padding: 16px;
            background: var(--green); border: none;
            border-radius: 12px; color: #fff;
            font-family: 'Orbitron', sans-serif;
            font-size: 14px; font-weight: 700;
            cursor: pointer; position: relative;
            overflow: hidden; letter-spacing: 0.1em;
            transition: all 0.35s cubic-bezier(0.22, 1, 0.36, 1);
            margin-top: 6px;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 35px var(--green-glow);
        }

        .submit-btn:active {
            transform: translateY(0) scale(0.97);
        }

        /* Ripple */
        .submit-btn .ripple {
            position: absolute; border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: scale(0);
            animation: rippleOut 0.65s ease-out;
            pointer-events: none;
        }

        @keyframes rippleOut {
            to { transform: scale(4); opacity: 0; }
        }

        /* Loading state */
        .submit-btn.loading { pointer-events: none; color: transparent; }

        .submit-btn .spinner {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.3s;
        }

        .submit-btn.loading .spinner { opacity: 1; }

        .spin-dot {
            width: 8px; height: 8px;
            background: #fff; border-radius: 50%;
            margin: 0 5px;
            animation: dotBounce 0.6s ease-in-out infinite;
        }
        .spin-dot:nth-child(2) { animation-delay: 0.12s; }
        .spin-dot:nth-child(3) { animation-delay: 0.24s; }

        @keyframes dotBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }

        /* Success checkmark */
        .submit-btn .check-icon {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transform: scale(0);
            transition: all 0.4s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .submit-btn.success { background: var(--success); color: transparent; }
        .submit-btn.success .check-icon { opacity: 1; transform: scale(1); }

        /* ========== DIVIDER ========== */
        .divider {
            display: flex; align-items: center; gap: 16px;
            margin: 26px 0;
        }

        .divider::before, .divider::after {
            content: ''; flex: 1; height: 1px;
            background: var(--input-border);
        }

        .divider span {
            color: var(--muted); font-size: 11px;
            text-transform: uppercase; letter-spacing: 0.12em;
            font-weight: 600;
        }

        /* ========== SOCIAL BUTTONS ========== */
        .social-row { display: flex; gap: 12px; }

        .social-btn {
            flex: 1; padding: 14px;
            background: var(--input-bg);
            border: 1.5px solid var(--input-border);
            border-radius: 12px; color: var(--fg);
            font-size: 18px; cursor: pointer;
            transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
            display: flex; align-items: center; justify-content: center;
            position: relative; overflow: hidden;
        }

        .social-btn::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(0,200,83,0.1), transparent);
            opacity: 0; transition: opacity 0.3s;
        }

        .social-btn:hover {
            border-color: rgba(0, 200, 83, 0.3);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }

        .social-btn:hover::before { opacity: 1; }
        .social-btn:active { transform: translateY(0) scale(0.96); }

        /* ========== TOAST ========== */
        .toast-box {
            position: fixed; top: 28px; right: 28px; z-index: 9999;
            display: flex; flex-direction: column; gap: 10px;
        }

        .toast {
            padding: 14px 22px; border-radius: 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px; font-weight: 500;
            backdrop-filter: blur(24px);
            border: 1px solid var(--card-border);
            display: flex; align-items: center; gap: 10px;
            animation: toastSlide 0.45s cubic-bezier(0.22, 1, 0.36, 1) forwards;
            min-width: 260px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.4);
        }

        .toast.success { background: rgba(0,200,83,0.15); border-color: rgba(0,200,83,0.3); color: #a7f3d0; }
        .toast.error   { background: rgba(255,71,87,0.15);  border-color: rgba(255,71,87,0.3);  color: #fca5a5; }
        .toast.info    { background: rgba(96,165,250,0.15);  border-color: rgba(96,165,250,0.3); color: #bfdbfe; }

        .toast.removing {
            animation: toastOut 0.35s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }

        @keyframes toastSlide {
            from { transform: translateX(120%); opacity: 0; }
            to   { transform: translateX(0); opacity: 1; }
        }

        @keyframes toastOut {
            to { transform: translateX(120%); opacity: 0; }
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 520px) {
            .auth-card { padding: 32px 24px; }
            .auth-wrapper { padding: 14px; }
            .back-link { top: 18px; left: 18px; font-size: 12px; }
            .auth-logo { top: 18px; right: 18px; font-size: 1.1rem; }
        }

        /* ========== REDUCED MOTION ========== */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>

    <!-- Animated Background -->
    <div class="bg-canvas">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>
    <div class="grid-overlay"></div>
    <div class="noise"></div>
    <div class="particles" id="particleLayer"></div>
    <div class="scan-line"></div>

    <!-- Back to landing -->
    <a href="landing_page.php" class="back-link">
        <i class="fas fa-arrow-left"></i> BACK
    </a>

    <!-- Logo -->
    <div class="auth-logo">GRANT<span>GATE</span></div>

    <!-- Toast container -->
    <div class="toast-box" id="toastBox"></div>

    <!-- Auth Card -->
    <div class="auth-wrapper">
        <div class="auth-card" id="authCard">
            <div class="corner-accent tl"></div>
            <div class="corner-accent br"></div>

            <!-- Tab Switcher -->
            <div class="tab-switcher">
                <div class="tab-slider" id="tabSlider"></div>
                <button class="tab-btn active" id="tabLogin" onclick="switchTab('login')" aria-label="Switch to login form">LOGIN</button>
                <button class="tab-btn" id="tabSignup" onclick="switchTab('signup')" aria-label="Switch to signup form">SIGN UP</button>
            </div>

            <!-- Form Container -->
            <div class="form-container">

                <!-- LOGIN FORM -->
                <div class="form-panel" id="loginPanel">
                    <form id="loginForm" onsubmit="handleLogin(event)" novalidate>

                        <div class="input-group">
                            <input type="email" id="loginEmail" placeholder=" " required autocomplete="email">
                            <i class="fas fa-envelope input-icon"></i>
                            <label class="floating-label" for="loginEmail">Email Address</label>
                            <span class="val-msg" id="loginEmailMsg"></span>
                        </div>

                        <div class="input-group">
                            <input type="password" id="loginPassword" placeholder=" " required autocomplete="current-password">
                            <i class="fas fa-lock input-icon"></i>
                            <label class="floating-label" for="loginPassword">Password</label>
                            <button type="button" class="pw-toggle" onclick="togglePw('loginPassword', this)" aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                            <span class="val-msg" id="loginPwMsg"></span>
                        </div>

                        <div class="extras-row">
                            <label class="check-row">
                                <input type="checkbox">
                                <span class="custom-check"><i class="fas fa-check"></i></span>
                                Remember me
                            </label>
                            <a href="#" class="forgot-link" onclick="showToast('Check your email for reset instructions.', 'info'); return false;">Forgot Password?</a>
                        </div>

                        <button type="submit" class="submit-btn" id="loginBtn">
                            SIGN IN
                            <div class="spinner">
                                <span class="spin-dot"></span>
                                <span class="spin-dot"></span>
                                <span class="spin-dot"></span>
                            </div>
                            <div class="check-icon"><i class="fas fa-check" style="color:#fff;font-size:20px;"></i></div>
                        </button>
                    </form>

                    <div class="divider"><span>or continue with</span></div>

                    <div class="social-row">
                        <button class="social-btn" onclick="showToast('Google auth is not yet connected.', 'info')" aria-label="Sign in with Google">
                            <i class="fab fa-google"></i>
                        </button>
                        <button class="social-btn" onclick="showToast('Facebook auth is not yet connected.', 'info')" aria-label="Sign in with Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </button>
                        <button class="social-btn" onclick="showToast('GitHub auth is not yet connected.', 'info')" aria-label="Sign in with GitHub">
                            <i class="fab fa-github"></i>
                        </button>
                    </div>
                </div>

                <!-- SIGNUP FORM -->
                <div class="form-panel hidden" id="signupPanel">
                    <form id="signupForm" onsubmit="handleSignup(event)" novalidate>

                        <div class="input-group">
                            <input type="text" id="signupName" placeholder=" " required autocomplete="name">
                            <i class="fas fa-user input-icon"></i>
                            <label class="floating-label" for="signupName">Full Name</label>
                            <span class="val-msg" id="signupNameMsg"></span>
                        </div>

                        <div class="input-group">
                            <input type="email" id="signupEmail" placeholder=" " required autocomplete="email">
                            <i class="fas fa-envelope input-icon"></i>
                            <label class="floating-label" for="signupEmail">Email Address</label>
                            <span class="val-msg" id="signupEmailMsg"></span>
                        </div>

                        <div class="input-group">
                            <input type="password" id="signupPassword" placeholder=" " required autocomplete="new-password" oninput="checkStrength(this.value)">
                            <i class="fas fa-lock input-icon"></i>
                            <label class="floating-label" for="signupPassword">Password</label>
                            <button type="button" class="pw-toggle" onclick="togglePw('signupPassword', this)" aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                            <span class="val-msg" id="signupPwMsg"></span>
                        </div>

                        <div class="pw-strength" id="pwStrength">
                            <div class="bar" id="bar1"></div>
                            <div class="bar" id="bar2"></div>
                            <div class="bar" id="bar3"></div>
                            <div class="bar" id="bar4"></div>
                        </div>
                        <div class="pw-label" id="pwLabel"></div>

                        <div class="input-group">
                            <input type="password" id="signupConfirm" placeholder=" " required autocomplete="new-password">
                            <i class="fas fa-shield-halved input-icon"></i>
                            <label class="floating-label" for="signupConfirm">Confirm Password</label>
                            <button type="button" class="pw-toggle" onclick="togglePw('signupConfirm', this)" aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                            <span class="val-msg" id="signupConfirmMsg"></span>
                        </div>

                        <label class="check-row" style="margin-bottom:14px;">
                            <input type="checkbox" id="agreeTerms" required>
                            <span class="custom-check"><i class="fas fa-check"></i></span>
                            I agree to the Terms & Conditions
                        </label>

                        <button type="submit" class="submit-btn" id="signupBtn">
                            CREATE ACCOUNT
                            <div class="spinner">
                                <span class="spin-dot"></span>
                                <span class="spin-dot"></span>
                                <span class="spin-dot"></span>
                            </div>
                            <div class="check-icon"><i class="fas fa-check" style="color:#fff;font-size:20px;"></i></div>
                        </button>
                    </form>

                    <div class="divider"><span>or sign up with</span></div>

                    <div class="social-row">
                        <button class="social-btn" onclick="showToast('Google auth is not yet connected.', 'info')" aria-label="Sign up with Google">
                            <i class="fab fa-google"></i>
                        </button>
                        <button class="social-btn" onclick="showToast('Facebook auth is not yet connected.', 'info')" aria-label="Sign up with Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </button>
                        <button class="social-btn" onclick="showToast('GitHub auth is not yet connected.', 'info')" aria-label="Sign up with GitHub">
                            <i class="fab fa-github"></i>
                        </button>
                    </div>
                </div>

            </div><!-- /form-container -->
        </div><!-- /auth-card -->
    </div><!-- /auth-wrapper -->

    <script>
        /* ========================================
           PARTICLES — floating green dots
        ======================================== */
        (function initParticles() {
            const layer = document.getElementById('particleLayer');
            const count = 30;
            for (let i = 0; i < count; i++) {
                const p = document.createElement('div');
                p.classList.add('particle');
                const size = Math.random() * 3 + 1.5;
                p.style.width = size + 'px';
                p.style.height = size + 'px';
                p.style.left = Math.random() * 100 + '%';
                p.style.bottom = '-5%';
                p.style.animationDuration = (Math.random() * 8 + 6) + 's';
                p.style.animationDelay = (Math.random() * 10) + 's';
                layer.appendChild(p);
            }
        })();

        /* ========================================
           TAB SWITCHING
        ======================================== */
        let currentTab = 'login';

        function switchTab(target) {
            if (target === currentTab) return;

            const slider = document.getElementById('tabSlider');
            const loginTab = document.getElementById('tabLogin');
            const signupTab = document.getElementById('tabSignup');
            const loginPanel = document.getElementById('loginPanel');
            const signupPanel = document.getElementById('signupPanel');

            /* Update tab button active states */
            loginTab.classList.toggle('active', target === 'login');
            signupTab.classList.toggle('active', target === 'signup');

            /* Slide the indicator */
            slider.classList.toggle('right', target === 'signup');

            /* Animate panels */
            const outDir = target === 'signup' ? 'slide-out-left' : 'slide-out-right';
            const inDir  = target === 'signup' ? 'slide-in-right' : 'slide-in-left';

            const leaving = target === 'signup' ? loginPanel : signupPanel;
            const entering = target === 'signup' ? signupPanel : loginPanel;

            leaving.classList.add(outDir);

            leaving.addEventListener('animationend', function handler() {
                leaving.removeEventListener('animationend', handler);
                leaving.classList.add('hidden');
                leaving.classList.remove(outDir);

                entering.classList.remove('hidden');
                entering.classList.add(inDir);

                entering.addEventListener('animationend', function h2() {
                    entering.removeEventListener('animationend', h2);
                    entering.classList.remove(inDir);
                });
            });

            currentTab = target;
        }

        /* ========================================
           PASSWORD VISIBILITY TOGGLE
        ======================================== */
        function togglePw(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        /* ========================================
           PASSWORD STRENGTH METER
        ======================================== */
        function checkStrength(val) {
            const bars = [document.getElementById('bar1'), document.getElementById('bar2'),
                          document.getElementById('bar3'), document.getElementById('bar4')];
            const label = document.getElementById('pwLabel');
            const container = document.getElementById('pwStrength');

            /* Reset */
            bars.forEach(b => b.className = 'bar');
            label.textContent = '';

            if (!val) { container.classList.remove('visible'); return; }
            container.classList.add('visible');

            let score = 0;
            if (val.length >= 6) score++;
            if (val.length >= 10) score++;
            if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
            if (/\d/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            /* Clamp 1-4 */
            const level = Math.min(4, Math.max(1, score));

            const classes = ['weak', 'weak', 'medium', 'strong', 'strong'];
            const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
            const colors = ['', 'var(--danger)', 'var(--warning)', 'var(--success)', 'var(--success)'];

            for (let i = 0; i < level; i++) {
                bars[i].classList.add(classes[level]);
            }
            label.textContent = labels[level];
            label.style.color = colors[level];
        }

        /* ========================================
           VALIDATION HELPERS
        ======================================== */
        function showVal(id, msg, type) {
            const el = document.getElementById(id);
            el.textContent = msg;
            el.className = 'val-msg show ' + type;
        }

        function clearVal(id) {
            const el = document.getElementById(id);
            el.textContent = '';
            el.className = 'val-msg';
        }

        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        /* ========================================
           RIPPLE EFFECT ON BUTTONS
        ======================================== */
        document.querySelectorAll('.submit-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const rect = this.getBoundingClientRect();
                const ripple = document.createElement('span');
                ripple.classList.add('ripple');
                const size = Math.max(rect.width, rect.height);
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
                ripple.style.top  = (e.clientY - rect.top  - size / 2) + 'px';
                this.appendChild(ripple);
                ripple.addEventListener('animationend', () => ripple.remove());
            });
        });

        /* ========================================
           LOGIN HANDLER
        ======================================== */
        function handleLogin(e) {
            e.preventDefault();
            let valid = true;

            const email = document.getElementById('loginEmail').value.trim();
            const pw    = document.getElementById('loginPassword').value;

            /* Email validation */
            if (!email) {
                showVal('loginEmailMsg', 'Email is required.', 'error');
                valid = false;
            } else if (!isValidEmail(email)) {
                showVal('loginEmailMsg', 'Enter a valid email address.', 'error');
                valid = false;
            } else {
                clearVal('loginEmailMsg');
            }

            /* Password validation */
            if (!pw) {
                showVal('loginPwMsg', 'Password is required.', 'error');
                valid = false;
            } else if (pw.length < 6) {
                showVal('loginPwMsg', 'At least 6 characters required.', 'error');
                valid = false;
            } else {
                clearVal('loginPwMsg');
            }

            if (!valid) return;

            /* Simulate loading */
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');

            setTimeout(() => {
                btn.classList.remove('loading');
                btn.classList.add('success');
                showToast('Welcome back! Redirecting...', 'success');

                setTimeout(() => {
                    btn.classList.remove('success');
                    /* Redirect: window.location.href = 'dashboard.php'; */
                }, 1800);
            }, 2000);
        }

        /* ========================================
           SIGNUP HANDLER
        ======================================== */
        function handleSignup(e) {
            e.preventDefault();
            let valid = true;

            const name    = document.getElementById('signupName').value.trim();
            const email   = document.getElementById('signupEmail').value.trim();
            const pw      = document.getElementById('signupPassword').value;
            const confirm = document.getElementById('signupConfirm').value;
            const agreed  = document.getElementById('agreeTerms').checked;

            /* Name */
            if (!name) {
                showVal('signupNameMsg', 'Full name is required.', 'error');
                valid = false;
            } else if (name.length < 2) {
                showVal('signupNameMsg', 'Name must be at least 2 characters.', 'error');
                valid = false;
            } else {
                clearVal('signupNameMsg');
            }

            /* Email */
            if (!email) {
                showVal('signupEmailMsg', 'Email is required.', 'error');
                valid = false;
            } else if (!isValidEmail(email)) {
                showVal('signupEmailMsg', 'Enter a valid email address.', 'error');
                valid = false;
            } else {
                clearVal('signupEmailMsg');
            }

            /* Password */
            if (!pw) {
                showVal('signupPwMsg', 'Password is required.', 'error');
                valid = false;
            } else if (pw.length < 6) {
                showVal('signupPwMsg', 'At least 6 characters required.', 'error');
                valid = false;
            } else {
                clearVal('signupPwMsg');
            }

            /* Confirm */
            if (!confirm) {
                showVal('signupConfirmMsg', 'Please confirm your password.', 'error');
                valid = false;
            } else if (confirm !== pw) {
                showVal('signupConfirmMsg', 'Passwords do not match.', 'error');
                valid = false;
            } else {
                clearVal('signupConfirmMsg');
            }

            /* Terms */
            if (!agreed) {
                showToast('You must agree to the Terms & Conditions.', 'error');
                valid = false;
            }

            if (!valid) return;

            /* Simulate loading */
            const btn = document.getElementById('signupBtn');
            btn.classList.add('loading');

            setTimeout(() => {
                btn.classList.remove('loading');
                btn.classList.add('success');
                showToast('Account created! Redirecting...', 'success');

                setTimeout(() => {
                    btn.classList.remove('success');
                    /* Redirect: window.location.href = 'dashboard.php'; */
                }, 1800);
            }, 2200);
        }

        /* ========================================
           TOAST NOTIFICATIONS
        ======================================== */
        function showToast(message, type) {
            const box = document.getElementById('toastBox');
            const toast = document.createElement('div');
            toast.classList.add('toast', type || 'info');

            const icons = { success: 'fa-circle-check', error: 'fa-circle-xmark', info: 'fa-circle-info' };
            toast.innerHTML = '<i class="fas ' + (icons[type] || icons.info) + '"></i><span>' + message + '</span>';

            box.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('removing');
                toast.addEventListener('animationend', () => toast.remove());
            }, 3500);
        }

        /* ========================================
           MOUSE GLOW on card (subtle)
        ======================================== */
        const card = document.getElementById('authCard');
        card.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            this.style.background =
                'radial-gradient(600px circle at ' + x + 'px ' + y + 'px, rgba(0,200,83,0.06), transparent 40%), ' +
                'rgba(15, 23, 42, 0.7)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.background = 'rgba(15, 23, 42, 0.7)';
        });

        /* ========================================
           KEYBOARD: Enter on password submits
        ======================================== */
        document.getElementById('loginPassword').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') document.getElementById('loginForm').requestSubmit();
        });
        document.getElementById('signupConfirm').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') document.getElementById('signupForm').requestSubmit();
        });
    </script>
</body>
</html>