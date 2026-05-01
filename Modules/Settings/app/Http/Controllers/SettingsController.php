<?php

namespace Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Modules\Settings\Services\SettingsService;

class SettingsController extends Controller
{
    public function index(Request $request, SettingsService $settingsService)
    {
        return $this->user($request, $settingsService);
    }

    public function user(Request $request, SettingsService $settingsService)
    {
        $user = $request->user();

        return $this->renderSettingsPage(
            scopeType: 'user',
            scopeModel: $user,
            title: 'User Settings',
            heading: 'User Settings',
            settingsService: $settingsService
        );
    }

    public function business(Request $request, SettingsService $settingsService)
    {
        $business = $request->user()?->businesses()->latest()->first();

        return $this->renderSettingsPage(
            scopeType: 'business',
            scopeModel: $business,
            title: 'Business Settings',
            heading: 'Business Settings',
            settingsService: $settingsService
        );
    }

    public function store(Request $request, SettingsService $settingsService)
    {
        $validated = $request->validate([
            'scope' => ['required', 'in:user,business'],
            'key' => ['required', 'string', 'max:100'],
            'value' => ['nullable'],
        ]);

        $definition = $this->findDefinition($validated['scope'], $validated['key']);
        if (!$definition || !$definition['is_enabled'] || $definition['is_disabled']) {
            return redirect()->back()->withErrors(['key' => 'Invalid or disabled setting field.']);
        }

        $user = $request->user();
        $scope = $validated['scope'] === 'business'
            ? $user?->businesses()->latest()->first()
            : $user;

        if (!$scope) {
            return redirect()->back()->withErrors(['scope' => 'Business not found for this user.']);
        }

        $value = $this->normalizeInputValue($request, $definition);

        if ($definition['required'] && ($value === null || $value === '')) {
            return redirect()->back()->withErrors(['value' => 'This setting field is required.']);
        }

        $settingsService->set($scope, $validated['key'], $value);

        $routeName = $validated['scope'] === 'business' ? 'settings.business' : 'settings.user';

        return redirect()->route($routeName)->with('status', 'Setting saved successfully.');
    }

