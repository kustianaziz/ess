<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { FileText, Plus, Edit2, Trash2, CheckCircle, XCircle } from 'lucide-vue-next';

const props = defineProps({
  expenseTypes: Array,
});

const showModal = ref(false);
const editingType = ref(null);

const form = useForm({
  name: '',
  is_active: true,
});

const openCreateModal = () => {
  editingType.value = null;
  form.reset();
  form.clearErrors();
  showModal.value = true;
};

const openEditModal = (type) => {
  editingType.value = type;
  form.clearErrors();
  form.name = type.name;
  form.is_active = Boolean(type.is_active);
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editingType.value = null;
  form.reset();
};

const submitForm = () => {
  if (editingType.value) {
    form.put(route('admin.expense-types.update', editingType.value.id), {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post(route('admin.expense-types.store'), {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteType = (type) => {
  if (confirm(`Hapus jenis pengeluaran ${type.name}?`)) {
    router.delete(route('admin.expense-types.destroy', type.id));
  }
};
</script>

<template>
  <Head title="Jenis Pengeluaran - Admin" />

  <AuthenticatedLayout>
    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <FileText class="w-7 h-7 text-emerald-600" />
            Jenis Pengeluaran (Reimbursement)
          </h1>
          <p class="text-xs text-slate-500 mt-1">
            Master kategori klaim pengeluaran reimbursement karyawan.
          </p>
        </div>

        <button
          @click="openCreateModal"
          class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-emerald-600/20 transition-all"
        >
          <Plus class="w-4 h-4" />
          <span>Tambah Jenis Pengeluaran</span>
        </button>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
              <th class="py-3.5 px-4">Nama Jenis Pengeluaran</th>
              <th class="py-3.5 px-4">Status</th>
              <th class="py-3.5 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="t in expenseTypes" :key="t.id" class="hover:bg-slate-50/50">
              <td class="py-3.5 px-4 font-bold text-slate-900">{{ t.name }}</td>
              <td class="py-3.5 px-4">
                <span v-if="t.is_active" class="inline-flex items-center gap-1 text-emerald-600 font-semibold">
                  <CheckCircle class="w-3.5 h-3.5" /> Aktif
                </span>
                <span v-else class="inline-flex items-center gap-1 text-slate-400 font-semibold">
                  <XCircle class="w-3.5 h-3.5" /> Nonaktif
                </span>
              </td>
              <td class="py-3.5 px-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="openEditModal(t)" class="p-1 text-slate-400 hover:text-emerald-600"><Edit2 class="w-4 h-4" /></button>
                  <button @click="deleteType(t)" class="p-1 text-slate-400 hover:text-rose-600"><Trash2 class="w-4 h-4" /></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Modal :show="showModal" @close="closeModal" maxWidth="md">
      <div class="p-6">
        <h3 class="text-lg font-bold text-slate-900 mb-4">{{ editingType ? 'Edit' : 'Tambah' }} Jenis Pengeluaran</h3>
        <form @submit.prevent="submitForm" class="space-y-4 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Nama Kategori</label>
            <input v-model="form.name" type="text" required placeholder="mis: Transportasi / Makan Minum" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs" />
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">Status Kategori</label>
            <select v-model="form.is_active" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
              <option :value="true">Aktif</option>
              <option :value="false">Nonaktif</option>
            </select>
          </div>
          <div class="pt-4 border-t border-slate-200 flex justify-end gap-2">
            <button type="button" @click="closeModal" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl">Batal</button>
            <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-emerald-600 text-white font-bold rounded-xl">Simpan</button>
          </div>
        </form>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>
