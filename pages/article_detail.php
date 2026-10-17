<?php
// pages/article_detail.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
require_once '../config/db.php';
$db     = getDB();
$userId = (int)$_SESSION['user_id'];

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if (empty($slug)) { header("Location: article.php"); exit; }

// Ditambahkan a.content2 di dalam SELECT query
$stmt = $db->prepare(
    "SELECT a.id, a.title, a.slug, a.content, a.content2, a.excerpt, a.category,
            a.image_url, a.image1, a.image2, a.source_name, a.source_logo, a.created_at,
            IF(sa.id IS NOT NULL, 1, 0) AS is_saved
     FROM   articles a
     LEFT JOIN saved_articles sa ON sa.article_id = a.id AND sa.user_id = :uid
     WHERE  a.slug = :slug AND a.is_published = 1 LIMIT 1"
);
$stmt->execute([':uid' => $userId, ':slug' => $slug]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$article) { header("Location: article.php"); exit; }

$relStmt = $db->prepare(
    "SELECT id, title, slug, image_url, category, created_at
     FROM articles WHERE category = :cat AND id != :id AND is_published = 1
     ORDER BY created_at DESC LIMIT 3"
);
$relStmt->execute([':cat' => $article['category'], ':id' => $article['id']]);
$related = $relStmt->fetchAll(PDO::FETCH_ASSOC);

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
    'Nutrisi Anak' => ['bg'=>'#E8F5E9','color'=>'#2E7D32','icon'=>'🌱'],
    'Diet Dewasa'  => ['bg'=>'#FFF3E0','color'=>'#E65100','icon'=>'🔥'],
    'Resep Sehat'  => ['bg'=>'#FFFDE7','color'=>'#F57F17','icon'=>'🍽️'],
    'Info Gizi'    => ['bg'=>'#E3F2FD','color'=>'#1565C0','icon'=>'💡'],
];
$m      = $catMeta[$article['category']] ?? ['bg'=>'#EEE','color'=>'#555','icon'=>'📄'];
$src    = (!empty($article['source_name'])) ? $article['source_name'] : 'NutriMoms';
$isSaved= (bool)$article['is_saved'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=375, user-scalable=no">
<link rel="manifest" href="/nutrimoms/manifest.json">
<title><?=htmlspecialchars($article['title'])?> – NutriMoms</title>
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

/* FLOAT HEADER */
.float-header{
    position:fixed;top:0;left:50%;transform:translateX(-50%);
    width:375px;z-index:50;
    padding:14px 20px;
    display:flex;align-items:center;justify-content:space-between;
    transition:background 0.3s, box-shadow 0.3s;
}
.float-header.scrolled{
    background:rgba(234,241,232,0.95);
    backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);
    box-shadow:0 1px 10px rgba(0,0,0,0.07);
}
.icon-btn{
    width:40px;height:40px;border-radius:50%;
    background:rgba(255,255,255,0.92);backdrop-filter:blur(8px);
    display:flex;align-items:center;justify-content:center;
    border:none;cursor:pointer;box-shadow:var(--sh);
    text-decoration:none;-webkit-tap-highlight-color:transparent;
    transition:transform 0.15s;
}
.icon-btn:active{transform:scale(0.88);}
.icon-btn svg{width:20px;height:20px;color:var(--ink);}

