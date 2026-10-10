import api from './api';

export type ViolationType =
  | 'fullscreen_exit'
  | 'tab_blur'
  | 'visibility_hidden'
  | 'devtools_suspected'
  | 'copy_paste_attempt'
  | 'connection_lost';

/**
 * Antrean pelanggaran yang gagal terkirim.
 *
 * Versi lama memanggil reportViolation secara fire-and-forget: saat request
 * gagal, error ditelan tetapi counter lokal tetap naik. Akibatnya penghitung
 * siswa dan `violation_count` di server berbeda, dan batas
 * `max_violation_count` bisa terlewati tanpa jejak.
 */
const pendingViolations: { sessionId: number; type: ViolationType }[] = [];
let flushing = false;

async function flushPendingViolations(): Promise<void> {
  if (flushing || pendingViolations.length === 0) return;
  flushing = true;

  try {
    while (pendingViolations.length > 0) {
      const item = pendingViolations[0];
      await api.post('/exam/violation', {
        exam_session_id: item.sessionId,
        type: item.type,
      });
      pendingViolations.shift();
    }
  } catch {
    // Masih gagal — sisanya tetap antre, dicoba lagi pada kejadian berikutnya.
  } finally {
    flushing = false;
  }
}

export const reportViolation = (
  examSessionId: number,
  type: ViolationType,
  meta?: Record<string, unknown>
) => {
  return api
    .post('/exam/violation', {
      exam_session_id: examSessionId,
      type,
      meta,
      occurred_at: new Date().toISOString(),
    })
    .then(async (res) => {
      // Antrean tertinggal bisa terkirim sekarang juga.
      void flushPendingViolations();
      return res;
    })
    .catch(async () => {
      // Simpan untuk dicoba lagi; pemanggil menyelaraskan penghitung dari
      // respons server, bukan menebak sendiri.
      pendingViolations.push({ sessionId: examSessionId, type });
      void flushPendingViolations();
      throw new Error('violation-queued');
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
