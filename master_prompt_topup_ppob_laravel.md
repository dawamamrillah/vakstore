# Master Prompt — Website Top Up Game & PPOB Laravel

Saya ingin membangun sebuah website **VAKSTORE** berbasis web menggunakan **Laravel PHP dan MySQL**.

Website ini ditujukan sebagai platform pembelian produk digital yang menyediakan layanan:

### Top Up Game
1. Free Fire
2. Mobile Legends
3. PUBG Mobile
4. Roblox
5. Magic Chess: Go Go

### PPOB / Pembayaran Digital
6. Pembayaran tagihan PDAM
7. Pembelian token listrik PLN
8. Pembelian pulsa
9. Pembayaran tagihan Telkom

Website harus memiliki dua jenis pengguna utama:

- **User / Pelanggan**
- **Admin**

Sistem harus dirancang menggunakan prinsip:

- Clean Code
- MVC
- Modular
- Secure
- Scalable
- Maintainable
- REST API ready
- Service Layer
- Repository/Interface jika diperlukan
- Database Transaction
- Queue/Job untuk proses asynchronous
- Logging
- Audit Trail

Jangan membuat sistem yang seluruh logikanya berada di Controller.

---

# 1. TEKNOLOGI

Gunakan:

- PHP versi yang kompatibel dengan Laravel terbaru/stabil
- Laravel versi terbaru/stabil yang kompatibel
- MySQL
- Laravel Blade
- HTML5
- CSS3
- JavaScript
- Eloquent ORM
- Laravel Migration
- Laravel Seeder
- Laravel Factory
- Laravel Validation
- Laravel Middleware
- Laravel Notification
- Laravel Queue
- Laravel Scheduler jika diperlukan

Untuk frontend boleh menggunakan framework/library yang sesuai dengan ekosistem Laravel, tetapi prioritaskan implementasi yang sederhana, cepat, responsive, dan mudah dipelihara.

---

# 2. ARSITEKTUR SISTEM

Gunakan arsitektur seperti:

```text
User/Admin
    ↓
Controller
    ↓
Form Request / Validation
    ↓
Service Layer
    ↓
Repository/Interface jika diperlukan
    ↓
Model / Database
    ↓
External API jika diperlukan
```

Untuk provider:

```text
TopupService
    ↓
Provider Interface
    ↓
Provider Adapter
    ↓
Provider API
```

Untuk pembayaran:

```text
PaymentService
    ↓
Payment Gateway Interface
    ↓
Payment Gateway Adapter
    ↓
Payment Gateway API
```

Jangan menaruh kode API provider langsung di Controller.

---

# 3. JENIS ROLE

Buat minimal dua role:

## USER

User dapat:

- Registrasi
- Login
- Logout
- Reset password
- Mengelola profil
- Melihat saldo
- Melakukan top up saldo
- Membeli produk
- Menggunakan voucher
- Melihat riwayat transaksi
- Melihat detail transaksi
- Melihat invoice
- Mengunduh invoice
- Melihat notifikasi.

## ADMIN

Admin dapat:

- Login
- Melihat dashboard
- Mengelola user
- Mengelola saldo user
- Mengelola kategori
- Mengelola game
- Mengelola produk
- Mengelola provider
- Mengatur harga
- Mengatur keuntungan
- Membuat promo
- Membuat voucher
- Melihat transaksi
- Melihat laporan
- Melihat keuntungan
- Melakukan refund sesuai kewenangan
- Melihat audit log
- Mengatur konfigurasi website.

Pastikan user biasa tidak dapat mengakses route admin.

Gunakan middleware authorization/role.

---

# 4. PRODUK GAME

Sistem harus mendukung produk game yang bersifat dinamis.

## FREE FIRE

User memasukkan:

- Player ID
- Server/Region jika diperlukan
- Produk diamond

Contoh:

- 5 Diamond
- 12 Diamond
- 50 Diamond
- 70 Diamond
- 100 Diamond
- 140 Diamond
- 210 Diamond
- 355 Diamond
- 720 Diamond

## MOBILE LEGENDS

User memasukkan:

- User ID
- Zone ID
- Produk diamond.

## PUBG MOBILE

User memasukkan:

