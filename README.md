# Dashboard Kasir POS - Backend System

Sistem Backend dan API untuk Aplikasi Point of Sales (POS) Offline-First berbasis Flutter. Dibangun menggunakan Laravel 11 dan Filament v3.

## Fitur Utama
1. **API Auth Sanctum**: Mendukung otentikasi via token untuk aplikasi kasir dan aplikasi kurir.
2. **Offline-First Synchronization**: Mekanisme Sync (Push/Pull) dengan prinsip idempotency berbasis UUID untuk mencegah duplikasi data transaksi jika terjadi putus koneksi.
3. **Filament Dashboard Admin**:
   - Tema gelap yang dikustomisasi (`#1B211A`, `#628141`, `#8BAE66`).
   - Widget Chart Penjualan dan Statistik Realtime.
   - Manajemen Produk, Kategori, Pengguna, dan Kurir.
   - Pemantauan Pengiriman dan Laporan Stok.
4. **AI-STICH Integration**: Rute placeholder untuk integrasi Machine Learning (Demand Forecasting & Analytics).
5. **Role-Based Access Control**: Mendukung multi-role seperti `admin`, `cashier`, dan `delivery`.

## Persyaratan
- PHP >= 8.2
- Composer
- MySQL / MariaDB
- Node.js & NPM (untuk membuild Filament Assets)

## Cara Instalasi
1. Clone repositori ini.
2. Salin file `.env.example` ke `.env` dan konfigurasikan koneksi database Anda.
3. Jalankan `composer install`
4. Jalankan `npm install` dan `npm run build`
5. Generate application key: `php artisan key:generate`
6. Lakukan migrasi beserta *dummy data*: `php artisan migrate:fresh --seed`
7. Akses Dashboard di `http://localhost:8000/admin`.
   - **User**: `admin@admin.com`
   - **Password**: `password`

## Dokumentasi API
Dokumentasi teknis untuk rute REST API, struktur payload, serta integrasi endpoint aplikasi Mobile dapat dilihat pada file [API_DOCUMENTATION.md](./API_DOCUMENTATION.md).

## Struktur Kode
- **API Controllers**: Terletak di `app/Http/Controllers/Api/V1`.
- **Admin Panel**: Terletak di `app/Filament/Admin/Resources`.
- **Database**: Skema transaksi kompleks termasuk relasi tabel kurir, tracking device, dan stock transactions berada di folder `database/migrations`.

---
*Dikembangkan dengan Laravel dan Filament.*
