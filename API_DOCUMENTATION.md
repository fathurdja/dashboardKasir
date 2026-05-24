# Dashboard Kasir POS - API Documentation

Base URL: `/api/v1`
Authentication: Bearer Token (Sanctum)

## 1. Authentication
Endpoint untuk otentikasi aplikasi mobile (kasir & kurir).

### Login
`POST /auth/login`
- **Body**: `email` (string), `password` (string), `device_name` (string, optional)
- **Response**: `200 OK`
  ```json
  {
      "access_token": "1|xyz...",
      "token_type": "Bearer",
      "user": { ... }
  }
  ```

### Logout
`POST /auth/logout`
- **Header**: `Authorization: Bearer <token>`
- **Response**: `200 OK`

### Register Device (Untuk Notifikasi / Tracking)
`POST /auth/register-device`
- **Header**: `Authorization: Bearer <token>`
- **Body**: `device_name` (string), `fcm_token` (string)

---

## 2. Products
Endpoint untuk mengambil data master produk.

### List Products
`GET /products`
- **Response**: `200 OK`
  ```json
  [
      {
          "id": "uuid",
          "name": "Nasi Goreng",
          "category_id": 1,
          "price": 25000,
          "is_active": true,
          "variants": [ ... ]
      }
  ]
  ```

---

## 3. Transactions & Sync (Offline-First)

Aplikasi mobile POS utamanya menggunakan mekanisme **Sync** untuk mengirim batch transaksi saat sedang online.
Mekanisme **Idempotency** dijamin melalui penggunaan UUID pada transaksi untuk mencegah duplikasi.

### Sync Upload (Mobile -> Server)
`POST /sync/upload`
- **Body**:
  ```json
  {
      "device_id": "uuid",
      "transactions": [
          {
              "id": "uuid-pesanan-1",
              "receipt_number": "INV-2026-0001",
              "status": "completed",
              "total_price": 25000,
              "items": [ ... ]
          }
      ]
  }
  ```

### Sync Download (Server -> Mobile)
`GET /sync/download`
- Mengambil update terbaru untuk master data (Produk, Kategori, Harga) yang berubah sejak sinkronisasi terakhir.

### Single Transaction Store
`POST /transactions`
- **Catatan**: Opsional untuk transaksi realtime. Gunakan field `id` (UUID) pada payload untuk idempotency. Jika ID yang sama diinput, API akan merespon `200 OK` tanpa membuat duplikasi.

---

## 4. Delivery (Kurir)
Endpoint khusus untuk akun dengan role `delivery`.

### Start / End Shift
`POST /delivery/start-shift`
`POST /delivery/end-shift`
- **Header**: `Authorization: Bearer <token>`

### My Orders
`GET /delivery/my-orders`
- **Response**: Daftar pesanan yang ditugaskan ke kurir yang sedang login.

### Update Status Pengiriman
`PUT /delivery/orders/{receiptNumber}/status`
- **Body**: `status` (pending | on_the_way | delivered | returned)
- **Response**: `200 OK`

---

## 5. Analytics & Reports
Endpoint untuk mengambil grafik dan laporan kasir/dashboard.

### Daily / Hourly Analytics
`GET /analytics/daily`
`GET /analytics/hourly`

### Top Products
`GET /analytics/top-products`

---

## 6. AI-STICH (Placeholder)
`GET /ai/forecast`
- Placeholder untuk fitur Prediksi Cerdas (AI-STICH). Saat ini mengembalikan dummy data atau struktur kosong untuk integrasi Flutter.
