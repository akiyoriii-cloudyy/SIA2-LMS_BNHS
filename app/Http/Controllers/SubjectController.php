<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $total = Subject::query()->count();

        $subjects = Subject::query()
            ->when($search !== '', function ($q) use ($search): void {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            })
            ->orderBy('code')
            ->orderBy('title')
            ->get();

        return view('subjects.index', [
            'subjects' => $subjects,
            'search' => $search,
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
        ]);

        Subject::query()->create([
            'code' => strtoupper(trim($validated['code'])),
            'title' => trim($validated['title']),
        ]);

        return back()->with('status', 'Subject added.');
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('subjects', 'code')->ignore($subject->id)],
            'title' => ['required', 'string', 'max:255'],
        ]);

        $subject->update([
            'code' => strtoupper(trim($validated['code'])),
            'title' => trim($validated['title']),
        ]);

        return back()->with('status', 'Subject updated.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $subject->delete();

        return back()->with('status', 'Subject deleted.');
    }
}
