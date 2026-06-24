# Dokumentasi Dashboard Stock Opname

Dokumen ini berisi penjelasan lengkap mengenai fitur dan komponen yang terdapat pada halaman Dashboard aplikasi Stock Opname. Dokumentasi ini dapat digunakan sebagai panduan untuk memahami alur data dan menjelaskan fungsionalitas kepada klien.

## 1. Fitur Utama: Filter Global (Filter Periode Data)

Halaman Dashboard memiliki fitur **Filter Global** yang terletak di bagian atas. Filter ini digunakan untuk mengubah rentang waktu data yang ditampilkan pada hampir seluruh widget di dashboard.

- **Pilihan Periode:**
  - `Hari Ini` (Today)
  - `7 Hari Terakhir` (Last 7 Days)
  - `30 Hari Terakhir` (Last 30 Days) - **(Default)**
  - `Bulan Ini` (This Month)
  - `Tahun Ini` (This Year)

*Catatan Teknis:* Filter ini diimplementasikan melalui _trait_ `HasFiltersForm` pada file `app/Filament/Pages/Dashboard.php` dan terhubung ke widget melalui _trait_ `InteractsWithPageFilters`.

---

## 2. Ringkasan Widget (Komponen Dashboard)

Dashboard ini terdiri dari 6 komponen (widget) utama yang disusun berdasarkan tingkat kepentingannya (sorting).

### A. Stats Overview (Ringkasan Statistik)
**Lokasi File:** `app/Filament/Widgets/StatsOverviewWidget.php`
**Posisi:** Paling Atas (Baris 1)

Widget ini menampilkan 4 kartu indikator utama (Key Performance Indicators) secara *real-time* (diperbarui otomatis setiap 30 detik).

1. **Penjualan Hari Ini**
   - Menampilkan total nominal transaksi penjualan (`outbounds`) dengan status 'selesai' pada hari ini.
   - Dilengkapi dengan tren persentase kenaikan/penurunan dibandingkan dengan penjualan kemarin.
2. **Laba Kotor Hari Ini**
   - Menampilkan total laba kotor dari barang yang terjual hari ini (Harga Jual - Modal).
   - Dilengkapi dengan tren persentase kenaikan/penurunan dibandingkan dengan laba kemarin.
3. **Barang Masuk Hari Ini**
   - Menampilkan total nilai barang masuk (`inbounds`) dengan status 'diterima' pada hari ini.
4. **Alert Stok Menipis**
   - Menampilkan jumlah jenis barang yang stok saat ini (`current_stock`) sudah menyentuh atau berada di bawah batas minimum (`min_stock`).
   - Warna indikator akan berubah menjadi merah (bahaya) jika ada barang yang menipis.

*(Catatan: Widget ini fokus pada data performa "Hari Ini" dan tidak terpengaruh oleh Filter Periode di atas).*

---

### B. Grafik Trend Penjualan & Laba
**Lokasi File:** `app/Filament/Widgets/SalesAndProfitChart.php`
**Posisi:** Baris 2 (Kiri - Tengah)

Widget ini berupa grafik garis (Line Chart) yang membandingkan pergerakan Penjualan dan Laba Kotor.

- **Data yang Ditampilkan:**
  - Garis Kuning (Total Penjualan): Total nominal transaksi penjualan per hari.
  - Garis Hijau (Laba Kotor): Total keuntungan yang didapatkan per hari.
- **Responsif terhadap Filter:** Ya. Grafik ini akan menyesuaikan rentang X-axis (tanggal) berdasarkan pilihan Filter Periode Data.

---

### C. Grafik Penjualan per Kategori
**Lokasi File:** `app/Filament/Widgets/SalesByCategoryChart.php`
**Posisi:** Baris 2 (Kanan)

Widget ini berupa grafik donat (Doughnut Chart) yang menunjukkan proporsi penjualan berdasarkan kategori barang.

- **Data yang Ditampilkan:** Menampilkan 5 kategori dengan penjualan tertinggi. Kategori lainnya akan digabungkan menjadi satu bagian bernama "Lainnya".
- **Responsif terhadap Filter:** Ya. Data akan diakumulasikan berdasarkan rentang tanggal pada Filter Periode Data.

---

### D. Tabel Top 10 Barang Terlaris
**Lokasi File:** `app/Filament/Widgets/TopSellingItemsTable.php`
**Posisi:** Baris 3 (Lebar Penuh)

Tabel ini menampilkan daftar 10 barang paling laku terjual. Sangat berguna bagi klien untuk mengetahui produk mana yang menjadi *best-seller*.

