<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  projects: Array,
  my_assignments: Array,
});
</script>

<template>
  <AppLayout>
    <template #header>Proyek & Penugasan Klien</template>

    <div class="space-y-6">
      <div class="pb-2 border-b border-slate-200">
        <h2 class="text-lg font-bold text-black">Portofolio Proyek Kreatif & Teknologi</h2>
        <p class="text-xs text-slate-600 mt-0.5">Pemantauan milestone, anggaran belanja, dan rincian penugasan personel.</p>
      </div>

      <!-- Kartu Sorotan Penugasan Pribadi (Jika Pengguna Memiliki Penugasan Aktif) -->
      <div v-if="my_assignments && my_assignments.length > 0" class="space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-xs font-bold uppercase tracking-wider text-black">Penugasan Kerja Aktif Anda</h3>
          <span class="px-2 py-0.5 text-[10px] font-bold bg-[#0a192f] text-white rounded">
            {{ my_assignments.length }} Penugasan Terjadwal
          </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div 
            v-for="assign in my_assignments" 
            :key="assign.project_id"
            class="bg-white border-2 border-[#0a192f] rounded-md p-5 space-y-4 shadow-xs"
          >
            <div class="flex items-start justify-between">
              <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ assign.client }}</span>
                <h4 class="text-sm font-bold text-black mt-0.5">{{ assign.project_name }}</h4>
                <p class="text-xs font-semibold text-slate-700 mt-1">Peran: {{ assign.role }}</p>
              </div>
              <span class="px-2.5 py-0.5 text-[11px] font-semibold rounded bg-blue-50 text-blue-900 border border-blue-200">
                {{ assign.project_status }}
              </span>
            </div>

            <!-- Detail Tugas & Progres -->
            <div class="p-3 bg-slate-50 border border-slate-200 rounded text-xs space-y-2">
              <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase">Tugas Utama yang Diberikan</p>
                <p class="text-xs font-medium text-black mt-0.5">{{ assign.task }}</p>
              </div>
              <div class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-200 text-[11px]">
                <div>
                  <span class="text-slate-500">Tenggat Waktu:</span>
                  <strong class="text-black ml-1">{{ assign.deadline }}</strong>
                </div>
                <div>
                  <span class="text-slate-500">Milestone:</span>
                  <strong class="text-black ml-1">{{ assign.project_milestone }}</strong>
                </div>
              </div>
            </div>

            <!-- Jam Alokasi vs Jam Terpakai -->
            <div class="flex items-center justify-between pt-1">
              <div class="text-xs">
                <span class="text-slate-600">Alokasi Waktu:</span>
                <span class="font-bold text-black ml-1">{{ assign.logged_hours }} / {{ assign.allocated_hours }} Jam</span>
              </div>
              <Link 
                href="/operations/timesheets"
                class="px-3 py-1.5 bg-[#0a192f] hover:bg-[#172a46] text-white text-xs font-medium rounded transition inline-flex items-center gap-1.5"
              >
                <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Lembar Kerja (Timesheet)</span>
              </Link>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabel Seluruh Portofolio Proyek -->
      <div class="bg-white border border-slate-200 rounded-md overflow-hidden">
        <div class="px-6 py-3.5 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
          <h3 class="text-xs font-bold uppercase tracking-wider text-black">Daftar Proyek Perusahaan</h3>
          <span class="text-xs text-slate-600">Total: {{ projects.length }} Proyek</span>
        </div>

        <table class="w-full text-left text-xs text-black">
          <thead class="bg-slate-50 text-[11px] uppercase font-bold border-b border-slate-200">
            <tr>
              <th class="px-6 py-3.5">Nama Proyek</th>
              <th class="px-6 py-3.5">Klien</th>
              <th class="px-6 py-3.5">Nilai Kontrak</th>
              <th class="px-6 py-3.5">Tim Penugasan</th>
              <th class="px-6 py-3.5">Milestone</th>
              <th class="px-6 py-3.5">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="proj in projects" :key="proj.id" class="hover:bg-slate-50 transition">
              <td class="px-6 py-4 font-bold text-black">{{ proj.name }}</td>
              <td class="px-6 py-4 text-slate-700">{{ proj.client }}</td>
              <td class="px-6 py-4 font-bold text-black">Rp {{ Number(proj.budget).toLocaleString('id-ID') }}</td>
              <td class="px-6 py-4">
                <div class="space-y-1">
                  <div 
                    v-for="member in proj.assignments" 
                    :key="member.email"
                    class="text-[11px] text-slate-700 flex items-center gap-1.5"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-[#0a192f]"></span>
                    <strong class="text-black">{{ member.employee_name }}</strong>
                    <span class="text-slate-500">({{ member.role }})</span>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-slate-600">{{ proj.milestone }}</td>
              <td class="px-6 py-4">
                <span class="px-2.5 py-0.5 text-[11px] font-semibold rounded bg-blue-50 text-blue-900 border border-blue-200">
                  {{ proj.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
