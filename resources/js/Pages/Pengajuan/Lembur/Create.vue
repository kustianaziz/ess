<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Stepper from '@/Components/Stepper.vue';
import { ArrowLeft, Clock, Save, Send } from 'lucide-vue-next';

const props = defineProps({
  leaders: Array,
});

const currentStep = ref(1);

const form = useForm({
  date: '',
  start_time: '',
  end_time: '',
  task_description: '',
  leader_id: '',
});

const submitForm = () => {
  form.post(route('pengajuan.lembur.store'), {
    preserveScroll: true,
  });
};
</script>

<template>
  <Head title="Pengajuan Lembur" />

  <AuthenticatedLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header Bar with Back Button -->
      <div class="flex items-center justify-between bg-white p-4 sm:p-5 rounded-2xl border border-slate-100 shadow-sm">
        <div class="flex items-center gap-3 sm:gap-4">
          <Link
            :href="route('dashboard')"
            class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
          >
            <ArrowLeft class="w-5 h-5 stroke-[2]" />
          </Link>
          <div>
            <h1 class="text-base sm:text-lg font-bold text-slate-800">
              Rencana Lembur (Tahap 1)
            </h1>
            <p class="text-[11px] sm:text-xs text-slate-400">Isi formulir rencana kegiatan lembur sebelum pelaksanaan</p>
          </div>
        </div>
        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center shrink-0">
          <Clock class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>

      <!-- Form Container -->
      <div class="bg-white p-4 sm:p-6 lg:p-8 rounded-2xl border border-slate-100 shadow-sm space-y-6 sm:space-y-8">
        <div class="space-y-6 sm:space-y-8">
          <!-- Section 1: Informasi Rencana -->
          <div>
            <h3 class="text-xs sm:text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100">
              Informasi Rencana Lembur
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
              <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Rencana Lembur <span class="text-rose-500">*</span></label>
                <input
                  type="date"
                  v-model="form.date"
                  class="w-full text-xs sm:text-sm border-slate-200 rounded-xl focus:ring-orange-500 focus:border-orange-500"
                />
                <div v-if="form.errors.date" class="text-xs text-rose-500 mt-1">{{ form.errors.date }}</div>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Waktu Mulai <span class="text-rose-500">*</span></label>
                <input
                  type="time"
                  v-model="form.start_time"
                  class="w-full text-xs sm:text-sm border-slate-200 rounded-xl focus:ring-orange-500 focus:border-orange-500"
                />
                <div v-if="form.errors.start_time" class="text-xs text-rose-500 mt-1">{{ form.errors.start_time }}</div>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Waktu Selesai <span class="text-rose-500">*</span></label>
                <input
                  type="time"
                  v-model="form.end_time"
                  class="w-full text-xs sm:text-sm border-slate-200 rounded-xl focus:ring-orange-500 focus:border-orange-500"
                />
                <div v-if="form.errors.end_time" class="text-xs text-rose-500 mt-1">{{ form.errors.end_time }}</div>
              </div>

              <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Target / Deskripsi Pekerjaan <span class="text-rose-500">*</span></label>
                <textarea
                  v-model="form.task_description"
                  rows="4"
                  placeholder="Jelaskan target pekerjaan yang akan diselesaikan saat lembur"
                  class="w-full text-xs sm:text-sm border-slate-200 rounded-xl focus:ring-orange-500 focus:border-orange-500 resize-none"
                ></textarea>
                <div v-if="form.errors.task_description" class="text-xs text-rose-500 mt-1">{{ form.errors.task_description }}</div>
              </div>

              <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pilih Atasan Langsung (Level 1) <span class="text-rose-500">*</span></label>
                <select
                  v-model="form.leader_id"
                  class="w-full text-xs sm:text-sm border-slate-200 rounded-xl focus:ring-orange-500 focus:border-orange-500 bg-white"
                >
                  <option value="" disabled>-- Pilih Atasan Langsung (Leader/Manager) --</option>
                  <option v-for="user in leaders" :key="user.id" :value="user.id">
                    {{ user.name }} <span v-if="user.position">- {{ user.position }}</span>
                  </option>
                </select>
                <div v-if="form.errors.leader_id" class="text-xs text-rose-500 mt-1">{{ form.errors.leader_id }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
          <button
            @click="submitForm"
            :disabled="form.processing"
            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-6 py-2.5 sm:py-3 bg-orange-600 hover:bg-orange-700 text-white text-xs sm:text-sm font-semibold rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Send class="w-4 h-4" />
            <span>Kirim Rencana</span>
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
