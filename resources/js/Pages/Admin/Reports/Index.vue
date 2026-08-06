<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { BarChart3, FileSpreadsheet, FileText, Calendar, Utensils, Download, Filter, RotateCcw, Building2, Layers, ListFilter, Zap, RefreshCcw } from 'lucide-vue-next';

const props = defineProps({
  stats: Object,
  divisionSummary: Array,
  detailList: Array,
  divisions: Array,
  filters: Object,
});

const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');
const type = ref(props.filters?.type || 'all');
const status = ref(props.filters?.status || 'all');
const divisionId = ref(props.filters?.division_id || 'all');
const activeTab = ref('all'); // 'all', 'rekap', 'list'

const applyFilter = () => {
  router.get(
    route('admin.reports.index'),
    {
      start_date: startDate.value,
      end_date: endDate.value,
      type: type.value,
      status: status.value,
      division_id: divisionId.value,
    },
    { preserveState: true, replace: true }
  );
};

const resetFilter = () => {
  startDate.value = '';
  endDate.value = '';
  type.value = 'all';
  status.value = 'all';
  divisionId.value = 'all';
  applyFilter();
};

const exportExcelUrl = computed(() => {
  const params = new URLSearchParams({
    start_date: startDate.value,
    end_date: endDate.value,
    type: type.value,
    status: status.value,
    division_id: divisionId.value,
  }).toString();
  return route('admin.reports.export-excel') + '?' + params;
});

const exportWordUrl = computed(() => {
  const params = new URLSearchParams({
    start_date: startDate.value,
    end_date: endDate.value,
    type: type.value,
    status: status.value,
    division_id: divisionId.value,
  }).toString();
  return route('admin.reports.export-word') + '?' + params;
});
</script>

