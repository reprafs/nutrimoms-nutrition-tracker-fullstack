<?php
// pages/saved_articles.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
require_once '../config/db.php';
$db     = getDB();
$userId = (int)$_SESSION['user_id'];

$stmt = $db->prepare(
    "SELECT a.id, a.title, a.slug, a.excerpt, a.category, a.image_url,
            a.source_name, a.source_logo, a.created_at, sa.saved_at
     FROM   saved_articles sa
     JOIN   articles a ON a.id = sa.article_id
     WHERE  sa.user_id = :uid AND a.is_published = 1
     ORDER  BY sa.saved_at DESC"
);
$stmt->execute([':uid' => $userId]);
$saved = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=375, user-scalable=no">
<link rel="manifest" href="/nutrimoms/manifest.json">
<title>Tersimpan – NutriMoms</title>
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

/* HEADER */
.header{
    position:fixed;top:0;left:50%;transform:translateX(-50%);
    width:375px;z-index:50;background:var(--bg);
    padding:16px 20px 14px;border-bottom:1px solid var(--bg-2);
}
.header-row{display:flex;align-items:center;gap:12px;}
.back-btn{
    width:36px;height:36px;border-radius:50%;
    background:var(--white);border:none;
    display:flex;align-items:center;justify-content:center;
    cursor:pointer;box-shadow:var(--sh);text-decoration:none;
    transition:transform 0.15s;-webkit-tap-highlight-color:transparent;flex-shrink:0;
}
.back-btn:active{transform:scale(0.88);}
.back-btn svg{width:18px;height:18px;color:var(--ink);}
.header-title{font-size:22px;font-weight:800;color:var(--ink);letter-spacing:-0.4px;}
.header-count{
    margin-left:auto;font-size:12px;font-weight:700;
    color:var(--g-dk);background:var(--g-lt);
    padding:4px 10px;border-radius:99px;
}

/* CONTENT */
.content{padding-top:78px;padding-bottom:40px;}

/* CARD LIST */
.card-list{display:flex;flex-direction:column;gap:10px;padding:16px 20px 0;}
.article-card{
    background:var(--white);border-radius:var(--r);
    padding:15px;box-shadow:var(--sh);border:1px solid var(--bg-2);
    cursor:pointer;-webkit-tap-highlight-color:transparent;transition:transform 0.13s;
    position:relative;
}
.article-card:active{transform:scale(0.985);}

/* Card top */
.card-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:11px;}
.badge{
    display:inline-flex;align-items:center;gap:6px;
    padding:5px 11px;border-radius:99px;font-size:11.5px;font-weight:700;line-height:1;
}
.badge-icon{width:14px;height:14px;object-fit:contain;display:block;flex-shrink:0;}

/* Unsave button */
.unsave-btn{
    width:34px;height:34px;border-radius:50%;border:none;
    background:#FFF0F0;
    display:flex;align-items:center;justify-content:center;
    cursor:pointer;-webkit-tap-highlight-color:transparent;
    transition:transform 0.15s;flex-shrink:0;
}
.unsave-btn:active{transform:scale(0.85);}
.unsave-btn svg{width:18px;height:18px;color:#E53935;fill:#E53935;}

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
    background:var(--g-lt);display:flex;align-items:center;justify-content:center;flex-shrink:0;
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
.saved-at{font-size:11px;color:var(--ink-3);font-weight:500;}

/* EMPTY STATE */
.empty-state{
    display:flex;flex-direction:column;align-items:center;
    justify-content:center;padding:80px 24px;text-align:center;
}
.empty-icon{font-size:56px;margin-bottom:18px;}
.empty-state h3{font-size:18px;font-weight:800;color:var(--ink);margin-bottom:8px;}
.empty-state p{font-size:14px;color:var(--ink-3);line-height:1.6;font-weight:500;margin-bottom:24px;}
.btn-browse{
    display:inline-flex;align-items:center;gap:8px;
    padding:13px 24px;border-radius:99px;
    background:var(--g);color:var(--white);
    font-size:14px;font-weight:700;font-family:'DM Sans',sans-serif;
    text-decoration:none;border:none;cursor:pointer;
    box-shadow:0 4px 14px rgba(90,158,106,0.35);
    -webkit-tap-highlight-color:transparent;transition:transform 0.15s;
}
.btn-browse:active{transform:scale(0.96);}

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

/* Card slide-out animasi saat dihapus */
.article-card.removing{
    transition:opacity 0.25s, transform 0.25s, max-height 0.3s 0.2s, margin 0.3s 0.2s, padding 0.3s 0.2s;
    opacity:0;transform:translateX(40px);max-height:0!important;
    overflow:hidden;padding:0!important;margin:0!important;
}
</style>
</head>
<body>
<div class="shell">

<!-- HEADER -->
<div class="header">
    <div class="header-row">
        <a href="article.php" class="back-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>
            </svg>
        </a>
        <div class="header-title">Tersimpan</div>
        <?php if(!empty($saved)): ?>
        <div class="header-count"><?=count($saved)?> artikel</div>
        <?php endif; ?>
    </div>
</div>

<div class="content">

<?php if(!empty($saved)): ?>
<div class="card-list" id="cardList">
<?php foreach($saved as $a):
    $m   = $catMeta[$a['category']] ?? ['bg'=>'#EEE','color'=>'#555','icon'=>'../assets/icon/infogizi.png'];
    $src = (!empty($a['source_name'])) ? $a['source_name'] : 'NutriMoms';
    $url = 'article_detail.php?slug='.urlencode($a['slug']);
?>
<div class="article-card" id="card-<?=$a['id']?>" onclick="location.href='<?=$url?>'">

    <div class="card-top">
        <span class="badge" style="background:<?=$m['bg']?>;color:<?=$m['color']?>">
            <img src="<?=$m['icon']?>" class="badge-icon" alt="">
            <?=htmlspecialchars($a['category'])?>
        </span>
        <button class="unsave-btn"
                onclick="unsave(event, <?=$a['id']?>)"
                title="Hapus dari simpanan">
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
        <span class="saved-at">Disimpan <?=timeAgo($a['saved_at'])?></span>
    </div>

</div>
<?php endforeach; ?>
</div>

<?php else: ?>
<div class="empty-state">
    <div class="empty-icon">🔖</div>
    <h3>Belum ada artikel tersimpan</h3>
    <p>Tap ikon bookmark di artikel mana saja untuk menyimpannya di sini.</p>
    <a href="article.php" class="btn-browse">Jelajahi Artikel</a>
</div>
<?php endif; ?>

</div><!-- /content -->

<div class="toast" id="toast"></div>

<script>
function unsave(e, articleId) {
    e.stopPropagation();
    const card = document.getElementById('card-' + articleId);
    const fd   = new FormData();
    fd.append('article_id', articleId);

    fetch('/nutrimoms/ajax/toggle_save_article.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(d => {
        if (d.success && !d.saved) {
            // Animasi slide-out lalu hapus dari DOM
            card.style.maxHeight = card.offsetHeight + 'px';
            card.classList.add('removing');
            setTimeout(() => {
                card.remove();
                // Update counter di header
                const list  = document.getElementById('cardList');
                const count = list ? list.querySelectorAll('.article-card').length : 0;
                const badge = document.querySelector('.header-count');
                if (badge) badge.textContent = count + ' artikel';
                // Kalau sudah kosong, tampilkan empty state
                if (count === 0) location.reload();
            }, 550);
            showToast('✓ Dihapus dari simpanan');
        } else {
            showToast('Gagal, coba lagi.');
        }
    })
    .catch(() => showToast('Koneksi bermasalah.'));
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