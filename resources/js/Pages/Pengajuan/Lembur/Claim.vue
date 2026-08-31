<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FileUploader from '@/Components/FileUploader.vue';
import { ArrowLeft, Send, Receipt, FileText } from 'lucide-vue-next';

const props = defineProps({
  applicant: Object,
  overtimeRequest: Object,
  level2Approvers: Array,
});

const form = useForm({
  actual_start_time: props.overtimeRequest.start_time,
  actual_end_time: props.overtimeRequest.end_time,
  amount: '',
  attachments: [],
});

const submitForm = () => {
  form.post(route('pengajuan.lembur.claim.store', props.overtimeRequest.id), {
    preserveScroll: true,
  });
};
</script>

<template>
  <Head title="Klaim Lembur" />

  <AuthenticatedLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header Bar with Back Button -->
      <div class="flex items-center justify-between bg-white p-4 sm:p-5 rounded-2xl border border-slate-100 shadow-sm">
        <div class="flex items-center gap-3 sm:gap-4">
          <Link
            :href="route('riwayat-pengajuan.show', { type: 'lembur', id: overtimeRequest.id })"
            class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
          >
            <ArrowLeft class="w-5 h-5 stroke-[2]" />
          </Link>
          <div>
            <h1 class="text-base sm:text-lg font-bold text-slate-800">
              Klaim Pencairan Lembur (Tahap 2)
            </h1>
            <p class="text-[11px] sm:text-xs text-slate-400">Ajukan pencairan untuk Rencana Lembur: {{ overtimeRequest.request_number }}</p>
          </div>
        </div>
        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
          <Receipt class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>

      <!-- Detail Rencana Lembur -->
      <div class="bg-slate-50 p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col gap-3">
        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
          <FileText class="w-4 h-4 text-slate-500" /> Referensi Rencana Lembur
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-2 text-xs sm:text-sm">
          <div><span class="font-semibold text-slate-600">Tanggal:</span> {{ new Date(overtimeRequest.date).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) }}</div>
          <div><span class="font-semibold text-slate-600">Waktu Rencana:</span> {{ overtimeRequest.start_time }} - {{ overtimeRequest.end_time }}</div>
          <div class="sm:col-span-2">
            <span class="font-semibold text-slate-600">Target Pekerjaan:</span> 
            <p class="mt-1 text-slate-700">{{ overtimeRequest.task_description }}</p>
          </div>
        </div>
      </div>

      <!-- Form Container -->
      <div class="bg-white p-4 sm:p-6 lg:p-8 rounded-2xl border border-slate-100 shadow-sm space-y-6 sm:space-y-8">
        <div class="space-y-6 sm:space-y-8">
          <!-- Section 1: Aktual Pelaksanaan -->
          <div>
            <h3 class="text-xs sm:text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100">
              Data Aktual Pelaksanaan
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Waktu Aktual Mulai <span class="text-rose-500">*</span></label>
                <input
                  type="time"
                  v-model="form.actual_start_time"
                  class="w-full text-xs sm:text-sm border-slate-200 rounded-xl focus:ring-emerald-500 focus:border-emerald-500"
                />
                <div v-if="form.errors.actual_start_time" class="text-xs text-rose-500 mt-1">{{ form.errors.actual_start_time }}</div>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Waktu Aktual Selesai <span class="text-rose-500">*</span></label>
                <input
                  type="time"
                  v-model="form.actual_end_time"
                  class="w-full text-xs sm:text-sm border-slate-200 rounded-xl focus:ring-emerald-500 focus:border-emerald-500"
                />
                <div v-if="form.errors.actual_end_time" class="text-xs text-rose-500 mt-1">{{ form.errors.actual_end_time }}</div>
              </div>

              <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nominal Klaim Lembur (Rp) <span class="text-rose-500">*</span></label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-slate-500 sm:text-sm">Rp</span>
                  </div>
                  <input
                    type="number"
                    v-model="form.amount"
                    class="w-full pl-10 text-xs sm:text-sm border-slate-200 rounded-xl focus:ring-emerald-500 focus:border-emerald-500"
                    placeholder="0"
                  />
                </div>
                <div v-if="form.errors.amount" class="text-xs text-rose-500 mt-1">{{ form.errors.amount }}</div>
              </div>

            </div>
          </div>

          <!-- Section 2: Lampiran Bukti -->
          <div>
            <h3 class="text-xs sm:text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100">
              Bukti Kegiatan / Hasil Pekerjaan
            </h3>
            
            <FileUploader
              v-model="form.attachments"
              :max-files="5"
              :max-size="5"
              help-text="Upload foto kegiatan atau screenshot hasil kerja. (Maks. 5 file, 5MB/file)"
            />
            <div v-if="form.errors.attachments" class="text-xs text-rose-500 mt-1">{{ form.errors.attachments }}</div>
          </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
          <button
            @click="submitForm"
            :disabled="form.processing"
            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-6 py-2.5 sm:py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-semibold rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Send class="w-4 h-4" />
            <span>Ajukan Pencairan</span>
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
