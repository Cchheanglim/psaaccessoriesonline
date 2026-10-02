<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class AdminPaymentMethodController extends Controller
{
    public function index()
    {
        $methods = PaymentMethod::all();
        return view('admin.payment-methods', compact('methods'));
    }

    public function toggle($id)
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Only an Admin can change payment methods.');
        $method = PaymentMethod::findOrFail($id);
        $method->update(['is_active' => !$method->is_active]);

        return back()->with('success', "Payment gateway '{$method->name}' toggled.");
    }
}
