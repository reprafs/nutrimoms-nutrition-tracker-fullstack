<?php
session_start();

require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$db     = getDB();
$userId = $_SESSION['user_id'];

// ── Fetch user (untuk nama, email) ──────────────────────────────────────────
$userQuery = $db->prepare("SELECT name, email FROM users WHERE id = ? LIMIT 1");
$userQuery->execute([$userId]);
$user = $userQuery->fetch(PDO::FETCH_ASSOC);

// ── Pastikan active_profile_id selalu terisi (sama seperti dietary.php) ──────
if (empty($_SESSION['active_profile_id'])) {
    $defaultProfile = $db->prepare(
        "SELECT id FROM profiles WHERE user_id = ? ORDER BY id ASC LIMIT 1"
    );
    $defaultProfile->execute([$userId]);
    $found = $defaultProfile->fetchColumn();
    if ($found) {
        $_SESSION['active_profile_id'] = (int)$found;
    } else {
        header("Location: setup.php");
        exit;
    }
}

// ── Fetch child profile (termasuk photo dari profiles, bukan users) ──────────
$profileId = (int)$_SESSION['active_profile_id'];
// Validasi ownership: profil harus milik user ini
$profileQuery = $db->prepare("SELECT * FROM profiles WHERE id = ? AND user_id = ? LIMIT 1");
$profileQuery->execute([$profileId, $userId]);
$child = $profileQuery->fetch(PDO::FETCH_ASSOC);
if (!$child) {
    // active_profile_id tidak valid / bukan milik user ini
    unset($_SESSION['active_profile_id']);
    header("Location: dietary.php");
    exit;
}

// ── Resolusi foto anak dari profiles.photo ────────────────────────────────────
$uploadDir    = '../uploads/avatars/';
$childPhoto   = null;
if (!empty($child['photo']) && file_exists($uploadDir . $child['photo'])) {
    $childPhoto = $uploadDir . $child['photo'] . '?v=' . filemtime($uploadDir . $child['photo']);
}

// ── Nutrition targets (untuk display) ─────────────────────────────────────────
$ntQuery = $db->prepare("SELECT * FROM nutrition_targets WHERE profile_id = ? LIMIT 1");
$ntQuery->execute([$profileId]);
$nt = $ntQuery->fetch(PDO::FETCH_ASSOC);