    public function destroy(Request $request, SettingsService $settingsService)
    {
        $validated = $request->validate([
            'scope' => ['required', 'in:user,business'],
            'key' => ['required', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $scope = $validated['scope'] === 'business'
            ? $user?->businesses()->latest()->first()
            : $user;

        if (!$scope) {
            return redirect()->back()->withErrors(['scope' => 'Business not found for this user.']);
        }

        $settingsService->forget($scope, $validated['key']);

        $routeName = $validated['scope'] === 'business' ? 'settings.business' : 'settings.user';

        return redirect()->route($routeName)->with('status', 'Setting deleted successfully.');
    }

    public function bulkStore(Request $request, SettingsService $settingsService)
    {
        $validatedScope = $request->validate([
            'scope' => ['required', 'in:user,business'],
        ]);

        $definitionsAll = $this->getDefinitionsByScope($validatedScope['scope'])
            ->filter(fn (array $definition) => $definition['is_enabled'] && !$definition['is_disabled']);

        $allowedTabs = $definitionsAll->map(fn (array $d) => $this->resolveDefinitionTab($d))->unique()->values()->all();
        if (count($allowedTabs) === 0) {
            return redirect()->back()->withErrors(['settings' => 'No settings sections are available for this scope.']);
        }

        $validatedRest = $request->validate([
            'values' => ['nullable', 'array'],
            'files' => ['nullable', 'array'],
            'tab' => ['required', 'string', Rule::in($allowedTabs)],
        ]);

        $validated = array_merge($validatedScope, $validatedRest);

        $user = $request->user();
        $scope = $validated['scope'] === 'business'
            ? $user?->businesses()->latest()->first()
            : $user;

        if (!$scope) {
            return redirect()->back()->withErrors(['scope' => 'Business not found for this user.']);
        }

        $definitions = $definitionsAll->filter(
            fn (array $d) => $this->resolveDefinitionTab($d) === $validated['tab']
        )->values();

        $existingSettings = $settingsService->allForScope($scope);
        $uploadedFiles = $request->file('files', []);

        $inputValues = (array) ($validated['values'] ?? []);
        $inputFiles = (array) ($validated['files'] ?? []);
        $toSave = [];

        foreach ($definitions as $definition) {
            $key = $definition['key'];
            $existingValue = $existingSettings->get($key);

            if ($definition['type'] === 'file') {
                $uploadedFile = is_array($uploadedFiles) && array_key_exists($key, $uploadedFiles)
                    ? $uploadedFiles[$key]
                    : null;
                if ($uploadedFile) {
                    $value = $uploadedFile->store(
                        "settings/{$validated['scope']}/{$scope->getKey()}",
                        'public'
                    );
                } else {
                    $value = $existingValue;
                }
            } else {
                if (array_key_exists($key, $inputValues)) {
                    $rawValue = $inputValues[$key];
                } elseif ($definition['type'] === 'checkbox') {
                    $rawValue = 0;
                } else {
                    $rawValue = $existingValue;
                }
                $value = $this->normalizeRawValue($rawValue, $definition);
            }

            if ($definition['required'] && ($value === null || $value === '')) {
                return redirect()->back()->withErrors(['value' => "The {$definition['name']} field is required."]);
            }

            $toSave[$key] = $value;
        }

        $settingsService->setMany($scope, $toSave);

        $routeName = $validated['scope'] === 'business' ? 'settings.business' : 'settings.user';
        $callbackUrl = route($routeName).'?' . http_build_query(['tab' => $validated['tab']]);

        return redirect()->to($callbackUrl)->with('status', 'Settings saved successfully.');
    }

    private function renderSettingsPage(
        string $scopeType,
        ?Model $scopeModel,
        string $title,
        string $heading,
        SettingsService $settingsService
    ) {
        $definitions = $this->getDefinitionsByScope($scopeType);
        $settings = $scopeModel ? $settingsService->allForScope($scopeModel) : collect();
        $tabs = $this->buildTabs($definitions, $settings);

        return view('settings::index', [
            'title' => $title,
            'heading' => $heading,
            'scopeType' => $scopeType,
            'hasScope' => (bool) $scopeModel,
            'tabs' => $tabs,
        ]);
    }

    private function buildTabs(Collection $definitions, Collection $settings): Collection
    {
        if ($definitions->isEmpty()) {
            return collect();
        }

        return $definitions
            ->filter(fn (array $definition) => $definition['is_enabled'] && !$definition['is_disabled'])
            ->map(function (array $definition) use ($settings): array {
                $tab = $this->resolveDefinitionTab($definition);
                $key = $definition['key'];
                $hasValue = $settings->has($key);
                $currentValue = $hasValue ? $settings->get($key) : ($definition['default'] ?? null);

                return [
                    ...$definition,
                    'tab' => $tab,
                    'value' => $currentValue,
                ];
            })
            ->groupBy('tab')
            ->map(fn (Collection $items) => $items->values());
    }

    private function resolveDefinitionTab(array $definition): string
    {
        return (string) (($definition['tab'] ?? '') !== ''
            ? $definition['tab']
            : (explode('.', (string) ($definition['key'] ?? ''))[0] ?: 'general'));
    }

    private function getDefinitionsByScope(string $scopeType): Collection
    {
        $path = base_path('Modules/Settings/database/seeders/settings-fields.json');
        if (!File::exists($path)) {
            return collect();
        }

        $payload = json_decode((string) File::get($path), true);
        $definitions = is_array($payload[$scopeType] ?? null) ? $payload[$scopeType] : [];

        return collect($definitions)
            ->filter(fn ($definition) => is_array($definition) && isset($definition['key']))
            ->map(function (array $definition): array {
                return [
                    'tab' => (string) ($definition['tab'] ?? ''),
                    'key' => (string) ($definition['key'] ?? ''),
                    'name' => (string) ($definition['name'] ?? ($definition['key'] ?? '')),
                    'type' => (string) ($definition['type'] ?? 'text'),
                    'default' => $definition['default'] ?? null,
                    'min' => array_key_exists('min', $definition) && is_numeric($definition['min']) ? (int) $definition['min'] : null,
                    'max' => array_key_exists('max', $definition) && is_numeric($definition['max']) ? (int) $definition['max'] : null,
                    'options' => is_array($definition['options'] ?? null) ? $definition['options'] : [],
                    'required' => (bool) ($definition['required'] ?? false),
                    'description' => (string) ($definition['description'] ?? ''),
                    'placeholder' => (string) ($definition['placeholder'] ?? ''),
                    'is_enabled' => (bool) ($definition['is_enabled'] ?? true),
                    'is_disabled' => (bool) ($definition['is_disabled'] ?? false),
                ];
            })
            ->values();
    }

    private function findDefinition(string $scopeType, string $key): ?array
    {
        return $this->getDefinitionsByScope($scopeType)
            ->first(fn (array $definition) => $definition['key'] === $key);
    }

    private function normalizeInputValue(Request $request, array $definition): mixed
    {
        return $this->normalizeRawValue($request->input('value'), $definition);
    }

    private function normalizeRawValue(mixed $rawValue, array $definition): mixed
    {
        $type = $definition['type'];

        if ($type === 'checkbox') {
            return filter_var($rawValue, FILTER_VALIDATE_BOOLEAN);
        }

        if ($type === 'number') {
            if ($rawValue === null || $rawValue === '') {
                return isset($definition['default']) && is_numeric($definition['default'])
                    ? (int) $definition['default']
                    : 0;
            }
            if (!is_numeric($rawValue)) {
                return isset($definition['default']) && is_numeric($definition['default'])
                    ? (int) $definition['default']
                    : 0;
            }
            $num = (int) $rawValue;
            $min = $definition['min'] ?? null;
            $max = $definition['max'] ?? null;
            if (is_int($min)) {
                $num = max($min, $num);
            }
            if (is_int($max)) {
                $num = min($max, $num);
            }

            return $num;
        }

        if ($type === 'select') {
            $allowed = collect($definition['options'])
                ->map(fn ($option) => is_array($option) ? ($option['value'] ?? null) : null)
                ->filter(fn ($value) => $value !== null)
                ->values();

            if ($allowed->isNotEmpty() && !$allowed->contains($rawValue)) {
                return $definition['default'] ?? null;
            }
        }

        if ($type === 'file') {
            if (is_string($rawValue) && $rawValue !== '' && Storage::disk('public')->exists($rawValue)) {
                return $rawValue;
            }

            return null;
        }

        return $rawValue;
    }
}
