# Blueprint Sistem (Final) — EDU Employee Self Service (ESS)

Dokumen ini adalah blueprint teknis **final** untuk membangun sistem **Employee Self Service (ESS)** sesuai mockup yang diberikan, dengan 3 modul pengajuan utama:

1. **Reimbursement Karyawan**
2. **Konsumsi / Operasional**
3. **Cuti Karyawan**

**Stack final yang digunakan:**
- Backend: **Laravel 11.x**
- Frontend: **Inertia.js + Vue 3 + Tailwind CSS**
- Database: **MySQL 8.x**

Tidak ada lagi opsi/alternatif terbuka — seluruh keputusan teknis di dokumen ini bersifat final dan siap dieksekusi langsung oleh AI agentic (misal: Claude Code) sebagai acuan pengembangan end-to-end: arsitektur, database, alur bisnis, use case, API, hingga struktur folder.

---

## 1. Ringkasan Proyek

### 1.1 Deskripsi
Portal internal berbasis web yang memungkinkan karyawan mengajukan **reimbursement**, **konsumsi/operasional**, dan **cuti** secara digital, dengan alur persetujuan berjenjang (atasan → HRD/Finance), tracking status real-time, riwayat pengajuan, dan notifikasi.

### 1.2 Modul Utama (dari mockup)
- **Dashboard/Beranda**: kartu 3 layanan pengajuan + ringkasan status pengajuan pribadi (Menunggu Persetujuan, Disetujui, Ditolak, Sudah Dibayarkan, Selesai).
- **Pengajuan Reimbursement**: form 3 langkah (Informasi → Lampiran → Review & Kirim).
- **Pengajuan Konsumsi/Operasional**: form 3 langkah (Informasi → Lampiran → Review & Kirim).
- **Pengajuan Cuti**: form 3 langkah (Informasi → Lampiran Opsional → Review & Kirim).
- **Riwayat & Status**: Riwayat Pengajuan, Notifikasi.
- **Akun Saya**: Profil Saya, Keluar (logout).

### 1.3 Aktor / Role Pengguna
| Role | Deskripsi |
|---|---|
| **Karyawan (Employee)** | Membuat, mengedit draft, dan mengirim pengajuan; memantau status. |
| **Atasan Langsung (Manager/Approver L1)** | Approve/reject pengajuan bawahannya (level 1). |
| **HRD/Finance (Approver L2)** | Validasi akhir, approve/reject, dan memproses pembayaran (untuk reimbursement & konsumsi/operasional) atau approval cuti final. |
| **Admin/Superadmin** | Mengelola data master (divisi, jenis pengeluaran, jenis kegiatan, jenis cuti), mengelola user & role, mengatur kuota cuti. |

---

## 2. Tech Stack Rekomendasi

### 2.1 Backend
- **Laravel 11.x** (PHP 8.3+)
- **Laravel Sanctum** — autentikasi SPA (session-based cookie auth, cocok untuk Inertia).
- **Laravel Breeze (starter kit, Inertia stack)** — scaffolding auth cepat.
- **Spatie Laravel Permission** — role & permission management (employee, manager, hrd, admin).
- **Spatie Laravel Medialibrary** — manajemen file lampiran (bukti reimbursement, dokumen cuti, dll).
- **Laravel Notifications** — notifikasi in-app (database channel) + email.
- **Laravel Queue (database/redis driver)** — proses notifikasi & generate PDF/nomor pengajuan secara async.
- **Laravel Excel (maatwebsite/excel)** — export riwayat pengajuan ke Excel (opsional, untuk HRD/Finance).
- **DomPDF / Snappy** — generate bukti pengajuan / slip approval dalam PDF (opsional).

### 2.2 Frontend — Stack Final (Keputusan)
> **Laravel + Inertia.js + Vue 3 + Tailwind CSS**

**Alasan:**
- Mockup memiliki UI custom yang detail (multi-step form, stepper, card dashboard, modal) → butuh fleksibilitas komponen seperti SPA, tapi tetap 1 codebase dengan Laravel (tidak perlu REST API terpisah + auth token management yang rumit).
- Inertia menghilangkan kebutuhan membangun REST API penuh sekaligus menjaga pengalaman SPA (routing tanpa reload, state management ringan).
- Vue 3 (Composition API) + Tailwind CSS sangat cocok untuk membangun stepper form, komponen kartu, dan komponen reusable (Badge status, Modal, Stepper).
- Ekosistem component library pendukung: **Headless UI (Vue)**, **VueUse**, **Vue Toastification** (untuk notifikasi toast).

**Setup awal:**
```bash
composer create-project laravel/laravel edu-ess
cd edu-ess
composer require laravel/breeze --dev
php artisan breeze:install vue
npm install
```

### 2.3 Database — Final
- **MySQL 8.x**

### 2.4 Infrastruktur Pendukung
- **Redis** — cache & queue driver.
- **Laravel Horizon** — monitoring queue (opsional, untuk production).
- **Laravel Telescope** — debugging saat development.
- **Vite** — bundler frontend (default Laravel 11).

---

## 3. Arsitektur Sistem

```mermaid
flowchart TB
    subgraph Client["Browser (Vue 3 + Inertia)"]
        A1[Dashboard]
        A2[Form Pengajuan]
        A3[Riwayat & Notifikasi]
    end

    subgraph Laravel["Laravel App (Monolith + Inertia)"]
        B1[Controllers]
        B2[Form Requests / Validation]
        B3[Services / Actions Layer]
        B4[Models / Eloquent]
        B5[Policies - Authorization]
        B6[Notifications]
        B7[Jobs / Queue]
    end

    subgraph Storage["Storage"]
        C1[(MySQL 8.x)]
        C2[File Storage - Lampiran]
        C3[(Redis - Cache/Queue)]
    end

    Client <--> Laravel
    Laravel --> C1
    Laravel --> C2
    Laravel --> C3
```

