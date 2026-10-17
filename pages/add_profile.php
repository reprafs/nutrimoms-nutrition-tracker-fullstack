<?php
/**
 * pages/add_profile.php
 * ─────────────────────────────────────────────────────────────────────
 * Halaman tambah profil anak baru untuk user yang sudah login.
 * Berbeda dari setup.php (onboarding user baru), halaman ini:
 *   - Hanya bisa diakses user yang sudah punya profil
 *   - Setelah sukses INSERT, set profil baru sebagai active_profile_id
 *   - Redirect ke dietary.php (dashboard)
 * ─────────────────────────────────────────────────────────────────────
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/db.php';
$db     = getDB();
$userId = (int)$_SESSION['user_id'];

$error   = '';
$success = false;

// ── Handle POST (form submit) ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $profileName   = trim($_POST['profile_name']          ?? '');
    $birthDate     = trim($_POST['birth_date']            ?? '');
    $gender        = trim($_POST['gender']                ?? '');
    $height        = trim($_POST['height']                ?? '');
    $weight        = trim($_POST['weight']                ?? '');
    $activityLevel = trim($_POST['activity_level']        ?? '');
    $targetType    = trim($_POST['nutrition_target_type'] ?? '');

    if (
        empty($profileName) || empty($birthDate) || empty($gender) ||
        empty($height)      || empty($weight)    || empty($activityLevel) ||
        empty($targetType)
    ) {
        $error = "Semua field wajib diisi.";
    } else {

        // ── Hitung usia dari birth_date ──────────────────────────────
        $birth     = new DateTime($birthDate);
        $now       = new DateTime();
        $ageYears  = (int)$birth->diff($now)->y;

        $w      = (float)$weight;
        $h      = (float)$height;
        $isMale = strtolower($gender) === 'male';

        // ════════════════════════════════════════════════════════════
        // KALKULASI NUTRISI
        // (Logika identik dengan setup.php — Schofield / Mifflin-St Jeor)
        // ════════════════════════════════════════════════════════════

        $isAdult = ($ageYears >= 18);

        // ── 1. BMR ───────────────────────────────────────────────────
        if (!$isAdult) {
            if ($ageYears < 10) {
                $bmr = $isMale
                    ? (19.59 * $w) + (1.303 * $h) + 414.9
                    : (16.97 * $w) + (1.618 * $h) + 371.2;
            } else {
                $bmr = $isMale
                    ? (16.25 * $w) + (1.372 * $h) + 515.5
                    : (8.365 * $w) + (4.660 * $h) + 200.0;
            }
        } else {
            $bmr = $isMale
                ? (10 * $w) + (6.25 * $h) - (5 * $ageYears) + 5
                : (10 * $w) + (6.25 * $h) - (5 * $ageYears) - 161;
        }

        // ── 2. PAL ───────────────────────────────────────────────────
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
                'sedentary'   => 1.40,
                'light'       => 1.55,
                'moderate'    => 1.725,
                'active'      => 1.90,
                'very_active' => 2.10,
            ];
        }
        $pal = $palMap[$activityLevel] ?? 1.55;

        // ── 3. TEE ───────────────────────────────────────────────────
        $tee = $bmr * $pal;
        if (!$isAdult) { $tee += 20; }

        // ── 4. Adjustment target_type ────────────────────────────────
        $calMultiplier  = 1.0;
        if ($targetType === 'Tinggi') $calMultiplier = 1.05;
        $targetCalories = round($tee * $calMultiplier, 1);

        // ── 5. Protein ───────────────────────────────────────────────
        if (!$isAdult) {
            if ($ageYears < 6)       $proteinPerKg = 1.12;
            elseif ($ageYears < 10)  $proteinPerKg = 1.00;
            elseif ($ageYears < 14)  $proteinPerKg = 0.95;
            else                     $proteinPerKg = 0.85;
        } else {
            $proteinPerKg = 0.8;
        }
        if ($targetType === 'Vegetarian') $proteinPerKg *= 1.10;
        if ($targetType === 'Tinggi')     $proteinPerKg += 0.15;
        $rawProtein = $w * $proteinPerKg;

        if ($ageYears >= 18)     { $proteinFloor = $isMale ? 62.0 : 56.0; }
        elseif ($ageYears >= 14) { $proteinFloor = $isMale ? 59.0 : 51.0; }
        elseif ($ageYears >= 10) { $proteinFloor = 40.0; }
        else                     { $proteinFloor = 19.0; }
        $targetProtein = round(max($rawProtein, $proteinFloor), 1);

        // ── 6. Fat ───────────────────────────────────────────────────
        if ($targetType === 'Rendah Lemak') {
            $fatPct = $isAdult ? 0.20 : 0.25;
        } elseif (!$isAdult) {
            $fatPct = 0.30;
        } else {
            $fatPct = 0.275;
        }
        $targetFat = round(($targetCalories * $fatPct) / 9, 1);

        // ── 7. Karbo ─────────────────────────────────────────────────
        $remainKcal  = $targetCalories - ($targetProtein * 4) - ($targetFat * 9);
        $targetCarbs = round(max($remainKcal, 0) / 4, 1);

        // ── 8. Mikro ─────────────────────────────────────────────────
        $targetSugar  = ($ageYears < 12) ? 25.0 : 30.0;

        if ($ageYears < 4)       $targetSodium = 1000;
        elseif ($ageYears < 9)   $targetSodium = 1200;
        else                     $targetSodium = 1500;

        $fiberRaw = ($targetCalories / 1000) * 14;
        if ($ageYears < 10)     $fiberFloor = 16.0;
        elseif ($ageYears < 18) $fiberFloor = 22.0;
        else                    $fiberFloor = 25.0;
        $targetFiber = round(max($fiberRaw, $fiberFloor), 1);

        $targetSaturatedFat = round(($targetCalories * 0.07) / 9, 1);
        $targetCholesterol  = ($targetType === 'Rendah Lemak') ? 200 : 300;

        if ($ageYears < 9)       $targetOmega3 = 900;
        elseif ($ageYears < 14)  $targetOmega3 = $isMale ? 1200 : 1000;
        else                     $targetOmega3 = $isMale ? 1600 : 1100;

        // ── INSERT ke DB ─────────────────────────────────────────────
        try {
            $db->beginTransaction();

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
                $targetType,
            ]);
            $newProfileId = (int)$db->lastInsertId();

            $insertTarget = $db->prepare("
                INSERT INTO nutrition_targets
                    (profile_id,
                     target_calories, target_protein, target_carbs, target_fat,
                     target_sugar, target_sodium, target_fiber,
                     target_saturated_fat, target_cholesterol, target_omega_3)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insertTarget->execute([
                $newProfileId,
                $targetCalories, $targetProtein, $targetCarbs, $targetFat,
                $targetSugar, $targetSodium, $targetFiber,
                $targetSaturatedFat, $targetCholesterol, $targetOmega3,
            ]);

            $db->commit();

            // ── Set profil baru sebagai profil aktif ──────────────────
            $_SESSION['active_profile_id'] = $newProfileId;

            // Redirect ke dashboard
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
    <title>Tambah Profil – NutriMoms</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <style>
/* ── Reset & Tokens ─────────────────────────── */
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
:root {
    --g:     #5A9E6A;
    --g-lt:  #EBF4ED;
    --g-mid: #C8E0CC;
    --ink:   #1A1D1F;
    --ink-2: #444950;
    --ink-3: #9098A1;
    --bg:    #EAF1E8;
    --bg-2:  #EDEAE4;
    --white: #FFFFFF;
    --r:     18px;
    --r-sm:  12px;
    --sh:    0 2px 14px rgba(0,0,0,0.07);
}
html { background:#DCE6D9; }
body {
    background:var(--bg);
    font-family:'DM Sans',sans-serif;
    color:var(--ink);
    display:flex; justify-content:center;
    -webkit-font-smoothing:antialiased;
}
.shell { width:375px; min-height:100vh; background:var(--bg); position:relative; }

/* ── Top Bar ─────────────────────────────────── */
.top-bar {
    position:sticky; top:0; z-index:50;
    background:var(--bg);
    padding:16px 20px 14px;
    border-bottom:1px solid var(--bg-2);
    display:flex; align-items:center; gap:12px;
}
.back-btn {
    width:36px; height:36px; border-radius:50%;
    background:var(--white); border:1px solid var(--bg-2);
    display:flex; align-items:center; justify-content:center;
    font-size:16px; color:var(--ink-2); cursor:pointer;
    flex-shrink:0; -webkit-tap-highlight-color:transparent;
    text-decoration:none;
}
.top-bar-title { font-size:17px; font-weight:700; }

/* ── Error banner ───────────────────────────── */
.error-banner {
    margin:14px 20px 0;
    background:#FDECEA; border:1px solid #F5C6C6;
    border-radius:var(--r-sm); padding:12px 15px;
    font-size:13px; color:#B03030; font-weight:600;
}

/* ── Scroll area ────────────────────────────── */
.form-scroll { padding:18px 20px 120px; }

/* ── Section ────────────────────────────────── */
.section { margin-bottom:24px; }
.section-title {
    font-size:11px; font-weight:700; color:var(--ink-3);
    text-transform:uppercase; letter-spacing:0.08em;
    margin-bottom:14px;
}
.field { margin-bottom:16px; }
.field-label { font-size:13px; font-weight:700; color:var(--ink-2); margin-bottom:7px; }
.input {
    width:100%; height:48px; border:1.5px solid var(--bg-2);
    background:var(--white); border-radius:var(--r-sm);
    padding:0 14px; font-size:15px; font-family:'DM Sans',sans-serif;
    color:var(--ink); outline:none;
    -webkit-appearance:none;
}
.input:focus { border-color:var(--g); }
.row-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.input-wrap { position:relative; }
.input-wrap .input { padding-right:38px; }
.input-suffix {
    position:absolute; right:14px; top:50%; transform:translateY(-50%);
    font-size:13px; font-weight:700; color:var(--ink-3);
}
.date-hint { font-size:11.5px; color:var(--g); font-weight:600; margin-top:5px; }

/* ── Select grid ────────────────────────────── */
.select-grid { display:grid; gap:9px; }
.select-grid.col-2 { grid-template-columns:1fr 1fr; }
.select-grid.col-3 { grid-template-columns:1fr 1fr 1fr; }
.sel-opt {
    background:var(--white); border:1.5px solid var(--bg-2);
    border-radius:var(--r-sm); padding:12px 8px;
    cursor:pointer; text-align:center; transition:all 0.13s;
    -webkit-tap-highlight-color:transparent;
    font-family:'DM Sans',sans-serif;
}
.sel-opt.selected { border-color:var(--g); background:var(--g-lt); }
.sel-opt-icon { font-size:24px; margin-bottom:5px; }
.sel-opt-icon img { width:28px; height:28px; object-fit:contain; }
.sel-opt-name { font-size:12.5px; font-weight:700; color:var(--ink); }
.sel-opt-desc { font-size:10px; color:var(--ink-3); margin-top:3px; line-height:1.3; }
.sel-opt.selected .sel-opt-name { color:var(--g); }

/* ── Bottom CTA ─────────────────────────────── */
.bottom-cta {
    position:fixed; bottom:0; left:50%; transform:translateX(-50%);
    width:375px; padding:14px 20px 22px;
    background:var(--bg);
    border-top:1px solid var(--bg-2);
    z-index:60;
}
.submit-btn {
    width:100%; height:50px; border:none; border-radius:var(--r-sm);
    background:var(--g); color:#fff;
    font-family:'DM Sans',sans-serif; font-size:15px; font-weight:700;
    cursor:pointer; box-shadow:0 3px 14px rgba(90,158,106,0.3);
    -webkit-tap-highlight-color:transparent;
}
.submit-btn:active { opacity:0.88; }
    </style>
</head>
<body>
<div class="shell">

<!-- ── Top Bar ── -->
<div class="top-bar">
    <a href="dietary.php" class="back-btn">←</a>
    <div class="top-bar-title">Tambah Profil Baru</div>
</div>

<?php if ($error): ?>
<div class="error-banner"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-scroll">
    <form method="POST" id="addProfileForm">

        <!-- ── Identitas ── -->
        <div class="section">
            <div class="section-title">Identitas</div>

            <div class="field">
                <div class="field-label">Nama</div>
                <input type="text" name="profile_name" class="input"
                    maxlength="80" required
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
                    <button type="button"
                        class="sel-opt <?= ($_POST['gender'] ?? '') === 'male' ? 'selected' : '' ?>"
                        data-val="male" onclick="pick('genderGrid','genderInput',this)">
                        <div class="sel-opt-icon"><img src="../assets/icon/iconmale.png" alt="Laki-laki"></div>
                        <div class="sel-opt-name">Laki-laki</div>
                    </button>
                    <button type="button"
                        class="sel-opt <?= ($_POST['gender'] ?? '') === 'female' ? 'selected' : '' ?>"
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
                    <button type="button"
                        class="sel-opt <?= ($_POST['activity_level'] ?? '') === 'light' ? 'selected' : '' ?>"
                        data-val="light" onclick="pick('activityGrid','activityInput',this)">
                        <div class="sel-opt-icon"><img src="../assets/icon/iconlow.png" alt="Rendah"></div>
                        <div class="sel-opt-name">Rendah</div>
                        <div class="sel-opt-desc">Aktivitas harian ringan</div>
                    </button>
                    <button type="button"
                        class="sel-opt <?= ($_POST['activity_level'] ?? '') === 'moderate' ? 'selected' : '' ?>"
                        data-val="moderate" onclick="pick('activityGrid','activityInput',this)">
                        <div class="sel-opt-icon"><img src="../assets/icon/iconmedium.png" alt="Sedang"></div>
                        <div class="sel-opt-name">Sedang</div>
                        <div class="sel-opt-desc">Olahraga 3–5x seminggu</div>
                    </button>
                    <button type="button"
                        class="sel-opt <?= ($_POST['activity_level'] ?? '') === 'active' ? 'selected' : '' ?>"
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
                    <button type="button"
                        class="sel-opt <?= ($_POST['nutrition_target_type'] ?? '') === 'Standard' ? 'selected' : '' ?>"
                        data-val="Standard" onclick="pick('targetGrid','targetInput',this)">
                        <div class="sel-opt-icon"><img src="../assets/icon/iconstandard.png" alt="Standard"></div>
                        <div class="sel-opt-name">Standard</div>
                        <div class="sel-opt-desc">Kebutuhan gizi umum harian</div>
                    </button>
                    <button type="button"
                        class="sel-opt <?= ($_POST['nutrition_target_type'] ?? '') === 'Tinggi' ? 'selected' : '' ?>"
                        data-val="Tinggi" onclick="pick('targetGrid','targetInput',this)">
                        <div class="sel-opt-icon"><img src="../assets/icon/iconweightgain.png" alt="Weight Gain"></div>
                        <div class="sel-opt-name">Weight Gain</div>
                        <div class="sel-opt-desc">Tinggi kalori & protein</div>
                    </button>
                    <button type="button"
                        class="sel-opt <?= ($_POST['nutrition_target_type'] ?? '') === 'Rendah Lemak' ? 'selected' : '' ?>"
                        data-val="Rendah Lemak" onclick="pick('targetGrid','targetInput',this)">
                        <div class="sel-opt-icon"><img src="../assets/icon/iconweightloss.png" alt="Weight Loss"></div>
                        <div class="sel-opt-name">Weight Loss</div>
                        <div class="sel-opt-desc">Rendah lemak & kalori terkontrol</div>
                    </button>
                    <button type="button"
                        class="sel-opt <?= ($_POST['nutrition_target_type'] ?? '') === 'Vegetarian' ? 'selected' : '' ?>"
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
    <button type="submit" form="addProfileForm" class="submit-btn">Simpan Profil →</button>
</div>

</div><!-- /shell -->
<script>
function pick(gridId, inputId, el) {
    document.querySelectorAll('#' + gridId + ' .sel-opt')
        .forEach(b => b.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById(inputId).value = el.dataset.val;
}

document.getElementById('birthDateInput').addEventListener('change', function () {
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

document.getElementById('addProfileForm').addEventListener('submit', function (e) {
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