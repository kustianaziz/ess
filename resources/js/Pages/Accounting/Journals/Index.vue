<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Plus, Trash2, FileText, CheckCircle2, Ban, BookOpen, Filter, RotateCcw } from 'lucide-vue-next';

const props = defineProps({
    journals: Array,
    pendingTransactions: Array,
    coas: Array,
    periods: Array,
    filters: Object,
    sourceTypes: Array,
});

const activeTab = ref('pending');
const isManualModalOpen = ref(false);
const isJurnalkanModalOpen = ref(false);
const selectedTrx = ref(null);

// Filter state — same pattern as History.vue
const filterDateFrom = ref(props.filters?.date_from ?? '');
const filterDateTo = ref(props.filters?.date_to ?? '');
const filterPeriodId = ref(props.filters?.period_id ?? '');
const filterSourceType = ref(props.filters?.source_type ?? '');

const applyFilters = () => {
    router.get(route('accounting.journals.index'), {
        date_from: filterDateFrom.value,
        date_to: filterDateTo.value,
        period_id: filterPeriodId.value,
        source_type: filterSourceType.value,
    }, { preserveState: true, preserveScroll: true });
};

const resetFilters = () => {
    filterDateFrom.value = '';
    filterDateTo.value = '';
    filterPeriodId.value = '';
    filterSourceType.value = '';
    applyFilters();
};

// ── Manual Journal Form ──
const manualForm = useForm({
    date: new Date().toISOString().split('T')[0],
    description: '',
    items: [
        { coa_id: '', description: '', debit: 0, credit: 0 },
        { coa_id: '', description: '', debit: 0, credit: 0 }
    ]
});

const manualTotalDebit = computed(() => manualForm.items.reduce((s, i) => s + (parseFloat(i.debit) || 0), 0));
const manualTotalCredit = computed(() => manualForm.items.reduce((s, i) => s + (parseFloat(i.credit) || 0), 0));
const isManualBalanced = computed(() => manualTotalDebit.value > 0 && Math.abs(manualTotalDebit.value - manualTotalCredit.value) < 0.01);

const addManualItem = () => manualForm.items.push({ coa_id: '', description: '', debit: 0, credit: 0 });
const removeManualItem = (i) => { if (manualForm.items.length > 2) manualForm.items.splice(i, 1); };

const openManualModal = () => {
    manualForm.reset();
    manualForm.date = new Date().toISOString().split('T')[0];
    manualForm.items = [
        { coa_id: '', description: '', debit: 0, credit: 0 },
        { coa_id: '', description: '', debit: 0, credit: 0 }
    ];
    isManualModalOpen.value = true;
};
const closeManualModal = () => { isManualModalOpen.value = false; manualForm.reset(); };
const submitManual = () => {
    manualForm.post(route('accounting.journals.store'), {
        preserveScroll: true,
        onSuccess: () => closeManualModal(),
    });
};

// ── Jurnalkan from source transaction (split-capable) ──
const jurnalkanForm = useForm({
    source_type: '',
    source_id: '',
    date: '',
    description: '',
    items: []
});

const openJurnalkanModal = (trx) => {
    selectedTrx.value = trx;
    jurnalkanForm.source_type = trx.source_type;
    jurnalkanForm.source_id = trx.source_id;
    jurnalkanForm.date = trx.date;
    jurnalkanForm.description = trx.description;
    jurnalkanForm.items = [
        { coa_id: '', description: trx.description, debit: trx.amount, credit: 0 },
        { coa_id: '', description: trx.description, debit: 0, credit: trx.amount },
    ];
    isJurnalkanModalOpen.value = true;
};
const closeJurnalkanModal = () => { isJurnalkanModalOpen.value = false; selectedTrx.value = null; };

const jurnalDebitTotal = computed(() => jurnalkanForm.items.reduce((s, i) => s + (parseFloat(i.debit) || 0), 0));
const jurnalCreditTotal = computed(() => jurnalkanForm.items.reduce((s, i) => s + (parseFloat(i.credit) || 0), 0));
const isJurnalBalanced = computed(() => jurnalDebitTotal.value > 0 && Math.abs(jurnalDebitTotal.value - jurnalCreditTotal.value) < 0.01);

