# Guest Management & Event Operations Platform
*Sistem Manajemen Tamu, Registrasi QR Code Real-Time, WhatsApp Broadcast & Denah Meja Visual*

---

## 📌 Tentang Aplikasi

**Guest Management** adalah sistem enterprise berbasis web untuk pengelolaan operasional acara berskala besar (Gala Dinner, Gathering, Konferensi, Resepsi). Dibangun menggunakan **Laravel 11**, **Filament v3**, dan **Livewire v3**, aplikasi ini menyatukan alur manajemen kuota tenant, distribusi undangan dan e-tiket via WhatsApp, pemetaan meja visual berbasis denah ballroom, dan meja registrasi kehadiran real-time.

---

## 🚀 Fitur Unggulan

### 1. Manajemen Hak Akses Multi-Peran (RBAC)
- **Admin**: Akses penuh ke seluruh modul, master data acara, tenant, manajemen pengguna, denah meja, broadcast WhatsApp, dan pengaturan tampilan global.
- **Tenant / Rekanan**: Akses mandiri untuk mengelola daftar tamu mereka sendiri sesuai alokasi kuota yang telah ditentukan per acara, cetak QR massal, dan export Excel.
- **Petugas Scanner (`register`)**: Terkunci khusus pada antarmuka tunggal Meja Registrasi (`/scan`) tanpa akses ke panel admin.

### 2. Meja Registrasi & Scanner QR Presisi (`/scan`)
- **Mode Ganda**: Mendukung pemindaian langsung via Kamera HP/Webcam (HTML5 QR Code) dan pemindai Barcode Fisik (USB Scanner) atau pencarian manual Nama/Nomor HP.
- **Ballroom Mode (Dark/Light)**: Tampilan kontras tinggi yang ramah pencahayaan redup ballroom, dilengkapi switch mode instan.
- **Preferensi Tersimpan**: Sistem otomatis mengingat pilihan kamera/manual dan acara aktif terakhir untuk setiap petugas.
- **Live Feed & Real-Time Counter**: Menampilkan ringkasan statistik kehadiran (Dewasa & Anak) serta feed 5 tamu terakhir yang melakukan check-in secara real-time.

### 3. Distribusi Undangan & E-Tiket via WhatsApp
- **Fase 1 (Direct wa.me)**: Pengiriman instan melalui tautan resmi WhatsApp Web / Mobile dengan template pesan dinamis (Nama Tamu, Acara, Pax, Link E-Tiket QR).
- **Fase 2 (WhatsApp Gateway API & Bulk Broadcast)**: Integrasi gateway pihak ketiga dengan dukungan broadcast massal ke seluruh tamu terpilih serta pencatatan status pengiriman badge waktu.
- **Halaman E-Tiket Publik**: Tampilan mobile-friendly untuk tamu melihat QR Code unik, status check-in, dan rincian acara.

### 4. Denah & Plotting Meja Visual (`/denah-meja`)
- **Unggah Denah Venue**: Mendukung denah ruangan (ballroom/layout) per acara.
- **Koordinat Persentase Responsif**: Posisi pin meja disimpan dalam persentase (`pos_x%`, `pos_y%`), memastikan posisi meja presisi di semua resolusi dan rasio layar.
- **Mode Atur Posisi Pin**: Baki meja belum dipetakan, fitur *drag-and-drop* pin, dan tombol reset pin.
- **Mode Plotting & Monitoring**: Pin berkode warna dinamis (Hijau: sisa > 2, Amber: sisa 1-2, Merah: penuh), slide-over drawer informasi meja, serta form penempatan tamu dengan proteksi pencegah *over-capacity*.
- **Kontrol Zoom**: Pembesaran denah 100%, 125%, 150% untuk venue padat dengan navigasi geser (*pan*).

### 5. Dynamic Branding & Multi-Theme
- Kustomisasi Nama Aplikasi, Subtitle Login, Logo Resmi, Favicon, serta Warna Utama (*Primary Color*) dan Sekunder (*Secondary Color*) via menu Pengaturan Tampilan.
- Opsi wallpaper kustom foto atau gradien modern pada layar login.

---

## 🛠️ Tech Stack & Kebutuhan Sistem

- **PHP**: `^8.2` (Direkomendasikan PHP 8.3)
- **Framework**: Laravel `^11.0`
- **Admin Panel & Form**: Filament `^3.2`
- **Fullstack Reactivity**: Livewire `^3.5`
- **Database**: MySQL `^8.0` / MariaDB `^10.4`
- **Frontend & Styling**: Tailwind CSS, Alpine.js, HTML5-QRCode
- **Ekspor/Impor**: Maatwebsite Excel / Filament Excel

---

## 📦 Panduan Instalasi Lokal

### 1. Klon Repositori
```bash
git clone <URL_REPOSITORY_ANDA>
cd guest_management
```

### 2. Instal Dependensi
```bash
composer install
npm install && npm run build
```

### 3. Konfigurasi Environment
Salin file konfigurasi environment dan sesuaikan kredensial database Anda:
```bash
cp .env.example .env
php artisan key:generate
```
Buka file `.env` dan atur koneksi database:
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=guest_management
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Migrasi Database & Seeder
```bash
php artisan migrate --seed
```

### 5. Buat Symlink Storage
```bash
php artisan storage:link
```

### 6. Jalankan Server Pengembangan
```bash
php artisan serve
```
Buka browser pada: `http://localhost:8000`

---

## 🔑 Akun Bawaan (Default Credentials)

| Peran (Role) | Username | Password | Akses URL | Deskripsi |
|---|---|---|---|---|
| **Administrator** | `admin` | `P123` | `/` | Akses penuh seluruh sistem |
| **Tenant** | `joko` | `P123` | `/` | Pengelolaan tamu per tenant |
| **Petugas Scanner** | `petugas1` | `P123` | `/scan` | Khusus meja registrasi & scan QR |

---

## 🧪 Menjalankan Automated Tests

Aplikasi dilengkapi dengan suite pengujian otomatis fitur dan keamanan:
```bash
php artisan test --compact
```
Atau jalankan PHPUnit secara langsung:
```bash
vendor/bin/phpunit
```

Untuk memeriksa kepatuhan standar format kode:
```bash
vendor/bin/pint --test
```

---

## 📄 Lisensi
Hak Cipta © 2026. Seluruh hak cipta dilindungi.
