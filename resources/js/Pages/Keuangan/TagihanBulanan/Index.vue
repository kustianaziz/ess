<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
  Zap,
  Calendar,
  CheckCircle2,
  Clock,
  DollarSign,
  Building2,
  FileText,
  CreditCard,
  Pen
} from 'lucide-vue-next'

const props = defineProps({
  payments: Array,
  summary: Object,
  month: Number,
  year: Number,
  cashAccounts: Array
})

const selectedMonth = ref(props.month)
const selectedYear = ref(props.year)

const monthsList = [
  { value: 1, label: 'Januari' },
  { value: 2, label: 'Februari' },
  { value: 3, label: 'Maret' },
  { value: 4, label: 'April' },
  { value: 5, label: 'Mei' },
  { value: 6, label: 'Juni' },
  { value: 7, label: 'Juli' },
  { value: 8, label: 'Agustus' },
  { value: 9, label: 'September' },
  { value: 10, label: 'Oktober' },
  { value: 11, label: 'November' },
  { value: 12, label: 'Desember' }
]

const yearsList = [2024, 2025, 2026, 2027]

const changePeriod = () => {
  router.get(
    route('keuangan.tagihan-bulanan.index'),
    {
      month: selectedMonth.value,
      year: selectedYear.value
    },
    { preserveState: true }
  )
}

// Pay Modal
const showPayModal = ref(false)
const selectedPayment = ref(null)

const payForm = ref({
  bill_amount: 0,
  payment_reference: '',
  cash_account_id: '',
  payment_date: new Date().toISOString().split('T')[0],
  notes: '',
  attachments: []
})

const handleFileChange = (e) => {
  payForm.value.attachments = Array.from(e.target.files)
}

const isSubmittingPay = ref(false)

const rawAmountInput = ref('')

const handleAmountInput = (e) => {
  const value = e.target.value.replace(/\D/g, '')
  payForm.value.bill_amount = value ? parseInt(value, 10) : 0
  rawAmountInput.value = value ? new Intl.NumberFormat('id-ID').format(value) : ''
}

const openPayModal = (item) => {
  selectedPayment.value = item
  payForm.value.bill_amount = item.bill_amount
  rawAmountInput.value = item.bill_amount ? new Intl.NumberFormat('id-ID').format(item.bill_amount) : ''
  payForm.value.payment_reference = 'TRF-' + Math.floor(100000 + Math.random() * 900000)
  payForm.value.cash_account_id = item.cash_account_id || props.cashAccounts[0]?.id || ''
  payForm.value.notes = ''
  payForm.value.attachments = []
  showPayModal.value = true
}

const submitPayment = () => {
  if (!payForm.value.payment_reference || !payForm.value.bill_amount || !payForm.value.cash_account_id) {
    alert('Mohon isi referensi transfer, nominal, dan akun kas pembayar.')
    return
  }

  isSubmittingPay.value = true
  router.post(route('keuangan.tagihan-bulanan.pay', selectedPayment.value.id), payForm.value, {
    forceFormData: true,
    onSuccess: () => {
      showPayModal.value = false
    },
    onFinish: () => {
      isSubmittingPay.value = false
    }
  })
}
// Add / Edit Bill Type Modal
const showAddTypeModal = ref(false)
const isEditType = ref(false)
const selectedTypeId = ref(null)

const typeForm = ref({
  name: '',
  vendor_name: '',
  default_amount: 0,
  due_date: new Date().toISOString().split('T')[0],
  cash_account_id: ''
})
const isSubmittingType = ref(false)

const rawTypeAmountInput = ref('')

const handleTypeAmountInput = (e) => {
  const value = e.target.value.replace(/\D/g, '')
  typeForm.value.default_amount = value ? parseInt(value, 10) : 0
  rawTypeAmountInput.value = value ? new Intl.NumberFormat('id-ID').format(value) : ''
}

const openAddTypeModal = () => {
  isEditType.value = false
  selectedTypeId.value = null
  typeForm.value.name = ''
  typeForm.value.vendor_name = ''
  typeForm.value.default_amount = 0
  rawTypeAmountInput.value = ''
  typeForm.value.due_date = new Date().toISOString().split('T')[0]
  typeForm.value.cash_account_id = props.cashAccounts[0]?.id || ''
  showAddTypeModal.value = true
}

const openEditTypeModal = (item) => {
  isEditType.value = true
  selectedTypeId.value = item.bill_type_id
  typeForm.value.name = item.bill_type_name
  typeForm.value.vendor_name = (item.vendor_name !== '-') ? item.vendor_name : ''
  typeForm.value.default_amount = item.bill_amount
  rawTypeAmountInput.value = item.bill_amount ? new Intl.NumberFormat('id-ID').format(item.bill_amount) : '0'
  typeForm.value.due_date = item.due_date_raw || new Date().toISOString().split('T')[0]
  typeForm.value.cash_account_id = item.cash_account_id || props.cashAccounts[0]?.id || ''
  showAddTypeModal.value = true
}

