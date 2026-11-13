# 📋 Guideline Penyempurnaan Inventory Management System
## Standar Industri Retail

> Dokumen ini disusun dari perspektif **pemilik toko elektronik** yang sudah puluhan tahun beroperasi manual (buku besar, nota karbon, hafalan stok) dan sekarang ingin semua tercatat rapi di sistem digital. Setiap poin berasal dari **pain point nyata** yang dialami sehari-hari.

---

## 📊 Ringkasan Analisis Kondisi Saat Ini

### ✅ Yang Sudah Baik (Fondasi Kuat)
| Aspek | Status | Catatan |
|-------|--------|---------|
| Master Item + Barcode | ✅ Sudah ada | Code auto-generate, barcode scanner support |
| Header-Detail Pattern | ✅ Benar | Inbound → InboundItems, Outbound → OutboundItems |
| Stock Movement Audit Trail | ✅ Sudah ada | Setiap mutasi tercatat di `stock_movements` |
| Harga Retail & Grosir | ✅ Sudah ada | Termasuk `min_grosir_qty` |
| Denormalisasi `current_stock` | ✅ Benar | Untuk performa query |
| Harga Frozen di Transaksi | ✅ Benar | `buy_price`, `sell_price` disimpan per transaksi |
| Profit/Loss Dashboard | ✅ Sudah ada | Widget laba-rugi, chart trend, top selling |
| Stock Opname | ✅ Sudah ada | Dengan selisih otomatis |
| Soft Delete Items | ✅ Sudah ada | Histori tetap terjaga |
| Bottom Stock Alert | ✅ Sudah ada | Widget + filter di dashboard |

### ❌ Gap Kritis yang Harus Diperbaiki
| No | Gap | Dampak Bisnis | Prioritas |
|----|-----|---------------|-----------|
| 1 | Tidak ada **Merk/Brand** | Tidak bisa bilang "ambilkan Eterna yang 2x1.5" vs "Kabelindo yang 2x1.5" — padahal ini cara pemilik toko mengidentifikasi barang | 🔴 CRITICAL |
| 2 | Retur hanya **ke supplier** (return_out) | Realita: customer juga bisa retur barang rusak/salah beli. Stok harus balik masuk | 🔴 CRITICAL |
| 3 | Tidak ada **Nomor Surat Jalan / Invoice** di Inbound | Pemilik toko selalu mencocokkan barang masuk dengan surat jalan dari supplier | 🔴 CRITICAL |
| 4 | Tidak ada **Konversi Satuan** | Beli per DUS (isi 100 pcs), jual per PCS — ini sangat umum di toko elektronik | 🔴 CRITICAL |
| 5 | Tidak ada **Transaction Locking** | Transaksi yang sudah selesai masih bisa di-edit → data integrity hancur | 🔴 CRITICAL |
| 6 | Tidak ada **Validasi Stok Negatif** | Bisa outbound melebihi stok → stok minus yang tidak masuk akal | 🔴 CRITICAL |
| 7 | Transaction code pakai `rand()` | Bisa collision, tidak sequential, susah dilacak kronologis | 🟡 HIGH |
| 8 | Tidak ada **Sub-Kategori** | "Listrik" terlalu luas — perlu: Kabel, Saklar, Stop Kontak, MCB, dll | 🟡 HIGH |
| 9 | Tidak ada **Export/Print** laporan | Pemilik toko butuh cetak untuk arsip pajak dan rekap manual | 🟡 HIGH |
| 10 | Tidak ada **Stock Card** (Kartu Stok) | Laporan mutasi per barang — ini dokumen WAJIB di industri | 🟡 HIGH |
| 11 | `item_supplier` pivot kurang dimanfaatkan | Sudah ada tabel, tapi tidak ada UI untuk manage lead time, minimum order, dll | 🟡 HIGH |
| 12 | Tidak ada **Diskon** di Outbound | Customer besar sering minta diskon khusus per item atau per transaksi | 🟡 HIGH |
| 13 | Tidak ada mekanisme **Pembatalan (Void)** yang proper | Edit transaksi selesai ≠ void. Void harus reverse stock movement | 🟡 HIGH |
| 14 | Outbound `total_cogs` tidak terkalkulasi otomatis | Field ada tapi tidak di-populate di form/model | 🟢 MEDIUM |
| 15 | Dashboard chart pakai data statis `[7, 2, 10, 3, 15, 4, 17]` | Chart sparkline di Stats widget bukan data real | 🟢 MEDIUM |
| 16 | Tidak ada **Activity Log** user | Siapa melakukan apa, kapan — penting untuk single-user pun (audit diri sendiri) | 🟢 MEDIUM |

---

## 🏗️ FASE 1 — Perbaikan Kritis (Data Model & Business Logic)

### 1.1 Tambah Master Merk/Brand

> **Perspektif Pemilik Toko:**
> "Kalau customer bilang 'ada kabel Eterna 2x1.5 nggak?', saya harus bisa cari berdasarkan MERK, bukan cuma nama barang. Di toko saya, satu jenis kabel bisa dari 5 merk berbeda dengan harga berbeda-beda."

#### Database: Tabel `brands`
```
| Kolom       | Tipe           | Constraint                      | Keterangan                              |
|-------------|----------------|---------------------------------|-----------------------------------------|
| id          | BIGINT UNSIGNED| PK, AUTO_INCREMENT              |                                         |
| name        | VARCHAR(100)   | UNIQUE, NOT NULL                | Nama merk (Eterna, Panasonic, Philips)  |
| slug        | VARCHAR(120)   | UNIQUE, NOT NULL                | URL-friendly                            |
| logo        | VARCHAR(255)   | NULLABLE                        | Path logo merk                          |
| description | TEXT           | NULLABLE                        | Catatan tentang merk                    |
| is_active   | BOOLEAN        | DEFAULT true                    |                                         |
| created_at  | TIMESTAMP      | NULLABLE                        |                                         |
| updated_at  | TIMESTAMP      | NULLABLE                        |                                         |
```

