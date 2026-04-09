<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\TeacherSubject;
use App\Services\InAppNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function __construct(
        private readonly InAppNotificationService $inAppNotifications,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'active');
        $status = in_array($status, ['active', 'deleted'], true) ? $status : 'active';
        $category = strtolower(trim((string) $request->query('category', 'all')));
        $allowedCategories = array_merge(['all'], Subject::CATEGORIES);
        if (! in_array($category, $allowedCategories, true)) {
            $category = 'all';
        }

        $activeCount = Subject::query()->count();
        $deletedCount = Subject::onlyTrashed()->count();
        $total = $activeCount + $deletedCount;
        $categoryCounts = collect(Subject::CATEGORIES)
            ->mapWithKeys(fn (string $item): array => [$item => (int) Subject::withTrashed()->where('category', $item)->count()])
            ->all();

        $subjects = Subject::query()
            ->when($status === 'deleted', fn ($q) => $q->onlyTrashed())
            ->when($category !== 'all', fn ($q) => $q->where('category', $category))
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sq) use ($search): void {
                    $sq->where('code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->orderBy('code')
            ->orderBy('title')
            ->get();

        return view('subjects.index', [
            'subjects' => $subjects,
            'search' => $search,
            'status' => $status,
            'category' => $category,
            'categories' => Subject::CATEGORIES,
            'categoryCounts' => $categoryCounts,
            'activeCount' => $activeCount,
            'deletedCount' => $deletedCount,
            'stats' => [
                'total' => $total,
                'filtered' => (int) $subjects->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:subjects,code'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(Subject::CATEGORIES)],
        ]);

        $title = trim($validated['title']);
        Subject::query()->create([
            'code' => strtoupper(trim($validated['code'])),
            'title' => $title,
            'category' => strtolower(trim($validated['category'])),
        ]);

        $this->inAppNotifications->notifyAllAdmins(
            'subject',
            'Subject created',
            "A new subject «{$title}» was added from Records.",
            $this->actorMeta($request),
        );

        return back()->with('status', 'Subject added.');
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('subjects', 'code')->ignore($subject->id)],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(Subject::CATEGORIES)],
        ]);

        $subject->update([
            'code' => strtoupper(trim($validated['code'])),
            'title' => trim($validated['title']),
            'category' => strtolower(trim($validated['category'])),
        ]);

        $meta = $this->actorMeta($request) + ['subject_id' => $subject->id];
        $this->inAppNotifications->notifyAllAdmins(
            'subject',
            'Subject updated',
            "Subject «{$subject->title}» ({$subject->code}) was updated from Records.",
            $meta,
        );

        $assignment = TeacherSubject::query()->where('subject_id', $subject->id)->with('teacher.user')->first();
        $assigneeUserId = $assignment?->teacher?->user_id;
        if ($assigneeUserId && $assigneeUserId !== $request->user()->id) {
            $this->inAppNotifications->notifyUser(
                $assigneeUserId,
                'subject',
                'Subject updated',
                "The subject you teach, «{$subject->title}», was updated.",
                $meta,
            );
        }

        return back()->with('status', 'Subject updated.');
    }

    public function destroy(Request $request, Subject $subject): RedirectResponse
    {
        $assignment = TeacherSubject::query()->where('subject_id', $subject->id)->with('teacher.user')->first();
        $label = $subject->title;
        $meta = $this->actorMeta($request) + ['subject_id' => $subject->id];

        $subject->delete();

        $this->inAppNotifications->notifyAllAdmins(
            'subject',
            'Subject deleted',
            "Subject «{$label}» was removed from Records.",
            $meta,
        );

        $assigneeUserId = $assignment?->teacher?->user_id;
        if ($assigneeUserId && $assigneeUserId !== $request->user()->id) {
            $this->inAppNotifications->notifyUser(
                $assigneeUserId,
                'subject',
                'Subject removed',
                "«{$label}» is no longer available in the catalog (your assignment may need review).",
                $meta,
            );
        }

        return back()->with('status', 'Subject deleted.');
    }

    public function restore(Request $request, string $id): RedirectResponse
    {
        $subject = Subject::onlyTrashed()->findOrFail($id);
        $subject->restore();

        $this->inAppNotifications->notifyAllAdmins(
            'subject',
            'Subject restored',
            "Subject «{$subject->title}» was restored.",
            $this->actorMeta($request) + ['subject_id' => $subject->id],
        );

        return back()->with('status', 'Subject restored.');
    }

    /**
     * @return array{actor_id: int, actor_name: string}
     */
    private function actorMeta(Request $request): array
    {
        $user = $request->user();

        return [
            'actor_id' => $user->id,
            'actor_name' => (string) ($user->display_name ?: $user->name),
        ];
    }
}
