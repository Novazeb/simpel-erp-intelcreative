<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  assets: Array,
});
</script>

<template>
  <AppLayout>
    <template #header>Inventaris Aset Kerja & Perangkat</template>

    <div class="space-y-6">
      <div class="pb-2 border-b border-slate-200">
        <h2 class="text-lg font-bold text-black">Aset Tetap & Perangkat Produksi</h2>
        <p class="text-xs text-slate-600 mt-0.5">Pencatatan perangkat keras berkinerja tinggi, kamera, dan penanggung jawab unit.</p>
      </div>

      <div class="bg-white border border-slate-200 rounded-md overflow-hidden">
        <table class="w-full text-left text-xs text-black">
          <thead class="bg-slate-50 text-[11px] uppercase font-bold border-b border-slate-200">
            <tr>
              <th class="px-6 py-3.5">Kode Aset</th>
              <th class="px-6 py-3.5">Nama Perangkat</th>
              <th class="px-6 py-3.5">Nomor Seri</th>
              <th class="px-6 py-3.5">Pemegang Unit</th>
              <th class="px-6 py-3.5">Nilai Perolehan</th>
              <th class="px-6 py-3.5">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="ast in assets" :key="ast.id" class="hover:bg-slate-50">
              <td class="px-6 py-4 font-bold text-black">{{ ast.asset_code }}</td>
              <td class="px-6 py-4 font-semibold text-black">{{ ast.name }}</td>
              <td class="px-6 py-4 text-slate-600">{{ ast.serial_number }}</td>
              <td class="px-6 py-4 text-black">{{ ast.assigned_to }}</td>
              <td class="px-6 py-4 font-bold text-black">Rp {{ Number(ast.acquisition_cost).toLocaleString('id-ID') }}</td>
              <td class="px-6 py-4">
                <span class="px-2.5 py-0.5 text-[11px] font-semibold rounded bg-emerald-50 text-emerald-800 border border-emerald-200">
                  {{ ast.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