// ── DASHBOARD GRID 2x2 — Kotak 1: Kalori Hari Ini ──────────────────────────────
$todayStr = date('Y-m-d');
$todayCalories = 0;
try {
    $calStmt = $db->prepare("
        SELECT total_calories FROM dietary_logs
        WHERE profile_id = ? AND log_date = ? LIMIT 1
    ");
    $calStmt->execute([$profileId, $todayStr]);
    $calRow = $calStmt->fetch(PDO::FETCH_ASSOC);
    $todayCalories = $calRow ? (float)$calRow['total_calories'] : 0;
} catch (Exception $e) {
    $todayCalories = 0; // tabel/kolom belum tersedia — fail-safe, tidak menghentikan halaman
}

// ── DASHBOARD GRID 2x2 — Kotak 2: Air Minum Hari Ini ───────────────────────────
$todayWaterMl  = 0;
$waterTargetMl = 1200; // default fallback bila belum ada target tersimpan
try {
    $waterStmt = $db->prepare("
        SELECT COALESCE(SUM(amount_ml), 0) AS total_ml
        FROM water_logs
        WHERE profile_id = ? AND log_date = ?
    ");
    $waterStmt->execute([$profileId, $todayStr]);
    $waterRow = $waterStmt->fetch(PDO::FETCH_ASSOC);
    $todayWaterMl = $waterRow ? (int)$waterRow['total_ml'] : 0;
} catch (Exception $e) {
    $todayWaterMl = 0; // tabel water_logs belum tersedia — fail-safe
}

// ── DASHBOARD GRID 2x2 — Kotak 3: Berat Badan Terakhir (dari profile_weights) ──
$latestWeight     = null;
$latestWeightDate = null;
try {
    $lwStmt = $db->prepare("
        SELECT weight, logged_date FROM profile_weights
        WHERE profile_id = ?
        ORDER BY logged_date DESC, id DESC
        LIMIT 1
    ");
    $lwStmt->execute([$profileId]);
    $lwRow = $lwStmt->fetch(PDO::FETCH_ASSOC);
    if ($lwRow) {
        $latestWeight     = (float)$lwRow['weight'];
        $latestWeightDate = $lwRow['logged_date'];
    }
} catch (Exception $e) {
    $latestWeight = null; // tabel belum ada (migration belum dijalankan) — fail-safe
}
// Fallback ke profiles.weight kalau belum ada riwayat sama sekali
if ($latestWeight === null && !empty($child['weight'])) {
    $latestWeight = (float)$child['weight'];
}

// ── REMINDERS — alarm tersimpan (reminder_meal / reminder_water) ──────────────
require_once '../config/notification_helper.php';
$reminderAlarms = [];
try {
    $reminderAlarms = getReminderAlarms($db, $profileId);
} catch (Exception $e) {
    $reminderAlarms = []; // fail-safe
}

// ── Feedback ganti password ────────────────────────────────────────────────────
$pwError   = '';
$pwSuccess = '';

// ════════════════════════════════════════════════════════════════════════════════
// POST HANDLERS
// ════════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── UPDATE CHILD ──────────────────────────────────────────────────────────
    if ($action === 'update_child') {
        $profileName     = trim($_POST['child_name']       ?? '');
        $birthDate       = trim($_POST['birth_date']       ?? '');
        $height          = trim($_POST['height']           ?? '');
        $weight          = trim($_POST['weight']           ?? '');
        $activityLevel   = trim($_POST['activity_level']   ?? '');
        $nutritionTarget = trim($_POST['nutrition_target'] ?? 'Standard');
        $genderInput     = trim($_POST['gender']           ?? '');

        // Validasi gender
        $allowedGender = ['male', 'female'];
        $genderDB = in_array($genderInput, $allowedGender) ? $genderInput : (strtolower($child['gender'] ?? 'male'));

        // Map UI 3-level → enum DB ('sedentary','light','moderate','active')
        $activityMap = [
            'rendah' => 'light',
            'sedang' => 'moderate',
            'tinggi' => 'active',
        ];
        $activityDB = $activityMap[$activityLevel] ?? 'moderate';

        // Validasi nilai enum yang diizinkan
        $allowedActivity = ['sedentary', 'light', 'moderate', 'active'];
        if (!in_array($activityDB, $allowedActivity)) $activityDB = 'moderate';

        $allowedTarget = ['Standard', 'Weight Gain', 'Weight Loss', 'Active Growth'];
        if (!in_array($nutritionTarget, $allowedTarget)) $nutritionTarget = 'Standard';

        if (!empty($profileName) && !empty($birthDate) && !empty($height) && !empty($weight)) {
            $db->prepare("
                UPDATE profiles
                SET profile_name=?, birth_date=?, height=?, weight=?,
                    activity_level=?, nutrition_target_type=?, gender=?
                WHERE id=?
            ")->execute([$profileName, $birthDate, (float)$height, (float)$weight,
                          $activityDB, $nutritionTarget, $genderDB, $profileId]);

            // ── Recalculate nutrition_targets ──────────────────────────────
            $w      = (float)$weight;
            $h      = (float)$height;
            $isMale = strtolower($child['gender'] ?? 'male') === 'male';
            $birth  = new DateTime($birthDate);
            $now    = new DateTime();
            $age    = (int)$birth->diff($now)->y;
            $isAdult = ($age >= 18);

            // BMR — Schofield (anak) / Mifflin-St Jeor (dewasa)
            if (!$isAdult) {
                $bmr = ($age < 10)
                    ? ($isMale ? (19.59*$w)+(1.303*$h)+414.9 : (16.97*$w)+(1.618*$h)+371.2)
                    : ($isMale ? (16.25*$w)+(1.372*$h)+515.5 : (8.365*$w)+(4.660*$h)+200.0);
            } else {
                $bmr = $isMale ? (10*$w)+(6.25*$h)-(5*$age)+5 : (10*$w)+(6.25*$h)-(5*$age)-161;
            }

            // PAL
            $palMap = $isAdult
                ? ['sedentary'=>1.40,'light'=>1.55,'moderate'=>1.725,'active'=>1.90]
                : ['sedentary'=>1.40,'light'=>1.55,'moderate'=>1.70,'active'=>1.85];
            $pal = $palMap[$activityDB] ?? 1.55;

            // TEE
            $tee = $bmr * $pal + (!$isAdult ? 20 : 0);

            // ── Multiplier per Nutrition Target ────────────────────────────
            // Standard      → TEE murni
            // Weight Gain   → +10% surplus (aman untuk anak)
            // Weight Loss   → -10% defisit ringan (aman anak)
            // Active Growth → +5% + protein lebih tinggi
            $calMultiplier = match($nutritionTarget) {
                'Weight Gain'   => 1.10,
                'Weight Loss'   => 0.90,
                'Active Growth' => 1.05,
                default         => 1.00, // Standard
            };
            $targetCalories = round($tee * $calMultiplier, 1);

            // Protein per kg
            if (!$isAdult) {
                if ($age < 6)      $ppkg = 1.12;
                elseif ($age < 10) $ppkg = 1.00;
                elseif ($age < 14) $ppkg = 0.95;
                else               $ppkg = 0.85;
            } else { $ppkg = 0.80; }

            // Active Growth & Weight Gain butuh protein lebih
            if ($nutritionTarget === 'Active Growth') $ppkg += 0.20;
            if ($nutritionTarget === 'Weight Gain')   $ppkg += 0.10;

            $rawPro = $w * $ppkg;
            if ($age >= 18)     $floor = $isMale ? 62.0 : 56.0;
            elseif ($age >= 14) $floor = $isMale ? 59.0 : 51.0;
            elseif ($age >= 10) $floor = 40.0;
            else                $floor = 19.0;
            $targetProtein = round(max($rawPro, $floor), 1);

            // Fat %
            // Weight Loss → batas bawah AMDR; lainnya normal
            $fatPct = ($nutritionTarget === 'Weight Loss')
                ? ($isAdult ? 0.20 : 0.25)
                : ($isAdult ? 0.275 : 0.30);
            $targetFat   = round(($targetCalories * $fatPct) / 9, 1);
            $targetCarbs = round(max($targetCalories - ($targetProtein*4) - ($targetFat*9), 0) / 4, 1);

            // Mikro
            $targetSugar        = ($age < 12) ? 25.0 : 30.0;
            $targetSodium       = ($age < 4) ? 1000 : (($age < 9) ? 1200 : 1500);
            $targetFiber        = round(max(($targetCalories/1000)*14, ($age<10?16:($age<18?22:25))), 1);
            $targetSaturatedFat = round(($targetCalories * 0.07) / 9, 1);
            $targetCholesterol  = ($nutritionTarget === 'Weight Loss') ? 200 : 300;
            $targetOmega3       = ($age < 9) ? 900 : (($age < 14) ? ($isMale ? 1200 : 1000) : ($isMale ? 1600 : 1100));

            // Upsert nutrition_targets
            $checkNt = $db->prepare("SELECT id FROM nutrition_targets WHERE profile_id = ? LIMIT 1");
            $checkNt->execute([$profileId]);
            if ($checkNt->fetch()) {
                $db->prepare("
                    UPDATE nutrition_targets SET
                        target_calories=?, target_protein=?, target_carbs=?, target_fat=?,
                        target_sugar=?, target_sodium=?, target_fiber=?,
                        target_saturated_fat=?, target_cholesterol=?, target_omega_3=?
                    WHERE profile_id=?
                ")->execute([$targetCalories,$targetProtein,$targetCarbs,$targetFat,
                             $targetSugar,$targetSodium,$targetFiber,
                             $targetSaturatedFat,$targetCholesterol,$targetOmega3,$profileId]);
            } else {
                $db->prepare("
                    INSERT INTO nutrition_targets
                        (profile_id,target_calories,target_protein,target_carbs,target_fat,
                         target_sugar,target_sodium,target_fiber,target_saturated_fat,target_cholesterol,target_omega_3)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?)
                ")->execute([$profileId,$targetCalories,$targetProtein,$targetCarbs,$targetFat,
                             $targetSugar,$targetSodium,$targetFiber,
                             $targetSaturatedFat,$targetCholesterol,$targetOmega3]);
            }
        }
        header("Location: profile.php");
        exit;
    }

    // ── ADD WEIGHT LOG (modul "My Weight" — Kotak 3 / Track Weight) ────────────
    // Universal: terhubung ke profile_id, tidak pakai istilah 'child'.
    if ($action === 'add_weight') {
        $newWeight  = trim($_POST['new_weight'] ?? '');
        $loggedDate = trim($_POST['logged_date'] ?? '') ?: date('Y-m-d');

        if ($newWeight !== '' && is_numeric($newWeight) && (float)$newWeight > 0) {
            $w = (float)$newWeight;

            // 1) INSERT ke riwayat profile_weights (Prepared Statement)
            $db->prepare("
                INSERT INTO profile_weights (profile_id, weight, logged_date, created_at)
                VALUES (?, ?, ?, NOW())
            ")->execute([$profileId, $w, $loggedDate]);

            // 2) Sync profiles.weight = berat terakhir (tetap dipertahankan
            //    sesuai keputusan: profiles.weight selalu mencerminkan
            //    entri profile_weights paling baru by logged_date).
            $latestStmt = $db->prepare("
                SELECT weight FROM profile_weights
                WHERE profile_id = ?
                ORDER BY logged_date DESC, id DESC
                LIMIT 1
            ");
            $latestStmt->execute([$profileId]);
            $latestRow = $latestStmt->fetch(PDO::FETCH_ASSOC);

            if ($latestRow) {
                $db->prepare("UPDATE profiles SET weight=? WHERE id=?")
                   ->execute([(float)$latestRow['weight'], $profileId]);
            }
        }
        header("Location: profile.php");
        exit;
    }

    // ── UPDATE ACCOUNT (email / password) ─────────────────────────────────────
    if ($action === 'update_account') {
        $field = $_POST['field'] ?? '';

        if ($field === 'email') {
            $newEmail = trim($_POST['new_email'] ?? '');
            if (!empty($newEmail) && filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $db->prepare("UPDATE users SET email=? WHERE id=?")->execute([$newEmail, $userId]);
            }
            header("Location: profile.php");
            exit;
        }

        if ($field === 'password') {
            $currentPw = $_POST['current_password'] ?? '';
            $newPw     = $_POST['new_password']     ?? '';
            $confirmPw = $_POST['confirm_password'] ?? '';

            if (empty($currentPw) || empty($newPw) || empty($confirmPw)) {
                $pwError = 'Semua kolom wajib diisi.';
            } elseif ($newPw !== $confirmPw) {
                $pwError = 'Password baru & konfirmasi tidak cocok.';
            } elseif (strlen($newPw) < 6) {
                $pwError = 'Password baru minimal 6 karakter.';
            } else {
                $pwRow = $db->prepare("SELECT password FROM users WHERE id=?");
                $pwRow->execute([$userId]);
                $pwRow = $pwRow->fetch();
                if ($pwRow && password_verify($currentPw, $pwRow['password'])) {
                    $db->prepare("UPDATE users SET password=? WHERE id=?")
                       ->execute([password_hash($newPw, PASSWORD_DEFAULT), $userId]);
                    $pwSuccess = 'Password berhasil diubah!';
                } else {
                    $pwError = 'Password saat ini salah.';
                }
            }
        }
    }

    // ── UPLOAD FOTO ANAK (simpan ke profiles.photo) ───────────────────────────
    if ($action === 'upload_photo') {
        $cropData = $_POST['crop_data'] ?? '';
        if (!empty($cropData) && strpos($cropData, 'data:image') === 0) {
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
            $filename  = 'child_' . $profileId . '_' . time() . '.jpg';
            $b64       = preg_replace('#^data:image/\w+;base64,#', '', $cropData);
            $imageData = base64_decode($b64);
            file_put_contents($uploadDir . $filename, $imageData);

            // Hapus foto lama
            if (!empty($child['photo']) && $child['photo'] !== $filename
                && file_exists($uploadDir . $child['photo'])) {
                unlink($uploadDir . $child['photo']);
            }

            $db->prepare("UPDATE profiles SET photo=? WHERE id=?")->execute([$filename, $profileId]);
        }
        header("Location: profile.php");
        exit;
    }
}

// ── Re-fetch data terbaru ──────────────────────────────────────────────────────
$userQuery->execute([$userId]);
$user = $userQuery->fetch(PDO::FETCH_ASSOC);

$profileQuery->execute([$profileId, $userId]);
$child = $profileQuery->fetch(PDO::FETCH_ASSOC);

// Re-resolve foto setelah re-fetch
$childPhoto = null;
if (!empty($child['photo']) && file_exists($uploadDir . $child['photo'])) {
    $childPhoto = $uploadDir . $child['photo'] . '?v=' . filemtime($uploadDir . $child['photo']);
}

// ── Hitung usia ───────────────────────────────────────────────────────────────
$childAge    = '-';
$childAgeMon = '';
if (!empty($child['birth_date'])) {
    $birth    = new DateTime($child['birth_date']);
    $now      = new DateTime();
    $diff     = $birth->diff($now);
    $childAge = $diff->y;
    if ($childAge < 2) $childAgeMon = $diff->m . ' bln';
}

// ── Map activity DB → UI label ────────────────────────────────────────────────
$activityUILabel = [
    'sedentary' => 'Rendah',
    'light'     => 'Rendah',
    'moderate'  => 'Sedang',
    'active'    => 'Tinggi',
];
$curActivityUI = $activityUILabel[$child['activity_level'] ?? 'moderate'] ?? 'Sedang';

// ── Map activity DB → UI value (untuk form) ───────────────────────────────────
$activityToUI = [
    'sedentary' => 'rendah',
    'light'     => 'rendah',
    'moderate'  => 'sedang',
    'active'    => 'tinggi',
];
$curActivityUIVal = $activityToUI[$child['activity_level'] ?? 'moderate'] ?? 'sedang';

// ── Helpers ───────────────────────────────────────────────────────────────────
function fmt($val) {
    if ($val === null || $val === '') return '-';
    $n = (float)$val;
    return ($n == (int)$n) ? (string)(int)$n : rtrim(rtrim(number_format($n, 1, '.', ''), '0'), '.');
}

$initial = strtoupper(substr($user['name'] ?? 'U', 0, 1));
$pwOpen  = ($pwError || $pwSuccess) ? 'true' : 'false';

$currentTarget = $child['nutrition_target_type'] ?? 'Standard';

// Target info untuk display
$targetInfo = [
    'Standard'      => ['icon'=>'🥗', 'color'=>'#6FA87A', 'bg'=>'#EEF5F0', 'desc'=>'Kebutuhan gizi normal harian'],
    'Weight Gain'   => ['icon'=>'📈', 'color'=>'#E8956D', 'bg'=>'#FEF0EC', 'desc'=>'Surplus kalori ringan untuk naik BB sehat'],
    'Weight Loss'   => ['icon'=>'🥦', 'color'=>'#5B9BD5', 'bg'=>'#EEF2FF', 'desc'=>'Defisit ringan, aman untuk anak'],
    'Active Growth' => ['icon'=>'⚡', 'color'=>'#9B7BD6', 'bg'=>'#F3EEFF', 'desc'=>'Protein & energi ekstra untuk tumbuh aktif'],
];
$tInfo = $targetInfo[$currentTarget] ?? $targetInfo['Standard'];

// BMI sederhana untuk display
$bmi     = '-';
$bmiNote = '';
if (!empty($child['height']) && !empty($child['weight']) && $child['height'] > 0) {
    $bmiVal  = (float)$child['weight'] / pow((float)$child['height'] / 100, 2);
    $bmi     = number_format($bmiVal, 1);
    if (is_numeric($childAge) && $childAge >= 2) {
        if ($bmiVal < 14)      $bmiNote = 'Sangat Kurus';
        elseif ($bmiVal < 17)  $bmiNote = 'Kurus';
        elseif ($bmiVal < 24)  $bmiNote = 'Normal';
        elseif ($bmiVal < 27)  $bmiNote = 'Gemuk';
        else                   $bmiNote = 'Obesitas';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="manifest" href="/nutrimoms/manifest.json">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=375, user-scalable=no">
    <title>Profil — NutriKids</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        :root {
            --green:       #6FA87A;
            --green-light: #EEF5F0;
            --green-mid:   #5D9669;
            --green-pale:  #F2F8F4;
            --orange:      #E8956D;
            --orange-light:  #EAF1E8;
            --blue-light:  #EEF2FF;
            --purple-light:#F3EEFF;
            --bg:          #EAF1E8;
            --bg2:         #D9E8D6;
            --card:        #FFFFFF;
            --text:        #1E1E1E;
            --sub:         #8A8A8A;
            --border:      #D9E8D6;
            --shadow:      0 2px 18px rgba(0,0,0,0.07);
            --shadow-sm:   0 1px 8px rgba(0,0,0,0.05);
        }

        body {
            background: var(--bg);
            font-family: 'DM Sans', sans-serif;
            display: flex;
            justify-content: center;
            color: var(--text);
            -webkit-tap-highlight-color: transparent;
        }

        .mobile-wrapper {
            width: 375px;
            min-height: 100vh;
            background: var(--bg);
            position: relative;
            overflow-x: hidden;
        }

        /* ─── STATUS BAR ─── */
        .status-space { height: 6px; background: var(--green-mid); }

        /* ─── HERO ─── */
        .hero {
            background: linear-gradient(160deg, #6FA87A 0%, #4E8A5A 100%);
            padding: 0 20px 40px;
            position: relative;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0; right: 0;
            height: 28px;
            background: var(--bg);
            border-radius: 28px 28px 0 0;
        }

        .hero-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0 24px;
            margin-bottom: -24px;
        }

        .page-title {
            font-size: 30px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.3px;
        }

        /* Settings button — fully standalone, no z-index conflict */
       .settings-btn {
            height: 34px;
            padding: 0 12px;
            border-radius: 999px;

            background: rgba(255,255,255,0.18);
            border: 1.5px solid rgba(255,255,255,0.3);

            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;

            cursor: pointer;
            transition: background .2s;

            color: #fff;
            font-size: 12px;
            font-weight: 600;

            backdrop-filter: blur(10px);
        }

        .settings-btn img {
            width: 14px;
            height: 14px;
            object-fit: contain;
        }

        .settings-btn:active {
            background: rgba(255,255,255,0.32);
        }

        .settings-btn svg {
            width: 14px;
            height: 14px;
            fill: none;
            stroke: #fff;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* avatar */
        .avatar-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 12px;
        }

        .avatar-ring {
            width: 92px;
            height: 92px;
            border-radius: 50%;
            background: rgba(255,255,255,0.22);
            padding: 3px;
            cursor: pointer;
            position: relative;
            flex-shrink: 0;
        }

        .avatar {
            width: 86px;
            height: 86px;
            border-radius: 50%;
            background: #fff;
            color: var(--green);
            font-size: 32px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .avatar img { width:100%; height:100%; object-fit:cover; }

        .camera-badge {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: var(--orange);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2.5px solid rgba(78,138,90,1);
        }
        .camera-badge svg { width:13px; height:13px; fill:none; stroke:#fff; stroke-width:2; }

        .child-name {
            margin-top: 14px;
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.3px;
        }

        .child-meta {
            margin-top: 4px;
            font-size: 13px;
            color: rgba(255,255,255,0.72);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .child-meta-dot {
            width: 3px; height: 3px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
        }

        .hero-actions {
            display: flex;
            gap: 10px;
            margin-top: 18px;
        }

        .hero-btn {
            height: 38px;
            padding: 0 18px;
            border-radius: 19px;
            border: 1.5px solid rgba(255,255,255,0.55);
            background: rgba(255,255,255,0.14);
            color: #fff;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s;
            white-space: nowrap;
        }
        .hero-btn:active { background: rgba(255,255,255,0.28); }
        .hero-btn.primary {
            background: rgba(255,255,255,0.92);
            color: var(--green-mid);
            border-color: transparent;
        }
        .hero-btn.primary:active { background: #fff; }

        /* ─── STAT PILLS ─── */
        .stats-row {
            display: flex;
            gap: 10px;
            margin: -4px 20px 0;
            position: relative;
            z-index: 10;
        }

        .stat-pill {
            flex: 1;
            background: var(--card);
            border-radius: 18px;
            padding: 14px 10px;
            text-align: center;
            box-shadow: var(--shadow);
        }

        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--text);
            line-height: 1;
            letter-spacing: -0.5px;
        }
        .stat-value small { font-size: 12px; font-weight: 500; color: var(--sub); letter-spacing: 0; }

        .stat-label {
            font-size: 11px;
            color: var(--sub);
            margin-top: 5px;
            font-weight: 500;
        }

        /* ─── CONTENT ─── */
        .content {
            padding: 20px 20px 130px;
        }

        /* ─── SECTION HEADER ─── */
        .sec-header {
            font-size: 11px;
            font-weight: 700;
            color: var(--sub);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin: 24px 0 10px 4px;
        }
        .sec-header:first-child { margin-top: 8px; }

        /* ─── CARD ─── */
        .card {
            background: var(--card);
            border-radius: 22px;
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-bottom: 12px;
        }

        .card-inner { padding: 0 18px; }

        /* ─── ROW ─── */
        .row {
            min-height: 56px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--border);
            transition: background .12s;
            padding: 10px 0;
            gap: 12px;
        }
        .row:last-child { border-bottom: none; }
        .row.tappable { cursor: pointer; }
        .row.tappable:active { background: var(--bg); margin: 0 -18px; padding: 10px 18px; border-radius: 4px; }

        .row-icon {
            width: 30px; height: 30px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        .row-icon.green  { background: transparent; }
        .row-icon.orange { background: transparent; }
        .row-icon.blue   { background: transparent; }
        .row-icon.purple { background: transparent; }
        .row-icon.cream  { background: transparent; }
        .row-icon.pink   { background: transparent; }
        .row-icon img { width: 20px; height: 20px; object-fit: contain; }

        .row-body { flex: 1; min-width: 0; }
        .row-label { font-size: 14px; font-weight: 500; line-height: 1.2; }
        .row-sub   { font-size: 11.5px; color: var(--sub); margin-top: 2px; }

        .row-right {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }
        .row-value { font-size: 13px; color: var(--sub); font-weight: 500; }
        .chevron   { color: #C8C8C8; font-size: 18px; }

        /* ─── BADGE ─── */
        .badge {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
        }
        .badge-green  { background: var(--green-light);  color: var(--green-mid); }
        .badge-orange { background: var(--orange-light); color: var(--orange); }
        .badge-blue   { background: var(--blue-light);   color: #5B9BD5; }
        .badge-purple { background: var(--purple-light); color: #9B7BD6; }

        /* ─── TARGET CARD (inline di content) ─── */
        .target-card {
            border-radius: 22px;
            padding: 16px 18px;
            margin-bottom: 12px;
            position: relative;
            overflow: hidden;
            background: var(--card);
            box-shadow: var(--shadow);
            border-left: 5px solid var(--green);
        }
        .target-card::before { display: none; }
        .target-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 4px;
        }
        .target-card-title { font-size: 16px; font-weight: 700; color: var(--text); margin-bottom: 3px; }
        .target-card-desc  { font-size: 12px; color: var(--sub); line-height: 1.4; }
        .target-card-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            flex-shrink: 0;
        }

        .target-macros {
            display: flex;
            gap: 8px;
            margin-top: 14px;
        }

        .macro-pill {
            flex: 1;
            background: var(--bg);
            border-radius: 12px;
            padding: 10px 8px;
            text-align: center;
        }
        .macro-val   { font-size: 15px; font-weight: 700; color: var(--text); }
        .macro-label { font-size: 10px; color: var(--sub); margin-top: 2px; }

        /* ─── SUBSCRIPTION SECTION ─── */
        .sub-scroll {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding: 2px 0 6px;
            scrollbar-width: none;
        }
        .sub-scroll::-webkit-scrollbar { display: none; }

        .sub-card {
            flex-shrink: 0;
            width: 130px;
            background: var(--card);
            border-radius: 18px;
            padding: 16px 14px;
            box-shadow: var(--shadow-sm);
            border: 2px solid transparent;
            cursor: pointer;
            transition: border-color .2s, box-shadow .2s;
            position: relative;
        }
        .sub-card:active { opacity: .9; }
        .sub-card.current { border-color: var(--green); }
        .sub-card-icon { font-size: 24px; margin-bottom: 8px; }
        .sub-card-name { font-size: 14px; font-weight: 700; color: var(--text); }
        .sub-card-price {
            font-size: 11px;
            color: var(--sub);
            margin-top: 3px;
            font-weight: 500;
        }
        .sub-card-badge {
            position: absolute;
            top: -8px; right: 10px;
            background: var(--orange);
            color: #fff;
            font-size: 9px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
        }

        /* ─── PREMIUM LOCKED ROW ─── */
        .locked-row {
            min-height: 56px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--border);
            padding: 10px 0;
            gap: 12px;
            cursor: pointer;
            position: relative;
        }
        .locked-row:last-child { border-bottom: none; }
        .locked-row:active { background: var(--bg); margin: 0 -18px; padding: 10px 18px; }

        .locked-blur {
            flex: 1;
            opacity: 0.45;
            user-select: none;
            pointer-events: none;
        }
        .lock-badge {
            position: absolute;
            right: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--orange-light);
            padding: 6px;
            border-radius: 20px;
        }

        /* ─── LOGOUT ─── */
        .logout-btn {
            width: 100%;
            height: 54px;
            border: none;
            border-radius: 18px;
            background: var(--green-pale);
            color: var(--orange);
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            text-decoration: none;
            margin-top: 6px;
            transition: opacity .2s;
        }
        .logout-btn:active { opacity: .8; }

        /* ════════════════════════════════════════════════════════════════
           NEW UI — Header Asimetris, Grid 2x2, Weight Module, Reminders
        ════════════════════════════════════════════════════════════════ */

        /* ─── ASYMMETRIC HEADER (FatSecret-style) ─── */
        .hero-asym {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: flex-start;
            gap: 16px;
            padding: 24px 16px 4px;
            width: 100%;
            text-align: left;
        }
        .hero-asym .avatar-ring {
            flex-shrink: 0;
            width: 76px; height: 76px;
        }
        .hero-asym .avatar {
            width: 76px; height: 76px;
            font-size: 28px;
        }
        .hero-asym-info {
            flex: 1 1 auto;
            flex-grow: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
            gap: 4px;
        }
        .hero-asym-name {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .hero-asym-physical {
            font-size: 13px;
            color: rgba(255,255,255,0.9);
            font-weight: 500;
            cursor: pointer;
            line-height: 1.4;
            padding: 6px 10px;
            margin-left: -10px;
            margin-top: 2px;
            border-radius: 10px;
            transition: background .15s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            max-width: 100%;
        }
        .hero-asym-physical:active { background: rgba(255,255,255,0.12); }
        .hero-asym-physical .physical-text {
            white-space: normal;
            overflow: visible;
            text-overflow: unset;
            max-width: 230px;
            line-height: 1.3;
        }

        .hero-asym-physical .chev-mini { font-size: 13px; opacity: .7; flex-shrink: 0; }

        /* ─── DASHBOARD PILLS ROW (capsule, menggantikan grid 2x2 lama) ─── */
        .pills-surface {
            background: var(--card);
            border-radius: 22px;
            padding: 14px;
            box-shadow: var(--shadow-sm);
            margin-bottom: -6px;
        }
        .pills-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }
        .pill-item {
            background: var(--bg);
            border-radius: 16px;
            padding: 12px 8px;
            cursor: pointer;
            transition: transform .15s, background .15s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            text-align: center;
            justify-content: center;
        }
        .pill-item:active { transform: scale(0.95); background: var(--green-light); }
        .pill-item.premium-locked { opacity: 0.6; position: relative; }
        .pill-item.premium-locked::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 16px;
            background: rgba(234,241,232,0.45);
        }
        .pill-lock-badge {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            z-index: 2;
            width: 22px; height: 22px;
            display: flex; align-items: center; justify-content: center;
        }
        .pill-lock-badge img { width: 18px; height: 18px; object-fit: contain; }
        .pill-icon {
            width: 34px; height: 34px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            background: var(--bg);
        }
        .pill-icon img{
            width: 22px;
            height: 22px;
            object-fit: contain;
        }
        .pill-value {
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
            line-height: 1.2;
            white-space: nowrap;
        }
        .pill-unit {
            font-size: 10px;
            font-weight: 600;
            color: var(--sub);
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        .pill-label-only {
            font-size: 11.5px;
            font-weight: 700;
            color: var(--text);
            margin-top: 2px;
        }

        /* ─── WEIGHT TRACK MODULE (chart + history) ─── */
        .weight-chart-wrap {
            background: var(--card);
            border-radius: 20px;
            padding: 18px 16px 14px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 16px;
        }
        .weight-chart-head {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 12px;
        }
        .weight-chart-latest {
            font-size: 24px;
            font-weight: 700;
            color: var(--text);
        }
        .weight-chart-latest small {
            font-size: 13px;
            font-weight: 600;
            color: var(--sub);
        }
        .weight-chart-date { font-size: 12px; color: var(--sub); font-weight: 500; }
        .weight-svg-wrap { width: 100%; height: 140px; min-height: 140px; overflow: visible; display: block; }
        .weight-svg-wrap svg { display: block; width: 100%; height: 100%; }
        .weight-empty {
            text-align: center;
            padding: 24px 12px;
            color: var(--sub);
            font-size: 13px;
        }

        .weight-add-form {
            display: flex;
            gap: 10px;
            margin-bottom: 18px;
        }
        .weight-add-input-wrap {
            flex: 1;
            position: relative;
        }
        .weight-add-input {
            width: 100%;
            height: 52px;
            border: 1.5px solid var(--border);
            outline: none;
            background: var(--bg);
            border-radius: 14px;
            padding: 0 40px 0 16px;
            font-size: 16px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            color: var(--text);
        }
        .weight-add-input:focus { border-color: var(--green); background: #fff; }
        .weight-add-suffix {
            position: absolute;
            right: 16px; top: 50%;
            transform: translateY(-50%);
            font-size: 13px;
            color: var(--sub);
            font-weight: 600;
            pointer-events: none;
        }
        .weight-add-submit {
            height: 52px;
            padding: 0 20px;
            border-radius: 14px;
            border: none;
            background: var(--green);
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            transition: opacity .15s;
        }
        .weight-add-submit:active { opacity: .85; }

        .weight-history-year {
            font-size: 13px;
            font-weight: 700;
            color: var(--sub);
            margin: 18px 0 8px;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .weight-history-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 13px 4px;
            border-bottom: 1px solid var(--border);
        }
        .weight-history-row:last-child { border-bottom: none; }
        .weight-history-date {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
        }
        .weight-history-value {
            font-size: 14px;
            font-weight: 700;
            color: var(--green-mid);
        }

        /* ─── REMINDERS PAGE ─── */
        .reminders-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 4px;
        }
        .reminders-add-btn {
            width: 38px; height: 38px;
            border-radius: 12px;
            background: var(--green-light);
            border: none;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            font-weight: 700;
            color: var(--green-mid);
            cursor: pointer;
        }
        .reminders-add-btn:active { background: var(--green); color: #fff; }

        .reminder-card {
            background: var(--card);
            border-radius: 18px;
            padding: 14px 16px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 10px;
        }
        .reminder-icon {
            width: 44px; height: 44px;
            border-radius: 13px;
            display: flex; align-items: center; justify-content: center;
            font-size: 19px;
            flex-shrink: 0;
        }
        .reminder-body { flex: 1; min-width: 0; }
        .reminder-title { font-size: 14.5px; font-weight: 700; color: var(--text); }
        .reminder-time  { font-size: 12.5px; color: var(--green-mid); font-weight: 600; margin-top: 2px; }
        .reminder-del-btn {
            width: 32px; height: 32px;
            border-radius: 10px;
            background: var(--bg);
            border: none;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
            color: #C0524A;
            cursor: pointer;
            flex-shrink: 0;
        }
        .reminder-del-btn:active { background: #FDECEA; }

        .reminders-empty {
            text-align: center;
            padding: 48px 20px;
            color: var(--sub);
        }
        .reminders-empty-icon { font-size: 40px; margin-bottom: 10px; }
        .reminders-empty-text { font-size: 13.5px; font-weight: 500; }

        /* Category picker (sheet "+") */
        .cat-opt-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 18px; }
        .cat-opt {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            background: var(--bg);
            border-radius: 16px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color .15s, background .15s;
        }
        .cat-opt.selected { border-color: var(--green); background: var(--green-light); }
        .cat-opt-icon {
            width: 40px; height: 40px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
            background: #fff;
        }
        .cat-opt-icon img{
            width: 76px;
            height: 76px;
            object-fit: contain;
        }
        .cat-opt-name { font-size: 14px; font-weight: 600; color: var(--text); }

        .time-picker-wrap {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .time-picker-input {
            font-size: 36px;
            font-weight: 700;
            text-align: center;
            border: none;
            background: transparent;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            outline: none;
            width: 160px;
        }

        /* ─── WATER BOTTOM SHEET ─── */
        .water-target-display {
            text-align: center;
            margin-bottom: 20px;
        }
        .water-target-value {
            font-size: 38px;
            font-weight: 700;
            color: var(--text);
        }
        .water-target-value small { font-size: 16px; color: var(--sub); font-weight: 600; }
        .water-quick-row {
            display: flex;
            gap: 10px;
            margin-bottom: 18px;
        }
        .water-quick-btn {
            flex: 1;
            height: 48px;
            border-radius: 14px;
            border: 1.5px solid var(--border);
            background: var(--bg);
            font-size: 13.5px;
            font-weight: 700;
            color: var(--text);
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
        }
        .water-quick-btn:active { background: var(--blue-light); border-color: #5B9BD5; }

        /* ─── NAVBAR ─── */
        /* ── NAVBAR ── */
        .navbar{
            position:fixed;bottom:24px;left:50%;transform:translateX(-50%);
            width:320px;height:56px;border-radius:28px;
            background:#fff;box-shadow:0 4px 24px rgba(0,0,0,0.12);
            display:flex;align-items:center;justify-content:space-around;
            z-index:100;
        }
        .nav-item{
            text-decoration:none;color:#8A8A8A;font-size:10px;
            display:flex;flex-direction:column;align-items:center;gap:3px;
            font-weight:600;min-width:52px;font-family:'DM Sans',sans-serif;
            -webkit-tap-highlight-color:transparent;
        }
        .nav-item.active{color:#6FA87A;}
        .nav-icon{
            width:24px;height:24px;
            display:flex;align-items:center;justify-content:center;
        }
        .nav-icon svg{width:20px;height:20px;}
        .nav-item.active .nav-icon{background:#EEF5F0;border-radius:8px;}

        /* ─── OVERLAYS ─── */
        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.42);
            display: none;
            align-items: flex-end;
            z-index: 500;
            backdrop-filter: blur(2px);
        }
        .overlay.active { display: flex; }

        .sheet {
            width: 100%;
            background: #fff;
            border-radius: 28px 28px 0 0;
            padding: 12px 20px 0;
            animation: slideUp .3s cubic-bezier(.32,1,.4,1);
            max-height: 92vh;
            overflow-y: auto;
        }
        @keyframes slideUp {
            from { transform: translateY(100%); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        .handle {
            width: 48px; height: 5px;
            border-radius: 999px;
            background: #E5E1DB;
            margin: 0 auto 20px;
        }
        .sheet-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 6px;
            letter-spacing: -0.3px;
        }
        .sheet-sub {
            font-size: 13px;
            color: var(--sub);
            margin-bottom: 20px;
        }

        /* settings menu */
        .menu-list { display: flex; flex-direction: column; gap: 10px; }
        .menu-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            background: var(--bg);
            border-radius: 16px;
            cursor: pointer;
            border: none;
            width: 100%;
            text-align: left;
            font-family: 'DM Sans', sans-serif;
            transition: background .15s;
        }
        .menu-item:active { background: var(--green-light); }
        .menu-item-icon {
            width: 42px; height: 42px;
            border-radius: 13px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .menu-item-icon img { width: 22px; height: 22px; object-fit: contain; }
        .menu-item-text { flex: 1; }
        .menu-item-label { font-size: 14px; font-weight: 600; color: var(--text); }
        .menu-item-desc  { font-size: 12px; color: var(--sub); margin-top: 2px; }
        .menu-chevron    { color: #C8C8C8; font-size: 18px; }

        /* form */
        .form-group { margin-bottom: 16px; }
        .form-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--sub);
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: 8px;
        }
        .input {
            width: 100%;
            height: 52px;
            border: 1.5px solid var(--border);
            outline: none;
            background: var(--bg);
            border-radius: 14px;
            padding: 0 16px;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            color: var(--text);
            transition: border-color .2s, background .2s;
            -webkit-appearance: none;
        }
        .input:focus { border-color: var(--green); background: #fff; }
        .input-row { display: flex; gap: 10px; }
        .input-row .form-group { flex: 1; }
        .input-suffix { position: relative; }
        .input-suffix .input { padding-right: 44px; }
        .suffix-text {
            position: absolute;
            right: 14px; top: 50%;
            transform: translateY(-50%);
            font-size: 13px;
            color: var(--sub);
            font-weight: 500;
        }

        /* activity selector 3-level */
        .activity-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 8px;
        }
        .act-opt {
            padding: 14px 8px;
            border-radius: 14px;
            background: var(--bg);
            border: 2px solid transparent;
            cursor: pointer;
            text-align: center;
            font-family: 'DM Sans', sans-serif;
            transition: all .2s;
        }
        .act-opt.selected { background: var(--green-light); border-color: var(--green); }
        .act-opt-icon { font-size: 22px; display:flex; align-items:center; justify-content:center; }
        .act-opt-name { font-size: 12px; font-weight: 700; color: var(--text); margin-top: 6px; }
        .act-opt-desc { font-size: 10px; color: var(--sub); margin-top: 2px; line-height: 1.3; }
        .act-opt.selected .act-opt-name { color: var(--green-mid); }

        /* gender selector */
        .gender-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .gender-opt {
            padding: 14px 8px;
            border-radius: 14px;
            background: var(--bg);
            border: 2px solid transparent;
            cursor: pointer;
            text-align: center;
            font-family: 'DM Sans', sans-serif;
            transition: all .2s;
        }
        .gender-opt.selected { background: var(--green-light); border-color: var(--green); }
        .gender-opt-icon { display: flex; align-items: center; justify-content: center; margin-bottom: 6px; }
        .gender-opt-icon img { width: 28px; height: 28px; object-fit: contain; }
        .gender-opt-name { font-size: 13px; font-weight: 700; color: var(--text); }
        .gender-opt.selected .gender-opt-name { color: var(--green-mid); }

        /* nutrition target selector */
        .target-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .tgt-opt {
            padding: 14px 12px;
            border-radius: 14px;
            background: var(--bg);
            border: 2px solid transparent;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            transition: all .2s;
        }
        .tgt-opt.selected { background: var(--green-light); border-color: var(--green); }
        .tgt-opt-icon { font-size: 20px; margin-bottom: 6px; display:flex; align-items:center; }
        .tgt-opt-name { font-size: 13px; font-weight: 700; color: var(--text); }
        .tgt-opt-desc { font-size: 10.5px; color: var(--sub); margin-top: 3px; line-height: 1.3; }
        .tgt-opt.selected .tgt-opt-name { color: var(--green-mid); }

        .save-btn {
            width: 100%;
            height: 54px;
            border: none;
            border-radius: 16px;
            background: var(--green);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            margin-top: 8px;
            transition: opacity .2s;
            box-shadow: 0 4px 16px rgba(111,168,122,0.3);
        }
        .save-btn:active { opacity: .85; }

        .back-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background: none;
            border: none;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: var(--sub);
            margin-bottom: 18px;
            padding: 0;
        }
        .back-btn svg { width:18px; height:18px; fill:none; stroke:var(--sub); stroke-width:2; }

        .info-box {
            background: var(--green-light);
            border-radius: 14px;
            padding: 12px 14px;
            font-size: 12px;
            color: var(--green-mid);
            line-height: 1.5;
            margin-bottom: 18px;
        }

        .sheet-divider { height: 1px; background: var(--border); margin: 16px 0; }

        /* ─── CROP SHEET ─── */
        #cropContainer {
            position: relative;
            width: 100%;
            aspect-ratio: 1;
            background: #111;
            border-radius: 18px;
            overflow: hidden;
            margin-bottom: 14px;
            touch-action: none;
        }
        #cropImg {
            position: absolute;
            transform-origin: center;
            cursor: grab;
            user-select: none;
            -webkit-user-drag: none;
        }
        .crop-circle-mask {
            position: absolute; inset: 0;
            pointer-events: none;
            display: flex; align-items: center; justify-content: center;
        }
        .crop-circle-mask::before {
            content: '';
            position: absolute; inset: 0;
            background: rgba(0,0,0,0.5);
        }
        .crop-circle-mask::after {
            content: '';
            width: 72%; aspect-ratio: 1;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.8);
            position: relative; z-index: 1;
            box-shadow: 0 0 0 9999px rgba(0,0,0,0.5);
        }
        .crop-actions { display: flex; gap: 10px; }
        .crop-actions button {
            flex: 1; height: 48px;
            border: none; border-radius: 14px;
            font-family: 'DM Sans', sans-serif;
            font-weight: 700; font-size: 14px;
            cursor: pointer;
        }
        .btn-cancel { background: var(--bg); color: var(--sub); }
        .btn-apply  { background: var(--green); color: #fff; }
        .zoom-slider { width: 100%; margin: 0 0 14px; accent-color: var(--green); }

        /* ─── TOAST ─── */
        .toast {
            position: fixed;
            bottom: 108px; left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: #1E1E1E;
            color: #fff;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            opacity: 0;
            transition: all .3s;
            white-space: nowrap;
            z-index: 999;
        }
        .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

        /* toggle switch */
        .toggle-label {
            position: relative;
            display: inline-block;
            width: 44px; height: 26px;
            cursor: pointer;
        }
        .toggle-label input { opacity:0; width:0; height:0; }
        .toggle-track {
            position: absolute; inset: 0;
            border-radius: 999px;
            transition: background .3s;
        }
        .toggle-thumb {
            position: absolute;
            left: 3px; top: 3px;
            width: 20px; height: 20px;
            background: #fff;
            border-radius: 50%;
            transition: transform .3s;
        }

        /* ─── SUBSCRIPTION POPUP ─── */
        .sub-popup-inner {
            padding-bottom: max(24px, env(safe-area-inset-bottom));
        }
        .sub-tier {
            background: var(--bg);
            border-radius: 18px;
            padding: 18px;
            margin-bottom: 10px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all .2s;
            position: relative;
        }
        .sub-tier:active { opacity: .9; }
        .sub-tier.highlight { border-color: var(--green); background: var(--green-pale); }
        .sub-tier-header { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .sub-tier-icon { font-size: 24px; }
        .sub-tier-name { font-size: 16px; font-weight: 700; }
        .sub-tier-price { font-size: 12px; color: var(--sub); margin-top: 1px; }
        .sub-tier-badge {
            position: absolute; top: 14px; right: 14px;
            background: var(--orange);
            color: #fff; font-size: 10px; font-weight: 700;
            padding: 3px 10px; border-radius: 20px;
        }
        .sub-features { display: flex; flex-direction: column; gap: 6px; }
        .sub-feature {
            display: flex; align-items: center; gap: 8px;
            font-size: 12.5px; color: var(--text);
        }
        .sub-feature-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--green);
            flex-shrink: 0;
        }
        .sub-feature-dot.locked { background: var(--sub); }
        .sub-cta {
            width: 100%;
            height: 52px;
            border: none;
            border-radius: 16px;
            background: var(--green);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            margin-top: 4px;
            box-shadow: 0 4px 16px rgba(111,168,122,0.3);
        }
        .sub-cta-free {
            width: 100%;
            height: 44px;
            border: 1.5px solid var(--border);
            border-radius: 14px;
            background: transparent;
            color: var(--sub);
            font-size: 13px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            margin-top: 8px;
        }

        /* ─── NEW SUBSCRIPTION SHEET — Benefit Checklist ─── */
        .sub-benefit-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 0;
            border-bottom: 1px solid var(--border);
        }
        .sub-benefit-row:last-child { border-bottom: none; }
        .sub-benefit-check {
            width: 22px; height: 22px;
            border-radius: 7px;
            background: var(--green-light);
            color: var(--green-mid);
            display: flex; align-items: center; justify-content: center;
            font-size: 12px;
            flex-shrink: 0;
        }
        .sub-benefit-text { font-size: 13.5px; font-weight: 600; color: var(--text); }

        /* ─── NEW SUBSCRIPTION SHEET — Pricing Tier (radio item) ─── */
        .price-tier-list { display: flex; flex-direction: column; gap: 10px; margin: 18px 0; }
        .price-tier {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1.5px solid var(--border);
            background: var(--card);
            cursor: pointer;
            position: relative;
            transition: border-color .15s, background .15s;
        }
        .price-tier.selected { border-color: var(--green); background: var(--green-pale); }
        .price-tier-radio {
            width: 20px; height: 20px;
            border-radius: 50%;
            border: 2px solid var(--border);
            flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            transition: border-color .15s;
        }
        .price-tier.selected .price-tier-radio { border-color: var(--green); }
        .price-tier-radio-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            background: var(--green);
            transform: scale(0);
            transition: transform .15s;
        }
        .price-tier.selected .price-tier-radio-dot { transform: scale(1); }
        .price-tier-info { flex: 1; }
        .price-tier-duration { font-size: 14px; font-weight: 700; color: var(--text); }
        .price-tier-amount { font-size: 12.5px; color: var(--sub); font-weight: 600; margin-top: 2px; }
        .price-tier-tag {
            position: absolute; top: -8px; right: 14px;
            background: var(--orange);
            color: #fff;
            font-size: 9.5px;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 20px;
        }

        /* pw error/success */
        .alert { border-radius: 14px; padding: 12px 16px; font-size: 13px; font-weight: 600; margin-bottom: 16px; }
        .alert-err  { background: var(--orange-light); color: var(--orange); }
        .alert-ok   { background: var(--green-light);  color: var(--green-mid); }
        /* ─── SAFE AREA (iPhone notch / home indicator) ─── */
        /* Top: samain warna hero gradient */
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0;
            height: env(safe-area-inset-top);
            background: #5D9669;
            z-index: 9999;
        }
        /* Bottom: samain warna bg utama */
        body::after {
            content: '';
            position: fixed;
            bottom: 0; left: 0; right: 0;
            height: env(safe-area-inset-bottom);
            background: var(--bg);
            z-index: 9999;
        }
        /* Sheet bottom safe area — warna putih sesuai sheet */
        .sheet {
            padding-bottom: max(32px, calc(20px + env(safe-area-inset-bottom)));
        }
    </style>