const submitBillType = () => {
  if (!typeForm.value.name || !typeForm.value.default_amount || !typeForm.value.cash_account_id) {
    alert('Mohon isi nama tagihan, estimasi nominal, dan akun kas pembayar.')
    return
  }

  isSubmittingType.value = true
  if (isEditType.value) {
    router.put(route('keuangan.tagihan-bulanan.types.update', selectedTypeId.value), typeForm.value, {
      onSuccess: () => {
        showAddTypeModal.value = false
      },
      onFinish: () => {
        isSubmittingType.value = false
      }
    })
  } else {
    router.post(route('keuangan.tagihan-bulanan.types.store'), typeForm.value, {
      onSuccess: () => {
        showAddTypeModal.value = false
      },
      onFinish: () => {
        isSubmittingType.value = false
      }
    })
  }
}
</script>

<template>
  <Head title="Pembayaran Tagihan Bulanan Rutin" />

  <AuthenticatedLayout>
    <div class="py-6 sm:py-8 max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-6">
      <!-- HEADER & PERIOD FILTER & ADD BUTTON -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="p-2 rounded-xl bg-orange-100 text-orange-600">
              <Zap class="w-5 h-5" />
            </span>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
              Pembayaran Tagihan Bulanan Rutin
            </h1>
          </div>
          <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Pengelolaan & pembayaran tagihan operasional rutin (Listrik, Internet, Parkir, Kebersihan)
          </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
          <!-- BUTTON ADD BILL TYPE -->
          <button
            @click="openAddTypeModal"
            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/20 transition-all flex items-center gap-1.5"
          >
            <span>+ Tambah Jenis Tagihan</span>
          </button>

          <!-- PERIOD SELECTOR -->
          <div class="flex items-center gap-2 bg-white p-2 rounded-xl border border-slate-200 shadow-sm">
            <Calendar class="w-4 h-4 text-slate-400 ml-1" />
            <select
              v-model="selectedMonth"
              @change="changePeriod"
              class="px-2 py-1 border-0 text-xs font-bold text-slate-800 focus:ring-0"
            >
              <option v-for="m in monthsList" :key="m.value" :value="m.value">{{ m.label }}</option>
            </select>
            <select
              v-model="selectedYear"
              @change="changePeriod"
              class="px-2 py-1 border-0 text-xs font-bold text-slate-800 focus:ring-0"
            >
              <option v-for="y in yearsList" :key="y" :value="y">{{ y }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- SUMMARY STATS -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Tagihan Bulan Ini</span>
            <h3 class="text-xl sm:text-2xl font-bold text-slate-900 mt-1">{{ summary.total_bill_formatted }}</h3>
          </div>
          <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
            <FileText class="w-5 h-5" />
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Sudah Dibayarkan</span>
            <h3 class="text-xl sm:text-2xl font-bold text-emerald-600 mt-1">{{ summary.total_paid_formatted }}</h3>
          </div>
          <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
            <CheckCircle2 class="w-5 h-5" />
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Belum Dibayarkan</span>
            <h3 class="text-xl sm:text-2xl font-bold text-amber-600 mt-1">{{ summary.total_unpaid_formatted }}</h3>
          </div>
          <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
            <Clock class="w-5 h-5" />
          </div>
        </div>
      </div>

      <!-- PAYMENTS CARDS GRID / TABLE -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div
          v-for="item in payments"
          :key="item.id"
          class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4 hover:border-indigo-300 transition-all flex flex-col justify-between"
        >
          <div class="space-y-2">
            <div class="flex items-center justify-between gap-2">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider" :class="item.status === 'paid' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-100 text-amber-800 border border-amber-200'">
                {{ item.status === 'paid' ? 'Sudah Dibayar' : 'Belum Dibayar' }}
              </span>
              <span class="text-[11px] text-slate-400 font-medium">Jatuh Tempo: {{ item.due_date }}</span>
            </div>

            <div>
              <div class="flex items-center justify-between gap-2">
                <h3 class="font-bold text-base text-slate-900 tracking-tight">{{ item.bill_type_name }}</h3>
                <button
                  @click="openEditTypeModal(item)"
                  class="p-1 text-slate-400 hover:text-indigo-600 rounded transition-colors"
                  title="Edit Jenis Tagihan"
                >
                  <Pen class="w-4 h-4" />
                </button>
              </div>
              <p class="text-xs text-slate-500 mt-0.5">Vendor: {{ item.vendor_name }}</p>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-3">
            <div>
              <span class="text-[10px] text-slate-400 block uppercase font-bold">Nominal Tagihan</span>
              <span class="font-black text-lg text-slate-900">{{ item.bill_amount_formatted }}</span>
            </div>

            <div>
              <button
                v-if="item.status !== 'paid'"
                @click="openPayModal(item)"
                class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition-all flex items-center gap-1.5"
              >
                <CreditCard class="w-3.5 h-3.5" />
                <span>Bayar Sekarang</span>
              </button>

              <div v-else class="text-right text-xs">
                <span class="text-slate-400 block text-[10px]">Ref: {{ item.payment_reference }}</span>
                <span class="text-emerald-600 font-bold">Dibayar {{ item.payment_date }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- PAY MODAL -->
    <div v-if="showPayModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
            <span class="p-1.5 rounded-lg bg-emerald-100 text-emerald-600">
              <CreditCard class="w-4 h-4" />
            </span>
            <span>Bayar Tagihan: {{ selectedPayment?.bill_type_name }}</span>
          </h3>
          <button @click="showPayModal = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
        </div>

        <form @submit.prevent="submitPayment" class="space-y-4 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Pilih Akun Kas Pembayar <span class="text-rose-500">*</span></label>
            <select v-model="payForm.cash_account_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-semibold text-slate-800">
              <option v-for="acc in cashAccounts" :key="acc.id" :value="acc.id">
                {{ acc.name }} ({{ acc.code }})
              </option>
            </select>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Nominal Tagihan Aktual (Rp) <span class="text-rose-500">*</span></label>
            <div class="relative">
              <span class="absolute left-3 top-2 font-bold text-slate-400">Rp</span>
              <input
                :value="rawAmountInput"
                @input="handleAmountInput"
                type="text"
                placeholder="0"
                class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 font-bold text-slate-900"
              />
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">No. Referensi Transfer / Struk <span class="text-rose-500">*</span></label>
            <input v-model="payForm.payment_reference" type="text" placeholder="Contoh: TRF-839120" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-800 font-bold" />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Tanggal Pembayaran <span class="text-rose-500">*</span></label>
            <input v-model="payForm.payment_date" type="date" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-800 font-medium" />
          </div>
          
          <div>
            <label class="block font-bold text-slate-700 mb-1">Bukti Bayar / Struk (Multiple)</label>
            <input type="file" multiple @change="handleFileChange" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-800 text-xs" />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Catatan (Opsional)</label>
            <textarea v-model="payForm.notes" rows="2" placeholder="Catatan transaksi pembayaran..." class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-800"></textarea>
          </div>

          <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
            <button type="button" @click="showPayModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
            <button
              type="submit"
              :disabled="isSubmittingPay"
              class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-md transition-all disabled:opacity-50"
            >
              Konfirmasi & Bayar Tagihan
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL ADD BILL TYPE -->
    <div v-if="showAddTypeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
            <span class="p-1.5 rounded-lg bg-indigo-100 text-indigo-600">
              <Zap class="w-4 h-4" />
            </span>
            <span>{{ isEditType ? 'Edit Jenis Tagihan Bulanan' : 'Tambah Jenis Tagihan Bulanan Baru' }}</span>
          </h3>
          <button @click="showAddTypeModal = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
        </div>

        <form @submit.prevent="submitBillType" class="space-y-4 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Nama Tagihan <span class="text-rose-500">*</span></label>
            <input v-model="typeForm.name" type="text" placeholder="Contoh: Sewa Server AWS Cloud / Zoom Meeting" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-800 font-semibold" />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Nama Vendor / Penyedia (Opsional)</label>
            <input v-model="typeForm.vendor_name" type="text" placeholder="Contoh: Amazon Web Services / Telkom / Gedung" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-800 font-medium" />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Estimasi Nominal Tagihan (Rp) <span class="text-rose-500">*</span></label>
            <div class="relative">
              <span class="absolute left-3 top-2 font-bold text-slate-400">Rp</span>
              <input
                :value="rawTypeAmountInput"
                @input="handleTypeAmountInput"
                type="text"
                placeholder="0"
                class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 font-bold text-slate-900"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Tanggal Jatuh Tempo <span class="text-rose-500">*</span></label>
              <input v-model="typeForm.due_date" type="date" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-800 font-semibold" />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Default Akun Kas <span class="text-rose-500">*</span></label>
              <select v-model="typeForm.cash_account_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-800 font-semibold">
                <option v-for="acc in cashAccounts" :key="acc.id" :value="acc.id">
                  {{ acc.name }}
                </option>
              </select>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
            <button type="button" @click="showAddTypeModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
            <button
              type="submit"
              :disabled="isSubmittingType"
              class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold shadow-md transition-all disabled:opacity-50"
            >
              {{ isEditType ? 'Simpan Perubahan' : 'Simpan Jenis Tagihan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
