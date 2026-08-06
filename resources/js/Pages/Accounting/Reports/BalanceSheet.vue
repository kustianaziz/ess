<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ReportFilters from '@/Components/ReportFilters.vue';

const props = defineProps({
    filters: Object,
    assets: Object,
    liabilities: Object,
    equities: Object,
    maxLevel: { type: Number, default: 5 },
});

const filterForm = useForm({
    as_of_date: props.filters.as_of_date || '',
    level: props.filters.level || 5,
    show_zero: props.filters.show_zero,
    show_code: props.filters.show_code,
});

const applyFilter = () => {
    filterForm.get(route('accounting.reports.balance-sheet'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(val || 0);
};

const handleExportPdf = () => {
    window.open(route('accounting.reports.balance-sheet', { ...filterForm, export: 'pdf' }), '_blank');
};

const handleExportExcel = () => {
    window.open(route('accounting.reports.balance-sheet', { ...filterForm, export: 'excel' }), '_blank');
};
</script>

<template>
    <Head title="Neraca" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">Neraca (Balance Sheet)</h2>
        </template>
    
        <div class="p-6">
        <div class="max-w-7xl mx-auto space-y-6">
            
            <ReportFilters 
                :form="filterForm" 
                :maxLevel="props.maxLevel" 
                :showAsOfDate="true"
                @apply="applyFilter"
                @exportPdf="handleExportPdf"
                @exportExcel="handleExportExcel"
            />

            <div class="bg-white rounded-3xl shadow-lg border border-slate-100 overflow-hidden">
                <div class="p-8 text-sm">
                    <div class="text-center mb-10">
                        <h2 class="text-2xl font-black uppercase tracking-widest text-slate-800">NERACA</h2>
                        <p class="text-slate-500 font-medium">Per Tanggal: {{ filters.as_of_date }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                        <!-- Left Side: Assets -->
                        <div class="space-y-6">
                            <div>
                                <h3 class="font-black text-slate-800 border-b-2 border-indigo-200 pb-2 mb-4 text-lg text-indigo-900 uppercase">AKTIVA (Aset)</h3>
                                <div v-for="item in assets.items" :key="item.code" class="flex justify-between py-2 border-b border-slate-50/50" :class="item.is_header ? 'font-bold text-slate-800' : 'text-slate-600'">
                                    <span :style="{ paddingLeft: ((item.level - 1) * 1.5) + 'rem' }">{{ filterForm.show_code ? item.code + ' - ' : '' }}{{ item.name }}</span>
                                    <span class="font-medium">{{ item.is_header ? '' : formatCurrency(item.balance) }}</span>
                                </div>
                            </div>

                            <div class="flex justify-between py-4 bg-indigo-50 px-4 rounded-xl font-black text-indigo-700 text-lg">
                                <span>Total Aktiva</span>
                                <span>{{ formatCurrency(assets.total) }}</span>
                            </div>
                        </div>

                        <!-- Right Side: Liabilities & Equities -->
                        <div class="space-y-8">
                            <div>
                                <h3 class="font-black text-slate-800 border-b-2 border-rose-200 pb-2 mb-4 text-lg text-rose-900 uppercase">PASIVA (Kewajiban)</h3>
                                <div v-for="item in liabilities.items" :key="item.code" class="flex justify-between py-2 border-b border-slate-50/50" :class="item.is_header ? 'font-bold text-slate-800' : 'text-slate-600'">
                                    <span :style="{ paddingLeft: ((item.level - 1) * 1.5) + 'rem' }">{{ filterForm.show_code ? item.code + ' - ' : '' }}{{ item.name }}</span>
                                    <span class="font-medium">{{ item.is_header ? '' : formatCurrency(item.balance) }}</span>
                                </div>
                                <div class="flex justify-between py-3 mt-2 font-bold text-rose-700 bg-rose-50 px-3 rounded-lg">
                                    <span>Total Kewajiban</span>
                                    <span>{{ formatCurrency(liabilities.total) }}</span>
                                </div>
                            </div>

                            <div>
                                <h3 class="font-black text-slate-800 border-b-2 border-emerald-200 pb-2 mb-4 text-lg text-emerald-900 uppercase">EKUITAS (Modal)</h3>
                                <div v-for="item in equities.items" :key="item.code" class="flex justify-between py-2 border-b border-slate-50/50" :class="item.is_header ? 'font-bold text-slate-800' : 'text-slate-600'">
                                    <span :style="{ paddingLeft: ((item.level - 1) * 1.5) + 'rem' }">{{ filterForm.show_code ? item.code + ' - ' : '' }}{{ item.name }}</span>
                                    <span class="font-medium">{{ item.is_header ? '' : formatCurrency(item.balance) }}</span>
                                </div>
                                <div class="flex justify-between py-3 mt-2 font-bold text-emerald-700 bg-emerald-50 px-3 rounded-lg">
                                    <span>Total Ekuitas</span>
                                    <span>{{ formatCurrency(equities.total) }}</span>
                                </div>
                            </div>

                            <div class="flex justify-between py-4 bg-slate-800 px-4 rounded-xl font-black text-white text-lg shadow-lg">
                                <span>Total Kewajiban + Ekuitas</span>
                                <span>{{ formatCurrency(liabilities.total + equities.total) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </AuthenticatedLayout>
</template>
