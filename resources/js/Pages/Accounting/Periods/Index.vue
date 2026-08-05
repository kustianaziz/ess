<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Plus, Edit2, Trash2, X, CalendarClock, Lock, Unlock } from 'lucide-vue-next';

const props = defineProps({
  periods: {
    type: Array,
    default: () => []
  }
});

const isModalOpen = ref(false);
const editingId = ref(null);

const form = useForm({
  name: '',
  start_date: '',
  end_date: '',
});

const openModal = (period = null) => {
  if (period) {
    editingId.value = period.id;
    form.name = period.name;
    form.start_date = period.start_date;
    form.end_date = period.end_date;
  } else {
    editingId.value = null;
    form.reset();
  }
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
  form.reset();
  form.clearErrors();
};

const save = () => {
  if (editingId.value) {
    form.put(route('accounting.periods.update', editingId.value), {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post(route('accounting.periods.store'), {
      onSuccess: () => closeModal(),
    });
  }
};

const deletePeriod = (id) => {
  if (confirm('Apakah Anda yakin ingin menghapus periode ini?')) {
    router.delete(route('accounting.periods.destroy', id));
  }
};

const closePeriod = (id) => {
  if (confirm('Apakah Anda yakin ingin menutup buku untuk periode ini? Tindakan ini tidak dapat dibatalkan.')) {
    router.post(route('accounting.periods.close', id));
  }
};

const formatDate = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  }).format(date);
};
</script>

<template>
  <Head title="Master Periode Akuntansi" />

  <AuthenticatedLayout>
    <div class="max-w-7xl mx-auto space-y-6">
      
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-lg shadow-indigo-600/20">
              <CalendarClock class="w-4 h-4" />
            </div>
            Master Periode Akuntansi
          </h1>
          <p class="text-sm text-slate-500 mt-1 font-medium">Kelola daftar periode akuntansi & tutup buku</p>
        </div>
        <button 
          @click="openModal()"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all hover:-translate-y-0.5"
        >
          <Plus class="w-4 h-4" />
          <span>Tambah Periode</span>
        </button>
      </div>

      <!-- Content -->
      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead class="text-xs text-slate-500 bg-slate-50/50 border-b border-slate-100 uppercase tracking-wider font-semibold">
              <tr>
                <th class="px-6 py-4">Nama Periode</th>
                <th class="px-6 py-4">Tanggal Mulai</th>
                <th class="px-6 py-4">Tanggal Berakhir</th>
                <th class="px-6 py-4">Status</th>
                <th class="px-6 py-4">Ditutup Oleh</th>
                <th class="px-6 py-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="period in periods" :key="period.id" class="hover:bg-slate-50/80 transition-colors">
                <td class="px-6 py-4 font-bold text-slate-900">{{ period.name }}</td>
                <td class="px-6 py-4 text-slate-700">{{ formatDate(period.start_date) }}</td>
                <td class="px-6 py-4 text-slate-700">{{ formatDate(period.end_date) }}</td>
                <td class="px-6 py-4">
                  <span v-if="period.is_closed" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700 text-xs font-bold border border-rose-200">
                    <Lock class="w-3.5 h-3.5" /> Ditutup
                  </span>
                  <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-200">
                    <Unlock class="w-3.5 h-3.5" /> Buka
                  </span>
                </td>
                <td class="px-6 py-4 text-slate-500 text-xs">
                  <div v-if="period.is_closed">
                    <div class="font-semibold">{{ period.closed_by?.name || 'Sistem' }}</div>
                    <div>{{ formatDate(period.closed_at) }}</div>
                  </div>
                  <div v-else>-</div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center justify-end gap-2">
                    <button 
                      v-if="!period.is_closed"
                      @click="closePeriod(period.id)"
                      class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                      title="Tutup Buku"
                    >
                      <Lock class="w-4 h-4" />
                    </button>
                    <button 
                      v-if="!period.is_closed"
                      @click="openModal(period)"
                      class="p-2 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
                      title="Edit"
                    >
                      <Edit2 class="w-4 h-4" />
                    </button>
                    <button 
                      v-if="!period.is_closed"
                      @click="deletePeriod(period.id)"
                      class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                      title="Hapus"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="periods.length === 0">
                <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                  Belum ada master periode akuntansi.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- Modal Form -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeModal"></div>
      
      <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden animate-in zoom-in-95 duration-200">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
          <h3 class="text-lg font-bold text-slate-900">
            {{ editingId ? 'Edit Periode' : 'Tambah Periode Baru' }}
          </h3>
          <button @click="closeModal" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-200/50 rounded-xl transition-colors">
            <X class="w-5 h-5" />
          </button>
        </div>
        
        <form @submit.prevent="save" class="p-6 space-y-5">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Nama Periode <span class="text-rose-500">*</span></label>
            <input 
              v-model="form.name"
              type="text" 
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all"
              placeholder="Mis: Januari 2026"
              required
            />
            <span v-if="form.errors.name" class="text-[10px] font-medium text-rose-500 mt-1 block">{{ form.errors.name }}</span>
          </div>
          
          <div class="grid grid-cols-2 gap-5">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Tanggal Mulai <span class="text-rose-500">*</span></label>
              <input 
                v-model="form.start_date"
                type="date" 
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all"
                required
              />
              <span v-if="form.errors.start_date" class="text-[10px] font-medium text-rose-500 mt-1 block">{{ form.errors.start_date }}</span>
            </div>
            
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Tanggal Berakhir <span class="text-rose-500">*</span></label>
              <input 
                v-model="form.end_date"
                type="date" 
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all"
                required
              />
              <span v-if="form.errors.end_date" class="text-[10px] font-medium text-rose-500 mt-1 block">{{ form.errors.end_date }}</span>
            </div>
          </div>
          
          <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
            <button 
              type="button" 
              @click="closeModal"
              class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-600 hover:bg-slate-100 transition-colors"
            >
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="form.processing"
              class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
