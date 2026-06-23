# Rancangan Database — Sistem Stock Opname

> Dokumen ini berisi rancangan lengkap tabel-tabel database untuk sistem Stock Opname berdasarkan analisis prototype frontend dan MVP produk. Dirancang mengikuti standar industri untuk sistem manajemen inventori / gudang.

---

## Ringkasan Tabel

| No | Nama Tabel | Deskripsi |
|----|-----------|-----------|
| 1 | `users` | Data pengguna sistem (admin gudang, dll) |
| 2 | `categories` | Kategori barang (Listrik, Bahan Bangunan, Cat & Kuas, dll) |
| 3 | `units` | Satuan barang (Roll, Sak, Pail, Pcs, Meter, Dus) |
| 4 | `suppliers` | Data supplier / pemasok barang |
| 5 | `items` | Master data barang (termasuk barcode, harga retail/grosir) |
| 6 | `item_prices` | Riwayat perubahan harga barang (audit trail harga) |
| 7 | `inbounds` | Header transaksi barang masuk |
| 8 | `inbound_items` | Detail item per transaksi barang masuk |
| 9 | `outbounds` | Header transaksi barang keluar |
| 10 | `outbound_items` | Detail item per transaksi barang keluar |
| 11 | `stock_opnames` | Header / sesi stock opname |
| 12 | `stock_opname_items` | Detail item per sesi stock opname |
| 13 | `returns` | Header transaksi retur |
| 14 | `return_items` | Detail item per transaksi retur |
| 15 | `stock_movements` | Log pergerakan stok (audit trail semua mutasi) |
| 16 | `item_supplier` | Pivot: relasi barang — supplier |

---

## Detail Tabel

---

### 1. `users`

> Menyimpan data pengguna sistem. Sudah ada di prototype.

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY, AUTO_INCREMENT` | |
| `nik` | `VARCHAR(20)` | `UNIQUE, NOT NULL` | Nomor Induk Karyawan (login) |
| `name` | `VARCHAR(100)` | `NOT NULL` | Nama lengkap |
| `role` | `ENUM('admin_gudang')` | `NOT NULL, DEFAULT 'admin_gudang'` | Role fix hanya admin_gudang |
| `password` | `VARCHAR(255)` | `NOT NULL` | Password (hashed) |
| `must_reset_password` | `BOOLEAN` | `DEFAULT true` | Wajib reset password saat login pertama |
| `phone` | `VARCHAR(20)` | `NULLABLE` | Nomor telepon |
| `avatar` | `VARCHAR(255)` | `NULLABLE` | Path foto profil |
| `is_active` | `BOOLEAN` | `DEFAULT true` | Status aktif/nonaktif |
| `last_login_at` | `TIMESTAMP` | `NULLABLE` | Waktu login terakhir |
| `remember_token` | `VARCHAR(100)` | `NULLABLE` | Token remember me |
| `created_at` | `TIMESTAMP` | `NULLABLE` | |
| `updated_at` | `TIMESTAMP` | `NULLABLE` | |

---

### 2. `categories`

> Master kategori barang. Dinormalisasi dari dropdown di form Create Barang.

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY, AUTO_INCREMENT` | |
| `name` | `VARCHAR(100)` | `UNIQUE, NOT NULL` | Nama kategori (cth: Listrik, Bahan Bangunan) |
| `slug` | `VARCHAR(120)` | `UNIQUE, NOT NULL` | URL-friendly identifier |
| `description` | `TEXT` | `NULLABLE` | Deskripsi kategori |
| `is_active` | `BOOLEAN` | `DEFAULT true` | Status aktif |
| `created_at` | `TIMESTAMP` | `NULLABLE` | |
| `updated_at` | `TIMESTAMP` | `NULLABLE` | |

---

### 3. `units`

