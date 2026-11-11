# Prompt Projek: Web "Antrian Pasien"

Kamu adalah senior full-stack developer. Bangun web **Antrian Pasien** secara bertahap sesuai aturan di bawah. Kerjakan per tahap, jelaskan singkat apa yang dibuat, dan tunggu konfirmasi sebelum lanjut ke tahap berikutnya. Jangan menambah fitur di luar daftar ini.

---

## 1. Ringkasan

Web antrian untuk klinik / puskesmas / rumah sakit dengan **satu dokter**. Tujuannya mengontrol antrian pasien yang sedang sakit atau periksa kesehatan. Pasien lansia didaftarkan oleh **pendamping** lewat HP. Pendamping mendapat notifikasi saat giliran tiba **tanpa harus membuka atau login ulang ke web**.

## 2. Tech Stack

- **Back end:** PHP versi terbaru (PHP 8.3+), MySQL/MariaDB, PDO + prepared statement. Boleh memakai Laravel terbaru atau PHP native terstruktur (MVC). Pilih satu dan jelaskan alasannya.
- **Front end:** wajib memakai **template JS yang sudah jadi** (cari dan sesuaikan dengan framework yang dipilih: React, Vue, atau lainnya). Jangan membuat UI dari nol. Sebutkan nama dan sumber template yang dipakai.
- **Notifikasi:** Web Push (Service Worker + Push API + VAPID). Wajib HTTPS.
- Mobile-first, karena semua akses pendamping memakai HP.

## 3. Role dan Akses

| Role | Akses |
|---|---|
| **Petugas antrian** | Login (username + password). Mengelola antrian, melihat data pasien. |
| **Pendamping** | Tanpa email, tanpa OTP. Masuk lewat scan barcode/QR. Mendaftarkan pasien dan menerima notifikasi. |
| **Admin** | Login. Kelola akun petugas, info dokter, jam praktek, logo. |

Data pasien **hanya bisa dilihat petugas** (dan admin). Pendamping hanya melihat status antriannya sendiri.

## 3a. Halaman Web

1. **Landing page** (publik, hasil scan barcode):
   - Bagian **paling atas**: form pendaftaran pemeriksaan.
   - Keterangan tempat (klinik / puskesmas / rumah sakit), nama, alamat, jam praktek.
   - Keterangan dokter: nama lengkap, gelar, dan info lain.
   - Prestasi dokter: gambar + keterangan.
   - Status hari ini: "Praktek buka" / "Antrian tutup" / "Libur".
2. **Halaman status antrian pendamping:** nomor antrian saya, nomor yang sedang dipanggil, sisa antrian.
3. **Dashboard petugas:** daftar antrian hari ini dengan tombol aksi.
4. **Dashboard admin.**

## 4. Alur Pendaftaran (Pendamping)

1. Pendamping scan barcode, langsung masuk ke landing page dengan form di bagian paling atas.
2. Isi form pasien: **Nama, Usia, Alamat, Keterangan Sakit**.
3. Saat submit, pendamping diminta **mengizinkan notifikasi** (jelaskan dengan bahasa sederhana: "Izinkan notifikasi agar kami bisa memberi tahu saat giliran tiba").
4. Sistem membuat nomor antrian dan sesi sementara (token acak aman, disimpan di cookie/localStorage) yang melekat pada antrian itu. Jika halaman ditutup lalu dibuka lagi, pendamping tetap melihat status antriannya.
5. **Satu pendamping tidak boleh mendaftarkan pasien lagi** selama antrian sebelumnya belum selesai (status masih menunggu/dipanggil). Boleh daftar lagi jika antrian sebelumnya sudah selesai, dibatalkan, atau dilewati.
6. Tidak ada OTP, tidak ada email. Hanya perangkat HP.

## 5. Aturan Nomor Antrian

- Format 2 digit: `01` sampai `99` per hari.
- Besok mulai lagi dari `01`.
- Jika sudah mencapai `99`, tolak pendaftaran baru dengan pesan "Antrian hari ini penuh".
- Pendaftaran hanya bisa saat status **buka**. Di luar itu, form dinonaktifkan dengan pesan jelas.

## 6. Jadwal Praktek

- **Sabtu dan Minggu libur** (otomatis tutup).
- **Jam mulai praktek dan jam tutup diatur manual** oleh petugas lewat tombol Buka Praktek dan Tutup Praktek. Jangan memakai jam otomatis.
- Saat tutup atau libur, landing page menampilkan status "Antrian tutup".

## 7. Status Antrian

```
menunggu → dipanggil → selesai
                    ↘ dilewati (digeser ke bawah)
menunggu / dipanggil → dibatalkan
```

## 8. Fitur Petugas (Dashboard)

Tombol untuk setiap antrian:

1. **Panggil** (berikutnya).
2. **Selesai.**
3. **Lewati.**
4. **Batalkan antrian** (pasien tidak jadi periksa).
5. **Geser nomor** ke bawah (pasien pulang dulu karena antrian masih lama, tapi tetap ingin periksa). Pasien tidak dibatalkan, hanya posisinya turun beberapa urutan. Nomor antrian aslinya tetap, yang berubah adalah urutan panggil.

### Aturan panggilan otomatis

- Satu pasien dipanggil maksimal **3 kali**.
- Setelah panggilan ke-3, tunggu **2 menit**.
- Jika pasien belum hadir, antrian **otomatis digeser agak ke bawah** (beberapa urutan, bukan dihapus).
- Implementasikan dengan job terjadwal (cron / queue), bukan timer di browser.

