<script setup>
import { ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Stepper from '@/Components/Stepper.vue';
import FileUploader from '@/Components/FileUploader.vue';
import { ArrowLeft, ArrowRight, Save, Send, Calendar } from 'lucide-vue-next';

const props = defineProps({
  applicant: Object,
  leaveTypes: Array,
  colleagues: Array,
});

const currentStep = ref(1);

const form = useForm({
  leave_type_id: '',
  start_date: '',
  end_date: '',
  total_days: 0,
  reason: '',
  handover_to_user_id: '',
  handover_notes: '',
  attachments: [],
  action: 'submit',
});

// Auto-calculate working days excluding weekends
const calculateDays = () => {
  if (!form.start_date || !form.end_date) {
    form.total_days = 0;
    return;
  }
  const start = new Date(form.start_date);
  const end = new Date(form.end_date);
  if (start > end) {
    form.total_days = 0;
    return;
  }

  let count = 0;
  let cur = new Date(start);
  while (cur <= end) {
    const day = cur.getDay();
    if (day !== 0 && day !== 6) { // exclude Sunday (0) and Saturday (6)
      count++;
    }
    cur.setDate(cur.getDate() + 1);
  }
  form.total_days = count;
};

watch(() => [form.start_date, form.end_date], calculateDays);

const selectedLeaveType = ref(null);

watch(() => form.leave_type_id, (newVal) => {
  selectedLeaveType.value = props.leaveTypes.find(t => t.id === newVal) || null;
});

const nextStep = () => {
  if (currentStep.value === 1) {
    if (!form.leave_type_id || !form.start_date || !form.end_date || !form.reason) {
      alert('Harap lengkapi jenis cuti, tanggal mulai, tanggal selesai, dan alasan cuti.');
      return;
    }
    if (selectedLeaveType.value && selectedLeaveType.value.remaining < form.total_days) {
      alert(`Sisa kuota cuti Anda (${selectedLeaveType.value.remaining} hari) tidak mencukupi untuk pengajuan ${form.total_days} hari cuti.`);
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
  form.post(route('pengajuan.cuti.store'), {
    preserveScroll: true,
  });
};

const submitForm = () => {
  form.action = 'submit';
  form.post(route('pengajuan.cuti.store'), {
    preserveScroll: true,
  });
};
</script>

<template>
  <Head title="Pengajuan Cuti" />

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
              Pengajuan Cuti Karyawan
            </h1>
            <p class="text-[11px] sm:text-xs text-slate-400">Isi formulir pengajuan izin dan cuti kerja</p>
          </div>
        </div>
        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
          <Calendar class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>

      <!-- Stepper Component -->
      <div class="bg-white p-3 sm:p-4 rounded-2xl border border-slate-100 shadow-sm">
        <Stepper
          :current-step="currentStep"
          accent-color="purple"
          :steps="[
            { number: 1, label: 'Informasi' },
            { number: 2, label: 'Lampiran (Opsional)' },
            { number: 3, label: 'Review & Kirim' },
          ]"
        />
      </div>

      <!-- Form Container -->
      <div class="bg-white p-8 rounded-2xl border border-slate-100 shadow-sm space-y-8">
        <!-- STEP 1: Informasi -->
        <div v-if="currentStep === 1" class="space-y-8">
          <!-- Section 1: Informasi Cuti -->
          <div>
            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100">
              Informasi Cuti
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
              <div class="md:col-span-3">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jenis Cuti <span class="text-rose-500">*</span></label>
                <select
                  v-model="form.leave_type_id"
                  class="w-full text-sm border-slate-200 rounded-xl focus:ring-purple-500 focus:border-purple-500"
                >
                  <option value="" disabled>Pilih jenis cuti</option>
                  <option v-for="type in leaveTypes" :key="type.id" :value="type.id">
                    {{ type.name }} (Sisa Kuota: {{ type.remaining }} Hari)
                  </option>
                </select>
                <p v-if="selectedLeaveType" class="text-xs text-purple-600 font-medium mt-1.5">
                  ✓ Sisa kuota untuk {{ selectedLeaveType.name }}: <span class="font-bold">{{ selectedLeaveType.remaining }} hari</span>
                </p>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Mulai <span class="text-rose-500">*</span></label>
                <input
                  type="date"
                  v-model="form.start_date"
                  class="w-full text-sm border-slate-200 rounded-xl focus:ring-purple-500 focus:border-purple-500"
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Selesai <span class="text-rose-500">*</span></label>
                <input
                  type="date"
                  v-model="form.end_date"
                  class="w-full text-sm border-slate-200 rounded-xl focus:ring-purple-500 focus:border-purple-500"
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Total Hari</label>
                <input
                  type="text"
                  :value="form.total_days + ' Hari'"
                  disabled
                  class="w-full text-sm bg-slate-50 border-slate-200 rounded-xl text-slate-700 font-bold cursor-not-allowed"
                />
              </div>

              <div class="md:col-span-3">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Alasan Cuti <span class="text-rose-500">*</span></label>
                <textarea
                  v-model="form.reason"
                  rows="3"
                  placeholder="Jelaskan alasan pengajuan cuti"
                  class="w-full text-sm border-slate-200 rounded-xl focus:ring-purple-500 focus:border-purple-500 resize-none"
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Section 2: Serah Terima Pekerjaan (Opsional) -->
          <div>
            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100">
              Serah Terima Pekerjaan (Opsional)
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Diserahkan Kepada</label>
                <select
                  v-model="form.handover_to_user_id"
                  class="w-full text-sm border-slate-200 rounded-xl focus:ring-purple-500 focus:border-purple-500"
                >
                  <option value="">Pilih atau ketik nama rekan kerja</option>
                  <option v-for="user in colleagues" :key="user.id" :value="user.id">
                    {{ user.name }} ({{ user.position || 'Staf' }})
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Keterangan Serah Terima</label>
                <input
                  type="text"
                  v-model="form.handover_notes"
                  placeholder="Jelaskan pekerjaan yang diserahkan (opsional)"
                  class="w-full text-sm border-slate-200 rounded-xl focus:ring-purple-500 focus:border-purple-500"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- STEP 2: Lampiran (Opsional) -->
        <div v-else-if="currentStep === 2" class="space-y-6">
          <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider pb-2 border-b border-slate-100">
            Upload Surat Dokter / Dokumen Pendukung Cuti (Opsional)
          </h3>
          <FileUploader v-model="form.attachments" accept=".jpg,.jpeg,.png,.pdf" :max-size-m-b="5" />
        </div>

        <!-- STEP 3: Review & Kirim -->
        <div v-else-if="currentStep === 3" class="space-y-6">
          <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider pb-2 border-b border-slate-100">
            Ringkasan Pengajuan Cuti
          </h3>
          <div class="bg-slate-50 p-6 rounded-2xl space-y-4 border border-slate-200/60">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
              <div>
                <span class="text-xs text-slate-400 block">Jenis Cuti</span>
                <span class="font-bold text-slate-800">{{ selectedLeaveType?.name }}</span>
              </div>
              <div>
                <span class="text-xs text-slate-400 block">Tanggal Mulai</span>
                <span class="font-bold text-slate-800">{{ form.start_date }}</span>
              </div>
              <div>
                <span class="text-xs text-slate-400 block">Tanggal Selesai</span>
                <span class="font-bold text-slate-800">{{ form.end_date }}</span>
              </div>
              <div>
                <span class="text-xs text-slate-400 block">Total Hari Kerja</span>
                <span class="font-bold text-purple-600 text-base">{{ form.total_days }} Hari</span>
              </div>
            </div>

            <div class="pt-3 border-t border-slate-200/60">
              <span class="text-xs text-slate-400 block">Alasan Cuti</span>
              <p class="text-sm text-slate-700 font-medium mt-1">{{ form.reason }}</p>
            </div>

            <div class="pt-3 border-t border-slate-200/60" v-if="form.handover_to_user_id">
              <span class="text-xs text-slate-400 block">Diserahkan Kepada</span>
              <p class="text-sm text-slate-800 font-semibold mt-1">
                {{ colleagues.find(c => c.id === form.handover_to_user_id)?.name }}
              </p>
              <p class="text-xs text-slate-500 mt-0.5" v-if="form.handover_notes">{{ form.handover_notes }}</p>
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
              class="px-6 py-2.5 rounded-xl bg-purple-600 text-white text-sm font-semibold hover:bg-purple-700 shadow-lg shadow-purple-600/30 transition-all flex items-center gap-2"
            >
              <span>Selanjutnya</span>
              <ArrowRight class="w-4 h-4" />
            </button>

            <button
              v-else
              type="button"
              @click="submitForm"
              :disabled="form.processing"
              class="px-6 py-2.5 rounded-xl bg-purple-600 text-white text-sm font-semibold hover:bg-purple-700 shadow-lg shadow-purple-600/30 transition-all flex items-center gap-2"
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