const addJurnalItem = () => jurnalkanForm.items.push({ coa_id: '', description: selectedTrx.value?.description ?? '', debit: 0, credit: 0 });
const removeJurnalItem = (i) => { if (jurnalkanForm.items.length > 2) jurnalkanForm.items.splice(i, 1); };

const submitJurnal = () => {
    jurnalkanForm.post(route('accounting.journals.store'), {
        preserveScroll: true,
        onSuccess: () => closeJurnalkanModal(),
    });
};

// ── Void journal (delete reference so it reappears in pending) ──
const voidJournal = (journal) => {
    if (!confirm(`Batalkan jurnal ${journal.journal_number}?\n\nTransaksi ini akan kembali ke tab "Belum Dijurnal" dan bisa dijurnal ulang.`)) return;
    router.post(route('accounting.journals.void', journal.id), {}, { preserveScroll: true });
};

const formatRupiah = (v) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(v || 0);
const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' }) : '-';
const statusColor = (s) => ({ posted: 'bg-emerald-100 text-emerald-700', void: 'bg-rose-100 text-rose-600', draft: 'bg-amber-100 text-amber-700' }[s] ?? 'bg-slate-100 text-slate-600');
</script>

<template>
    <Head title="Transaksi Jurnal" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold leading-tight text-gray-800">Transaksi Jurnal</h2>
                <PrimaryButton @click="openManualModal" class="gap-2">
                    <Plus class="w-4 h-4" /> Jurnal Manual
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-5">

                <!-- Filter Card — always visible, same pattern as History.vue -->
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                            <Filter class="w-4 h-4 text-indigo-600" />
                            Filter Pencarian
                        </h3>
                        <button @click="resetFilters" class="text-xs text-slate-500 hover:text-indigo-600 font-semibold flex items-center gap-1 transition-colors">
                            <RotateCcw class="w-3.5 h-3.5" />
                            Reset Filter
                        </button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs text-slate-500 mb-1">Tanggal Dari</label>
                            <input type="date" v-model="filterDateFrom" @change="applyFilters"
                                class="w-full text-xs border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500 mb-1">Tanggal Sampai</label>
                            <input type="date" v-model="filterDateTo" @change="applyFilters"
                                class="w-full text-xs border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500 mb-1">Periode Akuntansi</label>
                            <select v-model="filterPeriodId" @change="applyFilters"
                                class="w-full text-xs border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Semua Periode</option>
                                <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500 mb-1">Sumber Transaksi</label>
                            <select v-model="filterSourceType" @change="applyFilters"
                                class="w-full text-xs border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Semua Sumber</option>
                                <option v-for="st in sourceTypes" :key="st.value" :value="st.value">{{ st.label }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8">
                        <button @click="activeTab = 'pending'"
                            :class="activeTab === 'pending' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                            Belum Dijurnal
                            <span v-if="pendingTransactions.length > 0" class="bg-rose-100 text-rose-600 py-0.5 px-2.5 rounded-full text-xs font-bold">{{ pendingTransactions.length }}</span>
                        </button>
                        <button @click="activeTab = 'journals'"
                            :class="activeTab === 'journals' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                            <BookOpen class="w-4 h-4" /> Buku Jurnal Umum
                        </button>
                    </nav>
                </div>

                <!-- ── Tab: Belum Dijurnal ── -->
                <div v-if="activeTab === 'pending'" class="overflow-hidden bg-white shadow-sm sm:rounded-xl border border-slate-200">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[700px]">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-xs uppercase tracking-wider">
                                    <th class="p-4 font-semibold text-slate-500">Tanggal</th>
                                    <th class="p-4 font-semibold text-slate-500">Sumber</th>
                                    <th class="p-4 font-semibold text-slate-500">No. Referensi</th>
                                    <th class="p-4 font-semibold text-slate-500">Keterangan</th>
                                    <th class="p-4 font-semibold text-slate-500 text-right">Nominal</th>
                                    <th class="p-4 font-semibold text-slate-500 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="trx in pendingTransactions" :key="trx.id" class="border-b border-slate-100 hover:bg-slate-50/50">
                                    <td class="p-4 text-sm text-slate-600 whitespace-nowrap">{{ formatDate(trx.date) }}</td>
                                    <td class="p-4 text-sm whitespace-nowrap">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700">{{ trx.source_label }}</span>
                                    </td>
                                    <td class="p-4 text-sm font-mono text-slate-700 whitespace-nowrap">{{ trx.reference_number }}</td>
                                    <td class="p-4 text-sm text-slate-800 max-w-xs truncate">{{ trx.description }}</td>
                                    <td class="p-4 text-sm font-bold text-right whitespace-nowrap" :class="trx.type === 'in' ? 'text-emerald-600' : 'text-rose-600'">
                                        {{ formatRupiah(trx.amount) }}
                                    </td>
                                    <td class="p-4 text-center">
                                        <button @click="openJurnalkanModal(trx)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 transition-all">
                                            <Plus class="w-3 h-3" /> Jurnalkan
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="pendingTransactions.length === 0">
                                    <td colspan="6" class="p-16 text-center">
                                        <CheckCircle2 class="w-14 h-14 text-emerald-400 mx-auto mb-3" />
                                        <p class="text-slate-500 font-medium">Semua transaksi sudah dijurnal!</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ── Tab: Buku Jurnal ── -->
                <div v-if="activeTab === 'journals'" class="overflow-hidden bg-white shadow-sm sm:rounded-xl border border-slate-200">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[700px]">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-xs uppercase tracking-wider">
                                    <th class="p-4 font-semibold text-slate-500">Tanggal</th>
                                    <th class="p-4 font-semibold text-slate-500">Nomor Jurnal</th>
                                    <th class="p-4 font-semibold text-slate-500">Keterangan</th>
                                    <th class="p-4 font-semibold text-slate-500 text-right">Total</th>
                                    <th class="p-4 font-semibold text-slate-500">Status</th>
                                    <th class="p-4 font-semibold text-slate-500 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="journal in journals" :key="journal.id"
                                    class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors"
                                    :class="journal.status === 'void' ? 'opacity-50 bg-slate-50' : ''">
                                    <td class="p-4 text-sm text-slate-600 whitespace-nowrap">{{ formatDate(journal.date) }}</td>
                                    <td class="p-4 text-sm font-mono font-semibold text-indigo-600 whitespace-nowrap">{{ journal.journal_number }}</td>
                                    <td class="p-4 text-sm text-slate-700 max-w-xs truncate" :class="journal.status === 'void' ? 'line-through' : ''">{{ journal.description }}</td>
                                    <td class="p-4 text-sm text-right font-semibold text-slate-800 whitespace-nowrap">{{ formatRupiah(journal.amount) }}</td>
                                    <td class="p-4 text-sm">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full" :class="statusColor(journal.status)">{{ journal.status.toUpperCase() }}</span>
                                    </td>
                                    <td class="p-4 text-sm text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <Link :href="route('accounting.journals.show', journal.id)"
                                                class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Lihat Detail">
                                                <FileText class="w-4 h-4" />
                                            </Link>
                                            <button v-if="journal.status !== 'void'" @click="voidJournal(journal)"
                                                class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition" title="Batalkan Jurnal">
                                                <Ban class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="journals.length === 0">
                                    <td colspan="6" class="p-12 text-center text-slate-500">Belum ada data jurnal.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- ══════ Modal: Jurnalkan (from source) ══════ -->
        <Modal :show="isJurnalkanModalOpen" @close="closeJurnalkanModal" maxWidth="5xl">
            <div class="p-6">
                <div class="mb-5 pb-4 border-b border-slate-200">
                    <h2 class="text-lg font-bold text-gray-900">Jurnalkan Transaksi</h2>
                    <div v-if="selectedTrx" class="mt-2 flex flex-wrap gap-3 text-xs items-center">
                        <span class="bg-indigo-50 text-indigo-700 px-2.5 py-1 rounded-full font-semibold">{{ selectedTrx.source_label }}</span>
                        <span class="text-slate-500 font-mono">{{ selectedTrx.reference_number }}</span>
                        <span class="font-bold text-slate-800">{{ formatRupiah(selectedTrx.amount) }}</span>
                    </div>
                    <p class="text-xs text-slate-400 mt-2">Anda bisa pecah nominal ke beberapa akun. Pastikan Total Debit = Total Kredit.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    <div>
                        <InputLabel value="Tanggal Jurnal" />
                        <TextInput type="date" class="mt-1 block w-full" v-model="jurnalkanForm.date" />
                        <InputError :message="jurnalkanForm.errors.date" />
                    </div>
                    <div>
                        <InputLabel value="Keterangan" />
                        <TextInput type="text" class="mt-1 block w-full" v-model="jurnalkanForm.description" />
                        <InputError :message="jurnalkanForm.errors.description" />
                    </div>
                </div>

                <div class="border border-slate-200 rounded-xl overflow-hidden mb-4">
                    <div class="bg-slate-50 px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-700">Baris Akun (Debit / Kredit)</p>
                        <button @click="addJurnalItem" type="button" class="flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 font-semibold px-2 py-1 rounded hover:bg-indigo-50">
                            <Plus class="w-3.5 h-3.5" /> Tambah Baris
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm min-w-[580px]">
                            <thead class="text-xs text-slate-500 border-b border-slate-200 bg-slate-50/50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold">Akun COA</th>
                                    <th class="px-4 py-2 text-left font-semibold">Keterangan Baris</th>
                                    <th class="px-4 py-2 text-right font-semibold w-36">Debit</th>
                                    <th class="px-4 py-2 text-right font-semibold w-36">Kredit</th>
                                    <th class="px-4 py-2 w-10"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, idx) in jurnalkanForm.items" :key="idx" class="border-b border-slate-100">
                                    <td class="px-4 py-2">
                                        <select v-model="item.coa_id" class="border-gray-300 rounded-lg text-sm w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="" disabled>Pilih Akun...</option>
                                            <option v-for="c in coas" :key="c.id" :value="c.id">{{ c.code }} – {{ c.name }}</option>
                                        </select>
                                        <InputError :message="jurnalkanForm.errors[`items.${idx}.coa_id`]" />
                                    </td>
                                    <td class="px-4 py-2">
                                        <TextInput type="text" class="block w-full text-sm" v-model="item.description" placeholder="Opsional" />
                                    </td>
                                    <td class="px-4 py-2">
                                        <TextInput type="number" class="block w-full text-sm text-right" v-model="item.debit" min="0" step="0.01"
                                            @focus="item.debit = (item.debit == 0 ? '' : item.debit)" />
                                    </td>
                                    <td class="px-4 py-2">
                                        <TextInput type="number" class="block w-full text-sm text-right" v-model="item.credit" min="0" step="0.01"
                                            @focus="item.credit = (item.credit == 0 ? '' : item.credit)" />
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <button @click="removeJurnalItem(idx)" type="button" :disabled="jurnalkanForm.items.length <= 2"
                                            class="p-1 text-rose-400 hover:text-rose-600 disabled:opacity-30 disabled:cursor-not-allowed rounded">
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-slate-50 border-t-2 border-slate-300">
                                <tr>
                                    <td colspan="2" class="px-4 py-2.5 text-xs font-bold text-slate-500 uppercase text-right">Total</td>
                                    <td class="px-4 py-2.5 text-right font-bold text-sm" :class="isJurnalBalanced ? 'text-emerald-600' : 'text-rose-500'">{{ formatRupiah(jurnalDebitTotal) }}</td>
                                    <td class="px-4 py-2.5 text-right font-bold text-sm" :class="isJurnalBalanced ? 'text-emerald-600' : 'text-rose-500'">{{ formatRupiah(jurnalCreditTotal) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <InputError class="p-3" :message="jurnalkanForm.errors.items" />
                </div>
                <p v-if="!isJurnalBalanced" class="text-xs text-rose-500 mb-4">⚠ Total Debit dan Kredit harus seimbang dan lebih dari 0.</p>

                <div class="flex justify-end gap-3">
                    <SecondaryButton @click="closeJurnalkanModal">Batal</SecondaryButton>
                    <PrimaryButton @click="submitJurnal" :disabled="jurnalkanForm.processing || !isJurnalBalanced"
                        :class="{ 'opacity-50 cursor-not-allowed': jurnalkanForm.processing || !isJurnalBalanced }">
                        {{ jurnalkanForm.processing ? 'Menyimpan...' : 'Simpan Jurnal' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- ══════ Modal: Jurnal Manual ══════ -->
        <Modal :show="isManualModalOpen" @close="closeManualModal" maxWidth="5xl">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-1">Tambah Jurnal Manual</h2>
                <p class="text-xs text-slate-400 mb-5">Untuk penyesuaian, depresiasi, atau transaksi non-sistem lainnya.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    <div>
                        <InputLabel for="mdate" value="Tanggal Jurnal" />
                        <TextInput id="mdate" type="date" class="mt-1 block w-full" v-model="manualForm.date" required />
                        <InputError class="mt-1" :message="manualForm.errors.date" />
                    </div>
                    <div>
                        <InputLabel for="mdesc" value="Keterangan Umum" />
                        <TextInput id="mdesc" type="text" class="mt-1 block w-full" v-model="manualForm.description" placeholder="Cth: Penyesuaian depresiasi..." required />
                        <InputError class="mt-1" :message="manualForm.errors.description" />
                    </div>
                </div>

                <div class="border border-slate-200 rounded-xl overflow-hidden mb-4">
                    <div class="bg-slate-50 px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-700">Baris Akun</p>
                        <button @click="addManualItem" type="button" class="flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 font-semibold px-2 py-1 rounded hover:bg-indigo-50">
                            <Plus class="w-3.5 h-3.5" /> Tambah Baris
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm min-w-[580px]">
                            <thead class="text-xs text-slate-500 border-b border-slate-200 bg-slate-50/50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold">Akun COA</th>
                                    <th class="px-4 py-2 text-left font-semibold">Keterangan Baris</th>
                                    <th class="px-4 py-2 text-right font-semibold w-36">Debit</th>
                                    <th class="px-4 py-2 text-right font-semibold w-36">Kredit</th>
                                    <th class="px-4 py-2 w-10"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, idx) in manualForm.items" :key="idx" class="border-b border-slate-100">
                                    <td class="px-4 py-2">
                                        <select v-model="item.coa_id" class="border-gray-300 rounded-lg text-sm w-full shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="" disabled>Pilih Akun...</option>
                                            <option v-for="c in coas" :key="c.id" :value="c.id">{{ c.code }} – {{ c.name }}</option>
                                        </select>
                                        <InputError :message="manualForm.errors[`items.${idx}.coa_id`]" />
                                    </td>
                                    <td class="px-4 py-2">
                                        <TextInput type="text" class="block w-full text-sm" v-model="item.description" placeholder="Opsional" />
                                    </td>
                                    <td class="px-4 py-2">
                                        <TextInput type="number" class="block w-full text-sm text-right" v-model="item.debit" min="0" step="0.01"
                                            @focus="item.debit = (item.debit == 0 ? '' : item.debit)" />
                                    </td>
                                    <td class="px-4 py-2">
                                        <TextInput type="number" class="block w-full text-sm text-right" v-model="item.credit" min="0" step="0.01"
                                            @focus="item.credit = (item.credit == 0 ? '' : item.credit)" />
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <button @click="removeManualItem(idx)" type="button" :disabled="manualForm.items.length <= 2"
                                            class="p-1 text-rose-400 hover:text-rose-600 disabled:opacity-30 disabled:cursor-not-allowed rounded">
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-slate-50 border-t-2 border-slate-300">
                                <tr>
                                    <td colspan="2" class="px-4 py-2.5 text-xs font-bold text-slate-500 uppercase text-right">Total</td>
                                    <td class="px-4 py-2.5 text-right font-bold text-sm" :class="isManualBalanced ? 'text-emerald-600' : 'text-rose-500'">{{ formatRupiah(manualTotalDebit) }}</td>
                                    <td class="px-4 py-2.5 text-right font-bold text-sm" :class="isManualBalanced ? 'text-emerald-600' : 'text-rose-500'">{{ formatRupiah(manualTotalCredit) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <InputError class="p-3" :message="manualForm.errors.items" />
                </div>
                <p v-if="!isManualBalanced" class="text-xs text-rose-500 mb-4">⚠ Total Debit dan Kredit harus seimbang dan lebih dari 0.</p>

                <div class="flex justify-end gap-3">
                    <SecondaryButton @click="closeManualModal">Batal</SecondaryButton>
                    <PrimaryButton @click="submitManual" :disabled="manualForm.processing || !isManualBalanced"
                        :class="{ 'opacity-50 cursor-not-allowed': manualForm.processing || !isManualBalanced }">
                        {{ manualForm.processing ? 'Menyimpan...' : 'Simpan Jurnal' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.2s ease; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
