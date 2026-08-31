<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { CheckCircle2, XCircle, CheckSquare, Clock, History, Eye } from 'lucide-vue-next';

const props = defineProps({
  pendingApprovals: Array,
});

const selectedItem = ref(null);
const actionType = ref(''); // 'approve' or 'reject'
const notesInput = ref('');

const approveForm = useForm({
  notes: '',
});

const rejectForm = useForm({
  reason: '',
});

const goToDetail = (item) => {
  router.visit(route('riwayat-pengajuan.show', { type: item.type, id: item.id, from: 'approval' }));
};

const openApproveModal = (item) => {
  selectedItem.value = item;
  actionType.value = 'approve';
  notesInput.value = ['lembur', 'klaim-lembur'].includes(item.type) ? '' : 'Pengajuan disetujui.';
};

const openRejectModal = (item) => {
  selectedItem.value = item;
  actionType.value = 'reject';
  notesInput.value = '';
};

const submitApproval = () => {
  if (!selectedItem.value) return;

  if (actionType.value === 'approve') {
    if (['lembur', 'klaim-lembur'].includes(selectedItem.value.type) && !notesInput.value) {
      alert('Catatan approval wajib diisi untuk pengajuan lembur.');
      return;
    }
    approveForm.notes = notesInput.value || 'Pengajuan disetujui.';
    approveForm.post(route('approval.approve', { type: selectedItem.value.type, id: selectedItem.value.id }), {
      onSuccess: () => {
        selectedItem.value = null;
      },
    });
  } else {
    if (!notesInput.value) {
      alert('Alasan penolakan wajib diisi.');
      return;
    }
    rejectForm.reason = notesInput.value;
    rejectForm.post(route('approval.reject', { type: selectedItem.value.type, id: selectedItem.value.id }), {
      onSuccess: () => {
        selectedItem.value = null;
      },
    });
  }
};
</script>

