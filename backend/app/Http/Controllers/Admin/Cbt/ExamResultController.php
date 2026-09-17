<?php

namespace App\Http\Controllers\Admin\Cbt;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamResultController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('permission:cbt.view')];
    }

    public function index(Request $request, Exam $exam): View
    {
        $exam->load(['mataPelajaran', 'rombel.kelas', 'questions' => fn ($q) => $q->orderBy('order')]);

        $sessions = $exam->sessions()->with(['user', 'answers.question'])->orderBy('score', 'desc')->get();

        return view('admin.cbt.results.index', compact('exam', 'sessions'));
    }

    public function export(Request $request, Exam $exam): StreamedResponse
    {
        $exam->load('questions');
        $sessions = $exam->sessions()->with(['user', 'answers'])->get();

        $headers = ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => "attachment; filename=\"hasil-{$exam->id}.csv\""];

        return response()->stream(function () use ($exam, $sessions) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            $head = ['Nama', 'NIS', 'Status', 'Skor', 'Pelanggaran', 'Selesai'];
            foreach ($exam->questions as $q) {
                $head[] = 'Q'.$q->id.' ('.$q->score.'p)';
            }
            fputcsv($out, $head);
            foreach ($sessions as $s) {
                $row = [$s->user->name ?? '-', $s->user->username ?? '-', $s->status, $s->score ?? 0, $s->violation_count, $s->finished_at];
                foreach ($exam->questions as $q) {
                    $ans = $s->answers->firstWhere('exam_question_id', $q->id);
                    $row[] = $ans ? ($ans->score_obtained ?? 0) : '-';
                }
                fputcsv($out, $row);
            }
            fclose($out);
        }, 200, $headers);
    }
}