> Master satuan barang. Dinormalisasi dari dropdown di form Create Barang.

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY, AUTO_INCREMENT` | |
| `name` | `VARCHAR(50)` | `UNIQUE, NOT NULL` | Nama satuan (cth: Roll, Sak, Pail, Pcs) |
| `abbreviation` | `VARCHAR(10)` | `NULLABLE` | Singkatan (cth: pcs, rll, sak) |
| `created_at` | `TIMESTAMP` | `NULLABLE` | |
| `updated_at` | `TIMESTAMP` | `NULLABLE` | |

---

### 4. `suppliers`

> Master data supplier. Dinormalisasi agar bisa tracking stok per supplier.

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY, AUTO_INCREMENT` | |
| `code` | `VARCHAR(20)` | `UNIQUE, NOT NULL` | Kode supplier (cth: SUP-001) |
| `name` | `VARCHAR(150)` | `NOT NULL` | Nama supplier (cth: PT. Eterna Kabel) |
| `contact_person` | `VARCHAR(100)` | `NULLABLE` | Nama kontak di supplier |
| `phone` | `VARCHAR(20)` | `NULLABLE` | Nomor telepon |
| `email` | `VARCHAR(100)` | `NULLABLE` | Email supplier |
| `address` | `TEXT` | `NULLABLE` | Alamat lengkap |
| `is_active` | `BOOLEAN` | `DEFAULT true` | Status aktif |
| `created_at` | `TIMESTAMP` | `NULLABLE` | |
| `updated_at` | `TIMESTAMP` | `NULLABLE` | |

---

### 5. `items`

> Master data barang. Tabel inti sistem. Mendukung barcode, harga retail/grosir, dan bottom stock.

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY, AUTO_INCREMENT` | |
| `code` | `VARCHAR(30)` | `UNIQUE, NOT NULL` | Kode barang (cth: BRG-001) |
| `barcode` | `VARCHAR(50)` | `UNIQUE, NULLABLE` | Barcode / EAN-13 / CODE-128 (untuk scan) |
| `name` | `VARCHAR(200)` | `NOT NULL` | Nama barang (cth: Kabel Eterna NYM 2x1.5) |
| `description` | `TEXT` | `NULLABLE` | Deskripsi detail barang |
| `category_id` | `BIGINT UNSIGNED` | `FK → categories.id, NOT NULL` | Kategori barang |
| `unit_id` | `BIGINT UNSIGNED` | `FK → units.id, NOT NULL` | Satuan barang |
| `harga_beli` | `DECIMAL(15,2)` | `DEFAULT 0` | Harga beli / HPP terakhir |
| `harga_jual_retail` | `DECIMAL(15,2)` | `DEFAULT 0` | Harga jual retail (eceran) |
| `harga_jual_grosir` | `DECIMAL(15,2)` | `DEFAULT 0` | Harga jual grosir |
| `min_grosir_qty` | `INT UNSIGNED` | `DEFAULT 0` | Minimum qty untuk harga grosir |
| `current_stock` | `INT` | `DEFAULT 0` | Stok saat ini (denormalisasi untuk performa) |
| `min_stock` | `INT UNSIGNED` | `DEFAULT 0` | Bottom stock / stok minimum (alert) |
| `rack_location` | `VARCHAR(50)` | `NULLABLE` | Lokasi rak (cth: Rak A1, Gudang Belakang) |
| `image` | `VARCHAR(255)` | `NULLABLE` | Path gambar barang |
| `is_active` | `BOOLEAN` | `DEFAULT true` | Status aktif |
| `created_at` | `TIMESTAMP` | `NULLABLE` | |
| `updated_at` | `TIMESTAMP` | `NULLABLE` | |
| `deleted_at` | `TIMESTAMP` | `NULLABLE` | Soft delete |

**Index:**
- `INDEX idx_items_barcode (barcode)` — untuk pencarian scan barcode
- `INDEX idx_items_category (category_id)` — untuk filter per kategori
- `INDEX idx_items_name (name)` — untuk pencarian nama
- `INDEX idx_items_stock_alert (current_stock, min_stock)` — untuk query bottom stock

---

### 6. `item_prices`

> Riwayat perubahan harga. Audit trail untuk tracking perubahan harga beli/jual.

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY, AUTO_INCREMENT` | |
| `item_id` | `BIGINT UNSIGNED` | `FK → items.id, NOT NULL` | Barang |
| `price_type` | `ENUM('beli','retail','grosir')` | `NOT NULL` | Jenis harga yang berubah |
| `old_price` | `DECIMAL(15,2)` | `NOT NULL` | Harga lama |
| `new_price` | `DECIMAL(15,2)` | `NOT NULL` | Harga baru |
| `changed_by` | `BIGINT UNSIGNED` | `FK → users.id, NOT NULL` | User yang mengubah |
| `reason` | `VARCHAR(255)` | `NULLABLE` | Alasan perubahan |
| `created_at` | `TIMESTAMP` | `NULLABLE` | Waktu perubahan |

