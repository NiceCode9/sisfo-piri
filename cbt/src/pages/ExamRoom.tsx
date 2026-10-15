import { useState } from 'react';
import { useExamGuard } from '../hooks/useExamGuard';
import { useAutosaveAnswer } from '../hooks/useAutosaveAnswer';
import { useQuestionImage } from '../hooks/useQuestionImage';
import { useExamStore } from '../store/examStore';
import QuestionNavigator from '../components/QuestionNavigator';
import Icon from '../components/Icon';
import { finishExam } from '../services/examApi';
import { useNavigate } from 'react-router-dom';
import type { ExamMeta, Question } from '../store/examStore';

/** Tingkat ukuran teks soal - untuk siswa dengan kebutuhan penglihatan berbeda. */
type FontTier = 'normal' | 'large' | 'xlarge';

const STEM_SIZE: Record<FontTier, string> = {
  normal: 'text-body-md',
  large: 'text-body-lg',
  xlarge: 'text-[20px] leading-[32px]',
};

const QUESTION_SIZE: Record<FontTier, string> = {
  normal: 'text-headline-sm',
  large: 'text-[22px] leading-[30px]',
  xlarge: 'text-headline-md',
};

const OPTION_SIZE: Record<FontTier, string> = {
  normal: 'text-body-md',
  large: 'text-body-lg',
  xlarge: 'text-[20px] leading-[30px]',
};

