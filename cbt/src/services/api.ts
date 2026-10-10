import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  headers: { Accept: 'application/json' },
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('cbt_auth_token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

export function clearAuthToken(): void {
  localStorage.removeItem('cbt_auth_token');
  localStorage.removeItem('cbt_user_name');
}

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      clearAuthToken();
      // hard redirect supaya semua state (zustand) ke-reset bersih
      if (window.location.pathname !== '/login') {
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  }
);

/**
 * Membatalkan token Sanctum di server sebelum menghapusnya dari localStorage.
 *
 * Tanpa ini token tetap hidup di server sampai siswa login berikutnya — bisa
 * berhari-hari setelah ujian selesai. Pemanggil harus tetapromise:', karena
 * pemindahan layar tidak boleh tertahan kegagalan jaringan.
 */
export async function logout(): Promise<void> {
  try {
    await api.post('/logout');
  } catch {
    // Token sudah tidak berlaku / jaringan mati — hapus lokal tetap dilakukan.
  } finally {
    clearAuthToken();
  }
}

export default api;
