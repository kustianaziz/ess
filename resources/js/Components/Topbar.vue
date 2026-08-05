<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { Bell, ChevronDown, User, LogOut, Menu, CheckCheck, Inbox } from 'lucide-vue-next';

defineEmits(['toggleSidebar']);

const page = usePage();
const user = ref(page.props.auth.user);
const showProfileMenu = ref(false);
const showNotificationMenu = ref(false);

const notifiedIds = ref(new Set());

// Keep user reactive when page props change
watch(
  () => page.props.auth.user,
  (newUser) => {
    user.value = newUser;
    checkNewDesktopNotifications(newUser);
  },
  { deep: true, immediate: true }
);

const requestWebNotificationPermission = () => {
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
  }
};

const triggerDesktopNotification = (title, message, url) => {
  if ('Notification' in window && Notification.permission === 'granted') {
    try {
      const notif = new Notification(title || 'Notifikasi Baru ESS', {
        body: message || 'Ada pembaruan transaksi pengajuan baru.',
        icon: '/favicon.ico',
        badge: '/favicon.ico',
      });
      notif.onclick = () => {
        window.focus();
        if (url) {
          router.visit(url);
        }
      };
    } catch (e) {
      console.warn('Desktop notification error:', e);
    }
  }
};

const checkNewDesktopNotifications = (userData) => {
  if (!userData || !userData.recent_notifications) return;

  userData.recent_notifications.forEach((n) => {
    // If notification is unread and has not been popped up yet
    if (!n.read_at && !notifiedIds.value.has(n.id)) {
      notifiedIds.value.add(n.id);
      triggerDesktopNotification(n.data?.title, n.data?.message, n.data?.url);
    }
  });
};

let pollInterval = null;

onMounted(() => {
  requestWebNotificationPermission();

  // Populate initial notified IDs to prevent popup spam on first load
  if (user.value?.recent_notifications) {
    user.value.recent_notifications.forEach((n) => {
      notifiedIds.value.add(n.id);
    });
  }

  // Poll for new notifications every 6 seconds across open tabs/browsers
  pollInterval = setInterval(() => {
    router.reload({
      only: ['auth'],
      preserveScroll: true,
      preserveState: true,
    });
  }, 6000);
});

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval);
});

const markAllRead = () => {
  router.post(route('notifikasi.read-all'), {}, { preserveScroll: true });
};

const markReadAndNavigate = (notification) => {
  showNotificationMenu.value = false;
  router.post(route('notifikasi.mark-as-read', { id: notification.id }), {}, {
    preserveScroll: true,
    onSuccess: () => {
      if (notification.data?.url) {
        router.visit(notification.data.url);
      }
    }
  });
};
</script>

