<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfilSekolahRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama_sekolah' => ['required', 'string', 'max:255'],
            'npsn' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'telp' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255', $this->websiteDomainRule()],
            'tahun_berdiri' => ['nullable', 'integer', 'min:1900', 'max:'.date('Y')],
            'akreditasi' => ['nullable', 'in:A,B,C'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
            'foto_gedung' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'sambutan' => ['nullable', 'string', 'max:5000'],
            'visi' => ['nullable', 'string', 'max:5000'],
            'misi' => ['nullable', 'array', 'max:20'],

            /*
             * Baris misi yang kosong itu SAH, bukan error.
             *
             * Versi lama memakai `required_with:misi` di sini, padahal form
             * selalu merender minimal satu baris `misi[]` yang kosong secara
             * bawaan. Akibatnya SETIAP penyimpanan gagal begitu admin pernah
             * menyentuh baris misi lalu mengosongkannya lagi.
             *
             * Yang lebih buruk, kuncinya jadi `misi.0`, bukan `misi`. View
             * hanya menulis `@error('misi')` yang tidak menangkap `misi.0`,
             * sementara `layouts.partials.alert` menyingkirkan `misi.0` dari
             * ringkasan karena ia cocok dengan prefiks `misi.`. Errornya hilang
             * dari kedua tempat: tidak ada pesan di bawah kolom, dan tidak ada
             * ringkasan di atas. Penyimpanan gagal tanpa satu pesan pun.
             *
             * Baris kosong cukup dibuang. `ProfilSekolahController::update`
             * sudah melakukan `array_filter` atas hasilnya, jadi tidak ada
             * misi kosong yang akan tersimpan ke database.
             */
            'misi.*' => ['nullable', 'string', 'max:500'],
            'nama_kepala' => ['nullable', 'string', 'max:255'],
            'foto_kepala' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'maps_embed_url' => ['nullable', 'string', 'max:2000', 'starts_with:https://www.google.com/maps/embed'],
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function attributes(): array
    {
        // Laravel memakai nama aturan yang gagal di pesan standarnya, jadi
        // "Maps embed url harus diawali salah satu dari: ..." adalah satu-
        // satunya petunjuk yang muncul di layar. Ketidakjelasan itu tidak
        // menjelaskan apa yang harus administrator tempel.
        return [
            'nama_sekolah' => 'nama sekolah',
            'npsn' => 'NPSN',
            'telp' => 'nomor telepon',
            'email' => 'email',
            'website' => 'website',
            'tahun_berdiri' => 'tahun berdiri',
            'akreditasi' => 'akreditasi',
            'logo' => 'logo sekolah',
            'foto_gedung' => 'foto gedung',
            'foto_kepala' => 'foto kepala sekolah',
            'sambutan' => 'sambutan kepala sekolah',
            'visi' => 'visi',
            'misi' => 'misi',
            'misi.*' => 'butir misi',
            'nama_kepala' => 'nama kepala sekolah',
            'maps_embed_url' => 'URL embed Google Maps',
        ];
    }

    /**
     * Pesan khusus untuk aturan yang kalimat standarnya tidak berguna.
     */
    public function messages(): array
    {
        return [
            'maps_embed_url.starts_with' => 'URL harus diambil dari tombol Share Google Maps lalu pilih "Sematkan peta" (Embed a map), lalu salin atribut src. URL peta biasa tidak bisa dipakai.',
            'tahun_berdiri.min' => 'Tahun berdiri tidak boleh sebelum 1900.',
            'tahun_berdiri.max' => 'Tahun berdiri tidak boleh melebihi tahun berjalan.',
            'akreditasi.in' => 'Akreditasi hanya bisa A, B, atau C.',
        ];
    }

    /**
     * Aturan website yang menerima domain tanpa protokol.
     *
     * Aturan `url` bawaan menolak `sekolah.sch.id`, padahal itu cara paling
     * natural orang mengetik alamat situs. Yang lebih penting, halaman publik
     * sudah menangani hal itu: `about.blade.php` menambahkan `https://`
     * sendiri kalau protokolnya belum ada. Jadi aturan dan tampilan
     * bertentangan, dan administrator yang mengikuti aturannya akan mendapat
     * halaman publik yang tautannya rusak.
     *
     * Diterima: `sekolah.sch.id`, `www.sekolah.sch.id`,
     * `https://sekolah.sch.id`, `http://sekolah.sch.id/path`.
     *
     * Dipakai closure, bukan string `regex:`, karena pola ini mengandung
     * `:` sendiri (`https?:`). Parser aturan Laravel memecah string pada
     * tanda titik dua dan salah memotong pola yang di dalamnya, jadi
     * polanya berubah dan aturannya tidak pernah jalan.
     */
    private function websiteDomainRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_string($value) || $value === '') {
                return;
            }

            $pola = '/^(https?:\/\/)?([\w-]+\.)+[\w-]{2,}(\/\S*)?$/i';

            if (preg_match($pola, $value) !== 1) {
                $fail('Website harus berupa domain, misalnya "sekolah.sch.id" atau "https://sekolah.sch.id".');
            }
        };
    }
}
