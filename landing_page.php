<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrantGate | Official Admission</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary-green: #00c853; 
            --dark-bg: #0f172a; 
            --white: #ffffff;
            --text-gray: #444;
        }

        @property --angle { syntax: "<angle>"; initial-value: 0deg; inherits: false; }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body { 
            background-color: var(--dark-bg); 
            height: 100vh; 
            overflow: hidden; 
            position: relative; 
            color: white; 
        }

        /* --- NAVIGATION & HEADER --- */
        header {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            display: flex;
            align-items: center;
            padding: 30px 8%;
            z-index: 10000;
        }

        .logo {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.8rem;
            font-weight: 900;
            color: #fff;
            position: absolute; 
            left: 8%;
        }

        .logo span { color: var(--primary-green); }

        .navbar {
            width: 100%;
            display: flex;
            justify-content: center;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 60px;
        }

        .nav-links li a {
            position: relative;
            font-size: 1.1rem;
            text-decoration: none;
            color: transparent;
            -webkit-text-stroke: 1px rgba(255, 255, 255, 0.4);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            transition: 0.5s;
        }

        .nav-links li a::before {
            content: attr(data-text);
            position: absolute;
            color: var(--primary-green);
            width: 0;
            overflow: hidden;
            transition: 0.5s;
            border-right: 2px solid var(--primary-green);
            -webkit-text-stroke: 1px var(--primary-green);
            white-space: nowrap;
        }

        .nav-links li a:hover::before {
            width: 100%;
            filter: drop-shadow(0 0 15px var(--primary-green)) drop-shadow(0 0 30px var(--primary-green));
        }

        /* --- LAYOUT LAYERS --- */
        .white-bg-layer { 
            position: absolute; 
            background: #ffffff; 
            width: 195vh; 
            height: 200vh; 
            border-radius: 50%; 
            left: -40vh; 
            top: 15vh; 
            z-index: 1; 
            transition: 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55); 
        }

        .content-box { 
            position: absolute; 
            left: 10%; 
            top: 55%; 
            transform: translateY(-50%); 
            width: 600px; 
            z-index: 10; 
            transition: 0.5s; 
        }

        .content-box h1 { font-size: 5rem; font-weight: 800; line-height: 1; color: #111; margin-bottom: 10px; text-transform: uppercase; }
        .content-box h1 span { color: var(--primary-green); display: block; font-size: 4rem; }
        .content-box p { color: var(--text-gray); font-size: 1.1rem; margin-bottom: 40px; }

        .photo-circle { 
            position: absolute; 
            right: -2%; 
            top: 80%; 
            transform: translateY(-50%); 
            width: 100vh; 
            height: 100vh; 
            border-radius: 50%; 
            background: var(--primary-green); 
            z-index: 2; 
            border: 20px solid var(--dark-bg); 
            transition: 0.8s; 
            background-image: url('library.png');
            background-size: cover;
            background-position: center;
            box-shadow: 0 0 50px rgba(0, 200, 83, 0.3);
        }

        .overlap-img {
            position: absolute;
            bottom: 0;
            width: auto;
            object-fit: contain;
            pointer-events: none;
            z-index: 10;
        }

        .pic-left { height: 140% !important; left: 35% !important; transform: translateX(-50%); }
        .pic-right { height: 190% !important; left: 70% !important; transform: translateX(-50%); z-index: 9; }

        /* --- AUTH WRAPPER --- */
        .auth-master-wrapper {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0);
            z-index: 500; opacity: 0; visibility: hidden;
            transition: all 0.7s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .show-auth .auth-master-wrapper { transform: translate(-50%, -50%) scale(1); opacity: 1; visibility: visible; }
        .show-auth .white-bg-layer { transform: scale(0); opacity: 0; }
        .show-auth .content-box, .show-auth .photo-circle, .show-auth header { opacity: 0; pointer-events: none; }

        .container {
            background: #52698f; border-radius: 25px; position: relative;
            width: 900px; min-height: 580px; overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,0.6);
        }

        .container::before {
            content: ''; position: absolute; inset: -4px; border-radius: 29px; z-index: 0;
            background: conic-gradient(from var(--angle), var(--primary-green), transparent, #00ffff, transparent, var(--primary-green));
            animation: spin 4s linear infinite;
        }

        @keyframes spin { from { --angle: 0deg; } to { --angle: 360deg; } }

        .inner { position: absolute; inset: 5px; background: #58698f; border-radius: 22px; z-index: 1; overflow: hidden; }

        .form-container { position: absolute; top: 0; height: 100%; transition: all 0.6s ease-in-out; width: 50%; }
        .sign-in { left: 0; z-index: 2; }
        .sign-up { left: 0; opacity: 0; z-index: 1; }

        .right-panel-active .sign-in { transform: translateX(100%); opacity: 0; }
        .right-panel-active .sign-up { transform: translateX(100%); opacity: 1; z-index: 5; }

        .overlay-container {
            position: absolute; top: 0; left: 50%; width: 50%; height: 100%;
            overflow: hidden; transition: transform 0.6s ease-in-out; z-index: 100;
        }
        .right-panel-active .overlay-container { transform: translateX(-100%); }

        .overlay {
            background: linear-gradient(135deg, var(--primary-green), #008f3a);
            color: #FFFFFF; position: relative; left: -100%; height: 100%; width: 200%;
            transition: transform 0.6s ease-in-out;
        }
        .right-panel-active .overlay { transform: translateX(50%); }

        .overlay-panel {
            position: absolute; display: flex; align-items: center; justify-content: center;
            flex-direction: column; padding: 0 50px; text-align: center; top: 0; height: 100%; width: 50%;
            transition: transform 0.6s ease-in-out;
        }
        .overlay-left { transform: translateX(-20%); }
        .right-panel-active .overlay-left { transform: translateX(0); }
        .overlay-right { right: 0; transform: translateX(0); }
        .right-panel-active .overlay-right { transform: translateX(20%); }

        form { background: #111827; display: flex; align-items: center; justify-content: center; flex-direction: column; padding: 0 50px; height: 100%; }
        .input-group { position: relative; width: 100%; margin: 10px 0; }
        .input-group input { width: 100%; padding: 14px; background: #1f2937; border: 1px solid #374151; border-radius: 12px; color: white; outline: none; }
        .input-group label { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; transition: 0.3s; pointer-events: none; }
        .input-group input:focus ~ label, .input-group input:valid ~ label { top: 0px; font-size: 11px; color: var(--primary-green); background: #111827; padding: 0 8px; }

        .btn-main { background: var(--primary-green); color: #000; border: none; padding: 16px 45px; border-radius: 15px 15px 45px 15px; font-weight: 800; cursor: pointer; transition: 0.3s; }
        button.ghost { background: transparent; border: 2px solid #fff; color: #fff; padding: 12px 40px; border-radius: 50px; cursor: pointer; margin-top: 20px; font-weight: bold; }
        .close-btn { position: absolute; top: 25px; right: 25px; color: #9ca3af; font-weight: 800; cursor: pointer; z-index: 1000; }
    </style>
</head>
<body id="mainBody">

    <header>
        <div class="logo">GRANT<span>GATE</span></div>
        <nav class="navbar">
            <ul class="nav-links">
                <li><a href="#" data-text="HOME">HOME</a></li>
                <li><a href="#" data-text="ABOUT US">ABOUT US</a></li>
                <li><a href="#" data-text="CONTACT US">CONTACT US</a></li>
            </ul>
        </nav>
    </header>

    <div class="white-bg-layer"></div>

    <div class="content-box">
        <h1>Welcome! <span>To GrantGate</span></h1>
        <p>Ready to level up? Start your application today.</p>
        <button class="btn-main" onclick="toggleAuth()">APPLY NOW!</button>
    </div>

    <div class="photo-circle">
        <img src="james.png" class="overlap-img pic-left">
        <img src="nes.png" class="overlap-img pic-right">
    </div>

    <div class="auth-master-wrapper">
        <div class="container" id="container">
            <div class="close-btn" onclick="toggleAuth()">✕ CLOSE</div>
            <div class="inner">
                
                <div class="form-container sign-up">
                    <form action="register_logic.php" method="POST">
                        <h2 style="font-family:'Orbitron'; margin-bottom:20px;">Create Account</h2>
                        <div class="input-group">
                            <input type="text" name="name" required>
                            <label>Full Name</label>
                        </div>
                        <div class="input-group">
                            <input type="email" name="email" required>
                            <label>Email Address</label>
                        </div>
                        <div class="input-group">
                            <input type="password" name="password" required>
                            <label>Password</label>
                        </div>
                        <button type="submit" name="register_btn" class="btn-main" style="border-radius:50px;">Sign Up</button>
                    </form>
                </div>

                <div class="form-container sign-in">
                    <form action="login_logic.php" method="POST">
                        <h2 style="font-family:'Orbitron'; margin-bottom:20px;">Welcome Back</h2>
                        <div class="input-group"><input type="email" name="email" required><label>Email Address</label></div>
                        <div class="input-group"><input type="password" name="password" required><label>Password</label></div>
                        <button type="submit" name="login_btn" class="btn-main" style="border-radius:50px;">Login Now</button>
                    </form>
                </div>

                <div class="overlay-container">
                    <div class="overlay">
                        <div class="overlay-panel overlay-left">
                            <h2 style="font-family:'Orbitron'">Already a Member?</h2>
                            <p style="margin:20px 0;">Login to access your dashboard</p>
                            <button class="ghost" id="signIn">Sign In</button>
                        </div>
                        <div class="overlay-panel overlay-right">
                            <h2 style="font-family:'Orbitron'">New Student?</h2>
                            <p style="margin:20px 0;">Register and start your journey</p>
                            <button class="ghost" id="signUp">Sign Up</button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const mainBody = document.getElementById('mainBody');
        const container = document.getElementById('container');
        const signUpBtn = document.getElementById('signUp');
        const signInBtn = document.getElementById('signIn');

        function toggleAuth() { mainBody.classList.toggle('show-auth'); }

        signUpBtn.addEventListener('click', () => { container.classList.add("right-panel-active"); });
        signInBtn.addEventListener('click', () => { container.classList.remove("right-panel-active"); });
    </script>

    <?php
    if (isset($_SESSION['status'])) {
        $status_text = $_SESSION['status'];
        $status_code = $_SESSION['status_code']; 
    ?>
    <script>
        Swal.fire({
            icon: "<?php echo $status_code; ?>",
            title: "<?php echo $status_text; ?>",
            showConfirmButton: true,
            confirmButtonText: 'OK',
            confirmButtonColor: '#00c853',
            allowOutsideClick: false,
            customClass: {
                popup: 'swal2-popup-custom',
                title: 'swal2-title-custom'
            }
        });
    </script>
    <?php
        unset($_SESSION['status']);
        unset($_SESSION['status_code']);
    }
    ?>
</body>
</html>