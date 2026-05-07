<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrantGate | Register</title>
    <style>
        @property --angle { syntax: "<angle>"; initial-value: 0deg; inherits: false; }
        
        body { 
            background: #0f172a; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
            font-family: 'Poppins', sans-serif; 
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

        .card:hover { 
            width: 350px; 
            height: 550px; 
        }

        /* NEON BORDER - SOLID TO TRANSPARENT (FADED LOOK) */
        .card::before {
            content: ''; 
            position: absolute; 
            inset: -4px; 
            border-radius: 19px; 
            z-index: -1;
            /* INITIAL: 2 Segments (Cyan & Magenta) with Transparency Fade */
            background: conic-gradient(
                from var(--angle), 
                #00ffff 0deg, transparent 90deg, 
                #ff00ff 180deg, transparent 270deg,
                #00ffff 360deg
            );
            animation: spin 4s linear infinite;
            transition: 0.5s ease;
        }

        .card:hover::before {
            /* HOVER: 4 Different Colors (Cyan, Magenta, Lime, Yellow) */
            /* Naay "transparent" stops para makuha ang "solid to faded" effect */
            background: conic-gradient(
                from var(--angle), 
                #00ffff 0deg, transparent 45deg,
                #ff00ff 90deg, transparent 135deg,
                #00ff00 180deg, transparent 225deg,
                #ffff00 270deg, transparent 315deg,
                #00ffff 360deg
            );
            animation: spin 3s linear infinite;
        }

        @keyframes spin { from { --angle: 0deg; } to { --angle: 360deg; } }

        /* CENTERED ELEMENTS */
        .hover-text { 
            font-weight: bold; 
            color: #00ffff; 
            text-shadow: 0 0 15px #00ffff; 
            letter-spacing: 2px;
            transition: 0.3s; 
            position: absolute;
            width: 100%;
            text-align: center;
        }
        .card:hover .hover-text { opacity: 0; visibility: hidden; }

        form { 
            opacity: 0; 
            width: 100%; 
            height: 100%;
            transition: 0.4s ease; 
            pointer-events: none; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center;
            box-sizing: border-box;
            padding: 30px;
        }
        .card:hover form { opacity: 1; pointer-events: all; transition-delay: 0.3s; }

        h2 { color: white; margin-bottom: 25px; text-align: center; width: 100%; }
        input, button { width: 90%; padding: 14px; border-radius: 8px; box-sizing: border-box; }
        input { margin: 10px 0; background: #0f172a; border: 1px solid #334155; color: white; }
        button { background: #00ffff; border: none; cursor: pointer; font-weight: bold; margin-top: 20px; color: #0f172a; transition: 0.3s; }
        button:hover { background: #ff00ff; color: white; box-shadow: 0 0 20px #ff00ff; }
    </style>
</head>
<body>
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
</body>
</html>