- Player ID
- Produk UC.

## ROBLOX

User memasukkan:

- User ID/Username
- Produk Robux atau produk yang tersedia.

## MAGIC CHESS: GO GO

User memasukkan:

- User ID
- Server/Zone jika diperlukan
- Produk yang tersedia.

---

# 5. PRODUK PPOB

## PULSA

User memasukkan:

- Nomor HP
- Operator
- Nominal pulsa.

Sistem harus dapat mengelompokkan produk berdasarkan operator.

Contoh:

- Telkomsel
- Indosat
- XL
- Axis
- Tri
- Smartfren

Daftar operator harus dapat dikelola dari database/admin.

## TOKEN LISTRIK PLN

User memasukkan:

- ID pelanggan / nomor meter
- Nominal token.

Nominal produk harus dinamis.

Contoh:

- Rp20.000
- Rp50.000
- Rp100.000
- Rp200.000
- Rp500.000
- Rp1.000.000

Admin dapat menambah atau mengubah produk.

## PDAM

User memasukkan:

- Nomor pelanggan
- Wilayah/PDAM.

Jika provider API mendukung inquiry tagihan, sistem harus melakukan:

```text
User memasukkan nomor pelanggan
    ↓
Inquiry
    ↓
Provider mengembalikan data tagihan
    ↓
Website menampilkan tagihan
    ↓
User melakukan pembayaran
    ↓
Provider melakukan pembayaran
    ↓
Status transaksi diperbarui
```

## TELKOM

User memasukkan:

- Nomor pelanggan
- Data lain sesuai kebutuhan provider.

Gunakan mekanisme inquiry dan payment apabila API provider mendukungnya.

---

# 6. SISTEM KATEGORI

Buat kategori:

- Game
- Pulsa
- PLN
- PDAM
- Telkom

Admin dapat:

- Tambah kategori
- Edit kategori
- Hapus kategori
- Aktif/nonaktif kategori.

---

# 7. SISTEM GAME

Admin dapat:

- Tambah game
- Edit game
- Hapus game
- Upload logo game
- Mengatur deskripsi
- Mengatur slug
- Mengaktifkan/nonaktifkan game.

Contoh:

```text
Category:
Game

Game:
Free Fire

Game:
Mobile Legends

Game:
PUBG Mobile
```

---

# 8. SISTEM PRODUK

Produk memiliki minimal:

- ID
- Category ID
- Game ID nullable
- Provider ID nullable
- Nama produk
- SKU internal
- SKU provider
- Deskripsi
- Harga modal
- Harga jual
- Keuntungan
- Status
- Created at
- Updated at.

Contoh:

```text
Produk:
Free Fire 70 Diamond

Harga modal:
Rp9.500

Harga jual:
Rp12.000

Keuntungan:
Rp2.500
```

Admin dapat mengubah harga tanpa mengubah transaksi lama.

---

# 9. SISTEM KEUNTUNGAN

Ini adalah fitur utama.

Admin harus dapat menentukan keuntungan setiap produk.

Gunakan formula:

```text
Keuntungan Kotor =
Harga Jual - Harga Modal
```

Jika terdapat diskon:

```text
Keuntungan Bersih =
Harga Setelah Diskon - Harga Modal
```

Jika ada biaya lain, buat perhitungan yang jelas dan konsisten.

Contoh:

```text
Harga modal = Rp10.000
Harga jual = Rp13.000
Diskon = Rp1.000
Biaya admin = Rp500

Total pembayaran:

Rp13.000 - Rp1.000 + Rp500
= Rp12.500

Keuntungan transaksi:

Rp12.500 - Rp10.000
= Rp2.500
```

Simpan snapshot nilai berikut pada transaksi:

- Cost price
- Selling price
- Discount
- Admin fee
- Total
- Profit

Tujuannya agar transaksi lama tidak berubah ketika harga produk diubah.

---

# 10. SISTEM WALLET / SALDO

Setiap user memiliki wallet.

Contoh:

```text
Saldo:
Rp100.000

Pembelian:
Rp20.000

Saldo:
Rp80.000
```

Buat tabel:

- `wallets`
- `wallet_transactions`

Wallet transaction harus menyimpan:

