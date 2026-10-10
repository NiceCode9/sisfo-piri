import { useExamStore } from '../store/examStore';

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

  const cellClass = (isFlagged: boolean, isAnswered: boolean, isActive: boolean) => {
    if (isActive) return 'bg-primary text-on-primary border-primary';
    if (isFlagged) return 'bg-secondary-fixed text-on-secondary-fixed border-secondary';
    if (isAnswered) return 'bg-tertiary text-white border-tertiary';

    return 'bg-surface-container border-outline-variant text-on-surface-variant';
  };

  return (
    <>
      <div className="grid grid-cols-5 gap-2">
        {questions.map((q, idx) => {
          const isAnswered = (answers[q.id] ?? []).some((v) => v.trim() !== '');
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
              className={`flex h-10 w-10 items-center justify-center rounded-lg border text-xs font-bold ${cellClass(
                isFlagged,
                isAnswered,
                isActive,
              )}`}
              title={`Soal ${idx + 1}${isFlagged ? ' — Ragu' : isAnswered ? ' — Terjawab' : ' — Kosong'}`}
            >
              {idx + 1}
            </button>
          );
        })}
      </div>

      <div className="mt-4 flex flex-wrap gap-3 text-xs">
        <span className="flex items-center gap-1">
          <span className="h-3 w-3 rounded bg-tertiary" /> Terjawab
        </span>
        <span className="flex items-center gap-1">
          <span className="h-3 w-3 rounded bg-secondary-fixed" /> Ragu
        </span>
        <span className="flex items-center gap-1">
          <span className="h-3 w-3 rounded border border-outline-variant bg-surface-container" /> Kosong
        </span>
      </div>
    </>
  );
}