</head>
<body>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<div class="mobile-wrapper">

    <div class="status-space"></div>

    <!-- ─── HERO ─── -->
    <div class="hero">
        <div class="hero-top">
            <div class="page-title">Profil</div>
            <!-- Settings button: openSheet dipanggil langsung, tidak ada konflik modal -->
            <button class="settings-btn" id="settingsBtn" aria-label="Pengaturan" type="button">
                <img src="../assets/icon/iconpengaturan.png" alt="Pengaturan">
                <span> Pengaturan </span>
            </button>
        </div>

        <div class="avatar-wrap hero-asym">
            <!-- foto dari profiles.photo, fallback inisial nama anak -->
            <div class="avatar-ring" onclick="triggerPhotoUpload()">
                <div class="avatar" id="avatarEl">
                    <?php if ($childPhoto): ?>
                        <img src="<?= htmlspecialchars($childPhoto) ?>" alt="foto anak" id="avatarImg">
                    <?php else: ?>
                        <span id="avatarInitial"><?= htmlspecialchars(strtoupper(substr($child['profile_name'] ?? 'A', 0, 1))) ?></span>
                    <?php endif; ?>
                </div>
                <div class="camera-badge">
                    <svg viewBox="0 0 24 24"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                </div>
            </div>
            <div class="hero-asym-info">
                <div class="hero-asym-name"><?= htmlspecialchars($child['profile_name'] ?? '-') ?></div>
                <!-- ── POIN #1: Teks info fisik di header → SATU pintu ke editChildSheet,
                     sama persis dengan row Data Anak di bawah. Tidak ada lagi
                     pemisahan fungsi antara "tampil" dan "edit". Dibangun sebagai
                     satu string PHP (tanpa newline antar-echo) supaya tidak ada
                     whitespace tersembunyi yang memicu wrap visual. ── -->
                <?php
                    $physicalParts = [];
                    $physicalParts[] = is_numeric($childAge) ? $childAge . ' Tahun' : '-';
                    if ($child['gender'] ?? '') {
                        $physicalParts[] = (strtolower($child['gender']) === 'male') ? 'Laki-laki' : 'Perempuan';
                    }
                    $physicalParts[] = fmt($child['height'] ?? null) . ' cm';
                    $physicalParts[] = fmt($child['weight'] ?? null) . ' kg';
                    $physicalParts[] = 'BMI ' . $bmi;
                    $physicalLine = implode(' • ', $physicalParts);
                ?>
                <div class="hero-asym-physical tappable" id="rowPhysicalHeader"><span class="physical-text"><?= htmlspecialchars($physicalLine) ?></span><span class="chev-mini">›</span></div>
            </div>
        </div>
    </div>

    <!-- ─── DASHBOARD PILLS ROW (capsule, menggantikan grid 2x2) ─── -->
    <div class="content" style="padding-top:0; padding-bottom:0; margin-top:-4px; position:relative; z-index:10;">
        <div class="pills-surface">
            <div class="pills-row">
                <!-- Pill 1: Kalori → pages/dietary.php + auto trigger #add-food-btn -->
                <div class="pill-item" id="dashBoxCalories">
                    <div class="pill-icon" style="background:var(--orange-light)">
                        <img src="../assets/icon/iconkalori.png" alt="Kalori">
                    </div>

                    <div class="pill-value"><?= fmt($todayCalories) ?></div>
                    <div class="pill-unit">Kcal</div>
                </div>


                <div class="pill-item premium-locked" id="dashBoxWater">
                    <div class="pill-lock-badge"><img src="../assets/icon/icongembok.png" alt="Locked"></div>
                    <div class="pill-icon" style="background:var(--blue-light)">
                        <img src="../assets/icon/iconwater.png" alt="Air">
                    </div>

                    <div class="pill-value"><?= (int)$todayWaterMl ?></div>
                    <div class="pill-unit">ml</div>
                </div>


                <div class="pill-item" id="dashBoxWeight">
                    <div class="pill-icon" style="background:var(--green-light)">
                        <img src="../assets/icon/iconweight.png" alt="Berat">
                    </div>

                    <div class="pill-value">
                        <?= $latestWeight !== null ? fmt($latestWeight) : '-' ?>
                    </div>

                    <div class="pill-unit">Kg</div>
                </div>


                <div class="pill-item" id="dashBoxReminders">
                    <div class="pill-icon" style="background:var(--purple-light)">
                        <img src="../assets/icon/iconreminder.png" alt="Pengingat">
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- ─── CONTENT ─── -->
    <div class="content">

        <!-- ── Target Nutrisi Card ── -->
        <?php
        $targetColors = [
            'Standard'      => ['border'=>'#6FA87A', 'badge_bg'=>'#EEF5F0', 'badge_text'=>'#5D9669'],
            'Weight Gain'   => ['border'=>'#E8956D', 'badge_bg'=>'#FEF0EC', 'badge_text'=>'#C8693C'],
            'Weight Loss'   => ['border'=>'#5B9BD5', 'badge_bg'=>'#EEF2FF', 'badge_text'=>'#4182BE'],
            'Active Growth' => ['border'=>'#9B7BD6', 'badge_bg'=>'#F3EEFF', 'badge_text'=>'#7B5BB6'],
        ];
        $tc = $targetColors[$currentTarget] ?? $targetColors['Standard'];
        ?>
        <div class="sec-header">Target Nutrisi</div>
        <div class="target-card" style="border-left-color:<?= $tc['border'] ?>;">
            <div class="target-card-header">
                <div>
                    <div class="target-card-title"><?= htmlspecialchars($currentTarget) ?></div>
                    <div class="target-card-desc"><?= $tInfo['desc'] ?></div>
                </div>
                <div class="target-card-badge" style="background:<?= $tc['badge_bg'] ?>;color:<?= $tc['badge_text'] ?>;"><?= htmlspecialchars($currentTarget) ?></div>
            </div>
            <?php if ($nt): ?>
            <div class="target-macros">
                <div class="macro-pill">
                    <div class="macro-val"><?= fmt($nt['target_calories'] ?? null) ?></div>
                    <div class="macro-label">kkal</div>
                </div>
                <div class="macro-pill">
                    <div class="macro-val"><?= fmt($nt['target_protein'] ?? null) ?>g</div>
                    <div class="macro-label">Protein</div>
                </div>
                <div class="macro-pill">
                    <div class="macro-val"><?= fmt($nt['target_carbs'] ?? null) ?>g</div>
                    <div class="macro-label">Karbo</div>
                </div>
                <div class="macro-pill">
                    <div class="macro-val"><?= fmt($nt['target_fat'] ?? null) ?>g</div>
                    <div class="macro-label">Lemak</div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Data Anak ── -->
        <div class="sec-header">Data Anak</div>
        <div class="card">
            <div class="card-inner">
                <div class="row tappable" id="rowChildName">
                    <div class="row-icon green"><img src="../assets/icon/iconuser.png" alt="Nama"></div>
                    <div class="row-body">
                        <div class="row-label">Nama</div>
                    </div>
                    <div class="row-right">
                        <div class="row-value"><?= htmlspecialchars($child['profile_name'] ?? '-') ?></div>
                        <div class="chevron">›</div>
                    </div>
                </div>
                <div class="row tappable" id="rowBirth">
                    <div class="row-icon cream"><img src="../assets/icon/iconbirth.png" alt="Tanggal Lahir"></div>
                    <div class="row-body">
                        <div class="row-label">Tanggal Lahir</div>
                    </div>
                    <div class="row-right">
                        <div class="row-value">
                            <?php
                            echo !empty($child['birth_date'])
                                ? (new DateTime($child['birth_date']))->format('d M Y')
                                : '-';
                            ?>
                        </div>
                        <div class="chevron">›</div>
                    </div>
                </div>
                <div class="row tappable" id="rowPhysical">
                    <div class="row-icon blue"><img src="../assets/icon/iconmeasure.png" alt="Tinggi / Berat"></div>
                    <div class="row-body">
                        <div class="row-label">Tinggi / Berat</div>
                    </div>
                    <div class="row-right">
                        <div class="row-value"><?= fmt($child['height'] ?? null) ?>cm · <?= fmt($child['weight'] ?? null) ?>kg</div>
                        <div class="chevron">›</div>
                    </div>
                </div>
                <div class="row tappable" id="rowActivity">
                    <div class="row-icon green"><img src="../assets/icon/iconactivity.png" alt="Tingkat Aktivitas"></div>
                    <div class="row-body">
                        <div class="row-label">Tingkat Aktivitas</div>
                    </div>
                    <div class="row-right">
                        <div class="badge badge-green"><?= $curActivityUI ?></div>
                        <div class="chevron">›</div>
                    </div>
                </div>
                <div class="row tappable" id="rowTarget">
                    <div class="row-icon orange"><img src="../assets/icon/icontarget.png" alt="Target Nutrisi"></div>
                    <div class="row-body">
                        <div class="row-label">Target Nutrisi</div>
                        <div class="row-sub">Mempengaruhi semua kalkulasi</div>
                    </div>
                    <div class="row-right">
                        <div class="badge badge-orange"><?= htmlspecialchars($currentTarget) ?></div>
                        <div class="chevron">›</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Preferensi & Nutrisi (Premium Locked) ── -->
        <div class="sec-header">Preferensi & Nutrisi</div>
        <div class="card">
            <div class="card-inner">
                <!-- Locked: Pola Makan -->
                <div class="locked-row" onclick="openSheet('subSheet')">
                    <div class="locked-blur" style="display:flex; align-items:center; gap:12px;">
                        <div class="row-icon purple"><img src="../assets/icon/iconpolamakan.png" alt="Pola Makan"></div>
                        <div class="row-body">
                            <div class="row-label">Pola Makan</div>
                            <div class="row-sub">Vegetarian, vegan, halal...</div>
                        </div>
                    </div>
                    <div class="lock-badge"><img src="../assets/icon/icongembok.png" alt="Locked" style="width:18px;height:18px;object-fit:contain;"></div>
                </div>
                <!-- Locked: Target Air Minum -->
                <div class="locked-row" onclick="openSheet('subSheet')">
                    <div class="locked-blur" style="display:flex; align-items:center; gap:12px;">
                        <div class="row-icon blue"><img src="../assets/icon/icontargetminum.png" alt="Target Air Minum"></div>
                        <div class="row-body">
                            <div class="row-label">Target Air Minum</div>
                            <div class="row-sub">Kalkulasi kebutuhan cairan harian</div>
                        </div>
                    </div>
                    <div class="lock-badge"><img src="../assets/icon/icongembok.png" alt="Locked" style="width:18px;height:18px;object-fit:contain;"></div>
                </div>
                <!-- Locked: Meal Plan Otomatis -->
                <div class="locked-row" onclick="openSheet('subSheet')">
                    <div class="locked-blur" style="display:flex; align-items:center; gap:12px;">
                        <div class="row-icon cream"><img src="../assets/icon/iconmealplan.png" alt="Meal Plan Otomatis"></div>
                        <div class="row-body">
                            <div class="row-label">Meal Plan Otomatis</div>
                            <div class="row-sub">Rencana makan 7 hari personalisasi</div>
                        </div>
                    </div>
                    <div class="lock-badge"><img src="../assets/icon/icongembok.png" alt="Locked" style="width:18px;height:18px;object-fit:contain;"></div>
                </div>
            </div>
        </div>

        <!-- ── Akun ── -->
        <div class="sec-header">Akun</div>
        <div class="card">
            <div class="card-inner">
                <div class="row">
                    <div class="row-icon blue"><img src="../assets/icon/iconakun.png" alt="Akun"></div>
                    <div class="row-body">
                        <div class="row-label"><?= htmlspecialchars($user['name'] ?? '-') ?></div>
                        <div class="row-sub"><?= htmlspecialchars($user['email'] ?? '-') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Tentang ── -->
        <div class="sec-header">Tentang Aplikasi</div>
        <div class="card">
            <div class="card-inner">
                <div class="row">
                    <div class="row-icon green"><img src="../assets/img/iconnutrimoms.png" alt="NutriKids"></div>
                    <div class="row-body"><div class="row-label">Nutrimoms</div><div class="row-sub">Aplikasi nutrisi anak Indonesia</div></div>
                    <div class="row-right"><div class="badge badge-green">vDemo</div></div>
                </div>
                <div class="row tappable">
                    <div class="row-icon blue"><img src="../assets/icon/iconterms.png" alt="Kebijakan Privasi"></div>
                    <div class="row-body"><div class="row-label">Kebijakan Privasi</div></div>
                    <div class="chevron">›</div>
                </div>
            </div>
        </div>

        <a href="../auth/logout.php" class="logout-btn">
            <span>Keluar dari Akun</span>
        </a>
    </div>

    <!-- ─── NAVBAR ─── -->
 <!-- Navbar -->
    <nav class="navbar">

        <!-- Dietary -->
        <a href="dietary.php" class="nav-item">
            <div class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                    <!-- Garpu -->
                    <path d="M18 8h1a4 4 0 0 1 0 8h-1
                            M2 2v7a4 4 0 0 0 4 4h4a4 4 0 0 0 4-4V2
                            M6 2v5
                            M10 2v5
                            M6 13v9
                            M10 13v9"/>
                </svg>
            </div>
            <span>Dietary</span>
        </a>

        <!-- Report -->
        <a href="report.php" class="nav-item">
            <div class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                    <path d="M18 20V10"/>
                    <path d="M12 20V4"/>
                    <path d="M6 20v-6"/>
                </svg>
            </div>
            <span>Report</span>
        </a>

        <!-- Artikel -->
        <a href="article.php" class="nav-item">
            <div class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
            </div>
            <span>Artikel</span>
        </a>

        <!-- Profile -->
        <a href="profile.php" class="nav-item active">
            <div class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <span>Me</span>
        </a>

    </nav>

