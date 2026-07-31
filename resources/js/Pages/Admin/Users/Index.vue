<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Users, UserPlus, Search, Edit2, Trash2, Shield, CheckCircle, XCircle } from 'lucide-vue-next';

const props = defineProps({
  users: Object,
  divisions: Array,
  managers: Array,
  roles: Array,
  filters: Object,
});

const search = ref(props.filters.search || '');
const showModal = ref(false);
const editingUser = ref(null);

const form = useForm({
  nik: '',
  name: '',
  email: '',
  password: '',
  division_id: '',
  position: '',
  manager_id: null,
  role: 'employee',
  status: 'active',
});

const openCreateModal = () => {
  editingUser.value = null;
  form.reset();
  form.clearErrors();
  showModal.value = true;
};

const openEditModal = (user) => {
  editingUser.value = user;
  form.clearErrors();
  form.nik = user.nik;
  form.name = user.name;
  form.email = user.email;
  form.password = '';
  form.division_id = user.division_id;
  form.position = user.position;
  form.manager_id = user.manager_id;
  form.role = user.roles?.[0]?.name || 'employee';
  form.status = user.status;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  editingUser.value = null;
  form.reset();
};

const handleSearch = () => {
  router.get(route('admin.users.index'), { search: search.value }, { preserveState: true, replace: true });
};

const submitForm = () => {
  if (editingUser.value) {
    form.put(route('admin.users.update', editingUser.value.id), {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post(route('admin.users.store'), {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteUser = (user) => {
  if (confirm(`Apakah Anda yakin ingin menghapus pengguna ${user.name}?`)) {
    router.delete(route('admin.users.destroy', user.id));
  }
};
</script>

<template>
  <Head title="Kelola Pengguna - Admin" />

  <AuthenticatedLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <Users class="w-7 h-7 text-indigo-600" />
            Kelola Pengguna & Hak Akses
          </h1>
          <p class="text-xs text-slate-500 mt-1">
            Manajemen data pengguna, divisi, jabatan, atasan langsung, dan role hak akses sistem.
          </p>
        </div>

        <button
          @click="openCreateModal"
          class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/20 transition-all"
        >
          <UserPlus class="w-4 h-4" />
          <span>Tambah Pengguna</span>
        </button>
      </div>

      <!-- Filter & Search Bar -->
      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-3">
        <div class="relative flex-1">
          <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            v-model="search"
            @keyup.enter="handleSearch"
            type="text"
            placeholder="Cari berdasarkan NIK, nama, atau email..."
            class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500"
          />
        </div>
        <button
          @click="handleSearch"
          class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition-colors"
        >
          Cari
        </button>
      </div>

      <!-- Users Table Card -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                <th class="py-3.5 px-4">Pengguna</th>
                <th class="py-3.5 px-4">Divisi & Jabatan</th>
                <th class="py-3.5 px-4">Atasan Langsung</th>
                <th class="py-3.5 px-4">Role</th>
                <th class="py-3.5 px-4">Status</th>
                <th class="py-3.5 px-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
              <tr v-for="u in users.data" :key="u.id" class="hover:bg-slate-50/50 transition-colors">
                <td class="py-3.5 px-4">
                  <div class="font-bold text-slate-900">{{ u.name }}</div>
                  <div class="text-[11px] text-slate-400 font-mono">{{ u.nik }} • {{ u.email }}</div>
                </td>
                <td class="py-3.5 px-4">
                  <div class="font-medium text-slate-800">{{ u.division?.name || '-' }}</div>
                  <div class="text-[11px] text-slate-400">{{ u.position }}</div>
                </td>
                <td class="py-3.5 px-4 text-slate-600 font-medium">
                  {{ u.manager?.name || '-' }}
                </td>
                <td class="py-3.5 px-4">
                  <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold uppercase bg-indigo-50 text-indigo-700 border border-indigo-200">
                    <Shield class="w-3 h-3" />
                    {{ u.roles?.[0]?.name || 'employee' }}
                  </span>
                </td>
                <td class="py-3.5 px-4">
                  <span v-if="u.status === 'active'" class="inline-flex items-center gap-1 text-emerald-600 font-semibold">
                    <CheckCircle class="w-3.5 h-3.5" /> Aktif
                  </span>
                  <span v-else class="inline-flex items-center gap-1 text-slate-400 font-semibold">
                    <XCircle class="w-3.5 h-3.5" /> Nonaktif
                  </span>
                </td>
                <td class="py-3.5 px-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      @click="openEditModal(u)"
                      class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                      title="Edit"
                    >
                      <Edit2 class="w-4 h-4" />
                    </button>
                    <button
                      @click="deleteUser(u)"
                      class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                      title="Hapus"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="users.data.length === 0">
                <td colspan="6" class="py-8 text-center text-slate-400">Tidak ada pengguna ditemukan.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <Modal :show="showModal" @close="closeModal" maxWidth="xl">
      <div class="p-6">
        <h3 class="text-lg font-bold text-slate-900 mb-4">
          {{ editingUser ? 'Edit Data Pengguna' : 'Tambah Pengguna Baru' }}
        </h3>

        <form @submit.prevent="submitForm" class="space-y-4 text-xs">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block font-bold text-slate-700 mb-1">NIK (Nomor Induk Karyawan)</label>
              <input
                v-model="form.nik"
                type="text"
                required
                placeholder="mis: EDU-IT-001"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white"
              />
              <p v-if="form.errors.nik" class="text-rose-500 text-[11px] mt-1">{{ form.errors.nik }}</p>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Nama Lengkap</label>
              <input
                v-model="form.name"
                type="text"
                required
                placeholder="Nama Karyawan"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white"
              />
              <p v-if="form.errors.name" class="text-rose-500 text-[11px] mt-1">{{ form.errors.name }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Email Login</label>
              <input
                v-model="form.email"
                type="email"
                required
                placeholder="email@edu.id"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white"
              />
              <p v-if="form.errors.email" class="text-rose-500 text-[11px] mt-1">{{ form.errors.email }}</p>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Kata Sandi {{ editingUser ? '(Opsional)' : '' }}</label>
              <input
                v-model="form.password"
                type="password"
                :required="!editingUser"
                placeholder="••••••••"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white"
              />
              <p v-if="form.errors.password" class="text-rose-500 text-[11px] mt-1">{{ form.errors.password }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Divisi</label>
              <select v-model="form.division_id" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white">
                <option value="">Pilih Divisi</option>
                <option v-for="d in divisions" :key="d.id" :value="d.id">{{ d.name }}</option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Jabatan</label>
              <input
                v-model="form.position"
                type="text"
                required
                placeholder="mis: IT Support"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white"
              />
            </div>
          </div>

          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Atasan Langsung (Approver L1)</label>
              <select v-model="form.manager_id" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white">
                <option :value="null">Tanpa Atasan (Eskalasi ke HRD)</option>
                <option v-for="m in managers" :key="m.id" :value="m.id">{{ m.name }} ({{ m.position }})</option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Role / Hak Akses</label>
              <select v-model="form.role" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white">
                <option v-for="r in roles" :key="r.id" :value="r.name">{{ r.name }}</option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Status Akun</label>
              <select v-model="form.status" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white">
                <option value="active">Aktif</option>
                <option value="inactive">Nonaktif</option>
              </select>
            </div>
          </div>

          <div class="pt-4 border-t border-slate-200 flex justify-end gap-2">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl"
            >
              Simpan
            </button>
          </div>
        </form>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>
