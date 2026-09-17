import { create } from 'zustand';

interface Question {
  id: number;
  question_text: string;
  question_image: string | null;
  type: 'single_choice' | 'multiple_choice' | 'essay';
  options: { key: string; text: string }[] | null;
  score: number;
}

interface ExamMeta {
  examSessionId: number;
  examId: number;
  examName: string;
  maxViolationCount: number;
  startedAt: string;
  expectedEndAt: string;
  violationCount?: number;
}

interface ExamStore {
  meta: ExamMeta | null;
  questions: Question[];
  answers: Record<number, string[]>; // question_id -> jawaban
  savingStatus: Record<number, 'idle' | 'saving' | 'saved' | 'error'>;
  currentIndex: number;
  flagged: Record<number, boolean>;

  setExamData: (meta: ExamMeta, questions: Question[], savedAnswers: Record<number, string[]>) => void;
  setAnswer: (questionId: number, answer: string[]) => void;
  setSavingStatus: (questionId: number, status: 'idle' | 'saving' | 'saved' | 'error') => void;
  goToQuestion: (index: number) => void;
  toggleFlag: (questionId: number) => void;
  reset: () => void;
}

export const useExamStore = create<ExamStore>((set) => ({
  meta: null,
  questions: [],
  answers: {},
  savingStatus: {},
  currentIndex: 0,
  flagged: {},

  setExamData: (meta, questions, savedAnswers) =>
    set({ meta, questions, answers: savedAnswers, currentIndex: 0, flagged: {} }),

  setAnswer: (questionId, answer) =>
    set((state) => ({ answers: { ...state.answers, [questionId]: answer } })),

  setSavingStatus: (questionId, status) =>
    set((state) => ({ savingStatus: { ...state.savingStatus, [questionId]: status } })),

  goToQuestion: (index) => set({ currentIndex: index }),

  toggleFlag: (questionId) =>
    set((state) => ({ flagged: { ...state.flagged, [questionId]: !state.flagged[questionId] } })),

  reset: () => set({ meta: null, questions: [], answers: {}, savingStatus: {}, currentIndex: 0, flagged: {} }),
}));
