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

// Edit Payment Details (Attachments/Reference) Modal
const showEditPaymentModal = ref(false)
const editPaymentForm = ref({
  payment_reference: '',
  notes: '',
  attachments: []
})
const isSubmittingEditPayment = ref(false)

const handleEditFileChange = (e) => {
  editPaymentForm.value.attachments = Array.from(e.target.files)
}

const openEditPaymentModal = (item) => {
  selectedPayment.value = item
  editPaymentForm.value.payment_reference = item.payment_reference || ''
  editPaymentForm.value.notes = item.notes || ''
  editPaymentForm.value.attachments = []
  showEditPaymentModal.value = true
}

const submitEditPayment = () => {
  if (!editPaymentForm.value.payment_reference) {
    alert('Mohon isi referensi transfer.')
    return
  }

  isSubmittingEditPayment.value = true
  router.post(route('keuangan.tagihan-bulanan.update-details', selectedPayment.value.id), editPaymentForm.value, {
    forceFormData: true,
    onSuccess: () => {
      showEditPaymentModal.value = false
    },
    onFinish: () => {
      isSubmittingEditPayment.value = false
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
  has_end_date: false,
  end_date: '',
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
  typeForm.value.has_end_date = false
  typeForm.value.end_date = ''
  typeForm.value.cash_account_id = props.cashAccounts[0]?.id || ''
  showAddTypeModal.value = true
}

const openEditTypeModal = (item) => {
  isEditType.value = true
  selectedTypeId.value = item.bill_type_id
  typeForm.value.name = item.bill_type_name
  typeForm.value.vendor_name = (item.vendor_name !== '-') ? item.vendor_name : ''
  typeForm.value.default_amount = item.default_amount
  rawTypeAmountInput.value = item.default_amount ? new Intl.NumberFormat('id-ID').format(item.default_amount) : '0'
  typeForm.value.due_date = item.due_date_raw || new Date().toISOString().split('T')[0]
  typeForm.value.has_end_date = false // To make it perfect, we'd need end_date from API, but we just reset it
  typeForm.value.end_date = ''
  typeForm.value.cash_account_id = item.cash_account_id || props.cashAccounts[0]?.id || ''
  showAddTypeModal.value = true
}

const deleteBillType = (id) => {
  if (confirm('Anda yakin ingin menghapus KESELURUHAN tagihan ini? (Semua tagihan yang belum dibayar akan ikut terhapus)')) {
    router.delete(route('keuangan.tagihan-bulanan.types.destroy', id), {
      preserveScroll: true
    })
  }
}

const submitBillType = () => {
  if (!typeForm.value.name || !typeForm.value.default_amount || !typeForm.value.cash_account_id) {
    alert('Mohon isi nama tagihan, estimasi nominal, dan akun kas pembayar.')
    return
  }

  const payload = { ...typeForm.value }
  if (!payload.has_end_date) {
      payload.end_date = null
  }

  isSubmittingType.value = true
  if (isEditType.value) {
    router.put(route('keuangan.tagihan-bulanan.types.update', selectedTypeId.value), payload, {
      onSuccess: () => {
        showAddTypeModal.value = false
      },
      onFinish: () => {
        isSubmittingType.value = false
      }
    })
  } else {
    router.post(route('keuangan.tagihan-bulanan.types.store'), payload, {
      onSuccess: () => {
        showAddTypeModal.value = false
      },
      onFinish: () => {
        isSubmittingType.value = false
      }
    })
  }
}

// Edit Amount Modal
const showEditAmountModal = ref(false)
const selectedPaymentForAmount = ref(null)
const editAmountForm = ref({ bill_amount: 0 })
const rawEditAmountInput = ref('')

const handleEditAmountInput = (e) => {
  const value = e.target.value.replace(/\D/g, '')
  editAmountForm.value.bill_amount = value ? parseInt(value, 10) : 0
  rawEditAmountInput.value = value ? new Intl.NumberFormat('id-ID').format(value) : ''
}

const openEditAmountModal = (item) => {
  selectedPaymentForAmount.value = item
  editAmountForm.value.bill_amount = item.bill_amount
  rawEditAmountInput.value = item.bill_amount ? new Intl.NumberFormat('id-ID').format(item.bill_amount) : '0'
  showEditAmountModal.value = true
}

const submitEditAmount = () => {
  router.put(route('keuangan.tagihan-bulanan.amount.update', selectedPaymentForAmount.value.id), editAmountForm.value, {
    onSuccess: () => {
      showEditAmountModal.value = false
    }
  })
}

const deletePayment = (item) => {
  if (confirm('Apakah Anda yakin ingin menghapus tagihan ini untuk bulan ini saja?')) {
    router.delete(route('keuangan.tagihan-bulanan.destroy', item.id))
  }
}

const cancelPayment = (item) => {
  if (confirm('Apakah Anda yakin ingin MEMBATALKAN pembayaran ini? Saldo kas akan otomatis dikembalikan.')) {
    router.post(route('keuangan.tagihan-bulanan.cancel', item.id))
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
                <div class="flex items-center gap-1">
                  <button
                    @click="openEditAmountModal(item)"
                    class="p-1 text-slate-400 hover:text-emerald-600 rounded transition-colors"
                    title="Edit Nominal Bulan Ini"
                    v-if="item.status !== 'paid'"
                  >
                    <DollarSign class="w-4 h-4" />
                  </button>
                  <button
                    @click="openEditTypeModal(item)"
                    class="p-1 text-slate-400 hover:text-indigo-600 rounded transition-colors"
                    title="Edit Jenis Tagihan (Master)"
                  >
                    <Pen class="w-4 h-4" />
                  </button>
                  <button
                    @click="deletePayment(item)"
                    class="p-1 text-slate-400 hover:text-rose-600 rounded transition-colors"
                    title="Hapus Tagihan Bulan Ini"
                    v-if="item.status !== 'paid'"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                  </button>
                  <button
                    @click="deleteBillType(item.bill_type_id)"
                    class="p-1 text-slate-400 hover:text-red-700 rounded transition-colors"
                    title="Hapus KESELURUHAN Tagihan (Master)"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" y1="11" x2="14" y2="17"/><line x1="14" y1="11" x2="10" y2="17"/></svg>
                  </button>
                </div>
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
                <div class="flex items-center justify-end gap-1 mb-1">
                  <span class="text-slate-400 text-[10px]">Ref: {{ item.payment_reference }}</span>
                  <button @click="openEditPaymentModal(item)" class="text-indigo-500 hover:text-indigo-700 p-0.5" title="Edit Referensi & Lampiran">
                    <Pen class="w-3 h-3" />
                  </button>
                </div>
                <span class="text-emerald-600 font-bold block mb-1">Dibayar {{ item.payment_date }}</span>
                <button
                  @click="cancelPayment(item)"
                  class="text-rose-500 hover:text-rose-700 font-medium underline text-[10px]"
                >
                  Batalkan Pembayaran
                </button>
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

          <div class="grid grid-cols-1 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Tipe Durasi Tagihan <span class="text-rose-500">*</span></label>
              <select v-model="typeForm.has_end_date" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-800 font-semibold">
                <option :value="false">Selamanya (Otomatis muncul tiap bulan)</option>
                <option :value="true">Ada Batas Akhir / Sampai Tanggal Tertentu</option>
              </select>
            </div>

            <div v-if="typeForm.has_end_date">
              <label class="block font-bold text-slate-700 mb-1">Batas Akhir Tagihan <span class="text-rose-500">*</span></label>
              <input v-model="typeForm.end_date" type="date" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-800 font-semibold" required />
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

    <!-- MODAL EDIT AMOUNT ONLY -->
    <div v-if="showEditAmountModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-sm w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
            <span class="p-1.5 rounded-lg bg-emerald-100 text-emerald-600">
              <DollarSign class="w-4 h-4" />
            </span>
            <span>Edit Nominal (Bulan Ini)</span>
          </h3>
          <button @click="showEditAmountModal = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
        </div>

        <form @submit.prevent="submitEditAmount" class="space-y-4 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Nominal Baru (Rp) <span class="text-rose-500">*</span></label>
            <div class="relative">
              <span class="absolute left-3 top-2 font-bold text-slate-400">Rp</span>
              <input
                :value="rawEditAmountInput"
                @input="handleEditAmountInput"
                type="text"
                placeholder="0"
                class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 font-bold text-slate-900"
              />
            </div>
            <p class="text-[10px] text-slate-400 mt-1">Perubahan ini hanya berlaku untuk tagihan bulan yang dipilih, tidak mengubah template tagihan bulanan.</p>
          </div>

          <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
            <button type="button" @click="showEditAmountModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
            <button
              type="submit"
              class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-md transition-all"
            >
              Simpan Perubahan
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL EDIT PAYMENT DETAILS -->
    <div v-if="showEditPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
            <span class="p-1.5 rounded-lg bg-indigo-100 text-indigo-600">
              <Pen class="w-4 h-4" />
            </span>
            <span>Edit Referensi & Lampiran</span>
          </h3>
          <button @click="showEditPaymentModal = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
        </div>

        <form @submit.prevent="submitEditPayment" class="space-y-4 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Referensi Transfer <span class="text-rose-500">*</span></label>
            <input
              v-model="editPaymentForm.payment_reference"
              type="text"
              placeholder="Contoh: TRF-12345"
              required
              class="w-full px-3 py-2 rounded-xl border border-slate-200 font-bold text-slate-900"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Upload Lampiran Baru (Opsional)</label>
            <input
              @change="handleEditFileChange"
              type="file"
              multiple
              class="w-full text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer"
            />
            <p class="text-[10px] text-slate-400 mt-1">Lampiran yang baru di-upload akan ditambahkan, tidak menghapus lampiran sebelumnya.</p>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Catatan (Opsional)</label>
            <textarea
              v-model="editPaymentForm.notes"
              rows="2"
              class="w-full px-3 py-2 rounded-xl border border-slate-200"
              placeholder="Tambahkan catatan jika ada"
            ></textarea>
          </div>

          <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
            <button type="button" @click="showEditPaymentModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
            <button
              type="submit"
              :disabled="isSubmittingEditPayment"
              class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold shadow-md transition-all disabled:opacity-50"
            >
              Simpan Perubahan
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
