<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { ArrowLeft, Clock, FileText, CheckCircle2, User, Building, Paperclip, CreditCard } from 'lucide-vue-next';

const props = defineProps({
  requestData: Object,
});
</script>

<template>
  <Head :title="`Detail ${requestData.request_number}`" />

  <AuthenticatedLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header Navigation -->
      <div class="flex items-center justify-between bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
        <div class="flex items-center gap-4">
          <Link
            :href="route('riwayat-pengajuan.index')"
            class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
          >
            <ArrowLeft class="w-5 h-5 stroke-[2]" />
          </Link>
          <div>
            <div class="flex items-center gap-3">
              <h1 class="text-lg font-bold text-slate-800">
                {{ requestData.request_number }}
              </h1>
              <StatusBadge :status="requestData.status" />
            </div>
            <p class="text-xs text-slate-400 mt-0.5">
              {{ requestData.type_label }} • Diajukan pada {{ requestData.created_at }}
            </p>
          </div>
        </div>
      </div>

      <!-- Applicant Info Card -->
      <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
          <User class="w-4 h-4 text-indigo-500" />
          Informasi Pemohon
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
          <div>
            <span class="text-xs text-slate-400 block">Nama Lengkap</span>
            <span class="font-bold text-slate-800">{{ requestData.applicant.name }}</span>
          </div>
          <div>
            <span class="text-xs text-slate-400 block">NIK</span>
            <span class="font-bold text-slate-800">{{ requestData.applicant.nik }}</span>
          </div>
          <div>
            <span class="text-xs text-slate-400 block">Divisi</span>
            <span class="font-bold text-slate-800">{{ requestData.applicant.division }}</span>
          </div>
          <div>
            <span class="text-xs text-slate-400 block">Jabatan</span>
            <span class="font-bold text-slate-800">{{ requestData.applicant.position }}</span>
          </div>
        </div>
      </div>

      <!-- Details Card -->
      <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-4">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-2">
          <FileText class="w-4 h-4 text-indigo-500" />
          Rincian Pengajuan
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div v-for="(value, key) in requestData.details" :key="key" class="p-3 bg-slate-50 rounded-xl border border-slate-100">
            <span class="text-xs text-slate-400 block mb-0.5">{{ key }}</span>
            <span class="font-semibold text-slate-800">{{ value }}</span>
          </div>
        </div>

        <div v-if="requestData.rejected_reason" class="p-4 bg-rose-50 rounded-xl border border-rose-100 text-rose-800 text-sm">
          <span class="text-xs font-bold uppercase tracking-wider block text-rose-600 mb-1">Alasan Penolakan:</span>
          <p class="font-medium">{{ requestData.rejected_reason }}</p>
        </div>
      </div>

      <!-- Attachments Card -->
      <div v-if="requestData.attachments && requestData.attachments.length > 0" class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
          <Paperclip class="w-4 h-4 text-indigo-500" />
          Lampiran File ({{ requestData.attachments.length }})
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <a
            v-for="file in requestData.attachments"
            :key="file.id"
            :href="`/storage/${file.file_path}`"
            target="_blank"
            class="p-3 bg-slate-50 hover:bg-slate-100 rounded-xl border border-slate-100 flex items-center gap-3 transition-colors"
          >
            <FileText class="w-5 h-5 text-indigo-600 shrink-0" />
            <div class="truncate text-xs">
              <span class="font-semibold text-slate-800 truncate block">{{ file.file_name }}</span>
              <span class="text-slate-400">Buka file</span>
            </div>
          </a>
        </div>
      </div>

      <!-- Timeline & Audit Trail -->
      <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-6 pb-2 border-b border-slate-100 flex items-center gap-2">
          <Clock class="w-4 h-4 text-indigo-500" />
          Riwayat Timeline Status
        </h3>

        <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
          <div v-for="log in requestData.status_histories" :key="log.id" class="relative">
            <div class="absolute -left-6 top-0.5 w-4 h-4 rounded-full bg-indigo-600 border-2 border-white shadow-sm"></div>
            <div>
              <p class="text-xs font-bold text-slate-800">
                Status berubah ke <span class="text-indigo-600 uppercase">{{ log.to_status }}</span>
              </p>
              <p class="text-xs text-slate-500 mt-0.5">
                {{ log.notes || 'Perubahan status sistem' }}
              </p>
              <span class="text-[10px] text-slate-400 font-medium block mt-1">
                {{ log.created_at }} • Oleh {{ log.changed_by?.name || 'Sistem' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
