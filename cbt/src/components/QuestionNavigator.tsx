import { useExamStore } from '../store/examStore';
import Icon from './Icon';

interface Props {
  onSelect?: (index: number) => void;
}

/**
 * Matriks navigasi soal. Dipakai dua kali: sebagai sidebar di desktop dan
 * sebagai panel layar penuh di mobile. Versi lama hanya ada sebagai
 * `hidden lg:block`, sehingga di ponsel tidak ada cara sama sekali berpindah
 * soal selain tombol Sebelumnya/S Holidays — untuk ujian 40 soal itu tidak
 * bisa dipakai.
 */
export default function QuestionNavigator({ onSelect }: Props) {
  const questions = useExamStore((s) => s.questions);
  const answers = useExamStore((s) => s.answers);
  const flagged = useExamStore((s) => s.flagged);
  const currentIndex = useExamStore((s) => s.currentIndex);
  const goToQuestion = useExamStore((s) => s.goToQuestion);

  const isAnswered = (id: number) => (answers[id] ?? []).some((v) => v.trim() !== '');
  const answeredCount = questions.filter((q) => isAnswered(q.id)).length;
  const flaggedCount = questions.filter((q) => flagged[q.id]).length;
  const emptyCount = questions.length - answeredCount;

  return (
    <div className="flex flex-col gap-space-md">
      {/* Ringkasan status — memberi angka absolut sebelum siswa mulai
          menekan nomor satu per satu. */}
      <div className="grid grid-cols-3 gap-2 text-center">
        <div className="flex flex-col items-center rounded-lg bg-tertiary-fixed/30 px-1 py-2">
          <span className="font-headline-sm text-headline-sm leading-none text-tertiary">{answeredCount}</span>
          <span className="mt-1 font-label-sm text-label-sm font-semibold text-tertiary">Terjawab</span>
        </div>
        <div className="flex flex-col items-center rounded-lg bg-secondary-fixed/50 px-1 py-2">
          <span className="font-headline-sm text-headline-sm leading-none text-on-secondary-fixed">{flaggedCount}</span>
          <span className="mt-1 font-label-sm text-label-sm font-semibold text-on-secondary-fixed">Ragu-ragu</span>
        </div>
        <div className="flex flex-col items-center rounded-lg bg-surface-container px-1 py-2">
          <span className="font-headline-sm text-headline-sm leading-none text-on-surface-variant">{emptyCount}</span>
          <span className="mt-1 font-label-sm text-label-sm font-semibold text-on-surface-variant">Kosong</span>
        </div>
      </div>

      <div className="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 px-1 font-label-sm text-label-sm text-on-surface-variant">
        <span className="flex items-center gap-1.5">
          <span className="inline-block h-3 w-3 rounded bg-tertiary" />
          Terjawab
        </span>
        <span className="flex items-center gap-1.5">
          <span className="inline-block h-3 w-3 rounded bg-secondary-container" />
          Ragu-ragu
        </span>
        <span className="flex items-center gap-1.5">
          <span className="inline-block h-3 w-3 rounded bg-surface-container" />
          Belum
        </span>
      </div>

      <div
        aria-label="Nomor pilihan soal"
        role="group"
        className="grid max-h-[380px] grid-cols-5 gap-2 overflow-y-auto pr-1"
      >
        {questions.map((q, idx) => {
          const answered = isAnswered(q.id);
          const isFlagged = !!flagged[q.id];
          const isActive = idx === currentIndex;

          return (
            <button
              key={q.id}
              type="button"
              onClick={() => {
                goToQuestion(idx);
                onSelect?.(idx);
              }}
              aria-current={isActive ? 'true' : undefined}
              className={`relative flex h-10 items-center justify-center rounded-lg font-label-grid-num text-label-grid-num transition-opacity hover:opacity-90 ${
                isActive
                  ? 'bg-primary-fixed font-bold text-on-primary-fixed shadow-sm'
                  : isFlagged
                    ? 'bg-secondary-container text-on-secondary-container'
                    : answered
                      ? 'bg-tertiary text-on-tertiary'
                      : 'bg-surface-container text-on-surface-variant transition-colors hover:bg-surface-container-high'
              }`}
              title={`Soal ${idx + 1}${isFlagged ? ' — Ragu-ragu' : answered ? ' — Terjawab' : ' — Kosong'}`}
            >
              {idx + 1}
              {/* Penanda ragu-ragu dan soal aktif dibuat sebagai glyph di atas
                  tile, bukan warna latar saja: kalau siswanya buta warna,
                  status tetap harus terbaca dari bentuk juga. */}
              {isFlagged && (
                <Icon name="flag" size={10} filled className="absolute right-1 top-1" />
              )}
              {isActive && <span className="absolute -right-1 -top-1 h-2.5 w-2.5 rounded-full bg-primary" />}
            </button>
          );
        })}
      </div>
    </div>
  );
}