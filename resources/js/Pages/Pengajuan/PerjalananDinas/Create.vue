<script setup>
import { ref, computed } from 'vue'
import { useForm, Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Stepper from '@/Components/Stepper.vue'
import FileUploader from '@/Components/FileUploader.vue'
import {
  FileText,
  MapPin,
  Calendar,
  DollarSign,
  Briefcase,
  CheckCircle2,
  AlertCircle,
  ArrowRight,
  ArrowLeft,
  Plane,
  Save,
  Building2
} from 'lucide-vue-next'

const props = defineProps({
  applicant: {
    type: Object,
    required: true
  }
})

const currentStep = ref(1)

const form = useForm({
  destination: '',
  target_institution: '',
  purpose: '',
  start_date: '',
  end_date: '',
  transportation_type: 'Pesawat / Kendaraan Umum',
  assignment_letter_number: '',
  estimated_budget: 0,
  attachments: [],
  action: 'submit'
})

// Format Rupiah Input
const rawBudgetInput = ref('')

const handleBudgetInput = (e) => {
  const value = e.target.value.replace(/\D/g, '')
  form.estimated_budget = value ? parseInt(value, 10) : 0
  rawBudgetInput.value = value ? new Intl.NumberFormat('id-ID').format(value) : ''
}

const formattedBudget = computed(() => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(form.estimated_budget || 0)
})

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}

// Step Validation
const isStep1Valid = computed(() => {
  return (
    form.destination.trim() !== '' &&
    form.purpose.trim() !== '' &&
    form.start_date !== '' &&
    form.end_date !== '' &&
    form.estimated_budget >= 0
  )
})

