<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Plus, Edit2, Trash2, X, FileText, CheckCircle2, XCircle } from 'lucide-vue-next';

const props = defineProps({
  coas: {
    type: Array,
    default: () => []
  }
});

const isModalOpen = ref(false);
const editingId = ref(null);

const form = useForm({
  code: '',
  name: '',
  type: 'aset',
  normal_balance: 'debit',
  parent_id: '',
  is_active: true,
  is_header: false,
  description: ''
});

// Watch for type changes to automate normal_balance
import { watch } from 'vue';
watch(() => form.type, (newType) => {
  if (['aset', 'beban'].includes(newType)) {
    form.normal_balance = 'debit';
  } else if (['hutang', 'modal', 'pendapatan'].includes(newType)) {
    form.normal_balance = 'credit';
  }
});

const openModal = (coa = null) => {
  if (coa) {
    editingId.value = coa.id;
    form.code = coa.code;
    form.name = coa.name;
    form.type = coa.type;
    form.normal_balance = coa.normal_balance;
    form.parent_id = coa.parent_id || '';
    form.is_active = !!coa.is_active;
    form.is_header = !!coa.is_header;
    form.description = coa.description || '';
  } else {
    editingId.value = null;
    form.reset();
  }
  isModalOpen.value = true;
};

const addSubCoa = (parentCoa) => {
  editingId.value = null;
  form.reset();
  form.parent_id = parentCoa.id;
  form.type = parentCoa.type;
  if (['aset', 'beban'].includes(parentCoa.type)) {
    form.normal_balance = 'debit';
  } else {
    form.normal_balance = 'credit';
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
    form.put(route('accounting.coas.update', editingId.value), {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post(route('accounting.coas.store'), {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteCoa = (id) => {
  if (confirm('Apakah Anda yakin ingin menghapus COA ini?')) {
    form.delete(route('accounting.coas.destroy', id));
  }
};

const groupedCoas = computed(() => {
  const groups = {
    aset: { label: 'Aset (Harta)', color: 'text-emerald-600 bg-emerald-50 border-emerald-200', items: [] },
    hutang: { label: 'Hutang (Kewajiban)', color: 'text-rose-600 bg-rose-50 border-rose-200', items: [] },
    modal: { label: 'Modal (Ekuitas)', color: 'text-purple-600 bg-purple-50 border-purple-200', items: [] },
    pendapatan: { label: 'Pendapatan', color: 'text-sky-600 bg-sky-50 border-sky-200', items: [] },
    beban: { label: 'Beban (Pengeluaran)', color: 'text-orange-600 bg-orange-50 border-orange-200', items: [] }
  };
  
  props.coas.forEach(coa => {
    if (groups[coa.type]) {
      groups[coa.type].items.push(coa);
    }
  });
  
  return groups;
});

const getParentOptions = computed(() => {
  return props.coas.map(c => ({
    id: c.id,
    label: `${c.code} - ${c.name}`
  }));
});
</script>

<template>
  <Head title="Master COA (Chart of Accounts)" />

  <AuthenticatedLayout>
    <div class="max-w-7xl mx-auto space-y-6">
      
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-lg shadow-indigo-600/20">
              <FileText class="w-4 h-4" />
            </div>
            Master Chart of Accounts (COA)
          </h1>
          <p class="text-sm text-slate-500 mt-1 font-medium">Kelola daftar akun buku besar perusahaan</p>
        </div>
        <button 
          @click="openModal()"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all hover:-translate-y-0.5"
        >
          <Plus class="w-4 h-4" />
          <span>Tambah COA</span>
        </button>
      </div>

      <!-- Content -->
      <div class="space-y-6">
        <template v-for="(group, type) in groupedCoas" :key="type">
          <div v-if="group.items.length > 0" class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div :class="['px-6 py-4 border-b flex items-center justify-between', group.color.split(' ')[1]]">
              <h2 :class="['text-lg font-bold tracking-tight', group.color.split(' ')[0]]">{{ group.label }}</h2>
              <span class="px-3 py-1 rounded-full text-xs font-bold bg-white/50 border border-white/40">
                {{ group.items.length }} Akun
              </span>
            </div>
            
            <div class="overflow-x-auto">
              <table class="w-full text-sm text-left">
                <thead class="text-xs text-slate-500 bg-slate-50/50 border-b border-slate-100 uppercase tracking-wider font-semibold">
                  <tr>
                    <th class="px-6 py-4">Kode Akun</th>
                    <th class="px-6 py-4">Nama Akun</th>
                    <th class="px-6 py-4">Saldo Normal</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="coa in group.items" :key="coa.id" class="hover:bg-slate-50/80 transition-colors group/row">
                    <td class="px-6 py-4 font-mono font-bold text-slate-900">{{ coa.code }}</td>
                    <td class="px-6 py-4">
                      <div class="font-semibold text-slate-800 flex items-center gap-2">
                        {{ coa.name }}
                        <span v-if="coa.is_header" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-500 border border-slate-200 uppercase">Header</span>
                      </div>
                      <div v-if="coa.parent" class="text-[10px] text-slate-500 mt-0.5 font-medium">
                        Sub dari: {{ coa.parent.code }} - {{ coa.parent.name }}
                      </div>
                    </td>
                    <td class="px-6 py-4">
                      <span :class="[
                        'px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wider',
                        coa.normal_balance === 'debit' ? 'bg-sky-100 text-sky-700' : 'bg-purple-100 text-purple-700'
                      ]">
                        {{ coa.normal_balance }}
                      </span>
                    </td>
                    <td class="px-6 py-4">
                      <span v-if="coa.is_active" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-200">
                        <CheckCircle2 class="w-3.5 h-3.5" /> Aktif
                      </span>
                      <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 text-xs font-bold border border-slate-200">
                        <XCircle class="w-3.5 h-3.5" /> Nonaktif
                      </span>
                    </td>
                    <td class="px-6 py-4">
                      <div class="flex items-center justify-end gap-2 opacity-0 group-hover/row:opacity-100 transition-opacity">
                        <button 
                          v-if="coa.is_header"
                          @click="addSubCoa(coa)"
                          class="p-2 rounded-xl text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors"
                          title="Tambah Sub Akun"
                        >
                          <Plus class="w-4 h-4" />
                        </button>
                        <button 
                          @click="openModal(coa)"
                          class="p-2 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
                          title="Edit"
                        >
                          <Edit2 class="w-4 h-4" />
                        </button>
                        <button 
                          @click="deleteCoa(coa.id)"
                          class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
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
        </template>
        
        <div v-if="props.coas.length === 0" class="bg-white rounded-3xl border border-slate-200/80 p-12 text-center shadow-sm">
          <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <FileText class="w-8 h-8 text-slate-400" />
          </div>
          <h3 class="text-lg font-bold text-slate-900 mb-1">Belum ada Master COA</h3>
          <p class="text-sm text-slate-500 mb-6">Mulai dengan menambahkan Chart of Accounts pertama Anda.</p>
          <button 
            @click="openModal()"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all hover:-translate-y-0.5"
          >
            <Plus class="w-4 h-4" />
            <span>Tambah COA</span>
          </button>
        </div>
      </div>

    </div>

    <!-- Modal Form -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeModal"></div>
      
      <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden animate-in zoom-in-95 duration-200">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
          <h3 class="text-lg font-bold text-slate-900">
            {{ editingId ? 'Edit COA' : 'Tambah COA Baru' }}
          </h3>
          <button @click="closeModal" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-200/50 rounded-xl transition-colors">
            <X class="w-5 h-5" />
          </button>
        </div>
        
        <form @submit.prevent="save" class="p-6 space-y-5">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Kode Akun <span class="text-rose-500">*</span></label>
              <input 
                v-model="form.code"
                type="text" 
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all font-mono"
                placeholder="Mis: 101-01"
                required
              />
              <span v-if="form.errors.code" class="text-[10px] font-medium text-rose-500 mt-1 block">{{ form.errors.code }}</span>
            </div>
            
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Tipe Akun <span class="text-rose-500">*</span></label>
              <select 
                v-model="form.type"
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all"
                required
              >
                <option value="aset">Aset (Harta)</option>
                <option value="hutang">Hutang (Kewajiban)</option>
                <option value="modal">Modal (Ekuitas)</option>
                <option value="pendapatan">Pendapatan</option>
                <option value="beban">Beban (Pengeluaran)</option>
              </select>
              <span v-if="form.errors.type" class="text-[10px] font-medium text-rose-500 mt-1 block">{{ form.errors.type }}</span>
            </div>
          </div>
          
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Nama Akun <span class="text-rose-500">*</span></label>
            <input 
              v-model="form.name"
              type="text" 
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all"
              placeholder="Mis: Kas di Tangan"
              required
            />
            <span v-if="form.errors.name" class="text-[10px] font-medium text-rose-500 mt-1 block">{{ form.errors.name }}</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Saldo Normal <span class="text-rose-500">*</span></label>
              <select 
                v-model="form.normal_balance"
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all bg-slate-100 cursor-not-allowed"
                required
                disabled
              >
                <option value="debit">Debit</option>
                <option value="credit">Kredit</option>
              </select>
              <p class="text-[9px] text-slate-500 mt-1">Otomatis menyesuaikan tipe akun.</p>
              <span v-if="form.errors.normal_balance" class="text-[10px] font-medium text-rose-500 mt-1 block">{{ form.errors.normal_balance }}</span>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Sub Akun Dari (Opsional)</label>
              <select 
                v-model="form.parent_id"
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all"
              >
                <option value="">-- Tidak Ada Induk --</option>
                <option v-for="opt in getParentOptions" :key="opt.id" :value="opt.id">
                  {{ opt.label }}
                </option>
              </select>
              <span v-if="form.errors.parent_id" class="text-[10px] font-medium text-rose-500 mt-1 block">{{ form.errors.parent_id }}</span>
            </div>
          </div>
          
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Deskripsi (Opsional)</label>
            <textarea 
              v-model="form.description"
              rows="2"
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all resize-none"
              placeholder="Catatan tambahan..."
            ></textarea>
            <span v-if="form.errors.description" class="text-[10px] font-medium text-rose-500 mt-1 block">{{ form.errors.description }}</span>
          </div>

          <div class="flex items-center gap-6 py-2">
            <div class="flex items-center gap-3">
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
              </label>
              <span class="text-sm font-semibold text-slate-700">Akun Aktif</span>
            </div>

            <div class="flex items-center gap-3">
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="form.is_header" class="sr-only peer">
                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
              </label>
              <span class="text-sm font-semibold text-slate-700">Akun Header</span>
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
              {{ form.processing ? 'Menyimpan...' : 'Simpan COA' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </AuthenticatedLayout>
</template>
