<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrantGate | Admission</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Poppins:wght@300;400;600;900&display=swap" rel="stylesheet">
    <style>
        :root { --primary-green: #00c853; --dark-bg: #111111; }
        @property --angle { syntax: "<angle>"; initial-value: 0deg; inherits: false; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body { background-color: var(--dark-bg); height: 100vh; overflow: hidden; position: relative; }

        /* --- LANDING PAGE --- */
        nav { position: absolute; top: 0; width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 30px 8%; z-index: 100; transition: 0.5s; }
        .logo { font-size: 1.5rem; font-weight: 900; color: white; }
        .white-bg-layer { position: absolute; background-color: white; width: 180vh; height: 130vh; border-radius: 50%; left: -35vh; top: 20vh; z-index: 1; transition: 0.8s ease; }
        .content-box { position: absolute; left: 10%; top: 60%; transform: translateY(-50%); width: 450px; z-index: 10; transition: 0.5s; }
        .content-box h1 { font-size: 4.8rem; font-weight: 900; line-height: 0.9; color: #111; margin-bottom: 20px; }
        .photo-circle { position: absolute; right: 5%; top: 70%; transform: translateY(-50%); width: 75vh; height: 75vh; border-radius: 50%; background-color: var(--primary-green); z-index: 2; border: 15px solid var(--dark-bg); transition: 0.8s; }

        /* --- AUTH MASTER --- */
        .auth-master-wrapper {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0);
            z-index: 500; opacity: 0; visibility: hidden;
            transition: all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        .show-auth .auth-master-wrapper { transform: translate(-50%, -50%) scale(1); opacity: 1; visibility: visible; }
        .show-auth nav, .show-auth .white-bg-layer, .show-auth .content-box, .show-auth .photo-circle { opacity: 0; visibility: hidden; }

        /* --- CONTAINER & SLIDE DESIGN --- */
        .container {
            background: #1e293b; border-radius: 20px; position: relative;
            width: 850px; min-height: 550px; overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
        }
        .container::before {
            content: ''; position: absolute; inset: -2px; border-radius: 22px; z-index: 0;
            background: conic-gradient(from var(--angle), #00ffff, transparent, #ff00ff, transparent, #00ffff);
            animation: spin 4s linear infinite;
        }
        @keyframes spin { from { --angle: 0deg; } to { --angle: 360deg; } }

        .inner { position: absolute; inset: 4px; background: #1e293b; border-radius: 18px; z-index: 1; overflow: hidden; }

        .form-container { position: absolute; top: 0; height: 100%; transition: all 0.6s ease-in-out; }
        .sign-in { left: 0; width: 50%; z-index: 2; }
        .sign-up { left: 0; width: 50%; opacity: 0; z-index: 1; }

        /* Animation States */
        .right-panel-active .sign-in { transform: translateX(100%); opacity: 0; }
        .right-panel-active .sign-up { transform: translateX(100%); opacity: 1; z-index: 5; }

        /* OVERLAY FIX (Kani ang nawala sa imong screenshot) */
        .overlay-container {
            position: absolute; top: 0; left: 50%; width: 50%; height: 100%;
            overflow: hidden; transition: transform 0.6s ease-in-out; z-index: 100;
        }
        .right-panel-active .overlay-container { transform: translateX(-100%); }

        .overlay {
            background: linear-gradient(135deg, #00ffff, #ff00ff);
            background-repeat: no-repeat; background-size: cover;
            color: #FFFFFF; position: relative; left: -100%;
            height: 100%; width: 200%; transform: translateX(0);
            transition: transform 0.6s ease-in-out;
        }
        .right-panel-active .overlay { transform: translateX(50%); }

        .overlay-panel {
            position: absolute; display: flex; align-items: center; justify-content: center;
            flex-direction: column; padding: 0 40px; text-align: center; top: 0; height: 100%; width: 50%;
            transition: transform 0.6s ease-in-out;
        }
        .overlay-left { transform: translateX(-20%); }
        .right-panel-active .overlay-left { transform: translateX(0); }
        .overlay-right { right: 0; transform: translateX(0); }
        .right-panel-active .overlay-right { transform: translateX(20%); }

        /* FORMS & BUTTONS */
        form { background: #1e293b; display: flex; align-items: center; justify-content: center; flex-direction: column; padding: 0 40px; height: 100%; }
        .input-group { position: relative; width: 100%; margin: 8px 0; }
        .input-group input { width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: white; outline: none; }
        .input-group label { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; transition: 0.3s; pointer-events: none; font-size: 0.8rem; }
        .input-group input:focus ~ label, .input-group input:valid ~ label { top: -2px; font-size: 10px; color: #00ffff; background: #1e293b; padding: 0 5px; }

        .btn-green { background: var(--primary-green); color: white; border: none; padding: 12px 35px; border-radius: 30px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        button.ghost { background: transparent; border: 1px solid #fff; color: #fff; border-radius: 30px; padding: 10px 30px; cursor: pointer; margin-top: 15px; font-weight: bold; }
        .close-btn { position: absolute; top: 20px; right: 20px; color: rgba(255,255,255,0.5); cursor: pointer; z-index: 1000; font-weight: bold; }
    </style>
</head>
<body id="mainBody">

    <nav><div class="logo">GranGate</div></nav>
    <div class="white-bg-layer"></div>
    <div class="content-box">
        <h1>GranGate</h1>
        <p>Student Application & Admin Dashboard</p>
        <button class="btn-green" style="border-radius: 12px 12px 40px 12px;" onclick="toggleAuth()">ENROLL NOW</button>
    </div>
    <div class="photo-circle"></div>

    <div class="auth-master-wrapper">
        <div class="container" id="container">
            <div class="close-btn" onclick="toggleAuth()">✕ CLOSE</div>
            <div class="inner">
                
                <div class="form-container sign-up">
                    <form action="register_logic.php" method="POST">
                        <h2 style="color:white; font-family:'Orbitron'; margin-bottom:15px;">Create Account</h2>
                        <div class="input-group"><input type="text" name="name" required><label>Full Name</label></div>
                        <div class="input-group"><input type="email" name="email" required><label>Email</label></div>
                        <div class="input-group"><input type="password" name="password" required><label>Password</label></div>
                        <button type="submit" name="register_btn" class="btn-green" style="margin-top:20px">Sign Up</button>
                    </form>
                </div>

                <div class="form-container sign-in">
                    <form action="login_logic.php" method="POST">
                        <h2 style="color:white; font-family:'Orbitron'; margin-bottom:15px;">Welcome Back</h2>
                        <div class="input-group"><input type="email" name="email" required><label>Email Address</label></div>
                        <div class="input-group"><input type="password" name="password" required><label>Password</label></div>
                        <button type="submit" name="login_btn" class="btn-green" style="margin-top:20px">Login Now</button>
                    </form>
                </div>

                <div class="overlay-container">
                    <div class="overlay">
                        <div class="overlay-panel overlay-left">
                            <h2 style="font-family:'Orbitron'">Glad to see you!</h2>
                            <p style="font-size: 0.8rem; margin: 15px 0;">To keep connected with us please login with your info</p>
                            <button class="ghost" id="signIn">Sign In</button>
                        </div>
                        <div class="overlay-panel overlay-right">
                            <h2 style="font-family:'Orbitron'">Hello, Student!</h2>
                            <p style="font-size: 0.8rem; margin: 15px 0;">Enter your personal details and start journey with us</p>
                            <button class="ghost" id="signUp">Sign Up</button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        const mainBody = document.getElementById('mainBody');
        const container = document.getElementById('container');
        const signUpBtn = document.getElementById('signUp');
        const signInBtn = document.getElementById('signIn');

        function toggleAuth() { mainBody.classList.toggle('show-auth'); }

        signUpBtn.addEventListener('click', () => { container.classList.add("right-panel-active"); });
        signInBtn.addEventListener('click', () => { container.classList.remove("right-panel-active"); });
    </script>
</body>
</html>