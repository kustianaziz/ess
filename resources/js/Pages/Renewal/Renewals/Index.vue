<script setup>
import { ref } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { RefreshCw, Plus, Eye, X, Globe } from 'lucide-vue-next'

const props = defineProps({ renewals: Array, domains: Array })

const showModal = ref(false)
const form = useForm({ domain_id: '', period_year: 1, notes: '' })

const statusBadge = {
  pending: 'bg-amber-100 text-amber-700 border-amber-200',
  invoiced_customer: 'bg-blue-100 text-blue-700 border-blue-200',
  paid_customer: 'bg-sky-100 text-sky-700 border-sky-200',
  paid_vendor: 'bg-violet-100 text-violet-700 border-violet-200',
  completed: 'bg-emerald-100 text-emerald-700 border-emerald-200',
  cancelled: 'bg-slate-100 text-slate-500 border-slate-200',
}
const statusLabel = {
  pending: 'Pending', invoiced_customer: 'Invoice Dikirim', paid_customer: 'Klien Sudah Bayar',
  paid_vendor: 'Vendor Sudah Dibayar', completed: 'Selesai', cancelled: 'Dibatalkan'
}

const submit = () => {
  form.post(route('renewal.renewals.store'), {
    onSuccess: () => { showModal.value = false; form.reset(); form.period_year = 1 }
  })
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'
</script>

<template>
  <Head title="Renewal Webpraktis" />
  <AuthenticatedLayout>
    <div class="max-w-7xl mx-auto space-y-6 py-6 sm:py-8 px-3 sm:px-6 lg:px-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-slate-900 flex items-center gap-2">
            <span class="p-2 rounded-xl bg-emerald-600 text-white shadow-md shadow-emerald-600/20"><RefreshCw class="w-5 h-5" /></span>
            <span>Renewal Webpraktis</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1">Kelola proses perpanjangan domain & hosting klien — dari invoice hingga pembayaran vendor.</p>
        </div>
        <button @click="showModal = true" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-lg transition-all flex items-center justify-center gap-2">
          <Plus class="w-4 h-4" /> Buat Renewal Request
        </button>
      </div>

      <!-- TABLE -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse min-w-[700px]">
            <thead><tr class="bg-slate-50 border-b border-slate-200">
              <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">No. Renewal</th>
              <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Domain / Hosting</th>
              <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Klien</th>
              <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Periode</th>
              <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Invoice</th>
              <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Status</th>
              <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Aksi</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="renewals.length === 0"><td colspan="7" class="px-4 py-8 text-center text-slate-500 text-sm">Belum ada renewal request.</td></tr>
              <tr v-for="r in renewals" :key="r.id" class="hover:bg-slate-50/50 transition-colors">
                <td class="px-4 py-3 font-bold text-slate-900 text-sm font-mono">{{ r.renewal_number }}</td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-1.5"><Globe class="w-3.5 h-3.5 text-cyan-500" /><span class="font-semibold text-sm text-slate-800">{{ r.domain?.name }}</span></div>
                  <div class="text-[11px] text-slate-400 uppercase mt-0.5 pl-5">{{ r.domain?.type }}</div>
                </td>
                <td class="px-4 py-3 text-sm font-semibold text-slate-700">{{ r.domain?.customer?.name || '-' }}</td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ r.period_year }} Tahun</td>
                <td class="px-4 py-3">
                  <span v-if="r.invoice" class="font-mono text-xs font-bold text-indigo-700 bg-indigo-50 px-2 py-1 rounded-lg">{{ r.invoice.invoice_number }}</span>
                  <span v-else class="text-xs text-slate-400 italic">Belum ada invoice</span>
                </td>
                <td class="px-4 py-3">
                  <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold border" :class="statusBadge[r.status] || 'bg-slate-100 text-slate-500'">
                    {{ statusLabel[r.status] || r.status }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <Link :href="route('renewal.renewals.show', r.id)" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                    <Eye class="w-3.5 h-3.5" /> Detail
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL Buat Renewal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white w-full max-w-md rounded-2xl shadow-xl overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b border-slate-100">
          <h3 class="font-bold text-lg text-slate-900">Buat Renewal Request</h3>
          <button @click="showModal = false"><X class="w-5 h-5 text-slate-400" /></button>
        </div>
        <form @submit.prevent="submit" class="p-4 space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Domain / Hosting <span class="text-rose-500">*</span></label>
            <select v-model="form.domain_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-emerald-500">
              <option value="">-- Pilih Domain/Hosting --</option>
              <option v-for="d in domains" :key="d.id" :value="d.id">
                {{ d.name }} ({{ d.customer?.name || '-' }})
              </option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Periode Perpanjangan <span class="text-rose-500">*</span></label>
            <div class="flex items-center gap-2">
              <input v-model.number="form.period_year" type="number" min="1" max="10" required class="w-24 px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm font-bold text-center focus:border-emerald-500">
              <span class="text-sm text-slate-600">Tahun</span>
            </div>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan</label>
            <textarea v-model="form.notes" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-emerald-500" placeholder="Catatan tambahan..."></textarea>
          </div>
          <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
            <button type="button" @click="showModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold hover:bg-slate-200">Batal</button>
            <button type="submit" :disabled="form.processing" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-bold hover:bg-emerald-700 shadow-md">Buat Request</button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
