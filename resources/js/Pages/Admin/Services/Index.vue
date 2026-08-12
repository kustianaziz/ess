<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Layers, Plus, Edit, Trash2, X, ToggleLeft, ToggleRight } from 'lucide-vue-next'

const props = defineProps({
  services: Array
})

const showModal = ref(false)
const isEditing = ref(false)

const form = useForm({
  id: null,
  name: '',
  description: '',
  is_active: true,
  logo: null,
  signature_image: null,
  stamp_image: null,
  signature_name: '',
  bank_credentials: '',
  address: '',
  invoice_notes: ''
})

const openModal = (service = null) => {
  if (service) {
    isEditing.value = true
    form.id = service.id
    form.name = service.name
    form.description = service.description || ''
    form.is_active = service.is_active
    form.logo = null // Don't bind existing file
    form.signature_image = null
    form.stamp_image = null
    form.signature_name = service.signature_name || ''
    form.bank_credentials = service.bank_credentials || ''
    form.address = service.address || ''
    form.invoice_notes = service.invoice_notes || ''
  } else {
    isEditing.value = false
    form.reset()
    form.is_active = true
  }
  showModal.value = true
}

const submit = () => {
  if (isEditing.value) {
    // Inertia requires POST with _method=PUT to upload files correctly
    form.transform((data) => ({
      ...data,
      _method: 'put',
    })).post(route('admin.services.update', form.id), {
      onSuccess: () => { showModal.value = false; form.reset() }
    })
  } else {
    form.post(route('admin.services.store'), {
      onSuccess: () => { showModal.value = false; form.reset() }
    })
  }
}

const handleFile = (e, field) => {
  if (e.target.files.length) {
    form[field] = e.target.files[0]
  }
}

const deleteService = (id) => {
  if (confirm('Apakah Anda yakin ingin menghapus layanan ini?')) {
    useForm({}).delete(route('admin.services.destroy', id))
  }
}
</script>

