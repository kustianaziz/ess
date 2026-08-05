## 1. COLOR TOKENS

```css
/* Primary */
--color-primary: #1657FF;        /* tombol utama, angka jam besar, active state, link */
--color-primary-dark: #0F3FCC;   /* pressed/hover state tombol primary */
--color-primary-soft: #EAF0FF;   /* border & bg card meeting (light blue) */

/* Semantic */
--color-success: #22B14C;        /* status "in office", Total Earnings, dot hijau */
--color-danger: #E0333D;         /* Total Deductions, angka minus */
--color-warning-bg: #FDEDEF;     /* background banner notifikasi pink/peach */
--color-badge: #FF4757;          /* notification dot merah di icon bell */

/* Neutral */
--color-bg-page: #F5F6F8;        /* background halaman */
--color-bg-card: #FFFFFF;        /* semua card */
--color-text-primary: #111318;   /* judul, angka penting, isi utama */
--color-text-secondary: #8A8F98; /* subtitle, label, tanggal, gray text */
--color-text-tertiary: #B0B4BB;  /* placeholder "--", teks paling ringan */
--color-border-subtle: #ECEDF0;  /* divider/garis pemisah tipis */

/* Quick Menu Icon Backgrounds (pastel, satu warna per kategori) */
--icon-bg-yellow: #FFF3D6;   /* Ramadan Challenge */
--icon-bg-purple: #F1E8FF;   /* Announcement */
--icon-bg-orange: #FFE9D6;   /* Taksfy */
--icon-bg-red: #FFE1E1;      /* KPI */
--icon-bg-blue: #DCE9FF;     /* Survey */
--icon-bg-green: #DFF7E6;    /* Payslip */
--icon-bg-pink: #FFE1EC;     /* Penilaian Kinerja */
--icon-bg-teal: #D9F5F0;     /* Meeting */
```

---

## 2. TYPOGRAPHY

Font family: sans-serif modern (Inter / SF Pro / Poppins — pakai yang tersedia di stack, prioritas **Inter**).

```css
--font-family: 'Inter', -apple-system, 'Segoe UI', sans-serif;

/* Scale */
--text-display: 28px;   /* angka jam "09:00:55", nominal gaji "Rp 8,250,000" */
--text-h1: 20px;        /* greeting "Hello Lova!", title header "Payroll" */
--text-h2: 16px;        /* section header: "Today's Work Hours", "Quick Menu" */
--text-body: 14px;      /* teks isi normal, nama meeting, nama menu */
--text-caption: 13px;   /* subtitle, tanggal, label kecil */
--text-small: 12px;     /* label paling kecil: "Clock In", "Total Hours" label */

/* Weight */
--weight-bold: 700;     /* angka besar, judul, nominal */
--weight-semibold: 600; /* section header, judul card */
--weight-regular: 400;  /* body text, subtitle */

/* Line height */
--line-height-tight: 1.2;   /* angka besar */
--line-height-normal: 1.4;  /* body/caption */
```

---

## 3. SPACING & LAYOUT

```css
--space-page-x: 16px;      /* padding horizontal halaman */
--space-section-gap: 16px; /* jarak antar section/card */
--space-card-padding: 16px;/* padding internal card */
--space-xs: 4px;
--space-sm: 8px;
--space-md: 12px;
--space-lg: 16px;
--space-xl: 24px;
```

## 4. RADIUS & SHADOW

```css
--radius-card: 20px;     /* card besar (jam, payroll summary, meeting) */
--radius-button: 14px;   /* tombol full-width */
--radius-icon-box: 14px; /* kotak icon quick menu, 48x48px */
--radius-pill: 999px;    /* badge/status pill kecil, avatar */

--shadow-card: 0 2px 10px rgba(17, 19, 24, 0.05);
```

---

## 5. KOMPONEN — SPESIFIKASI DETAIL

### 5.1 Top Greeting Header (Home)
- Struktur: teks di kiri, icon bell + badge di kanan, align top, satu baris.
- "Hello, {nama}!" → `--text-h1`, `--weight-bold`, `--color-text-primary`.
- Subtitle di bawahnya → `--text-caption`, `--weight-regular`, `--color-text-secondary`.
- Icon bell: outline style, ukuran 24px, warna `--color-text-primary`.
- Badge notifikasi: lingkaran kecil `--color-badge`, posisi absolute top-right icon bell, teks angka putih 10px bold.

### 5.2 Date & Clock Card
- Card putih, radius `--radius-card`, shadow `--shadow-card`, padding `--space-card-padding`.
- Baris atas: icon kalender kecil + "{Hari}" (`--text-body`, `--weight-semibold`) lalu "{tanggal lengkap}" (`--text-caption`, `--color-text-secondary`) — di kiri. Jam digital besar di kanan: `--text-display`, `--weight-bold`, `--color-primary`, format `HH:MM:SS`.
- Tombol full-width di bawahnya: background `--color-primary`, text putih bold, height 48px, radius `--radius-button`. Label dinamis: "Clock In" / "Clock Out" tergantung state.
- Di bawah tombol: status indicator center-aligned → dot hijau 6px + teks `--text-caption` `--color-text-secondary`, contoh "You are in office".

