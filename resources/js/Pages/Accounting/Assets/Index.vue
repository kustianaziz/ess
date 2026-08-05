<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Plus, Edit2, Trash2, X, Archive, Calculator } from 'lucide-vue-next';

const props = defineProps({
  assets: {
    type: Array,
    default: () => []
  },
  coas: {
    type: Array,
    default: () => []
  }
});

const isModalOpen = ref(false);
const editingId = ref(null);

const form = useForm({
  asset_number: '',
  name: '',
  category: 'Peralatan Kantor',
  purchase_date: '',
  purchase_price: 0,
  salvage_value: 0,
  useful_life_years: 1,
  depreciation_method: 'straight_line',
  coa_asset_id: '',
  coa_depreciation_expense_id: '',
  coa_accumulated_depreciation_id: '',
});

const openModal = (asset = null) => {
  if (asset) {
    editingId.value = asset.id;
    form.asset_number = asset.asset_number;
    form.name = asset.name;
    form.category = asset.category || 'Peralatan Kantor';
    form.purchase_date = asset.purchase_date;
    form.purchase_price = asset.purchase_price;
    form.salvage_value = asset.salvage_value;
    form.useful_life_years = asset.useful_life_years;
    form.depreciation_method = asset.depreciation_method;
    form.coa_asset_id = asset.coa_asset_id;
    form.coa_depreciation_expense_id = asset.coa_depreciation_expense_id;
    form.coa_accumulated_depreciation_id = asset.coa_accumulated_depreciation_id;
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
    form.put(route('accounting.assets.update', editingId.value), {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post(route('accounting.assets.store'), {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteAsset = (id) => {
  if (confirm('Apakah Anda yakin ingin menghapus Aset ini?')) {
    form.delete(route('accounting.assets.destroy', id));
  }
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};

const groupedCoas = computed(() => {
  const groups = {
    aset: [],
    hutang: [],
    modal: [],
    pendapatan: [],
    beban: []
  };
  
  props.coas.forEach(c => {
    if (groups[c.type]) {
      groups[c.type].push(c);
    }
  });
  
  return groups;
});

const groupedAssets = computed(() => {
  const groups = {};
  props.assets.forEach(asset => {
    const cat = asset.category || 'Lainnya';
    if (!groups[cat]) {
      groups[cat] = [];
    }
    groups[cat].push(asset);
  });
  return groups;
});
</script>

<template>
  <Head title="Master Data Aset (Fixed Assets)" />

  <AuthenticatedLayout>
    <div class="max-w-7xl mx-auto space-y-6">
      
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-lg shadow-indigo-600/20">
              <Archive class="w-4 h-4" />
            </div>
            Master Data Aset
          </h1>
          <p class="text-sm text-slate-500 mt-1 font-medium">Kelola daftar aset tetap dan penyusutan perusahaan</p>
        </div>
        <button 
          @click="openModal()"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all hover:-translate-y-0.5"
        >
          <Plus class="w-4 h-4" />
          <span>Tambah Aset</span>
        </button>
      </div>

      <!-- Content -->
      <div class="space-y-6">
        <template v-for="(items, category) in groupedAssets" :key="category">
          <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
              <h2 class="text-lg font-bold text-slate-800 tracking-tight flex items-center gap-2">
                <div class="w-2 h-6 rounded-full bg-indigo-500"></div>
                {{ category }}
              </h2>
              <span class="px-3 py-1 rounded-full text-xs font-bold bg-white border border-slate-200 text-slate-600 shadow-sm">
                {{ items.length }} Aset
              </span>
            </div>
            
            <div class="overflow-x-auto">
              <table class="w-full text-sm text-left">
                <thead class="text-xs text-slate-500 bg-white border-b border-slate-100 uppercase tracking-wider font-semibold">
                  <tr>
                    <th class="px-6 py-4">Nomor Aset</th>
                    <th class="px-6 py-4">Nama Aset</th>
                    <th class="px-6 py-4">Tgl Pembelian</th>
                    <th class="px-6 py-4">Harga Beli</th>
                    <th class="px-6 py-4">Nilai Buku</th>
                    <th class="px-6 py-4">Umur (Thn)</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="asset in items" :key="asset.id" class="hover:bg-slate-50/80 transition-colors">
                    <td class="px-6 py-4 font-mono font-bold text-slate-900">{{ asset.asset_number }}</td>
                    <td class="px-6 py-4 font-medium text-slate-700">{{ asset.name }}</td>
                    <td class="px-6 py-4">{{ asset.purchase_date }}</td>
                    <td class="px-6 py-4">{{ formatCurrency(asset.purchase_price) }}</td>
                    <td class="px-6 py-4 text-emerald-600 font-semibold">{{ formatCurrency(asset.book_value) }}</td>
                    <td class="px-6 py-4">{{ asset.useful_life_years }}</td>
                    <td class="px-6 py-4">
                      <div class="flex items-center justify-end gap-2">
                        <button 
                          @click="openModal(asset)"
                          class="p-2 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
                          title="Edit"
                        >
                          <Edit2 class="w-4 h-4" />
                        </button>
                        <button 
                          @click="deleteAsset(asset.id)"
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

        <div v-if="assets.length === 0" class="bg-white rounded-3xl border border-slate-200/80 p-12 text-center shadow-sm">
          <Archive class="w-12 h-12 text-slate-300 mx-auto mb-3" />
          <h3 class="text-lg font-bold text-slate-900 mb-1">Belum ada data aset tetap</h3>
          <p class="text-sm text-slate-500 mb-6">Mulai tambahkan aset tetap perusahaan Anda.</p>
          <button 
            @click="openModal()"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all hover:-translate-y-0.5"
          >
            <Plus class="w-4 h-4" />
            <span>Tambah Aset</span>
          </button>
        </div>
      </div>

    </div>

    <!-- Modal Form -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeModal"></div>
      
      <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden animate-in zoom-in-95 duration-200">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
          <h3 class="text-lg font-bold text-slate-900">
            {{ editingId ? 'Edit Aset' : 'Tambah Aset Baru' }}
          </h3>
          <button @click="closeModal" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-200/50 rounded-xl transition-colors">
            <X class="w-5 h-5" />
          </button>
        </div>
        
        <form @submit.prevent="save" class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Nomor Aset <span class="text-rose-500">*</span></label>
              <input 
                v-model="form.asset_number"
                type="text" 
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all font-mono"
                placeholder="Mis: AST-2026-001"
                required
              />
              <span v-if="form.errors.asset_number" class="text-[10px] font-medium text-rose-500 mt-1 block">{{ form.errors.asset_number }}</span>
            </div>
            
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Nama Aset <span class="text-rose-500">*</span></label>
              <input 
                v-model="form.name"
                type="text" 
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all"
                placeholder="Mis: Laptop Dell XPS 13"
                required
              />
              <span v-if="form.errors.name" class="text-[10px] font-medium text-rose-500 mt-1 block">{{ form.errors.name }}</span>
            </div>
            
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Kategori <span class="text-rose-500">*</span></label>
              <select 
                v-model="form.category"
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all"
                required
              >
                <option value="Tanah">Tanah</option>
                <option value="Gedung & Bangunan">Gedung & Bangunan</option>
                <option value="Kendaraan">Kendaraan</option>
                <option value="Peralatan Kantor">Peralatan Kantor</option>
                <option value="Mesin">Mesin</option>
                <option value="Perangkat IT & Elektronik">Perangkat IT & Elektronik</option>
                <option value="Lainnya">Lainnya</option>
              </select>
              <span v-if="form.errors.category" class="text-[10px] font-medium text-rose-500 mt-1 block">{{ form.errors.category }}</span>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Tgl Pembelian <span class="text-rose-500">*</span></label>
              <input 
                v-model="form.purchase_date"
                type="date" 
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all"
                required
              />
              <span v-if="form.errors.purchase_date" class="text-[10px] font-medium text-rose-500 mt-1 block">{{ form.errors.purchase_date }}</span>
            </div>
            
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Harga Beli <span class="text-rose-500">*</span></label>
              <input 
                v-model="form.purchase_price"
                type="number" 
                min="0"
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all"
                required
              />
              <span v-if="form.errors.purchase_price" class="text-[10px] font-medium text-rose-500 mt-1 block">{{ form.errors.purchase_price }}</span>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Nilai Sisa (Salvage) <span class="text-rose-500">*</span></label>
              <input 
                v-model="form.salvage_value"
                type="number" 
                min="0"
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all"
                required
              />
              <span v-if="form.errors.salvage_value" class="text-[10px] font-medium text-rose-500 mt-1 block">{{ form.errors.salvage_value }}</span>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Umur Ekonomis (Tahun) <span class="text-rose-500">*</span></label>
              <input 
                v-model="form.useful_life_years"
                type="number" 
                min="1"
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all"
                required
              />
              <span v-if="form.errors.useful_life_years" class="text-[10px] font-medium text-rose-500 mt-1 block">{{ form.errors.useful_life_years }}</span>
            </div>
            
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Metode Penyusutan <span class="text-rose-500">*</span></label>
              <select 
                v-model="form.depreciation_method"
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all"
                required
              >
                <option value="straight_line">Garis Lurus (Straight Line)</option>
                <option value="declining_balance">Saldo Menurun (Declining Balance)</option>
              </select>
              <span v-if="form.errors.depreciation_method" class="text-[10px] font-medium text-rose-500 mt-1 block">{{ form.errors.depreciation_method }}</span>
            </div>
          </div>

          <div class="border-t border-slate-100 pt-5 mt-5">
            <h4 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">
              <Calculator class="w-4 h-4 text-indigo-500" />
              Pemetaan Akun Jurnal (COA)
            </h4>
            
            <div class="space-y-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Akun Aset <span class="text-rose-500">*</span></label>
                <select 
                  v-model="form.coa_asset_id"
                  class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all"
                  required
                >
                  <option value="">-- Pilih Akun Aset --</option>
                  <optgroup label="Aset (Harta)">
                    <option v-for="coa in groupedCoas.aset" :key="coa.id" :value="coa.id">
                      {{ coa.code }} - {{ coa.name }}
                    </option>
                  </optgroup>
                </select>
                <span v-if="form.errors.coa_asset_id" class="text-[10px] font-medium text-rose-500 mt-1 block">{{ form.errors.coa_asset_id }}</span>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Akun Beban Penyusutan <span class="text-rose-500">*</span></label>
                <select 
                  v-model="form.coa_depreciation_expense_id"
                  class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all"
                  required
                >
                  <option value="">-- Pilih Akun Beban --</option>
                  <optgroup label="Beban (Pengeluaran)">
                    <option v-for="coa in groupedCoas.beban" :key="coa.id" :value="coa.id">
                      {{ coa.code }} - {{ coa.name }}
                    </option>
                  </optgroup>
                </select>
                <span v-if="form.errors.coa_depreciation_expense_id" class="text-[10px] font-medium text-rose-500 mt-1 block">{{ form.errors.coa_depreciation_expense_id }}</span>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wide">Akun Akumulasi Penyusutan <span class="text-rose-500">*</span></label>
                <select 
                  v-model="form.coa_accumulated_depreciation_id"
                  class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all"
                  required
                >
                  <option value="">-- Pilih Akun Akumulasi --</option>
                  <optgroup label="Aset (Harta)">
                    <option v-for="coa in groupedCoas.aset" :key="coa.id" :value="coa.id">
                      {{ coa.code }} - {{ coa.name }}
                    </option>
                  </optgroup>
                </select>
                <span v-if="form.errors.coa_accumulated_depreciation_id" class="text-[10px] font-medium text-rose-500 mt-1 block">{{ form.errors.coa_accumulated_depreciation_id }}</span>
              </div>
            </div>
          </div>

          <div class="pt-4 border-t border-slate-100 flex justify-end gap-3 mt-6">
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
              {{ form.processing ? 'Menyimpan...' : 'Simpan Aset' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </AuthenticatedLayout>
</template>