</div><!-- /.mobile-wrapper -->

<!-- ════════════════════════════════════════════════════════════════
     OVERLAYS — semua di luar .mobile-wrapper agar z-index bersih
     dan tidak ada konflik click/pointer-event dengan konten utama
════════════════════════════════════════════════════════════════ -->

<!-- ── SETTINGS SHEET ── -->
<div class="overlay" id="settingsSheet">
    <div class="sheet" onclick="event.stopPropagation()">
        <div class="handle"></div>
        <div class="sheet-title">Pengaturan</div>
        <div class="sheet-sub">Kelola akun & preferensi kamu</div>
        <div class="menu-list">
            <button class="menu-item" type="button" onclick="switchSheet('settingsSheet','emailSheet')">
                <div class="menu-item-icon" style="background:#EEF2FF"><img src="../assets/icon/iconemail.png" alt="Email"></div>
                <div class="menu-item-text">
                    <div class="menu-item-label">Ganti Email</div>
                    <div class="menu-item-desc"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                </div>
                <div class="menu-chevron">›</div>
            </button>
            <button class="menu-item" type="button" onclick="switchSheet('settingsSheet','passwordSheet')">
                <div class="menu-item-icon" style="background:#FFF8EC"><img src="../assets/icon/iconpassword.png" alt="Password"></div>
                <div class="menu-item-text">
                    <div class="menu-item-label">Ganti Password</div>
                    <div class="menu-item-desc">Ubah kata sandi akun kamu</div>
                </div>
                <div class="menu-chevron">›</div>
            </button>
            <button class="menu-item" type="button" onclick="switchSheet('settingsSheet','notifSheet')">
                <div class="menu-item-icon" style="background:#EEF5F0"><img src="../assets/icon/iconnot.png" alt="Notifikasi"></div>
                <div class="menu-item-text">
                    <div class="menu-item-label">Notifikasi</div>
                    <div class="menu-item-desc">Atur pengingat & laporan harian</div>
                </div>
                <div class="menu-chevron">›</div>
            </button>
            <button class="menu-item" type="button" onclick="closeSheet('settingsSheet'); triggerPhotoUpload()">
                <div class="menu-item-icon" style="background:#FEF0EC"><img src="../assets/icon/iconimage.png" alt="Foto Anak"></div>
                <div class="menu-item-text">
                    <div class="menu-item-label">Ganti Foto</div>
                    <div class="menu-item-desc">Upload & crop foto terbaru</div>
                </div>
                <div class="menu-chevron">›</div>
            </button>
        </div>
        <div style="margin-top:16px">
            <button class="save-btn" style="background:var(--bg);color:var(--sub);box-shadow:none" type="button" onclick="closeSheet('settingsSheet')">Tutup</button>
        </div>
    </div>
