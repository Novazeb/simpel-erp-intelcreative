<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  slip: Object,
});
</script>

<template>
  <AppLayout>
    <template #header>Slip Gaji</template>

    <div class="max-w-2xl mx-auto space-y-6">
      <div class="bg-white rounded-md border border-slate-200 p-8">
        <div class="flex items-center justify-between border-b border-slate-200 pb-5 mb-5">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <img src="/images/logo-icon.png" alt="IC" class="w-7 h-7 object-contain" />
              <span class="text-[11px] font-bold text-slate-500 tracking-wider uppercase">PT INTEL CREATIVE</span>
            </div>
            <h2 class="text-xl font-bold text-black mt-0.5">SLIP GAJI KARYAWAN</h2>
            <p class="text-xs text-slate-600">{{ slip.period?.name }}</p>
          </div>
          <div class="text-right">
            <span class="px-2.5 py-1 bg-slate-100 text-black border border-slate-200 text-[11px] font-semibold rounded">
              DOKUMEN RESMI
            </span>
            <p class="text-xs text-slate-600 mt-2">Diterbitkan: {{ slip.period?.payment_date }}</p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4 text-xs bg-slate-50 p-4 rounded-md border border-slate-200 mb-6">
          <div>
            <p class="text-slate-600 font-semibold text-[11px]">Nama Karyawan</p>
            <p class="font-bold text-black text-xs mt-0.5">{{ slip.employee?.full_name }}</p>
          </div>
          <div>
            <p class="text-slate-600 font-semibold text-[11px]">NIK</p>
            <p class="font-bold text-black text-xs mt-0.5">{{ slip.employee?.nik }}</p>
          </div>
          <div>
            <p class="text-slate-600 font-semibold text-[11px]">Departemen / Posisi</p>
            <p class="font-medium text-black mt-0.5">{{ slip.employee?.department?.name }} / {{ slip.employee?.designation?.title }}</p>
          </div>
          <div>
            <p class="text-slate-600 font-semibold text-[11px]">Rekap Kehadiran</p>
            <p class="font-medium text-black mt-0.5">
              {{ slip.attendance_count }} Hadir, {{ slip.late_minutes_count }} Menit Telat, {{ slip.overtime_hours_count }} Jam Lembur
            </p>
          </div>
        </div>

        <!-- Breakdown Items -->
        <div class="space-y-3">
          <h4 class="text-[11px] font-bold uppercase tracking-wider text-black">Rincian Komponen</h4>

          <div class="border border-slate-200 rounded-md divide-y divide-slate-100 text-xs">
            <div class="flex justify-between p-3 bg-slate-50">
              <span class="font-semibold text-black">Gaji Pokok (Tercatat)</span>
              <span class="font-bold text-black">Rp {{ Number(slip.basic_salary_snapshot).toLocaleString('id-ID') }}</span>
            </div>

            <div v-for="item in slip.items" :key="item.id" class="flex justify-between p-3">
              <span :class="['font-medium', ['DEDUCTION', 'TAX'].includes(item.item_type) ? 'text-rose-900' : 'text-black']">
                {{ item.name }}
              </span>
              <span :class="['font-semibold', ['DEDUCTION', 'TAX'].includes(item.item_type) ? 'text-rose-900' : 'text-black']">
                {{ ['DEDUCTION', 'TAX'].includes(item.item_type) ? '-' : '+' }} Rp {{ Number(item.amount).toLocaleString('id-ID') }}
              </span>
            </div>
          </div>
        </div>

        <!-- Take Home Pay Total -->
        <div class="mt-6 pt-5 border-t border-slate-200 flex items-center justify-between bg-white border rounded-md p-5 text-black">
          <div>
            <p class="text-[11px] uppercase text-slate-600 font-bold tracking-wider">Total Bersih Diterima</p>
            <p class="text-xl font-bold mt-0.5 text-black">Rp {{ Number(slip.take_home_pay).toLocaleString('id-ID') }}</p>
          </div>
          <div class="text-right text-xs text-slate-600">
            Rekening Tujuan: <strong class="text-black">{{ slip.employee?.bank_name }} / {{ slip.employee?.bank_account_number }}</strong>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
