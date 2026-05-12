<?php 
session_start(); 
require_once 'db_conn.php';

 $error = '';
 $success = '';
 $valid_token = false;

 $token = $_GET['token'] ?? '';

if (empty($token)) {
    die("<div style='color:#ff4757;text-align:center;margin-top:20vh;font-family:sans-serif'>Invalid or missing token.</div>");
}

// Verify token is valid and not expired
 $stmt = $pdo->prepare("SELECT * FROM tbl_users WHERE reset_token = ? AND reset_expires > NOW()");
 $stmt->execute([$token]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("<div style='color:#ff4757;text-align:center;margin-top:20vh;font-family:sans-serif'>This reset link is invalid or has expired. <br><a href='forgot_password.php' style='color:#00c853'>Try again</a></div>");
}

 $valid_token = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    if (empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash the new password
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password and clear the token so it can't be reused
        $upd = $pdo->prepare("UPDATE tbl_users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE user_id = ?");
        $upd->execute([$hashed, $user['user_id']]);
        
        $success = "Your password has been updated successfully! You may now login.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrantGate | Set New Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { --green: #00c853; --green-glow: rgba(0,200,83,0.4); --dark-bg: #0f172a; --darker: #0b1121; --fg: #ffffff; --muted: #64748b; --input-bg: rgba(255,255,255,0.05); --input-border: rgba(255,255,255,0.1); --input-focus: rgba(0,200,83,0.25); --danger: #ff4757; }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Poppins',sans-serif;background:var(--dark-bg);color:var(--fg);min-height:100vh;display:flex;align-items:center;justify-content:center}
        .form-container{background:var(--darker);border:1px solid rgba(255,255,255,0.06);border-radius:20px;padding:40px;width:100%;max-width:440px;box-shadow:0 20px 60px rgba(0,0,0,0.5)}
        .logo{font-family:'Orbitron',sans-serif;font-size:1.5rem;font-weight:900;text-align:center;margin-bottom:8px}
        .logo span{color:var(--green)}
        .subtitle{text-align:center;color:var(--muted);font-size:14px;margin-bottom:32px}
        .field{position:relative;margin-bottom:20px}
        .field label{display:block;font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px}
        .field input{width:100%;padding:13px 16px;background:var(--input-bg);border:1.5px solid var(--input-border);border-radius:10px;color:var(--fg);font-family:'Poppins',sans-serif;font-size:14px;outline:none;transition:all 0.3s}
        .field input:focus{border-color:var(--green);background:rgba(0,200,83,0.05);box-shadow:0 0 0 3px var(--input-focus)}
        .btn{width:100%;padding:14px;border-radius:10px;font-family:'Orbitron',sans-serif;font-size:0.85rem;font-weight:700;cursor:pointer;border:none;background:var(--green);color:#fff;box-shadow:0 4px 20px var(--green-glow);transition:all 0.3s;letter-spacing:0.05em;display:flex;align-items:center;justify-content:center;gap:8px}
        .btn:hover{transform:translateY(-2px);box-shadow:0 8px 30px var(--green-glow)}
        .message{text-align:center;padding:12px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:20px;line-height:1.6}
        .message.error{background:rgba(255,71,87,0.1);border:1px solid rgba(255,71,87,0.2);color:#fca5a5}
        .message.success{background:rgba(0,200,83,0.1);border:1px solid rgba(0,200,83,0.2);color:#a7f3d0}
        .back-link{display:block;text-align:center;margin-top:24px;color:var(--muted);font-size:13px;text-decoration:none;transition:color 0.3s}
        .back-link:hover{color:var(--green)}
    </style>
</head>
<body>
    <div class="form-container">
        <div class="logo">GRANT<span>GATE</span></div>
        <p class="subtitle">Set your new password</p>

        <?php if($error): ?>
            <div class="message error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="message success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
            <a href="register.php" class="btn" style="margin-top: 10px; text-decoration:none;"><i class="fas fa-sign-in-alt"></i> Login Now</a>
        <?php else: ?>
        <form method="POST">
            <div class="field">
                <label>New Password</label>
                <input type="password" name="password" placeholder="Min. 6 characters" required>
            </div>
            <div class="field">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" placeholder="Re-enter password" required>
            </div>
            <button type="submit" class="btn"><i class="fas fa-key"></i> Update Password</button>
        </form>
        <?php endif; ?>

        <a href="register.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Login</a>
    </div>
</body>
</html>