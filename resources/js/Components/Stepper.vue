<script setup>
import { Check } from 'lucide-vue-next';

const props = defineProps({
  currentStep: {
    type: Number,
    required: true,
    default: 1,
  },
  steps: {
    type: Array,
    default: () => [
      { number: 1, label: 'Informasi' },
      { number: 2, label: 'Lampiran' },
      { number: 3, label: 'Review & Kirim' },
    ],
  },
  accentColor: {
    type: String,
    default: 'emerald', // emerald (reimbursement), orange (operasional), purple (cuti)
  },
});

const getBgColor = (stepNumber) => {
  if (stepNumber < props.currentStep) return 'bg-emerald-600 text-white';
  if (stepNumber === props.currentStep) {
    if (props.accentColor === 'orange') return 'bg-orange-500 text-white shadow-md shadow-orange-200';
    if (props.accentColor === 'purple') return 'bg-purple-600 text-white shadow-md shadow-purple-200';
    return 'bg-emerald-600 text-white shadow-md shadow-emerald-200';
  }
  return 'bg-slate-200 text-slate-500';
};
</script>

<template>
  <div class="w-full max-w-2xl mx-auto py-2 sm:py-4 px-1 sm:px-4">
    <div class="relative flex items-center justify-between">
      <!-- Line background -->
      <div class="absolute left-0 top-4 sm:top-1/2 -translate-y-1/2 w-full h-1 bg-slate-200 -z-0 rounded-full"></div>
      
      <!-- Progress fill -->
      <div
        class="absolute left-0 top-4 sm:top-1/2 -translate-y-1/2 h-1 -z-0 transition-all duration-300 rounded-full"
        :class="{
          'bg-orange-500': accentColor === 'orange',
          'bg-purple-600': accentColor === 'purple',
          'bg-emerald-600': accentColor === 'emerald'
        }"
        :style="{ width: `${((currentStep - 1) / (steps.length - 1)) * 100}%` }"
      ></div>

      <!-- Steps -->
      <div
        v-for="step in steps"
        :key="step.number"
        class="flex flex-col items-center bg-white px-1 sm:px-2 z-10"
      >
        <div
          class="w-7 h-7 sm:w-9 sm:h-9 rounded-full flex items-center justify-center font-semibold text-xs sm:text-sm transition-all duration-200"
          :class="getBgColor(step.number)"
        >
          <Check v-if="step.number < currentStep" class="w-4 h-4 sm:w-5 sm:h-5 stroke-[2.5]" />
          <span v-else>{{ step.number }}</span>
        </div>
        <span
          class="mt-1 sm:mt-2 text-[10px] sm:text-xs font-medium transition-colors text-center max-w-[65px] sm:max-w-none leading-tight"
          :class="step.number === currentStep ? 'text-slate-900 font-semibold' : 'text-slate-400'"
        >
          {{ step.label }}
        </span>
      </div>
    </div>
  </div>
</template>
