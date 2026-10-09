<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const qrCodeSvg = ref('');
const countdown = ref(30);
const loading = ref(true);
let timer = null;

const fetchQrCode = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/api/v1/attendance/my-qr');
    qrCodeSvg.value = response.data.qr_svg;
    countdown.value = response.data.ttl_seconds || 30;
  } catch (error) {
    console.error('Gagal memuat QR Code:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchQrCode();
  timer = setInterval(() => {
    if (countdown.value > 1) {
      countdown.value--;
    } else {
      fetchQrCode();
    }
  }, 1000);
});

onUnmounted(() => {
  if (timer) clearInterval(timer);
});
</script>

<template>
  <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-xs text-center max-w-sm mx-auto">
    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
      Kode QR Presensi Dinamis
    </div>
    
    <div class="my-4 flex justify-center items-center min-h-[200px]">
      <div v-if="loading" class="text-sm text-slate-400 animate-pulse">
        Memperbarui QR Token...
      </div>
      <div v-else-if="qrCodeSvg" class="p-2 bg-white border border-slate-100 rounded-md" v-html="qrCodeSvg"></div>
      <div v-else class="text-sm text-red-500">
        Gagal merender QR Code.
      </div>
    </div>

    <div class="flex items-center justify-between text-xs text-slate-600 bg-slate-50 px-3 py-2 rounded-md">
      <span>Auto Refresh Dalam:</span>
      <span class="font-bold text-[#0a192f] tabular-nums">{{ countdown }} Detik</span>
    </div>
    <p class="text-[11px] text-slate-400 mt-2">
      Tunjukkan kode QR ini ke layar kamera Kios Lobi Kantor. Dilarang mengambil screenshot.
    </p>
  </div>
</template>

