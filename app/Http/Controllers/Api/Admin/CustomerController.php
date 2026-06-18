<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group لوحة التحكم (Admin)
 *
 * @header Content-Type application/json
 * @header Accept application/json
 * @authenticated
 */
class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'));

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $customers = $query->withCount('orders')
            ->with(['orders' => fn ($q) => $q->latest()->limit(1)])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $customers->items(),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'total' => $customers->total(),
                'per_page' => $customers->perPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $customer = User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'))
            ->withCount('orders')
            ->with(['orders' => fn ($q) => $q->latest()->limit(10)])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $customer,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $customer = User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'))->findOrFail($id);
        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully.',
        ]);
    }
}
