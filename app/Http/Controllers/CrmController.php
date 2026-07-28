<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Services\OdooService;

class CrmController extends Controller
{
    /**
     * Display the CRM page or password prompt
     */
    public function index(Request $request)
    {
        // Check if user is authenticated for CRM
        if (!session('crm_authenticated')) {
            return view('crm.index', ['authenticated' => false]);
        }

        // Get unique customers that have rentals
        $customers = \App\Models\Item::whereNotNull('rental_id')
            ->whereNotNull('current_customer')
            ->where('current_customer', '!=', '')
            ->where('current_customer', '!=', '-')
            ->where('is_company', true)
            ->select('current_customer as customer', 'pic_name', 'pic_email')
            ->distinct()
            ->orderBy('current_customer')
            ->get();

        // For each customer, get their rentals
        foreach ($customers as $c) {
            $c->rentals = \App\Models\Item::where('current_customer', $c->customer)
                ->whereNotNull('rental_id')
                ->select('rental_id', 'product', 'reserved_lot', 'rental_period_start', 'rental_period_end', 'lot_number')
                ->orderBy('rental_id')
                ->get();
        }

        return view('crm.index', [
            'authenticated' => true,
            'customers' => $customers
        ]);
    }

    /**
     * Authenticate for CRM page
     */
    public function authenticate(Request $request)
    {
        $password = $request->input('password');
        $storedPassword = Setting::get('crm_password', 'CRM@786');

        if ($password === $storedPassword) {
            session(['crm_authenticated' => true]);
            return redirect()->route('crm.index')->with('success', 'CRM unlocked successfully.');
        }

        return redirect()->back()->with('error', 'Incorrect password.');
    }

    /**
     * Display CRM Settings page
     */
    public function settings()
    {
        $currentPassword = Setting::get('crm_password', 'CRM@786');
        return view('crm.settings', compact('currentPassword'));
    }

    /**
     * Update CRM password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:4'
        ]);

        Setting::set('crm_password', $request->input('password'));

        return redirect()->back()->with('success', 'CRM password updated successfully.');
    }
}