#### Perubahan di `items`
- Tambah kolom `brand_id` → `FK → brands.id, NULLABLE`
- Tambah index: `INDEX idx_items_brand (brand_id)`

#### Contoh Data Real Toko Elektronik
| Brand | Produk Tipikal |
|-------|---------------|
| Eterna | Kabel NYM, NYA, Stop Kontak |
| Panasonic | Saklar, Stop Kontak, MCB |
| Philips | Lampu LED, Downlight |
| Schneider | MCB, ELCB, Panel Box |
| Broco | Saklar, Fitting, T-Doos |
| Supreme | Kabel NYM, NYAF |
| Clipsal | Saklar premium, Outlet |

#### Implementasi Filament
- Buat `BrandResource` dengan CRUD sederhana (seperti `CategoryResource`)
- Di `ItemForm`, tambah `Select::make('brand_id')` setelah `name`, sebelum `category_id`
- Di `ItemsTable`, tambah kolom `brand.name` dengan badge
- Filter tambahan di table: filter by brand

---

### 1.2 Sistem Retur yang Lengkap (Inbound Return + Outbound Return)

> **Perspektif Pemilik Toko:**
> "Retur itu ada 2 arah. Kalau barang dari supplier cacat/salah kirim, saya retur KE supplier (stok berkurang). Tapi kalau customer beli lampu terus seminggu kemudian mati, dia retur ke saya (stok bertambah lagi). Dua-duanya harus bisa dicatat."

#### Perubahan di `returns` — Kembalikan kolom `type`
```
| Kolom           | Tipe                              | Constraint    | Keterangan                        |
|-----------------|-----------------------------------|---------------|-----------------------------------|
| type            | ENUM('supplier','customer')       | NOT NULL      | Arah retur                        |
| customer_name   | VARCHAR(150)                      | NULLABLE      | Nama customer (jika retur customer)|
| reference_code  | VARCHAR(30)                       | NULLABLE      | No. transaksi asli (IN-xxx/OUT-xxx)|
```

#### Logic di `ReturnItem` Model

```php
protected static function booted()
{
    static::created(function ($model) {
        $returnTransaction = $model->returnTransaction;
        $item = $model->item;
        $stockBefore = $item->current_stock;
        
        if ($returnTransaction->type === 'supplier') {
            // Retur KE supplier → stok BERKURANG
            $qty = -$model->quantity;
            $movementType = 'return_out';
            $notes = 'Retur ke Supplier: ' . ($returnTransaction->supplier?->name ?? '-');
        } else {
            // Retur DARI customer → stok BERTAMBAH  
            $qty = +$model->quantity;
            $movementType = 'return_in';
            $notes = 'Retur dari Customer: ' . ($returnTransaction->customer_name ?? '-');
        }
        
        $item->current_stock += $qty;
        $item->save();
        
        StockMovement::create([
            'item_id' => $item->id,
            'type' => $movementType,
            'reference_id' => $returnTransaction->id,
            'reference_type' => 'returns',
            'quantity' => $qty,
            'stock_before' => $stockBefore,
            'stock_after' => $item->current_stock,
            'price' => $model->price ?? 0,
            'notes' => $notes,
            'created_by' => auth()->id() ?? 1,
        ]);
    });
}
```

#### Form ReturnTransaction — Dinamis berdasarkan Type
```
- Pilih type = "supplier" → Tampilkan field Supplier (required), sembunyikan customer_name
- Pilih type = "customer" → Tampilkan field customer_name (required), sembunyikan supplier  
- Field condition tetap ada: 'baik', 'rusak', 'cacat'
- Tambah field 'action' per item: 'masukkan_ke_stok', 'buang/disposal' (untuk retur customer dengan barang rusak)
```

#### Kolom tambahan di `return_items`
```
| Kolom            | Tipe                                | Keterangan                                |
|------------------|-------------------------------------|-------------------------------------------|
| action           | ENUM('restock','dispose','repair')  | Apa yang dilakukan dengan barang retur     |
| original_transaction_code | VARCHAR(30)              | Kode transaksi asal (traceability)        |
```

---

### 1.3 Nomor Surat Jalan / Invoice Reference di Inbound

> **Perspektif Pemilik Toko:**
> "Setiap kali barang datang dari supplier, pasti ada surat jalan. Saya harus cocokkan item per item dengan surat jalan itu. Kalau ada selisih, saya langsung telepon supplier. Nomor surat jalan ini juga saya butuhkan kalau mau retur — supplier pasti tanya 'surat jalan nomor berapa?'"

#### Perubahan di `inbounds`
```
| Kolom                | Tipe          | Constraint    | Keterangan                              |
|----------------------|---------------|---------------|-----------------------------------------|
| supplier_invoice_no  | VARCHAR(50)   | NULLABLE      | Nomor faktur/invoice dari supplier      |
| delivery_note_no     | VARCHAR(50)   | NULLABLE      | Nomor surat jalan dari supplier         |
| received_by          | VARCHAR(100)  | NULLABLE      | Nama yang menerima barang fisik         |
| payment_status       | ENUM          | DEFAULT 'unpaid'| 'unpaid', 'partial', 'paid'           |
| payment_due_date     | DATE          | NULLABLE      | Jatuh tempo pembayaran                  |
```

#### Implementasi di InboundForm
- Tambahkan section "Informasi Supplier" dengan field invoice dan surat jalan
- Tambahkan section "Informasi Penerimaan" dengan `received_by`
- Tambahkan section "Pembayaran" (opsional, bisa di fase lanjutan)

---

### 1.4 Konversi Satuan (Unit Conversion)

> **Perspektif Pemilik Toko:**
> "Saya beli kabel per ROL (100 meter), tapi jual per METER. Saya beli lampu per DUS (isi 20), tapi jual per PCS. Ini HARUS bisa dicatat di sistem. Kalau tidak, stok pasti kacau."

