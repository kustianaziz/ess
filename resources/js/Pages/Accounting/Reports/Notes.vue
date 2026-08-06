<script setup>
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    notes: Array,
});

const isEditing = ref(false);
const form = useForm({
    id: null,
    title: '',
    period_date: '',
    content: '',
});

const editNote = (note) => {
    isEditing.value = true;
    form.id = note.id;
    form.title = note.title;
    form.period_date = note.period_date;
    form.content = note.content;
};

const cancelEdit = () => {
    isEditing.value = false;
    form.reset();
};

const saveNote = () => {
    if (form.id) {
        form.put(route('accounting.notes.update', form.id), {
            preserveScroll: true,
            onSuccess: () => cancelEdit(),
        });
    } else {
        form.post(route('accounting.notes.store'), {
            preserveScroll: true,
            onSuccess: () => cancelEdit(),
        });
    }
};

const deleteNote = (id) => {
    if (confirm('Yakin ingin menghapus catatan ini?')) {
        useForm({}).delete(route('accounting.notes.destroy', id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="CALK" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">Catatan Atas Laporan Keuangan (CALK)</h2>
        </template>
    
        <div class="p-6">
            <div class="max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- Notes List -->
                <div class="md:col-span-2 space-y-6">
                    <div v-if="notes.length === 0" class="bg-white p-8 rounded-3xl text-center text-slate-400 border border-slate-100 shadow-sm">
                        Belum ada catatan laporan keuangan.
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="note in notes" :key="note.id" class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-lg font-bold text-indigo-700">{{ note.title }}</h3>
                                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mt-1">Periode: {{ note.period_date }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="editNote(note)" class="text-sky-600 hover:text-sky-800 text-sm font-semibold">Edit</button>
                                    <button @click="deleteNote(note.id)" class="text-rose-600 hover:text-rose-800 text-sm font-semibold">Hapus</button>
                                </div>
                            </div>
                            <div class="prose prose-sm max-w-none text-slate-600 whitespace-pre-wrap">{{ note.content }}</div>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 h-fit sticky top-6">
                    <h2 class="text-lg font-bold text-slate-800 mb-4">{{ isEditing ? 'Edit Catatan' : 'Tambah Catatan Baru' }}</h2>
                    <form @submit.prevent="saveNote" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Judul Catatan</label>
                            <input type="text" v-model="form.title" required class="w-full rounded-xl border-slate-200 text-sm" placeholder="Misal: Penjelasan Kas & Bank" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Periode Tanggal</label>
                            <input type="date" v-model="form.period_date" required class="w-full rounded-xl border-slate-200 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Isi Catatan</label>
                            <textarea v-model="form.content" required rows="6" class="w-full rounded-xl border-slate-200 text-sm" placeholder="Isi penjelasan atau rincian..."></textarea>
                        </div>
                        
                        <div class="flex gap-3 pt-2">
                            <button type="submit" class="flex-1 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 transition" :disabled="form.processing">
                                Simpan
                            </button>
                            <button v-if="isEditing" type="button" @click="cancelEdit" class="px-4 py-2.5 bg-slate-100 text-slate-600 rounded-xl text-sm font-semibold hover:bg-slate-200 transition">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