**Index:**
- `INDEX idx_item_prices_item (item_id, created_at)` — untuk riwayat harga per barang

---

### 7. `inbounds`

> Header transaksi barang masuk. Satu transaksi bisa berisi banyak item.

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY, AUTO_INCREMENT` | |
| `transaction_code` | `VARCHAR(30)` | `UNIQUE, NOT NULL` | Nomor transaksi (cth: IN-2026-001) |
| `date` | `DATE` | `NOT NULL` | Tanggal barang masuk |
| `supplier_id` | `BIGINT UNSIGNED` | `FK → suppliers.id, NULLABLE` | Supplier pengirim |
| `notes` | `TEXT` | `NULLABLE` | Catatan transaksi |
| `status` | `ENUM('pending','diterima','dibatalkan')` | `DEFAULT 'diterima'` | Status transaksi |
| `total_amount` | `DECIMAL(15,2)` | `DEFAULT 0` | Total nilai transaksi (sum harga × qty) |
| `created_by` | `BIGINT UNSIGNED` | `FK → users.id, NOT NULL` | Dicatat oleh (admin gudang) |
| `created_at` | `TIMESTAMP` | `NULLABLE` | |
| `updated_at` | `TIMESTAMP` | `NULLABLE` | |

**Index:**
- `INDEX idx_inbounds_date (date)` — untuk laporan harian/bulanan
- `INDEX idx_inbounds_supplier (supplier_id)` — untuk stok per supplier
- `INDEX idx_inbounds_code (transaction_code)` — untuk pencarian no. transaksi

---

### 8. `inbound_items`

> Detail item pada setiap transaksi barang masuk. Menyimpan harga beli saat transaksi.

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY, AUTO_INCREMENT` | |
| `inbound_id` | `BIGINT UNSIGNED` | `FK → inbounds.id, ON DELETE CASCADE` | Header transaksi |
| `item_id` | `BIGINT UNSIGNED` | `FK → items.id, NOT NULL` | Barang |
| `quantity` | `INT UNSIGNED` | `NOT NULL` | Jumlah masuk |
| `buy_price` | `DECIMAL(15,2)` | `NOT NULL, DEFAULT 0` | Harga beli per unit saat transaksi |
| `subtotal` | `DECIMAL(15,2)` | `NOT NULL, DEFAULT 0` | quantity × buy_price |
| `expiry_date` | `DATE` | `NULLABLE` | Tanggal kadaluarsa (jika ada) |
| `batch_number` | `VARCHAR(50)` | `NULLABLE` | Nomor batch (jika ada) |
| `created_at` | `TIMESTAMP` | `NULLABLE` | |
| `updated_at` | `TIMESTAMP` | `NULLABLE` | |

**Index:**
- `INDEX idx_inbound_items_item (item_id)` — untuk query stok per barang
- `UNIQUE idx_inbound_item_unique (inbound_id, item_id)` — satu barang per transaksi

---

### 9. `outbounds`

