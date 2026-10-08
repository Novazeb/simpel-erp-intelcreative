<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  metrics: Object,
});

const page = usePage();
const permissions = computed(() => page.props.auth?.user?.permissions || []);
const can = (perm) => permissions.value.includes(perm);
</script>

<template>
  <AppLayout>
    <template #header>Dasbor Perusahaan</template>

    <div class="space-y-6">
      <div class="pb-2 border-b border-slate-200">
        <h2 class="text-lg font-bold text-black">Indikator Kinerja Utama (KPI)</h2>
        <p class="text-xs text-slate-600 mt-0.5">Ringkasan status personalia, kehadiran hari ini, dan likuiditas pencairan dana.</p>
      </div>

      <!-- KPI Stat Cards (White/Slate Minimalist) -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200 rounded-md p-5">
          <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Karyawan</p>
          <p class="text-2xl font-bold text-black mt-1">{{ metrics.employee_count }}</p>
          <p class="text-[11px] text-slate-600 mt-1">Personel terdaftar aktif</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-md p-5">
          <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Presensi Hari Ini</p>
          <p class="text-2xl font-bold text-black mt-1">{{ metrics.today_attendance_count }}</p>
          <p class="text-[11px] text-slate-600 mt-1">Log presensi tercatat</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-md p-5">
          <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Siklus Payroll</p>
          <p class="text-base font-bold text-black mt-2">{{ metrics.active_payroll_status }}</p>
          <p class="text-[11px] text-slate-600 mt-1">Status periode terkini</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-md p-5">
          <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Pencairan Kas</p>
          <p class="text-lg font-bold text-black mt-2">Rp {{ Number(metrics.total_disbursement).toLocaleString('id-ID') }}</p>
          <p class="text-[11px] text-slate-600 mt-1">Akumulasi voucher payroll</p>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="bg-white border border-slate-200 rounded-md p-6">
        <h3 class="text-sm font-bold text-black mb-3">Akses Pintas Modul Korporat</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
          <Link v-if="can('leave.approve') || can('payroll.approve')" href="/approvals" class="p-3 border border-slate-200 rounded hover:bg-slate-50 transition block">
            <p class="font-bold text-black">Pusat Persetujuan</p>
            <p class="text-slate-600 mt-0.5">Validasi izin, lembur, dan pencairan biaya.</p>
          </Link>
          <Link v-else href="/attendance" class="p-3 border border-slate-200 rounded hover:bg-slate-50 transition block">
            <p class="font-bold text-black">Presensi & Kehadiran</p>
            <p class="text-slate-600 mt-0.5">Pencatatan jam kerja dan absensi mandiri.</p>
          </Link>

          <Link href="/operations/projects" class="p-3 border border-slate-200 rounded hover:bg-slate-50 transition block">
            <p class="font-bold text-black">Proyek & Penugasan</p>
            <p class="text-slate-600 mt-0.5">Pantau realisasi deliverable & budget klien.</p>
          </Link>

          <Link v-if="can('finance.journal-view')" href="/finance/ledger" class="p-3 border border-slate-200 rounded hover:bg-slate-50 transition block">
            <p class="font-bold text-black">Buku Besar (General Ledger)</p>
            <p class="text-slate-600 mt-0.5">Penjurnalan otomatis & bagan akun korporat.</p>
          </Link>
          <Link v-else href="/operations/timesheets" class="p-3 border border-slate-200 rounded hover:bg-slate-50 transition block">
            <p class="font-bold text-black">Lembar Kerja (Timesheet)</p>
            <p class="text-slate-600 mt-0.5">Laporan aktivitas kerja dan jam penugasan.</p>
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