</div>

<!-- ── EDIT CHILD SHEET (satu form, tidak nested) ── -->
<div class="overlay" id="editChildSheet">
    <div class="sheet" onclick="event.stopPropagation()">
        <div class="handle"></div>
        <div class="sheet-title">Edit Profil</div>

        <form method="POST" id="editChildForm">
            <input type="hidden" name="action" value="update_child">
            <input type="hidden" name="activity_level" id="activityLevelInput" value="<?= htmlspecialchars($curActivityUIVal) ?>">
            <input type="hidden" name="nutrition_target" id="nutritionTargetInput" value="<?= htmlspecialchars($currentTarget) ?>">
            <input type="hidden" name="gender" id="genderInput" value="<?= htmlspecialchars(strtolower($child['gender'] ?? 'male')) ?>">

            <div class="form-group">
                <div class="form-label">Nama</div>
                <input type="text" name="child_name" class="input"
                    value="<?= htmlspecialchars($child['profile_name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <div class="form-label">Tanggal Lahir</div>
                <input type="date" name="birth_date" class="input"
                    value="<?= htmlspecialchars($child['birth_date'] ?? '') ?>"
                    max="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="input-row">
                <div class="form-group">
                    <div class="form-label">Tinggi</div>
                    <div class="input-suffix">
                        <input type="number" step="0.1" name="height" class="input"
                            value="<?= ($child['height'] ?? '') ?>">
                        <span class="suffix-text">cm</span>
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-label">Berat</div>
                    <div class="input-suffix">
                        <input type="number" step="0.1" name="weight" class="input"
                            value="<?= ($child['weight'] ?? '') ?>">
                        <span class="suffix-text">kg</span>
                    </div>
                </div>
            </div>

            <!-- Gender selector -->
            <div class="form-group">
                <div class="form-label">Jenis Kelamin</div>
                <div class="gender-grid">
                    <?php $curGender = strtolower($child['gender'] ?? 'male'); ?>
                    <div class="gender-opt <?= $curGender === 'male' ? 'selected' : '' ?>" data-value="male" onclick="selectGender(this)">
                        <div class="gender-opt-icon"><img src="../assets/icon/iconmale.png" alt="Laki-laki"></div>
                        <div class="gender-opt-name">Laki-laki</div>
                    </div>
                    <div class="gender-opt <?= $curGender === 'female' ? 'selected' : '' ?>" data-value="female" onclick="selectGender(this)">
                        <div class="gender-opt-icon"><img src="../assets/icon/iconfemale.png" alt="Perempuan"></div>
                        <div class="gender-opt-name">Perempuan</div>
                    </div>
                </div>
            </div>

            <!-- Aktivitas: 3 level UI → map ke enum DB -->
            <div class="form-group">
                <div class="form-label">Tingkat Aktivitas</div>
                <div class="activity-grid">
                    <?php
                    $actOpts = [
                        'rendah' => ['icon'=>'<img src="../assets/icon/iconlow.png" alt="Rendah" style="width:24px;height:24px;object-fit:contain;">', 'name'=>'Rendah',  'desc'=>'Santai, sedikit gerak'],
                        'sedang' => ['icon'=>'<img src="../assets/icon/iconmedium.png" alt="Sedang" style="width:24px;height:24px;object-fit:contain;">', 'name'=>'Sedang',  'desc'=>'Aktif 3–5x seminggu'],
                        'tinggi' => ['icon'=>'<img src="../assets/icon/iconhigh.png" alt="Tinggi" style="width:24px;height:24px;object-fit:contain;">', 'name'=>'Tinggi',  'desc'=>'Olahraga intens / atlet'],
                    ];
                    foreach ($actOpts as $val => $opt):
                        $sel = ($curActivityUIVal === $val) ? 'selected' : '';
                    ?>
                    <div class="act-opt <?= $sel ?>" data-value="<?= $val ?>" onclick="selectActivity(this)">
                        <div class="act-opt-icon"><?= $opt['icon'] ?></div>
                        <div class="act-opt-name"><?= $opt['name'] ?></div>
                        <div class="act-opt-desc"><?= $opt['desc'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Nutrition Target: 4 pilihan dengan efek nyata -->
            <div class="form-group">
                <div class="form-label">Target Nutrisi</div>
                <div class="target-grid">
                    <?php
                    $targets = [
                        'Standard'      => ['icon'=>'<img src="../assets/icon/iconstandard.png" alt="Standard" style="width:24px;height:24px;object-fit:contain;">', 'desc'=>'Kebutuhan gizi normal harian'],
                        'Weight Gain'   => ['icon'=>'<img src="../assets/icon/iconweightgain.png" alt="Weight Gain" style="width:24px;height:24px;object-fit:contain;">', 'desc'=>'+10% kalori · protein lebih tinggi'],
                        'Weight Loss'   => ['icon'=>'<img src="../assets/icon/iconweightloss.png" alt="Weight Loss" style="width:24px;height:24px;object-fit:contain;">', 'desc'=>'−10% kalori · lemak dibatasi'],
                        'Active Growth' => ['icon'=>'<img src="../assets/icon/icongrwoth.png" alt="Active Growth" style="width:24px;height:24px;object-fit:contain;">', 'desc'=>'+5% kalori · protein maksimal'],
                    ];
                    foreach ($targets as $key => $t):
                        $sel = ($currentTarget === $key) ? 'selected' : '';
                    ?>
                    <div class="tgt-opt <?= $sel ?>" data-value="<?= $key ?>" onclick="selectTarget(this)">
                        <div class="tgt-opt-icon"><?= $t['icon'] ?></div>
                        <div class="tgt-opt-name"><?= $key ?></div>
                        <div class="tgt-opt-desc"><?= $t['desc'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="save-btn">Simpan</button>
        </form>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════════
     POIN #2 — WEIGHT TRACK SHEET (Kotak 3 / "My Weight")
     Grafik (Line Chart) + History List dikelompokkan per tahun,
     keduanya disuplai dari profile_weights_get.php (AJAX),
     dan form tambah entri yang submit ke action=add_weight.
════════════════════════════════════════════════════════════════ -->
<div class="overlay" id="weightSheet">
    <div class="sheet" onclick="event.stopPropagation()">
        <div class="handle"></div>
        <div class="sheet-title">Riwayat Berat Badan</div>
        <div class="sheet-sub">Pantau perkembangan berat badan dari waktu ke waktu</div>

        <!-- Form tambah berat — INSERT ke profile_weights via action=add_weight -->
        <form method="POST" class="weight-add-form" id="weightAddForm">
            <input type="hidden" name="action" value="add_weight">
            <input type="hidden" name="logged_date" value="<?= date('Y-m-d') ?>">
            <div class="weight-add-input-wrap">
                <input type="number" step="0.1" name="new_weight" class="weight-add-input"
                    placeholder="Berat hari ini" required>
                <span class="weight-add-suffix">kg</span>
            </div>
            <button type="submit" class="weight-add-submit">+ Catat</button>
        </form>

        <!-- Grafik (Line Chart) — di-render via JS dari data profile_weights_get.php -->
        <div class="weight-chart-wrap">
            <div class="weight-chart-head">
                <div>
                    <div class="weight-chart-latest" id="weightChartLatestVal">
                        <?= $latestWeight !== null ? fmt($latestWeight) : '-' ?><small> kg</small>
                    </div>
                    <div class="weight-chart-date" id="weightChartLatestDate">
                        <?= $latestWeightDate ? (new DateTime($latestWeightDate))->format('d M Y') : 'Belum ada data' ?>
                    </div>
                </div>
            </div>
            <div class="weight-svg-wrap" id="weightChartSvgWrap">
                <div class="weight-empty">Memuat grafik...</div>
            </div>
        </div>

        <!-- History List — dikelompokkan per tahun via JS -->
        <div id="weightHistoryWrap">
            <div class="weight-empty">Memuat riwayat...</div>
        </div>

        <div style="margin-top:8px">
            <button class="save-btn" style="background:var(--bg);color:var(--sub);box-shadow:none" type="button" onclick="closeSheet('weightSheet')">Tutup</button>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════════
     KOTAK 2 — WATER TARGET BOTTOM SHEET
     Quick-add air minum. (Catatan: endpoint penyimpanan air mengikuti
     tabel water_logs — sesuaikan nama tabel/kolom dengan skema asli
     project jika berbeda dari asumsi di sini.)
════════════════════════════════════════════════════════════════ -->
<div class="overlay" id="waterSheet">
    <div class="sheet" onclick="event.stopPropagation()">
        <div class="handle"></div>
        <div class="sheet-title">Target Air Minum</div>
        <div class="sheet-sub">Catat asupan air harian</div>

        <div class="water-target-display">
            <div class="water-target-value" id="waterCurrentVal">
                <?= (int)$todayWaterMl ?><small> / <?= (int)$waterTargetMl ?> mL</small>
            </div>
        </div>

        <div class="water-quick-row">
            <button class="water-quick-btn" type="button" onclick="quickAddWater(200)">+200 mL</button>
            <button class="water-quick-btn" type="button" onclick="quickAddWater(300)">+300 mL</button>
            <button class="water-quick-btn" type="button" onclick="quickAddWater(500)">+500 mL</button>
        </div>

        <button class="save-btn" style="background:var(--bg);color:var(--sub);box-shadow:none" type="button" onclick="closeSheet('waterSheet')">Tutup</button>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════════
     POIN #3 — REMINDERS SHEET
     List alarm (reminder_meal / reminder_water) dari tabel
     notifications, query via getReminderAlarms() di server-side
     render awal, lalu CRUD via AJAX (notifications_create.php /
     notifications_delete.php).
════════════════════════════════════════════════════════════════ -->
<div class="overlay" id="remindersSheet">
    <div class="sheet" onclick="event.stopPropagation()">
        <div class="handle"></div>
        <div class="reminders-header">
            <div>
                <div class="sheet-title" style="margin-bottom:2px">Pengingat</div>
                <div class="sheet-sub" style="margin-bottom:0">Atur alarm makan & minum air</div>
            </div>
            <button class="reminders-add-btn" type="button" id="remindersAddBtn" aria-label="Tambah Pengingat">+</button>
        </div>

        <div id="remindersListWrap" style="margin-top:18px">
            <?php if (empty($reminderAlarms)): ?>
                <div class="reminders-empty">
                    <div class="reminders-empty-icon">⏰</div>
                    <div class="reminders-empty-text">Belum ada pengingat.<br>Tekan "+" untuk menambahkan.</div>
                </div>
            <?php else: ?>
                <?php foreach ($reminderAlarms as $alarm):
                    $isWater = ($alarm['type'] === 'reminder_water');
                    $iconBg  = $isWater ? 'var(--blue-light)' : 'var(--orange-light)';
                    $icon    = $isWater ? '💧' : '🍽️';
                ?>
                <div class="reminder-card" data-reminder-id="<?= (int)$alarm['id'] ?>">
                    <div class="reminder-icon" style="background:<?= $iconBg ?>"><?= $icon ?></div>
                    <div class="reminder-body">
                        <div class="reminder-title"><?= htmlspecialchars($alarm['title']) ?></div>
                        <div class="reminder-time"><?= htmlspecialchars($alarm['body']) ?></div>
                    </div>
                    <button class="reminder-del-btn" type="button" onclick="deleteReminder(<?= (int)$alarm['id'] ?>, this)">✕</button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="margin-top:8px">
            <button class="save-btn" style="background:var(--bg);color:var(--sub);box-shadow:none" type="button" onclick="closeSheet('remindersSheet')">Tutup</button>
        </div>
    </div>
</div>

<!-- ── ADD REMINDER SHEET (kategori + time picker) ── -->
<div class="overlay" id="addReminderSheet">
    <div class="sheet" onclick="event.stopPropagation()">
        <div class="handle"></div>
        <div class="sheet-title">Tambah Pengingat</div>
        <div class="sheet-sub">Pilih kategori dan jam</div>

        <div class="cat-opt-list" id="catOptList">
            <?php
            $reminderCats = [
            'Makan Pagi'  => '../assets/icon/iconpagi.png',
            'Makan Siang' => '../assets/icon/iconsiang.png',
            'Makan Malam' => '../assets/icon/iconmalam.png',
            'Camilan'     => '../assets/icon/iconsnack.png',
        ];

        foreach ($reminderCats as $catName => $catIcon):
        ?>
            <div class="cat-opt"
                data-value="<?= htmlspecialchars($catName) ?>"
                onclick="selectReminderCat(this)">

                <div class="cat-opt-icon">
                    <img src="<?= htmlspecialchars($catIcon) ?>"
                        alt="<?= htmlspecialchars($catName) ?>">
                </div>

                <div class="cat-opt-name">
                    <?= htmlspecialchars($catName) ?>
                </div>

            </div>
        <?php endforeach; ?>
        </div>

        <div class="form-group">
            <div class="form-label">Jam</div>
            <div class="time-picker-wrap">
                <input type="time" id="reminderTimeInput" class="time-picker-input" value="07:00">
            </div>
        </div>

        <button class="save-btn" type="button" id="saveReminderBtn" disabled style="opacity:.5">Simpan</button>
    </div>
</div>

<!-- ── EMAIL SHEET ── -->
<div class="overlay" id="emailSheet">
    <div class="sheet" onclick="event.stopPropagation()">
        <div class="handle"></div>
        <button class="back-btn" type="button" onclick="switchSheet('emailSheet','settingsSheet')">
            <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
            Kembali
        </button>
        <div class="sheet-title">Ganti Email</div>
        <div class="info-box">Email saat ini: <strong><?= htmlspecialchars($user['email'] ?? '') ?></strong></div>
        <form method="POST">
            <input type="hidden" name="action" value="update_account">
            <input type="hidden" name="field" value="email">
            <div class="form-group">
                <div class="form-label">Email Baru</div>
                <input type="email" name="new_email" class="input" placeholder="email@baru.com" required>
            </div>
            <button type="submit" class="save-btn">Perbarui Email</button>
        </form>
    </div>
</div>

<!-- ── PASSWORD SHEET ── -->
<div class="overlay" id="passwordSheet">
    <div class="sheet" onclick="event.stopPropagation()">
        <div class="handle"></div>
        <button class="back-btn" type="button" onclick="switchSheet('passwordSheet','settingsSheet')">
            <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
            Kembali
        </button>
        <div class="sheet-title">Ganti Password</div>

        <?php if ($pwError): ?>
        <div class="alert alert-err">⚠️ <?= htmlspecialchars($pwError) ?></div>
        <?php endif; ?>
        <?php if ($pwSuccess): ?>
        <div class="alert alert-ok">✅ <?= htmlspecialchars($pwSuccess) ?></div>
        <?php endif; ?>

        <form method="POST" onsubmit="return validatePwForm()">
            <input type="hidden" name="action" value="update_account">
            <input type="hidden" name="field" value="password">
            <div class="form-group">
                <div class="form-label">Password Saat Ini</div>
                <input type="password" name="current_password" class="input" placeholder="••••••••" required>
            </div>
            <div class="form-group">
                <div class="form-label">Password Baru <span style="color:var(--sub);font-weight:400;text-transform:none;letter-spacing:0">(min. 6 karakter)</span></div>
                <input type="password" name="new_password" id="pwNew" class="input" placeholder="••••••••" required oninput="checkPwMatch()">
            </div>
            <div class="form-group">
                <div class="form-label">Konfirmasi Password</div>
                <input type="password" name="confirm_password" id="pwConfirm" class="input" placeholder="••••••••" required oninput="checkPwMatch()">
                <div id="pwMatchMsg" style="font-size:12px;margin-top:6px;display:none"></div>
            </div>
            <button type="submit" class="save-btn" id="pwSubmitBtn">Simpan Password</button>
        </form>
    </div>
</div>

<!-- ── NOTIF SHEET ── -->
<div class="overlay" id="notifSheet">
    <div class="sheet" onclick="event.stopPropagation()">
        <div class="handle"></div>
        <button class="back-btn" type="button" onclick="switchSheet('notifSheet','settingsSheet')">
            <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
            Kembali
        </button>
        <div class="sheet-title">Notifikasi</div>

        <div class="form-group">
            <div class="form-label">Pengingat Makan</div>
            <div style="display:flex;align-items:center;justify-content:space-between;background:var(--bg);padding:14px 16px;border-radius:14px;">
                <span style="font-size:14px;font-weight:500">Ingatkan waktu makan</span>
                <label class="toggle-label">
                    <input type="checkbox" checked id="notif1">
                    <span class="toggle-track" id="notif1track" style="background:var(--green)">
                        <span class="toggle-thumb" style="transform:translateX(18px)"></span>
                    </span>
                </label>
            </div>
        </div>
        <div class="form-group">
            <div class="form-label">Laporan Harian</div>
            <div style="display:flex;align-items:center;justify-content:space-between;background:var(--bg);padding:14px 16px;border-radius:14px;">
                <span style="font-size:14px;font-weight:500">Kirim ringkasan nutrisi</span>
                <label class="toggle-label">
                    <input type="checkbox" id="notif2">
                    <span class="toggle-track" id="notif2track" style="background:#ccc">
                        <span class="toggle-thumb"></span>
                    </span>
                </label>
            </div>
        </div>
        <button class="save-btn" type="button" onclick="showToast('Pengaturan notifikasi disimpan ✓'); closeSheet('notifSheet')">Simpan</button>
    </div>
</div>

<!-- ── SUBSCRIPTION SHEET ── -->
<div class="overlay" id="subSheet">
    <div class="sheet sub-popup-inner" onclick="event.stopPropagation()">
        <div class="handle"></div>
        <div class="sheet-title">Pilih Langganan</div>
        <div class="sheet-sub">Buka fitur lengkap untuk nutrisi anak yang lebih optimal</div>

        <!-- ── a. Benefit Section ── -->
        <div class="sub-benefit-row">
            <div class="sub-benefit-check">✓</div>
            <div class="sub-benefit-text">Track Intake Minuman</div>
        </div>
        <div class="sub-benefit-row">
            <div class="sub-benefit-check">✓</div>
            <div class="sub-benefit-text">Custom Alarm Pengingat Nutrisi</div>
        </div>
        <div class="sub-benefit-row">
            <div class="sub-benefit-check">✓</div>
            <div class="sub-benefit-text">Personal Meal Plan Otomatis</div>
        </div>
        <div class="sub-benefit-row">
            <div class="sub-benefit-check">✓</div>
            <div class="sub-benefit-text">Voice to Draft Smart Tracker</div>
        </div>

        <!-- ── b. Pricing Tier List (radio item) ── -->
        <div class="price-tier-list" id="priceTierList">
            <div class="price-tier selected" data-value="1bulan" data-label="1 Bulan — Rp 59.000" onclick="selectPriceTier(this)">
                <div class="price-tier-radio"><div class="price-tier-radio-dot"></div></div>
                <div class="price-tier-info">
                    <div class="price-tier-duration">1 Bulan</div>
                    <div class="price-tier-amount">Rp 59.000</div>
                </div>
            </div>
            <div class="price-tier" data-value="3bulan" data-label="3 Bulan — Rp 149.000" onclick="selectPriceTier(this)">
                <div class="price-tier-tag">🔥 Populer</div>
                <div class="price-tier-radio"><div class="price-tier-radio-dot"></div></div>
                <div class="price-tier-info">
                    <div class="price-tier-duration">3 Bulan</div>
                    <div class="price-tier-amount">Rp 149.000</div>
                </div>
            </div>
            <div class="price-tier" data-value="12bulan" data-label="12 Bulan — Rp 259.000" onclick="selectPriceTier(this)">
                <div class="price-tier-tag" style="background:var(--green-mid)">💰 Hemat</div>
                <div class="price-tier-radio"><div class="price-tier-radio-dot"></div></div>
                <div class="price-tier-info">
                    <div class="price-tier-duration">12 Bulan</div>
                    <div class="price-tier-amount">Rp 259.000</div>
                </div>
            </div>
        </div>

        <div style="margin-top: 4px;">
            <button class="sub-cta" type="button" id="subCtaBtn" onclick="handleSubCta()">Langganan</button>
            <button class="sub-cta-free" type="button" onclick="closeSheet('subSheet')">Lanjut pakai Free</button>
        </div>
    </div>
</div>

<!-- ── CROP PHOTO SHEET ── -->
<div class="overlay" id="cropSheet">
    <div class="sheet" onclick="event.stopPropagation()">
        <div class="handle"></div>
        <div class="sheet-title">Atur Foto</div>
        <div id="cropContainer">
            <img id="cropImg" src="" alt="foto" draggable="false">
            <div class="crop-circle-mask"></div>
        </div>
        <input type="range" class="zoom-slider" id="zoomSlider" min="1" max="3" step="0.01" value="1">
        <div class="crop-actions">
            <button class="btn-cancel" type="button" onclick="closeSheet('cropSheet')">Batal</button>
            <button class="btn-apply" type="button" onclick="applyCrop()">Gunakan Foto</button>
        </div>
    </div>
</div>

<!-- Hidden file + photo form -->
<input type="file" id="photoFileInput" accept="image/*" style="display:none" onchange="onFileChosen(event)">
<form id="photoForm" method="POST" style="display:none">
    <input type="hidden" name="action" value="upload_photo">
    <input type="hidden" name="crop_data" id="cropDataInput">
</form>

<script>
/* ════════════════════════════════════════════════
   SHEET SYSTEM
   Semua overlay di luar mobile-wrapper → z-index bersih
   closeIfOverlay tidak dipakai lagi;
   overlay background ditangani lewat onclick pada .overlay sendiri
   dengan stopPropagation pada .sheet
════════════════════════════════════════════════ */

function openSheet(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('active');
}
function closeSheet(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('active');
}
function switchSheet(fromId, toId) {
    closeSheet(fromId);
    setTimeout(() => openSheet(toId), 220);
}

// Klik backdrop (overlay itu sendiri) → tutup
document.querySelectorAll('.overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            overlay.classList.remove('active');
        }
    });
});

