<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import TimesheetModal from '@/Components/Operations/TimesheetModal.vue';
import { ref, computed } from 'vue';
import { sumBy } from '@/Utils/money';

const props = defineProps({
  timesheets: Array,
  projects: Array,
  tasks: Array,
  currentUserEmail: String,
  isManager: Boolean,
});

const showModal = ref(false);
const activeTab = ref(props.isManager ? 'all' : 'my');

const filteredTimesheets = computed(() => {
  if (activeTab.value === 'my') {
    return (props.timesheets || []).filter(ts => ts.email === props.currentUserEmail);
  }
  return props.timesheets || [];
});

const totalHours = computed(() => {
  return sumBy(filteredTimesheets.value, 'billable_hours');
});
</script>

<template>
  <AppLayout>
    <template #header>Log Jam Proyek (Timesheet)</template>

    <div class="space-y-6">
      <div class="pb-2 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h2 class="text-lg font-bold text-black">Pelacakan Durasi & Billable Hours</h2>
          <p class="text-xs text-slate-600 mt-0.5">Alokasi jam kerja langsung ke proyek klien untuk kalkulasi biaya tenaga kerja langsung.</p>
        </div>
        <button
          @click="showModal = true"
          class="px-4 py-2 bg-[#0a192f] hover:bg-[#112240] text-white text-xs font-semibold rounded-md transition shadow-xs self-start md:self-auto"
        >
          + Catat Jam Kerja
        </button>
      </div>

      <!-- Tab Filter & Ringkasan Jam -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="inline-flex rounded-md border border-slate-200 p-0.5 bg-slate-100 text-xs">
          <button 
            @click="activeTab = 'my'"
            :class="[
              'px-4 py-2 font-semibold rounded transition text-xs',
              activeTab === 'my' 
                ? 'bg-[#0a192f] text-white shadow-xs' 
                : 'text-slate-600 hover:text-black'
            ]"
          >
            Timesheet Saya
          </button>
          <button 
            @click="activeTab = 'all'"
            :class="[
              'px-4 py-2 font-semibold rounded transition text-xs',
              activeTab === 'all' 
                ? 'bg-[#0a192f] text-white shadow-xs' 
                : 'text-slate-600 hover:text-black'
            ]"
          >
            Semua Tim
          </button>
        </div>

        <div class="flex items-center gap-3">
          <div class="px-4 py-2 bg-white border border-slate-200 rounded text-xs flex items-center gap-2">
            <span class="text-slate-500 font-medium">Total Terhitung:</span>
            <span class="font-bold text-black text-sm">{{ totalHours.toFixed(1) }} Jam</span>
          </div>
          <div class="px-4 py-2 bg-white border border-slate-200 rounded text-xs flex items-center gap-2">
            <span class="text-slate-500 font-medium">Total Aktivitas:</span>
            <span class="font-bold text-black text-sm">{{ filteredTimesheets.length }} Log</span>
          </div>
        </div>
      </div>

      <!-- Tabel Timesheet -->
      <div class="bg-white border border-slate-200 rounded-md overflow-hidden">
        <table class="w-full text-left text-xs text-black">
          <thead class="bg-slate-50 text-[11px] uppercase font-bold border-b border-slate-200">
            <tr>
              <th class="px-6 py-3.5">Tanggal</th>
              <th class="px-6 py-3.5">Personel</th>
              <th class="px-6 py-3.5">Proyek</th>
              <th class="px-6 py-3.5">Rincian Aktivitas</th>
              <th class="px-6 py-3.5 text-right">Jam Terbebankan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="filteredTimesheets.length === 0">
              <td colspan="5" class="px-6 py-10 text-center text-slate-500 font-medium">
                Belum ada entri timesheet yang tercatat pada filter ini.
              </td>
            </tr>

            <tr v-for="ts in filteredTimesheets" :key="ts.id" class="hover:bg-slate-50 transition">
              <td class="px-6 py-4 text-slate-700">{{ ts.date }}</td>
              <td class="px-6 py-4">
                <span class="font-bold text-black">{{ ts.employee_name }}</span>
                <span v-if="ts.email === currentUserEmail" class="ml-2 px-1.5 py-0.2 bg-blue-50 text-blue-900 border border-blue-200 text-[10px] font-semibold rounded">
                  Anda
                </span>
              </td>
              <td class="px-6 py-4 font-medium text-black">{{ ts.project_name }}</td>
              <td class="px-6 py-4 text-slate-600">{{ ts.task }}</td>
              <td class="px-6 py-4 text-right font-bold text-black">{{ Number(ts.billable_hours).toFixed(1) }} Jam</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Input Timesheet -->
    <TimesheetModal
      :show="showModal"
      :projects="projects"
      :tasks="tasks"
      @close="showModal = false"
    />
  </AppLayout>
</template>
