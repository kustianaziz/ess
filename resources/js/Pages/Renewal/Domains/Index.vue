<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Globe, Plus, Edit, Trash2, X, Search, AlertTriangle, Calendar } from 'lucide-vue-next'

const props = defineProps({ domains: Array, customers: Array, vendors: Array })

const search = ref('')
const filtered = computed(() => props.domains.filter(d =>
  !search.value || d.name.toLowerCase().includes(search.value.toLowerCase()) ||
  d.customer?.name.toLowerCase().includes(search.value.toLowerCase())
))

const showModal = ref(false)
const isEditing = ref(false)

const statusBadge = {
  active: 'bg-emerald-100 text-emerald-700',
  expiring_soon: 'bg-amber-100 text-amber-700',
  expired: 'bg-rose-100 text-rose-700',
  cancelled: 'bg-slate-100 text-slate-500'
}
const statusLabel = { active: 'Aktif', expiring_soon: 'Segera Expired', expired: 'Expired', cancelled: 'Dibatalkan' }

const form = useForm({
  id: null, customer_id: '', vendor_id: '', name: '', type: 'hosting',
  purchase_date: '', expired_date: '', price_customer: 0, cost_vendor: 0,
  auto_renew: false, status: 'active'
})

const openModal = (d = null) => {
  if (d) {
    isEditing.value = true; form.id = d.id; form.customer_id = d.customer_id; form.vendor_id = d.vendor_id
    form.name = d.name; form.type = d.type; form.purchase_date = d.purchase_date?.split('T')[0] || ''
    form.expired_date = d.expired_date?.split('T')[0] || ''; form.price_customer = d.price_customer
    form.cost_vendor = d.cost_vendor; form.auto_renew = d.auto_renew; form.status = d.status
  } else { isEditing.value = false; form.reset(); form.status = 'active'; form.type = 'hosting' }
  showModal.value = true
}

const submit = () => {
  const opts = { onSuccess: () => { showModal.value = false; form.reset() } }
  isEditing.value ? form.put(route('renewal.domains.update', form.id), opts) : form.post(route('renewal.domains.store'), opts)
}

const del = (id) => { if (confirm('Hapus domain ini?')) useForm({}).delete(route('renewal.domains.destroy', id)) }

const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'
const formatRp = (v) => Number(v || 0).toLocaleString('id-ID')

const daysLeft = (expired) => {
  if (!expired) return null
  return Math.ceil((new Date(expired) - new Date()) / (1000 * 60 * 60 * 24))
}
</script>

