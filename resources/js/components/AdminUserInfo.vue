<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import { computed } from 'vue';

interface Administrator {
    id: number;
    email: string;
}

interface Props {
    admin: Administrator;
    showEmail?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});

const { getInitials } = useInitials();

// Sample admin avatar URL for demonstration
const sampleAvatarUrl = 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&h=150&fit=crop&crop=face&auto=format';

// Generate a display name from email (part before @)
const displayName = computed(() => {
    return props.admin.email.split('@')[0];
});
</script>

<template>
    <Avatar class="h-8 w-8 overflow-hidden rounded-lg ring-2 ring-primary/10">
        <AvatarImage :src="sampleAvatarUrl" :alt="displayName" class="object-cover" />
        <AvatarFallback class="rounded-lg text-black dark:text-white bg-gradient-to-br from-red-500 to-orange-600 text-white font-semibold">
            {{ getInitials(displayName) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">{{ displayName }}</span>
        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">{{ admin.email }}</span>
    </div>
</template>