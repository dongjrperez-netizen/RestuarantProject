<?php

namespace App\Console\Commands;

use App\Models\Dish;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupBrokenImages extends Command
{
    protected $signature = 'images:cleanup';

    protected $description = 'Clean up broken image references in the dishes table';

    public function handle()
    {
        $this->info('Starting cleanup of broken image references...');

        $dishes = Dish::whereNotNull('image_url')->get();
        $cleaned = 0;

        foreach ($dishes as $dish) {
            if (!$dish->image_url) continue;

            // Remove /storage/ prefix to get the actual path
            $imagePath = str_replace('/storage/', '', $dish->image_url);

            // Check if file exists in storage
            if (!Storage::disk('public')->exists($imagePath)) {
                $this->warn("Broken image for dish ID {$dish->dish_id}: {$dish->image_url}");
                $dish->update(['image_url' => null]);
                $cleaned++;
                $this->info("Cleared broken image URL for dish ID {$dish->dish_id}");
            }
        }

        $this->info("Cleanup complete. Cleaned {$cleaned} broken image references.");

        return 0;
    }
}