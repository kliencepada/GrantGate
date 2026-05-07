<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GranGate | School Admission</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;700;900&display=swap" rel="stylesheet">
    
    <style>
        /* GI-PASTE NAKO DIRETSO ANG CSS DIRI PARA MO-GANA NA GYUD */
        :root { --primary-green: #00c853; --dark-bg: #1a1a1a; --text-gray: #555; --white: #ffffff; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--dark-bg); height: 100vh; overflow: hidden; }
        nav { position: absolute; width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 30px 8%; z-index: 100; }
        .logo { font-size: 1.5rem; color: var(--dark-bg); font-weight: bold; }
        .nav-links { display: flex; list-style: none; gap: 25px; }
        .nav-links li { color: var(--text-gray); font-size: 0.9rem; cursor: pointer; }
        .btn-reg { background-color: var(--primary-green); color: white; border: none; padding: 10px 25px; border-radius: 25px; font-weight: bold; cursor: pointer; }
        .hero { display: flex; height: 100vh; width: 100%; }
        .white-section { background-color: var(--white); width: 60%; height: 100%; display: flex; align-items: center; padding-left: 8%; clip-path: ellipse(95% 130% at 0% 50%); z-index: 2; }
        .content { max-width: 500px; }
        .content h1 { font-size: 4.5rem; font-weight: 900; line-height: 1.1; color: var(--dark-bg); }
        .green-text { color: var(--primary-green); }
        .content p { margin: 20px 0; color: var(--text-gray); }
        .btn-enroll { background-color: var(--dark-bg); color: white; border: none; padding: 15px 35px; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .image-section { width: 40%; height: 100%; background-color: var(--dark-bg); background-image: url('https://images.unsplash.com/photo-1523050853063-9158946122b2?auto=format&fit=crop&q=80&w=1000'); background-size: cover; background-position: center; position: relative; }
        .green-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 200, 83, 0.2); }
        .social-icons { margin-top: 30px; display: flex; gap: 15px; }
        .social-icons span { width: 35px; height: 35px; border: 1px solid var(--primary-green); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary-green); }
    </style>
</head>
<body>
    <nav>
        <div class="logo">/ SCHOOL</div>
        <ul class="nav-links">
            <li>Home</li><li>About us</li><li>Programs</li><li>Blog</li><li>Contact</li>
        </ul>
        <button class="btn-reg">REGISTRATION</button>
    </nav>
    <main class="hero">
        <div class="white-section">
            <div class="content">
                <h1>SCHOOL <br> <span class="green-text">ADMISSION</span></h1>
                <p>We offer exciting school admission deals, certified teachers, and tailored learning plans to help you succeed!</p>
                <button class="btn-enroll">ENROLL NOW</button>
                <p style="margin-top:20px; color:var(--primary-green); font-weight:bold;">NOW OPEN FOR REGISTRATION</p>
                <div class="social-icons"><span>f</span><span>t</span><span>i</span></div>
            </div>
        </div>
        <div class="image-section"><div class="green-overlay"></div></div>
    </main>
</body>
</html>