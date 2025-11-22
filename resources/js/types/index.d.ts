import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
    userType?: string;
    restaurant?: RestaurantData;
    subscription?: {
        plan_name: string | null;
        plan_id: number | null;
        status: string | null;
        end_date: string | null;
    } | null;
}

export interface RestaurantData {
    id?: number;
    user_id?: number;
    restaurant_name: string;
    address?: string;
    contact_number?: string;
    logo?: string;
    created_at?: string;
    updated_at?: string;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

// export interface NavItem {
//     title: string;
//     href: string;
//     icon?: LucideIcon;
//     isActive?: boolean;
// }
export interface NavItem {
  title: string
  href: string   // for single links
  icon?: LucideIcon
  isActive?: boolean;
  children?: NavItem[] // for collapsible groups
}


export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    first_name: string;
    last_name: string;
    middle_name?: string;
    name: string;
    date_of_birth: string;
    age: number | null;
    gender: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
