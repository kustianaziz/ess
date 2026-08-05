<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
  Home,
  FileText,
  Utensils,
  Calendar,
  Plus,
  X,
  Clock,
  CheckSquare,
  Plane,
  CreditCard,
  Wallet,
  Zap,
  History,
  Users,
  Settings
} from 'lucide-vue-next';

const page = usePage();
const showQuickMenu = ref(false);

const user = computed(() => page.props.auth.user);

const isLevel1OrAbove = computed(() => {
  return user.value?.roles?.some(r => ['manager', 'admin', 'hrd_finance'].includes(r.name));
});

const isHrdOrAdmin = computed(() => {
  return user.value?.roles?.some(r => ['admin', 'hrd_finance'].includes(r.name));
});

const isAdmin = computed(() => {
  return user.value?.roles?.some(r => r.name === 'admin');
});

const isCurrentRoute = (routeName) => {
  if (!routeName) return false;
  return route().current(routeName) || route().current(routeName + '.*');
};
</script>

<template>
  <div>
    <!-- Floating Bottom Navigation Bar (Mobile Native App Style) -->
    <nav class="lg:hidden fixed bottom-4 left-3 right-3 z-40 bg-white/95 backdrop-blur-xl border border-slate-200/80 rounded-2xl shadow-2xl px-3 py-2 flex items-center justify-around">
      <!-- Home Link -->
      <Link
        :href="route('dashboard')"
        class="flex flex-col items-center gap-1 p-1 rounded-xl transition-all"
        :class="isCurrentRoute('dashboard') ? 'text-indigo-600 font-bold scale-105' : 'text-slate-400 hover:text-slate-600'"
      >
        <Home class="w-5 h-5" />
        <span class="text-[10px] font-semibold">Beranda</span>
      </Link>

      <!-- Approval Link (For Level 1 Manager & Level 2 HRD) -->
      <Link
        v-if="isLevel1OrAbove"
        :href="route('approval.index')"
        class="flex flex-col items-center gap-1 p-1 rounded-xl transition-all relative"
        :class="isCurrentRoute('approval') ? 'text-amber-600 font-bold scale-105' : 'text-slate-400 hover:text-slate-600'"
      >
        <CheckSquare class="w-5 h-5" />
        <span class="text-[10px] font-semibold">Approval</span>
      </Link>

      <!-- Disbursement Link (For HRD & Finance) -->
      <Link
        v-if="isHrdOrAdmin"
        :href="route('keuangan.pencairan.index')"
        class="flex flex-col items-center gap-1 p-1 rounded-xl transition-all"
        :class="isCurrentRoute('keuangan.pencairan') ? 'text-emerald-600 font-bold scale-105' : 'text-slate-400 hover:text-slate-600'"
      >
        <CreditCard class="w-5 h-5" />
        <span class="text-[10px] font-semibold">Pencairan</span>
      </Link>

      <!-- History Link -->
      <Link
        :href="route('riwayat-pengajuan.index')"
        class="flex flex-col items-center gap-1 p-1 rounded-xl transition-all"
        :class="isCurrentRoute('riwayat-pengajuan') ? 'text-indigo-600 font-bold scale-105' : 'text-slate-400 hover:text-slate-600'"
      >
        <Clock class="w-5 h-5" />
        <span class="text-[10px] font-semibold">Riwayat</span>
      </Link>

      <!-- FAB Plus Action Button -->
      <button
        @click="showQuickMenu = !showQuickMenu"
        class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center shadow-lg shadow-slate-900/30 hover:scale-105 transition-all shrink-0"
        title="Buka Menu Akses Cepat"
      >
        <X v-if="showQuickMenu" class="w-5 h-5" />
        <Plus v-else class="w-5 h-5" />
      </button>
    </nav>

    <!-- Quick Action Sheet / Drawer when FAB '+' clicked -->
    <div v-if="showQuickMenu" class="lg:hidden fixed inset-0 z-50 flex items-end">
      <div @click="showQuickMenu = false" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm"></div>

      <div class="relative w-full bg-white rounded-t-3xl p-5 sm:p-6 shadow-2xl z-10 animate-in slide-in-from-bottom duration-200 max-h-[85vh] overflow-y-auto space-y-4">
        <div class="w-12 h-1.5 bg-slate-200 rounded-full mx-auto mb-2"></div>
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="text-base font-bold text-slate-900">Menu Akses Cepat</h3>
            <p class="text-xs text-slate-500">Layanan sesuai wewenang akun Anda</p>
          </div>
          <button @click="showQuickMenu = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 font-bold">✕</button>
        </div>

        <!-- SECTION 1: FORM PENGAJUAN BARU -->
        <div class="space-y-2">
          <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Buat Pengajuan Baru</span>
          <div class="grid grid-cols-2 gap-2.5">
            <Link
              :href="route('pengajuan.reimbursement.create')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-purple-50/70 border border-purple-100 flex items-center gap-3 hover:bg-purple-100/70 transition-all"
            >
              <div class="w-8 h-8 rounded-xl bg-purple-600 text-white flex items-center justify-center shrink-0">
                <FileText class="w-4 h-4" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Reimburse</p>
                <p class="text-[10px] text-slate-500 truncate">Klaim nota</p>
              </div>
            </Link>

            <Link
              :href="route('pengajuan.operasional.create')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-orange-50/70 border border-orange-100 flex items-center gap-3 hover:bg-orange-100/70 transition-all"
            >
              <div class="w-8 h-8 rounded-xl bg-orange-500 text-white flex items-center justify-center shrink-0">
                <Utensils class="w-4 h-4" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Operasional</p>
                <p class="text-[10px] text-slate-500 truncate">Konsumsi/kegiatan</p>
              </div>
            </Link>

            <Link
              :href="route('pengajuan.perjalanan-dinas.create')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-sky-50/70 border border-sky-100 flex items-center gap-3 hover:bg-sky-100/70 transition-all"
            >
              <div class="w-8 h-8 rounded-xl bg-sky-600 text-white flex items-center justify-center shrink-0">
                <Plane class="w-4 h-4" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Dinas Office</p>
                <p class="text-[10px] text-slate-500 truncate">Perjalanan dinas</p>
              </div>
            </Link>

            <Link
              :href="route('pengajuan.cuti.create')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-emerald-50/70 border border-emerald-100 flex items-center gap-3 hover:bg-emerald-100/70 transition-all"
            >
              <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0">
                <Calendar class="w-4 h-4" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Cuti Karyawan</p>
                <p class="text-[10px] text-slate-500 truncate">Izin & cuti</p>
              </div>
            </Link>
          </div>
        </div>

        <!-- SECTION 2: PERSETUJUAN (MANAGER LEVEL 1 & HRD LEVEL 2) -->
        <div v-if="isLevel1OrAbove" class="space-y-2 pt-2 border-t border-slate-100">
          <span class="text-[11px] font-bold text-amber-700 uppercase tracking-wider block">Wewenang Persetujuan (Approval)</span>
          <div class="grid grid-cols-2 gap-2.5">
            <Link
              :href="route('approval.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-amber-50/80 border border-amber-200 flex items-center gap-3 hover:bg-amber-100/80 transition-all"
            >
              <div class="w-8 h-8 rounded-xl bg-amber-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                <CheckSquare class="w-4 h-4" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Antrean Approval</p>
                <p class="text-[10px] text-amber-700 font-semibold truncate">Verifikasi ajuan</p>
              </div>
            </Link>

            <Link
              :href="route('approval.history')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-slate-50 border border-slate-200 flex items-center gap-3 hover:bg-slate-100 transition-all"
            >
              <div class="w-8 h-8 rounded-xl bg-slate-800 text-white flex items-center justify-center shrink-0">
                <History class="w-4 h-4" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Riwayat Approval</p>
                <p class="text-[10px] text-slate-500 truncate">Histori persetujuan</p>
              </div>
            </Link>
          </div>
        </div>

        <!-- SECTION 3: MODUL KEUANGAN & PENCAIRAN (HRD & FINANCE LEVEL 2) -->
        <div v-if="isHrdOrAdmin" class="space-y-2 pt-2 border-t border-slate-100">
          <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider block">Modul Keuangan & Pencairan Kas</span>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
            <Link
              :href="route('keuangan.pencairan.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-emerald-50/80 border border-emerald-200 flex items-center gap-3 hover:bg-emerald-100/80 transition-all"
            >
              <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                <CreditCard class="w-4 h-4" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Pencairan & Bayar</p>
                <p class="text-[10px] text-emerald-700 font-semibold truncate">Eksekusi dana disetujui</p>
              </div>
            </Link>

            <Link
              :href="route('keuangan.kas-operasional.dashboard')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-sky-50/80 border border-sky-200 flex items-center gap-3 hover:bg-sky-100/80 transition-all"
            >
              <div class="w-8 h-8 rounded-xl bg-sky-600 text-white flex items-center justify-center shrink-0">
                <Wallet class="w-4 h-4" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Buku Kas & Saldo</p>
                <p class="text-[10px] text-slate-500 truncate">Master akun kas/bank</p>
              </div>
            </Link>

            <Link
              :href="route('keuangan.tagihan-bulanan.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-amber-50/80 border border-amber-200 flex items-center gap-3 hover:bg-amber-100/80 transition-all"
            >
              <div class="w-8 h-8 rounded-xl bg-amber-600 text-white flex items-center justify-center shrink-0">
                <Zap class="w-4 h-4" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Tagihan Bulanan</p>
                <p class="text-[10px] text-slate-500 truncate">Listrik, internet, sewa</p>
              </div>
            </Link>
          </div>
        </div>

        <!-- SECTION 4: ADMINISTRASI SYSTEM (ADMINISTRATOR) -->
        <div v-if="isAdmin" class="space-y-2 pt-2 border-t border-slate-100">
          <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Panel Pengelola Admin</span>
          <div class="grid grid-cols-2 gap-2.5">
            <Link
              :href="route('admin.users.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-slate-100 border border-slate-200 flex items-center gap-3 hover:bg-slate-200 transition-all"
            >
              <div class="w-8 h-8 rounded-xl bg-slate-900 text-white flex items-center justify-center shrink-0">
                <Users class="w-4 h-4" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Kelola Pengguna</p>
                <p class="text-[10px] text-slate-500 truncate">Data user & role</p>
              </div>
            </Link>

            <Link
              :href="route('admin.master-data.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-slate-100 border border-slate-200 flex items-center gap-3 hover:bg-slate-200 transition-all"
            >
              <div class="w-8 h-8 rounded-xl bg-slate-900 text-white flex items-center justify-center shrink-0">
                <Settings class="w-4 h-4" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Master Data</p>
                <p class="text-[10px] text-slate-500 truncate">Divisi & kategori</p>
              </div>
            </Link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
