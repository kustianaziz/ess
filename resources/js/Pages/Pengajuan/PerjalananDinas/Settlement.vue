<script setup>
import { ref, computed } from 'vue'
import { useForm, Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import FileUploader from '@/Components/FileUploader.vue'
import {
  FileText,
  Plus,
  Trash2,
  CheckCircle2,
  ArrowLeft,
  Plane,
  Calculator,
  Receipt
} from 'lucide-vue-next'

const props = defineProps({
  tripRequest: {
    type: Object,
    required: true
  },
  applicant: {
    type: Object,
    required: true
  }
})

const form = useForm({
  trip_report: '',
  expense_items: (props.tripRequest.allowance_breakdown && props.tripRequest.allowance_breakdown.length > 0)
    ? props.tripRequest.allowance_breakdown.map(item => ({
        category: 'lainnya',
        description: item.item_name,
        disbursed_amount: item.amount,
        amount: 0,
        expense_date: props.tripRequest.start_date
      }))
    : [
        {
          category: 'tiket',
          description: 'Tiket Pesawat / Kereta / Transportasi',
          amount: 0,
          expense_date: props.tripRequest.start_date
        }
      ],
  attachments: []
})

const addExpenseItem = () => {
  form.expense_items.push({
    category: 'bbm',
    description: '',
    amount: 0,
    expense_date: props.tripRequest.start_date
  })
}

const removeExpenseItem = (index) => {
  if (form.expense_items.length > 1) {
    form.expense_items.splice(index, 1)
  }
}

const categories = [
  { value: 'tiket', label: 'Tiket Pesawat / Kereta' },
  { value: 'boarding_pass', label: 'Boarding Pass / Airport Tax' },
  { value: 'hotel', label: 'Hotel / Akomodasi' },
  { value: 'bbm', label: 'BBM / Tol / Parkir' },
  { value: 'makan', label: 'Makan & Uang Saku' },
  { value: 'lainnya', label: 'Pengeluaran Lainnya' }
]

const totalActualCost = computed(() => {
  return form.expense_items.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0)
})

const differenceAmount = computed(() => {
  return totalActualCost.value - props.tripRequest.estimated_budget
})

const formatCurrency = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(val || 0)
}

const submitForm = () => {
  form.post(route('pengajuan.perjalanan-dinas.settlement.store', props.tripRequest.id), {
    forceFormData: true
  })
}
</script>

