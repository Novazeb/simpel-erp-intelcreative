<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  employee: Object,
  slips: Array,
  latest_slip: Object,
});
</script>

<template>
  <AppLayout>
    <template #header>Snapshot & Slip Gaji Pribadi</template>

    <div class="space-y-6 max-w-5xl">
      <!-- Header Deskriptif -->
      <div class="pb-2 border-b border-slate-200">
        <h2 class="text-lg font-bold text-black">Ringkasan Kompensasi & Rekapitulasi Slip Gaji</h2>
        <p class="text-xs text-slate-600 mt-0.5">Informasi transparansi paket remunerasi, potongan resmi regulasi pemerintah, dan riwayat penerbitan slip gaji.</p>
      </div>

      <!-- Kartu Profil Karyawan & Status Rekening -->
      <div class="bg-white border border-slate-200 rounded-md p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b border-slate-200">
          <div>
            <span class="text-[10px] font-bold tracking-wider uppercase text-slate-500">Profil Personel Aktif</span>
            <h3 class="text-base font-bold text-black mt-0.5">{{ employee?.full_name || 'Personel Intel Creative' }}</h3>
            <p class="text-xs text-slate-600">
              {{ employee?.designation?.title || 'Posisi Staf' }} &bull; {{ employee?.department?.name || 'Departemen Operasional' }}
            </p>
          </div>
          <div class="flex items-center gap-3">
            <div class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded text-right">
              <p class="text-[10px] uppercase font-bold text-slate-500">Nomor Induk Karyawan</p>
              <p class="text-xs font-bold text-black">{{ employee?.nik || '-' }}</p>
            </div>
            <div class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded text-right">
              <p class="text-[10px] uppercase font-bold text-slate-500">Status Kontrak</p>
              <p class="text-xs font-semibold text-black">{{ employee?.employment_status || 'PKWTT' }}</p>
            </div>
          </div>
        </div>

        <!-- Detail Rekening Payroll -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-5 text-xs">
          <div class="p-3 bg-slate-50 border border-slate-200 rounded">
            <p class="text-[10px] font-bold text-slate-500 uppercase">Rekening Payroll Terdaftar</p>
            <p class="text-xs font-bold text-black mt-1">{{ employee?.bank_name }} - {{ employee?.bank_account_number }}</p>
            <p class="text-[11px] text-slate-600 mt-0.5">a.n. {{ employee?.bank_account_holder }}</p>
          </div>

          <div class="p-3 bg-slate-50 border border-slate-200 rounded">
            <p class="text-[10px] font-bold text-slate-500 uppercase">Tanggal Bergabung Perusahaan</p>
            <p class="text-xs font-bold text-black mt-1">{{ employee?.join_date }}</p>
            <p class="text-[11px] text-slate-600 mt-0.5">Masa Kerja Aktif</p>
          </div>

          <div class="p-3 bg-slate-50 border border-slate-200 rounded">
            <p class="text-[10px] font-bold text-slate-500 uppercase">Status Pajak Penghasilan</p>
            <p class="text-xs font-bold text-black mt-1">{{ employee?.tax_status || 'TK/0' }} (TER Kategori A)</p>
            <p class="text-[11px] text-slate-600 mt-0.5">Sesuai PP 58/2023 & PMK 168/2023</p>
          </div>
        </div>
      </div>

      <!-- KPI Remunerasi Snapshot Terkini -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200 rounded-md p-5">
          <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Gaji Pokok (Master)</p>
          <p class="text-xl font-bold text-black mt-1">
            Rp {{ Number(employee?.basic_salary || 0).toLocaleString('id-ID') }}
          </p>
          <p class="text-[11px] text-slate-600 mt-1">Snapshot upah dasar terdaftar</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-md p-5">
          <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tunjangan Terakhir</p>
          <p class="text-xl font-bold text-emerald-800 mt-1">
            + Rp {{ Number(latest_slip?.total_allowances || 0).toLocaleString('id-ID') }}
          </p>
          <p class="text-[11px] text-slate-600 mt-1">Transport, kehadiran, & lembur</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-md p-5">
          <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Potongan Resmi</p>
          <p class="text-xl font-bold text-rose-800 mt-1">
            - Rp {{ Number(latest_slip?.total_deductions || 0).toLocaleString('id-ID') }}
          </p>
          <p class="text-[11px] text-slate-600 mt-1">PPh 21 TER, BPJS TK & Kes</p>
        </div>

        <div class="bg-[#0a192f] text-white border border-[#1e2d4a] rounded-md p-5">
          <p class="text-[11px] font-bold text-slate-300 uppercase tracking-wider">Take Home Pay Terakhir</p>
          <p class="text-xl font-bold text-white mt-1">
            Rp {{ Number(latest_slip?.take_home_pay || employee?.basic_salary || 0).toLocaleString('id-ID') }}
          </p>
          <p class="text-[11px] text-slate-300 mt-1">Bersih ditransfer ke rekening</p>
        </div>
      </div>

      <!-- Tabel Riwayat Slip Gaji -->
      <div class="bg-white rounded-md border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
          <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-black">Riwayat Slip Pembayaran Gaji</h3>
            <p class="text-xs text-slate-600 mt-0.5">Daftar arsip slip gaji bulanan yang telah disetujui direksi dan ditransfer.</p>
          </div>
        </div>

        <table class="w-full text-left text-xs text-black">
          <thead class="bg-slate-50 text-[11px] uppercase font-bold border-b border-slate-200">
            <tr>
              <th class="px-6 py-3.5">Periode Penggajian</th>
              <th class="px-6 py-3.5">Tanggal Transfer</th>
              <th class="px-6 py-3.5">Presensi</th>
              <th class="px-6 py-3.5 text-right">Gaji Pokok</th>
              <th class="px-6 py-3.5 text-right">Take Home Pay</th>
              <th class="px-6 py-3.5 text-center">Status</th>
              <th class="px-6 py-3.5 text-right">Dokumen</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="!slips || slips.length === 0">
              <td colspan="7" class="px-6 py-10 text-center text-slate-500 font-medium">
                Belum ada slip gaji yang diterbitkan untuk akun ini.
              </td>
            </tr>

            <tr v-for="slip in slips" :key="slip.id" class="hover:bg-slate-50 transition">
              <td class="px-6 py-4 font-bold text-black">{{ slip.period?.name }}</td>
              <td class="px-6 py-4 text-slate-700">{{ slip.period?.payment_date }}</td>
              <td class="px-6 py-4 text-slate-700">
                {{ slip.attendance_count }} Hadir ({{ slip.overtime_hours_count }} Jam Lembur)
              </td>
              <td class="px-6 py-4 text-right text-slate-700">
                Rp {{ Number(slip.basic_salary_snapshot).toLocaleString('id-ID') }}
              </td>
              <td class="px-6 py-4 text-right font-bold text-black">
                Rp {{ Number(slip.take_home_pay).toLocaleString('id-ID') }}
              </td>
              <td class="px-6 py-4 text-center">
                <span class="px-2.5 py-0.5 text-[11px] font-semibold rounded bg-emerald-50 text-emerald-800 border border-emerald-200">
                  DITRANSFER
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <Link 
                  :href="`/payroll/slips/${slip.id}`"
                  class="px-3 py-1.5 bg-[#0a192f] hover:bg-[#172a46] text-white text-xs font-medium rounded transition inline-flex items-center gap-1.5"
                >
                  <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <span>Buka Slip Resmi</span>
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

