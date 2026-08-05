<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import Modal from '@/Components/Modal.vue';
import axios from 'axios';

const props = defineProps({
  revenue_total: Number,
  piutang_total: Number,
  expense_total: Number,
  net_profit: Number,
  chart_data: Object,
  revenue_by_category: Array,
  expense_by_category: Array,
  period: String,
  top_customers: Array,
  active_domains: Number,
  renewal_margin: Number,
  upcoming_renewals: Array,
});

const formatRupiah = (angka) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka || 0)
}
const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const updatePeriod = (event) => {
    router.get(route('executive.dashboard'), { period: event.target.value }, { preserveState: true });
}

// Chart Options
const chartOptions = ref({
  chart: {
    type: 'area',
    height: 350,
    fontFamily: 'Inter, sans-serif',
    toolbar: { show: false },
    background: 'transparent'
  },
  colors: ['#34d399', '#f43f5e'],
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 2 },
  fill: {
    type: 'gradient',
    gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.1, stops: [0, 90, 100] }
  },
  xaxis: {
    categories: props.chart_data.labels,
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: { style: { colors: '#94a3b8' } }
  },
  yaxis: {
    labels: {
      style: { colors: '#94a3b8' },
      formatter: (value) => {
        if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + 'M'
        return 'Rp ' + (value / 1000).toFixed(0) + 'K'
      }
    }
  },
  grid: { borderColor: 'rgba(148, 163, 184, 0.2)', strokeDashArray: 4 },
  legend: {
    position: 'top',
    horizontalAlign: 'right',
    labels: { colors: '#64748b' }
  },
  theme: { mode: 'light' },
  markers: {
    size: 4,
    hover: { size: 6 }
  },
  events: {
    dataPointSelection: (event, chartContext, config) => {
      const index = config.dataPointIndex;
      if (index >= 0) {
        updateDonutCharts(index);
      }
    }
  }
});

const chartSeries = ref([
  {
    name: 'Pendapatan',
    data: props.chart_data.revenue
  },
  {
    name: 'Pengeluaran',
    data: props.chart_data.expense
  }
]);

// Donut Chart Configs
const donutOptions = (labels, colors, typeStr) => ({
  chart: {
    type: 'donut',
    fontFamily: 'Inter, sans-serif',
    background: 'transparent',
    events: {
      dataPointSelection: (event, chartContext, config) => {
         const category = config.w.config.labels[config.dataPointIndex];
         fetchBreakdownDetails(category);
      }
    }
  },
  labels: labels,
  colors: colors,
  stroke: { show: false },
  dataLabels: { enabled: false },
  legend: {
    position: 'bottom',
    labels: { colors: '#64748b' }
  },
  plotOptions: {
    pie: {
      donut: {
        size: '75%',
        labels: {
          show: true,
          name: { show: true, color: '#64748b' },
          value: {
            show: true,
            color: '#0f172a',
            formatter: (val) => 'Rp ' + (val / 1000000).toFixed(1) + 'M'
          }
        }
      }
    }
  },
  tooltip: {
    y: {
      formatter: (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val)
    }
  }
});

const selectedLabel = ref('Keseluruhan Waktu');
const selectedBounds = ref({ start: null, end: null });

const revenueOptions = ref(donutOptions(props.revenue_by_category.map(item => item.category), ['#34d399', '#38bdf8', '#fbbf24', '#f472b6', '#818cf8']));
const revenueDataSeries = ref(props.revenue_by_category.map(item => item.total));

const expenseOptions = ref(donutOptions(props.expense_by_category.map(item => item.category), ['#f43f5e', '#fb923c', '#a78bfa', '#2dd4bf', '#fb7185', '#94a3b8']));
const expenseDataSeries = ref(props.expense_by_category.map(item => item.total));

