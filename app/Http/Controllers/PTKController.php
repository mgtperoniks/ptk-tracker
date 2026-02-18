<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Imports\PTKImport;
use App\Models\{PTK, Department, Category, User};
use App\Services\AttachmentService;
use App\Support\DeptScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\{JsonResponse, RedirectResponse, Request};
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class PTKController extends Controller
{
    private const PER_PAGE = 20;
    private const KANBAN_LIMIT = 30;

    // Tambahkan status baru agar konsisten dengan flow antrian
    private const STATUSES = ['Not Started', 'In Progress', 'Submitted', 'Waiting Director', 'Completed'];

    public function __construct(private readonly AttachmentService $attachments)
    {
        $this->authorizeResource(PTK::class, 'ptk');
    }

    # =========================================================
    # INDEX — daftar PTK (pakai scope visibleTo) + role_filter
    # =========================================================
    public function index(Request $request): View
    {
        // Base query dengan eager load dan scope visibleTo (akses)
        $base = PTK::with(['department', 'category', 'subcategory', 'pic'])
            ->visibleTo($request->user());

        // Free-text search (number OR title)
        if ($q = $request->query('q')) {
            $base->where(function ($qb) use ($q) {
                $qb->where('number', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%");
            });
        }

        // Filter status (opsional)
        if ($status = $request->query('status')) {
            $base->where('status', $status);
        }

        // Filter berdasarkan role admin/divisi (role_filter)
        if ($role = $request->query('role_filter')) {
            if (is_string($role)) {
                // Ambil user id untuk role tersebut
                $userIds = User::role($role)->pluck('id')->toArray();

                if (empty($userIds)) {
                    $base->whereRaw('0 = 1');
                } else {
                    $base->where(function ($qb) use ($userIds) {
                        $qb->whereIn('created_by', $userIds)
                            ->orWhereIn('pic_user_id', $userIds);
                    });
                }
            }
        }

        // order & paginate (jaga querystring supaya filter tetap ada ketika pindah halaman)
        $ptks = $base->latest('created_at')->paginate(self::PER_PAGE)->withQueryString();

        return view('ptk.index', compact('ptks'));
    }

    # =========================================================
    # CREATE / STORE
    # =========================================================
    public function create(Request $request): View
    {
        return view('ptk.create', [
            'departments' => Department::orderBy('name')->pluck('name', 'id'),
            'categories' => Category::all(),
            'picCandidates' => $this->picCandidatesFor($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // 1. Validation Logic
        $user = $request->user();
        $isMtc = $user->hasAnyRole(['admin_mtc', 'kabag_mtc']);

        $rules = $this->rulesForStore();
        if ($isMtc) {
            $rules = array_merge($rules, $this->rulesForMtc());
        }

        $data = $request->validate($rules);

        // 2. Create PTK Core
        $ptkData = collect($data)->except(['attachments', 'mtc', 'spareparts'])->toArray();
        $ptkData['created_by'] = $user->id;

        // Set default description for MTC (they don't have description field)
        if ($isMtc && empty($ptkData['description'])) {
            $ptkData['description'] = '';
        }

        $ptk = PTK::create($ptkData);
        $this->handleAttachments($request, $ptk->id);

        // 3. Handle MTC Details
        if ($isMtc) {
            $this->storeMtcDetails($ptk, $request);
        }

        return redirect()->route('ptk.index')->with('ok', 'PTK tersimpan.');
    }

    # =========================================================
    # SHOW / EDIT / UPDATE
    # =========================================================
    public function show(PTK $ptk): View
    {
        $this->authorize('view', $ptk);

        $ptk->load([
            'pic:id,name',
            'department:id,name',
            'category:id,name',
            'subcategory:id,name',
            'creator:id,name',
            'attachments',
            'mtcDetail.spareparts', // Load MTC details
        ]);

        return view('ptk.show', compact('ptk'));
    }

    public function edit(Request $request, PTK $ptk): View
    {
        $ptk->load('mtcDetail.spareparts');

        return view('ptk.edit', [
            'ptk' => $ptk,
            'departments' => Department::orderBy('name')->pluck('name', 'id'),
            'categories' => Category::all(),
            'picCandidates' => $this->picCandidatesFor($request),
        ]);
    }

    public function update(Request $request, PTK $ptk): RedirectResponse
    {
        $user = $request->user();
        $isMtc = $user->hasAnyRole(['admin_mtc', 'kabag_mtc']);

        $rules = $this->rulesForUpdate($ptk);
        if ($isMtc) {
            $rules = array_merge($rules, $this->rulesForMtc());
        }

        $data = $request->validate($rules);

        // Auto set In Progress ketika evaluation diisi dari Not Started
        if ($request->filled('evaluation') && $ptk->status === 'Not Started') {
            $data['status'] = 'In Progress';
        }

        $ptk->update(collect($data)->except(['attachments', 'mtc', 'spareparts'])->toArray());
        $this->handleAttachments($request, $ptk->id);

        // Handle MTC Details
        if ($isMtc) {
            $this->storeMtcDetails($ptk, $request);
        }

        return back()->with('ok', 'Perubahan disimpan.');
    }

    # =========================================================
    # DESTROY (soft delete)
    # =========================================================
    public function destroy(PTK $ptk): RedirectResponse
    {
        try {
            $ptk->delete();
            return redirect()->route('ptk.index')->with('ok', 'PTK dipindahkan ke Recycle Bin.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus PTK: ' . $e->getMessage());
        }
    }

    # =========================================================
    # KANBAN — pakai scope visibleTo
    # =========================================================
    public function kanban(): View
    {
        $base = PTK::with(['department', 'category', 'subcategory', 'pic'])
            ->visibleTo(auth()->user());

        $notStarted = (clone $base)->where('status', 'Not Started')
            ->orderBy('created_at')->limit(self::KANBAN_LIMIT)->get();

        $inProgress = (clone $base)->where('status', 'In Progress')
            ->orderBy('created_at')->limit(self::KANBAN_LIMIT)->get();

        $completed = (clone $base)->where('status', 'Completed')
            ->latest()->limit(self::KANBAN_LIMIT)->get();

        return view('ptk.kanban', compact('notStarted', 'inProgress', 'completed'));
    }

    # =========================================================
    # QUICK STATUS (AJAX)
    # =========================================================
    public function quickStatus(Request $request, PTK $ptk): JsonResponse
    {
        $this->authorize('update', $ptk);

        $request->validate(['status' => ['required', Rule::in(self::STATUSES)]]);
        $ptk->update(['status' => $request->string('status')]);

        return response()->json(['ok' => true]);
    }

    # =========================================================
    # QUEUE — antrian approval (tanpa filter "no number")
    #   - stage: null/all (default kabag/manager) | director
    #   - jika user direktur, auto ke antrian direktur
    # =========================================================
    public function queue(Request $request, ?string $stage = null): View
    {
        $user = auth()->user();
        $stage = $stage ? strtolower($stage) : null;

        $base = PTK::with(['department', 'pic'])->visibleTo($user);

        if ($user->hasRole('director') || $stage === 'director') {
            // Stage 2: menunggu Direktur
            $items = (clone $base)
                ->where('status', 'Waiting Director')
                ->whereNull('approved_stage2_at')
                ->latest('updated_at')
                ->get();
            $stage = 'director';
        } else {
            // Stage 1: menunggu Kabag/Manager
            $items = (clone $base)
                ->where('status', 'Submitted')
                ->whereNull('approved_stage1_at')
                ->latest('updated_at')
                ->get();
            $stage = 'approver';
        }

        return view('ptk.queue', compact('items', 'stage'));
    }

    # =========================================================
    # RECYCLE BIN (pakai scope visibleTo)
    # =========================================================
    public function recycle(Request $request): View
    {
        $items = PTK::onlyTrashed()
            ->visibleTo(auth()->user())
            ->with(['department:id,name', 'category:id,name', 'subcategory:id,name', 'pic:id,name'])
            ->latest('deleted_at')
            ->paginate(self::PER_PAGE);

        return view('ptk.recycle', compact('items'));
    }

    public function restore(Request $request, string $id): RedirectResponse
    {
        $ptk = PTK::withTrashed()->findOrFail($id);
        $this->authorize('delete', $ptk);
        $ptk->restore();

        return back()->with('ok', 'PTK dipulihkan.');
    }

    public function forceDelete(Request $request, string $id): RedirectResponse
    {
        $ptk = PTK::withTrashed()->with('attachments')->findOrFail($id);
        $this->authorize('delete', $ptk);

        foreach ($ptk->attachments as $a) {
            if ($a->path)
                Storage::disk('public')->delete($a->path);
            $a->delete();
        }

        $ptk->forceDelete();
        return back()->with('ok', 'PTK dihapus permanen.');
    }

    # =========================================================
    # SUBMIT — ubah status ke Submitted (bukan Completed)
    #   - wajib sudah ada number (manual)
    #   - reset approval & reject info lama
    # =========================================================
    public function submit(PTK $ptk): RedirectResponse
    {
        $this->authorize('update', $ptk);

        // Wajib sudah ada number (sekarang manual)
        if (empty($ptk->number)) {
            return back()->with('ok', 'Isi Nomor PTK dulu sebelum Submit.');
        }

        $ptk->update([
            'status' => 'Submitted',
            'approved_stage1_by' => null,
            'approved_stage1_at' => null,
            'approved_stage2_by' => null,
            'approved_stage2_at' => null,
            'last_reject_stage' => null,
            'last_reject_reason' => null,
            'last_reject_by' => null,
            'last_reject_at' => null,
        ]);

        return back()->with('ok', 'PTK dikirim ke antrian Kabag/Manager.');
    }

    # =========================================================
    # IMPORT
    # =========================================================
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ]);

        Excel::import(new PTKImport, $request->file('file'));

        return back()->with('ok', 'Import selesai.');
    }

    # =========================================================
    # HELPERS
    # =========================================================
    private function picCandidatesFor(Request $request)
    {
        $builder = User::query()->orderBy('name')->select(['id', 'name']);
        $allowedDeptIds = DeptScope::allowedDeptIds($request->user());

        if (!empty($allowedDeptIds)) {
            $builder->whereIn('department_id', $allowedDeptIds);
        }

        return $builder->get();
    }

    /**
     * Validasi untuk STORE (create)
     * - number wajib & unik
     * - due_date wajib
     * - title max 200
     * - pic_user_id wajib (selaras dengan form)
     */
    private function rulesForStore(): array
    {
        return [
            'number' => ['required', 'string', 'max:50', 'unique:ptks,number'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'desc_nc' => ['nullable', 'string'],
            'evaluation' => ['nullable', 'string'],
            'action_correction' => ['nullable', 'string'],
            'action_corrective' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:subcategories,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'pic_user_id' => ['required', 'exists:users,id'],
            'due_date' => ['required', 'date'],
            'form_date' => ['required', 'date'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    /**
     * Validasi untuk UPDATE (edit)
     * - number unik tapi mengabaikan id PTK sendiri
     * - due_date wajib
     */
    private function rulesForUpdate(PTK $ptk): array
    {
        return [
            'number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('ptks', 'number')->ignore($ptk->id),
            ],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'desc_nc' => ['nullable', 'string'],
            'evaluation' => ['nullable', 'string'],
            'action_correction' => ['nullable', 'string'],
            'action_corrective' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:subcategories,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'pic_user_id' => ['required', 'exists:users,id'],
            'due_date' => ['required', 'date'],
            'form_date' => ['required', 'date'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    private function handleAttachments(Request $request, int $ptkId): void
    {
        if (!$request->hasFile('attachments')) {
            return;
        }

        foreach ((array) $request->file('attachments') as $file) {
            if ($file) {
                $this->attachments->handle($file, $ptkId);
            }
        }
    }

    private function rulesForMtc(): array
    {
        return [
            // MTC Detail
            'mtc.machine_damage_desc' => 'nullable|string',
            'mtc.machine_stop_status' => 'nullable|in:total,partial',
            'mtc.problem_evaluation' => 'nullable|string',
            'mtc.needs_sparepart' => 'nullable|boolean', // 1 or 0
            'mtc.installation_date' => 'nullable|date',
            'mtc.repaired_by' => 'nullable|string|max:100',
            'mtc.technical_notes' => 'nullable|string',
            'mtc.machine_status_after' => 'nullable|in:normal,trouble',
            'mtc.trial_hours' => 'nullable|integer',
            'mtc.trial_result' => 'nullable|string',

            // Spareparts (Array)
            'spareparts' => 'nullable|array',
            'spareparts.*.name' => 'required_with:spareparts|string|max:255',
            'spareparts.*.spec' => 'nullable|string|max:255',
            'spareparts.*.qty' => 'nullable|integer|min:1',
            'spareparts.*.supplier' => 'nullable|string|max:255',
            'spareparts.*.order_date' => 'nullable|date',
            'spareparts.*.status' => 'nullable|in:Requested,Ordered,Shipped,Received',
            'spareparts.*.est_arrival_date' => 'nullable|date',
            'spareparts.*.actual_arrival_date' => 'nullable|date',
        ];
    }

    private function storeMtcDetails(PTK $ptk, Request $request): void
    {
        // 1. Update/Create Parent MTC Detail
        $mtcData = $request->input('mtc', []);

        // Ensure boolean is handled (checkbox often sends '1' or nothing)
        $mtcData['needs_sparepart'] = $request->boolean('mtc.needs_sparepart');

        $detail = $ptk->mtcDetail()->updateOrCreate(
            ['ptk_id' => $ptk->id],
            $mtcData
        );

        // 2. Sync Spareparts
        // Strategy: Delete all existing and re-create (simplest for dynamic lists)
        $detail->spareparts()->delete();

        if ($request->has('spareparts') && $mtcData['needs_sparepart']) {
            foreach ($request->input('spareparts') as $sp) {
                // Ignore empty rows if any (and trim name to be safe)
                if (empty($sp['name']) || empty(trim($sp['name'])))
                    continue;

                $detail->spareparts()->create($sp);
            }
        }
    }
}