#### Database: Tabel `unit_conversions`
```
| Kolom          | Tipe           | Constraint                      | Keterangan                              |
|----------------|----------------|---------------------------------|-----------------------------------------|
| id             | BIGINT UNSIGNED| PK, AUTO_INCREMENT              |                                         |
| item_id        | BIGINT UNSIGNED| FK → items.id, NOT NULL         | Barang yang punya konversi              |
| from_unit_id   | BIGINT UNSIGNED| FK → units.id, NOT NULL         | Satuan beli (DUS, ROL)                  |
| to_unit_id     | BIGINT UNSIGNED| FK → units.id, NOT NULL         | Satuan jual (PCS, METER)               |
| conversion_rate| DECIMAL(15,4)  | NOT NULL                        | 1 DUS = 20 PCS → rate = 20             |
| created_at     | TIMESTAMP      | NULLABLE                        |                                         |
| updated_at     | TIMESTAMP      | NULLABLE                        |                                         |
```

#### Perubahan di `items`
```
| Kolom              | Tipe           | Constraint    | Keterangan                              |
|--------------------|----------------|---------------|-----------------------------------------|
| purchase_unit_id   | BIGINT UNSIGNED| FK → units.id | Satuan beli (DUS, ROL, SAK)             |
| sell_unit_id       | BIGINT UNSIGNED| FK → units.id | Satuan jual (PCS, METER, KG)            |
| stock_unit_id      | BIGINT UNSIGNED| FK → units.id | Satuan stok = satuan terkecil           |
```

> [!IMPORTANT]
> **Prinsip: `current_stock` SELALU disimpan dalam satuan terkecil (stock_unit).**
> Contoh: Beli 2 DUS lampu (1 DUS = 20 PCS) → `current_stock += 40` (dalam PCS).

#### Contoh Skenario

| Barang | Beli Dalam | Jual Dalam | Stok Dalam | Konversi |
|--------|-----------|------------|-----------|----------|
| Kabel NYM 2x1.5 | ROL (100m) | METER | METER | 1 ROL = 100 METER |
| Lampu LED Philips 10W | DUS (20pcs) | PCS | PCS | 1 DUS = 20 PCS |
| Semen | SAK | SAK | SAK | 1:1 (no conversion) |
| Cat Dulux 5L | PAIL | PAIL | PAIL | 1:1 (no conversion) |
| Paku 5cm | KG | KG/ONS | GRAM | 1 KG = 1000 GRAM |

#### Logic di Inbound
```
Beli 3 ROL Kabel Eterna 2x1.5:
→ quantity_received = 3 (dalam purchase_unit = ROL)
→ stock_added = 3 × 100 = 300 (dalam stock_unit = METER)  
→ current_stock += 300
```

#### Logic di Outbound
```
Jual 15 METER Kabel Eterna 2x1.5:
→ quantity_sold = 15 (dalam sell_unit = METER)
→ stock_deducted = 15 (sudah dalam stock_unit = METER)
→ current_stock -= 15
```

---

### 1.5 Transaction Locking & Void Mechanism

> **Perspektif Pemilik Toko:**
> "Kalau barang sudah masuk dan saya sudah terima, catatan itu tidak boleh bisa diubah sembarangan. Kalau mau batalkan, harus ada catatan pembatalan yang jelas — bukan dihapus begitu saja."

#### Aturan Locking
```
Status 'diterima'/'selesai'/'approved' → LOCK transaksi (tidak bisa edit)
Hanya bisa melakukan VOID → membuat reverse stock movement

VOID mechanism:
1. Set status → 'voided'  
2. Untuk setiap item di transaksi, buat stock_movement kebalikannya
3. Update current_stock
4. Catat alasan void
5. Catat siapa yang void dan kapan
```

#### Kolom tambahan di `inbounds` dan `outbounds`
```
| Kolom        | Tipe          | Constraint | Keterangan                  |
|--------------|---------------|------------|-----------------------------|
| voided_at    | TIMESTAMP     | NULLABLE   | Waktu pembatalan            |
| voided_by    | BIGINT UNSIGNED| NULLABLE  | FK → users.id               |
| void_reason  | TEXT          | NULLABLE   | Alasan pembatalan           |
```

#### Implementasi di Filament
- `EditAction` → hidden jika status sudah final
- Tambah `VoidAction` → custom action dengan modal konfirmasi + alasan
- Di tabel, tampilkan badge `VOID` berwarna merah untuk transaksi yang di-void

---

### 1.6 Validasi Stok — Cegah Stok Negatif

> **Perspektif Pemilik Toko:**
> "Bagaimana bisa jual 50 pcs kalau stok cuma ada 30? Sistem harus MENOLAK transaksi keluar yang melebihi stok."

#### Implementasi di OutboundItem Model
```php
protected static function booted()
{
    static::creating(function ($model) {
        $item = Item::find($model->item_id);
        if ($item->current_stock < $model->quantity) {
            throw new \Exception(
                "Stok {$item->name} tidak cukup. " .
                "Tersedia: {$item->current_stock}, " .
                "Diminta: {$model->quantity}"
            );
        }
        // ... existing subtotal/profit calculation
    });
}
```

#### Implementasi di OutboundForm (Filament validation)
```php
TextInput::make('quantity')
    ->numeric()
    ->required()
    ->live(onBlur: true)
    ->rules([
        fn (callable $get): \Closure => function ($attribute, $value, $fail) use ($get) {
            $item = Item::find($get('item_id'));
            if ($item && $value > $item->current_stock) {
                $fail("Stok tidak cukup. Tersedia: {$item->current_stock}");
            }
        },
    ])
```

---

### 1.7 Perbaikan Transaction Code Generation