### 5.3 "Today's Work Hours" — 3 Mini Card
- Section header di atas: `--text-h2 --weight-semibold`.
- 3 card sejajar (grid 3 kolom, gap `--space-sm`), tiap card: bg putih, radius 14px, padding 12px, isi: icon kecil (24px, warna sesuai kategori — biru/pink/hijau) + label (`--text-small`, `--color-text-secondary`) + value (`--text-body --weight-semibold`, default `"--"` jika kosong, `--color-text-tertiary`).
- Label: "Clock In", "Clock Out", "Total Hours".

### 5.4 Meeting Upcoming Card
- Section header + link "View All" kanan (`--text-caption`, `--color-primary`, `--weight-semibold`).
- Card: background `--color-primary-soft`, border 1px `--color-primary` opacity rendah, radius `--radius-card`, padding 16px.
- Tag status: dot hijau + teks "Today" (`--text-small --weight-semibold --color-success`), top-left card.
- Layout dalam: kotak waktu di kiri (background `--color-primary`, teks putih bold, radius 10px, padding 8px, format 2 baris "10:00" / "AM"), di kanan judul meeting (`--text-body --weight-bold`), lokasi dengan icon pin (`--text-caption --color-text-secondary`).
- Baris bawah: avatar stack (lingkaran overlap, border putih 2px, tiap avatar warna solid berbeda + inisial) + teks "{n} peserta" (`--text-caption --color-text-secondary`), tombol "Join" kanan — bg `--color-primary`, teks putih, radius pill, padding 8px 20px.

### 5.5 Quick Menu Grid
- Section header dengan icon pin kecil + "Quick Menu".
- Grid 5 kolom (mobile), gap 8-12px, tiap item: icon box 48x48px radius `--radius-icon-box` bg pastel sesuai kategori (lihat token warna icon di atas), icon di tengah 24px warna gelap senada, label di bawah icon center-aligned `--text-small --color-text-secondary`, max 2 baris.
- Daftar menu row 1: Ramadan Challenge, Announcement, Taksfy, KPI, Survey.
- Row 2: Payslip, Penilaian Kinerja, Meeting.

### 5.6 Latest Updates
- Section header + icon api 🔥 + "View All" link kanan, style sama seperti section header lain.
- (Konten list di bawahnya mengikuti pola card list standar — reuse card component.)

### 5.7 Bottom Navigation
- Fixed bottom, background putih, shadow ke atas tipis, height ±64px, 4 item merata.
- Item: icon (24px) + label (`--text-small`) vertikal.
- State aktif: icon + label warna `--color-primary`, weight semibold.
- State non-aktif: warna `--color-text-secondary`, weight regular.
- Item terakhir ("Profile"): avatar bulat kecil dengan inisial (bg warna solid, mis. orange `#FF9F43`, teks putih bold) menggantikan icon biasa.
- Menu: Home, History, Leave, Profile.

### 5.8 Header Halaman Detail (Payroll style)
- Back icon (chevron-left, 24px) kiri, judul halaman center-aligned (`--text-h1 --weight-semibold`), background transparan/putih, tanpa shadow, height 56px.

### 5.9 Hero Card — Ringkasan Nilai Utama (Take Home Pay)
- Full-width card, background solid `--color-primary` (bukan gradient kompleks — solid blue), radius `--radius-card`, padding 20px, teks putih.
- Baris atas: label kecil ("Take Home Pay ({periode})") `--text-caption`, opacity 80%, + icon "hide/eye-slash" kanan atas (toggle visibility nominal), 20px, putih.
- Nominal besar: `--text-display` (bisa naik ke 30-32px khusus di sini), `--weight-bold`, putih, format "Rp {angka}".
- Sub-caption di bawah nominal: `--text-caption`, opacity 70%, contoh "Paid on {tanggal}".
- Tombol di dalam card (bawah): background putih opacity 15-20% (glassmorphism ringan), teks putih, icon dokumen kiri, radius `--radius-button`, full-width, height 44px. Label: "View Payslip".

### 5.10 Summary Card (rincian angka)
- Card putih standar (`--radius-card`, `--shadow-card`, padding 16px).
- Judul card: `--text-h2 --weight-semibold`, margin-bottom 12px.
- List baris key-value: label kiri (`--text-body --color-text-secondary`), value kanan (`--text-body --weight-semibold`) — value berwarna semantik: hijau untuk earnings/positif (`--color-success`), merah dengan prefix "-" untuk deduction (`--color-danger`), hitam untuk total netral.
- Divider tipis (`--color-border-subtle`, 1px) sebelum baris total/final.
- Baris total: label + value lebih besar (`--text-h2 --weight-bold`, `--color-text-primary`).

