// ServeWise Design System Utilities
// Common helper functions and type definitions

export interface StatusColorMap {
  [key: string]: string;
}

export interface PerformanceThresholds {
  excellent: number;
  good: number;
  average: number;
}

// Status color mappings for consistent badge styling
export const statusColors: StatusColorMap = {
  // Order Status
  pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300',
  approved: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300',
  delivered: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300',
  cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300',
  
  // Supplier Status
  active: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300',
  inactive: 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-300',
  suspended: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300',
  
  // General Status
  completed: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300',
  in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300',
  failed: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300',
  
  // Inventory Status
  in_stock: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300',
  low_stock: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300',
  out_of_stock: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300',
  
  // Payment Status
  paid: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300',
  partial: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300',
  overdue: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300',
  
  // Priority levels
  high: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300',
  medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300',
  low: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300'
};

// Icon color mappings for statistics cards
export const iconColors: StatusColorMap = {
  revenue: 'bg-blue-500',
  orders: 'bg-green-500',
  users: 'bg-purple-500',
  ratings: 'bg-yellow-500',
  inventory: 'bg-orange-500',
  suppliers: 'bg-indigo-500',
  reports: 'bg-pink-500',
  analytics: 'bg-teal-500'
};

// Performance thresholds
export const performanceThresholds: PerformanceThresholds = {
  excellent: 90,
  good: 80,
  average: 70
};

/**
 * Get consistent status color classes for badges and indicators
 */
export const getStatusColor = (status: string): string => {
  return statusColors[status.toLowerCase()] || statusColors.inactive;
};

/**
 * Get icon color class for statistics cards
 */
export const getIconColor = (type: string): string => {
  return iconColors[type.toLowerCase()] || iconColors.analytics;
};

/**
 * Get performance-based color classes
 */
export const getPerformanceColor = (score: number): string => {
  if (score >= performanceThresholds.excellent) {
    return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300';
  }
  if (score >= performanceThresholds.good) {
    return 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300';
  }
  if (score >= performanceThresholds.average) {
    return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300';
  }
  return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300';
};

/**
 * Get rating color based on numeric value
 */
export const getRatingColor = (rating: number): string => {
  if (rating >= 4.5) return 'text-green-600 dark:text-green-400';
  if (rating >= 4.0) return 'text-blue-600 dark:text-blue-400';
  if (rating >= 3.5) return 'text-yellow-600 dark:text-yellow-400';
  return 'text-red-600 dark:text-red-400';
};

/**
 * Format currency values consistently (Philippine Peso)
 */
export const formatCurrency = (amount: number, locale: string = 'en-PH'): string => {
  return `₱${amount.toLocaleString(locale, { minimumFractionDigits: 2 })}`;
};

/**
 * Format large numbers with appropriate suffixes (K, M, B)
 */
export const formatLargeNumber = (num: number): string => {
  if (num >= 1000000000) {
    return (num / 1000000000).toFixed(1) + 'B';
  }
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M';
  }
  if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K';
  }
  return num.toString();
};

/**
 * Format percentage values
 */
export const formatPercentage = (value: number, decimals: number = 1): string => {
  return `${value.toFixed(decimals)}%`;
};

/**
 * Get star rating breakdown for display
 */
export const getRatingStars = (rating: number): { fullStars: number; hasHalfStar: boolean } => {
  const fullStars = Math.floor(rating);
  const hasHalfStar = rating % 1 >= 0.5;
  return { fullStars, hasHalfStar };
};

/**
 * Generate initials from a name for avatar display
 */
export const getInitials = (name: string): string => {
  return name
    .split(' ')
    .map(word => word.charAt(0).toUpperCase())
    .join('')
    .slice(0, 2);
};

/**
 * Truncate text to specified length with ellipsis
 */
export const truncateText = (text: string, maxLength: number = 50): string => {
  if (text.length <= maxLength) return text;
  return text.slice(0, maxLength) + '...';
};

/**
 * Format date strings consistently
 */
export const formatDate = (date: string | Date, format: 'short' | 'long' | 'time' = 'short'): string => {
  const dateObj = typeof date === 'string' ? new Date(date) : date;
  
  switch (format) {
    case 'long':
      return dateObj.toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    case 'time':
      return dateObj.toLocaleTimeString('en-PH', {
        hour: '2-digit',
        minute: '2-digit'
      });
    default:
      return dateObj.toLocaleDateString('en-PH');
  }
};

