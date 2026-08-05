<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    coas: Array,
});

const form = useForm({
    date: new Date().toISOString().split('T')[0],
    balances: props.coas.map(coa => ({
        coa_id: coa.id,
        code: coa.code,
        name: coa.name,
        normal_balance: coa.normal_balance,
        is_header: coa.is_header,
        debit: parseFloat(coa.debit) || 0,
        credit: parseFloat(coa.credit) || 0,
    })),
});

import { watch } from 'vue';

watch(
    () => form.balances,
    (newBalances) => {
        newBalances.forEach(header => {
            if (header.is_header) {
                let sumDebit = 0;
                let sumCredit = 0;
                newBalances.forEach(detail => {
                    if (!detail.is_header && detail.code.startsWith(header.code + '.')) {
                        sumDebit += parseFloat(detail.debit) || 0;
                        sumCredit += parseFloat(detail.credit) || 0;
                    }
                });
                if (header.debit !== sumDebit) header.debit = sumDebit;
                if (header.credit !== sumCredit) header.credit = sumCredit;
            }
        });
    },
    { deep: true }
);

const totalDebit = computed(() => {
    return form.balances.reduce((sum, item) => sum + (item.is_header ? 0 : (parseFloat(item.debit) || 0)), 0);
});

const totalCredit = computed(() => {
    return form.balances.reduce((sum, item) => sum + (item.is_header ? 0 : (parseFloat(item.credit) || 0)), 0);
});

const difference = computed(() => {
    return Math.abs(totalDebit.value - totalCredit.value);
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};

const submit = () => {
    if (difference.value !== 0) {
        alert('Total Debit dan Kredit tidak seimbang!');
        return;
    }

    form.transform((data) => ({
        ...data,
        balances: data.balances.filter(item => !item.is_header)
    })).post(route('accounting.beginning-balances.store'), {
        preserveScroll: true,
        onSuccess: () => alert('Neraca Awal berhasil disimpan.'),
        onError: () => alert('Terjadi kesalahan. Silakan periksa kembali.'),
    });
};
</script>

<template>
    <AuthenticatedLayout title="Neraca Awal">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Neraca Awal
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="mb-4">
                            <label for="date" class="block text-sm font-medium text-gray-700">Tanggal Neraca Awal</label>
                            <input type="date" id="date" v-model="form.date" class="mt-1 block w-full sm:w-1/4 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode Akun</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Akun</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo Normal</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kredit</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(item, index) in form.balances" :key="item.coa_id" :class="{'bg-slate-50/50': item.is_header}">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono" :class="{'font-bold text-indigo-700': item.is_header}">{{ item.code }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" :style="{ paddingLeft: 1.5 + (item.code.split('.').length - 1) * 1.5 + 'rem' }">
                                            <span :class="{'font-bold text-slate-800': item.is_header}">{{ item.name }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 capitalize">
                                            <span :class="{'font-bold': item.is_header}">{{ item.normal_balance }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <input type="number" v-model="item.debit" min="0" step="0.01" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" :disabled="item.credit > 0 || item.is_header" :class="{'bg-gray-100 font-bold text-gray-600': item.is_header}">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <input type="number" v-model="item.credit" min="0" step="0.01" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" :disabled="item.debit > 0 || item.is_header" :class="{'bg-gray-100 font-bold text-gray-600': item.is_header}">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6 flex flex-col md:flex-row justify-between items-center bg-gray-50 p-4 rounded-lg shadow-sm border border-gray-200">
                            <div class="flex space-x-6 mb-4 md:mb-0">
                                <div>
                                    <span class="block text-sm text-gray-500 font-medium">Total Debit</span>
                                    <span class="text-xl font-bold text-gray-800">{{ formatCurrency(totalDebit) }}</span>
                                </div>
                                <div>
                                    <span class="block text-sm text-gray-500 font-medium">Total Kredit</span>
                                    <span class="text-xl font-bold text-gray-800">{{ formatCurrency(totalCredit) }}</span>
                                </div>
                                <div>
                                    <span class="block text-sm text-gray-500 font-medium">Selisih</span>
                                    <span :class="{'text-red-600': difference !== 0, 'text-green-600': difference === 0}" class="text-xl font-bold">
                                        {{ formatCurrency(difference) }}
                                    </span>
                                </div>
                            </div>
                            
                            <button 
                                @click="submit" 
                                :disabled="difference !== 0 || form.processing"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring focus:ring-indigo-300 disabled:opacity-50 transition"
                            >
                                Simpan Neraca Awal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
