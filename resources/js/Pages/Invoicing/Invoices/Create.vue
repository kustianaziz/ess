<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { FileText, ArrowLeft, Plus, Trash2, CheckCircle2, Info } from 'lucide-vue-next'

const props = defineProps({
  customers: Array
})

// PPN setting
const ppnMode = ref('exclude') // 'none' | 'exclude' | 'include'
const ppnRate = ref(11) // persen, bisa diubah user

const form = useForm({
  invoice_number: '',
  customer_id: '',
  invoice_date: new Date().toISOString().split('T')[0],
  due_date: '',
  po_number: '',
  notes: '',
  items: [
    { description: '', qty: 1, unit_price: 0 }
  ],
  subtotal: 0,
  tax_amount: 0,
  total_amount: 0,
  ppn_mode: 'exclude',
  ppn_rate: 11
})

const addItem = () => {
  form.items.push({ description: '', qty: 1, unit_price: 0 })
}

const removeItem = (index) => {
  if (form.items.length > 1) {
    form.items.splice(index, 1)
    calculateTotal()
  }
}

// Kalkulasi cerdas: Include vs Exclude vs None
const calculateTotal = () => {
  let rawSum = 0
  form.items.forEach(item => {
    rawSum += (item.qty * item.unit_price)
  })

  if (ppnMode.value === 'none') {
    // Tanpa PPN
    form.subtotal = rawSum
    form.tax_amount = 0
    form.total_amount = rawSum
  } else if (ppnMode.value === 'exclude') {
    // PPN ditambahkan di atas harga (harga belum termasuk PPN)
    const tax = Math.round(rawSum * (ppnRate.value / 100))
    form.subtotal = rawSum
    form.tax_amount = tax
    form.total_amount = rawSum + tax
  } else if (ppnMode.value === 'include') {
    // Harga sudah termasuk PPN (PPN di-extract dari total)
    const subtotalExcl = Math.round(rawSum / (1 + ppnRate.value / 100))
    const tax = rawSum - subtotalExcl
    form.subtotal = subtotalExcl
    form.tax_amount = tax
    form.total_amount = rawSum
  }

  form.ppn_mode = ppnMode.value
  form.ppn_rate = ppnRate.value
}

const onModeChange = () => {
  calculateTotal()
}

const submit = () => {
  calculateTotal()
  if (form.items.some(item => !item.description || item.unit_price <= 0)) {
    alert('Mohon lengkapi semua deskripsi item dan pastikan harga satuan lebih dari 0.')
    return
  }
  form.post(route('invoicing.invoices.store'))
}

const formatRp = (val) => Number(val || 0).toLocaleString('id-ID')

// Label helper
const modeLabel = computed(() => {
  if (ppnMode.value === 'none') return { text: 'Tanpa PPN', color: 'bg-slate-100 text-slate-600 border-slate-200' }
  if (ppnMode.value === 'exclude') return { text: `PPN ${ppnRate.value}% Eksklusif (ditambahkan ke total)`, color: 'bg-amber-50 text-amber-700 border-amber-200' }
  return { text: `PPN ${ppnRate.value}% Inklusif (sudah termasuk dalam harga)`, color: 'bg-blue-50 text-blue-700 border-blue-200' }
})
</script>

