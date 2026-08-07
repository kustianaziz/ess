<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
  Home,
  FileText,
  Utensils,
  Calendar,
  LayoutGrid,
  X,
  Clock,
  CheckSquare,
  Plane,
  CreditCard,
  Wallet,
  Zap,
  History,
  Users,
  Building2,
  Scale,
  BarChart3,
  Bell,
  User,
  LogOut,
  LineChart,
  Layers,
  Store,
  Globe,
  RefreshCw,
  Archive,
  CalendarClock
} from 'lucide-vue-next';

const page = usePage();
const showQuickMenu = ref(false);

const currentUser = computed(() => page.props.auth.user);

const userRoles = computed(() => {
  const roles = page.props.auth.user?.roles || [];
  if (Array.isArray(roles)) {
    return roles.map(r => (typeof r === 'string' ? r : (r.name || r)));
  }
  if (typeof roles === 'object') {
    return Object.values(roles).map(r => (typeof r === 'string' ? r : (r.name || r)));
  }
  if (typeof roles === 'string') return [roles];
  return [];
});

const isAdmin = computed(() => userRoles.value.includes('admin'));

const isHrdOrAdmin = computed(() => {
  return userRoles.value.includes('admin') || userRoles.value.includes('hrd_finance');
});

const isLevel1OrAbove = computed(() => {
  return (
    userRoles.value.includes('admin') ||
    userRoles.value.includes('hrd_finance') ||
    userRoles.value.includes('manager') ||
    !page.props.auth.user?.manager_id
  );
});

const unreadNotificationsCount = computed(() => {
  return page.props.auth.user?.unread_notifications_count || 0;
});

const isCurrentRoute = (routeName) => {
  if (!routeName) return false;
  return route().current(routeName) || route().current(routeName + '.*');
};
</script>