### 3.1 Pola Desain
- **Service/Action Pattern**: logika bisnis (mis. proses approval, generate nomor pengajuan, hitung total hari cuti) dipisahkan dari Controller ke dalam class `Actions` atau `Services` agar mudah di-testing dan reusable.
- **Form Request Validation**: setiap form pengajuan punya `FormRequest` class sendiri (`StoreReimbursementRequest`, `StoreOperationalRequest`, `StoreLeaveRequest`).
- **Policy-based Authorization**: gunakan Laravel Policy untuk mengatur siapa yang boleh approve/reject/edit pengajuan tertentu.
- **Polymorphic Approval & Status History**: karena ada 3 jenis pengajuan dengan alur approval yang mirip, gunakan tabel `approvals` dan `status_histories` yang bersifat polymorphic agar tidak duplikasi logika.
- **Observer Pattern**: gunakan Model Observer untuk trigger notifikasi otomatis saat status pengajuan berubah.

---

## 4. Struktur Database

### 4.1 ERD (Entity Relationship Diagram)

```mermaid
erDiagram
    USERS ||--o{ REIMBURSEMENT_REQUESTS : mengajukan
    USERS ||--o{ OPERATIONAL_REQUESTS : mengajukan
    USERS ||--o{ LEAVE_REQUESTS : mengajukan
    USERS }o--|| DIVISIONS : anggota
    USERS }o--o{ ROLES : memiliki
    USERS ||--o{ LEAVE_BALANCES : memiliki
    USERS ||--o| USERS : "atasan_dari"

    EXPENSE_TYPES ||--o{ REIMBURSEMENT_REQUESTS : kategori
    ACTIVITY_TYPES ||--o{ OPERATIONAL_REQUESTS : kategori
    LEAVE_TYPES ||--o{ LEAVE_REQUESTS : kategori
    LEAVE_TYPES ||--o{ LEAVE_BALANCES : kategori

    REIMBURSEMENT_REQUESTS ||--o{ ATTACHMENTS : punya
    OPERATIONAL_REQUESTS ||--o{ ATTACHMENTS : punya
    LEAVE_REQUESTS ||--o{ ATTACHMENTS : punya

    REIMBURSEMENT_REQUESTS ||--o{ APPROVALS : diproses
    OPERATIONAL_REQUESTS ||--o{ APPROVALS : diproses
    LEAVE_REQUESTS ||--o{ APPROVALS : diproses

    REIMBURSEMENT_REQUESTS ||--o{ STATUS_HISTORIES : dicatat
    OPERATIONAL_REQUESTS ||--o{ STATUS_HISTORIES : dicatat
    LEAVE_REQUESTS ||--o{ STATUS_HISTORIES : dicatat

    USERS ||--o{ NOTIFICATIONS : menerima
```

### 4.2 Detail Tabel (Migration Blueprint)

> Konvensi: gunakan `ulid()` atau `id()` bigint auto-increment (rekomendasi: tetap `id()` bigint untuk performa relasi sederhana, tapi tambahkan `uuid`/`request_number` untuk referensi eksternal).

#### `users`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigIncrements | PK |
| nik | string, unique | Nomor Induk Karyawan (mis. `EDU-IT-001`) |
| name | string | Nama lengkap |
| email | string, unique | Email login |
| password | string | Hashed |
| avatar | string, nullable | Path foto profil |
| division_id | foreignId → divisions | Divisi |
| position | string | Jabatan (mis. "IT Support") |
| phone | string, nullable | |
| manager_id | foreignId → users, nullable | Atasan langsung (self-reference, untuk approval L1) |
| hire_date | date, nullable | Tanggal bergabung |
| status | enum('active','inactive') default 'active' | |
| email_verified_at | timestamp, nullable | |
| remember_token | string, nullable | |
| timestamps | | created_at, updated_at |

Relasi role: gunakan tabel pivot Spatie `model_has_roles` (role: `employee`, `manager`, `hrd_finance`, `admin`).

#### `divisions`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigIncrements | |
| name | string | mis. "IT Department" |
| code | string, nullable | |
| timestamps | | |

#### `expense_types` (Jenis Pengeluaran — untuk Reimbursement)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigIncrements | |
| name | string | mis. "Transportasi", "Konsumsi", "Perlengkapan Kantor", "Kesehatan" |
| is_active | boolean default true | |
| timestamps | | |

#### `activity_types` (Jenis Kegiatan — untuk Konsumsi/Operasional)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigIncrements | |
| name | string | mis. "Rapat Internal", "Kunjungan Klien", "Acara Perusahaan" |
| is_active | boolean default true | |
| timestamps | | |

#### `leave_types` (Jenis Cuti)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigIncrements | |
| name | string | mis. "Cuti Tahunan", "Cuti Sakit", "Cuti Melahirkan", "Cuti Menikah" |
| default_quota | integer, nullable | Kuota default per tahun |
| requires_attachment | boolean default false | Wajib lampiran (mis. surat dokter) |
| is_active | boolean default true | |
| timestamps | | |

