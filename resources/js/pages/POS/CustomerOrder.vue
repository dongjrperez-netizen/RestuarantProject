<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { type BreadcrumbItem } from '@/types';

interface MenuItem {
  id: number;
  name: string;
  image: string;
  description: string;
  disabled: boolean;
}

interface MenuSection {
  category: string;
  items: MenuItem[];
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Menu', href: '/menu' },
];

const defaultImage = "https://images.pexels.com/photos/461382/pexels-photo-461382.jpeg?auto=compress&w=500&q=80";

const menu = ref<MenuSection[]>([
  {
    category: "Appetizer",
    items: [
      { id: 1, name: "Spring Rolls", image: defaultImage, description: "Crispy vegetable rolls served with sweet chili sauce.", disabled: false },
      { id: 2, name: "Garlic Bread", image: defaultImage, description: "Freshly baked garlic bread slices with herbs.", disabled: false },
      { id: 3, name: "Bruschetta", image: defaultImage, description: "Toasted bread topped with tomato, basil, and olive oil.", disabled: false },
      { id: 4, name: "Stuffed Mushrooms", image: defaultImage, description: "Mushrooms stuffed with cheese and herbs, baked to perfection.", disabled: false },
      { id: 5, name: "Caesar Salad", image: defaultImage, description: "Romaine lettuce, parmesan, croutons, and Caesar dressing.", disabled: false },
      { id: 6, name: "Greek Salad", image: defaultImage, description: "Tomatoes, cucumbers, olives, feta cheese, and olive oil.", disabled: false },
      { id: 7, name: "Tomato Soup", image: defaultImage, description: "Classic tomato soup garnished with basil and cream.", disabled: false },
      { id: 8, name: "Chicken Noodle Soup", image: defaultImage, description: "Hearty chicken soup with noodles and vegetables.", disabled: false }
    ]
  },
  {
    category: "Main Course",
    items: [
      { id: 9, name: "Grilled Chicken", image: defaultImage, description: "Juicy grilled chicken served with roasted vegetables.", disabled: false },
      { id: 10, name: "Beef Steak", image: defaultImage, description: "Tender steak grilled to perfection with garlic butter.", disabled: false },
      { id: 11, name: "Salmon Fillet", image: defaultImage, description: "Pan-seared salmon fillet with lemon butter sauce.", disabled: false },
      { id: 12, name: "Shrimp Scampi", image: defaultImage, description: "Shrimp sautéed in garlic butter sauce, served over pasta.", disabled: false },
      { id: 13, name: "Fish and Chips", image: defaultImage, description: "Crispy battered fish fillets with fries and tartar sauce.", disabled: false },
      { id: 14, name: "Margherita Pizza", image: defaultImage, description: "Classic pizza with tomato, mozzarella, and basil.", disabled: false },
      { id: 15, name: "Pepperoni Pizza", image: defaultImage, description: "Pizza topped with pepperoni and mozzarella cheese.", disabled: false },
      { id: 16, name: "Spaghetti Bolognese", image: defaultImage, description: "Spaghetti pasta with rich meat sauce.", disabled: false },
      { id: 17, name: "Fettuccine Alfredo", image: defaultImage, description: "Fettuccine pasta tossed in creamy Alfredo sauce.", disabled: false }
    ]
  },
  {
    category: "Side Course",
    items: [
      { id: 18, name: "French Fries", image: defaultImage, description: "Crispy golden fries served with ketchup.", disabled: false },
      { id: 19, name: "Mashed Potatoes", image: defaultImage, description: "Creamy mashed potatoes with butter and herbs.", disabled: false },
      { id: 20, name: "Grilled Vegetables", image: defaultImage, description: "Assorted seasonal vegetables grilled to perfection.", disabled: false }
    ]
  },
  {
    category: "Desserts",
    items: [
      { id: 21, name: "Chocolate Lava Cake", image: defaultImage, description: "Warm chocolate cake with a gooey molten center.", disabled: false },
      { id: 22, name: "Cheesecake", image: defaultImage, description: "Classic creamy cheesecake with a graham cracker crust.", disabled: false },
      { id: 23, name: "Fruit Tart", image: defaultImage, description: "Crispy tart shell filled with custard and topped with fresh fruits.", disabled: false }
    ]
  },
  {
    category: "Beverages",
    items: [
      { id: 24, name: "Fresh Lemonade", image: defaultImage, description: "Refreshing homemade lemonade with a hint of mint.", disabled: false },
      { id: 25, name: "Iced Coffee", image: defaultImage, description: "Chilled coffee served over ice, perfect for a hot day.", disabled: false },
      { id: 26, name: "Mango Smoothie", image: defaultImage, description: "Creamy smoothie made with ripe mangoes and yogurt.", disabled: false }
    ]
  }
]);

