<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { LoaderCircle, XCircle, AlertTriangle } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const step = ref(1);
const showErrorModal = ref(false);

const form = useForm({
  last_name: '',
  first_name: '',
  middle_name: '',
  date_of_birth: '',
  gender: '',
  email: '',
  password: '',
  password_confirmation: '',
  restaurant_name:'',
  address: '',
  postal_code:'',
  contact_number: '',
});

const hasErrors = computed(() => {
  return Object.keys(form.errors).length > 0;
});

const errorMessages = computed(() => {
  return Object.values(form.errors);
});

const nextStep = () => {
  if (step.value < 3) step.value++;
};
const prevStep = () => {
  if (step.value > 1) step.value--;
};

const submit = () => {
  form.postal_code = String(form.postal_code);
  form.contact_number = String(form.contact_number);

  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
    onError: (errors) => {
      // Check if it's a timeout error
      const errorString = JSON.stringify(errors).toLowerCase();
      if (errorString.includes('timeout') || errorString.includes('maximum execution time')) {
        showErrorModal.value = true;
      }
    },
  });
};

const proceedToDocuments = () => {
  showErrorModal.value = false;
  router.visit(route('register.documents'));
};

const closeModal = () => {
  showErrorModal.value = false;
};
</script>

<template>
  <AuthBase title="Create Your Account" description="Join ServeWise to manage your restaurant like a pro">
    <Head title="Register - ServeWise" />

    <!-- Error Alert -->
    <Alert v-if="hasErrors" variant="destructive" class="mb-6">
      <XCircle class="h-4 w-4" />
      <AlertTitle>Registration Error</AlertTitle>
      <AlertDescription>
        <ul class="list-disc list-inside space-y-1 mt-2">
          <li v-for="(error, index) in errorMessages" :key="index" class="text-sm">
            {{ error }}
          </li>
        </ul>
      </AlertDescription>
    </Alert>

    <!-- Step Progress Indicator -->
    <div class="mb-6 sm:mb-8">
      <div class="flex items-center justify-between sm:justify-center sm:space-x-4 px-2">
        <div class="flex items-center flex-shrink-0">
          <div :class="['w-7 h-7 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-xs sm:text-sm font-semibold',
                       step >= 1 ? 'bg-orange-500 text-white' : 'bg-gray-200 text-gray-600']">
            1
          </div>
          <span class="ml-1 sm:ml-2 text-xs sm:text-sm font-medium text-gray-700 hidden sm:inline">Personal</span>
        </div>
        <div class="w-6 sm:w-8 h-px bg-gray-300 flex-shrink"></div>
        <div class="flex items-center flex-shrink-0">
          <div :class="['w-7 h-7 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-xs sm:text-sm font-semibold',
                       step >= 2 ? 'bg-orange-500 text-white' : 'bg-gray-200 text-gray-600']">
            2
          </div>
          <span class="ml-1 sm:ml-2 text-xs sm:text-sm font-medium text-gray-700 hidden sm:inline">Restaurant</span>
        </div>
        <div class="w-6 sm:w-8 h-px bg-gray-300 flex-shrink"></div>
        <div class="flex items-center flex-shrink-0">
          <div :class="['w-7 h-7 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-xs sm:text-sm font-semibold',
                       step >= 3 ? 'bg-orange-500 text-white' : 'bg-gray-200 text-gray-600']">
            3
          </div>
          <span class="ml-1 sm:ml-2 text-xs sm:text-sm font-medium text-gray-700 hidden sm:inline">Security</span>
        </div>
      </div>
      <!-- Mobile step labels -->
      <div class="text-center mt-3 sm:hidden">
        <span class="text-xs font-medium text-gray-700">
          {{ step === 1 ? 'Personal Information' : step === 2 ? 'Restaurant Details' : 'Security' }}
        </span>
      </div>
    </div>

    <form @submit.prevent="submit" class="space-y-6">
      <!-- STEP 1: Personal Information -->
      <template v-if="step === 1">
        <div class="space-y-4">
          <div class="text-center mb-4 sm:mb-6">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-1 sm:mb-2">Personal Information</h3>
            <p class="text-xs sm:text-sm text-gray-600">Tell us about yourself</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
            <div class="space-y-2">
              <Label for="first_name" class="text-sm">First Name</Label>
              <Input id="first_name" type="text" required autofocus autocomplete="given-name" v-model="form.first_name" placeholder="John" class="text-sm" />
            </div>
            <div class="space-y-2">
              <Label for="last_name" class="text-sm">Last Name</Label>
              <Input id="last_name" type="text" required autocomplete="family-name" v-model="form.last_name" placeholder="Doe" class="text-sm" />
            </div>
          </div>

          <div class="space-y-2">
            <Label for="middle_name" class="text-sm">Middle Name <span class="text-gray-400 text-xs">(Optional)</span></Label>
            <Input id="middle_name" type="text" autocomplete="additional-name" v-model="form.middle_name" placeholder="Middle name" class="text-sm" />
          </div>

          <div class="space-y-2">
            <Label for="date_of_birth" class="text-sm">Date of Birth</Label>
            <DatePicker
              id="date_of_birth"
              v-model="form.date_of_birth"
              :required="true"
              autocomplete="bday"
              placeholder="Select your birth date"
            />
          </div>

          <div class="space-y-2">
            <Label class="text-sm">Gender</Label>
            <div class="flex flex-wrap gap-4 sm:gap-6 pt-2">
              <label class="flex items-center space-x-2 cursor-pointer">
                <input
                  type="radio"
                  name="gender"
                  value="Male"
                  v-model="form.gender"
                  required
                  class="w-4 h-4 text-orange-500 border-gray-300 focus:ring-orange-500 focus:ring-2"
                />
                <span class="text-xs sm:text-sm text-gray-700">Male</span>
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
                <span class="text-xs sm:text-sm text-gray-700">Female</span>
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
                <span class="text-xs sm:text-sm text-gray-700">Other</span>
              </label>
            </div>
          </div>

          <div class="space-y-2">
            <Label for="email" class="text-sm">Email Address</Label>
            <Input id="email" type="email" required autocomplete="email" v-model="form.email" placeholder="john@example.com" class="text-sm" />
          </div>

        </div>

        <Button type="button" class="w-full bg-orange-500 hover:bg-orange-600 text-white text-sm sm:text-base" @click="nextStep">
          Continue to Restaurant Info
        </Button>
      </template>

      <!-- STEP 2: Restaurant Information -->
      <template v-else-if="step === 2">
        <div class="space-y-4">
          <div class="text-center mb-4 sm:mb-6">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-1 sm:mb-2">Restaurant Details</h3>
            <p class="text-xs sm:text-sm text-gray-600">Tell us about your restaurant</p>
          </div>

          <div class="space-y-2">
            <Label for="restaurant_name" class="text-sm">Restaurant Name</Label>
            <Input id="restaurant_name" type="text" required autocomplete="organization" v-model="form.restaurant_name" placeholder="Bella Vista Restaurant" class="text-sm" />
          </div>

          <div class="space-y-2">
            <Label for="address" class="text-sm">Restaurant Address</Label>
            <Input id="address" type="text" required autocomplete="street-address" v-model="form.address" placeholder="123 Main Street, City" class="text-sm" />
          </div>

          <div class="space-y-2">
            <Label for="postal_code" class="text-sm">Postal Code <span class="text-gray-400 text-xs">(Optional)</span></Label>
            <Input id="postal_code" type="text" autocomplete="postal-code" v-model="form.postal_code" placeholder="12345" class="text-sm" />
          </div>

          <div class="space-y-2">
            <Label for="contact_number" class="text-sm">Restaurant Phone</Label>
            <Input id="contact_number" type="tel" required autocomplete="tel" v-model="form.contact_number" placeholder="+1 (555) 987-6543" class="text-sm" />
          </div>
        </div>

        <div class="flex gap-3">
          <Button type="button" variant="outline" class="flex-1 border-gray-300 text-sm sm:text-base" @click="prevStep">
            Back
          </Button>
          <Button type="button" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white text-sm sm:text-base" @click="nextStep">
            Continue to Security
          </Button>
        </div>
      </template>

      <!-- STEP 3: Password Setup -->
      <template v-else>
        <div class="space-y-4">
          <div class="text-center mb-4 sm:mb-6">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-1 sm:mb-2">Secure Your Account</h3>
            <p class="text-xs sm:text-sm text-gray-600">Choose a strong password</p>
          </div>

          <div class="space-y-2">
            <Label for="password" class="text-sm">Password</Label>
            <Input
              id="password"
              type="password"
              required
              autocomplete="new-password"
              v-model="form.password"
              placeholder="Create a strong password"
              class="text-sm"
            />
            <p class="text-xs text-gray-500 mt-1">Password must be at least 8 characters long</p>
          </div>

          <div class="space-y-2">
            <Label for="password_confirmation" class="text-sm">Confirm Password</Label>
            <Input
              id="password_confirmation"
              type="password"
              required
              autocomplete="new-password"
              v-model="form.password_confirmation"
              placeholder="Confirm your password"
              class="text-sm"
            />
          </div>
        </div>

        <div class="flex gap-3">
          <Button type="button" variant="outline" class="flex-1 border-gray-300 text-sm sm:text-base" @click="prevStep">
            Back
          </Button>
          <Button type="submit" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white text-sm sm:text-base" :disabled="form.processing">
            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
            Create Account
          </Button>
        </div>
      </template>

      <div class="text-center text-xs sm:text-sm text-gray-600 pt-4 border-t border-gray-200">
        Already have an account?
        <TextLink :href="route('login')" class="text-orange-500 hover:text-orange-600 font-medium">Sign in here</TextLink>
      </div>
    </form>

    <!-- Timeout Error Modal -->
    <Dialog :open="showErrorModal" @update:open="closeModal">
      <DialogContent class="sm:max-w-[500px]">
        <DialogHeader>
          <div class="flex items-center gap-3 mb-2">
            <div class="rounded-full bg-yellow-100 p-2">
              <AlertTriangle class="h-6 w-6 text-yellow-600" />
            </div>
            <DialogTitle class="text-xl">Registration Timeout</DialogTitle>
          </div>
          <DialogDescription class="text-base pt-2">
            The server took too long to process your registration. However, your account may have been created successfully.
          </DialogDescription>
        </DialogHeader>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 my-4">
          <p class="text-sm text-blue-800">
            <strong>What happens next:</strong>
          </p>
          <p class="text-sm text-blue-700 mt-2">
            Click "Proceed to Upload Documents" to continue with the document upload process. You can also skip this step and upload documents later from your dashboard.
          </p>
        </div>

        <DialogFooter class="gap-2 sm:gap-0">
          <Button
            variant="outline"
            @click="closeModal"
          >
            Cancel
          </Button>
          <Button
            @click="proceedToDocuments"
            class="bg-orange-500 hover:bg-orange-600"
          >
            Proceed to Upload Documents
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AuthBase>
</template>
