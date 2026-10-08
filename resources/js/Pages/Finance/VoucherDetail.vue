<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { sumBy, toBig, formatCurrency } from '@/Utils/money';

const props = defineProps({
  voucher: Object,
});

const totalDebit = computed(() => {
  return sumBy(props.voucher.journal_entry?.items || [], 'debit');
});

const totalCredit = computed(() => {
  return sumBy(props.voucher.journal_entry?.items || [], 'credit');
});

const isBalanced = computed(() => {
  return toBig(totalDebit.value).minus(toBig(totalCredit.value)).abs().lt(0.01);
});

const doReconcile = () => {
  router.post(`/finance/vouchers/${props.voucher.id}/reconcile`);
};
</script>

<template>
  <AppLayout>
    <template #header>Voucher Pengeluaran & Jurnal</template>

    <div class="max-w-4xl mx-auto space-y-6">
      <div class="bg-white rounded-md border border-slate-200 p-8">
        <div class="flex items-center justify-between border-b border-slate-200 pb-5">
          <div>
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Disbursement Voucher</div>
            <h2 class="text-xl font-bold text-black mt-0.5">{{ voucher.voucher_number }}</h2>
          </div>
          <div class="flex items-center gap-2">
            <span :class="[
              'px-2.5 py-1 text-xs font-semibold rounded border',
              voucher.status === 'RECONCILED' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-blue-50 text-blue-900 border-blue-200'
            ]">
              {{ voucher.status }}
            </span>
            <a 
              :href="`/finance/vouchers/${voucher.id}/export/bca`" 
              class="min-h-[38px] px-3.5 py-1.5 border border-slate-300 hover:bg-slate-50 text-black text-xs font-medium rounded transition inline-flex items-center gap-1.5"
            >
              <svg class="w-3.5 h-3.5 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              KlikBCA
            </a>
            <a 
              :href="`/finance/vouchers/${voucher.id}/export/mandiri`" 
              class="min-h-[38px] px-3.5 py-1.5 border border-slate-300 hover:bg-slate-50 text-black text-xs font-medium rounded transition inline-flex items-center gap-1.5"
            >
              <svg class="w-3.5 h-3.5 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              Mandiri MCM
            </a>
            <button 
              v-if="voucher.status !== 'RECONCILED' && $page.props.auth?.user?.permissions?.includes('finance.voucher-release')"
              @click="doReconcile"
              class="min-h-[38px] px-4 py-1.5 bg-[#0a192f] hover:bg-[#112240] text-white text-xs font-medium rounded transition inline-flex items-center gap-1.5"
            >
              <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
              Rekonsiliasi
            </button>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4 py-4 border-b border-slate-200 text-xs">
          <div>
            <span class="text-slate-600">Tanggal Bayar:</span>
            <span class="ml-2 font-bold text-black">{{ voucher.payment_date }}</span>
          </div>
          <div class="text-right">
            <span class="text-slate-600">Total Pencairan:</span>
            <span class="ml-2 font-black text-black text-base">
              Rp {{ Number(voucher.total_amount).toLocaleString('id-ID') }}
            </span>
          </div>
        </div>

        <!-- Double Entry Journal Table -->
        <div class="mt-6 space-y-3">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-bold text-black text-sm">Pembukuan Berpasangan (Auto-Journal)</h3>
              <p class="text-xs text-slate-600 mt-0.5">
                Bukti: {{ voucher.journal_entry?.entry_number }} ({{ voucher.journal_entry?.description }})
              </p>
            </div>
            <div>
              <span v-if="isBalanced" class="px-2.5 py-1 bg-slate-100 text-black border border-slate-200 text-xs font-medium rounded inline-flex items-center gap-1">
                <svg class="w-3 h-3 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Jurnal Seimbang
              </span>
              <span v-else class="px-2.5 py-1 bg-rose-50 text-rose-800 border border-rose-200 text-xs font-medium rounded">
                Tidak Seimbang
              </span>
            </div>
          </div>

          <div class="border border-slate-200 rounded-md overflow-hidden">
            <table class="w-full text-left text-xs text-black">
              <thead class="bg-slate-50 text-black text-[11px] font-bold uppercase border-b border-slate-200">
                <tr>
                  <th class="px-4 py-3">Kode Akun</th>
                  <th class="px-4 py-3">Nama Akun</th>
                  <th class="px-4 py-3 text-right">Debit (IDR)</th>
                  <th class="px-4 py-3 text-right">Kredit (IDR)</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="(item, idx) in voucher.journal_entry?.items" :key="idx" class="hover:bg-slate-50">
                  <td class="px-4 py-3 font-semibold text-black">{{ item.account_code }}</td>
                  <td class="px-4 py-3 font-medium text-black">{{ item.name }}</td>
                  <td class="px-4 py-3 text-right text-black">
                    {{ toBig(item.debit).gt(0) ? formatCurrency(item.debit).replace('Rp ', '') : '-' }}
                  </td>
                  <td class="px-4 py-3 text-right text-black">
                    {{ toBig(item.credit).gt(0) ? formatCurrency(item.credit).replace('Rp ', '') : '-' }}
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-slate-50 text-black font-bold border-t border-slate-200">
                <tr>
                  <td colspan="2" class="px-4 py-3 uppercase text-xs">Total Keseimbangan</td>
                  <td class="px-4 py-3 text-right text-black">{{ formatCurrency(totalDebit) }}</td>
                  <td class="px-4 py-3 text-right text-black">{{ formatCurrency(totalCredit) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