// Settings button — event listener terpisah, bukan onclick inline
// agar tidak ada konflik dengan event bubbling dari modal lain
document.getElementById('settingsBtn').addEventListener('click', function(e) {
    e.stopPropagation();
    openSheet('settingsSheet');
});

// Edit profil — dipicu dari teks info fisik di header (poin #1: satu pintu,
// menggantikan tombol "Edit Data Anak" lama yang terpisah dari tampilan data)
var rowPhysicalHeaderEl = document.getElementById('rowPhysicalHeader');
if (rowPhysicalHeaderEl) {
    rowPhysicalHeaderEl.addEventListener('click', function(e) {
        e.stopPropagation();
        openSheet('editChildSheet');
    });
}

// Semua row tappable di card Data Anak → buka editChildSheet
['rowChildName','rowBirth','rowPhysical','rowActivity','rowTarget'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('click', function() { openSheet('editChildSheet'); });
});

// Auto-buka password sheet jika ada error/success dari server
window.addEventListener('DOMContentLoaded', function() {
    if (<?= $pwOpen ?>) {
        // Buka settings dulu, lalu langsung ke password
        openSheet('passwordSheet');
    }
});

/* ── ACTIVITY SELECTOR ── */
function selectActivity(el) {
    document.querySelectorAll('.act-opt').forEach(function(o) { o.classList.remove('selected'); });
    el.classList.add('selected');
    document.getElementById('activityLevelInput').value = el.dataset.value;
}