#### `leave_balances` (Kuota Cuti per Karyawan per Tahun)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigIncrements | |
| user_id | foreignId → users | |
| leave_type_id | foreignId → leave_types | |
| year | year | |
| quota | integer | Total kuota tahun berjalan |
| used | integer default 0 | Terpakai |
| remaining | integer (computed atau di-update saat approval) | |
| timestamps | | |
| unique | (user_id, leave_type_id, year) | |

#### `reimbursement_requests`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigIncrements | |
| request_number | string, unique | Auto-generate, mis. `RB/2026/07/0001` |
| user_id | foreignId → users | Pengaju |
| expense_type_id | foreignId → expense_types | |
| expense_date | date | Tanggal pengeluaran |
| amount | decimal(15,2) | Nominal (Rp) |
| description | text | Keterangan |
| status | enum('draft','submitted','approved','rejected','paid','completed') default 'draft' | |
| current_approval_level | tinyInteger default 0 | Level approval saat ini |
| submitted_at | timestamp, nullable | |
| rejected_reason | text, nullable | |
| paid_at | timestamp, nullable | |
| paid_by | foreignId → users, nullable | |
| payment_reference | string, nullable | No. referensi transfer |
| timestamps + softDeletes | | |

#### `operational_requests` (Konsumsi/Operasional)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigIncrements | |
| request_number | string, unique | mis. `KO/2026/07/0001` |
| user_id | foreignId → users | |
| activity_type_id | foreignId → activity_types | |
| activity_date | date | |
| activity_name | string | Nama kegiatan |
| purpose | text | Tujuan/Keterangan |
| participant_count | integer | Jumlah peserta |
| estimated_cost | decimal(15,2) | Estimasi biaya |
| location | string | Lokasi kegiatan |
| status | enum('draft','submitted','approved','rejected','paid','completed') default 'draft' | |
| current_approval_level | tinyInteger default 0 | |
| submitted_at | timestamp, nullable | |
| rejected_reason | text, nullable | |
| paid_at | timestamp, nullable | |
| paid_by | foreignId → users, nullable | |
| payment_reference | string, nullable | |
| timestamps + softDeletes | | |

#### `leave_requests`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigIncrements | |
| request_number | string, unique | mis. `CT/2026/07/0001` |
| user_id | foreignId → users | |
| leave_type_id | foreignId → leave_types | |
| start_date | date | |
| end_date | date | |
| total_days | integer | Dihitung otomatis (exclude weekend/hari libur — opsional) |
| reason | text | Alasan cuti |
| handover_to_user_id | foreignId → users, nullable | Serah terima pekerjaan (opsional) |
| handover_notes | text, nullable | |
| status | enum('draft','submitted','approved','rejected','completed','cancelled') default 'draft' | |
| current_approval_level | tinyInteger default 0 | |
| submitted_at | timestamp, nullable | |
| rejected_reason | text, nullable | |
| timestamps + softDeletes | | |

#### `attachments` (Polymorphic — lampiran untuk semua jenis pengajuan)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigIncrements | |
| attachable_type | string | Model class (Reimbursement/Operational/LeaveRequest) |
| attachable_id | bigInteger | |
| file_name | string | Nama asli file |
| file_path | string | Path di storage |
| file_type | string | mime type |
| file_size | integer | dalam bytes |
| uploaded_by | foreignId → users | |
| timestamps | | |

> **Catatan implementasi:** bisa juga langsung memakai **Spatie Media Library** (tabel `media`) sebagai pengganti tabel `attachments` custom ini — lebih matang untuk validasi, konversi, dan manajemen file.

#### `approvals` (Polymorphic — riwayat & status approval berjenjang)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigIncrements | |
| approvable_type | string | Model class pengajuan |
| approvable_id | bigInteger | |
| approver_id | foreignId → users | Siapa yang approve |
| level | tinyInteger | 1 = Atasan, 2 = HRD/Finance |
| status | enum('pending','approved','rejected') default 'pending' | |
| notes | text, nullable | Catatan approval/penolakan |
| acted_at | timestamp, nullable | |
| timestamps | | |

#### `status_histories` (Polymorphic — log perubahan status untuk audit trail)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigIncrements | |
| trackable_type | string | |
| trackable_id | bigInteger | |
| from_status | string, nullable | |
| to_status | string | |
| changed_by | foreignId → users, nullable | null = sistem (auto) |
| notes | text, nullable | |
| created_at | timestamp | |

#### `notifications` (default Laravel notifications table)
Gunakan struktur default Laravel (`php artisan notifications:table`): `id (uuid)`, `type`, `notifiable_type`, `notifiable_id`, `data (json)`, `read_at`, `created_at`, `updated_at`.

### 4.3 Index & Optimisasi
- Index composite pada `(user_id, status)` di ketiga tabel request untuk query dashboard ringkasan cepat.
- Index pada `approvable_type, approvable_id` dan `attachable_type, attachable_id` untuk query polymorphic.
- Index pada `request_number` (unique) untuk pencarian cepat di riwayat.

---

## 5. Alur Bisnis (Business Flow)

### 5.1 Status Umum
**Reimbursement & Konsumsi/Operasional:**
```
Draft → Diajukan (Menunggu Persetujuan) → Disetujui / Ditolak
                                              ↓ (jika disetujui)
                                        Sudah Dibayarkan → Selesai
```

**Cuti:**
```
Draft → Diajukan (Menunggu Persetujuan) → Disetujui / Ditolak
                                              ↓ (jika disetujui, setelah tanggal cuti lewat)
                                           Selesai (otomatis via scheduler)
```

### 5.2 Alur Approval Berjenjang

