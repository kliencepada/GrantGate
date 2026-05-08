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
        
        * { box-sizing: border-box; }

        body { 
            background: #0f172a; 
            display: flex; flex-direction: column; justify-content: center; align-items: center; 
            min-height: 100vh; margin: 0; font-family: 'Poppins', sans-serif; overflow: hidden;
        }

        /* BRANDING */
        .branding { text-align: center; margin-bottom: 20px; }
        .branding h1 { 
            font-family: 'Orbitron', sans-serif; font-size: 2.5rem; color: #fff; 
            margin: 0; text-shadow: 0 0 15px rgba(0, 255, 255, 0.4); 
        }
        .branding h1 span { color: #00ffff; }

        /* ALERT POP-UP */
        .alert-message {
            position: absolute; top: 20px; background: #ff00ff; color: white;
            padding: 12px 25px; border-radius: 8px; font-weight: bold;
            box-shadow: 0 0 20px #ff00ff; z-index: 2000; animation: slideDown 0.5s ease;
        }

        /* MAIN SLIDING CONTAINER */
        .container {
            background: #1e293b; border-radius: 20px; position: relative;
            width: 800px; max-width: 95%; min-height: 550px;
            overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.5);
        }

        /* NEON BORDER EFFECT */
        .container::before {
            content: ''; position: absolute; inset: -2px; border-radius: 22px; z-index: 0;
            background: conic-gradient(from var(--angle), #00ffff 0deg, transparent 90deg, #ff00ff 180deg, transparent 270deg, #00ffff 360deg);
            animation: spin 4s linear infinite;
        }
        @keyframes spin { from { --angle: 0deg; } to { --angle: 360deg; } }

        .inner-content {
            position: absolute; inset: 4px; background: #1e293b; border-radius: 18px;
            z-index: 1; overflow: hidden;
        }

        .form-container {
            position: absolute; top: 0; height: 100%; transition: all 0.6s ease-in-out;
        }

        /* SIGN IN & SIGN UP LAYOUT */
        .sign-in-container { left: 0; width: 50%; z-index: 2; }
        .sign-up-container { left: 0; width: 50%; opacity: 0; z-index: 1; }

        .container.right-panel-active .sign-in-container { transform: translateX(100%); opacity: 0; }
        .container.right-panel-active .sign-up-container { transform: translateX(100%); opacity: 1; z-index: 5; }

        /* OVERLAY SYSTEM */
        .overlay-container {
            position: absolute; top: 0; left: 50%; width: 50%; height: 100%;
            overflow: hidden; transition: transform 0.6s ease-in-out; z-index: 100;
        }
        .container.right-panel-active .overlay-container { transform: translateX(-100%); }

        .overlay {
            background: linear-gradient(135deg, #00ffff, #ff00ff);
            color: #fff; position: relative; left: -100%; height: 100%; width: 200%;
            transform: translateX(0); transition: transform 0.6s ease-in-out;
        }
        .container.right-panel-active .overlay { transform: translateX(50%); }

        .overlay-panel {
            position: absolute; display: flex; align-items: center; justify-content: center;
            flex-direction: column; padding: 0 40px; text-align: center; top: 0; height: 100%; width: 50%;
            transition: transform 0.6s ease-in-out;
        }
        .overlay-left { transform: translateX(-20%); }
        .container.right-panel-active .overlay-left { transform: translateX(0); }
        .overlay-right { right: 0; transform: translateX(0); }
        .container.right-panel-active .overlay-right { transform: translateX(20%); }

        /* FORM ELEMENTS */
        form { background: #1e293b; display: flex; align-items: center; justify-content: center; flex-direction: column; padding: 0 40px; height: 100%; }
        h2 { color: white; font-family: 'Orbitron'; margin-bottom: 20px; }
        
        .input-group { position: relative; width: 100%; margin: 10px 0; }
        .input-group input { 
            width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; 
            border-radius: 8px; color: white; outline: none; 
        }
        .input-group label { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; transition: 0.3s; pointer-events: none; font-size: 0.9rem; }
        .input-group input:focus ~ label, .input-group input:valid ~ label { top: -2px; font-size: 10px; color: #00ffff; background: #1e293b; padding: 0 5px; }

        button { 
            border-radius: 30px; border: none; background: #00ffff; color: #0f172a; 
            padding: 12px 40px; font-weight: bold; cursor: pointer; text-transform: uppercase; margin-top: 15px;
            transition: 0.3s;
        }
        button:hover { box-shadow: 0 0 15px #00ffff; transform: scale(1.05); }
        button.ghost { background: transparent; border: 2px solid #fff; color: #fff; margin-top: 20px; }
        button.ghost:hover { background: #fff; color: #ff00ff; box-shadow: 0 0 15px #fff; }

    </style>
</head>
<body>

    <?php if(isset($_SESSION['message'])): ?>
        <div class="alert-message"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php endif; ?>

    <div class="branding">
        <h1>Grant<span>Gate</span></h1>
    </div>

    <div class="container" id="container">
        <div class="inner-content">
            
            <div class="form-container sign-up-container">
                <form action="register_logic.php" method="POST">
                    <h2>Create Account</h2>
                    <div class="input-group"><input type="text" name="name" required><label>Full Name</label></div>
                    <div class="input-group"><input type="text" name="school" required><label>School</label></div>
                    <div class="input-group"><input type="email" name="email" required><label>Email Address</label></div>
                    <div class="input-group"><input type="password" name="password" required><label>Password</label></div>
                    <button type="submit" name="register_btn">Sign Up</button>
                </form>
            </div>

            <div class="form-container sign-in-container">
                <form action="login_logic.php" method="POST">
                    <h2>Welcome Back</h2>
                    <div class="input-group"><input type="email" name="email" required><label>Email Address</label></div>
                    <div class="input-group"><input type="password" name="password" required><label>Password</label></div>
                    <button type="submit" name="login_btn">Login Now</button>
                </form>
            </div>

            <div class="overlay-container">
                <div class="overlay">
                    <div class="overlay-panel overlay-left">
                        <h2>Already a Member?</h2>
                        <p>Login to access your dashboard</p>
                        <button class="ghost" id="signIn">Sign In</button>
                    </div>
                    <div class="overlay-panel overlay-right">
                        <h2>New Here?</h2>
                        <p>Register and start your application today</p>
                        <button class="ghost" id="signUp">Sign Up</button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        const signUpButton = document.getElementById('signUp');
        const signInButton = document.getElementById('signIn');
        const container = document.getElementById('container');

        signUpButton.addEventListener('click', () => {
            container.classList.add("right-panel-active");
        });

        signInButton.addEventListener('click', () => {
            container.classList.remove("right-panel-active");
        });
    </script>

</body>
</html>