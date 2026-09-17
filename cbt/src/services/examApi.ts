import api from './api';

export const reportViolation = (
  examSessionId: number,
  type: 'fullscreen_exit' | 'tab_blur' | 'visibility_hidden' | 'devtools_suspected' | 'copy_paste_attempt' | 'connection_lost',
  meta?: Record<string, unknown>
) => {
  return api.post('/exam/violation', {
    exam_session_id: examSessionId,
    type,
    meta,
    occurred_at: new Date().toISOString(),
  });
};

export const sendHeartbeat = (examSessionId: number) => {
  return api.post('/exam/heartbeat', { exam_session_id: examSessionId });
};

async function withRetry<T>(fn: () => Promise<T>, retries = 2, delayMs = 800): Promise<T> {
  let lastErr: unknown;
  for (let i = 0; i <= retries; i++) {
    try {
      return await fn();
    } catch (e) {
      lastErr = e;
      if (i < retries) await new Promise((r) => setTimeout(r, delayMs * (i + 1)));
    }
  }
  throw lastErr;
}

export const finishExam = (examSessionId: number, reason: 'manual' | 'time_up' | 'violation_limit') => {
  return withRetry(() => api.post('/exam/finish', { exam_session_id: examSessionId, finish_reason: reason }));
};