- **Kolom Tabel:**
  - **Nama Barang:** Nama produk.
  - **Qty (Kuantitas):** Total jumlah unit yang terjual (berwarna hijau).
  - **Revenue (Pendapatan):** Total uang yang dihasilkan dari penjualan barang tersebut.
  - **Laba:** Total keuntungan bersih dari penjualan barang tersebut.
- **Urutan:** Diurutkan secara menurun (descending) berdasarkan Qty (Kuantitas) terbanyak.
- **Responsif terhadap Filter:** Ya. Data akan diakumulasikan berdasarkan rentang tanggal pada Filter Periode Data.

---

### E. Laporan Laba Rugi (Profit & Loss Overview)
**Lokasi File:** `app/Filament/Widgets/ProfitLossOverview.php`
**Posisi:** Baris 4 (Lebar Penuh)

Ini adalah laporan ringkasan finansial yang sangat penting bagi manajemen. Disajikan dalam bentuk rincian akuntansi sederhana.

- **Rincian Data:**
  1. **Pendapatan (Penjualan):** Total seluruh nilai transaksi penjualan yang berstatus 'selesai'.
  2. **HPP (Modal Terjual):** Harga Pokok Penjualan (Total Modal * Kuantitas Terjual). Ini adalah pengurang (-).
  3. **Laba Kotor:** Hasil pengurangan Pendapatan dengan HPP (Pendapatan - HPP).
  4. **Retur Pembelian/Penjualan:** Total nilai barang yang diretur (dikembalikan). Ini adalah pengurang (-).
  5. **Laba Bersih (Estimasi):** Hasil akhir dari Laba Kotor dikurangi nilai Retur.
- **Responsif terhadap Filter:** Ya. Laporan akan dihitung ulang berdasarkan rentang waktu yang dipilih pada Filter Periode Data.

---

### F. Tabel Barang Stok Menipis
**Lokasi File:** `app/Filament/Widgets/BottomStockTable.php`
**Posisi:** Tergabung di baris bawah (tergantung ukuran layar)

Tabel *early warning system* (sistem peringatan dini) untuk membantu klien melakukan restock barang tepat waktu.

- **Kondisi Data:** Hanya menampilkan barang yang nilai `current_stock` (Stok Saat Ini) lebih kecil atau sama dengan `min_stock` (Batas Minimum Stok).
- **Kolom Tabel:**
  - **Nama Barang**
  - **Stok:** Jumlah stok saat ini (berwarna merah jika habis/0, kuning jika hampir habis).
  - **Min:** Batas minimum stok yang disetel untuk barang tersebut.
  - **Kurang:** Selisih jumlah barang yang perlu dipesan ulang agar mencapai batas aman (Min - Stok).
- **Urutan:** Diurutkan berdasarkan barang yang paling "kritis" (selisih kekurangannya paling besar).
- *(Catatan: Widget ini menampilkan data real-time saat ini dan tidak terpengaruh oleh Filter Periode).*

---

## 3. Kesimpulan & Cara Menjelaskan ke Klien

Saat melakukan presentasi atau *handover* kepada klien, Anda dapat menggunakan alur berikut:

1. **Kenalkan Filter:** Mulailah dengan menunjukkan tombol **Filter Periode Data** di paling atas. Jelaskan bahwa mereka bisa melihat performa bisnis di masa lalu (seperti bulan lalu atau tahun ini) hanya dengan mengubah filter ini.
2. **Sorot Performa Harian:** Tunjukkan bagian **Ringkasan Statistik** (4 Kotak di atas). Beri tahu klien, "Ini adalah detak jantung bisnis Anda hari ini. Anda bisa langsung tahu berapa penjualan hari ini dibandingkan kemarin, dan apakah ada stok yang harus segera dibeli."
3. **Analisis Tren & Kategori:** Geser ke bagian grafik. Jelaskan bahwa grafik garis membantu mereka melihat apakah tren bisnis sedang naik atau turun, sementara grafik donat membantu mereka mengetahui "Kategori barang apa yang paling menghasilkan uang".
4. **Fokus pada Produk (Top 10):** Tunjukkan tabel Top 10 Barang Terlaris. "Ini adalah produk andalan Anda. Pastikan stok kesepuluh barang ini selalu tersedia karena mereka menyumbang penjualan terbesar."
5. **Kesehatan Finansial (Laba Rugi):** Terakhir, perlihatkan Laporan Laba Rugi. Jelaskan bahwa sistem secara otomatis menghitung berapa keuntungan nyata yang didapat (Laba Bersih) setelah dikurangi modal dan barang yang diretur/dikembalikan.
6. **Aksi Preventif:** Tunjukkan tabel Barang Stok Menipis sebagai alat bantu operasional harian untuk tim gudang.
