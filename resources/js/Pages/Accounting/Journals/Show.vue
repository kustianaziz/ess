<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ArrowLeft, Printer, Ban } from 'lucide-vue-next';

const props = defineProps({
    journal: Object,
});

const voidJournal = () => {
    if (!confirm(`Batalkan jurnal ${props.journal.journal_number}? Tindakan ini tidak bisa dibatalkan.`)) return;
    router.post(route('accounting.journals.void', props.journal.id), {}, {
        onSuccess: () => router.visit(route('accounting.journals.index'))
    });
};

const formatRupiah = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};

const formatDate = (dateString) => {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
};
</script>

<template>
    <Head :title="`Detail Jurnal ${journal.journal_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('accounting.journals.index')" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full transition-all">
                        <ArrowLeft class="w-5 h-5" />
                    </Link>
                    <h2 class="text-xl font-bold leading-tight text-gray-800">
                        Detail Jurnal: {{ journal.journal_number }}
                    </h2>
                </div>
                <div class="flex items-center gap-2">
                    <button v-if="journal.status !== 'void'" @click="voidJournal" class="flex items-center gap-2 px-4 py-2 bg-rose-50 border border-rose-200 text-rose-600 rounded-lg hover:bg-rose-100 transition-all font-medium text-sm">
                        <Ban class="w-4 h-4" />
                        Batalkan Jurnal
                    </button>
                    <button @click="() => window.print()" class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-all font-medium text-sm shadow-sm">
                        <Printer class="w-4 h-4" />
                        Cetak
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8 space-y-6">
                <!-- Info Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl p-6 border border-slate-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-1">Informasi Jurnal</p>
                            <div class="space-y-3 mt-4">
                                <div>
                                    <p class="text-xs text-slate-500">Nomor Jurnal</p>
                                    <p class="text-base font-bold text-slate-900">{{ journal.journal_number }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Tanggal</p>
                                    <p class="text-sm font-medium text-slate-900">{{ formatDate(journal.date) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Keterangan Umum</p>
                                    <p class="text-sm font-medium text-slate-900">{{ journal.description }}</p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-1">Status & Pembuat</p>
                            <div class="space-y-3 mt-4">
                                <div>
                                    <p class="text-xs text-slate-500">Status</p>
                                    <span class="inline-flex mt-1 px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200">
                                        {{ journal.status.toUpperCase() }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Dibuat Oleh</p>
                                    <p class="text-sm font-medium text-slate-900">{{ journal.creator?.name || 'Sistem' }}</p>
                                </div>
                                <div v-if="journal.reference_type">
                                    <p class="text-xs text-slate-500">Referensi Sumber</p>
                                    <p class="text-sm font-medium text-slate-900">{{ journal.reference_type.split('\\').pop() }} #{{ journal.reference_id }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Status</p>
                                    <span class="inline-flex mt-1 px-2.5 py-1 text-xs font-semibold rounded-full"
                                        :class="{'bg-emerald-100 text-emerald-700': journal.status === 'posted', 'bg-rose-100 text-rose-600': journal.status === 'void', 'bg-amber-100 text-amber-700': journal.status === 'draft'}">
                                        {{ journal.status.toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detail Items Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50">
                        <h3 class="text-base font-bold text-slate-800">Detail Transaksi Akun (Debit & Kredit)</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-sm">
                                    <th class="p-4 font-semibold text-slate-600">Kode Akun</th>
                                    <th class="p-4 font-semibold text-slate-600">Nama Akun</th>
                                    <th class="p-4 font-semibold text-slate-600">Keterangan Baris</th>
                                    <th class="p-4 font-semibold text-slate-600 text-right">Debit</th>
                                    <th class="p-4 font-semibold text-slate-600 text-right">Kredit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in journal.items" :key="item.id" class="border-b border-slate-100">
                                    <td class="p-4 text-sm font-mono text-slate-600">{{ item.coa?.code }}</td>
                                    <td class="p-4 text-sm font-medium text-slate-900">
                                        {{ item.coa?.name }}
                                    </td>
                                    <td class="p-4 text-sm text-slate-600">{{ item.description }}</td>
                                    <td class="p-4 text-sm text-right font-medium" :class="item.debit > 0 ? 'text-slate-900' : 'text-slate-400'">
                                        {{ formatRupiah(item.debit) }}
                                    </td>
                                    <td class="p-4 text-sm text-right font-medium" :class="item.credit > 0 ? 'text-slate-900' : 'text-slate-400'">
                                        {{ formatRupiah(item.credit) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-50 border-t-2 border-slate-200">
                                    <td colspan="3" class="p-4 text-sm font-bold text-slate-700 text-right uppercase tracking-wider">
                                        Total Balance
                                    </td>
                                    <td class="p-4 text-sm font-bold text-emerald-600 text-right">
                                        {{ formatRupiah(journal.amount) }}
                                    </td>
                                    <td class="p-4 text-sm font-bold text-emerald-600 text-right">
                                        {{ formatRupiah(journal.amount) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
