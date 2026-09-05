<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersTrashed;
use App\Http\Controllers\Controller;
use App\Models\Pic;
use App\Models\Pilgrim;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PicController extends Controller
{
    use FiltersTrashed;

    public function index(Request $request)
    {
        $query = $this->applyTrashFilter(
            Pic::query()->with('creator')->orderBy('sort_order')->orderBy('name'),
            $request
        );

        return view('admin.pics.index', [
            'pics' => $query->get(),
            ...$this->trashViewData(Pic::class, $request),
        ]);
    }

    public function store(Request $request)
    {
        Pic::query()->create($this->validated($request));

        return redirect()->route('admin.pics.index')->with('ok', 'PIC ditambahkan.');
    }

    public function update(Request $request, Pic $pic)
    {
        $pic->update($this->validated($request, $pic));

        return redirect()->route('admin.pics.index')->with('ok', 'PIC diperbarui.');
    }

    public function destroy(Pic $pic)
    {
        if ($this->isUsed($pic)) {
            return back()->withErrors('PIC masih dipakai jamaah. Nonaktifkan saja, jangan hapus.');
        }

        $pic->delete();

        return redirect()->route('admin.pics.index')->with('ok', 'PIC dihapus.');
    }

    public function restore(Pic $pic)
    {
        $pic->restore();

        return redirect()->route('admin.pics.index', ['trashed' => 1])
            ->with('ok', 'PIC dipulihkan.');
    }

    /**
     * @return array{name: string, phone: ?string, sort_order: int, is_active: bool}
     */
    private function validated(Request $request, ?Pic $pic = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('pics', 'name')->ignore($pic)->whereNull('deleted_at')],
            'phone' => ['nullable', 'string', 'max:32'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $phone = trim((string) ($data['phone'] ?? ''));

        return [
            'name' => $data['name'],
            'phone' => $phone === '' ? null : $phone,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function isUsed(Pic $pic): bool
    {
        return Pilgrim::withTrashed()->where('pic_id', $pic->id)->exists();
    }
}
