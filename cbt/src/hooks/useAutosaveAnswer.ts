import { useCallback, useRef } from 'react';
import api from '../services/api';
import { useExamStore } from '../store/examStore';

export function useAutosaveAnswer(examSessionId: number) {
  const setSavingStatus = useExamStore((s) => s.setSavingStatus);
  const timers = useRef<Record<number, ReturnType<typeof setTimeout>>>({});
  const pending = useRef<Record<number, string[]>>({});

  const postWithRetry = useCallback(
    async (questionId: number, answer: string[]) => {
      const MAX_ATTEMPTS = 3;
      for (let attempt = 1; attempt <= MAX_ATTEMPTS; attempt++) {
        try {
          await api.post('/exam/answer', {
            exam_session_id: examSessionId,
            exam_question_id: questionId,
            answer,
          });
          return true;
        } catch {
          if (attempt === MAX_ATTEMPTS) return false;
        }
      }
      return false;
    },
    [examSessionId]
  );

  const saveAnswer = useCallback(
    (questionId: number, answer: string[]) => {
      if (timers.current[questionId]) clearTimeout(timers.current[questionId]);
      pending.current[questionId] = answer;
      setSavingStatus(questionId, 'saving');

      timers.current[questionId] = setTimeout(async () => {
        const payload = pending.current[questionId];
        const ok = await postWithRetry(questionId, payload);
        if (ok) {
          setSavingStatus(questionId, 'saved');
          delete pending.current[questionId];
        } else {
          setSavingStatus(questionId, 'error');
        }
        delete timers.current[questionId];
      }, 800);
    },
    [examSessionId, setSavingStatus, postWithRetry]
  );

  const flush = useCallback(async () => {
    const entries = Object.entries(pending.current);
    if (entries.length === 0) return;
    // Batalkan timer & kirim langsung
    Object.values(timers.current).forEach(clearTimeout);
    timers.current = {};
    await Promise.all(
      entries.map(async ([qid, answer]) => {
        const ok = await postWithRetry(Number(qid), answer);
        if (ok) {
          setSavingStatus(Number(qid), 'saved');
          delete pending.current[Number(qid)];
        } else {
          setSavingStatus(Number(qid), 'error');
        }
      })
    );
  }, [examSessionId, setSavingStatus, postWithRetry]);

  return { saveAnswer, flush };
}
