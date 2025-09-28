<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/components/Modal.vue';
import AddProduct from '../products/Actions/AddProduct.vue';
import { Button } from '@/components/ui/button';

import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Product',
        href: '/product',
    },
];

const showModal = ref(false);

// Define props to receive products from backend
defineProps<{
    products: Array<{
        id: number;
        product_name: string;
        description: string;
        price: number;
        status: string;
    }>
}>();

</script>

<template>
    <Head title="Product" />

    <AppLayout :breadcrumbs="breadcrumbs">
         <div class="p-3">
            <Button @click="showModal = true">Add Product</Button>
        </div>

        <!-- Modal with separate form -->
        <Modal :show="showModal" @close="showModal = false">
          <h2 class="text-lg font-bold mb-4">Add Product</h2>
          <AddProduct @cancel="showModal = false" @saved="showModal = false" />
        </Modal>

        <div class="p-5">
            <Table>
                <TableCaption>A list of your products.</TableCaption>
                <TableHeader>
                    <TableRow>
                        <TableHead>Product Name</TableHead>
                        <TableHead>Description</TableHead>
                        <TableHead>Price</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="product in products" :key="product.id">
                        <TableCell>{{ product.product_name }}</TableCell>
                        <TableCell>{{ product.description }}</TableCell>
                        <TableCell>{{ product.price }}</TableCell>
                        <TableCell>{{ product.status }}</TableCell>
                        <TableCell class="text-right">
                            <Button variant="destructive" class="mr-1">Disable</Button>
                            <Button variant="secondary" class="mr-1">Edit</Button>
                            <Button>View</Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </AppLayout>
</template>