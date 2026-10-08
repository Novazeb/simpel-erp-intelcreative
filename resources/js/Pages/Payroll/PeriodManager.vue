<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  periods: Array,
});

const showModal = ref(false);
const calculatingPeriodId = ref(null);

const handleKeyDown = (e) => {
  if (e.key === 'Escape' && showModal.value) {
    showModal.value = false;
  }
};

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
});

const form = useForm({
  name: 'Payroll Periode Oktober 2026',
  start_date: '2026-09-21',
  end_date: '2026-10-20',
  cutoff_date: '2026-10-20',
  payment_date: '2026-10-25',
});

const submit = () => {
  form.post('/payroll/periods', {
    onSuccess: () => {
      showModal.value = false;
    },
  });
};

const triggerCalculate = (periodId) => {
  calculatingPeriodId.value = periodId;
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  fetch(`/payroll/periods/${periodId}/calculate`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': token,
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    }
  }).then(() => {
    router.reload({
      onFinish: () => {
        calculatingPeriodId.value = null;
      }
    });
  }).catch(() => {
    calculatingPeriodId.value = null;
  });
};

const triggerApprove = (periodId) => {
  router.post(`/payroll/periods/${periodId}/approve`);
};
</script>

<template>
  <AppLayout>
    <template #header>Penggajian (Payroll)</template>

    <div class="space-y-6">
      <div class="flex items-center justify-between pb-2 border-b border-slate-200">
        <div>
          <h2 class="text-lg font-bold tracking-tight text-black">Periode Penggajian</h2>
          <p class="text-xs text-slate-600 mt-0.5">Siklus cut-off absensi, kalkulasi batch gaji, dan pengesahan voucher.</p>
        </div>
        <button 
          v-if="$page.props.auth?.user?.permissions?.includes('payroll.period-manage')"
          @click="showModal = true"
          class="min-h-[40px] px-4 py-2 bg-[#0a192f] hover:bg-[#112240] text-white font-medium text-xs rounded-md transition inline-flex items-center gap-1.5 focus:ring-2 focus:ring-slate-900 focus:outline-hidden"
        >
          <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
          </svg>
          Periode Baru
        </button>
      </div>

      <!-- Empty State Bersih Tanpa Emoji -->
      <div v-if="!periods || periods.length === 0" class="bg-white rounded-md border border-slate-200 p-12 text-center">
        <div class="w-10 h-10 border border-slate-300 rounded-md mx-auto flex items-center justify-center text-slate-700 mb-3">
          <svg class="w-5 h-5 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <h3 class="text-sm font-bold text-black">Belum Ada Periode Penggajian</h3>
        <p class="text-xs text-slate-600 max-w-sm mx-auto mt-1 mb-5">
          Buat siklus cut-off absensi bulanan untuk memulai penghitungan gaji staf.
        </p>
        <button 
          v-if="$page.props.auth?.user?.permissions?.includes('payroll.period-manage')"
          @click="showModal = true"
          class="min-h-[40px] px-4 py-2 bg-[#0a192f] hover:bg-[#112240] text-white font-medium text-xs rounded-md transition"
        >
          Buat Periode Sekarang
        </button>
      </div>

      <!-- Periods Grid Minimalis -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <div v-for="period in periods" :key="period.id" class="bg-white rounded-md border border-slate-200 p-5 flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between mb-3">
              <span :class="[
                'px-2 py-0.5 text-[11px] font-semibold rounded border',
                period.status === 'APPROVED' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' :
                period.status === 'CALCULATED' ? 'bg-blue-50 text-blue-900 border-blue-200' :
                period.status === 'CALCULATING' ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-slate-100 text-slate-700 border-slate-200'
              ]">
                {{ period.status }}
              </span>
              <span class="text-[11px] text-slate-600">Cut-off: {{ period.cutoff_date }}</span>
            </div>

            <h3 class="font-bold text-black text-sm">{{ period.name }}</h3>
            <p class="text-xs text-slate-600 mt-1">Rentang: {{ period.start_date }} s/d {{ period.end_date }}</p>
            <p class="text-xs text-slate-600">Pencairan: {{ period.payment_date }}</p>

            <div class="mt-3 pt-3 border-t border-slate-100 text-xs text-black">
              Total Diproses: <strong class="font-bold">{{ period.slips_count }} slip</strong>
            </div>
          </div>

          <div class="mt-5 pt-3 border-t border-slate-100 flex items-center gap-2">
            <!-- Tombol Hitung Ulang Batch (Icon SVG) -->
            <button 
              v-if="['DRAFT', 'CALCULATED'].includes(period.status) && $page.props.auth?.user?.permissions?.includes('payroll.calculate')"
              @click="triggerCalculate(period.id)"
              :disabled="calculatingPeriodId === period.id"
              class="min-h-[38px] flex-1 px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium rounded transition disabled:opacity-50 inline-flex items-center justify-center gap-1.5 focus:ring-1 focus:ring-slate-900 focus:outline-hidden"
            >
              <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span>{{ calculatingPeriodId === period.id ? 'Menghitung...' : 'Hitung Batch' }}</span>
            </button>

            <!-- Tombol Approve Direksi (Icon SVG) -->
            <button 
              v-if="period.status === 'CALCULATED' && $page.props.auth?.user?.permissions?.includes('payroll.approve')"
              @click="triggerApprove(period.id)"
              class="min-h-[38px] flex-1 px-3 py-1.5 bg-[#0a192f] hover:bg-[#112240] text-white text-xs font-medium rounded transition inline-flex items-center justify-center gap-1.5 focus:ring-1 focus:ring-slate-900 focus:outline-hidden"
            >
              <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
              <span>Setujui & Kunci</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Form Tambah Periode -->
    <div 
      v-if="showModal" 
      @click.self="showModal = false"
      role="dialog"
      aria-modal="true"
      class="fixed inset-0 bg-[#0a192f]/50 backdrop-blur-xs flex items-center justify-center p-4 z-50"
    >
      <div class="bg-white rounded-lg max-w-md w-full p-6 shadow-xl border border-slate-200 text-black">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
          <h3 class="text-sm font-bold text-black">Inisialisasi Periode Penggajian</h3>
          <button @click="showModal = false" class="text-slate-500 hover:text-black p-1 rounded focus:ring-1 focus:ring-slate-400 focus:outline-hidden" aria-label="Tutup modal">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-black mb-1">Nama Periode</label>
            <input v-model="form.name" required class="w-full px-3 py-2 border border-slate-300 rounded-md text-xs text-black focus:ring-2 focus:ring-[#0a192f] focus:outline-hidden" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-black mb-1">Mulai</label>
              <input v-model="form.start_date" type="date" required class="w-full px-3 py-2 border border-slate-300 rounded-md text-xs text-black focus:ring-2 focus:ring-[#0a192f] focus:outline-hidden" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-black mb-1">Selesai</label>
              <input v-model="form.end_date" type="date" required class="w-full px-3 py-2 border border-slate-300 rounded-md text-xs text-black focus:ring-2 focus:ring-[#0a192f] focus:outline-hidden" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-black mb-1">Cut-off</label>
              <input v-model="form.cutoff_date" type="date" required class="w-full px-3 py-2 border border-slate-300 rounded-md text-xs text-black focus:ring-2 focus:ring-[#0a192f] focus:outline-hidden" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-black mb-1">Tanggal Bayar</label>
              <input v-model="form.payment_date" type="date" required class="w-full px-3 py-2 border border-slate-300 rounded-md text-xs text-black focus:ring-2 focus:ring-[#0a192f] focus:outline-hidden" />
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <button type="button" @click="showModal = false" class="min-h-[38px] px-4 py-1.5 border border-slate-300 rounded-md text-xs font-medium text-black hover:bg-slate-50 transition">
              Batal
            </button>
            <button type="submit" :disabled="form.processing" class="min-h-[38px] px-4 py-1.5 bg-[#0a192f] text-white rounded-md text-xs font-medium hover:bg-[#112240] transition disabled:opacity-50">
              Simpan
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
