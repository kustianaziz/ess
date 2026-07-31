<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { CheckSquare, History, Search, Filter, RotateCcw, Eye, CheckCircle2, XCircle, UserCheck, ShieldCheck, Undo2 } from 'lucide-vue-next';

const props = defineProps({
  approvals: Object,
  filters: Object,
});

const scope = ref(props.filters?.scope || 'all');
const search = ref(props.filters?.search || '');
const type = ref(props.filters?.type || 'all');
const status = ref(props.filters?.status || 'all');
const level = ref(props.filters?.level || 'all');
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');

const unapproveItem = ref(null);

const applyFilters = () => {
  router.get(
    route('approval.history'),
    {
      scope: scope.value,
      search: search.value,
      type: type.value,
      status: status.value,
      level: level.value,
      start_date: startDate.value,
      end_date: endDate.value,
    },
    { preserveState: true, preserveScroll: true }
  );
};

const resetFilters = () => {
  scope.value = 'all';
  search.value = '';
  type.value = 'all';
  status.value = 'all';
  level.value = 'all';
  startDate.value = '';
  endDate.value = '';
  applyFilters();
};

const confirmUnapprove = (item) => {
  unapproveItem.value = item;
};

const executeUnapprove = () => {
  if (!unapproveItem.value) return;
  router.post(route('approval.unapprove', { type: unapproveItem.value.type, id: unapproveItem.value.id }), {}, {
    onSuccess: () => {
      unapproveItem.value = null;
    },
  });
};

let searchTimeout = null;
watch(search, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 400);
});
</script>

