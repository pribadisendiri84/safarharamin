<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersTrashed;
use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    use FiltersTrashed;

    public function index(Request $request)
    {
        $query = $this->applyTrashFilter(
            Testimonial::query()->with('creator')->orderBy('sort_order')->orderByDesc('id'),
            $request
        );

        return view('admin.testimonials.index', [
            'testimonials' => $query->paginate(30)->withQueryString(),
            ...$this->trashViewData(Testimonial::class, $request),
        ]);
    }

    public function create()
    {
        return view('admin.testimonials.form', ['testimonial' => new Testimonial]);
    }

    public function store(Request $request)
    {
        Testimonial::query()->create($this->validated($request));

        return redirect()->route('admin.testimonials.index')->with('ok', 'Testimoni ditambahkan.');
    }

    public function edit(Testimonial $testimonial)
    {
        return view('admin.testimonials.form', ['testimonial' => $testimonial]);
    }

    public function update(Request $request, Testimonial $testimonial)
    {
        $testimonial->update($this->validated($request));

        return redirect()->route('admin.testimonials.index')->with('ok', 'Testimoni diperbarui.');
    }

    public function destroy(Testimonial $testimonial)
    {
        $testimonial->delete();

        return redirect()->route('admin.testimonials.index')->with('ok', 'Testimoni dihapus.');
    }

    public function restore(Testimonial $testimonial)
    {
        $testimonial->restore();

        return redirect()->route('admin.testimonials.index', ['trashed' => 1])->with('ok', 'Testimoni dipulihkan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:80'],
            'package_title' => ['nullable', 'string', 'max:160'],
            'quote' => ['required', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }
}
