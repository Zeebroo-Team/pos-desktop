<?php

namespace Modules\Business\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Account\Models\Bill;
use Modules\Account\Models\Loan;
use Modules\Account\Models\Rental;
use Modules\HRManagement\Models\AllowanceType;
use Modules\HRManagement\Models\Department;
use Modules\HRManagement\Models\Employee;
use Modules\HRManagement\Models\HrBusinessHoliday;
use Modules\HRManagement\Models\HrComplaint;
use Modules\HRManagement\Models\JobTitle;
use Modules\HRManagement\Models\LeaveRequest;
use Modules\Settings\Concerns\HasSettings;

class Business extends Model
{
    use HasSettings;

    protected $fillable = [
        'user_id',
        'name',
        'category',
        'company_category_slug',
        'description',
        'short_description',
        'brand_features',
        'google_location_resource',
        'google_location_title_cache',
        'logo_path',
        'warehouse_branch_intro_acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'warehouse_branch_intro_acknowledged_at' => 'datetime',
            'google_location_linked_at' => 'datetime',
            'brand_features' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Public URL for the stored logo, or null if none. */
    public function logoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return asset('storage/'.$this->logo_path);
    }

    public static function defaultLogoPlaceholderUrl(): string
    {
        return asset('images/business-logo-placeholder.svg');
    }

    /** True when the business has uploaded a custom logo file. */
    public function hasCustomLogo(): bool
    {
        return filled($this->logo_path);
    }

    /** Logo URL for UI: uploaded file or shared placeholder graphic. */
    public function displayLogoUrl(): string
    {
        return $this->logoUrl() ?? self::defaultLogoPlaceholderUrl();
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class)->orderBy('name');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class)->orderBy('full_name')->orderBy('id');
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class)->orderBy('name')->orderBy('id');
    }

    public function jobTitles(): HasMany
    {
        return $this->hasMany(JobTitle::class)->orderBy('name')->orderBy('id');
    }

    public function allowanceTypes(): HasMany
    {
        return $this->hasMany(AllowanceType::class)->orderBy('sort_order')->orderBy('name')->orderBy('id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class)->orderByDesc('created_at');
    }

    /** Grievances / HR issues logged against this business (internal inbox). */
    public function hrComplaints(): HasMany
    {
        return $this->hasMany(HrComplaint::class)->orderByDesc('created_at');
    }

    /** Company-wide public holidays for calendars and payroll (reference). */
    public function hrHolidays(): HasMany
    {
        return $this->hasMany(HrBusinessHoliday::class)->orderBy('holiday_date')->orderBy('id');
    }

    public static function allForNavbar(?User $user): Collection
    {
        if (! $user) {
            return new Collection([]);
        }

        return static::query()->where('user_id', $user->id)->latest()->get();
    }

    /** Multi warehouse / branch mode enabled in business settings (default off). */
    public function multiWarehouseBranchEnabled(): bool
    {
        return (bool) get_settings('business.multi_warehouse_branch', false, $this);
    }

    /**
     * Business currently selected in the navbar (session), or latest as fallback.
     */
    public static function currentForNavbar(?User $user): ?static
    {
        if (! $user) {
            return null;
        }

        $selectedId = (int) session('selected_business_id');

        if ($selectedId !== 0) {
            $match = static::query()->where('user_id', $user->id)->where('id', $selectedId)->first();
            if ($match) {
                return $match;
            }
            session()->forget('selected_business_id');
        }

        $latest = static::query()->where('user_id', $user->id)->latest()->first();
        if ($latest) {
            session(['selected_business_id' => $latest->id]);

            return $latest;
        }

        session()->forget('selected_business_id');

        return null;
    }
}
