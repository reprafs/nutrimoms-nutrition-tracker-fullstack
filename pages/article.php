<?php
// pages/article.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
require_once '../config/db.php';
$db     = getDB();
$userId = (int)$_SESSION['user_id'];

$allowedCategories = ['Nutrisi Anak', 'Diet Dewasa', 'Resep Sehat', 'Info Gizi'];
$activeCategory    = (isset($_GET['cat']) && in_array($_GET['cat'], $allowedCategories)) ? $_GET['cat'] : '';
$searchQuery       = isset($_GET['q']) ? trim($_GET['q']) : '';

$params = [':uid' => $userId];
$sql = "SELECT a.id, a.title, a.slug, a.excerpt, a.category, a.image_url,
               a.source_name, a.source_logo, a.created_at,
               IF(sa.id IS NOT NULL, 1, 0) AS is_saved
        FROM   articles a
        LEFT JOIN saved_articles sa ON sa.article_id = a.id AND sa.user_id = :uid
        WHERE  a.is_published = 1";

if ($activeCategory !== '') { $sql .= " AND a.category = :cat"; $params[':cat'] = $activeCategory; }
if ($searchQuery !== '')    { $sql .= " AND (a.title LIKE :q OR a.excerpt LIKE :q2)"; $params[':q'] = '%'.$searchQuery.'%'; $params[':q2'] = '%'.$searchQuery.'%'; }
$sql .= " ORDER BY a.created_at DESC LIMIT 50";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

function timeAgo(string $dt): string {
    $d = time() - strtotime($dt);
    if ($d < 60)      return 'Baru saja';
    if ($d < 3600)    return (int)($d/60).' mnt lalu';
    if ($d < 86400)   return (int)($d/3600).' jam lalu';
    if ($d < 2592000) return (int)($d/86400).' hari lalu';
    return date('d M Y', strtotime($dt));
}
function srcInitial(string $n): string { $w = explode(' ', trim($n)); return mb_strtoupper(mb_substr($w[0],0,1)); }

$catMeta = [
    'Nutrisi Anak' => ['bg'=>'#E8F5E9','color'=>'#2E7D32','icon'=>'../assets/icon/nutrisianak.png'],
    'Diet Dewasa'  => ['bg'=>'#FFF3E0','color'=>'#E65100','icon'=>'../assets/icon/dietdewasa.png'],
    'Resep Sehat'  => ['bg'=>'#FFFDE7','color'=>'#F57F17','icon'=>'../assets/icon/resepsehat.png'],
    'Info Gizi'    => ['bg'=>'#E3F2FD','color'=>'#1565C0','icon'=>'../assets/icon/infogizi.png'],
];

