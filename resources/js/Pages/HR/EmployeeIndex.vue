<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  employees: Object,
  filters: Object,
  departments: Array,
  designations: Array,
  work_shifts: Array,
});

const showModal = ref(false);
const showImportModal = ref(false);
const search = ref(props.filters?.search || '');
let searchTimeout = null;

// Debounced Server-Side Search (umpanbalik3.md 2.A)
watch(search, (val) => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    router.get('/hr/employees', { search: val }, {
      preserveState: true,
      replace: true,
    });
  }, 300);
});

const handleKeyDown = (e) => {
  if (e.key === 'Escape') {
    if (showModal.value) showModal.value = false;
    if (showImportModal.value) showImportModal.value = false;
  }
};

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
});

const form = useForm({
  nik: '',
  full_name: '',
  email: '',
  phone: '',
  department_id: '',
  designation_id: '',
  work_shift_id: '',
  employment_status: 'PKWT',
  join_date: new Date().toISOString().split('T')[0],
  bank_name: 'BCA',
  bank_account_number: '',
  bank_account_holder: '',
  basic_salary: 8000000,
});

const importForm = useForm({
  file: null,
});

const submit = () => {
  form.post('/hr/employees', {
    onSuccess: () => {
      showModal.value = false;
      form.reset();
    },
  });
};

const submitImport = () => {
  importForm.post('/hr/employees/import', {
    onSuccess: () => {
      showImportModal.value = false;
      importForm.reset();
    },
  });
};
</script>

