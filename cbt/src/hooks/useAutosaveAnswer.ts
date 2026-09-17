import { useCallback, useRef } from 'react';
import api from '../services/api';
import { useExamStore } from '../store/examStore';

export function useAutosaveAnswer(examSessionId: number) {
  const setSavingStatus = useExamStore((s) => s.setSavingStatus);
  const timers = useRef<Record<number, ReturnType<typeof setTimeout>>>({});
  const pending = useRef<Record<number, string[]>>({});

  const saveAnswer = useCallback(
    (questionId: number, answer: string[]) => {
      if (timers.current[questionId]) clearTimeout(timers.current[questionId]);
      pending.current[questionId] = answer;
      setSavingStatus(questionId, 'saving');

      timers.current[questionId] = setTimeout(async () => {
        const payload = pending.current[questionId];
        try {
          await api.post('/exam/answer', {
            exam_session_id: examSessionId,
            exam_question_id: questionId,
            answer: payload,
          });
          setSavingStatus(questionId, 'saved');
          delete pending.current[questionId];
        } catch {
          setSavingStatus(questionId, 'error');
        } finally {
          delete timers.current[questionId];
        }
      }, 800);
    },
    [examSessionId, setSavingStatus]
  );

  const flush = useCallback(async () => {
    const entries = Object.entries(pending.current);
    if (entries.length === 0) return;
    // Batalkan timer & kirim langsung
    Object.values(timers.current).forEach(clearTimeout);
    timers.current = {};
    await Promise.all(
      entries.map(async ([qid, answer]) => {
        try {
          await api.post('/exam/answer', {
            exam_session_id: examSessionId,
            exam_question_id: Number(qid),
            answer,
          });
          setSavingStatus(Number(qid), 'saved');
        } catch {
          setSavingStatus(Number(qid), 'error');
        }
      })
    );
    pending.current = {};
  }, [examSessionId, setSavingStatus]);

  return { saveAnswer, flush };
}