- User
- Type
- Amount
- Balance before
- Balance after
- Reference
- Description
- Created at.

Jenis transaksi:

- Deposit
- Purchase
- Refund
- Bonus
- Admin adjustment.

Semua perubahan saldo harus tercatat.

---

# 11. KEAMANAN SALDO

Gunakan database transaction.

Ketika user melakukan pembelian:

1. Lock wallet
2. Periksa saldo
3. Pastikan saldo mencukupi
4. Kurangi saldo
5. Simpan wallet transaction
6. Buat transaksi
7. Commit

Jika terjadi error:

```text
Rollback
```

Pastikan tidak terjadi:

- Double deduction
- Negative balance
- Race condition
- Double transaction.

Gunakan locking seperti `lockForUpdate()` jika diperlukan.

---

# 12. TOP UP SALDO

User dapat melakukan deposit saldo.

Flow:

```text
User memilih nominal
    ↓
Pilih metode pembayaran
    ↓
Payment Gateway
    ↓
Pembayaran berhasil
    ↓
Callback diterima
    ↓
Validasi callback
    ↓
Wallet bertambah
    ↓
Wallet transaction dibuat
    ↓
User mendapatkan notifikasi
```

Jangan menambah saldo hanya berdasarkan redirect dari browser.

Saldo hanya boleh bertambah setelah callback/webhook/payment verification berhasil.

---

# 13. PAYMENT GATEWAY

Buat sistem payment gateway yang modular.

Gunakan:

`PaymentGatewayInterface`

Kemudian buat adapter/service.

Contoh:

`PaymentGatewayService`

Sistem harus siap mendukung:

- QRIS
- Virtual Account
- Bank transfer
- E-wallet
- Metode lainnya.

Jangan hardcode provider payment gateway tertentu jika belum ditentukan.

API credentials harus disimpan dalam `.env`.

---

# 14. PROVIDER API

Buat:

`ProviderInterface`

Contoh method:

- `inquiry()`
- `purchase()`
- `checkStatus()`
- `refund()` jika provider mendukung.

Kemudian buat service:

- `GameTopupService`
- `PulsaService`
- `PlnService`
- `PdamService`
- `TelkomService`.

Semua provider API harus menggunakan konfigurasi dari `.env`.

Jangan pernah hardcode API key di source code.

---

# 15. ALUR TRANSAKSI

Contoh transaksi Free Fire:

```text
User memilih Free Fire
    ↓
Memilih 70 Diamond
    ↓
Memasukkan Player ID
    ↓
Sistem menampilkan harga
    ↓
User memasukkan voucher
    ↓
Sistem validasi voucher
    ↓
Sistem menghitung total
    ↓
User klik Bayar
    ↓
Sistem mengecek saldo
    ↓
Sistem membuat transaksi
    ↓
Saldo dikurangi
    ↓
Request dikirim ke provider
    ↓
Provider memproses
    ↓
Provider berhasil
    ↓
Transaksi SUCCESS
    ↓
Profit dihitung
    ↓
Invoice dibuat
    ↓
User mendapat notifikasi
```

Jika provider gagal:

```text
Provider gagal
    ↓
Transaksi FAILED
    ↓
Saldo dikembalikan
    ↓
Wallet transaction REFUND dibuat
    ↓
User mendapat notifikasi
```

---

# 16. STATUS TRANSAKSI

Gunakan status pembayaran:

- `pending`
- `paid`
- `failed`
- `expired`
- `refunded`

Gunakan status transaksi:

- `pending`
- `processing`
- `success`
- `failed`
- `refunded`

Pastikan status dibuat menggunakan enum atau pendekatan yang konsisten.

---

# 17. ID TRANSAKSI

Setiap transaksi memiliki nomor unik.

Contoh:

`TRX-20260908-000001`

atau gunakan UUID/ULID sebagai primary key/reference.

Invoice number harus unique.

Jangan menggunakan ID increment biasa sebagai nomor transaksi yang ditampilkan ke user jika dapat membocorkan jumlah transaksi.

---

# 18. IDEMPOTENCY

Sistem harus mencegah transaksi ganda.

Contoh:

User menekan tombol bayar dua kali.

