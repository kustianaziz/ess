<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ReportFilters from '@/Components/ReportFilters.vue';

const props = defineProps({
    revenues: Object,
    expenses: Object,
    otherRevenues: Object,
    otherExpenses: Object,
    taxes: Object,
    grossProfit: Number,
    operatingProfit: Number,
    netProfit: Number,
    filters: Object,
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
    filterForm.get(route('accounting.reports.income-statement'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(val || 0);
};

const handleExportPdf = () => {
    window.open(route('accounting.reports.income-statement', { ...filterForm.data(), export: 'pdf' }), '_blank');
};

const handleExportExcel = () => {
    window.open(route('accounting.reports.income-statement', { ...filterForm.data(), export: 'excel' }), '_blank');
};
</script>

<template>
    <Head title="Laba Rugi" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">Laporan Laba Rugi (Income Statement)</h2>
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
                        <h2 class="text-xl font-bold uppercase tracking-wider text-slate-800">Laporan Laba Rugi</h2>
                        <p class="text-slate-500">Periode: {{ filters.start_date }} s/d {{ filters.end_date }}</p>
                    </div>

                    <!-- Revenues -->
                    <div>
                        <h3 class="font-bold text-slate-800 border-b border-slate-200 pb-2 mb-3 text-base">Pendapatan</h3>
                        <div v-for="item in revenues.items" :key="item.code" class="flex justify-between py-1.5 border-b border-slate-50/50" :class="item.is_header ? 'font-bold text-slate-800' : 'text-slate-600'">
                            <span :style="{ paddingLeft: ((item.level - 1) * 1.5) + 'rem' }">{{ filterForm.show_code ? item.code + ' - ' : '' }}{{ item.name }}</span>
                            <span class="font-medium">{{ item.is_header ? '' : formatCurrency(item.balance) }}</span>
                        </div>
                        <div class="flex justify-between py-2 mt-2 font-bold text-slate-800 border-t border-slate-100">
                            <span>Total Pendapatan</span>
                            <span>{{ formatCurrency(revenues.total) }}</span>
                        </div>
                    </div>

                    <!-- Expenses -->
                    <div>
                        <h3 class="font-bold text-slate-800 border-b border-slate-200 pb-2 mb-3 text-base">Harga Pokok & Biaya</h3>
                        <div v-for="item in expenses.items" :key="item.code" class="flex justify-between py-1.5 border-b border-slate-50/50" :class="item.is_header ? 'font-bold text-slate-800' : 'text-slate-600'">
                            <span :style="{ paddingLeft: ((item.level - 1) * 1.5) + 'rem' }">{{ filterForm.show_code ? item.code + ' - ' : '' }}{{ item.name }}</span>
                            <span class="font-medium">{{ item.is_header ? '' : formatCurrency(item.balance) }}</span>
                        </div>
                        <div class="flex justify-between py-2 mt-2 font-bold text-slate-800 border-t border-slate-100">
                            <span>Total Harga Pokok & Biaya</span>
                            <span>{{ formatCurrency(expenses.total) }}</span>
                        </div>
                    </div>

                    <!-- Gross Profit -->
                    <div class="flex justify-between py-4 bg-indigo-50/50 px-4 rounded-xl font-black text-indigo-700 text-lg">
                        <span>Laba Kotor</span>
                        <span>{{ formatCurrency(grossProfit) }}</span>
                    </div>

                    <!-- Other Revenues -->
                    <div>
                        <h3 class="font-bold text-slate-800 border-b border-slate-200 pb-2 mb-3 text-base">Pendapatan Lain-lain</h3>
                        <div v-for="item in otherRevenues.items" :key="item.code" class="flex justify-between py-1.5 border-b border-slate-50/50" :class="item.is_header ? 'font-bold text-slate-800' : 'text-slate-600'">
                            <span :style="{ paddingLeft: ((item.level - 1) * 1.5) + 'rem' }">{{ filterForm.show_code ? item.code + ' - ' : '' }}{{ item.name }}</span>
                            <span class="font-medium">{{ item.is_header ? '' : formatCurrency(item.balance) }}</span>
                        </div>
                        <div class="flex justify-between py-2 mt-2 font-bold text-slate-800 border-t border-slate-100">
                            <span>Total Pendapatan Lain-lain</span>
                            <span>{{ formatCurrency(otherRevenues.total) }}</span>
                        </div>
                    </div>

                    <!-- Other Expenses -->
                    <div>
                        <h3 class="font-bold text-slate-800 border-b border-slate-200 pb-2 mb-3 text-base">Beban Lain-lain</h3>
                        <div v-for="item in otherExpenses.items" :key="item.code" class="flex justify-between py-1.5 border-b border-slate-50/50" :class="item.is_header ? 'font-bold text-slate-800' : 'text-slate-600'">
                            <span :style="{ paddingLeft: ((item.level - 1) * 1.5) + 'rem' }">{{ filterForm.show_code ? item.code + ' - ' : '' }}{{ item.name }}</span>
                            <span class="font-medium">{{ item.is_header ? '' : formatCurrency(item.balance) }}</span>
                        </div>
                        <div class="flex justify-between py-2 mt-2 font-bold text-slate-800 border-t border-slate-100">
                            <span>Total Beban Lain-lain</span>
                            <span>{{ formatCurrency(otherExpenses.total) }}</span>
                        </div>
                    </div>

                    <!-- Operating Profit -->
                    <div class="flex justify-between py-4 bg-sky-50/50 px-4 rounded-xl font-black text-sky-700 text-lg">
                        <span>Laba Operasi</span>
                        <span>{{ formatCurrency(operatingProfit) }}</span>
                    </div>

                    <!-- Taxes -->
                    <div>
                        <h3 class="font-bold text-slate-800 border-b border-slate-200 pb-2 mb-3 text-base">Pajak</h3>
                        <div v-for="item in taxes.items" :key="item.code" class="flex justify-between py-1.5 border-b border-slate-50/50" :class="item.is_header ? 'font-bold text-slate-800' : 'text-slate-600'">
                            <span :style="{ paddingLeft: ((item.level - 1) * 1.5) + 'rem' }">{{ filterForm.show_code ? item.code + ' - ' : '' }}{{ item.name }}</span>
                            <span class="font-medium">{{ item.is_header ? '' : formatCurrency(item.balance) }}</span>
                        </div>
                        <div class="flex justify-between py-2 mt-2 font-bold text-slate-800 border-t border-slate-100">
                            <span>Total Pajak</span>
                            <span>{{ formatCurrency(taxes.total) }}</span>
                        </div>
                    </div>

                    <!-- Net Profit -->
                    <div class="flex justify-between py-6 bg-emerald-50 px-6 rounded-2xl font-black text-emerald-700 text-xl shadow-inner border border-emerald-100">
                        <span>Laba Bersih (Setelah Pajak)</span>
                        <span>{{ formatCurrency(netProfit) }}</span>
                    </div>

                </div>
            </div>
        </div>
        </div>
    </AuthenticatedLayout>
</template>
