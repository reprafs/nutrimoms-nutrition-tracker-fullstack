<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/db.php';
$db     = getDB();
$userId = $_SESSION['user_id'];

// ── Pastikan active_profile_id selalu terisi ─────────────────────────
// Kalau session belum punya active_profile_id (misal: login lama sebelum
// fitur multi-profile), auto-set ke profile pertama milik user ini.
if (empty($_SESSION['active_profile_id'])) {
    $defaultProfile = $db->prepare(
        "SELECT id FROM profiles WHERE user_id = ? ORDER BY id ASC LIMIT 1"
    );
    $defaultProfile->execute([$userId]);
    $found = $defaultProfile->fetchColumn();
    if ($found) {
        $_SESSION['active_profile_id'] = (int)$found;
    } else {
        // Belum punya profil sama sekali → paksa ke setup
        header("Location: setup.php");
        exit;
    }
}
$activeProfileId = (int)$_SESSION['active_profile_id'];

// ── Data profil aktif (untuk header & bottom sheet) ──────────────────
$activeProfileQuery = $db->prepare(
    "SELECT p.id, p.profile_name, p.photo
     FROM profiles p
     WHERE p.id = ? AND p.user_id = ?
     LIMIT 1"
);
$activeProfileQuery->execute([$activeProfileId, $userId]);
$activeProfile = $activeProfileQuery->fetch();

// Fallback keamanan: kalau active_profile_id tidak valid / bukan milik user
if (!$activeProfile) {
    unset($_SESSION['active_profile_id']);
    header("Location: dietary.php");
    exit;
}

// ── Semua profil milik user ini (untuk bottom sheet switcher) ─────────
$allProfilesQuery = $db->prepare(
    "SELECT id, profile_name, photo FROM profiles WHERE user_id = ? ORDER BY id ASC"
);
$allProfilesQuery->execute([$userId]);
$allProfiles = $allProfilesQuery->fetchAll();

// ── Build tampilan header ─────────────────────────────────────────────
$uploadDir = '../uploads/avatars/';
$photoPath = null;
if (!empty($activeProfile['photo']) && file_exists($uploadDir . $activeProfile['photo'])) {
    $photoPath = $uploadDir . $activeProfile['photo'];
}

