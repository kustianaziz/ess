<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Bell, CheckCheck, Inbox, CheckSquare, Eye, CheckCircle2, XCircle, CreditCard, ArrowRight } from 'lucide-vue-next';

const props = defineProps({
  notifications: Array,
});

const markAllRead = () => {
  router.post(route('notifikasi.read-all'), {}, { preserveScroll: true });
};

const handleNotificationClick = (item) => {
  const targetUrl = item.data?.url || '/approval';
  
  if (!item.read_at) {
    router.post(route('notifikasi.mark-as-read', { id: item.id }), {}, {
      preserveScroll: true,
      onSuccess: () => {
        router.visit(targetUrl);
      }
    });
  } else {
    router.visit(targetUrl);
  }
};

const getNotificationIcon = (title) => {
  if (!title) return Bell;
  const t = title.toLowerCase();
  if (t.includes('persetujuan') || t.includes('perlu')) return CheckSquare;
  if (t.includes('disetujui')) return CheckCircle2;
  if (t.includes('ditolak')) return XCircle;
  if (t.includes('pembayaran') || t.includes('diproses')) return CreditCard;
  return Bell;
};

const getNotificationIconBg = (title) => {
  if (!title) return 'bg-indigo-50 text-indigo-600';
  const t = title.toLowerCase();
  if (t.includes('persetujuan') || t.includes('perlu')) return 'bg-amber-50 text-amber-600 border border-amber-200';
  if (t.includes('disetujui')) return 'bg-emerald-50 text-emerald-600 border border-emerald-200';
  if (t.includes('ditolak')) return 'bg-rose-50 text-rose-600 border border-rose-200';
  if (t.includes('pembayaran') || t.includes('diproses')) return 'bg-sky-50 text-sky-600 border border-sky-200';
  return 'bg-indigo-50 text-indigo-600 border border-indigo-200';
};
</script>

<template>
  <Head title="Notifikasi" />

  <AuthenticatedLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
      <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between gap-4">
        <div>
          <h1 class="text-lg sm:text-xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
            <Bell class="w-5 h-5 text-indigo-600" />
            Notifikasi Saya
          </h1>
          <p class="text-xs text-slate-400 mt-1">
            Pemberitahuan perubahan status pengajuan dan shortcut langsung ke proses persetujuan.
          </p>
        </div>

        <button
          v-if="notifications.some(n => !n.read_at)"
          @click="markAllRead"
          class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold flex items-center gap-2 transition-colors shrink-0"
        >
          <CheckCheck class="w-4 h-4 text-emerald-600" />
          <span class="hidden sm:inline">Tandai Semua Dibaca</span>
        </button>
      </div>

      <!-- Notifications List with Direct Shortcuts -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm divide-y divide-slate-100 overflow-hidden">
        <div
          v-for="item in notifications"
          :key="item.id"
          @click="handleNotificationClick(item)"
          class="p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 hover:bg-slate-50 transition-all cursor-pointer group"
          :class="{ 'bg-indigo-50/40 border-l-4 border-l-indigo-600': !item.read_at }"
        >
          <div class="flex items-start gap-3.5 flex-1 min-w-0">
            <div
              class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 shadow-xs transition-transform group-hover:scale-105"
              :class="getNotificationIconBg(item.data?.title)"
            >
              <component :is="getNotificationIcon(item.data?.title)" class="w-5 h-5" />
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <h4 class="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">
                  {{ item.data?.title || 'Pemberitahuan Sistem' }}
                </h4>
                <span v-if="!item.read_at" class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
              </div>
              
              <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                {{ item.data?.message || 'Perubahan status pengajuan telah diperbarui.' }}
              </p>

              <span class="text-[10px] text-slate-400 font-medium mt-2 block">
                {{ new Date(item.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) }}
              </span>
            </div>
          </div>

          <!-- Direct Shortcut Button -->
          <div class="w-full sm:w-auto pt-2 sm:pt-0 flex justify-end shrink-0 border-t sm:border-t-0 border-slate-100">
            <button
              type="button"
              @click.stop="handleNotificationClick(item)"
              class="w-full sm:w-auto px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 shadow-sm"
              :class="item.data?.url?.includes('/approval')
                ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-indigo-600/30'
                : 'bg-slate-900 hover:bg-slate-800 text-white shadow-slate-900/30'"
            >
              <CheckSquare v-if="item.data?.url?.includes('/approval')" class="w-3.5 h-3.5" />
              <Eye v-else class="w-3.5 h-3.5" />
              <span>{{ item.data?.url?.includes('/approval') ? 'Proses Persetujuan' : 'Lihat Detail' }}</span>
              <ArrowRight class="w-3.5 h-3.5 transition-transform group-hover:translate-x-1" />
            </button>
          </div>
        </div>

        <div v-if="notifications.length === 0" class="p-12 text-center text-slate-400 space-y-2">
          <Inbox class="w-10 h-10 mx-auto text-slate-300 stroke-[1.5]" />
          <p class="text-sm font-medium">Tidak ada notifikasi saat ini.</p>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
