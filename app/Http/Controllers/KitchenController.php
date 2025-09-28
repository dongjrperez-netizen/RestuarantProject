<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class KitchenController extends Controller
{
    public function index()
    {
        return Inertia::render('Sidebarbutton/Inventory/Kitchens/Kitchen');
    }
}