const updateDonutCharts = (index) => {
  if (index === null || index === undefined) {
    selectedLabel.value = 'Keseluruhan Waktu';
    selectedBounds.value = { start: null, end: null };
    
    revenueOptions.value = donutOptions(props.revenue_by_category.map(item => item.category), ['#34d399', '#38bdf8', '#fbbf24', '#f472b6', '#818cf8']);
    revenueDataSeries.value = props.revenue_by_category.map(item => item.total);
    
    expenseOptions.value = donutOptions(props.expense_by_category.map(item => item.category), ['#f43f5e', '#fb923c', '#a78bfa', '#2dd4bf', '#fb7185', '#94a3b8']);
    expenseDataSeries.value = props.expense_by_category.map(item => item.total);
  } else {
    selectedLabel.value = props.chart_data.labels[index];
    if (props.chart_data.bounds) {
        selectedBounds.value = props.chart_data.bounds[index];
    }
    
    const revData = props.chart_data.revenue_categories[index] || [];
    revenueOptions.value = donutOptions(revData.map(item => item.category), ['#34d399', '#38bdf8', '#fbbf24', '#f472b6', '#818cf8']);
    revenueDataSeries.value = revData.map(item => item.total);
    
    const expData = props.chart_data.expense_categories[index] || [];
    expenseOptions.value = donutOptions(expData.map(item => item.category), ['#f43f5e', '#fb923c', '#a78bfa', '#2dd4bf', '#fb7185', '#94a3b8']);
    expenseDataSeries.value = expData.map(item => item.total);
  }
}

const resetSelection = () => {
  updateDonutCharts(null);
}

const breakdownModalOpen = ref(false);
const breakdownData = ref([]);
const breakdownCategory = ref('');
const breakdownLoading = ref(false);

const fetchBreakdownDetails = (category) => {
    breakdownCategory.value = category;
    breakdownModalOpen.value = true;
    breakdownLoading.value = true;
    breakdownData.value = [];

    const params = { category: category };
    if (selectedBounds.value.start && selectedBounds.value.end) {
        params.start = selectedBounds.value.start;
        params.end = selectedBounds.value.end;
    }

    axios.get('/executive/dashboard/breakdown', { params })
        .then(res => {
            breakdownData.value = res.data;
        })
        .catch(err => {
            console.error('Failed to fetch breakdown details', err);
        })
        .finally(() => {
            breakdownLoading.value = false;
        });
};

</script>

