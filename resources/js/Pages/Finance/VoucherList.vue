<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  vouchers: Object,
});
</script>

<template>
  <AppLayout>
    <template #header>Pengeluaran Kas & Bank</template>

    <div class="space-y-6">
      <div class="pb-2 border-b border-slate-200">
        <h2 class="text-lg font-bold tracking-tight text-black">Voucher Pengeluaran</h2>
        <p class="text-xs text-slate-600 mt-0.5">Daftar pencairan dana penggajian yang diterbitkan otomatis.</p>
      </div>

      <div class="bg-white rounded-md border border-slate-200 overflow-hidden">
        <table class="w-full text-left text-xs text-black">
          <thead class="bg-slate-50 text-black text-[11px] uppercase font-bold border-b border-slate-200">
            <tr>
              <th class="px-6 py-3.5">Nomor Voucher</th>
              <th class="px-6 py-3.5">Periode Penggajian</th>
              <th class="px-6 py-3.5">Tanggal Bayar</th>
              <th class="px-6 py-3.5">Total Pengeluaran</th>
              <th class="px-6 py-3.5">Status</th>
              <th class="px-6 py-3.5 text-right">Tindakan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="vouchers.data.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-slate-500 font-medium">
                Belum ada disbursement voucher diterbitkan.
              </td>
            </tr>
            <tr v-for="v in vouchers.data" :key="v.id" class="hover:bg-slate-50 transition">
              <td class="px-6 py-4 font-bold text-black">{{ v.voucher_number }}</td>
              <td class="px-6 py-4 font-semibold text-black">{{ v.payroll_period?.name }}</td>
              <td class="px-6 py-4 text-slate-600">{{ v.payment_date }}</td>
              <td class="px-6 py-4 font-bold text-black">
                Rp {{ Number(v.total_amount).toLocaleString('id-ID') }}
              </td>
              <td class="px-6 py-4">
                <span :class="[
                  'px-2.5 py-0.5 text-[11px] font-semibold rounded border',
                  v.status === 'RECONCILED' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-blue-50 text-blue-900 border-blue-200'
                ]">
                  {{ v.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <Link 
                  :href="`/finance/vouchers/${v.id}`" 
                  class="min-h-[36px] px-3 py-1.5 bg-[#0a192f] hover:bg-[#112240] text-white rounded text-xs font-medium inline-flex items-center gap-1 transition"
                >
                  Detail & Jurnal
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
