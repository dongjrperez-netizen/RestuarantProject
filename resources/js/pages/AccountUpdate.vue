<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { BreadcrumbItem } from '@/types';
import { useForm, Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import AddLayoutAccountUpdate from '@/layouts/AddLayoutAccountUpdate.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Account Update',
        href: '/account/update',
    }
];
interface DocumentItem {
    id: number;
    file: File | null;
    type: string;
    error: string;
    optionalName?: string;
}

const initialDocuments = () => [
    { id: 1, file: null, type: 'Business Permit', error: '' },
    { id: 2, file: null, type: "Mayor's Permit", error: '' },
    { id: 3, file: null, type: 'BIR Registration', error: '' }
];

const documents = ref<DocumentItem[]>(initialDocuments());
const nextId = ref(4);

const additionalDocumentTypes = [
    'Sanitary Permit',
    'Fire Safety Certificate',
    'DTI/SEC Registration',
    'Lease Contract',
    'Other Business Documents'
];

const addDocumentField = () => {
  // Count how many 'Optional' documents exist
  const optionalCount = documents.value.filter(doc => doc.type === 'Optional').length;
  if (optionalCount < 2) {
    documents.value.push({
      id: nextId.value++,
      file: null,
      type: 'Optional',
      error: '',
      optionalName: ''
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

const hasAtLeastOneDocument = () => {
    return documents.value.some(doc => doc.file !== null);
};

const user = ref<{ 
    name: string; 
    email: string; 
    status?: string; 
    id?: number 
} | null>(null);
const form = useForm({
    name: '',
    email: '',
    password: '',
    documents: [] as File[],
    document_types: [] as string[],
});

onMounted(async () => {
    try {
        const res = await fetch('/api/account/user');
        if (res.ok) {
            const data = await res.json();
            // Concatenate name fields using correct column names
            const fullName = [data.first_name, data.middle_name, data.last_name]
                .filter(Boolean)
                .join(' ');
            user.value = {
                name: fullName,
                email: data.email,
                status: data.status,
                id: data.id
            };
            // Only prefill if empty
            if (!form.name) form.name = fullName;
            if (!form.email) form.email = data.email;
        }
    } catch (e) {
        // handle error
    }
});

function submitForm() {
        // Add document files to the form
        const documentFiles: File[] = [];
        const documentTypes: string[] = [];
        
        documents.value.forEach(doc => {
            if (doc.file) {
                documentFiles.push(doc.file);
                if (doc.type === 'Optional') {
                    documentTypes.push(doc.optionalName || 'Optional');
                } else {
                    documentTypes.push(doc.type);
                }
            }
        });

        // Use Inertia form with documents
        form.documents = documentFiles;
        form.document_types = documentTypes;

        form.post(route('account.update'), {
            forceFormData: true,
            onFinish: () => {
                documents.value = initialDocuments();
                form.documents = [];
                form.document_types = [];
            },
            onError: (errors) => {
                console.error('Form submission errors:', errors);
            },
            onSuccess: () => {
                console.log('Account updated successfully');
            }
        });
}
</script>
<template>
    <Head title="Account Update" />
    <AddLayoutAccountUpdate :breadcrumbs="breadcrumbs">
        <div class="max-w-5xl mx-auto bg-white rounded-2xl shadow-xl p-8 mt-12">
            <!-- User Info -->
            <div v-if="user" class="mb-8 p-6 bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl flex flex-col gap-2 border border-blue-200">
                <div class="flex items-center gap-3 mb-2">
                    <div class="bg-blue-600 text-white rounded-full w-12 h-12 flex items-center justify-center text-xl font-bold">
                        {{ user.name.charAt(0) }}
                    </div>
                    <div>
                        <div class="text-lg font-semibold text-blue-900">{{ user.name }}</div>
                        <div class="text-sm text-gray-500">{{ user.email }}</div>
                    </div>
                </div>
                <div class="flex gap-6">
                    <div v-if="user.status" class="text-xs px-2 py-1 rounded bg-blue-200 text-blue-800 font-medium">Status: {{ user.status }}</div>
                    <div v-if="user.id" class="text-xs px-2 py-1 rounded bg-gray-200 text-gray-800 font-medium">ID: {{ user.id }}</div>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Basic Info -->
                <div class="flex flex-col md:flex-row gap-6 mb-6">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                        <input v-model="form.name" type="text" required class="w-full border border-blue-300 rounded-lg px-4 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition" />
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <input v-model="form.email" type="email" required class="w-full border border-blue-300 rounded-lg px-4 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition" />
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input v-model="form.password" type="password" class="w-full border border-blue-300 rounded-lg px-4 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition" placeholder="Leave blank to keep current password" />
                    </div>
                </div>

                <!-- Upload Documents -->
                <div class="space-y-4">
                    <label class="block text-base font-semibold text-blue-700 mb-2">Upload Documents</label>

                    <div class="space-y-4">
                        <div v-for="document in documents" :key="document.id" 
                             class="flex flex-col gap-1 border border-gray-200 rounded-lg p-3">
                            <div class="flex items-center gap-4">
                                <!-- Document Type -->
                                <span class="w-48 font-medium text-gray-700">
                                  <template v-if="document.type === 'Optional'">
                                    <input v-model="document.optionalName" type="text" placeholder="Optional name" class="border border-gray-300 rounded px-2 py-1 w-48" />
                                  </template>
                                  <template v-else>
                                    {{ document.type }}
                                  </template>
                                </span>

                                <!-- File Input -->
                                <input 
                                    :id="`document-${document.id}`"
                                    :name="`document-${document.id}`"
                                    type="file" 
                                    accept=".pdf,.jpg,.jpeg,.png" 
                                    @change="handleFileChange($event, document.id)"
                                    :class="document.file ? 'border-green-500' : ''"
                                    class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                />

                                <!-- Error -->
                                <InputError :message="document.error" class="text-sm w-48" />

                                <!-- Remove Button -->
                                <Button 
                                    v-if="documents.length > 3" 
                                    @click="removeDocumentField(document.id)" 
                                    type="button" 
                                    variant="ghost" 
                                    size="icon" 
                                    class="h-6 w-6"
                                >
                                    <span class="text-lg">&times;</span>
                                </Button>
                            </div>
                            <!-- File Preview -->
                            <div v-if="document.file" 
                                 class="ml-48 text-sm text-green-600 flex items-center gap-1">
                                <span class="font-medium">Selected:</span>
                                <span>{{ document.file.name }}</span>
                                <span class="text-xs text-gray-500">
                                    ({{ (document.file.size / 1024 / 1024).toFixed(2) }} MB)
                                </span>
                            </div>
                        </div>

                        <!-- Add Button -->
                        <Button 
                            v-if="documents.filter(doc => doc.type === 'Optional').length < 2" 
                            @click="addDocumentField" 
                            type="button" 
                            variant="outline" 
                            class="flex items-center gap-2"
                        >
                            + Add Another Document
                        </Button>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end pt-4">
                    <button type="submit" 
                            class="px-6 py-2 rounded-lg bg-blue-600 text-white font-semibold shadow hover:bg-blue-700 transition" 
                            :disabled="form.processing || !hasAtLeastOneDocument()">
                        {{ hasAtLeastOneDocument() ? 'Update Account & Upload Documents' : 'Please upload at least one document' }}
                    </button>
                </div>
            </form>
        </div>
    </AddLayoutAccountUpdate>
</template>

