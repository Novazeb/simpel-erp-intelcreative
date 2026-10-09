<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const roles = computed(() => page.props.auth?.user?.roles || []);
const permissions = computed(() => page.props.auth?.user?.permissions || []);
const pendingApprovalsCount = computed(() => page.props.pendingApprovalsCount || 0);

const isSuperAdmin = computed(() => roles.value.includes('super-admin'));
const can = (perm) => permissions.value.includes(perm);

// Collapsible Group States
const openHris = ref(true);
const openOps = ref(true);
const openFinance = ref(true);
const openGovernance = ref(true);
</script>

<template>
  <div class="min-h-screen flex bg-slate-50 text-black">
    <!-- Sidebar Enterprise 7-Tier Navigation -->
    <aside class="w-64 bg-[#0a192f] text-white flex flex-col justify-between shrink-0 border-r border-[#1e2d4a]" aria-label="Navigasi Korporat">
      <div class="overflow-y-auto">
        <!-- Logo Brand -->
        <div class="h-16 flex items-center px-6 border-b border-[#1e2d4a] gap-3 sticky top-0 bg-[#0a192f] z-10">
          <img src="/images/logo-icon.png" alt="Intel Creative" class="w-8 h-8 object-contain" />
          <span class="font-bold text-xs tracking-widest uppercase text-white">INTEL CREATIVE</span>
        </div>

        <nav class="p-3 space-y-4 text-xs font-medium">
          <!-- 1. RINGKASAN EKSEKUTIF -->
          <div class="space-y-1">
            <div class="px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Ringkasan Eksekutif</div>
            <Link 
              href="/dashboard" 
              class="flex items-center gap-2.5 px-3 py-2 rounded text-slate-300 hover:text-white hover:bg-[#172a46] transition"
            >
              <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
              </svg>
              <span>Dasbor Perusahaan</span>
            </Link>

            <Link 
              href="/approvals" 
              class="flex items-center justify-between px-3 py-2 rounded text-slate-300 hover:text-white hover:bg-[#172a46] transition"
            >
              <div class="flex items-center gap-2.5">
                <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span>Pusat Persetujuan</span>
              </div>
              <span v-if="pendingApprovalsCount > 0" class="px-1.5 py-0.2 bg-amber-500 text-black text-[10px] font-bold rounded">
                {{ pendingApprovalsCount }}
              </span>
            </Link>
          </div>

          <!-- 2. SUMBER DAYA MANUSIA (HRIS) -->
          <div class="space-y-1">
            <button @click="openHris = !openHris" class="w-full flex items-center justify-between px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
              <span>Sumber Daya Manusia</span>
              <span>{{ openHris ? '−' : '+' }}</span>
            </button>
            <div v-show="openHris" class="space-y-0.5">
              <Link 
                v-if="can('employee.view-any')"
                href="/hr/employees" 
                class="flex items-center gap-2.5 px-3 py-2 rounded text-slate-300 hover:text-white hover:bg-[#172a46] transition"
              >
                <span>Direktori Karyawan</span>
              </Link>
              <Link 
                v-if="can('attendance.view-all') || can('attendance.record-self')"
                href="/attendance" 
                class="flex items-center gap-2.5 px-3 py-2 rounded text-slate-300 hover:text-white hover:bg-[#172a46] transition"
              >
                <span>Presensi & Kehadiran</span>
              </Link>
            </div>
          </div>

          <!-- 3. OPERASIONAL & BISNIS KREATIF -->
          <div class="space-y-1">
            <button @click="openOps = !openOps" class="w-full flex items-center justify-between px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
              <span>Bisnis & Operasional</span>
              <span>{{ openOps ? '−' : '+' }}</span>
            </button>
            <div v-show="openOps" class="space-y-0.5">
              <Link 
                href="/operations/projects" 
                class="flex items-center gap-2.5 px-3 py-2 rounded text-slate-300 hover:text-white hover:bg-[#172a46] transition"
              >
                <span>Proyek & Penugasan</span>
              </Link>
              <Link 
                href="/operations/timesheets" 
                class="flex items-center gap-2.5 px-3 py-2 rounded text-slate-300 hover:text-white hover:bg-[#172a46] transition"
              >
                <span>Log Jam Proyek (Timesheet)</span>
              </Link>
            </div>
          </div>

          <!-- 4. PENGADAAN & ASET -->
          <div class="space-y-1">
            <div class="px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Aset & Logistik</div>
            <Link 
              href="/procurement/assets" 
              class="flex items-center gap-2.5 px-3 py-2 rounded text-slate-300 hover:text-white hover:bg-[#172a46] transition"
            >
              <span>Inventaris Aset Kerja</span>
            </Link>
          </div>

          <!-- 5. KEUANGAN & AKUNTANSI -->
          <div class="space-y-1">
            <button @click="openFinance = !openFinance" class="w-full flex items-center justify-between px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
              <span>Keuangan & Akuntansi</span>
              <span>{{ openFinance ? '−' : '+' }}</span>
            </button>
            <div v-show="openFinance" class="space-y-0.5">
              <Link 
                v-if="can('payroll.period-manage') || can('payroll.approve')"
                href="/payroll/periods" 
                class="flex items-center gap-2.5 px-3 py-2 rounded text-slate-300 hover:text-white hover:bg-[#172a46] transition"
              >
                <span>Siklus Penggajian</span>
              </Link>
              <Link 
                v-if="can('payroll.view-my-slip')"
                href="/payroll/my-slips" 
                class="flex items-center gap-2.5 px-3 py-2 rounded text-slate-300 hover:text-white hover:bg-[#172a46] transition"
              >
                <span>Slip & Snapshot Gaji</span>
              </Link>
              <Link 
                v-if="can('finance.journal-view')"
                href="/finance/vouchers" 
                class="flex items-center gap-2.5 px-3 py-2 rounded text-slate-300 hover:text-white hover:bg-[#172a46] transition"
              >
                <span>Voucher Pengeluaran</span>
              </Link>
              <Link 
                v-if="can('finance.journal-view')"
                href="/finance/ledger" 
                class="flex items-center gap-2.5 px-3 py-2 rounded text-slate-300 hover:text-white hover:bg-[#172a46] transition"
              >
                <span>Buku Besar & COA</span>
              </Link>
              <Link 
                href="/finance/reimbursements" 
                class="flex items-center gap-2.5 px-3 py-2 rounded text-slate-300 hover:text-white hover:bg-[#172a46] transition"
              >
                <span>Klaim & Kas Kecil</span>
              </Link>
            </div>
          </div>

          <!-- 6. TATA KELOLA & KEAMANAN (Super Admin Only) -->
          <div v-if="isSuperAdmin" class="space-y-1">
            <button @click="openGovernance = !openGovernance" class="w-full flex items-center justify-between px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
              <span>Tata Kelola Sistem</span>
              <span>{{ openGovernance ? '−' : '+' }}</span>
            </button>
            <div v-show="openGovernance" class="space-y-0.5">
              <Link 
                href="/admin/roles-permissions" 
                class="flex items-center gap-2.5 px-3 py-2 rounded text-slate-300 hover:text-white hover:bg-[#172a46] transition"
              >
                <span>Manajemen Akses & RBAC</span>
              </Link>
              <Link 
                href="/admin/audit-trails" 
                class="flex items-center gap-2.5 px-3 py-2 rounded text-slate-300 hover:text-white hover:bg-[#172a46] transition"
              >
                <span>Log Jejak Audit</span>
              </Link>
            </div>
          </div>
        </nav>
      </div>

      <!-- Footer Akun -->
      <div class="p-4 border-t border-[#1e2d4a] flex items-center justify-between bg-[#0a192f]">
        <div class="truncate mr-2">
          <p class="text-xs font-semibold text-white truncate">{{ user?.name }}</p>
          <p class="text-[10px] text-slate-400 uppercase truncate font-medium tracking-wider">{{ user?.roles?.[0] }}</p>
        </div>
        <Link 
          href="/logout" 
          method="post" 
          as="button"
          class="px-2.5 py-1 text-xs text-slate-300 hover:text-white hover:bg-[#172a46] rounded transition inline-flex items-center gap-1"
        >
          Keluar
        </Link>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 bg-white">
      <header class="h-16 bg-white border-b border-slate-200 px-8 flex items-center justify-between">
        <h1 class="text-base font-bold text-black tracking-tight">
          <slot name="header">PT INTEL CREATIVE</slot>
        </h1>
      </header>

      <main class="flex-1 p-8 overflow-y-auto bg-slate-50">
        <div v-if="$page.props.flash?.success" class="mb-6 p-4 rounded bg-white border border-slate-300 text-black text-xs font-medium flex items-center justify-between shadow-xs">
          <span>{{ $page.props.flash.success }}</span>
        </div>
        <div v-if="$page.props.flash?.error" class="mb-6 p-4 rounded bg-white border border-rose-300 text-rose-950 text-xs font-medium flex items-center justify-between shadow-xs">
          <span>{{ $page.props.flash.error }}</span>
        </div>

        <slot />
      </main>
    </div>
  </div>
</template>
