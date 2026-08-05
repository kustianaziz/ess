<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
  Wallet,
  ArrowDownLeft,
  ArrowUpRight,
  Plus,
  Search,
  Filter,
  CreditCard,
  Building2,
  Calendar,
  CheckCircle2,
  XCircle,
  FileText,
  Pen
} from 'lucide-vue-next'

const props = defineProps({
  cashAccounts: Array,
  summary: Object,
  transactions: Object,
  filters: Object,
  usersList: Array
})

const activeCashAccounts = computed(() => props.cashAccounts.filter(acc => acc.is_active !== false))

const showModal = ref(false)
const modalType = ref('in') // 'in' or 'out'

const form = useForm({
  cash_account_id: activeCashAccounts.value[0]?.id || props.cashAccounts[0]?.id || '',
  type: 'in',
  category: 'setoran_kas',
  amount: 0,
  description: '',
  transaction_date: new Date().toISOString().split('T')[0]
})

const rawAmountInput = ref('')

const handleAmountInput = (e) => {
  const value = e.target.value.replace(/\D/g, '')
  form.amount = value ? parseInt(value, 10) : 0
  rawAmountInput.value = value ? new Intl.NumberFormat('id-ID').format(value) : ''
}

const openModal = (type) => {
  modalType.value = type
  form.type = type
  form.category = type === 'in' ? 'setoran_kas' : 'operasional_lain'
  form.amount = 0
  rawAmountInput.value = ''
  form.description = ''
  showModal.value = true
}

const submitTransaction = () => {
  if (!form.cash_account_id || !form.amount || !form.description) {
    alert('Mohon lengkapi akun kas, nominal, dan keterangan transaksi.')
    return
  }
  form.post(route('keuangan.kas-operasional.transaksi.store'), {
    onSuccess: () => {
      showModal.value = false
    }
  })
}

// Filter State
const searchInput = ref(props.filters.search || '')
const selectedAccount = ref(props.filters.cash_account_id || '')
const selectedType = ref(props.filters.type || '')

const applyFilters = () => {
  router.get(
    route('keuangan.kas-operasional.dashboard'),
    {
      search: searchInput.value,
      cash_account_id: selectedAccount.value,
      type: selectedType.value
    },
    { preserveState: true }
  )
}

// Cash Account Modal State
const showAccountModal = ref(false)
const isEditAccount = ref(false)
const selectedAccountId = ref(null)

const accountForm = useForm({
  name: '',
  code: '',
  type: 'cash',
  bank_name: '',
  account_number: '',
  current_balance: 0,
  pic_user_id: '',
  is_active: true
})

const rawAccountBalanceInput = ref('')

const handleAccountBalanceInput = (e) => {
  const value = e.target.value.replace(/\D/g, '')
  accountForm.current_balance = value ? parseInt(value, 10) : 0
  rawAccountBalanceInput.value = value ? new Intl.NumberFormat('id-ID').format(value) : ''
}

const openAddAccountModal = () => {
  isEditAccount.value = false
  selectedAccountId.value = null
  accountForm.name = ''
  accountForm.code = ''
  accountForm.type = 'cash'
  accountForm.bank_name = ''
  accountForm.account_number = ''
  accountForm.current_balance = 0
  rawAccountBalanceInput.value = ''
  accountForm.pic_user_id = props.usersList?.[0]?.id || ''
  accountForm.is_active = true
  showAccountModal.value = true
}

const openEditAccountModal = (acc) => {
  isEditAccount.value = true
  selectedAccountId.value = acc.id
  accountForm.name = acc.name
  accountForm.code = acc.code
  accountForm.type = acc.type || 'cash'
  accountForm.bank_name = acc.bank_name || ''
  accountForm.account_number = acc.account_number || ''
  accountForm.current_balance = acc.current_balance
  rawAccountBalanceInput.value = acc.current_balance ? new Intl.NumberFormat('id-ID').format(acc.current_balance) : '0'
  accountForm.pic_user_id = acc.pic_user_id || props.usersList?.[0]?.id || ''
  accountForm.is_active = acc.is_active !== false
  showAccountModal.value = true
}

const submitAccount = () => {
  if (!accountForm.name || !accountForm.code) {
    alert('Mohon isi nama akun dan kode akun kas.')
    return
  }

  if (isEditAccount.value) {
    accountForm.put(route('keuangan.kas-operasional.accounts.update', selectedAccountId.value), {
      onSuccess: () => {
        showAccountModal.value = false
      }
    })
  } else {
    accountForm.post(route('keuangan.kas-operasional.accounts.store'), {
      onSuccess: () => {
        showAccountModal.value = false
      }
    })
  }
}
</script>

