<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrantGate | Official Admission</title>
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

        @keyframes headerSlide {
            to { transform: translateY(0); }
        }

        .logo { font-family: 'Orbitron', sans-serif; font-size: 1.8rem; font-weight: 900; color: #fff; }
        .logo span { color: var(--green); }

        .nav-links { display: flex; list-style: none; gap: 40px; }
        .nav-links li a {
            position: relative; font-size: 1rem; text-decoration: none;
            color: transparent; -webkit-text-stroke: 1px rgba(255, 255, 255, 0.4);
            font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px;
            transition: 0.3s;
        }
        .nav-links li a::before {
            content: attr(data-text); position: absolute; color: var(--green);
            width: 0; overflow: hidden; transition: 0.5s;
            border-right: 2px solid var(--green); -webkit-text-stroke: 1px var(--green);
            white-space: nowrap;
        }
        .nav-links li a:hover::before, .nav-links li a.active::before { width: 100%; filter: drop-shadow(0 0 15px var(--green)); }

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

        @keyframes bgScale {
            to { transform: scale(1); }
        }

        /* Floating particles on home */
        .home-particles {
            position: absolute; inset: 0; z-index: 3; pointer-events: none;
            overflow: hidden;
        }

        .h-particle {
            position: absolute;
            border-radius: 50%;
            background: var(--green);
            opacity: 0;
            animation: hFloat linear infinite;
        }

        @keyframes hFloat {
            0%   { opacity: 0; transform: translateY(0) scale(0); }
            8%   { opacity: 0.5; transform: scale(1); }
            80%  { opacity: 0.3; }
            100% { opacity: 0; transform: translateY(-100vh) scale(0.4); }
        }

        /* Content box */
        .content-box { 
            position: relative; margin-left: 8%; width: 600px; z-index: 10; 
        }

        /* Small label above heading */
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

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        /* Heading */
        .content-box h1 { 
            font-size: 4.5rem; font-weight: 800; line-height: 1.05; 
            color: #111; text-transform: uppercase; margin-bottom: 8px;
        }

        .content-box h1 .line-green {
            color: var(--green); display: block; font-size: 3.8rem;
        }

        /* Letter-by-letter reveal */
        .rl {
            display: inline-block !important;
            opacity: 0;
            transform: translateY(50px);
            animation: letterUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }

        @keyframes letterUp {
            to { opacity: 1; transform: translateY(0); }
        }

        /* Green underline accent */
        .heading-accent {
            width: 80px; height: 4px; background: var(--green);
            border-radius: 4px; margin: 16px 0 20px;
            opacity: 0; transform: scaleX(0); transform-origin: left;
            animation: lineGrow 0.8s 1.6s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }

        @keyframes lineGrow {
            to { opacity: 1; transform: scaleX(1); }
        }

        /* Subtitle */
        .content-box .subtitle {
            font-size: 1.1rem; color: #555; line-height: 1.7;
            max-width: 480px; margin-bottom: 32px;
            opacity: 0; transform: translateY(20px);
            animation: fadeUp 0.7s 1.8s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }

        @keyframes fadeUp {
            to { opacity: 1; transform: translateY(0); }
        }

        /* CTA Button */
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

        .cta-btn::before {
            content: ''; position: absolute; inset: -3px;
            border-radius: 14px; background: var(--green);
            opacity: 0; z-index: -1;
            animation: pulseGlow 2.5s ease-in-out infinite;
        }

        @keyframes pulseGlow {
            0%, 100% { opacity: 0; transform: scale(1); }
            50% { opacity: 0.3; transform: scale(1.06); }
        }

        .cta-btn::after {
            content: ''; position: absolute;
            top: 0; left: -100%; width: 60%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.25), transparent);
            animation: shineSweep 3s 2.5s ease-in-out infinite;
        }

        @keyframes shineSweep {
            0%   { left: -100%; }
            30%  { left: 150%; }
            100% { left: 150%; }
        }

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

        /* Photo circle */
        .photo-circle { 
            position: absolute; right: -5%; top: 70%; transform: translateY(-50%); 
            width: 100vh; height: 80vh; border-radius: 50%; background: var(--green); 
            z-index: 2; border: 15px solid var(--dark-bg); 
            background-image: url('library.png'); background-size: cover;
            opacity: 0; transform: translateY(-50%) scale(0.8);
            animation: circleIn 1s 0.3s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }

        @keyframes circleIn {
            to { opacity: 1; transform: translateY(-50%) scale(1); }
        }

        .photo-circle::before {
            content: ''; position: absolute; inset: -25px;
            border-radius: 50%; border: 2px solid rgba(0, 200, 83, 0.15);
            animation: ringPulse 3s ease-in-out infinite;
        }

        .photo-circle::after {
            content: ''; position: absolute; inset: -45px;
            border-radius: 50%; border: 1px solid rgba(0, 200, 83, 0.08);
            animation: ringPulse 3s 0.8s ease-in-out infinite;
        }

        @keyframes ringPulse {
            0%, 100% { opacity: 0.5; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.02); }
        }

        .overlap-img { position: absolute; bottom: 0; width: auto; z-index: 10; pointer-events: none; }
        .pic-left { height: 130% !important; left: 35% !important; transform: translateX(-50%); }
        .pic-right { height: 120% !important; left: 60% !important; transform: translateX(-50%); z-index: 0; }

        /* Scroll indicator */
        .scroll-indicator {
            position: absolute; bottom: 30px; left: 50%;
            transform: translateX(-50%); z-index: 20;
            display: flex; flex-direction: column; align-items: center; gap: 8px;
            opacity: 0; animation: fadeUp 0.7s 2.8s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }

        .scroll-indicator span {
            font-size: 0.7rem; color: #999; text-transform: uppercase;
            letter-spacing: 0.15em; font-weight: 600;
        }

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

        @keyframes scrollDot {
            0%   { opacity: 1; top: 6px; }
            100% { opacity: 0; top: 20px; }
        }

        /* --- ABOUT SECTION --- */
        #about-section { 
            position: relative; background: #0b1121; height: 100vh; 
            display: flex; align-items: center; justify-content: center; padding: 0 5%;
        }
        
        .team-container { 
            display: flex; gap: 50px; z-index: 10; width: 100%; 
            justify-content: center; align-items: flex-end;
            transition: 0.8s ease-in-out; 
            height: 100%; padding-bottom: 20px;
        }
        
        .member-card { cursor: pointer; text-align: center; transition: 0.5s; position: relative; }
        .member-card h3 { display: none; } 

        .member-card img { 
            height: 550px; width: auto; 
            filter: drop-shadow(0 0 15px rgba(0,200,83,0.3)); 
            transition: 0.8s; 
        }

        .about-text-center {
            text-align: center; margin-bottom: 120px;
            z-index: 5; transition: 0.5s; flex-shrink: 0;
        }

        .expanded .member-card:not(.active) { opacity: 0; transform: scale(0); pointer-events: none; position: absolute; }
        .expanded .about-text-center { display: none; }

        .member-card.active { display: flex; align-items: center; justify-content: flex-start; width: 100%; position: relative; left: 0; }
        .member-card.active img { height: 90vh !important; filter: drop-shadow(0 0 30px var(--green)); }

        .member-info { display: none; text-align: left; margin-left: 60px; max-width: 600px; }
        .member-card.active .member-info { display: block; animation: fadeIn 0.5s forwards; }

        @keyframes fadeIn { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }

        .member-info h2 { font-family: 'Orbitron'; font-size: 3.5rem; color: var(--green); margin-bottom: 20px; }
        .member-info p { font-size: 1.2rem; line-height: 1.8; color: #cbd5e1; margin-bottom: 30px; }

        .back-btn {
            background: transparent; border: 2px solid var(--green); color: var(--green);
            padding: 12px 30px; font-family: 'Orbitron'; cursor: pointer; border-radius: 5px; text-transform: uppercase;
            transition: all 0.3s;
        }
        .back-btn:hover {
            background: var(--green); color: #fff;
            box-shadow: 0 0 20px var(--green-glow);
        }

        /* --- CONTACT SECTION --- */
        #contact-section { padding: 120px 8%; background: var(--dark-bg); text-align: center; min-height: 100vh; }
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

        /* Scroll reveal */
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
            .photo-circle { display: none; }
            .stats-row { flex-direction: column; gap: 16px; }
            .nav-links { gap: 20px; }
            .nav-links li a { font-size: 0.8rem; }
        }
    </style>
