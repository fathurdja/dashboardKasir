# Dashboard Kasir POS - API Documentation

Base URL: `https://filament.fathurrzqn8n.web.id/api/v1`
Authentication: Bearer Token (Sanctum) - sertakan di header `Authorization: Bearer <token>` untuk semua endpoint yang dilindungi.

---

## 1. Authentication
Endpoint untuk otentikasi aplikasi mobile (kasir & kurir).

### Roles yang Tersedia
| Role | Deskripsi |
|------|-----------|
| `admin` | Administrator, akses penuh ke dashboard Filament dan semua fitur |
| `kasir` | Kasir, akses ke fitur POS, transaksi, dan laporan |
| `delivery` | Kurir, akses ke fitur delivery (shift, pengiriman, performa) |

> **Default**: Jika `role` tidak dikirim saat register, maka otomatis diset ke `kasir`.

### Login
`POST /auth/login`
- **Body**:
  ```json
  {
      "username": "fathur",
      "password": "password123",
      "device_name": "Kasir-Tab-01",
      "platform": "android"
  }
  ```
- **Response** (`200 OK`):
  ```json
  {
      "token": "1|xyz...",
      "user": {
          "id": 1,
          "name": "Fathur",
          "username": "fathur",
          "role": "kasir" // "admin" | "kasir" | "delivery"
      },
      "device": {
          "id": 1,
          "device_name": "Kasir-Tab-01",
          "platform": "android"
      },
      "store": {
          "name": "Toko Fathur",
          "address": "Jl. Raya No.1",
          "phone": "08123456789",
          "tax_rate": 10.0,
          "currency": "IDR",
          "store_code": "TK-001"
      }
  }
  ```

### Register
`POST /auth/register`
- **Body**:
  ```json
  {
      "name": "Budi Kasir",
      "username": "budikasir",
      "password": "password123",
      "role": "kasir",
      "device_name": "Kasir-Tab-02",
      "platform": "android"
  }
  ```
  > **Catatan field `role`**: Opsional. Nilai yang valid: `admin`, `kasir`, `delivery`. Jika tidak diisi, default = `kasir`.
- **Response** (`201 Created`): Mirip dengan response Login.

### Verify Token
`GET /auth/verify`
- **Body**: (Empty)
- **Header**: `Authorization: Bearer <token>`
- **Response** (`200 OK` - Valid):
  ```json
  {
      "authenticated": true,
      "message": "Token is valid",
      "token": "1|xyz...",
      "user": {
          "id": 1,
          "name": "Budi Kasir",
          "username": "budikasir",
          "role": "kasir"
      }
  }
  ```
- **Response** (`401 Unauthorized` - Expired/Invalid):
  ```json
  {
      "authenticated": false,
      "message": "Token is invalid or expired"
  }
  ```

### Get Current User Profile (Me)
`GET /auth/me`
- **Body**: (Empty)
- **Response** (`200 OK`): Mengembalikan objek `user` dan `store` yang sama dengan saat login (tanpa token).

### Logout
`POST /auth/logout`
- **Body**: (Empty)
- **Response** (`200 OK`):
  ```json
  {
      "message": "Logged out successfully"
  }
  ```

### Register Device (Push Notifications dll)
`POST /auth/register-device`
- **Body**:
  ```json
  {
      "device_name": "Kasir-Tab-01",
      "platform": "android"
  }
  ```
- **Response** (`201 Created`):
  ```json
  {
      "message": "Device registered successfully",
      "device": { ... }
  }
  ```

---

## 2. Products
Endpoint untuk data produk (Master Data).

### List Products
`GET /products`
- **Query Params**:
  - `category_id` (uuid, optional)
  - `search` (string, optional)
  - `updated_since` (datetime ISO, optional) - untuk sinkronisasi.
- **Body**: (Empty)
- **Response** (`200 OK`):
  ```json
  {
      "data": [
          {
              "id": "uuid",
              "name": "Nasi Goreng",
              "category_id": "uuid",
              "price": 25000,
              "is_active": true,
              "variants": [ ... ]
          }
      ]
  }
  ```

### Create Product
`POST /products`
- **Body**:
  ```json
  {
      "name": "Es Teh Manis",
      "category_id": "uuid-kategori",
      "description": "Es Teh Manis Segar",
      "barcode": "899123456789",
      "purchase_price": 3000,
      "price": 5000,
      "is_active": true
  }
  ```
- **Response** (`201 Created`): Mengembalikan data produk yang dibuat.

### Update Product
`PUT /products/{productId}`
- **Body**: Sama seperti Create Product (field bersifat opsional).
- **Response** (`200 OK`): Mengembalikan data produk yang diupdate.

### Delete Product
`DELETE /products/{productId}`
- **Body**: (Empty)
- **Response** (`200 OK`):
  ```json
  {
      "message": "Product deleted successfully"
  }
  ```

---

## 3. Transactions (Live Mode)
Manajemen transaksi secara live (satu per satu).

### List Transactions
`GET /transactions`
- **Query Params**: `date` (YYYY-MM-DD), `from` (YYYY-MM-DD), `to` (YYYY-MM-DD), `status` (completed/pending/canceled), `payment_method`, `per_page`.
- **Response** (`200 OK`): Mengembalikan daftar transaksi dengan pagination.

