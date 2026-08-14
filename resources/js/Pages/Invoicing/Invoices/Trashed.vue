<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { FileText, ArrowLeft, Trash2, RotateCcw } from 'lucide-vue-next'

const props = defineProps({
  invoices: Array
})

const restoreInvoice = (id) => {
  if (confirm('Pulihkan invoice ini?')) {
    router.post(route('invoicing.invoices.restore', id))
  }
}

const forceDeleteInvoice = (id) => {
  if (confirm('Hapus permanen invoice ini? Data tidak akan bisa dikembalikan!')) {
    router.delete(route('invoicing.invoices.force-delete', id))
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}
</script>

<template>
  <Head title="Invoice Terhapus (Trash)" />

  <AuthenticatedLayout>
    <div class="max-w-5xl mx-auto space-y-6 py-6 sm:py-8 px-3 sm:px-6 lg:px-8">
      
      <!-- HEADER -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
          <Link :href="route('invoicing.invoices.index')" class="p-2 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
            <ArrowLeft class="w-5 h-5 text-slate-600" />
          </Link>
          <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 flex items-center gap-2">
              <span class="p-2 rounded-xl bg-rose-600 text-white shadow-md shadow-rose-600/20">
                <Trash2 class="w-5 h-5" />
              </span>
              <span>Invoice Terhapus</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
              Daftar invoice yang telah dihapus (soft delete).
            </p>
          </div>
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
                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Waktu Dihapus</th>
                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="invoices.length === 0">
                <td colspan="4" class="px-4 py-8 text-center text-slate-500 text-sm">Tong sampah kosong.</td>
              </tr>
              <tr v-for="invoice in invoices" :key="invoice.id" class="hover:bg-slate-50/50 transition-colors">
                <td class="px-4 py-3">
                  <div class="font-bold text-indigo-700 text-sm">{{ invoice.invoice_number }}</div>
                  <div class="text-xs text-slate-500">Tgl: {{ formatDate(invoice.invoice_date) }}</div>
                </td>
                <td class="px-4 py-3">
                  <div class="font-bold text-slate-900 text-sm">{{ invoice.customer?.name }}</div>
                  <div class="text-xs text-slate-500">Total: Rp {{ Number(invoice.total_amount).toLocaleString('id-ID') }}</div>
                </td>
                <td class="px-4 py-3">
                  <span class="text-xs text-rose-600 font-bold">{{ formatDate(invoice.deleted_at) }}</span>
                </td>
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      @click="restoreInvoice(invoice.id)"
                      class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-xs font-bold transition-colors"
                    >
                      <RotateCcw class="w-3.5 h-3.5" />
                      <span>Pulihkan</span>
                    </button>
                    <button
                      @click="forceDeleteInvoice(invoice.id)"
                      class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 text-xs font-bold transition-colors"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
                      <span>Hapus Permanen</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </AuthenticatedLayout>
</template>
