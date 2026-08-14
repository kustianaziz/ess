<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { FileText, Plus, Search, Eye, FileSpreadsheet, Download, Trash2 } from 'lucide-vue-next'

const props = defineProps({
  invoices: Array,
  customers: Array,
  filters: Object,
  upcoming_renewals: Array,
})

const filterForm = ref({
  customer_id: props.filters?.customer_id || '',
  date_start: props.filters?.date_start || '',
  date_end: props.filters?.date_end || '',
  due_start: props.filters?.due_start || '',
  due_end: props.filters?.due_end || '',
})

const showRenewalsModal = ref(false)

const applyFilter = () => {
  router.get(route('invoicing.invoices.index'), filterForm.value, { preserveState: true, preserveScroll: true })
}

const clearFilter = () => {
  filterForm.value = { customer_id: '', date_start: '', date_end: '', due_start: '', due_end: '' }
  router.get(route('invoicing.invoices.index'))
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

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const generateRenewal = (invoiceId) => {
  if (confirm('Terbitkan invoice baru untuk periode selanjutnya (Draft)?')) {
    router.post(route('invoicing.invoices.duplicate', invoiceId))
  }
}

const formatRupiah = (angka) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka || 0)
}
</script>

<template>
  <Head title="Invoice & Tagihan" />

  <AuthenticatedLayout>
    <div class="max-w-7xl mx-auto space-y-6 py-6 sm:py-8 px-3 sm:px-6 lg:px-8">
      <!-- HEADER -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-slate-900 flex items-center gap-2">
            <span class="p-2 rounded-xl bg-indigo-600 text-white shadow-md shadow-indigo-600/20">
              <FileText class="w-5 h-5" />
            </span>
            <span>Invoice & Tagihan</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Kelola tagihan pelanggan (Customer) dan pantau status pembayarannya.
          </p>
        </div>
        <div class="flex gap-2">
          <button
            v-if="upcoming_renewals && upcoming_renewals.length > 0"
            @click="showRenewalsModal = true"
            class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-amber-100 hover:bg-amber-200 text-amber-800 text-xs font-bold shadow-sm transition-all flex items-center justify-center gap-2 border border-amber-300 relative"
          >
            <span class="absolute -top-1 -right-1 flex h-3 w-3">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
            </span>
            <span>{{ upcoming_renewals.length }} Butuh Renewal</span>
          </button>
          <Link
            :href="route('invoicing.invoices.trashed')"
            class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold shadow-sm transition-all flex items-center justify-center gap-2 border border-slate-200"
          >
            <Trash2 class="w-4 h-4" />
            <span>Tong Sampah</span>
          </Link>
          <Link
            :href="route('invoicing.invoices.create')"
            class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-lg shadow-indigo-600/20 transition-all flex items-center justify-center gap-2"
          >
            <Plus class="w-4 h-4" />
            <span>Buat Invoice Baru</span>
          </Link>
        </div>
      </div>

      <!-- FILTERS -->
      <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row gap-4 items-end">
        <div class="flex-1 w-full">
          <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Customer</label>
          <select v-model="filterForm.customer_id" class="w-full border-slate-200 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">Semua Customer</option>
            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div class="flex-1 w-full flex gap-2">
          <div class="w-1/2">
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Tgl Invoice (Mulai)</label>
            <input type="date" v-model="filterForm.date_start" class="w-full border-slate-200 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div class="w-1/2">
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Tgl Invoice (Akhir)</label>
            <input type="date" v-model="filterForm.date_end" class="w-full border-slate-200 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500">
          </div>
        </div>
        <div class="flex-1 w-full flex gap-2">
          <div class="w-1/2">
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Jatuh Tempo (Mulai)</label>
            <input type="date" v-model="filterForm.due_start" class="w-full border-slate-200 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500">
          </div>
          <div class="w-1/2">
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Jatuh Tempo (Akhir)</label>
            <input type="date" v-model="filterForm.due_end" class="w-full border-slate-200 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500">
          </div>
        </div>
        <div class="flex gap-2">
          <button @click="clearFilter" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">Reset</button>
          <button @click="applyFilter" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-colors flex items-center gap-2">
            <Search class="w-4 h-4" /> Filter
          </button>
        </div>
      </div>

      <!-- TABLE -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto custom-scrollbar">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200">
                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">No. Invoice & Tanggal</th>
                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Customer</th>
                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Total Tagihan</th>
                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="invoices.length === 0">
                <td colspan="5" class="px-4 py-8 text-center text-slate-500 text-sm">Belum ada invoice yang dibuat.</td>
              </tr>
              <tr v-for="invoice in invoices" :key="invoice.id" @click="router.visit(route('invoicing.invoices.show', invoice.id))" class="hover:bg-slate-50/50 transition-colors cursor-pointer">
                <td class="px-4 py-3">
                  <div class="font-bold text-indigo-700 text-sm">{{ invoice.invoice_number }}</div>
                  <div class="text-xs text-slate-500">Tgl: {{ formatDate(invoice.invoice_date) }} | JT: <span :class="{'text-rose-500 font-bold': invoice.status === 'overdue'}">{{ formatDate(invoice.due_date) }}</span></div>
                </td>
                <td class="px-4 py-3">
                  <div class="font-bold text-slate-900 text-sm">{{ invoice.customer?.name }}</div>
                  <div class="text-xs text-slate-500">{{ invoice.source_type === 'renewal' ? 'Renewal Webpraktis' : 'General' }}</div>
                </td>
                <td class="px-4 py-3">
                  <div class="font-bold text-slate-900 text-sm">Rp {{ Number(invoice.total_amount).toLocaleString('id-ID') }}</div>
                  <div class="text-[10px] text-slate-500 mt-0.5">
                    Terbayar: <span class="font-bold text-emerald-600">Rp {{ Number(invoice.paid_amount).toLocaleString('id-ID') }}</span>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border uppercase tracking-wider" :class="getStatusBadge(invoice.status)">
                    {{ getStatusLabel(invoice.status) }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <a
                      @click.stop
                      :href="route('invoicing.invoices.pdf', invoice.id)"
                      target="_blank"
                      class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 text-xs font-bold transition-colors border border-slate-200"
                    >
                      <Download class="w-3.5 h-3.5" />
                      <span>PDF</span>
                    </a>
                    <Link
                      @click.stop
                      :href="route('invoicing.invoices.show', invoice.id)"
                      class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 text-xs font-bold transition-colors"
                    >
                      <Eye class="w-3.5 h-3.5" />
                      <span>Detail</span>
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL RENEWAL FULL PAGE -->
    <div v-if="showRenewalsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
      <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
          <div>
            <h2 class="text-xl font-bold text-slate-800">Daftar Klien Butuh Renewal</h2>
            <p class="text-xs text-slate-500 mt-1">Tagihan berikut akan jatuh tempo dalam kurun waktu 30 hari ke depan.</p>
          </div>
          <button @click="showRenewalsModal = false" class="p-2 text-slate-400 hover:bg-slate-200 hover:text-slate-600 rounded-xl transition-colors">
            X
          </button>
        </div>
        <div class="p-6 overflow-y-auto custom-scrollbar flex-1 bg-slate-50/50">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="inv in upcoming_renewals" :key="inv.id" class="p-4 bg-white border border-slate-200 rounded-xl flex justify-between items-center shadow-sm hover:shadow-md transition-shadow">
              <div>
                <p class="font-bold text-slate-900 text-base">{{ inv.customer?.name }}</p>
                <p class="text-xs text-slate-500 mt-1">Inv Terakhir: <span class="font-semibold text-slate-700">{{ inv.invoice_number }}</span></p>
                
                <div class="mt-2 space-y-0.5 bg-slate-50 p-2 rounded-lg border border-slate-100">
                  <div class="flex justify-between text-[11px] text-slate-600">
                    <span>Subtotal:</span>
                    <span class="font-medium">{{ formatRupiah(inv.subtotal) }}</span>
                  </div>
                  <div v-if="inv.tax_amount > 0" class="flex justify-between text-[11px] text-slate-600">
                    <span>PPN:</span>
                    <span class="font-medium">{{ formatRupiah(inv.tax_amount) }}</span>
                  </div>
                  <div class="flex justify-between text-xs text-indigo-700 font-bold pt-1 mt-1 border-t border-slate-200">
                    <span>Total Tagihan:</span>
                    <span>{{ formatRupiah(inv.total_amount) }}</span>
                  </div>
                </div>

                <p class="text-[11px] text-slate-500 mt-2">Berakhir: <span class="font-bold text-rose-500">{{ formatDate(inv.due_date) }}</span></p>
              </div>
              <button @click="generateRenewal(inv.id)" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-colors shadow-lg shadow-indigo-600/20 whitespace-nowrap">
                Terbitkan
              </button>
            </div>
          </div>
        </div>
        <div class="p-4 border-t border-slate-100 bg-white text-right">
          <button @click="showRenewalsModal = false" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
            Tutup
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
