<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GranGate | School Admission</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-green: #00c853;
            --dark-bg: #111111;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body {
            background-color: var(--dark-bg);
            height: 100vh;
            overflow: hidden;
            position: relative;
        }

        /* NAVIGATION - Magpabilin sa Itom nga background sa taas */
        nav {
            position: absolute;
            top: 0; width: 100%;
            display: flex; justify-content: space-between; align-items: center;
            padding: 30px 8%;
            z-index: 100;
        }
        .logo { font-size: 1.5rem; font-weight: 900; color: white; }
        nav ul { display: flex; list-style: none; gap: 25px; }
        nav ul li { color: #ccc; font-size: 0.85rem; cursor: pointer; }
        .btn-reg {
            background: var(--primary-green); color: white; border: none;
            padding: 8px 25px; border-radius: 50px; font-weight: bold;
        }

        /* 1. ANG PUTI NGA LAYER - Gi-ubos nato gamay (top: 15vh) */
        .white-bg-layer {
            position: absolute;
            background-color: white;
            width: 180vh; 
            height: 130vh;
            border-radius: 50%;
            left: -35vh; 
            top: 20vh; /* Gi-ubos para dili moabot sa taas */
            z-index: 1; 
        }

        /* 2. TEXT CONTENT - Naa sa ibabaw sa puti */
        .content-box {
            position: absolute;
            left: 10%; 
            top: 60%; /* I-adjust base sa imong panan-aw */
            transform: translateY(-50%);
            width: 450px;
            z-index: 10;
        }
        .content-box h1 { font-size: 4.8rem; font-weight: 900; line-height: 0.9; color: #111; margin-bottom: 20px; }
        .content-box h1 span { color: var(--primary-green); }
        .content-box p { color: #555; font-size: 0.9rem; margin-bottom: 30px; }
        
        .btn-enroll {
            background: var(--primary-green); color: white; border: none;
            padding: 15px 35px; border-radius: 12px 12px 40px 12px; 
            font-weight: 900; cursor: pointer;
        }

        /* 3. ANG GAMAY NGA CIRCLE - Molusot ang puti sa iyang luyo */
        .photo-circle {
            position: absolute;
            right: 5%; 
            top: 70%;
            transform: translateY(-50%);
            width: 75vh; /* Gidako-on sa circle */
            height: 75vh;
            border-radius: 50%;
            background-color: var(--primary-green); /* Placeholder samtang wala pay pic */
            z-index: 2; /* Atubangan sa white layer */
            border: 15px solid var(--dark-bg); /* Gap effect */
            overflow: hidden;
        }

        .socials { margin-top: 30px; display: flex; gap: 15px; align-items: center; }
        .social-text { color: var(--primary-green); font-size: 0.75rem; font-weight: bold; }
        .socials span {
            width: 35px; height: 35px; border: 2px solid var(--primary-green);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            color: var(--primary-green); font-weight: bold; font-size: 0.8rem;
        }
    </style>
</head>
<body>

    <nav>
        <div class="logo">GranGate</div>
        <ul>
            <li>Home</li>
            <li>About us</li>
            <li>Contact</li>
        </ul>
        <a href="register.php" style="text-decoration: none;">
    <button class="btn-reg">REGISTRATION</button>
</a>
    </nav>

    <div class="white-bg-layer"></div>

    <div class="content-box">
        <h1>GranGate <br> </h1>
        <p>Student Application & Admin Dashboard</p>
        <a href="register.php" style="text-decoration: none;">
    <button class="btn-enroll">ENROLL NOW</button>
</a>
        
        <div class="socials">
            <div class="social-text">NOW OPEN FOR REGISTRATION</div>
            <span>f</span><span>t</span><span>i</span>
        </div>
    </div>

    <div class="photo-circle">
        </div>

</body>
</html>