> **Perspektif Pemilik Toko:**
> "Nomor nota harus urut! Kalau auditor datang dan lihat nota saya loncat-loncat, pasti dicurigai. IN-2026-0001, IN-2026-0002, IN-2026-0003 — harus berurutan."

#### Pola yang Benar
```php
public static function generateTransactionCode(string $prefix): string
{
    // Format: PREFIX-YYYY-MMDD-NNNN
    // Contoh: IN-2026-0625-0001
    
    $today = now();
    $datePrefix = $prefix . '-' . $today->format('Y') . '-' . $today->format('md');
    
    $lastTransaction = self::query()
        ->where('transaction_code', 'like', $datePrefix . '%')
        ->orderByDesc('transaction_code')
        ->lockForUpdate()  // Prevent race condition
        ->first();
    
    if ($lastTransaction) {
        $lastNumber = (int) substr($lastTransaction->transaction_code, -4);
        $nextNumber = $lastNumber + 1;
    } else {
        $nextNumber = 1;
    }
    
    return $datePrefix . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
}
```

**Hasil:**
- `IN-2026-0625-0001` (Inbound pertama tanggal 25 Juni 2026)
- `OUT-2026-0625-0001` (Outbound pertama tanggal 25 Juni 2026)
- `RET-2026-0625-0001` (Return pertama tanggal 25 Juni 2026)
- `SO-2026-0625-0001` (Stock Opname pertama tanggal 25 Juni 2026)

---

## 🏗️ FASE 2 — Penyempurnaan Fungsional

### 2.1 Sub-Kategori (Category Hierarchy)

> **Perspektif Pemilik Toko:**
> "Kategori 'Listrik' itu terlalu luas. Di toko saya, 'Listrik' itu ada: Kabel, Saklar & Stop Kontak, MCB & Panel, Lampu, Fitting & Ballast, dan lain-lain. Saya perlu breakdown lebih detail untuk laporan dan pencarian."

#### Perubahan di `categories`
```
| Kolom      | Tipe           | Constraint          | Keterangan                |
|------------|----------------|---------------------|---------------------------|
| parent_id  | BIGINT UNSIGNED| FK → categories.id, NULLABLE | Kategori induk    |
| level      | TINYINT        | DEFAULT 0           | 0 = root, 1 = sub         |
```

#### Contoh Hierarki untuk Toko Elektronik
```
📁 Listrik
   ├── 🔌 Kabel & Kawat
   ├── 🔲 Saklar & Stop Kontak  
   ├── ⚡ MCB & Panel
   ├── 💡 Lampu & LED
   ├── 🔧 Fitting & Ballast
   └── 📦 Accessories Listrik

📁 Bahan Bangunan
   ├── 🧱 Semen & Mortar
   ├── 🎨 Cat & Pelapis
   ├── 🪠 Pipa & Fitting PVC
   └── 🔩 Baut, Mur, Paku

📁 Alat & Perkakas
   ├── 🔨 Hand Tools
   ├── ⚙️ Power Tools
   └── 📐 Alat Ukur
```

#### Implementasi Filament
- Form: `Select::make('parent_id')` dengan `->relationship('parent', 'name')` untuk memilih induk
- Table: Tampilkan sebagai tree/indent atau badge breadcrumb "Listrik > Kabel & Kawat"
- Filter item by sub-category

---

### 2.2 Item-Supplier Relationship yang Lebih Kaya

> **Perspektif Pemilik Toko:**
> "Setiap supplier punya harga berbeda untuk barang yang sama. PT Eterna kasih harga Rp 250.000/rol, kalau dari distributor lain bisa Rp 260.000. Saya juga perlu tahu lead time-nya — kalau pesan dari Eterna direct butuh 3 hari, kalau dari distributor lokal besok sampai."

#### Perkaya tabel `item_supplier`
```
| Kolom              | Tipe           | Constraint    | Keterangan                              |
|--------------------|----------------|---------------|-----------------------------------------|
| supplier_sku       | VARCHAR(50)    | NULLABLE      | Kode barang versi supplier              |
| default_buy_price  | DECIMAL(15,2)  | DEFAULT 0     | Harga beli standar dari supplier ini    |
| last_buy_price     | DECIMAL(15,2)  | NULLABLE      | Harga beli terakhir (update otomatis)   |
| min_order_qty      | INT UNSIGNED   | DEFAULT 1     | Minimum order quantity                  |
| lead_time_days     | INT UNSIGNED   | NULLABLE      | Waktu pengiriman dalam hari             |
| is_primary         | BOOLEAN        | DEFAULT false  | Supplier utama untuk item ini           |
| notes              | TEXT           | NULLABLE      | Catatan khusus                          |
| last_ordered_at    | TIMESTAMP      | NULLABLE      | Terakhir kali pesan dari supplier ini   |
```

#### Auto-Update `last_buy_price`
Setiap kali Inbound dari supplier tersebut di-create, update `last_buy_price` di pivot `item_supplier`.

#### Implementasi di ItemForm
Tampilkan sebagai Relation Manager (tab terpisah di halaman edit Item):
```
Tab "Supplier" di halaman Edit Item:
┌──────────────────────────────────────────────────────────────┐
│ Supplier         │ SKU Supplier │ Harga Beli │ Lead Time │ ★ │
│ PT Eterna        │ ET-NYM-215   │ Rp 250.000 │ 3 hari    │ ★ │
│ Toko Bangunan XY │ -            │ Rp 260.000 │ 1 hari    │   │
│ CV Elektro Jaya  │ EJ-4421      │ Rp 255.000 │ 2 hari    │   │
└──────────────────────────────────────────────────────────────┘
★ = Supplier utama (is_primary)
```

---

### 2.3 Diskon per Item dan per Transaksi di Outbound

> **Perspektif Pemilik Toko:**
> "Langganan besar selalu minta diskon. Kadang diskon per item (saklar ini saya kasih diskon 10%), kadang diskon keseluruhan nota (total belanja di atas 5 juta, diskon 5%). Ini HARUS bisa dicatat biar laporan laba-rugi akurat."

