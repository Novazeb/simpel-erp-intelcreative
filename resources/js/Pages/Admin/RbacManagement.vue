<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  roles: Array,
  permissions: Array,
});
</script>

<template>
  <AppLayout>
    <template #header>Tata Kelola: Manajemen Akses & Matriks Peran (RBAC)</template>

    <div class="space-y-6">
      <div class="pb-2 border-b border-slate-200">
        <h2 class="text-lg font-bold text-black">Matriks Otorisasi Pengguna</h2>
        <p class="text-xs text-slate-600 mt-0.5">Pemetaan visual izin akses per peran pengguna korporat.</p>
      </div>

      <div class="bg-white border border-slate-200 rounded-md overflow-x-auto">
        <table class="w-full text-left text-xs text-black">
          <thead class="bg-slate-50 text-[11px] uppercase font-bold border-b border-slate-200">
            <tr>
              <th class="px-6 py-3.5">Nama Izin Akses (Permission)</th>
              <th v-for="role in roles" :key="role.id" class="px-6 py-3.5 text-center">
                {{ role.name }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="perm in permissions" :key="perm.id" class="hover:bg-slate-50">
              <td class="px-6 py-3 font-medium text-black">{{ perm.name }}</td>
              <td v-for="role in roles" :key="role.id" class="px-6 py-3 text-center">
                <span 
                  v-if="role.permissions.some(p => p.id === perm.id)" 
                  class="text-emerald-700 font-bold"
                >
                  ✓
                </span>
                <span v-else class="text-slate-300">
                  −
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

