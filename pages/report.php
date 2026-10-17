<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="manifest" href="/nutrimoms/manifest.json">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=375, user-scalable=no">
    <title>Laporan - NutriMoms</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
        body{background:linear-gradient(160deg,#EAF2EC 0%,#F0F5EF 60%,#EBF0E8 100%);font-family:'DM Sans',sans-serif;display:flex;justify-content:center;color:#1E1E1E;min-height:100vh}
        .mobile-wrapper{width:375px;min-height:100vh;background:linear-gradient(160deg,#EAF2EC 0%,#F0F5EF 60%,#EBF0E8 100%);position:relative;overflow-x:hidden}
        .status-space{height:0}
        .content{padding:16px 20px 120px}
        .header{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
        .header-eyebrow{font-size:11px;font-weight:600;color:#AAAAAA;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:2px}
        .page-title{font-size:26px;font-weight:700;letter-spacing:-0.8px;line-height:1}
        .goals-btn{display:flex;align-items:center;gap:6px;background:#5A9E6A;color:#FFFFFF;border:none;border-radius:14px;padding:9px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;letter-spacing:0.2px;white-space:nowrap}
        .period-row{display:flex;align-items:center;gap:10px;margin-bottom:12px}
        .period-dropdown-wrap{position:relative;flex:1}
        .period-select{width:100%;appearance:none;background:#FFFFFF;border:1px solid #E8E4DD;border-radius:14px;padding:10px 36px 10px 14px;font-size:13px;font-weight:600;color:#1E1E1E;font-family:'DM Sans',sans-serif;cursor:pointer;box-shadow:0 1px 6px rgba(0,0,0,0.04)}
        .period-chevron{position:absolute;right:12px;top:50%;transform:translateY(-50%);pointer-events:none;font-size:12px;color:#AAAAAA}
        .date-range-label{font-size:11px;font-weight:600;color:#AAAAAA;white-space:nowrap}
        .sub-tab-row{display:flex;gap:0;margin-bottom:20px;border-bottom:1.5px solid #E8E4DD}
        .sub-tab{flex:1;border:none;background:transparent;padding:10px 0;font-size:13px;font-weight:600;color:#AAAAAA;cursor:pointer;font-family:'DM Sans',sans-serif;position:relative;min-height:44px;transition:color 0.2s ease}
        .sub-tab::after{content:'';position:absolute;bottom:-1.5px;left:16%;width:68%;height:2px;background:#4A8C5C;border-radius:2px 2px 0 0;opacity:0;transition:opacity 0.2s ease}
        .sub-tab.active{color:#1E1E1E}
        .sub-tab.active::after{opacity:1}
        .kpi-summary{background:#5A9E6A;border-radius:22px;padding:20px 20px 22px;margin-bottom:18px;position:relative;overflow:hidden}
        .kpi-summary::after{content:'';position:absolute;top:-30px;right:-30px;width:110px;height:110px;border-radius:50%;background:rgba(255,255,255,0.08)}
        .kpi-week-label{font-size:11px;font-weight:600;color:rgba(255,255,255,0.6);text-transform:uppercase;letter-spacing:0.6px;margin-bottom:6px}
        .kpi-big{font-size:44px;font-weight:700;color:#FFFFFF;letter-spacing:-2px;line-height:1;margin-bottom:2px}
        .kpi-big span{font-size:16px;font-weight:500;color:rgba(255,255,255,0.6);letter-spacing:0}
        .kpi-sub-row{display:flex;gap:0;margin-top:18px}
        .kpi-sub-item{flex:1}
        .kpi-sub-item+.kpi-sub-item{border-left:1px solid rgba(255,255,255,0.2);padding-left:16px}
        .kpi-sub-label{font-size:10px;color:rgba(255,255,255,0.6);font-weight:600;text-transform:uppercase;letter-spacing:0.4px;margin-bottom:3px}
        .kpi-sub-val{font-size:15px;font-weight:700;color:#FFFFFF}
        .chart-section-eyebrow{font-size:11px;font-weight:700;color:#AAAAAA;text-transform:uppercase;letter-spacing:0.6px;margin-bottom:10px;padding-left:4px;margin-}
        .chart-card{background:#FFFFFF;border-radius:20px;padding:22px 16px 16px;margin-bottom:14px;box-shadow:0 1px 8px rgba(0,0,0,0.04)}
        .chart-area{display:flex;align-items:flex-end;gap:4px;height:140px;position:relative}
        .goal-line{position:absolute;left:0;right:0;border-top:2px dashed rgba(74,140,92,0.6);pointer-events:none;z-index:3}
        .bar-col{flex:1;display:flex;flex-direction:column;align-items:center;gap:5px;cursor:pointer;height:100%;justify-content:flex-end;transition:opacity 0.25s ease;position:relative;z-index:2}
        .bar-col.dimmed{opacity:0.18}
        .bar-stack{width:100%;max-width:34px;display:flex;flex-direction:column-reverse;border-radius:3px 3px 2px 2px;overflow:hidden}
        .bar-col.selected .bar-stack{outline:2px solid #4A8C5C;outline-offset:1px;border-radius:3px}
        .bar-segment{width:100%;transition:height 0.35s ease}
        .bar-segment.pagi{background:#E8A838}
        .bar-segment.siang{background:#6B8DD6}
        .bar-segment.malam{background:#8B6BD6}
        .bar-segment.snack{background:#D96B6B}
        .bar-segment.protein{background:#7FA8D9}
        .bar-segment.carbs{background:#E8C25E}
        .bar-segment.fat{background:#E8A86B}
        .bar-empty{width:100%;max-width:34px;height:4px;background:#E8E4DD;border-radius:2px}
        .bar-day-label{display:flex;flex-direction:column;align-items:center;user-select:none;line-height:1.2;text-align:center}
        .lbl-date{font-size:10px;font-weight:700;color:#888888;transition:color 0.2s ease}
        .lbl-day{font-size:9px;font-weight:600;color:#999999;transition:color 0.2s ease}
        .bar-col.selected .lbl-date,.bar-col.selected .lbl-day{color:#4A8C5C}
        .chart-legend{display:flex;gap:14px;flex-wrap:wrap;margin-top:12px;padding-top:10px;border-top:1px solid #F0EDE8}
        .legend-item{display:flex;align-items:center;gap:5px;font-size:10px;font-weight:600;color:#AAAAAA}
        .legend-dot{width:8px;height:8px;border-radius:50%}
        .distribusi-card{background:#FFFFFF;border-radius:20px;padding:16px 18px;margin-bottom:20px;box-shadow:0 1px 8px rgba(0,0,0,0.05)}
        .distribusi-title{font-size:11px;font-weight:700;color:#AAAAAA;text-transform:uppercase;letter-spacing:0.6px;margin-bottom:14px}
        .dist-row{display:flex;align-items:center;gap:9px;margin-bottom:11px}
        .dist-row:last-child{margin-bottom:0}
        .dist-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0}
        .dist-label{font-size:12px;font-weight:600;color:#1E1E1E;width:44px;flex-shrink:0}
        .dist-track{flex:1;height:6px;background:#F0EDE8;border-radius:3px;overflow:hidden}
        .dist-fill{height:100%;border-radius:3px;transition:width 0.4s ease}
        .dist-pct{font-size:11px;font-weight:700;color:#888;width:30px;text-align:right;flex-shrink:0}
        .dist-kcal{font-size:11px;font-weight:600;color:#AAAAAA;width:64px;text-align:right;flex-shrink:0}
        .dist-row-nutr{display:flex;align-items:center;gap:9px;margin-bottom:11px}
        .dist-row-nutr:last-child{margin-bottom:0}
        .dist-track-nutr{width:60px;flex-shrink:0;height:6px;background:#F0EDE8;border-radius:3px;overflow:hidden}
        .dist-pct-nutr{font-size:11px;font-weight:700;color:#888;width:32px;text-align:right;flex-shrink:0}
        .dist-amount-nutr{font-size:11px;font-weight:600;color:#AAAAAA;flex:1;text-align:right}
        .dist-amount-nutr b{color:#1E1E1E;font-weight:700}
        .foods-header-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
        .section-heading{font-size:15px;font-weight:700;letter-spacing:-0.2px}
        .foods-count-badge{font-size:11px;font-weight:600;color:#4A8C5C;background:#EEF4F0;padding:3px 9px;border-radius:20px}
        .food-day-group{margin-bottom:20px}
        .food-day-header{font-size:12px;font-weight:700;color:#1E1E1E;margin-bottom:8px;padding:8px 12px;background:#EEF4F0;border-radius:10px;display:flex;align-items:center;justify-content:space-between}
        .food-day-header-cal{font-size:11px;font-weight:700;color:#4A8C5C}
        .food-table-head{display:grid;grid-template-columns:1fr 80px 68px;gap:8px;padding:8px 4px 8px;border-bottom:1px solid #F0EDE8;margin-bottom:4px}
        .food-th{font-size:10px;font-weight:700;color:#AAAAAA;text-transform:uppercase;letter-spacing:0.3px}
        .food-th:nth-child(2),.food-th:nth-child(3){text-align:right}
        .food-row{display:grid;grid-template-columns:1fr 80px 68px;gap:4px;padding:8px 4px;border-bottom:1px solid #F7F5F1;align-items:center}
        .food-row:last-child{border-bottom:none}
        .food-row-name{display:flex;align-items:center;gap:8px;min-width:0}
        .food-row-img{width:30px;height:30px;border-radius:7px;object-fit:cover;flex-shrink:0}
        .food-row-placeholder{width:30px;height:30px;border-radius:7px;background:#EEF4F0;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}
        .food-row-text{min-width:0}
        .food-name-text{font-size:12px;font-weight:600;color:#1E1E1E;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .food-meal-chip{display:inline-block;font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;margin-top:2px}
        .food-meal-chip.pagi{background:#FFF3D6;color:#B07010}
        .food-meal-chip.siang{background:#E8EEFF;color:#3A5BAA}
        .food-meal-chip.malam{background:#EEE8FF;color:#6040AA}
        .food-meal-chip.snack{background:#FFE8E8;color:#AA3030}
        .food-row-portion{font-size:11px;font-weight:500;color:#777;text-align:right}
        .food-row-cal{font-size:12px;font-weight:700;color:#4A8C5C;text-align:right}
        .food-row-cal span{display:block;font-size:9px;font-weight:400;color:#AAAAAA}
        .food-table-head-nutr{display:grid;grid-template-columns:1fr 56px 50px 50px 50px;gap:6px;padding:8px 4px 8px;border-bottom:1px solid #F0EDE8;margin-bottom:4px}
        .food-table-head-nutr .food-th{text-align:right}
        .food-table-head-nutr .food-th:first-child{text-align:left}
        .food-row-nutr{display:grid;grid-template-columns:1fr 56px 50px 50px 50px;gap:6px;padding:9px 4px;border-bottom:1px solid #F7F5F1;align-items:center}
        .food-row-nutr:last-child{border-bottom:none}
        .food-row-num{font-size:12px;font-weight:600;color:#1E1E1E;text-align:right}
        .food-empty{background:#FFFFFF;border-radius:14px;padding:28px;text-align:center;font-size:13px;color:#AAAAAA;box-shadow:0 1px 5px rgba(0,0,0,0.04)}
        .macro-bars-card{background:#FFFFFF;border-radius:20px;padding:18px 18px;margin-bottom:16px;box-shadow:0 1px 8px rgba(0,0,0,0.04)}
        .macro-bar-row{display:flex;align-items:center;gap:10px;margin-bottom:13px}
        .macro-bar-row:last-child{margin-bottom:0}
        .macro-bar-name{width:54px;font-size:12px;font-weight:700;color:#1E1E1E;flex-shrink:0}
        .macro-bar-track{flex:1;height:7px;background:#F0EDE8;border-radius:4px;overflow:hidden}
        .macro-bar-fill{height:100%;border-radius:4px;transition:width 0.45s ease;width:0%}
        .macro-bar-fill.protein{background:#D96B6B}
        .macro-bar-fill.carbs{background:#C08030}
        .macro-bar-fill.fat{background:#B8A020}
        .macro-bar-fill.over{background:#D94040}
        .macro-bar-label{font-size:11px;font-weight:600;color:#AAAAAA;text-align:right;flex-shrink:0;min-width:72px}
        .stat-card{background:#FFFFFF;border-radius:20px;padding:18px 16px;margin-bottom:20px;box-shadow:0 1px 8px rgba(0,0,0,0.04)}
        .stat-header{display:grid;grid-template-columns:minmax(0,1.4fr) 62px 54px 46px;gap:4px;padding-bottom:8px;border-bottom:1px solid #F0EDE8;margin-bottom:4px}
        .stat-th{font-size:10px;font-weight:700;color:#AAAAAA;text-transform:uppercase;letter-spacing:0.4px;text-align:right}
        .stat-th:first-child{text-align:left}
        .stat-group-label{font-size:10px;font-weight:700;color:#4A8C5C;text-transform:uppercase;letter-spacing:0.6px;padding:10px 0 5px}
        .stat-row{display:grid;grid-template-columns:minmax(0,1.4fr) 62px 54px 46px;gap:4px;padding:7px 0;border-bottom:1px solid #F7F5F1;align-items:center}
        .stat-row:last-child{border-bottom:none}
        .stat-nutrient-wrap{min-width:0}
        .stat-nutrient{font-size:12px;font-weight:600;color:#1E1E1E;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .stat-mini-bar{margin-top:3px;width:100%;height:3px;background:#F0EDE8;border-radius:2px;overflow:hidden}
        .stat-mini-fill{height:100%;border-radius:2px;transition:width 0.4s ease;width:0%}
        .stat-mini-fill.grey{background:#CCCCCC}
        .stat-mini-fill.yellow{background:#E8A838}
        .stat-mini-fill.green{background:#4A8C5C}
        .stat-mini-fill.red{background:#D96B6B}
        .stat-td{font-size:11px;font-weight:600;color:#1E1E1E;text-align:right}
        .stat-td .u{font-size:10px;font-weight:400;color:#BBBBBB}
        .stat-remaining{font-size:11px;font-weight:700;text-align:right}
        .stat-remaining.neutral{color:#AAAAAA;font-weight:500}
        .stat-remaining.over{color:#D96B6B}
        .tab-panel{display:none}
        .tab-panel.active{display:block}
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
    </style>
</head>
<body>
<div class="mobile-wrapper">
    <div class="status-space"></div>
    <div class="content">

        <div class="header">
            <div class="header-left">
                <div class="page-title">Progress</div>
            </div>
            <button class="goals-btn" onclick="openGoals()">Goals</button>
        </div>

        <div class="period-row">
            <div class="period-dropdown-wrap">
                <select class="period-select" id="periodSelect">
                    <option value="this_week">Minggu Ini</option>
                    <option value="last_week">Minggu Lalu</option>
                    <option value="today">Hari Ini</option>
                    <option value="yesterday">Kemarin</option>
                </select>
                <span class="period-chevron">▾</span>
            </div>
            <div class="date-range-label" id="dateRangeLabel">—</div>
        </div>

        <div class="sub-tab-row">
            <button class="sub-tab active" data-tab="calories">Calories</button>
            <button class="sub-tab" data-tab="nutrients">Nutrients</button>
        </div>

        <div class="tab-panel active" id="panel-calories">

            <div class="kpi-summary" id="kpiSummary">
                <div class="kpi-week-label" id="kpiWeekLabel">Minggu Ini</div>
                <div class="kpi-big" id="kpiBig">— <span>kcal</span></div>
                <div class="kpi-sub-row">
                    <div class="kpi-sub-item">
                        <div class="kpi-sub-label" id="kpiSubLabel1">Rata-rata/hari</div>
                        <div class="kpi-sub-val" id="kpiSub1">—</div>
                    </div>
                    <div class="kpi-sub-item">
                        <div class="kpi-sub-label" id="kpiSubLabel2">Target/hari</div>
                        <div class="kpi-sub-val" id="kpiSub2">—</div>
                    </div>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-area" id="chartArea">
                    <div class="goal-line" id="goalLine" style="bottom:0"></div>
                </div>
            </div>

            <div class="distribusi-card" id="distribusiCard">
                <div class="distribusi-title" id="distribusiTitle">Distribusi Kalori</div>
                <div id="distribusiRows"></div>
            </div>

            <div id="foodsListCal"></div>

        </div>

        <div class="tab-panel" id="panel-nutrients">

            <div class="chart-card">
                <div class="chart-area" id="chartAreaNutr"></div>
            </div>

            <div class="distribusi-card" id="distribusiCardNutr">
                <div class="distribusi-title">Distribusi Makro</div>
                <div id="distribusiRowsNutr"></div>
            </div>

            <div id="foodsListNutr"></div>

            <div class="chart-section-eyebrow">Detail Nutrisi</div>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-th" style="text-align:left">Nutrisi</div>
                    <div class="stat-th">Konsumsi</div>
                    <div class="stat-th">Target</div>
                    <div class="stat-th">+/-</div>
                </div>
                <div id="statTableBody"></div>
            </div>
        </div>

    </div>

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
        <a href="report.php" class="nav-item active">
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
        <a href="profile.php" class="nav-item">
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
</div>

<script>
const DAY_SHORT = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
const MEAL_ORDER = ['pagi','siang','malam','snack'];
const MEAL_LABELS = {pagi:'Pagi',siang:'Siang',malam:'Malam',snack:'Snack'};
const MEAL_COLORS = {pagi:'#E8A838',siang:'#6B8DD6',malam:'#8B6BD6',snack:'#D96B6B'};

const S = {
    period: 'this_week',
    activeTab: 'calories',
    selectedDate: null,
    nutrSelectedDate: null,
    weeklyLogs: [],
    allFoods: [],
    targets: null,
    goalCal: 0,
};

function fmtDate(d) {
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
}

function getRange(period) {
    const today = new Date(); today.setHours(0,0,0,0);
    if (period === 'today')     return {start:fmtDate(today), end:fmtDate(today)};
    if (period === 'yesterday') { const y=new Date(today); y.setDate(y.getDate()-1); return {start:fmtDate(y),end:fmtDate(y)}; }
    if (period === 'this_week') { const dow=(today.getDay()+6)%7; const mon=new Date(today); mon.setDate(today.getDate()-dow); const sun=new Date(mon); sun.setDate(mon.getDate()+6); return {start:fmtDate(mon),end:fmtDate(sun)}; }
    if (period === 'last_week') { const dow=(today.getDay()+6)%7; const tm=new Date(today); tm.setDate(today.getDate()-dow); const lm=new Date(tm); lm.setDate(tm.getDate()-7); const ls=new Date(tm); ls.setDate(tm.getDate()-1); return {start:fmtDate(lm),end:fmtDate(ls)}; }
}

function datesBetween(start, end) {
    const arr=[]; const cur=new Date(start+'T00:00:00'); const endD=new Date(end+'T00:00:00');
    while(cur<=endD){arr.push(fmtDate(cur));cur.setDate(cur.getDate()+1);}
    return arr;
}

function fmtRangeLabel(start,end) {
    const opts={day:'numeric',month:'short'};
    const s=new Date(start+'T00:00:00').toLocaleDateString('id-ID',opts);
    const e=new Date(end+'T00:00:00').toLocaleDateString('id-ID',opts);
    return start===end?s:s+' – '+e;
}

function longLabel(dateStr) {
    const d=new Date(dateStr+'T00:00:00');
    return d.toLocaleDateString('id-ID',{weekday:'long',day:'numeric',month:'long'});
}

function clamp(v,mn,mx){return Math.min(Math.max(v,mn),mx)}
function pct(v,t){if(!t||t<=0)return 0;return clamp(Math.round(v/t*100),0,100)}
function fmt(n,dec=0){return (parseFloat(n)||0).toFixed(dec)}

// FIX: Helper tunggal untuk ambil tanggal dari food item
function getFoodDate(f) {
    const d = f.log_date || (f.created_at ? f.created_at.split(' ')[0] : '') || '';
    return /^\d{4}-\d{2}-\d{2}$/.test(d) ? d : '';
}

async function apiFetch(url) {
    try { const r=await fetch(url); const d=await r.json(); return d.success?d:null; } catch(e){return null;}
}
async function fetchWeeklyMeals(s,e){const d=await apiFetch(`../api/report_weekly.php?start=${s}&end=${e}&group_meal=1`);return d?(d.logs||[]):[];}
async function fetchFoodsInRange(s,e){const d=await apiFetch(`../api/dietary_log_items_range.php?start=${s}&end=${e}`);return d?(d.items||[]):[];}
async function fetchTargets(date){const d=await apiFetch(`../api/report_data.php?date=${date}`);return d?d.targets:null;}

function getMealsByDate(dateStr) {
    const meals={pagi:0,siang:0,malam:0,snack:0};
    S.weeklyLogs.forEach(l=>{if(l.log_date===dateStr&&l.meal_type)meals[l.meal_type]=parseFloat(l.total_calories)||0;});
    return meals;
}

function getMacrosByDate(dateStr) {
    const m={protein:0,carbs:0,fat:0};
    S.weeklyLogs.forEach(l=>{
        if(l.log_date!==dateStr)return;
        m.protein+=parseFloat(l.total_protein||l.protein||0);
        m.carbs+=parseFloat(l.total_carbs||l.carbs||0);
        m.fat+=parseFloat(l.total_fat||l.fat||0);
    });
    return m;
}

function renderKpiSummary(dateStr) {
    if (dateStr) {
        const meals=getMealsByDate(dateStr);
        const total=meals.pagi+meals.siang+meals.malam+meals.snack;
        const d=new Date(dateStr+'T00:00:00');
        const dayName=d.toLocaleDateString('id-ID',{weekday:'long'});
        const dateNum=d.toLocaleDateString('id-ID',{day:'numeric',month:'long'});
        const achPct=S.goalCal>0?Math.round((total/S.goalCal)*100):0;
        document.getElementById('kpiWeekLabel').textContent=(dayName+', '+dateNum).toUpperCase();
        document.getElementById('kpiWeekLabel').style.cssText='font-size:11px;font-weight:700;color:rgba(255,255,255,0.85);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:6px';
        document.getElementById('kpiBig').innerHTML=`${Math.round(total).toLocaleString('id-ID')} <span>kcal</span>`;
        document.getElementById('kpiSubLabel1').textContent='Tercapai';
        document.getElementById('kpiSub1').textContent=achPct+'%';
        document.getElementById('kpiSubLabel2').textContent='Target/Hari';
        document.getElementById('kpiSub2').textContent=Math.round(S.goalCal).toLocaleString('id-ID')+' kcal';
    } else {
        const logs=S.weeklyLogs;
        const total=logs.reduce((s,l)=>s+(parseFloat(l.total_calories)||0),0);
        const activeDays=new Set(logs.map(l=>l.log_date)).size;
        const avg=activeDays>0?Math.round(total/activeDays):0;
        const weeklyTarget=Math.round(S.goalCal*7);
        const periodLabels={this_week:'Minggu Ini',last_week:'Minggu Lalu',today:'Hari Ini',yesterday:'Kemarin'};
        document.getElementById('kpiWeekLabel').textContent=periodLabels[S.period]||'Periode Ini';
        document.getElementById('kpiWeekLabel').style.cssText='color:rgba(255,255,255,1)';
        document.getElementById('kpiBig').innerHTML=`${Math.round(total).toLocaleString('id-ID')} <span>kcal</span>`;
        document.getElementById('kpiSubLabel1').textContent='Rata-rata/hari';
        document.getElementById('kpiSub1').textContent=avg.toLocaleString('id-ID')+' kcal';
        document.getElementById('kpiSubLabel2').textContent='Target/Minggu';
        document.getElementById('kpiSub2').textContent=weeklyTarget.toLocaleString('id-ID')+' kcal';
    }
}

function renderChart() {
    const area=document.getElementById('chartArea');
    const goalLine=document.getElementById('goalLine');
    area.innerHTML='';
    area.appendChild(goalLine);

    const range=getRange(S.period);
    const dates=datesBetween(range.start,range.end);

    const byDate={};
    dates.forEach(d=>{byDate[d]={pagi:0,siang:0,malam:0,snack:0};});
    S.weeklyLogs.forEach(l=>{if(byDate[l.log_date]&&l.meal_type)byDate[l.log_date][l.meal_type]=parseFloat(l.total_calories)||0;});

    const dayTotals=dates.map(d=>{const m=byDate[d];return(m.pagi||0)+(m.siang||0)+(m.malam||0)+(m.snack||0);});
    const maxCal=Math.max(...dayTotals,S.goalCal,1);

    const CHART_H=140;
    const LABEL_H=25; // tinggi label tanggal + gap, bar tidak tumbuh di sini

    const glPx=Math.round((S.goalCal/maxCal)*(CHART_H-LABEL_H))+LABEL_H;
    goalLine.style.bottom=glPx+'px';
    goalLine.style.top='auto';

    dates.forEach((dateStr,i)=>{
        const meals=byDate[dateStr];
        const total=dayTotals[i];
        const isSelected=S.selectedDate===dateStr;
        const hasSel=S.selectedDate!==null;

        const col=document.createElement('div');
        col.className='bar-col'+(isSelected?' selected':'')+(hasSel&&!isSelected?' dimmed':'');
        col.dataset.date=dateStr;

        if(total<=0){
            const empty=document.createElement('div');
            empty.className='bar-empty';
            col.appendChild(empty);
        } else {
            const BAR_H=CHART_H-LABEL_H;
            const stackH=clamp(Math.round((total/maxCal)*BAR_H),6,BAR_H);
            const stack=document.createElement('div');
            stack.className='bar-stack';
            stack.style.height=stackH+'px';
            MEAL_ORDER.forEach(meal=>{
                const cal=meals[meal]||0;
                if(cal<=0)return;
                const seg=document.createElement('div');
                seg.className='bar-segment '+meal;
                seg.style.height=clamp(Math.round((cal/total)*stackH),2,stackH)+'px';
                stack.appendChild(seg);
            });
            col.appendChild(stack);
        }

        const d=new Date(dateStr+'T00:00:00');
        const lbl=document.createElement('div');
        lbl.className='bar-day-label';
        lbl.innerHTML=`<span class="lbl-date">${d.getDate()}</span><span class="lbl-day">${DAY_SHORT[d.getDay()]}</span>`;
        col.appendChild(lbl);

        col.addEventListener('click',()=>handleBarClick(dateStr));
        area.appendChild(col);
    });
}

function handleBarClick(dateStr) {
    if(S.selectedDate===dateStr){
        S.selectedDate=null;
        renderChart();
        renderKpiSummary(null);
        renderDistribusi(null);
        renderFoodsList('cal',S.allFoods,'Makanan Dikonsumsi');
    } else {
        S.selectedDate=dateStr;
        renderChart();
        renderKpiSummary(dateStr);
        renderDistribusi(dateStr);
        const dayFoods=filterFoodsByDate(S.allFoods,dateStr);
        renderFoodsList('cal',dayFoods,'Makanan Dikonsumsi');
    }
}

function renderDistribusi(dateStr) {
    let meals;
    if(dateStr){
        meals=getMealsByDate(dateStr);
        const d=new Date(dateStr+'T00:00:00');
        document.getElementById('distribusiTitle').textContent='Distribusi Kalori';
    } else {
        meals={pagi:0,siang:0,malam:0,snack:0};
        S.weeklyLogs.forEach(l=>{
            if(l.meal_type && meals.hasOwnProperty(l.meal_type))
                meals[l.meal_type]+=(parseFloat(l.total_calories)||0);
        });
        document.getElementById('distribusiTitle').textContent='Distribusi Kalori';
    }

    const total=meals.pagi+meals.siang+meals.malam+meals.snack;
    let html='';
    MEAL_ORDER.forEach(meal=>{
        const cal=meals[meal]||0;
        const p=total>0?Math.round((cal/total)*100):0;
        html+=`<div class="dist-row">
            <div class="dist-dot" style="background:${MEAL_COLORS[meal]}"></div>
            <div class="dist-label">${MEAL_LABELS[meal]}</div>
            <div class="dist-track"><div class="dist-fill" style="background:${MEAL_COLORS[meal]};width:${p}%"></div></div>
            <div class="dist-pct">${p}%</div>
            <div class="dist-kcal">${Math.round(cal).toLocaleString('id-ID')} kcal</div>
        </div>`;
    });
    document.getElementById('distribusiRows').innerHTML=html;
}

// FIX: Pakai getFoodDate() agar format selalu konsisten
function filterFoodsByDate(foods, dateStr) {
    return foods.filter(f => getFoodDate(f) === dateStr);
}

function deduplicateFoods(foods){
    const map={};
    foods.forEach(f=>{
        const date=getFoodDate(f); // FIX: pakai getFoodDate()
        const qtype=f.quantity_type||'portion';
        const key=`${f.food_id||f.food_name}__${date}__${qtype}__${f.meal_type}`;
        if(!map[key]){
            map[key]=Object.assign({},f,{_qty:parseFloat(f.quantity||1)});
        } else {
            map[key]._qty          +=parseFloat(f.quantity||1);
            map[key].calories       =(parseFloat(map[key].calories)||0)+(parseFloat(f.calories)||0);
            map[key].protein        =(parseFloat(map[key].protein)||0)+(parseFloat(f.protein)||0);
            map[key].carbs          =(parseFloat(map[key].carbs)||0)+(parseFloat(f.carbs)||0);
            map[key].fat            =(parseFloat(map[key].fat)||0)+(parseFloat(f.fat)||0);
        }
    });
    return Object.values(map);
}

function renderFoodsList(mode, foods, heading) {
    const headingText = heading || 'Makanan Dikonsumsi';
    const container = document.getElementById('foodsListCal');

    if (!foods || foods.length === 0) {
        container.innerHTML = '<div class="food-empty">Belum ada makanan dicatat pada periode ini.</div>';
        return;
    }

    const isWeeklyView = !S.selectedDate;

    if (isWeeklyView) {
        // ── MODE MINGGU: gabungkan semua makanan yang sama (nama + unit) ──
        const mergeMap = {};
        foods.forEach(f => {
            const qtype = f.quantity_type || 'portion';
            const key   = `${(f.food_name||'').toLowerCase()}__${qtype}`;
            if (!mergeMap[key]) {
                mergeMap[key] = Object.assign({}, f, {
                    _qty: parseFloat(f.quantity || 1)
                });
            } else {
                mergeMap[key]._qty      += parseFloat(f.quantity || 1);
                mergeMap[key].calories  += parseFloat(f.calories || 0);
                mergeMap[key].protein   += parseFloat(f.protein  || 0);
                mergeMap[key].carbs     += parseFloat(f.carbs    || 0);
                mergeMap[key].fat       += parseFloat(f.fat      || 0);
            }
        });
        const merged = Object.values(mergeMap);
        const weeklyTotal = merged.reduce((s, f) => s + (parseFloat(f.calories) || 0), 0);
        let html = '<div style="background:#FFFFFF;border-radius:20px;padding:4px 14px 14px;box-shadow:0 1px 8px rgba(0,0,0,0.04);margin-bottom:4px">';
        html += `<div style="padding:8px 0 10px;display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:11px;font-weight:700;color:#AAAAAA;text-transform:uppercase;letter-spacing:0.6px">${headingText}</span>
            <span style="font-size:11px;font-weight:600;color:#4A8C5C;background:#EEF4F0;padding:3px 9px;border-radius:20px">${merged.length} item</span>
        </div>`;
        html += `<div style="border-top:1.5px solid #F0EDE8;margin:0 -18px"></div>`;
        html += `<div class="food-table-head">
            <div class="food-th">Makanan</div>
            <div class="food-th">Porsi</div>
            <div class="food-th">Kalori</div>
        </div>`;

        merged.forEach(f => {
            const imgEl = f.image
                ? `<img src="${f.image}" class="food-row-img" alt="${f.food_name}" onerror="this.outerHTML='<div class=\\'food-row-placeholder\\'>🍽️</div>'">`
                : `<div class="food-row-placeholder">🍽️</div>`;
            const qty  = f._qty;
            const qtype = f.quantity_type || 'portion';
            const portionText = qtype === 'gram'
                ? `${Math.round(qty)} g`
                : `${qty % 1 === 0 ? qty : qty.toFixed(1)} Porsi`;
            const meal  = (f.meal_type || 'snack').toLowerCase();
            const rightEl = `<div class="food-row-cal">${Math.round(f.calories || 0).toLocaleString('id-ID')}<span>kcal</span></div>`;

            html += `<div class="food-row">
                <div class="food-row-name">
                    ${imgEl}
                    <div class="food-row-text">
                        <div class="food-name-text">${f.food_name}</div>
                        <span class="food-meal-chip ${meal}">${MEAL_LABELS[meal] || meal}</span>
                    </div>
                </div>
                <div class="food-row-portion">${portionText}</div>
                ${rightEl}
            </div>`;
        });

        html += `<div style="border-top:1.5px solid #F0EDE8;margin:8px -14px 0;padding:10px 14px 0;display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:12px;font-weight:700;color:#AAAAAA;text-transform:uppercase;letter-spacing:0.4px">Total</span>
            <span style="font-size:15px;font-weight:700;color:#4A8C5C">${Math.round(weeklyTotal).toLocaleString('id-ID')} kcal</span>
        </div>`;
        html += '</div>';
        container.innerHTML = html;

    } else {
        // ── MODE HARI (klik grafik): tampilkan dalam card, grouping per meal ──
        const deduped = deduplicateFoods(foods);
        const byMeal = { pagi: [], siang: [], malam: [], snack: [] };
        deduped.forEach(f => {
            const mt = (f.meal_type || 'snack').toLowerCase();
            (byMeal[mt] || byMeal.snack).push(f);
        });

        const dayTotal = deduped.reduce((s, f) => s + (parseFloat(f.calories) || 0), 0);
        let html = '<div style="background:#FFFFFF;border-radius:20px;padding:4px 14px 14px;box-shadow:0 1px 8px rgba(0,0,0,0.04);margin-bottom:4px">';
        html += `<div style="padding:14px 0 10px;display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:11px;font-weight:700;color:#AAAAAA;text-transform:uppercase;letter-spacing:0.6px">${headingText}</span>
            <span style="font-size:11px;font-weight:600;color:#4A8C5C;background:#EEF4F0;padding:3px 9px;border-radius:20px">${deduped.length} item</span>
        </div>`;
        html += `<div style="border-top:1.5px solid #F0EDE8;margin:0 -14px"></div>`;
        html += `<div class="food-table-head">
            <div class="food-th">Makanan</div>
            <div class="food-th">Porsi</div>
            <div class="food-th">Kalori</div>
        </div>`;

        let hasAny = false;
        MEAL_ORDER.forEach(meal => {
            const items = byMeal[meal];
            if (!items || items.length === 0) return;
            hasAny = true;
            items.forEach(f => {
                const imgEl = f.image
                    ? `<img src="${f.image}" class="food-row-img" alt="${f.food_name}" onerror="this.outerHTML='<div class=\\'food-row-placeholder\\'>🍽️</div>'">`
                    : `<div class="food-row-placeholder">🍽️</div>`;
                const qty  = parseFloat(f._qty || f.quantity || 1);
                const qtype = f.quantity_type || 'portion';
                const portionText = qtype === 'gram'
                    ? `${Math.round(qty)} g`
                    : `${qty % 1 === 0 ? qty : qty.toFixed(1)} Porsi`;
                const rightEl = `<div class="food-row-cal">${Math.round(f.calories || 0).toLocaleString('id-ID')}<span>kcal</span></div>`;

                html += `<div class="food-row">
                    <div class="food-row-name">
                        ${imgEl}
                        <div class="food-row-text">
                            <div class="food-name-text">${f.food_name}</div>
                            <span class="food-meal-chip ${meal}">${MEAL_LABELS[meal]}</span>
                        </div>
                    </div>
                    <div class="food-row-portion">${portionText}</div>
                    ${rightEl}
                </div>`;
            });
        });

        if (!hasAny) html += `<div style="font-size:12px;color:#AAAAAA;padding:10px 4px">Tidak ada data.</div>`;
        html += `<div style="border-top:1.5px solid #F0EDE8;margin:8px -14px 0;padding:10px 14px 0;display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:12px;font-weight:700;color:#AAAAAA;text-transform:uppercase;letter-spacing:0.4px">Total</span>
            <span style="font-size:15px;font-weight:700;color:#4A8C5C">${Math.round(dayTotal).toLocaleString('id-ID')} kcal</span>
        </div>`;
        html += '</div>';
        container.innerHTML = html;
    }
}

function renderFoodsListNutr(foods) {
    const container = document.getElementById('foodsListNutr');

    if (!foods || foods.length === 0) {
        container.innerHTML = '<div class="food-empty">Belum ada makanan dicatat pada periode ini.</div>';
        return;
    }

    const isWeeklyView = !S.nutrSelectedDate;
    let rows;
    let totals = {protein:0,carbs:0,fat:0};

    if (isWeeklyView) {
        const mergeMap = {};
        foods.forEach(f => {
            const qtype = f.quantity_type || 'portion';
            const key   = `${(f.food_name||'').toLowerCase()}__${qtype}`;
            if (!mergeMap[key]) {
                mergeMap[key] = Object.assign({}, f, { _qty: parseFloat(f.quantity || 1) });
            } else {
                mergeMap[key]._qty    += parseFloat(f.quantity || 1);
                mergeMap[key].protein += parseFloat(f.protein || 0);
                mergeMap[key].carbs   += parseFloat(f.carbs   || 0);
                mergeMap[key].fat     += parseFloat(f.fat     || 0);
            }
        });
        rows = Object.values(mergeMap);
    } else {
        rows = deduplicateFoods(foods);
    }

    rows.forEach(f => {
        totals.protein += parseFloat(f.protein || 0);
        totals.carbs   += parseFloat(f.carbs   || 0);
        totals.fat     += parseFloat(f.fat     || 0);
    });

    let html = '<div style="background:#FFFFFF;border-radius:20px;padding:10px 14px 14px;box-shadow:0 1px 8px rgba(0,0,0,0.04);margin-bottom:4px">';
    html += `<div class="food-table-head-nutr">
        <div class="food-th">Makanan</div>
        <div class="food-th">Porsi</div>
        <div class="food-th">Protein (g)</div>
        <div class="food-th">Karbo (g)</div>
        <div class="food-th">Lemak (g)</div>
    </div>`;

    rows.forEach(f => {
        const qty   = parseFloat(f._qty || f.quantity || 1);
        const qtype = f.quantity_type || 'portion';
        const qtyDisp = qty % 1 === 0 ? qty : qty.toFixed(1);
        const portionText = qtype === 'gram' ? `${Math.round(qty)} g` : `${qtyDisp}x`;

        html += `<div class="food-row-nutr">
            <div class="food-row-text">
                <div class="food-name-text">${f.food_name}</div>
            </div>
            <div class="food-row-portion">${portionText}</div>
            <div class="food-row-num">${fmt(f.protein,1)}</div>
            <div class="food-row-num">${fmt(f.carbs,1)}</div>
            <div class="food-row-num">${fmt(f.fat,1)}</div>
        </div>`;
    });

    html += `<div class="food-row-nutr food-row-nutr-total">
        <div style="font-size:11px;font-weight:700;color:#AAAAAA;text-transform:uppercase;letter-spacing:0.4px">Total</div>
        <div></div>
        <div class="food-row-num food-row-num-total">${fmt(totals.protein,1)}</div>
        <div class="food-row-num food-row-num-total">${fmt(totals.carbs,1)}</div>
        <div class="food-row-num food-row-num-total">${fmt(totals.fat,1)}</div>
    </div>`;
    html += '</div>';
    container.innerHTML = html;
}

function renderChartNutr(){
    const area=document.getElementById('chartAreaNutr');
    area.innerHTML='';

    const range=getRange(S.period);
    const dates=datesBetween(range.start,range.end);
    const targets=S.targets;
    const pTarget=targets?(parseFloat(targets.target_protein)||0):0;
    const cTarget=targets?(parseFloat(targets.target_carbs)||0):0;
    const fTarget=targets?(parseFloat(targets.target_fat)||0):0;
    const dayTarget=pTarget+cTarget+fTarget;

    const byDate={};
    dates.forEach(d=>{byDate[d]={protein:0,carbs:0,fat:0};});
    S.weeklyLogs.forEach(l=>{
        if(!byDate[l.log_date])return;
        byDate[l.log_date].protein+=parseFloat(l.total_protein||l.protein||0);
        byDate[l.log_date].carbs+=parseFloat(l.total_carbs||l.carbs||0);
        byDate[l.log_date].fat+=parseFloat(l.total_fat||l.fat||0);
    });

    const dayTotals=dates.map(d=>{const m=byDate[d];return(m.protein||0)+(m.carbs||0)+(m.fat||0);});
    const maxVal=Math.max(...dayTotals,dayTarget,1);

    const CHART_H=140;
    const LABEL_H=25;
    const BAR_H=CHART_H-LABEL_H;

    dates.forEach((dateStr,i)=>{
        const m=byDate[dateStr];
        const total=dayTotals[i];
        const isSelected=S.nutrSelectedDate===dateStr;
        const hasSel=S.nutrSelectedDate!==null;

        const col=document.createElement('div');
        col.className='bar-col'+(isSelected?' selected':'')+(hasSel&&!isSelected?' dimmed':'');
        col.dataset.date=dateStr;

        if(total<=0){
            const empty=document.createElement('div');
            empty.className='bar-empty';
            col.appendChild(empty);
        } else {
            const stackH=clamp(Math.round((total/maxVal)*BAR_H),6,BAR_H);
            const stack=document.createElement('div');
            stack.className='bar-stack';
            stack.style.height=stackH+'px';
            ['protein','carbs','fat'].forEach(key=>{
                const val=m[key]||0;
                if(val<=0)return;
                const seg=document.createElement('div');
                seg.className='bar-segment '+key;
                seg.style.height=clamp(Math.round((val/total)*stackH),2,stackH)+'px';
                stack.appendChild(seg);
            });
            col.appendChild(stack);
        }

        const d=new Date(dateStr+'T00:00:00');
        const lbl=document.createElement('div');
        lbl.className='bar-day-label';
        lbl.innerHTML=`<span class="lbl-date">${d.getDate()}</span><span class="lbl-day">${DAY_SHORT[d.getDay()]}</span>`;
        col.appendChild(lbl);

        col.addEventListener('click',()=>handleBarClickNutr(dateStr));
        area.appendChild(col);
    });
}

function handleBarClickNutr(dateStr){
    if(S.nutrSelectedDate===dateStr){
        S.nutrSelectedDate=null;
    } else {
        S.nutrSelectedDate=dateStr;
    }
    renderNutrientsTab();
}

function renderDistribusiNutr(dateStr,goalDays){
    let m;
    if(dateStr){
        m=getMacrosByDate(dateStr);
    } else {
        m={protein:0,carbs:0,fat:0};
        S.weeklyLogs.forEach(l=>{
            m.protein+=parseFloat(l.total_protein||l.protein||0);
            m.carbs+=parseFloat(l.total_carbs||l.carbs||0);
            m.fat+=parseFloat(l.total_fat||l.fat||0);
        });
    }
    const targets=S.targets||{};
    const goals={
        protein:(parseFloat(targets.target_protein)||0)*goalDays,
        carbs:(parseFloat(targets.target_carbs)||0)*goalDays,
        fat:(parseFloat(targets.target_fat)||0)*goalDays,
    };
    const NUTR_COLORS={protein:'#7FA8D9',carbs:'#E8C25E',fat:'#E8A86B'};
    const NUTR_LABELS={protein:'Protein',carbs:'Karbo',fat:'Lemak'};
    let html='';
    ['protein','carbs','fat'].forEach(key=>{
        const val=m[key]||0;
        const goal=goals[key];
        const p=pct(val,goal);
        html+=`<div class="dist-row-nutr">
            <div class="dist-dot" style="background:${NUTR_COLORS[key]}"></div>
            <div class="dist-label">${NUTR_LABELS[key]}</div>
            <div class="dist-track-nutr"><div class="dist-fill" style="background:${NUTR_COLORS[key]};width:${p}%"></div></div>
            <div class="dist-pct-nutr">${p}%</div>
            <div class="dist-amount-nutr"><b>${fmt(val,0)}</b> / ${fmt(goal,0)} g</div>
        </div>`;
    });
    document.getElementById('distribusiRowsNutr').innerHTML=html;
}

function renderNutrientsTab(){
    const targets=S.targets;
    if(!targets){document.getElementById('statTableBody').innerHTML='<div style="font-size:12px;color:#AAAAAA;padding:12px 0">Data target tidak ditemukan.</div>';return;}

    const dateStr=S.nutrSelectedDate;
    const range=getRange(S.period);
    const days=datesBetween(range.start,range.end).length;
    // Goal multiplier: 1 day if a date is selected, otherwise the full period length
    const goalDays=dateStr?1:days;

    renderChartNutr();
    renderDistribusiNutr(dateStr,goalDays);

    // Totals: either single-day actuals, or whole-period actuals
    const totals={calories:0,protein:0,carbs:0,fat:0,sugar:0,sodium:0,fiber:0,saturated_fat:0,cholesterol:0,omega_3:0};
    const sourceLogs=dateStr?S.weeklyLogs.filter(l=>l.log_date===dateStr):S.weeklyLogs;
    sourceLogs.forEach(l=>{Object.keys(totals).forEach(k=>{totals[k]+=parseFloat(l['total_'+k]||l[k]||0);});});

    const NUTR_ROWS=[
        {key:'calories',label:'Calories (kcal)',tKey:'target_calories'},
        {key:'protein',label:'Protein (g)',tKey:'target_protein'},
        {key:'carbs',label:'Karbo (g)',tKey:'target_carbs'},
        {key:'fat',label:'Lemak (g)',tKey:'target_fat'},
        {key:'fiber',label:'Serat (g)',tKey:'target_fiber'},
        {key:'sugar',label:'Gula (g)',tKey:'target_sugar'},
        {key:'sodium',label:'Sodium (mg)',tKey:'target_sodium'},
        {key:'saturated_fat',label:'Lemak Jenuh (g)',tKey:'target_saturated_fat'},
        {key:'cholesterol',label:'Kolesterol (mg)',tKey:'target_cholesterol'},
        {key:'omega_3',label:'Omega-3 (mg)',tKey:'target_omega_3'},
    ];

    function buildRow(r){
        const consumed=totals[r.key]||0;
        const dailyGoal=parseFloat(targets[r.tKey])||0;
        const periodGoal=dailyGoal*goalDays;
        const remaining=periodGoal-consumed;
        const rawPct=periodGoal>0?(consumed/periodGoal)*100:0;
        const isOver=rawPct>100;
        let miniClass=isOver?'red':rawPct>=75?'green':rawPct>=25?'yellow':'grey';
        const remAbs=Math.abs(remaining);
        const remText=isOver?`+${fmt(remAbs,0)}`:`-${fmt(remAbs,0)}`;
        const remClass=isOver?'over':'neutral';
        return `<div class="stat-row">
            <div class="stat-nutrient-wrap">
                <div class="stat-nutrient">${r.label}</div>
                <div class="stat-mini-bar"><div class="stat-mini-fill ${miniClass}" style="width:${Math.min(rawPct,100)}%"></div></div>
            </div>
            <div class="stat-td">${fmt(consumed,0)}</div>
            <div class="stat-td">${fmt(periodGoal,0)}</div>
            <div class="stat-remaining ${remClass}">${remText}</div>
        </div>`;
    }

    document.getElementById('statTableBody').innerHTML=NUTR_ROWS.map(buildRow).join('');

    if(dateStr){
        const dayFoods=filterFoodsByDate(S.allFoods,dateStr);
        renderFoodsListNutr(dayFoods);
    } else {
        renderFoodsListNutr(S.allFoods);
    }
}

async function loadPeriod(period){
    S.period=period;
    S.selectedDate=null;
    S.nutrSelectedDate=null;
    const range=getRange(period);
    document.getElementById('dateRangeLabel').textContent=fmtRangeLabel(range.start,range.end);
    document.getElementById('foodsListCal').innerHTML='<div class="food-empty" style="background:transparent">Memuat...</div>';

    const [logs,foods,targets]=await Promise.all([
        fetchWeeklyMeals(range.start,range.end),
        fetchFoodsInRange(range.start,range.end),
        fetchTargets(range.end),
    ]);
    S.weeklyLogs=logs; S.allFoods=foods; S.targets=targets;
    S.goalCal=targets?(parseFloat(targets.target_calories)||0):0;

    renderKpiSummary(null);
    renderChart();
    renderDistribusi(null);
    renderFoodsList('cal',foods,'Makanan Dikonsumsi');
    renderNutrientsTab();
}

document.getElementById('periodSelect').addEventListener('change',function(){loadPeriod(this.value);});

document.querySelectorAll('.sub-tab').forEach(tab=>{
    tab.addEventListener('click',()=>{
        document.querySelectorAll('.sub-tab').forEach(t=>t.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('panel-'+tab.dataset.tab).classList.add('active');
        S.activeTab=tab.dataset.tab;
    });
});

function openGoals(){window.location.href='profile.php?tab=goals';}

loadPeriod('this_week');

</script>
</body>
</html>