<template>
  <div>
    <!-- Floating Bottom Navigation Bar (Mobile Native App Style) -->
    <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-slate-100 shadow-[0_-4px_15px_rgba(0,0,0,0.05)] h-16 px-2 sm:px-6 flex items-center justify-around pb-safe">
      <!-- Home Link -->
      <Link
        :href="route('dashboard')"
        class="flex flex-col items-center gap-1 p-2 rounded-xl transition-all"
        :class="isCurrentRoute('dashboard') ? 'text-indigo-600 font-bold' : 'text-slate-400 hover:text-slate-600'"
      >
        <Home class="w-5 h-5" />
        <span class="text-[10px] font-semibold">Beranda</span>
      </Link>

      <!-- Approval Link (For Level 1 Manager & Level 2 HRD) -->
      <Link
        v-if="isLevel1OrAbove"
        :href="route('approval.index')"
        class="flex flex-col items-center gap-1 p-2 rounded-xl transition-all relative"
        :class="isCurrentRoute('approval') ? 'text-amber-600 font-bold' : 'text-slate-400 hover:text-slate-600'"
      >
        <CheckSquare class="w-5 h-5" />
        <span class="text-[10px] font-semibold">Approval</span>
      </Link>

      <!-- Disbursement Link (For HRD & Finance) -->
      <Link
        v-if="isHrdOrAdmin"
        :href="route('keuangan.pencairan.index')"
        class="flex flex-col items-center gap-1 p-2 rounded-xl transition-all"
        :class="isCurrentRoute('keuangan.pencairan') ? 'text-emerald-600 font-bold' : 'text-slate-400 hover:text-slate-600'"
      >
        <CreditCard class="w-5 h-5" />
        <span class="text-[10px] font-semibold">Pencairan</span>
      </Link>

      <!-- History Link -->
      <Link
        :href="route('riwayat-pengajuan.index')"
        class="flex flex-col items-center gap-1 p-2 rounded-xl transition-all"
        :class="isCurrentRoute('riwayat-pengajuan') ? 'text-indigo-600 font-bold' : 'text-slate-400 hover:text-slate-600'"
      >
        <Clock class="w-5 h-5" />
        <span class="text-[10px] font-semibold">Riwayat</span>
      </Link>

      <button
        @click="showQuickMenu = !showQuickMenu"
        class="w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center shadow-lg hover:scale-105 transition-transform shrink-0 mt-[-20px] ring-4 ring-white"
        title="Buka Menu Seluruh Fitur Aplikasi"
      >
        <X v-if="showQuickMenu" class="w-5 h-5" />
        <LayoutGrid v-else class="w-5 h-5" />
      </button>
    </nav>

    <!-- Full Mobile Menu Drawer when FAB '+' clicked -->
    <div v-if="showQuickMenu" class="lg:hidden fixed inset-0 z-50 flex items-end">
      <div @click="showQuickMenu = false" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm"></div>

      <div class="relative w-full bg-white rounded-t-3xl p-5 sm:p-6 shadow-2xl z-10 animate-in slide-in-from-bottom duration-200 max-h-[85vh] overflow-y-auto space-y-5">
        <div class="w-12 h-1.5 bg-slate-200 rounded-full mx-auto mb-1"></div>
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="text-base font-bold text-slate-900">Seluruh Fitur & Layanan ESS</h3>
            <p class="text-xs text-slate-500">
              Akun: <span class="font-bold text-slate-800">{{ currentUser?.name }}</span>
              <span class="ml-1 px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-700 uppercase">
                {{ userRoles.join(', ') || 'USER' }}
              </span>
            </p>
          </div>
          <button @click="showQuickMenu = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 font-bold">✕</button>
        </div>

        <!-- SECTION: DASHBOARD EKSEKUTIF -->
        <div v-if="isLevel1OrAbove" class="space-y-2">
          <Link
            :href="route('executive.dashboard')"
            @click="showQuickMenu = false"
            class="p-3 rounded-2xl bg-indigo-600 text-white flex items-center justify-between hover:bg-indigo-700 transition-all shadow-md shadow-indigo-600/30"
          >
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-8 h-8 rounded-xl bg-white/20 text-white flex items-center justify-center shrink-0">
                <LineChart class="w-4 h-4" />
              </div>
              <div class="min-w-0">
                <p class="text-sm font-bold truncate">Dashboard Eksekutif</p>
                <p class="text-[10px] text-indigo-100 truncate">Analisis Keuangan & Laba Rugi</p>
              </div>
            </div>
          </Link>
        </div>

        <!-- SECTION 1: FORM PENGAJUAN BARU -->
        <div class="space-y-2 pt-2 border-t border-slate-100">
          <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">1. Layanan & Form Pengajuan Saya</span>
          <div class="grid grid-cols-2 gap-2">
            <Link
              :href="route('pengajuan.reimbursement.create')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-purple-50/70 border border-purple-100 flex items-center gap-2.5 hover:bg-purple-100/70 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-purple-600 text-white flex items-center justify-center shrink-0">
                <FileText class="w-3.5 h-3.5" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Reimbursement</p>
                <p class="text-[9px] text-slate-500 truncate">Klaim biaya nota</p>
              </div>
            </Link>

            <Link
              :href="route('pengajuan.operasional.create')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-orange-50/70 border border-orange-100 flex items-center gap-2.5 hover:bg-orange-100/70 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-orange-500 text-white flex items-center justify-center shrink-0">
                <Utensils class="w-3.5 h-3.5" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Operasional</p>
                <p class="text-[9px] text-slate-500 truncate">Konsumsi/kegiatan</p>
              </div>
            </Link>

            <Link
              :href="route('pengajuan.perjalanan-dinas.create')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-sky-50/70 border border-sky-100 flex items-center gap-2.5 hover:bg-sky-100/70 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-sky-600 text-white flex items-center justify-center shrink-0">
                <Plane class="w-3.5 h-3.5" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Dinas Office</p>
                <p class="text-[9px] text-slate-500 truncate">Perjalanan dinas</p>
              </div>
            </Link>

            <Link
              :href="route('pengajuan.cuti.create')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-emerald-50/70 border border-emerald-100 flex items-center gap-2.5 hover:bg-emerald-100/70 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0">
                <Calendar class="w-3.5 h-3.5" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Cuti Karyawan</p>
                <p class="text-[9px] text-slate-500 truncate">Pengajuan izin/cuti</p>
              </div>
            </Link>
          </div>
        </div>

        <!-- SECTION 2: PERSETUJUAN (MANAGER LEVEL 1 & HRD LEVEL 2) -->
        <div v-if="isLevel1OrAbove" class="space-y-2 pt-2 border-t border-slate-100">
          <span class="text-[11px] font-bold text-amber-700 uppercase tracking-wider block">2. Wewenang Persetujuan (Approval)</span>
          <div class="grid grid-cols-2 gap-2">
            <Link
              :href="route('approval.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-amber-50/80 border border-amber-200 flex items-center gap-2.5 hover:bg-amber-100/80 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-amber-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                <CheckSquare class="w-3.5 h-3.5" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Antrean Approval</p>
                <p class="text-[9px] text-amber-700 font-semibold truncate">Verifikasi ajuan</p>
              </div>
            </Link>

            <Link
              :href="route('approval.history')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-slate-50 border border-slate-200 flex items-center gap-2.5 hover:bg-slate-100 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-slate-800 text-white flex items-center justify-center shrink-0">
                <History class="w-3.5 h-3.5" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Riwayat Approval</p>
                <p class="text-[9px] text-slate-500 truncate">Histori keputusan</p>
              </div>
            </Link>
          </div>
        </div>

        <!-- SECTION 3: MODUL KEUANGAN & KAS (HRD & FINANCE LEVEL 2) -->
        <div v-if="isLevel1OrAbove" class="space-y-2 pt-2 border-t border-slate-100">
          <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider block">3. Modul Keuangan & Pencairan Kas</span>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            <Link
              v-if="isHrdOrAdmin"
              :href="route('keuangan.pencairan.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-emerald-50/80 border border-emerald-200 flex items-center gap-2.5 hover:bg-emerald-100/80 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                <CreditCard class="w-3.5 h-3.5" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Pencairan Kas</p>
                <p class="text-[9px] text-emerald-700 font-semibold truncate">Eksekusi bayar</p>
              </div>
            </Link>

            <Link
              :href="route('keuangan.kas-operasional.dashboard')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-sky-50/80 border border-sky-200 flex items-center gap-2.5 hover:bg-sky-100/80 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-sky-600 text-white flex items-center justify-center shrink-0">
                <Wallet class="w-3.5 h-3.5" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Buku Kas & Saldo</p>
                <p class="text-[9px] text-slate-500 truncate">Kas & bank</p>
              </div>
            </Link>

            <Link
              v-if="isHrdOrAdmin"
              :href="route('keuangan.tagihan-bulanan.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-amber-50/80 border border-amber-200 flex items-center gap-2.5 hover:bg-amber-100/80 transition-all col-span-2 sm:col-span-1"
            >
              <div class="w-7 h-7 rounded-xl bg-amber-600 text-white flex items-center justify-center shrink-0">
                <Zap class="w-3.5 h-3.5" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Tagihan Bulanan</p>
                <p class="text-[9px] text-slate-500 truncate">Listrik, internet, sewa</p>
              </div>
            </Link>
          </div>
        </div>

        <!-- SECTION 4: PENDAPATAN / INVOICING -->
        <div v-if="isHrdOrAdmin" class="space-y-2 pt-2 border-t border-slate-100">
          <span class="text-[11px] font-bold text-sky-700 uppercase tracking-wider block">4. Pendapatan & Invoicing</span>
          <div class="grid grid-cols-2 gap-2">
            <Link
              :href="route('invoicing.customers.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-sky-50/80 border border-sky-200 flex items-center gap-2.5 hover:bg-sky-100/80 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-sky-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                <Users class="w-3.5 h-3.5" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Klien / Customer</p>
                <p class="text-[9px] text-sky-700 font-semibold truncate">Data pelanggan</p>
              </div>
            </Link>

            <Link
              :href="route('invoicing.invoices.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-indigo-50/80 border border-indigo-200 flex items-center gap-2.5 hover:bg-indigo-100/80 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                <FileText class="w-3.5 h-3.5" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Invoice Tagihan</p>
                <p class="text-[9px] text-indigo-700 font-semibold truncate">Pembuatan invoice</p>
              </div>
            </Link>
          </div>
        </div>

        <!-- SECTION 5: RENEWAL WEBPRAKTIS -->
        <div v-if="isHrdOrAdmin" class="space-y-2 pt-2 border-t border-slate-100">
          <span class="text-[11px] font-bold text-violet-700 uppercase tracking-wider block">5. Modul Renewal Webpraktis</span>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            <Link
              :href="route('renewal.vendors.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-violet-50/80 border border-violet-200 flex items-center gap-2.5 hover:bg-violet-100/80 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-violet-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                <Store class="w-3.5 h-3.5" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Master Vendor</p>
                <p class="text-[9px] text-violet-700 font-semibold truncate">Daftar layanan</p>
              </div>
            </Link>

            <Link
              :href="route('renewal.domains.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-cyan-50/80 border border-cyan-200 flex items-center gap-2.5 hover:bg-cyan-100/80 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-cyan-600 text-white flex items-center justify-center shrink-0">
                <Globe class="w-3.5 h-3.5" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Domain & Hosting</p>
                <p class="text-[9px] text-slate-500 truncate">Aset digital klien</p>
              </div>
            </Link>

            <Link
              :href="route('renewal.renewals.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-emerald-50/80 border border-emerald-200 flex items-center gap-2.5 hover:bg-emerald-100/80 transition-all col-span-2 sm:col-span-1"
            >
              <div class="w-7 h-7 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0">
                <RefreshCw class="w-3.5 h-3.5" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Proses Renewal</p>
                <p class="text-[9px] text-slate-500 truncate">Perpanjangan layanan</p>
              </div>
            </Link>
          </div>
        </div>

        <!-- SECTION 6: REKAPITULASI & LAPORAN (HRD & ADMIN) -->
        <div v-if="isLevel1OrAbove" class="space-y-2 pt-2 border-t border-slate-100">
          <span class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider block">4. Rekapitulasi & Laporan Perusahaan</span>
          <Link
            :href="route('admin.reports.index')"
            @click="showQuickMenu = false"
            class="p-3 rounded-2xl bg-indigo-50/70 border border-indigo-100 flex items-center gap-3 hover:bg-indigo-100/70 transition-all"
          >
            <div class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0">
              <BarChart3 class="w-4 h-4" />
            </div>
            <div class="min-w-0">
              <p class="text-xs font-bold text-slate-900 truncate">Laporan & Rekapitulasi Lengkap</p>
              <p class="text-[10px] text-indigo-700 font-medium truncate">Export Excel & Word seluruh pengajuan</p>
            </div>
          </Link>
        </div>

        <!-- SECTION 9: AKUNTANSI (HRD & ADMIN) -->
        <div v-if="isLevel1OrAbove" class="space-y-2 pt-2 border-t border-slate-100">
          <span class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider block">5. Akuntansi</span>
          <div class="grid grid-cols-2 gap-2">
            <Link
              v-if="isHrdOrAdmin"
              :href="route('accounting.coas.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-indigo-50/70 border border-indigo-100 flex items-center gap-3 hover:bg-indigo-100/70 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0">
                <FileText class="w-3.5 h-3.5" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Master COA</p>
                <p class="text-[10px] text-indigo-700 font-medium truncate">Chart of Accounts</p>
              </div>
            </Link>
            
            <Link
              v-if="isHrdOrAdmin"
              :href="route('accounting.assets.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-indigo-50/70 border border-indigo-100 flex items-center gap-3 hover:bg-indigo-100/70 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0">
                <Archive class="w-3.5 h-3.5" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Master Aset</p>
                <p class="text-[10px] text-indigo-700 font-medium truncate">Data Aset Tetap</p>
              </div>
            </Link>

            <Link
              v-if="isHrdOrAdmin"
              :href="route('accounting.periods.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-indigo-50/70 border border-indigo-100 flex items-center gap-3 hover:bg-indigo-100/70 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0">
                <CalendarClock class="w-3.5 h-3.5" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Master Periode</p>
                <p class="text-[10px] text-indigo-700 font-medium truncate">Periode Akuntansi</p>
              </div>
            </Link>

            <Link
              v-if="isHrdOrAdmin"
              :href="route('accounting.beginning-balances.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-indigo-50/70 border border-indigo-100 flex items-center gap-3 hover:bg-indigo-100/70 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0">
                <Scale class="w-3.5 h-3.5" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Neraca Awal</p>
                <p class="text-[10px] text-indigo-700 font-medium truncate">Saldo Awal COA</p>
              </div>
            </Link>

            <Link
              v-if="isHrdOrAdmin"
              :href="route('accounting.journals.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-indigo-50/70 border border-indigo-100 flex items-center gap-3 hover:bg-indigo-100/70 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0">
                <FileText class="w-3.5 h-3.5" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Transaksi Jurnal</p>
                <p class="text-[10px] text-indigo-700 font-medium truncate">Jurnal Umum & Penyesuaian</p>
              </div>
            </Link>
          </div>
          
          <span class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider block mt-4">Laporan Keuangan</span>
          <div class="grid grid-cols-2 gap-2 mt-2">
            <Link :href="route('accounting.reports.ledger')" @click="showQuickMenu = false" class="p-3 rounded-2xl bg-pink-50/70 border border-pink-100 flex items-center gap-3 hover:bg-pink-100/70 transition-all">
              <div class="w-7 h-7 rounded-xl bg-pink-600 text-white flex items-center justify-center shrink-0"><FileText class="w-3.5 h-3.5" /></div>
              <div class="min-w-0"><p class="text-xs font-bold text-slate-900 truncate">Buku Besar</p></div>
            </Link>
            <Link :href="route('accounting.reports.income-statement')" @click="showQuickMenu = false" class="p-3 rounded-2xl bg-green-50/70 border border-green-100 flex items-center gap-3 hover:bg-green-100/70 transition-all">
              <div class="w-7 h-7 rounded-xl bg-green-600 text-white flex items-center justify-center shrink-0"><BarChart3 class="w-3.5 h-3.5" /></div>
              <div class="min-w-0"><p class="text-xs font-bold text-slate-900 truncate">Laba Rugi</p></div>
            </Link>
            <Link :href="route('accounting.reports.balance-sheet')" @click="showQuickMenu = false" class="p-3 rounded-2xl bg-blue-50/70 border border-blue-100 flex items-center gap-3 hover:bg-blue-100/70 transition-all">
              <div class="w-7 h-7 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0"><Scale class="w-3.5 h-3.5" /></div>
              <div class="min-w-0"><p class="text-xs font-bold text-slate-900 truncate">Neraca</p></div>
            </Link>
            <Link :href="route('accounting.reports.cash-flow')" @click="showQuickMenu = false" class="p-3 rounded-2xl bg-yellow-50/70 border border-yellow-100 flex items-center gap-3 hover:bg-yellow-100/70 transition-all">
              <div class="w-7 h-7 rounded-xl bg-yellow-600 text-white flex items-center justify-center shrink-0"><Zap class="w-3.5 h-3.5" /></div>
              <div class="min-w-0"><p class="text-xs font-bold text-slate-900 truncate">Arus Kas</p></div>
            </Link>
            <Link :href="route('accounting.reports.calk')" @click="showQuickMenu = false" class="p-3 rounded-2xl bg-purple-50/70 border border-purple-100 flex items-center gap-3 hover:bg-purple-100/70 transition-all col-span-2">
              <div class="w-7 h-7 rounded-xl bg-purple-600 text-white flex items-center justify-center shrink-0"><Layers class="w-3.5 h-3.5" /></div>
              <div class="min-w-0"><p class="text-xs font-bold text-slate-900 truncate">Catatan Laporan Keuangan (CALK)</p></div>
            </Link>
          </div>
        </div>

        <!-- SECTION 7: ADMIN PANEL (KHUSUS ROLE ADMIN) -->
        <div v-if="isAdmin" class="space-y-2 pt-2 border-t border-slate-100">
          <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">7. Admin Panel (Master Data)</span>
          <div class="grid grid-cols-2 gap-2">
            <Link
              :href="route('admin.users.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-slate-100 border border-slate-200 flex items-center gap-2.5 hover:bg-slate-200 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-slate-900 text-white flex items-center justify-center shrink-0">
                <Users class="w-3.5 h-3.5 text-indigo-400" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Kelola User</p>
                <p class="text-[9px] text-slate-500 truncate">Pengguna & role</p>
              </div>
            </Link>

            <Link
              :href="route('admin.divisions.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-slate-100 border border-slate-200 flex items-center gap-2.5 hover:bg-slate-200 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-slate-900 text-white flex items-center justify-center shrink-0">
                <Building2 class="w-3.5 h-3.5 text-blue-400" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Kelola Divisi</p>
                <p class="text-[9px] text-slate-500 truncate">Struktur divisi</p>
              </div>
            </Link>

            <Link
              :href="route('admin.expense-types.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-slate-100 border border-slate-200 flex items-center gap-2.5 hover:bg-slate-200 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-slate-900 text-white flex items-center justify-center shrink-0">
                <FileText class="w-3.5 h-3.5 text-emerald-400" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Jenis Pengeluaran</p>
                <p class="text-[9px] text-slate-500 truncate">Kategori reimburse</p>
              </div>
            </Link>

            <Link
              :href="route('admin.activity-types.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-slate-100 border border-slate-200 flex items-center gap-2.5 hover:bg-slate-200 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-slate-900 text-white flex items-center justify-center shrink-0">
                <Utensils class="w-3.5 h-3.5 text-orange-400" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Jenis Kegiatan</p>
                <p class="text-[9px] text-slate-500 truncate">Kategori operasional</p>
              </div>
            </Link>

            <Link
              :href="route('admin.leave-types.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-slate-100 border border-slate-200 flex items-center gap-2.5 hover:bg-slate-200 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-slate-900 text-white flex items-center justify-center shrink-0">
                <Calendar class="w-3.5 h-3.5 text-purple-400" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Jenis Cuti</p>
                <p class="text-[9px] text-slate-500 truncate">Master tipe cuti</p>
              </div>
            </Link>

            <Link
              :href="route('admin.leave-balances.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-slate-100 border border-slate-200 flex items-center gap-2.5 hover:bg-slate-200 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-slate-900 text-white flex items-center justify-center shrink-0">
                <Scale class="w-3.5 h-3.5 text-amber-400" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Kuota Cuti</p>
                <p class="text-[9px] text-slate-500 truncate">Saldo cuti karyawan</p>
              </div>
            </Link>

            <Link
              :href="route('admin.services.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-slate-100 border border-slate-200 flex items-center gap-2.5 hover:bg-slate-200 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-slate-900 text-white flex items-center justify-center shrink-0">
                <Layers class="w-3.5 h-3.5 text-teal-400" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Master Layanan</p>
                <p class="text-[9px] text-slate-500 truncate">Layanan Invoice</p>
              </div>
            </Link>
          </div>
        </div>

        <!-- SECTION 8: NOTIFIKASI & AKUN SAYA -->
        <div class="space-y-2 pt-2 border-t border-slate-100">
          <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">8. Akun & Notifikasi</span>
          <div class="grid grid-cols-2 gap-2">
            <Link
              :href="route('notifikasi.index')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between hover:bg-slate-100 transition-all"
            >
              <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-7 h-7 rounded-xl bg-slate-800 text-white flex items-center justify-center shrink-0">
                  <Bell class="w-3.5 h-3.5 text-amber-400" />
                </div>
                <div class="min-w-0">
                  <p class="text-xs font-bold text-slate-900 truncate">Notifikasi</p>
                  <p class="text-[9px] text-slate-500 truncate">Pemberitahuan</p>
                </div>
              </div>
              <span
                v-if="unreadNotificationsCount > 0"
                class="px-2 py-0.5 text-[10px] font-bold bg-rose-500 text-white rounded-full shrink-0"
              >
                {{ unreadNotificationsCount }}
              </span>
            </Link>

            <Link
              :href="route('profile.edit')"
              @click="showQuickMenu = false"
              class="p-3 rounded-2xl bg-slate-50 border border-slate-200 flex items-center gap-2.5 hover:bg-slate-100 transition-all"
            >
              <div class="w-7 h-7 rounded-xl bg-slate-800 text-white flex items-center justify-center shrink-0">
                <User class="w-3.5 h-3.5" />
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">Profil Saya</p>
                <p class="text-[9px] text-slate-500 truncate">Ubah password & foto</p>
              </div>
            </Link>
          </div>
        </div>

        <!-- LOGOUT BUTTON -->
        <div class="pt-2">
          <Link
            :href="route('logout')"
            method="post"
            as="button"
            @click="showQuickMenu = false"
            class="w-full py-3 px-4 rounded-2xl bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold border border-rose-200 flex items-center justify-center gap-2 transition-all"
          >
            <LogOut class="w-4 h-4" />
            <span>Keluar Akun (Logout)</span>
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>