> Header transaksi barang keluar.

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY, AUTO_INCREMENT` | |
| `transaction_code` | `VARCHAR(30)` | `UNIQUE, NOT NULL` | Nomor transaksi (cth: OUT-2026-001) |
| `date` | `DATE` | `NOT NULL` | Tanggal barang keluar |
| `type` | `ENUM('penjualan','penggunaan','retur','lainnya')` | `NOT NULL, DEFAULT 'penjualan'` | Jenis pengeluaran |
| `customer_name` | `VARCHAR(150)` | `NULLABLE` | Nama pembeli / pelanggan |
| `destination` | `VARCHAR(200)` | `NULLABLE` | Tujuan / penerima (Proyek A, Mandor B) |
| `price_type_applied` | `ENUM('retail','grosir')` | `DEFAULT 'retail'` | Jenis harga yang diterapkan |
| `notes` | `TEXT` | `NULLABLE` | Catatan transaksi |
| `status` | `ENUM('pending','selesai','dibatalkan')` | `DEFAULT 'selesai'` | Status transaksi |
| `total_amount` | `DECIMAL(15,2)` | `DEFAULT 0` | Total nilai penjualan |
| `total_cogs` | `DECIMAL(15,2)` | `DEFAULT 0` | Total HPP (Harga Pokok Penjualan) |
| `created_by` | `BIGINT UNSIGNED` | `FK → users.id, NOT NULL` | Dicatat oleh |
| `created_at` | `TIMESTAMP` | `NULLABLE` | |
| `updated_at` | `TIMESTAMP` | `NULLABLE` | |

**Index:**
- `INDEX idx_outbounds_date (date)` — untuk laporan harian/bulanan/tahunan
- `INDEX idx_outbounds_type (type)` — untuk filter jenis pengeluaran
- `INDEX idx_outbounds_code (transaction_code)` — untuk pencarian

---

### 10. `outbound_items`

> Detail item pada setiap transaksi barang keluar. Menyimpan harga jual & HPP saat transaksi.

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY, AUTO_INCREMENT` | |
| `outbound_id` | `BIGINT UNSIGNED` | `FK → outbounds.id, ON DELETE CASCADE` | Header transaksi |
| `item_id` | `BIGINT UNSIGNED` | `FK → items.id, NOT NULL` | Barang |
| `quantity` | `INT UNSIGNED` | `NOT NULL` | Jumlah keluar |
| `sell_price` | `DECIMAL(15,2)` | `NOT NULL, DEFAULT 0` | Harga jual per unit saat transaksi |
| `cogs_price` | `DECIMAL(15,2)` | `NOT NULL, DEFAULT 0` | HPP per unit saat transaksi |
| `subtotal` | `DECIMAL(15,2)` | `NOT NULL, DEFAULT 0` | quantity × sell_price |
| `profit` | `DECIMAL(15,2)` | `NOT NULL, DEFAULT 0` | (sell_price - cogs_price) × quantity |
| `created_at` | `TIMESTAMP` | `NULLABLE` | |
| `updated_at` | `TIMESTAMP` | `NULLABLE` | |

**Index:**
- `INDEX idx_outbound_items_item (item_id)` — untuk query stok per barang
- `UNIQUE idx_outbound_item_unique (outbound_id, item_id)` — satu barang per transaksi

---

### 11. `stock_opnames`

> Header / sesi stock opname. Setiap sesi bisa disimpan, dijeda, dan dilanjutkan.

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY, AUTO_INCREMENT` | |
| `code` | `VARCHAR(30)` | `UNIQUE, NOT NULL` | Kode SO (cth: SO-2026-003) |
| `session_name` | `VARCHAR(100)` | `NOT NULL` | Nama sesi (cth: SO Kuartal 1 2026) |
| `date` | `DATE` | `NOT NULL` | Tanggal pelaksanaan |
| `status` | `ENUM('draft','in_progress','review','approved','rejected')` | `DEFAULT 'draft'` | Status sesi SO |
| `notes` | `TEXT` | `NULLABLE` | Catatan sesi |
| `total_items_scanned` | `INT UNSIGNED` | `DEFAULT 0` | Jumlah item yang discan |
| `total_match` | `INT UNSIGNED` | `DEFAULT 0` | Jumlah item cocok |
| `total_difference` | `INT UNSIGNED` | `DEFAULT 0` | Jumlah item selisih |
| `approved_at` | `TIMESTAMP` | `NULLABLE` | Waktu approve |
| `approved_by` | `BIGINT UNSIGNED` | `FK → users.id, NULLABLE` | Diapprove oleh |
| `created_by` | `BIGINT UNSIGNED` | `FK → users.id, NOT NULL` | Dibuat oleh |
| `created_at` | `TIMESTAMP` | `NULLABLE` | |
| `updated_at` | `TIMESTAMP` | `NULLABLE` | |

**Index:**
- `INDEX idx_so_date (date)` — untuk filter per tanggal
- `INDEX idx_so_status (status)` — untuk filter per status

---

### 12. `stock_opname_items`

> Detail item per sesi stock opname. Menyimpan stok sistem vs stok fisik.

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY, AUTO_INCREMENT` | |
| `stock_opname_id` | `BIGINT UNSIGNED` | `FK → stock_opnames.id, ON DELETE CASCADE` | Sesi SO |
| `item_id` | `BIGINT UNSIGNED` | `FK → items.id, NOT NULL` | Barang |
| `system_stock` | `INT` | `NOT NULL` | Stok di sistem saat discan |
| `physical_stock` | `INT` | `NOT NULL` | Stok fisik hasil hitung |
| `difference` | `INT` | `NOT NULL, DEFAULT 0` | Selisih (physical - system) |
| `status` | `ENUM('cocok','kurang','lebih')` | `NOT NULL, DEFAULT 'cocok'` | Status selisih |
| `notes` | `VARCHAR(255)` | `NULLABLE` | Catatan per item (alasan selisih) |
| `scanned_at` | `TIMESTAMP` | `NULLABLE` | Waktu discan |
| `created_at` | `TIMESTAMP` | `NULLABLE` | |
| `updated_at` | `TIMESTAMP` | `NULLABLE` | |

