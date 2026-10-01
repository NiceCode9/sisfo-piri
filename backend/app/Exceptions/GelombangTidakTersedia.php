<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Gelombang yang diminta pendaftar tidak bisa dipakai.
 *
 * Dipakai sebagai tanda supaya controller bisa menempelkan pesan ke field
 * `gelombang_id`. Tanpa kelas khusus ini, error gelombang ikut dilaporkan di
 * `jalur_pendaftaran_id` karena dibungkus blok catch RuntimeException yang sama
 * dengan error kuota jalur.
 */
class GelombangTidakTersedia extends RuntimeException
{
    //
}
