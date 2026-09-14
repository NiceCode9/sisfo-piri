import React, { useEffect, useState } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import api from './services/api';
import { useExamStore } from './store/examStore';
import Login from './pages/Login';
import TokenEntry from './pages/TokenEntry';
import ExamRoom from './pages/ExamRoom';
import Finished from './pages/Finished';

function isAuthenticated() {
  return !!localStorage.getItem('cbt_auth_token');
}

function RequireAuth({ children }: { children: React.ReactNode }) {
  if (!isAuthenticated()) return <Navigate to="/login" replace />;
  return children;
}

function AppRoutes() {
  const [checking, setChecking] = useState(true);
  const setExamData = useExamStore((s) => s.setExamData);
  const hasSession = useExamStore((s) => !!s.meta);

  useEffect(() => {
    const checkActiveSession = async () => {
      if (!isAuthenticated()) {
        setChecking(false);
        return;
      }
      try {
        const res = await api.get('/exam/active');
        if (res.data.active) {
          const questionsRes = await api.get('/exam/questions', {
            params: { exam_session_id: res.data.exam_session_id },
          });
          setExamData(
            {
              examSessionId: res.data.exam_session_id,
              examId: res.data.exam.id,
              examName: res.data.exam.name,
              maxViolationCount: res.data.exam.max_violation_count,
              startedAt: '',
              expectedEndAt: res.data.expected_end_at,
            },
            questionsRes.data.questions,
            questionsRes.data.saved_answers ?? {}
          );
        }
      } catch {
        // kalau /exam/active gagal (401 dsb), interceptor api.ts sudah handle redirect
      } finally {
        setChecking(false);
      }
    };
    checkActiveSession();
  }, [setExamData]);

  if (checking) return <div className="flex h-screen items-center justify-center">Memuat...</div>;

  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route
        path="/token"
        element={
          <RequireAuth>
            <TokenEntry />
          </RequireAuth>
        }
      />
      <Route
        path="/exam"
        element={
          <RequireAuth>
            {hasSession ? <ExamRoom /> : <Navigate to="/token" replace />}
          </RequireAuth>
        }
      />
      <Route path="/finished" element={<RequireAuth><Finished /></RequireAuth>} />
      <Route path="*" element={<Navigate to={isAuthenticated() ? '/token' : '/login'} replace />} />
    </Routes>
  );
}

export default function App() {
  return (
    <BrowserRouter>
      <AppRoutes />
    </BrowserRouter>
  );
}
