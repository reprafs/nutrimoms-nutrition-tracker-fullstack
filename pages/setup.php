<?php
session_start();

require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$db     = getDB();
$userId = $_SESSION['user_id'];

// Kalau sudah punya profile, langsung ke dietary
$checkProfile = $db->prepare("SELECT id FROM profiles WHERE user_id = ? LIMIT 1");
$checkProfile->execute([$userId]);
if ($checkProfile->fetch()) {
    header("Location: dietary.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $profileName   = trim($_POST['profile_name']         ?? '');
    $birthDate     = trim($_POST['birth_date']           ?? '');
    $gender        = trim($_POST['gender']               ?? '');
    $height        = trim($_POST['height']               ?? '');
    $weight        = trim($_POST['weight']               ?? '');
    $activityLevel = trim($_POST['activity_level']       ?? '');
    $targetType    = trim($_POST['nutrition_target_type'] ?? '');

    if (
        empty($profileName) || empty($birthDate) || empty($gender) ||
        empty($height)      || empty($weight)    || empty($activityLevel) ||
        empty($targetType)
    ) {
        $error = "Semua field wajib diisi.";
    } else {

        // ── Hitung usia dari birth_date ──────────────────────────────────────
        $birth      = new DateTime($birthDate);
        $now        = new DateTime();
        $ageYears   = (int)$birth->diff($now)->y;
        $ageMonths  = (int)$birth->diff($now)->m; // untuk anak < 3 tahun

        $w = (float)$weight;
        $h = (float)$height; // cm
        $isMale = strtolower($gender) === 'male';

        // ════════════════════════════════════════════════════════════════
        // KALKULASI NUTRISI
        // Anak < 18 thn  → Schofield (WHO 1985) + AKG Kemenkes 2019
        // Dewasa ≥ 18 thn → Mifflin-St Jeor (AND/ADA) + DRI IOM
        // TEE = BMR × PAL
        // ════════════════════════════════════════════════════════════════

        $isAdult = ($ageYears >= 18);

        // ── 1. BMR ───────────────────────────────────────────────────────
        if (!$isAdult) {
            // Schofield Equation — WHO 1985
            if ($ageYears < 10) {
                $bmr = $isMale
                    ? (19.59 * $w) + (1.303 * $h) + 414.9
                    : (16.97 * $w) + (1.618 * $h) + 371.2;
            } else {
                // 10–17
                $bmr = $isMale
                    ? (16.25 * $w) + (1.372 * $h) + 515.5
                    : (8.365 * $w) + (4.660 * $h) + 200.0;
            }
        } else {
            // Mifflin-St Jeor — direkomendasikan AND/ADA untuk dewasa
            $bmr = $isMale
                ? (10 * $w) + (6.25 * $h) - (5 * $ageYears) + 5
                : (10 * $w) + (6.25 * $h) - (5 * $ageYears) - 161;
        }

        // ── 2. PAL — WHO 2004 ────────────────────────────────────────────
        // Anak & dewasa pakai PAL berbeda sesuai referensi WHO
        if (!$isAdult) {
            $palMap = [
                'sedentary'   => 1.40,
                'light'       => 1.55,
                'moderate'    => 1.70,
                'active'      => 1.85,
                'very_active' => 2.00,
            ];
        } else {
            $palMap = [
                'sedentary'   => 1.40,  // desk job, hampir tidak olahraga
                'light'       => 1.55,  // aktivitas ringan / olahraga 1–3x/minggu
                'moderate'    => 1.725, // olahraga 3–5x/minggu
                'active'      => 1.90,  // olahraga intens 6–7x/minggu
                'very_active' => 2.10,  // atlet / kerja fisik berat
            ];
        }
        $pal = $palMap[$activityLevel] ?? 1.55;

        // ── 3. TEE ───────────────────────────────────────────────────────
        $tee = $bmr * $pal;

        // Energi deposisi pertumbuhan anak — WHO 2004
        if (!$isAdult) {
            $tee += 20;
        }

        // ── 4. Adjustment target_type ────────────────────────────────────
        // "Tinggi"       → +5% saja (cukup untuk naik BB secara sehat)
        // "Standard"     → TEE murni
        // "Rendah Lemak" → TEE murni, fat dibatasi di langkah makro
        // "Vegetarian"   → TEE murni, protein dinaikkan di langkah makro
        $calMultiplier = 1.0;
        if ($targetType === 'Tinggi') $calMultiplier = 1.05;

        $targetCalories = round($tee * $calMultiplier, 1);

        // ── 5. Protein ───────────────────────────────────────────────────
        if (!$isAdult) {
            // AKG Kemenkes 2019 / IOM DRI untuk anak
            if ($ageYears < 6)       $proteinPerKg = 1.12;
            elseif ($ageYears < 10)  $proteinPerKg = 1.00;
            elseif ($ageYears < 14)  $proteinPerKg = 0.95;
            else                     $proteinPerKg = 0.85; // 14–17
        } else {
            // IOM DRI dewasa: 0.8 g/kg — tapi ada floor minimum
            $proteinPerKg = 0.8;
        }

        // Vegetarian: +10% karena bioavailabilitas nabati lebih rendah
        if ($targetType === 'Vegetarian') $proteinPerKg *= 1.10;

        // Target "Tinggi" → protein sedikit dinaikkan untuk support massa otot
        if ($targetType === 'Tinggi') $proteinPerKg += 0.15;

        $rawProtein = $w * $proteinPerKg;

        // Floor minimum — AKG Kemenkes 2019 / IOM RDA
        if ($ageYears >= 18) {
            $proteinFloor = $isMale ? 62.0 : 56.0;
        } elseif ($ageYears >= 14) {
            $proteinFloor = $isMale ? 59.0 : 51.0;
        } elseif ($ageYears >= 10) {
            $proteinFloor = $isMale ? 40.0 : 40.0;
        } else {
            $proteinFloor = 19.0;
        }

        $targetProtein = round(max($rawProtein, $proteinFloor), 1);

        // ── 6. Fat ───────────────────────────────────────────────────────
        // AMDR: Anak 3–18 → 25–35% kalori, Dewasa → 20–35%
        // Rendah Lemak → batas bawah AMDR (20%)
        if ($targetType === 'Rendah Lemak') {
            $fatPct = $isAdult ? 0.20 : 0.25;
        } elseif (!$isAdult) {
            $fatPct = 0.30;
        } else {
            $fatPct = 0.275; // tengah AMDR dewasa
        }
        $targetFat = round(($targetCalories * $fatPct) / 9, 1);

        // ── 7. Karbo — sisa kalori setelah protein & fat ─────────────────
        $remainKcal  = $targetCalories - ($targetProtein * 4) - ($targetFat * 9);
        // Pastikan karbo tidak negatif
        $targetCarbs = round(max($remainKcal, 0) / 4, 1);

        // ── 8. Mikro ─────────────────────────────────────────────────────

        // Sugar — hard cap, bukan % kalori (mencegah nilai meledak)
        // WHO 2015: <25g ideal, <50g toleransi
        // Anak < 12: max 25g, remaja & dewasa: max 30g
        $targetSugar = ($ageYears < 12) ? 25.0 : 30.0;

        // Sodium — AHA / WHO / AKG Kemenkes 2019
        if ($ageYears < 4)       $targetSodium = 1000;
        elseif ($ageYears < 9)   $targetSodium = 1200;
        elseif ($ageYears < 14)  $targetSodium = 1500;
        else                     $targetSodium = 1500; // remaja & dewasa

        // Fiber — IOM 2002: 14g per 1000 kcal, dengan floor & cap
        $fiberRaw    = ($targetCalories / 1000) * 14;
        // AKG Kemenkes: anak 3–9: 16–22g, 10–18: 22–30g, dewasa: 25–38g
        if ($ageYears < 10)      $fiberFloor = 16.0;
        elseif ($ageYears < 18)  $fiberFloor = 22.0;
        else                     $fiberFloor = 25.0;
        $targetFiber = round(max($fiberRaw, $fiberFloor), 1);

        // Saturated Fat — <7% kalori (AHA konservatif)
        $targetSaturatedFat = round(($targetCalories * 0.07) / 9, 1);

        // Cholesterol — AAP/AHA
        // Rendah Lemak: <200mg, selainnya: <300mg
        $targetCholesterol = ($targetType === 'Rendah Lemak') ? 200 : 300;

        // Omega-3 — IOM AI (Adequate Intake)
        if ($ageYears < 9)       $targetOmega3 = 900;
        elseif ($ageYears < 14)  $targetOmega3 = $isMale ? 1200 : 1000;
        else                     $targetOmega3 = $isMale ? 1600 : 1100;

        try {
            $db->beginTransaction();

            // ── INSERT profiles ──────────────────────────────────────────
            $insertProfile = $db->prepare("
                INSERT INTO profiles
                    (user_id, profile_name, birth_date, gender, height, weight, activity_level, nutrition_target_type)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insertProfile->execute([
                $userId,
                $profileName,
                $birthDate,
                $gender,
                $h,
                $w,
                $activityLevel,
                $targetType
            ]);
            $profileId = $db->lastInsertId();

            // ── INSERT nutrition_targets ─────────────────────────────────
            $insertTarget = $db->prepare("
                INSERT INTO nutrition_targets
                    (profile_id,
                     target_calories, target_protein, target_carbs, target_fat,
                     target_sugar, target_sodium, target_fiber,
                     target_saturated_fat, target_cholesterol, target_omega_3)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insertTarget->execute([
                $profileId,
                $targetCalories,
                $targetProtein,
                $targetCarbs,
                $targetFat,
                $targetSugar,
                $targetSodium,
                $targetFiber,
                $targetSaturatedFat,
                $targetCholesterol,
                $targetOmega3
            ]);

            $db->commit();

            // ── Set session profile_id ───────────────────────────────────
            $_SESSION['profile_id'] = $profileId;

            header("Location: dietary.php");
            exit;

        } catch (PDOException $e) {
            $db->rollBack();
            $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=375, user-scalable=no">
    <title>Setup Profil — NutriKids</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        :root {
            --green:       #6FA87A;
            --green-dk:    #5D9669;
            --green-lt:    #EEF5F0;
            --orange:      #E8956D;
            --orange-lt:   #FEF0EC;
            --bg:     #EAF1E8;
            --bg-2:        #EDEAE4;
            --white:       #FFFFFF;
            --ink:         #1E1E1E;
            --ink-2:       #444950;
            --ink-3:       #8A8A8A;
            --r:           16px;
            --sh:          0 2px 16px rgba(0,0,0,0.07);
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--ink);
            display: flex;
            justify-content: center;
            -webkit-tap-highlight-color: transparent;
        }

        .wrapper {
            width: 375px;
            min-height: 100vh;
            background: var(--bg);
            position: relative;
        }

        /* ─── HERO ─── */
        .hero {
            background: linear-gradient(155deg, #6FA87A 0%, #5D9669 100%);
            padding: 54px 24px 32px;
        }

        .step-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.2);
            border-radius: 99px;
            padding: 5px 14px;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 18px;
        }

        .hero-title {
            font-size: 28px;
            font-weight: 700;
            color: #fff;
            line-height: 1.2;
        }

        .hero-sub {
            margin-top: 8px;
            font-size: 13px;
            color: rgba(255,255,255,0.8);
            line-height: 1.6;
        }

        /* ─── FORM AREA ─── */
        .form-area {
            padding: 24px 20px 140px;
        }

        /* ─── ERROR ─── */
        .error-box {
            background: var(--orange-lt);
            color: var(--orange);
            padding: 14px 16px;
            border-radius: var(--r);
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ─── SECTION ─── */
        .section {
            background: var(--white);
            border-radius: 20px;
            padding: 6px 16px 14px;
            box-shadow: var(--sh);
            margin-bottom: 14px;
        }

        .section-title {
            font-size: 11px;
            font-weight: 700;
            color: var(--ink-3);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 14px 0 10px;
            border-bottom: 1px solid var(--bg-2);
            margin-bottom: 4px;
        }

        /* ─── FIELD ─── */
        .field { padding: 12px 0 4px; }
        .field + .field { border-top: 1px solid var(--bg-2); }

        .field-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--ink-3);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 8px;
        }

        .input {
            width: 100%;
            height: 48px;
            border: 1.5px solid var(--bg-2);
            outline: none;
            background: var(--bg);
            border-radius: 12px;
            padding: 0 14px;
            font-size: 15px;
            font-family: 'DM Sans', sans-serif;
            color: var(--ink);
            transition: border-color 0.15s;
            appearance: none;
        }
        .input:focus { border-color: var(--green); background: var(--white); }

        .input-wrap { position: relative; }
        .input-wrap .input { padding-right: 44px; }
        .input-suffix {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 13px;
            font-weight: 600;
            color: var(--ink-3);
        }

        .row-2 { display: flex; gap: 10px; }
        .row-2 .field { flex: 1; }

        /* ─── SELECT GRID (gender, activity, target) ─── */
        .select-grid {
            display: grid;
            gap: 8px;
        }
        .select-grid.col-2 { grid-template-columns: 1fr 1fr; }
        .select-grid.col-3 { grid-template-columns: 1fr 1fr 1fr; }
        .select-grid.col-1 { grid-template-columns: 1fr; }

        .sel-opt {
            border: 1.5px solid var(--bg-2);
            background: var(--bg);
            border-radius: 12px;
            padding: 10px 14px;
            cursor: pointer;
            text-align: left;
            font-family: 'DM Sans', sans-serif;
            transition: all 0.15s;
            -webkit-tap-highlight-color: transparent;
        }
        .sel-opt:active { opacity: 0.8; }
        .sel-opt.selected {
            background: var(--green-lt);
            border-color: var(--green);
        }
        .sel-opt-icon {
            font-size: 20px;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .sel-opt-icon img {
            width: 24px;
            height: 24px;
            object-fit: contain;
            margin-bottom: 5px;
        }
        /* Aktivitas card: center semua konten */
        .select-grid.col-3 .sel-opt {
            text-align: center;
            padding: 12px 8px;
        }
        .select-grid.col-3 .sel-opt-desc {
            font-size: 10px;
        }
        .sel-opt-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--ink);
            text-align: center;
        }
        .sel-opt-desc {
            font-size: 11px;
            color: var(--ink-3);
            margin-top: 2px;
            line-height: 1.3;
        }
        .sel-opt.selected .sel-opt-name { color: var(--green-dk); }
        .sel-opt.selected .sel-opt-desc { color: var(--green); }

        /* hidden inputs */
        input[type="hidden"] { display: none; }

        /* ─── BOTTOM CTA ─── */
        .bottom-cta {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 375px;
            padding: 16px 20px 34px;
            background: linear-gradient(to top, var(--bg) 70%, transparent);
        }

        .submit-btn {
            width: 100%;
            height: 54px;
            border: none;
            border-radius: 16px;
            background: var(--green);
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(111,168,122,0.35);
            transition: opacity 0.15s;
        }
        .submit-btn:active { opacity: 0.85; }

        /* ─── DATE HINT ─── */
        .date-hint {
            font-size: 11px;
            color: var(--ink-3);
            margin-top: 6px;
            text-align: right;
        }
    </style>
</head>
<body>
<div class="wrapper">

    <!-- HERO -->
    <div class="hero">
        <div class="hero-title">Kenalan dulu,<br>yuk!</div>
        <div class="hero-sub">Isi data di bawah agar target nutrisi dihitung otomatis & akurat.</div>
    </div>

    <div class="form-area">

        <?php if ($error): ?>
        <div class="error-box">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="setupForm">

            <!-- ── Identitas ── -->
            <div class="section">
                <div class="section-title">Identitas</div>

                <div class="field">
                    <div class="field-label">Nama</div>
                    <input type="text" name="profile_name" class="input"
                        placeholder="Nama lengkap" required
                        value="<?= htmlspecialchars($_POST['profile_name'] ?? '') ?>">
                </div>

                <div class="field">
                    <div class="field-label">Tanggal Lahir</div>
                    <input type="date" name="birth_date" class="input"
                        max="<?= date('Y-m-d') ?>"
                        min="<?= date('Y-m-d', strtotime('-25 years')) ?>"
                        required
                        value="<?= htmlspecialchars($_POST['birth_date'] ?? '') ?>"
                        id="birthDateInput">
                    <div class="date-hint" id="ageHint"></div>
                </div>

                <div class="field">
                    <div class="field-label">Jenis Kelamin</div>
                    <div class="select-grid col-2" id="genderGrid">
                        <button type="button" class="sel-opt <?= ($_POST['gender'] ?? '') === 'male' ? 'selected' : '' ?>"
                            data-val="male" onclick="pick('genderGrid','genderInput',this)">
                            <div class="sel-opt-icon"><img src="../assets/icon/iconmale.png" alt="Laki-laki"></div>
                            <div class="sel-opt-name">Laki-laki</div>
                        </button>
                        <button type="button" class="sel-opt <?= ($_POST['gender'] ?? '') === 'female' ? 'selected' : '' ?>"
                            data-val="female" onclick="pick('genderGrid','genderInput',this)">
                            <div class="sel-opt-icon"><img src="../assets/icon/iconfemale.png" alt="Perempuan"></div>
                            <div class="sel-opt-name">Perempuan</div>
                        </button>
                    </div>
                    <input type="hidden" name="gender" id="genderInput"
                        value="<?= htmlspecialchars($_POST['gender'] ?? '') ?>" required>
                </div>
            </div>

            <!-- ── Fisik ── -->
            <div class="section">
                <div class="section-title">Data Fisik</div>

                <div class="row-2">
                    <div class="field">
                        <div class="field-label">Tinggi</div>
                        <div class="input-wrap">
                            <input type="number" step="0.1" name="height" class="input"
                                placeholder="120" min="60" max="220" required
                                value="<?= htmlspecialchars($_POST['height'] ?? '') ?>">
                            <span class="input-suffix">cm</span>
                        </div>
                    </div>
                    <div class="field">
                        <div class="field-label">Berat</div>
                        <div class="input-wrap">
                            <input type="number" step="0.1" name="weight" class="input"
                                placeholder="25" min="5" max="200" required
                                value="<?= htmlspecialchars($_POST['weight'] ?? '') ?>">
                            <span class="input-suffix">kg</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Aktivitas ── -->
            <div class="section">
                <div class="section-title">Tingkat Aktivitas</div>
                <div class="field" style="border:none;padding-top:8px">
                    <div class="select-grid col-3" id="activityGrid">
                        <button type="button" class="sel-opt <?= ($_POST['activity_level'] ?? '') === 'light' ? 'selected' : '' ?>"
                            data-val="light" onclick="pick('activityGrid','activityInput',this)">
                            <div class="sel-opt-icon"><img src="../assets/icon/iconlow.png" alt="Rendah"></div>
                            <div class="sel-opt-name">Rendah</div>
                            <div class="sel-opt-desc">Aktivitas harian ringan</div>
                        </button>
                        <button type="button" class="sel-opt <?= ($_POST['activity_level'] ?? '') === 'moderate' ? 'selected' : '' ?>"
                            data-val="moderate" onclick="pick('activityGrid','activityInput',this)">
                            <div class="sel-opt-icon"><img src="../assets/icon/iconmedium.png" alt="Sedang"></div>
                            <div class="sel-opt-name">Sedang</div>
                            <div class="sel-opt-desc">Olahraga 3–5x seminggu</div>
                        </button>
                        <button type="button" class="sel-opt <?= ($_POST['activity_level'] ?? '') === 'active' ? 'selected' : '' ?>"
                            data-val="active" onclick="pick('activityGrid','activityInput',this)">
                            <div class="sel-opt-icon"><img src="../assets/icon/iconhigh.png" alt="Tinggi"></div>
                            <div class="sel-opt-name">Tinggi</div>
                            <div class="sel-opt-desc">Olahraga intens, banyak gerak</div>
                        </button>
                    </div>
                    <input type="hidden" name="activity_level" id="activityInput"
                        value="<?= htmlspecialchars($_POST['activity_level'] ?? '') ?>" required>
                </div>
            </div>

            <!-- ── Target Nutrisi ── -->
            <div class="section">
                <div class="section-title">Target Nutrisi</div>
                <div class="field" style="border:none;padding-top:8px">
                    <div class="select-grid col-2" id="targetGrid">
                        <button type="button" class="sel-opt <?= ($_POST['nutrition_target_type'] ?? '') === 'Standard' ? 'selected' : '' ?>"
                            data-val="Standard" onclick="pick('targetGrid','targetInput',this)">
                            <div class="sel-opt-icon"><img src="../assets/icon/iconstandard.png" alt="Standard"></div>
                            <div class="sel-opt-name">Standard</div>
                            <div class="sel-opt-desc">Kebutuhan gizi umum harian</div>
                        </button>
                        <button type="button" class="sel-opt <?= ($_POST['nutrition_target_type'] ?? '') === 'Tinggi' ? 'selected' : '' ?>"
                            data-val="Tinggi" onclick="pick('targetGrid','targetInput',this)">
                            <div class="sel-opt-icon"><img src="../assets/icon/iconweightgain.png" alt="Weight Gain"></div>
                            <div class="sel-opt-name">Weight Gain</div>
                            <div class="sel-opt-desc">Tinggi kalori & protein</div>
                        </button>
                        <button type="button" class="sel-opt <?= ($_POST['nutrition_target_type'] ?? '') === 'Rendah Lemak' ? 'selected' : '' ?>"
                            data-val="Rendah Lemak" onclick="pick('targetGrid','targetInput',this)">
                            <div class="sel-opt-icon"><img src="../assets/icon/iconweightloss.png" alt="Weight Loss"></div>
                            <div class="sel-opt-name">Weight Loss</div>
                            <div class="sel-opt-desc">Rendah lemak & kalori terkontrol</div>
                        </button>
                        <button type="button" class="sel-opt <?= ($_POST['nutrition_target_type'] ?? '') === 'Vegetarian' ? 'selected' : '' ?>"
                            data-val="Vegetarian" onclick="pick('targetGrid','targetInput',this)">
                            <div class="sel-opt-icon"><img src="../assets/icon/icongrwoth.png" alt="Active Growth"></div>
                            <div class="sel-opt-name">Active Growth</div>
                            <div class="sel-opt-desc">Tinggi protein nabati</div>
                        </button>
                    </div>
                    <input type="hidden" name="nutrition_target_type" id="targetInput"
                        value="<?= htmlspecialchars($_POST['nutrition_target_type'] ?? '') ?>" required>
                </div>
            </div>

        </form>
    </div>

    <div class="bottom-cta">
        <button type="submit" form="setupForm" class="submit-btn">Simpan & Mulai →</button>
    </div>

</div>

<script>
// ── Option picker ──────────────────────────────────────────────────────────
function pick(gridId, inputId, el) {
    document.querySelectorAll('#' + gridId + ' .sel-opt').forEach(b => b.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById(inputId).value = el.dataset.val;
}

// ── Age hint dari birth_date ───────────────────────────────────────────────
document.getElementById('birthDateInput').addEventListener('change', function() {
    const hint = document.getElementById('ageHint');
    if (!this.value) { hint.textContent = ''; return; }
    const birth = new Date(this.value);
    const now   = new Date();
    let years   = now.getFullYear() - birth.getFullYear();
    const m     = now.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && now.getDate() < birth.getDate())) years--;
    if (years < 0) { hint.textContent = 'Tanggal tidak valid'; return; }
    hint.textContent = `Usia: ${years} tahun`;
});

// ── Form validation sebelum submit ────────────────────────────────────────
document.getElementById('setupForm').addEventListener('submit', function(e) {
    const required = ['genderInput', 'activityInput', 'targetInput'];
    for (const id of required) {
        if (!document.getElementById(id).value) {
            e.preventDefault();
            alert('Mohon lengkapi semua pilihan sebelum melanjutkan.');
            return;
        }
    }
});
</script>
</body>
</html>