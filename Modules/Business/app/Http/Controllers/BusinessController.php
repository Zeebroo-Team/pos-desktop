<?php

namespace Modules\Business\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Business\Models\Business;
use Modules\Business\Services\BranchService;
use Modules\Business\Services\BusinessService;

class BusinessController extends Controller
{
    public function __construct(
        private readonly BusinessService $businessService,
        private readonly BranchService $branchService,
    ) {
    }

    public function acknowledgeWarehouseIntro(Request $request): RedirectResponse
    {
        $business = Business::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->first();

        if (!$business instanceof Business) {
            return redirect()->route('dashboard')->withErrors(['business' => 'No business profile found.']);
        }

        if (Business::query()->whereKey($business->id)->whereNotNull('warehouse_branch_intro_acknowledged_at')->exists()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'multi_warehouse_branch' => ['required', Rule::in(['0', '1'])],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'address' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $enabled = $validated['multi_warehouse_branch'] === '1';

        $branchData = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];

        $updatedRows = 0;

        DB::transaction(function () use ($business, $enabled, &$updatedRows, $branchData): void {
            $business->setSetting('business.multi_warehouse_branch', $enabled);
            $updatedRows = Business::query()
                ->whereKey($business->id)
                ->whereNull('warehouse_branch_intro_acknowledged_at')
                ->update([
                    'warehouse_branch_intro_acknowledged_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->branchService->create($business, $branchData);
        });

        /** @var int|string $bizId */
        $bizId = $business->getKey();
        if ($updatedRows > 0) {
            session()->put('warehouse_intro_ack.'.$bizId, true);
        }

        $status = $enabled
            ? 'Multi-location mode on—we saved your choice and primary location.'
            : 'Single location saved—we captured your premises details and hid branch management shortcuts.';

        return redirect()->route('dashboard')->with('status', $status);
    }

    public function storeOnboarding(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->businessService->upsertForUser($request->user(), $data);

        return redirect()->route('dashboard')->with('status', 'Business profile saved.');
    }
}
