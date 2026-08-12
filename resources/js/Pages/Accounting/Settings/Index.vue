<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Settings, Save, CheckCircle2 } from 'lucide-vue-next';

const props = defineProps({
    coas: Array,
    settings: Object,
});

const form = useForm({
    retained_earnings_coa_id: props.settings.retained_earnings_coa_id || '',
    current_earnings_coa_id: props.settings.current_earnings_coa_id || '',
});

const saveSettings = () => {
    form.post(route('accounting.settings.save'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Pengaturan Akuntansi" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold leading-tight text-gray-800 flex items-center gap-2">
                    <Settings class="w-5 h-5 text-indigo-600" />
                    <span>Pengaturan Akuntansi</span>
                </h2>
            </div>
        </template>

        <div class="max-w-3xl mx-auto py-6">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-bold text-slate-800 text-base">Pemetaan Akun Laporan Keuangan</h3>
                    <p class="text-xs text-slate-500 mt-1">Petakan akun spesifik untuk digunakan dalam kalkulasi otomatis pada Neraca (Balance Sheet).</p>
                </div>

                <form @submit.prevent="saveSettings" class="p-6 space-y-6">
                    <!-- Retained Earnings Account Selection -->
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-800">
                            Akun Laba Ditahan (Retained Earnings)
                        </label>
                        <select
                            v-model="form.retained_earnings_coa_id"
                            class="w-full text-sm border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-medium"
                        >
                            <option value="">-- Buat Baris Otomatis (3-RE) --</option>
                            <option v-for="coa in coas.filter(c => c.type === 'modal')" :key="coa.id" :value="coa.id">
                                [{{ coa.code }}] {{ coa.name }}
                            </option>
                        </select>
                        <p class="text-[11px] text-slate-500">
                            Pilih akun ekuitas untuk menampung saldo Laba Ditahan dari tahun-tahun sebelumnya. Jika dikosongkan, sistem akan otomatis membuat baris virtual <code>3-RE</code>.
                        </p>
                    </div>

                    <!-- Current Year Earnings Account Selection -->
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-800">
                            Akun Laba Tahun Berjalan (Current Year Earnings)
                        </label>
                        <select
                            v-model="form.current_earnings_coa_id"
                            class="w-full text-sm border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-medium"
                        >
                            <option value="">-- Buat Baris Otomatis (3-CY) --</option>
                            <option v-for="coa in coas.filter(c => c.type === 'modal')" :key="coa.id" :value="coa.id">
                                [{{ coa.code }}] {{ coa.name }}
                            </option>
                        </select>
                        <p class="text-[11px] text-slate-500">
                            Pilih akun ekuitas untuk menampung Laba/Rugi bersih tahun berjalan. Jika dikosongkan, sistem akan otomatis membuat baris virtual <code>3-CY</code>.
                        </p>
                    </div>

                    <!-- Flash Messages -->
                    <div v-if="form.recentlySuccessful" class="p-4 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center gap-2.5">
                        <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                        <span class="text-xs font-bold text-emerald-800">Pengaturan berhasil disimpan!</span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-4 border-t border-slate-100 flex justify-end">
                        <PrimaryButton :disabled="form.processing" class="gap-2">
                            <Save class="w-4 h-4" />
                            <span>Simpan Pengaturan</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
