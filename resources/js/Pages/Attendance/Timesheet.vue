<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  attendances: Object,
});

const clockForm = useForm({});

const doClockIn = () => {
  clockForm.post('/attendance/clock-in');
};
</script>

<template>
  <AppLayout>
    <template #header>Presensi Kerja</template>

    <div class="space-y-6">
      <!-- Minimalist Action Bar (Putih dengan aksen biru tua) -->
      <div class="bg-white border border-slate-200 rounded-md p-6 flex items-center justify-between">
        <div>
          <h2 class="text-base font-bold text-black">Catatan Kehadiran Harian</h2>
          <p class="text-xs text-slate-600 mt-0.5">Lakukan pencatatan kehadiran sesuai jadwal kerja yang ditetapkan.</p>
        </div>
        <button 
          @click="doClockIn"
          :disabled="clockForm.processing"
          class="min-h-[40px] px-5 py-2.5 bg-[#0a192f] hover:bg-[#112240] text-white font-medium text-xs rounded-md transition inline-flex items-center gap-2 disabled:opacity-50 focus:ring-2 focus:ring-slate-900 focus:outline-hidden"
        >
          <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>{{ clockForm.processing ? 'Mencatat...' : 'Catat Presensi' }}</span>
        </button>
      </div>

      <!-- Timesheet Table -->
      <div class="bg-white rounded-md border border-slate-200 overflow-hidden">
        <div class="px-6 py-3.5 border-b border-slate-200 bg-slate-50">
          <h3 class="text-xs font-bold uppercase tracking-wider text-black">Rekapitulasi Kehadiran</h3>
        </div>
        <table class="w-full text-left text-xs text-black">
          <thead class="bg-slate-50 text-black text-[11px] uppercase font-bold border-b border-slate-200">
            <tr>
              <th class="px-6 py-3.5">Tanggal</th>
              <th class="px-6 py-3.5">Karyawan</th>
              <th class="px-6 py-3.5">Jam Masuk</th>
              <th class="px-6 py-3.5">Keterlambatan</th>
              <th class="px-6 py-3.5">Lembur</th>
              <th class="px-6 py-3.5">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="attendances.data.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-slate-500 font-medium">
                Belum ada catatan presensi pada periode ini.
              </td>
            </tr>
            <tr v-for="att in attendances.data" :key="att.id" class="hover:bg-slate-50 transition">
              <td class="px-6 py-3.5 font-medium text-black">{{ att.work_date }}</td>
              <td class="px-6 py-3.5 font-semibold text-black">{{ att.employee?.full_name }}</td>
              <td class="px-6 py-3.5 text-slate-700">{{ att.clock_in ? new Date(att.clock_in).toLocaleTimeString('id-ID') : '-' }}</td>
              <td class="px-6 py-3.5 text-rose-700 font-semibold">
                {{ att.late_minutes > 0 ? `${att.late_minutes} menit` : 'Tepat Waktu' }}
              </td>
              <td class="px-6 py-3.5 text-emerald-800 font-semibold">
                {{ att.overtime_hours > 0 ? `${att.overtime_hours} jam` : '-' }}
              </td>
              <td class="px-6 py-3.5">
                <span :class="[
                  'px-2 py-0.5 text-[11px] font-semibold rounded border',
                  att.status === 'PRESENT' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' :
                  att.status === 'LATE' ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-rose-50 text-rose-800 border-rose-200'
                ]">
                  {{ att.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
