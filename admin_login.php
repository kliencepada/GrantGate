<?php
session_start();
require_once 'db_conn.php';

// If admin is already logged in, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit;
}

// Handle Login AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'admin_login') {
    header('Content-Type: application/json');
    ob_clean();
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Username and password are required.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM tbl_admin WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        $passwordValid = false;
        if ($admin) {
            if (password_verify($password, $admin['password'])) {
                $passwordValid = true;
            } elseif ($admin['password'] === $password) {
                // Legacy plain text match — upgrade it to hashed
                $passwordValid = true;
                $upgrade = $pdo->prepare("UPDATE tbl_admin SET password = ? WHERE admin_id = ?");
                $upgrade->execute([password_hash($password, PASSWORD_DEFAULT), $admin['admin_id']]);
            }
        }

        if ($passwordValid) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];
            echo json_encode(['status' => 'success', 'message' => 'Access granted! Redirecting...']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid username or password.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Server error.']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrantGate | Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --green: #00c853;
            --green-glow: rgba(0,200,83,0.4);
            --green-dim: rgba(0,200,83,0.1);
            --dark-bg: #0f172a;
            --darker: #0b1121;
            --card-bg: rgba(15,23,42,0.85);
            --fg: #ffffff;
            --muted: #64748b;
            --input-bg: rgba(255,255,255,0.05);
            --input-border: rgba(255,255,255,0.1);
            --input-focus: rgba(0,200,83,0.25);
            --danger: #ff4757;
            --success: #00c853;
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Poppins',sans-serif;background:var(--dark-bg);color:var(--fg);min-height:100vh;display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative}

        .bg-blobs{position:fixed;inset:0;z-index:0;overflow:hidden}
        .blob{position:absolute;border-radius:50%;filter:blur(120px);opacity:0.35;animation:blobDrift 16s ease-in-out infinite alternate}
        .blob-1{width:600px;height:600px;background:radial-gradient(circle,var(--green),transparent 70%);top:-20%;left:-15%}
        .blob-2{width:500px;height:500px;background:radial-gradient(circle,#00e676,transparent 70%);bottom:-20%;right:-10%;animation-delay:-5s;animation-duration:20s}
        .blob-3{width:350px;height:350px;background:radial-gradient(circle,#1de9b6,transparent 70%);top:40%;left:50%;opacity:0.15;animation-delay:-10s;animation-duration:22s}
        @keyframes blobDrift{0%{transform:translate(0,0) scale(1)}33%{transform:translate(60px,-50px) scale(1.1)}66%{transform:translate(-40px,60px) scale(0.95)}100%{transform:translate(50px,30px) scale(1.05)}}
        .grid-bg{position:fixed;inset:0;z-index:1;pointer-events:none;background-image:linear-gradient(rgba(0,200,83,0.015) 1px,transparent 1px),linear-gradient(90deg,rgba(0,200,83,0.015) 1px,transparent 1px);background-size:70px 70px}
        .scan-line{position:fixed;left:0;width:100%;height:2px;z-index:2;background:linear-gradient(90deg,transparent,var(--green),transparent);opacity:0.1;pointer-events:none;animation:scanMove 6s linear infinite}
        @keyframes scanMove{0%{top:-2px}100%{top:100%}}

        .login-container{position:relative;z-index:10;width:440px;max-width:95vw;opacity:0;transform:translateY(40px) scale(0.96);animation:containerIn 0.8s 0.2s cubic-bezier(0.22,1,0.36,1) forwards}
        @keyframes containerIn{to{opacity:1;transform:translateY(0) scale(1)}}

        .login-card{position:relative;overflow:hidden;border-radius:20px;background:var(--card-bg);backdrop-filter:blur(40px) saturate(1.5);border:1px solid rgba(255,255,255,0.06);box-shadow:0 25px 80px rgba(0,0,0,0.5);padding:48px 40px}
        .corner{position:absolute;width:40px;height:40px;pointer-events:none;opacity:0.3}
        .corner.tl{top:0;left:0;border-top:2px solid var(--green);border-left:2px solid var(--green);border-radius:20px 0 0 0}
        .corner.tr{top:0;right:0;border-top:2px solid var(--green);border-right:2px solid var(--green);border-radius:0 20px 0 0}
        .corner.bl{bottom:0;left:0;border-bottom:2px solid var(--green);border-left:2px solid var(--green);border-radius:0 0 0 20px}
        .corner.br{bottom:0;right:0;border-bottom:2px solid var(--green);border-right:2px solid var(--green);border-radius:0 0 20px 0}

        .login-header{text-align:center;margin-bottom:36px}
        .login-logo{font-family:'Orbitron',sans-serif;font-size:1.6rem;font-weight:900;color:#fff;margin-bottom:6px}
        .login-logo span{color:var(--green)}
        .admin-tag{display:inline-flex;align-items:center;gap:6px;background:rgba(0,200,83,0.1);border:1px solid rgba(0,200,83,0.25);color:var(--green);padding:5px 16px;border-radius:20px;font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;font-family:'Orbitron',sans-serif;margin-top:10px}
        .login-sub{color:var(--muted);font-size:0.85rem;margin-top:16px;line-height:1.5}

        .inp-group{position:relative;margin-bottom:24px}
        .inp-group input{width:100%;padding:15px 16px 15px 46px;background:var(--input-bg);border:1.5px solid var(--input-border);border-radius:12px;color:var(--fg);font-family:'Poppins',sans-serif;font-size:14px;outline:none;transition:all 0.35s cubic-bezier(0.22,1,0.36,1)}
        .inp-group input::placeholder{color:transparent}
        .inp-group input:focus{border-color:var(--green);background:var(--green-dim);box-shadow:0 0 0 4px var(--input-focus),0 4px 20px rgba(0,200,83,0.06)}
        .inp-group .inp-icon{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:15px;transition:color 0.3s,transform 0.3s;z-index:2}
        .inp-group input:focus~.inp-icon{color:var(--green);transform:translateY(-50%) scale(1.15)}
        .inp-group .float-label{position:absolute;left:46px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:14px;pointer-events:none;transition:all 0.3s cubic-bezier(0.22,1,0.36,1);padding:0 5px}
        .inp-group input:focus~.float-label,.inp-group input:not(:placeholder-shown)~.float-label{top:0;left:14px;font-size:10px;font-weight:600;color:var(--green);background:linear-gradient(to bottom,transparent 48%,rgba(15,23,42,0.95) 48%);letter-spacing:0.08em;text-transform:uppercase}

        .pw-eye{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;font-size:14px;transition:color 0.3s;z-index:2}
        .pw-eye:hover{color:var(--fg)}

        .submit-btn{width:100%;padding:15px;background:var(--green);border:none;border-radius:12px;color:#fff;font-family:'Orbitron',sans-serif;font-size:0.85rem;font-weight:700;cursor:pointer;position:relative;overflow:hidden;letter-spacing:0.1em;margin-top:8px;transition:all 0.35s cubic-bezier(0.22,1,0.36,1);display:flex;align-items:center;justify-content:center;gap:10px}
        .submit-btn:hover{transform:translateY(-3px);box-shadow:0 8px 35px var(--green-glow)}
        .submit-btn:active{transform:translateY(0) scale(0.97)}
        .submit-btn.loading{pointer-events:none;color:transparent}
        .submit-btn .spin{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity 0.3s}
        .submit-btn.loading .spin{opacity:1}
        .sdot{width:7px;height:7px;background:#fff;border-radius:50%;margin:0 4px;animation:sdotBounce 0.6s ease-in-out infinite}
        .sdot:nth-child(2){animation-delay:0.12s}
        .sdot:nth-child(3){animation-delay:0.24s}
        @keyframes sdotBounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}

        .back-link{display:flex;align-items:center;justify-content:center;gap:8px;margin-top:24px;text-decoration:none;color:var(--muted);font-size:13px;font-weight:500;transition:color 0.3s}
        .back-link:hover{color:var(--green)}

        .toast-box{position:fixed;top:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px}
        .toast{padding:14px 22px;border-radius:12px;font-size:13px;font-weight:500;backdrop-filter:blur(24px);border:1px solid rgba(255,255,255,0.08);display:flex;align-items:center;gap:10px;min-width:240px;box-shadow:0 8px 30px rgba(0,0,0,0.4);animation:toastIn 0.4s cubic-bezier(0.22,1,0.36,1) forwards}
        .toast.success{background:rgba(0,200,83,0.15);border-color:rgba(0,200,83,0.3);color:#a7f3d0}
        .toast.error{background:rgba(255,71,87,0.15);border-color:rgba(255,71,87,0.3);color:#fca5a5}
        .toast.info{background:rgba(96,165,250,0.15);border-color:rgba(96,165,250,0.3);color:#bfdbfe}
        .toast.out{animation:toastOut 0.35s forwards}
        @keyframes toastIn{from{transform:translateX(120%);opacity:0}to{transform:translateX(0);opacity:1}}
        @keyframes toastOut{to{transform:translateX(120%);opacity:0}}

        @media(max-width:480px){.login-card{padding:36px 24px}}
    </style>
</head>
<body>

    <div class="bg-blobs"><div class="blob blob-1"></div><div class="blob blob-2"></div><div class="blob blob-3"></div></div>
    <div class="grid-bg"></div>
    <div class="scan-line"></div>

    <div class="toast-box" id="toastBox"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="corner tl"></div><div class="corner tr"></div><div class="corner bl"></div><div class="corner br"></div>

            <div class="login-header">
                <div class="login-logo">GRANT<span>GATE</span></div>
                <div class="admin-tag"><i class="fas fa-shield-halved"></i> Admin Panel</div>
                <div class="login-sub">Sign in to manage scholarship applications</div>
            </div>

            <form id="adminLoginForm" onsubmit="handleLogin(event)" novalidate>
                <input type="hidden" name="action" value="admin_login">
                <div class="inp-group">
                    <input type="text" id="username" name="username" placeholder=" " required autocomplete="username">
                    <i class="fas fa-user-shield inp-icon"></i>
                    <label class="float-label" for="username">Username</label>
                </div>
                <div class="inp-group">
                    <input type="password" id="password" name="password" placeholder=" " required autocomplete="current-password">
                    <i class="fas fa-lock inp-icon"></i>
                    <label class="float-label" for="password">Password</label>
                    <button type="button" class="pw-eye" onclick="togglePw()"><i class="fas fa-eye"></i></button>
                </div>
                <button type="submit" class="submit-btn" id="loginBtn">
                    LOGIN
                    <div class="spin"><span class="sdot"></span><span class="sdot"></span><span class="sdot"></span></div>
                </button>
            </form>

            <a href="landing_page.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Homepage</a>
        </div>
    </div>

    <script>
        function togglePw() {
            const inp = document.getElementById('password');
            const ico = document.querySelector('.pw-eye i');
            if (inp.type === 'password') { inp.type = 'text'; ico.classList.replace('fa-eye','fa-eye-slash'); }
            else { inp.type = 'password'; ico.classList.replace('fa-eye-slash','fa-eye'); }
        }

        async function handleLogin(e) {
            e.preventDefault();
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');

            try {
                const formData = new FormData(document.getElementById('adminLoginForm'));
                const response = await fetch('admin_login.php', { method: 'POST', body: formData });
                const text = await response.text();
                let data;
                try { data = JSON.parse(text); } catch(e) { throw new Error("Server: " + text.substring(0,150)); }

                btn.classList.remove('loading');
                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    setTimeout(() => { window.location.href = 'admin_dashboard.php'; }, 1500);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                btn.classList.remove('loading');
                showToast('An error occurred. Please try again.', 'error');
            }
        }

        function showToast(msg, type) {
            const box = document.getElementById('toastBox');
            const t = document.createElement('div');
            t.classList.add('toast', type || 'info');
            const icons = {success:'fa-circle-check',error:'fa-circle-xmark',info:'fa-circle-info'};
            t.innerHTML = '<i class="fas '+(icons[type]||icons.info)+'"></i><span>'+msg+'</span>';
            box.appendChild(t);
            setTimeout(()=>{t.classList.add('out');t.addEventListener('animationend',()=>t.remove())},3500);
        }
    </script>
</body>
</html>