```mermaid
sequenceDiagram
    participant K as Karyawan
    participant S as Sistem
    participant A as Atasan (Level 1)
    participant H as HRD/Finance (Level 2)

    K->>S: Isi form (3 langkah) & Simpan Draft
    K->>S: Kirim Pengajuan (Submit)
    S->>S: Status = "Menunggu Persetujuan"<br/>Buat record approval level 1
    S->>A: Kirim notifikasi ke Atasan
    A->>S: Approve / Reject (dengan catatan)

    alt Ditolak oleh Atasan
        S->>S: Status = "Ditolak"
        S->>K: Notifikasi pengajuan ditolak
    else Disetujui oleh Atasan
        S->>S: Buat record approval level 2
        S->>H: Kirim notifikasi ke HRD/Finance
        H->>S: Approve / Reject (dengan catatan)
        alt Ditolak HRD/Finance
            S->>S: Status = "Ditolak"
            S->>K: Notifikasi ditolak
        else Disetujui HRD/Finance
            S->>S: Status = "Disetujui"
            S->>K: Notifikasi disetujui
            opt Reimbursement / Konsumsi-Operasional
                H->>S: Proses pembayaran, input referensi transfer
                S->>S: Status = "Sudah Dibayarkan"
                S->>K: Notifikasi pembayaran selesai
                S->>S: (Scheduler) auto set "Selesai" setelah N hari
            end
            opt Cuti
                S->>S: (Scheduler) auto set "Selesai" setelah tanggal selesai cuti lewat
                S->>S: Update leave_balances.used
            end
        end
    end
```

**Catatan konfigurasi approval:**
- Level approval bisa dikonfigurasi per jenis pengajuan (mis. Cuti Tahunan cukup 1 level/Atasan saja, sedangkan Reimbursement > Rp1.000.000 wajib 2 level). Simpan aturan ini di tabel konfigurasi (`approval_rules`) — lihat bagian 5.4 (opsional/advanced).
- Jika karyawan tidak punya `manager_id`, sistem otomatis eskalasi ke HRD/Finance sebagai approver level 1.

### 5.3 Alur Multi-Step Form (Sesuai Mockup)

Setiap modul pengajuan memiliki 3 langkah dengan pola yang identik:

**Step 1 — Informasi**
- Reimbursement: Nama Lengkap, NIK, Divisi, Jabatan, Tanggal Pengajuan (auto-fill dari profil user & tanggal hari ini — read-only), lalu Detail Reimbursement: Tanggal Pengeluaran, Jenis Pengeluaran (dropdown `expense_types`), Nominal (Rp), Keterangan.
- Konsumsi/Operasional: Tanggal Kegiatan, Jenis Kegiatan (dropdown `activity_types`), Nama Kegiatan, Tujuan/Keterangan, lalu Detail Kegiatan: Jumlah Peserta, Estimasi Biaya (Rp), Lokasi.
- Cuti: Jenis Cuti (dropdown `leave_types`, tampilkan sisa kuota), Tanggal Mulai, Tanggal Selesai, Total Hari (auto-calculate), Alasan Cuti.

**Step 2 — Lampiran**
- Upload bukti (struk/nota untuk reimbursement, dokumentasi/proposal untuk konsumsi-operasional).
- Untuk Cuti: **opsional** (Serah Terima Pekerjaan: Diserahkan Kepada [dropdown user], Keterangan) + lampiran opsional (mis. surat dokter jika Cuti Sakit).
- Validasi: max file size (mis. 5MB), tipe file (pdf, jpg, png), multiple upload.

**Step 3 — Review & Kirim**
- Tampilkan ringkasan semua data yang diinput (read-only summary).
- Tombol "Simpan Draft" (di semua step) → simpan status `draft`.
- Tombol "Kirim/Selanjutnya" → validasi lengkap → submit → status `submitted`, generate `request_number`, trigger notifikasi ke approver.

### 5.4 Aturan Bisnis Tambahan
- **Perhitungan Total Hari Cuti**: hitung selisih `start_date` s.d. `end_date` (opsional: exclude Sabtu/Minggu & hari libur nasional — gunakan package `spatie/laravel-holidays` atau tabel `national_holidays`).
- **Validasi Kuota Cuti**: sebelum submit, cek `leave_balances.remaining` ≥ `total_days`. Jika tidak cukup, tampilkan error.
- **Validasi Nominal Reimbursement**: tidak boleh 0 atau negatif; bisa ditambahkan batas maksimum per jenis pengeluaran (opsional, dikonfigurasi admin).
- **Draft tidak terhitung** dalam ringkasan status (hanya `submitted` ke atas yang muncul di ringkasan dashboard).
- **Karyawan hanya bisa edit** pengajuan berstatus `draft` atau `submitted` (sebelum ada approval pertama masuk); setelah diproses approver, pengajuan terkunci (read-only), kecuali dibatalkan.
- **Nomor Pengajuan (request_number)**: format `{PREFIX}/{TAHUN}/{BULAN}/{URUTAN}` — di-generate di dalam DB transaction agar tidak duplikat (gunakan `lockForUpdate` atau tabel counter terpisah).

---

## 6. Use Case

### 6.1 Diagram Use Case (Ringkasan)

