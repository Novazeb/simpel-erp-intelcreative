<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  show: Boolean,
  projects: Array,
  tasks: Array,
});

const emit = defineEmits(['close']);

const form = useForm({
  project_id: '',
  task_id: '',
  work_date: new Date().toISOString().substring(0, 10),
  billable_hours: 8.0,
  task_description: '',
});

const submit = () => {
  form.post('/operations/timesheets', {
    onSuccess: () => {
      form.reset();
      emit('close');
    },
  });
};
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs">
    <div class="bg-white rounded-lg shadow-xl border border-slate-200 w-full max-w-lg p-6">
      <div class="flex justify-between items-center pb-3 border-b border-slate-100 mb-4">
        <h3 class="text-base font-bold text-slate-900">Catat Log Jam Kerja Proyek</h3>
        <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
      </div>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Proyek Penugasan</label>
          <select v-model="form.project_id" class="w-full text-sm border-slate-300 rounded-md shadow-xs focus:border-[#0a192f] focus:ring-[#0a192f]" required>
            <option value="">-- Pilih Proyek --</option>
            <option v-for="proj in (projects || [])" :key="proj.id" :value="proj.id">{{ proj.project_name }}</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Pengerjaan</label>
          <input type="date" v-model="form.work_date" class="w-full text-sm border-slate-300 rounded-md shadow-xs focus:border-[#0a192f] focus:ring-[#0a192f]" required />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Durasi Terbebankan (Jam)</label>
          <input type="number" step="0.5" min="0.5" max="16.0" v-model="form.billable_hours" class="w-full text-sm border-slate-300 rounded-md shadow-xs focus:border-[#0a192f] focus:ring-[#0a192f]" required />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Tugas & Deliverable</label>
          <textarea v-model="form.task_description" rows="3" class="w-full text-sm border-slate-300 rounded-md shadow-xs focus:border-[#0a192f] focus:ring-[#0a192f]" placeholder="Jelaskan spesifikasi tugas yang diselesaikan..." required></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <button type="button" @click="$emit('close')" class="px-4 py-2 text-xs font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-md">Batal</button>
          <button type="submit" :disabled="form.processing" class="px-4 py-2 text-xs font-medium text-white bg-[#0a192f] hover:bg-[#112240] rounded-md">Simpan Log Jam</button>
        </div>
      </form>
    </div>
  </div>
</template>

