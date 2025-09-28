
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

const emit = defineEmits(['cancel', 'saved']);

const form = useForm({
  product_name: '',
  description: '',
  price:'',
});

function submit() {
  form.post('/product ', {
    onSuccess: () => {
      emit('saved');
      form.reset();
    },
  });
}
</script>
<template>
  <form @submit.prevent="submit">
    <div class="mb-4">
      <label class="block mb-1 text-sm font-medium">Product Name</label>
      <input
        v-model="form.product_name"
        type="text"
        class="w-full border rounded p-2"
        placeholder="Enter Product Name"
      />
    </div>

    <div class="mb-4">
      <label class="block mb-1 text-sm font-medium">Description</label>
      <input
        v-model="form.description"
        type="text"
        class="w-full border rounded p-2"
        placeholder="Enter Description"
      />
    </div>
    <div class="mb-4">
      <label class="block mb-1 text-sm font-medium">Price</label>
      <input
        v-model="form.price"
        type="number"
        class="w-full border rounded p-2"
        placeholder="Enter Price"
      />
    </div>
    <!-- <div class="mb-4">
      <label class="block mb-1 text-sm font-medium">Status</label>
      <input
        v-model="form.contact"
        type="text"
        class="w-full border rounded p-2"
        placeholder="Enter contact info"
      />
    </div> -->

    <div class="flex justify-end gap-2">
      <Button type="button" @click="emit('cancel')">Cancel</Button>
      <Button type="submit">Save</Button>
    </div>
  </form>
</template>