Request pertama:

`SUCCESS`

Request kedua:

ditolak karena idempotency key sudah pernah digunakan.

Gunakan:

- Unique transaction reference
- Idempotency key
- Database unique constraint
- Transaction locking.

---

# 19. SISTEM DISKON

Admin dapat membuat:

- Diskon persentase
- Diskon nominal
- Voucher.

Data voucher:

- Code
- Name
- Type
- Value
- Minimum transaction
- Maximum discount
- Usage limit
- Usage per user
- Start at
- End at
- Status.

Voucher dapat dibatasi berdasarkan:

- Produk
- Game
- Kategori
- User.

---

# 20. PERHITUNGAN DISKON

Contoh:

```text
Harga:
Rp20.000

Diskon:
10%

Diskon:
Rp2.000

Total:
Rp18.000
```

Jika maksimal diskon Rp1.500:

```text
Diskon:
Rp1.500

Total:
Rp18.500
```

Pastikan perhitungan dilakukan di server.

Jangan mempercayai harga dari frontend.

---

# 21. DASHBOARD USER

Dashboard harus menampilkan:

- Nama user
- Saldo
- Total transaksi
- Transaksi sukses
- Transaksi pending
- Transaksi gagal
- Transaksi terbaru
- Promo
- Produk populer.

Buat shortcut:

- Top Up
- Pulsa
- PLN
- PDAM
- Telkom
- Game.

---

# 22. DASHBOARD ADMIN

Dashboard admin menampilkan:

### Statistik

- Total user
- Total transaksi
- Transaksi hari ini
- Transaksi sukses
- Transaksi pending
- Transaksi gagal
- Total omzet
- Total modal
- Total keuntungan
- Total diskon
- Total refund.

### Grafik

- Transaksi harian
- Omzet
- Keuntungan
- Produk paling banyak dibeli.

Gunakan filter:

- Hari ini
- 7 hari
- Bulan ini
- Tahun ini
- Custom range.

---

# 23. MANAJEMEN USER ADMIN

Admin dapat:

- Melihat user
- Search user
- Filter user
- Detail user
- Aktif/nonaktif user
- Melihat saldo
- Menambah saldo
- Mengurangi saldo
- Melihat transaksi
- Melihat mutasi saldo.

Setiap perubahan saldo manual harus memiliki:

- Admin
- User
- Nominal
- Saldo sebelum
- Saldo sesudah
- Alasan
- Timestamp.

Simpan dalam audit log.

---

# 24. MANAJEMEN PRODUK ADMIN

Admin dapat:

- Tambah
- Edit
- Hapus
- Aktifkan
- Nonaktifkan
- Atur harga modal
- Atur harga jual
- Atur keuntungan
- Atur SKU
- Atur provider
- Atur SKU provider.

---

# 25. MANAJEMEN PROVIDER

Admin dapat melihat:

- Nama provider
- Code
- Status
- Base URL
- Produk yang menggunakan provider.

API key tidak boleh ditampilkan secara penuh.

Contoh:

`****************XYZ`

Provider dapat diaktifkan/nonaktifkan.

---

# 26. RIWAYAT TRANSAKSI USER

User dapat melihat:

- Invoice
- Produk
- Harga
- Diskon
- Total
- Status
- Tanggal.

Tambahkan:

- Search
- Filter status
- Filter tanggal
- Pagination.

---

# 27. RIWAYAT TRANSAKSI ADMIN

Admin dapat melihat seluruh transaksi.

Kolom:

- Invoice
- User
- Produk
- Kategori
- Harga modal
- Harga jual
- Diskon
- Biaya admin
- Total
- Profit
- Provider
- Status
- Tanggal.

Tambahkan:

- Search
- Filter user
- Filter produk
- Filter status
- Filter tanggal
- Pagination
- Export CSV/Excel jika memungkinkan.

---

# 28. LAPORAN KEUNTUNGAN

Buat halaman khusus:

`Laporan Keuntungan`

Tampilkan:

- Tanggal
- Produk
- Jumlah transaksi
- Omzet
- Modal
- Diskon
- Profit.

Contoh:

