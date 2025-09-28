<script setup lang="ts">
import AppLayoutAdministrator from '@/layouts/AppLayoutAdministrator.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import Badge from '@/components/ui/badge/Badge.vue';




import { Users, Building, CheckCircle, XCircle, CreditCard, TrendingUp, Calendar, Activity } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: '/admin/dashboard',
    },
];

interface Analytics {
    totalRestaurants: number;
    pendingApprovals: number;
    approvedRestaurants: number;
    rejectedApplications: number;
    activeSubscriptions: number;
    expiredSubscriptions: number;
    totalRevenue: number;
    monthlyStats: {
        newRegistrations: number;
        newSubscriptions: number;
        approvalsThisMonth: number;
    };
}

interface Activity {
    type: string;
    message: string;
    timestamp: string;
    status: string;
}

const props = defineProps<{
    analytics: Analytics;
    recentActivities: Activity[];
}>();

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getActivityBadgeVariant = (status: string) => {
    switch (status) {
        case 'Approved':
        case 'active':
            return 'default';
        case 'Pending':
            return 'secondary';
        case 'Rejected':
        case 'archive':
            return 'destructive';
        default:
            return 'outline';
    }
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AppLayoutAdministrator :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6 overflow-x-auto">
            <!-- Analytics Cards Grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <!-- Total Restaurants -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Restaurants</CardTitle>
                        <Building class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ analytics.totalRestaurants }}</div>
                        <p class="text-xs text-muted-foreground">
                            +{{ analytics.monthlyStats.newRegistrations }} this month
                        </p>
                    </CardContent>
                </Card>

                <!-- Pending Approvals -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Pending Approvals</CardTitle>
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ analytics.pendingApprovals }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ analytics.monthlyStats.approvalsThisMonth }} approved this month
                        </p>
                    </CardContent>
                </Card>

                <!-- Active Subscriptions -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Active Subscriptions</CardTitle>
                        <CreditCard class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ analytics.activeSubscriptions }}</div>
                        <p class="text-xs text-muted-foreground">
                            +{{ analytics.monthlyStats.newSubscriptions }} this month
                        </p>
                    </CardContent>
                </Card>

                <!-- Total Revenue -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Est. Monthly Revenue</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(analytics.totalRevenue) }}</div>
                        <p class="text-xs text-muted-foreground">
                            Based on active subscriptions
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Status Overview -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Approved</CardTitle>
                        <CheckCircle class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ analytics.approvedRestaurants }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Rejected</CardTitle>
                        <XCircle class="h-4 w-4 text-red-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">{{ analytics.rejectedApplications }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Expired Subscriptions</CardTitle>
                        <Activity class="h-4 w-4 text-orange-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-orange-600">{{ analytics.expiredSubscriptions }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Activities -->
            <Card class="flex-1">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Activity class="h-5 w-5" />
                        Recent Activities
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div 
                            v-for="activity in recentActivities" 
                            :key="activity.message + activity.timestamp"
                            class="flex items-center justify-between p-3 bg-muted/50 rounded-lg"
                        >
                            <div class="flex-1">
                                <p class="text-sm font-medium">{{ activity.message }}</p>
                                <p class="text-xs text-muted-foreground">{{ formatDate(activity.timestamp) }}</p>
                            </div>
                            <Badge :variant="getActivityBadgeVariant(activity.status)">
                                {{ activity.status }}
                            </Badge>
                        </div>
                        <div v-if="recentActivities.length === 0" class="text-center py-8 text-muted-foreground">
                            No recent activities
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayoutAdministrator>
</template>