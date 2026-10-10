<?php

namespace App\Http\Controllers\Admin\Cbt;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamToken;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;

class ExamTokenController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    public static function middleware(): array
    {
        return [new Middleware('permission:cbt.manage')];
    }

    public function store(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

        $data = $request->validate([
            'active_from' => ['required', 'date'],
            'active_until' => ['required', 'date', 'after:active_from'],
            'max_usage' => ['nullable', 'integer', 'min:1'],
        ]);

        $data['exam_id'] = $exam->id;
        $data['token'] = strtoupper(Str::random(12));
        $data['created_by'] = auth()->id();

        ExamToken::create($data);

        return back()->with('success', 'Token dibuat.');
    }

    public function destroy(ExamToken $token): RedirectResponse
    {
        $this->authorize('update', $token->exam);

        $token->delete();

        return back()->with('success', 'Token dihapus.');
    }
}
