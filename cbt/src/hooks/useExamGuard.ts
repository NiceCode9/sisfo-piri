import { useCallback, useEffect, useRef, useState } from 'react';
import { reportViolation, sendHeartbeat, finishExam } from '../services/examApi';

interface UseExamGuardOptions {
  examSessionId: number;
  maxViolationCount: number;
  expectedEndAt: string; // ISO string dari server, JANGAN dihitung sendiri di client
  onForceFinish: (reason: 'time_up' | 'violation_limit') => void;
  heartbeatIntervalMs?: number; // default 20000
}

interface ExamGuardState {
  isFullscreen: boolean;
  violationCount: number;
  remainingSeconds: number;
  showViolationWarning: boolean;
  lastViolationType: string | null;
}

export function useExamGuard({
  examSessionId,
  maxViolationCount,
  expectedEndAt,
  onForceFinish,
  heartbeatIntervalMs = 20000,
}: UseExamGuardOptions) {
  const [state, setState] = useState<ExamGuardState>({
    isFullscreen: false,
    violationCount: 0,
    remainingSeconds: Math.max(
      0,
      Math.floor((new Date(expectedEndAt).getTime() - Date.now()) / 1000)
    ),
    showViolationWarning: false,
    lastViolationType: null,
  });

  const finishedRef = useRef(false); // guard supaya finishExam tidak double-call

  // --- Helper: catat pelanggaran, naikkan counter, cek batas ---
  const recordViolation = useCallback(
    async (type: Parameters<typeof reportViolation>[1]) => {
      if (finishedRef.current) return;

      setState((prev) => {
        const nextCount = prev.violationCount + 1;
        return {
          ...prev,
          violationCount: nextCount,
          showViolationWarning: true,
          lastViolationType: type,
        };
      });

      try {
        await reportViolation(examSessionId, type);
      } catch {
        // Kalau request gagal (misal lagi disconnect), tetap lanjut —
        // server juga akan expire sesi ini via expected_end_at kalau perlu.
      }
    },
    [examSessionId]
  );

  // --- 1. Fullscreen handling ---
  const enterFullscreen = useCallback(async () => {
    try {
      await document.documentElement.requestFullscreen();
    } catch {
      // Browser mungkin block kalau tidak dipicu user gesture — pastikan
      // dipanggil dari onClick tombol "Mulai Ujian", bukan otomatis di useEffect.
    }
  }, []);

  useEffect(() => {
    const handleFullscreenChange = () => {
      const isFs = !!document.fullscreenElement;
      setState((prev) => ({ ...prev, isFullscreen: isFs }));
      if (!isFs && !finishedRef.current) {
        recordViolation('fullscreen_exit');
      }
    };

    document.addEventListener('fullscreenchange', handleFullscreenChange);
    return () => document.removeEventListener('fullscreenchange', handleFullscreenChange);
  }, [recordViolation]);

  // --- 2. Tab switch / window blur / visibility ---
  useEffect(() => {
    const handleVisibilityChange = () => {
      if (document.hidden && !finishedRef.current) {
        recordViolation('visibility_hidden');
      }
    };

    const handleBlur = () => {
      if (!finishedRef.current) {
        recordViolation('tab_blur');
      }
    };

    document.addEventListener('visibilitychange', handleVisibilityChange);
    window.addEventListener('blur', handleBlur);
    return () => {
      document.removeEventListener('visibilitychange', handleVisibilityChange);
      window.removeEventListener('blur', handleBlur);
    };
  }, [recordViolation]);

  // --- 3. Deteksi koneksi terputus ---
  // Bobot pelanggaran ini idealnya lebih longgar drpd tab_blur/fullscreen_exit
  // karena murni masalah jaringan, bukan indikasi niat curang. Kalau perlu,
  // kecualikan tipe ini dari perhitungan max_violation_count di sisi Laravel.
  useEffect(() => {
    const handleOffline = () => {
      if (!finishedRef.current) {
        recordViolation('connection_lost');
      }
    };
    window.addEventListener('offline', handleOffline);
    return () => window.removeEventListener('offline', handleOffline);
  }, [recordViolation]);

  // --- 4. Cegah keluar/reload tanpa sengaja ---
  useEffect(() => {
    const handleBeforeUnload = (e: BeforeUnloadEvent) => {
      if (finishedRef.current) return;
      e.preventDefault();
      e.returnValue = ''; // trigger native browser confirm dialog
    };
    window.addEventListener('beforeunload', handleBeforeUnload);
    return () => window.removeEventListener('beforeunload', handleBeforeUnload);
  }, []);

  // --- 5. Disable klik kanan & shortcut umum (deterrent, bukan security utama) ---
  useEffect(() => {
    const blockContextMenu = (e: MouseEvent) => e.preventDefault();
    const blockShortcuts = (e: KeyboardEvent) => {
      const blocked =
        e.key === 'F12' ||
        (e.ctrlKey && e.shiftKey && ['I', 'J', 'C'].includes(e.key)) ||
        (e.ctrlKey && ['c', 'v', 'u', 'p'].includes(e.key.toLowerCase()));
      if (blocked) {
        e.preventDefault();
        recordViolation('copy_paste_attempt');
      }
    };

    document.addEventListener('contextmenu', blockContextMenu);
    document.addEventListener('keydown', blockShortcuts);
    return () => {
      document.removeEventListener('contextmenu', blockContextMenu);
      document.removeEventListener('keydown', blockShortcuts);
    };
  }, [recordViolation]);

  // --- 6. Timer — hitung mundur di client, tapi TIDAK jadi sumber kebenaran ---
  useEffect(() => {
    const interval = setInterval(() => {
      setState((prev) => {
        const remaining = Math.max(
          0,
          Math.floor((new Date(expectedEndAt).getTime() - Date.now()) / 1000)
        );
        return { ...prev, remainingSeconds: remaining };
      });
    }, 1000);
    return () => clearInterval(interval);
  }, [expectedEndAt]);

  // --- 7. Heartbeat berkala ke server ---
  useEffect(() => {
    const interval = setInterval(() => {
      if (!finishedRef.current) {
        sendHeartbeat(examSessionId).catch(() => {});
      }
    }, heartbeatIntervalMs);
    return () => clearInterval(interval);
  }, [examSessionId, heartbeatIntervalMs]);

  // --- 8. Auto-finish kalau waktu habis atau violation melebihi batas ---
  useEffect(() => {
    if (finishedRef.current) return;

    if (state.remainingSeconds <= 0) {
      finishedRef.current = true;
      finishExam(examSessionId, 'time_up').finally(() => onForceFinish('time_up'));
      return;
    }

    if (state.violationCount >= maxViolationCount) {
      finishedRef.current = true;
      finishExam(examSessionId, 'violation_limit').finally(() => onForceFinish('violation_limit'));
    }
  }, [state.remainingSeconds, state.violationCount, maxViolationCount, examSessionId, onForceFinish]);

  const dismissWarning = useCallback(() => {
    setState((prev) => ({ ...prev, showViolationWarning: false }));
  }, []);

  return {
    ...state,
    enterFullscreen,
    dismissWarning,
    remainingViolations: Math.max(0, maxViolationCount - state.violationCount),
  };
}
