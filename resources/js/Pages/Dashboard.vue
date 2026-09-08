<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ServiceCard from '@/Components/ServiceCard.vue';
import SummaryCard from '@/Components/SummaryCard.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
  ChevronRight, 
  Plus, 
  ShieldCheck, 
  Clock, 
  CheckCircle2, 
  AlertCircle, 
  Users, 
  Calendar, 
  ArrowRight, 
  FileText, 
  DollarSign,
  Palmtree,
  Sparkles
} from 'lucide-vue-next';

const props = defineProps({
  user: Object,
  summaryCounts: Object,
  recentRequests: {
    type: Array,
    default: () => [],
  },
  isApprover: {
    type: Boolean,
    default: false,
  },
  approverDashboard: {
    type: Object,
    default: null,
  },
});

const activeTab = ref(props.isApprover ? 'team' : 'personal');

const getStatusColor = (status) => {
  switch (status) {
    case 'submitted': return 'text-amber-600 bg-amber-500';
    case 'approved': return 'text-emerald-600 bg-emerald-500';
    case 'rejected': return 'text-rose-600 bg-rose-500';
    case 'paid': return 'text-sky-600 bg-sky-500';
    case 'completed':
    default: return 'text-slate-500 bg-slate-400';
  }
};

const getTypeBadgeColor = (type) => {
  switch (type) {
    case 'lembur': return 'bg-indigo-50 text-indigo-700 border-indigo-200';
    case 'klaim-lembur': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    case 'cuti': return 'bg-purple-50 text-purple-700 border-purple-200';
    case 'reimbursement': return 'bg-blue-50 text-blue-700 border-blue-200';
    case 'operasional': return 'bg-amber-50 text-amber-700 border-amber-200';
    case 'perjalanan-dinas': return 'bg-sky-50 text-sky-700 border-sky-200';
    default: return 'bg-slate-50 text-slate-700 border-slate-200';
  }
};
</script>