<template>
  <Head title="Persetujuan (Approval)" />

  <AuthenticatedLayout>
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
            <CheckSquare class="w-5 h-5 text-amber-500" />
            Daftar Persetujuan (Approval)
          </h1>
          <p class="text-xs text-slate-400 mt-1">
            Pengajuan karyawan yang membutuhkan verifikasi & persetujuan Anda. (Klik baris untuk lihat detail).
          </p>
        </div>

        <div class="flex items-center gap-2 self-stretch sm:self-auto">
          <span class="px-4 py-2 rounded-xl bg-amber-50 text-amber-700 text-xs font-bold border border-amber-200 flex items-center gap-2">
            <CheckSquare class="w-4 h-4" />
            <span>Antrean Persetujuan</span>
          </span>

          <Link
            :href="route('approval.history')"
            class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-bold transition-all flex items-center gap-2"
          >
            <History class="w-4 h-4 text-emerald-600" />
            <span>Riwayat Persetujuan</span>
          </Link>
        </div>
      </div>

      <!-- MOBILE CARDS VIEW (md:hidden) -->
      <div class="block md:hidden space-y-3">
        <div
          v-for="item in pendingApprovals"
          :key="'mobile_' + item.approval_id"
          class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm space-y-3 hover:border-indigo-300 transition-all"
        >
          <div class="flex items-center justify-between gap-2">
            <span class="text-xs font-mono font-bold text-slate-800">{{ item.request_number }}</span>
            <span class="px-2 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 rounded-full">
              Level {{ item.level }}
            </span>
          </div>

          <div>
            <h4 class="font-bold text-sm text-slate-900">{{ item.applicant_name }}</h4>
            <p class="text-xs text-slate-500">{{ item.applicant_division }} • <span class="font-semibold text-slate-700">{{ item.type_label }}</span></p>
            <span class="text-[10px] text-slate-400 block mt-1">Disubmit: {{ item.submitted_at }}</span>
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center gap-2">
            <Link
              :href="route('riwayat-pengajuan.show', { type: item.type, id: item.id, from: 'approval' })"
              class="px-3 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center gap-1 flex-1"
            >
              <Eye class="w-3.5 h-3.5" />
              <span>Detail</span>
            </Link>

            <button
              @click="openApproveModal(item)"
              class="px-3 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold flex items-center justify-center gap-1 flex-1 shadow-sm"
            >
              <CheckCircle2 class="w-3.5 h-3.5" />
              <span>Setujui</span>
            </button>

            <button
              @click="openRejectModal(item)"
              class="px-3 py-2 rounded-xl bg-rose-600 text-white text-xs font-bold flex items-center justify-center gap-1 flex-1 shadow-sm"
            >
              <XCircle class="w-3.5 h-3.5" />
              <span>Tolak</span>
            </button>
          </div>
        </div>

        <div v-if="pendingApprovals.length === 0" class="bg-white p-8 rounded-2xl border border-slate-200 text-center text-slate-400 text-xs font-medium">
          Tidak ada pengajuan yang membutuhkan persetujuan saat ini.
        </div>
      </div>

      <!-- DESKTOP TABLE VIEW (hidden md:block) -->
      <div class="hidden md:block bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 border-b border-slate-100 text-xs uppercase font-semibold text-slate-500 tracking-wider">
              <tr>
                <th class="px-6 py-4">No. Pengajuan</th>
                <th class="px-6 py-4">Pemohon / Divisi</th>
                <th class="px-6 py-4">Jenis Layanan</th>
                <th class="px-6 py-4">Status L1 (Atasan)</th>
                <th class="px-6 py-4">Status L2 (HRD)</th>
                <th class="px-6 py-4">Status Keseluruhan</th>
                <th class="px-6 py-4 text-right">Aksi Persetujuan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="item in pendingApprovals"
                :key="item.approval_id"
                @click="goToDetail(item)"
                class="hover:bg-indigo-50/50 transition-colors cursor-pointer group"
                title="Klik untuk membuka detail pengajuan"
              >
                <td class="px-6 py-4 font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">
                  {{ item.request_number }}
                </td>
                <td class="px-6 py-4">
                  <span class="font-bold text-slate-800 block">{{ item.applicant_name }}</span>
                  <span class="text-[10px] text-slate-500">{{ item.applicant_division }}</span>
                </td>
                <td class="px-6 py-4 text-xs font-medium text-slate-700">
                  {{ item.type_label }}
                </td>
                <td class="px-6 py-4">
                  <div class="flex flex-col items-start gap-1">
                    <span
                      v-if="item.l1_status === 'approved'"
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                    >
                      <CheckCircle2 class="w-3 h-3" /> Approved
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200"
                    >
                      Pending
                    </span>
                    <span class="text-[9px] text-slate-400 mt-0.5">{{ item.l1_approver }}</span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex flex-col items-start gap-1">
                    <span
                      v-if="item.l2_status === 'approved'"
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                    >
                      <CheckCircle2 class="w-3 h-3" /> Approved
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200"
                    >
                      {{ item.l2_status === '-' ? '-' : 'Pending' }}
                    </span>
                    <span class="text-[9px] text-slate-400 mt-0.5">{{ item.l2_approver }}</span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex flex-col items-start gap-1">
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 tracking-wider">
                      {{ item.overall_status_label }}
                    </span>
                    <span class="text-[9px] text-slate-400 font-semibold">Aktif di L{{ item.level }}</span>
                  </div>
                </td>
                <td class="px-6 py-4 text-right" @click.stop>
                  <div class="flex items-center justify-end gap-2">
                    <Link
                      :href="route('riwayat-pengajuan.show', { type: item.type, id: item.id, from: 'approval' })"
                      @click.stop
                      class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-900 text-slate-600 hover:text-white text-xs font-semibold flex items-center gap-1 transition-all"
                      title="Lihat Detail Rincian"
                    >
                      <Eye class="w-3.5 h-3.5" />
                      <span>Detail</span>
                    </Link>

                    <button
                      @click.stop="openApproveModal(item)"
                      class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold flex items-center gap-1 transition-all shadow-sm"
                    >
                      <CheckCircle2 class="w-3.5 h-3.5" />
                      <span>Setujui</span>
                    </button>

                    <button
                      @click.stop="openRejectModal(item)"
                      class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold flex items-center gap-1 transition-all shadow-sm"
                    >
                      <XCircle class="w-3.5 h-3.5" />
                      <span>Tolak</span>
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="pendingApprovals.length === 0">
                <td colspan="7" class="px-6 py-12 text-center text-slate-400 text-sm">
                  Tidak ada pengajuan yang membutuhkan persetujuan saat ini.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Modal Confirmation -->
    <div v-if="selectedItem" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-in fade-in">
      <div class="bg-white w-full max-w-md rounded-2xl p-6 shadow-2xl border border-slate-100 space-y-4">
        <h3 class="text-base font-bold text-slate-800">
          {{ actionType === 'approve' ? 'Konfirmasi Persetujuan' : 'Konfirmasi Penolakan' }}
        </h3>
        <p class="text-xs text-slate-500">
          Apakah Anda yakin ingin {{ actionType === 'approve' ? 'menyetujui' : 'menolak' }} pengajuan <span class="font-bold text-slate-800">{{ selectedItem.request_number }}</span> dari <span class="font-bold text-slate-800">{{ selectedItem.applicant_name }}</span>?
        </p>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            {{ 
              actionType === 'approve' 
                ? (['lembur', 'klaim-lembur'].includes(selectedItem?.type) ? 'Catatan Approval (Wajib)' : 'Catatan Approval (Opsional)') 
                : 'Alasan Penolakan (Wajib)' 
            }}
          </label>
          <textarea
            v-model="notesInput"
            rows="3"
            class="w-full text-xs border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 resize-none"
            :placeholder="
              actionType === 'approve' 
                ? (['lembur', 'klaim-lembur'].includes(selectedItem?.type) ? 'Wajib melampirkan catatan...' : 'Catatan tambahan...') 
                : 'Jelaskan alasan penolakan...'
            "
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
          <button
            @click="selectedItem = null"
            class="px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 text-slate-600 hover:bg-slate-50"
          >
            Batal
          </button>
          <button
            @click="submitApproval"
            class="px-4 py-2 rounded-xl text-xs font-semibold text-white shadow-md transition-all"
            :class="actionType === 'approve' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'"
          >
            {{ actionType === 'approve' ? 'Ya, Setujui' : 'Ya, Tolak' }}
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