```text
Free Fire 70 Diamond
100 transaksi

Omzet:
Rp1.200.000

Modal:
Rp950.000

Profit:
Rp250.000
```

Admin dapat memfilter berdasarkan:

- Produk
- Kategori
- Provider
- Tanggal.

---

# 29. INVOICE

Setiap transaksi menghasilkan invoice.

Invoice berisi:

- Logo website
- Nama website
- Invoice number
- User
- Produk
- Target
- Harga
- Diskon
- Biaya admin
- Total
- Status
- Provider reference
- SN/token jika tersedia
- Waktu transaksi.

Sediakan:

- Print
- Download PDF.

---

# 30. NOTIFIKASI

Gunakan Laravel Notification.

Notifikasi ketika:

- Transaksi berhasil
- Transaksi gagal
- Transaksi pending
- Saldo bertambah
- Saldo berkurang
- Voucher digunakan
- Refund berhasil.

Siapkan struktur agar nantinya dapat dikembangkan ke:

- Email
- WhatsApp
- Telegram.

---

# 31. AUDIT LOG

Catat aktivitas penting:

- Login admin
- Logout admin
- Perubahan harga
- Perubahan saldo
- Refund
- Perubahan provider
- Perubahan voucher
- Perubahan konfigurasi.

Audit log menyimpan:

- User/admin
- Action
- Model
- Model ID
- Old data
- New data
- IP
- User agent
- Timestamp.

---

# 32. DATABASE

Buat migration untuk minimal tabel berikut.

## users

- id
- name
- email
- phone
- password
- role
- status
- timestamps

## wallets

- id
- user_id
- balance
- timestamps

## wallet_transactions

- id
- user_id
- wallet_id
- type
- amount
- balance_before
- balance_after
- reference
- description
- timestamps

## categories

- id
- name
- slug
- status
- timestamps

## games

- id
- category_id
- name
- slug
- description
- image
- status
- timestamps

## providers

- id
- name
- code
- base_url
- api_key
- api_secret
- status
- timestamps

## products

- id
- category_id
- game_id nullable
- provider_id nullable
- name
- sku
- provider_sku
- description
- cost_price
- selling_price
- profit
- status
- timestamps

## transactions

- id
- invoice_number
- user_id
- product_id
- provider_id
- target
- target_secondary
- cost_price
- selling_price
- discount
- admin_fee
- total
- profit
- payment_status
- transaction_status
- provider_reference
- serial_number
- failure_reason
- idempotency_key
- timestamps

## payments

- id
- transaction_id
- payment_method
- payment_reference
- amount
- status
- paid_at
- expired_at
- callback_payload
- timestamps

## vouchers

- id
- code
- name
- type
- value
- minimum_transaction
- maximum_discount
- usage_limit
- usage_per_user
- start_at
- end_at
- status
- timestamps

## voucher_usages

- id
- voucher_id
- user_id
- transaction_id
- discount
- timestamps

## audit_logs

- id
- user_id
- action
- model
- model_id
- old_data
- new_data
- ip_address
- user_agent
- timestamps

Sesuaikan struktur apabila diperlukan untuk meningkatkan normalisasi dan keamanan database.

---

# 33. DATABASE RELATIONSHIP

Gunakan Eloquent Relationship.

User:

- hasOne Wallet
- hasMany Transactions
- hasMany WalletTransactions
- hasMany VoucherUsages

Wallet:

- belongsTo User
- hasMany WalletTransactions

Category:

- hasMany Games
- hasMany Products

Game:

- belongsTo Category
- hasMany Products

Product:

- belongsTo Category
- belongsTo Game
- belongsTo Provider
- hasMany Transactions

Provider:

- hasMany Products
- hasMany Transactions

Transaction:

- belongsTo User
- belongsTo Product
- belongsTo Provider
- hasOne Payment.

---

# 34. VALIDASI TARGET

Jangan hanya menerima string bebas.

Buat validasi berdasarkan tipe produk.

Contoh:

Free Fire:

`player_id` wajib.

Mobile Legends:

`user_id` wajib.
`zone_id` wajib.

Pulsa:

`phone_number` wajib.
Nomor harus valid.

PLN:

`customer_number` wajib.

PDAM:

`customer_number` wajib.

