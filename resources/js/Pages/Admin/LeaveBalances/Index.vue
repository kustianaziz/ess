<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Scale, Plus, Edit2 } from 'lucide-vue-next';

const props = defineProps({
  balances: Object,
  users: Array,
  leaveTypes: Array,
  year: Number,
});

const selectedYear = ref(props.year);
const showModal = ref(false);
const editingBalance = ref(null);

const form = useForm({
  user_id: '',
  leave_type_id: '',
  year: props.year,
  quota: 12,
  used: 0,
});

const handleYearChange = () => {
  router.get(route('admin.leave-balances.index'), { year: selectedYear.value }, { preserveState: true, replace: true });
};

const openCreateModal = () => {
  editingBalance.value = null;
  form.reset();
  form.year = selectedYear.value;
  form.clearErrors();
  showModal.value = true;
};

const openEditModal = (b) => {
  editingBalance.value = b;
  form.clearErrors();
  form.quota = b.quota;
  form.used = b.used;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editingBalance.value = null;
  form.reset();
};

const submitForm = () => {
  if (editingBalance.value) {
    form.put(route('admin.leave-balances.update', editingBalance.value.id), {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post(route('admin.leave-balances.store'), {
      onSuccess: () => closeModal(),
    });
  }
};
</script>

<template>
  <Head title="Kuota Cuti Karyawan - Admin" />

  <AuthenticatedLayout>
    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <Scale class="w-7 h-7 text-indigo-600" />
            Kuota & Sisa Cuti Karyawan
          </h1>
          <p class="text-xs text-slate-500 mt-1">
            Pengelolaan kuota tahunan, pemakaian cuti, dan penyesuaian sisa cuti per karyawan.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <select v-model="selectedYear" @change="handleYearChange" class="p-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-800">
            <option v-for="y in [2024, 2025, 2026, 2027]" :key="y" :value="y">Tahun {{ y }}</option>
          </select>

          <button
            @click="openCreateModal"
            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/20 transition-all"
          >
            <Plus class="w-4 h-4" />
            <span>Tambah Kuota</span>
          </button>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
              <th class="py-3.5 px-4">Karyawan</th>
              <th class="py-3.5 px-4">Jenis Cuti</th>
              <th class="py-3.5 px-4">Tahun</th>
              <th class="py-3.5 px-4">Total Kuota</th>
              <th class="py-3.5 px-4">Terpakai</th>
              <th class="py-3.5 px-4">Sisa Kuota</th>
              <th class="py-3.5 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="b in balances.data" :key="b.id" class="hover:bg-slate-50/50">
              <td class="py-3.5 px-4">
                <div class="font-bold text-slate-900">{{ b.user?.name || '-' }}</div>
                <div class="text-[11px] text-slate-400 font-mono">{{ b.user?.nik }}</div>
              </td>
              <td class="py-3.5 px-4 font-semibold text-slate-800">{{ b.leave_type?.name }}</td>
              <td class="py-3.5 px-4 text-slate-600 font-mono">{{ b.year }}</td>
              <td class="py-3.5 px-4 font-bold text-slate-800">{{ b.quota }} Hari</td>
              <td class="py-3.5 px-4 font-bold text-amber-600">{{ b.used }} Hari</td>
              <td class="py-3.5 px-4 font-bold text-emerald-600">{{ b.remaining }} Hari</td>
              <td class="py-3.5 px-4 text-right">
                <button @click="openEditModal(b)" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg">
                  <Edit2 class="w-4 h-4" />
                </button>
              </td>
            </tr>
            <tr v-if="balances.data.length === 0">
              <td colspan="7" class="py-8 text-center text-slate-400">Belum ada data kuota cuti untuk tahun ini.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Modal :show="showModal" @close="closeModal" maxWidth="md">
      <div class="p-6">
        <h3 class="text-lg font-bold text-slate-900 mb-4">{{ editingBalance ? 'Edit Kuota Cuti' : 'Tambah Kuota Cuti Karyawan' }}</h3>
        
        <form @submit.prevent="submitForm" class="space-y-4 text-xs">
          <div v-if="!editingBalance">
            <label class="block font-bold text-slate-700 mb-1">Pilih Karyawan</label>
            <select v-model="form.user_id" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
              <option value="">Pilih Karyawan</option>
              <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.nik }})</option>
            </select>
          </div>

          <div v-if="!editingBalance">
            <label class="block font-bold text-slate-700 mb-1">Jenis Cuti</label>
            <select v-model="form.leave_type_id" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
              <option value="">Pilih Jenis Cuti</option>
              <option v-for="t in leaveTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Total Kuota (Hari)</label>
              <input v-model="form.quota" type="number" min="0" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs" />
            </div>

            <div v-if="editingBalance">
              <label class="block font-bold text-slate-700 mb-1">Sudah Terpakai (Hari)</label>
              <input v-model="form.used" type="number" min="0" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs" />
            </div>
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
