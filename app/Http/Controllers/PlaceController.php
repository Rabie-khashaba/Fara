<?php

namespace App\Http\Controllers;

use App\Models\AppUserCheckIn;
use App\Models\AppUserCheckInCity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlaceController extends Controller
{
    private const CATEGORIES = [
        'restaurant',
        'cafe',
        'mall',
        'other',
    ];

    public function index(Request $request): View
    {
        $query = AppUserCheckInCity::query()
            ->withCount('checkIns')
            ->when($request->filled('search'), function ($builder) use ($request) {
                $search = trim((string) $request->string('search'));

                $builder->where('name', 'like', "%{$search}%");
            })
            ->when(
                in_array($request->input('category'), self::CATEGORIES, true),
                fn ($builder) => $builder->where('category', $request->input('category'))
            )
            ->addSelect([
                'current_users_count' => AppUserCheckIn::query()
                    ->selectRaw('count(*)')
                    ->joinSub(
                        AppUserCheckIn::query()
                            ->selectRaw('MAX(id) as latest_check_in_id')
                            ->groupBy('app_user_id'),
                        'latest_check_ins',
                        fn ($join) => $join->on('latest_check_ins.latest_check_in_id', '=', 'app_user_check_ins.id')
                    )
                    ->whereColumn('app_user_check_ins.app_user_check_in_city_id', 'app_user_check_in_cities.id'),
            ])
            ->latest();

        return view('places.index', [
            'places' => $query->paginate(10)->withQueryString(),
            'categories' => self::categoryOptions(),
        ]);
    }

    public function create(): View
    {
        return view('places.create', [
            'categories' => self::categoryOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['slug'] = $this->generateUniqueSlug($data['name']);
        $data['country_code'] = strtoupper($data['country_code']);
        $data['is_predefined'] = $request->boolean('is_predefined', true);

        AppUserCheckInCity::query()->create($data);

        return redirect()->route('places.index')->with('status', 'Place created successfully.');
    }

    public function edit(AppUserCheckInCity $place): View
    {
        return view('places.edit', [
            'place' => $place,
            'categories' => self::categoryOptions(),
        ]);
    }

    public function update(Request $request, AppUserCheckInCity $place): RedirectResponse
    {
        $data = $this->validatedData($request, $place);
        $data['country_code'] = strtoupper($data['country_code']);
        $data['is_predefined'] = $request->boolean('is_predefined');

        if ($place->name !== $data['name']) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $place->id);
        }

        $place->update($data);

        return redirect()->route('places.index')->with('status', 'Place updated successfully.');
    }

    private function validatedData(Request $request, ?AppUserCheckInCity $place = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'country_code' => ['required', 'string', 'size:2'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['required', 'numeric', 'min:0.1', 'max:500'],
            'is_predefined' => ['nullable', 'boolean'],
        ]);
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'place';
        $slug = $baseSlug;
        $suffix = 1;

        while (
            AppUserCheckInCity::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private static function categoryOptions(): array
    {
        return [
            'restaurant' => 'Restaurant',
            'cafe' => 'Cafe',
            'mall' => 'Mall',
            'other' => 'Other',
        ];
    }
}
