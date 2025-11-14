<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref, computed } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    unified?: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const resendForm = useForm({
    email: '',
});

const loginError = ref('');
const showResendButton = ref(false);

// Check if error message is about email verification
const isEmailVerificationError = computed(() => {
    return form.errors.email?.includes('verify your email') || loginError.value.includes('verify your email');
});

const submit = () => {
    loginError.value = '';
    showResendButton.value = false;

    form.post(route('login'), {
        onFinish: () => form.reset('password'),
        onError: (errors) => {
            if (errors.status) {
                // Handle custom status errors from backend
                loginError.value = errors.status;
            }
            // Show resend button if verification error
            if (errors.email?.includes('verify your email')) {
                showResendButton.value = true;
            }
        },
        onSuccess: () => {

        }
    });
};

const resendVerification = () => {
    resendForm.email = form.email;
    resendForm.post(route('verification.resend.public'), {
        preserveScroll: true,
        onSuccess: () => {
            showResendButton.value = false;
        }
    });
};
</script>

<template>
    <AuthBase title="Welcome Back">

        <Head title="Sign In - ServeWise" />

        <!-- Status message (for password reset, etc) -->
        <div v-if="status" class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200">
            <div class="text-center text-sm font-medium text-green-700">
                {{ status }}
            </div>
        </div>

        <!-- Login error message -->
        <div v-if="loginError" class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200">
            <div class="text-center text-sm font-medium text-red-700">
                {{ loginError }}
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="space-y-4">
                <div class="space-y-2">
                    <Label for="email" class="text-gray-700 font-medium text-sm sm:text-base">Email Address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="Enter your email address"
                        class="w-full px-3 sm:px-4 py-2 sm:py-3 text-sm sm:text-base !bg-white !text-gray-900 !border-gray-300 dark:!bg-white dark:!text-gray-900 dark:!border-gray-300 placeholder:!text-gray-500"
                    />
                    <InputError :message="form.errors.email" />

                    <!-- Resend verification email button -->
                    <div v-if="showResendButton && form.email" class="mt-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="resendVerification"
                            :disabled="resendForm.processing"
                            class="w-full text-orange-600 border-orange-300 hover:bg-orange-50"
                        >
                            <LoaderCircle v-if="resendForm.processing" class="w-4 h-4 mr-2 animate-spin" />
                            {{ resendForm.processing ? 'Sending...' : 'Resend Verification Email' }}
                        </Button>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <Label for="password" class="text-gray-700 font-medium text-sm sm:text-base">Password</Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-xs sm:text-sm text-orange-500 hover:text-orange-600 font-medium"
                            :tabindex="5"
                        >
                            Forgot password?
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        v-model="form.password"
                        placeholder="Enter your password"
                        class="w-full px-3 sm:px-4 py-2 sm:py-3 text-sm sm:text-base !bg-white !text-gray-900 !border-gray-300 dark:!bg-white dark:!text-gray-900 dark:!border-gray-300 placeholder:!text-gray-500"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between pt-2">
                    <Label for="remember" class="flex items-center space-x-2 sm:space-x-3 cursor-pointer">
                        <Checkbox
                            id="remember"
                            v-model="form.remember"
                            :tabindex="3"
                            class="text-orange-500 focus:ring-orange-500"
                        />
                        <span class="text-xs sm:text-sm text-gray-600">Remember me</span>
                    </Label>
                </div>
            </div>

            <Button
                type="submit"
                class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 sm:py-3 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 text-sm sm:text-base"
                :tabindex="4"
                :disabled="form.processing"
            >
                <LoaderCircle v-if="form.processing" class="h-4 w-4 sm:h-5 sm:w-5 animate-spin mr-2" />
                {{ form.processing ? 'Signing in...' : 'Sign In to ServeWise' }}
            </Button>

            

            <!-- Sign up link -->
            <div class="text-center">
                <TextLink
                    :href="route('register')"
                    :tabindex="5"
                    class="w-full inline-flex justify-center items-center px-4 py-2 sm:py-3 border border-orange-500 text-orange-500 font-semibold rounded-lg hover:bg-orange-50 transition-colors text-sm sm:text-base"
                >
                    Create your account
                </TextLink>
            </div>

            <!-- Additional help -->
            <!-- <div class="text-center text-xs text-gray-500 pt-4">
                Having trouble signing in?
                <a href="#" class="text-orange-500 hover:text-orange-600">Contact support</a>
            </div> -->
        </form>
    </AuthBase>
</template>