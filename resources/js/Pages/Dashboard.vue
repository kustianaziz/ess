<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ServiceCard from '@/Components/ServiceCard.vue';
import SummaryCard from '@/Components/SummaryCard.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ChevronRight, Plus, ShieldCheck } from 'lucide-vue-next';

const props = defineProps({
  user: Object,
  summaryCounts: Object,
  recentRequests: {
    type: Array,
    default: () => [],
  },
});

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
</script>

<template>
  <Head title="Beranda" />

  <AuthenticatedLayout>
    <div class="space-y-6 max-w-7xl mx-auto">
      
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
          <div class="grid grid-cols-3 gap-6">
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
  </AuthenticatedLayout>
</template>
