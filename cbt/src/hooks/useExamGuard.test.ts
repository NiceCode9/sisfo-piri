import { describe, expect, it, vi, beforeEach } from 'vitest';
import { renderHook, act, waitFor } from '@testing-library/react';

import { useExamGuard } from './useExamGuard';
import * as examApi from '../services/examApi';

/**
 * Mock mencerminkan ViolationController: `connection_lost` dicatat tapi TIDAK
 * menaikkan `violation_count`, jadi responsnya harus mengembalikan angka yang
 * tidak berubah. Kalau mock selalu mengembalikan 1, test tidak bisa membedakan
 * perilaku yang benar dari yang salah.
 */
let serverViolationCount = 0;

function violationResponse(type: string) {
  if (type !== 'connection_lost') serverViolationCount += 1;

  return {
    data: {
      violation_count: serverViolationCount,
      max_violation_count: 3,
      disqualified: false,
    },
  };
}

vi.mock('../services/examApi', () => ({
  reportViolation: vi.fn(),
  sendHeartbeat: vi.fn().mockResolvedValue({ data: { ok: true } }),
  finishExam: vi.fn().mockResolvedValue({ data: { ok: true } }),
}));

const END_AT = new Date(Date.now() + 3600 * 1000).toISOString();

function renderGuard(overrides: Partial<Parameters<typeof useExamGuard>[0]> = {}) {
  const onForceFinish = vi.fn();
  const view = renderHook(() =>
    useExamGuard({
      examSessionId: 1,
      maxViolationCount: 3,
      expectedEndAt: END_AT,
      onForceFinish,
      ...overrides,
    }),
  );

  return { ...view, onForceFinish };
}

describe('useExamGuard', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    serverViolationCount = 0;
    vi.mocked(examApi.reportViolation).mockImplementation((_sessionId: number, type: string) =>
      Promise.resolve(violationResponse(type) as never),
    );
  });

  it('menghitung sisa waktu dari expectedEndAt server, bukan jam lokal', () => {
    const { result } = renderGuard();

    // Hook sengaja tidak mempercayai jam lokal; sumbernya expectedEndAt.
    expect(result.current.remainingSeconds).toBeGreaterThan(3500);
    expect(result.current.remainingSeconds).toBeLessThanOrEqual(3600);
  });

  it('meminta fullscreen saat tombol ditekan', async () => {
    const { result } = renderGuard();

    // Hook mulai dari isFullscreen=false supaya ExamRoom menampilkan
    // gerbang "Lanjutkan Ujian" lebih dulu.
    expect(result.current.isFullscreen).toBe(false);

    await act(async () => {
      await result.current.enterFullscreen();
    });

    expect(document.documentElement.requestFullscreen).toHaveBeenCalled();
  });

  it('mencatat pelanggaran saat keluar fullscreen', async () => {
    const { result } = renderGuard();

    await act(async () => {
      Object.defineProperty(document, 'fullscreenElement', {
        writable: true,
        configurable: true,
        value: null,
      });
      document.dispatchEvent(new Event('fullscreenchange'));
    });

    await waitFor(() => {
      expect(examApi.reportViolation).toHaveBeenCalledWith(1, 'fullscreen_exit');
    });
    expect(result.current.lastViolationType).toBe('fullscreen_exit');
  });

  it('mencatat connection_lost tanpa menaikkan penghitung curang', async () => {
    const { result } = renderGuard();

    await act(async () => {
      window.dispatchEvent(new Event('offline'));
    });

    await waitFor(() => {
      expect(examApi.reportViolation).toHaveBeenCalledWith(1, 'connection_lost');
    });
    // connection_lost murni jaringan — tidak boleh dihitung sebagai curang.
    expect(result.current.violationCount).toBe(0);
  });

  it('menyelaraskan penghitung dengan violation_count dari server', async () => {
    vi.mocked(examApi.reportViolation).mockResolvedValueOnce({
      data: { violation_count: 2, max_violation_count: 3, disqualified: false },
    } as never);

    const { result } = renderGuard();

    await act(async () => {
      window.dispatchEvent(new Event('blur'));
    });

    // Penghitung harus mengikuti angka server, bukan menebak sendiri.
    await waitFor(() => {
      expect(result.current.violationCount).toBe(2);
    });
  });

  it('menyelesaikan ujian otomatis saat batas pelanggaran terlampaui', async () => {
    const { result, onForceFinish } = renderGuard({ maxViolationCount: 1 });

    await act(async () => {
      window.dispatchEvent(new Event('blur'));
    });

    await waitFor(() => {
      expect(examApi.finishExam).toHaveBeenCalledWith(1, 'violation_limit');
    });
    await waitFor(() => {
      expect(onForceFinish).toHaveBeenCalledWith('violation_limit');
    });
    expect(result.current.remainingViolations).toBe(0);
  });

  it('menyelesaikan ujian otomatis saat waktu habis', async () => {
    const lewat = new Date(Date.now() - 1000).toISOString();
    const { onForceFinish } = renderGuard({ expectedEndAt: lewat });

    await waitFor(() => {
      expect(examApi.finishExam).toHaveBeenCalledWith(1, 'time_up');
    });
    expect(onForceFinish).toHaveBeenCalledWith('time_up');
  });

  it('hanya menyelesaikan satu kali meski beberapa kondisi kepicu bersamaan', async () => {
    const lewat = new Date(Date.now() - 1000).toISOString();
    const { onForceFinish } = renderGuard({ expectedEndAt: lewat });

    await act(async () => {
      window.dispatchEvent(new Event('blur'));
      window.dispatchEvent(new Event('offline'));
    });

    await waitFor(() => {
      expect(onForceFinish).toHaveBeenCalledTimes(1);
    });
  });

  it('memblokir Ctrl+C dan F12', async () => {
    renderGuard();

    const event = new KeyboardEvent('keydown', { key: 'c', ctrlKey: true, bubbles: true, cancelable: true });
    document.dispatchEvent(event);

    expect(event.defaultPrevented).toBe(true);

    await waitFor(() => {
      expect(examApi.reportViolation).toHaveBeenCalledWith(1, 'copy_paste_attempt');
    });
  });

  it('tidak melaporkan pelanggaran setelah ujian selesai', async () => {
    const lewat = new Date(Date.now() - 1000).toISOString();
    renderGuard({ expectedEndAt: lewat });

    await waitFor(() => {
      expect(examApi.finishExam).toHaveBeenCalled();
    });

    const callsBefore = vi.mocked(examApi.reportViolation).mock.calls.length;
    await act(async () => {
      window.dispatchEvent(new Event('blur'));
    });

    expect(vi.mocked(examApi.reportViolation).mock.calls.length).toBe(callsBefore);
  });
});