Buat mekanisme agar field produk dapat berbeda tanpa mengubah banyak kode.

---

# 35. FRONTEND

Buat desain modern dan profesional.

Landing page:

- Navbar
- Hero
- Search
- Kategori
- Produk populer
- Promo
- Keunggulan
- Cara transaksi
- FAQ
- Footer.

Gunakan card untuk produk.

Contoh:

```text
Free Fire

70 Diamond

Rp12.000

[ Beli ]
```

---

# 36. CHECKOUT

Checkout harus menampilkan:

- Produk
- Target
- Harga
- Diskon
- Biaya admin
- Total.

User dapat memasukkan voucher.

Sistem melakukan perhitungan ulang di server.

Jangan menggunakan harga yang dikirim dari frontend sebagai sumber kebenaran.

---

# 37. LOADING DAN ERROR STATE

Buat:

- Loading indicator
- Toast notification
- Success message
- Error message
- Empty state
- Confirmation modal.

Contoh:

> "Apakah Anda yakin ingin melakukan transaksi Rp20.000?"

---

# 38. KEAMANAN

Implementasikan:

- CSRF
- XSS protection
- SQL injection protection
- Authentication
- Authorization
- Middleware
- Form Request validation
- Password hashing
- Rate limiting
- Secure session
- Database transaction
- Idempotency
- Audit logging.

Jangan pernah:

- Menyimpan password plain text
- Menampilkan API key
- Menaruh API key dalam source code
- Mempercayai harga dari frontend
- Menambah saldo berdasarkan redirect frontend
- Mengurangi saldo tanpa database transaction.

---

# 39. ENV

Gunakan `.env` untuk:

- Database
- Payment gateway
- Provider API
- Mail
- WhatsApp API
- Application settings.

Buat:

`.env.example`

Jangan masukkan `.env` ke repository.

Buat `.gitignore` yang benar.

---

# 40. LOGGING

Gunakan Laravel logging untuk:

- Provider request
- Provider response
- Payment callback
- Transaction error
- Refund
- API error.

Tetapi jangan menyimpan:

- Password
- API secret
- Token rahasia
- Data sensitif.

---

# 41. QUEUE

Gunakan Laravel Queue untuk proses:

- Provider request yang membutuhkan waktu
- Notification
- Sinkronisasi status
- Callback processing
- Generate PDF jika diperlukan.

Gunakan Job seperti:

- `ProcessTopupTransaction`
- `ProcessPaymentCallback`
- `SendTransactionNotification`

---

# 42. SCHEDULER

Jika diperlukan, gunakan Laravel Scheduler untuk:

- Mengecek transaksi pending
- Mengecek status provider
- Expire payment
- Sinkronisasi transaksi.

---

# 43. TESTING

Buat test untuk:

## Authentication

- Register
- Login
- Logout

## Wallet

- Deposit
- Purchase
- Refund
- Insufficient balance

## Transaction

- Successful transaction
- Failed transaction
- Pending transaction
- Duplicate transaction

## Voucher

- Valid voucher
- Expired voucher
- Usage limit
- Minimum transaction

## Security

- User tidak dapat mengakses admin
- User tidak dapat mengubah harga
- User tidak dapat mengubah saldo
- Callback tidak valid ditolak.

---

# 44. SEEDER

Buat admin dummy:

Email:

`admin@example.com`

Password:

Gunakan password development yang aman.

Buat juga kategori:

- Game
- Pulsa
- PLN
- PDAM
- Telkom

Game:

- Free Fire
- Mobile Legends
- PUBG Mobile
- Roblox
- Magic Chess: Go Go

Buat produk dummy secukupnya untuk testing.

---

# 45. ROUTING

Pisahkan route:

`routes/web.php`

untuk halaman web.

`routes/api.php`

untuk endpoint API.

Kelompokkan route admin menggunakan middleware.

Contoh admin:

- `/admin/dashboard`
- `/admin/users`
- `/admin/products`
- `/admin/providers`
- `/admin/transactions`
- `/admin/vouchers`
- `/admin/reports`

User:

- `/dashboard`
- `/products`
- `/checkout`
- `/transactions`
- `/wallet`
- `/profile`

---

