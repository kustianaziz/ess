<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { CheckCircle2, XCircle, CheckSquare, Clock, History, Eye, Paperclip, ExternalLink, FileText } from 'lucide-vue-next';

const props = defineProps({
  pendingApprovals: Array,
});

const getTypeBadgeColor = (type) => {
  switch (type) {
    case 'lembur':
      return 'bg-indigo-50 text-indigo-700 border-indigo-200';
    case 'klaim-lembur':
      return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    case 'reimbursement':
      return 'bg-blue-50 text-blue-700 border-blue-200';
    case 'operasional':
      return 'bg-amber-50 text-amber-700 border-amber-200';
    case 'cuti':
      return 'bg-purple-50 text-purple-700 border-purple-200';
    case 'perjalanan-dinas':
      return 'bg-sky-50 text-sky-700 border-sky-200';
    default:
      return 'bg-slate-50 text-slate-700 border-slate-200';
  }
};

const selectedItem = ref(null);
const actionType = ref(''); // 'approve' or 'reject'
const notesInput = ref('');
const amountInput = ref('');

const approveForm = useForm({
  notes: '',
  amount: '',
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
  amountInput.value = item.amount || '';
};

const openRejectModal = (item) => {
  selectedItem.value = item;
  actionType.value = 'reject';
  notesInput.value = '';
  amountInput.value = '';
};

const submitApproval = () => {
  if (!selectedItem.value) return;

  if (actionType.value === 'approve') {
    if (['lembur', 'klaim-lembur'].includes(selectedItem.value.type) && !notesInput.value) {
      alert('Catatan approval wajib diisi untuk pengajuan lembur.');
      return;
    }
    approveForm.notes = notesInput.value || 'Pengajuan disetujui.';
    approveForm.amount = amountInput.value;
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
            <div class="flex items-center gap-1.5">
              <span 
                class="px-2 py-0.5 text-[10px] font-bold border rounded-full uppercase tracking-wider"
                :class="getTypeBadgeColor(item.type)"
              >
                {{ item.type_label }}
              </span>
              <span class="px-2 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 rounded-full">
                L{{ item.level }}
              </span>
            </div>
          </div>

          <div>
            <h4 class="font-bold text-sm text-slate-900">{{ item.applicant_name }}</h4>
            <p class="text-xs text-slate-500 font-medium">{{ item.applicant_position }}</p>
            <span class="text-[10px] text-slate-400 block mt-0.5">Disubmit: {{ item.submitted_at }}</span>
          </div>

          <!-- Rincian & Nominal -->
          <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between text-xs">
            <span class="text-slate-600 font-medium">{{ item.summary_info }}</span>
            <span v-if="item.amount_formatted" class="font-black text-slate-900">
              {{ item.amount_formatted }}
            </span>
          </div>

          <!-- Catatan Pengajuan -->
          <div v-if="item.notes" class="text-xs text-slate-600 bg-amber-50/60 border border-amber-200/60 p-2.5 rounded-xl space-y-0.5">
            <span class="text-[10px] font-bold text-amber-800 uppercase tracking-wider block">Catatan Pengajuan:</span>
            <p class="italic text-slate-700">"{{ item.notes }}"</p>
          </div>

          <!-- Lampiran File -->
          <div v-if="item.attachments?.length > 0" class="space-y-1.5 pt-1">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Lampiran File ({{ item.attachments.length }}):</span>
            <div class="flex flex-wrap gap-1.5">
              <a
                v-for="att in item.attachments"
                :key="att.id"
                :href="att.url"
                target="_blank"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold border border-indigo-200 transition-colors"
                :title="att.file_name"
              >
                <Paperclip class="w-3.5 h-3.5 text-indigo-500" />
                <span class="max-w-[160px] truncate">{{ att.file_name }}</span>
                <ExternalLink class="w-3 h-3 text-indigo-400 shrink-0" />
              </a>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center gap-2">
            <Link
              :href="route('riwayat-pengajuan.show', { type: item.type, id: item.id, from: 'approval' })"
              class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold flex items-center justify-center gap-1 flex-1 transition-all"
            >
              <Eye class="w-3.5 h-3.5" />
              <span>Detail</span>
            </Link>

            <button
              @click="openApproveModal(item)"
              class="px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold flex items-center justify-center gap-1 flex-1 shadow-sm transition-all"
            >
              <CheckCircle2 class="w-3.5 h-3.5" />
              <span>Setujui</span>
            </button>

            <button
              @click="openRejectModal(item)"
              class="px-3 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold flex items-center justify-center gap-1 flex-1 shadow-sm transition-all"
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
                <th class="px-5 py-4">Pemohon & Pengajuan</th>
                <th class="px-5 py-4">Layanan & Nominal</th>
                <th class="px-5 py-4 min-w-[260px]">Catatan & Lampiran Berkas</th>
                <th class="px-5 py-4">Status Pipeline</th>
                <th class="px-5 py-4 text-right">Aksi Cepat</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="item in pendingApprovals"
                :key="item.approval_id"
                class="hover:bg-indigo-50/40 transition-colors group"
              >
                <!-- Pemohon & No. Pengajuan -->
                <td class="px-5 py-4 align-top">
                  <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-xs shrink-0 border border-indigo-200 mt-0.5">
                      {{ item.applicant_name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                      <span class="font-bold text-slate-900 block leading-tight">{{ item.applicant_name }}</span>
                      <span class="text-xs text-slate-500 font-medium block mt-0.5">{{ item.applicant_position }}</span>
                      <div class="flex items-center gap-1.5 mt-1.5">
                        <span class="font-mono text-[11px] font-bold text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded">
                          {{ item.request_number }}
                        </span>
                        <span class="text-[10px] text-slate-400">• {{ item.submitted_at }}</span>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Layanan & Nominal -->
                <td class="px-5 py-4 align-top">
                  <div class="space-y-1">
                    <span 
                      class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider"
                      :class="getTypeBadgeColor(item.type)"
                    >
                      {{ item.type_label }}
                    </span>
                    <p class="text-xs font-semibold text-slate-800 leading-snug">
                      {{ item.summary_info }}
                    </p>
                    <span v-if="item.amount_formatted" class="text-sm font-black text-slate-900 block pt-0.5">
                      {{ item.amount_formatted }}
                    </span>
                  </div>
                </td>

                <!-- Catatan & Lampiran Berkas -->
                <td class="px-5 py-4 align-top">
                  <div class="space-y-2">
                    <!-- Catatan Ajuan -->
                    <div v-if="item.notes" class="text-xs bg-amber-50/70 border border-amber-200/70 p-2.5 rounded-xl text-slate-700">
                      <span class="text-[10px] font-bold text-amber-800 uppercase tracking-wider block mb-0.5">Catatan Ajuan:</span>
                      <p class="italic text-slate-800 leading-relaxed">"{{ item.notes }}"</p>
                    </div>
                    <div v-else class="text-xs text-slate-400 italic">
                      Tanpa catatan khusus
                    </div>

                    <!-- Direct Attachment Links -->
                    <div v-if="item.attachments?.length > 0" class="flex flex-wrap items-center gap-1.5 pt-0.5">
                      <a
                        v-for="att in item.attachments"
                        :key="att.id"
                        :href="att.url"
                        target="_blank"
                        @click.stop
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold border border-indigo-200/80 transition-all shadow-2xs group/link"
                        :title="'Buka ' + att.file_name"
                      >
                        <Paperclip class="w-3.5 h-3.5 text-indigo-500" />
                        <span class="max-w-[140px] truncate">{{ att.file_name }}</span>
                        <ExternalLink class="w-3 h-3 text-indigo-400 group-hover/link:text-indigo-600" />
                      </a>
                    </div>
                  </div>
                </td>

                <!-- Status Pipeline -->
                <td class="px-5 py-4 align-top">
                  <div class="space-y-1.5 text-xs">
                    <div class="flex items-center gap-1.5">
                      <span class="text-[11px] font-bold text-slate-500 w-6">L1:</span>
                      <span
                        v-if="item.l1_status === 'approved'"
                        class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                      >
                        <CheckCircle2 class="w-2.5 h-2.5" /> Disetujui
                      </span>
                      <span
                        v-else
                        class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200"
                      >
                        Pending
                      </span>
                      <span class="text-[10px] text-slate-400 truncate max-w-[80px]">({{ item.l1_approver }})</span>
                    </div>

                    <div v-if="item.l2_status !== '-'" class="flex items-center gap-1.5">
                      <span class="text-[11px] font-bold text-slate-500 w-6">L2:</span>
                      <span
                        v-if="item.l2_status === 'approved'"
                        class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                      >
                        <CheckCircle2 class="w-2.5 h-2.5" /> Disetujui
                      </span>
                      <span
                        v-else
                        class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200"
                      >
                        Pending
                      </span>
                      <span class="text-[10px] text-slate-400 truncate max-w-[80px]">({{ item.l2_approver }})</span>
                    </div>

                    <div v-if="item.l3_status !== '-'" class="flex items-center gap-1.5">
                      <span class="text-[11px] font-bold text-slate-500 w-6">L3:</span>
                      <span
                        v-if="item.l3_status === 'approved'"
                        class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                      >
                        <CheckCircle2 class="w-2.5 h-2.5" /> Disetujui
                      </span>
                      <span
                        v-else
                        class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200"
                      >
                        Pending
                      </span>
                      <span class="text-[10px] text-slate-400 truncate max-w-[80px]">({{ item.l3_approver }})</span>
                    </div>

                    <div class="pt-1">
                      <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                        Aktif di Level {{ item.level }}
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Aksi Cepat -->
                <td class="px-5 py-4 align-top text-right" @click.stop>
                  <div class="flex flex-col items-end gap-1.5">
                    <button
                      @click.stop="openApproveModal(item)"
                      class="w-full sm:w-auto px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold flex items-center justify-center gap-1.5 transition-all shadow-sm"
                    >
                      <CheckCircle2 class="w-3.5 h-3.5" />
                      <span>Setujui</span>
                    </button>

                    <button
                      @click.stop="openRejectModal(item)"
                      class="w-full sm:w-auto px-3.5 py-1.5 rounded-xl bg-white hover:bg-rose-50 border border-rose-200 text-rose-600 text-xs font-bold flex items-center justify-center gap-1.5 transition-all shadow-2xs"
                    >
                      <XCircle class="w-3.5 h-3.5" />
                      <span>Tolak</span>
                    </button>

                    <Link
                      :href="item.url"
                      @click.stop
                      class="w-full sm:w-auto px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold flex items-center justify-center gap-1.5 transition-all"
                      title="Lihat Detail Rincian Lengkap"
                    >
                      <Eye class="w-3.5 h-3.5 text-slate-500" />
                      <span>Detail</span>
                    </Link>
                  </div>
                </td>
              </tr>

              <tr v-if="pendingApprovals.length === 0">
                <td colspan="5" class="px-6 py-12 text-center text-slate-400 text-sm">
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

        <div v-if="actionType === 'approve' && selectedItem?.amount !== null">
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Koreksi Nominal (Rp)
          </label>
          <input
            type="number"
            v-model="amountInput"
            class="w-full text-xs border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="Nominal disetujui"
          />
          <p class="text-[10px] text-slate-400 mt-1">Atasan/HRD berhak mengubah nominal klaim untuk pencairan.</p>
        </div>

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
