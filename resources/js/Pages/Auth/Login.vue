<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
  GraduationCap,
  Mail,
  Lock,
  Eye,
  EyeOff,
  LogIn,
  FileText,
  Utensils,
  Calendar,
  ShieldCheck
} from 'lucide-vue-next';

defineProps({
  canResetPassword: {
    type: Boolean,
  },
  status: {
    type: String,
  },
});

const showPassword = ref(false);

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <Head title="Masuk - Portal ESS EDU" />

  <div class="min-h-screen bg-slate-950 flex flex-col justify-center items-center p-4 sm:p-6 lg:p-8 font-sans antialiased relative overflow-hidden">
    <!-- Decorative Glowing Background Orbs -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-purple-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Container Box -->
    <div class="w-full max-w-5xl bg-[#0F172A]/90 backdrop-blur-xl border border-slate-800 rounded-3xl shadow-2xl overflow-hidden grid grid-cols-1 lg:grid-cols-12 z-10">
      
      <!-- LEFT HERO COLUMN (Branding & Feature Showcase) -->
      <div class="lg:col-span-5 p-8 lg:p-10 bg-gradient-to-b from-slate-900 via-slate-900/90 to-[#0B1120] border-b lg:border-b-0 lg:border-r border-slate-800 flex flex-col justify-between relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>
        
        <div>
          <!-- Logo & Brand Header -->
          <div class="flex items-center gap-3.5 mb-10">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-xl shadow-indigo-500/30 ring-1 ring-white/20">
              <GraduationCap class="w-7 h-7 stroke-[2]" />
            </div>
            <div>
              <h1 class="text-2xl font-black text-white tracking-tight leading-none">
                EDU
              </h1>
              <p class="text-xs font-semibold text-indigo-400 tracking-wider uppercase mt-1">
                Employee Self Service
              </p>
            </div>
          </div>

          <!-- Headline -->
          <div class="space-y-3 mb-8">
            <h2 class="text-2xl font-bold text-white leading-tight">
              Portal Layanan Digital Karyawan EDU
            </h2>
            <p class="text-xs text-slate-400 leading-relaxed">
              Platform terpadu pengajuan reimbursement, kebutuhan operasional, dan izin cuti karyawan secara cepat, transparan, dan terintegrasi.
            </p>
          </div>

          <!-- Feature Cards List -->
          <div class="space-y-3">
            <div class="p-3.5 rounded-2xl bg-slate-800/40 border border-slate-700/50 flex items-center gap-3.5 backdrop-blur-sm">
              <div class="w-9 h-9 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                <FileText class="w-5 h-5" />
              </div>
              <div>
                <h4 class="text-xs font-bold text-slate-200">Reimbursement Karyawan</h4>
                <p class="text-[11px] text-slate-400">Klaim penggantian biaya kerja digital</p>
              </div>
            </div>

            <div class="p-3.5 rounded-2xl bg-slate-800/40 border border-slate-700/50 flex items-center gap-3.5 backdrop-blur-sm">
              <div class="w-9 h-9 rounded-xl bg-orange-500/20 text-orange-400 flex items-center justify-center shrink-0">
                <Utensils class="w-5 h-5" />
              </div>
              <div>
                <h4 class="text-xs font-bold text-slate-200">Konsumsi / Operasional</h4>
                <p class="text-[11px] text-slate-400">Pengajuan dana rapat & kegiatan kantor</p>
              </div>
            </div>

            <div class="p-3.5 rounded-2xl bg-slate-800/40 border border-slate-700/50 flex items-center gap-3.5 backdrop-blur-sm">
              <div class="w-9 h-9 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center shrink-0">
                <Calendar class="w-5 h-5" />
              </div>
              <div>
                <h4 class="text-xs font-bold text-slate-200">Cuti Karyawan</h4>
                <p class="text-[11px] text-slate-400">Tracking kuota & persetujuan berjenjang</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Info -->
        <div class="pt-8 border-t border-slate-800/80 mt-8 flex items-center gap-2 text-[11px] text-slate-400">
          <ShieldCheck class="w-4 h-4 text-emerald-400 shrink-0" />
          <span>Sistem aman & terenkripsi • EDU System v2.0</span>
        </div>
      </div>

      <!-- RIGHT LOGIN FORM COLUMN -->
      <div class="lg:col-span-7 p-8 lg:p-12 bg-white flex flex-col justify-between">
        <div>
          <!-- Title Section -->
          <div class="mb-8">
            <h3 class="text-2xl font-bold text-slate-900 tracking-tight">
              Selamat Datang Kembali 👋
            </h3>
            <p class="text-xs text-slate-500 mt-1">
              Silakan masukkan kredensial akun Anda untuk mengakses portal ESS.
            </p>
          </div>

          <!-- Status Message -->
          <div v-if="status" class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-medium text-emerald-700">
            {{ status }}
          </div>

          <!-- Form -->
          <form @submit.prevent="submit" class="space-y-5">
            <!-- Username / Email Input -->
            <div>
              <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                Username / Email Karyawan
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                  <Mail class="w-4 h-4" />
                </div>
                <input
                  id="email"
                  type="text"
                  v-model="form.email"
                  required
                  autofocus
                  autocomplete="username"
                  placeholder="Masukkan Username atau Email..."
                  class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
                  :class="{ 'border-rose-500 ring-2 ring-rose-200': form.errors.email }"
                />
              </div>
              <p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-600 font-medium">
                {{ form.errors.email }}
              </p>
            </div>

            <!-- Password Input -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                  Kata Sandi
                </label>
                <Link
                  v-if="canResetPassword"
                  :href="route('password.request')"
                  class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 transition-colors"
                >
                  Lupa Kata Sandi?
                </Link>
              </div>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                  <Lock class="w-4 h-4" />
                </div>
                <input
                  id="password"
                  :type="showPassword ? 'text' : 'password'"
                  v-model="form.password"
                  required
                  autocomplete="current-password"
                  placeholder="••••••••"
                  class="w-full pl-10 pr-11 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
                  :class="{ 'border-rose-500 ring-2 ring-rose-200': form.errors.password }"
                />
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600"
                >
                  <Eye v-if="!showPassword" class="w-4 h-4" />
                  <EyeOff v-else class="w-4 h-4" />
                </button>
              </div>
              <p v-if="form.errors.password" class="mt-1.5 text-xs text-rose-600 font-medium">
                {{ form.errors.password }}
              </p>
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between pt-1">
              <label class="flex items-center cursor-pointer select-none">
                <input
                  type="checkbox"
                  v-model="form.remember"
                  class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500"
                />
                <span class="ml-2 text-xs font-semibold text-slate-600">
                  Ingat Saya di Perangkat Ini
                </span>
              </label>
            </div>

            <!-- Submit Button -->
            <button
              type="submit"
              :disabled="form.processing"
              class="w-full py-3.5 px-4 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-600/30 hover:shadow-indigo-600/40 transition-all duration-200 flex items-center justify-center gap-2 group disabled:opacity-50"
            >
              <LogIn class="w-4 h-4 transition-transform group-hover:translate-x-1" />
              <span>Masuk ke System ESS</span>
            </button>
          </form>
        </div>

        <!-- Copyright Footer -->
        <p class="text-[11px] text-slate-400 text-center mt-6">
          © {{ new Date().getFullYear() }} EDU Employee Self Service. All rights reserved.
        </p>
      </div>

    </div>
  </div>
</template>
