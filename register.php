<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrantGate | Auth</title>
    <style>
        @property --angle { syntax: "<angle>"; initial-value: 0deg; inherits: false; }
        
        body { 
            background: #0f172a; 
            display: flex; 
            flex-direction: column;
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
            font-family: 'Poppins', sans-serif; 
            overflow: hidden;
        }

        /* Alert Message Style */
        .alert-message {
            position: absolute;
            top: 30px;
            background: #ff00ff;
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: bold;
            box-shadow: 0 0 20px #ff00ff;
            z-index: 1000;
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .main-container {
            display: flex;
            gap: 50px;
            justify-content: center;
            align-items: center;
            width: 100%;
        }

        .card {
            background: #1e293b;
            border-radius: 15px;
            position: relative;
            width: 200px; 
            height: 80px;
            transition: 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            display: flex; 
            justify-content: center; 
            align-items: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }

        .card:hover { width: 350px; height: 550px; }

        /* NEON BORDER - EQUAL & FADED */
        .card::before {
            content: ''; position: absolute; inset: -4px; border-radius: 19px; z-index: -1;
            background: conic-gradient(from var(--angle), #00ffff 0deg, transparent 90deg, #ff00ff 180deg, transparent 270deg, #00ffff 360deg);
            animation: spin 4s linear infinite;
            transition: 0.5s ease;
        }

        .card:hover::before {
            background: conic-gradient(from var(--angle), #00ffff 0deg, transparent 45deg, #ff00ff 90deg, transparent 135deg, #00ff00 180deg, transparent 225deg, #ffff00 270deg, transparent 315deg, #00ffff 360deg);
            animation: spin 3s linear infinite;
        }

        @keyframes spin { from { --angle: 0deg; } to { --angle: 360deg; } }

        .hover-text { 
            font-weight: bold; color: #00ffff; text-shadow: 0 0 15px #00ffff; 
            letter-spacing: 2px; transition: 0.3s; position: absolute; width: 100%; text-align: center;
        }
        .card:hover .hover-text { opacity: 0; visibility: hidden; }

        form { 
            opacity: 0; width: 100%; height: 100%; transition: 0.4s ease; 
            pointer-events: none; display: flex; flex-direction: column; 
            align-items: center; justify-content: center; box-sizing: border-box; padding: 30px;
        }
        .card:hover form { opacity: 1; pointer-events: all; transition-delay: 0.3s; }

        h2 { color: white; margin-bottom: 25px; text-align: center; }
        input, button { width: 90%; padding: 14px; border-radius: 8px; box-sizing: border-box; font-family: inherit; }
        input { margin: 10px 0; background: #0f172a; border: 1px solid #334155; color: white; outline: none; }
        input:focus { border-color: #00ffff; box-shadow: 0 0 10px #00ffff; }
        button { background: #00ffff; border: none; cursor: pointer; font-weight: bold; margin-top: 20px; color: #0f172a; transition: 0.3s; text-transform: uppercase; }
        button:hover { background: #ff00ff; color: white; box-shadow: 0 0 20px #ff00ff; }
    </style>
</head>
<body>

    <?php if(isset($_SESSION['message'])): ?>
        <div class="alert-message"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php endif; ?>

    <div class="main-container">
        <div class="card">
            <div class="hover-text">LOGIN</div>
            <form action="login_logic.php" method="POST">
                <h2>Login</h2>
                <input type="email" name="email" placeholder="Email Address" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login_btn">Login Now</button>
            </form>
        </div>

        <div class="card">
            <div class="hover-text">REGISTER</div>
            <form action="register_logic.php" method="POST">
                <h2>Sign up</h2>
                <input type="text" name="name" placeholder="Full Name" required>
                <input type="text" name="school" placeholder="School" required>
                <input type="email" name="email" placeholder="Email Address" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="register_btn">Register Now</button>
            </form>
        </div>
    </div>
</body>
</html>