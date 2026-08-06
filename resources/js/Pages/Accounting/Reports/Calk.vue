<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import ReportFilters from '@/Components/ReportFilters.vue';
import { ref } from 'vue';
import { ChevronDown, ChevronRight, ListCollapse } from 'lucide-vue-next';

const props = defineProps({
    coas: Array,
    maxLevel: Number,
    filters: Object,
});

const filterForm = useForm({
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    level: props.filters.level || 5,
    show_zero: props.filters.show_zero,
    show_code: props.filters.show_code,
});

const applyFilter = () => {
    filterForm.get(route('accounting.reports.calk'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const handleExportPdf = () => {
    window.open(route('accounting.reports.calk', { ...filterForm.data(), export: 'pdf' }), '_blank');
};

const handleExportExcel = () => {
    window.location.href = route('accounting.reports.calk', { ...filterForm.data(), export: 'excel' });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2 }).format(value);
};

// Expand/collapse logic for transactions
const expandedCoas = ref([]);

const toggleCoa = (coaId) => {
    const index = expandedCoas.value.indexOf(coaId);
    if (index > -1) {
        expandedCoas.value.splice(index, 1);
    } else {
        expandedCoas.value.push(coaId);
    }
};
</script>

<template>
    <Head title="Catatan Atas Laporan Keuangan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">Catatan Atas Laporan Keuangan (CALK)</h2>
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

                <div class="bg-white rounded-3xl shadow-lg border border-slate-100 overflow-hidden">
                    <div class="p-8 space-y-6 text-sm">
                        <div class="text-center mb-8">
                            <h2 class="text-xl font-bold uppercase tracking-wider text-slate-800">Catatan Atas Laporan Keuangan</h2>
                            <p class="text-slate-500">Periode: {{ filterForm.start_date }} s/d {{ filterForm.end_date }}</p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 border-b-2 border-slate-200">
                                        <th class="py-4 px-6 font-semibold text-slate-700 w-2/3">AKUN / URAIAN TRANSAKSI</th>
                                        <th class="py-4 px-6 font-semibold text-slate-700 text-right w-1/3">SALDO / NILAI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="coa in coas" :key="coa.id">
                                        <!-- COA Row -->
                                        <tr class="border-b border-slate-100 transition hover:bg-slate-50 cursor-pointer" 
                                            :class="{'bg-slate-50': coa.is_header}"
                                            @click="!coa.is_header && coa.transactions && coa.transactions.length > 0 ? toggleCoa(coa.id) : null">
                                            <td class="py-3 px-6" 
                                                :class="{'font-bold text-slate-800': coa.is_header, 'text-slate-600': !coa.is_header}"
                                                :style="{ paddingLeft: (coa.level * 1.5) + 'rem' }">
                                                <div class="flex items-center gap-2">
                                                    <span v-if="!coa.is_header && coa.transactions && coa.transactions.length > 0">
                                                        <ChevronDown v-if="expandedCoas.includes(coa.id)" class="w-4 h-4 text-indigo-500" />
                                                        <ChevronRight v-else class="w-4 h-4 text-slate-400" />
                                                    </span>
                                                    <span v-else-if="!coa.is_header" class="w-4"></span>
                                                    {{ filterForm.show_code ? coa.code + ' - ' : '' }}{{ coa.name }}
                                                </div>
                                            </td>
                                            <td class="py-3 px-6 text-right font-medium" :class="{'text-slate-800 font-bold': coa.is_header, 'text-slate-600': !coa.is_header}">
                                                {{ coa.is_header ? '' : formatCurrency(coa.balance) }}
                                            </td>
                                        </tr>
                                        
                                        <!-- Transactions Dropdown (only for details) -->
                                        <template v-if="expandedCoas.includes(coa.id) && coa.transactions">
                                            <tr v-for="(t, index) in coa.transactions" :key="index" class="bg-slate-50/50 border-b border-dashed border-slate-200">
                                                <td class="py-2 px-6 text-slate-500 text-xs" :style="{ paddingLeft: ((coa.level * 1.5) + 2.5) + 'rem' }">
                                                    <span class="font-semibold text-slate-700 mr-2">{{ t.date }}</span>
                                                    <span class="text-indigo-600 mr-2">[{{ t.reference }}]</span>
                                                    {{ t.description }}
                                                </td>
                                                <td class="py-2 px-6 text-right text-slate-600 text-xs">
                                                    <span v-if="t.debit > 0" class="text-emerald-600">D: {{ formatCurrency(t.debit) }}</span>
                                                    <span v-if="t.credit > 0" class="text-rose-600">K: {{ formatCurrency(t.credit) }}</span>
                                                </td>
                                            </tr>
                                        </template>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