#### Perubahan di `outbound_items`
```
| Kolom           | Tipe           | Constraint    | Keterangan                          |
|-----------------|----------------|---------------|-------------------------------------|
| discount_type   | ENUM           | NULLABLE      | 'percentage', 'fixed'               |
| discount_value  | DECIMAL(15,2)  | DEFAULT 0     | Nilai diskon (10 = 10%, atau nominal)|
| discount_amount | DECIMAL(15,2)  | DEFAULT 0     | Jumlah diskon dalam rupiah          |
| price_before_discount | DECIMAL(15,2) | NOT NULL | Harga sebelum diskon              |
```

#### Perubahan di `outbounds`
```
| Kolom                | Tipe           | Constraint    | Keterangan                      |
|----------------------|----------------|---------------|---------------------------------|
| overall_discount_pct | DECIMAL(5,2)   | DEFAULT 0     | Diskon keseluruhan nota (%)     |
| overall_discount_amt | DECIMAL(15,2)  | DEFAULT 0     | Total diskon nota               |
| total_before_discount| DECIMAL(15,2)  | DEFAULT 0     | Total sebelum diskon            |
```

#### Kalkulasi
```
Subtotal per item = (sell_price × quantity) - discount_amount
Total nota = Σ subtotal - overall_discount
Profit per item = subtotal - (cogs_price × quantity)
```

---

### 2.4 Kartu Stok (Stock Card) — Laporan per Barang

> **Perspektif Pemilik Toko:**
> "Saya butuh lihat riwayat lengkap satu barang. Berapa masuk, berapa keluar, dari siapa, ke siapa, tanggal berapa. Di dunia manual ini namanya 'Kartu Stok' — setiap barang punya kartunya sendiri."

#### Implementasi: Halaman View Item → Tab Kartu Stok

Buat sebagai **custom Filament page** atau **Relation Manager** di Item Resource:

```
╔══════════════════════════════════════════════════════════════════════╗
║  KARTU STOK — Kabel Eterna NYM 2x1.5mm (ITM-0023)                 ║
║  Periode: Juni 2026                                                 ║
╠════════════╤═══════════╤══════╤══════╤═══════╤═══════════╤═════════╣
║ Tanggal    │ Keterangan│Masuk │Keluar│ Saldo │ Harga/Unit│Referensi║
╠════════════╪═══════════╪══════╪══════╪═══════╪═══════════╪═════════╣
║ 01/06/2026 │ Saldo Awal│  -   │  -   │  150  │     -     │    -    ║
║ 03/06/2026 │ Inbound   │  200 │  -   │  350  │ Rp250.000 │IN-0025  ║
║ 05/06/2026 │ Penjualan │  -   │  45  │  305  │ Rp350.000 │OUT-0103 ║
║ 10/06/2026 │ Retur Sup │  -   │  10  │  295  │ Rp250.000 │RET-0008 ║
║ 15/06/2026 │ SO Adjust │  5   │  -   │  300  │     -     │SO-0003  ║
║ 20/06/2026 │ Penjualan │  -   │  80  │  220  │ Rp340.000 │OUT-0156 ║
╚════════════╧═══════════╧══════╧══════╧═══════╧═══════════╧═════════╝
```

Data source: Query dari `stock_movements` WHERE `item_id = X`, ORDER BY `created_at ASC`

#### Implementasi Filament
- Buat sebagai **custom page** `StockCard` di `ItemResource`
- Atau buat sebagai **RelationManager** yang menampilkan `stockMovements`
- Tambahkan tombol **Export PDF** dan **Export Excel**

---

### 2.5 Export & Print Laporan

> **Perspektif Pemilik Toko:**
> "Saya butuh cetak laporan bulanan untuk arsip dan keperluan pajak. Minimal bisa export ke Excel atau PDF."

#### Laporan yang Dibutuhkan (Standar Industri)

| No | Nama Laporan | Deskripsi | Format |
|----|-------------|-----------|--------|
| 1 | **Laporan Barang Masuk** | Rekap inbound per periode, per supplier | PDF, Excel |
| 2 | **Laporan Barang Keluar** | Rekap outbound/penjualan per periode | PDF, Excel |
| 3 | **Laporan Stok** | Daftar semua barang + stok saat ini | PDF, Excel |
| 4 | **Kartu Stok** | Mutasi per barang per periode | PDF |
| 5 | **Laporan Laba Rugi** | Revenue, COGS, Gross Profit per periode | PDF, Excel |
| 6 | **Laporan Stock Opname** | Hasil SO termasuk selisih | PDF |
| 7 | **Laporan Retur** | Rekap retur per periode | PDF, Excel |
| 8 | **Laporan Stok Menipis** | Barang yang di bawah min_stock | PDF, Excel |
| 9 | **Laporan Pembelian per Supplier** | Rekap pembelian ke setiap supplier | PDF, Excel |
| 10 | **Laporan Barang Terlaris** | Ranking penjualan per periode | PDF, Excel |

#### Rekomendasi Package
- **Laravel Excel** (`maatwebsite/excel`) → untuk export Excel
- **Laravel DomPDF** (`barryvdh/laravel-dompdf`) → untuk export PDF
- **Filament Export Action** → integrasikan di Table Header Actions

#### Implementasi di setiap Table
```php
->headerActions([
    ExportAction::make()
        ->exports([
            ExcelExport::make('excel')
                ->fromTable()
                ->withFilename('laporan-inbound-' . now()->format('Y-m-d')),
        ]),
    // Custom PDF action
    Action::make('exportPdf')
        ->label('Export PDF')
        ->icon('heroicon-o-document-arrow-down')
        ->action(fn () => /* generate PDF */),
])
```

---

### 2.6 Perbaikan Outbound — `total_cogs` Auto-Calculate

