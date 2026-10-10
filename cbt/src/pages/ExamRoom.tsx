import { useState } from 'react';
import { useExamGuard } from '../hooks/useExamGuard';
import { useAutosaveAnswer } from '../hooks/useAutosaveAnswer';
import { useExamStore } from '../store/examStore';
import QuestionNavigator from '../components/QuestionNavigator';
import { finishExam } from '../services/examApi';
import { useNavigate } from 'react-router-dom';
import type { ExamMeta, Question } from '../store/examStore';

/**
 * Cangkang luar hanya menahan render sampai `meta` terisi. Semua hook hidup di
 * ExamRoomInner: `onForceFinish` memanggil `reset()` yang mennullkan `meta`,
 * dan bila `if (!meta) return null` berada di komponen yang sama, render
 * berikutnya memotong daftar hook sehingga React melempar "Rendered fewer hooks
 * than expected" — tab ujian blank tepat di saat auto-finish memicunya.
 * Dengan memisahkan keduanya, komponen dalam hanya di-unmount, bukan
 * dirender dengan jumlah hook berbeda.
 */
export default function ExamRoom() {
  const meta = useExamStore((s) => s.meta);
  const questions = useExamStore((s) => s.questions);

  if (!meta) return null;

  return <ExamRoomInner meta={meta} questions={questions} />;
}

