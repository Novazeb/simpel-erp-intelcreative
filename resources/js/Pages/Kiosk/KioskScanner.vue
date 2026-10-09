<template>
  <div class="min-h-screen bg-slate-950 text-white font-sans flex flex-col justify-between p-6 select-none">
    
    <!-- Header Terminal Kios -->
    <header class="flex justify-between items-center bg-slate-900/80 backdrop-blur border border-slate-800 rounded-2xl p-4 px-6 shadow-xl">
      <div class="flex items-center gap-4">
        <img src="/images/logo-icon.png" alt="PT Intel Creative" class="h-10 w-auto object-contain" />
        <div>
          <h1 class="text-xl font-bold tracking-tight text-white">TERMINAL PRESENSI LOBBY</h1>
          <p class="text-xs text-slate-400">PT INTEL CREATIVE — KIOSK #01</p>
        </div>
      </div>

      <!-- Live Clock & Status Window Indicator -->
      <div class="text-right flex items-center gap-6">
        <div>
          <div class="text-3xl font-extrabold tabular-nums tracking-wider text-emerald-400">
            {{ currentTime }} <span class="text-xs font-normal text-slate-400">WIB</span>
          </div>
          <div class="text-xs text-slate-400 font-medium">{{ currentDate }}</div>
        </div>

        <!-- Window Status Badge -->
        <div class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider border shadow-sm"
             :class="windowStatusClass">
          {{ windowStatusText }}
        </div>
      </div>
    </header>

    <!-- Main Viewport Area: Pemindai Kamera & Kamera Live -->
    <main class="grid grid-cols-12 gap-6 my-auto items-center">
      
      <!-- Video Scanner Feed (Kiri) -->
      <div class="col-span-12 lg:col-span-7 flex flex-col items-center justify-center">
        <div class="relative w-full max-w-md aspect-square bg-slate-900 border-2 border-slate-700 rounded-3xl overflow-hidden shadow-2xl flex items-center justify-center">
          
          <!-- Element Video Stream Kamera -->
          <video ref="videoElement" class="w-full h-full object-cover" autoplay playsinline muted></video>

          <!-- Laser Scan Line Animation -->
          <div v-if="isScanning" class="absolute inset-0 border-2 border-emerald-500 rounded-3xl pointer-events-none">
            <div class="w-full h-1 bg-gradient-to-r from-transparent via-emerald-400 to-transparent shadow-[0_0_15px_#10b981] animate-pulse my-auto"></div>
          </div>

          <!-- Overlay Petunjuk Pemindaian -->
          <div class="absolute bottom-4 bg-slate-950/80 backdrop-blur px-4 py-2 rounded-full border border-slate-800 text-xs text-slate-300">
            Arahkan QR Code ponsel Anda ke dalam kotak
          </div>
        </div>
      </div>

      <!-- Panel Kontrol Kios & Tombol Input Darurat (Kanan) -->
      <div class="col-span-12 lg:col-span-5 flex flex-col gap-4">
        
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 shadow-lg">
          <h2 class="text-lg font-bold text-slate-200 mb-2">Panduan Presensi QR</h2>
          <ul class="text-xs text-slate-400 space-y-2 list-disc list-inside">
            <li>Buka Aplikasi ERP / Portal Mandiri Karyawan di Ponsel.</li>
            <li>Pilih menu <strong class="text-emerald-400">Presensi QR</strong>.</li>
            <li>Tunjukkan layar ponsel yang menampilkan QR Code dinamis.</li>
            <li>Status kehadiran akan langsung dicatat secara otomatis.</li>
          </ul>
        </div>

        <!-- Tombol Input Darurat NIK + PIN -->
        <button 
          @click="showEmergencyModal = true"
          class="w-full py-4 bg-amber-600/20 hover:bg-amber-600/30 border border-amber-500/40 text-amber-300 rounded-2xl font-bold tracking-wide flex items-center justify-center gap-3 transition-all active:scale-95 shadow-lg cursor-pointer">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
          </svg>
          INPUT DARURAT (NIK + PIN)
        </button>
      </div>
    </main>

    <!-- Overlay Banner Hasil Scan (Pop-up Sukses / Warning / Error) -->
    <transition name="fade">
      <div v-if="scanResult" 
           class="fixed inset-x-6 bottom-6 p-6 rounded-2xl border shadow-2xl flex items-center justify-between backdrop-blur-md z-50 animate-bounce"
           :class="resultBannerClass">
        <div class="flex items-center gap-4">
          <div class="p-3 rounded-xl bg-white/10">
            <svg v-if="scanResult.status === 'SUCCESS'" class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <svg v-else-if="scanResult.status === 'WARNING'" class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <svg v-else class="w-8 h-8 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </div>
          <div>
            <div class="text-lg font-extrabold text-white">{{ scanResult.employee_name || 'Pemberitahuan Sistem' }}</div>
            <div class="text-sm font-medium text-slate-200">{{ scanResult.message }}</div>
          </div>
        </div>
        <div class="text-right">
          <div class="text-2xl font-bold tabular-nums text-white">{{ scanResult.clock_in || currentTime }}</div>
          <div class="text-xs text-slate-300 font-semibold uppercase">{{ scanResult.attendance_status || 'Kios Log' }}</div>
        </div>
      </div>
    </transition>

    <!-- Modal Input Darurat (NIK + PIN Numpad Touchscreen) -->
    <div v-if="showEmergencyModal" class="fixed inset-0 bg-slate-950/90 backdrop-blur-md z-50 flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-bold text-white">Presensi Darurat (NIK + PIN)</h3>
          <button @click="closeEmergencyModal" class="text-slate-400 hover:text-white font-bold text-xl cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="submitEmergencyPin" class="space-y-4">
          <div>
            <label class="block text-xs text-slate-400 mb-1">Nomor Induk Karyawan (NIK)</label>
            <input v-model="emergencyForm.nik" type="text" placeholder="Contoh: ITC-STF-002" required 
                   class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white tabular-nums focus:border-emerald-500 outline-none" />
          </div>

          <div>
            <label class="block text-xs text-slate-400 mb-1">PIN Rahasia 6-Digit</label>
            <input v-model="emergencyForm.pin" type="password" maxlength="6" placeholder="******" required 
                   class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white text-center tracking-widest tabular-nums text-xl focus:border-emerald-500 outline-none" />
          </div>

          <div class="flex gap-3 pt-2">
            <button type="button" @click="closeEmergencyModal" class="flex-1 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl font-bold text-sm cursor-pointer">Batal</button>
            <button type="submit" :disabled="isSubmitting" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold text-sm flex justify-center items-center cursor-pointer">
              <span v-if="!isSubmitting">Kirim Presensi</span>
              <span v-else class="animate-spin border-2 border-white border-t-transparent rounded-full w-5 h-5"></span>
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';

