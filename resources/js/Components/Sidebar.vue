<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
  Home,
  FileText,
  Utensils,
  Calendar,
  Clock,
  History,
  Bell,
  User,
  LogOut,
  GraduationCap,
  CheckSquare,
  Users,
  Building2,
  Scale,
  BarChart3,
  Plane,
  Wallet,
  Zap,
  Layers,
  RefreshCw,
  Globe,
  Store,
  LineChart
} from 'lucide-vue-next';

const page = usePage();

const isCurrentRoute = (routeName) => {
  if (!routeName) return false;
  return route().current(routeName) || route().current(routeName + '.*');
};

const isAdmin = computed(() => {
  const roles = page.props.auth.user?.roles || [];
  const roleArray = Array.isArray(roles) ? roles : [roles];
  return roleArray.includes('admin');
});

const isHrdOrAdmin = computed(() => {
  const roles = page.props.auth.user?.roles || [];
  const roleArray = Array.isArray(roles) ? roles : [roles];
  return roleArray.includes('admin') || roleArray.includes('hrd_finance');
});
</script>

<template>
  <aside class="w-64 bg-[#0F172A] text-slate-300 flex flex-col justify-between h-screen sticky top-0 border-r border-slate-800 select-none z-30 shrink-0">
    <div>
      <!-- Brand Header -->
      <div class="h-20 px-6 flex items-center gap-3 border-b border-slate-800/60">
        <img src="/logo.png" alt="EDU ESS Logo" class="w-10 h-10 object-contain rounded-xl" />
        <div>
          <h1 class="font-black text-xl text-white tracking-tight leading-none">
            EDU
          </h1>
          <p class="text-[10px] font-medium text-slate-400 tracking-wider uppercase mt-0.5">
            Employee Self Service
          </p>
        </div>
      </div>

      <!-- Navigation List -->
      <nav class="p-4 space-y-6 overflow-y-auto max-h-[calc(100vh-160px)] custom-scrollbar">
        <!-- Main Beranda -->
        <div class="space-y-1">
          <Link
            :href="route('dashboard')"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
            :class="isCurrentRoute('dashboard') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
          >
            <Home class="w-4 h-4" />
            <span>Beranda ESS</span>
          </Link>
          <Link
            v-if="isHrdOrAdmin"
            :href="route('executive.dashboard')"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
            :class="isCurrentRoute('executive.dashboard') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
          >
            <LineChart class="w-4 h-4 text-emerald-400" />
            <span>Dashboard Eksekutif</span>
          </Link>
        </div>

        <!-- Section: PENGAJUAN -->
        <div>
          <p class="px-3.5 text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-2">
            PENGAJUAN
          </p>
          <div class="space-y-1">
            <Link
              :href="route('pengajuan.reimbursement.create')"
              class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="isCurrentRoute('pengajuan.reimbursement') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <FileText class="w-4 h-4 text-emerald-400" />
              <span>Reimbursement</span>
            </Link>

            <Link
              :href="route('pengajuan.operasional.create')"
              class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="isCurrentRoute('pengajuan.operasional') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <Utensils class="w-4 h-4 text-orange-400" />
              <span>Konsumsi / Operasional</span>
            </Link>

            <Link
              :href="route('pengajuan.cuti.create')"
              class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="isCurrentRoute('pengajuan.cuti') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <Calendar class="w-4 h-4 text-purple-400" />
              <span>Cuti</span>
            </Link>

            <Link
              :href="route('pengajuan.perjalanan-dinas.create')"
              class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="isCurrentRoute('pengajuan.perjalanan-dinas') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <Plane class="w-4 h-4 text-sky-400" />
              <span>Perjalanan Dinas</span>
            </Link>
          </div>
        </div>

        <!-- Section: KEUANGAN (OPERASIONAL & TAGIHAN) -->
        <div v-if="isHrdOrAdmin">
          <p class="px-3.5 text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-2">
            KEUANGAN
          </p>
          <div class="space-y-1">
            <Link
              :href="route('keuangan.pencairan.index')"
              class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="isCurrentRoute('keuangan.pencairan') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <CreditCard class="w-4 h-4 text-emerald-400" />
              <span>Pencairan & Pembayaran</span>
            </Link>

            <Link
              :href="route('keuangan.kas-operasional.dashboard')"
              class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="isCurrentRoute('keuangan.kas-operasional') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <Wallet class="w-4 h-4 text-sky-400" />
              <span>Kas Operasional & Saldo</span>
            </Link>

            <Link
              :href="route('keuangan.tagihan-bulanan.index')"
              class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="isCurrentRoute('keuangan.tagihan-bulanan') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <Zap class="w-4 h-4 text-amber-400" />
              <span>Tagihan Bulanan Rutin</span>
            </Link>
          </div>
        </div>

        <!-- Section: PENDAPATAN / INVOICING -->
        <div v-if="isHrdOrAdmin">
          <p class="px-3.5 text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-2 mt-4">
            PENDAPATAN
          </p>
          <div class="space-y-1">
            <Link
              :href="route('invoicing.customers.index')"
              class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="isCurrentRoute('invoicing.customers') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <Users class="w-4 h-4 text-emerald-400" />
              <span>Klien / Customer</span>
            </Link>

            <Link
              :href="route('invoicing.invoices.index')"
              class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="isCurrentRoute('invoicing.invoices') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <FileText class="w-4 h-4 text-sky-400" />
              <span>Invoice Tagihan</span>
            </Link>
          </div>
        </div>

        <!-- Section: RENEWAL WEBPRAKTIS -->
        <div v-if="isHrdOrAdmin">
          <p class="px-3.5 text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-2 mt-4">
            RENEWAL
          </p>
          <div class="space-y-1">
            <Link
              :href="route('renewal.vendors.index')"
              class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="isCurrentRoute('renewal.vendors') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <Store class="w-4 h-4 text-violet-400" />
              <span>Master Vendor</span>
            </Link>
            <Link
              :href="route('renewal.domains.index')"
              class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="isCurrentRoute('renewal.domains') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <Globe class="w-4 h-4 text-cyan-400" />
              <span>Domain & Hosting</span>
            </Link>
            <Link
              :href="route('renewal.renewals.index')"
              class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="isCurrentRoute('renewal.renewals') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <RefreshCw class="w-4 h-4 text-emerald-400" />
              <span>Proses Renewal</span>
            </Link>
          </div>
        </div>

        <!-- Section: AKUNTANSI -->
        <div v-if="isHrdOrAdmin">
          <p class="px-3.5 text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-2 mt-4">
            AKUNTANSI
          </p>
          <div class="space-y-1">
            <Link
              :href="route('accounting.coas.index')"
              class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="isCurrentRoute('accounting.coas') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <FileText class="w-4 h-4 text-indigo-400" />
              <span>Master COA</span>
            </Link>
          </div>
        </div>
        <div>
          <p class="px-3.5 text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-2">
            RIWAYAT & STATUS
          </p>
          <div class="space-y-1">
            <Link
              :href="route('riwayat-pengajuan.index')"
              class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="isCurrentRoute('riwayat-pengajuan') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <Clock class="w-4 h-4" />
              <span>Riwayat Pengajuan</span>
            </Link>

            <Link
              :href="route('approval.index')"
              class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="isCurrentRoute('approval.index') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <div class="flex items-center gap-3">
                <CheckSquare class="w-4 h-4 text-amber-400" />
                <span>Persetujuan Saya</span>
              </div>
            </Link>

            <Link
              :href="route('approval.history')"
              class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="isCurrentRoute('approval.history') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <History class="w-4 h-4 text-emerald-400" />
              <span>Riwayat Persetujuan</span>
            </Link>

            <Link
              v-if="isHrdOrAdmin"
              :href="route('admin.reports.index')"
              class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="isCurrentRoute('admin.reports') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <BarChart3 class="w-4 h-4 text-rose-400" />
              <span>Laporan & Rekap</span>
            </Link>

            <Link
              :href="route('notifikasi.index')"
              class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="isCurrentRoute('notifikasi') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <div class="flex items-center gap-3">
                <Bell class="w-4 h-4" />
                <span>Notifikasi</span>
              </div>
              <span
                v-if="page.props.auth.user?.unread_notifications_count > 0"
                class="px-2 py-0.5 text-[10px] font-bold bg-rose-500 text-white rounded-full"
              >
                {{ page.props.auth.user.unread_notifications_count }}
              </span>
            </Link>
          </div>
        </div>

        <!-- Section: ADMIN PANEL (Visible for Admin role) -->
        <div v-if="isAdmin">
          <p class="px-3.5 text-[11px] font-semibold uppercase tracking-wider text-indigo-400 mb-2">
            ADMIN PANEL
          </p>
          <div class="space-y-1">
            <Link
              :href="route('admin.users.index')"
              class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition-all duration-200"
              :class="isCurrentRoute('admin.users') ? 'bg-indigo-600 text-white font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <Users class="w-4 h-4 text-indigo-400" />
              <span>Kelola Pengguna</span>
            </Link>

            <Link
              :href="route('admin.divisions.index')"
              class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition-all duration-200"
              :class="isCurrentRoute('admin.divisions') ? 'bg-indigo-600 text-white font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <Building2 class="w-4 h-4 text-blue-400" />
              <span>Kelola Divisi</span>
            </Link>

            <Link
              :href="route('admin.expense-types.index')"
              class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition-all duration-200"
              :class="isCurrentRoute('admin.expense-types') ? 'bg-indigo-600 text-white font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <FileText class="w-4 h-4 text-emerald-400" />
              <span>Jenis Pengeluaran</span>
            </Link>

            <Link
              :href="route('admin.activity-types.index')"
              class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition-all duration-200"
              :class="isCurrentRoute('admin.activity-types') ? 'bg-indigo-600 text-white font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <Utensils class="w-4 h-4 text-orange-400" />
              <span>Jenis Kegiatan</span>
            </Link>

            <Link
              :href="route('admin.leave-types.index')"
              class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition-all duration-200"
              :class="isCurrentRoute('admin.leave-types') ? 'bg-indigo-600 text-white font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <Calendar class="w-4 h-4 text-purple-400" />
              <span>Jenis Cuti</span>
            </Link>

            <Link
              :href="route('admin.leave-balances.index')"
              class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition-all duration-200"
              :class="isCurrentRoute('admin.leave-balances') ? 'bg-indigo-600 text-white font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <Scale class="w-4 h-4 text-amber-400" />
              <span>Kuota Cuti Karyawan</span>
            </Link>

            <Link
              :href="route('admin.services.index')"
              class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition-all duration-200"
              :class="isCurrentRoute('admin.services') ? 'bg-indigo-600 text-white font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <Layers class="w-4 h-4 text-teal-400" />
              <span>Master Layanan</span>
            </Link>

            <Link
              :href="route('admin.reports.index')"
              class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-xs font-medium transition-all duration-200"
              :class="isCurrentRoute('admin.reports') ? 'bg-indigo-600 text-white font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <BarChart3 class="w-4 h-4 text-rose-400" />
              <span>Laporan & Agregasi</span>
            </Link>
          </div>
        </div>

        <!-- Section: AKUN SAYA -->
        <div>
          <p class="px-3.5 text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-2">
            AKUN SAYA
          </p>
          <div class="space-y-1">
            <Link
              :href="route('profile.edit')"
              class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="isCurrentRoute('profile') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-semibold' : 'hover:bg-slate-800/60 hover:text-white'"
            >
              <User class="w-4 h-4" />
              <span>Profil Saya</span>
            </Link>

            <Link
              :href="route('logout')"
              method="post"
              as="button"
              class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium text-rose-400 hover:bg-rose-500/10 hover:text-rose-300 transition-all duration-200 text-left"
            >
              <LogOut class="w-4 h-4" />
              <span>Keluar</span>
            </Link>
          </div>
        </div>
      </nav>
    </div>
  </aside>
</template>
