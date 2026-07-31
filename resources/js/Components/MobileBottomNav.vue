<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Home, FileText, Utensils, Calendar, Plus, X, Clock, CheckSquare } from 'lucide-vue-next';

const page = usePage();
const showQuickMenu = ref(false);

const isCurrentRoute = (routeName) => {
  if (!routeName) return false;
  return route().current(routeName) || route().current(routeName + '.*');
};
</script>

<template>
  <div>
    <!-- Floating Bottom Navigation Bar (Matching mockup_mobile.webp) -->
    <nav class="lg:hidden fixed bottom-4 left-4 right-4 z-40 bg-white/95 backdrop-blur-xl border border-slate-200/80 rounded-2xl shadow-2xl px-4 py-2.5 flex items-center justify-between">
      <!-- Home Link -->
      <Link
        :href="route('dashboard')"
        class="flex flex-col items-center gap-1 p-1.5 rounded-xl transition-all"
        :class="isCurrentRoute('dashboard') ? 'text-indigo-600 font-bold scale-105' : 'text-slate-400 hover:text-slate-600'"
      >
        <Home class="w-5 h-5" />
        <span class="text-[10px] font-semibold">Beranda</span>
      </Link>

      <!-- Reimbursement Link -->
      <Link
        :href="route('pengajuan.reimbursement.create')"
        class="flex flex-col items-center gap-1 p-1.5 rounded-xl transition-all"
        :class="isCurrentRoute('pengajuan.reimbursement') ? 'text-emerald-600 font-bold scale-105' : 'text-slate-400 hover:text-slate-600'"
      >
        <FileText class="w-5 h-5" />
        <span class="text-[10px] font-semibold">Reimburse</span>
      </Link>

      <!-- Operasional Link -->
      <Link
        :href="route('pengajuan.operasional.create')"
        class="flex flex-col items-center gap-1 p-1.5 rounded-xl transition-all"
        :class="isCurrentRoute('pengajuan.operasional') ? 'text-orange-500 font-bold scale-105' : 'text-slate-400 hover:text-slate-600'"
      >
        <Utensils class="w-5 h-5" />
        <span class="text-[10px] font-semibold">Operasional</span>
      </Link>

      <!-- Cuti Link -->
      <Link
        :href="route('pengajuan.cuti.create')"
        class="flex flex-col items-center gap-1 p-1.5 rounded-xl transition-all"
        :class="isCurrentRoute('pengajuan.cuti') ? 'text-purple-600 font-bold scale-105' : 'text-slate-400 hover:text-slate-600'"
      >
        <Calendar class="w-5 h-5" />
        <span class="text-[10px] font-semibold">Cuti</span>
      </Link>

      <!-- FAB Plus Action Button -->
      <button
        @click="showQuickMenu = !showQuickMenu"
        class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center shadow-lg shadow-slate-900/30 hover:scale-105 transition-all ml-1 shrink-0"
      >
        <X v-if="showQuickMenu" class="w-5 h-5" />
        <Plus v-else class="w-5 h-5" />
      </button>
    </nav>

    <!-- Quick Action Modal Drawer when FAB clicked -->
    <div v-if="showQuickMenu" class="lg:hidden fixed inset-0 z-50 flex items-end">
      <div @click="showQuickMenu = false" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm"></div>

      <div class="relative w-full bg-white rounded-t-3xl p-6 shadow-2xl z-10 animate-in slide-in-from-bottom duration-200">
        <div class="w-10 h-1 bg-slate-200 rounded-full mx-auto mb-4"></div>
        <h3 class="text-base font-bold text-slate-900 mb-4">Pilih Aksi Cepat</h3>

        <div class="grid grid-cols-2 gap-3 mb-4">
          <Link
            :href="route('pengajuan.reimbursement.create')"
            @click="showQuickMenu = false"
            class="p-4 rounded-2xl bg-emerald-50 border border-emerald-100 flex flex-col gap-2"
          >
            <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center">
              <FileText class="w-4 h-4" />
            </div>
            <div>
              <p class="text-xs font-bold text-slate-900">Reimbursement</p>
              <p class="text-[10px] text-slate-500">Klaim biaya kerja</p>
            </div>
          </Link>

          <Link
            :href="route('pengajuan.operasional.create')"
            @click="showQuickMenu = false"
            class="p-4 rounded-2xl bg-orange-50 border border-orange-100 flex flex-col gap-2"
          >
            <div class="w-8 h-8 rounded-xl bg-orange-500 text-white flex items-center justify-center">
              <Utensils class="w-4 h-4" />
            </div>
            <div>
              <p class="text-xs font-bold text-slate-900">Operasional</p>
              <p class="text-[10px] text-slate-500">Dana kegiatan</p>
            </div>
          </Link>

          <Link
            :href="route('pengajuan.cuti.create')"
            @click="showQuickMenu = false"
            class="p-4 rounded-2xl bg-purple-50 border border-purple-100 flex flex-col gap-2"
          >
            <div class="w-8 h-8 rounded-xl bg-purple-600 text-white flex items-center justify-center">
              <Calendar class="w-4 h-4" />
            </div>
            <div>
              <p class="text-xs font-bold text-slate-900">Cuti Karyawan</p>
              <p class="text-[10px] text-slate-500">Ajukan izin/cuti</p>
            </div>
          </Link>

          <Link
            :href="route('riwayat-pengajuan.index')"
            @click="showQuickMenu = false"
            class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col gap-2"
          >
            <div class="w-8 h-8 rounded-xl bg-slate-800 text-white flex items-center justify-center">
              <Clock class="w-4 h-4" />
            </div>
            <div>
              <p class="text-xs font-bold text-slate-900">Riwayat Status</p>
              <p class="text-[10px] text-slate-500">Semua pengajuan</p>
            </div>
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>