```mermaid
flowchart LR
    Karyawan((Karyawan))
    Atasan((Atasan/Manager))
    HRD((HRD/Finance))
    Admin((Admin))

    Karyawan --> UC1[Login/Logout]
    Karyawan --> UC2[Ajukan Reimbursement]
    Karyawan --> UC3[Ajukan Konsumsi/Operasional]
    Karyawan --> UC4[Ajukan Cuti]
    Karyawan --> UC5[Simpan Draft]
    Karyawan --> UC6[Upload Lampiran]
    Karyawan --> UC7[Lihat Riwayat Pengajuan]
    Karyawan --> UC8[Lihat Notifikasi]
    Karyawan --> UC9[Kelola Profil]
    Karyawan --> UC10[Batalkan Pengajuan Draft]

    Atasan --> UC11[Approve/Reject Level 1]
    Atasan --> UC7
    Atasan --> UC8

    HRD --> UC12[Approve/Reject Level 2]
    HRD --> UC13[Proses Pembayaran]
    HRD --> UC14[Kelola Kuota Cuti]
    HRD --> UC7
    HRD --> UC8

    Admin --> UC15[Kelola Data Master]
    Admin --> UC16[Kelola User & Role]
    Admin --> UC17[Kelola Kuota Cuti Default]
    Admin --> UC18[Lihat Semua Pengajuan/Laporan]
```

### 6.2 Detail Use Case Utama

| ID | Use Case | Aktor | Deskripsi Singkat |
|---|---|---|---|
| UC-01 | Login | Semua | Login dengan email & password (Laravel Breeze/Fortify). |
| UC-02 | Lihat Dashboard | Karyawan | Melihat 3 kartu layanan + ringkasan status pengajuan pribadi. |
| UC-03 | Ajukan Reimbursement | Karyawan | Isi form 3 langkah, submit, sistem generate nomor & kirim ke approval L1. |
| UC-04 | Ajukan Konsumsi/Operasional | Karyawan | Sama seperti UC-03, untuk jenis konsumsi/operasional. |
| UC-05 | Ajukan Cuti | Karyawan | Isi form cuti, sistem validasi kuota, submit ke approval. |
| UC-06 | Simpan Draft | Karyawan | Simpan form belum lengkap sebagai draft, bisa dilanjutkan nanti. |
| UC-07 | Upload Lampiran | Karyawan | Upload file bukti pada step 2 form. |
| UC-08 | Lihat Riwayat Pengajuan | Karyawan/Approver | List semua pengajuan dengan filter status, jenis, tanggal. |
| UC-09 | Lihat Detail Pengajuan | Karyawan/Approver | Detail + timeline status (dari `status_histories`). |
| UC-10 | Terima Notifikasi | Semua | Notifikasi in-app & email saat status berubah / ada pengajuan baru untuk diproses. |
| UC-11 | Approve/Reject Pengajuan | Atasan, HRD/Finance | Approve/reject dengan catatan wajib jika reject. |
| UC-12 | Proses Pembayaran | HRD/Finance | Update status ke "Sudah Dibayarkan" + input referensi transfer. |
| UC-13 | Kelola Data Master | Admin | CRUD `expense_types`, `activity_types`, `leave_types`, `divisions`. |
| UC-14 | Kelola User & Role | Admin | CRUD user, assign role & atasan (manager_id). |
| UC-15 | Kelola Kuota Cuti | Admin/HRD | Set/atur kuota cuti tahunan per karyawan. |
| UC-16 | Edit Profil | Karyawan | Update data diri, foto profil, ganti password. |
| UC-17 | Batalkan Pengajuan | Karyawan | Batalkan pengajuan berstatus draft/submitted (sebelum diproses). |
| UC-18 | Export Laporan | HRD/Finance/Admin | Export riwayat pengajuan ke Excel/PDF. |

---

## 7. Struktur Halaman & Routing Frontend

### 7.1 Peta Halaman (Sesuai Mockup + Kelengkapan Sistem)

```
/login                              → Halaman login
/dashboard                          → Beranda (kartu layanan + ringkasan status)

/pengajuan/reimbursement/create     → Form reimbursement (3 step, single page dgn state step)
/pengajuan/konsumsi-operasional/create → Form konsumsi/operasional (3 step)
/pengajuan/cuti/create              → Form cuti (3 step)

/riwayat-pengajuan                  → List semua riwayat (filter: jenis, status, tanggal)
/riwayat-pengajuan/{type}/{id}      → Detail pengajuan + timeline status

/notifikasi                         → List notifikasi

/profil                             → Profil saya (edit data, ganti password)

--- Area Approver (Atasan/HRD) ---
/approval                           → List pengajuan yang perlu diproses (pending approval)
/approval/{type}/{id}               → Detail + tombol Approve/Reject

--- Area Admin ---
/admin/users                        → Kelola user
/admin/divisions                    → Kelola divisi
/admin/expense-types                → Kelola jenis pengeluaran
/admin/activity-types                → Kelola jenis kegiatan
/admin/leave-types                  → Kelola jenis cuti
/admin/leave-balances                → Kelola kuota cuti karyawan
/admin/reports                      → Laporan & export
```

### 7.2 Komponen UI Reusable (Vue)
- `StatusBadge.vue` — badge warna sesuai status (kuning=menunggu, hijau=disetujui, merah=ditolak, biru=dibayar, abu=selesai).
- `Stepper.vue` — komponen step indicator (1-2-3) seperti di mockup.
- `SummaryCard.vue` — kartu ringkasan angka (Menunggu Persetujuan: 3, dst).
- `ServiceCard.vue` — kartu layanan pengajuan (Reimbursement/Konsumsi/Cuti) dengan icon, deskripsi, tombol CTA.
- `FileUploader.vue` — drag & drop upload lampiran dengan preview.
- `FormWizard.vue` — wrapper multi-step form (state management step aktif, validasi per step, tombol Simpan Draft/Selanjutnya/Kembali).
- `Sidebar.vue` & `Topbar.vue` — layout utama sesuai mockup (sidebar navigasi kiri, topbar dengan notifikasi & profil).
- `Modal.vue` — untuk form yang tampil sebagai modal/slide-over (sesuai mockup bagian bawah yang menunjukkan form sebagai card/modal).