<template>
  <Head title="Form Penyelesaian Perjalanan Dinas" />

  <AuthenticatedLayout>
    <div class="py-6 sm:py-8 max-w-4xl mx-auto px-3 sm:px-6 lg:px-8">
      <!-- HEADER -->
      <div class="mb-6 sm:mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="p-2 rounded-xl bg-blue-100 text-blue-600">
              <Receipt class="w-5 h-5" />
            </span>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
              Penyelesaian Perjalanan Dinas (Settlement)
            </h1>
          </div>
          <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Form pertanggungjawaban realisasi biaya & laporan perjalanan dinas: <span class="font-bold text-slate-700">{{ tripRequest.request_number }}</span>
          </p>
        </div>

        <Link
          :href="route('riwayat-pengajuan.index')"
          class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-white border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-all shadow-sm"
        >
          <ArrowLeft class="w-4 h-4" />
          <span>Batal & Kembali</span>
        </Link>
      </div>

      <!-- SUMMARY ADVANCE CARD -->
      <div class="mb-6 bg-gradient-to-r from-blue-50 to-indigo-50/70 border border-blue-100 rounded-2xl p-4 sm:p-5 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
        <div>
          <span class="text-slate-500 block">Kota / Tujuan</span>
          <span class="font-bold text-slate-900">{{ tripRequest.destination }}</span>
        </div>
        <div>
          <span class="text-slate-500 block">Periode Tugas</span>
          <span class="font-bold text-slate-900">{{ tripRequest.start_date }} s.d {{ tripRequest.end_date }}</span>
        </div>
        <div>
          <span class="text-slate-500 block">Uang Muka (Advance)</span>
          <span class="font-bold text-blue-700">{{ tripRequest.estimated_budget_formatted }}</span>
        </div>
        <div>
          <span class="text-slate-500 block">Status Selisih</span>
          <span
            class="font-bold"
            :class="differenceAmount > 0 ? 'text-amber-600' : differenceAmount < 0 ? 'text-emerald-600' : 'text-slate-700'"
          >
            {{ differenceAmount > 0 ? 'Kurang Bayar (+)' : differenceAmount < 0 ? 'Kembalikan Sisa (-)' : 'Pas (0)' }}
          </span>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden p-4 sm:p-7 space-y-6">
        <form @submit.prevent="submitForm" class="space-y-6">
          <!-- SECTION 1: RINCIAN EXPENSE ITEMS -->
          <div>
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
              <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                <Calculator class="w-4 h-4 text-blue-600" />
                <span>1. Rincian Biaya Realisasi</span>
              </h2>
              <button
                type="button"
                @click="addExpenseItem"
                class="px-3 py-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 text-xs font-bold transition-all flex items-center gap-1"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>Tambah Item</span>
              </button>
            </div>

            <!-- EXPENSE ITEMS TABLE / LIST -->
            <div class="space-y-3">
              <div
                v-for="(item, idx) in form.expense_items"
                :key="idx"
                class="p-3.5 bg-slate-50 border border-slate-200/70 rounded-xl grid grid-cols-1 sm:grid-cols-12 gap-3 items-center"
              >
                <div class="sm:col-span-3">
                  <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Kategori</label>
                  <select
                    v-model="item.category"
                    class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs font-medium text-slate-800"
                  >
                    <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                      {{ cat.label }}
                    </option>
                  </select>
                </div>

                <div class="sm:col-span-3">
                  <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Keterangan Nota / Struk</label>
                  <input
                    v-model="item.description"
                    type="text"
                    placeholder="mis: Hotel Santika 2 Malam"
                    class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs text-slate-800"
                  />
                  <div v-if="item.disbursed_amount !== undefined" class="mt-1 text-[10px] font-medium text-slate-500">
                    Dicairkan: <span class="font-bold text-blue-600">{{ formatCurrency(item.disbursed_amount) }}</span>
                  </div>
                </div>

                <div class="sm:col-span-2">
                  <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Tanggal</label>
                  <input
                    v-model="item.expense_date"
                    type="date"
                    class="w-full px-2 py-2 rounded-lg border border-slate-200 text-xs text-slate-800"
                  />
                </div>

                <div class="sm:col-span-2">
                  <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Realisasi (Rp)</label>
                  <input
                    v-model.number="item.amount"
                    type="number"
                    min="0"
                    placeholder="0"
                    class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs font-bold text-slate-900"
                  />
                </div>

                <div class="sm:col-span-2">
                  <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Sisa (Rp)</label>
                  <div
                    class="w-full px-3 py-2 rounded-lg bg-slate-100 border border-slate-200 text-xs font-bold"
                    :class="item.disbursed_amount !== undefined && (item.disbursed_amount - item.amount) < 0 ? 'text-amber-600' : 'text-slate-700'"
                  >
                    {{ item.disbursed_amount !== undefined ? formatCurrency(item.disbursed_amount - item.amount) : '-' }}
                  </div>
                </div>

                <div class="sm:col-span-1 flex justify-end">
                  <button
                    type="button"
                    @click="removeExpenseItem(idx)"
                    :disabled="form.expense_items.length === 1"
                    class="p-2 text-slate-400 hover:text-rose-600 disabled:opacity-30 transition-all mt-4 sm:mt-0"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>

            <!-- TOTAL REALISASI & SELISIH SUMMARY BOX -->
            <div class="mt-4 p-4 rounded-xl bg-slate-900 text-white flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
              <div class="flex items-center gap-6">
                <div>
                  <span class="text-slate-400 block text-[11px]">Uang Muka Diterima:</span>
                  <span class="font-bold text-sm">{{ tripRequest.estimated_budget_formatted }}</span>
                </div>
                <div class="border-l border-slate-800 pl-6">
                  <span class="text-slate-400 block text-[11px]">Total Realisasi Biaya:</span>
                  <span class="font-bold text-sm text-blue-400">{{ formatCurrency(totalActualCost) }}</span>
                </div>
              </div>

              <div class="text-right bg-slate-800/80 px-4 py-2 rounded-lg border border-slate-700 w-full sm:w-auto">
                <span class="text-slate-400 block text-[10px] uppercase tracking-wider font-bold">
                  {{ differenceAmount > 0 ? 'Kurang Bayar Ke Karyawan' : differenceAmount < 0 ? 'Karyawan Kembalikan Sisa' : 'Nominal Lunas Pas' }}
                </span>
                <span
                  class="font-black text-base"
                  :class="differenceAmount > 0 ? 'text-amber-400' : differenceAmount < 0 ? 'text-emerald-400' : 'text-slate-200'"
                >
                  {{ formatCurrency(Math.abs(differenceAmount)) }}
                </span>
              </div>
            </div>
          </div>

          <!-- SECTION 2: LAPORAN HASIL PERJALANAN DINAS -->
          <div>
            <div class="border-b border-slate-100 pb-3 mb-4">
              <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                <FileText class="w-4 h-4 text-blue-600" />
                <span>2. Ringkasan Laporan Perjalanan Dinas</span>
              </h2>
            </div>
            <textarea
              v-model="form.trip_report"
              rows="4"
              placeholder="Tuliskan ringkasan hasil kegiatan / pencapaian tugas selama perjalanan dinas..."
              class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-500 text-xs text-slate-800"
            ></textarea>
          </div>

          <!-- SECTION 3: UPLOAD NOTA / STRUK / BUKTI -->
          <div>
            <div class="border-b border-slate-100 pb-3 mb-4">
              <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                <Receipt class="w-4 h-4 text-blue-600" />
                <span>3. Upload Nota, Boarding Pass, Struk & Bukti Transfer Sisa</span>
              </h2>
            </div>
            <FileUploader
              v-model="form.attachments"
              :max-files="10"
              accept=".pdf,.jpg,.jpeg,.png"
            />
          </div>

          <!-- SUBMIT ACTION BUTTON -->
          <div class="pt-4 border-t border-slate-100 flex justify-end">
            <button
              type="submit"
              :disabled="form.processing"
              class="w-full sm:w-auto px-7 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-lg shadow-emerald-600/20 transition-all flex items-center justify-center gap-2"
            >
              <CheckCircle2 class="w-4 h-4" />
              <span>Submit Pertanggungjawaban Settlement</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