> **Perspektif Pemilik Toko:**
> "Saya perlu tahu berapa modal yang keluar di setiap penjualan. Bukan cuma omzet, tapi HPP-nya juga."

#### Perbaikan di Outbound Model
```php
// Di Outbound model, tambahkan method:
public function recalculateTotals(): void
{
    $this->total_amount = $this->items->sum('subtotal');
    $this->total_cogs = $this->items->sum(fn ($item) => $item->cogs_price * $item->quantity);
    $this->save();
}

// Hook setelah OutboundItem created/deleted:
static::created(function ($model) {
    // ... existing logic
    $model->outbound->recalculateTotals();
});
```

---

## 🏗️ FASE 3 — Peningkatan UX & Operasional

### 3.1 Perbaikan Dashboard — Data Real, Bukan Statis

> **Perspektif Pemilik Toko:**
> "Saya buka dashboard, saya mau langsung lihat kondisi toko hari ini. Berapa penjualan, berapa laba, ada masalah stok nggak, dan tren minggu ini."

#### Perbaikan StatsOverviewWidget
```php
// Ganti chart statis [7, 2, 10, 3, 15, 4, 17] dengan data real 7 hari terakhir:
->chart(
    Outbound::where('type', 'penjualan')
        ->where('status', 'selesai')
        ->where('created_at', '>=', now()->subDays(6)->startOfDay())
        ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
        ->groupByRaw('DATE(created_at)')
        ->orderBy('date')
        ->pluck('total')
        ->toArray()
)
```

#### Widget Tambahan yang Dibutuhkan

| Widget | Tipe | Deskripsi |
|--------|------|-----------|
| **Transaksi Hari Ini** | Table | List 10 transaksi terakhir hari ini |
| **Barang Sering Di-Retur** | Table | Top 5 barang yang paling sering diretur (indikator quality issue) |
| **Hutang Supplier** | Stats | Total inbound dengan status `payment_status = 'unpaid'` |
| **Perbandingan Bulanan** | Chart | Bar chart: penjualan bulan ini vs bulan lalu |
| **Stok Mati (Dead Stock)** | Table | Barang yang tidak ada movement > 90 hari |

---

### 3.2 Notification / Alert System

> **Perspektif Pemilik Toko:**
> "Saya mau dikasih tahu otomatis kalau ada barang yang stoknya tinggal sedikit. Jangan sampai customer datang baru tahu stok habis."

#### Jenis Notifikasi

| Trigger | Pesan | Severity |
|---------|-------|----------|
| `current_stock <= min_stock` | "Stok [Nama Barang] tinggal [X] [satuan]. Segera restock!" | 🔴 Critical |
| `current_stock = 0` | "STOK HABIS: [Nama Barang]" | 🔴 Critical |
| Payment due date H-3 | "Hutang ke [Supplier] jatuh tempo [tanggal]" | 🟡 Warning |
| Barang tidak ada movement > 90 hari | "Dead Stock Alert: [Nama Barang] tidak ada transaksi 90 hari" | 🟢 Info |
| Stock Opname selisih besar (>10%) | "Selisih signifikan di SO [kode]: [barang] selisih [N] unit" | 🔴 Critical |

#### Implementasi
- Gunakan **Filament Notifications** (sudah built-in)
- Buat **Scheduled Command** (Laravel Scheduler) yang cek kondisi setiap pagi
- Tampilkan badge notifikasi di navigation

---

### 3.3 Search & Filter yang Lebih Powerful

> **Perspektif Pemilik Toko:**
> "Saya mau bisa cari barang pakai cara apapun — ketik nama, scan barcode, ketik kode, atau pilih merk. Harus cepat!"

#### Global Search
- Aktifkan **Filament Global Search** di semua resource
- Item: searchable by `code`, `barcode`, `name`, `brand.name`
- Inbound/Outbound: searchable by `transaction_code`, `supplier.name`

#### Filter Tambahan di ItemsTable
```php
->filters([
    SelectFilter::make('brand_id')
        ->relationship('brand', 'name')
        ->label('Merk')
        ->searchable()
        ->preload(),
    SelectFilter::make('category_id')
        ->relationship('category', 'name')
        ->label('Kategori')
        ->searchable()
        ->preload(),
    SelectFilter::make('supplier')
        ->relationship('suppliers', 'name')
        ->label('Supplier')
        ->searchable()
        ->preload(),
    TernaryFilter::make('stock_status')
        ->label('Status Stok')
        ->queries(
            true: fn ($query) => $query->whereColumn('current_stock', '>', 'min_stock'),
            false: fn ($query) => $query->whereColumn('current_stock', '<=', 'min_stock'),
        )
        ->trueLabel('Stok Aman')
        ->falseLabel('Stok Menipis'),
    TernaryFilter::make('is_active'),
])
```

#### Filter di InboundsTable dan OutboundsTable
```php
->filters([
    SelectFilter::make('supplier_id')
        ->relationship('supplier', 'name')
        ->label('Supplier'),
    Filter::make('date_range')
        ->form([
            DatePicker::make('from')->label('Dari'),
            DatePicker::make('until')->label('Sampai'),
        ])
        ->query(function ($query, array $data) {
            return $query
                ->when($data['from'], fn ($q) => $q->whereDate('date', '>=', $data['from']))
                ->when($data['until'], fn ($q) => $q->whereDate('date', '<=', $data['until']));
        }),
    SelectFilter::make('status')
        ->options([...]),
])
```

---

### 3.4 View Detail yang Informatif (Bukan Cuma Form Read-Only)

> **Perspektif Pemilik Toko:**
> "Kalau saya klik satu transaksi, saya mau lihat SEMUA informasinya di satu tempat — header, detail item, total, siapa yang input, kapan."

