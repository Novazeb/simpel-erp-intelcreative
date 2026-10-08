<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  accounts: Array,
  recent_journals: Array,
});
</script>

<template>
  <AppLayout>
    <template #header>Buku Besar & Bagan Akun (Chart of Accounts)</template>

    <div class="space-y-6">
      <div class="pb-2 border-b border-slate-200">
        <h2 class="text-lg font-bold text-black">Bagan Akun (Chart of Accounts)</h2>
        <p class="text-xs text-slate-600 mt-0.5">Struktur klasifikasi akun aset, kewajiban, ekuitas, dan beban operasional.</p>
      </div>

      <!-- COA Table -->
      <div class="bg-white border border-slate-200 rounded-md overflow-hidden">
        <table class="w-full text-left text-xs text-black">
          <thead class="bg-slate-50 text-[11px] uppercase font-bold border-b border-slate-200">
            <tr>
              <th class="px-6 py-3.5">Kode Akun</th>
              <th class="px-6 py-3.5">Nama Akun</th>
              <th class="px-6 py-3.5">Klasifikasi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="acc in accounts" :key="acc.id" class="hover:bg-slate-50">
              <td class="px-6 py-3.5 font-bold text-black">{{ acc.code }}</td>
              <td class="px-6 py-3.5 font-semibold text-black">{{ acc.name }}</td>
              <td class="px-6 py-3.5">
                <span class="px-2 py-0.5 text-[11px] font-semibold rounded bg-slate-100 text-slate-800 border border-slate-200">
                  {{ acc.type }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Recent Journal Entries Section -->
      <div class="pt-4">
        <h3 class="text-base font-bold text-black mb-3">Entri Jurnal Otomatis Terkini</h3>
        <div class="bg-white border border-slate-200 rounded-md overflow-hidden">
          <table class="w-full text-left text-xs text-black">
            <thead class="bg-slate-50 text-[11px] uppercase font-bold border-b border-slate-200">
              <tr>
                <th class="px-6 py-3.5">Nomor Bukti Jurnal</th>
                <th class="px-6 py-3.5">Tanggal</th>
                <th class="px-6 py-3.5">Keterangan</th>
                <th class="px-6 py-3.5">Total Baris</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="recent_journals.length === 0">
                <td colspan="4" class="px-6 py-6 text-center text-slate-500 font-medium">
                  Belum ada entri jurnal dalam sistem.
                </td>
              </tr>
              <tr v-for="j in recent_journals" :key="j.id" class="hover:bg-slate-50">
                <td class="px-6 py-3.5 font-bold text-black">{{ j.entry_number }}</td>
                <td class="px-6 py-3.5 text-slate-700">{{ j.transaction_date }}</td>
                <td class="px-6 py-3.5 text-black">{{ j.description }}</td>
                <td class="px-6 py-3.5 text-slate-700">{{ j.items?.length || 0 }} baris</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

