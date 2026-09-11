<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersTrashed;
use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageKind;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PackageKindController extends Controller
{
    use FiltersTrashed;

    public function index(Request $request)
    {
        $query = $this->applyTrashFilter(
            PackageKind::query()->with('creator')->orderBy('sort_order')->orderBy('name'),
            $request
        );

        return view('admin.package-kinds.index', [
            'kinds' => $query->get(),
            ...$this->trashViewData(PackageKind::class, $request),
        ]);
    }

    public function store(Request $request)
    {
        PackageKind::query()->create($this->validated($request));

        return redirect()->route('admin.package-kinds.index')->with('ok', 'Tipe paket ditambahkan.');
    }

    public function update(Request $request, PackageKind $packageKind)
    {
        $packageKind->update($this->validated($request, $packageKind));

        return redirect()->route('admin.package-kinds.index')->with('ok', 'Tipe paket diperbarui.');
    }

    public function destroy(PackageKind $packageKind)
    {
        if ($this->isUsed($packageKind)) {
            return back()->withErrors('Tipe paket masih dipakai. Nonaktifkan saja, jangan hapus.');
        }

        $packageKind->delete();

        return redirect()->route('admin.package-kinds.index')->with('ok', 'Tipe paket dihapus.');
    }

    public function restore(PackageKind $packageKind)
    {
        $packageKind->restore();

        return redirect()->route('admin.package-kinds.index', ['trashed' => 1])
            ->with('ok', 'Tipe paket dipulihkan.');
    }

    public function editTemplate(PackageKind $packageKind)
    {
        return view('admin.package-kinds.template', [
            'kind' => $packageKind,
        ]);
    }

    public function updateTemplate(Request $request, PackageKind $packageKind)
    {
        $packageKind->update($this->validatedTemplate($request));

        return redirect()
            ->route('admin.package-kinds.template.edit', $packageKind)
            ->with('ok', 'Template konten tipe paket disimpan.');
    }

    /**
     * @return array{name: string, slug: string, sort_order: int, is_active: bool}
     */
    private function validated(Request $request, ?PackageKind $kind = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('package_kinds', 'name')->ignore($kind)->whereNull('deleted_at')],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = Str::slug($data['name']) ?: 'tipe-paket';
        $request->merge(['slug' => $slug]);
        $request->validate([
            'slug' => [Rule::unique('package_kinds', 'slug')->ignore($kind)->whereNull('deleted_at')],
        ]);

        return [
            'name' => $data['name'],
            'slug' => $slug,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function isUsed(PackageKind $kind): bool
    {
        return Package::withTrashed()->where('package_kind_id', $kind->id)->exists();
    }

    /**
     * @return array{
     *     description: ?string,
     *     hotel_makkah: ?string,
     *     hotel_makkah_setaraf: bool,
     *     hotel_madinah: ?string,
     *     hotel_madinah_setaraf: bool,
     *     facilities: list<string>,
     *     exclusions: list<string>
     * }
     */
    private function validatedTemplate(Request $request): array
    {
        $data = $request->validate([
            'description' => ['nullable', 'string'],
            'hotel_makkah' => ['nullable', 'string', 'max:120'],
            'hotel_madinah' => ['nullable', 'string', 'max:120'],
            'facilities_text' => ['nullable', 'string'],
            'exclusions_text' => ['nullable', 'string'],
        ]);

        return [
            'description' => filled($data['description'] ?? null) ? $data['description'] : null,
            'hotel_makkah' => filled($data['hotel_makkah'] ?? null) ? $data['hotel_makkah'] : null,
            'hotel_makkah_setaraf' => $request->boolean('hotel_makkah_setaraf'),
            'hotel_madinah' => filled($data['hotel_madinah'] ?? null) ? $data['hotel_madinah'] : null,
            'hotel_madinah_setaraf' => $request->boolean('hotel_madinah_setaraf'),
            'facilities' => $this->lines($data['facilities_text'] ?? null),
            'exclusions' => $this->lines($data['exclusions_text'] ?? null),
        ];
    }

    /**
     * @return list<string>
     */
    private function lines(?string $text): array
    {
        if (! filled($text)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (string $line) => trim($line),
            preg_split('/\R/', $text) ?: [],
        )));
    }
}
