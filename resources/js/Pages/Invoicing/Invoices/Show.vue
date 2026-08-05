<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ArrowLeft, CheckCircle2, Wallet, Clock, Download, Upload, Trash2, Send } from 'lucide-vue-next'

const props = defineProps({
  invoice: Object,
  cashAccounts: Array
})

// Payment Modal State
const showPayModal = ref(false)
const payForm = useForm({
  amount: 0,
  payment_date: new Date().toISOString().split('T')[0],
  payment_method: 'Transfer Bank',
  cash_account_id: props.cashAccounts?.[0]?.id || '',
  proof_of_payment: []
})

const selectedProofFiles = ref([])

const openPayModal = () => {
  const remaining = Number(props.invoice.total_amount) - Number(props.invoice.paid_amount)
  payForm.amount = remaining > 0 ? remaining : 0
  payForm.payment_date = new Date().toISOString().split('T')[0]
  payForm.payment_method = 'Transfer Bank'
  payForm.cash_account_id = props.cashAccounts?.[0]?.id || ''
  payForm.proof_of_payment = []
  selectedProofFiles.value = []
  showPayModal.value = true
}

const handleProofFilesChange = (e) => {
  if (e.target.files && e.target.files.length > 0) {
    const newFiles = Array.from(e.target.files)
    const combined = [...selectedProofFiles.value]
    newFiles.forEach((nf) => {
      if (!combined.some(f => f.name === nf.name && f.size === nf.size)) {
        combined.push(nf)
      }
    })
    selectedProofFiles.value = combined
    payForm.proof_of_payment = combined
    e.target.value = ''
  }
}

const removeProofFile = (index) => {
  selectedProofFiles.value.splice(index, 1)
  payForm.proof_of_payment = selectedProofFiles.value
}

const submitPayment = () => {
  if (payForm.amount <= 0) {
    alert('Nominal pembayaran harus lebih dari 0.')
    return
  }
  if (!payForm.cash_account_id) {
    alert('Silakan pilih Rekening / Kas penerima pembayaran.')
    return
  }

  payForm.post(route('invoicing.invoices.payments.store', props.invoice.id), {
    onSuccess: () => {
      showPayModal.value = false
    }
  })
}

const getStatusBadge = (status) => {
  switch (status) {
    case 'draft': return 'bg-slate-100 text-slate-700 border-slate-200'
    case 'sent': return 'bg-blue-100 text-blue-700 border-blue-200'
    case 'partial': return 'bg-amber-100 text-amber-700 border-amber-200'
    case 'paid': return 'bg-emerald-100 text-emerald-700 border-emerald-200'
    case 'overdue': return 'bg-rose-100 text-rose-700 border-rose-200'
    case 'cancelled': return 'bg-slate-800 text-white border-slate-700'
    default: return 'bg-slate-100 text-slate-700'
  }
}

const getStatusLabel = (status) => {
  const map = {
    'draft': 'DRAFT',
    'sent': 'TERKIRIM',
    'partial': 'CICILAN',
    'paid': 'LUNAS',
    'overdue': 'JATUH TEMPO',
    'cancelled': 'DIBATALKAN'
  }
  return map[status] || status.toUpperCase()
}

const formatRupiah = (angka) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka || 0)
}
</script>