### Show Single Transaction
`GET /transactions/{receiptNumber}`
- **Response** (`200 OK`): Mengembalikan detail transaksi beserta daftar item pesanan.

### Store Transaction (Checkout)
`POST /transactions`
- **Body**:
  ```json
  {
      "id": "1111-2222-3333-4444", 
      "receipt_number": "INV-20260530-ABCD", 
      "order_type": "dine-in",
      "payment_method": "cash",
      "tax_amount": 2500,
      "discount_amount": 0,
      "total_price": 27500,
      "received_amount": 30000,
      "change_amount": 2500,
      "customer_name": "Pak Budi",
      "customer_phone": "081234",
      "device_id": 1,
      "items": [
          {
              "product_id": "uuid-produk-1",
              "quantity": 1,
              "bonus_qty": 0,
              "unit_price": 25000,
              "subtotal": 25000
          }
      ]
  }
  ```
- **Catatan**: Field `id` opsional namun sangat disarankan diisi oleh mobile app (UUID) agar bersifat idempotent (menghindari duplikasi saat koneksi putus-nyambung).

### Void Transaction
`PUT /transactions/{receiptNumber}/void`
- **Body**: (Empty)
- **Response** (`200 OK`):
  ```json
  {
      "message": "Transaction voided successfully"
  }
  ```

### Pay Bon (Kasbon)
`PUT /transactions/{receiptNumber}/pay-bon`
- **Body**: (Empty)
- **Response** (`200 OK`): Mengubah status transaksi bon menjadi `completed` dan memotong stok.

---

## 4. Sync (Offline-First)
Endpoint untuk sinkronisasi batch transaksi yang terjadi secara offline.

### Upload Offline Transactions (Mobile -> Server)
`POST /sync/upload`
- **Body**:
  ```json
  {
      "device_id": 1,
      "orders": [
          {
              "id": "uuid-pesanan-1",
              "receipt_number": "INV-2026-0001",
              "status": "completed",
              "order_type": "take-away",
              "total_price": 25000,
              "tax_amount": 2500,
              "discount_amount": 0,
              "payment_method": "cash",
              "received_amount": 30000,
              "change_amount": 2500,
              "created_at": "2026-05-30T14:30:00Z",
              "items": [
                  {
                      "id": "uuid-item-1",
                      "product_id": "uuid-produk",
                      "quantity": 1,
                      "unit_price": 25000,
                      "subtotal": 25000
                  }
              ]
          }
      ]
  }
  ```
- **Response** (`200 OK`):
  ```json
  {
      "synced": 1,
      "failed": 0,
      "errors": [],
      "server_time": "2026-05-30T15:00:00Z"
  }
  ```

### Download Data Server (Server -> Mobile)
`GET /sync/download`
- **Query Params**: `since` (ISO Datetime), `device_id`
- **Response** (`200 OK`):
  ```json
  {
      "products": [ ... ],
      "orders": [ ... ],
      "server_time": "2026-05-30T15:00:00Z"
  }
  ```

### Check Sync Status
`GET /sync/status?device_id=1`
- **Response** (`200 OK`): Mengembalikan log sinkronisasi terakhir dari device tersebut.

---

## 5. Analytics & Reports
Laporan grafik dan statistik. Seluruh request tidak memerlukan body (hanya Query Params).

- **Daily Summary**: `GET /analytics/daily?date=YYYY-MM-DD`
- **Hourly Chart**: `GET /analytics/hourly?date=YYYY-MM-DD`
- **Periodic Summary**: `GET /analytics/summary?period=week` (atau `month`)
- **Top Products**: `GET /analytics/top-products?limit=10&date=YYYY-MM-DD`
- **Bon Report (Hutang)**: `GET /analytics/bon-report`

---

## 6. Delivery (Kurir)
Endpoint untuk role `delivery`.

### Start Shift
`POST /delivery/start-shift`
- **Body**: (Empty)
- **Response** (`201 Created`):
  ```json
  {
      "message": "Shift delivery dimulai",
      "data": {
          "id": 1,
          "date": "2026-05-30",
          "status": "active",
          "started_at": "14:00:00"
      }
  }
  ```

### End Shift
`POST /delivery/end-shift`
- **Body**:
  ```json
  {
      "notes": "Shift berjalan lancar, 1 retur."
  }
  ```
- **Response** (`200 OK`): Mengembalikan summary total uang tunai yang terkumpul selama shift.

### Get My Orders
`GET /delivery/my-orders?date=YYYY-MM-DD`
- **Response** (`200 OK`): Daftar order yang ditugaskan ke kurir tersebut pada hari ini.

### Update Order Delivery Status
`PUT /delivery/orders/{receiptNumber}/status`
- **Body**:
  ```json
  {
      "delivery_status": "delivered" // pending | on_the_way | delivered | returned
  }
  ```
- **Response** (`200 OK`):
  ```json
  {
      "message": "Status delivery diperbarui",
      "delivery_status": "delivered"
  }
  ```

### Performance Stats
`GET /delivery/performance?period=today` (today, week, month)
- **Response** (`200 OK`): Statistik pengiriman kurir.

---

## 7. AI Forecast & Stock (Placeholder/Tambahan)
Fitur Prediksi / Laporan tambahan.

- **AI Forecast**: `GET /ai/forecast`
- **Daily Stock Report**: `GET /stock-report/daily`
