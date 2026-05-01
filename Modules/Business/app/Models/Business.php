<?php

namespace Modules\Business\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Account\Models\Loan;
use Modules\Settings\Concerns\HasSettings;

class Business extends Model
{
    use HasSettings;

    protected $fillable = [
        'user_id',
        'name',
        'category',
        'description',
        'warehouse_branch_intro_acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'warehouse_branch_intro_acknowledged_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class)->orderBy('name');
    }

    public static function allForNavbar(?User $user): Collection
    {
        if (!$user) {
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
        if (!$user) {
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
