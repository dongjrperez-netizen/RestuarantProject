<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/settings/profile',
    },
];

const page = usePage();
const auth = page.props.auth as { user: any; userType?: string };
const user = auth.user;
const userType = auth.userType ?? 'owner';

// Normalize gender value to match the radio options (Male/Female/Other)
const normalizeGender = (gender: string | null | undefined): string => {
    if (!gender) return '';
    const lower = gender.toLowerCase();
    if (lower === 'male') return 'Male';
    if (lower === 'female') return 'Female';
    if (lower === 'other') return 'Other';
    return gender;
};

const isEmployee = userType === 'employee';

const form = useForm({
    // For employees, backend fields are firstname/lastname/middlename
    first_name: isEmployee ? user.firstname : user.first_name,
    last_name: isEmployee ? user.lastname : user.last_name,
    middle_name: (isEmployee ? user.middlename : user.middle_name) || '',
    date_of_birth: user.date_of_birth || '',
    gender: normalizeGender(user.gender),
    email: user.email,
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};

// Restaurant settings (only for owners)
const restaurant = page.props.auth?.restaurant || null;
const restaurantForm = useForm({
    logo: null as File | null,
});

const logoPreview = ref<string | null>(null);
const showLogoSettings = ref(false);

// Handle logo file selection
const handleLogoChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        console.log('File selected:', file.name, file.type, file.size); // Debug log
        restaurantForm.logo = file;

        // Create preview URL
        const reader = new FileReader();
        reader.onload = (e) => {
            logoPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const submitRestaurantSettings = () => {
    console.log('Submitting form with logo:', restaurantForm.logo); // Debug log

    if (!restaurantForm.logo) {
        console.error('No logo file selected');
        restaurantForm.setError('logo' as any, 'Please select a logo to upload.');
        return;
    }

    restaurantForm.clearErrors();

    restaurantForm.post(route('profile.restaurant.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            console.log('Upload successful via Inertia form.');
            logoPreview.value = null;
            restaurantForm.reset('logo');

            // Reload only the auth props so the new logo is available
            router.reload({ only: ['auth'] });
        },
        onError: (errors) => {
            console.error('Upload failed with validation errors:', errors);
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Profile information" description="Update your personal information and email address" />

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Name fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="first_name">First Name</Label>
                            <Input
                                id="first_name"
                                class="mt-1 block w-full"
                                v-model="form.first_name"
                                required
                                autocomplete="given-name"
                                placeholder="First name"
                            />
                            <InputError class="mt-2" :message="form.errors.first_name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="last_name">Last Name</Label>
                            <Input
                                id="last_name"
                                class="mt-1 block w-full"
                                v-model="form.last_name"
                                required
                                autocomplete="family-name"
                                placeholder="Last name"
                            />
                            <InputError class="mt-2" :message="form.errors.last_name" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="middle_name">Middle Name <span class="text-muted-foreground text-sm">(Optional)</span></Label>
                        <Input
                            id="middle_name"
                            class="mt-1 block w-full"
                            v-model="form.middle_name"
                            autocomplete="additional-name"
                            placeholder="Middle name"
                        />
                        <InputError class="mt-2" :message="form.errors.middle_name" />
                    </div>

                    <!-- Date of Birth -->
                    <div class="grid gap-2">
                        <Label for="date_of_birth">Date of Birth</Label>
                        <DatePicker
                            id="date_of_birth"
                            v-model="form.date_of_birth"
                            :required="true"
                            autocomplete="bday"
                            placeholder="Select your birth date"
                        />
                        <InputError class="mt-2" :message="form.errors.date_of_birth" />
                    </div>

                    <!-- Gender -->
                    <div class="grid gap-2">
                        <Label>Gender</Label>
                        <div class="flex flex-wrap gap-6 pt-2">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input
                                    type="radio"
                                    name="gender"
                                    value="Male"
                                    v-model="form.gender"
                                    required
                                    class="w-4 h-4 text-orange-500 border-gray-300 focus:ring-orange-500 focus:ring-2"
                                />
                                <span class="text-sm">Male</span>
                            </label>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input
                                    type="radio"
                                    name="gender"
                                    value="Female"
                                    v-model="form.gender"
                                    required
                                    class="w-4 h-4 text-orange-500 border-gray-300 focus:ring-orange-500 focus:ring-2"
                                />
                                <span class="text-sm">Female</span>
                            </label>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input
                                    type="radio"
                                    name="gender"
                                    value="Other"
                                    v-model="form.gender"
                                    required
                                    class="w-4 h-4 text-orange-500 border-gray-300 focus:ring-orange-500 focus:ring-2"
                                />
                                <span class="text-sm">Other</span>
                            </label>
                        </div>
                        <InputError class="mt-2" :message="form.errors.gender" />
                    </div>

                    <!-- Email -->
                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            placeholder="Email address"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Save</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
                        </Transition>
                    </div>
                </form>
            </div>

            <!-- Restaurant Settings (Only for owners) -->
            <div v-if="!isEmployee && restaurant" class="flex flex-col space-y-6">
                <HeadingSmall
                    title="Restaurant Settings"
                    description="Customize your restaurant information and branding"
                />

                <!-- Toggle button to expand/collapse logo upload section -->
                <div>
                    <Button
                        type="button"
                        variant="outline"
                        class="flex items-center gap-2"
                        @click="showLogoSettings = !showLogoSettings"
                    >
                        <Plus class="h-4 w-4" />
                        <span>
                            {{ restaurant.logo ? 'Update restaurant logo' : 'Add restaurant logo' }}
                        </span>
                    </Button>
                </div>

                <form
                    v-if="showLogoSettings"
                    @submit.prevent="submitRestaurantSettings"
                    class="space-y-6"
                >
                    <!-- Restaurant Logo -->
                    <div class="grid gap-2 mt-4">
                        <Label for="logo">Restaurant Logo</Label>
                        <div class="flex items-start gap-4">
                            <!-- Current/Preview Logo -->
                            <div class="flex-shrink-0">
                                <div class="h-20 w-20 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden bg-gray-50">
                                    <img
                                        v-if="logoPreview"
                                        :src="logoPreview"
                                        alt="Logo preview"
                                        class="h-full w-full object-cover"
                                    />
                                    <img
                                        v-else-if="restaurant.logo"
                                        :src="restaurant.logo.includes('http') ? restaurant.logo : `/storage/${restaurant.logo}`"
                                        alt="Restaurant logo"
                                        class="h-full w-full object-cover"
                                    />
                                    <span v-else class="text-gray-400 text-xs">No logo</span>
                                </div>
                            </div>

                            <!-- Upload Button -->
                            <div class="flex-1">
                                <Input
                                    id="logo"
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp"
                                    @change="handleLogoChange"
                                    class="mt-1 block w-full"
                                />
                                <p class="text-sm text-muted-foreground mt-2">
                                    Upload a square image (recommended: 512x512px). Accepted formats: JPG, PNG, GIF, WebP
                                </p>
                                <InputError class="mt-2" :message="restaurantForm.errors.logo" />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="restaurantForm.processing">Update Restaurant Settings</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="restaurantForm.recentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