**Index:**
- `UNIQUE idx_so_item_unique (stock_opname_id, item_id)` — satu barang per sesi SO
- `INDEX idx_so_items_status (status)` — untuk filter selisih

---

### 13. `returns`

> Header transaksi retur barang (baik retur ke supplier maupun retur dari customer).

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY, AUTO_INCREMENT` | |
| `return_code` | `VARCHAR(30)` | `UNIQUE, NOT NULL` | Kode retur (cth: RET-2026-001) |
| `type` | `ENUM('retur_supplier','retur_customer')` | `NOT NULL` | Jenis retur |
| `date` | `DATE` | `NOT NULL` | Tanggal retur |
| `reference_id` | `BIGINT UNSIGNED` | `NULLABLE` | ID inbound/outbound terkait |
| `reference_type` | `VARCHAR(50)` | `NULLABLE` | Tipe referensi ('inbound' / 'outbound') |
| `supplier_id` | `BIGINT UNSIGNED` | `FK → suppliers.id, NULLABLE` | Supplier (jika retur ke supplier) |
| `customer_name` | `VARCHAR(150)` | `NULLABLE` | Nama customer (jika retur dari customer) |
| `reason` | `TEXT` | `NOT NULL` | Alasan retur (rusak, salah kirim, dll) |
| `status` | `ENUM('pending','diproses','selesai','ditolak')` | `DEFAULT 'pending'` | Status retur |
| `total_amount` | `DECIMAL(15,2)` | `DEFAULT 0` | Total nilai retur |
| `created_by` | `BIGINT UNSIGNED` | `FK → users.id, NOT NULL` | Dicatat oleh |
| `created_at` | `TIMESTAMP` | `NULLABLE` | |
| `updated_at` | `TIMESTAMP` | `NULLABLE` | |

**Index:**
- `INDEX idx_returns_date (date)` — untuk laporan
- `INDEX idx_returns_type (type)` — untuk filter jenis retur

---

### 14. `return_items`

> Detail item per transaksi retur.

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY, AUTO_INCREMENT` | |
| `return_id` | `BIGINT UNSIGNED` | `FK → returns.id, ON DELETE CASCADE` | Header retur |
| `item_id` | `BIGINT UNSIGNED` | `FK → items.id, NOT NULL` | Barang |
| `quantity` | `INT UNSIGNED` | `NOT NULL` | Jumlah diretur |
| `price` | `DECIMAL(15,2)` | `NOT NULL, DEFAULT 0` | Harga per unit saat retur |
| `subtotal` | `DECIMAL(15,2)` | `NOT NULL, DEFAULT 0` | quantity × price |
| `condition` | `ENUM('baik','rusak','cacat')` | `DEFAULT 'rusak'` | Kondisi barang |
| `notes` | `VARCHAR(255)` | `NULLABLE` | Catatan per item |
| `created_at` | `TIMESTAMP` | `NULLABLE` | |
| `updated_at` | `TIMESTAMP` | `NULLABLE` | |

