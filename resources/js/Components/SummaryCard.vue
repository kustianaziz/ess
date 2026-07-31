<script setup>
import { computed } from 'vue';
import { Clock, CheckCircle, XCircle, Send, CheckCircle2 } from 'lucide-vue-next';

const props = defineProps({
  type: {
    type: String,
    required: true, // 'pending', 'approved', 'rejected', 'paid', 'completed'
  },
  count: {
    type: Number,
    default: 0,
  },
});

const config = computed(() => {
  switch (props.type) {
    case 'pending':
      return {
        label: 'Menunggu Persetujuan',
        icon: Clock,
        bgIcon: 'bg-amber-400 text-white',
        textColor: 'text-slate-800',
      };
    case 'approved':
      return {
        label: 'Disetujui',
        icon: CheckCircle,
        bgIcon: 'bg-emerald-500 text-white',
        textColor: 'text-slate-800',
      };
    case 'rejected':
      return {
        label: 'Ditolak',
        icon: XCircle,
        bgIcon: 'bg-rose-500 text-white',
        textColor: 'text-slate-800',
      };
    case 'paid':
      return {
        label: 'Sudah Dibayarkan',
        icon: Send,
        bgIcon: 'bg-sky-500 text-white',
        textColor: 'text-slate-800',
      };
    case 'completed':
    default:
      return {
        label: 'Selesai',
        icon: CheckCircle2,
        bgIcon: 'bg-slate-400 text-white',
        textColor: 'text-slate-800',
      };
  }
});
</script>

<template>
  <div class="bg-white rounded-2xl p-3 sm:p-4 border border-slate-100 shadow-sm flex items-center justify-between gap-2 hover:shadow-md transition-shadow">
    <div class="flex items-center gap-2 sm:gap-3 min-w-0">
      <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center shrink-0 shadow-sm" :class="config.bgIcon">
        <component :is="config.icon" class="w-4 h-4 sm:w-5 sm:h-5 stroke-[2]" />
      </div>
      <span class="text-[10px] sm:text-xs font-semibold text-slate-600 leading-tight truncate">
        {{ config.label }}
      </span>
    </div>
    <span class="text-lg sm:text-2xl font-bold tracking-tight shrink-0" :class="config.textColor">
      {{ count }}
    </span>
  </div>
</template>