<template>
  <Head title="Master Layanan" />

  <AuthenticatedLayout>
    <div class="max-w-4xl mx-auto space-y-6 py-6 sm:py-8 px-3 sm:px-6 lg:px-8">

      <!-- HEADER -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-slate-900 flex items-center gap-2">
            <span class="p-2 rounded-xl bg-teal-600 text-white shadow-md shadow-teal-600/20">
              <Layers class="w-5 h-5" />
            </span>
            <span>Master Layanan</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Kelola daftar layanan yang dapat dihubungkan ke pelanggan (Customer) dan Invoice.
          </p>
        </div>
        <button
          @click="openModal()"
          class="px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-lg shadow-teal-600/20 transition-all flex items-center justify-center gap-2"
        >
          <Plus class="w-4 h-4" />
          <span>Tambah Layanan</span>
        </button>
      </div>

      <!-- TABLE -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto custom-scrollbar">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200">
                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider w-10">#</th>
                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Nama Layanan</th>
                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Deskripsi</th>
                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                <th class="px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="services.length === 0">
                <td colspan="5" class="px-4 py-8 text-center text-slate-500 text-sm">
                  Belum ada layanan yang ditambahkan.
                </td>
              </tr>
              <tr v-for="(service, index) in services" :key="service.id" class="hover:bg-slate-50/50 transition-colors">
                <td class="px-4 py-3 text-xs text-slate-400 font-bold">{{ index + 1 }}</td>
                <td class="px-4 py-3">
                  <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full flex-shrink-0" :class="service.is_active ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                    {{ service.name }}
                  </div>
                </td>
                <td class="px-4 py-3 text-sm text-slate-500 max-w-xs">
                  {{ service.description || '-' }}
                </td>
                <td class="px-4 py-3">
                  <span
                    class="px-2.5 py-1 rounded-lg text-[11px] font-bold border"
                    :class="service.is_active
                      ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
                      : 'bg-slate-100 text-slate-500 border-slate-200'"
                  >
                    {{ service.is_active ? 'AKTIF' : 'TIDAK AKTIF' }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <button
                      @click="openModal(service)"
                      class="p-1.5 rounded-lg text-blue-600 bg-blue-50 hover:bg-blue-100 transition-colors"
                      title="Edit"
                    >
                      <Edit class="w-4 h-4" />
                    </button>
                    <button
                      @click="deleteService(service.id)"
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
      <div class="bg-white w-full max-w-md rounded-2xl shadow-xl overflow-hidden flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between p-4 border-b border-slate-100 shrink-0">
          <h3 class="font-bold text-lg text-slate-900 flex items-center gap-2">
            <span class="p-1.5 rounded-lg bg-teal-100 text-teal-600"><Layers class="w-4 h-4" /></span>
            {{ isEditing ? 'Edit Layanan' : 'Tambah Layanan Baru' }}
          </h3>
          <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submit" class="p-4 space-y-4 overflow-y-auto custom-scrollbar flex-1">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Layanan <span class="text-rose-500">*</span></label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-teal-500 focus:ring-teal-500"
              placeholder="Contoh: Pembuatan Website, Hosting & Domain..."
            >
            <div v-if="form.errors.name" class="text-rose-500 text-xs mt-1">{{ form.errors.name }}</div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi (Opsional)</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-teal-500 focus:ring-teal-500"
              placeholder="Penjelasan singkat tentang layanan ini..."
            ></textarea>
          </div>

          <div class="p-3 border border-slate-200 rounded-xl bg-slate-50 space-y-3">
            <h4 class="font-bold text-xs text-slate-800 border-b border-slate-200 pb-2">Pengaturan Invoice & Penagihan (Dinamis)</h4>
            
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Logo di Invoice (Opsional)</label>
              <input type="file" @change="e => handleFile(e, 'logo')" accept="image/*" class="w-full text-xs" />
              <div v-if="form.errors.logo" class="text-rose-500 text-xs mt-1">{{ form.errors.logo }}</div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Upload Gambar Tanda Tangan (Opsional)</label>
              <input type="file" @change="e => handleFile(e, 'signature_image')" accept="image/*" class="w-full text-xs" />
              <div v-if="form.errors.signature_image" class="text-rose-500 text-xs mt-1">{{ form.errors.signature_image }}</div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Upload Cap Perusahaan (Opsional)</label>
              <input type="file" @change="e => handleFile(e, 'stamp_image')" accept="image/*" class="w-full text-xs" />
              <div v-if="form.errors.stamp_image" class="text-rose-500 text-xs mt-1">{{ form.errors.stamp_image }}</div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Nama Penandatangan</label>
              <input
                v-model="form.signature_name"
                type="text"
                class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-sm focus:border-teal-500 focus:ring-teal-500"
                placeholder="Contoh: Budi Santoso, Direktur"
              >
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Informasi Transfer / Credential Bank</label>
              <textarea
                v-model="form.bank_credentials"
                rows="3"
                class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-sm focus:border-teal-500 focus:ring-teal-500"
                placeholder="Contoh: BCA 1234567890 a.n PT Contoh Sukses"
              ></textarea>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Perusahaan (Opsional)</label>
              <textarea
                v-model="form.address"
                rows="3"
                class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-sm focus:border-teal-500 focus:ring-teal-500"
                placeholder="Misal: Jl. Raya No. 123, Bandung..."
              ></textarea>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan Invoice (Opsional)</label>
              <textarea
                v-model="form.invoice_notes"
                rows="3"
                class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-sm focus:border-teal-500 focus:ring-teal-500"
                placeholder="Catatan pengiriman bukti, dsb..."
              ></textarea>
            </div>
          </div>

          <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
            <div>
              <p class="text-sm font-bold text-slate-800">Status Aktif</p>
              <p class="text-xs text-slate-500">Layanan non-aktif tidak akan muncul di pilihan Customer.</p>
            </div>
            <button
              type="button"
              @click="form.is_active = !form.is_active"
              class="transition-colors"
            >
              <ToggleRight v-if="form.is_active" class="w-9 h-9 text-teal-500" />
              <ToggleLeft v-else class="w-9 h-9 text-slate-400" />
            </button>
          </div>

          <div class="pt-4 border-t border-slate-100 flex justify-end gap-2 shrink-0">
            <button
              type="button"
              @click="showModal = false"
              class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold hover:bg-slate-200"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-4 py-2 rounded-xl bg-teal-600 text-white font-bold hover:bg-teal-700 shadow-md"
            >
              {{ isEditing ? 'Simpan Perubahan' : 'Tambah Layanan' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </AuthenticatedLayout>
</template>