const nextStep = () => {
  if (currentStep.value === 1 && !isStep1Valid.value) {
    alert('Mohon lengkapi seluruh field informasi perjalanan dinas yang wajib diisi.')
    return
  }
  if (currentStep.value < 3) {
    currentStep.value++
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const submitForm = (actionType) => {
  form.action = actionType
  form.post(route('pengajuan.perjalanan-dinas.store'), {
    forceFormData: true,
    onError: () => {
      currentStep.value = 1
    }
  })
}
</script>

<template>
  <Head title="Pengajuan Perjalanan Dinas" />

  <AuthenticatedLayout>
    <div class="py-6 sm:py-8 max-w-4xl mx-auto px-3 sm:px-6 lg:px-8">
      <!-- PAGE HEADER -->
      <div class="mb-6 sm:mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="p-2 rounded-xl bg-blue-100 text-blue-600">
              <Plane class="w-5 h-5" />
            </span>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
              Pengajuan Perjalanan Dinas
            </h1>
          </div>
          <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Isi formulir pengajuan perjalanan dinas & permohonan estimasi uang muka
          </p>
        </div>

        <Link
          :href="route('riwayat-pengajuan.index')"
          class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-white border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-all shadow-sm"
        >
          <ArrowLeft class="w-4 h-4" />
          <span>Kembali ke Riwayat</span>
        </Link>
      </div>

      <!-- STEPPER COMPONENT -->
      <div class="mb-6 sm:mb-8 bg-white p-4 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <Stepper
          :current-step="currentStep"
          :steps="['Informasi Perjalanan', 'Lampiran Pendukung', 'Review & Kirim']"
          accent-color="blue"
        />
      </div>

      <!-- FORM CARD -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <form @submit.prevent>
          <!-- STEP 1: INFORMASI PERJALANAN DINAS -->
          <div v-show="currentStep === 1" class="p-4 sm:p-7 space-y-6">
            <div class="border-b border-slate-100 pb-4">
              <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                <Briefcase class="w-4 h-4 text-blue-600" />
                <span>1. Informasi Pemohon & Detail Dinas</span>
              </h2>
            </div>

            <!-- APPLICANT INFO READONLY CARD -->
            <div class="bg-slate-50/80 border border-slate-200/60 rounded-xl p-4 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
              <div>
                <span class="text-slate-400 block font-medium">Nama Pemohon</span>
                <span class="font-semibold text-slate-800">{{ applicant.name }}</span>
              </div>
              <div>
                <span class="text-slate-400 block font-medium">NIK</span>
                <span class="font-semibold text-slate-800">{{ applicant.nik }}</span>
              </div>
              <div>
                <span class="text-slate-400 block font-medium">Divisi</span>
                <span class="font-semibold text-slate-800">{{ applicant.division }}</span>
              </div>
              <div>
                <span class="text-slate-400 block font-medium">Tanggal Pengajuan</span>
                <span class="font-semibold text-slate-800">{{ applicant.submission_date }}</span>
              </div>
            </div>

            <div class="space-y-4">
              <!-- DESTINATION & TARGET INSTITUTION & TRANSPORTATION -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">
                    Kota / Destination <span class="text-rose-500">*</span>
                  </label>
                  <div class="relative">
                    <MapPin class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                    <input
                      v-model="form.destination"
                      type="text"
                      placeholder="Contoh: Bandung, Surabaya"
                      class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-xs text-slate-800"
                    />
                  </div>
                  <span v-if="form.errors.destination" class="text-[11px] text-rose-500 mt-1 block">
                    {{ form.errors.destination }}
                  </span>
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">
                    Instansi / Klien Tujuan
                  </label>
                  <div class="relative">
                    <Building2 class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
                    <input
                      v-model="form.target_institution"
                      type="text"
                      placeholder="Contoh: PT Telkom / Disdik"
                      class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-xs text-slate-800"
                    />
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">
                    Moda Transportasi
                  </label>
                  <select
                    v-model="form.transportation_type"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-xs text-slate-800"
                  >
                    <option value="Pesawat / Kendaraan Umum">Pesawat / Kendaraan Umum</option>
                    <option value="Kereta Api">Kereta Api</option>
                    <option value="Mobil Dinas">Mobil Dinas Perusahaan</option>
                    <option value="Kendaraan Pribadi">Kendaraan Pribadi</option>
                    <option value="Travel / Taksi">Travel / Taksi</option>
                  </select>
                </div>
              </div>

              <!-- DATES -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">
                    Tanggal Berangkat <span class="text-rose-500">*</span>
                  </label>
                  <input
                    v-model="form.start_date"
                    type="date"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-xs text-slate-800"
                  />
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">
                    Tanggal Kembali <span class="text-rose-500">*</span>
                  </label>
                  <input
                    v-model="form.end_date"
                    type="date"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-xs text-slate-800"
                  />
                </div>
              </div>

              <!-- ASSIGNMENT LETTER NUMBER & ESTIMATED BUDGET -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">
                    No. Surat Tugas <span class="text-slate-400 font-normal">(Opsional)</span>
                  </label>
                  <input
                    v-model="form.assignment_letter_number"
                    type="text"
                    placeholder="Contoh: ST/2026/08/012"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-xs text-slate-800"
                  />
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">
                    Estimasi Uang Muka (Rp) <span class="text-rose-500">*</span>
                  </label>
                  <div class="relative">
                    <span class="absolute left-3.5 top-2.5 text-xs font-bold text-slate-400">Rp</span>
                    <input
                      :value="rawBudgetInput"
                      @input="handleBudgetInput"
                      type="text"
                      placeholder="0"
                      class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-xs font-bold text-slate-900"
                    />
                  </div>
                  <span class="text-[11px] text-blue-600 font-semibold mt-1 block">
                    Format: {{ formattedBudget }}
                  </span>
                </div>
              </div>

              <!-- PURPOSE -->
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                  Tujuan Perjalanan Dinas <span class="text-rose-500">*</span>
                </label>
                <textarea
                  v-model="form.purpose"
                  rows="3"
                  placeholder="Jelaskan secara singkat agenda dan tujuan perjalanan dinas..."
                  class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-xs text-slate-800"
                ></textarea>
              </div>
            </div>
          </div>

          <!-- STEP 2: LAMPIRAN PENDUKUNG -->
          <div v-show="currentStep === 2" class="p-4 sm:p-7 space-y-6">
            <div class="border-b border-slate-100 pb-4">
              <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                <FileText class="w-4 h-4 text-blue-600" />
                <span>2. Lampiran Surat Tugas / Proposal (Opsional)</span>
              </h2>
              <p class="text-xs text-slate-500 mt-1">
                Unggah dokumen Surat Tugas, undangan kegiatan, atau proposal estimasi biaya.
              </p>
            </div>

            <FileUploader
              v-model="form.attachments"
              :max-files="5"
              accept=".pdf,.jpg,.jpeg,.png"
            />
          </div>

          <!-- STEP 3: REVIEW & KIRIM -->
          <div v-show="currentStep === 3" class="p-4 sm:p-7 space-y-6">
            <div class="border-b border-slate-100 pb-4">
              <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                <span>3. Review Ringkasan Pengajuan</span>
              </h2>
              <p class="text-xs text-slate-500 mt-1">
                Periksa kembali data perjalanan dinas sebelum dikirim ke Atasan / HRD.
              </p>
            </div>

            <div class="bg-blue-50/70 border border-blue-100 rounded-xl p-4 sm:p-5 space-y-3">
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                <div>
                  <span class="text-slate-500 block">Kota Tujuan</span>
                  <span class="font-bold text-slate-900">{{ form.destination }}</span>
                </div>
                <div>
                  <span class="text-slate-500 block">Instansi / Klien</span>
                  <span class="font-bold text-slate-900">{{ form.target_institution || '-' }}</span>
                </div>
                <div>
                  <span class="text-slate-500 block">Tanggal Berangkat</span>
                  <span class="font-bold text-slate-900">{{ formatDate(form.start_date) }}</span>
                </div>
                <div>
                  <span class="text-slate-500 block">Tanggal Kembali</span>
                  <span class="font-bold text-slate-900">{{ formatDate(form.end_date) }}</span>
                </div>
                <div>
                  <span class="text-slate-500 block">Moda Transportasi</span>
                  <span class="font-bold text-slate-900">{{ form.transportation_type }}</span>
                </div>
                <div>
                  <span class="text-slate-500 block">No. Surat Tugas</span>
                  <span class="font-bold text-slate-900">{{ form.assignment_letter_number || '-' }}</span>
                </div>
                <div>
                  <span class="text-slate-500 block">Estimasi Uang Muka</span>
                  <span class="font-bold text-blue-700 text-sm">{{ formattedBudget }}</span>
                </div>
              </div>

              <div class="pt-2 border-t border-blue-100 text-xs">
                <span class="text-slate-500 block mb-1">Tujuan Agenda:</span>
                <p class="text-slate-800 bg-white p-3 rounded-lg border border-blue-100/80 leading-relaxed whitespace-pre-line">
                  {{ form.purpose }}
                </p>
              </div>
            </div>
          </div>

          <!-- ACTION FOOTER (RESPONSIVE MOBILE FLEX) -->
          <div class="bg-slate-50 px-4 py-4 sm:px-7 border-t border-slate-200/80 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <button
              v-if="currentStep > 1"
              type="button"
              @click="prevStep"
              class="px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-100 transition-all flex items-center justify-center gap-1.5"
            >
              <ArrowLeft class="w-3.5 h-3.5" />
              <span>Sebelumnya</span>
            </button>
            <div v-else class="hidden sm:block"></div>

            <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center gap-2">
              <button
                type="button"
                @click="submitForm('draft')"
                :disabled="form.processing"
                class="px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-bold transition-all flex items-center justify-center gap-1.5"
              >
                <Save class="w-3.5 h-3.5" />
                <span>Simpan Draft</span>
              </button>

              <button
                v-if="currentStep < 3"
                type="button"
                @click="nextStep"
                class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-600/20 transition-all flex items-center justify-center gap-1.5"
              >
                <span>Selanjutnya</span>
                <ArrowRight class="w-3.5 h-3.5" />
              </button>

              <button
                v-else
                type="button"
                @click="submitForm('submit')"
                :disabled="form.processing"
                class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition-all flex items-center justify-center gap-1.5"
              >
                <CheckCircle2 class="w-4 h-4" />
                <span>Kirim Pengajuan</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
