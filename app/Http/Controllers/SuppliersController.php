<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class SuppliersController extends Controller
{
    public function index()
    {
        return Inertia::render('Sidebarbutton/MySuppliers/Supplier');
    }

    public function reorder()
    {
        return Inertia::render('Sidebarbutton/MySuppliers/Reorder');
    }

    public function orderedRequested()
    {
        return Inertia::render('Suppliers/OrderRequested');
    }
}
