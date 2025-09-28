<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post(route('admin.login.submit'));
};
</script>

<template>
  <div class="flex justify-center items-center h-screen">
    <form @submit.prevent="submit" class="w-96 p-6 bg-white shadow rounded">
      <h1 class="text-xl font-bold mb-4">Administrator Login</h1>

      <div class="mb-3">
        <label>Email</label>
        <input v-model="form.email" type="email" class="border rounded w-full p-2" />
        <div v-if="form.errors.email" class="text-red-500 text-sm">{{ form.errors.email }}</div>
      </div>

      <div class="mb-3">
        <label>Password</label>
        <input v-model="form.password" type="password" class="border rounded w-full p-2" />
        <div v-if="form.errors.password" class="text-red-500 text-sm">{{ form.errors.password }}</div>
      </div>

      <div class="mb-3">
        <label>
          <input type="checkbox" v-model="form.remember" />
          Remember me
        </label>
      </div>

      <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded">
        Login
      </button>
    </form>
  </div>
</template>
