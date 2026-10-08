<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import Big from 'big.js';
import { formatCurrency } from '@/Utils/money';

// Contoh kalkulasi subtotal yang aman dari kebocoran desimal (umpanbalik4.md poin 3)
const calculateSubtotal = (items) => {
  return items.reduce((total, item) => {
    // Konversi string/number ke objek Big sebelum operasi matematika
    const price = new Big(item.unit_price || 0);
    const qty = new Big(item.quantity || 0);

    return total.plus(price.times(qty));
  }, new Big(0)).toFixed(2); // Kembalikan ke format desimal presisi 2
};

const items = ref([
  { description: 'UI/UX Design System & Prototyping', unit_price: '15000000.00', quantity: 1 },
  { description: 'Fullstack Web Application Development', unit_price: '25000000.00', quantity: 1 },
  { description: 'PostgreSQL Database Optimization & Indexing', unit_price: '8500000.00', quantity: 1 },
]);

const addItem = () => {
  items.value.push({ description: '', unit_price: '0.00', quantity: 1 });
};

const removeItem = (index) => {
  if (items.value.length > 1) {
    items.value.splice(index, 1);
  }
};

const subtotal = computed(() => calculateSubtotal(items.value));

const taxAmount = computed(() => {
  return new Big(subtotal.value).times(new Big('0.11')).toFixed(2); // PPN 11%
});

const grandTotal = computed(() => {
  return new Big(subtotal.value).plus(new Big(taxAmount.value)).toFixed(2);
});
</script>

<template>
  <AppLayout>
    <template #header>Kalkulator Penawaran (Quotation Calculator)</template>

    <div class="max-w-4xl mx-auto space-y-6">
      <div class="bg-white rounded-md border border-slate-200 p-8">
        <div class="flex items-center justify-between border-b border-slate-200 pb-5 mb-6">
          <div>
            <h2 class="text-base font-bold text-black">Formulir Penawaran Biaya Proyek</h2>
            <p class="text-xs text-slate-600 mt-0.5">
              Kalkulasi matematis sisi klien aman dengan presisi desimal mutlak (Big.js).
            </p>
          </div>
          <button
            @click="addItem"
            type="button"
            class="min-h-[38px] px-3.5 py-1.5 bg-[#0a192f] hover:bg-[#172a46] text-white text-xs font-medium rounded transition inline-flex items-center gap-1.5 focus:ring-2 focus:ring-slate-900 focus:outline-hidden"
          >
            <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            <span>Tambah Item</span>
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-black">
            <thead class="bg-slate-50 text-[11px] uppercase font-bold border-b border-slate-200">
              <tr>
                <th class="px-4 py-3">Uraian Pekerjaan / Layanan</th>
                <th class="px-4 py-3 text-right w-36">Harga Satuan (Rp)</th>
                <th class="px-4 py-3 text-center w-20">Volume</th>
                <th class="px-4 py-3 text-right w-40">Subtotal (Rp)</th>
                <th class="px-2 py-3 text-center w-12">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(item, idx) in items" :key="idx" class="hover:bg-slate-50">
                <td class="px-4 py-3">
                  <input
                    v-model="item.description"
                    type="text"
                    placeholder="Nama item deliverable..."
                    class="w-full text-xs border border-slate-200 rounded px-2.5 py-1.5 text-black focus:ring-1 focus:ring-slate-900 focus:outline-hidden"
                  />
                </td>
                <td class="px-4 py-3 text-right">
                  <input
                    v-model="item.unit_price"
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full text-xs text-right border border-slate-200 rounded px-2.5 py-1.5 text-black focus:ring-1 focus:ring-slate-900 focus:outline-hidden"
                  />
                </td>
                <td class="px-4 py-3 text-center">
                  <input
                    v-model="item.quantity"
                    type="number"
                    min="1"
                    class="w-full text-xs text-center border border-slate-200 rounded px-2.5 py-1.5 text-black focus:ring-1 focus:ring-slate-900 focus:outline-hidden"
                  />
                </td>
                <td class="px-4 py-3 text-right font-bold text-black">
                  {{ formatCurrency(new Big(item.unit_price || 0).times(new Big(item.quantity || 0)).toFixed(2)) }}
                </td>
                <td class="px-2 py-3 text-center">
                  <button
                    @click="removeItem(idx)"
                    :disabled="items.length <= 1"
                    type="button"
                    class="text-rose-600 hover:text-rose-800 disabled:opacity-30 p-1"
                    title="Hapus baris"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-slate-50 border-t border-slate-200 text-black">
              <tr>
                <td colspan="3" class="px-4 py-2.5 font-semibold text-right text-slate-700">Subtotal:</td>
                <td class="px-4 py-2.5 text-right font-bold text-black">{{ formatCurrency(subtotal) }}</td>
                <td></td>
              </tr>
              <tr>
                <td colspan="3" class="px-4 py-2.5 font-semibold text-right text-slate-700">PPN 11%:</td>
                <td class="px-4 py-2.5 text-right font-semibold text-slate-800">{{ formatCurrency(taxAmount) }}</td>
                <td></td>
              </tr>
              <tr class="border-t border-slate-200 font-bold bg-slate-100">
                <td colspan="3" class="px-4 py-3 text-right uppercase text-xs text-black">Total Penawaran:</td>
                <td class="px-4 py-3 text-right text-sm text-black">{{ formatCurrency(grandTotal) }}</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