/* ── TARGET SELECTOR ── */
function selectTarget(el) {
    document.querySelectorAll('.tgt-opt').forEach(function(o) { o.classList.remove('selected'); });
    el.classList.add('selected');
    document.getElementById('nutritionTargetInput').value = el.dataset.value;
}

/* ── GENDER SELECTOR ── */
function selectGender(el) {
    document.querySelectorAll('.gender-opt').forEach(function(o) { o.classList.remove('selected'); });
    el.classList.add('selected');
    document.getElementById('genderInput').value = el.dataset.value;
}

/* ── PASSWORD MATCH ── */
function checkPwMatch() {
    var nw  = document.getElementById('pwNew').value;
    var cf  = document.getElementById('pwConfirm').value;
    var msg = document.getElementById('pwMatchMsg');
    var btn = document.getElementById('pwSubmitBtn');
    if (!cf) { msg.style.display = 'none'; return; }
    msg.style.display = 'block';
    if (nw === cf) {
        msg.style.color   = '#5D9669';
        msg.textContent   = '✓ Password cocok';
        btn.disabled      = false;
        btn.style.opacity = '1';
    } else {
        msg.style.color   = '#E8956D';
        msg.textContent   = '✗ Password tidak cocok';
        btn.disabled      = true;
        btn.style.opacity = '0.5';
    }
}
function validatePwForm() {
    var nw = document.getElementById('pwNew').value;
    var cf = document.getElementById('pwConfirm').value;
    if (nw !== cf)    { alert('Password baru dan konfirmasi tidak cocok!'); return false; }
    if (nw.length < 6){ alert('Password minimal 6 karakter.'); return false; }
    return true;
}

/* ── TOGGLE SWITCHES ── */
function setupToggle(id) {
    var cb    = document.getElementById(id);
    var track = document.getElementById(id + 'track');
    var thumb = track.querySelector('span');
    function update() {
        track.style.background    = cb.checked ? 'var(--green)' : '#ccc';
        thumb.style.transform     = cb.checked ? 'translateX(18px)' : 'translateX(0)';
    }
    cb.addEventListener('change', update);
    update();
}
setupToggle('notif1');
setupToggle('notif2');

/* ── TOAST ── */
function showToast(msg) {
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(function() { t.classList.remove('show'); }, 2800);
}

/* ── PHOTO / CROP ── */
function triggerPhotoUpload() {
    document.getElementById('photoFileInput').value = '';
    document.getElementById('photoFileInput').click();
}

var imgEl, containerEl;
var drag = false, startX, startY, imgX = 0, imgY = 0;
var currentScale = 1;

function onFileChosen(e) {
    var file = e.target.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function(ev) {
        openSheet('cropSheet');
        initCrop(ev.target.result);
    };
    reader.readAsDataURL(file);
}

