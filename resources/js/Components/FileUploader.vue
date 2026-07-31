<script setup>
import { ref } from 'vue';
import { UploadCloud, FileText, Trash2 } from 'lucide-vue-next';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  accept: {
    type: String,
    default: '.jpg,.jpeg,.png,.pdf',
  },
  maxSizeMB: {
    type: Number,
    default: 5,
  },
  multiple: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['update:modelValue']);
const isDragging = ref(false);
const fileInput = ref(null);

const triggerFileInput = () => {
  fileInput.value.click();
};

const handleFiles = (filesList) => {
  const newFiles = Array.from(filesList).filter((file) => {
    if (file.size > props.maxSizeMB * 1024 * 1024) {
      alert(`File ${file.name} melebihi batas ukuran ${props.maxSizeMB}MB.`);
      return false;
    }
    return true;
  });

  if (props.multiple) {
    emit('update:modelValue', [...props.modelValue, ...newFiles]);
  } else {
    emit('update:modelValue', newFiles.slice(0, 1));
  }
};

const onFileChange = (e) => {
  if (e.target.files.length) {
    handleFiles(e.target.files);
  }
};

const onDrop = (e) => {
  isDragging.value = false;
  if (e.dataTransfer.files.length) {
    handleFiles(e.dataTransfer.files);
  }
};

const removeFile = (index) => {
  const updated = [...props.modelValue];
  updated.splice(index, 1);
  emit('update:modelValue', updated);
};

const formatSize = (bytes) => {
  if (!bytes) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};
</script>

<template>
  <div class="space-y-4">
    <!-- Dropzone Area -->
    <div
      @click="triggerFileInput"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="onDrop"
      class="border-2 border-dashed rounded-2xl p-6 text-center cursor-pointer transition-all duration-200"
      :class="isDragging ? 'border-emerald-500 bg-emerald-50/50' : 'border-slate-200 bg-slate-50/50 hover:border-slate-300 hover:bg-slate-100/50'"
    >
      <input
        ref="fileInput"
        type="file"
        class="hidden"
        :accept="accept"
        :multiple="multiple"
        @change="onFileChange"
      />
      <div class="w-12 h-12 bg-white rounded-xl shadow-sm border border-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-500">
        <UploadCloud class="w-6 h-6 stroke-[1.8]" />
      </div>
      <p class="text-sm font-semibold text-slate-700">
        Klik untuk memilih file <span class="font-normal text-slate-500">atau tarik & lepas ke sini</span>
      </p>
      <p class="text-xs text-slate-400 mt-1">
        Format yang didukung: JPG, PNG, PDF (Maksimal {{ maxSizeMB }}MB per file)
      </p>
    </div>

    <!-- Preview List -->
    <div v-if="modelValue.length > 0" class="space-y-2">
      <div
        v-for="(file, index) in modelValue"
        :key="index"
        class="bg-white p-3 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between"
      >
        <div class="flex items-center gap-3 overflow-hidden">
          <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
            <FileText class="w-5 h-5" />
          </div>
          <div class="truncate">
            <p class="text-sm font-medium text-slate-800 truncate">{{ file.name }}</p>
            <p class="text-xs text-slate-400">{{ formatSize(file.size) }}</p>
          </div>
        </div>

        <button
          type="button"
          @click.stop="removeFile(index)"
          class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
        >
          <Trash2 class="w-4 h-4" />
        </button>
      </div>
    </div>
  </div>
</template>