const currentTime = ref('');
const currentDate = ref('');
const isScanning = ref(true);
const scanResult = ref(null);
const showEmergencyModal = ref(false);
const isSubmitting = ref(false);
const videoElement = ref(null);
let mediaStream = null;
let clockInterval = null;

const emergencyForm = ref({
  nik: '',
  pin: ''
});

// Update Live Jam Digital
const updateClock = () => {
  const now = new Date();
  currentTime.value = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  currentDate.value = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};

// Window Status Indicator (08:30-09:15, 09:16-09:25, >09:25)
const windowStatusText = computed(() => {
  const now = new Date();
  const timeStr = now.toTimeString().split(' ')[0];
  if (timeStr < '08:30:00') return 'Belum Dibuka';
  if (timeStr <= '09:15:00') return 'Window Tepat Waktu';
  if (timeStr <= '09:25:00') return 'Window Terlambat';
  return 'Presensi Ditutup';
});

const windowStatusClass = computed(() => {
  const now = new Date();
  const timeStr = now.toTimeString().split(' ')[0];
  if (timeStr < '08:30:00') return 'bg-slate-800 text-slate-400 border-slate-700';
  if (timeStr <= '09:15:00') return 'bg-emerald-950/80 text-emerald-400 border-emerald-800';
  if (timeStr <= '09:25:00') return 'bg-amber-950/80 text-amber-400 border-amber-800';
  return 'bg-rose-950/80 text-rose-400 border-rose-800';
});

const resultBannerClass = computed(() => {
  if (!scanResult.value) return '';
  if (scanResult.value.status === 'SUCCESS') return 'bg-emerald-950/90 border-emerald-600 text-emerald-200';
  if (scanResult.value.status === 'WARNING') return 'bg-amber-950/90 border-amber-600 text-amber-200';
  return 'bg-rose-950/90 border-rose-600 text-rose-200';
});

const triggerAudio = (soundType) => {
  try {
    const audio = new Audio(`/sounds/${soundType.toLowerCase()}.mp3`);
    audio.play().catch(() => {});
  } catch (e) {
    // Ignore audio play errors on devices without audio
  }
};

const handleScanResponse = (data) => {
  scanResult.value = data;
  triggerAudio(data.sound || 'SUCCESS_BEEP');
  
  setTimeout(() => {
    scanResult.value = null;
  }, 4000);
};

const submitEmergencyPin = async () => {
  isSubmitting.value = true;
  try {
    const res = await fetch('/api/v1/attendance/qr-scan', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        scan_type: 'MANUAL_PIN',
        nik: emergencyForm.value.nik,
        pin: emergencyForm.value.pin,
        kiosk_device_id: 'KIOSK-LOBBY-01'
      })
    });

    const data = await res.json();
    if (res.ok) {
      handleScanResponse(data);
      closeEmergencyModal();
    } else {
      handleScanResponse({
        status: 'ERROR',
        message: data.message || 'Presensi gagal diproses.',
        sound: data.sound || 'ERROR_BEEP'
      });
    }
  } catch (error) {
    handleScanResponse({
      status: 'ERROR',
      message: 'Gagal terhubung ke server kios.',
      sound: 'ERROR_BEEP'
    });
  } finally {
    isSubmitting.value = false;
  }
};

const closeEmergencyModal = () => {
  showEmergencyModal.value = false;
  emergencyForm.value = { nik: '', pin: '' };
};

const startCamera = async () => {
  if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
    try {
      mediaStream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 720 } }
      });
      if (videoElement.value) {
        videoElement.value.srcObject = mediaStream;
      }
    } catch (e) {
      // Camera permission not granted or device has no camera; graceful fallback
    }
  }
};

onMounted(() => {
  updateClock();
  clockInterval = setInterval(updateClock, 1000);
  startCamera();
});

onBeforeUnmount(() => {
  if (clockInterval) clearInterval(clockInterval);
  if (mediaStream) {
    mediaStream.getTracks().forEach(track => track.stop());
  }
});
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>

