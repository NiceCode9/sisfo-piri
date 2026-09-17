import http from 'k6/http';
import { check, sleep } from 'k6';
import { SharedArray } from 'k6/data';

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000/api/cbt';
const EXAM_TOKEN = __ENV.EXAM_TOKEN || 'LOADTEST';

// students.json dari LoadTestSeeder (500 user)
const students = new SharedArray('students', () => {
  try { return JSON.parse(open('./students.json')); } catch { return []; }
});

export const options = {
  vus: Number(__ENV.VUS) || 500,
  stages: [
    { duration: '15s', target: Number(__ENV.VUS) || 500 },
    { duration: '10m', target: Number(__ENV.VUS) || 500 },
    { duration: '15s', target: 0 },
  ],
  thresholds: {
    'http_req_failed': ['rate<0.02'],
    'http_req_duration': ['p(95)<1500'],
  },
};

function randomStudent(idx) {
  if (students.length) return students[idx % students.length];
  return { username: `loadtest_siswa${String(idx+1).padStart(4,'0')}`, password: 'password123' };
}

export default function () {
  const id = __VU * 10000 + __ITER;
  const stu = randomStudent(id);

  // 1. login
  const loginRes = http.post(`${BASE_URL}/login`, JSON.stringify({ identifier: stu.username, password: stu.password }), { headers: { 'Content-Type': 'application/json' } });
  check(loginRes, { 'login 200': (r) => r.status === 200 });
  if (loginRes.status !== 200) { sleep(1); return; }
  const token = loginRes.json('token');
  const headers = { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` };

  // 2. join
  const joinRes = http.post(`${BASE_URL}/exam/join`, JSON.stringify({ token: EXAM_TOKEN }), { headers });
  check(joinRes, { 'join 200': (r) => r.status === 200 });
  if (joinRes.status !== 200) { sleep(1); return; }
  const examSessionId = joinRes.json('exam_session_id');

  // 3. questions
  const qRes = http.get(`${BASE_URL}/exam/questions?exam_session_id=${examSessionId}`, { headers });
  check(qRes, { 'questions 200': (r) => r.status === 200, 'no correct_answer leak': (r) => !String(r.body).includes('correct_answer') });
  let questions = [];
  try { questions = qRes.json('questions') || []; } catch {}

  // 4. answer loop (3 soal pertama)
  for (let i = 0; i < Math.min(3, questions.length); i++) {
    const q = questions[i];
    const answer = q.options && q.options[0] ? [q.options[0].key] : ['A'];
    const aRes = http.post(`${BASE_URL}/exam/answer`, JSON.stringify({ exam_session_id: examSessionId, exam_question_id: q.id, answer }), { headers });
    check(aRes, { 'answer 200': (r) => r.status === 200 });
    sleep(0.5);
  }

  // 5. heartbeat 20s sekali (simulasi interval — sleep 2s per VU iterasi mewakili)
  const hbRes = http.post(`${BASE_URL}/exam/heartbeat`, JSON.stringify({ exam_session_id: examSessionId }), { headers });
  check(hbRes, { 'heartbeat 200': (r) => r.status === 200 });

  // 6. violation (acak 10%)
  if (Math.random() < 0.1) {
    http.post(`${BASE_URL}/exam/violation`, JSON.stringify({ exam_session_id: examSessionId, type: 'visibility_hidden' }), { headers });
  }

  sleep(1);

  // 7. finish 20% VU
  if (Math.random() < 0.2) {
    http.post(`${BASE_URL}/exam/finish`, JSON.stringify({ exam_session_id: examSessionId, finish_reason: 'manual' }), { headers });
  }
}
