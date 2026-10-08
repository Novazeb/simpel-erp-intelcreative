<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  logs: Array,
});
</script>

<template>
  <AppLayout>
    <template #header>Tata Kelola: Log Jejak Audit (Audit Trail)</template>

    <div class="space-y-6">
      <div class="pb-2 border-b border-slate-200">
        <h2 class="text-lg font-bold text-black">Rekaman Mutasi Data & Keamanan</h2>
        <p class="text-xs text-slate-600 mt-0.5">Catatan temporal perubahan kompensasi, rekening, IP address, dan identitas pengubah.</p>
      </div>

      <div class="bg-white border border-slate-200 rounded-md overflow-hidden">
        <table class="w-full text-left text-xs text-black">
          <thead class="bg-slate-50 text-[11px] uppercase font-bold border-b border-slate-200">
            <tr>
              <th class="px-6 py-3.5">Waktu</th>
              <th class="px-6 py-3.5">Entitas</th>
              <th class="px-6 py-3.5">Aksi</th>
              <th class="px-6 py-3.5">Field Berubah</th>
              <th class="px-6 py-3.5">Alamat IP</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="logs.length === 0">
              <td colspan="5" class="px-6 py-10 text-center text-slate-500 font-medium">
                Belum ada rekaman mutasi audit trail saat ini.
              </td>
            </tr>
            <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-50">
              <td class="px-6 py-3.5 text-slate-600">{{ log.created_at }}</td>
              <td class="px-6 py-3.5 font-bold text-black">{{ log.auditable_type }}</td>
              <td class="px-6 py-3.5">
                <span class="px-2 py-0.5 text-[11px] font-semibold rounded bg-slate-100 text-slate-800">
                  {{ log.event }}
                </span>
              </td>
              <td class="px-6 py-3.5 text-black">{{ log.field_changed || '-' }}</td>
              <td class="px-6 py-3.5 text-slate-600">{{ log.ip_address || '127.0.0.1' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

