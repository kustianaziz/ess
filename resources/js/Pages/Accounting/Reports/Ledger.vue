<script setup>
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ReportFilters from '@/Components/ReportFilters.vue';
import { ArrowLeft, BookOpen } from 'lucide-vue-next';

const props = defineProps({
    coas: Array,
    maxLevel: { type: Number, default: 5 },
    transactions: Array,
    selectedCoa: Object,
    filters: Object,
    beginningBalance: Number,
    endingBalance: Number,
});

const filterForm = useForm({
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    coa_id: props.filters.coa_id || '',
    level: props.filters.level || 5,
    show_zero: props.filters.show_zero,
    show_code: props.filters.show_code,
});

const applyFilter = () => {
    filterForm.get(route('accounting.reports.ledger'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const viewLedger = (coaId) => {
    filterForm.coa_id = coaId;
    applyFilter();
};

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(val || 0);
};

const handleExportPdf = () => {
    window.open(route('accounting.reports.ledger', { ...filterForm, export: 'pdf' }), '_blank');
};

const handleExportExcel = () => {
    window.open(route('accounting.reports.ledger', { ...filterForm, export: 'excel' }), '_blank');
};
</script>

<template>
    <Head title="Buku Besar" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">Buku Besar (General Ledger)</h2>
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

                <!-- Ledger Report (If COA Selected) -->
                <div v-if="selectedCoa" class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 bg-indigo-50/50 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <button @click="viewLedger('')" class="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-800 font-medium mb-2 transition-colors">
                                <ArrowLeft class="w-4 h-4" />
                                Kembali ke Daftar Akun
                            </button>
                            <h2 class="text-xl font-bold text-slate-800">{{ selectedCoa.code }} - {{ selectedCoa.name }}</h2>
                            <p class="text-sm text-slate-500">Saldo Normal: {{ selectedCoa.normal_balance }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Saldo Akhir</p>
                            <p class="text-2xl font-black text-indigo-600">{{ formatCurrency(endingBalance) }}</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-100 text-xs uppercase tracking-wider">
                                <tr>
                                    <th class="py-4 px-6">Tanggal</th>
                                    <th class="py-4 px-6">Referensi</th>
                                    <th class="py-4 px-6">Keterangan</th>
                                    <th class="py-4 px-6 text-right">Debit</th>
                                    <th class="py-4 px-6 text-right">Kredit</th>
                                    <th class="py-4 px-6 text-right">Saldo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <!-- Beginning Balance -->
                                <tr class="bg-amber-50/30">
                                    <td colspan="3" class="py-4 px-6 font-semibold text-slate-700 text-right">Saldo Awal</td>
                                    <td class="py-4 px-6"></td>
                                    <td class="py-4 px-6"></td>
                                    <td class="py-4 px-6 text-right font-bold text-slate-800">{{ formatCurrency(beginningBalance) }}</td>
                                </tr>
                                
                                <!-- Transactions -->
                                <tr v-for="t in transactions" :key="t.id" class="hover:bg-slate-50 transition">
                                    <td class="py-4 px-6 whitespace-nowrap">{{ t.date }}</td>
                                    <td class="py-4 px-6 font-medium">{{ t.reference }}</td>
                                    <td class="py-4 px-6 max-w-xs truncate" :title="t.description">{{ t.description }}</td>
                                    <td class="py-4 px-6 text-right text-emerald-600 font-medium">{{ t.debit > 0 ? formatCurrency(t.debit) : '-' }}</td>
                                    <td class="py-4 px-6 text-right text-rose-600 font-medium">{{ t.credit > 0 ? formatCurrency(t.credit) : '-' }}</td>
                                    <td class="py-4 px-6 text-right font-bold text-slate-800">{{ formatCurrency(t.balance) }}</td>
                                </tr>
                                
                                <tr v-if="transactions.length === 0">
                                    <td colspan="6" class="py-12 text-center text-slate-400">Tidak ada transaksi di periode ini.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- COA List (If no COA selected) -->
                <div v-else class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-slate-100">
                        <h2 class="font-bold text-lg text-slate-800">Daftar Akun Detail</h2>
                        <p class="text-sm text-slate-500">Pilih salah satu akun untuk melihat riwayat transaksi buku besar.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-100 text-xs uppercase tracking-wider">
                                <tr>
                                    <th class="py-4 px-6 w-32">Kode Akun</th>
                                    <th class="py-4 px-6">Nama Akun</th>
                                    <th class="py-4 px-6 w-40">Tipe</th>
                                    <th class="py-4 px-6 w-32">Saldo Normal</th>
                                    <th class="py-4 px-6 text-right w-40">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="coa in coas" :key="coa.id" class="hover:bg-slate-50 transition group" :class="{'bg-slate-50': coa.is_header}">
                                    <td class="py-4 px-6 font-bold text-slate-700" :style="{ paddingLeft: (coa.level * 1.5) + 'rem' }">
                                        {{ filterForm.show_code ? coa.code + ' - ' : '' }}{{ coa.name }}
                                    </td>
                                    <td class="py-4 px-6 font-medium">{{ coa.is_header ? '-' : coa.balance ? formatCurrency(coa.balance) : '-' }}</td>
                                    <td class="py-4 px-6">
                                        <span class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 capitalize">
                                            {{ coa.type }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="px-2.5 py-1 text-xs font-semibold rounded-lg capitalize" :class="coa.normal_balance === 'Debit' ? 'bg-blue-100 text-blue-700' : 'bg-rose-100 text-rose-700'">
                                            {{ coa.normal_balance }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <button v-if="!coa.is_header" @click="viewLedger(coa.id)" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-600 hover:text-white rounded-lg transition-colors">
                                            <BookOpen class="w-3.5 h-3.5" />
                                            Lihat Buku Besar
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="coas.length === 0">
                                    <td colspan="5" class="py-12 text-center text-slate-400">Tidak ada data COA detail.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
