import { beforeEach, describe, expect, it } from 'vitest';

import { useExamStore } from './examStore';

const meta = {
  examSessionId: 7,
  examId: 3,
  examName: 'Ujian TKA',
  maxViolationCount: 3,
  startedAt: '2026-10-10T08:00:00+07:00',
  expectedEndAt: '2026-10-10T09:00:00+07:00',
};

const questions = [
  { id: 1, question_text: 'Soal 1', question_image: null, type: 'single_choice' as const, options: null, score: 5 },
  { id: 2, question_text: 'Soal 2', question_image: null, type: 'essay' as const, options: null, score: 10 },
];

describe('examStore', () => {
  beforeEach(() => {
    useExamStore.getState().reset();
  });

  it('menyimpan meta, soal, dan jawaban yang tersimpan', () => {
    useExamStore.getState().setExamData(meta, questions, { 1: ['a'] });

    const state = useExamStore.getState();
    expect(state.meta).toEqual(meta);
    expect(state.questions).toHaveLength(2);
    expect(state.answers[1]).toEqual(['a']);
  });

  it('setExamData mengulang navigasi dan ragu-ragu', () => {
    useExamStore.getState().setExamData(meta, questions, {});
    useExamStore.getState().goToQuestion(1);
    useExamStore.getState().toggleFlag(1);

    useExamStore.getState().setExamData(meta, questions, {});

    const state = useExamStore.getState();
    expect(state.currentIndex).toBe(0);
    expect(state.flagged).toEqual({});
  });

  it('toggleFlag membalik status ragu', () => {
    useExamStore.getState().setExamData(meta, questions, {});

    useExamStore.getState().toggleFlag(2);
    expect(useExamStore.getState().flagged[2]).toBe(true);

    useExamStore.getState().toggleFlag(2);
    expect(useExamStore.getState().flagged[2]).toBe(false);
  });

  it('goToQuestion menggeser indeks', () => {
    useExamStore.getState().setExamData(meta, questions, {});
    useExamStore.getState().goToQuestion(1);

    expect(useExamStore.getState().currentIndex).toBe(1);
  });

  it('reset mengosongkan seluruh state agar ujian berikutnya bersih', () => {
    useExamStore.getState().setExamData(meta, questions, { 1: ['a'] });
    useExamStore.getState().reset();

    const state = useExamStore.getState();
    expect(state.meta).toBeNull();
    expect(state.questions).toEqual([]);
    expect(state.answers).toEqual({});
    expect(state.flagged).toEqual({});
  });
});