---

### 15. `stock_movements`

> Log pergerakan stok. Audit trail untuk SEMUA mutasi stok (masuk, keluar, retur, penyesuaian SO). Tabel ini krusial untuk laporan dan traceability.

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY, AUTO_INCREMENT` | |
| `item_id` | `BIGINT UNSIGNED` | `FK → items.id, NOT NULL` | Barang |
| `type` | `ENUM('inbound','outbound','return_in','return_out','adjustment','initial')` | `NOT NULL` | Jenis mutasi |
| `reference_id` | `BIGINT UNSIGNED` | `NULLABLE` | ID transaksi terkait |
| `reference_type` | `VARCHAR(50)` | `NULLABLE` | Tipe referensi (inbounds / outbounds / returns / stock_opnames) |
| `quantity` | `INT` | `NOT NULL` | Jumlah mutasi (+/-) |
| `stock_before` | `INT` | `NOT NULL` | Stok sebelum mutasi |
| `stock_after` | `INT` | `NOT NULL` | Stok setelah mutasi |
| `price` | `DECIMAL(15,2)` | `DEFAULT 0` | Harga per unit saat mutasi |
| `notes` | `VARCHAR(255)` | `NULLABLE` | Keterangan |
| `created_by` | `BIGINT UNSIGNED` | `FK → users.id, NOT NULL` | Dilakukan oleh |
| `created_at` | `TIMESTAMP` | `NULLABLE` | Waktu mutasi |

**Index:**
- `INDEX idx_movements_item_date (item_id, created_at)` — untuk riwayat stok per barang
- `INDEX idx_movements_type (type)` — untuk filter jenis mutasi
- `INDEX idx_movements_date (created_at)` — untuk laporan harian/bulanan
- `INDEX idx_movements_reference (reference_type, reference_id)` — untuk trace ke transaksi asal

---

### 16. `item_supplier` (Pivot)

> Relasi many-to-many antara barang dan supplier. Mendukung fitur stok per supplier.

| Kolom | Tipe Data | Constraint | Keterangan |
|-------|-----------|------------|------------|
| `id` | `BIGINT UNSIGNED` | `PRIMARY KEY, AUTO_INCREMENT` | |
| `item_id` | `BIGINT UNSIGNED` | `FK → items.id, ON DELETE CASCADE` | Barang |
| `supplier_id` | `BIGINT UNSIGNED` | `FK → suppliers.id, ON DELETE CASCADE` | Supplier |
| `supplier_sku` | `VARCHAR(50)` | `NULLABLE` | Kode barang di supplier |
| `default_buy_price` | `DECIMAL(15,2)` | `DEFAULT 0` | Harga beli default dari supplier ini |
| `is_primary` | `BOOLEAN` | `DEFAULT false` | Apakah supplier utama |
| `created_at` | `TIMESTAMP` | `NULLABLE` | |
| `updated_at` | `TIMESTAMP` | `NULLABLE` | |

**Index:**
- `UNIQUE idx_item_supplier_unique (item_id, supplier_id)` — satu pasangan barang-supplier

---

## Mapping Fitur MVP ke Tabel

Berikut pemetaan setiap fitur MVP ke tabel-tabel yang terlibat:

| Fitur MVP | Tabel Utama | Tabel Pendukung |
|-----------|-------------|-----------------|
| **1. Input barang masuk + harga** | `inbounds`, `inbound_items` | `items`, `suppliers`, `stock_movements` |
| **2. Input barang keluar + harga** | `outbounds`, `outbound_items` | `items`, `stock_movements` |
| **3. Stock opname** | `stock_opnames`, `stock_opname_items` | `items`, `stock_movements` |
| **4. Scan barcode** | `items` (kolom `barcode`) | Semua tabel transaksi (lookup via barcode) |
| **5. Retur** | `returns`, `return_items` | `items`, `suppliers`, `stock_movements` |
| **6. Laba rugi** | `outbound_items` (kolom `profit`, `sell_price`, `cogs_price`) | `outbounds` (kolom `total_amount`, `total_cogs`) |
| **7. Harga retail – grosir** | `items` (kolom `harga_jual_retail`, `harga_jual_grosir`, `min_grosir_qty`) | `item_prices`, `outbounds.price_type_applied` |
| **8. Laporan harian, bulanan, tahunan** | `stock_movements` | Semua tabel transaksi (query by `date`) |
| **9. Stock per supplier + bottom stock** | `item_supplier`, `inbounds` | `items` (kolom `min_stock`, `current_stock`) |

---

## Mapping Sidebar Frontend ke Tabel

| Menu Sidebar | Tabel yang Digunakan |
|-------------|---------------------|
| **Master Barang** | `items`, `categories`, `units`, `suppliers`, `item_supplier`, `item_prices` |
| **Barang Masuk** | `inbounds`, `inbound_items`, `items`, `suppliers`, `stock_movements` |
| **Barang Keluar** | `outbounds`, `outbound_items`, `items`, `stock_movements` |
| **Stock Opname** | `stock_opnames`, `stock_opname_items`, `items`, `stock_movements` |
| **User Profile** | `users` |

---

## Relasi Antar Tabel

```
users ──────────────────────────────────────────┐
  │                                              │
  ├──→ inbounds.created_by                       │
  ├──→ outbounds.created_by                      │
  ├──→ stock_opnames.created_by / approved_by    │
  ├──→ returns.created_by                        │
  ├──→ stock_movements.created_by                │
  └──→ item_prices.changed_by                    │
                                                 │