const selectedCategory = ref(menu.value[0].category);

function selectCategory(category: string) {
  selectedCategory.value = category;
}

function toggleDishDisabled(sectionCategory: string, dishId: number) {
  const section = menu.value.find(sec => sec.category === sectionCategory);
  if (!section) return;
  const dish = section.items.find(item => item.id === dishId);
  if (dish) dish.disabled = !dish.disabled;
}

const selectedSection = computed(() => {
  return menu.value.find(section => section.category === selectedCategory.value);
});

// Update Dish Form State
const showUpdateForm = ref(false);
const updateDishCategory = ref('');
const updateDishId = ref<number | null>(null);
const updateDishName = ref('');
const updateDishDescription = ref('');
const updateDishImage = ref('');

function openUpdateForm(category: string, dish: MenuItem) {
  showUpdateForm.value = true;
  updateDishCategory.value = category;
  updateDishId.value = dish.id;
  updateDishName.value = dish.name;
  updateDishDescription.value = dish.description;
  updateDishImage.value = dish.image;
}

function closeUpdateForm() {
  showUpdateForm.value = false;
  updateDishId.value = null;
}

function updateDish() {
  if (!updateDishName.value.trim() || !updateDishDescription.value.trim() || updateDishId.value === null) return;
  const section = menu.value.find(sec => sec.category === updateDishCategory.value);
  if (!section) return;
  const dish = section.items.find(item => item.id === updateDishId.value);
  if (!dish) return;
  dish.name = updateDishName.value;
  dish.description = updateDishDescription.value;
  dish.image = updateDishImage.value;
  closeUpdateForm();
}

// Add Dish Form State
const showAddForm = ref(false);
const newDishCategory = ref(menu.value[0].category);
const newDishName = ref('');
const newDishDescription = ref('');
const newDishImage = ref(defaultImage);

// Ingredients (form only, not saved in menu)
const newDishIngredients = ref<string[]>(['']);
const showIngredientsSection = ref(true); // Controls whether ingredients section is shown

function openAddForm() {
  showAddForm.value = true;
  newDishCategory.value = menu.value[0].category;
  newDishName.value = '';
  newDishDescription.value = '';
  newDishImage.value = defaultImage;
  // For beverages, don't show ingredients by default
  if (newDishCategory.value === 'Beverages') {
    newDishIngredients.value = [];
    showIngredientsSection.value = false;
  } else {
    newDishIngredients.value = [''];
    showIngredientsSection.value = true;
  }
}

function closeAddForm() {
  showAddForm.value = false;
}

function addIngredient() {
  newDishIngredients.value.push('');
}

function removeIngredient(index: number) {
  newDishIngredients.value.splice(index, 1);
}

// Watch for category changes to handle ingredients section visibility
function handleCategoryChange(newCategory: string) {
  if (newCategory === 'Beverages') {
    showIngredientsSection.value = false;
    newDishIngredients.value = [];
  } else {
    showIngredientsSection.value = true;
    if (newDishIngredients.value.length === 0) {
      newDishIngredients.value = [''];
    }
  }
}