function initCrop(src) {
    containerEl = document.getElementById('cropContainer');
    imgEl       = document.getElementById('cropImg');
    imgEl.src   = src;
    imgX = imgY = 0;
    currentScale = 1;
    document.getElementById('zoomSlider').value = 1;

    imgEl.onload = function() {
        var cw = containerEl.clientWidth;
        var iw = imgEl.naturalWidth, ih = imgEl.naturalHeight;
        var fitScale = Math.max(cw / iw, cw / ih);
        currentScale = fitScale;
        document.getElementById('zoomSlider').min   = fitScale;
        document.getElementById('zoomSlider').value = fitScale;
        positionImg();
    };

    containerEl.onpointerdown = function(e) {
        drag = true;
        startX = e.clientX - imgX;
        startY = e.clientY - imgY;
        containerEl.setPointerCapture(e.pointerId);
    };
    containerEl.onpointermove = function(e) {
        if (!drag) return;
        imgX = e.clientX - startX;
        imgY = e.clientY - startY;
        positionImg();
    };
    containerEl.onpointerup = function() { drag = false; };

    document.getElementById('zoomSlider').oninput = function() {
        currentScale = parseFloat(this.value);
        positionImg();
    };
}

function positionImg() {
    imgEl.style.transform = 'translate(calc(-50% + ' + imgX + 'px), calc(-50% + ' + imgY + 'px)) scale(' + currentScale + ')';
    imgEl.style.left = '50%';
    imgEl.style.top  = '50%';
}

function applyCrop() {
    var cw = containerEl.clientWidth;
    var ch = containerEl.clientHeight;
    var circleR = cw * 0.36;
    var cx = cw / 2, cy = ch / 2;
    var iw = imgEl.naturalWidth  * currentScale;
    var ih = imgEl.naturalHeight * currentScale;
    var imgLeft = cx + imgX - iw / 2;
    var imgTop  = cy + imgY - ih / 2;
    var srcX = (cx - circleR - imgLeft) / currentScale;
    var srcY = (cy - circleR - imgTop)  / currentScale;
    var srcD = (circleR * 2)            / currentScale;

    var canvas = document.createElement('canvas');
    canvas.width = canvas.height = 256;
    var ctx = canvas.getContext('2d');
    ctx.beginPath();
    ctx.arc(128, 128, 128, 0, Math.PI * 2);
    ctx.clip();
    ctx.drawImage(imgEl, srcX, srcY, srcD, srcD, 0, 0, 256, 256);

    var dataUrl = canvas.toDataURL('image/jpeg', 0.9);

    // Preview
    var av  = document.getElementById('avatarEl');
    var img = document.getElementById('avatarImg');
    if (!img) {
        av.innerHTML = '';
        img = document.createElement('img');
        img.id = 'avatarImg';
        av.appendChild(img);
    }
    img.src = dataUrl;

    document.getElementById('cropDataInput').value = dataUrl;
    document.getElementById('photoForm').submit();
    closeSheet('cropSheet');
    showToast('Foto profil diperbarui! ✓');
}

/* ════════════════════════════════════════════════════════════════
   NEW — DASHBOARD GRID 2x2 CLICK HANDLERS
════════════════════════════════════════════════════════════════ */

// Kotak 1: Kalori Hari Ini → pages/dietary.php + auto trigger #add-food-btn.
// Cross-page navigation, jadi pakai sessionStorage flag yang dibaca
// oleh dietary.php saat load untuk auto-klik tombol add-food.
var dashBoxCaloriesEl = document.getElementById('dashBoxCalories');
if (dashBoxCaloriesEl) {
    dashBoxCaloriesEl.addEventListener('click', function() {
        sessionStorage.setItem('autoOpenAddFood', '1');
        window.location.href = 'dietary.php';
    });
}

// Pill 2: Air Minum (Premium-locked) → buka Subscription Bottom Sheet
var dashBoxWaterEl = document.getElementById('dashBoxWater');
if (dashBoxWaterEl) {
    dashBoxWaterEl.addEventListener('click', function() {
        openSheet('subSheet');
    });
}

// Pill 3: Berat Badan → expand modul Track Weight (chart & history)
var dashBoxWeightEl = document.getElementById('dashBoxWeight');
if (dashBoxWeightEl) {
    dashBoxWeightEl.addEventListener('click', function() {
        openSheet('weightSheet');
        loadWeightData();
    });
}

// Pill 4: Pengingat → buka modal alarm list custom
var dashBoxRemindersEl = document.getElementById('dashBoxReminders');
if (dashBoxRemindersEl) {
    dashBoxRemindersEl.addEventListener('click', function() {
        openSheet('remindersSheet');
    });
}

/* ════════════════════════════════════════════════════════════════
   NEW — WEIGHT TRACK MODULE (chart + history via profile_weights_get.php)
════════════════════════════════════════════════════════════════ */
var weightDataLoaded = false;

var lastWeightChartData = null;

function loadWeightData() {
    fetch('../api/profile_weights_get.php')
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (!data.success) return;
            lastWeightChartData = data.chart;
            // requestAnimationFrame: pastikan sheet sudah selesai layout
            // (slideUp animation + overflow reflow) sebelum baca clientWidth,
            // supaya SVG tidak ter-render dengan width 0 (bug chart kosong).
            requestAnimationFrame(function() {
                renderWeightChart(data.chart);
                renderWeightHistory(data.history);
            });
        })
        .catch(function(err) {
            console.error('Gagal memuat data berat badan:', err);
        });
}

// Re-render chart saat ukuran viewport berubah (orientasi device, dsb)
// supaya SVG tetap full-width dan tidak terpotong.
window.addEventListener('resize', function() {
    if (lastWeightChartData) {
        renderWeightChart(lastWeightChartData);
    }
});

function renderWeightChart(chartData) {
    var wrap = document.getElementById('weightChartSvgWrap');
    if (!wrap) return;
    if (!chartData || chartData.length === 0) {
        wrap.innerHTML = '<div class="weight-empty">Belum ada data untuk ditampilkan.<br>Catat berat badan pertamamu di atas.</div>';
        return;
    }

    var w = wrap.clientWidth || 320;
    var h = 140;
    var padX = 12, padY = 16;

    var weights = chartData.map(function(d) { return d.weight; });
    var minW = Math.min.apply(null, weights);
    var maxW = Math.max.apply(null, weights);
    if (minW === maxW) { minW -= 1; maxW += 1; }

    var n = chartData.length;
    var stepX = n > 1 ? (w - padX * 2) / (n - 1) : 0;

    var points = chartData.map(function(d, i) {
        var x = padX + stepX * i;
        var y = padY + (h - padY * 2) * (1 - (d.weight - minW) / (maxW - minW));
        return { x: x, y: y, weight: d.weight, date: d.date };
    });

    var pathD = points.map(function(p, i) {
        return (i === 0 ? 'M' : 'L') + p.x.toFixed(1) + ',' + p.y.toFixed(1);
    }).join(' ');

    var areaD = pathD + ' L' + points[points.length - 1].x.toFixed(1) + ',' + (h - padY) +
                ' L' + points[0].x.toFixed(1) + ',' + (h - padY) + ' Z';

    var circles = points.map(function(p) {
        return '<circle cx="' + p.x.toFixed(1) + '" cy="' + p.y.toFixed(1) + '" r="3.5" fill="#6FA87A" stroke="#fff" stroke-width="1.5"/>';
    }).join('');

    var svg = '<svg viewBox="0 0 ' + w + ' ' + h + '" width="100%" height="100%" preserveAspectRatio="none">' +
        '<defs><linearGradient id="weightGradFill" x1="0" y1="0" x2="0" y2="1">' +
        '<stop offset="0%" stop-color="#6FA87A" stop-opacity="0.25"/>' +
        '<stop offset="100%" stop-color="#6FA87A" stop-opacity="0"/>' +
        '</linearGradient></defs>' +
        '<path d="' + areaD + '" fill="url(#weightGradFill)"/>' +
        '<path d="' + pathD + '" fill="none" stroke="#6FA87A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>' +
        circles +
        '</svg>';

    wrap.innerHTML = svg;
}

function renderWeightHistory(history) {
    var wrap = document.getElementById('weightHistoryWrap');
    if (!wrap) return;
    if (!history || history.length === 0) {
        wrap.innerHTML = '';
        return;
    }

    var html = '';
    history.forEach(function(yearGroup) {
        html += '<div class="weight-history-year">' + yearGroup.year + '</div>';
        yearGroup.entries.forEach(function(entry) {
            html += '<div class="weight-history-row">' +
                '<div class="weight-history-date">' + entry.label + '</div>' +
                '<div class="weight-history-value">' + entry.weight + ' kg</div>' +
                '</div>';
        });
    });
    wrap.innerHTML = html;
}

/* ════════════════════════════════════════════════════════════════
   NEW — WATER QUICK ADD
   Catatan: endpoint penyimpanan water_logs adalah ASUMSI nama tabel.
   Sesuaikan dengan skema asli project jika berbeda.
════════════════════════════════════════════════════════════════ */
function quickAddWater(amountMl) {
    fetch('../config/water_log_add.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ amount_ml: amountMl })
    })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                showToast('+' + amountMl + ' mL dicatat ✓');
                if (typeof data.total_ml !== 'undefined') {
                    document.getElementById('waterCurrentVal').innerHTML =
                        data.total_ml + '<small> / <?= (int)$waterTargetMl ?> mL</small>';
                }
            } else {
                showToast(data.message || 'Gagal mencatat air minum');
            }
        })
        .catch(function() {
            showToast('Gagal menghubungi server');
        });
}

/* ════════════════════════════════════════════════════════════════
   NEW — REMINDERS CRUD (notifications_create.php / notifications_delete.php)
════════════════════════════════════════════════════════════════ */
var selectedReminderCat = null;

document.getElementById('remindersAddBtn').addEventListener('click', function(e) {
    e.stopPropagation();
    switchSheet('remindersSheet', 'addReminderSheet');
});

function selectReminderCat(el) {
    document.querySelectorAll('.cat-opt').forEach(function(o) { o.classList.remove('selected'); });
    el.classList.add('selected');
    selectedReminderCat = el.dataset.value;
    var btn = document.getElementById('saveReminderBtn');
    btn.disabled = false;
    btn.style.opacity = '1';
}

document.getElementById('saveReminderBtn').addEventListener('click', function() {
    if (!selectedReminderCat) return;
    var time = document.getElementById('reminderTimeInput').value;
    if (!time) { showToast('Pilih jam terlebih dahulu'); return; }

    fetch('../api/notifications_create.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ category: selectedReminderCat, time: time })
    })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                showToast('Pengingat disimpan ✓');
                addReminderCardToList(data.reminder);
                switchSheet('addReminderSheet', 'remindersSheet');
                selectedReminderCat = null;
            } else {
                showToast(data.message || 'Gagal menyimpan pengingat');
            }
        })
        .catch(function() {
            showToast('Gagal menghubungi server');
        });
});

function addReminderCardToList(reminder) {
    var wrap = document.getElementById('remindersListWrap');
    var emptyState = wrap.querySelector('.reminders-empty');
    if (emptyState) emptyState.remove();

    var isWater = reminder.type === 'reminder_water';
    var iconBg  = isWater ? 'var(--blue-light)' : 'var(--orange-light)';
    var icon    = isWater ? '💧' : '🍽️';

    var card = document.createElement('div');
    card.className = 'reminder-card';
    card.dataset.reminderId = reminder.id;
    card.innerHTML =
        '<div class="reminder-icon" style="background:' + iconBg + '">' + icon + '</div>' +
        '<div class="reminder-body">' +
            '<div class="reminder-title">' + reminder.title + '</div>' +
            '<div class="reminder-time">' + reminder.body + '</div>' +
        '</div>' +
        '<button class="reminder-del-btn" type="button" onclick="deleteReminder(' + reminder.id + ', this)">✕</button>';
    wrap.appendChild(card);
}

function deleteReminder(notificationId, btnEl) {
    fetch('../api/notifications_delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ notification_id: notificationId })
    })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                var card = btnEl.closest('.reminder-card');
                if (card) card.remove();

                var wrap = document.getElementById('remindersListWrap');
                if (wrap.querySelectorAll('.reminder-card').length === 0) {
                    wrap.innerHTML =
                        '<div class="reminders-empty">' +
                            '<div class="reminders-empty-icon">⏰</div>' +
                            '<div class="reminders-empty-text">Belum ada pengingat.<br>Tekan "+" untuk menambahkan.</div>' +
                        '</div>';
                }
                showToast('Pengingat dihapus');
            } else {
                showToast(data.message || 'Gagal menghapus pengingat');
            }
        })
        .catch(function() {
            showToast('Gagal menghubungi server');
        });
}

/* ════════════════════════════════════════════════════════════════
   NEW — SUBSCRIPTION SHEET: pricing tier selection + CTA action
════════════════════════════════════════════════════════════════ */
var selectedPriceTierLabel = '1 Bulan — Rp 59.000';

function selectPriceTier(el) {
    document.querySelectorAll('.price-tier').forEach(function(o) { o.classList.remove('selected'); });
    el.classList.add('selected');
    selectedPriceTierLabel = el.dataset.label;
}

// ACTION LOGIC: tombol Langganan utama → Toast "Coming Soon" (fitur belum
// live di backend, ini placeholder aman yang tidak memicu transaksi nyata).
function handleSubCta() {
    showToast('Coming Soon 🚀');
}
</script>
</body>
</html>