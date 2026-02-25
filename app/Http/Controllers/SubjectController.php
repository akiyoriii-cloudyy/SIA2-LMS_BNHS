<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
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
}
