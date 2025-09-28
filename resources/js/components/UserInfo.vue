<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';
import { computed } from 'vue';

interface Props {
    user: User | null;
    showEmail?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});

const { getInitials } = useInitials();

// Sample user avatar URL for demonstration
const sampleAvatarUrl = 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&h=150&fit=crop&crop=face&auto=format';

// Compute whether we should show the avatar image (use sample if no avatar)
const showAvatar = computed(() => props.user?.avatar && props.user.avatar !== '' ? props.user.avatar : sampleAvatarUrl);
</script>

<template>
    <Avatar class="h-8 w-8 overflow-hidden rounded-lg ring-2 ring-primary/10">
        <AvatarImage :src="showAvatar" :alt="user?.name || 'User'" class="object-cover" />
        <AvatarFallback class="rounded-lg text-black dark:text-white bg-gradient-to-br from-blue-500 to-purple-600 text-white font-semibold">
            {{ user?.name ? getInitials(user.name) : 'U' }}
        </AvatarFallback>
    </Avatar>

    <div v-if="user" class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">{{ user.name }}</span>
        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">{{ user.email }}</span>
    </div>
    <div v-else class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">Guest</span>
        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">No email</span>
    </div>
</template>
