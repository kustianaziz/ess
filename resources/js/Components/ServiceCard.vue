<script setup>
import { Link } from '@inertiajs/vue3';
import { ChevronRight, FileText, Utensils, Calendar } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    required: true,
  },
  href: {
    type: String,
    required: true,
  },
  buttonText: {
    type: String,
    required: true,
  },
  variant: {
    type: String,
    default: 'green', // 'green', 'orange', 'purple'
  },
});

const variantStyles = computed(() => {
  switch (props.variant) {
    case 'orange':
      return {
        bgIcon: 'bg-orange-500 text-white shadow-lg shadow-orange-100',
        icon: Utensils,
        borderHover: 'hover:border-orange-300 hover:shadow-orange-500/10',
        button: 'border-orange-200 text-orange-600 hover:bg-orange-50 hover:border-orange-300',
      };
    case 'purple':
      return {
        bgIcon: 'bg-purple-600 text-white shadow-lg shadow-purple-100',
        icon: Calendar,
        borderHover: 'hover:border-purple-300 hover:shadow-purple-500/10',
        button: 'border-purple-200 text-purple-600 hover:bg-purple-50 hover:border-purple-300',
      };
    case 'green':
    default:
      return {
        bgIcon: 'bg-emerald-600 text-white shadow-lg shadow-emerald-100',
        icon: FileText,
        borderHover: 'hover:border-emerald-300 hover:shadow-emerald-500/10',
        button: 'border-emerald-200 text-emerald-600 hover:bg-emerald-50 hover:border-emerald-300',
      };
  }
});
</script>

<template>
  <div
    class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between"
    :class="variantStyles.borderHover"
  >
    <div>
      <div class="flex items-center gap-4 mb-4">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center transition-transform hover:scale-105" :class="variantStyles.bgIcon">
          <component :is="variantStyles.icon" class="w-7 h-7 stroke-[2]" />
        </div>
        <h3 class="text-xl font-bold text-slate-800 leading-tight">
          {{ title }}
        </h3>
      </div>
      <p class="text-sm text-slate-500 leading-relaxed mb-6">
        {{ description }}
      </p>
    </div>

    <Link
      :href="href"
      class="w-full py-2.5 px-4 rounded-xl border text-sm font-semibold flex items-center justify-between transition-all group"
      :class="variantStyles.button"
    >
      <span>{{ buttonText }}</span>
      <ChevronRight class="w-4 h-4 transition-transform group-hover:translate-x-1" />
    </Link>
  </div>
</template>
