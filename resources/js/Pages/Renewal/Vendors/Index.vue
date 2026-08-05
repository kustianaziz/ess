<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Store, Plus, Edit, Trash2, X, ToggleLeft, ToggleRight } from 'lucide-vue-next'

const props = defineProps({ vendors: Array })

const showModal = ref(false)
const isEditing = ref(false)

const typeLabel = { domain_registrar: 'Registrar Domain', hosting_provider: 'Provider Hosting', both: 'Keduanya', other: 'Lainnya' }
const typeBadge = { domain_registrar: 'bg-blue-100 text-blue-700', hosting_provider: 'bg-emerald-100 text-emerald-700', both: 'bg-indigo-100 text-indigo-700', other: 'bg-slate-100 text-slate-600' }

const form = useForm({ id: null, name: '', type: 'hosting_provider', contact_info: '', is_active: true })

const openModal = (v = null) => {
  if (v) { isEditing.value = true; form.id = v.id; form.name = v.name; form.type = v.type; form.contact_info = v.contact_info || ''; form.is_active = v.is_active }
  else { isEditing.value = false; form.reset(); form.is_active = true; form.type = 'hosting_provider' }
  showModal.value = true
}

const submit = () => {
  const opts = { onSuccess: () => { showModal.value = false; form.reset() } }
  isEditing.value ? form.put(route('renewal.vendors.update', form.id), opts) : form.post(route('renewal.vendors.store'), opts)
}

const del = (id) => { if (confirm('Hapus vendor ini?')) useForm({}).delete(route('renewal.vendors.destroy', id)) }
</script>

<template>
  <Head title="Master Vendor" />
  <AuthenticatedLayout>
    <div class="max-w-5xl mx-auto space-y-6 py-6 sm:py-8 px-3 sm:px-6 lg:px-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-slate-900 flex items-center gap-2">
            <span class="p-2 rounded-xl bg-violet-600 text-white shadow-md shadow-violet-600/20"><Store class="w-5 h-5" /></span>
            <span>Master Vendor Hosting</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1">Kelola data penyedia domain & hosting (Niagahoster, Rumahweb, dll).</p>
        </div>
        <button @click="openModal()" class="px-4 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold shadow-lg transition-all flex items-center justify-center gap-2">
          <Plus class="w-4 h-4" /> Tambah Vendor
        </button>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <table class="w-full text-left border-collapse">
          <thead><tr class="bg-slate-50 border-b border-slate-200">
            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">#</th>
            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Nama Vendor</th>
            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Tipe</th>
            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Kontak</th>
            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Status</th>
            <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase">Aksi</th>
          </tr></thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="vendors.length === 0"><td colspan="6" class="px-4 py-8 text-center text-slate-500 text-sm">Belum ada vendor.</td></tr>
            <tr v-for="(v, i) in vendors" :key="v.id" class="hover:bg-slate-50/50 transition-colors">
              <td class="px-4 py-3 text-xs text-slate-400 font-bold">{{ i + 1 }}</td>
              <td class="px-4 py-3 font-bold text-slate-900 text-sm">{{ v.name }}</td>
              <td class="px-4 py-3"><span class="px-2.5 py-1 rounded-lg text-[11px] font-bold" :class="typeBadge[v.type]">{{ typeLabel[v.type] }}</span></td>
              <td class="px-4 py-3 text-sm text-slate-600">{{ v.contact_info || '-' }}</td>
              <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-md text-[10px] font-bold" :class="v.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">{{ v.is_active ? 'AKTIF' : 'NONAKTIF' }}</span></td>
              <td class="px-4 py-3"><div class="flex gap-2">
                <button @click="openModal(v)" class="p-1.5 rounded-lg text-blue-600 bg-blue-50 hover:bg-blue-100"><Edit class="w-4 h-4" /></button>
                <button @click="del(v.id)" class="p-1.5 rounded-lg text-rose-600 bg-rose-50 hover:bg-rose-100"><Trash2 class="w-4 h-4" /></button>
              </div></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white w-full max-w-md rounded-2xl shadow-xl overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b border-slate-100">
          <h3 class="font-bold text-lg text-slate-900">{{ isEditing ? 'Edit Vendor' : 'Tambah Vendor' }}</h3>
          <button @click="showModal = false"><X class="w-5 h-5 text-slate-400" /></button>
        </div>
        <form @submit.prevent="submit" class="p-4 space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Vendor <span class="text-rose-500">*</span></label>
            <input v-model="form.name" type="text" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-violet-500" placeholder="Niagahoster, Rumahweb, ...">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Tipe Vendor <span class="text-rose-500">*</span></label>
            <select v-model="form.type" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-violet-500">
              <option value="domain_registrar">Registrar Domain</option>
              <option value="hosting_provider">Provider Hosting</option>
              <option value="both">Keduanya (Domain & Hosting)</option>
              <option value="other">Lainnya</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Info Kontak</label>
            <input v-model="form.contact_info" type="text" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-violet-500" placeholder="Email / telepon CS vendor">
          </div>
          <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
            <span class="text-xs font-bold text-slate-700">Status Aktif</span>
            <button type="button" @click="form.is_active = !form.is_active">
              <ToggleRight v-if="form.is_active" class="w-9 h-9 text-violet-500" />
              <ToggleLeft v-else class="w-9 h-9 text-slate-400" />
            </button>
          </div>
          <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
            <button type="button" @click="showModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold hover:bg-slate-200">Batal</button>
            <button type="submit" :disabled="form.processing" class="px-4 py-2 rounded-xl bg-violet-600 text-white font-bold hover:bg-violet-700 shadow-md">{{ isEditing ? 'Simpan' : 'Tambah' }}</button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
