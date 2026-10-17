-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 17, 2026 at 06:46 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nutrimoms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE `articles` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `content2` longtext DEFAULT NULL,
  `excerpt` varchar(300) DEFAULT NULL COMMENT 'Ringkasan singkat, max 300 karakter',
  `category` enum('Nutrisi Anak','Diet Dewasa','Resep Sehat','Info Gizi') NOT NULL DEFAULT 'Info Gizi',
  `image_url` varchar(500) DEFAULT NULL COMMENT 'URL thumbnail/cover artikel',
  `image1` varchar(500) DEFAULT NULL,
  `image2` varchar(500) DEFAULT NULL,
  `source_name` varchar(150) DEFAULT NULL COMMENT 'Nama sumber, NULL = tampil NutriMoms',
  `source_logo` varchar(500) DEFAULT NULL COMMENT 'URL logo sumber',
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `articles`
--

INSERT INTO `articles` (`id`, `title`, `slug`, `content`, `content2`, `excerpt`, `category`, `image_url`, `image1`, `image2`, `source_name`, `source_logo`, `is_published`, `created_at`, `updated_at`) VALUES
(1, 'Panduan Lengkap MPASI Pertama untuk Bayi 6 Bulan', 'panduan-mpasi-pertama-bayi-6-bulan', '<h2>Apa Itu MPASI dan Mengapa Usia 6 Bulan Sangat Krusial?</h2>\r\n\r\n<p>MPASI atau Makanan Pendamping ASI adalah makanan dan minuman bergizi yang diberikan kepada bayi selain ASI. Memasuki usia 6 bulan, terjadi <i>nutrition gap</i> atau kesenjangan nutrisi. Kebutuhan energi, protein, zat besi, zinc, serta vitamin A dan D pada bayi meningkat pesat, sementara kualitas dan kuantitas produksi ASI ibu mulai mengalami penurunan alami.</p>\r\n\r\n<p>Zat besi, misalnya, kebutuhan bayi usia 6-11 bulan melonjak hingga 11 mg per hari. Padahal, ASI hanya mampu menyumbang sekitar 0.2 mg per hari. Kekurangan zat besi pada fase ini dapat berdampak serius pada perkembangan otak dan memicu risiko anemia. Oleh karena itu, MPASI bukan sekadar mengenalkan rasa baru, melainkan strategi krusial untuk mencegah <i>stunting</i> dan mendukung pertumbuhan linier anak.</p>\r\n\r\n<p>Memulai MPASI sering kali memicu kecemasan bagi orang tua baru. Pertanyaan seputar jenis makanan yang aman, risiko alergi, porsi yang tepat, hingga kekhawatiran jika bayi tersedak atau menolak makanan adalah hal yang sangat wajar. Kuncinya adalah pemahaman yang matang mengenai kesiapan fisik dan psikologis sang buah hati.</p>\r\n\r\n<h2>Tanda Belajar: Kapan Bayi Benar-Benar Siap MPASI?</h2>\r\n\r\n<p>Organisasi Kesehatan Dunia (WHO) dan Ikatan Dokter Anak Indonesia (IDAI) merekomendasikan MPASI dimulai tepat pada usia 6 bulan (180 hari). Namun, orang tua juga harus jeli melihat indikator kesiapan klinis dan perkembangan motorik bayi berikut ini:</p>\r\n\r\n<ul>\r\n    <li><strong>Kontrol Leher dan Kepala:</strong> Bayi sudah mampu menegakkan kepalanya dengan stabil tanpa bantuan saat didudukkan. Ini penting untuk mencegah risiko tersedak (<i>choking</i>).</li>\r\n    <li><strong>Refleks Melepeh Berkurang:</strong> Refleks menjulurkan lidah (<i>extrusion reflex</i>) yang berfungsi mengeluarkan benda asing dari mulut sudah mulai menghilang.</li>\r\n    <li><strong>Ketertarikan pada Makanan:</strong> Bayi menunjukkan antusiasme tinggi saat melihat orang dewasa makan, seperti mencoba meraih makanan, mengecapkan bibir, atau mencondongkan tubuh ke depan.</li>\r\n    <li><strong>Kemampuan Motorik Mandiri:</strong> Bayi mulai bisa memasukkan benda atau tangannya sendiri ke dalam mulut dengan koordinasi mata-tangan yang baik.</li>\r\n</ul>\r\n\r\n<h2>Strategi Menu Pertama: Konsep Menu Tunggal vs Menu Lengkap</h2>\r\n\r\n<p>Dulu, sempat populer tren memberikan \"menu tunggal\" (hanya buah atau sayur saja) selama dua minggu pertama. Namun, panduan terbaru dari IDAI menegaskan bahwa bayi sebaiknya langsung diberikan <strong>Menu Lengkap</strong> sejak hari pertama MPASI.</p>\r\n\r\n<p>Menu lengkap wajib mengandung makronutrien dan mikronutrien seimbang, yang terdiri dari:</p>\r\n<ol>\r\n    <li><strong>Karbohidrat:</strong> Sebagai sumber energi utama (beras putih, beras merah, kentang, ubi, atau oatmeal).</li>\r\n    <li><strong>Protein Hewani:</strong> Ini adalah komponen wajib yang paling krusial untuk mencegah stunting dan menyuplai zat besi tinggi (hati ayam, daging sapi cincang, telur, ikan kembung, atau ayam).</li>\r\n    <li><strong>Lemak Tambahan:</strong> Sebagai booster kalori dan membantu penyerapan vitamin larut lemak (minyak kelapa sawit, santan matang, mentega/butter, atau minyak zaitun).</li>\r\n    <li><strong>Serat (Sayur & Buah):</strong> Hanya digunakan sebagai perkenalan dalam jumlah sangat sedikit (misal sepotong kecil wortel atau brokoli) karena serat berlebih dapat menghambat penyerapan zat besi dan memicu sembelit pada bayi.</li>\r\n</ol>\r\n\r\n<p>Pastikan Anda menghindari pemberian garam, gula, penyedap rasa buatan, madu (berisiko botulisme pada anak < 1 tahun), serta susu sapi segar sebagai minuman utama sebelum anak menginjak usia 12 bulan.</p>', '<h2>Aturan Emas Pemberian MPASI: Porsi, Tekstur, dan Frekuensi</h2>\r\n\r\n<p>Pemberian MPASI harus mengikuti prinsip tahapan usia (<i>responsive feeding</i>). Mengubah tekstur terlalu lambat dapat menyebabkan anak mengalami malas mengunyah di kemudian hari, sementara porsi yang terlalu banyak akan membebani pencernaannya.</p>\r\n\r\n<p>Berikut adalah panduan standardisasi dari WHO dan IDAI yang bisa dijadikan acuan:</p>\r\n\r\n<h3>1. Usia 6–8 Bulan (Tahap Awal)</h3>\r\n<ul>\r\n    <li><strong>Tekstur:</strong> Bubur kental yang disaring (<i>puree</i>/<i>mashed</i>). Jika sendok dimiringkan, bubur tidak langsung tumpah dengan mudah.</li>\r\n    <li><strong>Porsi:</strong> Dimulai dari 2–3 sendok makan per sesi makan, ditingkatkan bertahap hingga setengah mangkuk berukuran 250 ml.</li>\r\n    <li><strong>Frekuensi:</strong> 2–3 kali makan besar ditambah 1–2 kali camilan sehat (seperti puree buah).</li>\r\n</ul>\r\n\r\n<h3>2. Usia 9–11 Bulan (Tahap Transisi)</h3>\r\n<ul>\r\n    <li><strong>Tekstur:</strong> Makanan yang dicincang halus (<i>minced</i>), cincang kasar (<i>chopped</i>), atau makanan yang bisa digenggam anak (<i>finger foods</i>). Anak sudah mulai belajar mengunyah tanpa gigi.</li>\r\n    <li><strong>Porsi:</strong> Setengah hingga tiga perempat mangkuk ukuran 250 ml (sekitar 125–175 ml).</li>\r\n    <li><strong>Frekuensi:</strong> 3–4 kali makan besar ditambah 1–2 kali camilan sehat.</li>\r\n</ul>\r\n\r\n<h3>3. Usia 12–23 Bulan (Tahap Makanan Keluarga)</h3>\r\n<ul>\r\n    <li><strong>Tekstur:</strong> Sudah bisa mengonsumsi makanan yang sama dengan anggota keluarga lainnya. Potong lauk pauk menjadi ukuran yang aman agar tidak tersedak.</li>\r\n    <li><strong>Porsi:</strong> Tiga perempat sampai satu mangkuk penuh ukuran 250 ml.</li>\r\n    <li><strong>Frekuensi:</strong> 3–4 kali makan besar dan 2 kali camilan.</li>\r\n</ul>\r\n\r\n<h2>Menghadapi GTM (Gerakan Tutup Mulut) Tanpa Stres</h2>\r\n\r\n<p>GTM adalah salah satu tantangan terbesar yang sering membuat orang tua stres. Penyebab GTM sangat bervariasi, mulai dari fase tumbuh gigi (<i>teething</i>), bosan dengan menu yang itu-itu saja, mengantuk, kenyang karena terlalu dekat dengan jadwal minum susu, hingga trauma psikologis akibat dipaksa makan.</p>\r\n\r\n<p>Untuk mengatasinya, terapkan aturan makan atau <i>feeding rules</i> yang disiplin namun menyenangkan:</p>\r\n<ul>\r\n    <li><strong>Batasi Waktu Makan:</strong> Durasi makan maksimal adalah 30 menit. Jika dalam 30 menit anak tidak mau makan atau hanya memainkannya, sudahi proses makan tanpa marah atau membujuk berlebihan.</li>\r\n    <li><strong>Ciptakan Lingkungan Bebas Distraksi:</strong> Matikan televisi, jauhkan <i>smartphone</i>/gadget, dan hindari menyuapi anak sambil menggendongnya berjalan-jalan ke luar rumah. Fokuskan perhatian anak pada makanannya.</li>\r\n    <li><strong>Hargai Tanda Kenyang & Lapar:</strong> Jangan pernah memaksa, mencekoki, atau menjejalkan makanan ke mulut bayi secara paksa karena akan menimbulkan trauma mendalam terhadap aktivitas makan.</li>\r\n    <li><strong>Atur Jadwal yang Konsisten:</strong> Buat jarak minimal 2 jam antara pemberian susu/camilan dengan jadwal makan besar agar anak benar-benar merasakan sensasi lapar alami.</li>\r\n</ul>\r\n\r\n<h2>Kesimpulan dan Langkah Bijak Orang Tua</h2>\r\n\r\n<p>Perjalanan MPASI adalah maraton, bukan sprint. Setiap anak memiliki laju perkembangan yang unik dan tidak bisa disamakan satu sama lain. Keberhasilan MPASI tidak hanya diukur dari seberapa bersih mangkuknya setiap hari, melainkan dari bagaimana kedekatan (<i>bonding</i>) dan kebiasaan makan sehat itu terbentuk.</p>\r\n\r\n<p>Dengan membekali diri lewat pengetahuan yang valid seputar nutrisi, menjaga higienitas selama proses memasak, serta menerapkan <i>feeding rules</i> yang konsisten, Anda sedang membangun fondasi kesehatan jangka panjang yang kokoh untuk masa depan sang buah hati. Selamat mendampingi si kecil bertumbuh!</p>', 'Panduan lengkap memulai MPASI untuk bayi usia 6 bulan, mulai dari tanda kesiapan bayi, jadwal makan, jenis makanan pertama, hingga tips menghindari GTM.', 'Nutrisi Anak', '../assets/img/article1.jpeg', '../assets/img/article11.jpeg', '../assets/img/article111.jpeg', 'Ikatan Dokter Anak Indonesia', NULL, 1, '2026-06-25 12:49:06', '2026-06-26 17:39:23'),
(2, '5 Cara Efektif Menurunkan Berat Badan Tanpa Kelaparan', '5-cara-menurunkan-berat-badan-tanpa-kelaparan', '<h2>Diet Tidak Harus Menyiksa: Mengubah Pola Pikir tentang Defisit Kalori</h2>\r\n\r\n<p>Banyak orang gagal mempertahankan berat badan ideal karena terjebak dalam stigma bahwa diet berarti menahan lapar secara ekstrem. Faktanya, metode menyiksa diri seperti ini hanya akan memicu stres, merusak metabolisme, dan memicu efek <i>yo-yo</i>—kondisi di mana berat badan justru melonjak lebih tinggi setelah diet dihentikan.</p>\r\n\r\n<p>Kunci utama penurunan berat badan yang berkelanjutan adalah <strong>Defisit Kalori yang Sehat</strong>, bukan kelaparan. Tubuh tetap membutuhkan energi untuk menjalankan fungsi organ vital. Dengan memilih jenis makanan yang tepat dan kaya nutrisi, Anda bisa memangkas kalori harian tanpa harus membiarkan perut terus-menerus berbunyi. Ini adalah tentang mengubah gaya hidup, bukan sekadar program sementara.</p>\r\n\r\n<h2>1. Perbanyak Protein: Sahabat Terbaik Pembakar Lemak</h2>\r\n\r\n<p>Protein adalah makronutrien yang paling krusial dalam proses penurunan berat badan. Protein memiliki <i>Thermic Effect of Food</i> (TEF) yang paling tinggi, artinya tubuh membakar lebih banyak kalori hanya untuk mencerna protein dibandingkan dengan lemak atau karbohidrat.</p>\r\n\r\n<p>Selain itu, protein secara ilmiah mampu menekan hormon <i>ghrelin</i> (hormon pemicu lapar) dan meningkatkan hormon *satiety* seperti peptide YY yang membuat Anda merasa kenyang jauh lebih lama. Pastikan setiap sesi makan Anda dilengkapi dengan sumber protein berkualitas seperti dada ayam tanpa kulit, ikan kembung (kaya omega-3), telur, tahu, tempe, serta yogurt rendah lemak tanpa pemanis tambahan.</p>\r\n\r\n<h2>2. Konsumsi Serat Lebih Banyak untuk Memperpanjang Rasa Kenyang</h2>\r\n\r\n<p>Serat, terutama serat larut air (<i>soluble fiber</i>), akan membentuk matriks mirip gel di dalam saluran pencernaan setelah bercampur dengan air. Proses ini memperlambat pengosongan lambung dan memperpanjang waktu penyerapan nutrisi di usus.</p>\r\n\r\n<p>Efek praktisnya? Sinyal kenyang akan dikirim ke otak secara konstan, sehingga keinginan untuk *ngemil* makanan manis di sela-sela jam makan besar dapat ditekan secara alami. Anda bisa memenuhi kebutuhan serat harian dari sayuran berdaun hijau (bayam, brokoli), buah-buahan segar (apel, alpukat, beri), oatmeal, serta kacang-kacangan.</p>\r\n\r\n<h2>3. Strategi Substitusi: Hindari Kalori Cair (Minuman Manis)</h2>\r\n\r\n<p>Salah satu jebakan terbesar dalam diet adalah kalori tersembunyi dari apa yang kita minum. Minuman seperti boba, kopi susu kekinian, teh manis, dan soda mengandung gula sederhana dalam jumlah sangat tinggi. Celakanya, kalori cair ini tidak memberikan volume di lambung, sehingga otak tidak mencatatnya sebagai makanan dan Anda akan tetap merasa lapar.</p>\r\n\r\n<p>Mengganti seluruh konsumsi minuman manis dengan air putih atau teh hijau tanpa gula adalah langkah paling instan untuk memotong ratusan kalori harian tanpa mengurangi porsi makanan padat Anda.</p>', '<h2>4. Atur Porsi Makan dengan Metode Piring T (Isi Piringku)</h2>\r\n\r\n<p>Mengurangi porsi makan secara ekstrem langsung di hari pertama hanya akan membuat tubuh Anda mengalami syok dan kelaparan hebat. Pendekatan yang lebih bijak dan efektif adalah dengan memodifikasi visual dan komposisi piring makan Anda.</p>\r\n\r\n<p>Gunakan prinsip <strong>Isi Piringku</strong> atau Metode Piring T:</p>\r\n<ul>\r\n    <li><strong>50% Piring (Setengah):</strong> Isi dengan sayuran non-pati dan buah-buahan sebagai sumber serat dan mikronutrien rendah kalori.</li>\r\n    <li><strong>25% Piring (Seperempat):</strong> Isi dengan protein rendah lemak sebagai pembangun otot dan penjaga rasa kenyang.</li>\r\n    <li><strong>25% Piring (Seperempat sisanya):</strong> Isi dengan karbohidrat kompleks (nasi merah, kentang rebus dengan kulit, atau ubi) yang melepaskan energi secara perlahan (<i>low glycemic index</i>).</li>\r\n</ul>\r\n<p>Gunakan pula piring yang berukuran lebih kecil untuk trik psikologis, dan kunyah makanan secara perlahan (minimal 20-30 kali) agar hormon kenyang sempat mencapai otak sebelum Anda makan berlebihan.</p>\r\n\r\n<h2>5. Tetap Aktif Bergerak: Menjaga Metabolisme Tetap Optimal</h2>\r\n\r\n<p>Saat kalori dikurangi, tubuh memiliki kecenderungan alami untuk menurunkan pengeluaran energinya demi bertahan hidup. Fenomena ini disebut adaptasi metabolik. Untuk melawannya, Anda wajib menjaga tubuh tetap aktif bergerak.</p>\r\n\r\n<p>Anda tidak harus langsung melakukan angkat beban berat atau lari maraton jika belum terbiasa. Mulailah dengan meningkatkan <i>Non-Exercise Activity Thermogenesis</i> (NEAT), seperti berjalan kaki 30-45 menit setiap hari, memilih naik tangga dibanding lift, atau merapikan rumah. Aktivitas sederhana ini sangat efektif membakar kalori ekstra dan mempertahankan massa otot.</p>\r\n\r\n<h2>Membongkar Kesalahan Diet yang Sering Terjadi</h2>\r\n\r\n<p>Seringkali, meskipun sudah makan lebih sedikit, berat badan tetap stagnan. Hal ini biasanya dipicu oleh beberapa kebiasaan keliru berikut:</p>\r\n<ul>\r\n    <li><strong>Melewatkan Sarapan:</strong> Ini sering kali menjadi bumerang yang memicu kelaparan hebat di siang atau malam hari, membuat Anda makan dengan porsi balas dendam.</li>\r\n    <li><strong>Kurang Tidur (Begadang):</strong> Kurang tidur mengacaukan regulasi hormon. Hormon lapar (ghrelin) akan melonjak, sementara hormon kenyang (leptin) drop. Akibatnya, Anda akan mengidam makanan tinggi lemak dan gula keesokan harinya.</li>\r\n    <li><strong>Dehidrasi kronis:</strong> Otak manusia sering kali salah mengartikan sinyal haus sebagai sinyal lapar. Ketika Anda merasa ingin ngemil, bisa jadi tubuh Anda sebenarnya hanya kekurangan air putih.</li>\r\n</ul>\r\n\r\n<h2>Kesimpulan</h2>\r\n\r\n<p>Menurunkan berat badan adalah sebuah seni membangun kebiasaan baru yang berkelanjutan, bukan kompetisi siapa yang paling tahan menderita. Dengan fokus pada penambahan protein dan serat, mengatur komposisi piring, serta menjaga konsistensi bergerak, Anda bisa memotong lemak tubuh dengan nyaman tanpa perlu menyiksa fisik maupun mental Anda.</p>', 'Strategi diet sehat untuk menurunkan berat badan tanpa rasa lapar berlebihan, mulai dari pola makan seimbang hingga pengaturan kalori harian.', 'Diet Dewasa', '../assets/img/article2.jpeg', '../assets/img/article22.jpeg', '../assets/img/article222.jpeg', NULL, NULL, 1, '2026-06-25 09:49:06', '2026-06-26 17:43:14'),
(3, 'Resep Nugget Sayur Homemade untuk Si Kecil', 'resep-nugget-sayur-homemade-anak', '<h2>Nugget Sehat untuk Anak: Solusi Cerdas Hadapi Anak yang Pilih-Pilih Makanan</h2>\r\n\r\n<p>Menghadapi fase anak yang susah makan sayur (<i>picky eater</i>) sering kali menguras energi dan emosi orang tua. Banyak anak menolak sayuran karena aroma langu yang tajam atau tekstur seratnya yang terasa asing di lidah mereka. Jika dibiarkan, anak berisiko kekurangan mikronutrien penting seperti vitamin, mineral, dan serat yang sangat dibutuhkan untuk proses tumbuh kembang secara optimal.</p>\r\n\r\n<p>Membuat **Nugget Sayur Homemade** adalah salah satu strategi kamuflase makanan (<i>food sneaking</i>) yang paling efektif. Dengan mengubah bentuk sayuran menjadi nugget yang gurih dan renyah, anak-anak akan sangat antusias menyantapnya tanpa sadar bahwa mereka sedang mengonsumsi sayuran kaya gizi. Metode ini jauh lebih sehat dibandingkan memberikan nugget instan kemasan yang umumnya tinggi sodium (garam) dan zat pengawet buatan.</p>\r\n\r\n<h2>Bahan-Bahan Berkualitas Tinggi (High-Nutrient Ingredients)</h2>\r\n\r\n<p>Untuk membuat nugget yang tidak hanya lezat tetapi juga padat nutrisi, pastikan Anda menyiapkan bahan-bahan segar berikut ini:</p>\r\n\r\n<ul>\r\n    <li><strong>250 gram Daging Ayam Cincang:</strong> Gunakan bagian dada untuk protein tinggi rendah lemak, atau bagian paha jika ingin tekstur nugget yang lebih juicy.</li>\r\n    <li><strong>1 buah Wortel Ukuran Sedang:</strong> Diparut halus menggunakan parutan keju agar menyatu sempurna dengan daging ayam (sumber vitamin A dan beta-karoten).</li>\r\n    <li><strong>1 genggam Bayam Segar:</strong> Cincang sampai sangat halus (sumber zat besi, asam folat, dan serat).</li>\r\n    <li><strong>1 butir Telur Ayam:</strong> Berfungsi sebagai perekat adonan alami sekaligus menambah asupan protein dan kolin untuk otak anak.</li>\r\n    <li><strong>2-3 sendok makan Tepung Terigu / Tepung Oats:</strong> Sebagai pengikat kelembapan adonan nugget.</li>\r\n    <li><strong>30 gram Keju Parut:</strong> Memberikan rasa gurih alami (<i>umami</i>) sekaligus menyuplai kalsium untuk pertumbuhan tulang dan gigi si kecil.</li>\r\n    <li><strong>Bumbu Halus:</strong> 2 siung bawang putih, 1 siung bawang merah, sedikit merica bubuk, dan sejumput garam (opsional untuk anak di atas 1 tahun).</li>\r\n    <li><strong>Bahan Pelapis:</strong> 1 butir telur dikocok lepas dan tepung panir/tepung roti secukupnya kualitas premium agar hasilnya renyah tahan lama.</li>\r\n</ul>', '<h2>Panduan Cara Membuat Nugget Sayur yang Lembut di Dalam, Renyah di Luar</h2>\r\n\r\n<p>Proses pembuatan nugget ini sangat sederhana dan bisa dijadikan aktivitas seru memasak bersama anak di akhir pekan. Ikuti langkah demi langkahnya secara berurutan:</p>\r\n\r\n<ol>\r\n    <li><strong>Persiapan Sayur & Bumbu:</strong> Campurkan daging ayam cincang, parutan wortel halus, dan cincangan bayam ke dalam wadah besar. Masukkan bumbu halus (bawang putih dan bawang merah) serta keju parut.</li>\r\n    <li><strong>Penyatuan Adonan:</strong> Tambahkan satu butir telur dan tepung pengikat. Aduk semua bahan menggunakan spatula hingga membentuk adonan yang menyatu rata dan kalis.</li>\r\n    <li><strong>Proses Pengukusan:</strong> Siapkan loyang atau wadah tahan panas, olesi tipis dengan minyak atau mentega agar tidak lengket. Tuang adonan ke dalam loyang, ratakan permukaannya. Kukus di dalam dandang yang sudah dipanaskan selama 20–25 menit hingga matang dan memadat. Setelah matang, angkat dan biarkan dingin di suhu ruang.</li>\r\n    <li><strong>Pemotongan & Pelapisan:</strong> Potong-potong nugget yang sudah dingin sesuai selera (Anda bisa menggunakan cetakan karakter lucu seperti bintang atau beruang agar anak lebih tertarik). Celupkan potongan nugget ke dalam kocokan telur, lalu gulingkan ke atas tepung panir hingga seluruh permukaannya tertutup rata.</li>\r\n    <li><strong>Tahap Akhir (Memasak):</strong> Nugget siap digoreng dengan metode <i>deep frying</i> menggunakan api sedang hingga berwarna kuning keemasan, lalu tiriskan minyaknya dengan baik sebelum disajikan.</li>\r\n</ol>\r\n\r\n<h2>Tips Memasak Lebih Sehat & Metode Food Preparation</h2>\r\n\r\n<p>Agar nilai nutrisinya tetap terjaga maksimal, ada beberapa trik dapur yang bisa Anda terapkan:</p>\r\n<ul>\r\n    <li><strong>Gunakan Air Fryer:</strong> Jika ingin memangkas konsumsi minyak goreng, Anda bisa memasak nugget ini menggunakan <i>air fryer</i> pada suhu 180°C selama 10-12 menit. Olesi sedikit minyak di permukaannya agar warnanya tetap cantik dan renyah.</li>\r\n    <li><strong>Stok MPASI / Frozen Food Praktis:</strong> Nugget yang sudah dibalur tepung panir jangan langsung digoreng semua. Masukkan ke dalam wadah kedap udara (<i>thinwall</i>) dan simpan di dalam freezer. Nugget homemade ini bisa bertahan hingga 1 bulan di dalam pembeku dan siap digoreng kapan saja saat anak lapar.</li>\r\n</ul>\r\n\r\n<h2>Keunggulan Mutlak Nugget Sayur Homemade dibanding Nugget Instan</h2>\r\n\r\n<p>Membuat camilan sendiri di rumah memberikan garansi kesehatan jangka panjang bagi anak Anda. Berikut keuntungan yang akan Anda dapatkan:</p>\r\n<ul>\r\n    <li><strong>Bebas Bahan Kimia Berbahaya:</strong> 100% tanpa pengawet sintetik, tanpa pewarna buatan, dan tanpa sediaan MSG berlebih yang kurang baik untuk ginjal anak yang masih berkembang.</li>\r\n    <li><strong>Kaya Serat Alami:</strong> Rasio sayuran yang tinggi membantu melancarkan sistem pencernaan anak dan mencegah sembelit (susah BAB).</li>\r\n    <li><strong>Jauh Lebih Ekonomis:</strong> Dengan modal bahan yang terjangkau, Anda bisa menghasilkan nugget dalam jumlah banyak yang cukup untuk konsumsi satu keluarga selama beberapa minggu.</li>\r\n</ul>\r\n\r\n<h2>Kesimpulan</h2>\r\n\r\n<p>Mengatasi anak yang sulit makan sayur tidak harus lewat paksaan yang memicu trauma psikologis pada anak. Pendekatan kreatif lewat resep Nugget Sayur Homemade terbukti efektif menjembatani kebutuhan nutrisi harian anak dengan selera makan mereka. Selamat mencoba di rumah, Moms!</p>', 'Resep nugget sayur homemade yang lezat, bergizi, dan mudah dibuat untuk bekal anak maupun camilan sehat keluarga.', 'Resep Sehat', '../assets/img/article3.jpeg', '../assets/img/article33.jpeg', '../assets/img/article333.jpeg', NULL, NULL, 1, '2026-06-24 14:49:06', '2026-06-26 17:47:49'),
(4, 'Mengenal Makronutrien: Protein, Karbohidrat, dan Lemak', 'mengenal-makronutrien-protein-karbohidrat-lemak', '<h2>Apa Itu Makronutrien? Fondasi Utama Energi dan Metabolisme Tubuh</h2>\r\n\r\n<p>Dalam dunia nutrisi, makronutrien adalah zat gizi esensial yang diperlukan oleh tubuh dalam komponen skala besar (satuan gram) setiap harinya. Berbeda dengan mikronutrien (vitamin dan mineral) yang hanya dibutuhkan dalam jumlah miligram, makronutrien memiliki peran vital tunggal yang tidak bisa digantikan: yaitu sebagai satu-satunya sumber kalori atau energi yang menggerakkan seluruh fungsi biologis manusia.</p>\r\n\r\n<p>Makronutrien terdiri dari tiga pilar utama, yaitu <strong>Karbohidrat, Protein, dan Lemak</strong>. Ketiganya bekerja secara sinergis. Ketika Anda mengalami defisit kronis pada salah satu komponen ini, tubuh akan mengalami gangguan metabolisme, penurunan massa otot, hingga ketidakseimbangan hormon. Oleh karena itu, memahami fungsi spesifik dan kualitas dari masing-masing makronutrien adalah kunci utama dalam membangun tubuh yang sehat, bugar, dan berumur panjang.</p>\r\n\r\n<h2>1. Protein: Zat Pembangun Makro untuk Regenerasi Sel</h2>\r\n\r\n<p>Protein sering disebut sebagai blok bangunan (<i>building blocks</i>) kehidupan. Ketika dikonsumsi, tubuh akan memecah protein menjadi asam amino. Asam amino inilah yang digunakan untuk memperbaiki kerusakan mikro pada jaringan otot, meregenerasi sel kulit yang mati, memproduksi enzim pencernaan, serta membentuk sistem imun tubuh (antibodi).</p>\r\n\r\n<p>Bagi Anda yang sedang menjaga berat badan, protein sangat krusial karena memiliki efek mengenyangkan paling tinggi dibanding nutrisi lain. Sumber protein dibagi menjadi dua jenis utama:</p>\r\n<ul>\r\n    <li><strong>Protein Hewani (Asam Amino Lengkap):</strong> Dada ayam tanpa kulit, daging sapi tanpa lemak, telur, ikan salmon, ikan kembung, susu, dan yogurt rendah lemak.</li>\r\n    <li><strong>Protein Nabati:</strong> Tahu, tempe, kacang kedelai, dan edamame. Protein nabati sangat baik karena kaya akan serat, namun disarankan dikombinasikan dengan protein hewani agar profil asam aminonya saling melengkapi.</li>\r\n</ul>\r\n\r\n<h2>2. Karbohidrat: Bahan Bakar Utama Sistem Saraf dan Otot</h2>\r\n\r\n<p>Karbohidrat adalah sumber energi yang paling efisien dan paling disukai oleh tubuh, terutama oleh organ otak dan sistem saraf pusat yang mengonsumsi glukosa secara konstan. Di dalam tubuh, karbohidrat diubah menjadi glukosa yang mengalir di darah, atau disimpan di dalam otot dan hati sebagai glikogen.</p>\r\n\r\n<p>Berdasarkan struktur kimianya, karbohidrat dibagi menjadi dua kelompok besar:</p>\r\n<ul>\r\n    <li><strong>Karbohidrat Kompleks (Sangat Direkomendasikan):</strong> Memiliki rantai gula yang panjang dan kaya serat. Contohnya adalah nasi merah, oatmeal, ubi jalar, roti gandum utuh, dan jagung. Karbohidrat ini dicerna secara perlahan oleh lambung, sehingga melepaskan energi secara bertahap (<i>slow release energy</i>) dan mencegah lonjakan drastis gula darah (indeks glikemik rendah).</li>\r\n    <li><strong>Karbohidrat Simpleks (Batasi Konsumsi):</strong> Memiliki struktur molekul pendek yang sangat cepat diserap tubuh, seperti gula pasir, tepung terigu putih, sirup, dan minuman kemasan. Karbohidrat jenis ini memicu lonjakan insulin instan yang cepat membuat Anda merasa lemas dan lapar kembali.</li>\r\n</ul>', '<h2>3. Lemak: Pelindung Organ Vital dan Regulator Hormon</h2>\r\n\r\n<p>Meskipun sering mendapatkan reputasi buruk, lemak adalah komponen makronutrien yang mutlak dibutuhkan untuk bertahan hidup. Lemak berfungsi sebagai struktur dasar dinding sel, pelindung organ-organ internal dari benturan fisik, isolator suhu tubuh, serta komponen utama dalam produksi hormon reproduksi seperti estrogen dan testosteron. Selain itu, vitamin A, D, E, dan K tidak akan bisa diserap oleh tubuh tanpa adanya bantuan dari lemak.</p>\r\n\r\n<p>Kuncinya adalah memilih jenis lemak yang masuk ke dalam tubuh Anda:</p>\r\n<ul>\r\n    <li><strong>Lemak Sehat (Tak Jenuh):</strong> Membantu menurunkan kolesterol jahat (LDL) dan menjaga kesehatan jantung. Sumber terbaiknya bisa didapatkan dari buah alpukat, kacang almond, kacang mete, minyak zaitun (<i>extra virgin olive oil</i>), serta ikan berlemak baik seperti salmon dan tuna.</li>\r\n    <li><strong>Lemak Jahat (Lemak Jenuh & Lemak Trans):</strong> Harus dibatasi atau dihindari karena dapat menyumbat pembuluh darah dan memicu penyakit kardiovaskular. Contohnya adalah minyak goreng deep-fry (gorengan), margarin, sosis instan, dan daging berlemak tinggi.</li>\r\n</ul>\r\n\r\n<h2>Pentingnya Keseimbangan Proporsi Makronutrien (Macro Tracking)</h2>\r\n\r\n<p>Diet ekstrem yang memotong habis salah satu makronutrien secara total (misalnya tidak makan karbohidrat sama sekali atau menghindari lemak sama sekali) adalah langkah yang keliru dan berbahaya bagi kesehatan jangka panjang. Tubuh manusia membutuhkan ketiganya dalam proporsi yang seimbang sesuai target kesehatan masing-masing.</p>\r\n\r\n<p>Secara umum, panduan gizi seimbang yang direkomendasikan untuk menjaga kesehatan tubuh harian (<i>maintenance</i>) adalah:</p>\r\n<ul>\r\n    <li><strong>Karbohidrat:</strong> Berkontribusi sekitar 45% – 60% dari total kalori harian Anda (utamakan jenis kompleks).</li>\r\n    <li><strong>Protein:</strong> Berkontribusi sekitar 15% – 30% dari total kalori harian (sesuaikan lebih tinggi jika Anda aktif berolahraga beban).</li>\r\n    <li><strong>Lemak:</strong> Berkontribusi sekitar 20% – 35% dari total kalori harian (utamakan jenis lemak tak jenuh).</li>\r\n</ul>\r\n\r\n<h2>Kesimpulan</h2>\r\n\r\n<p>Kunci dari tubuh yang sehat dan berenergi sepanjang hari bukanlah mengurangi porsi makan secara drastis hingga kelaparan, melainkan tentang bagaimana Anda mengombinasikan kualitas karbohidrat kompleks, protein rendah lemak, dan lemak tak jenuh di atas piring makan Anda. Dengan menjaga keseimbangan rasio makronutrien ini, Anda tidak hanya dapat mengontrol berat badan ideal secara konsisten, tetapi juga menjaga stabilitas metabolisme tubuh secara keseluruhan.</p>', 'Panduan lengkap memahami fungsi protein, karbohidrat, dan lemak sebagai makronutrien penting bagi kesehatan dan energi tubuh.', 'Info Gizi', '../assets/img/article4.jpeg', '../assets/img/article44.jpeg', '../assets/img/article444.jpeg', 'NutriMoms', NULL, 1, '2026-06-23 14:49:06', '2026-06-26 17:57:31'),
(5, 'Tanda-tanda Anak Kekurangan Gizi yang Sering Terlewat', 'tanda-anak-kekurangan-gizi', '<p>Kekurangan gizi pada anak tidak selalu terlihat jelas. Banyak orang tua yang tidak menyadari anaknya mengalami malnutrisi karena tanda-tandanya seringkali dianggap biasa...</p><p>Salah satu tanda yang sering terlewat adalah mudah lelah dan kurang bersemangat. Anak yang kekurangan zat besi biasanya terlihat lesu dan tidak bertenaga...</p>', NULL, 'Kenali tanda-tanda kekurangan gizi pada anak yang sering diabaikan orang tua, dari fisik hingga perilaku.', 'Nutrisi Anak', 'https://images.unsplash.com/photo-1555252333-9f8e92e65df9?w=400&q=80', NULL, NULL, 'Kementerian Kesehatan RI', NULL, 1, '2026-06-22 14:49:06', '2026-06-25 14:49:06'),
(6, 'Intermittent Fasting: Apakah Aman untuk Ibu Menyusui?', 'intermittent-fasting-ibu-menyusui', '<p>Intermittent fasting (IF) semakin populer sebagai metode penurunan berat badan. Namun bagaimana dengan ibu menyusui yang ingin kembali ke berat badan ideal setelah melahirkan?</p><p>Secara umum, para ahli tidak merekomendasikan IF untuk ibu menyusui, terutama di 3 bulan pertama. Produksi ASI membutuhkan sekitar 500 kalori ekstra per hari...</p>', NULL, 'Apakah intermittent fasting aman dilakukan saat menyusui? Simak penjelasan ahli gizi sebelum mencobanya.', 'Diet Dewasa', 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=400&q=80', NULL, NULL, NULL, NULL, 1, '2026-06-21 14:49:06', '2026-06-25 14:49:06'),
(7, 'Smoothie Bowl Pelangi untuk Sarapan Bergizi', 'smoothie-bowl-pelangi-sarapan-bergizi', '<p>Smoothie bowl bukan hanya cantik di foto, tapi juga penuh nutrisi untuk mengawali hari. Kombinasi buah-buahan segar, granola, dan topping kreatif menjadikannya sarapan lengkap...</p><p><strong>Bahan dasar:</strong></p><ul><li>1 buah pisang beku</li><li>100g mangga beku</li><li>50ml susu almond</li><li>Topping: granola, stroberi, blueberry, biji chia</li></ul>', NULL, 'Resep smoothie bowl warna-warni yang lezat, bergizi, dan instagrammable untuk sarapan keluarga.', 'Resep Sehat', 'https://images.unsplash.com/photo-1511690743698-d9d85f2fbf38?w=400&q=80', NULL, NULL, NULL, NULL, 1, '2026-06-20 14:49:06', '2026-06-25 14:49:06'),
(8, 'Vitamin D dan Kalsium: Duo Penting untuk Tulang Anak', 'vitamin-d-kalsium-tulang-anak', '<p>Masa pertumbuhan adalah waktu kritis untuk membangun kepadatan tulang yang optimal. Dua nutrisi yang paling berperan adalah Vitamin D dan Kalsium yang bekerja secara sinergis...</p><p>Kalsium adalah bahan utama pembentuk tulang, sedangkan Vitamin D membantu penyerapan kalsium di usus. Tanpa Vitamin D yang cukup, konsumsi kalsium tinggi pun tidak akan optimal...</p>', NULL, 'Kenapa Vitamin D dan Kalsium harus dikonsumsi bersama? Ini penjelasan lengkap untuk mendukung pertumbuhan tulang anak.', 'Info Gizi', 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=400&q=80', NULL, NULL, NULL, NULL, 1, '2026-06-19 14:49:06', '2026-06-25 14:49:06'),
(9, '7 Makanan Tinggi Zat Besi untuk Mencegah Anemia pada Anak', '7-makanan-tinggi-zat-besi-untuk-anak', '<h2>Mengapa Zat Besi Penting untuk Anak?</h2>\r\n\r\n<p>Zat besi merupakan mineral penting yang berperan dalam pembentukan hemoglobin, yaitu protein dalam sel darah merah yang membawa oksigen ke seluruh tubuh. Anak-anak yang kekurangan zat besi berisiko mengalami anemia, mudah lelah, sulit fokus, hingga gangguan tumbuh kembang.</p>\r\n\r\n<p>Pada masa pertumbuhan, kebutuhan zat besi anak meningkat cukup tinggi karena tubuh sedang aktif membentuk jaringan dan sel baru. Sayangnya, banyak anak mengalami kekurangan zat besi akibat pola makan yang kurang seimbang atau kebiasaan memilih makanan tertentu.</p>\r\n\r\n<h2>Tanda Anak Kekurangan Zat Besi</h2>\r\n\r\n<p>Beberapa tanda yang sering muncul antara lain wajah pucat, mudah mengantuk, nafsu makan menurun, sulit berkonsentrasi, dan tubuh mudah lemas. Dalam kondisi tertentu, anak juga dapat menjadi lebih rewel dan kurang aktif dibanding biasanya.</p>\r\n\r\n<h2>1. Daging Merah</h2>\r\n\r\n<p>Daging sapi mengandung zat besi heme yang lebih mudah diserap tubuh dibanding zat besi dari tumbuhan. Berikan dalam bentuk sup, semur, atau bola-bola daging agar lebih mudah dikonsumsi anak.</p>\r\n\r\n<h2>2. Hati Ayam</h2>\r\n\r\n<p>Hati ayam dikenal sebagai salah satu sumber zat besi tertinggi. Selain itu, hati ayam juga mengandung vitamin A dan protein yang baik untuk tumbuh kembang anak.</p>\r\n\r\n<h2>3. Bayam</h2>\r\n\r\n<p>Bayam kaya akan zat besi nabati dan folat. Untuk membantu penyerapannya, kombinasikan bayam dengan makanan tinggi vitamin C seperti tomat atau jeruk.</p>\r\n\r\n<h2>4. Telur</h2>\r\n\r\n<p>Kuning telur mengandung zat besi dan protein yang membantu pembentukan jaringan tubuh anak. Telur juga praktis diolah menjadi berbagai menu sehat.</p>\r\n\r\n<h2>5. Ikan</h2>\r\n\r\n<p>Ikan seperti tuna dan sarden mengandung zat besi sekaligus omega-3 yang baik untuk perkembangan otak anak.</p>\r\n\r\n<h2>6. Kacang-kacangan</h2>\r\n\r\n<p>Kacang merah, kacang hijau, dan edamame dapat menjadi alternatif sumber zat besi nabati yang baik.</p>\r\n\r\n<h2>7. Sereal Fortifikasi</h2>\r\n\r\n<p>Beberapa sereal anak sudah diperkaya zat besi dan vitamin penting lainnya. Pastikan memilih produk dengan kandungan gula yang rendah.</p>\r\n\r\n<h2>Tips Agar Penyerapan Zat Besi Maksimal</h2>\r\n\r\n<p>Hindari memberikan teh atau susu berlebihan bersamaan dengan makanan tinggi zat besi karena dapat menghambat penyerapannya. Sebaliknya, tambahkan buah kaya vitamin C untuk membantu tubuh menyerap zat besi lebih optimal.</p>', '<h2>Kapan Harus ke Dokter?</h2>\r\n\r\n<p>Jika anak terlihat sangat pucat, berat badan sulit naik, atau sering merasa lelah dalam jangka panjang, sebaiknya konsultasikan ke dokter untuk pemeriksaan lebih lanjut.</p>\r\n\r\n<p>Pencegahan anemia sejak dini sangat penting agar anak tetap aktif, sehat, dan mampu belajar dengan optimal setiap hari.</p>', 'Daftar makanan tinggi zat besi yang membantu mencegah anemia pada anak serta tips meningkatkan penyerapan zat besi secara optimal.', 'Nutrisi Anak', '../assets/img/article5.jpeg', '../assets/img/article55.jpeg', '../assets/img/article555.jpeg', 'Ikatan Dokter Anak Indonesia', NULL, 1, '2026-06-26 18:02:30', '2026-06-26 18:02:30'),
(10, 'Panduan Lengkap Mengatasi Anak Susah Makan Tanpa Marah dan Paksaan', 'mengatasi-anak-susah-makan-tanpa-paksaan', '<h2>Mengapa Anak Menjadi Susah Makan?</h2>\r\n\r\n<p>Anak susah makan merupakan masalah yang sangat umum dialami orang tua, terutama pada usia 1 sampai 5 tahun. Pada fase ini anak mulai memiliki preferensi makanan sendiri, rasa ingin tahu yang tinggi, serta keinginan untuk lebih mandiri. Tidak sedikit orang tua merasa frustrasi karena anak hanya mau makan sedikit, memilih makanan tertentu, atau bahkan menolak makan sama sekali.</p>\r\n\r\n<p>Dalam banyak kasus, kondisi ini sebenarnya masih normal selama pertumbuhan anak tetap baik dan aktivitas hariannya tidak terganggu. Namun apabila tidak ditangani dengan tepat, kebiasaan makan yang buruk dapat menyebabkan kekurangan nutrisi dan menghambat tumbuh kembang anak.</p>\r\n\r\n<p>Banyak orang tua langsung memaksa anak makan ketika melihat porsi makan yang sedikit. Padahal tekanan dan paksaan justru dapat membuat anak semakin menolak makanan. Oleh karena itu penting memahami penyebab anak susah makan sebelum menentukan solusi yang tepat.</p>\r\n\r\n<h2>Penyebab Anak Susah Makan</h2>\r\n\r\n<h2>1. Nafsu Makan Menurun Secara Alami</h2>\r\n\r\n<p>Setelah usia satu tahun, pertumbuhan anak tidak secepat saat bayi sehingga kebutuhan kalorinya juga sedikit menurun. Akibatnya anak terlihat makan lebih sedikit dibanding sebelumnya.</p>\r\n\r\n<h2>2. Terlalu Banyak Camilan</h2>\r\n\r\n<p>Anak yang sering mengonsumsi camilan manis atau minuman kemasan sebelum jam makan utama biasanya menjadi cepat kenyang sehingga tidak tertarik makan nasi dan lauk.</p>\r\n\r\n<h2>3. Bosan dengan Menu yang Itu-Itu Saja</h2>\r\n\r\n<p>Anak mudah merasa bosan jika menu makanan tidak bervariasi. Tampilan makanan yang kurang menarik juga membuat anak kehilangan minat makan.</p>\r\n\r\n<h2>4. Sedang Tumbuh Gigi atau Tidak Enak Badan</h2>\r\n\r\n<p>Proses tumbuh gigi dapat menyebabkan gusi terasa tidak nyaman sehingga anak malas makan. Selain itu flu, sariawan, dan gangguan pencernaan juga sering membuat nafsu makan menurun.</p>\r\n\r\n<h2>5. Terlalu Banyak Distraksi</h2>\r\n\r\n<p>Kebiasaan makan sambil menonton televisi atau bermain gadget membuat anak tidak fokus terhadap makanan sehingga proses makan menjadi lebih lama dan tidak efektif.</p>\r\n\r\n<h2>Dampak Jika Anak Terus Susah Makan</h2>\r\n\r\n<p>Jika berlangsung lama, anak berisiko mengalami kekurangan energi dan nutrisi penting seperti protein, zat besi, kalsium, dan vitamin. Kondisi ini dapat memengaruhi pertumbuhan tinggi badan, berat badan, daya tahan tubuh, hingga perkembangan otak anak.</p>\r\n\r\n<p>Anak yang kekurangan nutrisi juga lebih mudah sakit, cepat lelah, sulit berkonsentrasi, dan kurang aktif dibanding teman seusianya.</p>\r\n\r\n<h2>Cara Mengatasi Anak Susah Makan</h2>\r\n\r\n<h2>1. Buat Jadwal Makan yang Teratur</h2>\r\n\r\n<p>Biasakan anak makan utama tiga kali sehari dengan dua kali camilan sehat di antara waktu makan. Jadwal yang konsisten membantu tubuh anak mengenali rasa lapar dan kenyang.</p>\r\n\r\n<h2>2. Hindari Memaksa Anak Makan</h2>\r\n\r\n<p>Memaksa anak makan justru dapat menciptakan trauma terhadap makanan. Berikan makanan dalam porsi kecil terlebih dahulu dan biarkan anak belajar mengenali rasa laparnya sendiri.</p>\r\n\r\n<h2>3. Sajikan Makanan dengan Tampilan Menarik</h2>\r\n\r\n<p>Anak lebih tertarik pada makanan dengan warna cerah dan bentuk lucu. Orang tua dapat membuat nasi berbentuk karakter, memotong buah dengan cetakan unik, atau menyusun sayuran agar terlihat menarik.</p>\r\n\r\n<h2>4. Libatkan Anak Saat Menyiapkan Makanan</h2>\r\n\r\n<p>Anak yang ikut memilih dan membantu menyiapkan makanan biasanya menjadi lebih tertarik untuk mencicipi hasil masakannya sendiri.</p>\r\n\r\n<h2>5. Kurangi Minuman Manis</h2>\r\n\r\n<p>Minuman manis dapat membuat anak cepat kenyang tanpa mendapatkan nutrisi yang cukup. Prioritaskan air putih dan susu sesuai kebutuhan usia anak.</p>\r\n\r\n<h2>6. Jadikan Waktu Makan Menyenangkan</h2>\r\n\r\n<p>Ciptakan suasana makan yang santai tanpa ancaman atau hukuman. Orang tua juga sebaiknya memberi contoh dengan makan makanan sehat bersama anak.</p>\r\n\r\n<h2>7. Perhatikan Porsi Makan</h2>\r\n\r\n<p>Porsi yang terlalu besar sering membuat anak merasa kewalahan sebelum mulai makan. Sajikan porsi kecil terlebih dahulu dan tambahkan bila anak masih lapar.</p>\r\n\r\n<h2>Makanan yang Bisa Membantu Menambah Nafsu Makan Anak</h2>\r\n\r\n<p>Beberapa makanan kaya nutrisi yang baik untuk anak susah makan antara lain telur, alpukat, ikan, sup ayam, yogurt, pisang, oatmeal, dan keju.</p>\r\n\r\n<p>Protein dan lemak sehat membantu menjaga energi serta mendukung pertumbuhan otot dan otak anak.</p>\r\n\r\n<h2>Kesalahan Orang Tua yang Harus Dihindari</h2>\r\n\r\n<p>Banyak orang tua membujuk anak makan dengan hadiah seperti permen atau es krim. Kebiasaan ini justru membuat anak menganggap makanan sehat sebagai sesuatu yang tidak menyenangkan.</p>\r\n\r\n<p>Membentak atau memarahi anak saat makan juga dapat memperburuk hubungan anak dengan makanan.</p>\r\n\r\n<h2>Kapan Harus Konsultasi ke Dokter?</h2>\r\n\r\n<p>Segera konsultasikan ke dokter jika anak mengalami penurunan berat badan drastis, muntah berulang, sulit menelan, terlihat sangat lemas, atau tidak mau makan dalam waktu lama.</p>\r\n\r\n<p>Dokter dapat membantu memastikan apakah terdapat masalah kesehatan tertentu yang memengaruhi nafsu makan anak.</p>', '<h2>Kesimpulan</h2>\r\n\r\n<p>Anak susah makan adalah tantangan yang umum, tetapi dapat diatasi dengan pendekatan yang tepat dan penuh kesabaran. Orang tua perlu memahami bahwa membangun kebiasaan makan sehat membutuhkan proses yang konsisten.</p>\r\n\r\n<p>Fokus utama bukan hanya membuat anak makan banyak, melainkan memastikan anak mendapatkan nutrisi yang cukup untuk tumbuh sehat, aktif, dan bahagia.</p>\r\n\r\n<p>Dengan suasana makan yang nyaman, menu yang bervariasi, serta dukungan orang tua yang positif, anak akan lebih mudah belajar menikmati makanan sehat setiap hari.</p>', 'Panduan lengkap mengatasi anak susah makan dengan cara sehat tanpa paksaan agar kebutuhan nutrisi tetap terpenuhi.', 'Nutrisi Anak', '../assets/img/article8.jpeg', '../assets/img/article88.jpeg', '../assets/img/article888.jpeg', 'Ikatan Dokter Anak Indonesia', NULL, 1, '2026-06-26 18:04:30', '2026-06-26 18:04:30');

-- --------------------------------------------------------

--
-- Table structure for table `dietary_items`
--

CREATE TABLE `dietary_items` (
  `id` int(11) NOT NULL,
  `dietary_log_id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `quantity_type` enum('piece','gram') NOT NULL,
  `quantity_value` decimal(8,2) NOT NULL,
  `calories` decimal(8,2) NOT NULL,
  `protein` decimal(8,2) NOT NULL,
  `carbs` decimal(8,2) NOT NULL,
  `fat` decimal(8,2) NOT NULL,
  `sugar` float NOT NULL DEFAULT 0,
  `sodium` float NOT NULL DEFAULT 0,
  `fiber` float NOT NULL DEFAULT 0,
  `saturated_fat` float NOT NULL DEFAULT 0,
  `cholesterol` float NOT NULL DEFAULT 0,
  `omega_3` float NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `meal_type` enum('pagi','siang','malam','snack') NOT NULL DEFAULT 'pagi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dietary_items`
--

INSERT INTO `dietary_items` (`id`, `dietary_log_id`, `food_id`, `quantity_type`, `quantity_value`, `calories`, `protein`, `carbs`, `fat`, `sugar`, `sodium`, `fiber`, `saturated_fat`, `cholesterol`, `omega_3`, `created_at`, `meal_type`) VALUES
(4, 1, 10, 'piece', 1.00, 96.00, 3.40, 21.00, 1.50, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(5, 1, 9, 'piece', 1.00, 160.00, 2.00, 8.50, 14.70, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(6, 1, 1, 'piece', 1.00, 165.00, 31.00, 0.00, 3.60, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(7, 2, 3, 'piece', 1.00, 155.00, 13.00, 1.10, 11.00, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(8, 2, 2, 'piece', 1.00, 208.00, 20.00, 0.00, 13.00, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(9, 3, 9, 'gram', 50.00, 80.00, 1.00, 4.30, 7.30, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(10, 3, 2, 'piece', 1.00, 208.00, 20.00, 0.00, 13.00, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(11, 3, 10, 'piece', 1.00, 96.00, 3.40, 21.00, 1.50, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(12, 4, 4, 'piece', 1.00, 34.00, 2.80, 6.60, 0.40, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(13, 4, 4, 'gram', 20.00, 6.80, 0.60, 1.30, 0.10, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(14, 4, 1, 'piece', 2.00, 330.00, 62.00, 0.00, 7.20, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(15, 4, 2, 'gram', 50.00, 104.00, 10.00, 0.00, 6.50, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(16, 4, 5, 'piece', 2.00, 260.00, 5.40, 56.00, 0.60, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(17, 5, 1, 'piece', 2.00, 330.00, 62.00, 0.00, 7.20, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(18, 5, 2, 'gram', 124.00, 257.90, 24.80, 0.00, 16.10, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(19, 5, 1, 'gram', 50.00, 82.50, 15.50, 0.00, 1.80, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(20, 5, 5, 'piece', 2.00, 390.00, 8.10, 84.00, 0.90, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(21, 5, 3, 'gram', 30.00, 46.50, 3.90, 0.30, 3.30, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(25, 7, 3, 'piece', 1.00, 155.00, 13.00, 1.10, 11.00, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(27, 7, 2, 'gram', 40.00, 83.20, 8.00, 0.00, 5.20, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(28, 7, 1, 'piece', 2.00, 330.00, 62.00, 0.00, 7.20, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(29, 7, 5, 'gram', 75.00, 97.50, 2.00, 21.00, 0.20, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(30, 7, 7, 'gram', 20.00, 8.20, 0.20, 1.90, 0.00, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(31, 7, 9, 'piece', 1.00, 160.00, 2.00, 8.50, 14.70, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(34, 8, 4, 'piece', 1.00, 34.00, 2.80, 6.60, 0.40, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(35, 6, 9, 'piece', 5.00, 800.00, 10.00, 42.50, 73.50, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(36, 6, 4, 'gram', 50.00, 17.00, 1.40, 3.30, 0.20, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(39, 6, 9, 'piece', 1.00, 240.00, 3.00, 12.80, 22.00, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(40, 6, 5, 'piece', 1.00, 195.00, 4.10, 42.00, 0.40, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(41, 6, 7, 'piece', 1.00, 32.80, 0.70, 7.70, 0.20, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(42, 5, 5, 'piece', 1.00, 195.00, 4.10, 42.00, 0.40, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(43, 5, 7, 'piece', 1.00, 32.80, 0.70, 7.70, 0.20, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(44, 5, 3, 'gram', 100.00, 155.00, 13.00, 1.10, 11.00, 0, 0, 0, 0, 0, 0, '2026-06-14 16:54:21', 'pagi'),
(46, 10, 1, 'gram', 50.00, 82.50, 15.50, 0.00, 1.80, 0, 0, 0, 0, 0, 0, '2026-06-14 17:02:51', 'siang'),
(47, 10, 10, 'gram', 20.00, 19.20, 0.70, 4.20, 0.30, 0, 0, 0, 0, 0, 0, '2026-06-14 17:02:51', 'pagi'),
(48, 10, 6, 'piece', 1.00, 106.80, 1.30, 27.40, 0.40, 0, 0, 0, 0, 0, 0, '2026-06-14 17:02:51', 'pagi'),
(49, 10, 7, 'piece', 1.00, 32.80, 0.70, 7.70, 0.20, 0, 0, 0, 0, 0, 0, '2026-06-14 17:03:18', 'siang'),
(51, 10, 11, 'piece', 1.00, 98.00, 6.80, 0.60, 7.50, 0, 0, 0, 0, 0, 0, '2026-06-14 17:03:18', 'siang'),
(52, 10, 5, 'piece', 1.00, 195.00, 4.10, 42.00, 0.40, 0, 0, 0, 0, 0, 0, '2026-06-14 17:03:41', 'malam'),
(53, 11, 4, 'piece', 1.00, 34.00, 2.80, 6.60, 0.40, 0, 0, 0, 0, 0, 0, '2026-06-14 17:09:44', 'pagi'),
(54, 11, 1, 'piece', 3.00, 594.00, 111.60, 0.00, 13.00, 0, 0, 0, 0, 0, 0, '2026-06-14 17:09:44', 'pagi'),
(55, 11, 8, 'piece', 1.00, 129.00, 2.40, 30.20, 0.20, 0, 0, 0, 0, 0, 0, '2026-06-14 17:09:44', 'pagi'),
(56, 11, 7, 'piece', 5.00, 164.00, 3.60, 38.40, 0.80, 0, 0, 0, 0, 0, 0, '2026-06-14 17:09:44', 'pagi'),
(57, 8, 9, 'piece', 5.00, 1200.00, 15.00, 63.80, 110.30, 0, 0, 0, 0, 0, 0, '2026-06-14 17:12:00', 'malam'),
(58, 12, 1, 'gram', 100.00, 165.00, 31.00, 0.00, 3.60, 0, 0, 0, 0, 0, 0, '2026-06-15 02:00:54', 'pagi'),
(61, 13, 6, 'piece', 1.00, 106.80, 1.30, 27.40, 0.40, 14.6, 1.2, 3.1, 0.1, 0, 32.4, '2026-06-15 06:25:50', 'pagi'),
(62, 13, 9, 'piece', 2.00, 480.00, 6.00, 25.50, 44.10, 0, 0, 0, 0, 0, 0, '2026-06-15 15:51:09', 'snack'),
(63, 13, 1, 'piece', 1.00, 198.00, 37.20, 0.00, 4.30, 0, 0, 0, 0, 0, 0, '2026-06-15 15:51:24', 'malam'),
(64, 13, 3, 'gram', 20.00, 31.00, 2.60, 0.20, 2.20, 0.2, 24.8, 0, 0.7, 74.6, 15.6, '2026-06-15 15:54:27', 'pagi'),
(65, 14, 9, 'piece', 1.00, 240.00, 3.00, 12.80, 22.00, 0, 0, 0, 0, 0, 0, '2026-06-17 03:49:14', 'pagi'),
(66, 13, 1, 'piece', 1.00, 198.00, 37.20, 0.00, 4.30, 0, 0, 0, 0, 0, 0, '2026-06-17 04:11:42', 'pagi'),
(67, 13, 1, 'piece', 1.00, 198.00, 37.20, 0.00, 4.30, 0, 0, 0, 0, 0, 0, '2026-06-17 04:11:42', 'pagi'),
(68, 13, 2, 'piece', 1.00, 312.00, 30.00, 0.00, 19.50, 0, 0, 0, 0, 0, 0, '2026-06-17 04:11:42', 'pagi'),
(69, 13, 7, 'piece', 1.00, 32.80, 0.70, 7.70, 0.20, 0, 0, 0, 0, 0, 0, '2026-06-17 04:11:42', 'pagi'),
(70, 14, 4, 'piece', 1.00, 34.00, 2.80, 6.60, 0.40, 0, 0, 0, 0, 0, 0, '2026-06-17 04:48:33', 'pagi'),
(71, 13, 5, 'piece', 1.00, 195.00, 4.10, 42.00, 0.40, 0, 0, 0, 0, 0, 0, '2026-06-17 04:48:56', 'pagi'),
(72, 13, 4, 'piece', 1.00, 34.00, 2.80, 6.60, 0.40, 0, 0, 0, 0, 0, 0, '2026-06-17 04:51:38', 'siang'),
(73, 13, 5, 'piece', 1.00, 195.00, 4.10, 42.00, 0.40, 0, 0, 0, 0, 0, 0, '2026-06-17 04:51:38', 'siang'),
(74, 13, 3, 'gram', 100.00, 155.00, 13.00, 1.10, 11.00, 0, 0, 0, 0, 0, 0, '2026-06-17 04:51:38', 'siang'),
(75, 13, 6, 'piece', 1.00, 106.80, 1.30, 27.40, 0.40, 0, 0, 0, 0, 0, 0, '2026-06-17 04:51:38', 'siang'),
(76, 15, 9, 'piece', 1.00, 240.00, 3.00, 12.80, 22.00, 0, 0, 0, 0, 0, 0, '2026-06-17 04:57:33', 'pagi'),
(77, 15, 4, 'piece', 2.00, 68.00, 5.60, 13.20, 0.80, 0, 0, 0, 0, 0, 0, '2026-06-17 04:57:33', 'pagi'),
(78, 15, 8, 'piece', 1.00, 129.00, 2.40, 30.20, 0.20, 0, 0, 0, 0, 0, 0, '2026-06-17 04:57:33', 'pagi'),
(80, 15, 9, 'piece', 1.00, 240.00, 3.00, 12.80, 22.10, 1.1, 10.5, 10.1, 3.2, 0, 165, '2026-06-17 06:45:55', 'pagi'),
(81, 14, 10, 'piece', 5.00, 480.00, 17.00, 105.00, 7.50, 31, 75, 10, 1, 0, 75, '2026-06-17 06:46:14', 'siang'),
(82, 14, 10, 'gram', 100.00, 96.00, 3.40, 21.00, 1.50, 6.2, 15, 2, 0.2, 0, 15, '2026-06-17 06:46:26', 'siang'),
(83, 15, 9, 'piece', 1.00, 240.00, 3.00, 12.80, 22.10, 1.1, 10.5, 10.1, 3.2, 0, 165, '2026-06-17 06:50:19', 'pagi'),
(84, 13, 2, 'piece', 1.00, 312.00, 30.00, 0.00, 19.50, 0, 66, 0, 3.6, 82.5, 3390, '2026-06-17 06:57:25', 'pagi'),
(85, 16, 4, 'piece', 1.00, 34.00, 2.80, 6.60, 0.40, 1.7, 33, 2.6, 0, 0, 21, '2026-06-18 10:54:33', 'pagi'),
(86, 16, 9, 'piece', 1.00, 240.00, 3.00, 12.80, 22.10, 1.1, 10.5, 10.1, 3.2, 0, 165, '2026-06-18 10:54:33', 'pagi'),
(87, 16, 9, 'piece', 1.00, 240.00, 3.00, 12.80, 22.10, 1.1, 10.5, 10.1, 3.2, 0, 165, '2026-06-18 10:54:33', 'pagi'),
(88, 16, 4, 'piece', 1.00, 34.00, 2.80, 6.60, 0.40, 1.7, 33, 2.6, 0, 0, 21, '2026-06-18 10:54:33', 'pagi'),
(90, 16, 2, 'piece', 1.00, 312.00, 30.00, 0.00, 19.50, 0, 66, 0, 3.6, 82.5, 3390, '2026-06-18 10:54:33', 'pagi'),
(92, 14, 2, 'piece', 1.00, 312.00, 30.00, 0.00, 19.50, 0, 66, 0, 3.6, 82.5, 3390, '2026-06-18 11:15:02', 'malam'),
(93, 16, 5, 'piece', 1.00, 195.00, 4.10, 42.00, 0.50, 0.2, 1.5, 0.6, 0.2, 0, 0, '2026-06-18 11:33:09', 'malam'),
(94, 17, 1, 'piece', 80.00, 15840.00, 2976.00, 0.00, 345.60, 0, 7104, 0, 96, 8160, 2880, '2026-06-20 13:30:10', 'pagi'),
(95, 17, 2, 'piece', 67.00, 20904.00, 2010.00, 0.00, 1306.50, 0, 4422, 0, 241.2, 5527.5, 227130, '2026-06-20 13:30:33', 'siang'),
(96, 17, 4, 'piece', 1.00, 34.00, 2.80, 6.60, 0.40, 1.7, 33, 2.6, 0, 0, 21, '2026-06-20 13:30:50', 'malam'),
(97, 18, 5, 'piece', 1.00, 195.00, 4.10, 42.00, 0.50, 0.2, 1.5, 0.6, 0.2, 0, 0, '2026-06-20 14:45:15', 'pagi'),
(98, 18, 1, 'piece', 1.00, 198.00, 37.20, 0.00, 4.30, 0, 88.8, 0, 1.2, 102, 36, '2026-06-20 14:45:15', 'pagi'),
(99, 18, 4, 'piece', 1.00, 34.00, 2.80, 6.60, 0.40, 1.7, 33, 2.6, 0, 0, 21, '2026-06-20 14:45:15', 'pagi'),
(101, 18, 5, 'piece', 1.00, 195.00, 4.10, 42.00, 0.50, 0.2, 1.5, 0.6, 0.2, 0, 0, '2026-06-20 14:45:32', 'siang'),
(102, 18, 6, 'piece', 1.00, 106.80, 1.30, 27.40, 0.40, 14.6, 1.2, 3.1, 0.1, 0, 32.4, '2026-06-20 14:45:32', 'siang'),
(103, 18, 11, 'piece', 2.00, 196.00, 13.60, 1.10, 15.00, 0.3, 162, 0, 4.4, 401, 85, '2026-06-20 14:45:32', 'siang'),
(104, 18, 7, 'gram', 50.00, 20.50, 0.50, 4.80, 0.10, 2.4, 34.5, 1.4, 0, 0, 1, '2026-06-20 14:45:32', 'siang'),
(105, 18, 2, 'piece', 1.00, 312.00, 30.00, 0.00, 19.50, 0, 66, 0, 3.6, 82.5, 3390, '2026-06-20 14:45:50', 'malam'),
(106, 18, 10, 'piece', 2.00, 192.00, 6.80, 42.00, 3.00, 12.4, 30, 4, 0.4, 0, 30, '2026-06-20 14:45:50', 'malam'),
(107, 18, 8, 'piece', 1.00, 129.00, 2.40, 30.20, 0.20, 1.2, 9, 3.3, 0, 0, 15, '2026-06-20 14:45:50', 'malam'),
(108, 18, 3, 'piece', 2.00, 155.00, 13.00, 1.10, 11.00, 1.1, 124, 0, 3.3, 373, 78, '2026-06-20 14:47:14', 'pagi'),
(109, 19, 9, 'piece', 1.00, 240.00, 3.00, 12.80, 22.10, 1.1, 10.5, 10.1, 3.2, 0, 165, '2026-06-21 06:47:57', 'pagi'),
(110, 19, 4, 'piece', 1.00, 34.00, 2.80, 6.60, 0.40, 1.7, 33, 2.6, 0, 0, 21, '2026-06-21 09:07:35', 'siang'),
(111, 19, 5, 'piece', 1.00, 195.00, 4.10, 42.00, 0.50, 0.2, 1.5, 0.6, 0.2, 0, 0, '2026-06-21 09:07:35', 'siang'),
(112, 19, 1, 'piece', 1.00, 198.00, 37.20, 0.00, 4.30, 0, 88.8, 0, 1.2, 102, 36, '2026-06-21 09:08:01', 'siang'),
(113, 20, 3, 'piece', 1.00, 77.50, 6.50, 0.60, 5.50, 0.6, 62, 0, 1.7, 186.5, 39, '2026-06-21 09:15:11', 'pagi'),
(114, 19, 28, 'piece', 2.00, 6.90, 0.10, 1.80, 0.00, 1.6, 0.2, 0.1, 0, 0, 2.4, '2026-06-21 10:14:18', 'siang'),
(115, 19, 35, 'piece', 2.00, 285.00, 6.00, 65.00, 2.00, 40, 5, 15, 0, 0, 180, '2026-06-21 10:14:18', 'siang'),
(116, 19, 33, 'piece', 2.00, 163.20, 6.20, 34.30, 2.40, 21.4, 4.8, 13, 0.7, 0, 117.6, '2026-06-21 10:14:18', 'siang'),
(117, 19, 31, 'piece', 2.00, 91.50, 1.70, 22.10, 0.80, 13.5, 4.5, 4.5, 0, 0, 63, '2026-06-21 10:14:18', 'siang'),
(118, 19, 5, 'piece', 1.00, 162.50, 3.40, 35.00, 0.40, 0.1, 1.3, 0.5, 0.1, 0, 0, '2026-06-21 10:14:40', 'malam'),
(119, 19, 14, 'piece', 1.00, 245.00, 25.00, 0.00, 15.50, 0, 210, 0, 4.2, 108, 140, '2026-06-21 10:14:40', 'malam'),
(120, 19, 3, 'piece', 1.00, 77.50, 6.50, 0.60, 5.50, 0.6, 62, 0, 1.7, 186.5, 39, '2026-06-21 10:14:40', 'malam'),
(122, 21, 28, 'piece', 1.00, 3.50, 0.00, 0.90, 0.00, 0.8, 0.1, 0, 0, 0, 1.2, '2026-06-22 08:40:06', 'pagi'),
(123, 21, 30, 'piece', 1.00, 1.10, 0.00, 0.30, 0.00, 0.2, 0, 0, 0, 0, 1.2, '2026-06-22 08:40:06', 'pagi'),
(124, 21, 5, 'piece', 1.00, 162.50, 3.40, 35.00, 0.40, 0.1, 1.3, 0.5, 0.1, 0, 0, '2026-06-22 08:40:28', 'siang'),
(125, 21, 12, 'piece', 1.00, 172.00, 21.60, 0.00, 8.80, 0, 144, 0, 2.5, 73.6, 96, '2026-06-22 08:40:28', 'siang'),
(126, 21, 4, 'gram', 100.00, 34.00, 2.80, 6.60, 0.40, 1.7, 33, 2.6, 0, 0, 21, '2026-06-22 08:40:28', 'siang'),
(127, 21, 31, 'piece', 1.00, 45.80, 0.80, 11.00, 0.40, 6.8, 2.3, 2.3, 0, 0, 31.5, '2026-06-22 08:41:04', 'malam'),
(128, 21, 17, 'piece', 2.00, 76.00, 8.00, 1.90, 4.80, 0.6, 7, 0.3, 0.7, 0, 300, '2026-06-22 08:41:04', 'malam'),
(129, 21, 3, 'piece', 1.00, 77.50, 6.50, 0.60, 5.50, 0.6, 62, 0, 1.7, 186.5, 39, '2026-06-22 08:41:04', 'malam'),
(130, 21, 16, 'gram', 75.00, 202.50, 14.10, 6.70, 15.20, 2, 12, 2.9, 2.2, 0, 240, '2026-06-22 08:41:04', 'malam'),
(131, 22, 5, 'piece', 1.00, 162.50, 3.40, 35.00, 0.40, 0.1, 1.3, 0.5, 0.1, 0, 0, '2026-06-23 06:18:01', 'pagi'),
(132, 22, 1, 'piece', 1.00, 198.00, 37.20, 0.00, 4.30, 0, 88.8, 0, 1.2, 102, 36, '2026-06-23 06:18:01', 'pagi'),
(133, 22, 22, 'piece', 1.00, 93.60, 0.50, 24.80, 0.40, 18.7, 1.8, 4.3, 0, 0, 19.8, '2026-06-23 06:18:13', 'pagi'),
(134, 23, 30, 'piece', 1.00, 1.10, 0.00, 0.30, 0.00, 0.2, 0, 0, 0, 0, 1.2, '2026-06-24 08:30:20', 'pagi'),
(135, 24, 5, 'piece', 2.00, 325.00, 6.80, 70.00, 0.80, 0.3, 2.5, 1, 0.3, 0, 0, '2026-06-25 18:14:00', 'pagi'),
(136, 24, 12, 'piece', 2.00, 344.00, 43.20, 0.00, 17.60, 0, 288, 0, 5, 147.2, 192, '2026-06-25 18:14:00', 'pagi'),
(137, 24, 13, 'piece', 1.00, 137.60, 22.40, 0.00, 5.20, 0, 76, 0, 1.4, 70.4, 88, '2026-06-25 18:17:45', 'siang'),
(138, 24, 14, 'piece', 1.00, 245.00, 25.00, 0.00, 15.50, 0, 210, 0, 4.2, 108, 140, '2026-06-25 18:17:45', 'siang'),
(140, 24, 9, 'piece', 1.00, 240.00, 3.00, 12.80, 22.10, 1.1, 10.5, 10.1, 3.2, 0, 165, '2026-06-25 18:17:45', 'siang'),
(142, 25, 22, 'piece', 1.00, 93.60, 0.50, 24.80, 0.40, 18.7, 1.8, 4.3, 0, 0, 19.8, '2026-06-27 08:37:49', 'pagi'),
(143, 25, 30, 'piece', 1.00, 1.10, 0.00, 0.30, 0.00, 0.2, 0, 0, 0, 0, 1.2, '2026-06-27 08:37:49', 'pagi'),
(144, 25, 5, 'piece', 1.00, 162.50, 3.40, 35.00, 0.40, 0.1, 1.3, 0.5, 0.1, 0, 0, '2026-06-27 08:37:49', 'pagi'),
(145, 26, 5, 'piece', 1.00, 162.50, 3.40, 35.00, 0.40, 0.1, 1.3, 0.5, 0.1, 0, 0, '2026-06-28 06:29:12', 'pagi'),
(146, 26, 2, 'piece', 1.00, 312.00, 30.00, 0.00, 19.50, 0, 66, 0, 3.6, 82.5, 3390, '2026-06-28 06:29:12', 'pagi'),
(147, 26, 22, 'piece', 1.00, 93.60, 0.50, 24.80, 0.40, 18.7, 1.8, 4.3, 0, 0, 19.8, '2026-06-28 06:29:12', 'pagi'),
(148, 26, 5, 'piece', 1.00, 162.50, 3.40, 35.00, 0.40, 0.1, 1.3, 0.5, 0.1, 0, 0, '2026-06-28 06:29:49', 'siang'),
(149, 26, 14, 'piece', 1.00, 245.00, 25.00, 0.00, 15.50, 0, 210, 0, 4.2, 108, 140, '2026-06-28 06:29:49', 'siang'),
(150, 26, 5, 'gram', 50.00, 65.00, 1.40, 14.00, 0.20, 0.1, 0.5, 0.2, 0.1, 0, 0, '2026-06-28 06:30:25', 'malam'),
(151, 26, 4, 'gram', 100.00, 34.00, 2.80, 6.60, 0.40, 1.7, 33, 2.6, 0, 0, 21, '2026-06-28 06:30:25', 'malam'),
(152, 26, 10, 'piece', 1.00, 96.00, 3.40, 21.00, 1.50, 6.2, 15, 2, 0.2, 0, 15, '2026-06-28 06:30:25', 'malam'),
(153, 26, 31, 'piece', 1.00, 45.80, 0.80, 11.00, 0.40, 6.8, 2.3, 2.3, 0, 0, 31.5, '2026-06-28 06:30:25', 'malam'),
(154, 26, 15, 'piece', 1.00, 209.00, 26.00, 0.00, 10.90, 0, 84, 0, 3, 135, 130, '2026-06-28 06:30:25', 'malam'),
(155, 27, 5, 'piece', 1.00, 162.50, 3.40, 35.00, 0.40, 0.1, 1.3, 0.5, 0.1, 0, 0, '2026-06-28 06:34:07', 'pagi'),
(156, 27, 23, 'piece', 1.00, 61.10, 1.20, 15.30, 0.10, 12.2, 0, 3.1, 0, 0, 18.2, '2026-06-28 06:34:07', 'pagi'),
(157, 27, 12, 'piece', 1.00, 172.00, 21.60, 0.00, 8.80, 0, 144, 0, 2.5, 73.6, 96, '2026-06-28 06:34:07', 'pagi'),
(158, 27, 15, 'piece', 1.00, 209.00, 26.00, 0.00, 10.90, 0, 84, 0, 3, 135, 130, '2026-06-28 06:34:07', 'pagi'),
(159, 27, 4, 'gram', 50.00, 17.00, 1.40, 3.30, 0.20, 0.9, 16.5, 1.3, 0, 0, 10.5, '2026-06-28 06:34:07', 'pagi'),
(161, 27, 5, 'piece', 2.00, 325.00, 6.80, 70.00, 0.80, 0.3, 2.5, 1, 0.3, 0, 0, '2026-06-28 06:34:35', 'siang'),
(162, 27, 7, 'piece', 1.00, 32.80, 0.70, 7.70, 0.20, 3.8, 55.2, 2.2, 0, 0, 1.6, '2026-06-28 06:34:35', 'siang'),
(163, 27, 2, 'gram', 70.00, 145.60, 14.00, 0.00, 9.10, 0, 30.8, 0, 1.7, 38.5, 1582, '2026-06-28 06:34:35', 'siang'),
(164, 27, 44, 'piece', 1.00, 87.00, 0.90, 9.40, 5.10, 5.8, 32, 0.2, 2.4, 1, 0, '2026-06-28 07:22:49', 'snack'),
(165, 27, 48, 'piece', 1.00, 86.00, 0.80, 19.20, 0.10, 13.2, 17, 0.1, 0, 0, 0, '2026-06-28 07:22:49', 'snack'),
(166, 27, 5, 'piece', 1.00, 162.50, 3.40, 35.00, 0.40, 0.1, 1.3, 0.5, 0.1, 0, 0, '2026-06-28 07:25:01', 'malam'),
(167, 27, 12, 'piece', 1.00, 172.00, 21.60, 0.00, 8.80, 0, 144, 0, 2.5, 73.6, 96, '2026-06-28 07:25:01', 'malam'),
(168, 26, 49, 'piece', 1.00, 20.00, 0.70, 1.50, 1.20, 0.2, 39, 0.4, 0.2, 0, 50, '2026-06-28 07:25:18', 'snack'),
(169, 26, 42, 'piece', 1.00, 198.00, 1.90, 29.10, 8.10, 14.6, 101, 0.8, 3.7, 0, 0, '2026-06-28 07:25:18', 'snack'),
(170, 27, 22, 'piece', 1.00, 93.60, 0.50, 24.80, 0.40, 18.7, 1.8, 4.3, 0, 0, 19.8, '2026-06-28 07:56:59', 'pagi'),
(171, 28, 21, 'piece', 2.00, 277.50, 6.50, 57.50, 2.30, 1, 12.5, 4.5, 0.5, 0, 62.5, '2026-06-28 08:35:16', 'siang'),
(172, 28, 2, 'piece', 1.00, 312.00, 30.00, 0.00, 19.50, 0, 66, 0, 3.6, 82.5, 3390, '2026-06-28 08:35:16', 'siang'),
(173, 28, 30, 'piece', 1.00, 1.10, 0.00, 0.30, 0.00, 0.2, 0, 0, 0, 0, 1.2, '2026-06-28 08:35:16', 'siang'),
(174, 28, 1, 'piece', 1.00, 198.00, 37.20, 0.00, 4.30, 0, 88.8, 0, 1.2, 102, 36, '2026-06-28 08:35:32', 'pagi'),
(175, 28, 8, 'piece', 1.00, 129.00, 2.40, 30.20, 0.20, 1.2, 9, 3.3, 0, 0, 15, '2026-06-28 08:35:32', 'pagi'),
(177, 28, 36, 'piece', 2.00, 735.00, 7.50, 135.50, 26.50, 99, 10, 19, 11.5, 0, 190, '2026-06-28 08:35:49', 'malam'),
(178, 28, 43, 'piece', 1.00, 95.00, 1.20, 10.80, 5.30, 6.9, 39, 0.3, 2.5, 1, 0, '2026-06-28 08:36:13', 'snack'),
(179, 28, 47, 'piece', 1.00, 86.00, 1.00, 9.20, 5.10, 0.9, 102, 0.5, 2.2, 0, 0, '2026-06-28 08:36:13', 'snack'),
(180, 29, 22, 'piece', 1.00, 93.60, 0.50, 24.80, 0.40, 18.7, 1.8, 4.3, 0, 0, 19.8, '2026-06-28 08:36:36', 'pagi'),
(181, 29, 5, 'piece', 1.00, 162.50, 3.40, 35.00, 0.40, 0.1, 1.3, 0.5, 0.1, 0, 0, '2026-06-28 08:36:36', 'pagi'),
(182, 29, 22, 'piece', 1.00, 93.60, 0.50, 24.80, 0.40, 18.7, 1.8, 4.3, 0, 0, 19.8, '2026-06-28 08:36:36', 'pagi'),
(183, 29, 15, 'piece', 1.00, 209.00, 26.00, 0.00, 10.90, 0, 84, 0, 3, 135, 130, '2026-06-28 08:36:36', 'pagi'),
(184, 29, 8, 'piece', 2.00, 258.00, 4.80, 60.30, 0.30, 2.4, 18, 6.6, 0, 0, 30, '2026-06-28 08:36:55', 'siang'),
(185, 29, 30, 'piece', 1.00, 1.10, 0.00, 0.30, 0.00, 0.2, 0, 0, 0, 0, 1.2, '2026-06-28 08:36:55', 'siang'),
(186, 29, 11, 'piece', 1.00, 98.00, 6.80, 0.60, 7.50, 0.2, 81, 0, 2.2, 200.5, 42.5, '2026-06-28 08:36:55', 'siang'),
(187, 29, 3, 'piece', 1.00, 77.50, 6.50, 0.60, 5.50, 0.6, 62, 0, 1.7, 186.5, 39, '2026-06-28 08:36:55', 'siang'),
(188, 29, 19, 'piece', 1.00, 108.00, 6.40, 0.90, 6.70, 0.2, 84, 0, 1.9, 223.2, 48, '2026-06-28 08:36:55', 'siang'),
(189, 29, 17, 'piece', 1.00, 38.00, 4.00, 1.00, 2.40, 0.3, 3.5, 0.2, 0.4, 0, 150, '2026-06-28 08:37:07', 'malam'),
(190, 29, 8, 'piece', 1.00, 129.00, 2.40, 30.20, 0.20, 1.2, 9, 3.3, 0, 0, 15, '2026-06-28 08:37:07', 'malam'),
(191, 29, 7, 'piece', 1.00, 32.80, 0.70, 7.70, 0.20, 3.8, 55.2, 2.2, 0, 0, 1.6, '2026-06-28 08:37:07', 'malam'),
(192, 29, 10, 'piece', 1.00, 96.00, 3.40, 21.00, 1.50, 6.2, 15, 2, 0.2, 0, 15, '2026-06-28 08:37:07', 'malam'),
(193, 29, 4, 'gram', 100.00, 34.00, 2.80, 6.60, 0.40, 1.7, 33, 2.6, 0, 0, 21, '2026-06-28 08:37:07', 'malam'),
(194, 30, 4, 'gram', 100.00, 34.00, 2.80, 6.60, 0.40, 1.7, 33, 2.6, 0, 0, 21, '2026-06-28 08:37:18', 'pagi'),
(195, 30, 7, 'piece', 1.00, 32.80, 0.70, 7.70, 0.20, 3.8, 55.2, 2.2, 0, 0, 1.6, '2026-06-28 08:37:18', 'pagi'),
(196, 30, 10, 'piece', 1.00, 96.00, 3.40, 21.00, 1.50, 6.2, 15, 2, 0.2, 0, 15, '2026-06-28 08:37:18', 'pagi'),
(197, 30, 8, 'piece', 1.00, 129.00, 2.40, 30.20, 0.20, 1.2, 9, 3.3, 0, 0, 15, '2026-06-28 08:37:18', 'pagi'),
(198, 30, 5, 'piece', 1.00, 162.50, 3.40, 35.00, 0.40, 0.1, 1.3, 0.5, 0.1, 0, 0, '2026-06-28 08:37:29', 'siang'),
(199, 30, 18, 'piece', 1.00, 67.20, 7.10, 2.70, 3.90, 0.2, 3.2, 0.5, 0.8, 0, 147, '2026-06-28 08:37:29', 'siang'),
(200, 30, 3, 'piece', 1.00, 77.50, 6.50, 0.60, 5.50, 0.6, 62, 0, 1.7, 186.5, 39, '2026-06-28 08:37:29', 'siang'),
(201, 30, 19, 'piece', 1.00, 108.00, 6.40, 0.90, 6.70, 0.2, 84, 0, 1.9, 223.2, 48, '2026-06-28 08:37:29', 'siang'),
(202, 30, 14, 'piece', 1.00, 245.00, 25.00, 0.00, 15.50, 0, 210, 0, 4.2, 108, 140, '2026-06-28 08:37:44', 'malam'),
(203, 30, 21, 'piece', 1.00, 138.80, 3.30, 28.80, 1.10, 0.5, 6.3, 2.3, 0.3, 0, 31.3, '2026-06-28 08:37:44', 'malam'),
(204, 30, 22, 'piece', 1.00, 93.60, 0.50, 24.80, 0.40, 18.7, 1.8, 4.3, 0, 0, 19.8, '2026-06-28 08:37:44', 'malam'),
(205, 30, 30, 'piece', 1.00, 1.10, 0.00, 0.30, 0.00, 0.2, 0, 0, 0, 0, 1.2, '2026-06-28 08:37:44', 'malam'),
(206, 30, 35, 'piece', 1.00, 142.50, 3.00, 32.50, 1.00, 20, 2.5, 7.5, 0, 0, 90, '2026-06-28 08:37:44', 'malam'),
(207, 30, 8, 'piece', 1.00, 129.00, 2.40, 30.20, 0.20, 1.2, 9, 3.3, 0, 0, 15, '2026-06-28 08:37:54', 'siang'),
(208, 30, 15, 'piece', 1.00, 209.00, 26.00, 0.00, 10.90, 0, 84, 0, 3, 135, 130, '2026-06-28 08:37:54', 'siang'),
(209, 31, 20, 'piece', 1.00, 159.30, 21.60, 0.00, 7.20, 0, 73.8, 0, 2, 115.2, 103.5, '2026-06-28 08:38:08', 'pagi'),
(210, 31, 1, 'piece', 1.00, 198.00, 37.20, 0.00, 4.30, 0, 88.8, 0, 1.2, 102, 36, '2026-06-28 08:38:08', 'pagi'),
(211, 31, 4, 'gram', 100.00, 34.00, 2.80, 6.60, 0.40, 1.7, 33, 2.6, 0, 0, 21, '2026-06-28 08:38:08', 'pagi'),
(212, 31, 7, 'piece', 1.00, 32.80, 0.70, 7.70, 0.20, 3.8, 55.2, 2.2, 0, 0, 1.6, '2026-06-28 08:38:08', 'pagi'),
(213, 31, 10, 'piece', 1.00, 96.00, 3.40, 21.00, 1.50, 6.2, 15, 2, 0.2, 0, 15, '2026-06-28 08:38:08', 'pagi'),
(214, 31, 8, 'piece', 1.00, 129.00, 2.40, 30.20, 0.20, 1.2, 9, 3.3, 0, 0, 15, '2026-06-28 08:38:23', 'siang'),
(215, 31, 11, 'piece', 1.00, 98.00, 6.80, 0.60, 7.50, 0.2, 81, 0, 2.2, 200.5, 42.5, '2026-06-28 08:38:23', 'siang'),
(216, 31, 17, 'piece', 1.00, 38.00, 4.00, 1.00, 2.40, 0.3, 3.5, 0.2, 0.4, 0, 150, '2026-06-28 08:38:23', 'siang'),
(217, 31, 17, 'piece', 1.00, 38.00, 4.00, 1.00, 2.40, 0.3, 3.5, 0.2, 0.4, 0, 150, '2026-06-28 08:38:23', 'siang'),
(218, 31, 13, 'piece', 1.00, 137.60, 22.40, 0.00, 5.20, 0, 76, 0, 1.4, 70.4, 88, '2026-06-28 08:39:17', 'malam'),
(219, 31, 16, 'piece', 1.00, 121.50, 8.50, 4.00, 9.10, 1.2, 7.2, 1.8, 1.3, 0, 144, '2026-06-28 08:39:17', 'malam'),
(220, 31, 12, 'piece', 1.00, 172.00, 21.60, 0.00, 8.80, 0, 144, 0, 2.5, 73.6, 96, '2026-06-28 08:39:17', 'malam'),
(221, 32, 8, 'piece', 5.00, 645.00, 12.00, 150.80, 0.80, 6, 45, 16.5, 0, 0, 75, '2026-06-28 08:39:33', 'pagi'),
(222, 32, 5, 'piece', 1.00, 162.50, 3.40, 35.00, 0.40, 0.1, 1.3, 0.5, 0.1, 0, 0, '2026-06-28 08:39:43', 'siang'),
(223, 32, 2, 'piece', 1.00, 312.00, 30.00, 0.00, 19.50, 0, 66, 0, 3.6, 82.5, 3390, '2026-06-28 08:39:43', 'siang'),
(224, 32, 36, 'piece', 1.00, 367.50, 3.80, 67.80, 13.30, 49.5, 5, 9.5, 5.8, 0, 95, '2026-06-28 08:39:43', 'siang'),
(225, 33, 5, 'piece', 1.00, 162.50, 3.40, 35.00, 0.40, 0.1, 1.3, 0.5, 0.1, 0, 0, '2026-07-16 05:36:53', 'pagi'),
(226, 33, 2, 'piece', 1.00, 312.00, 30.00, 0.00, 19.50, 0, 66, 0, 3.6, 82.5, 3390, '2026-07-16 05:36:53', 'pagi'),
(227, 33, 10, 'piece', 1.00, 96.00, 3.40, 21.00, 1.50, 6.2, 15, 2, 0.2, 0, 15, '2026-07-16 05:36:53', 'pagi'),
(228, 33, 5, 'piece', 1.00, 162.50, 3.40, 35.00, 0.40, 0.1, 1.3, 0.5, 0.1, 0, 0, '2026-07-16 05:37:09', 'siang'),
(229, 33, 14, 'piece', 1.00, 245.00, 25.00, 0.00, 15.50, 0, 210, 0, 4.2, 108, 140, '2026-07-16 05:37:09', 'siang'),
(230, 33, 44, 'piece', 1.00, 87.00, 0.90, 9.40, 5.10, 5.8, 32, 0.2, 2.4, 1, 0, '2026-07-16 05:37:57', 'snack'),
(231, 33, 22, 'piece', 1.00, 93.60, 0.50, 24.80, 0.40, 18.7, 1.8, 4.3, 0, 0, 19.8, '2026-07-16 05:39:11', 'malam'),
(232, 33, 1, 'piece', 1.00, 198.00, 37.20, 0.00, 4.30, 0, 88.8, 0, 1.2, 102, 36, '2026-07-16 05:39:11', 'malam'),
(233, 33, 7, 'piece', 1.00, 32.80, 0.70, 7.70, 0.20, 3.8, 55.2, 2.2, 0, 0, 1.6, '2026-07-16 05:39:11', 'malam'),
(234, 34, 7, 'piece', 1.00, 32.80, 0.70, 7.70, 0.20, 3.8, 55.2, 2.2, 0, 0, 1.6, '2026-07-16 05:39:37', 'pagi'),
(235, 34, 5, 'piece', 1.00, 162.50, 3.40, 35.00, 0.40, 0.1, 1.3, 0.5, 0.1, 0, 0, '2026-07-16 05:39:37', 'pagi'),
(236, 34, 4, 'gram', 100.00, 34.00, 2.80, 6.60, 0.40, 1.7, 33, 2.6, 0, 0, 21, '2026-07-16 05:39:37', 'pagi'),
(237, 34, 17, 'piece', 1.00, 38.00, 4.00, 1.00, 2.40, 0.3, 3.5, 0.2, 0.4, 0, 150, '2026-07-16 05:39:37', 'pagi'),
(238, 34, 3, 'piece', 1.00, 77.50, 6.50, 0.60, 5.50, 0.6, 62, 0, 1.7, 186.5, 39, '2026-07-16 05:39:37', 'pagi'),
(239, 34, 19, 'piece', 1.00, 108.00, 6.40, 0.90, 6.70, 0.2, 84, 0, 1.9, 223.2, 48, '2026-07-16 05:39:37', 'pagi'),
(240, 34, 17, 'piece', 1.00, 38.00, 4.00, 1.00, 2.40, 0.3, 3.5, 0.2, 0.4, 0, 150, '2026-07-16 05:39:55', 'siang'),
(241, 34, 3, 'piece', 1.00, 77.50, 6.50, 0.60, 5.50, 0.6, 62, 0, 1.7, 186.5, 39, '2026-07-16 05:39:55', 'siang'),
(242, 34, 5, 'piece', 1.00, 162.50, 3.40, 35.00, 0.40, 0.1, 1.3, 0.5, 0.1, 0, 0, '2026-07-16 05:39:55', 'siang'),
(243, 34, 12, 'piece', 1.00, 172.00, 21.60, 0.00, 8.80, 0, 144, 0, 2.5, 73.6, 96, '2026-07-16 05:39:55', 'siang'),
(244, 34, 15, 'piece', 1.00, 209.00, 26.00, 0.00, 10.90, 0, 84, 0, 3, 135, 130, '2026-07-16 05:40:41', 'siang'),
(245, 34, 5, 'piece', 1.00, 162.50, 3.40, 35.00, 0.40, 0.1, 1.3, 0.5, 0.1, 0, 0, '2026-07-16 05:40:41', 'siang'),
(246, 34, 4, 'gram', 100.00, 34.00, 2.80, 6.60, 0.40, 1.7, 33, 2.6, 0, 0, 21, '2026-07-16 05:40:41', 'siang'),
(247, 34, 23, 'piece', 1.00, 61.10, 1.20, 15.30, 0.10, 12.2, 0, 3.1, 0, 0, 18.2, '2026-07-16 05:40:41', 'siang'),
(248, 34, 5, 'piece', 1.00, 162.50, 3.40, 35.00, 0.40, 0.1, 1.3, 0.5, 0.1, 0, 0, '2026-07-16 05:41:08', 'malam'),
(249, 34, 2, 'piece', 1.00, 312.00, 30.00, 0.00, 19.50, 0, 66, 0, 3.6, 82.5, 3390, '2026-07-16 05:41:08', 'malam'),
(250, 35, 2, 'piece', 1.00, 312.00, 30.00, 0.00, 19.50, 0, 66, 0, 3.6, 82.5, 3390, '2026-07-16 05:42:42', 'pagi'),
(251, 35, 21, 'piece', 1.00, 138.80, 3.30, 28.80, 1.10, 0.5, 6.3, 2.3, 0.3, 0, 31.3, '2026-07-16 05:42:42', 'pagi'),
(252, 35, 9, 'piece', 1.00, 240.00, 3.00, 12.80, 22.10, 1.1, 10.5, 10.1, 3.2, 0, 165, '2026-07-16 05:42:42', 'pagi'),
(253, 35, 5, 'piece', 1.00, 162.50, 3.40, 35.00, 0.40, 0.1, 1.3, 0.5, 0.1, 0, 0, '2026-07-16 05:42:42', 'pagi'),
(254, 35, 5, 'piece', 1.00, 162.50, 3.40, 35.00, 0.40, 0.1, 1.3, 0.5, 0.1, 0, 0, '2026-07-16 05:44:01', 'siang'),
(255, 35, 22, 'piece', 1.00, 93.60, 0.50, 24.80, 0.40, 18.7, 1.8, 4.3, 0, 0, 19.8, '2026-07-16 05:44:01', 'siang'),
(256, 35, 30, 'piece', 1.00, 1.10, 0.00, 0.30, 0.00, 0.2, 0, 0, 0, 0, 1.2, '2026-07-16 05:44:01', 'siang'),
(257, 35, 5, 'piece', 1.00, 162.50, 3.40, 35.00, 0.40, 0.1, 1.3, 0.5, 0.1, 0, 0, '2026-07-16 05:44:01', 'siang'),
(258, 35, 5, 'piece', 1.00, 162.50, 3.40, 35.00, 0.40, 0.1, 1.3, 0.5, 0.1, 0, 0, '2026-07-16 05:44:53', 'malam'),
(259, 35, 22, 'piece', 1.00, 93.60, 0.50, 24.80, 0.40, 18.7, 1.8, 4.3, 0, 0, 19.8, '2026-07-16 05:44:53', 'malam'),
(260, 35, 35, 'piece', 1.00, 142.50, 3.00, 32.50, 1.00, 20, 2.5, 7.5, 0, 0, 90, '2026-07-16 05:44:53', 'malam'),
(261, 35, 30, 'piece', 1.00, 1.10, 0.00, 0.30, 0.00, 0.2, 0, 0, 0, 0, 1.2, '2026-07-16 05:44:53', 'malam'),
(262, 35, 23, 'piece', 1.00, 61.10, 1.20, 15.30, 0.10, 12.2, 0, 3.1, 0, 0, 18.2, '2026-07-16 05:44:53', 'malam'),
(263, 35, 33, 'piece', 1.00, 81.60, 3.10, 17.20, 1.20, 10.7, 2.4, 6.5, 0.4, 0, 58.8, '2026-07-16 05:44:53', 'malam'),
(264, 35, 39, 'piece', 1.00, 6.60, 0.10, 1.70, 0.00, 1.5, 0.1, 0.1, 0, 0, 1.2, '2026-07-16 05:44:53', 'malam'),
(265, 35, 31, 'piece', 1.00, 45.80, 0.80, 11.00, 0.40, 6.8, 2.3, 2.3, 0, 0, 31.5, '2026-07-16 05:44:53', 'malam'),
(266, 36, 9, 'piece', 1.00, 240.00, 3.00, 12.80, 22.10, 1.1, 10.5, 10.1, 3.2, 0, 165, '2026-07-16 05:46:33', 'pagi'),
(267, 36, 28, 'piece', 2.00, 6.90, 0.10, 1.80, 0.00, 1.6, 0.2, 0.1, 0, 0, 2.4, '2026-07-16 05:46:33', 'pagi'),
(268, 36, 41, 'piece', 1.00, 210.00, 2.00, 25.00, 12.00, 13, 95, 1, 7, 0, 0, '2026-07-16 05:46:47', 'snack'),
(269, 36, 45, 'piece', 1.00, 60.00, 0.60, 7.70, 2.90, 0.9, 74, 0.3, 1.2, 0, 0, '2026-07-16 05:46:47', 'snack'),
(271, 36, 24, 'piece', 1.00, 120.00, 1.60, 30.00, 0.80, 27.4, 2, 3.2, 0.2, 0, 56, '2026-07-16 06:38:54', 'siang'),
(272, 36, 41, 'piece', 1.00, 210.00, 2.00, 25.00, 12.00, 13, 95, 1, 7, 0, 0, '2026-07-16 06:39:25', 'pagi'),
(273, 37, 28, 'piece', 1.00, 3.50, 0.00, 0.90, 0.00, 0.8, 0.1, 0, 0, 0, 1.2, '2026-08-18 15:24:24', 'pagi'),
(274, 37, 22, 'piece', 1.00, 93.60, 0.50, 24.80, 0.40, 18.7, 1.8, 4.3, 0, 0, 19.8, '2026-08-18 15:24:24', 'pagi'),
(275, 37, 21, 'piece', 1.00, 138.80, 3.30, 28.80, 1.10, 0.5, 6.3, 2.3, 0.3, 0, 31.3, '2026-08-18 15:24:24', 'pagi'),
(276, 37, 5, 'piece', 1.00, 162.50, 3.40, 35.00, 0.40, 0.1, 1.3, 0.5, 0.1, 0, 0, '2026-08-18 15:24:24', 'pagi'),
(277, 37, 28, 'piece', 1.00, 3.50, 0.00, 0.90, 0.00, 0.8, 0.1, 0, 0, 0, 1.2, '2026-08-18 15:24:36', 'siang'),
(278, 37, 5, 'piece', 1.00, 162.50, 3.40, 35.00, 0.40, 0.1, 1.3, 0.5, 0.1, 0, 0, '2026-08-18 15:24:36', 'siang'),
(279, 37, 10, 'piece', 1.00, 96.00, 3.40, 21.00, 1.50, 6.2, 15, 2, 0.2, 0, 15, '2026-08-18 15:24:36', 'siang'),
(280, 37, 15, 'piece', 1.00, 209.00, 26.00, 0.00, 10.90, 0, 84, 0, 3, 135, 130, '2026-08-18 15:24:36', 'siang'),
(281, 37, 1, 'piece', 3.00, 594.00, 111.60, 0.00, 13.00, 0, 266.4, 0, 3.6, 306, 108, '2026-08-18 15:24:43', 'malam'),
(282, 38, 15, 'piece', 1.00, 209.00, 26.00, 0.00, 10.90, 0, 84, 0, 3, 135, 130, '2026-10-17 16:04:53', 'pagi'),
(283, 38, 5, 'piece', 2.00, 325.00, 6.80, 70.00, 0.80, 0.3, 2.5, 1, 0.3, 0, 0, '2026-10-17 16:04:53', 'pagi'),
(284, 38, 28, 'piece', 1.00, 3.50, 0.00, 0.90, 0.00, 0.8, 0.1, 0, 0, 0, 1.2, '2026-10-17 16:04:53', 'pagi'),
(285, 38, 30, 'piece', 1.00, 1.10, 0.00, 0.30, 0.00, 0.2, 0, 0, 0, 0, 1.2, '2026-10-17 16:04:53', 'pagi');

-- --------------------------------------------------------

--
-- Table structure for table `dietary_logs`
--

CREATE TABLE `dietary_logs` (
  `id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `total_calories` decimal(10,2) DEFAULT 0.00,
  `total_protein` decimal(10,2) DEFAULT 0.00,
  `total_carbs` decimal(10,2) DEFAULT 0.00,
  `total_fat` decimal(10,2) DEFAULT 0.00,
  `total_sugar` float NOT NULL DEFAULT 0,
  `total_sodium` float NOT NULL DEFAULT 0,
  `total_fiber` float NOT NULL DEFAULT 0,
  `total_saturated_fat` float NOT NULL DEFAULT 0,
  `total_cholesterol` float NOT NULL DEFAULT 0,
  `total_omega_3` float NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dietary_logs`
--

INSERT INTO `dietary_logs` (`id`, `profile_id`, `log_date`, `total_calories`, `total_protein`, `total_carbs`, `total_fat`, `total_sugar`, `total_sodium`, `total_fiber`, `total_saturated_fat`, `total_cholesterol`, `total_omega_3`, `created_at`) VALUES
(1, 1, '2026-05-17', 421.00, 36.40, 29.50, 19.80, 0, 0, 0, 0, 0, 0, '2026-05-17 05:39:41'),
(2, 2, '2026-05-17', 363.00, 33.00, 1.10, 24.00, 0, 0, 0, 0, 0, 0, '2026-05-17 06:20:21'),
(3, 1, '2026-05-18', 384.00, 24.40, 25.30, 21.80, 0, 0, 0, 0, 0, 0, '2026-05-18 01:37:14'),
(4, 1, '2026-06-07', 734.80, 80.80, 63.90, 14.80, 0, 0, 0, 0, 0, 0, '2026-06-07 07:33:57'),
(5, 1, '2026-06-08', 1489.70, 132.10, 135.10, 40.90, 0, 0, 0, 0, 0, 0, '2026-06-08 01:27:51'),
(6, 1, '2026-06-09', 1284.80, 19.20, 108.30, 96.30, 0, 0, 0, 0, 0, 0, '2026-06-09 04:29:25'),
(7, 1, '2026-06-10', 833.90, 87.20, 32.50, 38.30, 0, 0, 0, 0, 0, 0, '2026-06-10 02:45:01'),
(8, 1, '2026-06-12', 1234.00, 17.80, 70.40, 110.70, 0, 0, 0, 0, 0, 0, '2026-06-14 06:16:19'),
(9, 1, '2026-06-14', 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, '2026-06-14 08:36:02'),
(10, 1, '2026-06-11', 534.30, 29.10, 81.90, 10.60, 0, 0, 0, 0, 0, 0, '2026-06-14 17:02:51'),
(11, 1, '2026-06-13', 921.00, 120.40, 75.20, 14.40, 0, 0, 0, 0, 0, 0, '2026-06-14 17:09:44'),
(12, 5, '2026-06-15', 165.00, 31.00, 0.00, 3.60, 0, 0, 0, 0, 0, 0, '2026-06-15 02:00:54'),
(13, 1, '2026-06-15', 2554.40, 207.50, 179.90, 111.40, 14.8, 92, 3.1, 4.4, 157.1, 3438, '2026-06-15 06:25:50'),
(14, 1, '2026-06-16', 1162.00, 56.20, 145.40, 50.90, 37.2, 156, 12, 4.8, 82.5, 3480, '2026-06-17 03:49:14'),
(15, 1, '2026-06-17', 917.00, 17.00, 81.80, 67.20, 2.2, 21, 20.2, 6.4, 0, 330, '2026-06-17 04:57:33'),
(16, 1, '2026-06-18', 1055.00, 45.70, 80.80, 65.00, 5.8, 332.1, 26, 12.6, 286.5, 3834, '2026-06-18 10:54:33'),
(17, 8, '2026-06-20', 36778.00, 4988.80, 6.60, 1652.50, 1.7, 11559, 2.6, 337.2, 13687.5, 230031, '2026-06-20 13:30:10'),
(18, 1, '2026-06-19', 1733.30, 115.80, 197.20, 54.90, 34.1, 551.5, 15.6, 13.4, 958.5, 3688.4, '2026-06-20 14:45:15'),
(19, 1, '2026-06-21', 1698.60, 96.00, 220.20, 53.90, 80.2, 421.6, 46.4, 11.3, 396.5, 764, '2026-06-21 06:47:57'),
(20, 1, '2026-06-20', 77.50, 6.50, 0.60, 5.50, 0.6, 62, 0, 1.7, 186.5, 39, '2026-06-21 09:15:11'),
(21, 1, '2026-06-22', 774.90, 57.20, 63.00, 35.50, 12.8, 261.7, 8.6, 7.2, 260.1, 729.9, '2026-06-21 23:48:25'),
(22, 1, '2026-06-23', 454.10, 41.10, 59.80, 5.10, 18.8, 91.9, 4.8, 1.3, 102, 55.8, '2026-06-23 06:18:01'),
(23, 10, '2026-06-24', 1.10, 0.00, 0.30, 0.00, 0.2, 0, 0, 0, 0, 1.2, '2026-06-24 08:30:20'),
(24, 1, '2026-06-25', 1291.60, 100.40, 82.80, 61.20, 2.4, 587.1, 11.3, 14.1, 325.6, 590.8, '2026-06-25 18:14:00'),
(25, 1, '2026-06-24', 257.20, 3.90, 60.10, 0.80, 19, 3.1, 4.8, 0.1, 0, 21, '2026-06-27 08:37:49'),
(26, 11, '2026-06-22', 1643.40, 99.30, 178.00, 58.90, 48.5, 555.2, 13.6, 15.2, 325.5, 3797.3, '2026-06-28 06:29:12'),
(27, 11, '2026-06-23', 1726.10, 102.30, 219.70, 45.30, 55.1, 530.4, 13.2, 12.6, 321.7, 1954.1, '2026-06-28 06:34:07'),
(28, 11, '2026-06-24', 1833.60, 85.80, 243.50, 63.20, 109.2, 327.3, 27.6, 21.5, 185.5, 3694.7, '2026-06-28 08:35:16'),
(29, 11, '2026-06-25', 1431.10, 68.20, 213.80, 36.80, 54.3, 449.6, 26, 9.5, 745.2, 532.9, '2026-06-28 08:36:36'),
(30, 11, '2026-06-26', 1666.00, 92.90, 221.30, 47.90, 54.6, 576.3, 28.5, 12.2, 652.7, 713.9, '2026-06-28 08:37:18'),
(31, 11, '2026-06-27', 1254.20, 135.40, 72.10, 49.20, 14.9, 590, 12.3, 11.6, 561.7, 862.6, '2026-06-28 08:38:08'),
(32, 11, '2026-06-28', 1487.00, 49.20, 253.60, 34.00, 55.6, 117.3, 26.5, 9.5, 82.5, 3560, '2026-06-28 08:39:33'),
(33, 11, '2026-07-13', 1389.40, 104.50, 132.90, 47.30, 34.7, 471.4, 9.7, 11.8, 293.5, 3602.4, '2026-07-16 05:36:53'),
(34, 11, '2026-07-14', 1843.90, 126.10, 180.30, 64.40, 21.8, 635.4, 12.9, 15.6, 887.3, 4103.8, '2026-07-16 05:39:37'),
(35, 11, '2026-07-15', 1867.80, 59.10, 309.50, 47.80, 91, 98.9, 42.5, 7.9, 82.5, 3828, '2026-07-16 05:42:42'),
(36, 11, '2026-07-16', 846.90, 9.30, 102.30, 49.80, 57, 276.7, 15.7, 18.6, 0, 223.4, '2026-07-16 05:46:33'),
(37, 9, '2026-08-18', 1463.40, 151.60, 146.40, 27.70, 27.2, 376.3, 9.6, 7.3, 441, 306.5, '2026-08-18 15:24:24'),
(38, 13, '2026-10-17', 538.60, 32.80, 71.20, 11.70, 1.3, 86.6, 1, 3.3, 135, 132.4, '2026-10-17 16:04:53');

-- --------------------------------------------------------

--
-- Table structure for table `foods`
--

CREATE TABLE `foods` (
  `id` int(11) NOT NULL,
  `food_name` varchar(120) NOT NULL,
  `image` text DEFAULT NULL,
  `calories` decimal(8,2) NOT NULL,
  `protein` decimal(8,2) NOT NULL,
  `carbs` decimal(8,2) NOT NULL,
  `fat` decimal(8,2) NOT NULL,
  `base_gram` int(11) DEFAULT 100,
  `category` enum('Protein','Carbs','Fruits','Veggies','Snacks') NOT NULL,
  `piece_gram` float DEFAULT NULL,
  `sugar` float NOT NULL DEFAULT 0,
  `sodium` float NOT NULL DEFAULT 0,
  `fiber` float NOT NULL DEFAULT 0,
  `saturated_fat` float NOT NULL DEFAULT 0,
  `cholesterol` float NOT NULL DEFAULT 0,
  `omega_3` float NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `foods`
--

INSERT INTO `foods` (`id`, `food_name`, `image`, `calories`, `protein`, `carbs`, `fat`, `base_gram`, `category`, `piece_gram`, `sugar`, `sodium`, `fiber`, `saturated_fat`, `cholesterol`, `omega_3`) VALUES
(1, 'Dada Ayam', '../assets/foods/dada_ayam.jpeg', 165.00, 31.00, 0.00, 3.60, 100, 'Protein', 120, 0, 74, 0, 1, 85, 30),
(2, 'Ikan Salmon', '../assets/foods/ikan_salmon.jpg', 208.00, 20.00, 0.00, 13.00, 100, 'Protein', 150, 0, 44, 0, 2.4, 55, 2260),
(3, 'Telur Rebus\r\n', '../assets/foods/telur_rebus.jpg', 155.00, 13.00, 1.10, 11.00, 100, 'Protein', 50, 1.1, 124, 0, 3.3, 373, 78),
(4, 'Brokoli', '../assets/foods/brokoli.jpg', 34.00, 2.80, 6.60, 0.40, 100, 'Veggies', NULL, 1.7, 33, 2.6, 0, 0, 21),
(5, 'Nasi Putih', '../assets/foods/nasi_putih.jpg', 130.00, 2.70, 28.00, 0.30, 100, 'Carbs', 125, 0.1, 1, 0.4, 0.1, 0, 0),
(6, 'Pisang', '../assets/foods/pisang.jpg', 89.00, 1.10, 22.80, 0.30, 100, 'Fruits', 120, 12.2, 1, 2.6, 0.1, 0, 27),
(7, 'Wortel', '../assets/foods/wortel.jpg', 41.00, 0.90, 9.60, 0.20, 100, 'Veggies', 80, 4.7, 69, 2.8, 0, 0, 2),
(8, 'Kentang', '../assets/foods/kentang.jpg', 86.00, 1.60, 20.10, 0.10, 100, 'Carbs', 150, 0.8, 6, 2.2, 0, 0, 10),
(9, 'Alpukat', '../assets/foods/alpukat.jpg', 160.00, 2.00, 8.50, 14.70, 100, 'Fruits', 150, 0.7, 7, 6.7, 2.1, 0, 110),
(10, 'Jagung', '../assets/foods/jagung.jpg', 96.00, 3.40, 21.00, 1.50, 100, 'Veggies', 100, 6.2, 15, 2, 0.2, 0, 15),
(11, 'Telur Ceplok', '../assets/foods/telur_ceplok.jpg', 196.00, 13.60, 1.10, 15.00, 100, 'Protein', 50, 0.3, 162, 0, 4.4, 401, 85),
(12, 'Paha Bawah Ayam (Goreng)', '../assets/foods/paha_bawah_ayam_goreng.jpg', 215.00, 27.00, 0.00, 11.00, 100, 'Protein', 80, 0, 180, 0, 3.1, 92, 120),
(13, 'Paha Bawah Ayam (Rebus)', '../assets/foods/paha_bawah_ayam_rebus.jpg', 172.00, 28.00, 0.00, 6.50, 100, 'Protein', 80, 0, 95, 0, 1.8, 88, 110),
(14, 'Paha Atas Ayam (Goreng)', '../assets/foods/paha_atas_ayam_goreng.jpg', 245.00, 25.00, 0.00, 15.50, 100, 'Protein', 100, 0, 210, 0, 4.2, 108, 140),
(15, 'Paha Atas Ayam (Rebus)', '../assets/foods/paha_atas_ayam_rebus.jpg', 209.00, 26.00, 0.00, 10.90, 100, 'Protein', 100, 0, 84, 0, 3, 135, 130),
(16, 'Tahu Goreng', '../assets/foods/tahu_goreng.jpg', 270.00, 18.80, 8.90, 20.20, 100, 'Protein', 45, 2.7, 16, 3.9, 2.9, 0, 320),
(17, 'Tahu Rebus', '../assets/foods/tahu_rebus.jpg', 76.00, 8.00, 1.90, 4.80, 100, 'Protein', 50, 0.6, 7, 0.3, 0.7, 0, 300),
(18, 'Tempe Goreng', '../assets/foods/tempe_goreng.jpg', 192.00, 20.30, 7.60, 11.00, 100, 'Protein', 35, 0.5, 9, 1.4, 2.3, 0, 420),
(19, 'Telur Dadar', '../assets/foods/telur_dadar.jpg', 180.00, 10.60, 1.50, 11.20, 100, 'Protein', 60, 0.4, 140, 0, 3.1, 372, 80),
(20, 'Paha Ayam Fillet', '../assets/foods/paha_ayam_fillet.jpg', 177.00, 24.00, 0.00, 8.00, 100, 'Protein', 90, 0, 82, 0, 2.2, 128, 115),
(21, 'Nasi Merah', '../assets/foods/nasi_merah.jpg', 111.00, 2.60, 23.00, 0.90, 100, 'Carbs', 125, 0.4, 5, 1.8, 0.2, 0, 25),
(22, 'Apel', '../assets/foods/apel.jpg', 52.00, 0.30, 13.80, 0.20, 100, 'Fruits', 180, 10.4, 1, 2.4, 0, 0, 11),
(23, 'Jeruk', '../assets/foods/jeruk.jpg', 47.00, 0.90, 11.80, 0.10, 100, 'Fruits', 130, 9.4, 0, 2.4, 0, 0, 14),
(24, 'Mangga', '../assets/foods/mangga.jpg', 60.00, 0.80, 15.00, 0.40, 100, 'Fruits', 200, 13.7, 1, 1.6, 0.1, 0, 28),
(25, 'Semangka', '../assets/foods/semangka.jpg', 30.00, 0.60, 7.60, 0.20, 100, 'Fruits', 280, 6.2, 1, 0.4, 0, 0, 5),
(26, 'Melon', '../assets/foods/melon.jpg', 34.00, 0.80, 8.20, 0.20, 100, 'Fruits', 200, 7.9, 16, 0.9, 0.1, 0, 18),
(27, 'Pepaya', '../assets/foods/pepaya.jpg', 43.00, 0.50, 10.80, 0.30, 100, 'Fruits', 150, 7.8, 8, 1.7, 0.1, 0, 12),
(28, 'Anggur', '../assets/foods/anggur.jpg', 69.00, 0.70, 18.10, 0.20, 100, 'Fruits', 5, 15.5, 2, 0.9, 0, 0, 24),
(29, 'Stroberi', '../assets/foods/stroberi.jpg', 32.00, 0.70, 7.70, 0.30, 100, 'Fruits', 12, 4.9, 1, 2, 0, 0, 65),
(30, 'Blueberry', '../assets/foods/blueberry.jpg', 57.00, 0.70, 14.50, 0.30, 100, 'Fruits', 2, 10, 1, 2.4, 0, 0, 58),
(31, 'Kiwi', '../assets/foods/kiwi.jpg', 61.00, 1.10, 14.70, 0.50, 100, 'Fruits', 75, 9, 3, 3, 0, 0, 42),
(32, 'Pir', '../assets/foods/pir.jpg', 57.00, 0.40, 15.00, 0.10, 100, 'Fruits', 180, 9.8, 1, 3.1, 0, 0, 6),
(33, 'Jambu Biji', '../assets/foods/jambu_biji.jpg', 68.00, 2.60, 14.30, 1.00, 100, 'Fruits', 120, 8.9, 2, 5.4, 0.3, 0, 49),
(34, 'Nanas', '../assets/foods/nanas.jpg', 50.00, 0.50, 13.10, 0.10, 100, 'Fruits', 165, 9.9, 1, 1.4, 0, 0, 7),
(35, 'Buah Naga', '../assets/foods/buah_naga.jpg', 57.00, 1.20, 13.00, 0.40, 100, 'Fruits', 250, 8, 1, 3, 0, 0, 36),
(36, 'Durian', '../assets/foods/durian.jpg', 147.00, 1.50, 27.10, 5.30, 100, 'Fruits', 250, 19.8, 2, 3.8, 2.3, 0, 38),
(37, 'Rambutan', '../assets/foods/rambutan.jpg', 68.00, 0.90, 16.50, 0.20, 100, 'Fruits', 30, 13.2, 1, 0.9, 0, 0, 4),
(38, 'Salak', '../assets/foods/salak.jpg', 82.00, 0.40, 22.00, 0.40, 100, 'Fruits', 70, 12.1, 4, 2.6, 0.1, 0, 9),
(39, 'Leci', '../assets/foods/leci.jpg', 66.00, 0.80, 16.50, 0.40, 100, 'Fruits', 10, 15.2, 1, 1.3, 0, 0, 12),
(40, 'Hello Panda Chocolate', '../assets/foods/hello_panda_chocolate.jpeg', 263.00, 3.10, 31.20, 14.00, NULL, 'Snacks', 50, 14.5, 275, 0.4, 7.5, 0, 0),
(41, 'Koalas March Chocolate', '../assets/foods/koalas_march_chocolate.jpeg', 210.00, 2.00, 25.00, 12.00, NULL, 'Snacks', 41, 13, 95, 1, 7, 0, 0),
(42, 'Nextar Pineapple', '../assets/foods/nextar_pineapple.jpeg', 198.00, 1.90, 29.10, 8.10, NULL, 'Snacks', 42, 14.6, 101, 0.8, 3.7, 0, 0),
(43, 'Richoco Wafer Roll Chocolate', '../assets/foods/richoco_wafer_roll_chocolate.jpeg', 95.00, 1.20, 10.80, 5.30, NULL, 'Snacks', 18, 6.9, 39, 0.3, 2.5, 1, 0),
(44, 'Astor Chocolate Stick', '../assets/foods/astor_chocolate_stick.jpeg', 87.00, 0.90, 9.40, 5.10, NULL, 'Snacks', 16, 5.8, 32, 0.2, 2.4, 1, 0),
(45, 'Momogi Jagung Bakar', '../assets/foods/momogi_jagung_bakar.jpeg', 60.00, 0.60, 7.70, 2.90, NULL, 'Snacks', 12, 0.9, 74, 0.3, 1.2, 0, 0),
(46, 'Mie Gemez Original', '../assets/foods/mie_gemez_original.jpeg', 156.00, 2.60, 16.90, 8.70, NULL, 'Snacks', 30, 1.2, 234, 0.8, 3.5, 0, 0),
(47, 'Piattos Cheese', '../assets/foods/piattos_cheese.jpeg', 86.00, 1.00, 9.20, 5.10, NULL, 'Snacks', 16, 0.9, 102, 0.5, 2.2, 0, 0),
(48, 'Blackforest Gummies', '../assets/foods/blackforest_gummies.jpeg', 86.00, 0.80, 19.20, 0.10, NULL, 'Snacks', 25, 13.2, 17, 0.1, 0, 0, 0),
(49, 'Tao Kae Noi Crispy Seaweed', '../assets/foods/tao_kae_noi_crispy_seaweed.jpeg', 20.00, 0.70, 1.50, 1.20, NULL, 'Snacks', 4, 0.2, 39, 0.4, 0.2, 0, 50);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  `type` enum('insight_weekly','achievement','reminder_meal','reminder_water') NOT NULL,
  `title` varchar(150) NOT NULL,
  `body` text NOT NULL,
  `week_start_date` date DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `profile_id`, `type`, `title`, `body`, `week_start_date`, `is_read`, `is_deleted`, `created_at`, `read_at`) VALUES
(3, 1, 'insight_weekly', 'Ringkasan Minggu Lalu', 'Minggu lalu Anda berhasil mencapai target kalori pada 2 dari 7 hari yang tercatat (5 hari belum sesuai target). Masih ada ruang untuk lebih konsisten. Coba catat makanan lebih rutin minggu ini.', '2026-06-08', 1, 0, '2026-06-20 21:09:06', '2026-06-20 21:14:23'),
(4, 1, 'reminder_meal', 'Waktunya Makan Pagi', '10:00', NULL, 0, 1, '2026-06-21 14:02:05', NULL),
(5, 1, 'reminder_meal', 'Waktunya Makan Siang', '14:03', NULL, 1, 0, '2026-06-21 14:02:45', '2026-06-21 14:02:56'),
(6, 1, 'reminder_meal', 'Waktunya Makan Malam', '18:00', NULL, 1, 0, '2026-06-21 14:03:29', '2026-06-21 14:04:19'),
(7, 1, 'reminder_meal', 'Waktunya Makan Siang', '14:29', NULL, 0, 0, '2026-06-21 14:27:57', NULL),
(8, 1, 'insight_weekly', 'Ringkasan Minggu Lalu', 'Minggu lalu Anda berhasil mencapai target kalori pada 2 dari 7 hari yang tercatat (5 hari belum sesuai target). Masih ada ruang untuk lebih konsisten. Coba catat makanan lebih rutin minggu ini.', '2026-06-15', 1, 1, '2026-06-22 06:21:45', '2026-06-22 06:21:58'),
(9, 1, 'reminder_meal', 'Waktunya Makan Siang', '19:00', NULL, 0, 0, '2026-06-27 17:24:13', NULL),
(10, 11, 'reminder_meal', 'Waktunya Makan Siang', '11:00', NULL, 0, 0, '2026-06-28 15:46:09', NULL),
(11, 13, 'reminder_meal', 'Waktunya Makan Pagi', '07:00', NULL, 0, 0, '2026-10-17 23:20:10', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `nutrition_targets`
--

CREATE TABLE `nutrition_targets` (
  `id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  `target_calories` decimal(8,2) NOT NULL,
  `target_protein` decimal(8,2) NOT NULL,
  `target_carbs` decimal(8,2) NOT NULL,
  `target_fat` decimal(8,2) NOT NULL,
  `target_sugar` float NOT NULL DEFAULT 0,
  `target_sodium` float NOT NULL DEFAULT 0,
  `target_fiber` float NOT NULL DEFAULT 0,
  `target_saturated_fat` float NOT NULL DEFAULT 0,
  `target_cholesterol` float NOT NULL DEFAULT 0,
  `target_omega_3` float NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nutrition_targets`
--

INSERT INTO `nutrition_targets` (`id`, `profile_id`, `target_calories`, `target_protein`, `target_carbs`, `target_fat`, `target_sugar`, `target_sodium`, `target_fiber`, `target_saturated_fat`, `target_cholesterol`, `target_omega_3`) VALUES
(1, 1, 1361.10, 20.20, 217.90, 45.40, 25, 1000, 19.1, 10.6, 300, 900),
(2, 2, 1492.50, 22.40, 238.70, 49.80, 25, 1200, 20.9, 11.6, 300, 900),
(3, 3, 2250.00, 37.50, 281.25, 75.00, 25, 1000, 19, 25, 200, 900),
(4, 4, 2000.00, 60.00, 275.00, 66.67, 30, 2000, 30, 22, 300, 1600),
(5, 5, 2118.00, 39.80, 341.40, 65.90, 53, 1500, 29.7, 16.5, 300, 1100),
(6, 6, 2291.70, 62.00, 350.50, 71.30, 30, 1500, 32.1, 17.8, 300, 1600),
(7, 7, 2068.50, 62.00, 312.90, 63.20, 30, 1500, 29, 16.1, 300, 1600),
(8, 8, 2495.30, 62.00, 390.40, 76.20, 30, 1500, 34.9, 19.4, 300, 1600),
(9, 9, 2026.60, 56.00, 311.40, 61.90, 30, 1500, 28.4, 15.8, 300, 1.1),
(10, 10, 2307.20, 62.00, 356.20, 70.50, 30, 1500, 32.3, 17.9, 300, 1600),
(11, 11, 1772.70, 29.70, 280.50, 59.10, 25, 1200, 24.8, 13.8, 300, 900),
(12, 12, 1399.50, 19.70, 225.10, 46.70, 25, 1200, 19.6, 10.9, 300, 900),
(13, 13, 4499.30, 121.00, 666.30, 150.00, 25, 1000, 63, 35, 300, 900);

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE `profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `profile_name` varchar(255) NOT NULL,
  `birth_date` date NOT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `height` decimal(5,2) NOT NULL,
  `weight` decimal(5,2) NOT NULL,
  `activity_level` enum('sedentary','light','moderate','active') NOT NULL DEFAULT 'sedentary',
  `nutrition_target_type` varchar(100) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profiles`
--

INSERT INTO `profiles` (`id`, `user_id`, `profile_name`, `birth_date`, `gender`, `height`, `weight`, `activity_level`, `nutrition_target_type`, `photo`) VALUES
(1, 1, 'Rafi', '2024-09-10', 'Male', 75.00, 18.00, 'light', 'Standard', 'child_1_1782112766.jpg'),
(2, 2, 'arya', '2021-04-15', 'Male', 110.00, 20.00, 'light', 'Standard', NULL),
(3, 4, 'Dji', '2023-01-20', 'Male', 80.00, 25.00, 'sedentary', 'Active', NULL),
(4, 5, 'Arya', '1996-05-25', 'Male', 168.00, 74.00, 'sedentary', 'Active', NULL),
(5, 6, 'Sarah', '2003-08-04', 'Female', 165.00, 49.70, 'light', 'Tinggi', NULL),
(6, 7, 'Rafi2', '2004-07-04', 'Male', 170.00, 51.60, 'light', 'Standard', NULL),
(7, 8, 'rafi3', '2003-07-04', 'Male', 170.00, 52.00, 'sedentary', 'Standard', NULL),
(8, 9, 'Cencen', '2003-04-05', 'Male', 190.00, 62.00, 'sedentary', 'Tinggi', NULL),
(9, 10, 'Muhammad Rafi', '2003-07-04', 'Female', 170.00, 51.60, 'light', 'Standard', NULL),
(10, 1, 'Rafi 2', '2004-07-04', 'Male', 170.00, 53.50, 'light', 'Standard', 'child_10_1782551690.jpg'),
(11, 13, 'Balqis', '2018-07-05', 'Female', 122.00, 27.00, 'light', 'Weight Gain', NULL),
(12, 13, 'Zami', '2022-03-08', 'Male', 100.00, 17.60, 'light', 'Standard', NULL),
(13, 12, 'Rafi', '2026-10-17', 'Male', 80.00, 108.00, 'moderate', 'Standard', 'child_13_1792253980.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `profile_weights`
--

CREATE TABLE `profile_weights` (
  `id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  `weight` decimal(4,2) NOT NULL,
  `logged_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `profile_weights`
--

INSERT INTO `profile_weights` (`id`, `profile_id`, `weight`, `logged_date`, `created_at`) VALUES
(1, 1, 16.00, '2026-06-21', '2026-06-21 06:31:00'),
(2, 1, 17.00, '2026-06-21', '2026-06-21 06:31:08'),
(3, 1, 17.50, '2026-06-21', '2026-06-21 06:53:32'),
(4, 1, 18.00, '2026-06-23', '2026-06-23 10:21:52'),
(5, 10, 53.00, '2026-06-24', '2026-06-24 08:59:34'),
(6, 10, 53.50, '2026-06-24', '2026-06-24 08:59:42'),
(7, 11, 25.00, '2026-06-28', '2026-06-28 08:44:46'),
(8, 11, 25.50, '2026-06-28', '2026-06-28 08:44:51'),
(9, 11, 27.00, '2026-06-28', '2026-06-28 08:44:56'),
(10, 11, 26.50, '2026-06-28', '2026-06-28 08:45:01'),
(11, 11, 29.00, '2026-06-28', '2026-06-28 08:45:07'),
(12, 11, 27.00, '2026-06-28', '2026-06-28 08:45:11');

-- --------------------------------------------------------

--
-- Table structure for table `saved_articles`
--

CREATE TABLE `saved_articles` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT 'Relasi ke tabel users (level akun)',
  `article_id` int(10) UNSIGNED NOT NULL,
  `saved_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `saved_articles`
--

INSERT INTO `saved_articles` (`id`, `user_id`, `article_id`, `saved_at`) VALUES
(2, 1, 8, '2026-06-25 20:15:02'),
(6, 1, 1, '2026-06-26 01:29:54'),
(8, 1, 9, '2026-06-27 17:27:32'),
(9, 13, 9, '2026-06-28 15:43:04'),
(10, 13, 2, '2026-06-28 15:43:14'),
(11, 13, 3, '2026-06-28 15:43:16'),
(12, 13, 4, '2026-06-28 15:43:17'),
(13, 13, 10, '2026-07-16 13:41:50'),
(14, 10, 10, '2026-08-18 22:16:24'),
(15, 10, 9, '2026-08-18 22:16:25'),
(16, 12, 1, '2026-10-17 23:17:33'),
(17, 12, 3, '2026-10-17 23:17:34'),
(18, 12, 4, '2026-10-17 23:17:35');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`, `photo`) VALUES
(1, 'Muhammad Rafi', 'rafirafi@gmail.com', '$2y$10$rmEfnawM6YtOf.OfIG.I7.jK.iuYyENDLtgFjJ7fw1qySQ.SiNYy2', '2026-05-17 05:18:18', 'user_1_1780821979.jpg'),
(2, 'Arya Octavianus', 'arya@gmail.com', '$2y$10$zXZj70wM3BqvQM/8RwfmSelfOeoF9WdG23f1bwqE/JcENV74ys2N2', '2026-05-17 06:19:53', NULL),
(3, 'Favian', 'favian@gmail.com', '$2y$10$BkWZkE791XjISz2S/pss3emiJfHVkLswIqjZa9947HlHx4B5gfV/e', '2026-05-18 01:38:27', NULL),
(4, 'Banon', 'banon@gmail.com', '$2y$10$NKRzVTC8xUsWJY2zik06Tu5q2wrwt1KVTR8rzkcrRt.nK4b5w/iVy', '2026-06-09 04:27:41', NULL),
(5, 'Muhammad Arya Octavianus', 'arya.octavianus123@gmail.com', '$2y$10$cv0qkH12HY6yZslQoCwfPubEcjanmUfY4.TYm/nJERH5BIXQd0DI2', '2026-06-15 01:56:42', NULL),
(6, 'Sarah Sakinah', 'sarah@gmail.com', '$2y$10$77DyxkW6m1NqHNIfKfsfQuNlP1BKM7JCzoZCTH3u0UBbrtIKRz8S.', '2026-06-17 07:07:54', NULL),
(7, 'Rafi 2', 'rafiusakti@gmail.com', '$2y$10$VoeuaPnVjEVnv6ZrFFwBleLpTTendbvYuOXr4.TDLO1EYp45LfDOq', '2026-06-17 07:21:28', NULL),
(8, 'rafi3@gmail.com', 'rafi3@gmail.com', '$2y$10$15bN9nuo0oaqMFSEm2nOg.nEtdkTgG1/kRo/JuMy.02RqO4MU8Wi.', '2026-06-17 07:31:34', NULL),
(9, 'fav', 'a@gmail.com', '$2y$10$o6CP.dVQst6Uv2FYODLJHOzWTO4IUeRPB3R8sGhdSv6E6olZjx4ki', '2026-06-20 13:27:44', NULL),
(10, 'Muhammad Rafi', 'reprafs@gmail.com', '$2y$10$tdZzq9m799fBiINzg4vkyOEK7FUWrfIlhDUQqyhI14BXGaxuv9RZW', '2026-06-22 07:35:35', NULL),
(11, 'rrfrf', 'dfvr@gmail.com', '$2y$10$5j2Ac7jRoWHjCQNvmM1O8umvRJ1GNY778wlN9OC5JW7hVfkS/sgA6', '2026-06-23 07:25:18', NULL),
(12, 'mama', 'mama@gmail.com', '$2y$10$aiutkbAnCJriMTZjkgZBcuESRcj5ye33v9kRoAer8q7jADhD27AcS', '2026-06-24 07:07:03', NULL),
(13, 'Fitriah', 'fitriah@gmail.com', '$2y$10$68VePwHgO6IVIUny3nWzaODCN4t5VuDp53jQVXGTHmYqj7KqlownW', '2026-06-26 10:10:38', NULL),
(14, 'Mutiara N', 'tiaragold100@gmail.com', '$2y$10$sxeI0MbocEnrtxZv2kRvXOavQu1PmRcJE8.PddyV4Lf6Pgg7WBwwm', '2026-06-27 10:48:14', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_published` (`is_published`,`created_at`);

--
-- Indexes for table `dietary_items`
--
ALTER TABLE `dietary_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_item_log` (`dietary_log_id`),
  ADD KEY `fk_item_food` (`food_id`);

--
-- Indexes for table `dietary_logs`
--
ALTER TABLE `dietary_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_log_user` (`profile_id`);

--
-- Indexes for table `foods`
--
ALTER TABLE `foods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_profile_unread` (`profile_id`,`is_read`),
  ADD KEY `idx_profile_week` (`profile_id`,`week_start_date`),
  ADD KEY `idx_profile_deleted` (`profile_id`,`is_deleted`);

--
-- Indexes for table `nutrition_targets`
--
ALTER TABLE `nutrition_targets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_child_user` (`user_id`);

--
-- Indexes for table `profile_weights`
--
ALTER TABLE `profile_weights`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_profile_date` (`profile_id`,`logged_date`);

--
-- Indexes for table `saved_articles`
--
ALTER TABLE `saved_articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_article` (`user_id`,`article_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_article_id` (`article_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `dietary_items`
--
ALTER TABLE `dietary_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=286;

--
-- AUTO_INCREMENT for table `dietary_logs`
--
ALTER TABLE `dietary_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `foods`
--
ALTER TABLE `foods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `nutrition_targets`
--
ALTER TABLE `nutrition_targets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `profile_weights`
--
ALTER TABLE `profile_weights`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `saved_articles`
--
ALTER TABLE `saved_articles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dietary_items`
--
ALTER TABLE `dietary_items`
  ADD CONSTRAINT `fk_item_food` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_item_log` FOREIGN KEY (`dietary_log_id`) REFERENCES `dietary_logs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dietary_logs`
--
ALTER TABLE `dietary_logs`
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`profile_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `profiles`
--
ALTER TABLE `profiles`
  ADD CONSTRAINT `fk_child_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `profile_weights`
--
ALTER TABLE `profile_weights`
  ADD CONSTRAINT `fk_profile_weights_profile` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `saved_articles`
--
ALTER TABLE `saved_articles`
  ADD CONSTRAINT `fk_saved_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