---

## 8. API Endpoints (Route List Laravel)

> Karena menggunakan Inertia, sebagian besar route ini adalah **web routes** yang me-render halaman Inertia. Jika suatu saat butuh REST API murni (mis. untuk mobile app), buat namespace terpisah `routes/api.php` dengan resource controller yang sama.

```php
// routes/web.php

// Auth (dari Breeze)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create']);
    Route::post('/login', [AuthController::class, 'store']);
});
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Reimbursement
    Route::prefix('pengajuan/reimbursement')->group(function () {
        Route::get('/create', [ReimbursementController::class, 'create']);
        Route::post('/', [ReimbursementController::class, 'store']);       // submit / draft (bedakan via field `action`)
        Route::put('/{reimbursement}', [ReimbursementController::class, 'update']);
        Route::delete('/{reimbursement}', [ReimbursementController::class, 'destroy']); // batalkan draft
        Route::post('/{reimbursement}/attachments', [ReimbursementAttachmentController::class, 'store']);
        Route::delete('/attachments/{attachment}', [ReimbursementAttachmentController::class, 'destroy']);
    });

    // Konsumsi/Operasional
    Route::prefix('pengajuan/konsumsi-operasional')->group(function () {
        Route::get('/create', [OperationalController::class, 'create']);
        Route::post('/', [OperationalController::class, 'store']);
        Route::put('/{operational}', [OperationalController::class, 'update']);
        Route::delete('/{operational}', [OperationalController::class, 'destroy']);
        Route::post('/{operational}/attachments', [OperationalAttachmentController::class, 'store']);
        Route::delete('/attachments/{attachment}', [OperationalAttachmentController::class, 'destroy']);
    });

    // Cuti
    Route::prefix('pengajuan/cuti')->group(function () {
        Route::get('/create', [LeaveController::class, 'create']);
        Route::post('/', [LeaveController::class, 'store']);
        Route::put('/{leave}', [LeaveController::class, 'update']);
        Route::delete('/{leave}', [LeaveController::class, 'destroy']);
        Route::post('/{leave}/attachments', [LeaveAttachmentController::class, 'store']);
        Route::get('/quota', [LeaveController::class, 'quota']); // cek sisa kuota (untuk dropdown jenis cuti)
    });

    // Riwayat & Detail
    Route::get('/riwayat-pengajuan', [RequestHistoryController::class, 'index']);
    Route::get('/riwayat-pengajuan/{type}/{id}', [RequestHistoryController::class, 'show']);

    // Notifikasi
    Route::get('/notifikasi', [NotificationController::class, 'index']);
    Route::post('/notifikasi/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifikasi/read-all', [NotificationController::class, 'markAllAsRead']);

    // Profil
    Route::get('/profil', [ProfileController::class, 'edit']);
    Route::put('/profil', [ProfileController::class, 'update']);
    Route::put('/profil/password', [ProfileController::class, 'updatePassword']);

    // Approval (middleware role: manager|hrd_finance)
    Route::middleware('role:manager|hrd_finance')->prefix('approval')->group(function () {
        Route::get('/', [ApprovalController::class, 'index']);
        Route::get('/{type}/{id}', [ApprovalController::class, 'show']);
        Route::post('/{type}/{id}/approve', [ApprovalController::class, 'approve']);
        Route::post('/{type}/{id}/reject', [ApprovalController::class, 'reject']);
    });

    // Pembayaran (middleware role: hrd_finance)
    Route::middleware('role:hrd_finance')->group(function () {
        Route::post('/pembayaran/{type}/{id}', [PaymentController::class, 'process']);
    });

    // Admin (middleware role: admin)
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::resource('users', Admin\UserController::class);
        Route::resource('divisions', Admin\DivisionController::class);
        Route::resource('expense-types', Admin\ExpenseTypeController::class);
        Route::resource('activity-types', Admin\ActivityTypeController::class);
        Route::resource('leave-types', Admin\LeaveTypeController::class);
        Route::resource('leave-balances', Admin\LeaveBalanceController::class);
        Route::get('/reports', [Admin\ReportController::class, 'index']);
        Route::get('/reports/export', [Admin\ReportController::class, 'export']);
    });
});
```

---

## 9. Struktur Folder (Laravel + Inertia + Vue)

