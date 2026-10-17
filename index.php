<?php
session_start();
require_once 'config/db.php';

// Kalau udah login, langsung ke dietary
if (isset($_SESSION['user_id'])) {
    header("Location: pages/dietary.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=375, user-scalable=no">
    <title>NutriKids</title>
</head>
<body>
<script>
    // Cek localStorage — kalau onboarding sudah selesai, langsung ke auth
    // Kalau belum, ke onboarding
    const seen = localStorage.getItem('nutrimoms_onboarding_done');
    if (seen === '1') {
        window.location.replace('auth/welcome.php');
    } else {
        window.location.replace('onboarding.php');
    }
</script>
</body>
</html>