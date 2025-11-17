<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
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

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