<template>
  <Head title="Beranda" />

  <AuthenticatedLayout>
    <div class="space-y-6 max-w-7xl mx-auto">
      
      <!-- APPROVER / EXECUTIVE TAB SELECTOR (Only shown if user is an Approver or Manager) -->
      <div v-if="isApprover" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3 rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="flex items-center gap-2">
          <button
            @click="activeTab = 'team'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 relative"
            :class="activeTab === 'team' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
          >
            <ShieldCheck class="w-4 h-4" />
            <span>Monitoring Approval</span>
            <span
              v-if="approverDashboard?.pending_count > 0"
              class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-500 text-white animate-pulse"
            >
              {{ approverDashboard.pending_count }}
            </span>
          </button>

          <button
            @click="activeTab = 'personal'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2"
            :class="activeTab === 'personal' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
          >
            <FileText class="w-4 h-4" />
            <span>Pengajuan Pribadi Saya</span>
          </button>
        </div>

        <Link
          :href="route('approval.index')"
          class="text-xs font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1.5 bg-indigo-50/80 px-3.5 py-2 rounded-xl border border-indigo-100 hover:bg-indigo-100 transition-all self-start sm:self-auto"
        >
          <span>Daftar Lengkap Approval</span>
          <ChevronRight class="w-4 h-4" />
        </Link>
      </div>

      <!-- APPROVER / EXECUTIVE CONTENT VIEW -->
      <div v-if="isApprover && activeTab === 'team'" class="space-y-6">
        <!-- 1. Executive Welcome & Quick Stats Hero (Bright & Positive Modern Executive Theme) -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-sm relative overflow-hidden">
          <div class="absolute -right-16 -top-16 w-80 h-80 bg-indigo-50/70 rounded-full blur-3xl pointer-events-none"></div>
          <div class="absolute right-1/3 -bottom-16 w-72 h-72 bg-emerald-50/60 rounded-full blur-3xl pointer-events-none"></div>
          
          <div class="relative z-10 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div>
                <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                  <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 uppercase tracking-wider flex items-center gap-1.5">
                    <Sparkles class="w-3.5 h-3.5 text-indigo-500" />
                    <span>Portal Pimpinan • {{ user.position }}</span>
                  </span>
                  <span class="text-xs font-semibold text-slate-500">Divisi {{ user.division }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                  Selamat Datang, {{ user.name }}
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 mt-1 max-w-2xl">
                  Supervisi <strong class="text-indigo-600 font-bold">{{ approverDashboard?.subordinates_count || 0 }} anggota tim</strong>. Berikut ringkasan produktivitas dan persetujuan pengajuan tim yang siap Anda tinjau.
                </p>
              </div>

              <div class="flex items-center gap-2">
                <Link
                  :href="route('approval.index')"
                  class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-200 hover:shadow-indigo-300 transition-all flex items-center gap-2 shrink-0"
                >
                  <ShieldCheck class="w-4 h-4" />
                  <span>Buka Antrean Approval</span>
                </Link>
              </div>
            </div>

            <!-- Top Executive Summary Cards Grid (Bright & Positive Color Accents) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 pt-1">
              <!-- Pending Approvals -->
              <div 
                class="p-4 sm:p-5 rounded-2xl border transition-all"
                :class="approverDashboard?.pending_count > 0 
                  ? 'bg-gradient-to-br from-amber-50/90 via-amber-50/40 to-white border-amber-200/90 shadow-sm' 
                  : 'bg-gradient-to-br from-slate-50 to-white border-slate-200/80'"
              >
                <div class="flex items-center justify-between">
                  <span class="text-[11px] font-bold uppercase tracking-wider" :class="approverDashboard?.pending_count > 0 ? 'text-amber-800' : 'text-slate-500'">
                    Menunggu Persetujuan
                  </span>
                  <span 
                    class="w-2.5 h-2.5 rounded-full"
                    :class="approverDashboard?.pending_count > 0 ? 'bg-amber-500 animate-ping' : 'bg-emerald-500'"
                  ></span>
                </div>
                <div class="mt-2.5 flex items-baseline gap-1.5">
                  <span class="text-2xl sm:text-3xl font-black" :class="approverDashboard?.pending_count > 0 ? 'text-amber-600' : 'text-slate-800'">
                    {{ approverDashboard?.pending_count || 0 }}
                  </span>
                  <span class="text-xs font-bold text-slate-500">Pengajuan</span>
                </div>
                <p class="text-[11px] font-medium mt-1 truncate" :class="approverDashboard?.pending_count > 0 ? 'text-amber-700' : 'text-slate-400'">
                  {{ approverDashboard?.pending_count > 0 ? 'Perlu konfirmasi Anda' : 'Semua pengajuan telah tuntas' }}
                </p>
              </div>

              <!-- Total Nominal Pending -->
              <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-br from-emerald-50/90 via-emerald-50/30 to-white border border-emerald-200/90 shadow-sm">
                <div class="flex items-center justify-between">
                  <span class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider">Total Nominal Klaim</span>
                  <div class="w-7 h-7 rounded-lg bg-emerald-100/80 text-emerald-700 flex items-center justify-center">
                    <DollarSign class="w-4 h-4" />
                  </div>
                </div>
                <div class="mt-2.5">
                  <span class="text-xl sm:text-2xl font-black text-emerald-700 block truncate">
                    {{ approverDashboard?.pending_amount_formatted || 'Rp 0' }}
                  </span>
                </div>
                <p class="text-[11px] font-medium text-emerald-600/90 mt-1 truncate">
                  Estimasi dana menunggu approval
                </p>
              </div>

              <!-- Approved This Month -->
              <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-br from-indigo-50/90 via-indigo-50/30 to-white border border-indigo-200/90 shadow-sm">
                <div class="flex items-center justify-between">
                  <span class="text-[11px] font-bold text-indigo-800 uppercase tracking-wider">Disetujui Bulan Ini</span>
                  <div class="w-7 h-7 rounded-lg bg-indigo-100/80 text-indigo-700 flex items-center justify-center">
                    <CheckCircle2 class="w-4 h-4" />
                  </div>
                </div>
                <div class="mt-2.5 flex items-baseline gap-1.5">
                  <span class="text-2xl sm:text-3xl font-black text-indigo-700">
                    {{ approverDashboard?.approved_this_month || 0 }}
                  </span>
                  <span class="text-xs font-bold text-indigo-500">Selesai</span>
                </div>
                <p class="text-[11px] font-medium text-indigo-600/90 mt-1 truncate">
                  Persetujuan lancar bulan ini
                </p>
              </div>

              <!-- Team Presence / Absence -->
              <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-br from-purple-50/90 via-purple-50/30 to-white border border-purple-200/90 shadow-sm">
                <div class="flex items-center justify-between">
                  <span class="text-[11px] font-bold text-purple-800 uppercase tracking-wider">Staf Cuti / Izin</span>
                  <div class="w-7 h-7 rounded-lg bg-purple-100/80 text-purple-700 flex items-center justify-center">
                    <Palmtree class="w-4 h-4" />
                  </div>
                </div>
                <div class="mt-2.5 flex items-baseline gap-1.5">
                  <span class="text-2xl sm:text-3xl font-black text-purple-700">
                    {{ approverDashboard?.team_leave_today?.length || 0 }}
                  </span>
                  <span class="text-xs font-bold text-purple-500">Staf Hari Ini</span>
                </div>
                <p class="text-[11px] font-medium text-purple-600/90 mt-1 truncate">
                  {{ approverDashboard?.team_leave_today?.length > 0 ? 'Sedang izin tidak hadir' : 'Seluruh staf aktif hadir' }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. Breakdown Kategori Antrean (Mini Chips) -->
        <div v-if="approverDashboard?.pending_count > 0" class="flex flex-wrap items-center gap-2 bg-white p-3 rounded-2xl border border-slate-200/80 shadow-sm">
          <span class="text-xs font-bold text-slate-500 uppercase tracking-wider mr-1">Rincian Antrean:</span>
          
          <span v-if="approverDashboard.pending_by_type['klaim-lembur'] > 0" class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
            Klaim Lembur: {{ approverDashboard.pending_by_type['klaim-lembur'] }}
          </span>

          <span v-if="approverDashboard.pending_by_type['lembur'] > 0" class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
            Rencana Lembur: {{ approverDashboard.pending_by_type['lembur'] }}
          </span>

          <span v-if="approverDashboard.pending_by_type['reimbursement'] > 0" class="px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
            Reimbursement: {{ approverDashboard.pending_by_type['reimbursement'] }}
          </span>

          <span v-if="approverDashboard.pending_by_type['operasional'] > 0" class="px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
            Operasional: {{ approverDashboard.pending_by_type['operasional'] }}
          </span>

          <span v-if="approverDashboard.pending_by_type['cuti'] > 0" class="px-3 py-1 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
            Cuti: {{ approverDashboard.pending_by_type['cuti'] }}
          </span>

          <span v-if="approverDashboard.pending_by_type['perjalanan-dinas'] > 0" class="px-3 py-1 rounded-full text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200">
            Perjalanan Dinas: {{ approverDashboard.pending_by_type['perjalanan-dinas'] }}
          </span>
        </div>

        <!-- 3. Section Antrean Approval Mendesak (Quick Review Feed) -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
              <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <Clock class="w-5 h-5 text-indigo-600" />
                <span>Pengajuan Menunggu Persetujuan Anda</span>
              </h2>
              <p class="text-xs text-slate-500 mt-0.5">
                Daftar pengajuan bawahan teratas yang memerlukan tanda tangan / koreksi nominal.
              </p>
            </div>

            <Link
              :href="route('approval.index')"
              class="text-xs font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1 group"
            >
              <span>Buka Semua ({{ approverDashboard?.pending_count || 0 }})</span>
              <ChevronRight class="w-4 h-4 transition-transform group-hover:translate-x-1" />
            </Link>
          </div>

          <!-- Pending Items List -->
          <div v-if="approverDashboard?.pending_items?.length > 0" class="divide-y divide-slate-100">
            <div
              v-for="item in approverDashboard.pending_items"
              :key="item.type + item.id"
              class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50/70 p-2 rounded-2xl transition-all"
            >
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-sm shrink-0 border border-indigo-200">
                  {{ item.applicant_name.charAt(0).toUpperCase() }}
                </div>
                <div>
                  <div class="flex items-center gap-2 flex-wrap">
                    <h4 class="text-sm font-bold text-slate-900">{{ item.applicant_name }}</h4>
                    <span 
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider"
                      :class="getTypeBadgeColor(item.type)"
                    >
                      {{ item.type_label }} (L{{ item.level }})
                    </span>
                  </div>
                  <div class="flex items-center gap-2 text-xs text-slate-500 mt-0.5 flex-wrap">
                    <span class="font-medium text-slate-700">{{ item.request_number }}</span>
                    <span>•</span>
                    <span>{{ item.applicant_position }} ({{ item.applicant_division }})</span>
                    <span>•</span>
                    <span class="text-slate-400">{{ item.submitted_at }}</span>
                  </div>
                </div>
              </div>

              <div class="flex items-center justify-between sm:justify-end gap-4 shrink-0 pl-12 sm:pl-0">
                <div class="text-left sm:text-right">
                  <span v-if="item.amount_formatted" class="text-sm font-black text-slate-900 block">
                    {{ item.amount_formatted }}
                  </span>
                  <span class="text-xs text-slate-500 block">
                    {{ item.summary_info }}
                  </span>
                </div>

                <Link
                  :href="item.url"
                  class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-all shadow-sm flex items-center gap-1.5 whitespace-nowrap"
                >
                  <span>Tinjau & Setujui</span>
                  <ArrowRight class="w-3.5 h-3.5" />
                </Link>
              </div>
            </div>
          </div>

          <!-- Empty State When Zero Pending -->
          <div v-else class="py-10 text-center space-y-3">
            <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto border border-emerald-200">
              <CheckCircle2 class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-sm font-bold text-slate-800">Antrean Persetujuan Bersih!</h3>
              <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                Seluruh pengajuan dari anggota tim Anda telah diproses. Saat ini tidak ada yang membutuhkan persetujuan Anda.
              </p>
            </div>
          </div>
        </div>

        <!-- 4. Team Presence & Activity Monitoring (2 Columns) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <!-- Staf Cuti Hari Ini -->
          <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <Palmtree class="w-4 h-4 text-purple-600" />
                <span>Staf yang Cuti / Izin Hari Ini</span>
              </h3>
              <span class="text-xs font-bold text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full border border-purple-200">
                {{ approverDashboard?.team_leave_today?.length || 0 }} Staf
              </span>
            </div>

            <div v-if="approverDashboard?.team_leave_today?.length > 0" class="divide-y divide-slate-100">
              <div
                v-for="(staf, idx) in approverDashboard.team_leave_today"
                :key="idx"
                class="py-2.5 flex items-center justify-between text-xs"
              >
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-full bg-purple-100 text-purple-700 font-bold flex items-center justify-center text-[11px]">
                    {{ staf.name.charAt(0).toUpperCase() }}
                  </div>
                  <div>
                    <h5 class="font-bold text-slate-800">{{ staf.name }}</h5>
                    <p class="text-[11px] text-slate-400">{{ staf.position }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <span class="font-semibold text-purple-700 block">{{ staf.leave_type }}</span>
                  <span class="text-[10px] text-slate-400">{{ staf.dates }}</span>
                </div>
              </div>
            </div>

            <div v-else class="py-6 text-center text-xs text-slate-400">
              Semua anggota tim hadir hari ini (tidak ada yang cuti).
            </div>
          </div>

          <!-- Jadwal Lembur Hari Ini -->
          <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-5 space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <Clock class="w-4 h-4 text-indigo-600" />
                <span>Jadwal Lembur Hari Ini</span>
              </h3>
              <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full border border-indigo-200">
                {{ approverDashboard?.team_overtime_today?.length || 0 }} Staf
              </span>
            </div>

            <div v-if="approverDashboard?.team_overtime_today?.length > 0" class="divide-y divide-slate-100">
              <div
                v-for="(staf, idx) in approverDashboard.team_overtime_today"
                :key="idx"
                class="py-2.5 flex items-center justify-between text-xs"
              >
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-[11px]">
                    {{ staf.name.charAt(0).toUpperCase() }}
                  </div>
                  <div>
                    <h5 class="font-bold text-slate-800">{{ staf.name }}</h5>
                    <p class="text-[11px] text-slate-400 max-w-[200px] truncate">{{ staf.task }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <span class="font-bold text-indigo-700 block">{{ staf.hours }}</span>
                </div>
              </div>
            </div>

            <div v-else class="py-6 text-center text-xs text-slate-400">
              Tidak ada jadwal lembur tim untuk hari ini.
            </div>
          </div>
        </div>
      </div>

      <!-- PERSONAL / EMPLOYEE VIEW (Shown to all employees, and to approvers when tab is 'personal') -->
      <div v-if="!isApprover || activeTab === 'personal'" class="space-y-6">
        <!-- MOBILE DASHBOARD VIEW (Matching mockup_mobile.webp) -->
        <div class="lg:hidden space-y-5">
          <!-- 1. Top White Hero Stat Card -->
          <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-xs text-slate-400 font-semibold tracking-wide">Ringkasan Portal ESS</span>
              <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-full text-[10px] font-bold border border-emerald-200">
                <ShieldCheck class="w-3 h-3" /> System Verified
              </span>
            </div>

            <div>
              <h1 class="text-3xl font-black text-slate-900 tracking-tight">
                {{ summaryCounts.approved + summaryCounts.completed }} Disetujui
              </h1>
              <p class="text-xs text-slate-500 font-medium mt-1">
                {{ summaryCounts.pending_approval }} Pengajuan sedang menunggu persetujuan
              </p>
            </div>

            <!-- Timeline Stepper Dots (Approved -> Payment -> Paid out style) -->
            <div class="pt-3 border-t border-slate-100">
              <div class="relative flex items-center justify-between text-[11px] font-bold">
                <div class="absolute left-0 top-2 -translate-y-1/2 w-full h-0.5 bg-slate-200 border-dashed border-t border-slate-300"></div>
                
                <div class="flex flex-col items-center bg-white px-2 z-10">
                  <div class="w-3.5 h-3.5 rounded-full bg-amber-400 ring-4 ring-amber-100"></div>
                  <span class="mt-1 text-slate-600">Menunggu ({{ summaryCounts.pending_approval }})</span>
                </div>

                <div class="flex flex-col items-center bg-white px-2 z-10">
                  <div class="w-3.5 h-3.5 rounded-full bg-emerald-500 ring-4 ring-emerald-100"></div>
                  <span class="mt-1 text-slate-600">Disetujui ({{ summaryCounts.approved }})</span>
                </div>

                <div class="flex flex-col items-center bg-white px-2 z-10">
                  <div class="w-3.5 h-3.5 rounded-full bg-sky-500 ring-4 ring-sky-100"></div>
                  <span class="mt-1 text-slate-600">Dibayar ({{ summaryCounts.paid }})</span>
                </div>
              </div>
            </div>
          </div>

          <!-- 2. Dark Horizontal Floating Quick Action Pills Bar -->
          <div class="bg-[#0F172A] p-2.5 rounded-2xl shadow-xl flex items-center gap-2 overflow-x-auto custom-scrollbar">
            <Link
              :href="route('pengajuan.reimbursement.create')"
              class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold whitespace-nowrap border border-slate-700 shrink-0"
            >
              <Plus class="w-4 h-4 text-emerald-400" />
              <span>Add Reimbursement</span>
            </Link>

            <Link
              :href="route('pengajuan.operasional.create')"
              class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold whitespace-nowrap border border-slate-700 shrink-0"
            >
              <Plus class="w-4 h-4 text-orange-400" />
              <span>Add Operasional</span>
            </Link>

            <Link
              :href="route('pengajuan.cuti.create')"
              class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold whitespace-nowrap border border-slate-700 shrink-0"
            >
              <Plus class="w-4 h-4 text-purple-400" />
              <span>Add Cuti</span>
            </Link>

            <Link
              :href="route('pengajuan.lembur.create')"
              class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold whitespace-nowrap border border-slate-700 shrink-0"
            >
              <Plus class="w-4 h-4 text-indigo-400" />
              <span>Add Lembur</span>
            </Link>
          </div>

          <!-- 3. Bottom White Sheet Container (Recent Activities List matching mockup_mobile.webp) -->
          <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm space-y-4">
            <!-- Grab Handle Pill -->
            <div class="w-10 h-1 bg-slate-200 rounded-full mx-auto"></div>

            <div class="flex items-center justify-between">
              <h3 class="text-sm font-bold text-slate-900">Aktivitas & Pengajuan Terbaru</h3>
              <Link :href="route('riwayat-pengajuan.index')" class="text-xs font-bold text-indigo-600 hover:text-indigo-700">
                View all
              </Link>
            </div>

            <!-- List Rows -->
            <div class="divide-y divide-slate-100">
              <div v-for="item in recentRequests" :key="item.type + item.id" class="py-3 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                  <div class="w-10 text-center shrink-0">
                    <span class="text-[10px] font-bold text-slate-400 block uppercase leading-none">{{ item.date?.split(' ')[0] }}</span>
                    <span class="text-sm font-black text-slate-800 leading-tight">{{ item.date?.split(' ')[1] }}</span>
                  </div>
                  <div>
                    <h4 class="text-xs font-bold text-slate-900">{{ item.request_number }}</h4>
                    <p class="text-[11px] text-slate-400 font-medium">{{ item.category }}</p>
                  </div>
                </div>

                <div class="text-right shrink-0">
                  <p class="text-xs font-bold text-slate-900">{{ item.amount }}</p>
                  <div class="flex items-center justify-end gap-1.5 mt-0.5">
                    <span class="w-2 h-2 rounded-full" :class="getStatusColor(item.status).split(' ')[1]"></span>
                    <span class="text-[10px] font-bold capitalize" :class="getStatusColor(item.status).split(' ')[0]">{{ item.status_label }}</span>
                  </div>
                </div>
              </div>

              <div v-if="recentRequests.length === 0" class="py-8 text-center text-slate-400 text-xs">
                Belum ada aktivitas pengajuan terbaru.
              </div>
            </div>
          </div>
        </div>

        <!-- DESKTOP DASHBOARD VIEW -->
        <div class="hidden lg:block space-y-10">
          <!-- Section 1: Pilih Layanan Pengajuan -->
          <section>
            <h2 class="text-lg font-bold text-slate-800 tracking-tight mb-5">
              Pilih Layanan Pengajuan
            </h2>
            <div class="grid grid-cols-4 gap-6">
              <ServiceCard
                title="Reimbursement Karyawan"
                description="Ajukan penggantian biaya yang telah dikeluarkan untuk keperluan pekerjaan."
                :href="route('pengajuan.reimbursement.create')"
                button-text="Ajukan Reimbursement"
                variant="green"
              />
              <ServiceCard
                title="Konsumsi / Operasional"
                description="Ajukan kebutuhan konsumsi atau biaya operasional lainnya untuk kegiatan perusahaan."
                :href="route('pengajuan.operasional.create')"
                button-text="Ajukan Konsumsi / Operasional"
                variant="orange"
              />
              <ServiceCard
                title="Cuti Karyawan"
                description="Ajukan cuti sesuai jenis dan kebutuhan Anda."
                :href="route('pengajuan.cuti.create')"
                button-text="Ajukan Cuti"
                variant="purple"
              />
              <ServiceCard
                title="Lembur"
                description="Ajukan rencana lembur dan klaim uang lembur Anda."
                :href="route('pengajuan.lembur.create')"
                button-text="Ajukan Lembur"
                variant="indigo"
              />
            </div>
          </section>

          <!-- Section 2: Ringkasan Pengajuan Saya -->
          <section>
            <div class="flex items-center justify-between mb-5">
              <h2 class="text-lg font-bold text-slate-800 tracking-tight">
                Ringkasan Pengajuan Saya
              </h2>
              <Link
                :href="route('riwayat-pengajuan.index')"
                class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 flex items-center gap-1 group transition-all"
              >
                <span>Lihat Semua Riwayat</span>
                <ChevronRight class="w-4 h-4 transition-transform group-hover:translate-x-1" />
              </Link>
            </div>

            <div class="grid grid-cols-5 gap-4">
              <SummaryCard type="pending" :count="summaryCounts.pending_approval" />
              <SummaryCard type="approved" :count="summaryCounts.approved" />
              <SummaryCard type="rejected" :count="summaryCounts.rejected" />
              <SummaryCard type="paid" :count="summaryCounts.paid" />
              <SummaryCard type="completed" :count="summaryCounts.completed" />
            </div>
          </section>
        </div>
      </div>

    </div>
  </AuthenticatedLayout>
</template>