```
app/
├── Actions/
│   ├── Reimbursement/
│   │   ├── SubmitReimbursementAction.php
│   │   ├── ApproveReimbursementAction.php
│   │   └── ProcessReimbursementPaymentAction.php
│   ├── Operational/
│   │   └── ... (pola sama)
│   ├── Leave/
│   │   ├── SubmitLeaveAction.php
│   │   ├── ApproveLeaveAction.php
│   │   └── CalculateLeaveDaysAction.php
│   └── Shared/
│       ├── GenerateRequestNumberAction.php
│       └── RecordStatusHistoryAction.php
│
├── Http/
│   ├── Controllers/
│   │   ├── DashboardController.php
│   │   ├── ReimbursementController.php
│   │   ├── OperationalController.php
│   │   ├── LeaveController.php
│   │   ├── ApprovalController.php
│   │   ├── PaymentController.php
│   │   ├── RequestHistoryController.php
│   │   ├── NotificationController.php
│   │   ├── ProfileController.php
│   │   └── Admin/
│   │       ├── UserController.php
│   │       ├── DivisionController.php
│   │       ├── ExpenseTypeController.php
│   │       ├── ActivityTypeController.php
│   │       ├── LeaveTypeController.php
│   │       ├── LeaveBalanceController.php
│   │       └── ReportController.php
│   ├── Requests/
│   │   ├── StoreReimbursementRequest.php
│   │   ├── StoreOperationalRequest.php
│   │   ├── StoreLeaveRequest.php
│   │   ├── ApprovalActionRequest.php
│   │   └── ...
│   └── Middleware/
│       └── (role middleware dari Spatie)
│
├── Models/
│   ├── User.php
│   ├── Division.php
│   ├── ExpenseType.php
│   ├── ActivityType.php
│   ├── LeaveType.php
│   ├── LeaveBalance.php
│   ├── ReimbursementRequest.php
│   ├── OperationalRequest.php
│   ├── LeaveRequest.php
│   ├── Attachment.php
│   ├── Approval.php
│   └── StatusHistory.php
│
├── Policies/
│   ├── ReimbursementPolicy.php
│   ├── OperationalPolicy.php
│   └── LeavePolicy.php
│
├── Notifications/
│   ├── RequestSubmittedNotification.php     (ke approver)
│   ├── RequestApprovedNotification.php      (ke karyawan)
│   ├── RequestRejectedNotification.php      (ke karyawan)
│   └── PaymentProcessedNotification.php     (ke karyawan)
│
├── Observers/
│   ├── ReimbursementObserver.php
│   ├── OperationalObserver.php
│   └── LeaveObserver.php
│
├── Console/Commands/
│   └── CompleteFinishedRequestsCommand.php  (scheduler: auto set "Selesai")
│
└── Enums/
    ├── RequestStatus.php   (Draft, Submitted, Approved, Rejected, Paid, Completed)
    ├── ApprovalLevel.php   (Manager, HrdFinance)
    └── UserRole.php        (Employee, Manager, HrdFinance, Admin)

resources/
└── js/
    ├── Pages/
    │   ├── Dashboard.vue
    │   ├── Auth/Login.vue
    │   ├── Pengajuan/
    │   │   ├── Reimbursement/Create.vue
    │   │   ├── Operasional/Create.vue
    │   │   └── Cuti/Create.vue
    │   ├── RiwayatPengajuan/
    │   │   ├── Index.vue
    │   │   └── Show.vue
    │   ├── Notifikasi/Index.vue
    │   ├── Profil/Edit.vue
    │   ├── Approval/
    │   │   ├── Index.vue
    │   │   └── Show.vue
    │   └── Admin/
    │       ├── Users/Index.vue
    │       ├── Divisions/Index.vue
    │       ├── ExpenseTypes/Index.vue
    │       ├── ActivityTypes/Index.vue
    │       ├── LeaveTypes/Index.vue
    │       └── Reports/Index.vue
    ├── Components/
    │   ├── StatusBadge.vue
    │   ├── Stepper.vue
    │   ├── SummaryCard.vue
    │   ├── ServiceCard.vue
    │   ├── FileUploader.vue
    │   ├── FormWizard.vue
    │   ├── Sidebar.vue
    │   └── Topbar.vue
    ├── Layouts/
    │   └── AuthenticatedLayout.vue
    └── composables/
        ├── useMultiStepForm.js
        └── useLeaveDaysCalculator.js

database/
├── migrations/  (sesuai bagian 4.2)
├── seeders/
│   ├── DivisionSeeder.php
│   ├── ExpenseTypeSeeder.php
│   ├── ActivityTypeSeeder.php
│   ├── LeaveTypeSeeder.php
│   ├── RoleSeeder.php
│   └── UserSeeder.php (dummy data untuk testing)
└── factories/
    ├── UserFactory.php
    ├── ReimbursementRequestFactory.php
    ├── OperationalRequestFactory.php
    └── LeaveRequestFactory.php
```

---

## 10. Notifikasi

### 10.1 Trigger Notifikasi
| Event | Penerima | Channel |
|---|---|---|
| Pengajuan baru disubmit | Atasan (approver level 1) | Database + Email |
| Approval level 1 disetujui | HRD/Finance (approver level 2) | Database + Email |
| Pengajuan disetujui (final) | Karyawan pengaju | Database + Email |
| Pengajuan ditolak (level manapun) | Karyawan pengaju | Database + Email |
| Pembayaran diproses | Karyawan pengaju | Database + Email |
| Cuti otomatis selesai | Karyawan pengaju | Database |
| Kuota cuti hampir habis (opsional) | Karyawan | Database |

### 10.2 Struktur Notifikasi (contoh)
```php
class RequestApprovedNotification extends Notification
{
    public function via($notifiable) { return ['database', 'mail']; }

    public function toDatabase($notifiable) {
        return [
            'title' => 'Pengajuan Disetujui',
            'message' => "Pengajuan {$this->requestNumber} telah disetujui.",
            'related_type' => $this->type,
            'related_id' => $this->id,
            'url' => "/riwayat-pengajuan/{$this->type}/{$this->id}",
        ];
    }
}
```

---

## 11. Validasi & Keamanan

