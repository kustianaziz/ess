<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { FileText, Plus, Edit2, Trash2, CheckCircle, XCircle, Sparkles, RefreshCw, Layers } from 'lucide-vue-next';

const props = defineProps({
  expenseTypes: Array,
  aiStats: Object,
});

const showModal = ref(false);
const editingType = ref(null);

const form = useForm({
  name: '',
  is_active: true,
});

const aiForm = useForm({});

const runCategorizeAi = () => {
  if (confirm('Jalankan pemindaian dan konversi otomatis transaksi reimburse tools AI (Gemini, AGY, Claude, ChatGPT, Cursor, Copilot, dll) ke kategori Langganan & Layanan AI?')) {
    aiForm.post(route('admin.expense-types.categorize-ai'), {
      preserveScroll: true,
    });
  }
};

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
    <div class="space-y-6 max-w-7xl mx-auto">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
            <FileText class="w-7 h-7 text-emerald-600" />
            Jenis Pengeluaran (Reimbursement)
          </h1>
          <p class="text-xs text-slate-500 mt-1">
            Master kategori klaim pengeluaran reimbursement karyawan & pengelompokan biaya operasional.
          </p>
        </div>

        <button
          @click="openCreateModal"
          class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-lg shadow-emerald-600/20 transition-all self-start sm:self-auto"
        >
          <Plus class="w-4 h-4" />
          <span>Tambah Jenis Pengeluaran</span>
        </button>
      </div>

      <!-- BANNER / KONTROL EKSEKUSI PEMINDAHAN TRANSAKSI AI -->
      <div class="bg-gradient-to-br from-indigo-950 via-slate-900 to-indigo-900 p-5 sm:p-6 rounded-3xl text-white shadow-xl relative overflow-hidden border border-indigo-700/40">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5 relative z-10">
          <div class="space-y-2 max-w-2xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-400/30 text-indigo-200 text-xs font-bold">
              <Sparkles class="w-3.5 h-3.5 text-indigo-400" />
              <span>Kelompok Khusus: Langganan & Layanan AI</span>
            </div>
            <h2 class="text-base sm:text-lg font-bold text-white tracking-tight">
              Eksekusi Pemindahan Transaksi Reimburse Tools AI
            </h2>
            <p class="text-xs text-slate-300 leading-relaxed">
              Memudahkan Direktur & Keuangan mengukur produktivitas kerja tim dari biaya AI. Sistem memindai transaksi yang memuat: <strong class="text-indigo-200">Gemini, AGY, Antigravity, Claude, ChatGPT, Cursor, Copilot, Midjourney, OpenAI, dll.</strong>
            </p>

            <div class="flex flex-wrap items-center gap-4 pt-1 text-xs">
              <div class="flex items-center gap-1.5">
                <span class="text-slate-400">Tercatat di Kategori AI:</span>
                <span class="px-2.5 py-0.5 rounded-lg bg-emerald-500/20 text-emerald-300 font-bold border border-emerald-500/30">
                  {{ aiStats?.converted_count || 0 }} Transaksi
                </span>
              </div>
              <div v-if="aiStats?.unconverted_count > 0" class="flex items-center gap-1.5">
                <span class="text-slate-400">Terdeteksi Belum Terpindah:</span>
                <span class="px-2.5 py-0.5 rounded-lg bg-amber-500/20 text-amber-300 font-bold border border-amber-500/30 animate-pulse">
                  {{ aiStats?.unconverted_count }} Transaksi Siap Dipindahkan
                </span>
              </div>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 shrink-0">
            <button
              @click="runCategorizeAi"
              :disabled="aiForm.processing"
              class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-gradient-to-r from-indigo-500 via-indigo-600 to-emerald-600 hover:from-indigo-600 hover:to-emerald-700 text-white font-bold text-xs shadow-lg shadow-indigo-500/25 transition-all disabled:opacity-50"
            >
              <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': aiForm.processing }" />
              <span>{{ aiForm.processing ? 'Memproses Konversi...' : 'Pindai & Pindahkan Transaksi AI' }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Table Section -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                <th class="py-3.5 px-5">Nama Jenis Pengeluaran</th>
                <th class="py-3.5 px-5">Total Transaksi</th>
                <th class="py-3.5 px-5">Status</th>
                <th class="py-3.5 px-5 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr 
                v-for="t in expenseTypes" 
                :key="t.id" 
                class="hover:bg-slate-50/70 transition-colors"
                :class="{'bg-indigo-50/30': t.name === 'Langganan & Layanan AI'}"
              >
                <td class="py-3.5 px-5">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-bold text-slate-900 text-sm">{{ t.name }}</span>
                    <span 
                      v-if="t.name === 'Langganan & Layanan AI'" 
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-700 border border-indigo-200 shadow-2xs"
                    >
                      <Sparkles class="w-3 h-3 text-indigo-500" />
                      Kategori AI Khusus
                    </span>
                  </div>
                </td>
                <td class="py-3.5 px-5">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 font-semibold text-xs">
                    <Layers class="w-3.5 h-3.5 text-slate-400" />
                    {{ t.reimbursement_requests_count || 0 }} Transaksi
                  </span>
                </td>
                <td class="py-3.5 px-5">
                  <span v-if="t.is_active" class="inline-flex items-center gap-1 text-emerald-600 font-bold">
                    <CheckCircle class="w-3.5 h-3.5" /> Aktif
                  </span>
                  <span v-else class="inline-flex items-center gap-1 text-slate-400 font-semibold">
                    <XCircle class="w-3.5 h-3.5" /> Nonaktif
                  </span>
                </td>
                <td class="py-3.5 px-5 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button 
                      @click="openEditModal(t)" 
                      class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                      title="Edit Kategori"
                    >
                      <Edit2 class="w-4 h-4" />
                    </button>
                    <button 
                      @click="deleteType(t)" 
                      class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                      title="Hapus Kategori"
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

    <!-- Modal Form -->
    <Modal :show="showModal" @close="closeModal" maxWidth="md">
      <div class="p-6">
        <h3 class="text-base font-bold text-slate-900 mb-4">{{ editingType ? 'Edit' : 'Tambah' }} Jenis Pengeluaran</h3>
        <form @submit.prevent="submitForm" class="space-y-4 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Nama Kategori</label>
            <input v-model="form.name" type="text" required placeholder="mis: Transportasi / Langganan & Layanan AI" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-emerald-500 focus:border-emerald-500" />
          </div>
          <div>
            <label class="block font-bold text-slate-700 mb-1">Status Kategori</label>
            <select v-model="form.is_active" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-emerald-500 focus:border-emerald-500">
              <option :value="true">Aktif</option>
              <option :value="false">Nonaktif</option>
            </select>
          </div>
          <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
            <button type="button" @click="closeModal" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl hover:bg-slate-200 transition-colors">Batal</button>
            <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition-colors">Simpan</button>
          </div>
        </form>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>
