<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    form: Object,
    maxLevel: {
        type: Number,
        default: 5
    },
    showAsOfDate: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['apply', 'exportPdf', 'exportExcel']);

const periodOptions = [
    { label: 'Bulan Ini', value: 'this_month' },
    { label: 'Bulan Lalu', value: 'last_month' },
    { label: 'Tahun Ini', value: 'this_year' },
    { label: 'Tahun Lalu', value: 'last_year' },
    { label: 'Custom', value: 'custom' },
];
const selectedPeriod = ref('this_month');

const applyPeriod = () => {
    const today = new Date();
    let start, end;
    if (selectedPeriod.value === 'this_month') {
        start = new Date(today.getFullYear(), today.getMonth(), 1);
        end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    } else if (selectedPeriod.value === 'last_month') {
        start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        end = new Date(today.getFullYear(), today.getMonth(), 0);
    } else if (selectedPeriod.value === 'this_year') {
        start = new Date(today.getFullYear(), 0, 1);
        end = new Date(today.getFullYear(), 11, 31);
    } else if (selectedPeriod.value === 'last_year') {
        start = new Date(today.getFullYear() - 1, 0, 1);
        end = new Date(today.getFullYear() - 1, 11, 31);
    }
    
    if (start && end) {
        const startStr = new Date(start.getTime() - (start.getTimezoneOffset() * 60000)).toISOString().split('T')[0];
        const endStr = new Date(end.getTime() - (end.getTimezoneOffset() * 60000)).toISOString().split('T')[0];
        
        props.form.start_date = startStr;
        props.form.end_date = endStr;
        if (props.showAsOfDate) props.form.as_of_date = endStr;
    }
};

watch(selectedPeriod, applyPeriod);

const submit = () => emit('apply');
</script>

<template>
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex flex-wrap gap-4 items-end mb-6">
        <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Periode Cepat</label>
            <select v-model="selectedPeriod" class="rounded-xl border-slate-200 text-sm w-36">
                <option v-for="opt in periodOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
        </div>
        <div v-if="!showAsOfDate">
            <label class="block text-xs font-semibold text-slate-500 mb-1">Mulai Tanggal</label>
            <input type="date" v-model="form.start_date" :disabled="selectedPeriod !== 'custom'" class="rounded-xl border-slate-200 text-sm w-36" />
        </div>
        <div v-if="!showAsOfDate">
            <label class="block text-xs font-semibold text-slate-500 mb-1">Sampai Tanggal</label>
            <input type="date" v-model="form.end_date" :disabled="selectedPeriod !== 'custom'" class="rounded-xl border-slate-200 text-sm w-36" />
        </div>
        <div v-if="showAsOfDate">
            <label class="block text-xs font-semibold text-slate-500 mb-1">Per Tanggal</label>
            <input type="date" v-model="form.as_of_date" :disabled="selectedPeriod !== 'custom'" class="rounded-xl border-slate-200 text-sm w-36" />
        </div>
        
        <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Tingkat Detail COA</label>
            <select v-model="form.level" class="rounded-xl border-slate-200 text-sm w-32">
                <option v-for="i in maxLevel" :key="i" :value="i">Level {{ i }}</option>
            </select>
        </div>
        
        <div class="flex flex-col gap-2 pb-1">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" v-model="form.show_zero" class="rounded text-indigo-600 border-slate-300 w-4 h-4" />
                <span class="text-xs font-semibold text-slate-700">Tampilkan Nilai Nol</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" v-model="form.show_code" class="rounded text-indigo-600 border-slate-300 w-4 h-4" />
                <span class="text-xs font-semibold text-slate-700">Tampilkan Kode Akun</span>
            </label>
        </div>

        <button @click="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">
            Terapkan Filter
        </button>

        <div class="flex-1"></div>
        <div class="flex gap-2">
            <button @click="$emit('exportPdf')" class="px-4 py-2.5 bg-rose-600 text-white rounded-xl text-sm font-semibold hover:bg-rose-700 transition">
                Cetak PDF
            </button>
            <button @click="$emit('exportExcel')" class="px-4 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700 transition">
                Cetak Excel
            </button>
        </div>
    </div>
</template>
