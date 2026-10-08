# Sistem Enterprise Resource Planning PT Intel Creative

Sistem Enterprise Resource Planning (ERP) PT Intel Creative merupakan platform terintegrasi yang dirancang untuk mengelola proses bisnis operasional, manajemen sumber daya manusia, presensi, penggajian, penugasan proyek, serta pencatatan keuangan dan akuntansi korporat.

Aplikasi ini dibangun menggunakan arsitektur modern berbasis Domain-Driven Design (DDD) untuk memastikan modularitas, skalabilitas, dan kepatuhan terhadap standar tata kelola data perusahaan.

## Arsitektur Teknologi

Sistem mengadopsi tumpukan teknologi modern dengan pembagian peran yang terstruktur:

1. Backend: Laravel 12 berbasis PHP 8.3 dengan pemanfaatan Service Layer dan Action Pattern.
2. Frontend: Vue 3 Single Page Application (SPA) yang terintegrasi melalui Inertia.js v3.
3. Antarmuka Pengguna: Tailwind CSS dengan tipografi standar Plus Jakarta Sans dan format angka tabular numerik.
4. Basis Data: Relational Database Management System dengan integritas referensial dan pengindeksan parsial.
5. Presisi Finansial: Penanganan kalkulasi moneter menggunakan komputasi desimal berpresisi tinggi untuk mencegah galat pembulatan.

## Modul Utama Sistem

### 1. Manajemen Sumber Daya Manusia (HR)
Modul ini mencakup pengelolaan siklus hidup karyawan secara komprehensif:
* Manajemen master data personel, departemen, dan jabatan struktural.
* Pengelolaan status ketenagakerjaan karyawan tetap (PKWTT) dan kontrak (PKWT).
* Penyimpanan dokumen identitas dan catatan kepegawaian resmi.

### 2. Presensi dan Manajemen Cuti
Pencatatan kehadiran dan pengajuan izin karyawan dengan pengamanan ganda:
* Mekanisme pencatatan waktu masuk (Clock-In) dan keluar (Clock-Out) harian.
* Perlindungan atomic locking berbasis cache untuk mencegah manipulasi data ganda konkuren.
* Kalkulasi keterlambatan dan durasi kerja efektif otomatis.
* Alur persetujuan permohonan cuti bertingkat (Multi-Level Approval).

### 3. Penggajian dan Remunerasi (Payroll)
Pengelolaan kompensasi karyawan sesuai regulasi ketenagakerjaan dan perpajakan Indonesia:
* Perhitungan komponen gaji pokok, tunjangan jabatan, tunjangan operasional, dan kompensasi lembur.
* Pemotongan iuran BPJS Ketenagakerjaan dan BPJS Kesehatan secara otomatis.
* Kalkulasi estimasi Pajak Penghasilan (PPh 21).
* Penerbitan dokumen resmi slip gaji digital.
* Portal mandiri snapshot transparansi paket remunerasi karyawan.

### 4. Manajemen Proyek dan Lembar Kerja (Project & Timesheet)
Pengawasan pelaksanaan proyek kreatif dan penugasan operasional:
* Monitoring status proyek dari tahap inisiasi hingga penyelesaian.
* Pembagian tugas kerja kepada personel pelaksana.
* Pencatatan lembar kerja harian (Timesheet) untuk transparansi jam kerja dan alokasi sumber daya.

### 5. Keuangan dan Akuntansi (Finance & Accounting)
Pengelolaan tata buku keuangan korporat yang akuntabel:
* Struktur bagan akun standar (Chart of Accounts).
* Pencatatan buku besar (General Ledger) dan jurnal memorial.
* Otomasi pencatatan jurnal pengeluaran saat periode penggajian disetujui.
* Pengelolaan dan verifikasi disbursement voucher.

### 6. Keamanan, Kontrol Akses, dan Kepatuhan (Security & Audit Trail)
Infrastruktur perlindungan data dan jejak transaksi:
* Kontrol akses berbasis peran (Role-Based Access Control) multi-level mulai dari Superadmin, Direktur, HR Admin, Finance, Project Lead, hingga Karyawan.
* Pencatatan jejak audit (Audit Trail) terhadap perubahan data penting.
* Pembatasan laju permintaan (Rate Limiting) untuk memitigasi serangan brute force.

## Prasyarat Lingkungan

Sebelum menjalankan instalasi, pastikan lingkungan peladen telah memenuhi spesifikasi berikut:

* PHP versi 8.3 atau lebih tinggi
* Ekstensi PHP: OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath, GD/Imagick
* Composer versi 2.x
* Node.js versi 20.x atau lebih tinggi beserta NPM
* Database SQLite atau MySQL 8.0+

## Panduan Instalasi

Ikuti langkah-langkah berikut untuk memasang aplikasi pada lingkungan lokal:

1. Kloning repositori proyek:
   ```bash
   git clone https://github.com/Novazeb/simpel-erp-intelcreative.git
   cd simpel-erp-intelcreative
   ```

2. Pasang dependensi PHP melalui Composer:
   ```bash
   composer install
   ```

3. Pasang dependensi JavaScript melalui NPM:
   ```bash
   npm install
   ```

4. Siapkan file konfigurasi lingkungan:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. Lakukan migrasi skema basis data dan pengisian data awal:
   ```bash
   php artisan migrate --seed
   ```

6. Kompilasi aset antarmuka pengguna:
   ```bash
   npm run build
   ```

7. Jalankan peladen lokal:
   ```bash
   php artisan serve
   ```

## Verifikasi dan Pengujian

Sistem dilengkapi dengan rangkaian pengujian otomatis untuk menjamin integritas fungsional:

```bash
php artisan test
```

## Kebijakan Lisensi

Hak Cipta PT Intel Creative. Seluruh hak cipta dilindungi undang-undang. Perangkat lunak ini dikembangkan untuk keperluan operasional internal perusahaan.