#### View Inbound/Outbound — Tampilan Invoice-Like
Buat custom view yang menampilkan data seperti invoice/nota:
```
┌─────────────────────────────────────────────────────────────┐
│  NAMA TOKO (dari Pengaturan Toko)                            │
│  Jl. xxxxxxx                                                 │
├─────────────────────────────────────────────────────────────┤
│  NOTA BARANG MASUK                                           │
│  No: IN-2026-0625-0001           Tanggal: 25 Juni 2026      │
│  Supplier: PT Eterna             Surat Jalan: SJ-123456     │
│  Status: ✅ Diterima                                         │
├─────────────────────────────────────────────────────────────┤
│  No │ Kode   │ Nama Barang          │ Qty │ Harga    │ Total │
│   1 │ ITM-23 │ Kabel NYM 2x1.5 50m │  10 │ 250.000  │ 2.5jt │
│   2 │ ITM-45 │ Saklar Panasonic     │  50 │  35.000  │ 1.75jt│
├─────────────────────────────────────────────────────────────┤
│  TOTAL: Rp 4.250.000                                        │
│  Dicatat oleh: Admin         Diterima oleh: Budi            │
└─────────────────────────────────────────────────────────────┘
```

---

### 3.5 Activity Log

> **Perspektif Pemilik Toko:**
> "Walaupun cuma saya yang pakai, saya tetap mau tahu riwayat aktivitas. Kapan saya terakhir input barang, kapan saya edit harga, dll. Ini berguna untuk melacak kesalahan."

#### Rekomendasi: spatie/laravel-activitylog
```php
// Di setiap Model yang penting:
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Item extends Model
{
    use LogsActivity;
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'harga_beli', 'harga_jual_retail', 'harga_jual_grosir', 'current_stock'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Item {$eventName}");
    }
}
```

#### Widget Dashboard: "Aktivitas Terakhir"
Tampilkan 10 aktivitas terakhir di dashboard.

---

## 🏗️ FASE 4 — Fitur Lanjutan (Nice-to-Have)

### 4.1 Purchase Order (PO)

> **Perspektif Pemilik Toko:**
> "Kadang saya mau pesan barang ke supplier tapi belum dikirim. Saya catat dulu sebagai 'pesanan', nanti kalau barang sudah datang baru jadi Inbound. Ini yang namanya Purchase Order."

#### Alur PO → Inbound
```
1. Buat PO → Status: Draft
2. Kirim PO ke supplier → Status: Sent  
3. Supplier konfirmasi → Status: Confirmed
4. Barang datang → Convert PO to Inbound (auto-fill items)
5. PO Status: Received (Completed)
```

Ini bisa diimplementasikan sebagai Filament Resource `PurchaseOrderResource` yang terpisah, dengan action "Convert to Inbound".

---

### 4.2 Inventory Valuation (Penilaian Persediaan)

> **Perspektif Pemilik Toko:**
> "Di akhir tahun, saya perlu tahu total nilai persediaan (aset) saya. Berapa nilai total semua barang di gudang kalau dihitung berdasarkan harga beli?"

#### Metode: Weighted Average Cost (WAC)
```
Nilai Persediaan = Σ (current_stock × weighted_average_cost) untuk setiap item

WAC per item = Total Nilai Pembelian / Total Quantity Pembelian
```

#### Widget Dashboard
```
┌──────────────────────────────────┐
│ Total Nilai Persediaan           │
│ Rp 1.234.567.890                 │
│ (2,456 jenis barang)             │
└──────────────────────────────────┘
```

---

### 4.3 Multi-Location / Gudang

> **Perspektif Pemilik Toko:**
> "Saya punya toko depan dan gudang belakang. Nanti kalau berkembang, mungkin buka cabang. Sistemnya harus bisa handle banyak lokasi."

#### Database: Tabel `locations`
```
| Kolom       | Tipe           | Constraint     | Keterangan                |
|-------------|----------------|----------------|---------------------------|
| id          | BIGINT UNSIGNED| PK             |                           |
| name        | VARCHAR(100)   | NOT NULL       | Toko Utama, Gudang, dll   |
| type        | ENUM           | NOT NULL       | 'store', 'warehouse'      |
| address     | TEXT           | NULLABLE       |                           |
| is_active   | BOOLEAN        | DEFAULT true   |                           |
```

#### Dampak ke `items` dan transaksi
- Stok per lokasi: tabel pivot `item_location` dengan `quantity` per lokasi
- Transfer antar lokasi: tabel `stock_transfers`

> [!TIP]
> Fase ini hanya diimplementasikan jika bisnis sudah berkembang. Untuk saat ini, `rack_location` di tabel `items` sudah cukup.

---

### 4.4 Cetak Barcode / Label

> **Perspektif Pemilik Toko:**
> "Saya mau bisa print label barcode + harga dari sistem, terus tempel di rak. Jadi kalau scan barcode dari rak, langsung muncul di sistem."

#### Implementasi
- Buat action "Print Label" di ItemsTable (single dan bulk)
- Generate barcode image menggunakan `milon/barcode` atau `picqer/php-barcode-generator`
- Format label:
```
┌─────────────────────┐
│ Kabel Eterna NYM    │
│ 2x1.5mm 50m         │
│ ||||||||||||||||     │  ← barcode
│ 8991234567890        │
│ Rp 350.000           │
└─────────────────────┘
```

---

## 📐 PERBAIKAN TEKNIS (Code Quality)

### T.1 Database Transaction Wrapping

> **Masalah saat ini:** Stock update dan StockMovement create tidak dibungkus dalam DB transaction.

```php
// SEBELUM (rawan race condition):
static::created(function ($model) {
    $item = $model->item;
    $item->current_stock += $model->quantity;
    $item->save();
    StockMovement::create([...]);
});

// SESUDAH (aman):
static::created(function ($model) {
    DB::transaction(function () use ($model) {
        $item = Item::lockForUpdate()->find($model->item_id);
        $stockBefore = $item->current_stock;
        $item->current_stock += $model->quantity;
        $item->save();
        
        StockMovement::create([
            'stock_before' => $stockBefore,
            'stock_after' => $item->current_stock,
            // ...
        ]);
    });
});
```

