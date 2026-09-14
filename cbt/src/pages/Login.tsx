import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../services/api';

export default function Login() {
  const [identifier, setIdentifier] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setLoading(true);
    try {
      const res = await api.post('/login', { identifier, password });
      localStorage.setItem('cbt_auth_token', res.data.token);
      localStorage.setItem('cbt_user_name', res.data.user.name);
      navigate('/token');
    } catch (err: any) {
      setError(err.response?.data?.message ?? 'Login gagal. Periksa kembali data Anda.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex h-screen flex-col items-center justify-center gap-4">
      <h1 className="text-xl font-semibold">Login CBT</h1>
      <form onSubmit={handleLogin} className="flex w-72 flex-col gap-3">
        <input
          value={identifier}
          onChange={(e) => setIdentifier(e.target.value)}
          placeholder="NIS / Email"
          className="rounded border px-4 py-2"
          autoComplete="username"
        />
        <input
          type="password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          placeholder="Password"
          className="rounded border px-4 py-2"
          autoComplete="current-password"
        />
        {error && <p className="text-sm text-red-600">{error}</p>}
        <button
          type="submit"
          disabled={loading || !identifier || !password}
          className="rounded bg-blue-600 px-4 py-2 text-white disabled:opacity-50"
        >
          {loading ? 'Memproses...' : 'Login'}
        </button>
      </form>
    </div>
  );
}