### 5.11 Info/Notification Banner (inline, bukan toast)
- Background `--color-warning-bg` (pink lembut), radius `--radius-card`, padding 14px 16px, layout horizontal: icon (petir, dalam lingkaran kecil bg putih atau solid warna accent) kiri, teks tengah (judul `--text-body --weight-semibold`, subtitle `--text-caption --color-text-secondary`), chevron-right kanan (indikator clickable, 18px, `--color-text-secondary`).

---

## 6. PRINSIP DESAIN (WAJIB DIPATUHI KONSISTEN DI SELURUH APP)

1. **Card-based layout** — setiap grup informasi dibungkus card putih (`--radius-card`, `--shadow-card`) di atas background abu `--color-bg-page`. Tidak ada konten "mengambang" tanpa card.
2. **Satu warna anchor** — `--color-primary` HANYA dipakai untuk: CTA utama, angka/data terpenting di layar, state aktif (nav, tab, link). Jangan pakai primary untuk elemen dekoratif biasa.
3. **Tidak ada hard border** — pemisah antar elemen menggunakan spacing, radius, dan shadow tipis, bukan garis tegas (kecuali divider dalam summary card, 1px sangat tipis).
4. **Warna semantik konsisten**: hijau = positif/earnings/status aktif, merah = negatif/deduction/urgent, di seluruh app tanpa terkecuali.
5. **Hierarki angka besar-di-depan** — data paling penting per card (jam, nominal) selalu jadi elemen visual terbesar & terberat (bold, ukuran terbesar dalam card itu).
6. **Icon selalu outline/line-style ringan**, bukan filled solid, kecuali di dalam icon-box quick menu (boleh filled dengan warna gelap senada bg pastelnya).
7. **Radius besar konsisten** (18-20px card, 12-14px tombol/element kecil) — tidak ada sudut tajam di manapun.
8. **Padding halaman konsisten 16px** kiri-kanan di semua screen.

---

## 7. PROMPT UNTUK GEMINI CLI

Copy-paste prompt ini (sesuaikan path project & stack di bagian [ ]):

```
Baca file DESIGN_SYSTEM.md di root project ini. File ini adalah acuan
resmi & final untuk seluruh styling aplikasi — ikuti SEMUA token warna,
typography, spacing, radius, shadow, dan spesifikasi komponen di file
tersebut secara LITERAL. Jangan menebak atau mengganti nilai apapun
yang sudah didefinisikan di sana.

Stack project: [React Native / Flutter / Tailwind+React / dst]
Lokasi komponen: [src/components/ atau path lain]

Tugas:
1. Buat/perbarui token global (theme file / tailwind.config.js /
   theme.dart — sesuaikan stack) berisi seluruh variabel di section
   1-4 file DESIGN_SYSTEM.md (colors, typography, spacing, radius, shadow).
2. Refactor komponen berikut agar 100% sesuai spesifikasi di section 5:
   - [Header/Greeting]
   - [Card Jam/Clock]
   - [3 Mini Card Work Hours]
   - [Meeting Card]
   - [Quick Menu Grid]
   - [Bottom Navigation]
   - [Payroll Hero Card]
   - [Payroll Summary Card]
   - [Notification Banner]
   (sebutkan nama file/komponen aktual di project kamu untuk masing-masing)
3. Terapkan prinsip desain di section 6 secara konsisten ke SELURUH
   layar aplikasi, bukan cuma layar yang disebutkan di atas.
4. Jangan ubah logic/state management — HANYA styling/markup terkait tampilan.
5. Setelah selesai, berikan ringkasan: file apa saja yang diubah,
   token/section mana yang diterapkan di masing-masing file.

Jika ada elemen UI di project yang tidak tercakup di DESIGN_SYSTEM.md,
JANGAN menebak — tanyakan dulu ke saya sebelum mengubahnya.
```

---

## 8. CATATAN PENTING

- Nilai hex & px di atas adalah **hasil estimasi visual** dari mockup gambar. Untuk akurasi 100% (bukan estimasi), idealnya ambil nilai eksak dari file desain asli (Figma/Sketch) — export token dari sana lalu ganti section 1-4 file ini dengan nilai exact tersebut.
- Kalau project kamu sudah punya design token/theme file sebelumnya, minta Gemini CLI **selaraskan** (bukan duplikat) — sebutkan path file theme lama di prompt.
- Untuk hasil paling presisi, jalankan per-komponen (satu prompt satu komponen) daripada satu prompt untuk seluruh app sekaligus — lebih mudah direview & lebih kecil risiko error.