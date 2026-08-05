<script setup>
import Sidebar from '@/Components/Sidebar.vue';
import Topbar from '@/Components/Topbar.vue';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';
import Modal from '@/Components/Modal.vue';
import { usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { CheckCircle2, ShieldAlert, X } from 'lucide-vue-next';

const page = usePage();
const flashSuccess = ref(null);
const flashError = ref(null);
const showAccessDeniedModal = ref(false);
const showMobileSidebar = ref(false);

const lastShownSuccess = ref(null);
const lastShownError = ref(null);

watch(
  () => page.props.flash?.success,
  (val) => {
    if (val && val !== lastShownSuccess.value) {
      lastShownSuccess.value = val;
      flashSuccess.value = val;
      setTimeout(() => {
        flashSuccess.value = null;
      }, 4000);
    } else if (!val) {
      lastShownSuccess.value = null;
    }
  },
  { immediate: true }
);

watch(
  () => page.props.flash?.error,
  (val) => {
    if (val && val !== lastShownError.value) {
      lastShownError.value = val;
      flashError.value = val;
      showAccessDeniedModal.value = true;
    } else if (!val) {
      lastShownError.value = null;
    }
  },
  { immediate: true }
);

const closeModal = () => {
  showAccessDeniedModal.value = false;
  flashError.value = null;
};
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex text-slate-800 font-sans antialiased relative">
    <!-- Desktop Docked Sidebar -->
    <div class="hidden lg:block">
      <Sidebar />
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Topbar -->
      <Topbar @toggle-sidebar="showMobileSidebar = !showMobileSidebar" />

      <!-- Success Flash Toast Notification -->
      <div v-if="flashSuccess" class="fixed top-24 right-4 sm:right-8 z-50 animate-in fade-in slide-in-from-top-4">
        <div class="bg-emerald-600 text-white px-5 py-3.5 rounded-2xl shadow-xl flex items-center gap-3">
          <CheckCircle2 class="w-5 h-5 shrink-0" />
          <span class="text-xs sm:text-sm font-medium">{{ flashSuccess }}</span>
        </div>
      </div>

      <!-- Access Denied / Error Informative Modal -->
      <Modal :show="showAccessDeniedModal" @close="closeModal" maxWidth="md">
        <div class="p-6 text-center space-y-4">
          <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center mx-auto shadow-md">
            <ShieldAlert class="w-8 h-8" />
          </div>

          <div>
            <h3 class="text-lg font-bold text-slate-900 tracking-tight">
              Informasi Hak Akses
            </h3>
            <p class="text-xs text-slate-600 mt-2 leading-relaxed font-medium">
              {{ flashError || 'Halaman ini khusus untuk System Administrator. Akun Anda tidak memiliki perizinan untuk mengakses modul ini.' }}
            </p>
          </div>

          <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/60 text-[11px] text-slate-500 text-left">
            💡 <strong>Catatan:</strong> Jika Anda memerlukan akses Pengelolaan Pengguna atau Fitur Admin, silakan minta perizinan role <code>admin</code> ke System Administrator.
          </div>

          <div class="pt-2">
            <button
              @click="closeModal"
              class="w-full py-2.5 px-4 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition-all shadow-md"
            >
              Saya Mengerti
            </button>
          </div>
        </div>
      </Modal>

      <!-- Page Content -->
      <main class="flex-1 p-4 sm:p-6 lg:p-8 pb-24 lg:pb-8 overflow-y-auto">
        <slot />
      </main>

      <!-- Floating Mobile Bottom Navigation Bar -->
      <MobileBottomNav />
    </div>
  </div>
</template>
