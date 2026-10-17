<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name            = trim($_POST['name']             ?? '');
    $email           = trim($_POST['email']            ?? '');
    $password        =      $_POST['password']         ?? '';
    $confirmPassword =      $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        $_SESSION['error'] = "Semua field wajib diisi.";
        header("Location: register.php"); exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Format email tidak valid.";
        header("Location: register.php"); exit;
    }
    if (strlen($password) < 6) {
        $_SESSION['error'] = "Password minimal 6 karakter.";
        header("Location: register.php"); exit;
    }
    if ($password !== $confirmPassword) {
        $_SESSION['error'] = "Konfirmasi password tidak cocok.";
        header("Location: register.php"); exit;
    }

    try {
        $db    = getDB();
        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $_SESSION['error'] = "Email sudah terdaftar.";
            header("Location: register.php"); exit;
        }

        $insert = $db->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $insert->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);

        $_SESSION['user_id']   = $db->lastInsertId();
        $_SESSION['user_name'] = $name;
        header("Location: ../pages/setup.php"); exit;

    } catch (PDOException $e) {
        $_SESSION['error'] = "Terjadi kesalahan sistem.";
        header("Location: register.php"); exit;
    }
}

if (isset($_SESSION['user_id'])) {
    header("Location: ../pages/dietary.php"); exit;
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="manifest" href="/nutrimoms/manifest.json">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=375, user-scalable=no">
    <title>Daftar — NutriKids</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        :root {
            --green:      #6FA87A;
            --green-dark: #528260;
            --green-pale: #EAF1E8;
            --white:      #FFFFFF;
            --bg:         #F4F8F4;
            --text:       #1E2A22;
            --muted:      #7A8C7E;
            --border:     #E2EDE3;
            --input-bg:   #F0F6F1;
            --error-bg:   #FFF0F0;
            --error-text: #C0392B;
        }

        html, body {
            width: 100%; min-height: 100%;
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            display: flex; justify-content: center;
        }

        .app {
            width: 375px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 52px 20px 40px;
        }

        .back-btn {
            width: 40px; height: 40px;
            border-radius: 12px;
            border: 1.5px solid var(--border);
            background: var(--white);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; text-decoration: none;
            transition: background .15s;
            margin-bottom: 28px;
            align-self: flex-start;
        }
        .back-btn:hover { background: var(--green-pale); }
        .back-btn svg {
            width: 18px; height: 18px;
            fill: none; stroke: var(--text);
            stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round;
        }

        .page-header { margin-bottom: 28px; }
        .page-header h1 {
            font-size: 28px; font-weight: 700;
            color: var(--text); letter-spacing: -.4px; line-height: 1.2;
            margin-bottom: 8px;
        }
        .page-header p { font-size: 14px; color: var(--muted); line-height: 1.55; }
        .accent-dot {
            display: inline-block; width: 8px; height: 8px;
            background: var(--green); border-radius: 50%;
            margin-left: 3px; vertical-align: middle;
        }

        /* Card */
        .card {
            background: var(--white);
            border-radius: 24px;
            padding: 24px;
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* Error */
        .error-box {
            background: var(--error-bg);
            border: 1px solid #F5C6C6;
            border-radius: 12px; padding: 12px 14px;
            display: flex; align-items: flex-start; gap: 10px;
        }
        .error-box svg {
            flex-shrink: 0; width: 17px; height: 17px;
            fill: none; stroke: var(--error-text);
            stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
            margin-top: 1px;
        }
        .error-box span { font-size: 13px; color: var(--error-text); line-height: 1.5; }

        /* Field */
        .field { display: flex; flex-direction: column; gap: 7px; }
        .field label { font-size: 13px; font-weight: 600; color: var(--text); }
        .input-wrap { position: relative; }
        .input-icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            width: 17px; height: 17px;
            fill: none; stroke: var(--muted);
            stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round;
            pointer-events: none;
        }
        .field input {
            width: 100%; height: 52px;
            background: var(--input-bg);
            border: 1.5px solid transparent; border-radius: 14px;
            padding: 0 44px 0 42px;
            font-family: 'DM Sans', sans-serif; font-size: 15px; color: var(--text);
            outline: none;
            transition: border-color .2s, background .2s;
        }
        .field input::placeholder { color: #AABFAC; }
        .field input:focus { border-color: var(--green); background: var(--white); }

        .toggle-pw {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; padding: 4px;
            display: flex; align-items: center;
        }
        .toggle-pw svg {
            width: 17px; height: 17px;
            fill: none; stroke: var(--muted);
            stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round;
        }

        .pw-hint { font-size: 12px; color: #AABFAC; padding-left: 2px; }

        /* Divider between fields */
        .field-divider {
            height: 1px; background: var(--border); margin: 0 -24px;
        }

        .btn-submit {
            width: 100%; height: 54px;
            background: var(--green); color: var(--white);
            border: none; border-radius: 14px;
            font-family: 'DM Sans', sans-serif; font-size: 15px; font-weight: 700;
            cursor: pointer; margin-top: 4px;
            transition: background .2s, transform .15s;
        }
        .btn-submit:hover  { background: var(--green-dark); }
        .btn-submit:active { transform: scale(.98); }

        .bottom-link {
            text-align: center; font-size: 14px; color: var(--muted);
            margin-top: 20px;
        }
        .bottom-link a { color: var(--green); font-weight: 700; text-decoration: none; }
        .bottom-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="app">

    <a href="welcome.php" class="back-btn" aria-label="Kembali">
        <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
    </a>

    <div class="page-header">
        <h1>Buat akun<br>baru<span class="accent-dot"></span></h1>
        <p>Daftarkan dirimu dan mulai pantau gizi si kecil hari ini.</p>
    </div>

    <div class="card">

        <?php if ($error): ?>
        <div class="error-box">
            <svg viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <form action="register.php" method="POST" style="display:contents;">

            <div class="field">
                <label for="name">Nama Lengkap</label>
                <div class="input-wrap">
                    <svg class="input-icon" viewBox="0 0 24 24">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                    </svg>
                    <input type="text" id="name" name="name"
                        placeholder="Nama kamu" autocomplete="name" required>
                </div>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <div class="input-wrap">
                    <svg class="input-icon" viewBox="0 0 24 24">
                        <rect x="2" y="4" width="20" height="16" rx="3"/>
                        <polyline points="2,4 12,13 22,4"/>
                    </svg>
                    <input type="email" id="email" name="email"
                        placeholder="nama@email.com" autocomplete="email" required>
                </div>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <svg class="input-icon" viewBox="0 0 24 24">
                        <rect x="3" y="11" width="18" height="11" rx="2"/>
                        <path d="M7 11V7a5 5 0 0110 0v4"/>
                    </svg>
                    <input type="password" id="password" name="password"
                        placeholder="Min. 6 karakter" autocomplete="new-password" required>
                    <button type="button" class="toggle-pw" onclick="togglePw('password','eye1')" aria-label="Lihat password">
                        <svg id="eye1" viewBox="0 0 24 24">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
                <span class="pw-hint">Minimal 6 karakter</span>
            </div>

            <div class="field">
                <label for="confirm_password">Konfirmasi Password</label>
                <div class="input-wrap">
                    <svg class="input-icon" viewBox="0 0 24 24">
                        <rect x="3" y="11" width="18" height="11" rx="2"/>
                        <path d="M7 11V7a5 5 0 0110 0v4"/>
                    </svg>
                    <input type="password" id="confirm_password" name="confirm_password"
                        placeholder="Ulangi password" autocomplete="new-password" required>
                    <button type="button" class="toggle-pw" onclick="togglePw('confirm_password','eye2')" aria-label="Lihat konfirmasi">
                        <svg id="eye2" viewBox="0 0 24 24">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">Buat Akun</button>

        </form>

    </div>

    <p class="bottom-link">
        Sudah punya akun? <a href="login.php">Masuk</a>
    </p>

</div>
<script>
function togglePw(inputId, iconId) {
    const inp  = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.innerHTML = `<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>`;
    } else {
        inp.type = 'password';
        icon.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
    }
}
</script>
</body>
</html>