$profileName = $activeProfile['profile_name'] ?? 'Anak';
$initial     = strtoupper(substr($profileName, 0, 1));
$dateToday   = date('l, d M Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="manifest" href="/nutrimoms/manifest.json">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=375, user-scalable=no">
    <title>Dietary – NutriMoms</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <style>
/* ──────────────────────────────────────
   RESET & TOKENS
────────────────────────────────────── */
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
:root {
    --g:      #5A9E6A;
    --g-lt:   #EBF4ED;
    --g-mid:  #C8E0CC;
    --g-dk:   #3D7A4E;

    --ink:    #1A1D1F;
    --ink-2:  #444950;
    --ink-3:  #9098A1;

    --bg:     #EAF1E8;
    --bg-2:   #EDEAE4;
    --white:  #FFFFFF;

    --r:      18px;
    --r-sm:   12px;
    --sh:     0 2px 14px rgba(0,0,0,0.07);
    --sh-up:  0 8px 32px rgba(0,0,0,0.13);
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

/* ──────────────────────────────────────
   HEADER — FIX #1: reduced top padding
────────────────────────────────────── */
.header {
    position:fixed; top:0; left:50%; transform:translateX(-50%);
    width:375px; z-index:50;
    background:var(--bg);
    /* FIX #1: tighter top padding — no env() gap waste */
    padding:16px 20px 14px;
    border-bottom:1px solid var(--bg-2);
}
.header-row { display:flex; align-items:center; justify-content:space-between; }
.profile-wrap { display:flex; align-items:center; gap:11px; }
.avatar {
    width:46px; height:46px; border-radius:50%;
    background:var(--g); color:var(--white);
    font-weight:700; font-size:16px;
    display:flex; align-items:center; justify-content:center;
    overflow:hidden; flex-shrink:0;
}
.avatar img { width:100%; height:100%; object-fit:cover; }
.greeting-text { font-size:18px; font-weight:700; }
.greeting-date { font-size:12px; color:var(--ink-3); margin-top:2px; font-weight:500; }
.bell-btn {
    width:38px; height:38px; border-radius:11px;
    background:var(--white); border:none;
    display:flex; align-items:center; justify-content:center;
    font-size:16px; cursor:pointer; box-shadow:var(--sh);
    position:relative; padding:0;
}
.bell-icon-img { width:19px; height:19px; object-fit:contain; display:block; }
.notif-badge {
    position:absolute;
    top:-2px; right:-2px;
    min-width:9px; height:9px;
    background:#E0524F;
    border-radius:50%;
    border:1.5px solid var(--white);
}

/* ──────────────────────────────────────
   PAGE CONTENT — adjusted for tighter header
────────────────────────────────────── */
.content { padding-top:96px; padding-bottom:96px; }

/* ──────────────────────────────────────
   WEEK SELECTOR
────────────────────────────────────── */
.week-section { padding:0 20px 14px; }
.week-label   { font-size:12px; font-weight:600; color:var(--ink-3); letter-spacing:0.04em; text-transform:uppercase; margin-bottom:10px; }
.week-row     { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; }
.day-cell {
    display:flex; flex-direction:column; align-items:center; gap:4px;
    cursor:pointer; -webkit-tap-highlight-color:transparent;
}
.day-lbl    { font-size:9px; font-weight:700; color:var(--ink-3); letter-spacing:0.03em; }
.day-circle {
    width:33px; height:33px; border-radius:50%;
    background:var(--bg-2);
    display:flex; align-items:center; justify-content:center;
    font-size:12px; font-weight:700; color:var(--ink-2);
    transition:all 0.15s;
}
.day-cell.done     .day-circle { background:var(--g-lt); color:var(--g); }
.day-cell.selected .day-circle { background:var(--g); color:#fff; box-shadow:0 2px 8px rgba(90,158,106,0.38); }
.day-cell.today    .day-circle { border:2px solid var(--g); }
.day-cell.today.selected .day-circle { border:none; }
.day-cell.future .day-circle { opacity:0.45; }
.day-cell.future .day-lbl    { opacity:0.45; }
.day-cell.selected .day-lbl { color:var(--g); }

/* ──────────────────────────────────────
   SECTION HEADING
────────────────────────────────────── */
.sec-head {
    font-size:10px; font-weight:700; color:var(--ink-3);
    text-transform:uppercase; letter-spacing:0.08em;
    padding:0 20px 9px;
}

/* ──────────────────────────────────────
   NUTRITION CARD
────────────────────────────────────── */
.nutri-section { padding:0 20px 16px; }
.nutri-card {
    background:var(--white); border-radius:var(--r);
    border:1px solid var(--bg-2);
    padding:15px 12px; box-shadow:var(--sh);
}
.nutri-row { display:grid; grid-template-columns:repeat(4,1fr); gap:0; }
.nutri-col { text-align:center; padding:0 4px; position:relative; }
.nutri-col + .nutri-col::before {
    content:''; position:absolute; left:0; top:18%; height:64%;
    width:1px; background:var(--bg-2);
}
.nutri-lbl { font-size:10px; font-weight:600; color:var(--ink-3); margin-bottom:5px; letter-spacing:0.02em; }
.nutri-val { font-size:17px; font-weight:700; color:var(--ink); letter-spacing:-0.01em; }
.nutri-col:first-child .nutri-val { color:var(--g); }
.nutri-unit { font-size:9px; color:var(--ink-3); margin-top:2px; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; }

/* ──────────────────────────────────────
   ADD FOOD BUTTON
────────────────────────────────────── */
.add-section { padding:0 20px 14px; }
.add-food-btn {
    width:100%; height:48px; border-radius:var(--r-sm);
    background:var(--g); color:var(--white); border:none;
    font-family:'DM Sans',sans-serif; font-size:14px; font-weight:700;
    display:flex; align-items:center; justify-content:center; gap:7px;
    cursor:pointer; box-shadow:0 3px 14px rgba(90,158,106,0.3);
    -webkit-tap-highlight-color:transparent;
    transition:opacity 0.12s;
}
.add-food-btn:active { opacity:0.85; }
.add-food-btn .plus { font-size:20px; line-height:1; }

/* ──────────────────────────────────────
   MEAL CARDS — FIX #3: PNG icons
────────────────────────────────────── */
.meals-section { padding:0 20px; }
.meal-cards    { display:flex; flex-direction:column; gap:9px; }

.meal-card {
    background:var(--white); border-radius:var(--r);
    border:1px solid var(--bg-2); overflow:hidden;
    box-shadow:var(--sh);
}
.meal-card-head {
    display:flex; align-items:center; gap:12px;
    padding:13px 15px;
    cursor:pointer; -webkit-tap-highlight-color:transparent;
    user-select:none;
}
.meal-icon {
    width:36px; height:36px; border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0;
}
/* FIX #3: PNG icon sizing inside meal-icon — rigid dimensions, no flex compression */
.meal-icon img {
    width:90px; height:90px;
    object-fit:contain;
    flex-shrink:0;
    display:block;
}
.meal-head-info  { flex:1; min-width:0; }
.meal-head-title { font-size:13px; font-weight:700; }
.meal-head-sub   { font-size:11px; color:var(--ink-3); margin-top:2px; }

.meal-head-macros {
    display:flex; align-items:center; gap:9px; flex-shrink:0;
}
.meal-head-macros .mh-cell {
    font-size:11px; font-weight:700; color:var(--ink-2);
    min-width:38px; text-align:right;
    display:flex; flex-direction:column; align-items:flex-end; line-height:1.25;
}
.meal-head-macros .mh-cell .mh-unit { font-size:9px; font-weight:600; color:var(--ink-3); }
.meal-head-macros .mh-cell.kcal { color:var(--g); min-width:46px; }

.meal-chevron    { font-size:11px; color:var(--ink-3); transition:transform 0.2s; flex-shrink:0; }
.meal-chevron.open { transform:rotate(90deg); }

.meal-body { overflow:hidden; max-height:0; transition:max-height 0.26s ease; }
.meal-body.open { max-height:800px; }
.meal-body-inner { border-top:1px solid var(--bg-2); padding:2px 15px 12px; }

.meal-empty-row {
    padding:11px 0 4px;
    font-size:12px; color:var(--ink-3); font-style:italic;
}

.meal-food-row {
    display:flex; align-items:center; gap:8px;
    padding:9px 0;
}
.meal-food-name {
    flex:1; min-width:0;
    font-size:12.5px; font-weight:500; color:var(--ink-2);
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    display:flex; align-items:baseline; gap:5px;
}
.meal-food-name .mf-meta { font-size:10.5px; color:var(--ink-3); font-weight:500; flex-shrink:0; }

.meal-food-macros {
    display:flex; align-items:center; gap:9px; flex-shrink:0;
}
.meal-food-macros .mf-cell {
    font-size:12px; font-weight:600; color:var(--ink-2);
    min-width:38px; text-align:right;
}
.meal-food-macros .mf-cell.kcal { color:var(--ink); font-weight:700; min-width:46px; }

.manage-meal-btn {
    width:100%; margin-top:6px;
    border:none; background:transparent;
    color:var(--g); font-family:'DM Sans',sans-serif;
    font-size:12px; font-weight:700;
    padding:9px 0 2px; cursor:pointer;
    display:flex; align-items:center; justify-content:center; gap:6px;
    -webkit-tap-highlight-color:transparent;
    border-top:1px solid var(--bg-2);
}
.manage-meal-btn .gear { width:13px; height:13px; object-fit:contain; }

/* ──────────────────────────────────────
   LOCKED MEAL CARD — Minum Air (subscription gate)
────────────────────────────────────── */
.meal-card-locked { position:relative; opacity:0.55; cursor:pointer;margin-bottom:10px }
.meal-card-locked .meal-card-head { pointer-events:none; }
.meal-lock-badge {
    position:absolute; top:50%; right:15px; transform:translateY(-50%);
    width:22px; height:22px; object-fit:contain;
}

/* ──────────────────────────────────────
   SUBSCRIPTION SHEET
────────────────────────────────────── */
.sub-popup-inner { padding:0 20px 22px; max-height:88vh; overflow-y:auto; }
.sub-popup-inner .handle { width:44px; height:5px; background:var(--bg-2); border-radius:99px; margin:10px auto 14px; }
.sub-popup-inner .sheet-title { font-size:19px; font-weight:800; color:var(--ink); text-align:center; }
.sub-popup-inner .sheet-sub { font-size:12.5px; color:var(--ink-3); text-align:center; margin-top:4px; margin-bottom:18px; }

.sub-benefit-row { display:flex; align-items:center; gap:10px; padding:7px 0; }
.sub-benefit-check {
    width:20px; height:20px; border-radius:50%;
    background:var(--g); color:#fff; font-size:11px; font-weight:700;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.sub-benefit-text { font-size:13px; font-weight:600; color:var(--ink-2); }

.price-tier-list { display:flex; flex-direction:column; gap:10px; margin-top:18px; }
.price-tier {
    position:relative; display:flex; align-items:center; gap:12px;
    padding:14px; border:1.5px solid var(--bg-2); border-radius:14px;
    cursor:pointer; transition:border-color 0.15s, background 0.15s;
}
.price-tier.selected { border-color:var(--g); background:rgba(90,158,106,0.06); }
.price-tier-tag {
    position:absolute; top:-9px; left:14px;
    background:#E8945A; color:#fff; font-size:10px; font-weight:700;
    padding:2px 8px; border-radius:99px;
}
.price-tier-radio {
    width:20px; height:20px; border-radius:50%; border:1.5px solid var(--bg-2);
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.price-tier.selected .price-tier-radio { border-color:var(--g); }
.price-tier-radio-dot { width:10px; height:10px; border-radius:50%; background:transparent; }
.price-tier.selected .price-tier-radio-dot { background:var(--g); }
.price-tier-info { flex:1; }
.price-tier-duration { font-size:13.5px; font-weight:700; color:var(--ink); }
.price-tier-amount { font-size:12px; color:var(--ink-3); margin-top:2px; }

.sub-cta-btn {
    width:100%; margin-top:20px; padding:15px 0; border:none; border-radius:14px;
    background:var(--g); color:#fff; font-family:'DM Sans',sans-serif;
    font-size:14.5px; font-weight:700; cursor:pointer;
}
.sub-cta-btn.ghost {
    margin-top:8px; background:transparent; color:var(--ink-3);
    font-size:13px; font-weight:600;
}

/* ──────────────────────────────────────
   NAVBAR
────────────────────────────────────── */
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

/* ──────────────────────────────────────
   OVERLAY BASE
────────────────────────────────────── */
.overlay {
    position:fixed; inset:0;
    background:rgba(0,0,0,0.42);
    z-index:100; display:none;
}
.overlay.center { align-items:center; justify-content:center; }

.sheet {
    position:absolute; left:0; right:0; bottom:0;
    background:var(--white);
    border-radius:22px 22px 0 0;
    display:flex; flex-direction:column;
}
.sheet-handle-row { padding:10px 0 2px; flex-shrink:0; }
.sheet-handle { width:44px; height:5px; background:var(--bg-2); border-radius:99px; margin:0 auto; }

/* ──────────────────────────────────────
   ADD FOOD SHEET
────────────────────────────────────── */
.sheet#addSheet { height:94vh; max-height:94vh; }
.add-sheet-fixed {
    flex-shrink:0; padding:0 18px 12px;
    border-bottom:1px solid var(--bg-2);
}
.add-sheet-title-row {
    display:flex; align-items:center; gap:10px; margin-bottom:12px;
}
.back-btn-round {
    width:34px; height:34px; border-radius:50%;
    background:var(--bg); border:1px solid var(--bg-2);
    display:flex; align-items:center; justify-content:center;
    font-size:15px; color:var(--ink-2); cursor:pointer;
    flex-shrink:0; -webkit-tap-highlight-color:transparent;
}
.add-sheet-title { font-size:18px; font-weight:700; }

.search-wrap {
    display:flex; align-items:center; gap:8px;
    background:var(--bg); border-radius:var(--r-sm);
    padding:0 13px; height:44px; margin-bottom:10px;
    border:1px solid var(--bg-2);
}
.search-wrap input {
    flex:1; border:none; outline:none; background:transparent;
    font-size:14px; font-family:'DM Sans',sans-serif; color:var(--ink);
}
.search-clear { color:var(--ink-3); cursor:pointer; font-size:13px; }

.chips-row { display:flex; gap:7px; overflow-x:auto; scrollbar-width:none; }
.chips-row::-webkit-scrollbar { display:none; }
.chip {
    border:1.5px solid var(--bg-2); background:var(--white);
    padding:6px 14px; border-radius:99px;
    font-family:'DM Sans',sans-serif; font-size:12px; font-weight:600;
    color:var(--ink-2); white-space:nowrap; cursor:pointer;
    -webkit-tap-highlight-color:transparent; flex-shrink:0;
}
.chip.active { background:var(--g); border-color:var(--g); color:#fff; }

.add-sheet-scroll {
    overflow-y:auto; flex:1; min-height:0;
    -webkit-overflow-scrolling:touch; padding:14px 18px 90px;
}

.food-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.food-card {
    background:var(--white); border-radius:14px;
    border:1px solid var(--bg-2); overflow:hidden;
    cursor:pointer; -webkit-tap-highlight-color:transparent;
    box-shadow:var(--sh);
}
.food-card-img  { width:100%; height:86px; object-fit:cover; background:var(--bg-2); }
.food-card-body { padding:10px 11px 12px; }
.food-card-name { font-size:13px; font-weight:700; line-height:1.3; }
.food-card-srv  { font-size:11px; color:var(--ink-3); margin-top:2px; }
.food-card-kcal { font-size:12px; font-weight:700; color:var(--g); margin-top:7px; }

/* ──────────────────────────────────────
   DRAFT FLOATING BAR
────────────────────────────────────── */
.draft-float-bar {
    position:absolute; left:50%;
    transform:translateX(-50%);
    bottom:20px;
    width:calc(100% - 52px);
    max-width:290px;
    background:var(--g); border-radius:99px;
    padding:12px 20px;
    box-shadow:0 6px 24px rgba(90,158,106,0.42);
    display:none; align-items:center; justify-content:space-between;
    cursor:pointer; -webkit-tap-highlight-color:transparent;
    z-index:60;
    transition:opacity 0.16s, transform 0.16s;
}
.draft-float-bar.show { display:flex; }
.draft-float-bar.hidden-by-modal { opacity:0; transform:translateX(-50%) translateY(12px); pointer-events:none; }
.dfb-left  { font-size:13px; font-weight:700; color:#fff; }
.dfb-right { font-size:12px; font-weight:700; color:#fff; display:flex; align-items:center; gap:4px; }

/* ──────────────────────────────────────
   FOOD DETAIL SHEET
────────────────────────────────────── */
.detail-scroll { overflow-y:auto; flex:1; -webkit-overflow-scrolling:touch; padding:0 18px 24px; }
.detail-img { width:100%; height:170px; object-fit:cover; border-radius:16px; margin-bottom:14px; }
.detail-name { font-size:21px; font-weight:700; margin-bottom:14px; }

.macro-row-4 { display:grid; grid-template-columns:repeat(4,1fr); gap:7px; margin-bottom:18px; }
.mac-pill {
    background:var(--bg); border-radius:10px;
    padding:10px 6px; text-align:center; border:1px solid var(--bg-2);
}
.mac-pill .ml { font-size:9px; color:var(--ink-3); font-weight:600; margin-bottom:4px; }
.mac-pill .mv { font-size:13px; font-weight:700; color:var(--ink); }

.toggle-bar {
    display:flex; background:var(--bg); border-radius:11px;
    padding:3px; gap:3px; margin-bottom:13px; border:1px solid var(--bg-2);
}
.toggle-bar button {
    flex:1; height:36px; border:none; border-radius:9px;
    font-family:'DM Sans',sans-serif; font-size:13px; font-weight:600;
    cursor:pointer; background:transparent; color:var(--ink-2); transition:all 0.14s;
}
.toggle-bar button.active { background:var(--white); color:var(--g); box-shadow:0 1px 5px rgba(0,0,0,0.09); font-weight:700; }
.toggle-bar.gram-only .toggle-piece { display:none; }

.qty-field {
    width:100%; height:48px; border:1.5px solid var(--bg-2); outline:none;
    background:var(--bg); border-radius:var(--r-sm);
    padding:0 15px; font-size:16px; font-weight:700;
    font-family:'DM Sans',sans-serif; color:var(--ink); margin-bottom:5px;
}
.qty-field:focus { border-color:var(--g); }
.qty-hint { font-size:11px; color:var(--ink-3); text-align:right; margin-bottom:18px; }

.cta-btn {
    width:100%; height:50px; border-radius:var(--r-sm);
    border:none; cursor:pointer;
    font-family:'DM Sans',sans-serif; font-size:14px; font-weight:700;
    background:var(--g); color:#fff;
    box-shadow:0 3px 14px rgba(90,158,106,0.28);
    transition:opacity 0.12s; -webkit-tap-highlight-color:transparent;
}
.cta-btn:active   { opacity:0.85; }
.cta-btn:disabled { background:var(--bg-2); color:var(--ink-3); box-shadow:none; cursor:default; }
.cta-btn.outline  {
    background:transparent; color:var(--g);
    border:1.5px solid var(--g); box-shadow:none;
}

/* ──────────────────────────────────────
   DRAFT SHEET
────────────────────────────────────── */
.sheet#draftSheet { height:88vh; max-height:88vh; }
.draft-head {
    padding:0 18px 12px; flex-shrink:0; border-bottom:1px solid var(--bg-2);
    position:sticky; top:0; background:var(--white); z-index:5;
}
.draft-head-row { display:flex; align-items:center; gap:10px; }
.draft-head-title { font-size:18px; font-weight:700; }
.draft-count { font-size:12px; color:var(--ink-3); margin-top:2px; }

.draft-scroll { overflow-y:auto; flex:1; min-height:0; -webkit-overflow-scrolling:touch; }
.draft-scroll-inner { padding:0 18px 14px; }

.draft-item-row {
    display:flex; align-items:flex-start; gap:11px;
    padding:12px 0; border-bottom:1px solid var(--bg-2);
}
.draft-item-row:last-child { border-bottom:none; }
.draft-item-img { width:48px; height:48px; border-radius:11px; object-fit:cover; flex-shrink:0; background:var(--bg-2); }
.draft-item-info { flex:1; min-width:0; }
.draft-item-name { font-size:13px; font-weight:700; }
.draft-item-kcal { font-size:11px; color:var(--ink-3); margin-top:2px; }
.draft-stepper   { display:flex; align-items:center; gap:8px; margin-top:8px; }
.step-btn-sm {
    width:27px; height:27px; border-radius:50%;
    background:var(--bg-2); border:none; cursor:pointer;
    font-weight:700; font-size:14px;
    display:flex; align-items:center; justify-content:center;
    -webkit-tap-highlight-color:transparent;
}
.step-val      { font-size:12px; font-weight:700; min-width:52px; text-align:center; color:var(--ink); }
.gram-input-sm {
    width:60px; height:28px; border:1.5px solid var(--bg-2);
    background:var(--bg); border-radius:7px;
    text-align:center; font-size:12px; font-family:'DM Sans',sans-serif;
}
.gram-unit     { font-size:11px; color:var(--ink-3); font-weight:600; }
.draft-del-btn { background:none; border:none; color:var(--ink-3); font-size:11px; font-weight:700; cursor:pointer; margin-left:auto; }

/* FIX #5: Meal pick with icons — 2x2 grid */
.meal-pick-section {
    padding:14px 18px; border-top:1px solid var(--bg-2); flex-shrink:0;
    position:sticky; bottom:0; background:var(--white); z-index:5;
}
.meal-pick-label { font-size:12px; font-weight:700; color:var(--ink-2); margin-bottom:9px; }
.meal-pick-row {
    display:grid; grid-template-columns:repeat(2,1fr); grid-template-rows:repeat(2,1fr);
    gap:7px; margin-bottom:14px;
}
.meal-pick-btn {
    height:42px; border:1.5px solid var(--bg-2); border-radius:9px; background:var(--white);
    font-family:'DM Sans',sans-serif; font-size:12.5px; font-weight:700;
    cursor:pointer; color:var(--ink-2); -webkit-tap-highlight-color:transparent;
    
    display:flex; 
    align-items:center; 
    justify-content: flex-start; /* DIUBAH dari center ke flex-start agar ikon dan teks merapat ke kiri */
    padding-left: 38px;          /* Mengatur geser kirinya dari sini. Naikkan angkanya (misal 34px atau 36px) jika ingin lebih ke kanan, atau turunkan jika kurang ke kiri */
    gap:8px; 
}
.meal-pick-btn.active { background:var(--g); border-color:var(--g); color:#fff; }
/* FIX #5: icon sizing inside meal pick buttons — rigid, no flex compression */
/* FIX: Naikkan ukuran agar objek di dalam PNG kelihatan, tapi batasi agar tidak mendorong teks */
.meal-pick-btn img {
    width: 36px;   
    height: 36px;  
    object-fit:contain;
    flex-shrink:0;
    display:block;
    margin-right: -10px; /* Menghilangkan efek whitespace transparan bawaan gambar agar teks merapat */
}
.meal-pick-btn.active img { filter: brightness(0) invert(1); }

.draft-save-wrap { display:flex; gap:9px; }
.draft-total-row {
    display:flex; gap:6px; flex-wrap:wrap;
    padding:11px 18px 0; flex-shrink:0;
}
.dtotal-pill {
    background:var(--g-lt); border-radius:8px; padding:6px 11px;
    font-size:11px; color:var(--g); font-weight:700;
}
.draft-empty-state { text-align:center; color:var(--ink-3); font-size:13px; padding:28px 0; }

/* ──────────────────────────────────────
   MANAGE MEAL — BOTTOM SHEET 90VH
────────────────────────────────────── */
.manage-center-modal {
    position:absolute; left:0; right:0; bottom:0;
    background:var(--white);
    border-radius:24px 24px 0 0;
    height:90vh; max-height:90vh;
    display:flex; flex-direction:column;
    box-shadow:var(--sh-up);
    overflow:hidden;
    animation:slideUpSheet 0.22s ease;
}

/* Drag handle row — X kiri, title tengah, spacer kanan */
.manage-drag-handle-row {
    display:flex; align-items:center;
    padding:14px 16px 12px; flex-shrink:0;
    border-bottom:1px solid var(--bg-2);
    gap:10px;
}
.manage-drag-handle { display:none; }
.manage-modal-close {
    width:30px; height:30px; border-radius:50%;
    background:var(--bg); border:none;
    font-size:13px; cursor:pointer; color:var(--ink-3);
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0;
}
.manage-modal-title {
    flex:1; text-align:center;
    font-size:16px; font-weight:700; color:var(--ink);
}
.manage-drag-handle-spacer {
    width:30px; flex-shrink:0;
}

/* Sticky summary bar */
.manage-modal-head {
    display:flex; align-items:stretch;
    padding:0; flex-shrink:0;
    border-bottom:1px solid var(--bg-2);
    background:var(--white);
}
.manage-modal-title-wrap { display:none; }
.manage-modal-sub        { display:none; }
.manage-summary-pill {
    flex:1; display:flex; flex-direction:column; align-items:center;
    justify-content:center; padding:10px 4px; gap:2px;
    border-right:1px solid var(--bg-2);
    min-width:0;
}
.manage-summary-pill:last-child { border-right:none; }
.manage-summary-pill-val {
    font-size:15px; font-weight:800; color:var(--ink);
    letter-spacing:-0.01em; white-space:nowrap;
}
.manage-summary-pill:first-child .manage-summary-pill-val { color:var(--g); }
.manage-summary-pill-lbl {
    font-size:9px; font-weight:600; color:var(--ink-3);
    text-transform:uppercase; letter-spacing:0.05em;
}

/* hide old summary spans — replaced by pills */
.manage-modal-summary  { display:none; }
.manage-summary-kcal   { display:none; }
.manage-summary-macro  { display:none; }

/* Scrollable list */
.manage-modal-scroll {
    overflow-y:auto; flex:1; min-height:0;
    -webkit-overflow-scrolling:touch;
    padding:8px 18px 4px;
}

/* Item row — compact */
.manage-item-row {
    display:flex; align-items:center; gap:12px;
    padding:11px 0;
    border-bottom:1px solid var(--bg-2);
}
.manage-item-row:last-child { border-bottom:none; }
.manage-item-img {
    width:52px; height:52px; border-radius:14px;
    object-fit:contain; flex-shrink:0;
    background:var(--bg); border:1px solid var(--bg-2);
}
.manage-item-info { flex:1; min-width:0; }
.manage-item-name {
    font-size:13px; font-weight:700; color:var(--ink);
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.manage-item-kcal {
    font-size:11px; color:var(--ink-3); font-weight:500; margin-top:1px;
}

/* Capsule stepper — kanan, sejajar center */
.manage-stepper {
    display:inline-flex; align-items:center; flex-shrink:0;
    background:var(--bg); border:1px solid var(--bg-2);
    border-radius:99px; overflow:hidden;
}
.manage-step-btn {
    width:30px; height:28px; border-radius:0;
    background:transparent; border:none; cursor:pointer;
    font-weight:700; font-size:16px; color:var(--ink-2);
    display:flex; align-items:center; justify-content:center;
    -webkit-tap-highlight-color:transparent; flex-shrink:0;
    transition:background 0.1s;
}
.manage-step-btn:active { background:var(--bg-2); }
.manage-step-val {
    font-size:13px; font-weight:700; color:var(--ink);
    min-width:28px; text-align:center;
    transition:transform 0.15s;
}

/* Gram input capsule — kanan, sejajar center */
.manage-gram-wrap {
    display:inline-flex; align-items:center; gap:4px; flex-shrink:0;
    background:var(--bg); border:1px solid var(--bg-2);
    border-radius:99px; padding:0 12px;
    height:28px;
}
.manage-gram-input {
    width:44px; border:none; background:transparent;
    text-align:center; font-size:13px; font-weight:700;
    font-family:'DM Sans',sans-serif; color:var(--ink);
    outline:none;
}
.manage-gram-unit { font-size:11px; color:var(--ink-3); font-weight:600; }

/* Trash icon button */
.manage-del-btn {
    background:none; border:none; cursor:pointer;
    padding:6px; flex-shrink:0; color:#C94F4F;
    display:flex; align-items:center; justify-content:center;
    border-radius:8px; -webkit-tap-highlight-color:transparent;
}
.manage-del-btn:active { background:#FBEAEA; }
.manage-del-btn svg { width:17px; height:17px; }

/* Quick Add card */
.manage-quick-add {
    width:100%; margin-top:12px; margin-bottom:4px;
    padding:14px 16px;
    border:1.5px dashed var(--g-mid); border-radius:14px;
    background:var(--g-lt);
    color:var(--g); font-family:'DM Sans',sans-serif;
    font-size:13px; font-weight:700;
    display:flex; align-items:center; justify-content:center; gap:8px;
    cursor:pointer; -webkit-tap-highlight-color:transparent;
    transition:background 0.12s;
}
.manage-quick-add:active { background:var(--g-mid); }
.manage-quick-add-icon { font-size:18px; line-height:1; }

/* Sticky footer */
.manage-modal-footer {
    flex-shrink:0; padding:12px 20px 20px;
    border-top:1px solid var(--bg-2);
    display:flex; gap:9px; background:var(--white);
}

/* ──────────────────────────────────────
   NOTIFICATION CENTER
────────────────────────────────────── */
.notif-center-modal {
    background:var(--white);
    border-radius:20px;
    width:calc(375px - 40px);
    max-width:335px;
    height:72vh;
    max-height:72vh;
    display:flex;
    flex-direction:column;
    box-shadow:var(--sh-up);
    overflow:hidden;
}
.notif-modal-head {
    display:flex; align-items:center; gap:10px;
    padding:15px 16px 11px; flex-shrink:0;
    border-bottom:1px solid var(--bg-2);
}
.notif-modal-close {
    width:28px; height:28px; border-radius:50%;
    background:var(--bg-2); border:none;
    font-size:12px; cursor:pointer; color:var(--ink-3);
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0;
}
.notif-modal-title-wrap { flex:1; min-width:0; }
.notif-modal-title { font-size:15px; font-weight:700; }
.notif-modal-sub   { font-size:10.5px; color:var(--ink-3); margin-top:1px; }

/* Tombol "Tandai dibaca" — ringan, bukan elemen dominan */
.notif-mark-all-btn {
    background:none; border:none; color:var(--ink-3);
    font-size:10.5px; font-weight:600; cursor:pointer;
    flex-shrink:0; white-space:nowrap;
    padding:4px 2px;
    text-decoration:underline; text-underline-offset:2px;
    text-decoration-color:var(--bg-2);
}
.notif-mark-all-btn:active { color:var(--g); }

.notif-modal-scroll {
    overflow-y:auto; flex:1; min-height:0;
    -webkit-overflow-scrolling:touch;
    padding:6px 12px 10px;
}

/* ── Card: tinggi TETAP (fixed), memanjang ke samping ──────
   Row: [icon] [judul + preview 1-baris] [waktu] [trash]
   Trash selalu kelihatan di kanan, tidak bergantung expand. */
.notif-item {
    display:flex; align-items:flex-start; gap:8px;
    padding:10px 8px;
    border-radius:13px;
    -webkit-tap-highlight-color:transparent;
    transition:background 0.15s;
}
.notif-item:active { background:var(--bg); }
.notif-item + .notif-item { margin-top:2px; }
.notif-item.is-unread { background:#F7FBF8; }

.notif-item-icon {
    width:32px; height:32px; border-radius:10px;
    flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:14px;
}
.notif-item-icon.reminder { background:#FDF0E3; }
.notif-item-icon.insight_weekly { background:#EAF2FF; }
.notif-item-icon.achievement { background:#FBEFE0; }

/* Area tengah — yang ditekan untuk expand/collapse */
.notif-item-body {
    flex:1; min-width:0; cursor:pointer;
}
.notif-item-title {
    font-size:12.5px; font-weight:700; color:var(--ink);
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.notif-item.is-read .notif-item-title { font-weight:600; color:var(--ink-2); }

/* Preview 1 baris, dipotong "..." — selalu segini, tidak pernah melar */
.notif-item-text {
    font-size:11px; color:var(--ink-3); margin-top:2px;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}

.notif-item-time {
    font-size:9.5px; color:var(--ink-3); font-weight:600; margin-top:3px;
}

/* Kolom kanan: dot unread di atas trash icon */
.notif-item-side {
    display:flex; flex-direction:column; align-items:center; gap:6px;
    flex-shrink:0; margin-left:2px;
}
.notif-item-unread-dot {
    width:6px; height:6px; border-radius:50%;
    background:var(--g); flex-shrink:0;
}
.notif-item-unread-dot.hidden { visibility:hidden; }
.notif-item-delete-btn {
    width:26px; height:26px; border-radius:8px;
    background:none; border:none;
    cursor:pointer;
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0; padding:0;
}
.notif-item-delete-btn:active { background:#FBEAEA; }
.notif-item-delete-icon { width:15px; height:15px; object-fit:contain; }

/* ── Expanded detail: muncul sebagai sub-panel DI BAWAH row,
   card induk tetap fixed height — panel ini scroll sendiri
   kalau teksnya panjang, tidak mendorong tinggi card. ───── */
.notif-item-detail {
    max-height:0; overflow:hidden;
    transition:max-height 0.2s ease;
}
.notif-item.expanded .notif-item-detail {
    max-height:90px; overflow-y:auto;
    margin-top:6px; padding:8px 9px;
    background:var(--bg); border-radius:10px;
    -webkit-overflow-scrolling:touch;
}
.notif-item-detail-text {
    font-size:11.5px; color:var(--ink-2); line-height:1.5;
}

.notif-empty-state {
    text-align:center; color:var(--ink-3); font-size:12.5px;
    padding:34px 16px;
}
.notif-empty-icon { font-size:26px; margin-bottom:8px; opacity:0.5; }

.toast {
    position:fixed; top:68px; left:50%; transform:translateX(-50%);
    background:var(--ink); color:#fff;
    padding:11px 18px; border-radius:11px;
    font-size:13px; font-weight:700; display:none; z-index:500;
    white-space:nowrap; box-shadow:var(--sh-up);
}
.toast.ok  { background:var(--g); }
.toast.err { background:#C94F4F; }

/* ──────────────────────────────────────
   PROFILE SWITCHER BOTTOM SHEET
────────────────────────────────────── */
.profile-switcher-overlay {
    position:fixed; inset:0;
    background:rgba(0,0,0,0.42);
    z-index:200; display:none;
    align-items:flex-end; justify-content:center;
}
.profile-switcher-overlay.open { display:flex; }

.profile-switcher-sheet {
    width:375px; background:var(--white);
    border-radius:22px 22px 0 0;
    padding-bottom:env(safe-area-inset-bottom, 0px);
    max-height:80vh; display:flex; flex-direction:column;
    animation: slideUpSheet 0.22s ease;
}
@keyframes slideUpSheet {
    from { transform:translateY(100%); opacity:0; }
    to   { transform:translateY(0);    opacity:1; }
}

.pswitch-handle-row { padding:10px 0 6px; flex-shrink:0; }
.pswitch-handle { width:44px; height:5px; background:var(--bg-2); border-radius:99px; margin:0 auto; }

.pswitch-title {
    font-size:14px; font-weight:700; color:var(--ink-3);
    text-transform:uppercase; letter-spacing:0.08em;
    padding:0 20px 12px; flex-shrink:0;
}

.pswitch-list {
    overflow-y:auto; flex:1; -webkit-overflow-scrolling:touch;
    padding:0 16px;
}

.pswitch-item {
    display:flex; align-items:center; gap:13px;
    padding:11px 10px; border-radius:14px;
    cursor:pointer; -webkit-tap-highlight-color:transparent;
    transition:background 0.13s;
    margin-bottom:4px;
}
.pswitch-item:active { background:var(--g-lt); }
.pswitch-item.active-profile { background:var(--g-lt); }

.pswitch-avatar {
    width:44px; height:44px; border-radius:50%;
    background:var(--g); color:#fff;
    font-weight:700; font-size:15px;
    display:flex; align-items:center; justify-content:center;
    overflow:hidden; flex-shrink:0;
    border:2px solid transparent;
}
.pswitch-item.active-profile .pswitch-avatar { border-color:var(--g); }
.pswitch-avatar img { width:100%; height:100%; object-fit:cover; }

.pswitch-name {
    flex:1; font-size:14px; font-weight:600; color:var(--ink);
}
.pswitch-item.active-profile .pswitch-name { color:var(--g); font-weight:700; }

.pswitch-check {
    width:20px; height:20px; border-radius:50%;
    background:var(--g); display:none;
    align-items:center; justify-content:center;
    font-size:11px; color:#fff; font-weight:700;
    flex-shrink:0;
}
.pswitch-item.active-profile .pswitch-check { display:flex; }

.pswitch-divider { height:1px; background:var(--bg-2); margin:8px 0; }

.pswitch-add-btn {
    display:flex; align-items:center; gap:13px;
    padding:11px 10px; border-radius:14px;
    cursor:pointer; -webkit-tap-highlight-color:transparent;
    margin-bottom:4px; text-decoration:none;
    transition:background 0.13s;
}
.pswitch-add-btn:active { background:var(--bg); }

.pswitch-add-icon {
    width:44px; height:44px; border-radius:50%;
    background:var(--bg-2); border:2px dashed var(--g-mid);
    display:flex; align-items:center; justify-content:center;
    font-size:20px; color:var(--g); flex-shrink:0;
}
.pswitch-add-label {
    font-size:14px; font-weight:600; color:var(--g);
}

.pswitch-footer {
    padding:12px 16px 16px; flex-shrink:0;
}
.pswitch-close-btn {
    width:100%; height:46px; border:1.5px solid var(--bg-2); border-radius:14px;
    background:var(--white); font-family:'DM Sans',sans-serif;
    font-size:14px; font-weight:700; color:var(--ink-2); cursor:pointer;
    -webkit-tap-highlight-color:transparent;
}
.pswitch-close-btn:active { background:var(--bg); }
    </style>
</head>
<body>
<div class="shell">

<!-- ── HEADER ── -->
<div class="header">
    <div class="header-row">
        <!-- Profile wrap sekarang bisa diklik untuk buka profile switcher -->
        <div class="profile-wrap profile-switch-trigger" onclick="openProfileSwitcher()" style="cursor:pointer;">
            <div class="avatar">
                <?php if ($photoPath): ?>
                    <img src="<?= htmlspecialchars($photoPath) ?>" alt="">
                <?php else: ?>
                    <?= htmlspecialchars($initial) ?>
                <?php endif; ?>
            </div>
            <div>
                <div class="greeting-text" id="greetingText">
                    Halo, <?= htmlspecialchars($profileName) ?>
                    <!-- Chevron kecil sebagai hint "bisa diklik" -->
                    <span style="font-size:11px; color:var(--ink-3); font-weight:500; margin-left:4px;">▾</span>
                </div>
                <div class="greeting-date"><?= $dateToday ?></div>
            </div>
        </div>
        <button class="bell-btn" id="notifBellBtn" onclick="openNotifCenter()">
            <img src="../assets/icon/iconnotifications.PNG" alt="Notifikasi" class="bell-icon-img">
            <span class="notif-badge" id="notifBadge" style="display:none"></span>
        </button>
    </div>
</div>

<!-- ── CONTENT ── -->
<div class="content">

    <!-- Week -->
    <div class="week-section">
        <div class="week-label" id="weekLabel">Hari Ini</div>
        <div class="week-row" id="weekRow"></div>
    </div>

    <!-- Nutrition -->
    <div class="sec-head">Nutrisi Hari Ini</div>
    <div class="nutri-section">
        <div class="nutri-card">
            <div class="nutri-row">
                <div class="nutri-col">
                    <div class="nutri-lbl">Kalori</div>
                    <div class="nutri-val" id="sumCal">-</div>
                    <div class="nutri-unit">kcal</div>
                </div>
                <div class="nutri-col">
                    <div class="nutri-lbl">Protein</div>
                    <div class="nutri-val" id="sumPro">-</div>
                    <div class="nutri-unit">g</div>
                </div>
                <div class="nutri-col">
                    <div class="nutri-lbl">Karbo</div>
                    <div class="nutri-val" id="sumCarb">-</div>
                    <div class="nutri-unit">g</div>
                </div>
                <div class="nutri-col">
                    <div class="nutri-lbl">Lemak</div>
                    <div class="nutri-val" id="sumFat">-</div>
                    <div class="nutri-unit">g</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Food -->
    <div class="add-section">
        <button class="add-food-btn" onclick="openAddSheet()">
            <span class="plus">＋</span> Tambah Makanan
        </button>
    </div>

    <!-- Meals -->
    <div class="sec-head">Makanan Tercatat</div>
    <div class="meals-section">
        <div class="meal-cards" id="mealCards"></div>
    </div>

</div><!-- /content -->

<!-- Navbar -->
<nav class="navbar">
    <a href="dietary.php" class="nav-item active">
        <div class="nav-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <!-- Garpu -->
                <path d="M18 8h1a4 4 0 0 1 0 8h-1M2 2v7a4 4 0 0 0 4 4h4a4 4 0 0 0 4-4V2M6 2v5M10 2v5M6 13v9M10 13v9"/>
            </svg>
        </div>
        <span>Dietary</span>
    </a>
    <a href="report.php" class="nav-item">
        <div class="nav-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/>
            </svg>
        </div>
        <span>Report</span>
    </a>
    <a href="article.php" class="nav-item">
        <div class="nav-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
        </div>
        <span>Artikel</span>
    </a>
    <a href="profile.php" class="nav-item">
        <div class="nav-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
        </div>
        <span>Me</span>
    </a>
</nav>

</div><!-- /shell -->


<!-- ════════════════════════════════════
     OVERLAY: ADD FOOD SHEET
════════════════════════════════════ -->
<div class="overlay" id="addOverlay">
    <div class="sheet" id="addSheet">
        <div class="sheet-handle-row"><div class="sheet-handle"></div></div>

        <div class="add-sheet-fixed">
            <div class="add-sheet-title-row">
                <button class="back-btn-round" onclick="closeAddSheet()" aria-label="Kembali">←</button>
                <div class="add-sheet-title">Tambah Makanan</div>
            </div>

            <div class="search-wrap">
                <span style="font-size:14px;color:var(--ink-3)">🔍</span>
                <input type="text" id="addSearch" placeholder="Cari makanan...">
                <span class="search-clear" id="addClearBtn" style="display:none" onclick="clearSearch()">✕</span>
            </div>
            <div class="chips-row" id="addChips">
                <button class="chip active" data-cat="">Semua</button>
                <button class="chip" data-cat="Protein">Protein</button>
                <button class="chip" data-cat="Carbs">Karbo</button>
                <button class="chip" data-cat="Fruits">Buah</button>
                <button class="chip" data-cat="Veggies">Sayur</button>
                <button class="chip" data-cat="Snacks">Cemilan</button>
            </div>
        </div>

        <div class="add-sheet-scroll">
            <div class="food-grid" id="addFoodGrid"></div>
        </div>

        <div class="draft-float-bar" id="draftFloatBar" onclick="openDraftSheet()">
            <div class="dfb-left" id="draftFloatLabel">0 item dalam draft</div>
            <div class="dfb-right">Lihat Draft →</div>
        </div>
    </div>
</div>


<!-- ════════════════════════════════════
     OVERLAY: FOOD DETAIL SHEET
════════════════════════════════════ -->
<div class="overlay" id="detailOverlay">
    <div class="sheet" id="detailSheet">
        <div class="sheet-handle-row"><div class="sheet-handle"></div></div>
        <div class="detail-scroll">
            <img class="detail-img" id="detailImg">
            <div class="detail-name" id="detailName"></div>

            <div class="macro-row-4">
                <div class="mac-pill"><div class="ml">Kalori</div><div class="mv" id="dCal">0</div></div>
                <div class="mac-pill"><div class="ml">Protein</div><div class="mv" id="dPro">0g</div></div>
                <div class="mac-pill"><div class="ml">Karbo</div><div class="mv" id="dCarb">0g</div></div>
                <div class="mac-pill"><div class="ml">Lemak</div><div class="mv" id="dFat">0g</div></div>
            </div>

            <div class="toggle-bar" id="detailToggle">
                <button class="toggle-piece active" data-type="piece">Porsi / Buah</button>
                <button class="toggle-gram"          data-type="gram">Gram</button>
            </div>

            <input class="qty-field" type="number" id="detailQty" value="1" min="1">
            <div class="qty-hint" id="detailQtyHint"></div>

            <button class="cta-btn" id="addToDraftBtn">Tambah ke Draft</button>
        </div>
    </div>
</div>


<!-- ════════════════════════════════════
     OVERLAY: DRAFT SHEET
════════════════════════════════════ -->
<div class="overlay" id="draftOverlay">
    <div class="sheet" id="draftSheet">
        <div class="sheet-handle-row"><div class="sheet-handle"></div></div>

        <div class="draft-head">
            <div class="draft-head-row">
                <button class="back-btn-round" onclick="closeDraftSheet()" aria-label="Kembali">←</button>
                <div>
                    <div class="draft-head-title">Draft</div>
                    <div class="draft-count" id="draftCount">0 item</div>
                </div>
            </div>
        </div>

        <div class="draft-total-row" id="draftTotalRow"></div>

        <div class="draft-scroll" id="draftItemsWrap">
            <div class="draft-scroll-inner" id="draftItemsInner"></div>
        </div>

        <!-- FIX #5: Meal pick buttons with PNG icons -->
        <div class="meal-pick-section">
            <div class="meal-pick-label">Waktu Makan</div>
            <div class="meal-pick-row">
                <button class="meal-pick-btn active" data-meal="pagi">
                    <img src="../assets/icon/iconpagi.PNG" alt="">Pagi
                </button>
                <button class="meal-pick-btn" data-meal="siang">
                    <img src="../assets/icon/iconsiang.PNG" alt="">Siang
                </button>
                <button class="meal-pick-btn" data-meal="malam">
                    <img src="../assets/icon/iconmalam.PNG" alt="">Malam
                </button>
                <button class="meal-pick-btn" data-meal="snack">
                    <img src="../assets/icon/iconsnack.PNG" alt="">Snack
                </button>
            </div>
            <div class="draft-save-wrap">
                <button class="cta-btn outline" style="flex:0 0 auto;width:auto;padding:0 18px" onclick="closeDraftSheet()">Kembali</button>
                <button class="cta-btn" id="saveDraftBtn" style="flex:1">Simpan</button>
            </div>
        </div>
    </div>
</div>


<!-- ════════════════════════════════════
     OVERLAY: SUBSCRIPTION SHEET
════════════════════════════════════ -->
<div class="overlay" id="subOverlay" style="display:none" onclick="closeSubSheet()">
    <div class="sheet sub-popup-inner" onclick="event.stopPropagation()">
        <div class="handle"></div>
        <div class="sheet-title">Pilih Langganan</div>
        <div class="sheet-sub">Buka fitur lengkap untuk nutrisi anak yang lebih optimal</div>

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
                <div class="price-tier-tag" style="background:var(--g)">💰 Hemat</div>
                <div class="price-tier-radio"><div class="price-tier-radio-dot"></div></div>
                <div class="price-tier-info">
                    <div class="price-tier-duration">12 Bulan</div>
                    <div class="price-tier-amount">Rp 259.000</div>
                </div>
            </div>
        </div>

        <button class="sub-cta-btn" onclick="closeSubSheet()">Berlangganan Sekarang</button>
        <button class="sub-cta-btn ghost" onclick="closeSubSheet()">Lanjut Pakai Free</button>
    </div>
</div>


<!-- ════════════════════════════════════
     FIX #4: MANAGE MEAL — FIXED HEIGHT CENTER MODAL
     Temporary state: changes buffered until [Simpan]
════════════════════════════════════ -->
<div class="overlay" id="manageOverlay" style="display:none" onclick="handleManageBackdrop(event)">
    <div class="manage-center-modal" id="manageCenterModal">

        <!-- Top bar: X kiri · title tengah · spacer kanan -->
        <div class="manage-drag-handle-row">
            <button class="manage-modal-close" onclick="cancelManageSheet()">✕</button>
            <div class="manage-modal-title" id="manageTitle">Kelola</div>
            <div class="manage-drag-handle-spacer"></div>
        </div>

        <!-- Sticky summary pills -->
        <div class="manage-modal-head" id="manageSummaryBar">
            <div class="manage-summary-pill">
                <span class="manage-summary-pill-val" id="spKcal">0</span>
                <span class="manage-summary-pill-lbl">Kal</span>
            </div>
            <div class="manage-summary-pill">
                <span class="manage-summary-pill-val" id="spPro">0g</span>
                <span class="manage-summary-pill-lbl">Protein</span>
            </div>
            <div class="manage-summary-pill">
                <span class="manage-summary-pill-val" id="spCarb">0g</span>
                <span class="manage-summary-pill-lbl">Karbo</span>
            </div>
            <div class="manage-summary-pill">
                <span class="manage-summary-pill-val" id="spFat">0g</span>
                <span class="manage-summary-pill-lbl">Lemak</span>
            </div>
        </div>

        <!-- Scrollable list -->
        <div class="manage-modal-scroll" id="manageScroll"></div>

        <!-- Sticky footer -->
        <div class="manage-modal-footer">
            <button class="cta-btn outline" style="flex:0 0 auto;width:auto;padding:0 18px"
                    onclick="cancelManageSheet()">Kembali</button>
            <button class="cta-btn" style="flex:1" onclick="saveManageSheet()">Simpan</button>
        </div>
    </div>
</div>


<!-- ════════════════════════════════════
     NOTIFICATION CENTER
════════════════════════════════════ -->
<div class="overlay center" id="notifOverlay" style="display:none">
    <div class="notif-center-modal">
        <div class="notif-modal-head">
            <button class="notif-modal-close" onclick="closeNotifCenter()">✕</button>
            <div class="notif-modal-title-wrap">
                <div class="notif-modal-title">Notifikasi</div>
                <div class="notif-modal-sub" id="notifSub"></div>
            </div>
            <button class="notif-mark-all-btn" id="notifMarkAllBtn" onclick="markAllNotifRead()">Tandai semua dibaca</button>
        </div>
        <div class="notif-modal-scroll" id="notifScroll"></div>
    </div>
</div>


<!-- ══ PROFILE SWITCHER OVERLAY ══ -->
<div class="profile-switcher-overlay" id="profileSwitcherOverlay" onclick="handleSwitcherBackdrop(event)">
    <div class="profile-switcher-sheet" id="profileSwitcherSheet">
        <div class="pswitch-handle-row">
            <div class="pswitch-handle"></div>
        </div>
        <div class="pswitch-title">Pilih Profil</div>

        <div class="pswitch-list" id="pswitchList">
            <?php foreach ($allProfiles as $prof):
                $isActive  = ((int)$prof['id'] === $activeProfileId);
                $profPhoto = $uploadDir . ($prof['photo'] ?? '');
                $hasPhoto  = !empty($prof['photo']) && file_exists($profPhoto);
                $profInit  = strtoupper(substr($prof['profile_name'], 0, 1));
            ?>
            <div class="pswitch-item <?= $isActive ? 'active-profile' : '' ?>"
                 onclick="switchProfile(<?= (int)$prof['id'] ?>, <?= $isActive ? 'true' : 'false' ?>)">
                <div class="pswitch-avatar">
                    <?php if ($hasPhoto): ?>
                        <img src="<?= htmlspecialchars($profPhoto) ?>" alt="">
                    <?php else: ?>
                        <?= htmlspecialchars($profInit) ?>
                    <?php endif; ?>
                </div>
                <div class="pswitch-name"><?= htmlspecialchars($prof['profile_name']) ?></div>
                <div class="pswitch-check">✓</div>
            </div>
            <?php endforeach; ?>

            <div class="pswitch-divider"></div>

            <a href="add_profile.php" class="pswitch-add-btn">
                <div class="pswitch-add-icon">＋</div>
                <div class="pswitch-add-label">Tambah Profil</div>
            </a>
        </div>

        <div class="pswitch-footer">
            <button class="pswitch-close-btn" onclick="closeProfileSwitcher()">Kembali</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
/* ════════════════════════════════════
   FIX #2: Dynamic time-based greeting (client-side)
════════════════════════════════════ */
(function setGreeting() {
    const userName = <?= json_encode($profileName) ?>;
    const hour = new Date().getHours();
    let greeting;
    if      (hour >= 4  && hour < 11) greeting = 'Selamat Pagi';
    else if (hour >= 11 && hour < 15) greeting = 'Selamat Siang';
    else if (hour >= 15 && hour < 18) greeting = 'Selamat Sore';
    else                              greeting = 'Selamat Malam';
    const el = document.getElementById('greetingText');
    if (el) el.textContent = greeting + ', ' + userName;
})();

/* ════════════════════════════════════
   FIX #3: MEAL CONFIG — PNG icons (no emojis)
════════════════════════════════════ */
const MEAL_CFG = [
    { key:'pagi',  label:'Pagi',  icon:'../assets/icon/iconpagi.PNG'  },
    { key:'siang', label:'Siang', icon:'../assets/icon/iconsiang.PNG' },
    { key:'malam', label:'Malam', icon:'../assets/icon/iconmalam.PNG'  },
    { key:'snack', label:'Snack', icon:'../assets/icon/iconsnack.PNG' }
];
const MEAL_MAP = Object.fromEntries(MEAL_CFG.map(m => [m.key, m]));

/* ════════════════════════════════════
   STATE
════════════════════════════════════ */
let allFoods   = [];
let draft      = [];
let savedItems = [];
let batchMeal  = 'pagi';

let selFood  = null;
let qtyType  = 'piece';

// FIX #4: Manage modal temporary state
let manageKey      = null;
let manageTempItems = [];   // deep copy of items being edited
let manageDeletedIds = [];  // IDs queued for deletion

/* ════════════════════════════════════
   CONSTANTS
════════════════════════════════════ */
const DAY_S = ['Sen','Sel','Rab','Kam','Jum','Sab','Min'];
const DAY_F = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
const MON_F = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

/* ════════════════════════════════════
   DATE
════════════════════════════════════ */
function fmtD(d) {
    return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
}
function mondayIndex(jsDay) { return (jsDay + 6) % 7; }

const TODAY   = new Date(); TODAY.setHours(0,0,0,0);
const TODAY_S = fmtD(TODAY);
let selDate   = TODAY_S;
let loggedDates = [];

function weekArr() {
    const todayMonIdx = mondayIndex(TODAY.getDay());
    const monday = new Date(TODAY); monday.setDate(monday.getDate() - todayMonIdx);
    return Array.from({length:7}, (_,i) => {
        const d = new Date(monday); d.setDate(d.getDate()+i); return fmtD(d);
    });
}
const WEEK = weekArr();

/* ════════════════════════════════════
   DRAFT LOCALSTORAGE
════════════════════════════════════ */
const dk = d => 'nm_draft_' + d;
function loadDraft(d)  { try{ return JSON.parse(localStorage.getItem(dk(d)))||[]; }catch(e){return[];} }
function saveDraftLS() { localStorage.setItem(dk(selDate), JSON.stringify(draft)); }
function clearDraftLS(d){ localStorage.removeItem(dk(d)); }

/* ════════════════════════════════════
   TOAST
════════════════════════════════════ */
let toastT;
function toast(msg, type='ok') {
    const el = document.getElementById('toast');
    el.textContent = msg; el.className = `toast ${type}`;
    el.style.display = 'block';
    clearTimeout(toastT);
    toastT = setTimeout(() => { el.style.display='none'; }, 2500);
}

/* ════════════════════════════════════
   WEEK ROW
════════════════════════════════════ */
function updateWeekLabel() {
    const el = document.getElementById('weekLabel');
    if (selDate === TODAY_S) { el.textContent='Hari Ini'; return; }
    const yest = new Date(TODAY); yest.setDate(yest.getDate()-1);
    if (selDate === fmtD(yest)) { el.textContent='Kemarin'; return; }
    const [y,m,d] = selDate.split('-').map(Number);
    el.textContent = `${DAY_F[mondayIndex(new Date(y,m-1,d).getDay())]}, ${d} ${MON_F[m-1]}`;
}
function renderWeek() {
    const row = document.getElementById('weekRow');
    row.innerHTML = '';
    WEEK.forEach(ds => {
        const [y,m,d] = ds.split('-').map(Number);
        const obj = new Date(y,m-1,d);
        const idx = mondayIndex(obj.getDay());
        let cls = 'day-cell';
        if (ds > TODAY_S)   cls += ' future';
        if (ds === TODAY_S) cls += ' today';
        if (ds === selDate) cls += ' selected';
        if (loggedDates.includes(ds)) cls += ' done';
        const cell = document.createElement('div');
        cell.className = cls;
        cell.innerHTML = `<div class="day-lbl">${DAY_S[idx]}</div>
                          <div class="day-circle">${loggedDates.includes(ds) && ds!==selDate ? '✓' : d}</div>`;
        cell.addEventListener('click', () => selectDate(ds));
        row.appendChild(cell);
    });
}
async function fetchLoggedDates() {
    try {
        const r = await fetch(`../api/dietary_status.php?start=${WEEK[0]}&end=${WEEK[6]}`);
        const j = await r.json();
        if (j.success) loggedDates = j.data;
    } catch(e){}
}
async function selectDate(ds) {
    selDate = ds;
    updateWeekLabel();
    renderWeek();
    closeAllOverlays();
    await loadDay();
}

/* ════════════════════════════════════
   LOAD DAY
════════════════════════════════════ */
async function loadDay() {
    savedItems = await fetchSaved(selDate);
    draft      = loadDraft(selDate);
    renderNutri();
    renderMeals();
    syncDraftFloatBar();
}
async function fetchSaved(date) {
    try {
        const r = await fetch(`../api/dietary_log_items.php?date=${date}`);
        const j = await r.json();
        if (j.success) return j.items;
    } catch(e){}
    return [];
}

/* ════════════════════════════════════
   NUTRITION
════════════════════════════════════ */
function renderNutri() {
    let c=0,p=0,cb=0,f=0;
    savedItems.forEach(i=>{ c+=i.calories; p+=i.protein; cb+=i.carbs; f+=i.fat; });
    const hasData = savedItems.length > 0;
    document.getElementById('sumCal').textContent  = hasData ? c.toFixed(1)  : '-';
    document.getElementById('sumPro').textContent  = hasData ? p.toFixed(1) : '-';
    document.getElementById('sumCarb').textContent = hasData ? cb.toFixed(1) : '-';
    document.getElementById('sumFat').textContent  = hasData ? f.toFixed(1) : '-';
}

/* ════════════════════════════════════
   FIX #3: MEAL CARDS — PNG icon in .meal-icon
════════════════════════════════════ */
const openMeals = new Set();
function renderMeals() {
    const wrap = document.getElementById('mealCards');
    wrap.innerHTML = '';
    MEAL_CFG.forEach(cfg => {
        const items = savedItems.filter(i => (i.meal_type||'pagi') === cfg.key);
        const kcal  = items.reduce((a,i) => a+i.calories, 0);
        const pro   = items.reduce((a,i) => a+i.protein,  0);
        const carb  = items.reduce((a,i) => a+i.carbs,    0);
        const fat   = items.reduce((a,i) => a+i.fat,      0);
        const isOpen = openMeals.has(cfg.key);

        const card = document.createElement('div');
        card.className = 'meal-card';
        card.dataset.meal = cfg.key;

        const headMacros = items.length ? `
            <div class="meal-head-macros">
                <div class="mh-cell kcal">${kcal.toFixed(0)}<span class="mh-unit">kcal</span></div>
                <div class="mh-cell">${pro.toFixed(0)}<span class="mh-unit">P</span></div>
                <div class="mh-cell">${carb.toFixed(0)}<span class="mh-unit">K</span></div>
                <div class="mh-cell">${fat.toFixed(0)}<span class="mh-unit">L</span></div>
            </div>` : '';

        const sub = items.length ? `${items.length} item` : 'Belum ada makanan';

        // FIX #3: use <img> PNG icon instead of emoji
        card.innerHTML = `
            <div class="meal-card-head" onclick="toggleMeal('${cfg.key}')">
                <div class="meal-icon">
                    <img src="${cfg.icon}" alt="${cfg.label}">
                </div>
                <div class="meal-head-info">
                    <div class="meal-head-title">${cfg.label}</div>
                    <div class="meal-head-sub">${sub}</div>
                </div>
                ${headMacros}
                <div class="meal-chevron${isOpen?' open':''}" id="chev-${cfg.key}">›</div>
            </div>
            <div class="meal-body${isOpen?' open':''}" id="body-${cfg.key}">
                <div class="meal-body-inner" id="inner-${cfg.key}"></div>
            </div>
        `;
        wrap.appendChild(card);

        const inner = document.getElementById(`inner-${cfg.key}`);
        if (!items.length) {
            inner.innerHTML = '<div class="meal-empty-row">Belum ada yang dicatat</div>';
        } else {
            items.forEach(item => {
                const qty = item.quantity_type === 'piece'
                    ? `${item.quantity_value} porsi`
                    : `${item.quantity_value} g`;
                const row = document.createElement('div');
                row.className = 'meal-food-row';
                row.innerHTML = `
                    <div class="meal-food-name">
                        <span>${item.food_name}</span>
                        <span class="mf-meta">(${qty})</span>
                    </div>
                    <div class="meal-food-macros">
                        <div class="mf-cell kcal">${item.calories.toFixed(0)}</div>
                        <div class="mf-cell">${item.protein.toFixed(0)}</div>
                        <div class="mf-cell">${item.carbs.toFixed(0)}</div>
                        <div class="mf-cell">${item.fat.toFixed(0)}</div>
                    </div>
                `;
                inner.appendChild(row);
            });
            const manageBtn = document.createElement('button');
            manageBtn.className = 'manage-meal-btn';
            manageBtn.innerHTML = `<img src="../assets/icon/iconkelolamakana.png" alt="" class="gear"> Kelola Makanan`;
            manageBtn.onclick = (e) => { e.stopPropagation(); openManageSheet(cfg.key); };
            inner.appendChild(manageBtn);
        }
    });

    /* Locked card: Minum Air (subscription gate) */
    const waterCard = document.createElement('div');
    waterCard.className = 'meal-card meal-card-locked';
    waterCard.onclick = () => openSubSheet();
    waterCard.innerHTML = `
        <div class="meal-card-head">
            <div class="meal-icon">
                <img src="../assets/icon/iconminumairr.png" alt="Minum Air">
            </div>
            <div class="meal-head-info">
                <div class="meal-head-title">Minum Air</div>
                <div class="meal-head-sub">Fitur Premium</div>
            </div>
        </div>
        <img src="../assets/icon/icongembok.png" alt="Terkunci" class="meal-lock-badge">
    `;
    wrap.appendChild(waterCard);
}
function toggleMeal(key) {
    const body = document.getElementById(`body-${key}`);
    const chev = document.getElementById(`chev-${key}`);
    const open = body.classList.toggle('open');
    chev.classList.toggle('open', open);
    if (open) openMeals.add(key); else openMeals.delete(key);
}

/* ════════════════════════════════════
   FIX #4: MANAGE MEAL — FIXED MODAL WITH TEMP STATE
   - No pencil button
   - Inline stepper (piece) / input (gram)
   - Trash icon for deletion
   - Changes buffered → only sent on [Simpan]
   - [Kembali] / X → discard all changes
════════════════════════════════════ */
function _updateManageSummary() {
    const kcal = manageTempItems.reduce((a,i) => a + i.calories, 0);
    const pro  = manageTempItems.reduce((a,i) => a + i.protein,  0);
    const carb = manageTempItems.reduce((a,i) => a + i.carbs,    0);
    const fat  = manageTempItems.reduce((a,i) => a + i.fat,      0);
    const spKcal = document.getElementById('spKcal');
    const spPro  = document.getElementById('spPro');
    const spCarb = document.getElementById('spCarb');
    const spFat  = document.getElementById('spFat');
    if (spKcal) spKcal.textContent = kcal.toFixed(0);
    if (spPro)  spPro.textContent  = pro.toFixed(0)  + 'g';
    if (spCarb) spCarb.textContent = carb.toFixed(0) + 'g';
    if (spFat)  spFat.textContent  = fat.toFixed(0)  + 'g';
}

function openManageSheet(key) {
    manageKey = key;
    const cfg   = MEAL_MAP[key];
    const items = savedItems.filter(i => (i.meal_type||'pagi') === key);

    // Deep copy items into temp state + simpan snapshot original untuk kalkulasi per-unit
    manageTempItems   = items.map(i => ({
        ...i,
        _origQty:  i.quantity_value,
        _origCal:  i.calories,
        _origPro:  i.protein,
        _origCarb: i.carbs,
        _origFat:  i.fat,
    }));
    manageDeletedIds  = [];

    document.getElementById('manageTitle').textContent = `Kelola ${cfg.label}`;
    _updateManageSummary();

    renderManageList();
    document.getElementById('manageOverlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function renderManageList() {
    const scroll = document.getElementById('manageScroll');
    scroll.innerHTML = '';

    if (!manageTempItems.length) {
        scroll.innerHTML = `<div class="draft-empty-state" style="padding:36px 0">
            <div style="font-size:28px;margin-bottom:8px">🍽️</div>
            Belum ada makanan dicatat
        </div>`;
    } else {
        manageTempItems.forEach((item, ti) => {
            const isPiece = item.quantity_type === 'piece';
            const row = document.createElement('div');
            row.className = 'manage-item-row';

            const inlineCtrl = isPiece
                ? `<div class="manage-stepper">
                       <button class="manage-step-btn" onclick="manageTempStep(${ti},-1)">−</button>
                       <span class="manage-step-val" id="msv-${ti}">${item.quantity_value}</span>
                       <button class="manage-step-btn" onclick="manageTempStep(${ti},1)">+</button>
                   </div>`
                : `<div class="manage-gram-wrap">
                       <input class="manage-gram-input" id="mgi-${ti}"
                           type="number" value="${item.quantity_value}" min="1"
                           onchange="manageTempGram(${ti}, this.value)">
                       <span class="manage-gram-unit">g</span>
                   </div>`;

            row.innerHTML = `
                <img class="manage-item-img" src="${item.image}" alt="">
                <div class="manage-item-info">
                    <div class="manage-item-name">${item.food_name}</div>
                    <div class="manage-item-kcal" id="mkcal-${ti}">${item.calories.toFixed(0)} kcal</div>
                </div>
                ${inlineCtrl}
                <button class="manage-del-btn" onclick="manageTempDelete(${ti})" aria-label="Hapus">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6l-1 14H6L5 6"/>
                        <path d="M10 11v6"/><path d="M14 11v6"/>
                        <path d="M9 6V4h6v2"/>
                    </svg>
                </button>
            `;
            scroll.appendChild(row);
        });
    }

    // Quick Add card
    const qaBtn = document.createElement('button');
    qaBtn.className = 'manage-quick-add';
    qaBtn.innerHTML = `<span class="manage-quick-add-icon">＋</span> Tambah makanan`;
    qaBtn.onclick = () => {
        document.getElementById('manageOverlay').style.display = 'none';
        batchMeal = manageKey;
        document.querySelectorAll('.meal-pick-btn').forEach(b => {
            b.classList.toggle('active', b.dataset.meal === manageKey);
        });
        _addSheetFromManage = true;
        openAddSheet();
    };
    scroll.appendChild(qaBtn);
}

/* ── Helper: inject new item from Quick Add into manage temp state ── */
function injectNewItemToManage(food, qty, type) {
    const mul = calcMul(food, qty, type);
    manageTempItems.push({
        id:             null,        // null = brand new, no DB record yet
        is_new:         true,        // flag for saveManageSheet to INSERT, not UPDATE
        food_id:        food.id,
        food_name:      food.food_name,
        image:          food.image,
        base_gram:      food.base_gram,
        piece_gram:     food.piece_gram ?? null,
        quantity_type:  type,
        quantity_value: qty,
        meal_type:      manageKey,
        calories:       parseFloat((food.calories * mul).toFixed(1)),
        protein:        parseFloat((food.protein  * mul).toFixed(1)),
        carbs:          parseFloat((food.carbs    * mul).toFixed(1)),
        fat:            parseFloat((food.fat      * mul).toFixed(1)),
    });
    _updateManageSummary();
    renderManageList();
}

/* Temp stepper — no API call */
function manageTempStep(ti, d) {
    const item = manageTempItems[ti];
    if (!item || item.quantity_value + d <= 0) return;
    item.quantity_value += d;
    _updateManageTempKcal(ti);
    _updateManageSummary();
    const ssvEl = document.getElementById(`msv-${ti}`);
    if (ssvEl) {
        ssvEl.textContent = item.quantity_value;
        ssvEl.style.transition = 'transform 0.15s';
        ssvEl.style.transform = 'scale(1.3)';
        setTimeout(() => { ssvEl.style.transform = 'scale(1)'; }, 120);
    }
}

/* Temp gram change — no API call */
function manageTempGram(ti, val) {
    const q = parseFloat(val);
    const item = manageTempItems[ti];
    if (!item || !q || q <= 0) return;
    item.quantity_value = q;
    _updateManageTempKcal(ti);
    _updateManageSummary();
}

/* Update kcal label in modal based on base food data */
function _updateManageTempKcal(ti) {
    const item = manageTempItems[ti];

    // Inisialisasi per-unit sekali saja — pakai _origQty & _origCal (snapshot saat modal dibuka)
    // supaya tidak terpengaruh qty yang sudah berubah saat ini.
    if (item._kcalPerUnit == null) {
        const food = allFoods.find(x => x.id == item.food_id);

        if (food) {
            // Ada di allFoods — hitung dari sumber bersih
            const base = parseFloat(food.base_gram) || 100;
            if (item.quantity_type === 'piece') {
                const pieceG = parseFloat(food.piece_gram) || base;
                item._kcalPerUnit = food.calories * (pieceG / base);
                item._proPerUnit  = food.protein  * (pieceG / base);
                item._carbPerUnit = food.carbs     * (pieceG / base);
                item._fatPerUnit  = food.fat       * (pieceG / base);
            } else {
                item._kcalPerUnit = food.calories / base;
                item._proPerUnit  = food.protein  / base;
                item._carbPerUnit = food.carbs     / base;
                item._fatPerUnit  = food.fat       / base;
            }
        } else if (item._origQty > 0) {
            // Tidak ada di allFoods — derive dari snapshot ORIGINAL (sebelum user edit apapun)
            item._kcalPerUnit = item._origCal  / item._origQty;
            item._proPerUnit  = item._origPro  / item._origQty;
            item._carbPerUnit = item._origCarb / item._origQty;
            item._fatPerUnit  = item._origFat  / item._origQty;
        }
    }

    if (item._kcalPerUnit == null) return;

    const qty = item.quantity_value;
    item.calories = parseFloat((item._kcalPerUnit * qty).toFixed(1));
    item.protein  = parseFloat((item._proPerUnit  * qty).toFixed(1));
    item.carbs    = parseFloat((item._carbPerUnit * qty).toFixed(1));
    item.fat      = parseFloat((item._fatPerUnit  * qty).toFixed(1));

    const kcalEl = document.getElementById(`mkcal-${ti}`);
    if (kcalEl) kcalEl.textContent = `${item.calories.toFixed(0)} kcal`;
}

/* Queue deletion in temp state — no API call yet */
function manageTempDelete(ti) {
    const item = manageTempItems[ti];
    if (item?.id) manageDeletedIds.push(item.id);
    manageTempItems.splice(ti, 1);
    renderManageList();
    _updateManageSummary();
}

/* [Kembali] / X — discard all changes */
function cancelManageSheet() {
    manageTempItems  = [];
    manageDeletedIds = [];
    manageKey        = null;
    document.getElementById('manageOverlay').style.display = 'none';
    document.body.style.overflow = '';
}

function handleManageBackdrop(e) {
    if (e.target.id === 'manageOverlay') cancelManageSheet();
}

/* [Simpan] — batch commit all temp changes to DB */
async function saveManageSheet() {
    let hadError = false;

    // 1. Delete queued items
    for (const id of manageDeletedIds) {
        try {
            const r = await fetch('../api/dietary_item_delete.php', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ item_id: id })
            });
            const j = await r.json();
            if (!j.success) hadError = true;
        } catch(e) { hadError = true; }
    }

    // 2. Insert brand-new items (is_new: true) via draft_save.php
    const newItems = manageTempItems.filter(i => i.is_new === true);
    if (newItems.length) {
        try {
            const payload = newItems.map(i => ({
                food_id:        i.food_id,
                food_name:      i.food_name,
                image:          i.image,
                base_gram:      i.base_gram,
                piece_gram:     i.piece_gram,
                quantity_type:  i.quantity_type,
                quantity_value: i.quantity_value,
                calories:       i.calories,
                protein:        i.protein,
                carbs:          i.carbs,
                fat:            i.fat,
                meal_type:      i.meal_type,
            }));
            const r = await fetch('../api/draft_save.php', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ items: payload, log_date: selDate })
            });
            const j = await r.json();
            if (!j.success) hadError = true;
        } catch(e) { hadError = true; }
    }

    // 3. Update existing items where quantity changed
    const existingItems = manageTempItems.filter(i => !i.is_new && i.id);
    for (const item of existingItems) {
        const orig = savedItems.find(s => s.id === item.id);
        if (orig && orig.quantity_value === item.quantity_value) continue; // no change, skip
        try {
            const r = await fetch('../api/dietary_item_update.php', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ item_id: item.id, quantity_value: item.quantity_value })
            });
            const j = await r.json();
            if (!j.success) hadError = true;
        } catch(e) { hadError = true; }
    }

    // 4. Re-fetch & refresh UI
    await fetchLoggedDates();
    savedItems = await fetchSaved(selDate);
    renderWeek();
    renderMeals();
    renderNutri();

    manageTempItems  = [];
    manageDeletedIds = [];
    manageKey        = null;
    document.getElementById('manageOverlay').style.display = 'none';

    if (hadError) toast('Beberapa perubahan gagal disimpan', 'err');
    else toast('Perubahan disimpan ✓');
}

/* backdrop handled by handleManageBackdrop(event) on overlay element */

/* ════════════════════════════════════
   ADD FOOD SHEET
════════════════════════════════════ */
let _addSheetFromManage = false; // flag: opened from manage modal's Quick Add

function openAddSheet() {
    document.getElementById('addOverlay').style.display = 'block';
    document.body.style.overflow = 'hidden';
    if (!allFoods.length) fetchFoods('', '');
    syncDraftFloatBar();
}
function closeAddSheet() {
    document.getElementById('addOverlay').style.display = 'none';
    document.body.style.overflow = '';
    // If opened from manage modal, restore manage modal
    if (_addSheetFromManage) {
        _addSheetFromManage = false;
        if (manageKey) {
            renderManageList();
            document.getElementById('manageOverlay').style.display = 'flex';
        }
    }
}
document.getElementById('addOverlay').addEventListener('click', e => {
    if (e.target.id === 'addOverlay') closeAddSheet();
});

let debT;
document.getElementById('addSearch').addEventListener('input', function() {
    const v = this.value;
    document.getElementById('addClearBtn').style.display = v ? 'block' : 'none';
    clearTimeout(debT);
    debT = setTimeout(() => fetchFoods(v, activeCat()), 280);
});
function clearSearch() {
    document.getElementById('addSearch').value = '';
    document.getElementById('addClearBtn').style.display = 'none';
    fetchFoods('', activeCat());
}
function activeCat() {
    return document.querySelector('#addChips .chip.active')?.dataset.cat || '';
}

document.querySelectorAll('#addChips .chip').forEach(c => {
    c.addEventListener('click', () => {
        document.querySelectorAll('#addChips .chip').forEach(x => x.classList.remove('active'));
        c.classList.add('active');
        fetchFoods(document.getElementById('addSearch').value, c.dataset.cat);
    });
});

async function fetchFoods(search='', cat='') {
    const p = new URLSearchParams();
    if (search) p.append('search', search);
    if (cat)    p.append('category', cat);
    const url = '../api/foods.php' + (p.toString() ? '?'+p : '');
    try {
        const r = await fetch(url);
        const j = await r.json();
        if (j.success) { allFoods = j.data; renderFoodGrid(); }
    } catch(e){}
}

function renderFoodGrid() {
    const grid = document.getElementById('addFoodGrid');
    grid.innerHTML = '';
    if (!allFoods.length) {
        grid.innerHTML = `<div style="grid-column:span 2;text-align:center;padding:28px 0;color:var(--ink-3);font-size:13px">
            <div style="font-size:28px;margin-bottom:8px">🔍</div>Makanan tidak ditemukan</div>`;
        return;
    }
    allFoods.forEach(food => {
        const srv = food.piece_gram ? `1 porsi (${food.piece_gram}g)` : `${food.base_gram}g`;
        const card = document.createElement('div');
        card.className = 'food-card';
        card.innerHTML = `
            <img class="food-card-img" src="${food.image}" alt="" loading="lazy">
            <div class="food-card-body">
                <div class="food-card-name">${food.food_name}</div>
                <div class="food-card-srv">${srv}</div>
                <div class="food-card-kcal">${food.calories} kcal</div>
            </div>
        `;
        card.addEventListener('click', () => openDetail(food));
        grid.appendChild(card);
    });
}

/* ════════════════════════════════════
   FOOD DETAIL SHEET
════════════════════════════════════ */
function calcMul(food, qty, type) {
    const base = parseFloat(food.base_gram) || 100;
    if (type === 'piece') return food.piece_gram ? qty*(parseFloat(food.piece_gram)/base) : qty;
    return qty / base;
}

function openDetail(food) {
    selFood = food;
    qtyType = food.piece_gram ? 'piece' : 'gram';

    document.getElementById('detailImg').src          = food.image;
    document.getElementById('detailName').textContent = food.food_name;

    const tog = document.getElementById('detailToggle');
    if (!food.piece_gram) {
        tog.classList.add('gram-only');
        document.querySelector('#detailToggle .toggle-gram').classList.add('active');
        document.querySelector('#detailToggle .toggle-piece').classList.remove('active');
        document.getElementById('detailQty').value = food.base_gram || 100;
        document.getElementById('detailQtyHint').textContent = `Serving standar: ${food.base_gram}g`;
    } else {
        tog.classList.remove('gram-only');
        document.querySelector('#detailToggle .toggle-piece').classList.add('active');
        document.querySelector('#detailToggle .toggle-gram').classList.remove('active');
        document.getElementById('detailQty').value = 1;
        document.getElementById('detailQtyHint').textContent = `1 porsi ≈ ${food.piece_gram}g`;
    }

    refreshDetailMacros();
    document.getElementById('detailOverlay').style.display = 'block';
    document.getElementById('draftFloatBar').classList.add('hidden-by-modal');
}

function refreshDetailMacros() {
    if (!selFood) return;
    const qty = parseFloat(document.getElementById('detailQty').value) || 1;
    const mul = calcMul(selFood, qty, qtyType);
    document.getElementById('dCal').textContent  = (selFood.calories*mul).toFixed(1);
    document.getElementById('dPro').textContent  = (selFood.protein *mul).toFixed(1)+'g';
    document.getElementById('dCarb').textContent = (selFood.carbs   *mul).toFixed(1)+'g';
    document.getElementById('dFat').textContent  = (selFood.fat     *mul).toFixed(1)+'g';
}

document.querySelectorAll('#detailToggle button').forEach(btn => {
    btn.addEventListener('click', () => {
        if (!selFood) return;
        document.querySelectorAll('#detailToggle button').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        qtyType = btn.dataset.type;
        if (qtyType === 'piece') {
            document.getElementById('detailQty').value = 1;
            document.getElementById('detailQtyHint').textContent = `1 porsi ≈ ${selFood.piece_gram}g`;
        } else {
            document.getElementById('detailQty').value = selFood.base_gram || 100;
            document.getElementById('detailQtyHint').textContent = `Serving standar: ${selFood.base_gram}g`;
        }
        refreshDetailMacros();
    });
});
document.getElementById('detailQty').addEventListener('input', refreshDetailMacros);

function closeDetailSheet() {
    document.getElementById('detailOverlay').style.display = 'none';
    syncDraftFloatBar();
}
document.getElementById('detailOverlay').addEventListener('click', e => {
    if (e.target.id === 'detailOverlay') closeDetailSheet();
});

document.getElementById('addToDraftBtn').addEventListener('click', () => {
    const qty = parseFloat(document.getElementById('detailQty').value) || 1;
    const mul = calcMul(selFood, qty, qtyType);
    const newItem = {
        food_id:       selFood.id,
        food_name:     selFood.food_name,
        image:         selFood.image,
        base_gram:     selFood.base_gram,
        piece_gram:    selFood.piece_gram ?? null,
        quantity_type: qtyType,
        quantity_value:qty,
        calories:      parseFloat((selFood.calories*mul).toFixed(1)),
        protein:       parseFloat((selFood.protein *mul).toFixed(1)),
        carbs:         parseFloat((selFood.carbs   *mul).toFixed(1)),
        fat:           parseFloat((selFood.fat     *mul).toFixed(1)),
    };

   if (_addSheetFromManage && manageKey) {
        // FIX #4 + BUG FIX #2: Quick Add bypass — inject with is_new:true flag
        injectNewItemToManage(selFood, qty, qtyType);
        closeDetailSheet();
        toast(`${selFood.food_name} ditambahkan ke ${MEAL_MAP[manageKey].label} ✓`);
        // Re-open manage modal
        closeAddSheet(); // this already handles _addSheetFromManage restore
    } else {
        // Normal path — add to draft
        draft.push(newItem);
        saveDraftLS();
        closeDetailSheet();
        toast(`${selFood.food_name} ditambahkan ke draft ✓`);
    }
});

/* ════════════════════════════════════
   DRAFT FLOATING BAR
════════════════════════════════════ */
function syncDraftFloatBar() {
    const bar = document.getElementById('draftFloatBar');
    const lbl = document.getElementById('draftFloatLabel');
    bar.classList.remove('hidden-by-modal');
    if (draft.length > 0) {
        bar.classList.add('show');
        lbl.textContent = `${draft.length} item dalam draft`;
    } else {
        bar.classList.remove('show');
    }
}

/* ════════════════════════════════════
   DRAFT SHEET
════════════════════════════════════ */
function openDraftSheet() {
    renderDraftSheet();
    document.getElementById('draftOverlay').style.display = 'block';
}
function closeDraftSheet() {
    document.getElementById('draftOverlay').style.display = 'none';
}
document.getElementById('draftOverlay').addEventListener('click', e => {
    if (e.target.id === 'draftOverlay') closeDraftSheet();
});

/* ════════════════════════════════════
   SUBSCRIPTION SHEET
════════════════════════════════════ */
function openSubSheet() {
    document.getElementById('subOverlay').style.display = 'block';
}
function closeSubSheet() {
    document.getElementById('subOverlay').style.display = 'none';
}
function selectPriceTier(el) {
    document.querySelectorAll('#priceTierList .price-tier').forEach(t => t.classList.remove('selected'));
    el.classList.add('selected');
}

document.querySelectorAll('.meal-pick-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.meal-pick-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        batchMeal = btn.dataset.meal;
    });
});

function renderDraftSheet() {
    const wrap   = document.getElementById('draftItemsInner');
    const totRow = document.getElementById('draftTotalRow');
    const cntEl  = document.getElementById('draftCount');
    wrap.innerHTML = ''; totRow.innerHTML = '';

    cntEl.textContent = draft.length ? `${draft.length} item` : '0 item';

    if (!draft.length) {
        wrap.innerHTML = '<div class="draft-empty-state">Draft masih kosong.<br>Tambah makanan dulu ya.</div>';
        return;
    }

    let tc=0,tp=0,tcb=0,tf=0;
    draft.forEach((item, idx) => {
        tc+=item.calories; tp+=item.protein; tcb+=item.carbs; tf+=item.fat;
        const ctrl = item.quantity_type==='piece'
            ? `<button class="step-btn-sm" onclick="draftStep(${idx},-1)">−</button>
               <span class="step-val">${item.quantity_value} porsi</span>
               <button class="step-btn-sm" onclick="draftStep(${idx},1)">+</button>`
            : `<input class="gram-input-sm" type="number" value="${item.quantity_value}" min="1"
                   onchange="draftGram(${idx},this.value)">
               <span class="gram-unit">g</span>`;
        const div = document.createElement('div');
        div.className = 'draft-item-row';
        div.innerHTML = `
            <img class="draft-item-img" src="${item.image}" alt="">
            <div class="draft-item-info">
                <div class="draft-item-name">${item.food_name}</div>
                <div class="draft-item-kcal">${item.calories.toFixed(0)} kcal</div>
                <div class="draft-stepper">
                    ${ctrl}
                    <button class="draft-del-btn" onclick="draftDelete(${idx})">Hapus</button>
                </div>
            </div>
        `;
        wrap.appendChild(div);
    });

    totRow.innerHTML = `
        <div class="dtotal-pill">${tc.toFixed(0)} kcal</div>
        <div class="dtotal-pill">P ${tp.toFixed(0)}g</div>
        <div class="dtotal-pill">K ${tcb.toFixed(0)}g</div>
        <div class="dtotal-pill">L ${tf.toFixed(0)}g</div>
    `;
}

function recalcDraftItem(item) {
    const f = allFoods.find(x => x.id == item.food_id) ||
              {calories:0,protein:0,carbs:0,fat:0,base_gram:item.base_gram,piece_gram:item.piece_gram};
    const mul = calcMul(f, item.quantity_value, item.quantity_type);
    item.calories = parseFloat((f.calories*mul).toFixed(1));
    item.protein  = parseFloat((f.protein *mul).toFixed(1));
    item.carbs    = parseFloat((f.carbs   *mul).toFixed(1));
    item.fat      = parseFloat((f.fat     *mul).toFixed(1));
}
function draftStep(idx, d) {
    if (!draft[idx] || draft[idx].quantity_value+d <= 0) return;
    draft[idx].quantity_value += d;
    recalcDraftItem(draft[idx]);
    saveDraftLS(); renderDraftSheet(); syncDraftFloatBar();
}
function draftGram(idx, val) {
    const q = parseFloat(val);
    if (!draft[idx] || !q || q <= 0) return;
    draft[idx].quantity_value = q;
    recalcDraftItem(draft[idx]);
    saveDraftLS(); renderDraftSheet();
}
function draftDelete(idx) {
    draft.splice(idx, 1);
    saveDraftLS(); syncDraftFloatBar(); renderDraftSheet();
}

/* ════════════════════════════════════
   SAVE DRAFT → DB (draft_save.php)
════════════════════════════════════ */
document.getElementById('saveDraftBtn').addEventListener('click', async () => {
    if (!draft.length) { closeDraftSheet(); return; }

    const itemsToSave = draft.map(item => ({ ...item, meal_type: batchMeal }));

    try {
        const r = await fetch('../api/draft_save.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ items: itemsToSave, log_date: selDate })
        });
        const j = await r.json();
        if (!j.success) { toast(j.message||'Gagal menyimpan', 'err'); return; }

        draft = [];
        clearDraftLS(selDate);
        savedItems = await fetchSaved(selDate);
        await fetchLoggedDates();
        renderWeek();
        renderMeals();
        renderNutri();
        syncDraftFloatBar();
        closeDraftSheet();
        closeAddSheet();
        toast('Log berhasil disimpan! ✓');
    } catch(e){ toast('Gagal menyimpan', 'err'); }
});

/* ════════════════════════════════════
   NOTIFICATION CENTER
════════════════════════════════════ */
let _notifCache = [];
let _expandedNotifIdx = null; // hanya satu card boleh terbuka sekaligus

const NOTIF_ICON = {
    reminder:       '⏰',
    insight_weekly: '📊',
    achievement:    '🏆'
};
const NOTIF_LABEL = {
    reminder:       'Pengingat',
    insight_weekly: 'Insight Mingguan',
    achievement:    'Pencapaian'
};

function formatNotifTime(dateStr) {
    const d = new Date(dateStr.replace(' ', 'T'));
    if (isNaN(d.getTime())) return '';
    const now = new Date();
    const diffMs = now - d;
    const diffMin = Math.floor(diffMs / 60000);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);

    if (diffMin < 1) return 'Baru saja';
    if (diffMin < 60) return `${diffMin}m`;
    if (diffHour < 24) return `${diffHour}j`;
    if (diffDay < 7) return `${diffDay}h`;
    return d.toLocaleDateString('id-ID', { day:'numeric', month:'short' });
}

async function loadNotifBadge() {
    try {
        const r = await fetch('../api/notifications_get.php');
        const j = await r.json();
        if (!j.success) return;

        _notifCache = j.notifications || [];
        const badge = document.getElementById('notifBadge');
        badge.style.display = j.unread_count > 0 ? 'block' : 'none';
    } catch (e) {
        // Silent fail — badge notifikasi tidak kritikal untuk fungsi utama app.
    }
}

function renderNotifList() {
    const scroll = document.getElementById('notifScroll');
    const sub = document.getElementById('notifSub');
    const markAllBtn = document.getElementById('notifMarkAllBtn');

    if (!_notifCache.length) {
        scroll.innerHTML = `
            <div class="notif-empty-state">
                <div class="notif-empty-icon">🔔</div>
                Belum ada notifikasi
            </div>`;
        sub.textContent = '';
        markAllBtn.style.display = 'none';
        return;
    }

    const unreadCount = _notifCache.filter(n => !n.is_read).length;
    sub.textContent = unreadCount > 0 ? `${unreadCount} belum dibaca` : 'Semua sudah dibaca';
    markAllBtn.style.display = unreadCount > 0 ? 'block' : 'none';

    scroll.innerHTML = _notifCache.map((n, idx) => {
        const isExpanded = _expandedNotifIdx === idx;
        const icon = NOTIF_ICON[n.type] || '🔔';
        return `
        <div class="notif-item ${n.is_read ? 'is-read' : 'is-unread'} ${isExpanded ? 'expanded' : ''}" data-idx="${idx}">
            <div class="notif-item-icon ${n.type}">${icon}</div>
            <div class="notif-item-body" onclick="toggleNotifDetail(${idx})">
                <div class="notif-item-title">${n.title}</div>
                <div class="notif-item-text">${n.body}</div>
                <div class="notif-item-time">${formatNotifTime(n.created_at)}</div>
                <div class="notif-item-detail">
                    <div class="notif-item-detail-text">${n.body}</div>
                </div>
            </div>
            <div class="notif-item-side">
                <div class="notif-item-unread-dot ${n.is_read ? 'hidden' : ''}"></div>
                <button class="notif-item-delete-btn" onclick="event.stopPropagation(); deleteNotif(${idx})">
                    <img src="../assets/icon/iconremovenotif.jpeg" alt="Hapus" class="notif-item-delete-icon">
                </button>
            </div>
        </div>`;
    }).join('');
}

function toggleNotifDetail(idx) {
    const n = _notifCache[idx];
    if (!n) return;

    // Accordion: tutup yang lain, buka/tutup yang ditekan
    _expandedNotifIdx = (_expandedNotifIdx === idx) ? null : idx;
    renderNotifList();

    if (!n.is_read) {
        n.is_read = 1;
        markNotifRead(n.id);
        const stillUnread = _notifCache.filter(x => !x.is_read).length;
        document.getElementById('notifBadge').style.display = stillUnread > 0 ? 'block' : 'none';
    }
}

async function markNotifRead(notificationId) {
    try {
        await fetch('../api/notifications_mark_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ notification_id: notificationId })
        });
    } catch (e) {
        // Silent fail — status baca akan tersinkron lagi di load berikutnya.
    }
}

async function markAllNotifRead() {
    try {
        await fetch('../api/notifications_mark_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mark_all: true })
        });
        _notifCache.forEach(n => n.is_read = 1);
        renderNotifList();
        document.getElementById('notifBadge').style.display = 'none';
    } catch (e) {
        toast('Gagal menandai notifikasi', 'err');
    }
}

async function deleteNotif(idx) {
    const n = _notifCache[idx];
    if (!n) return;

    _notifCache.splice(idx, 1);
    _expandedNotifIdx = null;
    renderNotifList();

    try {
        await fetch('../api/notifications_delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ notification_id: n.id })
        });
    } catch (e) {
        toast('Gagal menghapus notifikasi', 'err');
    }
}

async function openNotifCenter() {
    document.getElementById('notifOverlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    _expandedNotifIdx = null;

    try {
        const r = await fetch('../api/notifications_get.php');
        const j = await r.json();
        if (j.success) {
            _notifCache = j.notifications || [];
        }
    } catch (e) {
        // Pakai cache lama kalau fetch gagal
    }
    renderNotifList();
}

function closeNotifCenter() {
    document.getElementById('notifOverlay').style.display = 'none';
    document.body.style.overflow = '';
}

/* ════════════════════════════════════
   UTIL
════════════════════════════════════ */
function closeAllOverlays() {
    ['addOverlay','detailOverlay','draftOverlay','notifOverlay','subOverlay'].forEach(id => {
        document.getElementById(id).style.display = 'none';
    });
    cancelManageSheet();
    document.body.style.overflow = '';
}

/* ════════════════════════════════════
   PROFILE SWITCHER
════════════════════════════════════ */
function openProfileSwitcher() {
    document.getElementById('profileSwitcherOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeProfileSwitcher() {
    document.getElementById('profileSwitcherOverlay').classList.remove('open');
    document.body.style.overflow = '';
}

// Tap backdrop (di luar sheet) untuk menutup
function handleSwitcherBackdrop(e) {
    if (e.target === document.getElementById('profileSwitcherOverlay')) {
        closeProfileSwitcher();
    }
}

async function switchProfile(profileId, isAlreadyActive) {
    // Kalau klik profil yang sudah aktif, cukup tutup sheet
    if (isAlreadyActive) {
        closeProfileSwitcher();
        return;
    }

    try {
        const r = await fetch('../api/switch_profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ profile_id: profileId })
        });
        const j = await r.json();
        if (j.success) {
            // Reload halaman agar semua data (nutrisi, log, header) ikut refresh
            window.location.reload();
        } else {
            toast(j.message || 'Gagal ganti profil', 'err');
            closeProfileSwitcher();
        }
    } catch (e) {
        toast('Koneksi bermasalah', 'err');
    }
}

/* ════════════════════════════════════
   INIT
════════════════════════════════════ */
async function init() {
    updateWeekLabel();
    renderWeek();
    await fetchLoggedDates();
    renderWeek();
    await loadDay();
    loadNotifBadge();
}
init();
</script>
</body>
</html>