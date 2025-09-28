<?php

namespace App\Http\Controllers;

use App\Models\MenuPlan;
use App\Models\MenuPlanDish;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class CustomerMenuController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date');
        $planId = $request->get('plan');

        if (!$date || !$planId) {
            return redirect()->back()->with('error', 'Invalid menu request');
        }

        // Get the menu plan with category information
        $menuPlan = MenuPlan::with(['menuPlanDishes.dish.category'])
            ->findOrFail($planId);

        // Filter dishes for the specific date
        $dishesForDate = $menuPlan->menuPlanDishes->filter(function ($menuPlanDish) use ($date) {
            // Handle different date formats
            $plannedDate = $menuPlanDish->planned_date;

            // If it's an ISO string, extract just the date part
            if (strpos($plannedDate, 'T') !== false) {
                $plannedDate = explode('T', $plannedDate)[0];
            }
            // If it's a datetime string with space, extract date part
            elseif (strpos($plannedDate, ' ') !== false) {
                $plannedDate = explode(' ', $plannedDate)[0];
            }

            return $plannedDate === $date;
        });

        return Inertia::render('CustomerMenu', [
            'dishes' => $dishesForDate->values()->toArray(),
            'planDate' => $date,
            'planName' => $menuPlan->plan_name,
        ]);
    }
}