<template>
  <Head title="Buat Invoice Baru" />

  <AuthenticatedLayout>
    <div class="max-w-4xl mx-auto space-y-6 py-6 sm:py-8 px-3 sm:px-6 lg:px-8">
      <!-- HEADER -->
      <div class="flex items-center gap-4">
        <Link :href="route('invoicing.invoices.index')" class="p-2.5 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
          <ArrowLeft class="w-5 h-5 text-slate-600" />
        </Link>
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
            Buat Invoice Baru
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Lengkapi data tagihan untuk dikirimkan ke pelanggan.</p>
        </div>
      </div>

      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-5 sm:p-8">
        <form @submit.prevent="submit" class="space-y-6">

          <!-- INFO DASAR -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">No. Invoice <span class="text-rose-500">*</span></label>
              <input v-model="form.invoice_number" type="text" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 bg-slate-50 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500" placeholder="Contoh: INV/2026/08/001">
              <p class="text-[10px] text-rose-500 mt-1" v-if="form.errors.invoice_number">{{ form.errors.invoice_number }}</p>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Customer <span class="text-rose-500">*</span></label>
              <select v-model="form.customer_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 bg-slate-50 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                <option value="" disabled>-- Pilih Klien / Perusahaan --</option>
                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
              <p class="text-[10px] text-rose-500 mt-1" v-if="form.errors.customer_id">{{ form.errors.customer_id }}</p>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Nomor PO / Referensi (Opsional)</label>
              <input v-model="form.po_number" type="text" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Contoh: PO-2026-001">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Tanggal Invoice <span class="text-rose-500">*</span></label>
              <input v-model="form.invoice_date" type="date" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 bg-slate-50 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Jatuh Tempo (Due Date) <span class="text-rose-500">*</span></label>
              <input v-model="form.due_date" type="date" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 bg-slate-50 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500">
            </div>
          </div>

          <!-- ITEM INVOICE -->
          <div class="pt-6 border-t border-slate-100">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-bold text-slate-800 text-sm">Item Tagihan (Barang / Jasa)</h3>
              <button type="button" @click="addItem" class="px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-[11px] font-bold transition-colors flex items-center gap-1.5">
                <Plus class="w-3.5 h-3.5" /> Tambah Item
              </button>
            </div>

            <div class="space-y-3">
              <div v-for="(item, index) in form.items" :key="index" class="flex flex-col sm:flex-row gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200">
                <div class="flex-1">
                  <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Deskripsi</label>
                  <input v-model="item.description" type="text" required class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs font-semibold focus:border-indigo-500" placeholder="Misal: Jasa Pembuatan Website">
                </div>
                <div class="w-full sm:w-24">
                  <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Qty</label>
                  <input v-model.number="item.qty" type="number" min="1" required @input="calculateTotal" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs font-bold focus:border-indigo-500">
                </div>
                <div class="w-full sm:w-40">
                  <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">
                    Harga Satuan (Rp)
                    <span v-if="ppnMode === 'include'" class="text-blue-500 normal-case">(inkl. PPN)</span>
                    <span v-else-if="ppnMode === 'exclude'" class="text-amber-500 normal-case">(belum PPN)</span>
                  </label>
                  <input v-model.number="item.unit_price" type="number" min="0" required @input="calculateTotal" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-xs font-bold focus:border-indigo-500">
                </div>
                <div class="w-full sm:w-10 flex items-end pb-0.5 justify-end">
                  <button type="button" @click="removeItem(index)" :disabled="form.items.length <= 1" class="p-2 text-slate-400 hover:text-rose-600 disabled:opacity-30 rounded-lg bg-white border border-slate-200 hover:border-rose-200 transition-colors">
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- PENGATURAN PPN -->
          <div class="pt-6 border-t border-slate-100">
            <h3 class="font-bold text-slate-800 text-sm mb-3">Pengaturan Pajak (PPN)</h3>

            <!-- Mode Selector -->
            <div class="grid grid-cols-3 gap-2 mb-4">
              <button
                type="button"
                @click="ppnMode = 'none'; onModeChange()"
                class="py-2.5 px-3 rounded-xl border-2 text-xs font-bold transition-all text-center"
                :class="ppnMode === 'none'
                  ? 'border-slate-600 bg-slate-600 text-white shadow-md'
                  : 'border-slate-200 bg-white text-slate-600 hover:border-slate-400'"
              >
                <span class="block text-base mb-0.5">🚫</span>
                Tanpa PPN
              </button>
              <button
                type="button"
                @click="ppnMode = 'exclude'; onModeChange()"
                class="py-2.5 px-3 rounded-xl border-2 text-xs font-bold transition-all text-center"
                :class="ppnMode === 'exclude'
                  ? 'border-amber-500 bg-amber-500 text-white shadow-md shadow-amber-500/20'
                  : 'border-slate-200 bg-white text-slate-600 hover:border-amber-300'"
              >
                <span class="block text-base mb-0.5">➕</span>
                PPN Eksklusif
                <span class="block text-[9px] font-normal opacity-80 mt-0.5">Ditambah ke total</span>
              </button>
              <button
                type="button"
                @click="ppnMode = 'include'; onModeChange()"
                class="py-2.5 px-3 rounded-xl border-2 text-xs font-bold transition-all text-center"
                :class="ppnMode === 'include'
                  ? 'border-blue-500 bg-blue-500 text-white shadow-md shadow-blue-500/20'
                  : 'border-slate-200 bg-white text-slate-600 hover:border-blue-300'"
              >
                <span class="block text-base mb-0.5">✅</span>
                PPN Inklusif
                <span class="block text-[9px] font-normal opacity-80 mt-0.5">Sudah termasuk harga</span>
              </button>
            </div>

            <!-- Tarif PPN (jika bukan none) -->
            <div v-if="ppnMode !== 'none'" class="flex items-center gap-3 p-3 rounded-xl border bg-slate-50" :class="ppnMode === 'include' ? 'border-blue-100' : 'border-amber-100'">
              <Info class="w-4 h-4 text-slate-400 shrink-0" />
              <div class="flex-1 flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-slate-700">Tarif PPN:</span>
                <div class="flex items-center gap-1">
                  <input
                    v-model.number="ppnRate"
                    type="number"
                    min="1"
                    max="100"
                    @input="calculateTotal"
                    class="w-16 px-2 py-1 rounded-lg border border-slate-300 bg-white text-xs font-bold text-center focus:border-indigo-500"
                  />
                  <span class="text-xs font-bold text-slate-500">%</span>
                </div>
                <span class="text-xs text-slate-500">(default PPN Indonesia: 11%)</span>
              </div>
            </div>

            <!-- Info badge mode aktif -->
            <div class="mt-3 px-3 py-2 rounded-xl border text-[11px] font-semibold" :class="modeLabel.color">
              ℹ️ {{ modeLabel.text }}
            </div>
          </div>

          <!-- PERHITUNGAN TOTAL -->
          <div class="pt-6 border-t border-slate-100 flex flex-col items-end gap-2.5 text-sm">
            <div class="w-full sm:w-72 space-y-2">
              <div class="flex justify-between">
                <span class="font-semibold text-slate-500">
                  Subtotal
                  <span v-if="ppnMode === 'include'" class="text-[10px] text-blue-500 font-bold">(harga jual ÷ PPN)</span>
                </span>
                <span class="font-bold text-slate-900">Rp {{ formatRp(form.subtotal) }}</span>
              </div>
              <div class="flex justify-between" v-if="ppnMode !== 'none'">
                <span class="font-semibold text-slate-500">
                  PPN {{ ppnRate }}%
                  <span class="text-[10px] font-bold" :class="ppnMode === 'include' ? 'text-blue-500' : 'text-amber-500'">
                    ({{ ppnMode === 'include' ? 'inkl.' : 'ekskl.' }})
                  </span>
                </span>
                <span class="font-bold" :class="ppnMode === 'include' ? 'text-blue-700' : 'text-amber-700'">
                  Rp {{ formatRp(form.tax_amount) }}
                </span>
              </div>
              <div class="flex justify-between pt-3 border-t border-slate-200">
                <span class="font-black text-slate-900 uppercase">Total Tagihan</span>
                <span class="font-black text-indigo-700 text-lg">Rp {{ formatRp(form.total_amount) }}</span>
              </div>
            </div>
          </div>

          <!-- NOTES -->
          <div class="pt-6 border-t border-slate-100">
            <label class="block text-xs font-bold text-slate-700 mb-1.5">Catatan Tambahan (Term & Conditions)</label>
            <textarea v-model="form.notes" rows="3" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 bg-slate-50 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Informasi rekening bank tujuan atau catatan lainnya..."></textarea>
          </div>

          <div class="pt-6 flex justify-end">
            <button type="submit" :disabled="form.processing" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold shadow-lg shadow-indigo-600/20 transition-all flex items-center justify-center gap-2">
              <CheckCircle2 class="w-5 h-5" />
              <span>Simpan & Buat Invoice</span>
            </button>
          </div>

        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