## 9. Notifikasi untuk Pendamping

Semua notifikasi dikirim lewat **Web Push**, sehingga muncul walau web tidak sedang dibuka.

| Kejadian | Isi notifikasi |
|---|---|
| Sisa 2 antrian | "2 antrian lagi, mohon bersiap." |
| Giliran tiba | "**Sekarang giliran anda.**" (getar + suara, tampil sebagai jendela peringatan) |
| Panggilan ulang (2x, 3x) | "Anda dipanggil kembali." |
| Digeser karena tidak hadir | "Antrian anda digeser, mohon segera ke ruang periksa." |
| Selesai | "Terima kasih karena sudah berkunjung, semoga pasien cepat sembuh." |

Ketentuan teknis:

- Gunakan Service Worker, VAPID keys, dan simpan subscription per antrian.
- Notifikasi giliran memakai `requireInteraction: true` dan pola getar.
- Jika halaman web sedang terbuka, tampilkan juga **modal/jendela peringatan** di dalam halaman.
- Sediakan **fallback polling** tiap beberapa detik untuk halaman yang terbuka, jika Push gagal.
- Catatan platform: di iPhone, Web Push hanya berfungsi jika web ditambahkan ke Home Screen (PWA, iOS 16.4+). Buat web sebagai **PWA** (manifest + service worker) dan tampilkan petunjuk singkat "Tambahkan ke layar utama" khusus pengguna iOS.

## 10. Halaman Admin

- Kelola akun petugas (tambah, nonaktifkan, reset password).
- Ubah info tempat dan info dokter (nama, gelar, prestasi + gambar).
- Ganti logo.
- Lihat riwayat dan laporan harian (jumlah pasien terlayani, dibatalkan, dilewati).

## 11. Tampilan dan Branding

- **Warna identik dengan tampilan puskesmas / rumah sakit / klinik** (dominan biru atau hijau medis, putih bersih, kontras tinggi). Definisikan sebagai CSS variables / design tokens.
- **Logo harus bisa diganti hanya dengan mengganti nama file**, misalnya `public/assets/logo.png`. Jangan menaruh logo di banyak tempat dengan path berbeda.
- **Nama web** disimpan di satu file konfigurasi (`config`), karena bisa berubah. Defaultnya: "Antrian Pasien".
- **Ramah lansia:** huruf besar (minimal 18px), tombol besar, kontras tinggi, bahasa sederhana, sedikit langkah.
- Tidak perlu layar TV ruang tunggu. Cukup notifikasi di HP.

## 12. Keamanan

- Password petugas/admin di-hash (`password_hash` dengan Argon2id atau bcrypt).
- Semua query memakai prepared statement. Validasi dan sanitasi semua input (cegah SQL injection dan XSS).
- Proteksi CSRF pada semua form dan aksi.
- Rate limiting pada pendaftaran (cegah spam) dan login (cegah brute force).
- Token sesi pendamping: acak, panjang, kedaluwarsa otomatis.
- Middleware role: pendamping tidak boleh mengakses halaman petugas/admin.
- HTTPS wajib, cookie `Secure`, `HttpOnly`, `SameSite`.
- Header keamanan dasar (CSP, X-Frame-Options, dll).

## 13. Privasi dan Retensi Data

- Data pasien (nama, usia, alamat, keterangan sakit) **hanya dapat dilihat petugas dan admin**.
- Data disimpan **1 bulan**, lalu dihapus otomatis oleh job terjadwal (cron).
- Jangan menulis data pasien ke log.

## 14. Barcode / QR

- Sediakan halaman admin untuk generate dan unduh QR yang mengarah ke URL landing page.
- QR yang discan langsung membuka landing page dengan form pendaftaran di bagian paling atas.

## 15. Database (usulan awal, sesuaikan jika perlu)

- `users` (id, nama, username, password_hash, role: petugas/admin, aktif)
- `patients_queue` (id, tanggal, nomor, urutan, nama, usia, alamat, keterangan_sakit, status, jumlah_panggilan, dipanggil_terakhir, session_token, created_at)
- `push_subscriptions` (id, queue_id, endpoint, p256dh, auth)
- `settings` (key, value), misalnya status buka/tutup, jam buka, jam tutup, nama web
- `doctor_profile` dan `doctor_achievements` (id, judul, keterangan, gambar)

## 16. Di Luar Cakupan (jangan dibuat)

- Antrian per poli / banyak dokter.
- Layar TV ruang tunggu.
- Login email atau OTP.
- Aplikasi native (Android/iOS).

## 17. Tahapan Pengerjaan

1. Setup projek, struktur folder, pilihan framework + template JS, konfigurasi `.env`.
2. Database, migrasi, seeder (akun admin awal).
3. Autentikasi petugas/admin dan middleware role.
4. Landing page + form pendaftaran + sesi pendamping.
5. Logika antrian (nomor 01–99, reset harian, buka/tutup, aturan satu pendamping satu antrian).
6. Dashboard petugas (panggil, selesai, lewati, batalkan, geser) + job panggilan 3x dan jeda 2 menit.
7. Web Push + PWA + modal fallback.
8. Halaman admin, laporan, generate QR.
9. Keamanan, retensi data 1 bulan, pengujian, dokumentasi (`README.md`).

**Mulai dari tahap 1.** Sebelum menulis kode, tanyakan hal yang masih belum jelas (maksimal 3 pertanyaan).
