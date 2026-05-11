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
            --green-glow: rgba(0, 200, 83, 0.4);
            --green-dim: rgba(0, 200, 83, 0.08);
            --dark-bg: #0f172a; 
            --darker: #0b1121;
            --white: #ffffff;
            --muted: #64748b;
        }

        html { scroll-behavior: smooth; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--dark-bg); color: white; overflow-x: hidden; }

        /* --- NAVIGATION --- */
        header {
            position: fixed; top: 0; left: 0; width: 100%;
            display: flex; justify-content: space-between;
            align-items: center; padding: 10px 8%; z-index: 10000;
            background: rgba(25, 37, 67, 0.85); backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            animation: headerSlide 0.8s cubic-bezier(0.22, 1, 0.36, 1) forwards;
            transform: translateY(-100%);
        }

        @keyframes headerSlide { to { transform: translateY(0); } }

        .logo { font-family: 'Orbitron', sans-serif; font-size: 1.8rem; font-weight: 900; color: #fff; text-decoration: none; }
        .logo span { color: var(--green); }

        .admin-nav-badge{
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(0,200,83,0.1); border: 1px solid rgba(0,200,83,0.25);
            color: var(--green); padding: 6px 18px; border-radius: 20px;
            font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
            font-family: 'Orbitron', sans-serif;
        }

        /* --- HOME SECTION --- */
        #home { 
            height: 100vh; position: relative; overflow: hidden; 
            display: flex; align-items: center; 
        }

        .white-bg-layer { 
            position: absolute; background: #ffffff; width: 190vh; height: 190vh; 
            border-radius: 50%; left: -35vh; top: 15vh; z-index: 1;
            animation: bgScale 1.2s cubic-bezier(0.22, 1, 0.36, 1) forwards;
            transform: scale(0.6);
        }

        @keyframes bgScale { to { transform: scale(1); } }

        .content-box { 
            position: relative; margin-left: 8%; width: 600px; z-index: 10; 
        }

        .content-label {
            display: inline-flex; align-items: center; gap: 10px;
            background: rgba(0, 200, 83, 0.1);
            border: 1px solid rgba(0, 200, 83, 0.25);
            padding: 8px 20px; border-radius: 50px;
            font-size: 0.8rem; font-weight: 600;
            color: var(--green); letter-spacing: 0.15em;
            text-transform: uppercase; margin-bottom: 24px;
            opacity: 0; transform: translateY(20px);
            animation: fadeUp 0.7s 0.3s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }

        .content-label i { font-size: 10px; animation: blink 1.5s ease-in-out infinite; }
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }

        .content-box h1 { 
            font-size: 4.5rem; font-weight: 800; line-height: 1.05; 
            color: #111; text-transform: uppercase; margin-bottom: 8px;
        }

        .content-box h1 .line-green {
            color: var(--green); display: block; font-size: 3.8rem;
        }

        .rl {
            display: inline-block !important;
            opacity: 0; transform: translateY(50px);
            animation: letterUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }

        @keyframes letterUp { to { opacity: 1; transform: translateY(0); } }

        .heading-accent {
            width: 80px; height: 4px; background: var(--green);
            border-radius: 4px; margin: 16px 0 20px;
            opacity: 0; transform: scaleX(0); transform-origin: left;
            animation: lineGrow 0.8s 1.6s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }

        @keyframes lineGrow { to { opacity: 1; transform: scaleX(1); } }

        .content-box .subtitle {
            font-size: 1.1rem; color: #555; line-height: 1.7;
            max-width: 480px; margin-bottom: 32px;
            opacity: 0; transform: translateY(20px);
            animation: fadeUp 0.7s 1.8s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }

        @keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }

        .cta-btn {
            display: inline-flex; align-items: center; gap: 12px;
            background: var(--green); border: none;
            padding: 16px 44px; border-radius: 12px;
            cursor: pointer; font-weight: 800; font-family: 'Orbitron', sans-serif;
            font-size: 0.95rem; color: #fff; text-decoration: none;
            letter-spacing: 0.1em; position: relative; overflow: hidden;
            opacity: 0; transform: translateY(20px);
            animation: fadeUp 0.7s 2.1s cubic-bezier(0.22, 1, 0.36, 1) forwards;
            transition: all 0.35s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .cta-btn:hover {
            transform: translateY(-3px) !important;
            box-shadow: 0 8px 35px var(--green-glow), 0 0 60px rgba(0,200,83,0.15);
        }

        .cta-btn:active { transform: translateY(0) scale(0.97) !important; }

        .cta-btn::after {
            content: ''; position: absolute;
            top: 0; left: -100%; width: 60%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.25), transparent);
            animation: shineSweep 3s 2.5s ease-in-out infinite;
        }

        @keyframes shineSweep { 0% { left: -100%; } 30% { left: 150%; } 100% { left: 150%; } }

        .cta-btn i { transition: transform 0.3s; }
        .cta-btn:hover i { transform: translateX(4px); }

        /* Stats row */
        .stats-row {
            display: flex; gap: 40px; margin-top: 40px;
            opacity: 0; transform: translateY(20px);
            animation: fadeUp 0.7s 2.4s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }

        .stat-item { text-align: left; }

        .stat-num {
            font-family: 'Orbitron', sans-serif;
            font-size: 2rem; font-weight: 900; color: #111; line-height: 1;
        }

        .stat-num .gn { color: var(--green); }

        .stat-label {
            font-size: 0.75rem; color: #888; text-transform: uppercase;
            letter-spacing: 0.1em; font-weight: 600; margin-top: 4px;
        }

        .scroll-indicator {
            position: absolute; bottom: 30px; left: 50%;
            transform: translateX(-50%); z-index: 20;
            display: flex; flex-direction: column; align-items: center; gap: 8px;
            opacity: 0; animation: fadeUp 0.7s 2.8s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }

        .scroll-indicator span { font-size: 0.7rem; color: #999; text-transform: uppercase; letter-spacing: 0.15em; font-weight: 600; }

        .scroll-mouse {
            width: 22px; height: 34px;
            border: 2px solid rgba(0,0,0,0.2); border-radius: 12px; position: relative;
        }

        .scroll-mouse::before {
            content: ''; position: absolute;
            top: 6px; left: 50%; transform: translateX(-50%);
            width: 3px; height: 8px; border-radius: 3px;
            background: var(--green); animation: scrollDot 1.8s ease-in-out infinite;
        }

        @keyframes scrollDot { 0% { opacity: 1; top: 6px; } 100% { opacity: 0; top: 20px; } }

        /* --- CONTACT SECTION --- */
        #contact-section { padding: 120px 8%; background: var(--dark-bg); text-align: center; min-height: 100vh; display: flex; flex-direction: column; justify-content: center; }
        .contact-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; margin-top: 60px; }
        .contact-card { 
            background: #1e293b; padding: 45px; border-radius: 20px; 
            border-bottom: 4px solid var(--green);
            transition: all 0.4s cubic-bezier(0.22, 1, 0.36, 1);
        }
        .contact-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.4), 0 0 30px var(--green-dim);
            border-bottom-color: #fff;
        }
        .contact-card h3 { 
            font-family: 'Orbitron', sans-serif; color: var(--green); 
            font-size: 1rem; letter-spacing: 0.1em; margin-bottom: 12px;
        }
        .contact-card p { color: #94a3b8; font-size: 1.05rem; }

        .reveal {
            opacity: 0; transform: translateY(40px);
            transition: all 0.8s cubic-bezier(0.22, 1, 0.36, 1);
        }
        .reveal.visible { opacity: 1; transform: translateY(0); }

        /* Responsive */
        @media (max-width: 900px) {
            .content-box h1 { font-size: 3rem; }
            .content-box h1 .line-green { font-size: 2.5rem; }
            .content-box { width: auto; margin-left: 5%; margin-right: 5%; }
            .stats-row { gap: 24px; }
            .stat-num { font-size: 1.5rem; }
        }

        @media (max-width: 600px) {
            .content-box h1 { font-size: 2.2rem; }
            .content-box h1 .line-green { font-size: 1.8rem; }
            .stats-row { flex-direction: column; gap: 16px; }
        }
    </style>
</head>
<body>

    <header>
        <a href="admin_index.php" class="logo">GRANT<span>GATE</span></a>
        <div class="admin-nav-badge"><i class="fas fa-shield-halved"></i> Administrator Portal</div>
    </header>

    <section id="home">
        <div class="white-bg-layer"></div>

        <div class="content-box">
            <div class="content-label">
                <i class="fas fa-circle"></i> Admin Control Panel
            </div>

            <h1 id="mainHeading"></h1>

            <div class="heading-accent"></div>

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

        <div class="scroll-indicator">
            <span>Scroll</span>
            <div class="scroll-mouse"></div>
        </div>
    </section>

    <section id="contact-section">
        <h1 class="reveal" style="font-family:'Orbitron'; font-size: 3.2rem;">SYSTEM <span style="color:var(--green);">INFO</span></h1>
        <div class="contact-grid">
            <div class="contact-card reveal">
                <h3><i class="fas fa-server" style="margin-right:8px;"></i>SERVER STATUS</h3>
                <p>Operational</p>
            </div>
            <div class="contact-card reveal">
                <h3><i class="fas fa-database" style="margin-right:8px;"></i>DATABASE</h3>
                <p>Connected</p>
            </div>
            <div class="contact-card reveal">
                <h3><i class="fas fa-shield-halved" style="margin-right:8px;"></i>SECURITY</h3>
                <p>Bcrypt Encrypted</p>
            </div>
        </div>
    </section>

    <script>
        /* LETTER-BY-LETTER HEADING REVEAL */
        (function animateHeading() {
            const heading = document.getElementById('mainHeading');
            const line1 = 'Welcome!';
            const line2 = 'To Admin Hub';

            let html = '';
            let delay = 0.5;

            for (let i = 0; i < line1.length; i++) {
                const ch = line1[i];
                if (ch === ' ') { html += ' '; } 
                else { html += '<span class="rl" style="animation-delay:' + delay.toFixed(2) + 's">' + ch + '</span>'; delay += 0.04; }
            }

            html += '<span class="line-green">';
            for (let i = 0; i < line2.length; i++) {
                const ch = line2[i];
                if (ch === ' ') { html += ' '; } 
                else { html += '<span class="rl" style="animation-delay:' + delay.toFixed(2) + 's">' + ch + '</span>'; delay += 0.04; }
            }
            html += '</span>';

            heading.innerHTML = html;
        })();

        /* SCROLL REVEAL */
        const revealEls = document.querySelectorAll('.reveal');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => { if (entry.isIntersecting) entry.target.classList.add('visible'); });
        }, { threshold: 0.15 });
        revealEls.forEach(el => observer.observe(el));
    </script>
</body>
</html>