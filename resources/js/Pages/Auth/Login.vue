<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  email: 'hr.manager@intelcreative.co.id',
  password: 'SecretPassword123!',
  remember: true,
});

const submit = () => {
  form.post('/login');
};
</script>

<template>
  <div class="min-h-screen bg-white flex items-center justify-center p-6 text-black">
    <div class="max-w-sm w-full bg-white border border-slate-200 rounded-lg p-8 shadow-xs">
      <div class="mb-6 text-center">
        <img src="/images/logo.png" alt="Intel Creative" class="h-20 mx-auto object-contain mb-3" />
        <h1 class="text-xl font-bold text-black tracking-tight">Masuk Sistem</h1>
        <p class="text-xs text-slate-600 mt-1">Platform Manajemen Terpadu PT INTEL CREATIVE</p>
      </div>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-black uppercase tracking-wider mb-1.5">Email</label>
          <input 
            v-model="form.email" 
            type="email" 
            required 
            class="w-full px-3.5 py-2.5 rounded-md bg-white border border-slate-300 text-black placeholder-slate-400 focus:ring-2 focus:ring-[#0a192f] focus:border-[#0a192f] focus:outline-hidden text-sm"
            placeholder="nama@intelcreative.co.id"
          />
          <p v-if="form.errors.email" class="text-rose-700 text-xs mt-1.5">{{ form.errors.email }}</p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-black uppercase tracking-wider mb-1.5">Kata Sandi</label>
          <input 
            v-model="form.password" 
            type="password" 
            required 
            class="w-full px-3.5 py-2.5 rounded-md bg-white border border-slate-300 text-black placeholder-slate-400 focus:ring-2 focus:ring-[#0a192f] focus:border-[#0a192f] focus:outline-hidden text-sm"
            placeholder="••••••••"
          />
          <p v-if="form.errors.password" class="text-rose-700 text-xs mt-1.5">{{ form.errors.password }}</p>
        </div>

        <div class="flex items-center text-xs">
          <label class="flex items-center gap-2 text-slate-700 cursor-pointer">
            <input type="checkbox" v-model="form.remember" class="rounded border-slate-300 text-[#0a192f] focus:ring-[#0a192f]">
            <span>Ingat sesi saya</span>
          </label>
        </div>

        <button 
          type="submit" 
          :disabled="form.processing"
          class="min-h-[44px] w-full py-2.5 bg-[#0a192f] hover:bg-[#112240] text-white font-medium rounded-md text-xs uppercase tracking-wider transition disabled:opacity-50 focus:ring-2 focus:ring-slate-900 focus:outline-hidden"
        >
          {{ form.processing ? 'Memproses...' : 'Masuk' }}
        </button>
      </form>

    </div>
  </div>
</template>