<template>
  <header class="h-16 sm:h-20 bg-white border-b border-slate-100 px-3 sm:px-8 flex items-center justify-between sticky top-0 z-20 shadow-sm/50">
    <!-- Left: Welcome Message -->
    <div class="flex items-center gap-2 sm:gap-3 min-w-0 pr-2">

      <div class="min-w-0">
        <h2 class="text-xs sm:text-lg md:text-xl font-bold text-slate-800 tracking-tight truncate max-w-[130px] xs:max-w-[190px] sm:max-w-none">
          Selamat datang, {{ user?.name || 'Karyawan' }} 👋
        </h2>
        <p class="text-[9px] sm:text-xs text-slate-400 font-medium truncate hidden xs:block">
          Portal Employee Self Service (ESS)
        </p>
      </div>
    </div>

    <!-- Right Actions: Notifications & Profile -->
    <div class="flex items-center gap-2 sm:gap-6 shrink-0">
      <!-- Live Notification Dropdown Popover -->
      <div class="relative">
        <button
          @click="showNotificationMenu = !showNotificationMenu; requestWebNotificationPermission();"
          class="relative p-2 sm:p-2.5 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-50 transition-colors"
          title="Notifikasi"
        >
          <Bell class="w-5 h-5 stroke-[2]" />
          <span
            v-if="user?.unread_notifications_count > 0"
            class="absolute top-1 right-1 w-4 h-4 bg-rose-500 text-white font-bold text-[10px] rounded-full flex items-center justify-center border-2 border-white animate-pulse"
          >
            {{ user.unread_notifications_count }}
          </span>
        </button>

        <!-- Backdrop Overlay on Mobile -->
        <div
          v-if="showNotificationMenu"
          @click="showNotificationMenu = false"
          class="sm:hidden fixed inset-0 z-40 bg-slate-950/20 backdrop-blur-xs"
        ></div>

        <!-- Dropdown Notifications Menu Box -->
        <div
          v-if="showNotificationMenu"
          class="fixed inset-x-3 top-16 sm:absolute sm:inset-x-auto sm:right-0 sm:top-auto sm:mt-2 sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-100 py-3 z-50 animate-in fade-in slide-in-from-top-2"
        >
          <div class="px-4 pb-3 border-b border-slate-100 flex items-center justify-between">
            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
              Notifikasi Terkini
            </h4>
            <button
              v-if="user?.unread_notifications_count > 0"
              @click="markAllRead"
              class="text-[11px] text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1"
            >
              <CheckCheck class="w-3.5 h-3.5" />
              <span>Tandai dibaca</span>
            </button>
          </div>

          <!-- Notification Items List -->
          <div class="max-h-80 overflow-y-auto custom-scrollbar divide-y divide-slate-50">
            <div
              v-for="n in (user?.recent_notifications || [])"
              :key="n.id"
              @click="markReadAndNavigate(n)"
              class="p-3.5 flex items-start gap-3 hover:bg-slate-50 transition-colors cursor-pointer text-left"
              :class="{ 'bg-indigo-50/40': !n.read_at }"
            >
              <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0 mt-0.5">
                <Bell class="w-4 h-4" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                  <p class="text-xs font-bold text-slate-900 truncate">
                    {{ n.data?.title || 'Notifikasi System' }}
                  </p>
                  <span class="text-[9px] text-slate-400 shrink-0">
                    {{ new Date(n.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}
                  </span>
                </div>
                <p class="text-[11px] text-slate-600 mt-0.5 line-clamp-2 leading-relaxed">
                  {{ n.data?.message || 'Update status pengajuan baru.' }}
                </p>
              </div>
            </div>

            <div v-if="!user?.recent_notifications || user.recent_notifications.length === 0" class="py-8 text-center text-slate-400">
              <Inbox class="w-8 h-8 mx-auto text-slate-300 mb-1" />
              <p class="text-xs font-medium">Belum ada notifikasi.</p>
            </div>
          </div>

          <div class="pt-2 px-4 border-t border-slate-100 text-center">
            <Link
              :href="route('notifikasi.index')"
              @click="showNotificationMenu = false"
              class="text-xs font-bold text-indigo-600 hover:text-indigo-700 block py-1"
            >
              Lihat Semua Notifikasi →
            </Link>
          </div>
        </div>
      </div>

      <!-- User Profile Dropdown Pill -->
      <div class="relative">
        <button
          @click="showProfileMenu = !showProfileMenu"
          class="flex items-center gap-2 sm:gap-3 py-1 sm:py-1.5 px-2 sm:px-3 rounded-2xl border border-slate-200 hover:border-slate-300 transition-all bg-white hover:bg-slate-50"
        >
          <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-gradient-to-tr from-slate-700 to-slate-900 text-white flex items-center justify-center font-bold text-xs sm:text-sm shadow-sm overflow-hidden shrink-0">
            <img v-if="user?.avatar" :src="user.avatar" class="w-full h-full object-cover" />
            <span v-else>{{ user?.name ? user.name.charAt(0) : 'A' }}</span>
          </div>
          <div class="text-left hidden sm:block">
            <p class="text-sm font-bold text-slate-800 leading-none">
              {{ user?.name || 'Karyawan' }}
            </p>
            <p class="text-[11px] text-slate-400 font-medium leading-tight mt-1">
              {{ user?.division || 'ESS' }}
            </p>
          </div>
          <ChevronDown class="w-4 h-4 text-slate-400 stroke-[2] ml-1 hidden sm:block" />
        </button>

        <!-- Profile Dropdown Menu -->
        <div
          v-if="showProfileMenu"
          @click="showProfileMenu = false"
          class="absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-50 animate-in fade-in slide-in-from-top-2"
        >
          <Link
            :href="route('profile.edit')"
            class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 font-medium"
          >
            <User class="w-4 h-4 text-slate-400" />
            <span>Profil Saya</span>
          </Link>
          <div class="my-1 border-t border-slate-100"></div>
          <Link
            :href="route('logout')"
            method="post"
            as="button"
            class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50 font-medium text-left"
          >
            <LogOut class="w-4 h-4 text-rose-500" />
            <span>Keluar</span>
          </Link>
        </div>
      </div>
    </div>
  </header>
</template>