.save-top-btn{
    width:40px;height:40px;border-radius:50%;
    background:rgba(255,255,255,0.92);backdrop-filter:blur(8px);
    display:flex;align-items:center;justify-content:center;
    border:none;cursor:pointer;box-shadow:var(--sh);
    -webkit-tap-highlight-color:transparent;transition:transform 0.15s;
}
.save-top-btn:active{transform:scale(0.88);}
.save-top-btn svg{width:20px;height:20px;transition:all 0.2s;}
.save-top-btn.saved{background:rgba(255,240,240,0.95);}
.save-top-btn.saved svg{color:#E53935;fill:#E53935;}
.save-top-btn:not(.saved) svg{color:var(--ink-2);fill:none;stroke:var(--ink-2);}

/* HERO */
.hero-img{width:375px;height:230px;object-fit:cover;display:block;}
.hero-placeholder{
    width:375px;height:180px;background:var(--g-lt);
    display:flex;align-items:center;justify-content:center;
    font-size:64px;padding-top:56px;
}

/* CONTENT CARD */
.content-card{
    background:var(--white);border-radius:var(--r) var(--r) 0 0;
    margin-top:-20px;position:relative;
    padding:22px 20px 32px;
}

.content-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.badge{
    display:inline-flex;align-items:center;gap:5px;
    padding:6px 13px;border-radius:99px;
    font-size:12px;font-weight:700;
}
.art-date{font-size:12px;color:var(--ink-3);font-weight:500;}

.art-title{
    font-size:22px;font-weight:800;color:var(--ink);
    line-height:1.35;letter-spacing:-0.3px;margin-bottom:16px;
}

/* Meta row */
.meta-row{
    display:flex;align-items:center;justify-content:space-between;
    padding-bottom:18px;border-bottom:1.5px solid var(--bg);margin-bottom:22px;
}
.source-info{display:flex;align-items:center;gap:8px;}
.src-logo-initial{
    width:26px;height:26px;border-radius:50%;
    background:var(--g);color:var(--white);
    font-size:10px;font-weight:800;
    display:flex;align-items:center;justify-content:center;
}
.src-logo-img{width:26px;height:26px;border-radius:50%;object-fit:cover;}
.src-name{font-size:13px;font-weight:700;color:var(--ink-2);}

/* Article body typography */
.art-body{font-size:15px;line-height:1.8;color:var(--ink-2);font-weight:400;}
.art-body p{margin-bottom:16px;}
.art-body p:last-child{margin-bottom:0;}
.art-body h2{font-size:18px;font-weight:800;color:var(--ink);margin:22px 0 10px;line-height:1.3;}
.art-body h3{font-size:16px;font-weight:700;color:var(--ink);margin:18px 0 8px;}
.art-body ul,.art-body ol{padding-left:20px;margin-bottom:16px;}
.art-body li{margin-bottom:7px;}
.art-body strong{color:var(--ink);font-weight:700;}
.art-body blockquote{
    border-left:4px solid var(--g);padding:12px 16px;
    background:var(--g-lt);border-radius:0 var(--r-sm) var(--r-sm) 0;
    margin:16px 0;font-style:italic;color:var(--g-dk);
}

/* In-article images */
.art-img-wrap{margin:24px -20px;}
.art-img-wrap img{
    width:100%;display:block;
    max-height:240px;object-fit:cover;
}
.art-img-caption{
    font-size:12px;color:var(--ink-3);font-weight:500;
    text-align:center;padding:7px 20px 0;font-style:italic;
}

/* DIVIDER */
.divider{height:8px;background:var(--bg);margin:28px -20px 0;}

/* RELATED (Desain Baru: Lebih Tipis & Memanjang) */
.related-wrap{padding:24px 0 36px;}
.related-title{
    font-size:16px;font-weight:800;color:var(--ink);
    padding:0 20px;margin-bottom:14px;
    display:flex;align-items:center;gap:10px;
}
.related-title::after{
    content:'';flex:1;height:2px;
    background:linear-gradient(to right,var(--g-mid),transparent);
    border-radius:99px;
}
.related-list{display:flex;flex-direction:column;gap:10px;padding:0 20px;}
.related-card{
    display:flex;align-items:center; /* Membuat isi vertikal center */
    text-decoration:none;overflow:hidden;
    background:var(--white);border-radius:var(--r-sm); /* Radius sedikit lebih rapat */
    border:1px solid var(--bg-2);box-shadow:var(--sh);
    -webkit-tap-highlight-color:transparent;transition:transform 0.13s;
}
.related-card:active{transform:scale(0.98);}
.rel-thumb{
    width:70px;height:70px; /* Ukuran gambar dikecilkan dari 100px ke 70px agar tidak tebal */
    object-fit:cover;flex-shrink:0;display:block;
}
.rel-thumb-placeholder{
    width:70px;height:70px; /* Menyesuaikan ukuran thumb baru */
    background:var(--g-lt);
    display:flex;align-items:center;justify-content:center;
    font-size:24px;flex-shrink:0;
}
.rel-body{
    flex:1;padding:8px 14px; /* Padding atas-bawah dikecilkan agar padat */
    display:flex;flex-direction:column;justify-content:center;min-width:0;
    gap:4px; /* Memberi jarak konsisten antara kategori dan judul */
}
.rel-cat{
    display:inline-flex;align-items:center;
    font-size:10px;font-weight:700;color:var(--g-dk);
    background:var(--g-lt);padding:2px 8px;border-radius:99px;
    width:fit-content;
}
.rel-title{
    font-size:13px;font-weight:700;color:var(--ink);line-height:1.4;
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
}

/* TOAST */
.toast{
    position:fixed;bottom:28px;left:50%;
    transform:translateX(-50%) translateY(16px);
    background:var(--ink);color:var(--white);
    font-size:13px;font-weight:700;font-family:'DM Sans',sans-serif;
    padding:11px 22px;border-radius:99px;
    opacity:0;transition:all 0.25s;pointer-events:none;
    white-space:nowrap;z-index:200;box-shadow:var(--sh-up);
}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0);}
</style>
</head>
<body>
<div class="shell">

