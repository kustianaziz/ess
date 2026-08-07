<script setup>
import { ref } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { RefreshCw, ArrowLeft, Globe, CheckCircle2, FileText, Plus, Trash2, X, Upload, DollarSign, Download } from 'lucide-vue-next'

const props = defineProps({ renewal: Object, cashAccounts: Array })

const formatRp = (v) => Number(v || 0).toLocaleString('id-ID')
const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'

// ============ MODAL: Buat Invoice ke Klien ============
const showInvoiceModal = ref(false)
const ppnMode = ref('exclude')
const ppnRate = ref(11)

// ============ MODAL: Client Payment ============
const showClientPaymentModal = ref(false)
const clientPaymentForm = useForm({
  payment_date: new Date().toISOString().split('T')[0],
  cash_account_id: ''
})

const submitClientPayment = () => {
  clientPaymentForm.post(route('renewal.renewals.mark-paid-customer', props.renewal.id), {
    onSuccess: () => { showClientPaymentModal.value = false; clientPaymentForm.reset() }
  })
}

const invoiceForm = useForm({
  invoice_date: new Date().toISOString().split('T')[0],
  due_date: '',
  notes: `Perpanjangan ${props.renewal.domain?.type || 'layanan'} ${props.renewal.domain?.name || ''} selama ${props.renewal.period_year} tahun.`,
  items: [{ description: `Renewal ${props.renewal.domain?.name || ''} (${props.renewal.period_year} thn)`, qty: 1, unit_price: props.renewal.domain?.price_customer || 0 }],
  subtotal: props.renewal.domain?.price_customer || 0,
  tax_amount: 0,
  total_amount: props.renewal.domain?.price_customer || 0,
})

const calcInvoice = () => {
  let raw = invoiceForm.items.reduce((s, i) => s + (i.qty * i.unit_price), 0)
  if (ppnMode.value === 'none') { invoiceForm.subtotal = raw; invoiceForm.tax_amount = 0; invoiceForm.total_amount = raw }
  else if (ppnMode.value === 'exclude') { const tax = Math.round(raw * ppnRate.value / 100); invoiceForm.subtotal = raw; invoiceForm.tax_amount = tax; invoiceForm.total_amount = raw + tax }
  else { const sub = Math.round(raw / (1 + ppnRate.value / 100)); invoiceForm.subtotal = sub; invoiceForm.tax_amount = raw - sub; invoiceForm.total_amount = raw }
}

const addInvoiceItem = () => invoiceForm.items.push({ description: '', qty: 1, unit_price: 0 })
const removeInvoiceItem = (i) => { if (invoiceForm.items.length > 1) { invoiceForm.items.splice(i, 1); calcInvoice() } }
const submitInvoice = () => {
  calcInvoice()
  invoiceForm.post(route('renewal.renewals.generate-invoice', props.renewal.id), {
    onSuccess: () => { showInvoiceModal.value = false }
  })
}

// ============ MODAL: Bayar Vendor ============
const showVendorModal = ref(false)
const vendorFiles = ref([])
const vendorForm = useForm({ amount: props.renewal.domain?.cost_vendor || 0, payment_date: new Date().toISOString().split('T')[0], cash_account_id: '', notes: '' })

const handleVendorFiles = (e) => { vendorFiles.value = Array.from(e.target.files) }
const submitVendorPayment = () => {
  vendorForm.transform((data) => ({ ...data }))
  const fd = new FormData()
  fd.append('amount', vendorForm.amount)
  fd.append('payment_date', vendorForm.payment_date)
  fd.append('cash_account_id', vendorForm.cash_account_id)
  fd.append('notes', vendorForm.notes || '')
  vendorFiles.value.forEach(f => fd.append('proof_of_payment[]', f))
  vendorForm.post(route('renewal.renewals.vendor-payment.store', props.renewal.id), { data: fd, onSuccess: () => { showVendorModal.value = false } })
}

// ============ MODAL: Selesaikan Renewal ============
const showCompleteModal = ref(false)
const completeForm = useForm({ new_expired_date: '' })
const submitComplete = () => {
  completeForm.post(route('renewal.renewals.complete', props.renewal.id), {
    onSuccess: () => { showCompleteModal.value = false }
  })
}

