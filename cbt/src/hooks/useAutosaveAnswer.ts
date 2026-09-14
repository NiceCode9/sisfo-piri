import { useCallback, useRef } from 'react';
import api from '../services/api';
import { useExamStore } from '../store/examStore';

export function useAutosaveAnswer(examSessionId: number) {
  const setSavingStatus = useExamStore((s) => s.setSavingStatus);
  const timers = useRef<Record<number, ReturnType<typeof setTimeout>>>({});

  const saveAnswer = useCallback(
    (questionId: number, answer: string[]) => {
      if (timers.current[questionId]) clearTimeout(timers.current[questionId]);

      setSavingStatus(questionId, 'saving');

      timers.current[questionId] = setTimeout(async () => {
        try {
          await api.post('/exam/answer', {
            exam_session_id: examSessionId,
            exam_question_id: questionId,
            answer,
          });
          setSavingStatus(questionId, 'saved');
        } catch {
          setSavingStatus(questionId, 'error');
        }
      }, 800);
    },
    [examSessionId, setSavingStatus]
  );

  return { saveAnswer };
}
