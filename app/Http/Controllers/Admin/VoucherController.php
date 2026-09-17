<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index()
    {
        $vouchers = Voucher::withCount('usages')->latest()->paginate(15);

        return view('admin.vouchers.index', compact('vouchers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:vouchers,code|max:30',
            'name' => 'required|string|max:255',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:1',
            'minimum_transaction' => 'required|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'required|integer|min:0',
            'usage_per_user' => 'required|integer|min:1',
            'status' => 'required|in:active,inactive',
        ]);

        $voucher = Voucher::create([
            'code' => strtoupper(trim($request->code)),
            'name' => $request->name,
            'type' => $request->type,
            'value' => (float) $request->value,
            'minimum_transaction' => (float) $request->minimum_transaction,
            'maximum_discount' => $request->maximum_discount ? (float) $request->maximum_discount : null,
            'usage_limit' => (int) $request->usage_limit,
            'usage_per_user' => (int) $request->usage_per_user,
            'status' => $request->status,
        ]);

        AuditService::log('create_voucher', Voucher::class, $voucher->id, null, $voucher->toArray(), auth()->user());

        return back()->with('success', 'Kode voucher '.$voucher->code.' berhasil dibuat!');
    }

    public function destroy(string $id)
    {
        $voucher = Voucher::findOrFail($id);
        $oldData = $voucher->toArray();
        $voucher->delete();

        AuditService::log('delete_voucher', Voucher::class, $voucher->id, $oldData, null, auth()->user());

        return back()->with('success', 'Voucher berhasil dihapus.');
    }
}
