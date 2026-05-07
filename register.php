<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrantGate | Auth</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        @property --angle { syntax: "<angle>"; initial-value: 0deg; inherits: false; }
        
        body { 
            background: #0f172a; 
            display: flex; flex-direction: column; justify-content: center; align-items: center; 
            min-height: 100vh; margin: 0; font-family: 'Poppins', sans-serif; overflow-x: hidden;
        }

        /* BRANDING */
        .branding { text-align: center; margin-bottom: 30px; }
        .branding h1 { 
            font-family: 'Orbitron', sans-serif; font-size: 2.5rem; color: #fff; 
            margin: 0; text-shadow: 0 0 15px rgba(0, 255, 255, 0.4); 
        }
        .branding h1 span { color: #00ffff; }
        .branding p { color: #94a3b8; font-size: 0.9rem; letter-spacing: 2px; text-transform: uppercase; margin-top: 5px; }

        /* ALERT POP-UP */
        .alert-message {
            position: absolute; top: 20px; background: #ff00ff; color: white;
            padding: 12px 25px; border-radius: 8px; font-weight: bold;
            box-shadow: 0 0 20px #ff00ff; z-index: 1000; animation: slideDown 0.5s ease;
        }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-50px); } to { opacity: 1; transform: translateY(0); } }

        /* MAIN CONTAINER */
        .main-container { display: flex; gap: 40px; justify-content: center; align-items: center; width: 100%; }

        /* CARD DESIGN */
        .card {
            background: #1e293b; border-radius: 15px; position: relative;
            width: 180px; height: 70px; transition: 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer; display: flex; justify-content: center; align-items: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5); z-index: 1;
        }

        /* EXPANDED SIZE */
        .card:hover { width: 340px; height: 540px; cursor: default; }

        /* NEON SPINNING BORDER */
        .card::before {
            content: ''; position: absolute; inset: -3px; border-radius: 18px; z-index: -1;
            background: conic-gradient(from var(--angle), #00ffff 0deg, transparent 90deg, #ff00ff 180deg, transparent 270deg, #00ffff 360deg);
            animation: spin 4s linear infinite;
        }
        @keyframes spin { from { --angle: 0deg; } to { --angle: 360deg; } }

        /* DEAD CENTER TEXT FIX */
        .hover-text { 
            font-weight: bold; color: #00ffff; text-shadow: 0 0 10px #00ffff; 
            letter-spacing: 3px; text-align: center; 
            position: absolute; /* Gi-absolute para pabilin sa tunga maski naay form */
            top: 50%; left: 50%; transform: translate(-50%, -50%);
            transition: 0.3s; pointer-events: none;
        }
        .card:hover .hover-text { opacity: 0; visibility: hidden; }

        /* FORM STYLING */
        form { 
            opacity: 0; width: 100%; padding: 30px; 
            display: flex; flex-direction: column; align-items: center; 
            transition: 0.4s; pointer-events: none; box-sizing: border-box;
        }
        .card:hover form { opacity: 1; pointer-events: all; transition-delay: 0.3s; }

        form h2 { color: white; margin-bottom: 25px; text-align: center; font-size: 24px; width: 100%; }

        /* INPUT GROUPS */
        .input-group { position: relative; width: 100%; margin: 12px 0; }
        .input-group input { 
            width: 100%; padding: 14px; background: transparent; border: 1px solid #334155; 
            border-radius: 10px; color: white; outline: none; box-sizing: border-box;
        }
        .input-group label { 
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%); 
            color: #94a3b8; transition: 0.3s; background: #1e293b; padding: 0 5px; pointer-events: none;
        }
        .input-group input:focus ~ label, .input-group input:valid ~ label { 
            top: 0; font-size: 12px; color: #00ffff; text-shadow: 0 0 5px #00ffff; 
        }

        button { 
            width: 100%; padding: 14px; border-radius: 10px; border: none; 
            background: #00ffff; color: #0f172a; font-weight: bold; 
            cursor: pointer; margin-top: 20px; text-transform: uppercase; transition: 0.3s;
        }
        button:hover { background: #ff00ff; color: white; box-shadow: 0 0 15px #ff00ff; }

    </style>
</head>
<body>

    <?php if(isset($_SESSION['message'])): ?>
        <div class="alert-message"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php endif; ?>

    <div class="branding">
        <h1>Grant<span>Gate</span></h1>
        <p>Student Application & Admin Dashboard</p>
    </div>

    <div class="main-container">
        <div class="card">
            <div class="hover-text">LOGIN</div>
            <form action="login_logic.php" method="POST">
                <h2>Welcome Back</h2>
                <div class="input-group">
                    <input type="email" name="email" required>
                    <label>Email Address</label>
                </div>
                <div class="input-group">
                    <input type="password" name="password" required>
                    <label>Password</label>
                </div>
                <button type="submit" name="login_btn">Login Now</button>
            </form>
        </div>

        <div class="card">
            <div class="hover-text">REGISTER</div>
            <form action="register_logic.php" method="POST">
                <h2>Create Account</h2>
                <div class="input-group">
                    <input type="text" name="name" required>
                    <label>Full Name</label>
                </div>
                <div class="input-group">
                    <input type="text" name="school" required>
                    <label>School</label>
                </div>
                <div class="input-group">
                    <input type="email" name="email" required>
                    <label>Email Address</label>
                </div>
                <div class="input-group">
                    <input type="password" name="password" required>
                    <label>Password</label>
                </div>
                <button type="submit" name="register_btn">Sign Up</button>
            </form>
        </div>
    </div>

</body>
</html>