<template>
  <Head title="Domain & Hosting" />
  <AuthenticatedLayout>
    <div class="max-w-7xl mx-auto space-y-6 py-6 sm:py-8 px-3 sm:px-6 lg:px-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-slate-900 flex items-center gap-2">
            <span class="p-2 rounded-xl bg-cyan-600 text-white shadow-md shadow-cyan-600/20"><Globe class="w-5 h-5" /></span>
            <span>Domain & Hosting Klien</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1">Pantau semua aset domain dan hosting milik klien beserta tanggal kedaluwarsanya.</p>
        </div>
        <button @click="openModal()" class="px-4 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-bold shadow-lg transition-all flex items-center justify-center gap-2">
          <Plus class="w-4 h-4" /> Tambah Domain/Hosting
        </button>
      </div>

      <div class="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-sm flex gap-3">
        <div class="relative flex-1">
          <Search class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" />
          <input v-model="search" type="text" placeholder="Cari nama domain atau klien..." class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-cyan-500" />
        </div>
        <span class="self-center text-xs font-semibold text-slate-500">{{ filtered.length }} domain</span>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse min-w-[800px]">
            <thead><tr class="bg-slate-50 border-b border-slate-200">
              <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Domain / Hosting</th>
              <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Klien</th>
              <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Vendor</th>
              <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Expired</th>
              <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Harga Klien</th>
              <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Status</th>
              <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Aksi</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="filtered.length === 0"><td colspan="7" class="px-4 py-8 text-center text-slate-500 text-sm">Belum ada data domain/hosting.</td></tr>
              <tr v-for="d in filtered" :key="d.id" class="hover:bg-slate-50/50 transition-colors">
                <td class="px-4 py-3">
                  <div class="font-bold text-slate-900 text-sm">{{ d.name }}</div>
                  <div class="text-[11px] text-slate-500 uppercase font-semibold">{{ d.type }}</div>
                </td>
                <td class="px-4 py-3 text-sm font-semibold text-slate-700">{{ d.customer?.name || '-' }}</td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ d.vendor?.name || '-' }}</td>
                <td class="px-4 py-3">
                  <div class="text-sm font-bold text-slate-900">{{ formatDate(d.expired_date) }}</div>
                  <div v-if="daysLeft(d.expired_date) !== null" class="text-[11px] font-semibold mt-0.5" :class="daysLeft(d.expired_date) <= 30 ? 'text-rose-600' : daysLeft(d.expired_date) <= 60 ? 'text-amber-600' : 'text-slate-400'">
                    <span v-if="daysLeft(d.expired_date) < 0">Sudah expired</span>
                    <span v-else>{{ daysLeft(d.expired_date) }} hari lagi</span>
                  </div>
                </td>
                <td class="px-4 py-3 text-sm font-bold text-slate-800">Rp {{ formatRp(d.price_customer) }}</td>
                <td class="px-4 py-3">
                  <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold border" :class="statusBadge[d.status]">{{ statusLabel[d.status] }}</span>
                </td>
                <td class="px-4 py-3"><div class="flex gap-2">
                  <button @click="openModal(d)" class="p-1.5 rounded-lg text-blue-600 bg-blue-50 hover:bg-blue-100"><Edit class="w-4 h-4" /></button>
                  <button @click="del(d.id)" class="p-1.5 rounded-lg text-rose-600 bg-rose-50 hover:bg-rose-100"><Trash2 class="w-4 h-4" /></button>
                </div></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white w-full max-w-xl rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-4 border-b border-slate-100 sticky top-0 bg-white z-10">
          <h3 class="font-bold text-lg text-slate-900">{{ isEditing ? 'Edit Domain/Hosting' : 'Tambah Domain/Hosting' }}</h3>
          <button @click="showModal = false"><X class="w-5 h-5 text-slate-400" /></button>
        </div>
        <form @submit.prevent="submit" class="p-4 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="block text-xs font-bold text-slate-700 mb-1">Nama Domain / Hosting <span class="text-rose-500">*</span></label>
              <input v-model="form.name" type="text" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-cyan-500" placeholder="contoh.com atau Hosting paket A">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Klien <span class="text-rose-500">*</span></label>
              <select v-model="form.customer_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-cyan-500">
                <option value="">-- Pilih Klien --</option>
                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Vendor <span class="text-rose-500">*</span></label>
              <select v-model="form.vendor_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-cyan-500">
                <option value="">-- Pilih Vendor --</option>
                <option v-for="v in vendors" :key="v.id" :value="v.id">{{ v.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Tipe <span class="text-rose-500">*</span></label>
              <select v-model="form.type" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-cyan-500">
                <option value="domain">Domain</option>
                <option value="hosting">Hosting</option>
                <option value="vps">VPS</option>
                <option value="email">Email Hosting</option>
                <option value="other">Lainnya</option>
              </select>
            </div>
            <div v-if="isEditing">
              <label class="block text-xs font-bold text-slate-700 mb-1">Status</label>
              <select v-model="form.status" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-cyan-500">
                <option value="active">Aktif</option>
                <option value="expiring_soon">Segera Expired</option>
                <option value="expired">Expired</option>
                <option value="cancelled">Dibatalkan</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Tgl Pembelian <span class="text-rose-500">*</span></label>
              <input v-model="form.purchase_date" type="date" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-cyan-500">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Tgl Expired <span class="text-rose-500">*</span></label>
              <input v-model="form.expired_date" type="date" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-cyan-500">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Harga ke Klien (Rp) <span class="text-rose-500">*</span></label>
              <input v-model.number="form.price_customer" type="number" min="0" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-cyan-500">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Biaya ke Vendor (Rp) <span class="text-rose-500">*</span></label>
              <input v-model.number="form.cost_vendor" type="number" min="0" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-cyan-500">
            </div>
          </div>
          <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
            <button type="button" @click="showModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold hover:bg-slate-200">Batal</button>
            <button type="submit" :disabled="form.processing" class="px-4 py-2 rounded-xl bg-cyan-600 text-white font-bold hover:bg-cyan-700 shadow-md">{{ isEditing ? 'Simpan' : 'Tambah' }}</button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
