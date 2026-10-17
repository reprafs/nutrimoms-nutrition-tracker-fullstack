<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: ../pages/dietary.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="manifest" href="/nutrimoms/manifest.json">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=375, user-scalable=no">
    <title>NutriKids</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        :root {
            --green:      #6FA87A;
            --green-dark: #528260;
            --green-pale: #EAF1E8;
            --white:      #FFFFFF;
            --text:       #1E2A22;
            --muted:      #7A8C7E;
        }

        html, body {
            width: 100%; height: 100%;
            font-family: 'DM Sans', sans-serif;
            background: var(--green);
            display: flex;
            justify-content: center;
            overflow: hidden;
        }

        .app {
            width: 375px;
            height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        /* ── GREEN HERO ── */
        .hero {
            flex: 1;
            background: var(--green);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0 32px;
            position: relative;
            overflow: hidden;
        }

        /* Decorative circles */
        .hero::before {
            content: '';
            position: absolute;
            width: 320px; height: 320px;
            border-radius: 50%;
            border: 1.5px solid rgba(255,255,255,.12);
            top: -80px; right: -80px;
        }
        .hero::after {
            content: '';
            position: absolute;
            width: 200px; height: 200px;
            border-radius: 50%;
            background: rgba(255,255,255,.06);
            bottom: 40px; left: -60px;
        }

        .hero-icon {
            width: 76px;
            height: 76px;

            background: #fff; /* background putih */

            border-radius: 50%; /* jadi bulat */

            display: flex;
            align-items: center;
            justify-content: center;

            margin-bottom: 24px;

            position: relative;
            z-index: 1;

            overflow: hidden;
        }

        .hero-icon img {
            margin-top:5px;
            width: 200px;
            height: 2000px;
            object-fit: contain;
        }

        .hero-icon svg {
            width: 40px; height: 40px;
            fill: none;
            stroke: var(--white);
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .hero h1 {
            font-size: 36px;
            font-weight: 700;
            color: var(--white);
            letter-spacing: -.5px;
            text-align: center;
            line-height: 1.1;
            position: relative;
            z-index: 1;
            margin-top: -12px;
            margin-bottom: 24px;
        }

        .hero p {
            font-size: 15px;
            color: rgba(255,255,255,.72);
            text-align: center;
            line-height: 1.55;
            position: relative;
            z-index: 1;
            max-width: 270px;
        }

        /* ── WHITE SHEET ── */
        .sheet {
            flex: 0 0 auto;
            background: var(--white);
            border-radius: 28px 28px 0 0;
            padding: 36px 24px 44px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .sheet-label {
            font-size: 13px;
            color: var(--muted);
            font-weight: 500;
            text-align: center;
            margin-bottom: 4px;
        }

        /* Primary button */
        .btn-primary {
            width: 100%;
            height: 58px;
            background: var(--green);
            color: var(--white);
            border: none;
            border-radius: 18px;
            font-family: 'DM Sans', sans-serif;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background .2s, transform .15s;
        }
        .btn-primary:hover  { background: var(--green-dark); }
        .btn-primary:active { transform: scale(.98); }

        /* Ghost / outline button */
        .btn-ghost {
            width: 100%;
            height: 58px;
            background: transparent;
            color: var(--text);
            border: 1.5px solid #D8E8D9;
            border-radius: 18px;
            font-family: 'DM Sans', sans-serif;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background .2s, border-color .2s, transform .15s;
        }
        .btn-ghost:hover  { background: var(--green-pale); border-color: var(--green); }
        .btn-ghost:active { transform: scale(.98); }

        .btn-icon {
            width: 20px; height: 20px;
            flex-shrink: 0;
        }
        .btn-icon-stroke {
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #C5D4C7;
            font-size: 13px;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #E8EFE9;
        }
    </style>
</head>
<body>
<div class="app">

    <!-- HERO -->
    <div class="hero">
        <div class="hero-icon">
            <img src="../assets/img/iconnutrimoms.png" alt="Nutrimoms Icon">
        </div>

        <h1>Nutrimoms</h1>

        <p>
            Pantau gizi si kecil dengan mudah, akurat, dan menyenangkan.
        </p>
    </div>

    <!-- SHEET -->
    <div class="sheet">
        <p class="sheet-label">Masuk atau buat akun baru</p>

        <a href="login.php" style="text-decoration:none; display:block;">
            <button class="btn-primary" style="width:100%;">
                <svg class="btn-icon" viewBox="0 0 24 24">
                    <path class="btn-icon-stroke" d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/>
                    <polyline class="btn-icon-stroke" points="10 17 15 12 10 7"/>
                    <line class="btn-icon-stroke" x1="15" y1="12" x2="3" y2="12"/>
                </svg>
                Masuk
            </button>
        </a>

        <div class="divider">atau</div>

        <a href="register.php" style="text-decoration:none; display:block;">
            <button class="btn-ghost" style="width:100%;">
                <svg class="btn-icon" viewBox="0 0 24 24">
                    <circle class="btn-icon-stroke" cx="12" cy="8" r="4"/>
                    <path class="btn-icon-stroke" d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                    <path class="btn-icon-stroke" d="M19 8h2M20 7v2"/>
                </svg>
                Buat Akun
            </button>
        </a>
    </div>

</div>
</body>
</html>