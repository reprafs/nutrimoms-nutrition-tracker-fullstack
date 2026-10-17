<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: pages/dietary.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="manifest" href="/nutrimoms/manifest.json">
    <link rel="apple-touch-icon" href="assets/img/iconnutrimoms.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=375, user-scalable=no">
    <title>NutriMoms — Selamat Datang</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        :root {
            --green:      #6FA87A;
            --green-dark: #528260;
            --green-pale: #EAF1E8;
            --green-mid:  #B8D9BE;
            --white:      #FFFFFF;
            --text:       #1E2A22;
            --muted:      #7A8C7E;
            --bg:         #F4F8F4;
        }

        html, body {
            width: 100%; height: 100%;
            overflow: hidden;
            background: var(--bg);
            font-family: 'DM Sans', sans-serif;
            display: flex; justify-content: center;
        }

        .app {
            width: 375px; height: 100vh;
            overflow: hidden;
            position: relative;
            background: var(--bg);
        }

        /* ── SLIDER ── */
        .slider {
            display: flex;
            width: calc(375px * 3);
            height: 100vh;
            transition: transform .45s cubic-bezier(.65,0,.35,1);
        }

        .slide {
            width: 375px; height: 100vh;
            display: flex; flex-direction: column;
            flex-shrink: 0;
        }

        /* ── VISUAL AREA ── */
        .visual {
            flex: 0 0 auto;
            height: 56vh;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .visual::before {
            content: '';
            position: absolute; inset: 0; z-index: 0;
        }

        .slide-1 .visual { background: var(--green-pale); }
        .slide-1 .visual::before {
            background: radial-gradient(ellipse 80% 70% at 60% 30%, #C8E6CC 0%, transparent 70%);
        }
        .slide-2 .visual { background: var(--green-pale); }
        .slide-2 .visual::before {
            background: radial-gradient(ellipse 75% 65% at 40% 35%, #D0E4FF 0%, transparent 70%);
        }
        .slide-3 .visual { background: var(--green-pale); }
        .slide-3 .visual::before {
            background: radial-gradient(ellipse 80% 70% at 55% 30%, #FFD9BC 0%, transparent 70%);
        }

        /* ── SHARED mock-screen ── */
        .mock-screen {
            border-radius: 18px;
            box-shadow: 0 12px 36px rgba(0,0,0,.13);
            background: var(--white);
            overflow: hidden;
            flex-shrink: 0;
        }
        .mock-screen img {
            width: 100%; height: 100%;
            object-fit: cover; display: block;
        }

        /* ══════════════════════════════════
           SLIDE 1 — 2 foto side by side (2x1)
           ══════════════════════════════════ */
        .img-slot-s1 {
            position: relative; z-index: 1;
            display: flex;
            gap: 12px;
            align-items: flex-end;
            justify-content: center;
            width: 140%;
            padding: 20px 24px 0;        /* ruang atas & kiri kanan */
            box-sizing: border-box;
        }
        .img-slot-s1 .mock-screen {
            width: calc(50% - 6px);      /* setengah lebar minus setengah gap */
            height: 300px;
            border-radius: 16px;
        }
        /* foto kiri sedikit lebih tinggi */
        .img-slot-s1 .mock-screen:first-child {
            transform: translateY(12px);
        }

        /* ══════════════════════════════════
           SLIDE 2 — 4 foto grid 2x2
           ══════════════════════════════════ */
        .img-slot-s2 {
            position: relative; z-index: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
            gap: 10px;
            width: 100%;
            padding: 20px 24px 12px;
            box-sizing: border-box;
            height: calc(56vh - 0px);   /* fill visual area */
        }
        .img-slot-s2 .mock-screen {
            width: 100%; height: 100%;
            border-radius: 14px;
            min-height: 0;              /* agar grid bisa shrink */
        }

        /* ══════════════════════════════════
           SLIDE 3 — atas 2x1, bawah 1 tengah
           ══════════════════════════════════ */
        .img-slot-s3 {
            position: relative; z-index: 1;
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
            padding: 20px 24px 16px;
            box-sizing: border-box;
            height: calc(56vh - 0px);
        }
        /* baris atas: 2 foto side by side */
        .img-slot-s3 .row-top {
            display: flex;
            gap: 10px;
            flex: 1;
        }
        .img-slot-s3 .row-top .mock-screen {
            flex: 1;
            height: 100%;
            border-radius: 14px;
        }
        /* baris bawah: 1 foto di tengah, lebar 55% */
        .img-slot-s3 .row-bottom {
            display: flex;
            justify-content: center;
            flex: 1.1;
        }
        .img-slot-s3 .row-bottom .mock-screen {
            width: 55%;
            height: 60%;
            border-radius: 14px;
        }

        /* ── COPY AREA ── */
        .copy {
            flex: 1;
            display: flex; flex-direction: column;
            padding: 28px 28px 100px;   /* 100px buat ruang bottom-nav */
            background: var(--white);
            border-radius: 28px 28px 0 0;
            margin-top: -24px;
            position: relative; z-index: 2;
        }

        .slide-tag {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--green-pale);
            color: var(--green-dark);
            font-size: 11px; font-weight: 600;
            letter-spacing: .06em; text-transform: uppercase;
            padding: 5px 12px; border-radius: 99px;
            align-self: flex-start; margin-bottom: 14px;
        }
        .slide-tag svg { width: 13px; height: 13px; fill: var(--green); }

        .copy h2 {
            font-size: 26px; font-weight: 700;
            color: var(--text); line-height: 1.25; margin-bottom: 10px;
        }
        .copy h2 em { font-style: normal; color: var(--green); }

        .copy p {
            font-size: 15px; color: var(--muted);
            line-height: 1.6; flex: 1;
        }

        /* ── BOTTOM NAV ── */
        .bottom-nav {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            padding: 0 28px 36px;
            background: var(--white);
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
            z-index: 10;
        }

        /* Next / Mulai button */
        .next-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            background: var(--green);
            color: var(--white);

            border: none;
            border-radius: 99px;

            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            font-weight: 700;

            padding: 14px 20px; /* diperkecil */
            
            cursor: pointer;

            transition: background .2s, transform .15s;

            margin-bottom: 10px; /* naik ke atas */
        }
        .next-btn:active { transform: scale(.97); }
        .next-btn:hover  { background: var(--green-dark); }
        .next-btn svg {
            width: 18px; height: 18px;
            fill: none; stroke: var(--white);
            stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round;
            transition: transform .2s;
        }
        .next-btn:hover svg { transform: translateX(3px); }

        /* Row bawah: dots + lewati */
        .nav-bottom-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .dots {
            display: flex; gap: 6px; align-items: center;
        }
        .dot {
            height: 8px; border-radius: 99px;
            background: var(--green-mid);
            transition: width .35s cubic-bezier(.65,0,.35,1), background .35s;
        }
        .dot.active { width: 28px; background: var(--green); }
        .dot:not(.active) { width: 8px; }

        /* Lewati text button */
        .skip-btn {
            background: transparent; border: none;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px; font-weight: 500;
            color: var(--muted); cursor: pointer;
            padding: 4px 0;
            transition: color .2s;
        }
        .skip-btn:hover { color: var(--green); }
    </style>
</head>
<body>
<div class="app">

    <div class="slider" id="slider">

        <!-- ════ SLIDE 1: 2 foto 2x1 ════ -->
        <div class="slide slide-1">
            <div class="visual">
                <div class="img-slot-s1">
                    <div class="mock-screen">
                        <img src="assets/img/slide1ke1.jpg" alt="App preview 1">
                    </div>
                    <div class="mock-screen">
                        <img src="assets/img/slide1ke2.jpg" alt="App preview 2">
                    </div>
                </div>
            </div>
            <div class="copy">
                <span class="slide-tag">
                    <svg viewBox="0 0 16 16"><path d="M8 1l1.8 3.6 4 .6-2.9 2.8.7 4L8 10l-3.6 1.9.7-4L2.2 5.2l4-.6z"/></svg>
                    Mudah Digunakan
                </span>
                <h2>Tracking nutrisi yang <em>simpel</em> & nyaman</h2>
                <p>Antarmuka yang bersih dan intuitif, dirancang khusus agar kamu bisa catat gizi si kecil tanpa ribet.</p>
            </div>
        </div>

        <!-- ════ SLIDE 2: 4 foto grid 2x2 ════ -->
        <div class="slide slide-2">
            <div class="visual">
                <div class="img-slot-s2">
                    <div class="mock-screen">
                        <img src="assets/img/slide2ke1.jpg" alt="Grafik nutrisi">
                    </div>
                    <div class="mock-screen">
                        <img src="assets/img/slide2ke2.jpg" alt="Dashboard statistik">
                    </div>
                    <div class="mock-screen">
                        <img src="assets/img/slide2ke3.jpg" alt="Data harian">
                    </div>
                    <div class="mock-screen">
                        <img src="assets/img/slide2ke4.jpg" alt="Ringkasan nutrisi">
                    </div>
                </div>
            </div>
            <div class="copy">
                <span class="slide-tag">
                    <svg viewBox="0 0 16 16"><rect x="2" y="9" width="3" height="5" rx="1"/><rect x="6.5" y="6" width="3" height="8" rx="1"/><rect x="11" y="3" width="3" height="11" rx="1"/></svg>
                    Data Akurat
                </span>
                <h2>Informasi gizi yang <em>lengkap</em> & terpercaya</h2>
                <p>Pantau kalori, makronutrien, dan mikronutrien harian dengan data yang akurat berdasarkan referensi ilmiah.</p>
            </div>
        </div>

        <!-- ════ SLIDE 3: atas 2x1, bawah 1 tengah ════ -->
        <div class="slide slide-3">
            <div class="visual">
                <div class="img-slot-s3">
                    <div class="row-top">
                        <div class="mock-screen">
                            <img src="assets/img/slide3ke1.jpg" alt="Target nutrisi">
                        </div>
                        <div class="mock-screen">
                            <img src="assets/img/slide3ke2.jpg" alt="Progress tracker">
                        </div>
                    </div>
                    <div class="row-bottom">
                        <div class="mock-screen">
                            <img src="assets/img/slide3ke3.jpg" alt="Personal goal">
                        </div>
                    </div>
                </div>
            </div>
            <div class="copy">
                <span class="slide-tag">
                    <svg viewBox="0 0 16 16"><circle cx="8" cy="8" r="6" stroke-width="2" fill="none" stroke="currentColor"/><circle cx="8" cy="8" r="2.5" fill="currentColor"/></svg>
                    Personalisasi
                </span>
                <h2>Target gizi sesuai <em>kebutuhan unik</em> si kecil</h2>
                <p>Atur goal diet, pantau progress mingguan, dan sesuaikan target nutrisi berdasarkan usia dan aktivitas anak.</p>
            </div>
        </div>

    </div><!-- /slider -->

    <!-- BOTTOM NAV -->
    <div class="bottom-nav">

        <button class="next-btn" id="nextBtn" onclick="next()">
            <span id="nextLabel">Lanjut</span>
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </button>

        <div class="nav-bottom-row">
            <div class="dots">
                <div class="dot active" id="dot0"></div>
                <div class="dot"        id="dot1"></div>
                <div class="dot"        id="dot2"></div>
            </div>
            <button class="skip-btn" onclick="finish()">Lewati</button>
        </div>

    </div>

</div>

<script>
    let current = 0;
    const total  = 3;
    const slider = document.getElementById('slider');
    const dots   = [0,1,2].map(i => document.getElementById('dot'+i));
    const label  = document.getElementById('nextLabel');
    const icon   = document.getElementById('nextIcon');

    function goTo(idx) {
        current = idx;
        slider.style.transform = `translateX(-${375 * idx}px)`;
        dots.forEach((d,i) => d.classList.toggle('active', i === idx));
        if (idx === total - 1) {
            label.textContent = 'Mulai';
            icon.style.display = 'none';
        } else {
            label.textContent = 'Lanjut';
            icon.style.display = '';
        }
    }

    function next() {
        if (current < total - 1) { goTo(current + 1); } else { finish(); }
    }

    function finish() {
        localStorage.setItem('nutrikids_onboarding_done', '1');
        window.location.href = 'auth/welcome.php';
    }

    let startX = 0;
    const app = document.querySelector('.app');
    app.addEventListener('touchstart', e => { startX = e.touches[0].clientX; }, {passive:true});
    app.addEventListener('touchend',   e => {
        const dx = e.changedTouches[0].clientX - startX;
        if (Math.abs(dx) < 40) return;
        if (dx < 0 && current < total - 1) goTo(current + 1);
        if (dx > 0 && current > 0)         goTo(current - 1);
    }, {passive:true});
</script>
</body>
</html>