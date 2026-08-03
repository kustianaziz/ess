# 📖 PANDUAN PENGGUNAAN APLIKASI EDU EMPLOYEE SELF SERVICE (ESS) v2.0

Selamat datang di Panduan Penggunaan Lengkap Aplikasi **EDU Employee Self Service (ESS)**. Dokumen ini disusun secara terperinci untuk memandu seluruh pengguna dari berbagai level (Karyawan, Atasan/Manager Level 1, HRD & Keuangan Level 2, serta System Administrator) dalam mengoperasikan sistem ESS mulai dari login hingga selesai.

---

## 📑 DAFTAR ISI
1. [Ringkasan Akses & Akun Uji Coba](#1-ringkasan-akses--akun-uji-coba)
2. [Modul Autentikasi & Login Sistem](#2-modul-autentikasi--login-sistem)
3. [Fitur Sistem Notifikasi Real-Time](#3-fitur-sistem-notifikasi-real-time)
4. [Panduan Level 1: Karyawan (Pemohon)](#4-panduan-level-1-karyawan-pemohon)
   - 4.1. Membuat Pengajuan Reimbursement
   - 4.2. Membuat Pengajuan Konsumsi / Operasional
   - 4.3. Membuat Pengajuan Cuti
   - 4.4. Mengelola Riwayat & Menghapus Pengajuan
5. [Panduan Level 2: Atasan Langsung / Manager (Persetujuan Level 1)](#5-panduan-level-2-atasan-langsung--manager-persetujuan-level-1)
   - 5.1. Memproses Antrean Persetujuan Level 1
   - 5.2. Membatalkan Persetujuan Level 1 (Batal Approve)
6. [Panduan Level 3: HRD & Keuangan (Persetujuan Level 2 & Pembayaran)](#6-panduan-level-3-hrd--keuangan-persetujuan-level-2--pembayaran)
   - 6.1. Memproses Persetujuan Final Level 2
   - 6.2. Memproses Pencairan / Pembayaran
   - 6.3. Membatalkan Persetujuan Level 2 (Batal Approve & Refund Cuti)
7. [Panduan Level 4: System Administrator (Admin Panel & Laporan)](#7-panduan-level-4-system-administrator-admin-panel--laporan)
   - 7.1. Pengelolaan Pengguna & Divisi
   - 7.2. Pengelolaan Master Data Pengeluaran, Kegiatan, & Cuti
   - 7.3. Fitur Laporan & Export Excel / Word
8. [Tabel Ringkasan Alur Status Transaksi](#8-tabel-ringkasan-alur-status-transaksi)

---

## 1. RINGKASAN AKSES & AKUN UJI COBA

Aplikasi dapat diakses melalui peramban (browser) di alamat: **`http://localhost:8000`**

### Kredensial Pengguna Bawaan (Default Password: `password`)
| Level / Peran | Nama Lengkap | Username | Level Persetujuan |
| :--- | :--- | :--- | :--- |
| **Karyawan** | Kustiani Abdul Aziz | `kustian` | Pemohon Pengajuan |
| **Karyawan** | Saca Sunantara | `saca` | Pemohon Pengajuan |
| **Karyawan** | Mutiara Nanda P | `muti` | Pemohon Pengajuan |
| **Atasan (Direktur)** | Ucu Komarudin | `ucu` | Penyetuju Level 1 (Atasan Langsung) |
| **HRD & Keuangan** | Ai Siti Nuralisah | `aisiti` | Penyetuju Level 2 & Eksekutor Pembayaran |
| **HRD & Keuangan** | Fourizal Noviansyah | `opi` | Penyetuju Level 2 & Eksekutor Pembayaran |
| **System Admin** | System Administrator | `admin@edu.id` | Pengelola Master Data & Hak Akses Full |

---

## 2. MODUL AUTENTIKASI & LOGIN SISTEM

### Langkah-langkah Login:
1. Buka browser (Google Chrome / Mozilla Firefox / Microsoft Edge / HP Browser).
2. Akses URL `http://localhost:8000`.
3. Pada halaman **`Masuk - Portal ESS EDU`**, Anda akan melihat dua tampilan responsif:
   - **Tampilan Desktop (PC/Laptop)**: Menampilkan panel informasi fitur di sisi kiri dan formulir login di sisi kanan.
   - **Tampilan Mobile (HP)**: Langsung fokus ke formulir login tanpa perlu scrolling (*To The Point*).
4. Isi kolom **`Username / Email Karyawan`** (misal: `kustian`, `ucu`, `aisiti`, atau `admin@edu.id`).
5. Isi kolom **`Kata Sandi`** dengan password akun Anda (default: `password`).
6. (Opsional) Centang opsi **`Ingat Saya di Perangkat Ini`** untuk menyimpan sesi.
7. Klik tombol **`Masuk ke System ESS`**.
8. Setelah berhasil terautentikasi, sistem akan mengarahkan Anda ke **Dashboard Beranda**.

---

## 3. FITUR SISTEM NOTIFIKASI REAL-TIME

Aplikasi ESS dilengkapi dengan 3 mekanisme notifikasi terpadu:

1. **Badge Angka Merah di Topbar Navigasi**:
   - Terletak pada ikon Lonceng (**`Notifikasi`**) di baris atas (*Topbar*).
   - Menampilkan jumlah notifikasi yang belum dibaca secara *live*.
2. **Dropdown Menu Popover Notifikasi**:
   - Mengklik ikon lonceng akan membuka kotak **`Notifikasi Terkini`**.
   - Menampilkan daftar pesan pengajuan baru atau pembaruan status.
   - Tersedia tombol **`Tandai dibaca`** dan link **`Lihat Semua Notifikasi →`**.
3. **Notifikasi Desktop Browser (HTML5 Web Notification API)**:
   - Saat pertama kali login, browser akan meminta izin notifikasi desktop.
   - Sistem melakukan *polling* otomatis di latar belakang (setiap 6 detik).
   - Jika terdapat transaksi pengajuan atau approval baru, banner notifikasi desktop bawaan OS/Browser akan muncul secara otomatis walaupun Anda sedang membuka tab lain.
   - Mengklik banner notifikasi desktop akan langsung mengarahkan Anda ke detail transaksi terkait.

---

## 4. PANDUAN LEVEL 1: KARYAWAN (PEMOHON)

Sebagai Karyawan, Anda memiliki hak akses untuk membuat pengajuan klaim biaya, biaya operasional, dan izin cuti, serta memantau status persetujuan.

### 4.1. Membuat Pengajuan Reimbursement
1. Klik menu **`PENGAJUAN`** -> **`Reimbursement`** pada Sidebar sebelah kiri.
2. **Langkah 1: Informasi Pengajuan**:
   - Informasi Pengaju (Nama, NIK, Divisi, Jabatan) terisi secara otomatis oleh sistem.
   - Pilih **`Tanggal Pengeluaran`** (Tanggal saat transaksi terjadi).
   - Pilih **`Jenis Pengeluaran`** (Misal: *Transportasi & Bensin*, *Konsumsi Klien*, *Alat Tulis Kantor*).
   - Masukkan **`Nominal`**. *(Sistem secara otomatis mengubah format angka menjadi Rupiah `Rp 150.000` saat Anda mengetik)*.
   - Masukkan **`Keterangan`** rincian pengeluaran.
   - Klik tombol **`Selanjutnya`** (Atau tombol **`Simpan Draft`** jika ingin menyimpan sebagai draf terlebih dahulu).
3. **Langkah 2: Upload Bukti Transaksi**:
   - Unggah foto struk/nota/kwitansi (Format `.jpg`, `.jpeg`, `.png`, atau `.pdf`, maks 5MB).
   - Klik tombol **`Selanjutnya`**.
4. **Langkah 3: Review & Kirim**:
   - Periksa kembali ringkasan pengajuan Reimbursement.
   - Klik tombol **`Kirim Pengajuan`**.
5. Sistem akan mengirim notifikasi ke Atasan Langsung (Manager Level 1) untuk diverifikasi.

### 4.2. Membuat Pengajuan Konsumsi / Operasional
1. Klik menu **`PENGAJUAN`** -> **`Konsumsi / Operasional`** pada Sidebar.
2. **Langkah 1: Informasi Kegiatan**:
   - Pilih **`Tanggal Kegiatan`** & **`Jenis Kegiatan`** (Misal: *Rapat Internal*, *Pelatihan*, *Operational Support*).
   - Masukkan **`Nama Kegiatan`** & **`Tujuan / Keterangan`**.
   - Masukkan **`Jumlah Peserta`** (Orang) & **`Estimasi Biaya`** *(Otomatis terformat Rupiah)*.
   - Masukkan **`Lokasi`** kegiatan.
   - Klik **`Selanjutnya`**.
3. **Langkah 2: Upload Dokumen Pendukung**:
   - Unggah berkas proposal / nota dinas pendukung.
   - Klik **`Selanjutnya`**.
4. **Langkah 3: Review & Kirim**:
   - Cek ringkasan dan klik **`Kirim Pengajuan`**.

### 4.3. Membuat Pengajuan Cuti
1. Klik menu **`PENGAJUAN`** -> **`Cuti`** pada Sidebar.
2. **Langkah 1: Detail Cuti**:
   - Pilih **`Jenis Cuti`** (Misal: *Cuti Tahunan*, *Cuti Sakit*, *Cuti Melahirkan*).
   - Pilih **`Tanggal Mulai`** dan **`Tanggal Selesai`**. *(Sistem menghitung total hari kerja secara otomatis)*.
   - Pilih **`Serah Terima Pekerjaan Kepada`** (Pilih rekan kerja pengganti sementara).
   - Masukkan **`Catatan Serah Terima`** & **`Alasan Cuti`**.
   - Klik **`Selanjutnya`**.
3. **Langkah 2: Upload Surat / Lampiran**:
   - Unggah surat keterangan dokter / lampiran pendukung (wajib untuk Cuti Sakit).
4. **Langkah 3: Review & Kirim**:
   - Cek ringkasan dan klik **`Kirim Pengajuan`**.

### 4.4. Mengelola Riwayat & Menghapus Pengajuan
1. Buka menu **`RIWAYAT & STATUS`** -> **`Riwayat Pengajuan`**.
2. Anda dapat memfilter riwayat berdasarkan kolom pencarian No. Pengajuan, Jenis Layanan, atau Status (`Menunggu Persetujuan`, `Disetujui`, `Ditolak`, `Sudah Dibayarkan`).
3. Klik tombol **`Detail`** untuk melihat progres rincian dan timeline approval.
4. **Menghapus Pengajuan (Belum Disetujui)**:
   - Apabila pengajuan masih berstatus pending (**`Menunggu Persetujuan`** / **`Draft`**), akan muncul tombol **`Hapus`** dengan ikon tempat sampah warna merah.
   - Klik tombol **`Hapus`**, lalu pada modal **`Konfirmasi Hapus Pengajuan`**, klik **`Ya, Hapus Pengajuan`**.
   - *(Catatan: Pengajuan yang sudah disetujui / dibayarkan tidak dapat dihapus)*.

---

## 5. PANDUAN LEVEL 2: ATASAN LANGSUNG / MANAGER (PERSETUJUAN LEVEL 1)

Sebagai Atasan Langsung (Level 1), Anda bertanggung jawab memeriksa dan menyetujui pengajuan yang diajukan oleh anggota tim/divisi Anda.

### 5.1. Memproses Antrean Persetujuan Level 1
1. Login menggunakan akun Manager (misal: `ucu`).
2. Buka menu **`RIWAYAT & STATUS`** -> **`Persetujuan Saya`** (URL: `http://localhost:8000/approval`).
3. Pada tabel **`Daftar Persetujuan (Approval)`**, Anda melihat pengajuan yang membutuhkan keputusan Anda (Status: `Level 1`).
4. **Menyetujui Pengajuan**:
   - Klik tombol hijau **`Setujui`**.
   - Pada modal **`Konfirmasi Persetujuan`**, isi *Catatan Approval* (Opsional).
   - Klik **`Ya, Setujui`**.
   - Status pengajuan berubah menjadi disetujui Level 1, dan antrean otomatis diteruskan ke HRD & Keuangan (Level 2).
5. **Menolak Pengajuan**:
   - Klik tombol merah **`Tolak`**.
   - Pada modal **`Konfirmasi Penolakan`**, isi **`Alasan Penolakan`** (Wajib).
   - Klik **`Ya, Tolak`**.
   - Status pengajuan berubah menjadi `DITOLAK` dan pemohon menerima notifikasi penolakan.

### 5.2. Membatalkan Persetujuan Level 1 (Batal Approve)
Jika Anda salah menyetujui dan transaksi tersebut belum dibayarkan oleh Keuangan:
1. Buka menu **`RIWAYAT & STATUS`** -> **`Riwayat Persetujuan`**.
2. Cari transaksi yang pernah Anda setujui.
3. Klik tombol kuning **`Batal Approve`** (Ikon panah putar `Undo`).
4. Pada modal **`Batalkan Persetujuan (Unapprove)`**, klik **`Ya, Batalkan Approve`**.
5. Antrean Level 2 di HRD akan ditarik kembali dan status pengajuan dikembalikan ke antrean Level 1 Anda.

---

## 6. PANDUAN LEVEL 3: HRD & KEUANGAN (PERSETUJUAN LEVEL 2 & PEMBAYARAN)

Sebagai HRD & Keuangan (Level 2), Anda bertindak sebagai verifikator akhir serta pelaksana pencairan/pembayaran transaksi.

### 6.1. Memproses Persetujuan Final Level 2
1. Login menggunakan akun HRD & Keuangan (misal: `aisiti` atau `opi`).
2. Buka menu **`Persetujuan Saya`** (`http://localhost:8000/approval`).
3. Pada daftar antrean, pilih transaksi berstatus `Level 2`.
4. Klik tombol **`Setujui`**, isi catatan, lalu klik **`Ya, Setujui`**.
5. Status transaksi berubah menjadi **`DISETUJUI` (Final Approved)**. Jika transaksi tersebut adalah **Cuti**, sistem secara otomatis mengurai dan memotong saldo sisa cuti pemohon.

### 6.2. Memproses Pencairan / Pembayaran (Khusus Reimbursement & Operasional)
1. Setelah transaksi disetujui Level 2, masuk ke halaman detail transaksi atau halaman pencairan.
2. Masukkan **`Nomor Referensi Pembayaran / Transfer`** (misal: `TRX-BCA-981237`).
3. Klik tombol **`Proses Pembayaran`**.
4. Status pengajuan berubah menjadi **`SUDAH DIBAYARKAN` (`PAID`)**, dan notifikasi pembayaran otomatis terkirim ke karyawan.

### 6.3. Membatalkan Persetujuan Level 2 (Batal Approve & Refund Cuti)
1. Buka menu **`Riwayat Persetujuan`**.
2. Pilih transaksi yang disetujui Level 2 yang ingin dibatalkan.
3. Klik tombol **`Batal Approve`**.
4. Klik **`Ya, Batalkan Approve`**.
5. Status pengajuan kembali ke `SUBMITTED`. Jika pengajuan adalah **Cuti**, sistem secara otomatis memulihkan (*refund*) sisa kuota cuti karyawan yang terpotong sebelumnya.

---

## 7. PANDUAN LEVEL 4: SYSTEM ADMINISTRATOR (ADMIN PANEL & LAPORAN)

Sebagai Administrator (`admin@edu.id`), Anda memiliki wewenang penuh dalam mengelola master data perusahaan dan mengunduh laporan eksekutif.

### 7.1. Pengelolaan Pengguna & Divisi
1. **Kelola Pengguna** (`ADMIN PANEL` -> `Kelola Pengguna`):
   - Menambah user baru, mengatur NIK, Divisi, Jabatan, Username, Email, Password, serta Peran Role (`admin`, `hrd_finance`, `manager`, `employee`).
   - Menentukan relasi **Atasan Langsung (Manager ID)** untuk setiap karyawan.
2. **Kelola Divisi** (`ADMIN PANEL` -> `Kelola Divisi`):
   - Menambah, mengubah, atau menghapus struktur divisi perusahaan (misal: IT, HRD, Marketing, Finance).

### 7.2. Pengelolaan Master Data Pengeluaran, Kegiatan, & Cuti
- **Jenis Pengeluaran**: Mengatur kategori reimbursement (Transportasi, Medis, ATK, dll).
- **Jenis Kegiatan**: Mengatur kategori operasional (Rapat, Pelatihan, Client Entertainment, dll).
- **Jenis Cuti**: Mengatur master jenis cuti beserta jatah hari standar.
- **Kuota Cuti Karyawan**: Mengatur saldo sisa cuti tahunan untuk setiap karyawan secara individu.

### 7.3. Fitur Laporan & Export Excel / Word
Modul ini dapat diakses oleh Admin & HRD Keuangan via menu **`Laporan & Rekap`** (`/admin/reports`).

1. **Filter Laporan**:
   - Filter berdasarkan **Rentang Tanggal**, **Jenis Layanan** (Reimbursement, Operasional, Cuti), **Divisi**, dan **Status**.
2. **Dua Mode Tampilan**:
   - **Tabel Rekap Agregasi**: Menampilkan ringkasan total pengajuan, total nominal disetujui, serta rekapitulasi per divisi.
   - **Daftar Rincian Pengajuan**: Menampilkan tabel detail transaksi secara lengkap.
3. **Ekspor Laporan Resmi**:
   - **Tombol `Export Excel`**: Mengunduh file laporan format spreadsheet `.xls` untuk analisis data angka.
   - **Tombol `Export Word`**: Mengunduh dokumen laporan format `.doc` yang sudah terformat rapi lengkap dengan **Kop Surat Resmi Perusahaan**, **Ringkasan Eksekutif**, **Tabel Rekap**, serta **Blok Kolom Tanda Tangan Resmi** (Dibuat oleh Admin/HRD & Disetujui oleh Direktur).

---

## 8. TABEL RINGKASAN ALUR STATUS TRANSAKSI

```
[ KARYAWAN ]            [ ATASAN L1 ]            [ HRD & FINANCE L2 ]
Draft Pengajuan  ---> Submit Pengajuan
                           |
                     (Verifikasi L1)
                           |---> Ditolak (Selesai - Status: DITOLAK)
                           |
                           v
                     Disetujui L1  --->  (Verifikasi L2)
                                               |---> Ditolak (Selesai - Status: DITOLAK)
                                               |
                                               v
                                         Disetujui L2 (Final - Status: DISETUJUI)
                                               |
                                         (Proses Transfer / Pencairan)
                                               |
                                               v
                                         Selesai (Status: SUDAH DIBAYARKAN)
```

---

*© 2026 EDU Employee Self Service System v2.0. All Rights Reserved.*