<template>
  <Head title="Kas Operasional & Saldo Real-Time" />

  <AuthenticatedLayout>
    <div class="py-6 sm:py-8 max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-6">
      <!-- HEADER -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="p-2 rounded-xl bg-indigo-100 text-indigo-600">
              <Wallet class="w-5 h-5" />
            </span>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
              Buku Kas Operasional & Saldo Real-Time
            </h1>
          </div>
          <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Monitoring saldo kas perusahaan & mutasi pencatatan keuangan real-time
          </p>
        </div>

        <!-- QUICK ACTION BUTTONS -->
        <div class="flex items-center gap-2">
          <button
            @click="openModal('in')"
            class="flex-1 sm:flex-initial px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition-all flex items-center justify-center gap-1.5"
          >
            <ArrowDownLeft class="w-4 h-4" />
            <span>Kas Masuk</span>
          </button>

          <button
            @click="openModal('out')"
            class="flex-1 sm:flex-initial px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-md shadow-rose-600/20 transition-all flex items-center justify-center gap-1.5"
          >
            <ArrowUpRight class="w-4 h-4" />
            <span>Kas Keluar</span>
          </button>
        </div>
      </div>

      <!-- CARDS SUMMARY STATS -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- TOTAL BALANCE -->
        <div class="bg-gradient-to-br from-slate-900 to-indigo-950 p-5 rounded-2xl text-white shadow-xl relative overflow-hidden">
          <div class="relative z-10 space-y-1">
            <span class="text-[11px] text-slate-400 uppercase tracking-wider font-bold block">Total Saldo Kas Real-Time</span>
            <h3 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
              {{ summary.total_balance_formatted }}
            </h3>
            <span class="text-[11px] text-indigo-300 block pt-1">
              Akumulasi dari {{ cashAccounts.length }} akun kas aktif
            </span>
          </div>
        </div>

        <!-- MONTHLY CASH IN -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Kas Masuk (Bulan Ini)</span>
            <h3 class="text-xl sm:text-2xl font-bold text-emerald-600 mt-1">
              {{ summary.total_cash_in_formatted }}
            </h3>
          </div>
          <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
            <ArrowDownLeft class="w-5 h-5" />
          </div>
        </div>

        <!-- MONTHLY CASH OUT -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Kas Keluar (Bulan Ini)</span>
            <h3 class="text-xl sm:text-2xl font-bold text-rose-600 mt-1">
              {{ summary.total_cash_out_formatted }}
            </h3>
          </div>
          <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
            <ArrowUpRight class="w-5 h-5" />
          </div>
        </div>
      </div>

      <!-- POS AKUN KAS LIST -->
      <div class="space-y-3">
        <div class="flex items-center justify-between gap-2">
          <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2">
            <CreditCard class="w-4 h-4 text-indigo-500" />
            Daftar Pos Akun Kas
          </h3>

          <button
            @click="openAddAccountModal"
            class="px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs font-bold transition-all flex items-center gap-1 border border-indigo-100"
          >
            <span>+ Tambah Pos Kas</span>
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <div
            v-for="acc in cashAccounts"
            :key="acc.id"
            class="p-4 rounded-xl border shadow-sm flex items-center justify-between transition-all group"
            :class="acc.is_active ? 'bg-white border-slate-200/80 hover:border-indigo-300' : 'bg-slate-50 border-slate-200 opacity-75'"
          >
            <div>
              <div class="flex items-center gap-1.5 flex-wrap">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 uppercase tracking-wider">
                  {{ acc.code }}
                </span>
                <span
                  class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider flex items-center gap-1"
                  :class="acc.type === 'bank' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'"
                >
                  <Building2 v-if="acc.type === 'bank'" class="w-3 h-3" />
                  <Wallet v-else class="w-3 h-3" />
                  <span>{{ acc.type_label }}</span>
                </span>
                <span
                  v-if="!acc.is_active"
                  class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 uppercase tracking-wider"
                >
                  Non-Aktif
                </span>
                <button
                  @click="openEditAccountModal(acc)"
                  class="p-1 text-slate-400 hover:text-indigo-600 rounded transition-colors ml-auto sm:ml-0"
                  title="Edit & Aktifkan Kembali Akun Kas"
                >
                  <Pen class="w-3.5 h-3.5" />
                </button>
              </div>
              <h4 class="font-bold text-sm text-slate-900 mt-1" :class="{ 'line-through text-slate-400': !acc.is_active }">{{ acc.name }}</h4>
              <span v-if="acc.type === 'bank' && acc.bank_name" class="text-[11px] text-slate-500 font-medium block">
                {{ acc.bank_name }} <span v-if="acc.account_number">• {{ acc.account_number }}</span>
              </span>
              <span class="text-[11px] text-slate-400 block mt-0.5">PIC: {{ acc.pic_name }}</span>
            </div>
            <div class="text-right">
              <span class="font-bold text-sm text-slate-900 block" :class="{ 'text-slate-400': !acc.is_active }">{{ acc.current_balance_formatted }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- RECENT TRANSACTIONS TABLE SECTION -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden space-y-4 p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
          <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
            <FileText class="w-4 h-4 text-indigo-500" />
            Riwayat Mutasi Transaksi Kas
          </h3>

          <!-- FILTERS -->
          <div class="flex flex-wrap items-center gap-2">
            <select
              v-model="selectedAccount"
              @change="applyFilters"
              class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-700"
            >
              <option value="">Semua Akun Kas</option>
              <option v-for="acc in cashAccounts" :key="acc.id" :value="acc.id">
                {{ acc.name }}
              </option>
            </select>

            <select
              v-model="selectedType"
              @change="applyFilters"
              class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-700"
            >
              <option value="">Semua Mutasi</option>
              <option value="in">Kas Masuk (+)</option>
              <option value="out">Kas Keluar (-)</option>
            </select>

            <div class="relative">
              <Search class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" />
              <input
                v-model="searchInput"
                @keyup.enter="applyFilters"
                type="text"
                placeholder="Cari no. transaksi / ket..."
                class="pl-8 pr-3 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-800"
              />
            </div>
          </div>
        </div>

        <!-- MOBILE RESPONSIVE TABLE -->
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse min-w-[700px]">
            <thead>
              <tr class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 font-bold border-b border-slate-200/80">
                <th class="py-3 px-4">No. Transaksi</th>
                <th class="py-3 px-4">Akun Kas</th>
                <th class="py-3 px-4">Kategori</th>
                <th class="py-3 px-4">Nominal</th>
                <th class="py-3 px-4">Keterangan</th>
                <th class="py-3 px-4">Tanggal</th>
                <th class="py-3 px-4">Oleh</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
              <tr v-for="tx in transactions.data" :key="tx.id" class="hover:bg-slate-50/70 transition-colors">
                <td class="py-3 px-4 font-bold text-slate-900">{{ tx.transaction_number }}</td>
                <td class="py-3 px-4 font-semibold text-slate-700">{{ tx.cash_account_name }}</td>
                <td class="py-3 px-4">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider" :class="tx.type === 'in' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700'">
                    {{ tx.category_label }}
                  </span>
                </td>
                <td class="py-3 px-4 font-bold" :class="tx.type === 'in' ? 'text-emerald-600' : 'text-rose-600'">
                  {{ tx.amount_formatted }}
                </td>
                <td class="py-3 px-4 text-slate-600 max-w-xs truncate">{{ tx.description }}</td>
                <td class="py-3 px-4 text-slate-500 whitespace-nowrap">{{ tx.transaction_date }}</td>
                <td class="py-3 px-4 text-slate-500 whitespace-nowrap">{{ tx.created_by }}</td>
              </tr>
              <tr v-if="transactions.data.length === 0">
                <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                  Belum ada transaksi kas yang dicatat.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL MANUAL CASH TRANSACTION -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
            <span class="p-1.5 rounded-lg" :class="modalType === 'in' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'">
              <ArrowDownLeft v-if="modalType === 'in'" class="w-4 h-4" />
              <ArrowUpRight v-else class="w-4 h-4" />
            </span>
            <span>Catat {{ modalType === 'in' ? 'Kas Masuk (+)' : 'Kas Keluar (-)' }}</span>
          </h3>
          <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
        </div>

        <form @submit.prevent="submitTransaction" class="space-y-4 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Pilih Akun Kas <span class="text-rose-500">*</span></label>
            <select v-model="form.cash_account_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-semibold text-slate-800">
              <option v-for="acc in cashAccounts" :key="acc.id" :value="acc.id">
                {{ acc.name }} (Saldo: {{ acc.current_balance_formatted }})
              </option>
            </select>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Kategori Transaksi <span class="text-rose-500">*</span></label>
            <select v-model="form.category" class="w-full px-3 py-2 rounded-xl border border-slate-200 font-semibold text-slate-800">
              <option v-if="modalType === 'in'" value="setoran_kas">Setoran Kas / Modal</option>
              <option v-if="modalType === 'in'" value="lainnya">Kas Masuk Lainnya</option>
              <option v-if="modalType === 'out'" value="operasional_lain">Pengeluaran Operasional</option>
              <option v-if="modalType === 'out'" value="pembayaran_bulanan">Pembayaran Tagihan</option>
              <option v-if="modalType === 'out'" value="lainnya">Kas Keluar Lainnya</option>
            </select>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Nominal (Rp) <span class="text-rose-500">*</span></label>
            <div class="relative">
              <span class="absolute left-3 top-2 font-bold text-slate-400">Rp</span>
              <input
                :value="rawAmountInput"
                @input="handleAmountInput"
                type="text"
                placeholder="0"
                class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 font-bold text-slate-900"
              />
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Tanggal Transaksi <span class="text-rose-500">*</span></label>
            <input v-model="form.transaction_date" type="date" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-800 font-medium" />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Keterangan / Keperluan <span class="text-rose-500">*</span></label>
            <textarea v-model="form.description" rows="3" placeholder="Tuliskan keterangan detail transaksi..." class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-800"></textarea>
          </div>

          <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
            <button type="button" @click="showModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-5 py-2 rounded-xl font-bold text-white shadow-md transition-all"
              :class="modalType === 'in' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'"
            >
              Simpan Transaksi
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL ADD / EDIT POS AKUN KAS -->
    <div v-if="showAccountModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
            <span class="p-1.5 rounded-lg bg-indigo-100 text-indigo-600">
              <CreditCard class="w-4 h-4" />
            </span>
            <span>{{ isEditAccount ? 'Edit Master Pos Akun Kas' : 'Tambah Pos Akun Kas Baru' }}</span>
          </h3>
          <button @click="showAccountModal = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
        </div>

        <form @submit.prevent="submitAccount" class="space-y-4 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Tipe Pos Akun Kas <span class="text-rose-500">*</span></label>
            <div class="grid grid-cols-2 gap-2">
              <label
                class="p-2.5 rounded-xl border flex items-center justify-center gap-2 cursor-pointer font-bold transition-all"
                :class="accountForm.type === 'cash' ? 'bg-emerald-50 border-emerald-500 text-emerald-700' : 'bg-slate-50 border-slate-200 text-slate-600'"
              >
                <input type="radio" v-model="accountForm.type" value="cash" class="sr-only" />
                <Wallet class="w-4 h-4" />
                <span>Kas Tunai</span>
              </label>

              <label
                class="p-2.5 rounded-xl border flex items-center justify-center gap-2 cursor-pointer font-bold transition-all"
                :class="accountForm.type === 'bank' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'bg-slate-50 border-slate-200 text-slate-600'"
              >
                <input type="radio" v-model="accountForm.type" value="bank" class="sr-only" />
                <Building2 class="w-4 h-4" />
                <span>Rekening Bank</span>
              </label>
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Nama Pos Akun Kas <span class="text-rose-500">*</span></label>
            <input v-model="accountForm.name" type="text" placeholder="Contoh: Kas Bank BCA Operasional / Kas Kecil" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-800 font-semibold" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Kode Unik Akun <span class="text-rose-500">*</span></label>
              <input v-model="accountForm.code" type="text" placeholder="Contoh: KAS-BCA" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-800 font-bold uppercase" />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">PIC / Pengelola Kas</label>
              <select v-model="accountForm.pic_user_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-slate-800 font-medium">
                <option value="">Admin Keuangan</option>
                <option v-for="user in usersList" :key="user.id" :value="user.id">
                  {{ user.name }}
                </option>
              </select>
            </div>
          </div>

          <div v-if="accountForm.type === 'bank'" class="grid grid-cols-2 gap-3 p-3 rounded-xl bg-blue-50/70 border border-blue-100">
            <div>
              <label class="block font-bold text-blue-900 mb-1">Nama Bank</label>
              <input v-model="accountForm.bank_name" type="text" placeholder="Contoh: Bank BCA / Bank Mandiri" class="w-full px-3 py-2 rounded-xl border border-blue-200 text-slate-800 font-semibold" />
            </div>

            <div>
              <label class="block font-bold text-blue-900 mb-1">Nomor Rekening</label>
              <input v-model="accountForm.account_number" type="text" placeholder="Contoh: 8391204812" class="w-full px-3 py-2 rounded-xl border border-blue-200 text-slate-800 font-bold" />
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Saldo Berjalan / Saldo Awal (Rp) <span class="text-rose-500">*</span></label>
            <div class="relative">
              <span class="absolute left-3 top-2 font-bold text-slate-400">Rp</span>
              <input
                :value="rawAccountBalanceInput"
                @input="handleAccountBalanceInput"
                type="text"
                placeholder="0"
                class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 font-bold text-slate-900"
              />
            </div>
          </div>

          <div v-if="isEditAccount" class="flex items-center gap-2 pt-1">
            <input id="is_active_chk" v-model="accountForm.is_active" type="checkbox" class="rounded text-indigo-600 focus:ring-indigo-500" />
            <label for="is_active_chk" class="font-bold text-slate-700">Akun Kas Aktif</label>
          </div>

          <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
            <button type="button" @click="showAccountModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
            <button
              type="submit"
              :disabled="accountForm.processing"
              class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold shadow-md transition-all"
            >
              {{ isEditAccount ? 'Simpan Perubahan' : 'Simpan Pos Kas' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
