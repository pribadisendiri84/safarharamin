<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersTrashed;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Package;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InquiryController extends Controller
{
    use FiltersTrashed;

    public function index(Request $request)
    {
        $user = $request->user();
        $query = $this->applyTrashFilter(
            Inquiry::query()->visibleTo($user)->with(['package', 'creator', 'pic'])->latest(),
            $request
        );

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($user?->canSeeLeadSources() && ($source = $request->string('source')->toString())) {
            $query->where('source', $source);
        }

        if ($user?->canSeeLeadSources() && ($picId = $request->integer('pic_id'))) {
            $query->where('pic_id', $picId);
        }

        $statusCounts = Inquiry::query()
            ->visibleTo($user)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $trash = $this->trashViewData(Inquiry::class, $request);
        $trash['trashedCount'] = Inquiry::query()->visibleTo($user)->onlyTrashed()->count();

        return view('admin.inquiries.index', [
            'inquiries' => $query->paginate(30)->withQueryString(),
            'statusCounts' => $statusCounts,
            'pics' => $user?->canSeeLeadSources()
                ? User::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
            ...$trash,
        ]);
    }

    public function create()
    {
        return view('admin.inquiries.form', [
            'packages' => Package::query()->published()->orderBy('title')->get(['id', 'title', 'price']),
            'pics' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedLead($request);
        $data['source'] = Inquiry::SOURCE_TEAM;
        $data['status'] = Inquiry::STATUS_NEW;
        if ($request->user()?->isStaff()) {
            $data['pic_id'] = $request->user()->id;
        } else {
            $data['pic_id'] = $data['pic_id'] ?? $request->user()?->id;
        }

        $inquiry = Inquiry::query()->create($data);

        return redirect()->route('admin.inquiries.show', $inquiry)->with('ok', 'Pengajuan dicatat. PIC: '.$inquiry->picName().'.');
    }

    public function show(Inquiry $inquiry)
    {
        $this->authorizeInquiry($inquiry);

        $inquiry->load(['package', 'followUps.author', 'creator', 'pic']);

        return view('admin.inquiries.show', [
            'inquiry' => $inquiry,
            'packages' => Package::query()->withTrashed()->orderBy('title')->get(['id', 'title', 'price', 'deleted_at']),
            'pics' => request()->user()?->canSeeLeadSources()
                ? User::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function update(Request $request, Inquiry $inquiry)
    {
        $this->authorizeInquiry($inquiry);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Inquiry::STATUSES))],
            'pic_id' => ['nullable', 'exists:users,id'],
            'package_id' => [
                Rule::requiredIf($request->input('status') === Inquiry::STATUS_SOLD && ! $inquiry->package_id),
                'nullable',
                'exists:packages,id',
            ],
            'sold_pax' => ['nullable', 'integer', 'min:1', 'max:80'],
            'sold_amount' => ['nullable', 'integer', 'min:0'],
            'closed_at' => ['nullable', 'date'],
        ]);

        if ($request->user()?->isStaff()) {
            unset($data['pic_id']);
        }

        foreach (['package_id', 'sold_pax', 'sold_amount', 'closed_at', 'pic_id'] as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            if ($key === 'pic_id') {
                continue;
            }
            if (! filled($data[$key])) {
                unset($data[$key]);
            }
        }

        if ($data['status'] === Inquiry::STATUS_SOLD) {
            $data['package_id'] = $data['package_id'] ?? $inquiry->package_id;
            $data['sold_pax'] = $data['sold_pax'] ?? $inquiry->sold_pax ?? $inquiry->pax ?? 1;
            $data['closed_at'] = $data['closed_at'] ?? $inquiry->closed_at ?? now();

            if (! isset($data['sold_amount'])) {
                $package = Package::query()->find($data['package_id']);
                $data['sold_amount'] = $package ? $package->price * (int) $data['sold_pax'] : ($inquiry->sold_amount ?? 0);
            }
        }

        DB::transaction(function () use ($inquiry, $data) {
            $inquiry->update($data);
            $inquiry->syncPackageSeats();
        });

        $message = $inquiry->isSold()
            ? 'Closing dicatat. Seat paket sudah dikurangi.'
            : 'Pengajuan diperbarui.';

        return redirect()->route('admin.inquiries.show', $inquiry)->with('ok', $message);
    }

    public function storeNote(Request $request, Inquiry $inquiry)
    {
        $this->authorizeInquiry($inquiry);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $inquiry->followUps()->create([
            'user_id' => $request->user()?->id,
            'body' => $data['body'],
        ]);

        if ($inquiry->status === Inquiry::STATUS_NEW) {
            $inquiry->update(['status' => Inquiry::STATUS_FOLLOWED_UP]);
        }

        if (! $inquiry->pic_id && $request->user()) {
            $inquiry->update(['pic_id' => $request->user()->id]);
        }

        return back()->with('ok', 'Catatan follow-up disimpan.');
    }

    public function destroy(Inquiry $inquiry)
    {
        $this->authorize('manage-catalog');
        $inquiry->delete();

        return redirect()->route('admin.inquiries.index')->with('ok', 'Pengajuan dihapus.');
    }

    public function restore(Inquiry $inquiry)
    {
        $this->authorize('manage-catalog');
        $inquiry->restore();

        return redirect()->route('admin.inquiries.index', ['trashed' => 1])->with('ok', 'Pengajuan dipulihkan.');
    }

    private function authorizeInquiry(Inquiry $inquiry): void
    {
        if (! $inquiry->isVisibleTo(request()->user())) {
            throw new AuthorizationException;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedLead(Request $request): array
    {
        return $request->validate([
            'kind' => ['required', Rule::in(['daftar', 'tanya'])],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'city' => ['nullable', 'string', Rule::exists('cities', 'slug')->whereNull('deleted_at')],
            'package_id' => ['nullable', 'exists:packages,id'],
            'pax' => ['required', 'integer', 'min:1', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'pic_id' => ['required', 'exists:users,id'],
        ]);
    }
}