// ============ UNDO METHODS ============
import { router } from '@inertiajs/vue3'

const undoComplete = () => {
    if(confirm('Batalkan penyelesaian renewal ini? Status akan kembali menjadi Vendor Dibayar.')) {
        router.post(route('renewal.renewals.undo-complete', props.renewal.id), {}, { preserveScroll: true })
    }
}
const undoPaidVendor = () => {
    if(confirm('Batalkan pembayaran ke vendor? Transaksi kas akan dikembalikan dan pembayaran dihapus.')) {
        router.post(route('renewal.renewals.undo-paid-vendor', props.renewal.id), {}, { preserveScroll: true })
    }
}
const undoPaidCustomer = () => {
    if(confirm('Batalkan pembayaran dari klien? Transaksi kas akan ditarik kembali.')) {
        router.post(route('renewal.renewals.undo-paid-customer', props.renewal.id), {}, { preserveScroll: true })
    }
}
const undoInvoice = () => {
    if(confirm('Batalkan dan hapus invoice tagihan klien ini?')) {
        router.post(route('renewal.renewals.undo-invoice', props.renewal.id), {}, { preserveScroll: true })
    }
}

// Status steps config
const steps = [
  { key: 'pending', label: 'Dibuat', icon: '📋' },
  { key: 'invoiced_customer', label: 'Invoice Dikirim', icon: '📄' },
  { key: 'paid_customer', label: 'Klien Bayar', icon: '💳' },
  { key: 'paid_vendor', label: 'Vendor Dibayar', icon: '🏪' },
  { key: 'completed', label: 'Selesai', icon: '✅' },
]
const stepOrder = ['pending', 'invoiced_customer', 'paid_customer', 'paid_vendor', 'completed']
const currentStep = (status) => stepOrder.indexOf(status)
</script>