function ExamRoomInner({ meta, questions }: { meta: ExamMeta; questions: Question[] }) {
  const navigate = useNavigate();
  const { answers, savingStatus, currentIndex, flagged, setAnswer, goToQuestion, toggleFlag, reset } = useExamStore();
  const [showFinishModal, setShowFinishModal] = useState(false);
  const [showNavigator, setShowNavigator] = useState(false);

  const { saveAnswer, flush } = useAutosaveAnswer(meta.examSessionId);
  const guard = useExamGuard({
    examSessionId: meta.examSessionId,
    maxViolationCount: meta.maxViolationCount,
    expectedEndAt: meta.expectedEndAt,
    initialViolationCount: meta.violationCount ?? 0,
    onForceFinish: (reason) => {
      reset();
      navigate(`/finished?reason=${reason}`);
    },
  });

  const currentQuestion = questions[currentIndex];
  const isFlagged = !!flagged[currentQuestion.id];
  const hasAnswer = (answers[currentQuestion.id] ?? []).some((v) => v.trim() !== '');

  const handleSelectOption = (optionKey: string) => {
    const newAnswer =
      currentQuestion.type === 'multiple_choice'
        ? toggleMultiple(answers[currentQuestion.id] ?? [], optionKey)
        : [optionKey];
    setAnswer(currentQuestion.id, newAnswer);
    saveAnswer(currentQuestion.id, newAnswer);
  };

  const handleEssayChange = (text: string) => {
    const newAnswer = [text];
    setAnswer(currentQuestion.id, newAnswer);
    saveAnswer(currentQuestion.id, newAnswer);
  };

  const confirmFinish = async () => {
    await flush();
    try {
      await finishExam(meta.examSessionId, 'manual');
    } catch {
      // retry di examApi sudah 2x
    }
    reset();
    navigate('/finished?reason=manual');
  };

  if (!guard.isFullscreen) {
    return (
      <div className="flex h-screen items-center justify-center bg-surface">
        <button onClick={guard.enterFullscreen} className="rounded-xl bg-primary px-8 py-4 font-semibold text-on-primary shadow-lg">
          Lanjutkan Ujian (Mode Layar Penuh)
        </button>
      </div>
    );
  }

  const timerClass =
    guard.remainingSeconds < 120
      ? 'bg-error text-on-error animate-pulse font-inter tabular-nums'
      : guard.remainingSeconds < 600
        ? 'bg-secondary-fixed text-on-secondary-fixed font-inter tabular-nums'
        : 'bg-surface-container text-on-surface font-inter tabular-nums';

  const answeredCount = questions.filter((q) => (answers[q.id] ?? []).some((v) => v.trim() !== '')).length;
  const flaggedCount = Object.values(flagged).filter(Boolean).length;
  const emptyCount = questions.length - answeredCount;

  return (
    <div className="flex h-screen flex-col bg-surface">
      <header className="flex flex-wrap items-center justify-between gap-2 border-b border-outline-variant bg-surface-container-lowest px-4 py-3 sm:px-6">
        <span className="font-jakarta font-semibold text-on-surface">{meta.examName}</span>
        <span className={`rounded-full px-4 py-1 text-sm font-bold ${timerClass}`}>
          {Math.floor(guard.remainingSeconds / 60)}:{String(guard.remainingSeconds % 60).padStart(2, '0')}
        </span>
        <span className="w-full text-sm text-on-surface-variant sm:w-auto">
          Pelanggaran: {guard.remainingViolations} tersisa
        </span>
      </header>

      <div className="flex flex-1 overflow-hidden">
        <main className="flex-1 overflow-y-auto p-4 sm:p-6 lg:w-[70%]">
          <p className="mb-4 font-inter text-sm text-on-surface-variant">
            Soal {currentIndex + 1} dari {questions.length} — {answeredCount} terjawab, {flaggedCount} ragu
          </p>
          <p className="mb-4 font-jakarta text-lg font-medium text-on-surface">{currentQuestion.question_text}</p>
          {currentQuestion.question_image && (
            <img src={currentQuestion.question_image} alt="Gambar soal" className="mb-4 max-h-72 rounded-lg border border-outline-variant" />
          )}

          {currentQuestion.type === 'essay' ? (
            <textarea
              value={(answers[currentQuestion.id] ?? [''])[0] ?? ''}
              onChange={(e) => handleEssayChange(e.target.value)}
              rows={6}
              placeholder="Tulis jawaban uraian di sini..."
              className="w-full rounded-lg border border-outline-variant bg-surface-container-lowest p-3 font-inter text-sm focus:border-primary focus:outline-none"
            />
          ) : (
            currentQuestion.options?.map((opt) => {
              const checked = (answers[currentQuestion.id] ?? []).includes(opt.key);
              return (
                <label
                  key={opt.key}
                  className={`mb-3 flex cursor-pointer items-center gap-3 rounded-lg border p-3.5 ${checked ? 'border-primary bg-primary-fixed/30' : 'border-outline-variant bg-surface-container-lowest hover:bg-surface-container-low'}`}
                >
                  <input
                    type={currentQuestion.type === 'multiple_choice' ? 'checkbox' : 'radio'}
                    checked={checked}
                    onChange={() => handleSelectOption(opt.key)}
                    className="accent-primary"
                  />
                  <span className={`flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold ${checked ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface-variant'}`}>{opt.key}</span>
                  {opt.text}
                </label>
              );
            })
          )}

          <p className="mt-2 text-xs text-on-surface-variant">
            {savingStatus[currentQuestion.id] === 'saving' && 'Menyimpan...'}
            {savingStatus[currentQuestion.id] === 'saved' && 'Tersimpan ✓'}
            {savingStatus[currentQuestion.id] === 'error' && 'Gagal menyimpan, akan dicoba lagi'}
            {isFlagged && <span className="ml-2 rounded bg-secondary-fixed px-2 py-0.5 text-xs font-semibold text-on-secondary-fixed">Ragu-ragu</span>}
            {hasAnswer && !isFlagged && <span className="ml-2 rounded bg-tertiary px-2 py-0.5 text-xs text-white">Terjawab</span>}
          </p>

          <div className="mt-6 flex gap-2">
            <button
              onClick={() => toggleFlag(currentQuestion.id)}
              className={`rounded-lg border px-4 py-2 text-sm font-semibold ${isFlagged ? 'border-secondary bg-secondary-fixed text-on-secondary-fixed' : 'border-outline-variant text-on-surface-variant'}`}
            >
              {isFlagged ? 'Batal Ragu' : 'Ragu-ragu'}
            </button>
          </div>
        </main>

        {/* Navigator — desktop */}
        <aside className="hidden w-[30%] min-w-[320px] max-w-[400px] overflow-y-auto border-l border-outline-variant bg-surface-container-lowest p-4 lg:block">
          <p className="mb-3 font-jakarta text-sm font-semibold text-on-surface">Navigasi Soal</p>
          <QuestionNavigator />
        </aside>
      </div>

      <footer className="flex items-center justify-between gap-2 border-t border-outline-variant bg-surface-container-lowest p-4">
        <button
          disabled={currentIndex === 0}
          onClick={() => goToQuestion(currentIndex - 1)}
          className="rounded-lg border border-outline-variant px-4 py-2 text-sm disabled:opacity-50"
        >
          Sebelumnya
        </button>

        {/* Di mobile sidebar disembunyikan, jadi navigasi harus punya pintu
            masuk sendiri — tanpa ini tidak ada cara lompat soal di ponsel. */}
        <button
          onClick={() => setShowNavigator((v) => !v)}
          className="rounded-lg border border-outline-variant px-3 py-2 text-sm font-semibold text-on-surface lg:hidden"
          aria-expanded={showNavigator}
        >
          Soal {currentIndex + 1}/{questions.length}
        </button>

        {currentIndex < questions.length - 1 ? (
          <button
            onClick={() => goToQuestion(currentIndex + 1)}
            className="rounded-lg bg-primary px-4 py-2 text-sm text-on-primary"
          >
            Selanjutnya
          </button>
        ) : (
          <button
            onClick={() => setShowFinishModal(true)}
            className="rounded-lg bg-tertiary px-4 py-2 text-sm font-semibold text-white"
          >
            Selesai Ujian
          </button>
        )}
      </footer>

      {/* Navigator — mobile */}
      {showNavigator && (
        <div className="fixed inset-0 z-40 flex flex-col bg-surface lg:hidden">
          <div className="flex items-center justify-between border-b border-outline-variant bg-surface-container-lowest px-4 py-3">
            <p className="font-jakarta font-semibold text-on-surface">Navigasi Soal</p>
            <button
              onClick={() => setShowNavigator(false)}
              className="rounded-lg border border-outline-variant px-3 py-1.5 text-sm text-on-surface-variant"
            >
              Tutup
            </button>
          </div>
          <div className="flex-1 overflow-y-auto p-4">
            <QuestionNavigator onSelect={() => setShowNavigator(false)} />
          </div>
        </div>
      )}

      {/* Modal Selesaikan */}
      {showFinishModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="w-full max-w-md rounded-xl bg-surface-container-lowest p-6 shadow-xl">
            <h3 className="font-jakarta text-lg font-semibold text-on-surface">Selesaikan Ujian?</h3>
            <p className="mt-2 font-inter text-sm text-on-surface-variant">
              {emptyCount} soal kosong, {flaggedCount} ragu, {answeredCount} terjawab dari {questions.length} soal.
            </p>
            <p className="mt-2 font-inter text-xs text-on-surface-variant">Jawaban tidak bisa diubah lagi setelah diselesaikan.</p>
            <div className="mt-6 flex justify-end gap-2">
              <button onClick={() => setShowFinishModal(false)} className="rounded-lg border border-outline-variant px-4 py-2 text-sm">Batal</button>
              <button onClick={confirmFinish} className="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-on-primary">Ya, Selesaikan</button>
            </div>
          </div>
        </div>
      )}

      {guard.showViolationWarning && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
          <div className="rounded bg-white p-6 text-center">
            <p className="mb-4 font-semibold">Pelanggaran terdeteksi: {guard.lastViolationType}</p>
            <button onClick={() => { guard.dismissWarning(); guard.enterFullscreen(); }} className="rounded bg-red-600 px-4 py-2 text-white">
              Kembali ke Ujian
            </button>
          </div>
        </div>
      )}
    </div>
  );
}

function toggleMultiple(current: string[], key: string): string[] {
  return current.includes(key) ? current.filter((k) => k !== key) : [...current, key];
}
