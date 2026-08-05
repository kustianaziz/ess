<script setup>
import { ref, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
  CreditCard,
  Clock,
  CheckCircle2,
  Building2,
  Wallet,
  FileText,
  User,
  ArrowRight,
  Upload
} from 'lucide-vue-next'

const props = defineProps({
  unpaidItems: Array,
  paidItems: Array,
  cashAccounts: Array,
  summary: Object
})

const activeTab = ref('unpaid') // 'unpaid' or 'paid'

// Payment Modal State
const showPayModal = ref(false)
const selectedItem = ref(null)

const payForm = useForm({
  payment_reference: '',
  cash_account_id: '',
  proof_of_payment: null,
  disbursed_budget: 0,
  allowance_breakdown: [
    { item: 'Tiket Pesawat / Transport', amount: 0 },
    { item: 'Uang Saku', amount: 0 },
    { item: 'Uang Makan', amount: 0 }
  ]
})

const selectedProofFiles = ref([])

const openPayModal = (item) => {
  selectedItem.value = item
  payForm.payment_reference = 'TRF-' + Math.floor(100000 + Math.random() * 900000)
  payForm.cash_account_id = props.cashAccounts?.[0]?.id || ''
  payForm.proof_of_payment = []
  selectedProofFiles.value = []
  payForm.disbursed_budget = item.amount
  showPayModal.value = true
}

const handleProofFilesChange = (e) => {
  if (e.target.files && e.target.files.length > 0) {
    const files = Array.from(e.target.files)
    selectedProofFiles.value = files
    payForm.proof_of_payment = files
  }
}

const submitPayment = () => {
  if (!payForm.payment_reference || !payForm.cash_account_id) {
    alert('Mohon isi nomor referensi transfer dan pilih pos akun kas pembayar.')
    return
  }

  payForm.post(route('payment.process', { type: selectedItem.value.type, id: selectedItem.value.id }), {
    onSuccess: () => {
      showPayModal.value = false
      selectedItem.value = null
    }
  })
}

const getBadgeColor = (type) => {
  switch (type) {
    case 'reimbursement':
      return 'bg-purple-100 text-purple-800 border-purple-200'
    case 'operasional':
      return 'bg-blue-100 text-blue-800 border-blue-200'
    case 'perjalanan-dinas':
      return 'bg-emerald-100 text-emerald-800 border-emerald-200'
    default:
      return 'bg-slate-100 text-slate-800 border-slate-200'
  }
}
</script>

