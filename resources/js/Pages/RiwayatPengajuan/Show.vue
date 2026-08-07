<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { ArrowLeft, Clock, FileText, CheckCircle2, XCircle, User, Paperclip, CheckSquare } from 'lucide-vue-next';

const props = defineProps({
  requestData: Object,
  cashAccounts: {
    type: Array,
    default: () => [],
  },
});

const page = usePage();

const isHrdOrAdmin = computed(() => {
  const user = page.props.auth.user;
  return user?.roles?.some(r => ['admin', 'hrd_finance'].includes(r.name));
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

// Payment Modal State (For HRD/Finance/Admin)
const showPayModal = ref(false);

const paymentForm = useForm({
  payment_reference: 'TRF-' + Math.floor(100000 + Math.random() * 900000),
  cash_account_id: props.cashAccounts?.[0]?.id || '',
  proof_of_payment: null,
  disbursed_budget: 0,
  allowance_breakdown: [
    { item: 'Uang Saku', amount: 0 },
    { item: 'Uang Makan', amount: 0 },
    { item: 'Tiket Transportasi', amount: 0 },
    { item: 'Akomodasi Hotel', amount: 0 },
  ],
});

const selectedProofFiles = ref([]);

const openPaymentModal = () => {
  paymentForm.payment_reference = 'TRF-' + Math.floor(100000 + Math.random() * 900000);
  paymentForm.cash_account_id = props.cashAccounts?.[0]?.id || '';
  paymentForm.proof_of_payment = [];
  selectedProofFiles.value = [];
  paymentForm.allowance_breakdown = [
    { item: 'Uang Saku', amount: 0 },
    { item: 'Uang Makan', amount: 0 },
    { item: 'Tiket Transportasi', amount: 0 },
    { item: 'Akomodasi Hotel', amount: 0 },
  ];
  showPayModal.value = true;
};

const addBreakdownRow = () => {
  paymentForm.allowance_breakdown.push({ item: '', amount: 0 });
};

const removeBreakdownRow = (index) => {
  if (paymentForm.allowance_breakdown.length > 1) {
    paymentForm.allowance_breakdown.splice(index, 1);
  }
};

const handleProofFilesChange = (e) => {
  if (e.target.files && e.target.files.length > 0) {
    const newFiles = Array.from(e.target.files);
    const existing = selectedProofFiles.value;
    const combined = [...existing];
    newFiles.forEach((nf) => {
      if (!combined.some(f => f.name === nf.name && f.size === nf.size)) {
        combined.push(nf);
      }
    });
    selectedProofFiles.value = combined;
    paymentForm.proof_of_payment = combined;
    e.target.value = '';
  }
};

const removeProofFile = (index) => {
  selectedProofFiles.value.splice(index, 1);
  paymentForm.proof_of_payment = selectedProofFiles.value;
};

const submitPayment = () => {
  if (!paymentForm.payment_reference || !paymentForm.cash_account_id) {
    alert('Mohon isi nomor referensi transfer dan pilih pos akun kas pembayar.');
    return;
  }

  paymentForm.post(route('payment.process', { type: props.requestData.type, id: props.requestData.id }), {
    onSuccess: () => {
      showPayModal.value = false;
    },
  });
};

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

      <!-- APPROVAL ACTION CARD (Soft & Compact Light Indigo Logo Theme) -->
      <div
        v-if="requestData.can_approve"
        class="bg-gradient-to-r from-indigo-50/90 via-slate-50 to-purple-50/80 border border-indigo-100 p-3.5 sm:p-5 rounded-2xl shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-4 animate-in fade-in"
      >
        <div class="flex items-start gap-2.5 sm:gap-3.5">
          <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-indigo-600/10 text-indigo-600 flex items-center justify-center shrink-0 mt-0.5">
            <CheckSquare class="w-4 h-4 sm:w-5 sm:h-5 stroke-[2]" />
          </div>
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h3 class="font-bold text-xs sm:text-sm text-slate-900 tracking-tight">
                Membutuhkan Persetujuan Anda
              </h3>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 uppercase tracking-wider">
                Level {{ requestData.pending_approval_level }}
              </span>
            </div>
            <p class="text-[11px] sm:text-xs text-slate-500 mt-0.5 leading-snug">
              Silakan periksa rincian pengajuan ini dan berikan keputusan Anda.
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-indigo-100/70">
          <button
            @click="openApproveModal"
            class="flex-1 sm:flex-initial px-4 py-2 sm:py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/20 transition-all flex items-center justify-center gap-1.5"
          >
            <CheckCircle2 class="w-3.5 h-3.5" />
            <span>Setujui</span>
          </button>

          <button
            @click="openRejectModal"
            class="flex-1 sm:flex-initial px-4 py-2 sm:py-2.5 rounded-xl bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 text-xs font-bold transition-all flex items-center justify-center gap-1.5"
          >
            <XCircle class="w-3.5 h-3.5" />
            <span>Tolak</span>
          </button>
        </div>
      </div>

      <!-- HRD / FINANCE PAYMENT DISBURSEMENT BANNER (WHEN APPROVED BUT NOT YET PAID) -->
      <div
        v-if="isHrdOrAdmin && ['approved', 'level_1_approved'].includes(requestData.status) && requestData.status !== 'paid'"
        class="bg-gradient-to-r from-emerald-600 to-teal-700 p-4 sm:p-5 rounded-2xl text-white shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 animate-in fade-in"
      >
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 uppercase tracking-wider">
              Keuangan & Kas
            </span>
            <h4 class="font-bold text-sm sm:text-base">Pengajuan Siap Dicairkan / Dibayarkan</h4>
          </div>
          <p class="text-xs text-emerald-100 leading-snug">
            Pengajuan ini telah disetujui. Silakan pilih akun kas pembayar, input no. referensi transfer, & upload bukti transfer.
          </p>
        </div>

        <button
          @click="openPaymentModal"
          class="px-5 py-2.5 rounded-xl bg-white hover:bg-emerald-50 text-emerald-700 font-bold text-xs shadow transition-all shrink-0 w-full sm:w-auto text-center flex items-center justify-center gap-1.5"
        >
          <CheckCircle2 class="w-4 h-4" />
          <span>Proses Pencairan & Pembayaran Kas →</span>
        </button>
      </div>

      <!-- SETTLEMENT ACTION BANNER FOR APPLICANT (IF PERJALANAN DINAS & APPROVED/PAID) -->
      <div
        v-if="requestData.type === 'perjalanan-dinas' && ['approved', 'paid'].includes(requestData.status)"
        class="bg-gradient-to-r from-blue-600 to-indigo-600 p-4 sm:p-5 rounded-2xl text-white shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
      >
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 uppercase tracking-wider">
              Settlement Pertanggungjawaban
            </span>
            <h4 class="font-bold text-sm sm:text-base">Sudah Pulang Perjalanan Dinas?</h4>
          </div>
          <p class="text-xs text-blue-100 leading-snug">
            Silakan isi form pertanggungjawaban realisasi biaya (settlement) & upload bukti nota/struk pengeluaran.
          </p>
        </div>

        <Link
          :href="route('pengajuan.perjalanan-dinas.settlement.create', requestData.id)"
          class="px-5 py-2.5 rounded-xl bg-white hover:bg-blue-50 text-blue-600 font-bold text-xs shadow transition-all shrink-0 w-full sm:w-auto text-center"
        >
          Isi Pertanggungjawaban (Settlement) →
        </Link>
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

        <!-- RINCIAN KOMPONEN UANG MUKA DARI KEUANGAN (IF ANY) -->
        <div
          v-if="requestData.allowance_breakdown && requestData.allowance_breakdown.length > 0"
          class="mt-4 p-4 rounded-xl bg-blue-50/80 border border-blue-100 space-y-2"
        >
          <h4 class="text-xs font-bold text-blue-900 uppercase tracking-wider flex items-center gap-1.5">
            <span>Rincian Komponen Uang Muka Dicairkan Keuangan:</span>
          </h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
            <div
              v-for="(comp, cIdx) in requestData.allowance_breakdown"
              :key="cIdx"
              class="p-2.5 bg-white rounded-lg border border-blue-100/80 flex items-center justify-between"
            >
              <span class="font-medium text-slate-700">{{ comp.item }}</span>
              <span class="font-bold text-blue-700">Rp {{ new Intl.NumberFormat('id-ID').format(comp.amount || 0) }}</span>
            </div>
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

      <!-- Settlement Details Card -->
      <div v-if="requestData.settlement" class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm space-y-4">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-2">
          <FileText class="w-4 h-4 text-emerald-500" />
          Laporan Pertanggungjawaban (Settlement)
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs mb-4">
          <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
            <span class="text-slate-400 block mb-0.5">Total Realisasi</span>
            <span class="font-bold text-slate-800">{{ requestData.settlement.total_actual_cost }}</span>
          </div>
          <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
            <span class="text-slate-400 block mb-0.5">Uang Muka Diterima</span>
            <span class="font-bold text-slate-800">{{ requestData.settlement.advance_amount }}</span>
          </div>
          <div class="p-3 bg-slate-50 rounded-xl border border-slate-100" :class="requestData.settlement.difference_raw > 0 ? 'bg-amber-50 border-amber-100' : (requestData.settlement.difference_raw < 0 ? 'bg-emerald-50 border-emerald-100' : '')">
            <span class="text-slate-500 block mb-0.5 font-medium">{{ requestData.settlement.difference_raw > 0 ? 'Kurang Bayar Ke Karyawan' : (requestData.settlement.difference_raw < 0 ? 'Karyawan Kembalikan Sisa' : 'Pas') }}</span>
            <span class="font-bold text-slate-800" :class="requestData.settlement.difference_raw > 0 ? 'text-amber-700' : (requestData.settlement.difference_raw < 0 ? 'text-emerald-700' : '')">{{ requestData.settlement.difference_amount }}</span>
          </div>
        </div>

        <div class="space-y-2">
          <h4 class="text-xs font-bold text-slate-700">Laporan Kegiatan</h4>
          <p class="text-xs text-slate-600 bg-slate-50 p-3 rounded-lg border border-slate-100 leading-relaxed whitespace-pre-wrap">{{ requestData.settlement.trip_report }}</p>
        </div>

        <div class="space-y-2 pt-2">
          <h4 class="text-xs font-bold text-slate-700">Rincian Realisasi</h4>
          <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="w-full text-left text-[11px] whitespace-nowrap">
              <thead class="bg-slate-50 text-slate-500 uppercase font-bold">
                <tr>
                  <th class="px-4 py-2 border-b border-slate-200">Tanggal</th>
                  <th class="px-4 py-2 border-b border-slate-200">Kategori</th>
                  <th class="px-4 py-2 border-b border-slate-200">Keterangan</th>
                  <th class="px-4 py-2 border-b border-slate-200 text-right">Nominal (Rp)</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                <tr v-for="ex in requestData.settlement.expense_items" :key="ex.id" class="hover:bg-slate-50/50">
                  <td class="px-4 py-2">{{ ex.expense_date }}</td>
                  <td class="px-4 py-2 uppercase">{{ ex.category }}</td>
                  <td class="px-4 py-2 truncate max-w-[200px]" :title="ex.description">{{ ex.description }}</td>
                  <td class="px-4 py-2 text-right font-bold">{{ new Intl.NumberFormat('id-ID').format(ex.amount || 0) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Settlement Attachments -->
        <div v-if="requestData.settlement.attachments && requestData.settlement.attachments.length > 0" class="pt-4 space-y-3">
          <h4 class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
            <Paperclip class="w-3.5 h-3.5" />
            Lampiran Nota / Bukti Transfer
          </h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
            <a
              v-for="file in requestData.settlement.attachments"
              :key="file.id"
              :href="`/storage/${file.file_path}`"
              target="_blank"
              class="p-2.5 bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-200 flex items-center gap-2 transition-colors"
            >
              <FileText class="w-4 h-4 text-indigo-500 shrink-0" />
              <span class="font-medium text-slate-700 truncate w-full">{{ file.file_name }}</span>
            </a>
          </div>
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

    <!-- MODAL PROCESS PAYMENT (FOR HRD / FINANCE) -->
    <div v-if="showPayModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-50 animate-in fade-in">
      <div class="bg-white w-full max-w-lg rounded-2xl p-6 shadow-2xl border border-slate-100 space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
            <span class="p-1.5 rounded-lg bg-emerald-100 text-emerald-600">
              <CheckCircle2 class="w-4 h-4" />
            </span>
            <span>Proses Pencairan & Pembayaran Kas</span>
          </h3>
          <button @click="showPayModal = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
        </div>

        <form @submit.prevent="submitPayment" class="space-y-4 text-xs">
          <!-- POS AKUN KAS / BANK PEMBAYAR -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Pilih Pos Akun Kas / Bank Pembayar <span class="text-rose-500">*</span></label>
            <select v-model="paymentForm.cash_account_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-semibold text-slate-800">
              <option v-for="acc in cashAccounts" :key="acc.id" :value="acc.id">
                {{ acc.name }} ({{ acc.type_label }}) - Saldo: {{ acc.current_balance_formatted }}
              </option>
            </select>
          </div>

          <!-- NOMOR REFERENSI TRANSFER -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">No. Referensi Transfer Bank / Struk <span class="text-rose-500">*</span></label>
            <input
              v-model="paymentForm.payment_reference"
              type="text"
              placeholder="Contoh: TRF-839120 / BCA-92810"
              class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-800 font-bold"
            />
          </div>

          <!-- UPLOAD BUKTI TRANSFER FILE (MULTI-FILE SUPPORT) -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Upload Bukti Transfer / Struk Bank (Bisa Banyak File sekaligus: JPG, PNG, PDF)</label>
            <input
              type="file"
              multiple
              @change="handleProofFilesChange"
              accept=".jpg,.jpeg,.png,.pdf"
              class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer"
            />
            <span class="text-[10px] text-slate-400 block mt-1">Dapat memilih lebih dari 1 file (Maks. 5MB per file)</span>

            <!-- PREVIEW TERPILIH -->
            <div v-if="selectedProofFiles.length > 0" class="mt-2 space-y-1.5 p-2.5 rounded-xl bg-slate-50 border border-slate-200">
              <span class="text-[10px] font-bold text-slate-500 block uppercase">File Terpilih ({{ selectedProofFiles.length }}):</span>
              <ul class="space-y-1">
                <li v-for="(f, i) in selectedProofFiles" :key="i" class="text-[11px] font-medium text-slate-700 flex items-center justify-between gap-2 p-1.5 rounded-lg bg-white border border-slate-200 shadow-2xs">
                  <div class="flex items-center gap-1.5 truncate">
                    <Paperclip class="w-3.5 h-3.5 text-indigo-600 shrink-0" />
                    <span class="truncate font-semibold text-slate-800">{{ f.name }}</span>
                    <span class="text-[9px] text-slate-400 shrink-0">({{ Math.round(f.size / 1024) }} KB)</span>
                  </div>
                  <button
                    type="button"
                    @click="removeProofFile(i)"
                    class="p-0.5 text-slate-400 hover:text-rose-600 rounded transition-colors"
                    title="Hapus file ini"
                  >
                    ✕
                  </button>
                </li>
              </ul>
            </div>
          </div>

          <!-- DYNAMIC RINCIAN PERJALANAN DINAS -->
          <div v-if="requestData.type === 'perjalanan-dinas'" class="space-y-3 p-3.5 rounded-xl bg-blue-50/70 border border-blue-100">
            <div class="flex items-center justify-between">
              <label class="block font-bold text-blue-900 uppercase tracking-wider text-[11px]">Rincian Komponen Uang Muka Dicairkan</label>
              <button
                type="button"
                @click="addBreakdownRow"
                class="px-2.5 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold transition-all flex items-center gap-1 shadow-xs"
              >
                <span>+ Tambah Komponen</span>
              </button>
            </div>

            <div class="space-y-2">
              <div v-for="(item, idx) in paymentForm.allowance_breakdown" :key="idx" class="flex items-center gap-2">
                <input
                  v-model="item.item"
                  type="text"
                  placeholder="Nama Komponen (Contoh: Uang Saku)"
                  class="flex-1 px-2.5 py-1.5 rounded-lg border border-blue-200 font-semibold text-slate-800 text-xs"
                />
                <div class="relative w-36 sm:w-44">
                  <span class="absolute left-2.5 top-1.5 font-bold text-slate-400 text-[11px]">Rp</span>
                  <input
                    v-model.number="item.amount"
                    type="number"
                    placeholder="0"
                    class="w-full pl-8 pr-2 py-1.5 rounded-lg border border-blue-200 font-bold text-slate-900 text-xs"
                  />
                </div>
                <button
                  type="button"
                  @click="removeBreakdownRow(idx)"
                  :disabled="paymentForm.allowance_breakdown.length <= 1"
                  class="p-1.5 text-slate-400 hover:text-rose-600 disabled:opacity-30 rounded transition-colors"
                  title="Hapus komponen ini"
                >
                  ✕
                </button>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
            <button type="button" @click="showPayModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
            <button
              type="submit"
              :disabled="paymentForm.processing"
              class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-md transition-all flex items-center gap-1.5"
            >
              <CheckCircle2 class="w-4 h-4" />
              <span>Konfirmasi & Process Pembayaran Kas</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
