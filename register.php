<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrantGate | Join Us</title>
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

        /* 1. INITIAL STATE (Gamay nga Box) */
        .card { 
            background: #1e293b; 
            border-radius: 15px; 
            position: relative; 
            width: 160px; /* Gamay sa sugod */
            height: 60px; 
            color: white; 
            transition: 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        /* 2. HOVER STATE (Mo-expand) */
        .card:hover { 
            width: 350px; 
            height: 520px; 
            padding: 20px;
        }

        /* Neon Animation (Mopakita lang inig hover) */
        .card::before {
            content: ''; position: absolute; inset: -4px; border-radius: 19px; z-index: -1;
            background: conic-gradient(from var(--angle), transparent, #00ffff, #ff00ff, #00ffff);
            animation: spin 3s linear infinite;
            opacity: 0;
            transition: 0.5s;
        }
        .card:hover::before { opacity: 1; }

        @keyframes spin { from { --angle: 0deg; } to { --angle: 360deg; } }

        /* 3. ELEMENTS INSIDE */
        .hover-text {
            font-weight: bold;
            letter-spacing: 2px;
            color: #00ffff;
            transition: 0.3s;
            position: absolute;
        }
        .card:hover .hover-text { opacity: 0; }

        form {
            opacity: 0;
            width: 100%;
            transition: 0.4s ease;
            pointer-events: none;
            display: flex;
            flex-direction: column;
        }
        .card:hover form { 
            opacity: 1; 
            pointer-events: all;
            transition-delay: 0.3s;
        }

        h2 { text-align: center; color: #00ffff; margin-bottom: 20px; }
        input { width: 100%; padding: 12px; margin: 8px 0; background: #334155; border: none; color: white; border-radius: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #00ffff; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; margin-top: 15px; transition: 0.3s; }
        button:hover { background: #ff00ff; color: white; }
    </style>
</head>
<body>

    <div class="card">
        <div class="hover-text">REGISTER</div>
        
        <form action="register_logic.php" method="POST">
            <h2>Create Account</h2>
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="text" name="school" placeholder="School" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="register_btn">Sign up</button>
        </form>
    </div>

</body>
</html>