<template>
  <Head title="Pencairan & Pembayaran Keuangan" />

  <AuthenticatedLayout>
    <div class="py-6 sm:py-8 max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-6">
      <!-- HEADER -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <span class="p-2 rounded-xl bg-emerald-600 text-white shadow-md shadow-emerald-600/20">
              <CreditCard class="w-5 h-5" />
            </span>
            <span>Pencairan & Pembayaran Keuangan</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Antrean dan histori eksekusi pencairan kas untuk Reimbursement, Operasional, dan Perjalanan Dinas yang telah disetujui.
          </p>
        </div>
      </div>

      <!-- SUMMARY CARDS -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Menunggu Pencairan Kas</span>
            <div class="flex items-baseline gap-2 mt-1">
              <h3 class="text-xl sm:text-2xl font-bold text-amber-600">{{ summary.unpaid_total_formatted }}</h3>
              <span class="text-xs font-semibold text-slate-500">({{ summary.unpaid_count }} pengajuan)</span>
            </div>
          </div>
          <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
            <Clock class="w-5 h-5" />
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Sudah Dicairkan / Dibayar</span>
            <div class="flex items-baseline gap-2 mt-1">
              <h3 class="text-xl sm:text-2xl font-bold text-emerald-600">{{ summary.paid_total_formatted }}</h3>
              <span class="text-xs font-semibold text-slate-500">({{ summary.paid_count }} pengajuan)</span>
            </div>
          </div>
          <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
            <CheckCircle2 class="w-5 h-5" />
          </div>
        </div>
      </div>

      <!-- TAB NAVIGATION -->
      <div class="flex items-center gap-2 border-b border-slate-200 pb-1">
        <button
          @click="activeTab = 'unpaid'"
          class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2"
          :class="activeTab === 'unpaid' ? 'bg-amber-600 text-white shadow-md shadow-amber-600/20' : 'text-slate-600 hover:bg-slate-100'"
        >
          <Clock class="w-4 h-4" />
          <span>Menunggu Pencairan ({{ summary.unpaid_count }})</span>
        </button>

        <button
          @click="activeTab = 'paid'"
          class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2"
          :class="activeTab === 'paid' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20' : 'text-slate-600 hover:bg-slate-100'"
        >
          <CheckCircle2 class="w-4 h-4" />
          <span>Riwayat Sudah Dicairkan ({{ summary.paid_count }})</span>
        </button>
      </div>

      <!-- CONTENT: UNPAID ITEMS -->
      <div v-if="activeTab === 'unpaid'" class="space-y-4">
        <div v-if="unpaidItems.length === 0" class="bg-white rounded-2xl border border-slate-200/80 p-8 text-center space-y-2">
          <CheckCircle2 class="w-10 h-10 text-emerald-500 mx-auto" />
          <h3 class="font-bold text-slate-800 text-sm">Tidak Ada Antrean Pencairan</h3>
          <p class="text-xs text-slate-500">Semua pengajuan yang telah disetujui HRD sudah selesai dicairkan!</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="item in unpaidItems"
            :key="item.type + '_' + item.id"
            class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4 hover:border-emerald-300 transition-all flex flex-col justify-between"
          >
            <div class="space-y-2.5">
              <div class="flex items-center justify-between gap-2 flex-wrap">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider" :class="getBadgeColor(item.type)">
                  {{ item.type_label }}
                </span>
                <span class="text-[11px] text-slate-400 font-medium">{{ item.created_at }}</span>
              </div>

              <div>
                <span class="text-[11px] font-mono text-slate-400 uppercase font-bold">{{ item.request_number }}</span>
                <h3 class="font-bold text-base text-slate-900 tracking-tight">{{ item.category }}</h3>
                <p class="text-xs text-slate-600 mt-0.5 flex items-center gap-1 font-medium">
                  <User class="w-3.5 h-3.5 text-slate-400" />
                  <span>{{ item.applicant_name }}</span>
                  <span class="text-slate-400">• {{ item.division }}</span>
                </p>
              </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-3">
              <div>
                <span class="text-[10px] text-slate-400 block uppercase font-bold">Nominal Dicairkan</span>
                <span class="font-black text-lg text-emerald-700">{{ item.amount_formatted }}</span>
              </div>

              <button
                @click="openPayModal(item)"
                class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition-all flex items-center gap-1.5 shrink-0"
              >
                <CreditCard class="w-4 h-4" />
                <span>Cairkan & Bayar →</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- CONTENT: PAID ITEMS (HISTORY) -->
      <div v-if="activeTab === 'paid'" class="space-y-4">
        <div v-if="paidItems.length === 0" class="bg-white rounded-2xl border border-slate-200/80 p-8 text-center space-y-2">
          <FileText class="w-10 h-10 text-slate-300 mx-auto" />
          <h3 class="font-bold text-slate-800 text-sm">Belum Ada Riwayat Pencairan</h3>
          <p class="text-xs text-slate-500">Belum ada transaksi pengajuan yang telah dibayarkan.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="item in paidItems"
            :key="item.type + '_' + item.id"
            class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-3 opacity-90"
          >
            <div class="flex items-center justify-between gap-2 flex-wrap">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider" :class="getBadgeColor(item.type)">
                {{ item.type_label }}
              </span>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase tracking-wider">
                Lunas
              </span>
            </div>

            <div>
              <span class="text-[11px] font-mono text-slate-400 uppercase font-bold">{{ item.request_number }}</span>
              <h3 class="font-bold text-base text-slate-900 tracking-tight">{{ item.category }}</h3>
              <p class="text-xs text-slate-600 mt-0.5 flex items-center gap-1 font-medium">
                <User class="w-3.5 h-3.5 text-slate-400" />
                <span>{{ item.applicant_name }}</span>
                <span class="text-slate-400">• {{ item.division }}</span>
              </p>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-3 text-xs">
              <div>
                <span class="text-[10px] text-slate-400 block uppercase font-bold">Nominal</span>
                <span class="font-bold text-slate-900">{{ item.amount_formatted }}</span>
              </div>
              <div class="text-right">
                <span class="text-[10px] text-slate-400 block uppercase font-bold">Ref: {{ item.payment_reference }}</span>
                <span class="text-[11px] text-slate-500 font-medium">{{ item.paid_at }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL PROCESS PAYMENT -->
    <div v-if="showPayModal && selectedItem" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-in fade-in">
      <div class="bg-white w-full max-w-lg rounded-2xl p-6 shadow-2xl border border-slate-100 space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
            <span class="p-1.5 rounded-lg bg-emerald-100 text-emerald-600">
              <CreditCard class="w-4 h-4" />
            </span>
            <span>Eksekusi Pencairan & Pembayaran Kas</span>
          </h3>
          <button @click="showPayModal = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
        </div>

        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-1">
          <div class="flex justify-between font-bold text-slate-800">
            <span>{{ selectedItem.request_number }}</span>
            <span class="text-emerald-700 font-black">{{ selectedItem.amount_formatted }}</span>
          </div>
          <p class="text-slate-600">Pemohon: {{ selectedItem.applicant_name }} ({{ selectedItem.division }})</p>
          <p class="text-slate-500 text-[11px]">Kategori: {{ selectedItem.category }}</p>
        </div>

        <form @submit.prevent="submitPayment" class="space-y-4 text-xs">
          <!-- POS AKUN KAS / BANK PEMBAYAR -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Pilih Pos Akun Kas / Bank Pembayar <span class="text-rose-500">*</span></label>
            <select v-model="payForm.cash_account_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-semibold text-slate-800">
              <option v-for="acc in cashAccounts" :key="acc.id" :value="acc.id">
                {{ acc.name }} ({{ acc.type_label }}) - Saldo: {{ acc.current_balance_formatted }}
              </option>
            </select>
          </div>

          <!-- NOMOR REFERENSI TRANSFER -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">No. Referensi Transfer Bank / Struk <span class="text-rose-500">*</span></label>
            <input
              v-model="payForm.payment_reference"
              type="text"
              placeholder="Contoh: TRF-839120 / BCA-92810"
              class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-800 font-bold"
            />
          </div>

          <!-- UPLOAD BUKTI TRANSFER FILE (MULTI-FILE SUPPORT) -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Upload Bukti Transfer / Struk Bank (Bisa Banyak File sekaligus: JPG, PNG, PDF)</label>
            <input
              type="file"
              multiple
              @change="handleProofFilesChange"
              accept=".jpg,.jpeg,.png,.pdf"
              class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer"
            />
            <span class="text-[10px] text-slate-400 block mt-1">Dapat memilih lebih dari 1 file (Maks. 5MB per file)</span>

            <!-- PREVIEW TERPILIH -->
            <div v-if="selectedProofFiles.length > 0" class="mt-2 space-y-1 p-2 rounded-xl bg-slate-50 border border-slate-200">
              <span class="text-[10px] font-bold text-slate-500 block uppercase">File Terpilih ({{ selectedProofFiles.length }}):</span>
              <ul class="space-y-0.5">
                <li v-for="(f, i) in selectedProofFiles" :key="i" class="text-[11px] font-medium text-slate-700 flex items-center gap-1.5 truncate">
                  <Upload class="w-3 h-3 text-emerald-600 shrink-0" />
                  <span class="truncate">{{ f.name }}</span>
                  <span class="text-[9px] text-slate-400 shrink-0">({{ Math.round(f.size / 1024) }} KB)</span>
                </li>
              </ul>
            </div>
          </div>

          <!-- OPTIONAL RINCIAN PERJALANAN DINAS -->
          <div v-if="selectedItem.type === 'perjalanan-dinas'" class="space-y-3 p-3 rounded-xl bg-blue-50/70 border border-blue-100">
            <label class="block font-bold text-blue-900 uppercase tracking-wider text-[11px]">Rincian Komponen Uang Muka Dicairkan</label>

            <div v-for="(item, idx) in payForm.allowance_breakdown" :key="idx" class="grid grid-cols-2 gap-2">
              <input v-model="item.item" type="text" placeholder="Nama Komponen" class="px-2.5 py-1.5 rounded-lg border border-blue-200 font-medium" />
              <input v-model.number="item.amount" type="number" placeholder="Nominal Rp" class="px-2.5 py-1.5 rounded-lg border border-blue-200 font-bold" />
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
            <button type="button" @click="showPayModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
            <button
              type="submit"
              :disabled="payForm.processing"
              class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-md transition-all flex items-center gap-1.5"
            >
              <CheckCircle2 class="w-4 h-4" />
              <span>Konfirmasi & Process Pembayaran Kas</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