/**
 * Calculate time ago from a date
 */
export const timeAgo = (date: string | Date): string => {
  const now = new Date();
  const then = typeof date === 'string' ? new Date(date) : date;
  const diffInMs = now.getTime() - then.getTime();
  
  const minute = 60 * 1000;
  const hour = minute * 60;
  const day = hour * 24;
  const week = day * 7;
  const month = day * 30;
  const year = day * 365;
  
  if (diffInMs < minute) return 'Just now';
  if (diffInMs < hour) return Math.floor(diffInMs / minute) + ' minutes ago';
  if (diffInMs < day) return Math.floor(diffInMs / hour) + ' hours ago';
  if (diffInMs < week) return Math.floor(diffInMs / day) + ' days ago';
  if (diffInMs < month) return Math.floor(diffInMs / week) + ' weeks ago';
  if (diffInMs < year) return Math.floor(diffInMs / month) + ' months ago';
  return Math.floor(diffInMs / year) + ' years ago';
};

/**
 * Generate consistent avatar colors based on name
 */
export const getAvatarColor = (name: string): string => {
  const colors = [
    'bg-blue-500',
    'bg-green-500',
    'bg-purple-500',
    'bg-yellow-500',
    'bg-orange-500',
    'bg-red-500',
    'bg-indigo-500',
    'bg-pink-500'
  ];
  
  const hash = name.split('').reduce((a, b) => a + b.charCodeAt(0), 0);
  return colors[hash % colors.length];
};

/**
 * Validate form fields and return error messages
 */
export const validateField = (value: any, rules: string[]): string | null => {
  for (const rule of rules) {
    switch (rule) {
      case 'required':
        if (!value || (typeof value === 'string' && value.trim() === '')) {
          return 'This field is required';
        }
        break;
      case 'email':
        if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
          return 'Please enter a valid email address';
        }
        break;
      case 'phone':
        if (value && !/^(\+63|0)[0-9]{10}$/.test(value.replace(/\s/g, ''))) {
          return 'Please enter a valid Philippine phone number';
        }
        break;
      case 'positive':
        if (value && (isNaN(value) || parseFloat(value) <= 0)) {
          return 'Please enter a positive number';
        }
        break;
    }
  }
  return null;
};

/**
 * Deep clone an object (for form state management)
 */
export const deepClone = <T>(obj: T): T => {
  if (obj === null || typeof obj !== 'object') return obj;
  if (obj instanceof Date) return new Date(obj.getTime()) as any;
  if (obj instanceof Array) return obj.map(item => deepClone(item)) as any;
  if (typeof obj === 'object') {
    const clonedObj = {} as any;
    for (const key in obj) {
      if (obj.hasOwnProperty(key)) {
        clonedObj[key] = deepClone(obj[key]);
      }
    }
    return clonedObj;
  }
  return obj;
};

/**
 * Debounce function for search inputs
 */
export const debounce = <T extends (...args: any[]) => any>(
  func: T,
  wait: number
): (...args: Parameters<T>) => void => {
  let timeout: NodeJS.Timeout;
  return (...args: Parameters<T>) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => func(...args), wait);
  };
};

/**
 * Check if user has required permissions
 */
export const hasPermission = (userRole: string, requiredRoles: string[]): boolean => {
  return requiredRoles.includes(userRole);
};

/**
 * Generate consistent sort functions for tables
 */
export const sortBy = <T>(key: keyof T, direction: 'asc' | 'desc' = 'asc') => {
  return (a: T, b: T): number => {
    const aVal = a[key];
    const bVal = b[key];
    
    if (aVal < bVal) return direction === 'asc' ? -1 : 1;
    if (aVal > bVal) return direction === 'asc' ? 1 : -1;
    return 0;
  };
};

/**
 * Generate search filter function
 */
export const createSearchFilter = <T>(searchTerm: string, searchFields: (keyof T)[]) => {
  const term = searchTerm.toLowerCase();
  return (item: T): boolean => {
    return searchFields.some(field => {
      const value = item[field];
      return typeof value === 'string' && value.toLowerCase().includes(term);
    });
  };
};