<template>
  <Head title="Laporan & Agregasi ESS" />

  <AuthenticatedLayout>
    <div class="space-y-6 max-w-7xl mx-auto">
      <!-- Header Bar with Export Actions -->
      <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
            <BarChart3 class="w-7 h-7 text-indigo-600" />
            Laporan Rekapitulasi & List ESS
          </h1>
          <p class="text-xs text-slate-500 mt-1">
            Laporan agregasi rekapitulasi divisi serta daftar detail pengajuan dengan fitur Export Excel & Word.
          </p>
        </div>

        <!-- Export Buttons -->
        <div class="flex flex-col sm:flex-row items-stretch gap-3 w-full md:w-auto mt-4 md:mt-0">
          <a
            :href="exportExcelUrl"
            target="_blank"
            class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-lg shadow-emerald-600/20 transition-all flex items-center justify-center gap-2"
          >
            <FileSpreadsheet class="w-4 h-4" />
            <span>Export Excel</span>
          </a>

          <a
            :href="exportWordUrl"
            target="_blank"
            class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-lg shadow-blue-600/20 transition-all flex items-center justify-center gap-2"
          >
            <FileText class="w-4 h-4" />
            <span>Export Word</span>
          </a>
        </div>
      </div>

      <!-- Filters Container -->
      <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
            <Filter class="w-4 h-4 text-indigo-600" />
            Filter Laporan
          </h3>

          <button
            @click="resetFilter"
            class="text-xs text-slate-500 hover:text-indigo-600 font-semibold flex items-center gap-1 transition-colors"
          >
            <RotateCcw class="w-3.5 h-3.5" />
            <span>Reset Filter</span>
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
          <!-- Filter Tanggal Mulai -->
          <div>
            <label class="block text-[11px] font-bold text-slate-600 mb-1">Dari Tanggal</label>
            <input
              type="date"
              v-model="startDate"
              @change="applyFilter"
              class="w-full text-xs border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 bg-slate-50/50"
            />
          </div>

          <!-- Filter Tanggal Akhir -->
          <div>
            <label class="block text-[11px] font-bold text-slate-600 mb-1">Sampai Tanggal</label>
            <input
              type="date"
              v-model="endDate"
              @change="applyFilter"
              class="w-full text-xs border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 bg-slate-50/50"
            />
          </div>

          <!-- Filter Layanan -->
          <div>
            <label class="block text-[11px] font-bold text-slate-600 mb-1">Jenis Layanan</label>
            <select
              v-model="type"
              @change="applyFilter"
              class="w-full text-xs border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 bg-slate-50/50"
            >
              <option value="all">Semua Layanan</option>
              <option value="reimbursement">Reimbursement</option>
              <option value="operasional">Operasional / Konsumsi</option>
              <option value="cuti">Cuti Karyawan</option>
              <option value="perjalanan-dinas">Perjalanan Dinas</option>
              <option value="tagihan-bulanan">Tagihan Bulanan</option>
              <option value="renewal-domain">Renewal Domain</option>
            </select>
          </div>

          <!-- Filter Divisi -->
          <div>
            <label class="block text-[11px] font-bold text-slate-600 mb-1">Divisi Karyawan</label>
            <select
              v-model="divisionId"
              @change="applyFilter"
              class="w-full text-xs border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 bg-slate-50/50"
            >
              <option value="all">Semua Divisi</option>
              <option v-for="d in divisions" :key="d.id" :value="d.id">{{ d.name }}</option>
            </select>
          </div>

          <!-- Filter Status -->
          <div>
            <label class="block text-[11px] font-bold text-slate-600 mb-1">Status Pengajuan</label>
            <select
              v-model="status"
              @change="applyFilter"
              class="w-full text-xs border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 bg-slate-50/50"
            >
              <option value="all">Semua Status</option>
              <option value="submitted">Menunggu Persetujuan</option>
              <option value="approved">Disetujui</option>
              <option value="paid">Sudah Dibayarkan</option>
              <option value="completed">Selesai</option>
              <option value="rejected">Ditolak</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Stat Cards Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-5">
        <!-- Reimbursement Stat Card -->
        <div class="bg-gradient-to-br from-emerald-50 to-white p-5 rounded-2xl border border-emerald-200/80 shadow-sm">
          <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center shadow-md shadow-emerald-500/20">
              <FileText class="w-5 h-5" />
            </div>
            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-md">
              Reimbursement
            </span>
          </div>
          <div class="space-y-1">
            <p class="text-xs text-slate-500 font-semibold">Total Claim Disetujui / Paid</p>
            <h3 class="text-2xl font-black text-slate-900">
              Rp {{ (stats.total_reimbursement_amount || 0).toLocaleString('id-ID') }}
            </h3>
          </div>
          <p class="text-xs text-slate-400 mt-3 border-t border-emerald-100/80 pt-2 font-medium">
            Total {{ stats.total_reimbursement_count }} transaksi pengajuan
          </p>
        </div>

        <!-- Operational Stat Card -->
        <div class="bg-gradient-to-br from-orange-50 to-white p-5 rounded-2xl border border-orange-200/80 shadow-sm">
          <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-orange-500 text-white flex items-center justify-center shadow-md shadow-orange-500/20">
              <Utensils class="w-5 h-5" />
            </div>
            <span class="text-[10px] font-bold text-orange-700 bg-orange-100 px-2 py-0.5 rounded-md">
              Operasional
            </span>
          </div>
          <div class="space-y-1">
            <p class="text-xs text-slate-500 font-semibold">Total Biaya Operasional Disetujui</p>
            <h3 class="text-2xl font-black text-slate-900">
              Rp {{ (stats.total_operational_amount || 0).toLocaleString('id-ID') }}
            </h3>
          </div>
          <p class="text-xs text-slate-400 mt-3 border-t border-orange-100/80 pt-2 font-medium">
            Total {{ stats.total_operational_count }} transaksi pengajuan
          </p>
        </div>

        <!-- Leave Stat Card -->
        <div class="bg-gradient-to-br from-purple-50 to-white p-5 rounded-2xl border border-purple-200/80 shadow-sm">
          <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center shadow-md shadow-purple-600/20">
              <Calendar class="w-5 h-5" />
            </div>
            <span class="text-[10px] font-bold text-purple-700 bg-purple-100 px-2 py-0.5 rounded-md">
              Cuti Karyawan
            </span>
          </div>
          <div class="space-y-1">
            <p class="text-xs text-slate-500 font-semibold">Total Hari Cuti Disetujui</p>
            <h3 class="text-2xl font-black text-slate-900">
              {{ stats.total_leave_days || 0 }} Hari Kerja
            </h3>
          </div>
          <p class="text-xs text-slate-400 mt-3 border-t border-purple-100/80 pt-2 font-medium">
            Total {{ stats.total_leave_count }} transaksi pengajuan
          </p>
        </div>

        <!-- Business Trip Stat Card -->
        <div class="bg-gradient-to-br from-blue-50 to-white p-5 rounded-2xl border border-blue-200/80 shadow-sm">
          <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-600/20">
              <FileText class="w-5 h-5" />
            </div>
            <span class="text-[10px] font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded-md">
              Perjalanan Dinas
            </span>
          </div>
          <div class="space-y-1">
            <p class="text-xs text-slate-500 font-semibold">Total Biaya Disetujui</p>
            <h3 class="text-2xl font-black text-slate-900">
              Rp {{ (stats.total_business_trip_amount || 0).toLocaleString('id-ID') }}
            </h3>
          </div>
          <p class="text-xs text-slate-400 mt-3 border-t border-blue-100/80 pt-2 font-medium">
            Total {{ stats.total_business_trip_count }} transaksi pengajuan
          </p>
        </div>
      </div>

      <!-- Tab View Selection (Rekap & Detail List) -->
      <div class="bg-white p-2 rounded-2xl border border-slate-100 shadow-sm flex flex-col md:flex-row items-stretch md:items-center gap-2">
        <button
          @click="activeTab = 'all'"
          class="flex-1 py-2 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2"
          :class="activeTab === 'all' ? 'bg-slate-900 text-white shadow-md' : 'text-slate-600 hover:bg-slate-50'"
        >
          <Layers class="w-4 h-4" />
          <span>Semua (Rekap & List)</span>
        </button>

        <button
          @click="activeTab = 'rekap'"
          class="flex-1 py-2 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2"
          :class="activeTab === 'rekap' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-50'"
        >
          <Building2 class="w-4 h-4" />
          <span>Tabel Divisi</span>
        </button>

        <button
          @click="activeTab = 'list'"
          class="flex-1 py-2 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2"
          :class="activeTab === 'list' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-50'"
        >
          <ListFilter class="w-4 h-4" />
          <span>List Detail</span>
        </button>
      </div>

      <!-- SECTION 1: REKAPITULASI DIVISI TABLE -->
      <div v-if="activeTab === 'all' || activeTab === 'rekap'" class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden space-y-3 p-5">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <Building2 class="w-4 h-4 text-indigo-600" />
            I. Tabel Rekapitulasi Pengajuan Per Divisi
          </h3>
          <span class="text-xs text-slate-400 font-medium">Total {{ divisionSummary.length }} Divisi Terlibat</span>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
          <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-slate-50 border-b border-slate-100 uppercase text-[10px] font-bold text-slate-400 tracking-wider">
              <tr>
                <th class="py-3 px-4">Nama Divisi</th>
                <th class="py-3 px-4 text-center">Total Transaksi</th>
                <th class="py-3 px-4 text-right">Reimbursement (Rp)</th>
                <th class="py-3 px-4 text-right">Operasional (Rp)</th>
                <th class="py-3 px-4 text-center">Cuti (Hari)</th>
                <th class="py-3 px-4 text-right">Dinas (Rp)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr v-for="div in divisionSummary" :key="div.division_name" class="hover:bg-slate-50/70">
                <td class="py-3 px-4 font-bold text-slate-800">{{ div.division_name }}</td>
                <td class="py-3 px-4 text-center font-bold text-slate-900">{{ div.total_requests }} Transaksi</td>
                <td class="py-3 px-4 text-right font-bold text-emerald-600">
                  Rp {{ div.reimbursement_sum.toLocaleString('id-ID') }}
                </td>
                <td class="py-3 px-4 text-right font-bold text-orange-600">
                  Rp {{ div.operational_sum.toLocaleString('id-ID') }}
                </td>
                <td class="py-3 px-4 text-center font-bold text-purple-600">
                  {{ div.leave_days_sum }} Hari
                </td>
                <td class="py-3 px-4 text-right font-bold text-blue-600">
                  Rp {{ div.business_trip_sum.toLocaleString('id-ID') }}
                </td>
              </tr>
              <tr v-if="divisionSummary.length === 0">
                <td colspan="5" class="py-8 text-center text-slate-400 font-medium">
                  Belum ada data rekapitulasi divisi.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- SECTION 2: DAFTAR DETAIL LIST PENGAJUAN -->
      <div v-if="activeTab === 'all' || activeTab === 'list'" class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden space-y-3 p-5">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <ListFilter class="w-4 h-4 text-indigo-600" />
            II. Daftar Detail List Transaksi Pengajuan
          </h3>
          <span class="text-xs text-slate-400 font-medium">Total {{ detailList.length }} Items</span>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
          <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-slate-50 border-b border-slate-100 uppercase text-[10px] font-bold text-slate-400 tracking-wider">
              <tr>
                <th class="py-3 px-4">No. Pengajuan</th>
                <th class="py-3 px-4">Tanggal</th>
                <th class="py-3 px-4">Nama Pemohon & NIK</th>
                <th class="py-3 px-4">Divisi</th>
                <th class="py-3 px-4">Layanan & Kategori</th>
                <th class="py-3 px-4 text-right">Nominal / Durasi</th>
                <th class="py-3 px-4 text-center">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr v-for="item in detailList" :key="item.type + item.id" class="hover:bg-slate-50/70">
                <td class="py-3 px-4 font-bold text-slate-900">{{ item.request_number }}</td>
                <td class="py-3 px-4 text-slate-500 whitespace-nowrap">{{ item.date }}</td>
                <td class="py-3 px-4">
                  <span class="font-bold text-slate-800 block">{{ item.applicant_name }}</span>
                  <span class="text-[10px] text-slate-400">NIK: {{ item.applicant_nik }}</span>
                </td>
                <td class="py-3 px-4 text-slate-700 font-semibold">{{ item.division_name }}</td>
                <td class="py-3 px-4">
                  <span class="font-bold text-slate-800 block">{{ item.type_label }}</span>
                  <span class="text-[10px] text-slate-400">{{ item.category }}</span>
                </td>
                <td class="py-3 px-4 text-right font-black text-slate-900">
                  {{ item.amount_formatted }}
                </td>
                <td class="py-3 px-4 text-center">
                  <StatusBadge :status="item.status" />
                </td>
              </tr>
              <tr v-if="detailList.length === 0">
                <td colspan="7" class="py-12 text-center text-slate-400 font-medium">
                  Tidak ada transaksi pengajuan yang cocok dengan filter laporan saat ini.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </AuthenticatedLayout>
</template>
