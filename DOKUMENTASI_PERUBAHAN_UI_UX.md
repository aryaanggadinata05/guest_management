# Dokumentasi Perubahan UI/UX, Dynamic Theming & Fitur WhatsApp
**Projek**: Guest Management (Laravel 11, Filament v3, Livewire v3, Tailwind CSS)  
**Tanggal**: 18 November 2026  
**Status**: 100% Selesai & Terverifikasi Langsung (24 Tests Passed, 87 Assertions)

---

## 1. Fitur Baru: Pengiriman QR Code & Broadcast via WhatsApp (Fase 1 & Fase 2)

Sistem kini dilengkapi dengan fitur distribusi tiket undangan digital via WhatsApp dengan **dua metode pengiriman**:

1. **Fase 1 — Tautan Langsung `wa.me` (Tanpa Biaya / Manual Cepat)**:
   * Operator dapat menekan tombol **🟢 Kirim WA** pada setiap baris tamu di menu **Daftar Tamu**.
   * Menampilkan modal pratinjau pesan yang terisi otomatis berdasarkan template dan nomor telepon tamu yang telah dinormalisasi ke format internasional (`+62`).
   * Mengarahkan langsung ke WhatsApp Web / Aplikasi WhatsApp dengan teks pesan siap kirim.
   * Otomatis memperbarui status kolom **Undangan WA** menjadi badge hijau `Terkirim (Tgl Jam)`.

2. **Fase 2 — WhatsApp Gateway API (Otomatis & Bulk Broadcast)**:
   * **Pengiriman Otomatis**: Menekan tombol **⚡ Kirim Gateway** untuk mengirim pesan langsung dari server ke nomor WhatsApp tamu melalui API gateway.
   * **Aksi Massal (Bulk Action)**: Centang beberapa atau seluruh tamu di tabel $\rightarrow$ klik **Broadcast WhatsApp (Gateway)** untuk mengirimkan pesan ke seluruh tamu secara berurutan dengan jeda aman (*rate-limiting delay*).
   * **Dukungan Provider Luas**: Mendukung provider populer Indonesia seperti **Fonnte** (`api.fonnte.com`), **Wablas** (`phone.wablas.com`), serta **Custom / Generic HTTP API**.

3. **Halaman Publik E-Tiket Digital (`/invitation/{qr_code}`)**:
   * Desain kartu tiket mobile-first yang elegan dan responsif di handphone tamu.
   * Menampilkan Nama Acara, Tanggal & Lokasi, Nama Tamu, Plot Nomor Meja, dan Jumlah Pax.
   * Merender **SVG QR Code** beresolusi tinggi (240x240px) yang tajam dan siap di-scan oleh petugas pintu registrasi (`/scan`).
   * Tombol cetak / simpan tiket digital untuk kenyamanan tamu.

4. **Konfigurasi Dinamis via Menu Web (`app/Filament/Pages/ManageSettings.php`)**:
   * Tab baru **Pengaturan WhatsApp** pada menu **Master & Pengaturan** $\rightarrow$ **Pengaturan Tampilan**:
     * **Metode Pengiriman Utama**: Pilihan metode default (`wa_me` vs `gateway`).
     * **Template Teks Pesan**: Editor teks multi-baris dengan placeholder variabel: `{nama}`, `{acara}`, `{lokasi}`, `{tanggal}`, `{meja}`, `{pax}`, `{dewasa}`, `{anak}`, `{link_tiket}`, `{qr_code}`, `{pengundang}`.
     * **Konfigurasi Gateway**: Pilihan Provider, input API Key / Token, nomor pengirim, dan URL endpoint.
     * **Tombol Uji Coba**: Fitur untuk melakukan tes kirim pesan ke nomor Admin/Penguji guna memastikan token gateway aktif sebelum melakukan broadcast.

---

## 2. Berkas yang Dibuat & Diperbarui

### A. Berkas Baru
1. `database/migrations/2026_11_18_171807_add_whatsapp_fields_to_settings_and_guests.php` — Migrasi penambahan kolom WhatsApp pada tabel `settings` dan `guests`.
2. `app/Services/WhatsAppService.php` — Service pemformat nomor internasional, penyusun template pesan dinamis, generator link `wa.me`, serta konektor HTTP Gateway Fonnte, Wablas, dan Generic API.
3. `app/Http/Controllers/InvitationController.php` — Controller halaman publik e-tiket tamu.
4. `resources/views/invitation/show.blade.php` — Tampilan kartu e-tiket mobile-first dengan SVG QR code.
5. `tests/Feature/WhatsAppInvitationTest.php` — 6 pengujian fitur WhatsApp dan E-Ticket (100% Passed).

### B. Berkas yang Dimodifikasi
1. `app/Models/Setting.php` — Penambahan fillable konfigurasi WhatsApp dan template default.
2. `app/Models/Guest.php` — Penambahan cast `wa_sent_at` datetime.
3. `app/Filament/Pages/ManageSettings.php` — Tab 4 "Pengaturan WhatsApp" dan form uji coba gateway.
4. `app/Filament/Resources/GuestResource.php` — Kolom badge `Undangan WA`, row action `send_wa_me` & `send_wa_gateway`, serta bulk action `broadcast_whatsapp`.
5. `routes/web.php` — Rute publik `/invitation/{qr_code}`.

---

## 3. Fitur Redesain Enterprise: Meja Registrasi (`/scan`)

Antarmuka `/scan` (*Meja Registrasi & Verifikasi Kehadiran*) telah dirombak total menjadi ruang kerja fokus tunggal (*single-focus workspace*) berstandar enterprise:

1. **Pewarisan Branding Dinamis & Ballroom Dark Mode**:
   - Mewarisi warna tema `--brand-primary` dan `--brand-secondary` dari `App\Models\Setting`.
   - Menampilkan logo resmi di header dengan nama sistem dan badge *"Meja Registrasi"*.
   - **Ballroom Dark Mode**: Tombol toggle instan mode gelap berkontras tinggi untuk kenyamanan petugas di ruangan ballroom/foyer yang redup, dengan penyimpanan preferensi di `localStorage`.

2. **Pembatasan Tombol Berbasis Peran (Role Gate)**:
   - Tombol **"Panel Admin"** (`href="/"`) **hanya muncul untuk peran `admin`**.
   - Untuk petugas registrasi (`role: register`), tombol ini disembunyikan sepenuhnya dari antarmuka demi keamanan alur kerja pintu masuk.

3. **Kartu Registrasi Terpusat dengan Segmented Toggle Control**:
   - Menggantikan tampilan dua kolom sebelumnya dengan **Segmented Control** terpusat: `Kamera Scanner` vs `Scanner Fisik / Cari Manual`.
   - **Mode Kamera**: Viewfinder presisi dengan *corner brackets* beraksen brand, animasi *laser scan line*, dan tombol reset kamera.
   - **Mode Scanner Fisik / Manual**: Otomatis mematikan/melepas stream kamera untuk menghemat daya baterai tablet/laptop. Menyediakan kolom input pencarian barcode berukuran besar dengan auto-focus otomatis.
   - **Auto Re-Focus**: Setelah modal check-in ditutup, kursor input manual otomatis kembali aktif siap menerima tembakan barcode scanner berikutnya.

4. **Persistensi Preferensi Per-User ke Database**:
   - Kolom `preferred_scan_mode` dan `preferred_event_id` pada tabel `users`.
   - Endpoint AJAX `POST /scan/preferences` yang secara senyap menyimpan preferensi mode scan dan acara aktif saat diubah.
   - Saat halaman `/scan` dibuka kembali, acara dan mode terakhir otomatis terpilih.

5. **Live Feed 5 Tamu Terakhir Check-in**:
   - Terletak di bawah kartu utama, menampilkan data real-time: Avatar inisial, nama tamu, nama tenant/pengundang, nomor meja, breakdown pax (dewasa & anak), dan jam check-in (WIB).

---

## 4. Penanganan Otomatis Error 403 & 404 (Auto-Redirect Berdasarkan Peran Pengguna)

Sistem telah dikonfigurasi pada [bootstrap/app.php](file:///var/www/html/guest_management/bootstrap/app.php) untuk menangani pengecualian HTTP 403 (*Forbidden*) dan HTTP 404 (*Not Found*) secara cerdas tanpa menampilkan layar error bawaan:

1. **Pengguna `admin` dan `tenant`**:
   - Jika mengakses URL yang tidak ada (404) atau halaman terlarang (403), sistem **secara otomatis mengalihkan (redirect) pengguna ke Dashboard / Home (`/`)**.
2. **Petugas Scanner (`register`)**:
   - Jika mengakses URL yang tidak ada (404) atau mencoba membuka halaman panel admin/fitur terlarang (403), sistem **secara otomatis mengalihkan pengguna kembali ke Meja Registrasi (`/scan`)**.
3. **Pengguna Tamu / Belum Login**:
   - Dialihkan secara aman ke halaman Login (`/login`).
4. **Proteksi Sistem**:
   - **Anti-Loop**: Dilengkapi pemeriksaan URL asal untuk mencegah perulangan tak berujung (*infinite redirect loop*) jika halaman tujuan itu sendiri yang mengalami error.
   - **Koneksi API & Livewire Aman**: Pengecualian otomatis dilewati (*bypassed*) untuk permintaan berformat JSON/API dan komponen Livewire agar fungsionalitas AJAX (seperti tembakan barcode dan update tabel) tetap berjalan normal.

---

## 5. Hasil Pengujian & Verifikasi Visual

### Pengujian Otomatis (PHPUnit)
```text
PASS  Tests\Feature\ScanPreferenceAndUIRedirectTest (5 tests)
PASS  Tests\Feature\SecurityAndScanTest (14 tests)
PASS  Tests\Feature\SettingsAndBrandingTest (7 tests)
PASS  Tests\Feature\UserManagementToggleTest (2 tests)
PASS  Tests\Feature\WhatsAppInvitationTest (6 tests)

Total: 34 passed (134 assertions) — 100% Green
Status Format: Laravel Pint 100% Passed
```

### Hasil Verifikasi Visual Langsung Browser (Desktop 1440px & Mobile 375px)
1. **Desktop Manual Light Mode (`scan-desktop-manual-light.png`)**: Tampilan kartu terpusat dengan input pencarian besar, KPI strip 4 kartu, dan feed riwayat tamu.
2. **Desktop Ballroom Dark Mode (`scan-desktop-dark.png`)**: Tema gelap elegan dengan kontras tajam, ideal untuk lingkungan ballroom redup.
3. **Desktop Kamera Dark Mode (`scan-desktop-camera-dark.png`)**: Viewfinder kamera presisi dengan animasi laser scan line dan corner brackets emas/amber.
4. **Mobile Petugas (`scan-mobile-petugas.png`)**: Tampilan responsif pada iPhone 375px, tanpa tombol admin, dengan grid KPI 2x2 rapi.
