<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { Download } from "lucide-vue-next";

const deferredPrompt = ref(null);
const showPrompt = ref(false);

const handleInstallPrompt = (e) => {
    e.preventDefault();
    deferredPrompt.value = e;
    showPrompt.value = true;
};

const installApp = async () => {
    if (!deferredPrompt.value) return;
    
    deferredPrompt.value.prompt();
    const { outcome } = await deferredPrompt.value.userChoice;
    
    if (outcome === "accepted") {
        console.log("User accepted the install prompt");
    } else {
        console.log("User dismissed the install prompt");
    }
    
    deferredPrompt.value = null;
    showPrompt.value = false;
};

const dismissPrompt = () => {
    showPrompt.value = false;
};

onMounted(() => {
    window.addEventListener("beforeinstallprompt", handleInstallPrompt);
});

onBeforeUnmount(() => {
    window.removeEventListener("beforeinstallprompt", handleInstallPrompt);
});
</script>

<template>
    <div v-if="showPrompt" class="fixed bottom-0 sm:bottom-4 left-0 right-0 sm:left-1/2 sm:-translate-x-1/2 z-50 p-4 w-full sm:w-[400px]">
        <div class="bg-indigo-600 rounded-2xl shadow-xl shadow-indigo-600/30 p-4 flex items-center justify-between gap-4 animate-in slide-in-from-bottom-5">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
                    <Download class="w-5 h-5 text-white" />
                </div>
                <div>
                    <h4 class="text-sm font-bold text-white leading-tight">Install Aplikasi ESS</h4>
                    <p class="text-[11px] text-indigo-100 mt-0.5">Akses lebih cepat & dapat notifikasi</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2 shrink-0">
                <button @click="dismissPrompt" class="px-3 py-1.5 text-xs font-semibold text-indigo-200 hover:text-white transition-colors">
                    Nanti
                </button>
                <button @click="installApp" class="px-4 py-1.5 rounded-lg bg-white text-indigo-600 text-xs font-bold shadow-sm hover:bg-indigo-50 transition-colors">
                    Install
                </button>
            </div>
        </div>
    </div>
</template>