- **CSRF Protection**: default Laravel + Inertia sudah aman.
- **Rate Limiting**: throttle login & submit form (`throttle:60,1`).
- **Role & Policy Guard**: setiap controller approval/admin wajib dicek via Policy/Middleware, jangan hanya mengandalkan hidden UI.
- **File Upload Security**: validasi mime type whitelist (`pdf,jpg,jpeg,png`), max size, scan nama file (hindari path traversal), simpan di storage non-public dengan signed URL untuk download.
- **Audit Trail**: semua perubahan status tercatat di `status_histories` (siapa, kapan, dari status apa ke apa).
- **Data Karyawan Read-only di Form**: field seperti Nama, NIK, Divisi, Jabatan di Step 1 diambil otomatis dari data user login (read-only), tidak diinput manual, untuk mencegah manipulasi data.

---

## 12. Scheduler / Cron Jobs

```php
// app/Console/Kernel.php
$schedule->command('requests:complete-finished')->daily();
```

**`CompleteFinishedRequestsCommand`** bertugas:
1. Set status `completed` pada `leave_requests` yang `end_date` sudah lewat dan status masih `approved`.
2. Set status `completed` pada `reimbursement_requests`/`operational_requests` yang sudah `paid` lebih dari N hari (mis. 3 hari) — sesuai definisi bisnis "Selesai" pada kartu ringkasan.
3. Update `leave_balances.used` & `remaining` setelah cuti disetujui.

---

## 13. Roadmap Pengembangan (Untuk AI Agentic / Tim Dev)

### Fase 1 — Fondasi
1. Setup project Laravel + Breeze (Inertia + Vue) + Tailwind.
2. Install & konfigurasi Spatie Permission, Media Library.
3. Buat migration seluruh tabel (bagian 4.2).
4. Buat seeder data master (divisions, expense_types, activity_types, leave_types, roles, dummy users).
5. Setup layout dasar (Sidebar, Topbar) sesuai mockup.

### Fase 2 — Modul Pengajuan (Core)
6. Buat model + relasi (User, ReimbursementRequest, OperationalRequest, LeaveRequest, Attachment, Approval, StatusHistory).
7. Buat `GenerateRequestNumberAction` & `RecordStatusHistoryAction` (shared logic).
8. Bangun form 3-step Reimbursement (frontend + backend + validasi + simpan draft/submit).
9. Bangun form 3-step Konsumsi/Operasional (pola sama).
10. Bangun form 3-step Cuti (termasuk kalkulasi total hari & validasi kuota).
11. Implementasi upload lampiran (Media Library) untuk ketiga modul.

### Fase 3 — Approval & Notifikasi
12. Buat `ApprovalController` + Policy untuk approve/reject berjenjang.
13. Implementasi Observer untuk trigger notifikasi otomatis di setiap perubahan status.
14. Buat halaman Approval (list pending + detail + form approve/reject).
15. Implementasi modul Pembayaran (HRD/Finance) untuk Reimbursement & Operasional.

### Fase 4 — Dashboard, Riwayat, Notifikasi
16. Buat `DashboardController` (kartu layanan + ringkasan status — agregasi query per status).
17. Buat halaman Riwayat Pengajuan (list + filter + detail + timeline status).
18. Buat halaman Notifikasi (list, mark as read).

### Fase 5 — Admin & Laporan
19. Buat modul Admin (CRUD data master, kelola user, kelola kuota cuti).
20. Buat modul Laporan/Export (Excel/PDF) untuk HRD/Finance & Admin.

### Fase 6 — Penyempurnaan
21. Scheduler untuk auto-complete status.
22. Testing (Feature test untuk setiap flow: submit, approve, reject, payment).
23. Optimisasi query dashboard (eager loading, caching ringkasan).
24. Responsive check & polish UI sesuai mockup (warna badge, ikon, dsb).

---

## 14. Mapping Warna & Ikon Status (Sesuai Mockup)

| Status | Warna Badge | Ikon (referensi lucide-vue) |
|---|---|---|
| Menunggu Persetujuan | Kuning (`bg-yellow-100 text-yellow-700`) | `clock` |
| Disetujui | Hijau (`bg-green-100 text-green-700`) | `check-circle` |
| Ditolak | Merah (`bg-red-100 text-red-700`) | `x-circle` |
| Sudah Dibayarkan | Biru (`bg-blue-100 text-blue-700`) | `send` / `banknote` |
| Selesai | Abu-abu (`bg-gray-100 text-gray-700`) | `check-circle-2` |

**Warna kartu layanan (sesuai mockup):**
- Reimbursement Karyawan → Hijau (`green-600`), ikon dokumen (`file-text`).
- Konsumsi/Operasional → Oranye (`orange-500`), ikon makan (`utensils`).
- Cuti Karyawan → Ungu (`purple-600`), ikon kalender (`calendar`).

---

## 15. Catatan Akhir untuk AI Agentic

- Bangun **Fase 1 & 2 dahulu** secara end-to-end untuk 1 modul (Reimbursement) sebagai *reference implementation*, baru replikasi pola yang sama ke 2 modul lainnya (Konsumsi/Operasional, Cuti) — karena ketiganya punya struktur sangat mirip (form 3 step, approval, attachment).
- Gunakan **satu set komponen Vue reusable** (`FormWizard`, `Stepper`, `FileUploader`, `StatusBadge`) agar konsisten dan efisien dalam development.
- Semua nama tabel, kolom, dan status di dokumen ini adalah **saran/konvensi** — dapat disesuaikan asal konsisten dipakai di seluruh layer (migration → model → controller → frontend).
- Prioritaskan **Policy & role middleware** sejak awal (jangan ditunda ke akhir) untuk menghindari refactor besar terkait otorisasi.