<template>
  <Head :title="`Detail Invoice ${invoice.invoice_number}`" />

  <AuthenticatedLayout>
    <div class="max-w-5xl mx-auto space-y-6 py-6 sm:py-8 px-3 sm:px-6 lg:px-8">
      <!-- HEADER -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <Link :href="route('invoicing.invoices.index')" class="p-2 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
            <ArrowLeft class="w-5 h-5 text-slate-600" />
          </Link>
          <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
              Invoice #{{ invoice.invoice_number }}
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">Detail tagihan, item, dan status pembayaran.</p>
          </div>
        </div>
        
        <div class="flex gap-2 w-full sm:w-auto">
          <a :href="route('invoicing.invoices.pdf', invoice.id)" target="_blank"
            class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold shadow-sm transition-all flex items-center justify-center gap-2 border border-slate-300"
          >
            <Download class="w-4 h-4" />
            <span>Unduh PDF</span>
          </a>
          <button
            v-if="!['paid', 'cancelled'].includes(invoice.status)"
            @click="openPayModal"
            class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-lg shadow-emerald-600/20 transition-all flex items-center justify-center gap-2"
          >
            <Wallet class="w-4 h-4" />
            <span>Terima Pembayaran</span>
          </button>
        </div>
      </div>

      <!-- INVOICE DOCUMENT -->
      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-10 space-y-8">
        
        <!-- HEADER INVOICE -->
        <div class="flex flex-col md:flex-row justify-between gap-6 pb-6 border-b border-slate-100">
          <div>
            <span class="px-3 py-1 rounded-lg text-[11px] font-bold border uppercase tracking-wider mb-4 inline-block" :class="getStatusBadge(invoice.status)">
              STATUS: {{ getStatusLabel(invoice.status) }}
            </span>
            <h2 class="text-base font-bold text-slate-400 uppercase tracking-widest mb-1">DITAGIHKAN KEPADA:</h2>
            <h3 class="text-xl font-black text-slate-900">{{ invoice.customer?.name }}</h3>
            <p class="text-sm text-slate-600 mt-1 max-w-sm">{{ invoice.customer?.address || 'Alamat tidak tersedia' }}</p>
            <p class="text-xs text-slate-500 mt-1">PIC: {{ invoice.customer?.pic_name || '-' }} | {{ invoice.customer?.phone || '-' }}</p>
          </div>
          
          <div class="md:text-right space-y-2">
            <div>
              <p class="text-[10px] font-bold text-slate-400 uppercase">TANGGAL INVOICE</p>
              <p class="font-semibold text-slate-800 text-sm">{{ invoice.invoice_date }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-slate-400 uppercase">JATUH TEMPO (DUE DATE)</p>
              <p class="font-semibold text-sm" :class="invoice.status === 'overdue' ? 'text-rose-600 font-bold' : 'text-slate-800'">
                {{ invoice.due_date }}
              </p>
            </div>
            <div v-if="invoice.po_number">
              <p class="text-[10px] font-bold text-slate-400 uppercase">NO. PO / REFERENSI</p>
              <p class="font-semibold text-slate-800 text-sm">{{ invoice.po_number }}</p>
            </div>
          </div>
        </div>

        <!-- ITEMS -->
        <div>
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b-2 border-slate-200">
                <th class="py-3 text-xs font-bold text-slate-400 uppercase">Deskripsi Item</th>
                <th class="py-3 text-xs font-bold text-slate-400 uppercase text-center w-16">Qty</th>
                <th class="py-3 text-xs font-bold text-slate-400 uppercase text-right w-32 sm:w-40">Harga Satuan</th>
                <th class="py-3 text-xs font-bold text-slate-400 uppercase text-right w-32 sm:w-40">Subtotal</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in invoice.items" :key="item.id" class="text-sm">
                <td class="py-4 font-medium text-slate-800">{{ item.description }}</td>
                <td class="py-4 text-center text-slate-600">{{ item.qty }}</td>
                <td class="py-4 text-right text-slate-600">{{ formatRupiah(item.unit_price) }}</td>
                <td class="py-4 text-right font-bold text-slate-900">{{ formatRupiah(item.subtotal) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- TOTALS -->
        <div class="flex flex-col md:flex-row justify-between gap-6 pt-6 border-t border-slate-100">
          <div class="flex-1">
            <h4 class="text-xs font-bold text-slate-500 uppercase mb-2">Catatan Tambahan</h4>
            <div class="p-4 bg-slate-50 rounded-xl text-xs text-slate-600 whitespace-pre-line border border-slate-200/60">
              {{ invoice.notes || 'Tidak ada catatan.' }}
            </div>
          </div>

          <div class="w-full md:w-72 space-y-3">
            <div class="flex justify-between text-sm">
              <span class="font-semibold text-slate-500">Subtotal</span>
              <span class="font-bold text-slate-800">{{ formatRupiah(invoice.subtotal) }}</span>
            </div>
            <div v-if="invoice.tax_amount > 0" class="flex justify-between text-sm">
              <span class="font-semibold text-slate-500">Pajak (PPN)</span>
              <span class="font-bold text-slate-800">{{ formatRupiah(invoice.tax_amount) }}</span>
            </div>
            <div class="flex justify-between text-lg pt-3 border-t border-slate-200">
              <span class="font-black text-slate-900">Total Tagihan</span>
              <span class="font-black text-indigo-700">{{ formatRupiah(invoice.total_amount) }}</span>
            </div>
            
            <div class="p-3 mt-4 rounded-xl border" :class="Number(invoice.paid_amount) >= Number(invoice.total_amount) ? 'bg-emerald-50 border-emerald-200' : 'bg-amber-50 border-amber-200'">
              <div class="flex justify-between text-xs mb-1">
                <span class="font-bold" :class="Number(invoice.paid_amount) >= Number(invoice.total_amount) ? 'text-emerald-700' : 'text-amber-700'">Sudah Terbayar</span>
                <span class="font-bold" :class="Number(invoice.paid_amount) >= Number(invoice.total_amount) ? 'text-emerald-700' : 'text-amber-700'">{{ formatRupiah(invoice.paid_amount) }}</span>
              </div>
              <div class="flex justify-between text-xs pt-1 border-t" :class="Number(invoice.paid_amount) >= Number(invoice.total_amount) ? 'border-emerald-200/50' : 'border-amber-200/50'">
                <span class="font-semibold text-slate-500">Sisa Tagihan</span>
                <span class="font-bold text-rose-600">{{ formatRupiah(Number(invoice.total_amount) - Number(invoice.paid_amount)) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- RIWAYAT PEMBAYARAN -->
        <div v-if="invoice.payments && invoice.payments.length > 0" class="pt-6 border-t border-slate-100">
          <h3 class="font-bold text-slate-800 text-sm mb-4 flex items-center gap-2">
            <Clock class="w-4 h-4 text-slate-400" /> Riwayat Pembayaran Masuk
          </h3>
          <div class="space-y-3">
            <div v-for="payment in invoice.payments" :key="payment.id" class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row justify-between gap-4">
              <div>
                <p class="font-bold text-emerald-700 text-base">{{ formatRupiah(payment.amount) }}</p>
                <p class="text-xs font-semibold text-slate-600 mt-0.5">Tgl: {{ payment.payment_date }} | Via: {{ payment.payment_method }}</p>
                <p class="text-[10px] text-slate-400 mt-1">Dicatat oleh: {{ payment.recorded_by_user?.name || 'Sistem' }}</p>
              </div>
              
              <!-- Lampiran Bukti Pembayaran -->
              <div v-if="payment.attachments && payment.attachments.length > 0" class="sm:text-right">
                <span class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Bukti Transfer</span>
                <div class="flex flex-wrap sm:justify-end gap-2">
                  <a v-for="att in payment.attachments" :key="att.id" :href="`/storage/${att.file_path}`" target="_blank" class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-[10px] font-bold text-indigo-600 hover:bg-indigo-50 transition-colors flex items-center gap-1 shadow-xs">
                    <Download class="w-3 h-3" /> {{ att.file_name }}
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- MODAL CATAT PEMBAYARAN -->
    <div v-if="showPayModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl overflow-hidden animate-in fade-in zoom-in-95 duration-200 max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between p-4 border-b border-slate-100 shrink-0">
          <h3 class="font-bold text-lg text-slate-900 flex items-center gap-2">
            <span class="p-1.5 rounded-lg bg-emerald-100 text-emerald-600">
              <Wallet class="w-5 h-5" />
            </span>
            <span>Terima Pembayaran</span>
          </h3>
          <button @click="showPayModal = false" class="text-slate-400 hover:text-slate-600">
            ✕
          </button>
        </div>

        <div class="p-4 overflow-y-auto custom-scrollbar">
          <form @submit.prevent="submitPayment" class="space-y-4">
            
            <div class="p-3 bg-blue-50 border border-blue-100 rounded-xl mb-2">
              <div class="flex justify-between text-xs mb-1">
                <span class="font-bold text-blue-900">Total Tagihan</span>
                <span class="font-bold text-blue-900">{{ formatRupiah(invoice.total_amount) }}</span>
              </div>
              <div class="flex justify-between text-xs mb-1">
                <span class="font-bold text-blue-700">Sudah Terbayar</span>
                <span class="font-bold text-blue-700">{{ formatRupiah(invoice.paid_amount) }}</span>
              </div>
              <div class="flex justify-between text-sm pt-2 mt-2 border-t border-blue-200/50">
                <span class="font-black text-rose-600">Sisa Harus Dibayar</span>
                <span class="font-black text-rose-600">{{ formatRupiah(Number(invoice.total_amount) - Number(invoice.paid_amount)) }}</span>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Nominal Diterima (Rp) <span class="text-rose-500">*</span></label>
              <input v-model.number="payForm.amount" type="number" min="1" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm font-bold focus:border-emerald-500 focus:ring-emerald-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Terima <span class="text-rose-500">*</span></label>
                <input v-model="payForm.payment_date" type="date" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-emerald-500 focus:ring-emerald-500">
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Metode <span class="text-rose-500">*</span></label>
                <select v-model="payForm.payment_method" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                  <option value="Transfer Bank">Transfer Bank</option>
                  <option value="Cash / Tunai">Cash / Tunai</option>
                  <option value="Cek / Giro">Cek / Giro</option>
                </select>
              </div>
            </div>

            <!-- TERKONEKSI KAS & BANK -->
            <div class="p-3 bg-emerald-50/50 border border-emerald-100 rounded-xl">
              <label class="block text-xs font-black text-emerald-800 mb-1.5 uppercase tracking-wider">
                Masuk ke Rekening / Kas Mana? <span class="text-rose-500">*</span>
              </label>
              <p class="text-[10px] text-emerald-600 mb-2 font-medium">Pembayaran ini akan otomatis menambah saldo di Buku Kas.</p>
              <select v-model="payForm.cash_account_id" required class="w-full px-3 py-2 rounded-xl border border-emerald-200 bg-white text-sm font-semibold focus:border-emerald-500 focus:ring-emerald-500">
                <option value="" disabled>-- Pilih Rekening Penerima --</option>
                <option v-for="acc in cashAccounts" :key="acc.id" :value="acc.id">
                  {{ acc.name }} (Saldo saat ini: {{ formatRupiah(acc.current_balance) }})
                </option>
              </select>
            </div>

            <!-- MULTI UPLOAD BUKTI -->
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Upload Bukti Pembayaran / Transfer (Multi File)</label>
              <input
                type="file"
                multiple
                @change="handleProofFilesChange"
                accept=".jpg,.jpeg,.png,.pdf"
                class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer"
              />
              <span class="text-[10px] text-slate-400 block mt-1">Bisa pilih lebih dari 1 file sekaligus (Struk, Nota, Bukti Potong Pajak). Maks 5MB/file.</span>

              <div v-if="selectedProofFiles.length > 0" class="mt-2 space-y-1.5 p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-[10px] font-bold text-slate-500 block uppercase">File Terpilih ({{ selectedProofFiles.length }}):</span>
                <ul class="space-y-1">
                  <li v-for="(f, i) in selectedProofFiles" :key="i" class="text-[11px] font-medium text-slate-700 flex items-center justify-between gap-2 p-1.5 rounded-lg bg-white border border-slate-200 shadow-2xs">
                    <div class="flex items-center gap-1.5 truncate">
                      <Upload class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                      <span class="truncate font-semibold text-slate-800">{{ f.name }}</span>
                      <span class="text-[9px] text-slate-400 shrink-0">({{ Math.round(f.size / 1024) }} KB)</span>
                    </div>
                    <button type="button" @click="removeProofFile(i)" class="p-0.5 text-slate-400 hover:text-rose-600 rounded">✕</button>
                  </li>
                </ul>
              </div>
            </div>

          </form>
        </div>
        <div class="p-4 border-t border-slate-100 flex justify-end gap-2 shrink-0 bg-slate-50 rounded-b-2xl">
          <button type="button" @click="showPayModal = false" class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 font-bold hover:bg-slate-100 transition-colors">Batal</button>
          <button @click="submitPayment" :disabled="payForm.processing" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-md shadow-emerald-600/20 transition-all flex items-center gap-2">
            <CheckCircle2 class="w-4 h-4" />
            <span>Simpan Pembayaran</span>
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
