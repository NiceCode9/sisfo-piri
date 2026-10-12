<?php

namespace App\Http\Controllers\Admin\Cbt;

use App\Http\Controllers\Controller;
use App\Models\ExamQuestion;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Penyaji gambar soal untuk panel admin.
 *
 * Route terpisah dari API siswa karena dunia auth-nya berbeda: di sini user
 * datang lewat session web, bukan Bearer token, dan `<img src>` memang bisa
 * memakainya langsung tanpa header tambahan.
 *
 * Soal diuji ke exam ATAU bank induknya, sesuai di mana soal itu hidup —
 * ini yang membuat kontrol Phase 1 (ExamPolicy) ikut berlaku di sini.
 */
class ExamQuestionImageController extends Controller
{
    use AuthorizesRequests;

    public static function middleware(): array
    {
        return [new Middleware('permission:cbt.view')];
    }

    public function __invoke(Request $request, ExamQuestion $question): StreamedResponse
    {
        abort_if(! $question->question_image, 404);

        if ($question->exam !== null) {
            $this->authorize('view', $question->exam);
        } elseif ($question->bank !== null) {
            $this->authorize('update', $question->bank);
        } else {
            abort(404);
        }

        abort_unless($question->imageExists(), 404);

        return Storage::disk(ExamQuestion::DISK)->response($question->question_image);
    }
}