<template>
  <Head :title="`Detail Renewal ${renewal.renewal_number}`" />
  <AuthenticatedLayout>
    <div class="max-w-5xl mx-auto space-y-6 py-6 sm:py-8 px-3 sm:px-6 lg:px-8">

      <!-- HEADER -->
      <div class="flex items-center gap-4">
        <Link :href="route('renewal.renewals.index')" class="p-2.5 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
          <ArrowLeft class="w-5 h-5 text-slate-600" />
        </Link>
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-slate-900">{{ renewal.renewal_number }}</h1>
          <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-1.5">
            <Globe class="w-3.5 h-3.5 text-cyan-500" />
            {{ renewal.domain?.name }} · {{ renewal.domain?.customer?.name }}
            · Vendor: {{ renewal.domain?.vendor?.name }}
          </p>
        </div>
      </div>

      <!-- PROGRESS STEPS -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
        <h2 class="text-sm font-bold text-slate-700 mb-4">Progress Renewal</h2>
        <div class="flex items-center gap-0">
          <template v-for="(step, idx) in steps" :key="step.key">
            <div class="flex flex-col items-center gap-1 flex-1">
              <div class="w-9 h-9 rounded-full flex items-center justify-center text-base font-bold border-2 transition-all"
                :class="currentStep(renewal.status) >= idx ? 'bg-emerald-600 border-emerald-600 text-white shadow-md shadow-emerald-600/20' : 'bg-white border-slate-200 text-slate-400'">
                {{ step.icon }}
              </div>
              <span class="text-[10px] font-bold text-center leading-tight" :class="currentStep(renewal.status) >= idx ? 'text-emerald-700' : 'text-slate-400'">{{ step.label }}</span>
            </div>
            <div v-if="idx < steps.length - 1" class="flex-1 h-0.5 mb-5 mx-1 rounded-full transition-all" :class="currentStep(renewal.status) > idx ? 'bg-emerald-500' : 'bg-slate-200'"></div>
          </template>
        </div>
      </div>

      <!-- INFO CARDS -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4">
          <p class="text-[11px] font-bold text-slate-500 uppercase mb-1">Periode</p>
          <p class="text-xl font-black text-slate-900">{{ renewal.period_year }} Tahun</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4">
          <p class="text-[11px] font-bold text-slate-500 uppercase mb-1">Harga ke Klien</p>
          <p class="text-xl font-black text-indigo-700">Rp {{ formatRp(renewal.domain?.price_customer) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4">
          <p class="text-[11px] font-bold text-slate-500 uppercase mb-1">Biaya Vendor</p>
          <p class="text-xl font-black text-rose-600">Rp {{ formatRp(renewal.domain?.cost_vendor) }}</p>
          <p class="text-[11px] text-emerald-600 font-bold mt-1">
            Margin: Rp {{ formatRp((renewal.domain?.price_customer || 0) - (renewal.domain?.cost_vendor || 0)) }}
          </p>
        </div>
      </div>

      <!-- INVOICE SECTION -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2"><FileText class="w-4 h-4 text-indigo-500" /> Invoice ke Klien</h2>
          <div class="flex gap-2">
            <a v-if="renewal.invoice" :href="route('invoicing.invoices.pdf', renewal.invoice.id)" target="_blank"
              class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 text-xs font-bold flex items-center gap-1.5 shadow-sm transition-colors">
              <Download class="w-3.5 h-3.5" /> Cetak PDF
            </a>
            <button v-if="renewal.invoice && renewal.status === 'invoiced_customer'" @click="undoInvoice"
              class="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-600 text-xs font-bold hover:bg-rose-100 flex items-center gap-1.5 transition-colors">
              <RefreshCw class="w-3.5 h-3.5" /> Batal Invoice
            </button>
            <button v-if="!renewal.invoice && renewal.status === 'pending'" @click="showInvoiceModal = true"
              class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700 flex items-center gap-1.5 shadow-md">
              <Plus class="w-3.5 h-3.5" /> Buat Invoice
            </button>
          </div>
        </div>
        <div v-if="renewal.invoice" class="space-y-2">
          <div class="flex flex-wrap justify-between gap-2 p-3 bg-indigo-50 rounded-xl border border-indigo-100">
            <div>
              <p class="font-mono text-sm font-black text-indigo-700">{{ renewal.invoice.invoice_number }}</p>
              <p class="text-xs text-slate-500">Jatuh Tempo: {{ formatDate(renewal.invoice.due_date) }}</p>
            </div>
            <div class="text-right">
              <p class="text-lg font-black text-indigo-900">Rp {{ formatRp(renewal.invoice.total_amount) }}</p>
              <span class="px-2 py-0.5 rounded-md text-[10px] font-bold" :class="renewal.invoice.status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                {{ renewal.invoice.status?.toUpperCase() }}
              </span>
            </div>
          </div>
          <!-- Mark Paid Customer -->
          <div class="flex flex-col gap-2">
            <button v-if="renewal.status === 'invoiced_customer'" @click="showClientPaymentModal = true"
              class="w-full py-2 rounded-xl bg-sky-600 text-white text-xs font-bold hover:bg-sky-700 flex items-center justify-center gap-2">
              <CheckCircle2 class="w-4 h-4" /> Tandai Klien Sudah Membayar
            </button>
            <button v-if="renewal.status === 'paid_customer'" @click="undoPaidCustomer"
              class="w-full py-2 rounded-xl border border-rose-200 text-rose-600 bg-rose-50 hover:bg-rose-100 text-xs font-bold flex items-center justify-center gap-2 transition-colors">
              <RefreshCw class="w-4 h-4" /> Batal Bayar Klien
            </button>
          </div>
        </div>
        <div v-else class="py-4 text-center text-slate-400 text-sm italic">Belum ada invoice untuk renewal ini.</div>
      </div>

      <!-- VENDOR PAYMENT SECTION -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2"><DollarSign class="w-4 h-4 text-rose-500" /> Pembayaran ke Vendor</h2>
          <div class="flex gap-2">
            <button v-if="renewal.vendor_payment && renewal.status === 'paid_vendor'" @click="undoPaidVendor"
              class="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-600 text-xs font-bold hover:bg-rose-100 flex items-center gap-1.5 transition-colors">
              <RefreshCw class="w-3.5 h-3.5" /> Batal Bayar Vendor
            </button>
            <button v-if="!renewal.vendor_payment && renewal.status === 'paid_customer'" @click="showVendorModal = true"
              class="px-3 py-1.5 rounded-lg bg-rose-600 text-white text-xs font-bold hover:bg-rose-700 flex items-center gap-1.5 shadow-md">
              <Plus class="w-3.5 h-3.5" /> Catat Pembayaran Vendor
            </button>
          </div>
        </div>
        <div v-if="renewal.vendor_payment" class="space-y-3">
          <div class="flex flex-wrap justify-between gap-2 p-3 bg-rose-50 rounded-xl border border-rose-100">
            <div>
              <p class="text-xs text-slate-500">Vendor: <span class="font-bold text-slate-800">{{ renewal.domain?.vendor?.name }}</span></p>
              <p class="text-xs text-slate-500">Tanggal Bayar: <span class="font-bold text-slate-800">{{ formatDate(renewal.vendor_payment.payment_date) }}</span></p>
            </div>
            <p class="text-lg font-black text-rose-700">Rp {{ formatRp(renewal.vendor_payment.amount) }}</p>
          </div>
          <!-- Bukti upload -->
          <div v-if="renewal.vendor_payment.attachments?.length" class="space-y-1">
            <p class="text-xs font-bold text-slate-500">Bukti Pembayaran:</p>
            <div v-for="att in renewal.vendor_payment.attachments" :key="att.id" class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 border border-slate-200">
              <Upload class="w-3.5 h-3.5 text-slate-400" />
              <a :href="`/storage/${att.file_path}`" target="_blank" class="text-xs font-semibold text-indigo-600 hover:underline">{{ att.file_name }}</a>
            </div>
          </div>
          <!-- Selesaikan -->
          <button v-if="renewal.status === 'paid_vendor'" @click="showCompleteModal = true"
            class="w-full py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 flex items-center justify-center gap-2">
            <CheckCircle2 class="w-4 h-4" /> Selesaikan Renewal & Update Expired Date
          </button>
        </div>
        <div v-else class="py-4 text-center text-slate-400 text-sm italic">
          <span v-if="renewal.status === 'paid_customer'">Klik tombol di atas untuk mencatat pembayaran ke vendor.</span>
          <span v-else>Tunggu klien melunasi invoice terlebih dahulu.</span>
        </div>
      </div>

      <!-- Completed Info -->
      <div v-if="renewal.status === 'completed'" class="bg-emerald-50 border border-emerald-200 rounded-2xl p-5 flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <CheckCircle2 class="w-10 h-10 text-emerald-600 shrink-0" />
          <div>
            <p class="font-black text-emerald-800 text-base">Renewal Selesai! 🎉</p>
            <p class="text-sm text-emerald-700 mt-0.5">
              Domain <strong>{{ renewal.domain?.name }}</strong> berhasil diperpanjang.
              Expired baru: <strong>{{ formatDate(renewal.new_expired_date) }}</strong>
            </p>
          </div>
        </div>
        <button @click="undoComplete"
          class="px-4 py-2 rounded-xl border border-rose-200 text-rose-600 bg-rose-50 hover:bg-rose-100 text-xs font-bold flex items-center gap-2 transition-colors">
          <RefreshCw class="w-4 h-4" /> Batal Selesai
        </button>
      </div>
    </div>

    <!-- ===== MODAL CLIENT PAYMENT ===== -->
    <div v-if="showClientPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white w-full max-w-sm rounded-2xl shadow-xl overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b border-slate-100">
          <h3 class="font-bold text-lg text-slate-900">Pembayaran dari Klien</h3>
          <button @click="showClientPaymentModal = false"><X class="w-5 h-5 text-slate-400" /></button>
        </div>
        <form @submit.prevent="submitClientPayment" class="p-4 space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Bayar</label>
            <input v-model="clientPaymentForm.payment_date" type="date" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Masuk ke Kas/Bank</label>
            <select v-model="clientPaymentForm.cash_account_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm">
              <option value="">Pilih Kas/Bank...</option>
              <option v-for="acc in cashAccounts" :key="acc.id" :value="acc.id">{{ acc.name }} - {{ acc.code }}</option>
            </select>
          </div>
          <div class="pt-2 flex gap-2">
            <button type="button" @click="showClientPaymentModal = false" class="flex-1 py-2 rounded-xl bg-slate-100 text-slate-600 font-bold text-sm">Batal</button>
            <button type="submit" :disabled="clientPaymentForm.processing" class="flex-1 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold text-sm">Catat & Lunas</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ===== MODAL BUAT INVOICE ===== -->
    <div v-if="showInvoiceModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white w-full max-w-2xl rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-4 border-b border-slate-100 sticky top-0 bg-white">
          <h3 class="font-bold text-lg text-slate-900">Buat Invoice ke Klien</h3>
          <button @click="showInvoiceModal = false"><X class="w-5 h-5 text-slate-400" /></button>
        </div>
        <form @submit.prevent="submitInvoice" class="p-4 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Invoice</label>
              <input v-model="invoiceForm.invoice_date" type="date" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-indigo-500">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Jatuh Tempo</label>
              <input v-model="invoiceForm.due_date" type="date" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-indigo-500">
            </div>
          </div>

          <!-- Items -->
          <div class="pt-3 border-t border-slate-100">
            <div class="flex items-center justify-between mb-2">
              <h4 class="text-xs font-bold text-slate-700">Item Invoice</h4>
              <button type="button" @click="addInvoiceItem" class="text-[11px] text-indigo-600 font-bold hover:underline flex items-center gap-1"><Plus class="w-3 h-3"/>Tambah Item</button>
            </div>
            <div class="space-y-2">
              <div v-for="(item, idx) in invoiceForm.items" :key="idx" class="flex gap-2 p-2 rounded-xl bg-slate-50 border border-slate-200">
                <input v-model="item.description" type="text" placeholder="Deskripsi" class="flex-1 px-2 py-1.5 rounded-lg border border-slate-300 bg-white text-xs focus:border-indigo-500">
                <input v-model.number="item.qty" type="number" min="1" @input="calcInvoice" class="w-14 px-2 py-1.5 rounded-lg border border-slate-300 bg-white text-xs text-center font-bold focus:border-indigo-500">
                <input v-model.number="item.unit_price" type="number" min="0" @input="calcInvoice" class="w-28 px-2 py-1.5 rounded-lg border border-slate-300 bg-white text-xs font-bold focus:border-indigo-500">
                <button type="button" @click="removeInvoiceItem(idx)" :disabled="invoiceForm.items.length <= 1" class="p-1.5 rounded-lg bg-white border border-slate-200 hover:border-rose-200 text-slate-400 hover:text-rose-500 disabled:opacity-30"><Trash2 class="w-3.5 h-3.5"/></button>
              </div>
            </div>
          </div>

          <!-- PPN Mode -->
          <div class="pt-3 border-t border-slate-100">
            <p class="text-xs font-bold text-slate-700 mb-2">Pengaturan PPN</p>
            <div class="flex gap-2">
              <button type="button" @click="ppnMode='none'; calcInvoice()" class="flex-1 py-2 rounded-xl border-2 text-xs font-bold transition-all" :class="ppnMode==='none' ? 'border-slate-600 bg-slate-600 text-white' : 'border-slate-200 text-slate-600'">Tanpa PPN</button>
              <button type="button" @click="ppnMode='exclude'; calcInvoice()" class="flex-1 py-2 rounded-xl border-2 text-xs font-bold transition-all" :class="ppnMode==='exclude' ? 'border-amber-500 bg-amber-500 text-white' : 'border-slate-200 text-slate-600'">Eksklusif {{ ppnRate }}%</button>
              <button type="button" @click="ppnMode='include'; calcInvoice()" class="flex-1 py-2 rounded-xl border-2 text-xs font-bold transition-all" :class="ppnMode==='include' ? 'border-blue-500 bg-blue-500 text-white' : 'border-slate-200 text-slate-600'">Inklusif {{ ppnRate }}%</button>
            </div>
          </div>

          <!-- Total -->
          <div class="flex flex-col items-end gap-1.5 text-sm pt-3 border-t border-slate-100">
            <div class="flex justify-between w-56"><span class="text-slate-500">Subtotal</span><span class="font-bold">Rp {{ formatRp(invoiceForm.subtotal) }}</span></div>
            <div v-if="ppnMode !== 'none'" class="flex justify-between w-56"><span class="text-slate-500">PPN</span><span class="font-bold">Rp {{ formatRp(invoiceForm.tax_amount) }}</span></div>
            <div class="flex justify-between w-56 pt-2 border-t border-slate-200"><span class="font-black text-slate-900">Total</span><span class="font-black text-indigo-700 text-base">Rp {{ formatRp(invoiceForm.total_amount) }}</span></div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan</label>
            <textarea v-model="invoiceForm.notes" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-indigo-500"></textarea>
          </div>

          <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
            <button type="button" @click="showInvoiceModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold hover:bg-slate-200">Batal</button>
            <button type="submit" :disabled="invoiceForm.processing" class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 shadow-md">Buat Invoice</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ===== MODAL BAYAR VENDOR ===== -->
    <div v-if="showVendorModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white w-full max-w-md rounded-2xl shadow-xl overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b border-slate-100">
          <h3 class="font-bold text-lg text-slate-900">Catat Pembayaran ke Vendor</h3>
          <button @click="showVendorModal = false"><X class="w-5 h-5 text-slate-400" /></button>
        </div>
        <form @submit.prevent="submitVendorPayment" class="p-4 space-y-4">
          <div class="p-3 bg-rose-50 rounded-xl border border-rose-100 text-sm">
            <p class="font-bold text-rose-800">Vendor: {{ renewal.domain?.vendor?.name }}</p>
            <p class="text-rose-600 text-xs mt-0.5">Biaya vendor: <strong>Rp {{ formatRp(renewal.domain?.cost_vendor) }}</strong></p>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Jumlah Dibayar (Rp) <span class="text-rose-500">*</span></label>
            <input v-model.number="vendorForm.amount" type="number" min="1" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm font-bold focus:border-rose-500">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Pembayaran <span class="text-rose-500">*</span></label>
            <input v-model="vendorForm.payment_date" type="date" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-rose-500">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Bayar dari Kas <span class="text-rose-500">*</span></label>
            <select v-model="vendorForm.cash_account_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm font-semibold focus:border-rose-500">
              <option value="">-- Pilih Rekening Kas --</option>
              <option v-for="acc in cashAccounts" :key="acc.id" :value="acc.id">{{ acc.name }} (Rp {{ formatRp(acc.current_balance) }})</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Bukti Pembayaran (multi-upload)</label>
            <input type="file" accept=".jpg,.jpeg,.png,.pdf" multiple @change="handleVendorFiles" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-rose-500">
            <p class="text-[11px] text-slate-400 mt-1">Bisa pilih beberapa file sekaligus (jpg, png, pdf · maks 5MB/file)</p>
          </div>
          <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
            <button type="button" @click="showVendorModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold hover:bg-slate-200">Batal</button>
            <button type="submit" :disabled="vendorForm.processing" class="px-4 py-2 rounded-xl bg-rose-600 text-white font-bold hover:bg-rose-700 shadow-md">Catat Pembayaran</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ===== MODAL SELESAIKAN RENEWAL ===== -->
    <div v-if="showCompleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white w-full max-w-sm rounded-2xl shadow-xl overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b border-slate-100">
          <h3 class="font-bold text-lg text-slate-900">Selesaikan Renewal</h3>
          <button @click="showCompleteModal = false"><X class="w-5 h-5 text-slate-400" /></button>
        </div>
        <form @submit.prevent="submitComplete" class="p-4 space-y-4">
          <div class="p-3 bg-emerald-50 border border-emerald-100 rounded-xl text-sm">
            <p class="text-emerald-700">Expired date domain <strong>{{ renewal.domain?.name }}</strong> akan diperbarui secara otomatis.</p>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Expired Date Baru <span class="text-rose-500">*</span></label>
            <input v-model="completeForm.new_expired_date" type="date" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm font-bold focus:border-emerald-500">
          </div>
          <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
            <button type="button" @click="showCompleteModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold hover:bg-slate-200">Batal</button>
            <button type="submit" :disabled="completeForm.processing" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-bold hover:bg-emerald-700 shadow-md">Selesaikan ✅</button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
