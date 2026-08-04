<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { ArrowLeft, Clock, FileText, CheckCircle2, XCircle, User, Paperclip, CheckSquare } from 'lucide-vue-next';

const props = defineProps({
  requestData: Object,
});

// Detect origin from URL query param ?from=approval or ?from=approval_history
const urlParams = new URLSearchParams(window.location.search);
const fromParam = urlParams.get('from');

const backRoute = computed(() => {
  if (fromParam === 'approval') return route('approval.index');
  if (fromParam === 'approval_history') return route('approval.history');
  return route('riwayat-pengajuan.index');
});

const backLabel = computed(() => {
  if (fromParam === 'approval') return 'Kembali ke Persetujuan Saya';
  if (fromParam === 'approval_history') return 'Kembali ke Riwayat Persetujuan';
  return 'Kembali ke Riwayat Pengajuan';
});

// Approval Modal State (Only for Approvers)
const showModal = ref(false);
const actionType = ref(''); // 'approve' or 'reject'
const notesInput = ref('');

const approveForm = useForm({
  notes: '',
});

const rejectForm = useForm({
  reason: '',
});

const openApproveModal = () => {
  actionType.value = 'approve';
  notesInput.value = 'Pengajuan disetujui.';
  showModal.value = true;
};

const openRejectModal = () => {
  actionType.value = 'reject';
  notesInput.value = '';
  showModal.value = true;
};

const submitApproval = () => {
  if (actionType.value === 'approve') {
    approveForm.notes = notesInput.value;
    approveForm.post(route('approval.approve', { type: props.requestData.type, id: props.requestData.id }), {
      onSuccess: () => {
        showModal.value = false;
        if (fromParam === 'approval') {
          router.visit(route('approval.index'));
        }
      },
    });
  } else {
    if (!notesInput.value) {
      alert('Alasan penolakan wajib diisi.');
      return;
    }
    rejectForm.reason = notesInput.value;
    rejectForm.post(route('approval.reject', { type: props.requestData.type, id: props.requestData.id }), {
      onSuccess: () => {
        showModal.value = false;
        if (fromParam === 'approval') {
          router.visit(route('approval.index'));
        }
      },
    });
  }
};
</script>

