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
import { Plus, Trash2, FileText, CheckCircle2 } from 'lucide-vue-next';

const props = defineProps({
    journals: Array,
    pendingTransactions: Array,
    coas: Array
});

const activeTab = ref('pending'); // 'pending' or 'journals'
const isModalOpen = ref(false);

const form = useForm({
    date: new Date().toISOString().split('T')[0],
    description: '',
    items: [
        { coa_id: '', description: '', debit: 0, credit: 0 },
        { coa_id: '', description: '', debit: 0, credit: 0 }
    ]
});

const totalDebit = computed(() => {
    return form.items.reduce((sum, item) => sum + (parseFloat(item.debit) || 0), 0);
});

const totalCredit = computed(() => {
    return form.items.reduce((sum, item) => sum + (parseFloat(item.credit) || 0), 0);
});

const isBalanced = computed(() => {
    return totalDebit.value === totalCredit.value && totalDebit.value > 0;
});

const addItem = () => {
    form.items.push({ coa_id: '', description: '', debit: 0, credit: 0 });
};

const removeItem = (index) => {
    if (form.items.length > 2) {
        form.items.splice(index, 1);
    }
};

const openModal = () => {
    form.reset();
    form.items = [
        { coa_id: '', description: '', debit: 0, credit: 0 },
        { coa_id: '', description: '', debit: 0, credit: 0 }
    ];
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    form.post(route('accounting.journals.store'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const formatRupiah = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};

const formatDate = (dateString) => {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
};

// Auto Journal Form
const autoJournalForm = useForm({
    source_type: '',
    source_id: '',
    date: '',
    description: '',
    amount: 0,
    debit_coa_id: '',
    credit_coa_id: '',
});

const submitAutoJournal = (trx) => {
    if (!trx.debit_coa_id || !trx.credit_coa_id) {
        alert("Silakan pilih akun Debit dan Kredit terlebih dahulu!");
        return;
    }
    autoJournalForm.source_type = trx.source_type;
    autoJournalForm.source_id = trx.source_id;
    autoJournalForm.date = trx.date;
    autoJournalForm.description = trx.description;
    autoJournalForm.amount = trx.amount;
    autoJournalForm.debit_coa_id = trx.debit_coa_id;
    autoJournalForm.credit_coa_id = trx.credit_coa_id;
    
    autoJournalForm.post(route('accounting.journals.store'), {
        preserveScroll: true,
        onSuccess: () => alert('Berhasil dijurnal!'),
    });
};

</script>

<template>
    <Head title="Transaksi Jurnal" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h2 class="text-xl font-bold leading-tight text-gray-800">Transaksi Jurnal</h2>
                <PrimaryButton @click="openModal" class="gap-2 shrink-0 self-start sm:self-auto">
                    <Plus class="w-4 h-4" />
                    Tambah Jurnal Manual
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                
                <!-- Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button 
                            @click="activeTab = 'pending'"
                            :class="activeTab === 'pending' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2"
                        >
                            Transaksi Belum Dijurnal
                            <span v-if="pendingTransactions.length > 0" class="bg-rose-100 text-rose-600 py-0.5 px-2.5 rounded-full text-xs font-bold">
                                {{ pendingTransactions.length }}
                            </span>
                        </button>
                        <button 
                            @click="activeTab = 'journals'"
                            :class="activeTab === 'journals' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                        >
                            Buku Jurnal Umum (Semua)
                        </button>
                    </nav>
                </div>

                <!-- Tab: Pending Transactions -->
                <div v-if="activeTab === 'pending'" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[800px]">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-sm">
                                    <th class="p-4 font-semibold text-slate-600">Tanggal</th>
                                    <th class="p-4 font-semibold text-slate-600">No. Referensi</th>
                                    <th class="p-4 font-semibold text-slate-600">Keterangan</th>
                                    <th class="p-4 font-semibold text-slate-600 text-right">Nominal</th>
                                    <th class="p-4 font-semibold text-slate-600">Debit</th>
                                    <th class="p-4 font-semibold text-slate-600">Kredit</th>
                                    <th class="p-4 font-semibold text-slate-600 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="trx in pendingTransactions" :key="trx.id" class="border-b border-slate-100 hover:bg-slate-50/50">
                                    <td class="p-4 text-sm">{{ formatDate(trx.date) }}</td>
                                    <td class="p-4 text-sm font-medium text-slate-700">{{ trx.reference_number }}</td>
                                    <td class="p-4 text-sm">{{ trx.description }}</td>
                                    <td class="p-4 text-sm font-bold text-slate-800 text-right">{{ formatRupiah(trx.amount) }}</td>
                                    <td class="p-4 text-sm">
                                        <select v-model="trx.debit_coa_id" class="border-gray-300 rounded-md text-sm w-full md:w-48 shadow-sm">
                                            <option value="" disabled>Pilih Akun Debit...</option>
                                            <option v-for="c in coas" :key="c.id" :value="c.id">{{ c.code }} - {{ c.name }}</option>
                                        </select>
                                    </td>
                                    <td class="p-4 text-sm">
                                        <select v-model="trx.credit_coa_id" class="border-gray-300 rounded-md text-sm w-full md:w-48 shadow-sm">
                                            <option value="" disabled>Pilih Akun Kredit...</option>
                                            <option v-for="c in coas" :key="c.id" :value="c.id">{{ c.code }} - {{ c.name }}</option>
                                        </select>
                                    </td>
                                    <td class="p-4 text-sm text-center">
                                        <PrimaryButton @click="submitAutoJournal(trx)" class="!px-3 !py-1.5" :disabled="autoJournalForm.processing">
                                            Jurnalkan
                                        </PrimaryButton>
                                    </td>
                                </tr>
                                <tr v-if="pendingTransactions.length === 0">
                                    <td colspan="7" class="p-12 text-center">
                                        <CheckCircle2 class="w-12 h-12 text-emerald-400 mx-auto mb-3" />
                                        <p class="text-slate-500 font-medium">Mantap! Semua transaksi otomatis sudah dijurnal.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: All Journals -->
                <div v-if="activeTab === 'journals'" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[800px]">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-sm">
                                    <th class="p-4 font-semibold text-slate-600">Tanggal</th>
                                    <th class="p-4 font-semibold text-slate-600">Nomor Jurnal</th>
                                    <th class="p-4 font-semibold text-slate-600">Keterangan</th>
                                    <th class="p-4 font-semibold text-slate-600 text-right">Nominal</th>
                                    <th class="p-4 font-semibold text-slate-600">Status</th>
                                    <th class="p-4 font-semibold text-slate-600 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="journal in journals" :key="journal.id" class="border-b border-slate-100 hover:bg-slate-50/50">
                                    <td class="p-4 text-sm">{{ formatDate(journal.date) }}</td>
                                    <td class="p-4 text-sm font-medium text-indigo-600">{{ journal.journal_number }}</td>
                                    <td class="p-4 text-sm">{{ journal.description }}</td>
                                    <td class="p-4 text-sm text-right font-medium text-emerald-600">{{ formatRupiah(journal.amount) }}</td>
                                    <td class="p-4 text-sm">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700">
                                            {{ journal.status.toUpperCase() }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-sm text-center">
                                        <Link :href="route('accounting.journals.show', journal.id)" class="text-indigo-600 hover:text-indigo-900 flex items-center justify-center gap-1" title="Detail">
                                            <FileText class="w-4 h-4" /> Lihat
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="journals.length === 0">
                                    <td colspan="6" class="p-8 text-center text-slate-500">
                                        Belum ada data jurnal.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <Modal :show="isModalOpen" @close="closeModal" maxWidth="4xl">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-6">Tambah Jurnal Manual</h2>

                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="date" value="Tanggal Jurnal" />
                            <TextInput
                                id="date"
                                type="date"
                                class="mt-1 block w-full"
                                v-model="form.date"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.date" />
                        </div>
                        <div>
                            <InputLabel for="description" value="Keterangan Umum" />
                            <TextInput
                                id="description"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.description"
                                required
                                placeholder="Cth: Penyesuaian persediaan..."
                            />
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>
                    </div>

                    <div class="border-t border-slate-200 pt-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold text-slate-700">Detail Akun (Chart of Accounts)</h3>
                            <SecondaryButton @click="addItem" type="button" class="text-xs">
                                <Plus class="w-3 h-3 mr-1" /> Tambah Baris
                            </SecondaryButton>
                        </div>
                        
                        <div class="space-y-3">
                            <div v-for="(item, index) in form.items" :key="index" class="flex flex-wrap md:flex-nowrap items-start gap-3">
                                <div class="w-full md:w-1/3">
                                    <select
                                        v-model="item.coa_id"
                                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full text-sm"
                                        required
                                    >
                                        <option value="" disabled>Pilih Akun</option>
                                        <template v-for="coa in coas" :key="coa.id">
                                            <option :value="coa.id">
                                                {{ coa.code }} - {{ coa.name }}
                                            </option>
                                        </template>
                                    </select>
                                    <InputError class="mt-1" :message="form.errors[`items.${index}.coa_id`]" />
                                </div>
                                <div class="w-full md:w-1/3">
                                    <TextInput
                                        type="text"
                                        class="block w-full text-sm"
                                        v-model="item.description"
                                        placeholder="Catatan (opsional)"
                                    />
                                </div>
                                <div class="w-[45%] md:w-1/6">
                                    <TextInput
                                        type="number"
                                        class="block w-full text-sm text-right"
                                        v-model="item.debit"
                                        min="0"
                                        step="0.01"
                                        placeholder="Debit"
                                        @focus="item.debit = item.debit === 0 ? '' : item.debit"
                                    />
                                    <InputError class="mt-1" :message="form.errors[`items.${index}.debit`]" />
                                </div>
                                <div class="w-[45%] md:w-1/6">
                                    <TextInput
                                        type="number"
                                        class="block w-full text-sm text-right"
                                        v-model="item.credit"
                                        min="0"
                                        step="0.01"
                                        placeholder="Kredit"
                                        @focus="item.credit = item.credit === 0 ? '' : item.credit"
                                    />
                                    <InputError class="mt-1" :message="form.errors[`items.${index}.credit`]" />
                                </div>
                                <div class="w-[10%] md:w-auto">
                                    <button 
                                        @click="removeItem(index)" 
                                        type="button"
                                        class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors mt-0.5"
                                        :disabled="form.items.length <= 2"
                                        :class="{'opacity-50 cursor-not-allowed': form.items.length <= 2}"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <InputError class="mt-4" :message="form.errors.items" />
                    </div>
                </div>

                <div class="mt-6 border-t border-slate-200 pt-4">
                    <div class="flex flex-col sm:flex-row justify-end gap-4 sm:gap-8 mb-6 sm:mr-12">
                        <div class="text-right">
                            <span class="text-xs font-semibold text-slate-500 uppercase">Total Debit</span>
                            <p class="text-lg font-bold" :class="isBalanced ? 'text-emerald-600' : 'text-rose-600'">
                                {{ formatRupiah(totalDebit) }}
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-semibold text-slate-500 uppercase">Total Kredit</span>
                            <p class="text-lg font-bold" :class="isBalanced ? 'text-emerald-600' : 'text-rose-600'">
                                {{ formatRupiah(totalCredit) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <SecondaryButton @click="closeModal">Batal</SecondaryButton>
                        <PrimaryButton
                            @click="submit"
                            :class="{ 'opacity-50 cursor-not-allowed': form.processing || !isBalanced }"
                            :disabled="form.processing || !isBalanced"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Jurnal' }}
                        </PrimaryButton>
                    </div>
                    <p v-if="!isBalanced" class="text-xs text-rose-500 text-right mt-2">
                        Total Debit dan Kredit harus seimbang dan lebih dari 0.
                    </p>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
