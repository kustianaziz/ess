<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Stepper from '@/Components/Stepper.vue';
import FileUploader from '@/Components/FileUploader.vue';
import { ArrowLeft, ArrowRight, Save, Send, FileText } from 'lucide-vue-next';

const props = defineProps({
  applicant: Object,
  expenseTypes: Array,
});

const currentStep = ref(1);

const form = useForm({
  expense_type_id: '',
  expense_date: '',
  amount: '',
  description: '',
  attachments: [],
  action: 'submit',
});

const displayAmount = ref(form.amount ? 'Rp ' + new Intl.NumberFormat('id-ID').format(form.amount) : '');

const handleAmountInput = (e) => {
  const rawValue = e.target.value.replace(/\D/g, '');
  if (!rawValue) {
    form.amount = '';
    displayAmount.value = '';
    return;
  }
  const numeric = parseInt(rawValue, 10);
  form.amount = numeric;
  displayAmount.value = 'Rp ' + new Intl.NumberFormat('id-ID').format(numeric);
};

const nextStep = () => {
  if (currentStep.value === 1) {
    if (!form.expense_type_id || !form.expense_date || !form.amount || !form.description) {
      alert('Harap lengkapi semua kolom informasi reimbursement.');
      return;
    }
  }
  if (currentStep.value === 2) {
    if (!form.attachments || form.attachments.length === 0) {
      alert('Harap lampirkan minimal 1 bukti transaksi / struk pengeluaran.');
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
  form.post(route('pengajuan.reimbursement.store'), {
    preserveScroll: true,
  });
};

const submitForm = () => {
  form.action = 'submit';
  form.post(route('pengajuan.reimbursement.store'), {
    preserveScroll: true,
  });
};

const formatCurrency = (val) => {
  if (!val) return 'Rp 0';
  return 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
};
</script>

<template>
  <Head title="Pengajuan Reimbursement" />

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
              Pengajuan Reimbursement
            </h1>
            <p class="text-[11px] sm:text-xs text-slate-400">Isi formulir penggantian biaya pekerjaan</p>
          </div>
        </div>
        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
          <FileText class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>

      <!-- Stepper Component -->
      <div class="bg-white p-3 sm:p-4 rounded-2xl border border-slate-100 shadow-sm">
        <Stepper :current-step="currentStep" accent-color="emerald" />
      </div>

      <!-- Form Container -->
      <div class="bg-white p-4 sm:p-6 lg:p-8 rounded-2xl border border-slate-100 shadow-sm space-y-6 sm:space-y-8">
        <!-- STEP 1: Informasi -->
        <div v-if="currentStep === 1" class="space-y-6 sm:space-y-8">
          <!-- Section 1: Informasi Pengaju -->
          <div>
            <h3 class="text-xs sm:text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100">
              Informasi Pengaju
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3.5 sm:gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Nama Lengkap</label>
                <input
                  type="text"
                  :value="applicant.name"
                  disabled
                  class="w-full text-xs sm:text-sm bg-slate-50 border-slate-200 rounded-xl text-slate-600 font-medium cursor-not-allowed"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">NIK</label>
                <input
                  type="text"
                  :value="applicant.nik"
                  disabled
                  class="w-full text-xs sm:text-sm bg-slate-50 border-slate-200 rounded-xl text-slate-600 font-medium cursor-not-allowed"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Divisi</label>
                <input
                  type="text"
                  :value="applicant.division"
                  disabled
                  class="w-full text-xs sm:text-sm bg-slate-50 border-slate-200 rounded-xl text-slate-600 font-medium cursor-not-allowed"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Jabatan</label>
                <input
                  type="text"
                  :value="applicant.position"
                  disabled
                  class="w-full text-xs sm:text-sm bg-slate-50 border-slate-200 rounded-xl text-slate-600 font-medium cursor-not-allowed"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal Pengajuan</label>
                <input
                  type="text"
                  :value="applicant.submission_date"
                  disabled
                  class="w-full text-xs sm:text-sm bg-slate-50 border-slate-200 rounded-xl text-slate-600 font-medium cursor-not-allowed"
                />
              </div>
            </div>
          </div>

          <!-- Section 2: Detail Reimbursement -->
          <div>
            <h3 class="text-xs sm:text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100">
              Detail Reimbursement
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 sm:gap-5">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Pengeluaran <span class="text-rose-500">*</span></label>
                <input
                  type="date"
                  v-model="form.expense_date"
                  class="w-full text-xs sm:text-sm border-slate-200 rounded-xl focus:ring-emerald-500 focus:border-emerald-500"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jenis Pengeluaran <span class="text-rose-500">*</span></label>
                <select
                  v-model="form.expense_type_id"
                  class="w-full text-xs sm:text-sm border-slate-200 rounded-xl focus:ring-emerald-500 focus:border-emerald-500"
                >
                  <option value="" disabled>Pilih jenis pengeluaran</option>
                  <option v-for="type in expenseTypes" :key="type.id" :value="type.id">
                    {{ type.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nominal <span class="text-rose-500">*</span></label>
                <input
                  type="text"
                  :value="displayAmount"
                  @input="handleAmountInput"
                  placeholder="Rp 0"
                  class="w-full text-xs sm:text-sm border-slate-200 rounded-xl focus:ring-emerald-500 focus:border-emerald-500 font-semibold text-emerald-700"
                />
              </div>
              <div class="sm:col-span-2 md:col-span-3">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Keterangan <span class="text-rose-500">*</span></label>
                <textarea
                  v-model="form.description"
                  rows="3"
                  placeholder="Masukkan keterangan pengeluaran"
                  class="w-full text-xs sm:text-sm border-slate-200 rounded-xl focus:ring-emerald-500 focus:border-emerald-500 resize-none"
                ></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- STEP 2: Lampiran -->
        <div v-else-if="currentStep === 2" class="space-y-6">
          <h3 class="text-xs sm:text-sm font-bold text-slate-800 uppercase tracking-wider pb-2 border-b border-slate-100">
            Upload Bukti Transaksi / Struk
          </h3>
          <FileUploader v-model="form.attachments" accept=".jpg,.jpeg,.png,.pdf" :max-size-m-b="5" />
        </div>

        <!-- STEP 3: Review & Kirim -->
        <div v-else-if="currentStep === 3" class="space-y-6">
          <h3 class="text-xs sm:text-sm font-bold text-slate-800 uppercase tracking-wider pb-2 border-b border-slate-100">
            Ringkasan Pengajuan Reimbursement
          </h3>
          <div class="bg-slate-50 p-4 sm:p-6 rounded-2xl space-y-4 border border-slate-200/60">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 text-xs sm:text-sm">
              <div>
                <span class="text-xs text-slate-400 block">Nama Pengaju</span>
                <span class="font-bold text-slate-800">{{ applicant.name }}</span>
              </div>
              <div>
                <span class="text-xs text-slate-400 block">Tanggal Pengeluaran</span>
                <span class="font-bold text-slate-800">{{ form.expense_date }}</span>
              </div>
              <div>
                <span class="text-xs text-slate-400 block">Jenis Pengeluaran</span>
                <span class="font-bold text-slate-800">{{ expenseTypes.find(t => t.id === form.expense_type_id)?.name }}</span>
              </div>
              <div>
                <span class="text-xs text-slate-400 block">Nominal</span>
                <span class="font-bold text-emerald-600 text-sm sm:text-base">{{ formatCurrency(form.amount) }}</span>
              </div>
            </div>

            <div class="pt-3 border-t border-slate-200/60">
              <span class="text-xs text-slate-400 block">Keterangan</span>
              <p class="text-xs sm:text-sm text-slate-700 font-medium mt-1">{{ form.description }}</p>
            </div>

            <div class="pt-3 border-t border-slate-200/60" v-if="form.attachments.length">
              <span class="text-xs text-slate-400 block">Jumlah Lampiran</span>
              <p class="text-xs sm:text-sm text-slate-800 font-semibold mt-1">{{ form.attachments.length }} File Terlampir</p>
            </div>
          </div>
        </div>

        <!-- Footer Actions Bar (Responsive Mobile Optimized) -->
        <div class="pt-6 border-t border-slate-100 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3">
          <button
            type="button"
            @click="saveDraft"
            :disabled="form.processing"
            class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-xs sm:text-sm font-semibold hover:bg-slate-50 transition-colors flex items-center justify-center gap-2"
          >
            <Save class="w-4 h-4" />
            <span>Simpan Draft</span>
          </button>

          <div class="flex items-center gap-2 sm:gap-3 w-full sm:w-auto">
            <button
              v-if="currentStep > 1"
              type="button"
              @click="prevStep"
              class="flex-1 sm:flex-initial px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-xs sm:text-sm font-semibold hover:bg-slate-50 transition-colors text-center"
            >
              Kembali
            </button>

            <button
              v-if="currentStep < 3"
              type="button"
              @click="nextStep"
              class="flex-1 sm:flex-initial px-6 py-2.5 rounded-xl bg-emerald-600 text-white text-xs sm:text-sm font-semibold hover:bg-emerald-700 shadow-lg shadow-emerald-600/30 transition-all flex items-center justify-center gap-2"
            >
              <span>Selanjutnya</span>
              <ArrowRight class="w-4 h-4" />
            </button>

            <button
              v-else
              type="button"
              @click="submitForm"
              :disabled="form.processing"
              class="flex-1 sm:flex-initial px-6 py-2.5 rounded-xl bg-emerald-600 text-white text-xs sm:text-sm font-semibold hover:bg-emerald-700 shadow-lg shadow-emerald-600/30 transition-all flex items-center justify-center gap-2"
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