/** 02:05:30 / 45:07 - detik tetap dua digit supaya tidak "bergeser" tiap tick. */
function formatTime(totalSeconds: number) {
  const h = Math.floor(totalSeconds / 3600);
  const m = Math.floor((totalSeconds % 3600) / 60);
  const s = totalSeconds % 60;
  const pad = (n: number) => String(n).padStart(2, '0');
  return h > 0 ? `${h}:${pad(m)}:${pad(s)}` : `${m}:${pad(s)}`;
}

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
  const [fontTier, setFontTier] = useState<FontTier>('normal');

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
  const gambarSoal = useQuestionImage(currentQuestion?.question_image);
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

  const answeredCount = questions.filter((q) => (answers[q.id] ?? []).some((v) => v.trim() !== '')).length;
  const flaggedCount = Object.values(flagged).filter(Boolean).length;
  const emptyCount = questions.length - answeredCount;

  // --- Gate layar penuh: harus tetap full-width, hanya tampilannya yang dirapikan ---
  if (!guard.isFullscreen) {
    return (
      <div className="flex h-screen flex-col items-center justify-center gap-space-lg bg-surface px-gutter text-center">
        <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-container/10">
          <Icon name="fullscreen" size={32} className="text-primary-container" />
        </div>
        <div>
          <h1 className="font-headline-md text-headline-md text-on-surface">Ujian Memerlukan Mode Layar Penuh</h1>
          <p className="mt-space-sm font-body-sm text-body-sm text-on-surface-variant">
            Aktifkan layar penuh untuk memulai. Keluar dari mode ini selama ujian tercatat sebagai pelanggaran.
          </p>
        </div>
        <button
          onClick={guard.enterFullscreen}
          className="flex items-center gap-space-sm rounded-lg bg-primary px-space-lg py-3.5 font-headline-sm text-headline-sm text-on-primary shadow-md transition-all hover:bg-primary-container active:scale-[0.99]"
        >
          <Icon name="fullscreen" size={22} />
          <span>Mulai Ujian</span>
        </button>
      </div>
    );
  }

  const timerTone =
    guard.remainingSeconds < 120
      ? 'border-error bg-error-container text-error'
      : guard.remainingSeconds < 600
        ? 'border-secondary-fixed-dim bg-secondary-fixed/60 text-on-secondary-fixed'
        : 'border-outline-variant bg-surface-container-low text-on-surface';

  const saveStatus = savingStatus[currentQuestion.id];
  const studentName = localStorage.getItem('cbt_user_name');

  return (
    <div className="flex h-screen flex-col bg-surface">
      {/* ============ HEADER ============ */}
      <header className="z-20 flex flex-wrap items-center justify-between gap-x-space-md gap-y-2 border-b border-surface-container-high bg-surface-container-lowest px-gutter py-2.5">
        <div className="flex min-w-0 items-center gap-space-md">
          <div className="flex shrink-0 items-center gap-space-sm">
            <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-on-primary">
              <Icon name="verified" size={18} />
            </span>
            <span className="hidden font-headline-sm text-headline-sm tracking-tight text-primary sm:inline">
              Ujian CBT
            </span>
          </div>
          <div className="hidden items-center gap-1.5 rounded-full bg-primary-fixed px-3 py-1 text-on-primary-fixed lg:flex">
            <Icon name="format_list_numbered" size={18} />
            <span className="font-label-md text-label-md">
              Nomor {currentIndex + 1} dari {questions.length}
            </span>
          </div>
          {/*
            Di bawah breakpoint lg sidebar disembunyikan, jadi navigasi soal
            harus punya pintu masuk sendiri — tanpa tombol ini tidak ada cara
            sama sekali melompat soal di ponsel/tablet. Ini jawaban atas
            tombol "Soal X/Y" yang dulu ada di footer.
          */}
          <button
            type="button"
            onClick={() => setShowNavigator(true)}
            aria-label="Buka daftar soal"
            className="flex items-center gap-1.5 rounded-full bg-primary-fixed px-3 py-1 font-label-md text-label-md text-on-primary-fixed lg:hidden"
          >
            <Icon name="apps" size={18} />
            {currentIndex + 1}/{questions.length}
          </button>
        </div>

        <div className="flex items-center gap-space-md">
          {/* Status simpan — umpan balik satu-satunya bahwa jawaban aman di server */}
          <span className="hidden items-center gap-1 font-label-sm text-label-sm text-on-surface-variant md:inline-flex">
            {saveStatus === 'saving' && (
              <>
                <Icon name="progress_activity" size={15} className="animate-spin text-primary" />
                Menyimpan...
              </>
            )}
            {saveStatus !== 'saving' && saveStatus === 'error' && (
              <>
                <Icon name="cloud_off" size={15} className="text-error" />
                <span className="text-error">Gagal menyimpan, mengulang...</span>
              </>
            )}
            {saveStatus !== 'error' && saveStatus !== 'saving' && hasAnswer && (
              <>
                <Icon name="check_circle" size={15} className="text-tertiary" />
                Tersimpan otomatis
              </>
            )}
          </span>

          {/* Countdown */}
          <div
            className={`flex items-center gap-2 rounded-lg border px-3 py-1.5 ${timerTone} ${
              guard.remainingSeconds < 120 ? 'animate-pulse' : ''
            }`}
          >
            <Icon name="schedule" size={20} />
            <div className="flex flex-col leading-none">
              <span className="font-label-sm text-label-sm uppercase tracking-wider opacity-70">Sisa Waktu</span>
              <span className="tabular-nums font-label-timer text-label-timer">
                {formatTime(guard.remainingSeconds)}
              </span>
            </div>
          </div>

          {studentName && (
            <span className="hidden max-w-[140px] truncate font-label-sm text-label-sm text-on-surface-variant xl:inline">
              {studentName}
            </span>
          )}
        </div>
      </header>

      <div className="flex flex-1 overflow-hidden">
        {/* ============ KOLOM SOAL ============ */}
        <main className="flex-1 overflow-y-auto">
          <div className="mx-auto flex w-full max-w-[1180px] flex-col gap-space-md px-gutter py-space-md">
            {/* Bar kendali: posisi soal + font resizer */}
            <div className="flex flex-wrap items-center justify-between gap-space-sm rounded-xl bg-surface-container-lowest px-space-md py-3 shadow-sm">
              <div className="flex flex-wrap items-center gap-space-sm">
                <span className="inline-flex max-w-[300px] items-center gap-1.5 truncate rounded-full bg-surface-container-low px-3 py-1 font-label-sm text-label-sm font-semibold text-primary">
                  <Icon name="menu_book" size={15} />
                  {meta.examName}
                </span>
                {currentQuestion.type === 'multiple_choice' && (
                  <span className="inline-flex items-center gap-1 rounded-full bg-surface-container-low px-3 py-1 font-label-sm text-label-sm text-on-surface-variant">
                    <Icon name="checklist" size={15} />
                    Pilih lebih dari satu
                  </span>
                )}
              </div>

              <div className="flex items-center gap-2 rounded-lg bg-surface-container-low px-2 py-1">
                <span className="hidden font-label-sm text-label-sm text-on-surface-variant xl:inline">Ukuran Font:</span>
                {(
                  [
                    ['normal', 'Normal'],
                    ['large', 'Besar'],
                    ['xlarge', 'Sangat Besar'],
                  ] as [FontTier, string][]
                ).map(([tier, label]) => (
                  <button
                    key={tier}
                    type="button"
                    onClick={() => setFontTier(tier)}
                    aria-pressed={fontTier === tier}
                    className={`rounded px-2.5 py-1 font-label-sm text-label-sm transition-all ${
                      fontTier === tier
                        ? 'bg-surface-container-lowest font-bold text-primary shadow-sm'
                        : 'text-on-surface-variant hover:text-on-surface'
                    }`}
                  >
                    {label}
                  </button>
                ))}
              </div>
            </div>

            {/* Kartu soal */}
            <div className="flex flex-col gap-space-lg rounded-xl bg-surface-container-lowest p-space-lg shadow-sm">
              <div className="flex flex-col gap-space-md">
                {gambarSoal && (
                  <img
                    src={gambarSoal}
                    alt={`Gambar soal ${currentIndex + 1}`}
                    className="max-h-80 w-full rounded-xl border border-surface-container-high bg-surface-container-lowest object-contain"
                  />
                )}
                {/*
                  Hanya ada SATU field teks di kontrak API (`question_text`), jadi
                  teks tidak boleh dirender dua kali. Kalau soal punya gambar,
                  gambar diperlakukan sebagai stimulus dan teksnya turun ke bawah sebagai
                  pertanyaan dengan hierarki tipografi yang lebih besar; tanpa
                  gambar, teks langsung tampil sebagai stem.
                */}
                {gambarSoal && <div className="h-px w-full bg-surface-container-high" />}
                <p className={`font-jakarta text-on-surface transition-all duration-150 ${gambarSoal ? QUESTION_SIZE[fontTier] : STEM_SIZE[fontTier]}`}>
                  {currentQuestion.question_text}
                </p>
              </div>

              {/* Pilihan jawaban */}
              <div className="flex flex-col gap-space-sm">

                {currentQuestion.type === 'essay' ? (
                  <textarea
                    value={(answers[currentQuestion.id] ?? [''])[0] ?? ''}
                    onChange={(e) => handleEssayChange(e.target.value)}
                    rows={8}
                    placeholder="Tulis jawaban uraian di sini..."
                    className={`w-full rounded-lg border border-outline-variant bg-surface-container-lowest p-3 font-jakarta text-on-surface transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary ${STEM_SIZE[fontTier]}`}
                  />
                ) : (
                  <fieldset className="flex flex-col gap-3">
                    <legend className="sr-only">Pilihan jawaban soal {currentIndex + 1}</legend>
                    {currentQuestion.options?.map((opt) => {
                      const checked = (answers[currentQuestion.id] ?? []).includes(opt.key);
                      return (
                        <label
                          key={opt.key}
                          className={`group relative flex cursor-pointer items-start rounded-xl p-3.5 transition-all sm:p-4 ${
                            checked
                              ? 'bg-primary-fixed/30 shadow-sm'
                              : 'bg-surface-container-lowest hover:bg-surface-container-low'
                          }`}
                        >
                          <input
                            type={currentQuestion.type === 'multiple_choice' ? 'checkbox' : 'radio'}
                            checked={checked}
                            onChange={() => handleSelectOption(opt.key)}
                            className="peer sr-only"
                          />
                          {/* Input-nya sr-only, jadi fokus keyboard tidak terlihat
                              tanpa perlakuan tambahan di sini. */}
                          <span className="pointer-events-none absolute inset-0 hidden rounded-xl ring-2 ring-primary ring-offset-2 ring-offset-surface-container-lowest peer-focus-visible:block" />

                          <span
                            className={`mr-3.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full font-label-md text-label-md transition-colors ${
                              checked
                                ? 'bg-primary text-on-primary shadow-sm'
                                : 'bg-surface-container text-on-surface-variant group-hover:bg-primary-fixed group-hover:text-on-primary-fixed'
                            }`}
                          >
                            {opt.key}
                          </span>

                          <span
                            className={`flex-1 font-jakarta leading-normal text-on-surface ${
                              checked ? 'font-medium' : ''
                            } ${OPTION_SIZE[fontTier]}`}
                          >
                            {opt.text}
                          </span>

                          {checked && (
                            <span className="ml-3 flex shrink-0 items-center justify-center text-primary">
                              <Icon name="check_circle" size={24} />
                            </span>
                          )}
                        </label>
                      );
                    })}
                  </fieldset>
                )}
              </div>

              {/* Badge status soal + action bar */}
              <div className="flex flex-col gap-space-sm border-t border-surface-container-high pt-space-md">
                <div className="flex flex-wrap items-center gap-2">
                  {saveStatus === 'saving' && (
                    <span className="font-label-sm text-label-sm text-on-surface-variant">Menyimpan...</span>
                  )}
                  {saveStatus === 'error' && (
                    <span className="font-label-sm text-label-sm text-error">
                      Gagal menyimpan, akan dicoba lagi
                    </span>
                  )}
                  {isFlagged && (
                    <span className="inline-flex items-center gap-1 rounded-full bg-secondary-fixed px-2.5 py-0.5 font-label-sm text-label-sm font-semibold text-on-secondary-fixed">
                      <Icon name="flag" size={14} filled />
                      Ragu-ragu
                    </span>
                  )}
                  {hasAnswer && !isFlagged && (
                    <span className="inline-flex items-center gap-1 rounded-full bg-tertiary-fixed/40 px-2.5 py-0.5 font-label-sm text-label-sm font-semibold text-tertiary">
                      <Icon name="check_circle" size={14} />
                      Terjawab
                    </span>
                  )}
                </div>

                <div className="flex flex-wrap items-center justify-between gap-space-sm">
                  <button
                    disabled={currentIndex === 0}
                    onClick={() => goToQuestion(currentIndex - 1)}
                    className="flex h-11 items-center gap-2 rounded-lg bg-surface-container-low px-5 font-label-md text-label-md text-on-surface transition-colors hover:bg-surface-container disabled:cursor-not-allowed disabled:opacity-50"
                  >
                    <Icon name="arrow_back" size={20} />
                    <span>Soal Sebelumnya</span>
                  </button>

                  <button
                    onClick={() => toggleFlag(currentQuestion.id)}
                    aria-pressed={isFlagged}
                    className={`flex h-11 select-none items-center gap-2.5 rounded-lg px-4 font-label-md text-label-md font-semibold transition-colors ${
                      isFlagged
                        ? 'bg-secondary-container text-on-secondary-container'
                        : 'bg-secondary-fixed text-on-secondary-fixed hover:bg-secondary-fixed-dim'
                    }`}
                  >
                    <Icon name="flag" size={20} filled={isFlagged} className="text-secondary" />
                    <span>{isFlagged ? 'Batal Ragu' : 'Ragu-ragu'}</span>
                  </button>

                  {currentIndex < questions.length - 1 ? (
                    <button
                      onClick={() => goToQuestion(currentIndex + 1)}
                      className="flex h-11 items-center gap-2 rounded-lg bg-primary px-6 font-label-md text-label-md text-on-primary transition-colors hover:bg-primary-container"
                    >
                      <span>Soal Berikutnya</span>
                      <Icon name="arrow_forward" size={20} />
                    </button>
                  ) : (
                    <button
                      onClick={() => setShowFinishModal(true)}
                      className="flex h-11 items-center gap-2 rounded-lg bg-error px-6 font-label-md text-label-md text-on-error transition-colors hover:bg-error/90"
                    >
                      <Icon name="assignment_turned_in" size={20} />
                      <span>Selesaikan Ujian</span>
                    </button>
                  )}
                </div>
              </div>
            </div>
          </div>
        </main>

        {/* ============ SIDEBAR NAVIGASI ============ */}
        <aside className="hidden w-[340px] shrink-0 flex-col gap-space-md overflow-y-auto border-l border-surface-container-high bg-surface-container-lowest p-space-md lg:flex xl:w-[380px]">
          <div className="flex flex-col gap-space-md rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
            <div className="flex items-center justify-between border-b border-surface-container-high pb-2">
              <div className="flex items-center gap-2">
                <Icon name="apps" size={22} className="text-primary" />
                <h3 className="font-headline-sm text-headline-sm text-on-surface">Daftar Soal</h3>
              </div>
              <span className="font-label-sm text-label-sm font-medium text-on-surface-variant">
                {questions.length} Butir
              </span>
            </div>

            <QuestionNavigator />

            <div className="flex flex-col gap-2 border-t border-surface-container-high pt-space-md">
              <button
                onClick={() => setShowFinishModal(true)}
                className="flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-error font-label-md text-label-md text-on-error transition-all hover:bg-error/90"
              >
                <Icon name="assignment_turned_in" size={20} />
                <span>Selesaikan Ujian</span>
              </button>
              <div className="flex items-center justify-between px-1 font-label-sm text-label-sm text-on-surface-variant">
                <span className="flex items-center gap-1">
                  <Icon name="info" size={14} />
                  {emptyCount > 0 ? `Masih ada ${emptyCount} soal kosong` : 'Semua soal telah dijawab'}
                </span>
                <span className="font-semibold text-primary">Petunjuk</span>
              </div>
            </div>
          </div>

          <div className="flex items-start gap-space-sm rounded-xl bg-surface-container-low p-space-md">
            <Icon name="shield_lock" size={24} className="shrink-0 text-primary" />
            <div className="flex flex-col">
              <span className="font-label-md text-label-md text-on-surface">Protokol Keamanan Aktif</span>
              <span className="font-body-sm text-body-sm leading-snug text-on-surface-variant">
                Dilarang memuat ulang peramban atau meninggalkan jendela ujian. Sistem mendeteksi aktivitas
                ganda secara real-time.
              </span>
            </div>
          </div>
        </aside>
      </div>

      {/* ============ FOOTER ============ */}
      <footer className="flex shrink-0 flex-col items-center justify-between gap-2 border-t border-surface-container-high bg-surface-container-lowest px-gutter py-2 font-label-sm text-label-sm text-on-surface-variant sm:flex-row">
        <span className="truncate">ExamPro Safe Browser Engine</span>
        <span className="flex items-center gap-1.5">
          <Icon name="verified_user" size={16} className="text-tertiary" />
          Sesi Terenkripsi
          {guard.remainingViolations <= 1 && (
            <span className="ml-1 inline-flex items-center gap-1 font-semibold text-error">
              <Icon name="warning" size={14} />
              {guard.remainingViolations} pelanggaran tersisa
            </span>
          )}
          {guard.remainingViolations > 1 && (
            <span className="ml-1">Pelanggaran tersisa: {guard.remainingViolations}</span>
          )}
        </span>
      </footer>

      {/* ============ NAVIGATOR — MOBILE ============ */}
      {/* Di mobile sidebar disembunyikan, jadi navigasi harus punya pintu
          masuk sendiri — tanpa ini tidak ada cara lompat soal di ponsel. */}
      {showNavigator && (
        <div className="fixed inset-0 z-40 flex flex-col bg-surface lg:hidden">
          <div className="flex items-center justify-between border-b border-surface-container-high bg-surface-container-lowest px-space-md py-3">
            <p className="flex items-center gap-2 font-headline-sm text-headline-sm text-on-surface">
              <Icon name="apps" size={22} className="text-primary" />
              Daftar Soal
            </p>
            <button
              onClick={() => setShowNavigator(false)}
              className="flex items-center gap-1 rounded-lg bg-surface-container-low px-3 py-1.5 font-label-md text-label-md text-on-surface-variant"
            >
              <Icon name="close" size={16} />
              Tutup
            </button>
          </div>
          <div className="flex-1 overflow-y-auto p-space-md">
            <div className="rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
              <QuestionNavigator onSelect={() => setShowNavigator(false)} />
            </div>
          </div>
        </div>
      )}

      {/* ============ MODAL SELESAIKAN ============ */}
      {showFinishModal && (
        <div
          role="dialog"
          aria-modal="true"
          aria-labelledby="finish-modal-title"
          className="fixed inset-0 z-50 flex items-center justify-center bg-on-surface/50 p-4 backdrop-blur-sm"
        >
          <div className="flex w-full max-w-md flex-col gap-space-md rounded-2xl bg-surface-container-lowest p-space-lg shadow-xl">
            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-error-container text-error">
              <Icon name="warning" size={28} />
            </div>
            <div className="flex flex-col gap-1 text-center">
              <h3 id="finish-modal-title" className="font-headline-md text-headline-md text-on-surface">
                Akhiri Sesi Ujian?
              </h3>
              <p className="font-body-sm text-body-sm text-on-surface-variant">
                Peringatan: Anda masih memiliki{' '}
                <strong className="font-semibold text-error">{emptyCount} butir soal yang belum dijawab</strong>{' '}
                dan <strong className="font-semibold text-secondary">{flaggedCount} butir ragu-ragu</strong>.
              </p>
            </div>

            <div className="flex justify-around rounded-xl bg-surface-container-low p-3 text-center">
              <div>
                <span className="block font-headline-sm text-headline-sm text-tertiary">{answeredCount}</span>
                <span className="font-label-sm text-label-sm text-on-surface-variant">Terjawab</span>
              </div>
              <div className="my-auto h-8 w-px bg-outline-variant" />
              <div>
                <span className="block font-headline-sm text-headline-sm text-on-secondary-fixed">
                  {flaggedCount}
                </span>
                <span className="font-label-sm text-label-sm text-on-surface-variant">Ragu-ragu</span>
              </div>
              <div className="my-auto h-8 w-px bg-outline-variant" />
              <div>
                <span className="block font-headline-sm text-headline-sm text-error">{emptyCount}</span>
                <span className="font-label-sm text-label-sm text-on-surface-variant">Kosong</span>
              </div>
            </div>

            <p className="text-center font-label-sm text-label-sm text-on-surface-variant">
              Jawaban tidak bisa diubah lagi setelah sesi diselesaikan.
            </p>

            <div className="flex flex-col gap-2 pt-2 sm:flex-row">
              <button
                onClick={() => setShowFinishModal(false)}
                className="h-11 flex-1 rounded-lg bg-surface-container font-label-md text-label-md text-on-surface transition-colors hover:bg-surface-container-high"
              >
                Kembali Mengerjakan
              </button>
              <button
                onClick={confirmFinish}
                className="h-11 flex-1 rounded-lg bg-error font-label-md text-label-md text-on-error shadow-sm transition-colors hover:bg-error/90"
              >
                Ya, Selesaikan
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ============ PERINGATAN PELANGGARAN ============ */}
      {guard.showViolationWarning && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-on-surface/50 p-4 backdrop-blur-sm">
          <div className="flex w-full max-w-sm flex-col items-center gap-space-md rounded-2xl bg-surface-container-lowest p-space-lg text-center shadow-xl">
            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-error-container text-error">
              <Icon name="gpp_maybe" size={28} />
            </div>
            <div className="flex flex-col gap-1">
              <h3 className="font-headline-md text-headline-md text-on-surface">Pelanggaran Terdeteksi</h3>
              <p className="font-body-sm text-body-sm text-on-surface-variant">
                Aktivitas{' '}
                <strong className="font-semibold text-error">
                  {(guard.lastViolationType && VIOLATION_LABELS[guard.lastViolationType]) || 'tidak dikenali'}
                </strong>{' '}
                tercatat oleh sistem.
              </p>
              <p className="font-body-sm text-body-sm text-on-surface-variant">
                Sisa peluang sebelum ujian dihentikan otomatis:{' '}
                <strong className="font-semibold text-error">{guard.remainingViolations}</strong>
              </p>
            </div>
            <button
              onClick={() => {
                guard.dismissWarning();
                guard.enterFullscreen();
              }}
              className="flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-space-lg py-3 font-label-md text-label-md text-on-primary transition-colors hover:bg-primary-container"
            >
              <Icon name="fullscreen" size={20} />
              <span>Kembali ke Ujian</span>
            </button>
          </div>
        </div>
      )}
    </div>
  );
}

/** Label ramah untuk UI - value API dibiarkan apa adanya. */
const VIOLATION_LABELS: Record<string, string> = {
  fullscreen_exit: 'keluar dari mode layar penuh',
  visibility_hidden: 'jendela ujian disembunyikan',
  tab_blur: 'perpindahan fokus jendela',
  copy_paste_attempt: 'percobaan salin / tempel',
  devtools_suspected: 'perkembangan developer tools',
  connection_lost: 'koneksi ke server terputus',
};

function toggleMultiple(current: string[], key: string): string[] {
  return current.includes(key) ? current.filter((k) => k !== key) : [...current, key];
}