</head>
<body>

    <header>
        <div class="logo">GRANT<span>GATE</span></div>
        <nav class="navbar">
            <ul class="nav-links">
                <li><a href="#home" data-text="HOME">HOME</a></li>
                <li><a href="#about-section" data-text="ABOUT US">ABOUT US</a></li>
                <li><a href="#contact-section" data-text="CONTACT US">CONTACT US</a></li>
            </ul>
        </nav>
    </header>

    <section id="home">
        <div class="white-bg-layer"></div>
        <div class="home-particles" id="homeParticles"></div>

        <div class="content-box">
            <div class="content-label">
                <i class="fas fa-circle"></i> Official Scholarship Portal
            </div>

            <h1 id="mainHeading"></h1>

            <div class="heading-accent"></div>

            <p class="subtitle">Ready to level up? Access scholarship opportunities, submit applications, and track your status — all in one place.</p>

            <a href="register.php" class="cta-btn">
                APPLY NOW <i class="fas fa-arrow-right"></i>
            </a>

            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-num"><span class="gn">500</span>+</div>
                    <div class="stat-label">Scholarships</div>
                </div>
                <div class="stat-item">
                    <div class="stat-num"><span class="gn">1K</span>+</div>
                    <div class="stat-label">Students Helped</div>
                </div>
                <div class="stat-item">
                    <div class="stat-num"><span class="gn">100</span>%</div>
                    <div class="stat-label">Free Access</div>
                </div>
            </div>
        </div>

        <div class="photo-circle">
            <img src="james.png" class="overlap-img pic-left">
            <img src="janes.png" class="overlap-img pic-right">
        </div>

        <div class="scroll-indicator">
            <span>Scroll</span>
            <div class="scroll-mouse"></div>
        </div>
    </section>

    <section id="about-section">
        <div class="team-container" id="teamBox">
            <div class="member-card" id="card-james" onclick="expandMember(this)">
                <img src="jamescut.png">
                <div class="member-info">
                    <h2>JIREH JAMES CEPADA</h2>
                    <p>I am a student who values hard work and community. Through GrantGate, I want to help my fellow students find scholarship opportunities more easily so that we can all succeed in our studies.</p>
                    <button class="back-btn" onclick="resetTeam(event)">← Back</button>
                </div>
            </div>

            <div class="about-text-center" style="margin-bottom: 250px;">
                <h2 style="font-family:'Orbitron'; color:var(--green); font-size: 2.8rem; margin-bottom: 20px;">GRANTGATE CORE</h2>
                <p style="color:#cbd5e1; max-width: 350px; font-size: 1.1rem; margin: 0 auto;">Dedicated to providing seamless scholarship opportunities and innovative student solutions.</p>
            </div>

            <div class="member-card" id="card-nes" onclick="expandMember(this)">
                <img src="janescut.png">
                <div class="member-info">
                    <h2>JNES PATRIANA</h2>
                    <p>I am a student who believes in the power of education. Through this project, I aim to contribute to a system that makes scholarship opportunities more accessible and organized for students.</p>
                    <button class="back-btn" onclick="resetTeam(event)">← Back</button>
                </div>
            </div>
        </div>
    </section>

    <section id="contact-section">
        <h1 class="reveal" style="font-family:'Orbitron'; font-size: 3.2rem;">CONTACT <span style="color:var(--green);">US</span></h1>
        <div class="contact-grid">
            <div class="contact-card reveal">
                <h3><i class="fas fa-envelope" style="margin-right:8px;"></i>EMAIL</h3>
                <p>grantgate@gmail.com</p>
            </div>
            <div class="contact-card reveal">
                <h3><i class="fas fa-map-marker-alt" style="margin-right:8px;"></i>LOCATION</h3>
                <p>Manolo Fortich, Bukidnon</p>
            </div>
            <div class="contact-card reveal">
                <h3><i class="fas fa-phone" style="margin-right:8px;"></i>PHONE</h3>
                <p>+63 921 334 6009</p>
            </div>
        </div>
    </section>

    <script>
        /* LETTER-BY-LETTER HEADING REVEAL */
        (function animateHeading() {
            const heading = document.getElementById('mainHeading');
            const line1 = 'Welcome!';
            const line2 = 'To GrantGate';

            let html = '';
            let delay = 0.5;

            for (let i = 0; i < line1.length; i++) {
                const ch = line1[i];
                if (ch === ' ') {
                    html += ' ';
                } else {
                    html += '<span class="rl" style="animation-delay:' + delay.toFixed(2) + 's">' + ch + '</span>';
                    delay += 0.04;
                }
            }

            html += '<span class="line-green">';
            for (let i = 0; i < line2.length; i++) {
                const ch = line2[i];
                if (ch === ' ') {
                    html += ' ';
                } else {
                    html += '<span class="rl" style="animation-delay:' + delay.toFixed(2) + 's">' + ch + '</span>';
                    delay += 0.04;
                }
            }
            html += '</span>';

            heading.innerHTML = html;
        })();

        /* HOME PARTICLES */
        (function initHomeParticles() {
            const layer = document.getElementById('homeParticles');
            const count = 20;
            for (let i = 0; i < count; i++) {
                const p = document.createElement('div');
                p.classList.add('h-particle');
                const size = Math.random() * 4 + 2;
                p.style.width = size + 'px';
                p.style.height = size + 'px';
                p.style.left = Math.random() * 55 + '%';
                p.style.bottom = '-5%';
                p.style.animationDuration = (Math.random() * 10 + 8) + 's';
                p.style.animationDelay = (Math.random() * 12) + 's';
                layer.appendChild(p);
            }
        })();

        /* SCROLL REVEAL */
        const revealEls = document.querySelectorAll('.reveal');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) entry.target.classList.add('visible');
            });
        }, { threshold: 0.15 });
        revealEls.forEach(el => observer.observe(el));

        /* ABOUT SECTION — expand / collapse */
        function expandMember(element) {
            const teamBox = document.getElementById('teamBox');
            if (!teamBox.classList.contains('expanded')) {
                teamBox.classList.add('expanded');
                element.classList.add('active');
            }
        }

        function resetTeam(event) {
            event.stopPropagation();
            const teamBox = document.getElementById('teamBox');
            const cards = document.querySelectorAll('.member-card');
            teamBox.classList.remove('expanded');
            cards.forEach(card => card.classList.remove('active'));
        }

        /* ACTIVE NAV LINK on scroll */
        const sections = document.querySelectorAll('section');
        const navLinks = document.querySelectorAll('.nav-links li a');

        window.addEventListener('scroll', () => {
            let current = '';
            sections.forEach(section => {
                const top = section.offsetTop - 120;
                if (scrollY >= top) current = section.getAttribute('id');
            });
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) link.classList.add('active');
            });
        });
    </script>
</body>
</html>