<template>
  <Head title="Riwayat Persetujuan" />

  <AuthenticatedLayout>
    <div class="max-w-7xl mx-auto space-y-6">
      <!-- Header Bar with Navigation Tabs -->
      <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl font-bold text-slate-800 tracking-tight flex items-center gap-2.5">
            <History class="w-6 h-6 text-indigo-600" />
            Riwayat Persetujuan Transaksi
          </h1>
          <p class="text-xs text-slate-400 mt-1">
            Histori lengkap keputusan persetujuan/penolakan beserta fitur Batal Approve.
          </p>
        </div>

        <div class="flex items-center gap-2 self-stretch sm:self-auto">
          <Link
            :href="route('approval.index')"
            class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-bold transition-all flex items-center gap-2"
          >
            <CheckSquare class="w-4 h-4 text-amber-500" />
            <span>Antrean Persetujuan</span>
          </Link>

          <span class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-bold shadow-md shadow-indigo-600/30 flex items-center gap-2">
            <History class="w-4 h-4" />
            <span>Histori Selesai</span>
          </span>
        </div>
      </div>

      <!-- Scope Filter Tabs (Semua vs Oleh Saya) -->
      <div class="bg-white p-2 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-2">
        <button
          @click="scope = 'all'; applyFilters()"
          class="flex-1 py-2 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2"
          :class="scope === 'all' ? 'bg-slate-900 text-white shadow-md' : 'text-slate-600 hover:bg-slate-50'"
        >
          <ShieldCheck class="w-4 h-4" />
          <span>Semua Riwayat Perusahaan</span>
        </button>

        <button
          @click="scope = 'my_approvals'; applyFilters()"
          class="flex-1 py-2 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2"
          :class="scope === 'my_approvals' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-50'"
        >
          <UserCheck class="w-4 h-4" />
          <span>Dieksekusi Oleh Saya</span>
        </button>
      </div>

      <!-- Search & Filters Container -->
      <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
            <Filter class="w-4 h-4 text-indigo-600" />
            Filter Pencarian
          </h3>

          <button
            @click="resetFilters"
            class="text-xs text-slate-500 hover:text-indigo-600 font-semibold flex items-center gap-1 transition-colors"
          >
            <RotateCcw class="w-3.5 h-3.5" />
            <span>Reset Filter</span>
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
          <!-- Search Bar -->
          <div class="lg:col-span-2 relative">
            <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              type="text"
              v-model="search"
              placeholder="No. Transaksi / Nama Karyawan..."
              class="w-full pl-10 pr-4 py-2 text-xs border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>

          <!-- Jenis Pengajuan -->
          <div>
            <select
              v-model="type"
              @change="applyFilters"
              class="w-full text-xs border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="all">Semua Layanan</option>
              <option value="reimbursement">Reimbursement</option>
              <option value="operasional">Operasional</option>
              <option value="cuti">Cuti Karyawan</option>
            </select>
          </div>

          <!-- Status Keputusan -->
          <div>
            <select
              v-model="status"
              @change="applyFilters"
              class="w-full text-xs border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="all">Semua Status</option>
              <option value="approved">Disetujui</option>
              <option value="rejected">Ditolak</option>
            </select>
          </div>

          <!-- Level Approval -->
          <div>
            <select
              v-model="level"
              @change="applyFilters"
              class="w-full text-xs border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="all">Semua Level</option>
              <option value="1">Level 1 (Atasan)</option>
              <option value="2">Level 2 (HRD/Finance)</option>
            </select>
          </div>

          <!-- Tanggal Mulai -->
          <div>
            <input
              type="date"
              v-model="startDate"
              @change="applyFilters"
              class="w-full text-xs border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500"
              title="Dari Tanggal"
            />
          </div>
        </div>
      </div>

      <!-- History Approval Table -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto custom-scrollbar">
          <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-slate-50 border-b border-slate-100 uppercase text-[10px] font-bold text-slate-400 tracking-wider">
              <tr>
                <th class="py-3.5 px-4">No. Transaksi & Jenis</th>
                <th class="py-3.5 px-4">Pemohon / Divisi</th>
                <th class="py-3.5 px-4">Nominal / Durasi</th>
                <th class="py-3.5 px-4">Eksekutor Penyetuju</th>
                <th class="py-3.5 px-4">Status Langkah Approval</th>
                <th class="py-3.5 px-4">Waktu Eksekusi</th>
                <th class="py-3.5 px-4 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr
                v-for="item in approvals.data"
                :key="item.approval_id"
                class="hover:bg-slate-50/70 transition-colors"
              >
                <td class="py-3.5 px-4">
                  <span class="font-bold text-slate-900 block text-xs">{{ item.request_number }}</span>
                  <span class="text-[10px] text-slate-400 font-semibold">{{ item.type_label }}</span>
                </td>
                <td class="py-3.5 px-4">
                  <span class="font-bold text-slate-800 block text-xs">{{ item.applicant_name }}</span>
                  <span class="text-[10px] text-slate-400">{{ item.applicant_division }}</span>
                </td>
                <td class="py-3.5 px-4 font-bold text-slate-800">
                  {{ item.amount_or_days }}
                </td>
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-1.5">
                    <span class="font-bold text-slate-900 block text-xs">{{ item.approver_name }}</span>
                    <span
                      v-if="item.is_acted_by_me"
                      class="px-1.5 py-0.5 text-[9px] bg-indigo-100 text-indigo-700 rounded-md font-bold shrink-0"
                    >
                      Oleh Saya
                    </span>
                  </div>
                  <span class="text-[10px] text-slate-400 block">{{ item.approver_role }}</span>
                </td>
                <td class="py-3.5 px-4">
                  <div class="flex flex-col items-start gap-1">
                    <span
                      v-if="item.status === 'approved'"
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                    >
                      <CheckCircle2 class="w-3 h-3" /> {{ item.step_description }}
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200"
                    >
                      <XCircle class="w-3 h-3" /> {{ item.step_description }}
                    </span>
                  </div>
                </td>
                <td class="py-3.5 px-4 text-[11px] text-slate-500 whitespace-nowrap">
                  {{ item.acted_at }}
                </td>
                <td class="py-3.5 px-4 text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    <Link
                      :href="route('riwayat-pengajuan.show', { type: item.type, id: item.id })"
                      class="p-2 rounded-xl bg-slate-100 hover:bg-slate-900 text-slate-600 hover:text-white transition-all inline-flex items-center justify-center"
                      title="Lihat Detail Transaksi"
                    >
                      <Eye class="w-4 h-4" />
                    </Link>

                    <!-- Batal Approve Action Button -->
                    <button
                      v-if="item.status === 'approved' && item.overall_status !== 'paid' && item.overall_status !== 'completed'"
                      @click="confirmUnapprove(item)"
                      class="p-2 rounded-xl bg-amber-50 hover:bg-amber-600 text-amber-700 hover:text-white border border-amber-200 transition-all inline-flex items-center justify-center"
                      title="Batalkan Persetujuan (Batal Approve)"
                    >
                      <Undo2 class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="!approvals.data || approvals.data.length === 0">
                <td colspan="7" class="py-12 text-center text-slate-400">
                  <History class="w-10 h-10 mx-auto text-slate-300 stroke-[1.5] mb-2" />
                  <p class="text-xs font-semibold">Tidak ada riwayat persetujuan yang cocok dengan filter saat ini.</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Links -->
        <div v-if="approvals.links && approvals.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
          <span class="text-xs text-slate-400 font-medium">
            Menampilkan {{ approvals.from || 0 }} - {{ approvals.to || 0 }} dari {{ approvals.total }} riwayat
          </span>

          <div class="flex items-center gap-1">
            <Link
              v-for="(link, idx) in approvals.links"
              :key="idx"
              :href="link.url || '#'"
              v-html="link.label"
              class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
              :class="{
                'bg-indigo-600 text-white': link.active,
                'text-slate-600 hover:bg-slate-100': !link.active && link.url,
                'text-slate-300 cursor-not-allowed': !link.url
              }"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Konfirmasi Batal Approve -->
    <div v-if="unapproveItem" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-in fade-in">
      <div class="bg-white w-full max-w-md rounded-2xl p-6 shadow-2xl border border-slate-100 space-y-4">
        <div class="flex items-center gap-3 text-amber-600">
          <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
            <Undo2 class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900">Batalkan Persetujuan (Unapprove)</h3>
            <p class="text-xs text-slate-400">Kembalikan transaksi ke status pending.</p>
          </div>
        </div>

        <p class="text-xs text-slate-600 leading-relaxed">
          Apakah Anda yakin ingin membatalkan persetujuan untuk transaksi <span class="font-bold text-slate-900">{{ unapproveItem.request_number }}</span>?
        </p>

        <div class="flex items-center justify-end gap-3 pt-2">
          <button
            @click="unapproveItem = null"
            class="px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 text-slate-600 hover:bg-slate-50"
          >
            Batal
          </button>
          <button
            @click="executeUnapprove"
            class="px-4 py-2 rounded-xl text-xs font-semibold text-white bg-amber-600 hover:bg-amber-700 shadow-md transition-all flex items-center gap-1.5"
          >
            <Undo2 class="w-3.5 h-3.5" />
            <span>Ya, Batalkan Approve</span>
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
