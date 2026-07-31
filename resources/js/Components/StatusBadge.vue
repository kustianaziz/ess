<script setup>
import { computed } from 'vue';
import { Clock, CheckCircle, XCircle, Send, CheckCircle2, FileText } from 'lucide-vue-next';

const props = defineProps({
  status: {
    type: String,
    required: true,
  },
});

const config = computed(() => {
  switch (props.status) {
    case 'submitted':
      return {
        label: 'Menunggu Persetujuan',
        bgClass: 'bg-amber-50 text-amber-700 border-amber-200',
        dotClass: 'bg-amber-500',
        icon: Clock,
      };
    case 'approved':
      return {
        label: 'Disetujui',
        bgClass: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        dotClass: 'bg-emerald-500',
        icon: CheckCircle,
      };
    case 'rejected':
      return {
        label: 'Ditolak',
        bgClass: 'bg-rose-50 text-rose-700 border-rose-200',
        dotClass: 'bg-rose-500',
        icon: XCircle,
      };
    case 'paid':
      return {
        label: 'Sudah Dibayarkan',
        bgClass: 'bg-sky-50 text-sky-700 border-sky-200',
        dotClass: 'bg-sky-500',
        icon: Send,
      };
    case 'completed':
      return {
        label: 'Selesai',
        bgClass: 'bg-slate-100 text-slate-700 border-slate-200',
        dotClass: 'bg-slate-500',
        icon: CheckCircle2,
      };
    case 'draft':
    default:
      return {
        label: 'Draft',
        bgClass: 'bg-gray-100 text-gray-600 border-gray-200',
        dotClass: 'bg-gray-400',
        icon: FileText,
      };
  }
});
</script>

<template>
  <span
    class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full border shadow-sm transition-all"
    :class="config.bgClass"
  >
    <component :is="config.icon" class="w-3.5 h-3.5" />
    {{ config.label }}
  </span>
</template>
