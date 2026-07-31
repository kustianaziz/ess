<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Stepper from '@/Components/Stepper.vue';
import FileUploader from '@/Components/FileUploader.vue';
import { ArrowLeft, ArrowRight, Save, Send, Utensils } from 'lucide-vue-next';

const props = defineProps({
  applicant: Object,
  activityTypes: Array,
});

const currentStep = ref(1);

const form = useForm({
  activity_type_id: '',
  activity_date: '',
  activity_name: '',
  purpose: '',
  participant_count: '',
  estimated_cost: '',
  location: '',
  attachments: [],
  action: 'submit',
});

const displayEstimatedCost = ref(form.estimated_cost ? 'Rp ' + new Intl.NumberFormat('id-ID').format(form.estimated_cost) : '');

const handleEstimatedCostInput = (e) => {
  const rawValue = e.target.value.replace(/\D/g, '');
  if (!rawValue) {
    form.estimated_cost = '';
    displayEstimatedCost.value = '';
    return;
  }
  const numeric = parseInt(rawValue, 10);
  form.estimated_cost = numeric;
  displayEstimatedCost.value = 'Rp ' + new Intl.NumberFormat('id-ID').format(numeric);
};

const nextStep = () => {
  if (currentStep.value === 1) {
    if (!form.activity_type_id || !form.activity_date || !form.activity_name || !form.purpose || !form.participant_count || !form.estimated_cost || !form.location) {
      alert('Harap lengkapi seluruh kolom informasi kegiatan konsumsi/operasional.');
      return;
    }
  }
  if (currentStep.value < 3) {
    currentStep.value++;
  }
};

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--;
  }
};

const saveDraft = () => {
  form.action = 'draft';
  form.post(route('pengajuan.operasional.store'), {
    preserveScroll: true,
  });
};

const submitForm = () => {
  form.action = 'submit';
  form.post(route('pengajuan.operasional.store'), {
    preserveScroll: true,
  });
};

const formatCurrency = (val) => {
  if (!val) return 'Rp 0';
  return 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
};
</script>