categories ──→ items.category_id                 │
units ────────→ items.unit_id                    │
                                                 │
suppliers ──┬──→ inbounds.supplier_id             │
            ├──→ returns.supplier_id              │
            └──→ item_supplier.supplier_id        │
                                                 │
items ──────┬──→ inbound_items.item_id            │
            ├──→ outbound_items.item_id           │
            ├──→ stock_opname_items.item_id       │
            ├──→ return_items.item_id             │
            ├──→ stock_movements.item_id          │
            ├──→ item_prices.item_id              │
            └──→ item_supplier.item_id            │
                                                 │
inbounds ───→ inbound_items (1:N)                │
outbounds ──→ outbound_items (1:N)               │
stock_opnames → stock_opname_items (1:N)         │
returns ────→ return_items (1:N)                 │
```

---

## Catatan Desain

> [!IMPORTANT]
> ### Prinsip Denormalisasi yang Digunakan
> - **`items.current_stock`**: Stok disimpan langsung di tabel items untuk performa query (tidak perlu SUM setiap kali). Nilainya di-update setiap ada mutasi stok melalui `stock_movements`.
> - **`outbound_items.profit`**: Laba per item disimpan langsung agar laporan laba-rugi cepat tanpa kalkulasi ulang.
> - **`stock_opnames.total_match/total_difference`**: Counter summary agar halaman review cepat.

> [!TIP]
> ### Best Practice yang Diterapkan
> 1. **Soft Delete** pada tabel `items` — barang yang dihapus tidak benar-benar hilang (histori tetap ada).
> 2. **Audit Trail via `stock_movements`** — setiap mutasi stok tercatat: siapa, kapan, dari mana, berapa.
> 3. **Harga Frozen pada Transaksi** — harga beli/jual disimpan di tabel detail transaksi (`inbound_items.buy_price`, `outbound_items.sell_price`) bukan referensi ke master, agar laporan historis tetap akurat walau harga master berubah.
> 4. **Header-Detail Pattern** — transaksi menggunakan pola header (inbounds) + detail (inbound_items) sesuai standar industri.
> 5. **Polymorphic Reference** pada `stock_movements` — kolom `reference_type` + `reference_id` memungkinkan trace ke sumber mutasi apapun (inbound, outbound, retur, SO adjustment).

> [!WARNING]
> ### Hal yang Perlu Diperhatikan Saat Implementasi
> - Selalu update `items.current_stock` di dalam **database transaction** yang sama dengan insert ke `stock_movements` untuk menghindari data race.
> - Generate `transaction_code` / `return_code` secara atomik (gunakan DB sequence atau lock) untuk menghindari duplikasi.
> - Validasi bahwa `outbound_items.quantity` tidak melebihi `items.current_stock` saat transaksi keluar.
