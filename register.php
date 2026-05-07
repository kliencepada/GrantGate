<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GrantGate | Register</title>

<style>
        /* 1. SETUP PARA SA ANIMATION */
        @property --angle { syntax: "<angle>"; initial-value: 0deg; inherits: false; }
        
        body { 
            background: #0f172a; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
            font-family: sans-serif; 
        }

    /* 2. ANG CARD (HIDDEN ANG NEON SA SUGOD) */
        .card { 
            background: #1e293b; 
    padding: 1rem; 
    border-radius: 15px; 
    position: relative; 
    /* Gamay ra siya sa sugod */
    width: 150px; 
    height: 50px;
    color: white; 
    transition: 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275); /* Smooth expand effect */
    cursor: pointer;
    overflow: hidden; /* Itago ang form sa sugod */
    display: flex;
    justify-content: center;
    align-items: center;
        }

        /* Ang "Text" nga makita sa sugod */
        .card::after {
            content: 'REGISTER';
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            font-weight: bold;
            letter-spacing: 2px;
            transition: 0.5s;
        }

        /* Ang Neon Border (Opacity 0 = Hidden) */
        .card::before {
            content: ''; position: absolute; inset: -4px; border-radius: 19px; z-index: -1;
            background: conic-gradient(from var(--angle), transparent, #00ffff, #ff00ff, #00ffff);
            animation: spin 3s linear infinite;
            opacity: 1;
            transition: 0.5s;
        }

        * 3. HOVER EFFECTS (MOPAKITA NA ANG TANAN) */
        .card:hover::before { opacity: 1; } /* Mugawas ang Neon */
        .card:hover::after { opacity: 0; }  /* Mawala ang "Touch to Register" */
        
        .card:hover form { 
            opacity: 1; 
            pointer-events: all; 
            transform: translateY(0);
        }

        /* 4. ANG FORM (ITAGO SA SUGOD) */
        form {
            opacity: 0;
            transition: 0.5s;
            pointer-events: none;
            transform: translateY(20px); /* Gamay nga animation pasaka */
        }

        @keyframes spin { from { --angle: 0deg; } to { --angle: 360deg; } }

        h2 { margin-top: 0; letter-spacing: 2px; }
        input { width: 100%; padding: 12px; margin: 10px 0; background: #334155; border: none; color: white; border-radius: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #00ffff; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; margin-top: 15px; }
    </style>
</head>
<body>

    <div class="card">
        <form action="register_logic.php" method="POST">
            <h2>Sign up</h2>
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="text" name="school" placeholder="School" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="register_btn">Register</button>
        </form>
    </div>

</body>
</html>