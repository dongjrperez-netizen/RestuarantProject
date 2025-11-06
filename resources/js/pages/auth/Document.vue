<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle, Plus, X } from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface Props {
  debug_info?: any;
}

const props = defineProps<Props>();
const page = usePage();

interface DocumentItem {
  id: number;
  file: File | null;
  type: string;
  error: string;
  required: boolean;
}

const initialDocuments = () => [
  { id: 1, file: null, type: 'Business Permit', error: '', required: true },
  { id: 2, file: null, type: 'Mayor\'s Permit', error: '', required: true },
  { id: 3, file: null, type: 'BIR Registration', error: '', required: true }
];

const documents = ref<DocumentItem[]>(initialDocuments());
const nextId = ref(4);

const form = useForm({
  documents: [] as File[],
  document_types: [] as string[],
});

const addDocumentField = () => {
  if (documents.value.length < 5) {
    documents.value.push({
      id: nextId.value++,
      file: null,
      type: 'Optional Document',
      error: '',
      required: false
    });
  }
};

const removeDocumentField = (id: number) => {
  if (documents.value.length > 3) {
    documents.value = documents.value.filter(doc => doc.id !== id);
  }
};

const handleFileChange = (e: Event, id: number) => {
  const target = e.target as HTMLInputElement;
  const document = documents.value.find(doc => doc.id === id);
  
  if (document && target.files && target.files[0]) {
    const file = target.files[0];
    const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    
    if (!validTypes.includes(file.type)) {
      document.error = 'Please upload a PDF or image file (JPEG, PNG, JPG)';
      document.file = null;
      return;
    }
    
    if (file.size > 5 * 1024 * 1024) {
      document.error = 'File size must be less than 5MB';
      document.file = null;
      return;
    }
    
    document.file = file;
    document.error = '';
  }
};

const uploadProgress = ref(0);
const uploadStatus = ref('');

const submit = () => {
  // Reset form before filling
  form.documents = [];
  form.document_types = [];

  // Only include documents that have files
  documents.value.forEach(doc => {
    if (doc.file) {
      form.documents.push(doc.file);
      form.document_types.push(doc.type);
    }
  });

  uploadProgress.value = 0;
  uploadStatus.value = 'Uploading documents...';

  form.post(route('register.documents.store'), {
    forceFormData: true, // <-- ensures multipart/form-data is sent
    timeout: 300000, // 5 minutes timeout (300 seconds)
    onProgress: (progress) => {
      // Update progress if available
      if (progress && progress.percentage) {
        uploadProgress.value = progress.percentage;
        uploadStatus.value = `Uploading... ${Math.round(progress.percentage)}%`;
      }
    },
    onFinish: () => {
      uploadStatus.value = '';
      uploadProgress.value = 0;
    },
    onError: () => {
      uploadStatus.value = 'Upload failed. Please try again.';
    }
  });
};

const hasRequiredDocuments = () => {
  const requiredDocs = documents.value.filter(doc => doc.required);
  return requiredDocs.every(doc => doc.file !== null);
};
</script>
<template>
  <AuthBase title="Upload Required Documents" description="Please upload your business documents to complete registration">
    <Head title="Upload Documents" />

    <!-- Debug Info Section -->
    <div v-if="props.debug_info" style="background: #e3f2fd; padding: 15px; margin-bottom: 20px; border-radius: 8px; border: 1px solid #2196f3;">
      <h3 style="color: #1976d2; margin: 0 0 10px 0;">🔍 Debug Information</h3>
      <pre style="background: #fff; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px;">{{ JSON.stringify(props.debug_info, null, 2) }}</pre>
    </div>

    <form @submit.prevent="submit" class="flex flex-col gap-6">
      <div class="grid gap-6">
        <div v-for="document in documents" :key="document.id" class="grid gap-2 relative">
          <div class="flex items-center justify-between">
            <Label :for="`document-${document.id}`">
              {{ document.type }}
              <span v-if="document.required" class="text-red-500 ml-1">*</span>
              <span v-else class="text-gray-400 text-sm ml-1">(Optional)</span>
            </Label>
            <Button
              v-if="!document.required && documents.length > 3"
              @click="removeDocumentField(document.id)"
              type="button"
              variant="ghost"
              size="icon"
              class="h-6 w-6"
            >
              <X class="h-4 w-4" />
            </Button>
          </div>
          
          <Input 
            :id="`document-${document.id}`"
            :name="`document-${document.id}`"
            type="file" 
            accept=".pdf,.jpg,.jpeg,.png" 
            @change="handleFileChange($event, document.id)"
            :class="document.file ? 'border-green-500' : ''"
          />
          
          <InputError :message="document.error" />
          
          <div v-if="document.file" class="text-sm text-green-600 flex items-center gap-1">
            <span class="font-medium">Selected:</span> 
            {{ document.file.name }} 
            <span class="text-xs text-gray-500">
              ({{ (document.file.size / 1024 / 1024).toFixed(2) }} MB)
            </span>
          </div>
        </div>

        <Button
          v-if="documents.length < 5"
          @click="addDocumentField"
          type="button"
          variant="outline"
          class="flex items-center gap-2"
        >
          <Plus class="h-4 w-4" />
          Add Optional Document
        </Button>

        <!-- Upload Progress -->
        <div v-if="form.processing" class="space-y-2">
          <div class="flex justify-between text-sm text-muted-foreground">
            <span>{{ uploadStatus }}</span>
            <span v-if="uploadProgress > 0">{{ Math.round(uploadProgress) }}%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2.5">
            <div
              class="bg-blue-600 h-2.5 rounded-full transition-all duration-300"
              :style="{ width: uploadProgress + '%' }"
            ></div>
          </div>
          <p class="text-xs text-muted-foreground text-center">
            This may take a few moments. Please don't close this page.
          </p>
        </div>

        <Button
          type="submit"
          :disabled="form.processing || !hasRequiredDocuments()"
          class="mt-4"
        >
          <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
          {{ hasRequiredDocuments() ? 'Upload Documents' : 'Please upload all required documents' }}
        </Button>
      </div>

      <div class="text-center text-sm text-muted-foreground">
        You can upload these later from your account settings
        <TextLink :href="route('home')" class="underline underline-offset-4">
          Skip for now
        </TextLink>
      </div>
    </form>
  </AuthBase>
</template>