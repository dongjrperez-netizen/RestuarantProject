<script setup lang="ts">
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { X, Upload, Image } from 'lucide-vue-next';

interface Props {
  modelValue?: string;
  disabled?: boolean;
  accept?: string;
  maxSize?: number; // in MB
}

const props = withDefaults(defineProps<Props>(), {
  accept: '.jpg,.jpeg,.png,.webp',
  maxSize: 5
});

const emit = defineEmits<{
  'update:modelValue': [value: string];
  'upload': [file: File];
  'error': [message: string];
}>();

const fileInput = ref<HTMLInputElement>();
const selectedFile = ref<File | null>(null);
const previewUrl = ref<string>('');
const isUploading = ref(false);

// Initialize preview with existing image
if (props.modelValue) {
  previewUrl.value = props.modelValue;
}

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];

  if (!file) return;

  // Validate file type
  const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
  if (!validTypes.includes(file.type)) {
    emit('error', 'Please upload a valid image file (JPEG, PNG, WebP)');
    return;
  }

  // Validate file size
  if (file.size > props.maxSize * 1024 * 1024) {
    emit('error', `File size must be less than ${props.maxSize}MB`);
    return;
  }

  selectedFile.value = file;

  // Create preview
  const reader = new FileReader();
  reader.onload = (e) => {
    previewUrl.value = e.target?.result as string;
  };
  reader.readAsDataURL(file);

  // Emit upload event
  isUploading.value = true;
  emit('upload', file);
};

const clearImage = () => {
  selectedFile.value = null;
  previewUrl.value = '';
  if (fileInput.value) {
    fileInput.value.value = '';
  }
  emit('update:modelValue', '');
};

const triggerFileInput = () => {
  fileInput.value?.click();
};

// Method to reset uploading state (called from parent)
const resetUploadingState = () => {
  isUploading.value = false;
};

// Expose method to parent
defineExpose({
  resetUploadingState
});

// Watch for changes in modelValue prop
const imageUrl = computed(() => {
  return props.modelValue || previewUrl.value;
});
</script>

<template>
  <div class="space-y-4">
    <Label class="text-base font-medium">Dish Image</Label>

    <!-- File Input (Hidden) -->
    <input
      ref="fileInput"
      type="file"
      :accept="accept"
      @change="handleFileSelect"
      class="hidden"
      :disabled="disabled"
    />

    <!-- Image Preview or Upload Area -->
    <div class="relative">
      <div
        v-if="imageUrl"
        class="relative w-full max-w-md mx-auto"
      >
        <img
          :src="imageUrl"
          alt="Dish preview"
          class="w-full h-64 object-cover rounded-lg border"
        />

        <!-- Remove Button -->
        <Button
          v-if="!disabled"
          @click="clearImage"
          type="button"
          variant="destructive"
          size="icon"
          class="absolute top-2 right-2 h-8 w-8"
        >
          <X class="h-4 w-4" />
        </Button>

        <!-- Uploading Overlay -->
        <div
          v-if="isUploading"
          class="absolute inset-0 bg-black/50 flex items-center justify-center rounded-lg"
        >
          <div class="text-white text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-white mx-auto mb-2"></div>
            <p class="text-sm">Uploading...</p>
          </div>
        </div>
      </div>

      <!-- Upload Area -->
      <div
        v-else
        @click="triggerFileInput"
        class="w-full h-64 border-2 border-dashed border-muted-foreground/25 rounded-lg flex flex-col items-center justify-center cursor-pointer hover:border-muted-foreground/50 transition-colors"
        :class="{ 'pointer-events-none opacity-50': disabled }"
      >
        <Image class="h-12 w-12 text-muted-foreground mb-4" />
        <p class="text-muted-foreground text-center mb-2">
          Click to upload dish image
        </p>
        <p class="text-xs text-muted-foreground text-center">
          PNG, JPG, WebP up to {{ maxSize }}MB
        </p>
      </div>
    </div>

    <!-- Upload Button (when no image) -->
    <div v-if="!imageUrl" class="flex justify-center">
      <Button
        @click="triggerFileInput"
        type="button"
        variant="outline"
        :disabled="disabled"
        class="flex items-center gap-2"
      >
        <Upload class="h-4 w-4" />
        Choose Image
      </Button>
    </div>

    <!-- File Info -->
    <div v-if="selectedFile" class="text-sm text-muted-foreground">
      <p class="font-medium">{{ selectedFile.name }}</p>
      <p>{{ (selectedFile.size / 1024 / 1024).toFixed(2) }} MB</p>
    </div>
  </div>
</template>