import { beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';

import { useQuestionImage } from './useQuestionImage';

const get = vi.hoisted(() => vi.fn());

vi.mock('../services/api', () => ({ default: { get } }));

describe('useQuestionImage', () => {
  beforeEach(() => {
    get.mockReset();
    vi.stubGlobal('URL', {
      ...URL,
      createObjectURL: vi.fn((blob: Blob) => `blob:${blob.size}`),
      revokeObjectURL: vi.fn(),
    });
  });

  it('mengambil gambar sebagai blob lalu menjadi object url', async () => {
    get.mockResolvedValue({ data: new Blob(['img']) });

    const { result } = renderHook(() => useQuestionImage('/api/cbt/exam/question-image/1'));

    expect(get).toHaveBeenCalledWith('/api/cbt/exam/question-image/1', { responseType: 'blob' });

    await waitFor(() => expect(result.current).toBe('blob:3'));
  });

  it('tidak memanggil api saat url kosong', () => {
    const { result } = renderHook(() => useQuestionImage(null));

    expect(get).not.toHaveBeenCalled();
    expect(result.current).toBeNull();
  });

  it('gagal memuat mengembalikan null tanpa melempar', async () => {
    get.mockRejectedValue(new Error('404'));

    const { result } = renderHook(() => useQuestionImage('/api/cbt/exam/question-image/9'));

    await waitFor(() => expect(get).toHaveBeenCalled());
    expect(result.current).toBeNull();
  });

  it('membebaskan object url lama saat soal berganti', async () => {
    get.mockResolvedValue({ data: new Blob(['img']) });

    const { rerender } = renderHook(({ url }) => useQuestionImage(url), {
      initialProps: { url: '/api/cbt/exam/question-image/1' },
    });

    await waitFor(() => expect(URL.revokeObjectURL).not.toHaveBeenCalled());

    rerender({ url: '/api/cbt/exam/question-image/2' });

    // Tanpa revoke, tiap perpindahan soal menahan satu blob sampai tab ditutup.
    await waitFor(() => expect(URL.revokeObjectURL).toHaveBeenCalled());
  });

  it('tidak menampilkan gambar soal sebelumnya saat yang baru belum selesai', async () => {
    let selesai = false;
    get.mockImplementation(() =>
      selesai ? Promise.resolve({ data: new Blob(['baru']) }) : new Promise(() => {}),
    );

    const { result, rerender } = renderHook(({ url }) => useQuestionImage(url), {
      initialProps: { url: null as string | null },
    });

    selesai = true;
    rerender({ url: '/api/cbt/exam/question-image/1' });
    await waitFor(() => expect(result.current).toBe('blob:4'));

    // Ganti ke soal yang belum termuat: harus null, bukan gambar lama.
    selesai = false;
    rerender({ url: '/api/cbt/exam/question-image/2' });

    expect(result.current).toBeNull();
  });
});