---

### T.2 Enum / Constants — Jangan Hardcode String

```php
// Buat enums:
namespace App\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case RECEIVED = 'diterima';
    case COMPLETED = 'selesai';
    case CANCELLED = 'dibatalkan';
    case VOIDED = 'voided';
}

enum MovementType: string
{
    case INBOUND = 'inbound';
    case OUTBOUND = 'outbound';
    case RETURN_IN = 'return_in';
    case RETURN_OUT = 'return_out';
    case ADJUSTMENT = 'adjustment';
    case INITIAL = 'initial';
    case VOID = 'void';
}

enum ReturnType: string
{
    case SUPPLIER = 'supplier';
    case CUSTOMER = 'customer';
}

enum ItemCondition: string
{
    case GOOD = 'baik';
    case DAMAGED = 'rusak';
    case DEFECTIVE = 'cacat';
}

enum PaymentStatus: string
{
    case UNPAID = 'unpaid';
    case PARTIAL = 'partial';
    case PAID = 'paid';
}
```

---

### T.3 Form Formatting — Consistent Money Formatting

```php
// Buat helper reusable:
class FormHelper
{
    public static function moneyInput(string $field): TextInput
    {
        return TextInput::make($field)
            ->numeric()
            ->prefix('Rp')
            ->mask(RawJs::make('$money($input)'))
            ->stripCharacters(',')
            ->default(0);
    }
}

// Penggunaan:
FormHelper::moneyInput('harga_beli')->required(),
FormHelper::moneyInput('harga_jual_retail')->required(),
```

---

### T.4 Model Formatting — Consistent Code Structure

> **Masalah saat ini:** Beberapa model ditulis satu baris (minified), beberapa multi-line. Tidak konsisten.

**Standarisasi:** Semua model harus multi-line, well-formatted, dengan:
1. `$guarded` atau `$fillable` yang eksplisit
2. `$casts` untuk date, decimal, boolean, enum
3. Relationship methods yang terorganisir
4. Business logic di Service class, bukan di Model boot

```php
class Item extends Model
{
    use SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'harga_beli' => 'decimal:2',
        'harga_jual_retail' => 'decimal:2',
        'harga_jual_grosir' => 'decimal:2',
        'min_grosir_qty' => 'integer',
        'current_stock' => 'integer',
        'min_stock' => 'integer',
        'is_active' => 'boolean',
    ];

    // ======== Relationships ========
    public function brand() { return $this->belongsTo(Brand::class); }
    public function category() { return $this->belongsTo(Category::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
    // ... dst

    // ======== Scopes ========
    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeLowStock($query) { 
        return $query->whereColumn('current_stock', '<=', 'min_stock')->where('min_stock', '>', 0); 
    }
    public function scopeDeadStock($query, int $days = 90) { ... }

    // ======== Accessors ========
    public function getIsLowStockAttribute(): bool { ... }
    public function getStockValueAttribute(): float { ... }
}
```

---

## 📋 PRIORITAS IMPLEMENTASI (Roadmap)

### Sprint 1 (Minggu 1-2) — Fondasi Kritis
- [ ] 1.5 Transaction Locking & Void ← **paling penting, data integrity**
- [ ] 1.6 Validasi stok negatif
- [ ] 1.7 Transaction code sequential
- [ ] T.1 DB Transaction wrapping
- [ ] T.2 Enum constants

### Sprint 2 (Minggu 3-4) — Data Model Enhancement
- [ ] 1.1 Tambah Brand/Merk
- [ ] 1.2 Retur 2 arah (supplier + customer)
- [ ] 1.3 Surat Jalan & Invoice di Inbound
- [ ] 2.1 Sub-Kategori

### Sprint 3 (Minggu 5-6) — Operasional
- [ ] 1.4 Konversi Satuan
- [ ] 2.2 Item-Supplier relationship enrichment
- [ ] 2.3 Diskon di Outbound
- [ ] 2.6 Total COGS auto-calculate

### Sprint 4 (Minggu 7-8) — Reporting & UX
- [ ] 2.4 Kartu Stok (Stock Card)
- [ ] 2.5 Export & Print laporan (PDF + Excel)
- [ ] 3.1 Dashboard data real
- [ ] 3.3 Search & Filter enhancement

### Sprint 5 (Minggu 9-10) — Polish
- [ ] 3.2 Notification system
- [ ] 3.4 View detail invoice-like
- [ ] 3.5 Activity Log
- [ ] T.3-T.4 Code quality standardization

### Backlog (Setelah Launch)
- [ ] 4.1 Purchase Order
- [ ] 4.2 Inventory Valuation
- [ ] 4.3 Multi-location
- [ ] 4.4 Cetak barcode/label

---

## 🔑 Prinsip Utama yang Harus Dipegang

> [!CAUTION]
> ### Jangan Pernah Melanggar Ini
> 1. **Stok TIDAK BOLEH negatif** — validasi di level form DAN model
> 2. **Transaksi selesai TIDAK BOLEH bisa diedit** — hanya void
> 3. **Setiap mutasi stok HARUS tercatat** di `stock_movements` — no exception
> 4. **Harga di transaksi HARUS frozen** — jangan reference ke master
> 5. **DB Transaction** untuk setiap operasi yang melibatkan update stok + create movement

> [!TIP]
> ### Mindset Pemilik Toko
> Setiap kali akan menambah fitur, tanyakan:
> - "Apakah pemilik toko benar-benar butuh ini SEHARI-HARI?"
> - "Apakah ini mempercepat kerja atau justru memperumit?"
> - "Apakah tanpa fitur ini, data bisa kacau?"
> 
> Jika jawabannya "tidak", tunda ke backlog.

---

*Dokumen ini dibuat berdasarkan analisis mendalam terhadap codebase project `stock-opname` dan best practice standar industri retail/grosir inventory management system.*
