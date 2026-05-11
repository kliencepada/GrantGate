<?php 
session_start(); 
require_once 'db_conn.php';

// ===== BACKEND LOGIC FOR AJAX =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    try {
                if ($action === 'login') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            $stmt = $pdo->prepare("SELECT * FROM tbl_users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Support both password_hash() and legacy plain text
                $passwordValid = false;
                if (password_verify($password, $user['password'])) {
                    $passwordValid = true;
                } elseif ($user['password'] === $password) {
                    // Legacy plain text match — upgrade it to hashed
                    $passwordValid = true;
                    $upgrade = $pdo->prepare("UPDATE tbl_users SET password = ? WHERE user_id = ?");
                    $upgrade->execute([password_hash($password, PASSWORD_DEFAULT), $user['user_id']]);
                }

                if ($passwordValid) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['firstname'] = $user['firstname'] ?? '';
                    $_SESSION['lastname'] = $user['lastname'] ?? '';
                    echo json_encode(['status' => 'success', 'message' => 'Welcome back! Redirecting...']);
                    exit;
                }
            }

            // If we reach here, login failed
            echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
            exit;
        }

        if ($action === 'signup') {
            $firstname = trim($_POST['firstname'] ?? '');
            $lastname = trim($_POST['lastname'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm'] ?? '';

            if ($password !== $confirm) {
                echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT user_id FROM tbl_users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Email already exists.']);
                exit;
            }

            // Hash the password securely before saving
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO tbl_users (firstname, lastname, email, password, created_at) VALUES (?, ?, ?, ?, NOW())");
            if ($stmt->execute([$firstname, $lastname, $email, $hashedPassword])) {
                echo json_encode(['status' => 'success', 'message' => 'Account created! Redirecting...']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Something went wrong. Please try again.']);
            }
            exit;
        }
    } catch (Exception $e) {
        // If there's a database error, it returns it as a JSON message instead of breaking the JS
        echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrantGate | Login / Sign Up</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --green: #00c853;
            --green-glow: rgba(0, 200, 83, 0.4);
            --green-dim: rgba(0, 200, 83, 0.1);
            --dark-bg: #0f172a;
            --darker: #0b1121;
            --card-bg: rgba(15, 23, 42, 0.85);
            --fg: #ffffff;
            --muted: #64748b;
            --input-bg: rgba(255, 255, 255, 0.05);
            --input-border: rgba(255, 255, 255, 0.1);
            --input-focus: rgba(0, 200, 83, 0.25);
            --danger: #ff4757;
            --warning: #ffc93c;
            --success: #00c853;
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

        /* ===== BACK BUTTON ===== */
        .back-btn-top {
            position: fixed; top: 24px; left: 28px; z-index: 10000;
            display: flex; align-items: center; gap: 10px;
            padding: 12px 28px; border-radius: 50px;
            background: var(--green); border: 2px solid var(--green);
            color: #fff; font-family: 'Poppins', sans-serif;
            font-size: 15px; font-weight: 700;
            cursor: pointer; text-decoration: none;
            transition: all 0.35s cubic-bezier(0.22, 1, 0.36, 1);
            opacity: 0; transform: translateX(-30px);
            animation: backBtnIn 0.6s 0.4s cubic-bezier(0.22, 1, 0.36, 1) forwards;
            box-shadow: 0 4px 20px var(--green-glow);
        }
        @keyframes backBtnIn { to { opacity: 1; transform: translateX(0); } }
        .back-btn-top i { font-size: 16px; transition: transform 0.3s; }
        .back-btn-top:hover {
            background: #fff; border-color: #fff; color: var(--green);
            transform: translateX(-4px) !important;
            box-shadow: 0 4px 30px rgba(255,255,255,0.3);
        }
        .back-btn-top:hover i { transform: translateX(-4px); }
        .back-btn-top:active { transform: scale(0.96) !important; }

        /* Page exit */
        .page-exit .auth-container { animation: pageExit 0.5s cubic-bezier(0.22,1,0.36,1) forwards; }
        .page-exit .back-btn-top { animation: backBtnOut 0.3s forwards; }
        .page-exit .auth-logo { animation: logoOut 0.3s forwards; }
        @keyframes pageExit { to { opacity: 0; transform: translateY(40px) scale(0.96); } }
        @keyframes backBtnOut { to { opacity: 0; transform: translateX(-30px); } }
        @keyframes logoOut { to { opacity: 0; transform: translateY(-20px); } }

        /* ===== BACKGROUND ===== */
        .bg-blobs { position: fixed; inset: 0; z-index: 0; overflow: hidden; }
        .blob { position: absolute; border-radius: 50%; filter: blur(120px); opacity: 0.35; animation: blobDrift 16s ease-in-out infinite alternate; }
        .blob-1 { width: 600px; height: 600px; background: radial-gradient(circle, var(--green), transparent 70%); top: -20%; left: -15%; }
        .blob-2 { width: 500px; height: 500px; background: radial-gradient(circle, #00e676, transparent 70%); bottom: -20%; right: -10%; animation-delay: -5s; animation-duration: 20s; }
        .blob-3 { width: 350px; height: 350px; background: radial-gradient(circle, #1de9b6, transparent 70%); top: 40%; left: 50%; opacity: 0.15; animation-delay: -10s; animation-duration: 22s; }
        @keyframes blobDrift { 0%{transform:translate(0,0) scale(1)} 33%{transform:translate(60px,-50px) scale(1.1)} 66%{transform:translate(-40px,60px) scale(0.95)} 100%{transform:translate(50px,30px) scale(1.05)} }
        .grid-bg { position: fixed; inset: 0; z-index: 1; pointer-events: none; background-image: linear-gradient(rgba(0,200,83,0.015) 1px,transparent 1px), linear-gradient(90deg,rgba(0,200,83,0.015) 1px,transparent 1px); background-size: 70px 70px; }
        .particles { position: fixed; inset: 0; z-index: 1; pointer-events: none; }
        .dot { position: absolute; border-radius: 50%; background: var(--green); opacity: 0; animation: dotFloat linear infinite; }
        @keyframes dotFloat { 0%{opacity:0;transform:translateY(0) scale(0)} 8%{opacity:0.5;transform:scale(1)} 80%{opacity:0.3} 100%{opacity:0;transform:translateY(-100vh) scale(0.4)} }
        .scan-line { position: fixed; left: 0; width: 100%; height: 2px; z-index: 2; background: linear-gradient(90deg,transparent,var(--green),transparent); opacity: 0.1; pointer-events: none; animation: scanMove 6s linear infinite; }
        @keyframes scanMove { 0%{top:-2px} 100%{top:100%} }

        /* ===== LOGO ===== */
        .auth-logo { position: fixed; top: 24px; right: 32px; z-index: 10000; font-family: 'Orbitron', sans-serif; font-size: 1.4rem; font-weight: 900; color: #fff; opacity: 0; animation: logoIn 0.6s 0.3s ease forwards; }
        .auth-logo span { color: var(--green); }
        @keyframes logoIn { from{opacity:0;transform:translateY(-20px)} to{opacity:1;transform:translateY(0)} }

        /* ===== AUTH CARD ===== */
        .auth-container {
            position: relative; z-index: 10;
            width: 900px; max-width: 95vw;
            opacity: 0; transform: translateY(40px) scale(0.96);
            animation: containerIn 0.8s 0.2s cubic-bezier(0.22,1,0.36,1) forwards;
        }
        @keyframes containerIn { to { opacity: 1; transform: translateY(0) scale(1); } }

        .auth-card {
            position: relative; overflow: hidden;
            border-radius: 20px;
            background: var(--card-bg);
            backdrop-filter: blur(40px) saturate(1.5);
            border: 1px solid rgba(255,255,255,0.06);
            box-shadow: 0 25px 80px rgba(0,0,0,0.5);
        }

        .corner { position: absolute; width: 40px; height: 40px; pointer-events: none; opacity: 0.3; }
        .corner.tl { top:0;left:0;border-top:2px solid var(--green);border-left:2px solid var(--green);border-radius:20px 0 0 0 }
        .corner.tr { top:0;right:0;border-top:2px solid var(--green);border-right:2px solid var(--green);border-radius:0 20px 0 0 }
        .corner.bl { bottom:0;left:0;border-bottom:2px solid var(--green);border-left:2px solid var(--green);border-radius:0 0 0 20px }
        .corner.br { bottom:0;right:0;border-bottom:2px solid var(--green);border-right:2px solid var(--green);border-radius:0 0 20px 0 }

        /* ===== FORMS WRAPPER ===== */
        .forms-wrapper {
            display: flex;
            width: 200%;
            transition: transform 0.7s cubic-bezier(0.22,1,0.36,1);
        }
        .forms-wrapper.signup-active { transform: translateX(-50%); }

        .form-panel {
            width: 50%;
            min-height: 580px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 50px 40px;
        }

        /* ===== OVERLAY ===== */
        .overlay-container {
            position: absolute;
            top: 0; left: 50%;
            width: 50%; height: 100%;
            overflow: hidden; z-index: 5;
            transition: transform 0.7s cubic-bezier(0.22,1,0.36,1);
        }
        .overlay-container.shifted { transform: translateX(-100%); }

        .overlay {
            position: absolute; inset: 0;
            background: linear-gradient(135deg, #00c853, #00e676, #1de9b6);
            display: flex; align-items: center; justify-content: center;
            padding: 50px;
            transition: transform 0.7s cubic-bezier(0.22,1,0.36,1);
        }
        .overlay-left { transform: translateX(0%); }
        .overlay-right { transform: translateX(0); }
        .overlay-container.shifted .overlay-left { transform: translateX(100%); }
        .overlay-container.shifted .overlay-right { transform: translateX(0%); }

        .overlay::before { content:''; position:absolute; width:500px; height:500px; border-radius:50%; background:rgba(255,255,255,0.08); top:-150px; right:-150px; }
        .overlay::after { content:''; position:absolute; width:300px; height:300px; border-radius:50%; background:rgba(255,255,255,0.06); bottom:-80px; left:-80px; }

        .overlay-content { text-align:center; z-index:2; animation: overlayFade 0.5s ease forwards; }
        @keyframes overlayFade { from{opacity:0;transform:scale(0.9)} to{opacity:1;transform:scale(1)} }
        .overlay-content h2 { font-family:'Orbitron',sans-serif; font-size:2rem; font-weight:900; color:#fff; margin-bottom:16px; text-shadow:0 2px 20px rgba(0,0,0,0.15); }
        .overlay-content p { color:rgba(255,255,255,0.85); font-size:0.95rem; line-height:1.6; margin-bottom:30px; max-width:280px; margin-left:auto; margin-right:auto; }

        .ghost-btn {
            display:inline-flex; align-items:center; gap:10px;
            background:transparent; border:2px solid #fff; color:#fff;
            padding:14px 36px; border-radius:12px;
            font-family:'Orbitron',sans-serif; font-size:0.85rem; font-weight:700;
            cursor:pointer; letter-spacing:0.08em;
            transition:all 0.35s cubic-bezier(0.22,1,0.36,1);
            position:relative; overflow:hidden;
        }
        .ghost-btn::before { content:''; position:absolute; inset:0; background:#fff; transform:scaleX(0); transform-origin:left; transition:transform 0.4s cubic-bezier(0.22,1,0.36,1); z-index:-1; }
        .ghost-btn:hover { color:var(--dark-bg); box-shadow:0 8px 30px rgba(0,0,0,0.2); }
        .ghost-btn:hover::before { transform:scaleX(1); }
        .ghost-btn:active { transform:scale(0.96); }
        .ghost-btn i { transition:transform 0.3s; }
        .ghost-btn:hover i { transform:translateX(4px); }

        /* ===== FORM STYLES ===== */
        .form-inner { width:100%; max-width:340px; }
        .form-title { font-family:'Orbitron',sans-serif; font-size:1.6rem; font-weight:900; color:var(--fg); margin-bottom:8px; }
        .form-title .green { color:var(--green); }
        .form-sub { color:var(--muted); font-size:0.85rem; margin-bottom:30px; line-height:1.5; }

        .inp-group { position:relative; margin-bottom:22px; }
        .inp-group input { width:100%; padding:15px 16px 15px 46px; background:var(--input-bg); border:1.5px solid var(--input-border); border-radius:12px; color:var(--fg); font-family:'Poppins',sans-serif; font-size:14px; outline:none; transition:all 0.35s cubic-bezier(0.22,1,0.36,1); }
        .inp-group input::placeholder { color:transparent; }
        .inp-group input:focus { border-color:var(--green); background:var(--green-dim); box-shadow:0 0 0 4px var(--input-focus),0 4px 20px rgba(0,200,83,0.06); }
        .inp-group .inp-icon { position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:15px; transition:color 0.3s,transform 0.3s; z-index:2; }
        .inp-group input:focus ~ .inp-icon { color:var(--green); transform:translateY(-50%) scale(1.15); }
        .inp-group .float-label { position:absolute; left:46px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:14px; pointer-events:none; transition:all 0.3s cubic-bezier(0.22,1,0.36,1); padding:0 5px; }
        .inp-group input:focus ~ .float-label, .inp-group input:not(:placeholder-shown) ~ .float-label { top:0; left:14px; font-size:10px; font-weight:600; color:var(--green); background:linear-gradient(to bottom,transparent 48%,rgba(15,23,42,0.95) 48%); letter-spacing:0.08em; text-transform:uppercase; }

        .pw-eye { position:absolute; right:14px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--muted); cursor:pointer; font-size:14px; transition:color 0.3s; z-index:2; }
        .pw-eye:hover { color:var(--fg); }

        .inp-group .vmsg { position:absolute; bottom:-16px; left:14px; font-size:10px; font-weight:500; opacity:0; transform:translateY(-3px); transition:all 0.3s; }
        .inp-group .vmsg.show { opacity:1; transform:translateY(0); }
        .vmsg.err { color:var(--danger); }
        .vmsg.ok { color:var(--success); }

        .pw-bars { display:flex; gap:4px; margin-top:8px; opacity:0; transition:opacity 0.3s; }
        .pw-bars.on { opacity:1; }
        .pw-bars .bar { flex:1; height:3px; border-radius:3px; background:rgba(255,255,255,0.06); transition:background 0.4s; }
        .pw-bars .bar.w { background:var(--danger); }
        .pw-bars .bar.m { background:var(--warning); }
        .pw-bars .bar.s { background:var(--success); }
        .pw-lbl { font-size:10px; color:var(--muted); min-height:14px; margin-bottom:6px; transition:color 0.3s; }

        .chk-row { display:flex; align-items:center; gap:8px; cursor:pointer; font-size:12px; color:var(--muted); user-select:none; margin-bottom:6px; }
        .chk-row input { display:none; }
        .chk-box { width:18px; height:18px; border:1.5px solid var(--input-border); border-radius:5px; display:flex; align-items:center; justify-content:center; transition:all 0.3s cubic-bezier(0.22,1,0.36,1); flex-shrink:0; }
        .chk-box i { font-size:9px; color:#fff; opacity:0; transform:scale(0); transition:all 0.35s cubic-bezier(0.22,1,0.36,1); }
        .chk-row input:checked ~ .chk-box { background:var(--green); border-color:var(--green); box-shadow:0 0 12px var(--green-glow); }
        .chk-row input:checked ~ .chk-box i { opacity:1; transform:scale(1); }

        .forgot { font-size:12px; color:var(--green); text-decoration:none; font-weight:500; transition:opacity 0.3s; }
        .forgot:hover { opacity:0.75; }
        .form-extras { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }

        .submit-btn { width:100%; padding:15px; background:var(--green); border:none; border-radius:12px; color:#fff; font-family:'Orbitron',sans-serif; font-size:0.85rem; font-weight:700; cursor:pointer; position:relative; overflow:hidden; letter-spacing:0.1em; margin-top:8px; transition:all 0.35s cubic-bezier(0.22,1,0.36,1); }
        .submit-btn:hover { transform:translateY(-3px); box-shadow:0 8px 35px var(--green-glow); }
        .submit-btn:active { transform:translateY(0) scale(0.97); }
        .submit-btn .rip { position:absolute; border-radius:50%; background:rgba(255,255,255,0.3); transform:scale(0); pointer-events:none; animation:ripOut 0.6s ease-out; }
        @keyframes ripOut { to{transform:scale(4);opacity:0} }
        .submit-btn.loading { pointer-events:none; color:transparent; }
        .submit-btn .spin { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity 0.3s; }
        .submit-btn.loading .spin { opacity:1; }
        .sdot { width:7px; height:7px; background:#fff; border-radius:50%; margin:0 4px; animation:sdotBounce 0.6s ease-in-out infinite; }
        .sdot:nth-child(2) { animation-delay:0.12s; }
        .sdot:nth-child(3) { animation-delay:0.24s; }
        @keyframes sdotBounce { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-10px)} }
        .submit-btn.done { background:var(--success); color:transparent; }
        .submit-btn .chk-icon { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; opacity:0; transform:scale(0); transition:all 0.4s cubic-bezier(0.22,1,0.36,1); }
        .submit-btn.done .chk-icon { opacity:1; transform:scale(1); }

        .divider { display:flex; align-items:center; gap:14px; margin:22px 0; }
        .divider::before,.divider::after { content:''; flex:1; height:1px; background:var(--input-border); }
        .divider span { color:var(--muted); font-size:10px; text-transform:uppercase; letter-spacing:0.12em; font-weight:600; }

        .social-row { display:flex; gap:10px; }
        .soc-btn { flex:1; padding:12px; background:var(--input-bg); border:1.5px solid var(--input-border); border-radius:10px; color:var(--fg); font-size:16px; cursor:pointer; transition:all 0.3s cubic-bezier(0.22,1,0.36,1); display:flex; align-items:center; justify-content:center; }
        .soc-btn:hover { border-color:rgba(0,200,83,0.3); transform:translateY(-2px); box-shadow:0 4px 16px rgba(0,0,0,0.3); }
        .soc-btn:active { transform:translateY(0) scale(0.96); }

        /* TOAST */
        .toast-box { position:fixed; top:24px; right:24px; z-index:9999; display:flex; flex-direction:column; gap:10px; }
        .toast { padding:14px 22px; border-radius:12px; font-size:13px; font-weight:500; backdrop-filter:blur(24px); border:1px solid rgba(255,255,255,0.08); display:flex; align-items:center; gap:10px; min-width:240px; box-shadow:0 8px 30px rgba(0,0,0,0.4); animation:toastIn 0.4s cubic-bezier(0.22,1,0.36,1) forwards; }
        .toast.success { background:rgba(0,200,83,0.15); border-color:rgba(0,200,83,0.3); color:#a7f3d0; }
        .toast.error { background:rgba(255,71,87,0.15); border-color:rgba(255,71,87,0.3); color:#fca5a5; }
        .toast.info { background:rgba(96,165,250,0.15); border-color:rgba(96,165,250,0.3); color:#bfdbfe; }
        .toast.out { animation:toastOut 0.35s forwards; }
        @keyframes toastIn { from{transform:translateX(120%);opacity:0} to{transform:translateX(0);opacity:1} }
        @keyframes toastOut { to{transform:translateX(120%);opacity:0} }

        /* Fix form alignment to prevent overlay overlap */
#loginPanel {
    padding: 50px 25% 50px 0; /* 25% of the 200% wrapper equals exactly half the card */
}
#signupPanel {
    padding: 50px 0 50px 25%; /* Reverses the alignment for the signup side */
}

        /* RESPONSIVE */
        @media (max-width: 800px) {
            .overlay-container { display:none; }
            .form-panel { width:100%; min-height:auto; padding:40px 24px; }
            .forms-wrapper { width:100%; flex-direction:column; }
            .forms-wrapper.signup-active { transform:translateY(-50%); }
            .form-panel { width:100%; }
            .auth-container { width:95vw; max-width:440px; }
            .mobile-toggle { display:flex !important; }
            .back-btn-top { top:16px; left:16px; padding:10px 20px; font-size:13px; }
        }
        @media (min-width: 801px) { .mobile-toggle { display:none !important; } }
        .mobile-toggle { position:absolute; bottom:16px; left:50%; transform:translateX(-50%); z-index:20; gap:8px; }
        .mob-tab { padding:8px 20px; border-radius:8px; border:1px solid var(--input-border); background:var(--input-bg); color:var(--muted); font-size:12px; font-weight:600; cursor:pointer; font-family:'Orbitron',sans-serif; letter-spacing:0.05em; transition:all 0.3s; }
        .mob-tab.active { background:var(--green); color:#fff; border-color:var(--green); }
    </style>
</head>
<body>

    <div class="bg-blobs"><div class="blob blob-1"></div><div class="blob blob-2"></div><div class="blob blob-3"></div></div>
    <div class="grid-bg"></div>
    <div class="particles" id="particleLayer"></div>
    <div class="scan-line"></div>

    <a href="landing_page.php" class="back-btn-top" id="backBtn">
        <i class="fas fa-arrow-left"></i> Back
    </a>

    <div class="auth-logo">GRANT<span>GATE</span></div>
    <div class="toast-box" id="toastBox"></div>

    <div class="auth-container" id="authContainer">
        <div class="auth-card">
            <div class="corner tl"></div><div class="corner tr"></div><div class="corner bl"></div><div class="corner br"></div>

            <div class="forms-wrapper" id="formsWrapper">

                <!-- LOGIN -->
                <div class="form-panel" id="loginPanel">
                    <div class="form-inner">
                        <div class="form-title">Welcome <span class="green">Back</span></div>
                        <div class="form-sub">Sign in to continue your application</div>
                        <form id="loginForm" onsubmit="handleLogin(event)" novalidate>
                            <input type="hidden" name="action" value="login">
                            <div class="inp-group">
                                <input type="email" id="lEmail" name="email" placeholder=" " required autocomplete="email">
                                <i class="fas fa-envelope inp-icon"></i>
                                <label class="float-label" for="lEmail">Email Address</label>
                                <span class="vmsg" id="lEmailMsg"></span>
                            </div>
                            <div class="inp-group">
                                <input type="password" id="lPw" name="password" placeholder=" " required autocomplete="current-password">
                                <i class="fas fa-lock inp-icon"></i>
                                <label class="float-label" for="lPw">Password</label>
                                <button type="button" class="pw-eye" onclick="togglePw('lPw',this)"><i class="fas fa-eye"></i></button>
                                <span class="vmsg" id="lPwMsg"></span>
                            </div>
                            <div class="form-extras">
                                <label class="chk-row"><input type="checkbox"><span class="chk-box"><i class="fas fa-check"></i></span>Remember me</label>
                                <a href="#" class="forgot" onclick="showToast('Check your email for reset instructions.','info');return false;">Forgot Password?</a>
                            </div>
                            <button type="submit" class="submit-btn" id="loginBtn">LOGIN NOW<div class="spin"><span class="sdot"></span><span class="sdot"></span><span class="sdot"></span></div><div class="chk-icon"><i class="fas fa-check" style="color:#fff;font-size:18px"></i></div></button>
                        </form>
                        <!-- <div class="divider"><span>or</span></div>
                        <div class="social-row">
                            <button class="soc-btn" onclick="showToast('Google auth not connected yet.','info')"><i class="fab fa-google"></i></button>
                            <button class="soc-btn" onclick="showToast('Facebook auth not connected yet.','info')"><i class="fab fa-facebook-f"></i></button>
                            <button class="soc-btn" onclick="showToast('GitHub auth not connected yet.','info')"><i class="fab fa-github"></i></button>
                        </div> -->
                    </div>
                </div>

                <!-- SIGNUP -->
                <div class="form-panel" id="signupPanel">
                    <div class="form-inner">
                        <div class="form-title">Create <span class="green">Account</span></div>
                        <div class="form-sub">Register and start your application today</div>
                        <form id="signupForm" onsubmit="handleSignup(event)" novalidate>
                            <input type="hidden" name="action" value="signup">
                            <div class="inp-group">
                                <input type="text" id="sFirstname" name="firstname" placeholder=" " required autocomplete="given-name">
                                <i class="fas fa-user inp-icon"></i>
                                <label class="float-label" for="sFirstname">First Name</label>
                                <span class="vmsg" id="sFirstnameMsg"></span>
                            </div>
                            <div class="inp-group">
                                <input type="text" id="sLastname" name="lastname" placeholder=" " required autocomplete="family-name">
                                <i class="fas fa-user inp-icon"></i>
                                <label class="float-label" for="sLastname">Last Name</label>
                                <span class="vmsg" id="sLastnameMsg"></span>
                            </div>
                            <div class="inp-group">
                                <input type="email" id="sEmail" name="email" placeholder=" " required autocomplete="email">
                                <i class="fas fa-envelope inp-icon"></i>
                                <label class="float-label" for="sEmail">Email Address</label>
                                <span class="vmsg" id="sEmailMsg"></span>
                            </div>
                            <div class="inp-group">
                                <input type="password" id="sPw" name="password" placeholder=" " required autocomplete="new-password" oninput="checkStr(this.value)">
                                <i class="fas fa-lock inp-icon"></i>
                                <label class="float-label" for="sPw">Password</label>
                                <button type="button" class="pw-eye" onclick="togglePw('sPw',this)"><i class="fas fa-eye"></i></button>
                                <span class="vmsg" id="sPwMsg"></span>
                            </div>
                            <div class="pw-bars" id="pwBars"><div class="bar" id="b1"></div><div class="bar" id="b2"></div><div class="bar" id="b3"></div><div class="bar" id="b4"></div></div>
                            <div class="pw-lbl" id="pwLbl"></div>
                            <div class="inp-group">
                                <input type="password" id="sConf" name="confirm" placeholder=" " required autocomplete="new-password">
                                <i class="fas fa-shield-halved inp-icon"></i>
                                <label class="float-label" for="sConf">Confirm Password</label>
                                <button type="button" class="pw-eye" onclick="togglePw('sConf',this)"><i class="fas fa-eye"></i></button>
                                <span class="vmsg" id="sConfMsg"></span>
                            </div>
                            <!-- ... rest of signup form ... -->
                            <label class="chk-row" style="margin-bottom:10px"><input type="checkbox" id="agreeChk" required><span class="chk-box"><i class="fas fa-check"></i></span>I agree to the Terms & Conditions</label>
                            <button type="submit" class="submit-btn" id="signupBtn">SIGN UP<div class="spin"><span class="sdot"></span><span class="sdot"></span><span class="sdot"></span></div><div class="chk-icon"><i class="fas fa-check" style="color:#fff;font-size:18px"></i></div></button>
                        </form>
                        <!-- <div class="divider"><span>or</span></div>
                        <div class="social-row">
                            <button class="soc-btn" onclick="showToast('Google auth not connected yet.','info')"><i class="fab fa-google"></i></button>
                            <button class="soc-btn" onclick="showToast('Facebook auth not connected yet.','info')"><i class="fab fa-facebook-f"></i></button>
                            <button class="soc-btn" onclick="showToast('GitHub auth not connected yet.','info')"><i class="fab fa-github"></i></button>
                        </div> -->
                    </div>
                </div>

            </div>

            <!-- GREEN OVERLAY -->
            <div class="overlay-container" id="overlayContainer">
                <div class="overlay overlay-left" id="overlayLeft">
                    <div class="overlay-content">
                        <h2>New Here?</h2>
                        <p>Register and start your scholarship application today. It's quick and free.</p>
                        <button class="ghost-btn" onclick="switchTo('signup')">SIGN UP <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
                <div class="overlay overlay-right" id="overlayRight">
                    <div class="overlay-content">
                        <h2>Welcome Back!</h2>
                        <p>Already have an account? Sign in to track your application status.</p>
                        <button class="ghost-btn" onclick="switchTo('login')"><i class="fas fa-arrow-left"></i> LOGIN</button>
                    </div>
                </div>
            </div>

            <!-- Mobile toggle -->
            <div class="mobile-toggle" id="mobToggle">
                <button class="mob-tab active" id="mobLogin" onclick="switchTo('login')">LOGIN</button>
                <button class="mob-tab" id="mobSignup" onclick="switchTo('signup')">SIGN UP</button>
            </div>

        </div>
    </div>

    <script>
        /* Particles */
        (function(){const l=document.getElementById('particleLayer');for(let i=0;i<25;i++){const d=document.createElement('div');d.classList.add('dot');const s=Math.random()*3+1.5;d.style.width=s+'px';d.style.height=s+'px';d.style.left=Math.random()*100+'%';d.style.bottom='-5%';d.style.animationDuration=(Math.random()*10+7)+'s';d.style.animationDelay=(Math.random()*12)+'s';l.appendChild(d);}})();

        /* Back button with exit animation */
        document.getElementById('backBtn').addEventListener('click', function(e) {
            e.preventDefault();
            document.body.classList.add('page-exit');
            setTimeout(function() { window.location.href = 'landing_page.php'; }, 500);
        });

        /* Panel switching */
        let currentPanel = 'login';
        function switchTo(panel) {
            if (panel === currentPanel) return;
            const w = document.getElementById('formsWrapper');
            const o = document.getElementById('overlayContainer');
            const oL = document.getElementById('overlayLeft');
            const oR = document.getElementById('overlayRight');
            const mL = document.getElementById('mobLogin');
            const mS = document.getElementById('mobSignup');
            if (panel === 'signup') {
                w.classList.add('signup-active');
                o.classList.add('shifted');
                oL.style.display = 'none';
                oR.style.display = 'flex';
                mL.classList.remove('active');
                mS.classList.add('active');
            } else {
                w.classList.remove('signup-active');
                o.classList.remove('shifted');
                oL.style.display = 'flex';
                oR.style.display = 'none';
                mL.classList.add('active');
                mS.classList.remove('active');
            }
            const aO = panel === 'signup' ? oR : oL;
            const c = aO.querySelector('.overlay-content');
            c.style.animation = 'none';
            c.offsetHeight;
            c.style.animation = 'overlayFade 0.5s ease forwards';
            currentPanel = panel;
        }
        document.getElementById('overlayRight').style.display = 'none';

        /* Password toggle */
        function togglePw(id, btn) {
            const inp = document.getElementById(id);
            const ico = btn.querySelector('i');
            if (inp.type === 'password') { inp.type = 'text'; ico.classList.replace('fa-eye','fa-eye-slash'); }
            else { inp.type = 'password'; ico.classList.replace('fa-eye-slash','fa-eye'); }
        }

        /* Password strength */
        function checkStr(v) {
            const bars = [document.getElementById('b1'),document.getElementById('b2'),document.getElementById('b3'),document.getElementById('b4')];
            const lbl = document.getElementById('pwLbl');
            const ct = document.getElementById('pwBars');
            bars.forEach(b => b.className = 'bar');
            lbl.textContent = '';
            if (!v) { ct.classList.remove('on'); return; }
            ct.classList.add('on');
            let sc = 0;
            if (v.length >= 6) sc++;
            if (v.length >= 10) sc++;
            if (/[A-Z]/.test(v) && /[a-z]/.test(v)) sc++;
            if (/\d/.test(v)) sc++;
            if (/[^A-Za-z0-9]/.test(v)) sc++;
            const lv = Math.min(4, Math.max(1, sc));
            const cls = ['','w','w','m','s'];
            const txts = ['','Weak','Fair','Good','Strong'];
            const cols = ['','var(--danger)','var(--danger)','var(--warning)','var(--success)'];
            for (let i = 0; i < lv; i++) bars[i].classList.add(cls[lv]);
            lbl.textContent = txts[lv]; lbl.style.color = cols[lv];
        }

        /* Validation */
        function showV(id,m,t) { const e=document.getElementById(id); e.textContent=m; e.className='vmsg show '+t; }
        function clearV(id) { const e=document.getElementById(id); e.textContent=''; e.className='vmsg'; }
        function isEmail(e) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e); }

        /* Ripple */
        document.querySelectorAll('.submit-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const r = this.getBoundingClientRect();
                const rip = document.createElement('span');
                rip.classList.add('rip');
                const sz = Math.max(r.width, r.height);
                rip.style.width = rip.style.height = sz + 'px';
                rip.style.left = (e.clientX - r.left - sz/2) + 'px';
                rip.style.top = (e.clientY - r.top - sz/2) + 'px';
                this.appendChild(rip);
                rip.addEventListener('animationend', () => rip.remove());
            });
        });

                /* Login */
        async function handleLogin(e) {
            e.preventDefault();
            let ok = true;
            const em = document.getElementById('lEmail').value.trim();
            const pw = document.getElementById('lPw').value;
            if (!em) { showV('lEmailMsg','Email is required.','err'); ok=false; }
            else if (!isEmail(em)) { showV('lEmailMsg','Enter a valid email.','err'); ok=false; }
            else clearV('lEmailMsg');
            if (!pw) { showV('lPwMsg','Password is required.','err'); ok=false; }
            else if (pw.length < 6) { showV('lPwMsg','At least 6 characters.','err'); ok=false; }
            else clearV('lPwMsg');
            if (!ok) return;

            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');

            try {
                const formData = new FormData(document.getElementById('loginForm'));
                const response = await fetch('register.php', { method: 'POST', body: formData });
                const text = await response.text();
                try { var data = JSON.parse(text); } catch(e) { throw new Error("Server: " + text.substring(0,150)); }

                btn.classList.remove('loading');
                if (data.status === 'success') {
                    btn.classList.add('done'); 
                    showToast(data.message, 'success'); 
                    setTimeout(() => { window.location.href = 'dashboard.php'; }, 1500); // Change dashboard.php to your target page
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                btn.classList.remove('loading');
                showToast('An error occurred. Please try again.', 'error');
            }
        }

        /* Signup */
        async function handleSignup(e) {
            e.preventDefault();
            let ok = true;
            const fn = document.getElementById('sFirstname').value.trim();
            const ln = document.getElementById('sLastname').value.trim();
            const em = document.getElementById('sEmail').value.trim();
            const pw = document.getElementById('sPw').value;
            const cf = document.getElementById('sConf').value;
            const ag = document.getElementById('agreeChk').checked;
            if (!fn) { showV('sFirstnameMsg','First name is required.','err'); ok=false; }
            else if (fn.length < 2) { showV('sFirstnameMsg','At least 2 characters.','err'); ok=false; }
            else clearV('sFirstnameMsg');
            if (!ln) { showV('sLastnameMsg','Last name is required.','err'); ok=false; }
            else if (ln.length < 2) { showV('sLastnameMsg','At least 2 characters.','err'); ok=false; }
            else clearV('sLastnameMsg');
            if (!em) { showV('sEmailMsg','Email is required.','err'); ok=false; }
            else if (!isEmail(em)) { showV('sEmailMsg','Enter a valid email.','err'); ok=false; }
            else clearV('sEmailMsg');
            if (!pw) { showV('sPwMsg','Password is required.','err'); ok=false; }
            else if (pw.length < 6) { showV('sPwMsg','At least 6 characters.','err'); ok=false; }
            else clearV('sPwMsg');
            if (!cf) { showV('sConfMsg','Confirm your password.','err'); ok=false; }
            else if (cf !== pw) { showV('sConfMsg','Passwords do not match.','err'); ok=false; }
            else clearV('sConfMsg');
            if (!ag) { showToast('You must agree to the Terms & Conditions.','error'); ok=false; }
            if (!ok) return;

            const btn = document.getElementById('signupBtn');
            btn.classList.add('loading');

            try {
                const formData = new FormData(document.getElementById('signupForm'));
                const response = await fetch('register.php', { method: 'POST', body: formData });
                const text = await response.text();
                try { var data = JSON.parse(text); } catch(e) { throw new Error("Server: " + text.substring(0,150)); }

                btn.classList.remove('loading');
                if (data.status === 'success') {
                    btn.classList.add('done'); 
                    showToast(data.message, 'success'); 
                    setTimeout(() => { window.location.href = 'register.php'; }, 1500); // Change dashboard.php to your target page
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                btn.classList.remove('loading');
                showToast('An error occurred. Please try again.', 'error');
            }
        }

        /* Toast */
        function showToast(msg, type) {
            const box = document.getElementById('toastBox');
            const t = document.createElement('div');
            t.classList.add('toast', type || 'info');
            const icons = { success:'fa-circle-check', error:'fa-circle-xmark', info:'fa-circle-info' };
            t.innerHTML = '<i class="fas '+(icons[type]||icons.info)+'"></i><span>'+msg+'</span>';
            box.appendChild(t);
            setTimeout(() => { t.classList.add('out'); t.addEventListener('animationend', () => t.remove()); }, 3500);
        }
    </script>
</body>
</html>