<template>
  <Head title="Pengajuan Konsumsi / Operasional" />

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
              Pengajuan Konsumsi / Operasional
            </h1>
            <p class="text-[11px] sm:text-xs text-slate-400">Isi formulir kebutuhan konsumsi atau operasional kegiatan</p>
          </div>
        </div>
        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-orange-50 text-orange-500 flex items-center justify-center shrink-0">
          <Utensils class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>

      <!-- Stepper Component -->
      <div class="bg-white p-3 sm:p-4 rounded-2xl border border-slate-100 shadow-sm">
        <Stepper :current-step="currentStep" accent-color="orange" />
      </div>

      <!-- Form Container -->
      <div class="bg-white p-4 sm:p-6 lg:p-8 rounded-2xl border border-slate-100 shadow-sm space-y-6 sm:space-y-8">
        <!-- STEP 1: Informasi -->
        <div v-if="currentStep === 1" class="space-y-8">
          <!-- Section 1: Informasi Kegiatan -->
          <div>
            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100">
              Informasi Kegiatan
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Kegiatan <span class="text-rose-500">*</span></label>
                <input
                  type="date"
                  v-model="form.activity_date"
                  class="w-full text-sm border-slate-200 rounded-xl focus:ring-orange-500 focus:border-orange-500"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jenis Kegiatan <span class="text-rose-500">*</span></label>
                <select
                  v-model="form.activity_type_id"
                  class="w-full text-sm border-slate-200 rounded-xl focus:ring-orange-500 focus:border-orange-500"
                >
                  <option value="" disabled>Pilih jenis kegiatan</option>
                  <option v-for="type in activityTypes" :key="type.id" :value="type.id">
                    {{ type.name }}
                  </option>
                </select>
              </div>
              <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Kegiatan <span class="text-rose-500">*</span></label>
                <input
                  type="text"
                  v-model="form.activity_name"
                  placeholder="Masukkan nama kegiatan"
                  class="w-full text-sm border-slate-200 rounded-xl focus:ring-orange-500 focus:border-orange-500"
                />
              </div>
              <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tujuan / Keterangan <span class="text-rose-500">*</span></label>
                <textarea
                  v-model="form.purpose"
                  rows="3"
                  placeholder="Jelaskan tujuan dan keterangan kegiatan"
                  class="w-full text-sm border-slate-200 rounded-xl focus:ring-orange-500 focus:border-orange-500 resize-none"
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Section 2: Detail Kegiatan -->
          <div>
            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100">
              Detail Kegiatan
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jumlah Peserta <span class="text-rose-500">*</span></label>
                <input
                  type="number"
                  v-model="form.participant_count"
                  placeholder="Masukkan jumlah peserta"
                  class="w-full text-sm border-slate-200 rounded-xl focus:ring-orange-500 focus:border-orange-500"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Estimasi Biaya <span class="text-rose-500">*</span></label>
                <input
                  type="text"
                  :value="displayEstimatedCost"
                  @input="handleEstimatedCostInput"
                  placeholder="Rp 0"
                  class="w-full text-sm border-slate-200 rounded-xl focus:ring-orange-500 focus:border-orange-500 font-semibold text-orange-700"
                />
              </div>
              <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Lokasi <span class="text-rose-500">*</span></label>
                <input
                  type="text"
                  v-model="form.location"
                  placeholder="Masukkan lokasi kegiatan"
                  class="w-full text-sm border-slate-200 rounded-xl focus:ring-orange-500 focus:border-orange-500"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- STEP 2: Lampiran -->
        <div v-else-if="currentStep === 2" class="space-y-6">
          <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider pb-2 border-b border-slate-100">
            Upload Dokumen Pendukung / Proposal
          </h3>
          <FileUploader v-model="form.attachments" accept=".jpg,.jpeg,.png,.pdf" :max-size-m-b="5" />
        </div>

        <!-- STEP 3: Review & Kirim -->
        <div v-else-if="currentStep === 3" class="space-y-6">
          <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider pb-2 border-b border-slate-100">
            Ringkasan Pengajuan Konsumsi / Operasional
          </h3>
          <div class="bg-slate-50 p-6 rounded-2xl space-y-4 border border-slate-200/60">
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
              <div>
                <span class="text-xs text-slate-400 block">Nama Kegiatan</span>
                <span class="font-bold text-slate-800">{{ form.activity_name }}</span>
              </div>
              <div>
                <span class="text-xs text-slate-400 block">Tanggal Kegiatan</span>
                <span class="font-bold text-slate-800">{{ form.activity_date }}</span>
              </div>
              <div>
                <span class="text-xs text-slate-400 block">Jenis Kegiatan</span>
                <span class="font-bold text-slate-800">{{ activityTypes.find(t => t.id === form.activity_type_id)?.name }}</span>
              </div>
              <div>
                <span class="text-xs text-slate-400 block">Jumlah Peserta</span>
                <span class="font-bold text-slate-800">{{ form.participant_count }} Orang</span>
              </div>
              <div>
                <span class="text-xs text-slate-400 block">Estimasi Biaya</span>
                <span class="font-bold text-orange-600 text-base">{{ formatCurrency(form.estimated_cost) }}</span>
              </div>
              <div>
                <span class="text-xs text-slate-400 block">Lokasi</span>
                <span class="font-bold text-slate-800">{{ form.location }}</span>
              </div>
            </div>

            <div class="pt-3 border-t border-slate-200/60">
              <span class="text-xs text-slate-400 block">Tujuan / Keterangan</span>
              <p class="text-sm text-slate-700 font-medium mt-1">{{ form.purpose }}</p>
            </div>
          </div>
        </div>

        <!-- Footer Actions Bar -->
        <div class="pt-6 border-t border-slate-100 flex items-center justify-between">
          <button
            type="button"
            @click="saveDraft"
            :disabled="form.processing"
            class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors flex items-center gap-2"
          >
            <Save class="w-4 h-4" />
            <span>Simpan Draft</span>
          </button>

          <div class="flex items-center gap-3">
            <button
              v-if="currentStep > 1"
              type="button"
              @click="prevStep"
              class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-slate-50 transition-colors"
            >
              Kembali
            </button>

            <button
              v-if="currentStep < 3"
              type="button"
              @click="nextStep"
              class="px-6 py-2.5 rounded-xl bg-orange-500 text-white text-sm font-semibold hover:bg-orange-600 shadow-lg shadow-orange-500/30 transition-all flex items-center gap-2"
            >
              <span>Selanjutnya</span>
              <ArrowRight class="w-4 h-4" />
            </button>

            <button
              v-else
              type="button"
              @click="submitForm"
              :disabled="form.processing"
              class="px-6 py-2.5 rounded-xl bg-orange-500 text-white text-sm font-semibold hover:bg-orange-600 shadow-lg shadow-orange-500/30 transition-all flex items-center gap-2"
            >
              <Send class="w-4 h-4" />
              <span>Kirim Pengajuan</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
