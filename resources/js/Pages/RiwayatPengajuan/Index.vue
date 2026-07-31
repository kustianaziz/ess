<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Search, Eye, Clock, Trash2, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
  requests: Array,
  filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const type = ref(props.filters.type || '');
const deleteItem = ref(null);

const applyFilters = () => {
  router.get(
    route('riwayat-pengajuan.index'),
    {
      search: search.value,
      status: status.value,
      type: type.value,
    },
    { preserveState: true, replace: true }
  );
};

const confirmDelete = (item) => {
  deleteItem.value = item;
};

const executeDelete = () => {
  if (!deleteItem.value) return;
  router.delete(route('riwayat-pengajuan.destroy', { type: deleteItem.value.type, id: deleteItem.value.id }), {
    onSuccess: () => {
      deleteItem.value = null;
    },
  });
};

watch([status, type], applyFilters);
</script>

<template>
  <Head title="Riwayat Pengajuan" />

  <AuthenticatedLayout>
    <div class="max-w-7xl mx-auto space-y-6">
      <!-- Title & Filters Bar -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
        <div>
          <h1 class="text-xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
            <Clock class="w-5 h-5 text-indigo-600" />
            Riwayat Pengajuan
          </h1>
          <p class="text-xs text-slate-400 mt-1">
            Daftar seluruh riwayat pengajuan reimbursement, operasional, dan cuti Anda.
          </p>
        </div>

        <!-- Filters Form -->
        <div class="flex flex-wrap items-center gap-3">
          <!-- Search -->
          <div class="relative">
            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              v-model="search"
              @keyup.enter="applyFilters"
              placeholder="Cari No. Pengajuan..."
              class="pl-9 pr-4 py-2 text-xs border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 w-48"
            />
          </div>

          <!-- Modul Type Filter -->
          <select
            v-model="type"
            class="text-xs border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 py-2"
          >
            <option value="">Semua Layanan</option>
            <option value="reimbursement">Reimbursement</option>
            <option value="operasional">Konsumsi / Operasional</option>
            <option value="cuti">Cuti</option>
          </select>

          <!-- Status Filter -->
          <select
            v-model="status"
            class="text-xs border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 py-2"
          >
            <option value="">Semua Status</option>
            <option value="submitted">Menunggu Persetujuan</option>
            <option value="approved">Disetujui</option>
            <option value="rejected">Ditolak</option>
            <option value="paid">Sudah Dibayarkan</option>
            <option value="completed">Selesai</option>
          </select>
        </div>
      </div>

      <!-- Data Table -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 border-b border-slate-100 text-xs uppercase font-semibold text-slate-500 tracking-wider">
              <tr>
                <th class="px-6 py-4">No. Pengajuan</th>
                <th class="px-6 py-4">Jenis Layanan</th>
                <th class="px-6 py-4">Kategori</th>
                <th class="px-6 py-4">Tanggal / Waktu</th>
                <th class="px-6 py-4">Nominal / Info</th>
                <th class="px-6 py-4">Status</th>
                <th class="px-6 py-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="item in requests"
                :key="`${item.type}-${item.id}`"
                class="hover:bg-slate-50/60 transition-colors"
              >
                <td class="px-6 py-4 font-bold text-slate-800">
                  {{ item.request_number }}
                </td>
                <td class="px-6 py-4 text-xs font-semibold text-slate-600">
                  {{ item.type_label }}
                </td>
                <td class="px-6 py-4 text-xs text-slate-500">
                  {{ item.category }}
                </td>
                <td class="px-6 py-4 text-xs text-slate-500 font-medium">
                  {{ item.date }}
                </td>
                <td class="px-6 py-4 text-xs font-bold text-slate-800">
                  <span v-if="item.amount !== null">Rp {{ new Intl.NumberFormat('id-ID').format(item.amount) }}</span>
                  <span v-else class="text-slate-500 font-normal">-</span>
                </td>
                <td class="px-6 py-4">
                  <StatusBadge :status="item.status" />
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <Link
                      :href="route('riwayat-pengajuan.show', { type: item.type, id: item.id })"
                      class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 px-2.5 py-1.5 rounded-lg hover:bg-indigo-50 transition-all"
                      title="Lihat Detail"
                    >
                      <Eye class="w-3.5 h-3.5" />
                      <span>Detail</span>
                    </Link>

                    <!-- Hapus Option for Unapproved Requests -->
                    <button
                      v-if="item.status !== 'approved' && item.status !== 'paid' && item.status !== 'completed'"
                      @click="confirmDelete(item)"
                      class="inline-flex items-center gap-1 text-xs font-semibold text-rose-600 hover:text-rose-800 px-2.5 py-1.5 rounded-lg hover:bg-rose-50 transition-all"
                      title="Hapus Pengajuan Belum Disetujui"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
                      <span>Hapus</span>
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="requests.length === 0">
                <td colspan="7" class="px-6 py-12 text-center text-slate-400 text-sm">
                  Belum ada riwayat pengajuan yang ditemukan.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div v-if="deleteItem" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-in fade-in">
      <div class="bg-white w-full max-w-md rounded-2xl p-6 shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center gap-3 text-rose-600">
          <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center shrink-0">
            <AlertTriangle class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900">Konfirmasi Hapus Pengajuan</h3>
            <p class="text-xs text-slate-400">Tindakan ini tidak dapat dibatalkan.</p>
          </div>
        </div>

        <p class="text-xs text-slate-600 leading-relaxed">
          Apakah Anda yakin ingin menghapus pengajuan <span class="font-bold text-slate-900">{{ deleteItem.request_number }}</span> ({{ deleteItem.type_label }})?
        </p>

        <div class="flex items-center justify-end gap-3 pt-2">
          <button
            @click="deleteItem = null"
            class="px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 text-slate-600 hover:bg-slate-50"
          >
            Batal
          </button>
          <button
            @click="executeDelete"
            class="px-4 py-2 rounded-xl text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 shadow-md transition-all flex items-center gap-1.5"
          >
            <Trash2 class="w-3.5 h-3.5" />
            <span>Ya, Hapus Pengajuan</span>
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
