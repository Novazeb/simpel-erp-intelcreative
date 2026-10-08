# PT INTEL CREATIVE Design System
## Minimalist Corporate Identity & UI Guidelines

---

### 1. Filosofi & Arah Desain (Design Direction)
* **Tema:** Minimalisme Modern Korporat (Clean, Distraction-Free, Functional).
* **Target Pengguna:** Tim Operasional Internal, Personalia (HR), Keuangan (Finance), dan Jajaran Direksi Eksekutif.
* **Dial Antislop:** **ENERGY 1 / RHYTHM 1 / MOTION 1** (Fokus pada keterbacaan data, kecepatan respons, dan presisi akuntansi).

---

### 2. Palet Warna (Color System)
Menghindari penggunaan satu warna secara berlebihan. Antarmuka didominasi oleh kanvas netral monokromatik (putih dan slate), dipadukan dengan aksen fungsional yang terukur:

* **Dasar / Latar Belakang (Canvas):**
  * Kanvas Utama: Putih Bersih (`#ffffff` / `bg-white`)
  * Kontras Wilayah / Surface: Slate Terang (`#f8fafc` / `bg-slate-50`, `#f1f5f9` / `bg-slate-100`)
* **Warna Dasar Utama (Deep Navy / Biru Tua):**
  * Sidebar & Aksen Utama: Biru Tua Elegan (`#0f172a` / `slate-900`, `#1e293b` / `slate-800`, `#1e3a8a` / `blue-900`)
  * Digunakan untuk navigasi utama dan tombol aksi primer, **bukan** mewarnai seluruh layar.
* **Tipografi (Font Hitam Pekat & Slate):**
  * **Keluarga Font (Font Family):** **Plus Jakarta Sans** (`font-sans`, dengan fallback system-ui)
  * Teks Utama / Headings: Hitam Pekat (`#09090b` / `text-black` / `text-zinc-950`)
  * Teks Body / Data: Hitam Netral (`#18181b` / `text-zinc-900` / `text-slate-900`)
  * Teks Sekunder / Label: Abu-abu Tua Berketerbacaan Tinggi (`#475569` / `text-slate-600` - WCAG AA Contrast > 5.5:1)
  * Data Finansial / NIK / Tanggal / Numerik: Plus Jakarta Sans dengan Tabular Figures (`font-feature-settings: 'tnum' 1; font-variant-numeric: tabular-nums;`), tanpa font-mono
* **Aksen Fungsional Terbatas (Status & Verifikasi):**
  * Hijau Halus: Status disetujui / seimbang (`text-emerald-700 bg-emerald-50 border-emerald-200`)
  * Merah Halus: Status potongan / keterlambatan (`text-rose-700 bg-rose-50 border-rose-200`)
  * Kuning Halus: Status proses / pending (`text-amber-700 bg-amber-50 border-amber-200`)

---

### 3. Standar Komponen & Eliminasi Elemen
Sesuai arahan audit antarmuka:
1. **Tanpa Status Bar Indikator:** Header dibersihkan dari indikator badge status hiasan (`Sistem Terkoneksi & Realtime` dihilangkan).
2. **Tanpa Karakter Em Dash (`—`):** Seluruh teks menggunakan tanda hubung standar, koma, titik dua, atau tanda kurung.
3. **Tanpa Emoji:** Seluruh simbol dekoratif berbasis teks/emoji diganti menjadi **Icon SVG Vektor Bergaris Presisi (Inline SVG)**.
4. **Fokus & Aksesibilitas:** Outline fokus kontras tinggi berstandar WCAG (`focus:ring-2 focus:ring-slate-900 focus:outline-hidden`).
5. **Ergonomi Sentuh:** Semua elemen interaktif memiliki target sentuh minimal 44 piksel.