<template>
  <AppLayout>
    <template #header>Direktori Karyawan</template>

    <div class="space-y-6">
      <!-- Action & Search Bar -->
      <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 pb-3 border-b border-slate-200">
        <div>
          <h2 class="text-lg font-bold tracking-tight text-black">Direktori Karyawan</h2>
          <p class="text-xs text-slate-600 mt-0.5">Kelola identitas, penempatan divisi, dan kompensasi kerja.</p>
        </div>

        <div class="flex items-center gap-2.5 w-full md:w-auto">
          <!-- Debounced Search Input (WCAG Accessible) -->
          <div class="relative w-full md:w-64">
            <input 
              v-model="search"
              type="text" 
              placeholder="Cari NIK, Nama, Email..." 
              class="w-full px-3 py-2 text-xs border border-slate-300 rounded-md text-black placeholder-slate-400 focus:ring-2 focus:ring-[#0a192f] focus:outline-hidden"
            />
          </div>

          <!-- Tombol Impor Massal (umpanbalik3.md) -->
          <button 
            v-if="$page.props.auth?.user?.permissions?.includes('employee.create')"
            @click="showImportModal = true" 
            class="min-h-[38px] px-3.5 py-2 border border-slate-300 hover:bg-slate-100 text-black font-medium text-xs rounded-md transition inline-flex items-center gap-1.5 focus:ring-1 focus:ring-slate-900 focus:outline-hidden shrink-0"
          >
            <svg class="w-3.5 h-3.5 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            Impor CSV
          </button>

          <!-- Tombol Tambah Karyawan -->
          <button 
            v-if="$page.props.auth?.user?.permissions?.includes('employee.create')"
            @click="showModal = true" 
            class="min-h-[38px] px-3.5 py-2 bg-[#0a192f] hover:bg-[#112240] text-white font-medium text-xs rounded-md transition inline-flex items-center gap-1.5 focus:ring-2 focus:ring-slate-900 focus:outline-hidden shrink-0"
          >
            <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Tambah Karyawan
          </button>
        </div>
      </div>

      <!-- Table Card Minimalis -->
      <div class="bg-white rounded-md border border-slate-200 overflow-hidden">
        <table class="w-full text-left text-xs text-black">
          <thead class="bg-slate-50 text-black text-[11px] uppercase font-bold border-b border-slate-200">
            <tr>
              <th class="px-6 py-3.5">NIK</th>
              <th class="px-6 py-3.5">Nama Lengkap</th>
              <th class="px-6 py-3.5">Departemen & Jabatan</th>
              <th class="px-6 py-3.5">Status</th>
              <th class="px-6 py-3.5" v-if="$page.props.auth?.user?.permissions?.includes('employee.view-salary')">Gaji Pokok</th>
              <th class="px-6 py-3.5 text-right">Tindakan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="employees.data.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-slate-500 font-medium">
                Tidak ada data karyawan yang cocok dengan kriteria pencarian.
              </td>
            </tr>
            <tr v-for="emp in employees.data" :key="emp.id" class="hover:bg-slate-50 transition">
              <td class="px-6 py-4 font-semibold text-black">{{ emp.nik }}</td>
              <td class="px-6 py-4">
                <div class="font-bold text-black">{{ emp.full_name }}</div>
                <div class="text-[11px] text-slate-600">{{ emp.email }}</div>
              </td>
              <td class="px-6 py-4">
                <div class="text-black font-semibold">{{ emp.department || '-' }}</div>
                <div class="text-[11px] text-slate-600">{{ emp.designation || '-' }}</div>
              </td>
              <td class="px-6 py-4">
                <span class="px-2.5 py-1 text-[11px] font-semibold rounded bg-slate-100 text-black border border-slate-200">
                  {{ emp.employment_status }}
                </span>
              </td>
              <td class="px-6 py-4 font-bold text-black" v-if="$page.props.auth?.user?.permissions?.includes('employee.view-salary')">
                Rp {{ Number(emp.basic_salary).toLocaleString('id-ID') }}
              </td>
              <td class="px-6 py-4 text-right">
                <button 
                  v-if="$page.props.auth?.user?.permissions?.includes('employee.update')"
                  @click="form.delete(`/hr/employees/${emp.id}`)"
                  class="min-h-[36px] px-2.5 py-1 text-xs text-rose-700 hover:text-rose-900 font-semibold focus:ring-1 focus:ring-rose-500 rounded focus:outline-hidden"
                >
                  Arsipkan
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Server-Side Pagination Bar -->
        <div v-if="employees.links && employees.links.length > 3" class="px-6 py-3 border-t border-slate-200 bg-slate-50 flex items-center justify-between text-xs text-slate-600">
          <div>
            Menampilkan {{ employees.from || 0 }} - {{ employees.to || 0 }} dari total {{ employees.total }} data
          </div>
          <div class="flex items-center gap-1">
            <template v-for="(link, key) in employees.links" :key="key">
              <span 
                v-if="!link.url" 
                class="px-2.5 py-1 text-slate-400 border border-transparent rounded text-xs" 
                v-html="link.label"
              />
              <a 
                v-else 
                :href="link.url" 
                :class="[
                  'px-2.5 py-1 rounded text-xs border transition',
                  link.active ? 'bg-[#0a192f] text-white border-[#0a192f] font-bold' : 'border-slate-300 hover:bg-slate-200 text-black'
                ]" 
                v-html="link.label"
              />
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Form Tambah Karyawan -->
    <div 
      v-if="showModal" 
      @click.self="showModal = false"
      role="dialog"
      aria-modal="true"
      class="fixed inset-0 bg-[#0a192f]/50 backdrop-blur-xs flex items-center justify-center p-4 z-50"
    >
      <div class="bg-white rounded-lg max-w-xl w-full p-6 shadow-xl border border-slate-200 text-black">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
          <h3 class="text-base font-bold text-black">Pendaftaran Karyawan Baru</h3>
          <button @click="showModal = false" class="text-slate-500 hover:text-black p-1 rounded focus:ring-1 focus:ring-slate-400 focus:outline-hidden" aria-label="Tutup modal">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-black mb-1">NIK</label>
              <input v-model="form.nik" required placeholder="ITC-2026-001" class="w-full px-3 py-2 border border-slate-300 rounded-md text-xs text-black focus:ring-2 focus:ring-[#0a192f] focus:outline-hidden" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-black mb-1">Nama Lengkap</label>
              <input v-model="form.full_name" required placeholder="Rian Ardiansyah" class="w-full px-3 py-2 border border-slate-300 rounded-md text-xs text-black focus:ring-2 focus:ring-[#0a192f] focus:outline-hidden" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-black mb-1">Email</label>
              <input v-model="form.email" type="email" required placeholder="rian@intelcreative.co.id" class="w-full px-3 py-2 border border-slate-300 rounded-md text-xs text-black focus:ring-2 focus:ring-[#0a192f] focus:outline-hidden" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-black mb-1">Departemen</label>
              <select v-model="form.department_id" required class="w-full px-3 py-2 border border-slate-300 rounded-md text-xs text-black bg-white focus:ring-2 focus:ring-[#0a192f] focus:outline-hidden">
                <option value="" disabled>Pilih Departemen</option>
                <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-black mb-1">Jabatan</label>
              <select v-model="form.designation_id" required class="w-full px-3 py-2 border border-slate-300 rounded-md text-xs text-black bg-white focus:ring-2 focus:ring-[#0a192f] focus:outline-hidden">
                <option value="" disabled>Pilih Jabatan</option>
                <option v-for="des in designations" :key="des.id" :value="des.id">{{ des.title }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-black mb-1">Jadwal Shift</label>
              <select v-model="form.work_shift_id" required class="w-full px-3 py-2 border border-slate-300 rounded-md text-xs text-black bg-white focus:ring-2 focus:ring-[#0a192f] focus:outline-hidden">
                <option value="" disabled>Pilih Shift</option>
                <option v-for="shift in work_shifts" :key="shift.id" :value="shift.id">{{ shift.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-black mb-1">Status Kontrak</label>
              <select v-model="form.employment_status" class="w-full px-3 py-2 border border-slate-300 rounded-md text-xs text-black bg-white focus:ring-2 focus:ring-[#0a192f] focus:outline-hidden">
                <option value="PKWT">PKWT</option>
                <option value="PKWTT">PKWTT</option>
                <option value="FREELANCE">FREELANCE</option>
                <option value="PROBATION">PROBATION</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-black mb-1">Gaji Pokok (IDR)</label>
              <input v-model="form.basic_salary" type="number" required class="w-full px-3 py-2 border border-slate-300 rounded-md text-xs text-black focus:ring-2 focus:ring-[#0a192f] focus:outline-hidden" />
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3 pt-3 border-t border-slate-100">
            <div>
              <label class="block text-xs font-semibold text-black mb-1">Bank</label>
              <input v-model="form.bank_name" required placeholder="BCA / Mandiri" class="w-full px-3 py-2 border border-slate-300 rounded-md text-xs text-black focus:ring-2 focus:ring-[#0a192f] focus:outline-hidden" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-black mb-1">No. Rekening</label>
              <input v-model="form.bank_account_number" required placeholder="872019281" class="w-full px-3 py-2 border border-slate-300 rounded-md text-xs text-black focus:ring-2 focus:ring-[#0a192f] focus:outline-hidden" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-black mb-1">Atas Nama</label>
              <input v-model="form.bank_account_holder" required placeholder="Rian Ardiansyah" class="w-full px-3 py-2 border border-slate-300 rounded-md text-xs text-black focus:ring-2 focus:ring-[#0a192f] focus:outline-hidden" />
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <button type="button" @click="showModal = false" class="min-h-[40px] px-4 py-2 border border-slate-300 rounded-md text-xs font-medium text-black hover:bg-slate-50 transition">
              Batal
            </button>
            <button type="submit" :disabled="form.processing" class="min-h-[40px] px-4 py-2 bg-[#0a192f] text-white rounded-md text-xs font-medium hover:bg-[#112240] transition disabled:opacity-50">
              Simpan Karyawan
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Form Impor CSV Massal (umpanbalik3.md 2.C) -->
    <div 
      v-if="showImportModal" 
      @click.self="showImportModal = false"
      role="dialog"
      aria-modal="true"
      class="fixed inset-0 bg-[#0a192f]/50 backdrop-blur-xs flex items-center justify-center p-4 z-50"
    >
      <div class="bg-white rounded-lg max-w-md w-full p-6 shadow-xl border border-slate-200 text-black">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
          <h3 class="text-sm font-bold text-black">Impor Data Karyawan Massal (CSV)</h3>
          <button @click="showImportModal = false" class="text-slate-500 hover:text-black p-1 rounded focus:ring-1 focus:ring-slate-400 focus:outline-hidden">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="submitImport" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-black mb-1">Pilih Berkas CSV</label>
            <input 
              type="file" 
              accept=".csv,.txt"
              required
              @input="importForm.file = $event.target.files[0]"
              class="w-full px-3 py-2 border border-slate-300 rounded-md text-xs text-black focus:ring-2 focus:ring-[#0a192f] focus:outline-hidden"
            />
            <p class="text-[11px] text-slate-500 mt-1">
              Header wajib: nik, full_name, email, department_code, designation_title, work_shift_name, employment_status, join_date, bank_name, bank_account_number, bank_account_holder, basic_salary, tax_status
            </p>
          </div>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button type="button" @click="showImportModal = false" class="min-h-[38px] px-4 py-1.5 border border-slate-300 rounded-md text-xs font-medium text-black hover:bg-slate-50 transition">
              Batal
            </button>
            <button type="submit" :disabled="importForm.processing" class="min-h-[38px] px-4 py-1.5 bg-[#0a192f] text-white rounded-md text-xs font-medium hover:bg-[#112240] transition disabled:opacity-50">
              {{ importForm.processing ? 'Mengunggah & Validasi...' : 'Unggah & Proses Impor' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
