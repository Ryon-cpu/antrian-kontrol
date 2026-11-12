# Sistem Antrian Pasien

Aplikasi web manajemen antrian pemeriksaan pasien untuk klinik, puskesmas, dan praktek dokter mandiri (satu dokter). Didesain ramah lansia, mobile-first untuk pendamping pasien tanpa login/OTP, serta dilengkapi notifikasi Web Push otomatis saat giliran tiba.

---

## 📋 Fitur Utama

1. **Pendaftaran Pasien (Mobile-First untuk Pendamping)**:
   - Akses via scan barcode / QR tanpa perlu login, email, atau OTP.
   - Form pendaftaran di bagian paling atas: Nama, Usia, Alamat, Keterangan Sakit.
   - Sesi token perangkat disimpan aman di browser (`localStorage`) untuk memantau status antrian di `/antrian`.
   - Aturan 1 perangkat: Satu pendamping hanya dapat mendaftarkan satu pasien aktif hingga antrian selesai, dibatalkan, atau dilewati.
   - PWA (Progressive Web App) dengan dukungan notifikasi Web Push latar belakang.

2. **Dashboard Petugas Antrian**:
   - Kontrol status praktek manual (**Buka Praktek** / **Tutup Praktek**).
   - Tindakan antrian lengkap: **Panggil Berikutnya**, **Panggil Ulang**, **Selesai**, **Lewati**, **Geser Nomor ke Bawah**, dan **Batalkan**.
   - Otomasi panggilan: Panggilan maksimal 3 kali. Jika pasien tidak hadir dalam 2 menit, antrian otomatis digeser beberapa urutan ke bawah oleh scheduler.
   - Pembaruan status realtime dengan polling fallback setiap 5 detik.

3. **Dashboard Admin**:
   - Kelola akun petugas klinik (tambah, nonaktifkan, ganti password).
   - Pengaturan identitas klinik, jam praktek, nama & gelar dokter, foto dokter, dan prestasi dokter.
   - Generator & unduh QR code yang langsung mengarah ke halaman pendaftaran publik.
   - Laporan dan rekapitulasi harian pasien terlayani, dibatalkan, dan dilewati.

4. **Notifikasi Web Push & PWA**:
   - Menggunakan Service Worker + Web Push API + VAPID.
   - Notifikasi giliran tiba dengan getar dan suara (serta modal popup jika web sedang terbuka).
   - Peringatan saat sisa 2 antrian di depan, panggilan ulang, dan antrian digeser.

---

## 🗄️ Domain & ERD Database

Diagram domain data aplikasi antrian pasien:

![ERD Antrian Pasien](docs/erd-antrian.svg)

### Struktur Tabel Utama:
- `users`: Akun petugas dan admin (`role`, `is_active`, `password_hash`).
- `queue_entries`: Data antrian harian (`queue_date`, nomor `01`-`99`, `position`, `device_hash`, data pasien, status antrian, dan riwayat panggilan).
- `push_subscriptions`: Endpoint langganan Web Push per token perangkat.
- `settings`: Konfigurasi sistem dinamis (status praktek, nama klinik, jam buka/tutup, profil dokter).
- `doctor_achievements`: Riwayat prestasi dan sertifikasi dokter.

---

## ⚙️ Persyaratan Sistem

- **PHP**: Versi 8.3 atau 8.4 (ekstensi: `pdo`, `mbstring`, `openssl`, `gmp` untuk Web Push)
- **Database**: MySQL 8.0+ atau MariaDB 10.4+
- **Node.js**: Versi 18+ & NPM
- **Composer**: Versi 2.x

---

## 🚀 Panduan Instalasi & Setup

### 1. Salin Environment & Install Dependencies
```bash
cp .env.example .env
composer install
npm install
```

### 2. Konfigurasi Database & Timezone
Pastikan konfigurasi database di file `.env` sudah sesuai, dan timezone menggunakan `Asia/Jakarta`:
```env
APP_TIMEZONE=Asia/Jakarta
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=antrian_pasien
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Generate App Key & VAPID Keys
```bash
php artisan key:generate
php artisan push:vapid
```
*Perintah `push:vapid` akan otomatis mengisi `VAPID_PUBLIC_KEY` dan `VAPID_PRIVATE_KEY` di file `.env`.*

### 4. Migrasi & Seeder Awal
```bash
php artisan migrate --seed
```
*Seeder akan membuat akun admin default dengan username `admin` dan password sesuai nilai `ADMIN_PASSWORD` di `.env` (atau password acak yang dicetak di terminal jika kosong).*

### 5. Kompilasi Aset Frontend
```bash
npm run build
```
*Untuk mode pengembangan dengan hot-reload:*
```bash
npm run dev
```

### 6. Menjalankan Laravel Scheduler
Scheduler diperlukan untuk menangani aturan timeout panggilan otomatis (2 menit setelah panggilan ke-3) serta reset antrian harian:
```bash
php artisan schedule:work
```

---

## 🔐 Akun & Akses Halaman

| Role / Halaman | URL Web | Keterangan |
|---|---|---|
| **Landing Page & Pendaftaran** | `/` | Form pendaftaran pendamping, info tempat, info & prestasi dokter. |
| **Status Antrian Pasien** | `/antrian` | Nomor antrian pendamping, antrian yang sedang dipanggil, dan sisa antrian. |
| **Dashboard Petugas (Web Session)** | `/dashboard` | Masuk melalui login Breeze menggunakan akun role `petugas` atau `admin`. |
| **Dashboard Petugas (Token/Standalone)** | `/petugas` | Alternatif mandiri menggunakan API token. |
| **API Autentikasi** | `POST /api/auth/login` | Endpoint Sanctum untuk otentikasi API. |

---

## 🧪 Pengujian (Testing)

Proyek ini dilengkapi rangkaian pengujian otomatis menggunakan **Pest**:
```bash
php artisan test
```