// Banner iklan langganan
$banners = [
    ['img' => '../assets/img/banner_premium1.jpeg', 'alt' => 'NutriMoms Premium'],
    ['img' => '../assets/img/banner_premium2.jpeg', 'alt' => 'Fitur Lengkap Premium'],
    ['img' => '../assets/img/banner_premium3.jpeg', 'alt' => 'Langganan Sekarang'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=375, user-scalable=no">
<link rel="manifest" href="/nutrimoms/manifest.json">
<title>Artikel – NutriMoms</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700;9..40,800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{
    --g:#5A9E6A;--g-lt:#EBF4ED;--g-mid:#C8E0CC;--g-dk:#3D7A4E;
    --ink:#1A1D1F;--ink-2:#444950;--ink-3:#9098A1;
    --bg:#EAF1E8;--bg-2:#EDEAE4;--white:#FFFFFF;
    --r:18px;--r-sm:12px;
    --sh:0 2px 14px rgba(0,0,0,0.07);--sh-up:0 8px 32px rgba(0,0,0,0.13);
}
html{background:#DCE6D9;}
body{
    background:var(--bg);font-family:'DM Sans',sans-serif;
    color:var(--ink);display:flex;justify-content:center;
    -webkit-font-smoothing:antialiased;
}
.shell{width:375px;min-height:100vh;background:var(--bg);position:relative;}

/* ── HEADER ── */
.header{
    position:fixed;top:0;left:50%;transform:translateX(-50%);
    width:375px;z-index:50;background:var(--bg);
    padding:16px 20px 14px;border-bottom:1px solid var(--bg-2);
}
.header-row{display:flex;align-items:center;justify-content:space-between;}
.header-title{font-size:26px;font-weight:800;color:var(--ink);letter-spacing:-0.5px;}
.header-icon-btn{
    width:40px;height:40px;border-radius:50%;
    background:var(--white);border:none;
    display:flex;align-items:center;justify-content:center;
    cursor:pointer;box-shadow:var(--sh);text-decoration:none;
    transition:transform 0.15s;-webkit-tap-highlight-color:transparent;
}
.header-icon-btn:active{transform:scale(0.90);}
.header-icon-btn svg{width:20px;height:20px;color:var(--ink-2);}

/* ── CONTENT ── */
.content{padding-top:82px;padding-bottom:100px;}

/* ── SEARCH ── */
.search-section{padding:14px 20px 10px;}
.search-box{
    display:flex;align-items:center;gap:10px;
    background:var(--white);border-radius:99px;
    padding:12px 18px;box-shadow:var(--sh);
}
.search-box svg{width:18px;height:18px;color:var(--ink-3);flex-shrink:0;}
.search-box input{
    border:none;outline:none;background:transparent;
    font-size:14px;font-family:'DM Sans',sans-serif;
    color:var(--ink);flex:1;min-width:0;font-weight:500;
}
.search-box input::placeholder{color:var(--ink-3);}

/* ── CHIPS ── */
.chips-section{padding:0 20px 14px;}
.chips-row{display:flex;gap:8px;overflow-x:auto;scrollbar-width:none;}
.chips-row::-webkit-scrollbar{display:none;}
.chip{
    display:inline-flex;align-items:center;gap:6px;
    padding:8px 14px;border-radius:99px;
    font-size:13px;font-weight:700;white-space:nowrap;
    cursor:pointer;text-decoration:none;flex-shrink:0;
    background:var(--white);color:var(--ink-2);
    border:none;box-shadow:0 1px 6px rgba(0,0,0,0.07);
    font-family:'DM Sans',sans-serif;
    -webkit-tap-highlight-color:transparent;transition:all 0.15s;
}
.chip.active{background:var(--g);color:var(--white);box-shadow:0 4px 12px rgba(90,158,106,0.35);}
.chip:active{transform:scale(0.94);}
.chip-icon{width:16px;height:16px;object-fit:contain;display:block;flex-shrink:0;}

/* ── BANNER SLIDER ── */
.banner-section{
    width:375px;overflow:hidden;
    margin-bottom:14px;
    position:relative;
}
.banner-track{
    display:flex;
    transition:transform 0.4s cubic-bezier(0.4,0,0.2,1);
    will-change:transform;
}
.banner-slide{
    width:375px;flex-shrink:0;
    cursor:pointer;-webkit-tap-highlight-color:transparent;
}
.banner-slide img{
    width:375px;height:140px;
    object-fit:cover;display:block;
}
/* Dots indicator */
.banner-dots{
    display:flex;align-items:center;justify-content:center;
    gap:6px;padding:8px 0 4px;
}
.banner-dot{
    width:6px;height:6px;border-radius:50%;
    background:var(--g-mid);transition:all 0.25s;
}
.banner-dot.active{width:18px;border-radius:3px;background:var(--g);}

/* ── SECTION LABEL ── */
.sec-label{
    font-size:12px;font-weight:700;color:var(--ink-3);
    text-transform:uppercase;letter-spacing:0.06em;
    padding:0 20px 12px;
    display:flex;align-items:center;justify-content:space-between;
}
.sec-label span{color:var(--g-dk);}

/* ── CARD LIST ── */
.card-list{display:flex;flex-direction:column;gap:10px;padding:0 20px;}
.article-card{
    background:var(--white);border-radius:var(--r);
    padding:15px;box-shadow:var(--sh);border:1px solid var(--bg-2);
    cursor:pointer;-webkit-tap-highlight-color:transparent;transition:transform 0.13s;
}
.article-card:active{transform:scale(0.985);}

/* Card top */
.card-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:11px;}
.badge{
    display:inline-flex;align-items:center;gap:6px;
    padding:5px 11px;border-radius:99px;
    font-size:11.5px;font-weight:700;line-height:1;
}
.badge-icon{width:14px;height:14px;object-fit:contain;display:block;flex-shrink:0;}
.save-btn{
    width:34px;height:34px;border-radius:50%;border:none;
    background:var(--bg);display:flex;align-items:center;justify-content:center;
    cursor:pointer;-webkit-tap-highlight-color:transparent;
    transition:transform 0.15s;flex-shrink:0;
}
.save-btn:active{transform:scale(0.85);}
.save-btn svg{width:18px;height:18px;transition:all 0.2s;}
.save-btn.saved svg{color:#E53935;fill:#E53935;}
.save-btn:not(.saved) svg{color:var(--ink-3);fill:none;stroke:var(--ink-3);}

/* Card body */
.card-body{display:flex;gap:13px;align-items:flex-start;}
.card-text{flex:1;min-width:0;}
.card-title{
    font-size:15px;font-weight:700;color:var(--ink);
    line-height:1.4;margin-bottom:5px;
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
}
.card-excerpt{
    font-size:13px;color:var(--ink-3);line-height:1.5;font-weight:500;
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
}
.card-thumb{width:82px;height:82px;border-radius:var(--r-sm);object-fit:cover;flex-shrink:0;background:var(--g-lt);}
.card-thumb-placeholder{
    width:82px;height:82px;border-radius:var(--r-sm);
    background:var(--g-lt);display:flex;align-items:center;justify-content:center;
    flex-shrink:0;
}
.card-thumb-placeholder img{width:36px;height:36px;object-fit:contain;opacity:0.5;}

/* Card footer */
.card-footer{
    display:flex;align-items:center;justify-content:space-between;
    margin-top:13px;padding-top:11px;border-top:1px solid var(--bg);
}
.source-info{display:flex;align-items:center;gap:7px;}
.src-logo-initial{
    width:22px;height:22px;border-radius:50%;
    background:var(--g);color:var(--white);font-size:9px;font-weight:800;
    display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.src-logo-img{width:22px;height:22px;border-radius:50%;object-fit:cover;}
.src-name{font-size:12px;font-weight:600;color:var(--ink-3);max-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.card-time{font-size:12px;color:var(--ink-3);font-weight:500;}

/* ── EMPTY STATE ── */
.empty-state{
    display:flex;flex-direction:column;align-items:center;
    justify-content:center;padding:64px 24px;text-align:center;
}
.empty-state .e-icon{font-size:56px;margin-bottom:18px;}
.empty-state h3{font-size:18px;font-weight:800;color:var(--ink);margin-bottom:8px;}
.empty-state p{font-size:14px;color:var(--ink-3);line-height:1.6;font-weight:500;}

/* ── TOAST ── */
.toast{
    position:fixed;bottom:calc(90px + 12px);left:50%;
    transform:translateX(-50%) translateY(16px);
    background:var(--ink);color:var(--white);
    font-size:13px;font-weight:700;font-family:'DM Sans',sans-serif;
    padding:11px 22px;border-radius:99px;
    opacity:0;transition:all 0.25s;pointer-events:none;
    white-space:nowrap;z-index:200;box-shadow:var(--sh-up);
}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0);}

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
<div class="shell">

<!-- HEADER -->
<div class="header">
    <div class="header-row">
        <div class="header-title">Artikel</div>
        <a href="saved_articles.php" class="header-icon-btn" title="Tersimpan">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
            </svg>
        </a>
    </div>
</div>

<div class="content">

    <!-- SEARCH -->
    <form method="GET" action="" class="search-section">
        <?php if ($activeCategory): ?><input type="hidden" name="cat" value="<?=htmlspecialchars($activeCategory)?>"> <?php endif; ?>
        <div class="search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="text" name="q" placeholder="Cari artikel..." value="<?=htmlspecialchars($searchQuery)?>" autocomplete="off">
        </div>
    </form>

    <!-- CHIPS -->
    <div class="chips-section">
        <div class="chips-row">
            <a href="?<?=$searchQuery?'q='.urlencode($searchQuery):''?>" class="chip <?=$activeCategory===''?'active':''?>">Semua</a>
            <?php foreach($allowedCategories as $cat):
                $m   = $catMeta[$cat];
                $url = '?cat='.urlencode($cat).($searchQuery?'&q='.urlencode($searchQuery):'');
            ?>
            <a href="<?=$url?>" class="chip <?=$activeCategory===$cat?'active':''?>">
                <img src="<?=$m['icon']?>" class="chip-icon" alt="<?=$cat?>">
                <?=$cat?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- BANNER SLIDER -->
    <div class="banner-section" id="bannerSection">
        <div class="banner-track" id="bannerTrack">
            <?php foreach($banners as $b): ?>
            <div class="banner-slide">
                <img src="<?=$b['img']?>" alt="<?=$b['alt']?>" onerror="this.parentElement.style.display='none'">
            </div>
            <?php endforeach; ?>
        </div>
        <div class="banner-dots" id="bannerDots">
            <?php foreach($banners as $i => $b): ?>
            <div class="banner-dot <?=$i===0?'active':''?>" data-idx="<?=$i?>"></div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (!empty($articles)): ?>
    <!-- SECTION LABEL -->
    <div class="sec-label">
        <span><?=count($articles)?> Artikel</span>
        <?=$searchQuery?'<em style="color:var(--ink-3);font-weight:500;font-style:normal;font-size:11px;">untuk "'.htmlspecialchars($searchQuery).'"</em>':''?>
    </div>

    <!-- CARDS -->
    <div class="card-list">
    <?php foreach($articles as $a):
        $m     = $catMeta[$a['category']] ?? ['bg'=>'#EEE','color'=>'#555','icon'=>'../assets/icon/infogizi.png'];
        $src   = (!empty($a['source_name'])) ? $a['source_name'] : 'NutriMoms';
        $saved = (bool)$a['is_saved'];
        $url   = 'article_detail.php?slug='.urlencode($a['slug']);
    ?>
    <div class="article-card" onclick="location.href='<?=$url?>'">

        <div class="card-top">
            <span class="badge" style="background:<?=$m['bg']?>;color:<?=$m['color']?>">
                <img src="<?=$m['icon']?>" class="badge-icon" alt="">
                <?=htmlspecialchars($a['category'])?>
            </span>
            <button class="save-btn <?=$saved?'saved':''?>"
                    onclick="toggleSave(event,this,<?=$a['id']?>)"
                    title="<?=$saved?'Hapus simpanan':'Simpan'?>">
                <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                </svg>
            </button>
        </div>

        <div class="card-body">
            <div class="card-text">
                <div class="card-title"><?=htmlspecialchars($a['title'])?></div>
                <?php if(!empty($a['excerpt'])): ?>
                <div class="card-excerpt"><?=htmlspecialchars($a['excerpt'])?></div>
                <?php endif; ?>
            </div>
            <?php if(!empty($a['image_url'])): ?>
            <img class="card-thumb" src="<?=htmlspecialchars($a['image_url'])?>" alt="" loading="lazy" onerror="this.style.display='none'">
            <?php else: ?>
            <div class="card-thumb-placeholder">
                <img src="<?=$m['icon']?>" alt="">
            </div>
            <?php endif; ?>
        </div>

        <div class="card-footer">
            <div class="source-info">
                <?php if(!empty($a['source_logo'])): ?>
                <img src="<?=htmlspecialchars($a['source_logo'])?>" class="src-logo-img" alt="" onerror="this.style.display='none'">
                <?php else: ?>
                <div class="src-logo-initial"><?=srcInitial($src)?></div>
                <?php endif; ?>
                <span class="src-name"><?=htmlspecialchars($src)?></span>
            </div>
            <span class="card-time"><?=timeAgo($a['created_at'])?></span>
        </div>

    </div>
    <?php endforeach; ?>
    </div>

    <?php else: ?>
    <div class="empty-state">
        <div class="e-icon"><?=$searchQuery?'🔍':'📰'?></div>
        <h3><?=$searchQuery?'Tidak ditemukan':'Belum ada artikel'?></h3>
        <p><?=$searchQuery?'Coba kata kunci lain atau ubah kategori.':'Artikel akan segera hadir!'?></p>
    </div>
    <?php endif; ?>

</div><!-- /content -->

<!-- TOAST -->
<div class="toast" id="toast"></div>

<!-- NAVBAR -->
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
    <a href="article.php" class="nav-item active">
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

</div><!-- /shell -->

<script>
/* ── BANNER AUTO-SLIDE ── */
(function(){
    const track  = document.getElementById('bannerTrack');
    const dots   = document.querySelectorAll('.banner-dot');
    const total  = <?=count($banners)?>;
    let cur = 0, timer, startX = 0, dragging = false;

    function goTo(idx) {
        cur = (idx + total) % total;
        track.style.transform = `translateX(-${cur * 375}px)`;
        dots.forEach((d,i) => d.classList.toggle('active', i === cur));
    }

    function next() { goTo(cur + 1); }

    function startAuto() { timer = setInterval(next, 3500); }
    function stopAuto()  { clearInterval(timer); }

    dots.forEach(d => d.addEventListener('click', () => { stopAuto(); goTo(+d.dataset.idx); startAuto(); }));

    // Swipe touch
    track.addEventListener('touchstart', e => { startX = e.touches[0].clientX; stopAuto(); }, { passive:true });
    track.addEventListener('touchend',   e => {
        const dx = e.changedTouches[0].clientX - startX;
        if (Math.abs(dx) > 40) goTo(dx < 0 ? cur + 1 : cur - 1);
        startAuto();
    }, { passive:true });

    startAuto();
})();

/* ── SAVE / BOOKMARK ── */
function toggleSave(e, btn, articleId) {
    e.stopPropagation();
    btn.classList.toggle('saved');
    const fd = new FormData();
    fd.append('article_id', articleId);

    fetch('/nutrimoms/ajax/toggle_save_article.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(d => {
        if (d.success) showToast(d.saved ? '🔖 Artikel disimpan' : '✓ Dihapus dari simpanan');
        else { btn.classList.toggle('saved'); showToast('Gagal, coba lagi.'); }
    })
    .catch(() => { btn.classList.toggle('saved'); showToast('Koneksi bermasalah.'); });
}

/* ── TOAST ── */
let _tt;
function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg; t.classList.add('show');
    clearTimeout(_tt); _tt = setTimeout(() => t.classList.remove('show'), 2200);
}
</script>
</body>
</html>