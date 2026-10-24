import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  headers: { Accept: 'application/json' },
  withCredentials: true,
});

// Token kini dikirim lewat cookie HttpOnly; tidak disimpan di localStorage.

export function clearAuthToken(): void {
  localStorage.removeItem('cbt_logged_in');
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