<header class="float-header" id="floatHeader">
    <a href="article.php" class="icon-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>
        </svg>
    </a>
    <button class="save-top-btn <?=$isSaved?'saved':''?>" id="saveBtn" onclick="toggleSave(<?=$article['id']?>)">
        <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
        </svg>
    </button>
</header>

<?php if(!empty($article['image_url'])): ?>
<img class="hero-img" src="<?=htmlspecialchars($article['image_url'])?>" alt="<?=htmlspecialchars($article['title'])?>">
<?php else: ?>
<div class="hero-placeholder"><?=$m['icon']?></div>
<?php endif; ?>

<div class="content-card">
    <div class="content-top">
        <span class="badge" style="background:<?=$m['bg']?>;color:<?=$m['color']?>"><?=$m['icon']?> <?=htmlspecialchars($article['category'])?></span>
        <span class="art-date"><?=timeAgo($article['created_at'])?></span>
    </div>

    <h1 class="art-title"><?=htmlspecialchars($article['title'])?></h1>

    <div class="meta-row">
        <div class="source-info">
            <?php if(!empty($article['source_logo'])): ?>
            <img src="<?=htmlspecialchars($article['source_logo'])?>" class="src-logo-img" alt="">
            <?php else: ?>
            <div class="src-logo-initial"><?=srcInitial($src)?></div>
            <?php endif; ?>
            <span class="src-name"><?=htmlspecialchars($src)?></span>
        </div>
        <span class="art-date"><?=date('d M Y', strtotime($article['created_at']))?></span>
    </div>

    <div class="art-body"><?=$article['content']?></div>

    <?php if(!empty($article['image1'])): ?>
    <div class="art-img-wrap">
        <img src="<?=htmlspecialchars($article['image1'])?>" alt="" loading="lazy" onerror="this.parentElement.style.display='none'">
    </div>
    <?php endif; ?>

    <?php if(!empty($article['content2'])): ?>
    <div class="art-body" style="margin-top: 16px;"><?=$article['content2']?></div>
    <?php endif; ?>

    <?php if(!empty($article['image2'])): ?>
    <div class="art-img-wrap">
        <img src="<?=htmlspecialchars($article['image2'])?>" alt="" loading="lazy" onerror="this.parentElement.style.display='none'">
    </div>
    <?php endif; ?>

    <?php if(!empty($related)): ?>
    <div class="divider"></div>
    <div class="related-wrap">
        <div class="related-title">Artikel Terkait</div>
        <div class="related-list">
        <?php foreach($related as $r):
            $rm = $catMeta[$r['category']] ?? ['icon'=>'📄'];
        ?>
        <a href="article_detail.php?slug=<?=urlencode($r['slug'])?>" class="related-card">
            <?php if(!empty($r['image_url'])): ?>
            <img class="rel-thumb" src="<?=htmlspecialchars($r['image_url'])?>" alt="" loading="lazy" onerror="this.style.display='none'">
            <?php else: ?>
            <div class="rel-thumb-placeholder"><?=$rm['icon']?></div>
            <?php endif; ?>
            <div class="rel-body">
                <span class="rel-cat"><?=htmlspecialchars($r['category'])?></span>
                <div class="rel-title"><?=htmlspecialchars($r['title'])?></div>
            </div>
        </a>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

</div><div class="toast" id="toast"></div>

<script>
window.addEventListener('scroll', () => {
    document.getElementById('floatHeader').classList.toggle('scrolled', window.scrollY > 50);
}, { passive:true });

let _saving = false;
function toggleSave(articleId) {
    if (_saving) return; _saving = true;
    const btn = document.getElementById('saveBtn');
    btn.classList.toggle('saved');
    const fd = new FormData(); fd.append('article_id', articleId);
    fetch('../ajax/toggle_save_article.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(d => {
        if (d.success) showToast(d.saved ? '🔖 Artikel disimpan' : '✓ Dihapus dari simpanan');
        else { btn.classList.toggle('saved'); showToast('Gagal, coba lagi.'); }
    })
    .catch(() => { btn.classList.toggle('saved'); showToast('Koneksi bermasalah.'); })
    .finally(() => { _saving = false; });
}
let _tt;
function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg; t.classList.add('show');
    clearTimeout(_tt); _tt = setTimeout(() => t.classList.remove('show'), 2200);
}
</script>
</body>
</html>