<template>
  <Head :title="`Detail ${requestData.request_number}`" />

  <AuthenticatedLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header Navigation -->
      <div class="flex items-center justify-between bg-white p-4 sm:p-5 rounded-2xl border border-slate-100 shadow-sm">
        <div class="flex items-center gap-3 sm:gap-4">
          <Link
            :href="backRoute"
            class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors flex items-center gap-2 text-xs font-semibold"
            :title="backLabel"
          >
            <ArrowLeft class="w-5 h-5 stroke-[2]" />
            <span class="hidden sm:inline">{{ backLabel }}</span>
          </Link>
          <div class="border-l border-slate-200 pl-3 sm:pl-4">
            <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
              <h1 class="text-base sm:text-lg font-bold text-slate-800">
                {{ requestData.request_number }}
              </h1>
              <StatusBadge :status="requestData.status" />
            </div>
            <p class="text-[11px] sm:text-xs text-slate-400 mt-0.5">
              {{ requestData.type_label }} • Diajukan pada {{ requestData.created_at }}
            </p>
          </div>
        </div>
      </div>

      <!-- APPROVAL ACTION CARD (Shown ONLY for Approvers, NEVER for Applicant) -->
      <div
        v-if="requestData.can_approve"
        class="bg-gradient-to-r from-amber-500 via-amber-600 to-amber-700 p-5 rounded-2xl text-white shadow-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 animate-in fade-in"
      >
        <div>
          <h3 class="font-bold text-base flex items-center gap-2">
            <CheckSquare class="w-5 h-5 text-amber-200" />
            Membutuhkan Persetujuan Anda (Level {{ requestData.pending_approval_level }})
          </h3>
          <p class="text-xs text-amber-100 mt-1">
            Silakan periksa rincian pengajuan ini dan berikan keputusan persetujuan Anda.
          </p>
        </div>

        <div class="flex items-center gap-2.5 self-stretch sm:self-auto shrink-0">
          <button
            @click="openApproveModal"
            class="flex-1 sm:flex-initial px-5 py-2.5 rounded-xl bg-white hover:bg-emerald-50 text-emerald-700 text-xs font-bold shadow-md transition-all flex items-center justify-center gap-1.5"
          >
            <CheckCircle2 class="w-4 h-4 text-emerald-600" />
            <span>Setujui</span>
          </button>

          <button
            @click="openRejectModal"
            class="flex-1 sm:flex-initial px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-md transition-all flex items-center justify-center gap-1.5 border border-rose-400"
          >
            <XCircle class="w-4 h-4" />
            <span>Tolak</span>
          </button>
        </div>
      </div>

      <!-- Applicant Info Card -->
      <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
          <User class="w-4 h-4 text-indigo-500" />
          Informasi Pemohon
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
          <div>
            <span class="text-xs text-slate-400 block">Nama Lengkap</span>
            <span class="font-bold text-slate-800">{{ requestData.applicant.name }}</span>
          </div>
          <div>
            <span class="text-xs text-slate-400 block">NIK</span>
            <span class="font-bold text-slate-800">{{ requestData.applicant.nik }}</span>
          </div>
          <div>
            <span class="text-xs text-slate-400 block">Divisi</span>
            <span class="font-bold text-slate-800">{{ requestData.applicant.division }}</span>
          </div>
          <div>
            <span class="text-xs text-slate-400 block">Jabatan</span>
            <span class="font-bold text-slate-800">{{ requestData.applicant.position }}</span>
          </div>
        </div>
      </div>

      <!-- Details Card -->
      <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-4">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-2">
          <FileText class="w-4 h-4 text-indigo-500" />
          Rincian Pengajuan
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div v-for="(value, key) in requestData.details" :key="key" class="p-3 bg-slate-50 rounded-xl border border-slate-100">
            <span class="text-xs text-slate-400 block mb-0.5">{{ key }}</span>
            <span class="font-semibold text-slate-800">{{ value }}</span>
          </div>
        </div>

        <div v-if="requestData.rejected_reason" class="p-4 bg-rose-50 rounded-xl border border-rose-100 text-rose-800 text-sm">
          <span class="text-xs font-bold uppercase tracking-wider block text-rose-600 mb-1">Alasan Penolakan:</span>
          <p class="font-medium">{{ requestData.rejected_reason }}</p>
        </div>
      </div>

      <!-- Attachments Card -->
      <div v-if="requestData.attachments && requestData.attachments.length > 0" class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
          <Paperclip class="w-4 h-4 text-indigo-500" />
          Lampiran File ({{ requestData.attachments.length }})
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <a
            v-for="file in requestData.attachments"
            :key="file.id"
            :href="`/storage/${file.file_path}`"
            target="_blank"
            class="p-3 bg-slate-50 hover:bg-slate-100 rounded-xl border border-slate-100 flex items-center gap-3 transition-colors"
          >
            <FileText class="w-5 h-5 text-indigo-600 shrink-0" />
            <div class="truncate text-xs">
              <span class="font-semibold text-slate-800 truncate block">{{ file.file_name }}</span>
              <span class="text-slate-400">Buka file</span>
            </div>
          </a>
        </div>
      </div>

      <!-- Timeline & Audit Trail -->
      <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-6 pb-2 border-b border-slate-100 flex items-center gap-2">
          <Clock class="w-4 h-4 text-indigo-500" />
          Riwayat Timeline Status
        </h3>

        <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
          <div v-for="log in requestData.status_histories" :key="log.id" class="relative">
            <div class="absolute -left-6 top-0.5 w-4 h-4 rounded-full bg-indigo-600 border-2 border-white shadow-sm"></div>
            <div>
              <p class="text-xs font-bold text-slate-800">
                Status berubah ke <span class="text-indigo-600 uppercase">{{ log.to_status }}</span>
              </p>
              <p class="text-xs text-slate-500 mt-0.5">
                {{ log.notes || 'Perubahan status sistem' }}
              </p>
              <span class="text-[10px] text-slate-400 font-medium block mt-1">
                {{ log.created_at }} • Oleh {{ log.changed_by?.name || 'Sistem' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Confirmation for Approver -->
    <div v-if="showModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-in fade-in">
      <div class="bg-white w-full max-w-md rounded-2xl p-6 shadow-2xl border border-slate-100 space-y-4">
        <h3 class="text-base font-bold text-slate-800">
          {{ actionType === 'approve' ? 'Konfirmasi Persetujuan' : 'Konfirmasi Penolakan' }}
        </h3>
        <p class="text-xs text-slate-500">
          Apakah Anda yakin ingin {{ actionType === 'approve' ? 'menyetujui' : 'menolak' }} pengajuan <span class="font-bold text-slate-800">{{ requestData.request_number }}</span> dari <span class="font-bold text-slate-800">{{ requestData.applicant.name }}</span>?
        </p>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            {{ actionType === 'approve' ? 'Catatan Approval (Opsional)' : 'Alasan Penolakan (Wajib)' }}
          </label>
          <textarea
            v-model="notesInput"
            rows="3"
            class="w-full text-xs border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 resize-none"
            :placeholder="actionType === 'approve' ? 'Catatan tambahan...' : 'Jelaskan alasan penolakan...'"
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
          <button
            @click="showModal = false"
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