function addDish() {
  if (!newDishName.value.trim() || !newDishDescription.value.trim()) return;

  // Check if ingredients are required (not Beverages category)
  if (newDishCategory.value !== 'Beverages' && showIngredientsSection.value) {
    // Check if at least one non-empty ingredient exists
    const hasValidIngredient = newDishIngredients.value.some(ing => ing.trim() !== '');
    if (!hasValidIngredient) {
      alert('Please add at least one ingredient for this category');
      return;
    }
  }

  const section = menu.value.find(sec => sec.category === newDishCategory.value);
  if (!section) return;

  const allItems = menu.value.flatMap(sec => sec.items);
  const nextId = allItems.length ? Math.max(...allItems.map(i => i.id)) + 1 : 1;

  section.items.push({
    id: nextId,
    name: newDishName.value,
    image: newDishImage.value,
    description: newDishDescription.value,
    disabled: false,
  });

  closeAddForm();
}
</script>

<template>
  <Head title="Menu" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-8 p-6 bg-gradient-to-br from-gray-50 via-white to-blue-50 min-h-screen">

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-3xl font-extrabold text-gray-800 dark:text-gray-100">Menu</h2>
      </div>

      <!-- Update Dish Modal -->
      <div v-if="showUpdateForm" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl p-8 w-full max-w-md relative">
          <button @click="closeUpdateForm" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>
          <h3 class="text-2xl font-bold mb-4 text-blue-700">Update Dish</h3>
          <form @submit.prevent="updateDish" class="flex flex-col gap-4">
            <label>Item Name
              <input v-model="updateDishName" type="text" class="w-full mt-1 px-3 py-2 border rounded-lg" required />
            </label>
            <label>Description
              <textarea v-model="updateDishDescription" class="w-full mt-1 px-3 py-2 border rounded-lg" required></textarea>
            </label>
            <label>Image URL
              <input v-model="updateDishImage" type="text" class="w-full mt-1 px-3 py-2 border rounded-lg" />
            </label>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2 rounded-lg mt-2">Update Dish</button>
          </form>
        </div>
      </div>

      <!-- Add Dish Modal -->
      <div v-if="showAddForm" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl p-8 w-full max-w-md relative">
          <button @click="closeAddForm" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>
          <h3 class="text-2xl font-bold mb-4 text-blue-700">Add New Dish</h3>
          <form @submit.prevent="addDish" class="flex flex-col gap-4">
            <label>Category
              <select v-model="newDishCategory" @change="handleCategoryChange(newDishCategory)" class="w-full mt-1 px-3 py-2 border rounded-lg">
                <option v-for="section in menu" :key="section.category" :value="section.category">{{ section.category }}</option>
              </select>
            </label>
            <label>Item Name
              <input v-model="newDishName" type="text" class="w-full mt-1 px-3 py-2 border rounded-lg" required />
            </label>
            
            <!-- Ingredients Section - Conditional for Beverages -->
            <!-- For Beverages: Optional button to add ingredients -->
            <div v-if="newDishCategory === 'Beverages' && !showIngredientsSection">
              <button type="button" @click="showIngredientsSection = true; newDishIngredients = ['']" 
                class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold w-full">
                + Add Ingredients (Optional)
              </button>
            </div>
            
            <!-- Ingredients form - Required for non-beverages, optional for beverages -->
            <div v-if="showIngredientsSection">
              <div class="flex items-center justify-between mb-2">
                <label class="block">
                  Ingredients 
                  <span v-if="newDishCategory !== 'Beverages'" class="text-red-500">*</span>
                  <span v-else class="text-gray-500 text-sm">(Optional)</span>
                </label>
                <button v-if="newDishCategory === 'Beverages'" type="button" 
                  @click="showIngredientsSection = false; newDishIngredients = []"
                  class="text-gray-500 hover:text-red-500 text-sm">
                  Close
                </button>
              </div>
              <div v-for="(ingredient, idx) in newDishIngredients" :key="idx" class="flex items-center gap-2 mb-2">
                <input :placeholder="`Ingredient ${idx + 1}`" v-model="newDishIngredients[idx]" type="text" 
                  class="flex-1 px-3 py-2 border rounded-lg" 
                  :required="newDishCategory !== 'Beverages'" />
                <button v-if="newDishIngredients.length > 1" type="button" @click="removeIngredient(idx)" 
                  class="text-red-500 font-bold px-2 py-1 rounded">×</button>
              </div>
              <button type="button" @click="addIngredient" 
                class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-lg text-xs font-bold">
                + Add Ingredient
              </button>
            </div>
            
            <label>Description
              <textarea v-model="newDishDescription" class="w-full mt-1 px-3 py-2 border rounded-lg" required></textarea>
            </label>
            <label>Image URL (optional)
              <input v-model="newDishImage" type="text" class="w-full mt-1 px-3 py-2 border rounded-lg" />
            </label>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold px-4 py-2 rounded-lg mt-2">Add Dish</button>
          </form>
        </div>
      </div>

      <!-- Category Navigation - Updated Layout -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border px-4 py-3 mb-6">
        <div class="flex items-center justify-between gap-4">
          <!-- Categories Label and Buttons Container -->
          <div class="flex items-center gap-6 flex-1">
            <h3 class="text-lg font-bold whitespace-nowrap">Categories</h3>
            <div class="flex flex-wrap gap-3">
              <button v-for="section in menu" :key="section.category" @click="selectCategory(section.category)"
                :class="['px-5 py-2 rounded-full font-semibold transition shadow-sm border-2',
                         selectedCategory === section.category ? 'bg-blue-600 text-white border-blue-600 scale-105'
                                                              : 'bg-gray-100 border-gray-300 hover:bg-blue-100 hover:border-blue-400']">
                {{ section.category }}
              </button>
            </div>
          </div>
          <!-- Add to Menu Button -->
          <button @click="openAddForm" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2 rounded-lg shadow transition whitespace-nowrap">
            + Add to Menu
          </button>
        </div>
      </div>

      <!-- Menu Items -->
      <div v-if="selectedSection">
        <h2 class="text-3xl font-extrabold text-center mb-8">{{ selectedSection.category }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
          <div v-for="item in selectedSection.items" :key="item.id"
            class="bg-white rounded-2xl shadow-xl hover:shadow-2xl transition overflow-hidden flex flex-col group border">
            <div class="relative">
              <img :src="item.image" :alt="item.name" class="w-full h-44 object-cover group-hover:scale-105 transition-transform duration-300" :class="{ 'opacity-50 grayscale': item.disabled }" />
              <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent opacity-70"></div>
              <span v-if="item.disabled" class="absolute top-2 left-2 bg-red-600 text-white text-xs px-2 py-1 rounded">Unavailable</span>
            </div>
            <div class="p-5 flex flex-col flex-1">
              <h4 class="text-xl font-bold mb-2" :class="{ 'opacity-50 grayscale': item.disabled }">{{ item.name }}</h4>
              <p class="text-sm mb-2" :class="{ 'opacity-50 grayscale': item.disabled }">{{ item.description }}</p>
              <div class="flex gap-2">
                <button @click.stop="toggleDishDisabled(selectedSection.category, item.id)" class="px-4 py-2 rounded-lg text-xs font-bold transition border"
                  :class="item.disabled ? 'bg-green-500 text-white' : 'bg-red-500 text-white'">
                  {{ item.disabled ? 'Enable' : 'Disable' }}
                </button>
                <button @click.stop="openUpdateForm(selectedSection.category, item)" class="px-4 py-2 rounded-lg text-xs font-bold transition border bg-yellow-500 text-white">
                  Update
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>