# 46. ADMIN SETTINGS

Admin dapat mengatur:

- Nama website
- Logo
- Favicon
- WhatsApp
- Email
- Biaya admin
- Minimum deposit
- Maximum deposit
- Maintenance mode
- Footer
- Informasi kontak.

---

# 47. STRUKTUR FOLDER

Gunakan struktur yang rapi:

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   └── User/
│   ├── Middleware/
│   └── Requests/
│
├── Models/
│
├── Services/
│   ├── Wallet/
│   ├── Transaction/
│   ├── Product/
│   ├── Provider/
│   ├── Payment/
│   └── Voucher/
│
├── Contracts/
│   ├── Provider/
│   └── Payment/
│
├── Jobs/
│
├── Notifications/
│
└── Policies/

database/
├── migrations/
├── seeders/
└── factories/

resources/
├── views/
├── css/
└── js/

routes/
├── web.php
└── api.php
```

---

# 48. PRINSIP PENTING

Jangan membuat sistem dengan pendekatan:

```text
Controller → langsung API provider
```

Gunakan:

```text
Controller
    ↓
Request Validation
    ↓
Service
    ↓
Provider Interface
    ↓
Provider Adapter
    ↓
API Provider
```

Untuk payment:

```text
Controller
    ↓
Payment Service
    ↓
Payment Gateway Interface
    ↓
Payment Gateway Adapter
    ↓
Payment Gateway
```

---

# 49. DEVELOPMENT SECARA BERTAHAP

Jangan langsung memberikan seluruh source code dalam satu jawaban.

Bangun project secara bertahap.

## PHASE 1 — PROJECT SETUP

Buat:

- Laravel
- MySQL configuration
- `.env.example`
- Authentication
- Role user/admin
- Middleware
- Basic layout.

## PHASE 2 — DATABASE

Buat:

- Migration
- Model
- Relationship
- Seeder
- Factory.

## PHASE 3 — USER

Buat:

- Dashboard
- Profile
- Wallet
- Transaction history.

## PHASE 4 — ADMIN

Buat:

- Dashboard
- User management
- Category management
- Game management
- Product management.

## PHASE 5 — WALLET

Buat:

- Deposit
- Wallet transaction
- Balance management
- Refund.

## PHASE 6 — TRANSACTION

Buat:

- Checkout
- Transaction
- Status
- Idempotency
- Profit calculation.

## PHASE 7 — VOUCHER

Buat:

- Voucher
- Discount
- Usage limit.

## PHASE 8 — PROVIDER

Buat:

- Provider interface
- Provider service
- API integration structure.

## PHASE 9 — PAYMENT

Buat:

- Payment interface
- Payment gateway
- Callback
- Webhook.

## PHASE 10 — INVOICE

Buat:

- Invoice
- PDF
- Print.

## PHASE 11 — NOTIFICATION

Buat:

- Laravel notification
- Email
- Struktur WhatsApp/Telegram.

## PHASE 12 — REPORT

Buat:

- Sales report
- Profit report
- Transaction report.

## PHASE 13 — SECURITY

Review:

- Authorization
- Validation
- Rate limit
- Transaction locking
- Idempotency
- API security.

## PHASE 14 — TESTING

Buat Unit Test dan Feature Test.

---

# 50. CARA MEMBERIKAN KODE

Ketika mulai mengimplementasikan setiap phase:

1. Jelaskan tujuan phase.
2. Jelaskan file yang akan dibuat/diubah.
3. Berikan path setiap file.
4. Berikan kode lengkap.
5. Jangan memberikan potongan kode yang tidak lengkap.
6. Jelaskan command yang harus dijalankan.
7. Jelaskan cara testing.
8. Jika terdapat dependency tambahan, jelaskan cara install.
9. Jangan mengubah file yang belum dijelaskan.
10. Pastikan kode antar-phase tetap kompatibel.

Contoh:

`app/Models/Wallet.php`

Kemudian berikan kode lengkap file tersebut.

Setelah itu:

```bash
php artisan migrate
```

Jelaskan hasil yang harus diperoleh.

---

# 51. COMMAND

Untuk setiap tahap, berikan command Laravel yang diperlukan.

Contoh:

```bash
php artisan make:model Wallet -m
php artisan make:controller WalletController
php artisan make:request StoreWalletRequest
php artisan migrate
php artisan db:seed
```

Jangan memberikan command yang tidak diperlukan.

---

# 52. DEVELOPMENT MODE

Pada tahap awal, jangan langsung membutuhkan API provider asli.

Buat **Mock Provider** terlebih dahulu.

Contoh:

```text
User membeli Free Fire
    ↓
