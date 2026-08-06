<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ReportFilters from '@/Components/ReportFilters.vue';

const props = defineProps({
    filters: Object,
    operatingActivities: Array,
    investingActivities: Array,
    financingActivities: Array,
    operatingTotal: Number,
    investingTotal: Number,
    financingTotal: Number,
    netIncrease: Number,
    beginningCash: Number,
    endingCash: Number,
    maxLevel: { type: Number, default: 5 },
});

const filterForm = useForm({
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    level: props.filters.level || 5,
    show_zero: props.filters.show_zero,
    show_code: props.filters.show_code,
});

const applyFilter = () => {
    filterForm.get(route('accounting.reports.cash-flow'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(val || 0);
};

const handleExportPdf = () => {
    window.open(route('accounting.reports.cash-flow', { ...filterForm, export: 'pdf' }), '_blank');
};

const handleExportExcel = () => {
    window.open(route('accounting.reports.cash-flow', { ...filterForm, export: 'excel' }), '_blank');
};
</script>

<template>
    <Head title="Arus Kas" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">Laporan Arus Kas (Cash Flow)</h2>
        </template>
    
        <div class="p-6">
        <div class="max-w-7xl mx-auto space-y-6">
            
            <ReportFilters 
                :form="filterForm" 
                :maxLevel="props.maxLevel" 
                @apply="applyFilter"
                @exportPdf="handleExportPdf"
                @exportExcel="handleExportExcel"
            />

            <!-- Report Body -->
            <div class="bg-white rounded-3xl shadow-lg border border-slate-100 overflow-hidden">
                <div class="p-8 space-y-8 text-sm">
                    <div class="text-center mb-8">
                        <h2 class="text-xl font-bold uppercase tracking-wider text-slate-800">Laporan Arus Kas</h2>
                        <p class="text-slate-500">Periode: {{ filters.start_date }} s/d {{ filters.end_date }}</p>
                    </div>

                    <!-- Operating Activities -->
                    <div>
                        <h3 class="font-bold text-slate-800 border-b border-slate-200 pb-2 mb-3 text-base">Arus Kas dari Aktivitas Operasi</h3>
                        <div v-for="(item, idx) in operatingActivities" :key="'op'+idx" class="flex justify-between py-1.5 text-slate-600">
                            <span>{{ item.description }}</span>
                            <span :class="item.amount < 0 ? 'text-rose-600' : 'text-emerald-600'">{{ formatCurrency(item.amount) }}</span>
                        </div>
                        <div class="flex justify-between py-2 mt-2 font-bold text-slate-800 border-t border-slate-100">
                            <span>Kas Bersih dari Aktivitas Operasi</span>
                            <span>{{ formatCurrency(operatingTotal) }}</span>
                        </div>
                    </div>

                    <!-- Investing Activities -->
                    <div>
                        <h3 class="font-bold text-slate-800 border-b border-slate-200 pb-2 mb-3 text-base">Arus Kas dari Aktivitas Investasi</h3>
                        <div v-for="(item, idx) in investingActivities" :key="'inv'+idx" class="flex justify-between py-1.5 text-slate-600">
                            <span>{{ item.description }}</span>
                            <span :class="item.amount < 0 ? 'text-rose-600' : 'text-emerald-600'">{{ formatCurrency(item.amount) }}</span>
                        </div>
                        <div class="flex justify-between py-2 mt-2 font-bold text-slate-800 border-t border-slate-100">
                            <span>Kas Bersih dari Aktivitas Investasi</span>
                            <span>{{ formatCurrency(investingTotal) }}</span>
                        </div>
                    </div>

                    <!-- Financing Activities -->
                    <div>
                        <h3 class="font-bold text-slate-800 border-b border-slate-200 pb-2 mb-3 text-base">Arus Kas dari Aktivitas Pendanaan</h3>
                        <div v-for="(item, idx) in financingActivities" :key="'fin'+idx" class="flex justify-between py-1.5 text-slate-600">
                            <span>{{ item.description }}</span>
                            <span :class="item.amount < 0 ? 'text-rose-600' : 'text-emerald-600'">{{ formatCurrency(item.amount) }}</span>
                        </div>
                        <div class="flex justify-between py-2 mt-2 font-bold text-slate-800 border-t border-slate-100">
                            <span>Kas Bersih dari Aktivitas Pendanaan</span>
                            <span>{{ formatCurrency(financingTotal) }}</span>
                        </div>
                    </div>

                    <!-- Net Increase and Balances -->
                    <div class="space-y-4 pt-6 mt-6 border-t-2 border-slate-200">
                        <div class="flex justify-between py-2 font-bold text-slate-800 text-base">
                            <span>Kenaikan (Penurunan) Bersih Kas</span>
                            <span :class="netIncrease < 0 ? 'text-rose-600' : 'text-emerald-600'">{{ formatCurrency(netIncrease) }}</span>
                        </div>
                        <div class="flex justify-between py-2 text-slate-600">
                            <span>Saldo Kas Awal Periode</span>
                            <span>{{ formatCurrency(beginningCash) }}</span>
                        </div>
                        <div class="flex justify-between py-4 bg-emerald-50 px-4 rounded-xl font-black text-emerald-700 text-lg shadow-inner">
                            <span>Saldo Kas Akhir Periode</span>
                            <span>{{ formatCurrency(endingCash) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </AuthenticatedLayout>
</template>
