<?php
session_start();

// If admin is already logged in, redirect straight to dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrantGate | Admin Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { 
            --green: #00c853; 
            --green-glow: rgba(0,200,83,0.4);
            --green-dim: rgba(0,200,83,0.1);
            --dark-bg: #0f172a; 
            --darker: #0b1121;
            --card-bg: rgba(15,23,42,0.85);
            --fg: #ffffff;
            --muted: #64748b;
            --input-bg: rgba(255,255,255,0.05);
            --input-border: rgba(255,255,255,0.1);
        }

        html { scroll-behavior: smooth; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--dark-bg); color: var(--fg); overflow-x: hidden; }

        /* ===== BACKGROUNDS ===== */
        .bg-blobs { position: fixed; inset: 0; z-index: 0; overflow: hidden; }
        .blob { position: absolute; border-radius: 50%; filter: blur(120px); opacity: 0.35; animation: blobDrift 16s ease-in-out infinite alternate; }
        .blob-1 { width: 600px; height: 600px; background: radial-gradient(circle, var(--green), transparent 70%); top: -20%; left: -15%; }
        .blob-2 { width: 500px; height: 500px; background: radial-gradient(circle, #00e676, transparent 70%); bottom: -20%; right: -10%; animation-delay: -5s; animation-duration: 20s; }
        .blob-3 { width: 350px; height: 350px; background: radial-gradient(circle, #1de9b6, transparent 70%); top: 40%; left: 50%; opacity: 0.15; animation-delay: -10s; animation-duration: 22s; }
        @keyframes blobDrift { 0%{transform:translate(0,0) scale(1)} 33%{transform:translate(60px,-50px) scale(1.1)} 66%{transform:translate(-40px,60px) scale(0.95)} 100%{transform:translate(50px,30px) scale(1.05)} }

        .grid-bg { position: fixed; inset: 0; z-index: 1; pointer-events: none; background-image: linear-gradient(rgba(0,200,83,0.015) 1px,transparent 1px), linear-gradient(90deg,rgba(0,200,83,0.015) 1px,transparent 1px); background-size: 70px 70px; }
        .particles { position: fixed; inset: 0; z-index: 1; pointer-events: none; }
        .dot { position: absolute; border-radius: 50%; background: var(--green); opacity: 0; animation: dotFloat linear infinite; }
        @keyframes dotFloat { 0%{opacity:0;transform:translateY(0) scale(0)} 8%{opacity:0.5;transform:scale(1)} 80%{opacity:0.3} 100%{opacity:0;transform:translateY(-100vh) scale(0.4)} }
        .scan-line { position: fixed; left: 0; width: 100%; height: 2px; z-index: 2; background: linear-gradient(90deg,transparent,var(--green),transparent); opacity: 0.1; pointer-events: none; animation: scanMove 6s linear infinite; }
        @keyframes scanMove { 0%{top:-2px} 100%{top:100%} }

        /* ===== NAVBAR ===== */
        .navbar {
            position: fixed; top: 0; left: 0; right: 0; height: 64px;
            background: rgba(11,20,36,0.95); backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 32px; z-index: 1000;
            animation: navIn 0.8s cubic-bezier(0.22,1,0.36,1) forwards;
            transform: translateY(-100%);
        }
        @keyframes navIn { to { transform: translateY(0); } }

        .nav-left { display: flex; align-items: center; gap: 16px; }
        .logo { font-family: 'Orbitron', sans-serif; font-size: 1.3rem; font-weight: 900; color: #fff; text-decoration: none; }
        .logo span { color: var(--green); }

        .admin-badge {
            background: rgba(0,200,83,0.1); border: 1px solid rgba(0,200,83,0.25);
            color: var(--green); padding: 4px 14px; border-radius: 20px;
            font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
            font-family: 'Orbitron', sans-serif;
        }

        .nav-right a {
            color: var(--muted); text-decoration: none; font-size: 13px; font-weight: 600;
            display: inline-flex; align-items: center; gap: 6px; transition: color 0.3s;
        }
        .nav-right a:hover { color: var(--green); }

        /* ===== HERO SECTION ===== */
        .hero {
            position: relative; z-index: 10;
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 100px 40px 60px;
        }

        .hero-content {
            text-align: center; max-width: 700px;
            opacity: 0; transform: translateY(40px) scale(0.96);
            animation: containerIn 0.8s 0.3s cubic-bezier(0.22,1,0.36,1) forwards;
        }
        @keyframes containerIn { to { opacity: 1; transform: translateY(0) scale(1); } }

        .hero-icon {
            width: 100px; height: 100px; border-radius: 50%;
            background: var(--green-dim); border: 2px solid rgba(0,200,83,0.3);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 30px; font-size: 40px; color: var(--green);
            animation: iconPulse 3s ease-in-out infinite;
        }
        @keyframes iconPulse { 0%,100%{box-shadow:0 0 20px var(--green-glow)} 50%{box-shadow:0 0 40px var(--green-glow)} }

        .hero h1 { font-family: 'Orbitron', sans-serif; font-size: 3.2rem; font-weight: 900; margin-bottom: 10px; line-height: 1.1; }
        .hero h1 span { color: var(--green); }
        .hero .subtitle { color: var(--muted); font-size: 1.1rem; line-height: 1.7; margin-bottom: 40px; max-width: 500px; margin-left: auto; margin-right: auto; }

        .cta-btn {
            display: inline-flex; align-items: center; gap: 12px;
            background: var(--green); border: none;
            padding: 16px 48px; border-radius: 12px;
            cursor: pointer; font-weight: 800; font-family: 'Orbitron', sans-serif;
            font-size: 0.95rem; color: #fff; text-decoration: none;
            letter-spacing: 0.1em; position: relative; overflow: hidden;
            transition: all 0.35s cubic-bezier(0.22, 1, 0.36, 1);
        }
        .cta-btn:hover { transform: translateY(-3px); box-shadow: 0 8px 35px var(--green-glow); }
        .cta-btn:active { transform: translateY(0) scale(0.97); }
        .cta-btn::after {
            content: ''; position: absolute; top: 0; left: -100%; width: 60%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shineSweep 3s 1.5s ease-in-out infinite;
        }
        @keyframes shineSweep { 0%{left:-100%} 30%{left:150%} 100%{left:150%} }
        .cta-btn i { transition: transform 0.3s; }
        .cta-btn:hover i { transform: translateX(4px); }

        .stats-row {
            display: flex; justify-content: center; gap: 50px; margin-top: 50px;
            opacity: 0; transform: translateY(20px);
            animation: fadeUp 0.7s 0.8s cubic-bezier(0.22,1,0.36,1) forwards;
        }
        @keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }

        .stat-item { text-align: center; }
        .stat-num { font-family: 'Orbitron', sans-serif; font-size: 1.6rem; font-weight: 900; color: var(--fg); }
        .stat-num .gn { color: var(--green); }
        .stat-label { font-size: 0.75rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.1em; font-weight: 600; margin-top: 4px; }

        /* ===== FEATURES SECTION ===== */
        .features {
            position: relative; z-index: 10;
            padding: 80px 40px; max-width: 1100px; margin: 0 auto;
        }
        .section-title {
            text-align: center; margin-bottom: 50px;
            opacity: 0; transform: translateY(30px);
            transition: all 0.8s cubic-bezier(0.22,1,0.36,1);
        }
        .section-title.visible { opacity: 1; transform: translateY(0); }
        .section-title h2 { font-family: 'Orbitron', sans-serif; font-size: 1.8rem; font-weight: 900; }
        .section-title h2 span { color: var(--green); }
        .section-title p { color: var(--muted); font-size: 0.95rem; margin-top: 10px; }

        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; }

        .feature-card {
            background: var(--card-bg); backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px; padding: 32px;
            transition: all 0.4s cubic-bezier(0.22,1,0.36,1);
            opacity: 0; transform: translateY(30px);
            transition: opacity 0.6s cubic-bezier(0.22,1,0.36,1), transform 0.6s cubic-bezier(0.22,1,0.36,1), box-shadow 0.4s;
        }
        .feature-card.visible { opacity: 1; transform: translateY(0); }
        .feature-card:hover { transform: translateY(-6px); box-shadow: 0 12px 40px rgba(0,0,0,0.3); border-color: rgba(0,200,83,0.2); }

        .feature-icon {
            width: 56px; height: 56px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; margin-bottom: 20px;
        }
        .feature-icon.green { background: rgba(0,200,83,0.1); color: var(--green); }
        .feature-icon.blue { background: rgba(59,130,246,0.1); color: #60a5fa; }
        .feature-icon.yellow { background: rgba(255,201,60,0.1); color: #ffc93c; }
        .feature-icon.red { background: rgba(255,71,87,0.1); color: #ff6b7a; }

        .feature-card h3 { font-size: 1.05rem; font-weight: 700; margin-bottom: 8px; }
        .feature-card p { font-size: 0.9rem; color: var(--muted); line-height: 1.6; }

        /* ===== FOOTER ===== */
        .footer {
            position: relative; z-index: 10;
            text-align: center; padding: 30px;
            border-top: 1px solid rgba(255,255,255,0.05);
            color: var(--muted); font-size: 13px;
        }
        .footer span { color: var(--green); font-weight: 600; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .hero h1 { font-size: 2.2rem; }
            .hero .subtitle { font-size: 0.95rem; }
            .stats-row { gap: 30px; }
            .stat-num { font-size: 1.3rem; }
            .hero, .features { padding-left: 20px; padding-right: 20px; }
        }
        @media (max-width: 480px) {
            .hero h1 { font-size: 1.8rem; }
            .stats-row { flex-direction: column; gap: 16px; }
            .navbar { padding: 0 16px; }
        }
    </style>
</head>
<body>

    <div class="bg-blobs"><div class="blob blob-1"></div><div class="blob blob-2"></div><div class="blob blob-3"></div></div>
    <div class="grid-bg"></div>
    <div class="particles" id="particleLayer"></div>
    <div class="scan-line"></div>

    <!-- NAVBAR -->
    <div class="navbar">
        <div class="nav-left">
            <a href="admin_index.php" class="logo">GRANT<span>GATE</span></a>
            <div class="admin-badge"><i class="fas fa-shield-halved"></i> Admin</div>
        </div>
        <div class="nav-right">
            <a href="landing_page.php"><i class="fas fa-arrow-left"></i> Student Portal</a>
        </div>
    </div>

    <!-- HERO -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-icon">
                <i class="fas fa-shield-halved"></i>
            </div>
            <h1>Admin <span>Control Hub</span></h1>
            <p class="subtitle">Manage scholarship applications, review documents, approve or reject candidates, and monitor platform analytics.</p>
            <a href="admin_login.php" class="cta-btn">
                ADMIN LOGIN <i class="fas fa-arrow-right"></i>
            </a>
            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-num"><span class="gn">Secure</span></div>
                    <div class="stat-label">Encrypted Access</div>
                </div>
                <div class="stat-item">
                    <div class="stat-num"><span class="gn">Real-time</span></div>
                    <div class="stat-label">Data Sync</div>
                </div>
                <div class="stat-item">
                    <div class="stat-num"><span class="gn">Full</span></div>
                    <div class="stat-label">Control</div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES -->
    <section class="features">
        <div class="section-title" id="featTitle">
            <h2>Admin <span>Capabilities</span></h2>
            <p>Everything you need to manage the GrantGate scholarship platform</p>
        </div>

        <div class="features-grid">
            <div class="feature-card" id="fc1">
                <div class="feature-icon green"><i class="fas fa-users"></i></div>
                <h3>Applicant Management</h3>
                <p>View, search, and filter all student applications in one organized dashboard.</p>
            </div>
            <div class="feature-card" id="fc2">
                <div class="feature-icon blue"><i class="fas fa-check-double"></i></div>
                <h3>Review & Approve</h3>
                <p>Access detailed student profiles and documents to approve or reject with one click.</p>
            </div>
            <div class="feature-card" id="fc3">
                <div class="feature-icon yellow"><i class="fas fa-chart-line"></i></div>
                <h3>Analytics Dashboard</h3>
                <p>Real-time charts and statistics tracking pending, approved, and rejected applications.</p>
            </div>
            <div class="feature-card" id="fc4">
                <div class="feature-icon red"><i class="fas fa-file-shield"></i></div>
                <h3>Document Verification</h3>
                <p>Verify uploaded requirements like COR, grades, and valid IDs directly from the portal.</p>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <div class="footer">
        &copy; 2024 <span>GrantGate</span>. Admin Portal. All rights reserved.
    </div>

    <script>
        /* Particles */
        (function(){const l=document.getElementById('particleLayer');for(let i=0;i<20;i++){const d=document.createElement('div');d.classList.add('dot');const s=Math.random()*3+1.5;d.style.width=s+'px';d.style.height=s+'px';d.style.left=Math.random()*100+'%';d.style.bottom='-5%';d.style.animationDuration=(Math.random()*10+7)+'s';d.style.animationDelay=(Math.random()*12)+'s';l.appendChild(d);}})();

        /* Scroll Reveal */
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => { if (entry.isIntersecting) entry.target.classList.add('visible'); });
        }, { threshold: 0.15 });

        document.querySelectorAll('.section-title, .feature-card').forEach(el => observer.observe(el));

        /* Staggered card animation delay */
        ['fc1','fc2','fc3','fc4'].forEach((id, i) => {
            const el = document.getElementById(id);
            if(el) el.style.transitionDelay = (i * 0.15) + 's';
        });
    </script>
</body>
</html>