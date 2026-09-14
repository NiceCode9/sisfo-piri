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

export const finishExam = (examSessionId: number, reason: 'manual' | 'time_up' | 'violation_limit') => {
  return api.post('/exam/finish', { exam_session_id: examSessionId, finish_reason: reason });
};