Mock Provider
    ↓
Response SUCCESS / FAILED
```

Dengan demikian seluruh sistem transaksi dapat diuji sebelum API provider asli tersedia.

Setelah sistem stabil, Mock Provider dapat diganti dengan provider API sebenarnya.

---

# 53. MOCK PAYMENT GATEWAY

Buat juga Mock Payment Gateway untuk development.

Contoh:

```text
User membuat deposit Rp100.000
    ↓
Mock Payment
    ↓
PENDING
    ↓
SUCCESS / FAILED
```

Tujuannya agar sistem dapat diuji tanpa menggunakan uang sungguhan.

---

# 54. ACCEPTANCE CRITERIA

Project dianggap berhasil apabila:

## User

- Dapat register
- Dapat login
- Dapat melihat saldo
- Dapat melakukan deposit
- Dapat membeli produk
- Dapat menggunakan voucher
- Dapat melihat transaksi
- Dapat melihat invoice.

## Admin

- Dapat mengelola user
- Dapat mengelola produk
- Dapat mengatur harga modal
- Dapat mengatur harga jual
- Dapat mengatur keuntungan
- Dapat membuat diskon
- Dapat melihat transaksi
- Dapat melihat keuntungan
- Dapat mengatur provider
- Dapat melihat laporan.

## Sistem

- Saldo aman
- Tidak terjadi double transaction
- Harga transaksi tersimpan sebagai snapshot
- Profit tersimpan pada transaksi
- Transaksi gagal dapat melakukan refund
- Callback dapat diverifikasi
- API key aman
- User tidak dapat mengakses admin
- Database memiliki relasi yang benar
- Testing tersedia.

---

# 55. HAL YANG TIDAK BOLEH DILAKUKAN

Jangan:

1. Menaruh API key langsung di Controller.
2. Menyimpan password plain text.
3. Mengubah harga transaksi lama ketika harga produk berubah.
4. Mengurangi saldo tanpa database transaction.
5. Menambah saldo hanya berdasarkan frontend.
6. Mempercayai harga yang dikirim browser.
7. Mengirim transaksi provider dua kali.
8. Membuat semua logic dalam Controller.
9. Membuat produk hardcoded di source code.
10. Membuat game hardcoded sehingga sulit ditambah.
11. Mengabaikan callback/webhook validation.
12. Mengabaikan authorization admin.
13. Menyimpan data rahasia di log.
14. Menggunakan ID transaksi biasa sebagai satu-satunya mekanisme pencegah duplicate transaction.

---

# 56. HASIL YANG SAYA INGINKAN

Saya ingin Anda bertindak sebagai **Senior Laravel Developer dan System Architect**.

Bantu saya membangun project ini dari awal sampai siap dikembangkan menjadi platform top up/PPOB production.

Mulai dari **PHASE 1 terlebih dahulu**.

Jangan langsung mengerjakan phase berikutnya sebelum phase sebelumnya selesai dan dapat dijalankan.

Pada setiap phase:

- Jelaskan arsitektur
- Buat struktur folder
- Buat migration/model/controller/service yang diperlukan
- Berikan kode lengkap
- Berikan command
- Berikan cara testing
- Jelaskan kemungkinan error
- Pastikan kode mengikuti best practice Laravel.

Prioritaskan terlebih dahulu sistem internal menggunakan **Mock Provider dan Mock Payment Gateway**.

API provider dan payment gateway nyata akan diintegrasikan setelah core system selesai.

Target akhirnya adalah website top up game dan PPOB yang memiliki sistem **saldo, transaksi, keuntungan, diskon, voucher, pembayaran, provider API, laporan, invoice, notifikasi, keamanan, dan admin management** yang dapat dikembangkan dalam jangka panjang.
