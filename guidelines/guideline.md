# 🚀 Project Guideline: Dashboard Kasir POS

Selamat datang di repositori **Dashboard Kasir POS**. Dokumen ini berfungsi sebagai panduan teknis untuk memahami struktur, fitur, dan teknologi yang digunakan dalam proyek ini.

---

## 🛠️ Tech Stack

Proyek ini dibangun menggunakan kombinasi teknologi modern untuk performa dan pengalaman pengguna yang optimal:

- **Framework PHP:** [Laravel 12+](https://laravel.com/) - Framework PHP generasi terbaru.
- **Admin Panel:** [Filament v4](https://filamentphp.com/) - TALL stack-based admin panel untuk antarmuka yang cepat dan elegan.
- **Styling:** [Tailwind CSS v4](https://tailwindcss.com/) & Custom CSS (Vite-powered).
- **Typography:** [Figtree](https://fonts.google.com/specimen/Figtree) - Google Fonts.
- **Database:** MySQL / SQLite (mendukung UUID & Soft Deletes).
- **Invoicing:** [Laravel DOMPDF](https://github.com/barryvdh/laravel-dompdf) - Untuk pembuatan struk/invoice PDF.
- **Environment:** [Laragon](https://laragon.org/) - Lingkungan server lokal yang direkomendasikan untuk pengembangan PHP di Windows.

---

## 🎨 Design System & Aesthetic

Proyek ini menggunakan desain modern dengan palet warna yang dikurasi untuk kenyamanan mata dan profesionalitas:

- **Typography:** Menggunakan font **Figtree** secara global untuk keterbacaan yang optimal.
- **Palet Warna:**
  - **Dark Navy (`#313647`):** Warna utama untuk sidebar dan elemen navigasi.
  - **Slate Teal (`#435663`):** Warna sekunder untuk border, hover, dan elemen UI pendukung.
  - **Sage Green (`#A3B087`):** Warna aksen untuk status sukses, indikator stok, dan logo brand.
  - **Cream (`#FFF8D4`):** Warna latar belakang lembut untuk card dan highlight teks.
- **Visual Feel:** Menggunakan gradien halus, *glassmorphism* ringan, dan *micro-animations* pada tombol dan kartu produk.

## ✨ Fitur Utama

1. **Sistem POS (Point of Interaction):**
   - Antarmuka 2-kolom yang responsif.
   - Pencarian produk instan dan filter kategori.
   - Validasi stok real-time (mencegah pembelian barang habis).
   - Pengurangan stok otomatis saat transaksi *Completed*.

2. **Manajemen Produk & Stok:**
   - Pencatatan produk dengan varian.
   - Sistem transaksi stok (`in`/`out`) untuk akurasi data.
   - Perhitungan stok dinamis berdasarkan riwayat transaksi.

3. **Dashboard Analitik:**
   - **Filtered Stats Overview:** Statistik penjualan dengan filter waktu (Hari ini, Minggu ini, Bulan ini, Tahun ini, Semua waktu).
   - **Sales Chart:** Visualisasi tren penjualan harian.
   - **Low Stock Notification:** Peringatan otomatis untuk produk dengan stok menipis (<= 5).

4. **Invoicing & Pelaporan:**
   - Generasi nomor nota otomatis.
   - Cetak struk langsung ke PDF.

---

## 🏗️ Arsitektur Data

- **Primary Key:** Proyek ini menggunakan **UUID** untuk keamanan dan skalabilitas.
- **Soft Deletes:** Data tidak dihapus secara permanen, memungkinkan pemulihan jika diperlukan.
- **Stock Logic:** Stok tidak disimpan sebagai kolom statis di tabel produk, melainkan dihitung secara real-time dari tabel `stock_transactions` untuk menjamin integritas data.

---

## 💻 Panduan Setup (Laragon)

Ikuti langkah-langkah berikut untuk menjalankan proyek di Laragon:

1. **Clone Repositori:**
   Letakkan folder proyek di dalam direktori `C:\laragon\www\`.

2. **Konfigurasi Environment:**
   - Salin `.env.example` menjadi `.env`.
   - Sesuaikan konfigurasi database di `.env` (Laragon secara default menggunakan `DB_USERNAME=root` dan `DB_PASSWORD=`).

3. **Install Dependencies:**
   Buka terminal di folder proyek dan jalankan:
   ```bash
   composer install
   ```

4. **Migrasi & Seeding:**
   Jalankan perintah berikut untuk menyiapkan database dan data dummy:
   ```bash
   php artisan migrate --seed
   ```

5. **Akses Dashboard:**
   - Jalankan `npm run dev` atau `npm run build` untuk mengompilasi aset frontend (Membutuhkan Node.js ≥ 20.19).
   - Akses via browser (contoh: `http://dashboardKasir.test/admin`).

---

*Catatan: Dokumen ini dibuat secara otomatis sebagai hasil analisis proyek. Silakan perbarui seiring perkembangan fitur baru.*
