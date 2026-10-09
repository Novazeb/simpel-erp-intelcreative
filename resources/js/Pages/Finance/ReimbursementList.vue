<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { formatRupiah } from '@/Utils/money';

const props = defineProps({
  reimbursements: Object,
  isFinance: Boolean,
});

const showModal = ref(false);

const form = useForm({
  title: '',
  claim_date: new Date().toISOString().substring(0, 10),
  items: [
    { category: 'TRANSPORT', description: '', amount: '' }
  ]
});

const addItem = () => {
  form.items.push({ category: 'TRANSPORT', description: '', amount: '' });
};

const removeItem = (index) => {
  if (form.items.length > 1) {
    form.items.splice(index, 1);
  }
};

const submitClaim = () => {
  form.post('/finance/reimbursements', {
    onSuccess: () => {
      form.reset();
      showModal.value = false;
    }
  });
};

const approveClaim = (id) => {
  if (confirm('Setujui dan cairkan reimbursement ini dari Kas Kecil?')) {
    router.post(`/finance/reimbursements/${id}/approve`);
  }
};
</script>

<template>
  <AppLayout>
    <template #header>Klaim Biaya & Kas Kecil (Reimbursement)</template>

    <div class="space-y-6">
      <div class="pb-2 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h2 class="text-lg font-bold text-black">Daftar Pengajuan Reimbursement</h2>
          <p class="text-xs text-slate-600 mt-0.5">Pencatatan pengeluaran operasional langsung dan pencairan dari Kas Kecil.</p>
        </div>
        <button
          @click="showModal = true"
          class="px-4 py-2 bg-[#0a192f] hover:bg-[#112240] text-white text-xs font-semibold rounded-md transition shadow-xs self-start md:self-auto"
        >
          + Ajukan Reimbursement
        </button>
      </div>

      <!-- Tabel Reimbursement -->
      <div class="bg-white border border-slate-200 rounded-md overflow-hidden">
        <table class="w-full text-left text-xs text-black">
          <thead class="bg-slate-50 text-[11px] uppercase font-bold border-b border-slate-200">
            <tr>
              <th class="px-6 py-3.5">Nomor Klaim</th>
              <th class="px-6 py-3.5">Tanggal</th>
              <th class="px-6 py-3.5">Pemohon</th>
              <th class="px-6 py-3.5">Judul Pengajuan</th>
              <th class="px-6 py-3.5 text-right">Total Biaya</th>
              <th class="px-6 py-3.5">Status</th>
              <th v-if="isFinance" class="px-6 py-3.5 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="!reimbursements?.data || reimbursements.data.length === 0">
              <td :colspan="isFinance ? 7 : 6" class="px-6 py-10 text-center text-slate-500 font-medium">
                Belum ada pengajuan reimbursement yang tercatat.
              </td>
            </tr>

            <tr v-for="reimb in reimbursements.data" :key="reimb.id" class="hover:bg-slate-50 transition">
              <td class="px-6 py-4 font-semibold text-black">{{ reimb.claim_number }}</td>
              <td class="px-6 py-4 text-slate-700">{{ reimb.claim_date }}</td>
              <td class="px-6 py-4 font-medium text-black">{{ reimb.employee?.full_name ?? '-' }}</td>
              <td class="px-6 py-4 text-slate-700">{{ reimb.title }}</td>
              <td class="px-6 py-4 text-right font-bold text-black tabular-nums">{{ formatRupiah(reimb.total_amount) }}</td>
              <td class="px-6 py-4">
                <span :class="[
                  'px-2 py-0.5 text-[11px] font-semibold rounded border',
                  reimb.status === 'PAID' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' :
                  reimb.status === 'REJECTED' ? 'bg-rose-50 text-rose-800 border-rose-200' :
                  'bg-amber-50 text-amber-800 border-amber-200'
                ]">
                  {{ reimb.status }}
                </span>
              </td>
              <td v-if="isFinance" class="px-6 py-4 text-center">
                <button
                  v-if="reimb.status === 'SUBMITTED'"
                  @click="approveClaim(reimb.id)"
                  class="px-2.5 py-1 bg-emerald-700 hover:bg-emerald-800 text-white rounded text-[11px] font-medium transition"
                >
                  Cairkan
                </button>
                <span v-else class="text-slate-400 text-[11px]">Selesai</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Form Pengajuan Reimbursement -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4">
      <div class="bg-white rounded-lg shadow-xl border border-slate-200 w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center pb-3 border-b border-slate-100 mb-4">
          <h3 class="text-base font-bold text-slate-900">Form Pengajuan Reimbursement</h3>
          <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
        </div>

        <form @submit.prevent="submitClaim" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Pengajuan</label>
            <input type="text" v-model="form.title" class="w-full text-sm border-slate-300 rounded-md shadow-xs focus:border-[#0a192f] focus:ring-[#0a192f]" placeholder="Contoh: Transport meeting klien PT ABC" required />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Pengeluaran</label>
            <input type="date" v-model="form.claim_date" class="w-full text-sm border-slate-300 rounded-md shadow-xs focus:border-[#0a192f] focus:ring-[#0a192f]" required />
          </div>

          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="block text-xs font-semibold text-slate-700">Rincian Item Pengeluaran</label>
              <button type="button" @click="addItem" class="text-xs text-blue-700 hover:underline font-semibold">+ Tambah Baris</button>
            </div>

            <div v-for="(item, idx) in form.items" :key="idx" class="p-3 bg-slate-50 border border-slate-200 rounded-md mb-2 space-y-2">
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Kategori</label>
                  <select v-model="item.category" class="w-full text-xs border-slate-300 rounded focus:border-[#0a192f]">
                    <option value="TRANSPORT">Transportasi</option>
                    <option value="MEALS">Konsumsi</option>
                    <option value="CLIENT_ENTERTAINMENT">Entertain Klien</option>
                    <option value="SUPPLIES">Perlengkapan Kerja</option>
                  </select>
                </div>
                <div>
                  <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Nominal (Rp)</label>
                  <input type="number" min="1" v-model="item.amount" class="w-full text-xs border-slate-300 rounded focus:border-[#0a192f]" placeholder="Nominal" required />
                </div>
              </div>
              <div class="flex gap-2 items-center">
                <input type="text" v-model="item.description" class="flex-1 text-xs border-slate-300 rounded focus:border-[#0a192f]" placeholder="Keterangan item..." required />
                <button type="button" v-if="form.items.length > 1" @click="removeItem(idx)" class="text-rose-600 hover:text-rose-800 text-xs font-bold px-2 py-1">Hapus</button>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
            <button type="button" @click="showModal = false" class="px-4 py-2 text-xs font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-md">Batal</button>
            <button type="submit" :disabled="form.processing" class="px-4 py-2 text-xs font-medium text-white bg-[#0a192f] hover:bg-[#112240] rounded-md">Kirim Pengajuan</button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