<template>
  <Head title="Executive Dashboard" />

  <AuthenticatedLayout>
    <div class="max-w-7xl mx-auto">
        <div class="space-y-6 bg-white rounded-3xl p-8 shadow-sm border border-slate-200/80 relative overflow-hidden">
          <!-- Background decoration -->
          <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-50 rounded-full blur-3xl"></div>
          <div class="absolute bottom-0 left-0 w-full h-1/2 bg-gradient-to-t from-white to-transparent pointer-events-none"></div>
          
          <div class="relative z-10">
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 tracking-tight mb-1">
                        Executive Dashboard
                    </h2>
                    <p class="text-sm text-slate-500">Ringkasan performa finansial dan operasional perusahaan.</p>
                </div>
                <div>
                    <select :value="period" @change="updatePeriod" class="bg-white border border-slate-300 text-slate-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 shadow-sm">
                        <option value="daily">Harian (7 Hari Terakhir)</option>
                        <option value="weekly">Mingguan (4 Minggu Terakhir)</option>
                        <option value="monthly">Bulanan (6 Bulan Terakhir)</option>
                    </select>
                </div>
            </div>

            <!-- 4 Financial KPI Cards -->
            <div class="grid grid-cols-4 gap-4 mb-8">
              <div class="bg-white border border-slate-200 p-5 rounded-2xl hover:border-emerald-500/50 hover:shadow-md transition-all">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Pendapatan</p>
                <h3 class="text-2xl font-black text-emerald-600">{{ formatRupiah(revenue_total) }}</h3>
              </div>
              <div class="bg-white border border-slate-200 p-5 rounded-2xl hover:border-amber-500/50 hover:shadow-md transition-all">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Piutang</p>
                <h3 class="text-2xl font-black text-amber-500">{{ formatRupiah(piutang_total) }}</h3>
              </div>
              <div class="bg-white border border-slate-200 p-5 rounded-2xl hover:border-rose-500/50 hover:shadow-md transition-all">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Pengeluaran</p>
                <h3 class="text-2xl font-black text-rose-500">{{ formatRupiah(expense_total) }}</h3>
              </div>
              <div class="bg-gradient-to-br from-indigo-600 to-indigo-800 border border-indigo-700 p-5 rounded-2xl relative overflow-hidden group hover:shadow-lg transition-all">
                <div class="absolute inset-0 bg-white/5 group-hover:bg-white/10 transition-colors"></div>
                <div class="relative z-10">
                  <p class="text-xs font-bold text-indigo-200 uppercase tracking-wider mb-1">Net Profit</p>
                  <h3 class="text-2xl font-black text-white">{{ formatRupiah(net_profit) }}</h3>
                </div>
              </div>
            </div>

            <!-- Chart Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
              <!-- Main Line Chart -->
              <div class="lg:col-span-2 bg-slate-50 border border-slate-200 p-5 rounded-2xl">
                  <div class="flex justify-between items-center mb-4">
                    <h4 class="text-sm font-bold text-slate-800">Grafik Arus Kas (Pendapatan vs Pengeluaran)</h4>
                    <p class="text-xs text-slate-500 italic">Klik pada titik grafik untuk melihat rincian</p>
                  </div>
                  <div class="-ml-2">
                      <VueApexCharts type="area" height="350" :options="chartOptions" :series="chartSeries" />
                  </div>
              </div>
              
              <!-- Category Donut Charts -->
              <div class="flex flex-col gap-6">
                <!-- Header for Selected Period -->
                <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-3 flex justify-between items-center">
                    <span class="text-xs font-semibold text-indigo-800">Rincian Periode: <span class="font-black text-indigo-600 ml-1">{{ selectedLabel }}</span></span>
                    <button v-if="selectedLabel !== 'Keseluruhan Waktu'" @click="resetSelection" class="text-[10px] bg-white text-indigo-600 px-2 py-1 rounded shadow-sm border border-indigo-200 hover:bg-indigo-600 hover:text-white transition-colors">Reset</button>
                </div>

                <!-- Revenue by Category -->
                <div class="bg-slate-50 border border-slate-200 p-5 rounded-2xl flex-1 flex flex-col justify-center relative">
                  <h4 class="text-sm font-bold text-slate-800 mb-2 text-center">Pendapatan per Kategori</h4>
                  <div class="flex justify-center flex-1 items-center">
                      <VueApexCharts v-if="revenueDataSeries.length > 0" type="donut" height="250" :options="revenueOptions" :series="revenueDataSeries" />
                      <p v-else class="text-xs text-slate-400 italic">Belum ada data pendapatan</p>
                  </div>
                </div>
                <!-- Expense by Category -->
                <div class="bg-slate-50 border border-slate-200 p-5 rounded-2xl flex-1 flex flex-col justify-center relative">
                  <h4 class="text-sm font-bold text-slate-800 mb-2 text-center">Pengeluaran per Kategori</h4>
                  <div class="flex justify-center flex-1 items-center">
                      <VueApexCharts v-if="expenseDataSeries.length > 0" type="donut" height="250" :options="expenseOptions" :series="expenseDataSeries" />
                      <p v-else class="text-xs text-slate-400 italic">Belum ada data pengeluaran</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Two Columns for Insights -->
            <div class="grid grid-cols-2 gap-6">
              <!-- Left: Webpraktis Renewal -->
              <div class="space-y-6">
                <!-- Renewal Info -->
                <div class="bg-slate-50 border border-slate-200 p-5 rounded-2xl h-full flex flex-col">
                  <h4 class="text-sm font-bold text-slate-800 mb-4">Layanan & Renewal Webpraktis</h4>
                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <p class="text-xs text-slate-500">Total Aset Aktif</p>
                      <p class="text-xl font-bold text-indigo-600">{{ active_domains }} Layanan</p>
                    </div>
                    <div>
                      <p class="text-xs text-slate-500">Proyeksi Laba Renewal</p>
                      <p class="text-xl font-bold text-emerald-600">{{ formatRupiah(renewal_margin) }}</p>
                    </div>
                  </div>
                  <!-- Upcoming Renewals -->
                  <div class="mt-4 pt-4 border-t border-slate-200 flex-1">
                    <p class="text-xs font-bold text-slate-700 mb-3">Tagihan Jatuh Tempo Terdekat</p>
                    <div class="space-y-2">
                      <div v-for="domain in upcoming_renewals" :key="domain.id" class="flex justify-between items-center text-xs p-2 rounded-lg bg-white border border-slate-100 hover:shadow-sm transition-all">
                        <span class="text-slate-800 font-medium truncate w-32">{{ domain.name }}</span>
                        <span class="text-slate-500 truncate w-24 text-right">{{ domain.customer?.name }}</span>
                        <span class="text-rose-500 font-bold w-20 text-right">{{ formatDate(domain.expired_date) }}</span>
                      </div>
                      <div v-if="!upcoming_renewals.length" class="text-xs text-slate-500 italic p-4 text-center bg-white rounded-lg border border-slate-100">Tidak ada tagihan mendesak.</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Right: Top 5 Customers -->
              <div class="bg-slate-50 border border-slate-200 p-5 rounded-2xl flex flex-col">
                <h4 class="text-sm font-bold text-slate-800 mb-4">Top 5 Klien Terbesar</h4>
                <div class="space-y-3 flex-1">
                  <div v-for="(customer, index) in top_customers" :key="customer.id" class="flex items-center gap-4 p-3 rounded-xl bg-white border border-slate-100 hover:shadow-sm transition-all">
                    <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-bold shrink-0">
                      {{ index + 1 }}
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-bold text-slate-800 truncate">{{ customer.name }}</p>
                      <p class="text-xs text-slate-500 truncate">{{ customer.email }}</p>
                    </div>
                    <div class="text-right shrink-0">
                      <p class="text-sm font-black text-emerald-600">{{ formatRupiah(customer.total_revenue) }}</p>
                    </div>
                  </div>
                  <div v-if="!top_customers.length" class="text-xs text-slate-500 italic text-center mt-10">Belum ada data pendapatan klien.</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Breakdown Modal -->
        <Modal :show="breakdownModalOpen" @close="breakdownModalOpen = false" maxWidth="md">
            <div class="p-6">
                <div class="flex justify-between items-center mb-5">
                    <h2 class="text-lg font-bold text-slate-800">Detail Rincian: {{ breakdownCategory }}</h2>
                    <button @click="breakdownModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <div v-if="breakdownLoading" class="flex justify-center py-10">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                </div>
                
                <div v-else-if="breakdownData.length === 0" class="text-center py-10 text-slate-500 italic">
                    Belum ada data rincian untuk periode ini.
                </div>
                
                <div v-else class="space-y-3 max-h-[60vh] overflow-y-auto pr-2">
                    <div v-for="(item, idx) in breakdownData" :key="idx" class="flex justify-between items-center p-3 rounded-lg border border-slate-100 bg-slate-50 hover:bg-slate-100 transition-colors">
                        <span class="font-medium text-slate-700 text-sm">{{ item.label }}</span>
                        <span class="font-bold text-slate-900 text-sm">{{ formatRupiah(item.total) }}</span>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button @click="breakdownModalOpen = false" class="px-4 py-2 bg-slate-200 text-slate-800 rounded-lg hover:bg-slate-300 transition-colors text-sm font-medium">Tutup</button>
                </div>
            </div>
        </Modal>
    </div>
  </AuthenticatedLayout>
</template>
