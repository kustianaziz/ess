<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Users, Search, Plus, Edit, Trash2, X, Filter } from 'lucide-vue-next'

const props = defineProps({
  customers: Array,
  services: Array
})

// Search & Filter
const searchQuery = ref('')
const filterService = ref('')

const filteredCustomers = computed(() => {
  return props.customers.filter(c => {
    const matchSearch = !searchQuery.value ||
      c.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (c.email && c.email.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
      (c.pic_name && c.pic_name.toLowerCase().includes(searchQuery.value.toLowerCase()))
    const matchService = !filterService.value ||
      String(c.service_id) === String(filterService.value)
    return matchSearch && matchService
  })
})

// Modal state
const showModal = ref(false)
const isEditing = ref(false)

const form = useForm({
  id: null,
  name: '',
  pic_name: '',
  email: '',
  phone: '',
  address: '',
  npwp: '',
  notes: '',
  service_id: ''
})

const openModal = (customer = null) => {
  if (customer) {
    isEditing.value = true
    form.id = customer.id
    form.name = customer.name
    form.pic_name = customer.pic_name || ''
    form.email = customer.email || ''
    form.phone = customer.phone || ''
    form.address = customer.address || ''
    form.npwp = customer.npwp || ''
    form.notes = customer.notes || ''
    form.service_id = customer.service_id || ''
  } else {
    isEditing.value = false
    form.reset()
  }
  showModal.value = true
}

const submit = () => {
  if (isEditing.value) {
    form.put(route('invoicing.customers.update', form.id), {
      onSuccess: () => {
        showModal.value = false
        form.reset()
      }
    })
  } else {
    form.post(route('invoicing.customers.store'), {
      onSuccess: () => {
        showModal.value = false
        form.reset()
      }
    })
  }
}

const deleteCustomer = (id) => {
  if (confirm('Apakah Anda yakin ingin menghapus pelanggan ini?')) {
    useForm({}).delete(route('invoicing.customers.destroy', id))
  }
}
</script>

<template>
  <Head title="Kelola Customer" />

  <AuthenticatedLayout>
    <div class="max-w-7xl mx-auto space-y-6 py-6 sm:py-8 px-3 sm:px-6 lg:px-8">
      <!-- HEADER -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-slate-900 flex items-center gap-2">
            <span class="p-2 rounded-xl bg-indigo-600 text-white shadow-md shadow-indigo-600/20">
              <Users class="w-5 h-5" />
            </span>
            <span>Customer (Klien)</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Kelola data pelanggan untuk keperluan pembuatan Invoice dan Tagihan.
          </p>
        </div>
        <button
          @click="openModal()"
          class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-lg shadow-indigo-600/20 transition-all flex items-center justify-center gap-2"
        >
          <Plus class="w-4 h-4" />
          <span>Tambah Customer</span>
        </button>
      </div>

      <!-- FILTER & SEARCH -->
      <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row gap-3 items-center">
        <div class="relative w-full sm:w-72">
          <Search class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari nama, email, atau PIC..."
            class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500"
          />
        </div>

        <!-- FILTER LAYANAN -->
        <div class="relative w-full sm:w-56">
          <Filter class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" />
          <select
            v-model="filterService"
            class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500 appearance-none"
          >
            <option value="">Semua Layanan</option>
            <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </div>

        <!-- BADGE HASIL -->
        <div class="ml-auto flex items-center gap-2">
          <span class="text-xs font-semibold text-slate-500">
            Menampilkan <span class="font-black text-indigo-700">{{ filteredCustomers.length }}</span> dari {{ customers.length }} klien
          </span>
          <button
            v-if="searchQuery || filterService"
            @click="searchQuery = ''; filterService = ''"
            class="px-2.5 py-1 rounded-lg bg-rose-50 text-rose-600 text-[11px] font-bold hover:bg-rose-100 transition-colors flex items-center gap-1"
          >
            <X class="w-3 h-3" /> Reset Filter
          </button>
        </div>
      </div>

      <!-- TABLE -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto custom-scrollbar">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200">
                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Nama Perusahaan / Klien</th>
                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Kontak (PIC)</th>
                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Email & Telepon</th>
                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Layanan</th>
                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="filteredCustomers.length === 0">
                <td colspan="5" class="px-4 py-10 text-center">
                  <div class="text-slate-400 text-sm">
                    <span class="text-2xl block mb-2">🔍</span>
                    <span v-if="searchQuery || filterService">Tidak ada customer yang sesuai filter.</span>
                    <span v-else>Belum ada data customer.</span>
                  </div>
                </td>
              </tr>
              <tr v-for="customer in filteredCustomers" :key="customer.id" class="hover:bg-slate-50/50 transition-colors">
                <td class="px-4 py-3">
                  <div class="font-bold text-slate-900 text-sm">{{ customer.name }}</div>
                  <div class="text-xs text-slate-500">{{ customer.npwp ? 'NPWP: ' + customer.npwp : 'Tanpa NPWP' }}</div>
                </td>
                <td class="px-4 py-3 text-sm font-medium text-slate-700">
                  {{ customer.pic_name || '-' }}
                </td>
                <td class="px-4 py-3">
                  <div class="text-sm font-medium text-slate-700">{{ customer.email || '-' }}</div>
                  <div class="text-xs text-slate-500">{{ customer.phone || '-' }}</div>
                </td>
                <td class="px-4 py-3">
                  <span class="px-2.5 py-1 rounded-lg text-xs font-bold" :class="customer.service ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-500'">
                    {{ customer.service ? customer.service.name : 'Belum Dipilih' }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <button
                      @click="openModal(customer)"
                      class="p-1.5 rounded-lg text-blue-600 bg-blue-50 hover:bg-blue-100 transition-colors"
                      title="Edit"
                    >
                      <Edit class="w-4 h-4" />
                    </button>
                    <button
                      @click="deleteCustomer(customer.id)"
                      class="p-1.5 rounded-lg text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors"
                      title="Hapus"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL FORM -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between p-4 border-b border-slate-100">
          <h3 class="font-bold text-lg text-slate-900">{{ isEditing ? 'Edit Customer' : 'Tambah Customer Baru' }}</h3>
          <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submit" class="p-4 space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Nama Perusahaan / Klien <span class="text-rose-500">*</span></label>
              <input v-model="form.name" type="text" required class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="PT. ABC Maju Bersama">
              <div v-if="form.errors.name" class="text-rose-500 text-xs mt-1">{{ form.errors.name }}</div>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Layanan Utama</label>
              <select v-model="form.service_id" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">-- Tanpa Layanan Khusus --</option>
                <option v-for="service in services" :key="service.id" :value="service.id">
                  {{ service.name }}
                </option>
              </select>
            </div>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Nama Kontak (PIC)</label>
              <input v-model="form.pic_name" type="text" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Budi Santoso">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Telepon</label>
              <input v-model="form.phone" type="text" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="08123456789">
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Email</label>
              <input v-model="form.email" type="email" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="budi@abc.com">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">NPWP</label>
              <input v-model="form.npwp" type="text" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="01.234.567.8-901.000">
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Lengkap</label>
            <textarea v-model="form.address" rows="3" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Jl. Sudirman No.123..."></textarea>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan</label>
            <textarea v-model="form.notes" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Catatan internal tambahan..."></textarea>
          </div>

          <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
            <button type="button" @click="showModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold hover:bg-slate-200">Batal</button>
            <button type="submit" :disabled="form.processing" class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 shadow-md">
              {{ isEditing ? 'Simpan Perubahan' : 'Tambah Customer' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
