# 🚀 VDownload Pro - Multi-Platform Video, Photo & Music Downloader

[![Version](https://img.shields.io/badge/Version-v2.5.0-0284c7?style=for-the-badge&logo=git&logoColor=white)](https://github.com/csbaguosps-stack/vdownload)
[![Extension](https://img.shields.io/badge/Extension-v1.6.0-10b981?style=for-the-badge&logo=googlechrome&logoColor=white)](extension/)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/License-Proprietary-0284c7?style=for-the-badge)](mailto:cs.baguosps@gmail.com)
[![Developer](https://img.shields.io/badge/Developer-cs.baguosps%40gmail.com-0ea5e9?style=for-the-badge&logo=gmail&logoColor=white)](mailto:cs.baguosps@gmail.com)

<p align="center">
  <img src="assets/img/preview.png" alt="VDownload Pro Preview" width="100%">
</p>

**VDownload Pro** adalah aplikasi web dan ekstensi browser modern untuk mengunduh video, foto/gambar, dan audio musik dari **YouTube**, **YouTube Music**, **TikTok**, **Instagram (Reels & Album Foto)**, dan **Shopee Video** tanpa tanda air (*no watermark*) dengan pilihan resolusi lengkap **1080p Full HD**, **720p HD**, dan **360p SD Hemat Kuota**, audio MP3 320kbps, pratinjau galeri carousel interaktif, serta paket unduhan berkas ZIP instan.

Dilengkapi dengan mesin **Kompresi Cerdas Berakselerasi GPU NVIDIA (NVENC)** agar berkas video hemat kuota hingga ~60% **tanpa mengurangi ketajaman video (anti-pecah)**, serta memiliki **Ekstensi Browser Standalone (v1.6.0)** resmi untuk kemudahan unduh 1-klik langsung di halaman web peramban Anda.

---

## 🆕 Catatan Rilis Versi 2.5.0 (Changelog)

* **🛡️ Riwayat Unduhan Berbasis Privasi & Pelacakan Tautan Asal (Link Tracking)**:
  * **Penyimpanan Nama & Link Saja**: Riwayat unduhan kini hanya mencatat nama/judul unduhan (`title`) dan tautan asli video (`url`) sebagai log aktivitas pelacakan (*tracking*).
  * **Zero Storage Bloat (File Fisik Tidak Disimpan di Server)**: Berkas media (video/audio/foto) tidak lagi disimpan di server atau di dalam history, menjaga privasi pengguna dan menghemat ruang penyimpanan server 100%.
  * **Panel Link Tracking Interaktif**: Kartu riwayat dilengkapi panel *Link Asli (Tracking)* dengan tombol **Salin Link** instan ke clipboard dan tombol **Buka** untuk langsung memeriksa atau mengunduh ulang video.
  * **Integrasi Parameter `?url=` di Beranda**: Navigasi dari riwayat langsung mengisi input unduhan dan menganalisa video secara otomatis tanpa langkah manual.
* **🧹 Manajemen & Pembersihan Berkas Server Otomatis (Auto-Cleanup & Pruning Engine)**:
  * **Unlink On Completed Stream**: Berkas video sementara hasil penggabungan (*merge*) atau kompresi otomatis langsung dihapus (`@unlink`) dari server saat transmisi unduhan ke peramban selesai.
  * **Auto-Pruner Berkas Sementara**: Menambahkan mekanisme `MediaCompressor::pruneOldFiles()` untuk membersihkan berkas sisa/sementara yang berumur lebih dari 5 menit.
* **🔑 Integrasi Modal Login & Session Cookie Instagram**:
  * Ditambahkan modal panduan sesi Instagram (`sessionid`) langsung pada antarmuka pengguna untuk mengatasi video Instagram Reels / Postingan yang dibatasi verifikasi login.

---

## 📋 Catatan Rilis Versi 2.4.0 (Changelog)

* **⚡ Turbo Acceleration Engine (Unduhan YouTube & YouTube Music Berkecepatan Tinggi)**:
  * **Integrasi Engine Deno JavaScript Runtime**: Menyelesaikan pemecahan *n-token cipher & JS challenges* YouTube secara otomatis, menghilangkan pembatasan kecepatan (*throttling*) YouTube dari ~50-100 KB/s melonjak drastis hingga **15–27 MB/s**.
  * **Akselerasi Multi-Fragment & Chunking 10MB**: Menambahkan parameter unduhan paralel 5 fragmen (`--concurrent-fragments 5`), chunk buffer 10MB (`--http-chunk-size 10M`), serta alokasi buffer 1024k untuk stabilitas maksimal.
  * **Direct Streaming Progressive Streams & M4A**: Format 720p & 360p (*progressive stream* yang sudah memiliki audio) serta M4A native dialirkan langsung tanpa perlu penggabungan ulang (*re-merge*) di server yang memakan waktu.
  * **Pipeline Transcoding MP3 320k Kilat**: Mengonversi berkas audio YouTube Music menjadi MP3 320k berkualitas studio hanya dalam ~4-8 detik menggunakan FFmpeg multithreading CPU penuh (`-threads 0`).
  * **Dukungan HTTP Range Requests & Resumable Downloads**: Menambahkan header `Accept-Ranges: bytes` dan status `206 Partial Content` di server streaming, memungkinkan unduhan paralel multi-koneksi lewat peramban atau *Download Manager* (IDM).
  * **Cache Metadata Cerdas (30 Menit)**: Mengurangi waktu respon analisis URL berulang menjadi hanya **0.01 detik**.
* **🧩 Pembaruan Ekstensi Browser ke v1.6.0**:
  * Sinkronisasi parameter `needs_merge` untuk rute unduhan Full HD 1080p, 720p, 360p, dan MP3 320k turbo.
  * Peningkatan keandalan penanganan URL stream langsung dan kompatibilitas Manifest V3.

---

## 📋 Catatan Rilis Versi 2.3.0

* **🎬 Dukungan Pilihan Resolusi Lengkap: 1080p, 720p, & 360p (Baru)**:
  * **⭐ 1080p Full HD**: Kualitas visual tertinggi (*pristine clarity*) dengan audio jernih tanpa kompresi berlebih untuk layar TV dan laptop.
  * **HD 720p**: Keseimbangan sempurna antara kejernihan gambar dan ukuran berkas yang efisien.
  * **⚡ SD 360p (Hemat Kuota)**: Pilihan ekstra ringan khusus untuk smartphone hemat kuota dan koneksi seluler tanpa buffering.
  * **Berlaku di Semua Platform**: YouTube, YouTube Music, TikTok, Instagram Reels, Shopee Video, dan Ekstensi Browser Standalone.
* **⚡ Mesin Penskalaan Cerdas Aspek Rasio (Aspect Ratio Scaling Engine)**:
  * Mendukung penskalaan otomatis baik untuk video lanskap (16:9) maupun video vertikal potret (9:16) secara presisi tanpa memotong bingkai (*no cropping*).
* **🎧 Integrasi Penuh YouTube Music (`music.youtube.com`)**:
  * Pilihan unduhan MP3 320k Studio, M4A native, video 1080p, 720p, 360p, dan Artwork Cover Album HD.
* **🧩 Pembaruan Ekstensi Browser ke v1.5.0**:
  * Opsi tombol unduhan 1080p, 720p, dan 360p terintegrasi langsung di popup ekstensi.
* **📸 Unduh Foto & Carousel Instagram**:
  * Mendukung unduhan foto tunggal dan album carousel multi-foto Instagram beresolusi penuh (1080p).
  * Opsi tombol instan: **Unduh Semua Foto Sekaligus (.ZIP)** untuk kemudahan mengunduh seluruh album dalam 1 klik.
  * Parser JavaScript Snapsave canggih di sisi server dengan multi-layer fallback ke Instagram GraphQL.
* **🎵 Unduh Foto Slideshow TikTok**:
  * Mendukung unduh gambar TikTok Photo Mode / Slideshow tanpa watermark kualitas HD asli.
  * Dilengkapi opsi unduhan paket bundel ZIP serta ekstraksi audio musik MP3.
* **🖼️ Pratinjau Galeri Interaktif (Carousel Viewer)**:
  * Halaman hasil pencarian dilengkapi slider pratinjau visual dengan kontrol navigasi (*Prev / Next*) dan penghitung jumlah foto (`1 / N`).
* **🧩 Pembaruan Ekstensi Browser Standalone**:
  * **CORS Preflight & Universal Origin**: Menambahkan header CORS penuh dan penanganan `OPTIONS` di backend sehingga ekstensi terhubung lancar tanpa blokir browser.
  * **Multi-Layer Fallback**: Server API -> Content Script DOM Extraction (`srcset` 1080p) -> Standalone Extractor.
  * **Tampilan Khusus**: Tombol unduhan bergradien khusus untuk musik YT Music (`🎧`), foto HD (`🖼️`), dan paket ZIP (`📦`).

---

## 📑 Daftar Isi

- [🆕 Catatan Rilis Versi 2.5.0 (Changelog)](#-catatan-rilis-versi-250-changelog)
- [📋 Catatan Rilis Versi 2.4.0 (Changelog)](#-catatan-rilis-versi-240-changelog)
- [🎯 Ringkasan Dukungan Platform](#-ringkasan-dukungan-platform)
- [✨ Fitur Unggulan](#-fitur-unggulan)
  - [1. 🎬 Multi-Platform Extractor (Video, Foto & Musik)](#1--multi-platform-extractor-video-foto--musik)
  - [2. ⚡ Mesin Kompresi GPU NVENC & CPU (Anti-Pecah)](#2--mesin-kompresi-gpu-nvenc--cpu-anti-pecah)
  - [3. 🧩 Ekstensi Browser Standalone](#3--ekstensi-browser-standalone)
  - [4. 🎨 Desain Modern & Responsif](#4--desain-modern--responsif)
- [🛠️ Persyaratan Sistem](#️-persyaratan-sistem)
- [🚀 Panduan Mulai Cepat (Quick Start)](#-panduan-mulai-cepat-quick-start)
  - [A. Menjalankan Website (XAMPP / Laragon)](#a-menjalankan-website-xampp--laragon)
  - [B. Memasang Ekstensi Browser (Chrome / Edge / Brave)](#b-memasang-ekstensi-browser-chrome--edge--brave)
- [⚙️ Pengaturan FFmpeg & Akselerasi GPU](#️-pengaturan-ffmpeg--akselerasi-gpu)
- [📂 Struktur Direktori Proyek](#-struktur-direktori-proyek)
- [❓ Tanya Jawab & Solusi Masalah (FAQ)](#-tanya-jawab--solusi-masalah-faq)
- [🔒 Kebijakan & Penggunaan Bijak](#-kebijakan--penggunaan-bijak)
- [📄 Hak Cipta & Pengembang](#-hak-cipta--pengembang)

---

## 🎯 Ringkasan Dukungan Platform

| Platform | Kualitas Video | Kualitas Foto / Album | Audio Musik | Paket ZIP | Keterangan Khusus |
| :--- | :---: | :---: | :---: | :---: | :--- |
| **YouTube & YT Music** | ✅ Full HD 1080p, 720p, SD | ✅ Cover Album / Thumbnail HD | ✅ **MP3 320k HQ & M4A** | — | Mendukung YouTube Music (`music.youtube.com`), Shorts (`/shorts/`), Video Standar |
| **TikTok** | ✅ Full HD & Clean SD (No WM) | ✅ **Foto HD Asli (Slideshow)** | ✅ MP3 Original | ✅ **Paket ZIP Semua Foto** | Tanpa watermark, ekstraksi multi-foto & lagu |
| **Instagram** | ✅ Full HD Reels & Feed Video | ✅ **Foto HD & Carousel Album** | *(Termasuk dlm video)* | ✅ **Paket ZIP Semua Foto** | Mendukung Reels, post foto tunggal & album carousel multi-slide |
| **Shopee Video** | ✅ **⭐ True Master HD** & SD | ✅ Cover HD JPG | *(Termasuk dlm video)* | — | Stream VOD murni 100% tanpa watermark UI Shopee |

---

## ✨ Fitur Unggulan

### 1. 🎬 Multi-Platform Extractor (Video & Foto)
* **Instagram (Reels & Album Foto)**:
  * Mendukung unduhan **Reels** resolusi HD tanpa batas.
  * Mendukung unduhan **Foto Tunggal & Album Carousel** (hingga resolusi asli 1080p).
  * Opsi **Unduh Semua Foto Sekaligus (.ZIP)** yang otomatis merangkum semua foto carousel dalam satu file ZIP.
* **TikTok (Video & Slideshow Foto)**:
  * Menghapus watermark bawaan TikTok secara otomatis (No Watermark True HD).
  * Mendukung unduhan **TikTok Photo Mode / Slideshow** baik per-foto HD maupun bundel **ZIP**.
  * Ekstraksi lagu pengiring (background music) dalam format MP3 jernih.
* **YouTube**:
  * Unduh Video resolusi 1080p Full HD, 720p HD, serta 360p SD.
  * Unduh berkas audio musik murni berformat MP3 / M4A kualitas tinggi.
  * Otomatis mengenali tautan YouTube Shorts, Video Standar, dan YouTube Music.
* **Shopee Video**:
  * **⭐ True Master HD**: Mengunduh berkas video asli sebelum dikompresi oleh template UI Shopee (100% tanpa watermark).
  * Opsi unduh Web SD hemat penyimpanan untuk pengguna seluler.

---

### 2. ⚡ Mesin Kompresi GPU NVENC & CPU (Anti-Pecah)
* **Akselerasi Kartu Grafis (NVIDIA NVENC)**:
  * Menggunakan encoder hardware `h264_nvenc` untuk merender kompresi video dalam hitungan detik (~5–7 detik untuk video 1 menit).
  * Otomatis beralih ke kompresi CPU multi-threaded jika komputer tidak memiliki kartu grafis NVIDIA.
* **Algoritma Kristal Anti-Pecah**:
  * Menjaga resolusi asli 100% (1080p tetap 1080p, 720p tetap 720p).
  * Menggunakan *Adaptive Quantization* dan nilai CRF seimbang sehingga detail wajah dan teks tetap tajam tanpa artefak kotak-kotak.
* **FastStart Ready**:
  * Penempatan moov atom di awal file (`+faststart`) agar video dapat langsung diputar secara instan di semua pemutar video HP dan laptop.

---

### 3. 🧩 Ekstensi Browser Standalone (v1.6.0)
* Terletak di direktori `extension/` berbasis **Manifest V3**.
* **100% Mandiri (Standalone)**: Dapat langsung mengunduh video & foto dari peramban tanpa perlu server lokal aktif.
* **Dukungan CORS Penuh**: Sinkronisasi instan dengan server VDownload lokal dengan izin preflight HTTP `OPTIONS`.
* **Multi-Layer Fallback Cerdas**:
  * Prioritas 1: Server VDownload lokal (Snapsave Unpacker & resolusi maksimal).
  * Prioritas 2: Content script DOM inspection (`srcset` 1080p langsung dari tab aktif).
  * Prioritas 3: Standalone client-side regex engine.
* **Tombol Unduh Mengambang (Floating Button)**: Muncul otomatis di pojok kanan bawah saat Anda membuka video/foto di YouTube, TikTok, Instagram, atau Shopee.
* **Deteksi Tab Aktif & Unduh Cepat**: Buka ekstensi dan klik 1 kali untuk langsung mengunduh media dari tab yang sedang aktif.

---

### 4. 🎨 Desain Modern & Responsif
* Mengusung tema **White & Blue Modern** dengan sentuhan *glassmorphism* yang bersih dan elegan.
* Responsif di segala ukuran layar (Desktop, Laptop, Tablet, dan Smartphone).
* Dilengkapi pemutar pratinjau video langsung di browser sebelum mengunduh.

---

## 🛠️ Persyaratan Sistem

Untuk menjalankan website utama:
* **Web Server**: Apache dengan modul `mod_rewrite` aktif (XAMPP, Laragon, atau LAMP stack).
* **PHP**: Versi **8.2** atau lebih tinggi (ekstensi aktif: `curl`, `json`, `mbstring`, `zip`).
* **FFmpeg**: Terpasang di PATH sistem operasi atau di folder `bin/ffmpeg.exe` *(disarankan untuk kompresi video dan konversi MP3)*.
* **Peramban Web**: Google Chrome, Microsoft Edge, Brave, atau peramban berbasis Chromium lainnya.

---

## 🚀 Panduan Mulai Cepat (Quick Start)

### A. Menjalankan Website (XAMPP / Laragon)

1. **Unduh / Kloning Repositori**:
   Tempatkan folder proyek ke dalam direktori `htdocs` server web Anda:
   ```bash
   git clone https://github.com/csbaguosps-stack/vdownload.git C:\xampp\htdocs\vdownload
   ```

2. **Jalankan Apache**:
   Buka **XAMPP Control Panel** dan klik **Start** pada modul **Apache**.

3. **Buka Aplikasi**:
   Akses alamat berikut di peramban web Anda:
   ```text
   http://localhost/vdownload/
   ```

4. **Mulai Mengunduh**:
   Tempel tautan video YouTube, TikTok, Instagram, atau Shopee, lalu klik **Unduh**.

---

### B. Memasang Ekstensi Browser (Chrome / Edge / Brave)

Ekstensi dapat dipasang langsung menggunakan fitur bawaan browser tanpa perlu akun developer:

#### Untuk Google Chrome & Brave:
1. Buka peramban dan kunjungi alamat:
   ```text
   chrome://extensions
   ```
2. Di pojok kanan atas, aktifkan sakelar **Mode pengembang** (*Developer mode*).
3. Di pojok kiri atas, klik tombol **Muat yang belum dibongkar** (*Load unpacked*).
4. Pilih folder `extension` dari proyek ini:
   ```text
   C:\xampp\htdocs\vdownload\extension
   ```
5. Ikon **VDownload Pro** akan muncul di bilah alat browser Anda dan siap digunakan!

#### Untuk Microsoft Edge:
1. Buka peramban Edge dan akses:
   ```text
   edge://extensions
   ```
2. Di panel sebelah kiri, aktifkan toggle **Mode Pengembang** (*Developer mode*).
3. Klik tombol **Muat yang belum dibongkar** (*Load unpacked*) dan pilih folder `C:\xampp\htdocs\vdownload\extension`.

---

## ⚙️ Pengaturan FFmpeg & Akselerasi GPU

Aplikasi ini dirancang untuk mendeteksi FFmpeg secara otomatis dengan urutan pencarian berikut:
1. `bin/ffmpeg.exe` (di dalam folder proyek)
2. `ffmpeg` dari Environment PATH sistem operasi Anda

> 💡 **Tips Pengguna Kartu Grafis NVIDIA**:
> Jika komputer Anda memiliki GPU NVIDIA (GeForce GTX/RTX) dan driver NVIDIA terbaru telah terpasang, aplikasi akan otomatis mengaktifkan encoder hardware **NVENC (`h264_nvenc`)** untuk proses kompresi kilat tanpa membebani CPU.

---

## 📂 Struktur Direktori Proyek

```text
vdownload/
├── .htaccess                      # Konfigurasi Apache mod_rewrite
├── .gitignore                     # Filter berkas Git
├── index.php                      # Front Controller & Autoloader PSR-4
├── README.md                      # Dokumentasi utama proyek
├── SECURITY.md                    # Kebijakan pelaporan keamanan
├── app/
│   ├── Config/
│   │   └── Config.php             # Pengaturan aplikasi & URL dasar
│   ├── Core/
│   │   ├── Controller.php         # Base Controller MVC
│   │   ├── Router.php             # Router HTTP GET & POST
│   │   ├── MediaInspector.php     # Pemeriksa stream & resolusi media
│   │   └── DownloaderInterface.php# Interface standar ekstraktor
│   ├── Controllers/
│   │   ├── HomeController.php     # Controller halaman utama, riwayat, panduan, pengaturan
│   │   └── DownloadController.php # Controller API ekstraksi & kompresi media
│   ├── Models/
│   │   ├── MediaCompressor.php    # Mesin kompresi GPU NVENC & CPU multi-thread
│   │   ├── YouTubeDownloader.php  # Ekstraktor Video & Audio YouTube
│   │   ├── ShopeeDownloader.php   # Ekstraktor Shopee Video VOD (True Master HD)
│   │   ├── TikTokDownloader.php   # Ekstraktor TikTok No-Watermark & Audio MP3
│   │   ├── InstagramDownloader.php# Ekstraktor Instagram Reels & Post HD
│   │   ├── DownloadHistory.php    # Pengelola riwayat unduhan lokal
│   │   └── SettingModel.php       # Pengelola preferensi aplikasi
│   └── Views/
│       ├── layouts/               # Template Header, Navbar, Footer
│       └── home/                  # Tampilan antarmuka pengguna
├── assets/
│   ├── css/style.css              # Desain visual White & Blue Glassmorphism
│   ├── js/app.js                  # Interaksi antarmuka & penanganan proses
│   └── img/preview.png            # Tangkapan layar antarmuka aplikasi
├── extension/                     # Berkas Ekstensi Browser Standalone (Manifest V3)
│   ├── manifest.json              # Konfigurasi ekstensi Chrome & Edge
│   ├── background.js              # Service worker latar belakang
│   ├── content.js & content.css   # Widget floating download di halaman video
│   ├── popup.html & popup.js      # Antarmuka popup ekstensi
│   ├── extractor.js               # Mesin ekstraksi mandiri di peramban
│   └── icons/                     # Ikon ekstensi (16px, 48px, 128px)
├── downloads/                     # Direktori penyimpanan paket unduhan
└── storage/                       # Direktori cache dan data lokal
```

---

## ❓ Tanya Jawab & Solusi Masalah (FAQ)

<details>
<summary><b>1. Apakah FFmpeg wajib dipasang?</b></summary>
Tidak wajib untuk pengunduhan dasar. Ekstraksi tautan video langsung (direct stream) dapat berjalan tanpa FFmpeg. Namun, FFmpeg sangat disarankan agar fitur <b>Kompresi Video Hemat Kuota</b> dan penggabungan video 1080p YouTube dengan audio dapat berjalan optimal.
</details>

<details>
<summary><b>2. Mengapa video Shopee atau TikTok tidak dapat diunduh?</b></summary>
Pastikan tautan yang Anda masukkan berasal dari postingan publik (bukan akun privat atau video yang telah dihapus). Pastikan juga koneksi internet server web atau komputer Anda stabil saat menghubungi API platform terkait.
</details>

<details>
<summary><b>3. Apakah ekstensi browser memerlukan server XAMPP yang menyala?</b></summary>
<b>Tidak.</b> Ekstensi VDownload Pro dirancang dengan arsitektur <i>Standalone Engine</i>, sehingga dapat mengekstrak dan mengunduh video langsung dari browser Anda tanpa ketergantungan pada server web lokal.
</details>

<details>
<summary><b>4. Bagaimana cara memperbarui ekstensi setelah melakukan modifikasi kode?</b></summary>
Buka halaman <code>chrome://extensions</code>, temukan kartu <b>VDownload Pro</b>, lalu klik ikon panah melingkar (<b>Muat Ulang / Reload</b>). Anda juga dapat menggunakan tombol reload cepat langsung di dalam popup ekstensi.
</details>

---

## 🔒 Kebijakan & Penggunaan Bijak

Aplikasi ini dibuat untuk keperluan edukasi, pembelajaran pemrograman, dan pencadangan konten pribadi yang sah. Pengguna bertanggung jawab penuh atas penggunaan konten yang diunduh. Harap senantiasa menghormati hak cipta kreator dan ketentuan layanan masing-masing platform.

---

## 📄 Hak Cipta & Pengembang

* **Aplikasi**: VDownload Pro
* **Pengembang**: [cs.baguosps@gmail.com](mailto:cs.baguosps@gmail.com)
* **Repositori Resmi**: [https://github.com/csbaguosps-stack/vdownload](https://github.com/csbaguosps-stack/vdownload)
* **Hak Cipta**: &copy; 2026 **cs.baguosps@gmail.com**. Seluruh hak cipta dilindungi undang-undang.
