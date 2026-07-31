<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Building2, Plus, Edit2, Trash2, Users } from 'lucide-vue-next';

const props = defineProps({
  divisions: Array,
});

const showModal = ref(false);
const editingDivision = ref(null);

const form = useForm({
  name: '',
  code: '',
});

const openCreateModal = () => {
  editingDivision.value = null;
  form.reset();
  form.clearErrors();
  showModal.value = true;
};

const openEditModal = (div) => {
  editingDivision.value = div;
  form.clearErrors();
  form.name = div.name;
  form.code = div.code || '';
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editingDivision.value = null;
  form.reset();
};

const submitForm = () => {
  if (editingDivision.value) {
    form.put(route('admin.divisions.update', editingDivision.value.id), {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post(route('admin.divisions.store'), {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteDivision = (div) => {
  if (confirm(`Apakah Anda yakin ingin menghapus divisi ${div.name}?`)) {
    router.delete(route('admin.divisions.destroy', div.id));
  }
};
</script>

<template>
  <Head title="Kelola Divisi - Admin" />

  <AuthenticatedLayout>
    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <Building2 class="w-7 h-7 text-indigo-600" />
            Kelola Divisi Perusahaan
          </h1>
          <p class="text-xs text-slate-500 mt-1">
            Master data divisi/departemen di lingkungan EDU Employee Self Service.
          </p>
        </div>

        <button
          @click="openCreateModal"
          class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/20 transition-all"
        >
          <Plus class="w-4 h-4" />
          <span>Tambah Divisi</span>
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="d in divisions"
          :key="d.id"
          class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all flex flex-col justify-between"
        >
          <div>
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-md border border-indigo-100">
                {{ d.code || 'DIV' }}
              </span>
              <div class="flex items-center gap-1">
                <button @click="openEditModal(d)" class="p-1 text-slate-400 hover:text-indigo-600">
                  <Edit2 class="w-4 h-4" />
                </button>
                <button @click="deleteDivision(d)" class="p-1 text-slate-400 hover:text-rose-600">
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </div>
            <h3 class="text-base font-bold text-slate-900">{{ d.name }}</h3>
          </div>

          <div class="pt-4 border-t border-slate-100 mt-4 flex items-center justify-between text-xs text-slate-500 font-medium">
            <span class="flex items-center gap-1.5">
              <Users class="w-4 h-4 text-slate-400" />
              Total Anggota:
            </span>
            <span class="font-bold text-slate-800">{{ d.users_count || 0 }} Karyawan</span>
          </div>
        </div>
      </div>
    </div>

    <Modal :show="showModal" @close="closeModal" maxWidth="md">
      <div class="p-6">
        <h3 class="text-lg font-bold text-slate-900 mb-4">
          {{ editingDivision ? 'Edit Divisi' : 'Tambah Divisi Baru' }}
        </h3>

        <form @submit.prevent="submitForm" class="space-y-4 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Kode Divisi</label>
            <input
              v-model="form.code"
              type="text"
              placeholder="mis: IT / HR / FIN"
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Nama Divisi</label>
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="mis: IT Department"
              class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white"
            />
            <p v-if="form.errors.name" class="text-rose-500 text-[11px] mt-1">{{ form.errors.name }}</p>
          </div>

          <div class="pt-4 border-t border-slate-200 flex justify-end gap-2">
            <button type="button" @click="closeModal" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl">Batal</button>
            <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-xl">Simpan</button>
          </div>
        </form>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>
