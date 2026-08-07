<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, shallowRef, markRaw } from 'vue';
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
  assets_by_category: Array,
  leaves_by_month: Array,
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
const chartOptions = shallowRef(markRaw({
  chart: {
    type: 'area',
    height: 350,
    fontFamily: 'Inter, sans-serif',
    toolbar: { show: false },
    background: 'transparent',
    selection: { enabled: false }
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
  states: {
    active: { filter: { type: 'none' } }
  },
  events: {
    click: (event, chartContext, config) => {
      if (!config || config.dataPointIndex === undefined || config.dataPointIndex < 0) return;
      updateDonutCharts(config.dataPointIndex);
    }
  }
}));

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
const donutOptions = (labels, colors, typeStr) => markRaw({
  chart: {
    type: 'donut',
    fontFamily: 'Inter, sans-serif',
    background: 'transparent',
    selection: { enabled: false },
    events: {}
  },
  states: {
    active: { filter: { type: 'none' } }
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
      expandOnClick: false,
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

const revenueOptions = shallowRef(donutOptions(props.revenue_by_category.map(item => item.category), ['#34d399', '#38bdf8', '#fbbf24', '#f472b6', '#818cf8']));
const revenueDataSeries = shallowRef(props.revenue_by_category.map(item => item.total));

const expenseOptions = shallowRef(donutOptions(props.expense_by_category.map(item => item.category), ['#f43f5e', '#fb923c', '#a78bfa', '#2dd4bf', '#fb7185', '#94a3b8']));
const expenseDataSeries = shallowRef(props.expense_by_category.map(item => item.total));

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

const handleNativeDonutClick = (type, e) => {
    // We capture mousedown to prevent ApexCharts from running its buggy pathMouseDown
    e.stopPropagation();
    let target = e.target;
    // Traverse up slightly just in case clicking on a nested element
    while (target && target.tagName !== 'svg' && target.tagName !== 'div') {
        if (target.tagName && target.tagName.toLowerCase() === 'path' && target.classList.contains('apexcharts-pie-area')) {
            const indexAttr = target.getAttribute('j');
            if (indexAttr !== null && indexAttr !== undefined) {
                const index = parseInt(indexAttr, 10);
                if (!isNaN(index)) {
                    const labels = type === 'revenue' ? revenueOptions.value.labels : expenseOptions.value.labels;
                    const category = labels[index];
                    if (category) {
                        fetchBreakdownDetails(category);
                        return;
                    }
                }
            }
        }
        target = target.parentNode;
    }
};

const resetSelection = () => {
  updateDonutCharts(null);
}

const breakdownModalOpen = ref(false);
const breakdownData = ref([]);
const breakdownCategory = ref('');
const breakdownLoading = ref(false);

function fetchBreakdownDetails(category, extraParams = {}) {
    breakdownCategory.value = category;
    breakdownModalOpen.value = true;
    breakdownLoading.value = true;
    breakdownData.value = [];

    const params = { category: category, ...extraParams };
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
}

// Asset Chart
const assetOptions = shallowRef(markRaw({
  chart: {
    type: 'bar',
    fontFamily: 'Inter, sans-serif',
    toolbar: { show: false },
    selection: { enabled: false },
    events: {
        click: (event, chartContext, config) => {
            if (!config || config.dataPointIndex === undefined || config.dataPointIndex < 0) return;
            if (config.w && config.w.config && config.w.config.xaxis && config.w.config.xaxis.categories) {
                const cat = config.w.config.xaxis.categories[config.dataPointIndex];
                if (cat) fetchBreakdownDetails('Aset', { asset_category: cat });
            }
        }
    }
  },
  states: {
    active: { filter: { type: 'none' } }
  },
  xaxis: { categories: (props.assets_by_category || []).map(i => i.category) },
  plotOptions: { bar: { borderRadius: 4, horizontal: true } },
  dataLabels: { enabled: false },
  colors: ['#3b82f6'],
  tooltip: { y: { formatter: (val) => formatRupiah(val) } }
}));
const assetSeries = shallowRef([{ name: 'Nominal Aset', data: (props.assets_by_category || []).map(i => i.total) }]);

// Leave Chart
const leaveOptions = shallowRef(markRaw({
  chart: {
    type: 'bar',
    fontFamily: 'Inter, sans-serif',
    toolbar: { show: false },
    selection: { enabled: false },
    events: {
        click: (event, chartContext, config) => {
            if (!config || config.dataPointIndex === undefined || config.dataPointIndex < 0) return;
            const dataItem = (props.leaves_by_month || [])[config.dataPointIndex];
            if (dataItem) {
                fetchBreakdownDetails('Cuti', { month: dataItem.month_num, year: dataItem.year });
            }
        }
    }
  },
  states: {
    active: { filter: { type: 'none' } }
  },
  xaxis: { categories: (props.leaves_by_month || []).map(i => i.month) },
  plotOptions: { bar: { borderRadius: 4 } },
  dataLabels: { enabled: false },
  colors: ['#8b5cf6'],
}));
const leaveSeries = shallowRef([{ name: 'Jumlah Pengajuan', data: (props.leaves_by_month || []).map(i => i.count) }]);

</script>

<template>
  <Head title="Executive Dashboard" />

  <AuthenticatedLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="space-y-6 bg-white rounded-3xl p-4 sm:p-6 lg:p-8 shadow-sm border border-slate-200/80 relative overflow-hidden">
          <!-- Background decoration -->
          <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-50 rounded-full blur-3xl"></div>
          <div class="absolute bottom-0 left-0 w-full h-1/2 bg-gradient-to-t from-white to-transparent pointer-events-none"></div>
          
          <div class="relative z-10">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4 mb-6 sm:mb-8">
                <div class="w-full sm:w-auto">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight mb-1">
                        Executive Dashboard
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500">Ringkasan performa finansial dan operasional perusahaan.</p>
                </div>
                <div class="w-full sm:w-auto">
                    <select :value="period" @change="updatePeriod" class="bg-white border border-slate-300 text-slate-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 shadow-sm">
                        <option value="daily">Harian (7 Hari Terakhir)</option>
                        <option value="weekly">Mingguan (4 Minggu Terakhir)</option>
                        <option value="monthly">Bulanan (6 Bulan Terakhir)</option>
                    </select>
                </div>
            </div>

            <!-- 4 Financial KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
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
              <div class="flex flex-col gap-6 lg:col-span-1">
                <!-- Header for Selected Period -->
                <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-3 flex flex-col sm:flex-row justify-between sm:items-center gap-2">
                    <span class="text-xs font-semibold text-indigo-800">Rincian Periode: <span class="font-black text-indigo-600 ml-1">{{ selectedLabel }}</span></span>
                    <button v-if="selectedLabel !== 'Keseluruhan Waktu'" @click="resetSelection" class="text-[10px] bg-white text-indigo-600 px-3 py-1.5 sm:px-2 sm:py-1 rounded shadow-sm border border-indigo-200 hover:bg-indigo-600 hover:text-white transition-colors">Reset Pilihan</button>
                </div>

                <!-- Revenue by Category -->
                <div class="bg-slate-50 border border-slate-200 p-5 rounded-2xl flex-1 flex flex-col justify-center relative">
                  <h4 class="text-sm font-bold text-slate-800 mb-2 text-center">Pendapatan per Kategori</h4>
                  <div class="flex justify-center flex-1 items-center" 
                       @mousedown.capture="handleNativeDonutClick('revenue', $event)"
                       @touchstart.capture="handleNativeDonutClick('revenue', $event)"
                       @pointerdown.capture="handleNativeDonutClick('revenue', $event)">
                      <VueApexCharts v-if="revenueDataSeries.length > 0" type="donut" height="250" :options="revenueOptions" :series="revenueDataSeries" />
                      <p v-else class="text-xs text-slate-400 italic">Belum ada data pendapatan</p>
                  </div>
                </div>
                <!-- Expense by Category -->
                <div class="bg-slate-50 border border-slate-200 p-5 rounded-2xl flex-1 flex flex-col justify-center relative">
                  <h4 class="text-sm font-bold text-slate-800 mb-2 text-center">Pengeluaran per Kategori</h4>
                  <div class="flex justify-center flex-1 items-center"
                       @mousedown.capture="handleNativeDonutClick('expense', $event)"
                       @touchstart.capture="handleNativeDonutClick('expense', $event)"
                       @pointerdown.capture="handleNativeDonutClick('expense', $event)">
                      <VueApexCharts v-if="expenseDataSeries.length > 0" type="donut" height="250" :options="expenseOptions" :series="expenseDataSeries" />
                      <p v-else class="text-xs text-slate-400 italic">Belum ada data pengeluaran</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- New Charts (Assets and Leaves) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
               <div class="bg-slate-50 border border-slate-200 p-5 rounded-2xl">
                  <div class="flex justify-between items-center mb-4">
                    <h4 class="text-sm font-bold text-slate-800">Nominal Aset per Kategori</h4>
                    <p class="text-xs text-slate-500 italic">Klik batang grafik untuk rincian aset</p>
                  </div>
                  <VueApexCharts v-if="assetSeries[0].data.length > 0" type="bar" height="250" :options="assetOptions" :series="assetSeries" />
                  <p v-else class="text-xs text-slate-400 italic text-center py-10">Belum ada data aset</p>
               </div>
               
               <div class="bg-slate-50 border border-slate-200 p-5 rounded-2xl">
                  <div class="flex justify-between items-center mb-4">
                    <h4 class="text-sm font-bold text-slate-800">Trend Pengajuan Cuti (6 Bulan Terakhir)</h4>
                    <p class="text-xs text-slate-500 italic">Klik batang grafik untuk melihat siapa saja</p>
                  </div>
                  <VueApexCharts v-if="leaveSeries[0].data.length > 0" type="bar" height="250" :options="leaveOptions" :series="leaveSeries" />
                  <p v-else class="text-xs text-slate-400 italic text-center py-10">Belum ada data cuti</p>
               </div>
            </div>

            <!-- Two Columns for Insights -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <!-- Left: Webpraktis Renewal -->
              <div class="space-y-6">
                <!-- Renewal Info -->
                <div class="bg-slate-50 border border-slate-200 p-5 rounded-2xl h-full flex flex-col">
                  <h4 class="text-sm font-bold text-slate-800 mb-4">Layanan & Renewal Webpraktis</h4>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                      <div v-for="domain in upcoming_renewals" :key="domain.id" class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1 sm:gap-2 text-xs p-3 sm:p-2 rounded-lg bg-white border border-slate-100 hover:shadow-sm transition-all">
                        <span class="text-slate-800 font-bold sm:font-medium truncate sm:w-32">{{ domain.name }}</span>
                        <span class="text-slate-500 truncate sm:w-24 sm:text-right text-[10px] sm:text-xs">{{ domain.customer?.name }}</span>
                        <span class="text-rose-500 font-bold sm:w-20 sm:text-right mt-1 sm:mt-0">{{ formatDate(domain.expired_date) }}</span>
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
                  <div v-for="(customer, index) in top_customers" :key="customer.id" class="flex flex-wrap sm:flex-nowrap items-center gap-3 sm:gap-4 p-3 rounded-xl bg-white border border-slate-100 hover:shadow-sm transition-all">
                    <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-bold shrink-0">
                      {{ index + 1 }}
                    </div>
                    <div class="flex-1 min-w-[50%]">
                      <p class="text-sm font-bold text-slate-800 truncate">{{ customer.name }}</p>
                      <p class="text-[10px] sm:text-xs text-slate-500 truncate mb-1">{{ customer.email }}</p>
                      <div class="flex flex-wrap items-center gap-1.5 text-[10px]" v-if="customer.invoices && customer.invoices.length > 0">
                        <span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 font-medium whitespace-nowrap">Inv: Sudah</span>
                        <span :class="customer.invoices[0].status === 'paid' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'" class="px-1.5 py-0.5 rounded font-medium whitespace-nowrap">
                          Bayar: {{ customer.invoices[0].status === 'paid' ? 'Sudah' : 'Belum' }}
                        </span>
                      </div>
                      <div class="flex items-center gap-1.5 text-[10px]" v-else>
                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 font-medium whitespace-nowrap">Inv: Belum</span>
                      </div>
                    </div>
                    <div class="w-full sm:w-auto text-right shrink-0 mt-2 sm:mt-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-50 flex justify-between sm:block items-center">
                      <span class="text-[10px] font-bold text-slate-400 sm:hidden uppercase">Total</span>
                      <p class="text-sm sm:text-base font-black text-emerald-600">{{ formatRupiah(customer.total_revenue) }}</p>
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
                
                <div v-else class="space-y-3 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
                    <template v-if="breakdownData[0]?.is_table">
                        <table class="w-full text-left text-xs text-slate-600 border border-slate-200 rounded-xl overflow-hidden">
                            <thead class="bg-slate-100 font-bold text-slate-500">
                                <tr>
                                    <th class="p-2">Nama Aset</th>
                                    <th class="p-2 text-right">Nilai Perolehan</th>
                                    <th class="p-2 text-right">Penyusutan Bln Ini</th>
                                    <th class="p-2 text-right">Nilai Buku Akhir</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-for="(item, idx) in breakdownData" :key="idx" class="hover:bg-slate-50">
                                    <td class="p-2 font-medium text-slate-800">{{ item.asset_name }}</td>
                                    <td class="p-2 text-right text-emerald-600">{{ formatRupiah(item.purchase_price) }}</td>
                                    <td class="p-2 text-right text-rose-500">{{ formatRupiah(item.depreciation_this_month) }}</td>
                                    <td class="p-2 text-right font-bold">{{ formatRupiah(item.book_value) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </template>
                    <template v-else-if="breakdownData[0]?.is_leave_table">
                        <table class="w-full text-left text-xs text-slate-600 border border-slate-200 rounded-xl overflow-hidden">
                            <thead class="bg-slate-100 font-bold text-slate-500">
                                <tr>
                                    <th class="p-2">Pemohon</th>
                                    <th class="p-2">Divisi</th>
                                    <th class="p-2">Tipe Cuti</th>
                                    <th class="p-2 text-center">Tgl Mulai</th>
                                    <th class="p-2 text-center">Durasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-for="(item, idx) in breakdownData" :key="idx" class="hover:bg-slate-50">
                                    <td class="p-2 font-medium text-slate-800">{{ item.applicant_name }}</td>
                                    <td class="p-2">{{ item.division }}</td>
                                    <td class="p-2">{{ item.leave_type }}</td>
                                    <td class="p-2 text-center">{{ item.start_date }}</td>
                                    <td class="p-2 text-center font-bold text-purple-600">{{ item.total_days }} Hari</td>
                                </tr>
                            </tbody>
                        </table>
                    </template>
                    <template v-else>
                        <div v-for="(item, idx) in breakdownData" :key="idx" class="flex justify-between items-center p-3 rounded-lg border border-slate-100 bg-slate-50 hover:bg-slate-100 transition-colors">
                            <span class="font-medium text-slate-700 text-sm">{{ item.label }}</span>
                            <span class="font-bold text-slate-900 text-sm">{{ formatRupiah(item.total) }}</span>
                        </div>
                    </template>
                </div>

                <div class="mt-6 flex justify-end">
                    <button @click="breakdownModalOpen = false" class="px-4 py-2 bg-slate-200 text-slate-800 rounded-lg hover:bg-slate-300 transition-colors text-sm font-medium">Tutup</button>
                </div>
            </div>
        </Modal>
    </div>
  </AuthenticatedLayout>
</template>
