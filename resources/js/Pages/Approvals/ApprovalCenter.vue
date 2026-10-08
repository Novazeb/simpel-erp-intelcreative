<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  pending_leaves: Array,
  calculated_payrolls: Array,
  draft_vouchers: Array,
});
</script>

<template>
  <AppLayout>
    <template #header>Pusat Persetujuan Terpadu (Unified Approval Center)</template>

    <div class="space-y-6">
      <div class="pb-2 border-b border-slate-200">
        <h2 class="text-lg font-bold text-black">Antrean Persetujuan Dokumen</h2>
        <p class="text-xs text-slate-600 mt-0.5">Validasi permohonan cuti, pengesahan batch payroll, dan otorisasi voucher dalam satu pintu.</p>
      </div>

      <!-- Section 1: Pengajuan Cuti Pending -->
      <div class="bg-white border border-slate-200 rounded-md overflow-hidden">
        <div class="px-6 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
          <h3 class="text-xs font-bold uppercase tracking-wider text-black">Permohonan Cuti & Izin Karyawan</h3>
          <span class="text-xs font-semibold text-slate-700">{{ pending_leaves.length }} antrean</span>
        </div>
        <table class="w-full text-left text-xs text-black">
          <thead class="bg-slate-50 text-[11px] uppercase font-bold border-b border-slate-200">
            <tr>
              <th class="px-6 py-3">Karyawan</th>
              <th class="px-6 py-3">Jenis Cuti</th>
              <th class="px-6 py-3">Rentang Tanggal</th>
              <th class="px-6 py-3">Alasan</th>
              <th class="px-6 py-3">Tahap Persetujuan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="pending_leaves.length === 0">
              <td colspan="5" class="px-6 py-6 text-center text-slate-500 font-medium">
                Tidak ada permohonan cuti yang menunggu persetujuan.
              </td>
            </tr>
            <tr v-for="leave in pending_leaves" :key="leave.id" class="hover:bg-slate-50">
              <td class="px-6 py-3 font-semibold text-black">{{ leave.employee?.full_name }}</td>
              <td class="px-6 py-3">{{ leave.leave_type?.name }}</td>
              <td class="px-6 py-3 text-slate-700">{{ leave.start_date }} s/d {{ leave.end_date }} ({{ leave.total_days }} hari)</td>
              <td class="px-6 py-3 text-slate-600">{{ leave.reason }}</td>
              <td class="px-6 py-3">
                <span class="px-2 py-0.5 text-[11px] font-semibold rounded bg-amber-50 text-amber-900 border border-amber-200">
                  {{ leave.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Section 2: Batch Payroll Menunggu Otorisasi Direksi -->
      <div class="bg-white border border-slate-200 rounded-md overflow-hidden">
        <div class="px-6 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
          <h3 class="text-xs font-bold uppercase tracking-wider text-black">Batch Penggajian Menunggu Persetujuan Eksekutif</h3>
          <span class="text-xs font-semibold text-slate-700">{{ calculated_payrolls.length }} antrean</span>
        </div>
        <table class="w-full text-left text-xs text-black">
          <thead class="bg-slate-50 text-[11px] uppercase font-bold border-b border-slate-200">
            <tr>
              <th class="px-6 py-3">Periode</th>
              <th class="px-6 py-3">Tanggal Cut-off</th>
              <th class="px-6 py-3">Tanggal Bayar</th>
              <th class="px-6 py-3">Status</th>
              <th class="px-6 py-3 text-right">Tindakan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="calculated_payrolls.length === 0">
              <td colspan="5" class="px-6 py-6 text-center text-slate-500 font-medium">
                Tidak ada batch gaji yang menunggu pengesahan.
              </td>
            </tr>
            <tr v-for="period in calculated_payrolls" :key="period.id" class="hover:bg-slate-50">
              <td class="px-6 py-3 font-bold text-black">{{ period.name }}</td>
              <td class="px-6 py-3 text-slate-700">{{ period.cutoff_date }}</td>
              <td class="px-6 py-3 text-slate-700">{{ period.payment_date }}</td>
              <td class="px-6 py-3">
                <span class="px-2 py-0.5 text-[11px] font-semibold rounded bg-blue-50 text-blue-900 border border-blue-200">
                  {{ period.status }}
                </span>
              </td>
              <td class="px-6 py-3 text-right">
                <Link href="/payroll/periods" class="px-3 py-1 bg-[#0a192f] text-white rounded text-xs font-medium hover:bg-[#112240]">
                  Verifikasi di Modul Payroll
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

