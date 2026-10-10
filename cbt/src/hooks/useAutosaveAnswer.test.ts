import { beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, act } from '@testing-library/react';

import { useAutosaveAnswer } from './useAutosaveAnswer';
import { useExamStore } from '../store/examStore';

const post = vi.hoisted(() => vi.fn(async () => ({ data: { ok: true } })));

vi.mock('../services/api', () => ({ default: { post } }));

const examSessionId = 42;

beforeEach(() => {
  post.mockReset();
  post.mockResolvedValue({ data: { ok: true } });
  useExamStore.getState().reset();
});

describe('useAutosaveAnswer', () => {
  it('menunda pengiriman sampai debounce 800ms', async () => {
    vi.useFakeTimers();
    const { result } = renderHook(() => useAutosaveAnswer(examSessionId));

    act(() => result.current.saveAnswer(1, ['a']));

    expect(post).not.toHaveBeenCalled();

    await act(async () => {
      vi.advanceTimersByTime(799);
    });
    expect(post).not.toHaveBeenCalled();

    await act(async () => {
      vi.advanceTimersByTime(1);
    });
    expect(post).toHaveBeenCalledTimes(1);

    vi.useRealTimers();
  });

  it('menggabungkan perubahan cepat jadi satu request berisi nilai terakhir', async () => {
    vi.useFakeTimers();
    const { result } = renderHook(() => useAutosaveAnswer(examSessionId));

    act(() => result.current.saveAnswer(1, ['a']));
    await act(async () => {
      vi.advanceTimersByTime(400);
    });
    act(() => result.current.saveAnswer(1, ['b']));
    await act(async () => {
      vi.advanceTimersByTime(400);
    });
    act(() => result.current.saveAnswer(1, ['c']));

    await act(async () => {
      vi.advanceTimersByTime(800);
    });

    expect(post).toHaveBeenCalledTimes(1);
    expect(post).toHaveBeenCalledWith('/exam/answer', {
      exam_session_id: 42,
      exam_question_id: 1,
      answer: ['c'],
    });

    vi.useRealTimers();
  });

  it('menandai status soal sebagai saving lalu saved', async () => {
    vi.useFakeTimers();
    const { result } = renderHook(() => useAutosaveAnswer(examSessionId));

    act(() => result.current.saveAnswer(5, ['a']));
    expect(useExamStore.getState().savingStatus[5]).toBe('saving');

    await act(async () => {
      vi.advanceTimersByTime(800);
    });

    expect(useExamStore.getState().savingStatus[5]).toBe('saved');

    vi.useRealTimers();
  });

  it('menandai error bila server menolak', async () => {
    vi.useFakeTimers();
    post.mockRejectedValue(new Error('422'));

    const { result } = renderHook(() => useAutosaveAnswer(examSessionId));

    act(() => result.current.saveAnswer(6, ['z']));
    await act(async () => {
      vi.advanceTimersByTime(800);
    });

    expect(useExamStore.getState().savingStatus[6]).toBe('error');

    vi.useRealTimers();
  });

  it('flush mengirim jawaban yang masih tertunda tanpa menunggu timer', async () => {
    vi.useFakeTimers();
    const { result } = renderHook(() => useAutosaveAnswer(examSessionId));

    act(() => result.current.saveAnswer(9, ['a']));
    expect(post).not.toHaveBeenCalled();

    await act(async () => {
      await result.current.flush();
    });

    expect(post).toHaveBeenCalledWith('/exam/answer', {
      exam_session_id: 42,
      exam_question_id: 9,
      answer: ['a'],
    });
    expect(useExamStore.getState().savingStatus[9]).toBe('saved');

    // Timer yang sudah dibatalkan tidak boleh mengirim dua kali.
    await act(async () => {
      vi.advanceTimersByTime(2000);
    });
    expect(post).toHaveBeenCalledTimes(1);

    vi.useRealTimers();
  });

  it('flush tanpa antrean tidak mengirim apa pun', async () => {
    const { result } = renderHook(() => useAutosaveAnswer(examSessionId));

    await act(async () => {
      await result.current.flush();
    });

    expect(post).not.toHaveBeenCalled();
  });
});