# 🚀 VDownload Pro - Multi-Platform Video & Music Downloader

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/License-Proprietary-0284c7?style=for-the-badge)](mailto:cs.baguosps@gmail.com)
[![Developer](https://img.shields.io/badge/Developer-cs.baguosps%40gmail.com-0ea5e9?style=for-the-badge&logo=gmail&logoColor=white)](mailto:cs.baguosps@gmail.com)

<p align="center">
  <img src="assets/img/preview.png" alt="VDownload Pro Preview" width="100%">
</p>

**VDownload Pro** adalah aplikasi web dan ekstensi browser modern untuk mengunduh video dan audio musik dari **YouTube**, **TikTok**, **Instagram Reels**, dan **Shopee Video** tanpa tanda air (*no watermark*) dengan kualitas **Full HD Asli (1080p / 720p)** dan audio stereo jernih.

Dilengkapi dengan mesin **Kompresi Cerdas Berakselerasi GPU NVIDIA (NVENC)** agar berkas video hemat kuota hingga ~60% **tanpa mengurangi ketajaman video (anti-pecah)**, serta memiliki **Ekstensi Browser Standalone** resmi untuk kemudahan unduh 1-klik langsung di halaman web.

---

## 📑 Daftar Isi

- [🎯 Ringkasan Dukungan Platform](#-ringkasan-dukungan-platform)
- [✨ Fitur Unggulan](#-fitur-unggulan)
  - [1. 🎬 Multi-Platform Extractor](#1--multi-platform-extractor)
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

| Platform | Kualitas Video | Audio Musik | Cover / Thumbnail | Keterangan Khusus |
| :--- | :---: | :---: | :---: | :--- |
| **YouTube** | ✅ Full HD 1080p, HD 720p, SD | ✅ MP3 / M4A High Quality | ✅ HD JPG | Mendukung video reguler, Shorts (`/shorts/`), dan YouTube Music |
| **TikTok** | ✅ Full HD & Clean SD (No WM) | ✅ MP3 Original Audio | ✅ Avatar HD & Cover | Tanpa watermark TikWM/TikTok, info likes & views |
| **Instagram** | ✅ Full HD Reels & Post | *(Termasuk dalam video)* | ✅ Cover HD | Mendukung Reels, postingan video Feed, ekstraksi multi-lapis |
| **Shopee Video** | ✅ **⭐ True Master HD** & SD | *(Termasuk dalam video)* | ✅ Cover HD | Stream VOD murni tanpa watermark template Shopee |

---

## ✨ Fitur Unggulan

### 1. 🎬 Multi-Platform Extractor
* **YouTube**:
  * Unduh Video resolusi 1080p Full HD, 720p HD, serta 360p SD.
  * Unduh berkas audio musik murni berformat MP3 / M4A kualitas tinggi.
  * Otomatis mengenali tautan YouTube Shorts, Video Standar, dan YouTube Music.
* **Shopee Video**:
  * **⭐ True Master HD**: Mengunduh berkas video asli sebelum dikompresi oleh template UI Shopee (100% tanpa watermark).
  * Opsi unduh Web SD hemat penyimpanan untuk pengguna seluler.
* **TikTok**:
  * Menghapus watermark bawaan TikTok secara otomatis.
  * Ekstraksi lagu pengiring (background music) dalam format MP3.
* **Instagram**:
  * Mendukung Reels dan Video Postingan publik dengan resolusi terbaik.

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

### 3. 🧩 Ekstensi Browser Standalone
* Terletak di direktori `extension/` berbasis **Manifest V3**.
* **100% Mandiri (Standalone)**: Dapat langsung mengunduh video dari peramban tanpa perlu server lokal aktif.
* **Tombol Unduh Mengambang (Floating Button)**: Muncul otomatis di pojok kanan bawah saat Anda membuka video di YouTube, TikTok, Instagram, atau Shopee.
* **Deteksi Tab Aktif**: Buka ekstensi dan klik 1 kali untuk langsung mengunduh video yang sedang Anda tonton.

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
