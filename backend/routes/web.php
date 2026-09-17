<?php

use App\Http\Controllers\Admin\AbsensiController;
use App\Http\Controllers\Admin\BiayaPendaftaranController;
use App\Http\Controllers\Admin\BrosurController;
use App\Http\Controllers\Admin\CalonSiswaController;
use App\Http\Controllers\Admin\Cbt\ExamAnswerController;
use App\Http\Controllers\Admin\Cbt\ExamController as CbtExamController;
use App\Http\Controllers\Admin\Cbt\ExamMonitoringController as CbtMonitoringController;
use App\Http\Controllers\Admin\Cbt\ExamQuestionController as CbtQuestionController;
use App\Http\Controllers\Admin\Cbt\ExamResultController;
use App\Http\Controllers\Admin\Cbt\ExamTokenController as CbtTokenController;
use App\Http\Controllers\Admin\Cbt\QuestionBankController;
use App\Http\Controllers\Admin\Cbt\QuestionBankQuestionController;
use App\Http\Controllers\Admin\GaleriController;
use App\Http\Controllers\Admin\GelombangController;
use App\Http\Controllers\Admin\GuruController;
use App\Http\Controllers\Admin\JadwalPpdbController;
use App\Http\Controllers\Admin\JalurPendaftaranController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\Admin\KenaikanKelasController;
use App\Http\Controllers\Admin\KuotaPendaftaranController;
use App\Http\Controllers\Admin\MataPelajaranController;
use App\Http\Controllers\Admin\MateriController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PembayaranController;
use App\Http\Controllers\Admin\PembayaranLainnyaController;
use App\Http\Controllers\Admin\PengampuController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\PengumumanController;
use App\Http\Controllers\Admin\ProfilSekolahController;
use App\Http\Controllers\Admin\RencanaAngsuranController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\RombelController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Admin\TahunAjaranController;
use App\Http\Controllers\Admin\TugasController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WhatsappController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Ortu\DashboardController as OrtuDashboardController;
use App\Http\Controllers\Ortu\ProfilController as OrtuProfilController;
use App\Http\Controllers\Siswa\DashboardController;
use App\Http\Controllers\Siswa\MateriController as SiswaMateriController;
use App\Http\Controllers\Siswa\ProfilController;
use App\Http\Controllers\Siswa\RiwayatController;
use App\Http\Controllers\Siswa\TugasController as SiswaTugasController;
use App\Http\Controllers\SpmbController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SpmbController::class, 'home'])->name('spmb.home');
Route::get('/pendaftaran', [SpmbController::class, 'create'])->name('spmb.pendaftaran');
Route::get('/tentang', [SpmbController::class, 'about'])->name('spmb.about');
Route::post('/pendaftaran', [SpmbController::class, 'store'])->name('spmb.store')->middleware('throttle:5,1');
Route::get('/pengumuman', [SpmbController::class, 'pengumumanIndex'])->name('spmb.pengumuman.index');
Route::get('/pengumuman/{pengumuman}', [SpmbController::class, 'pengumumanShow'])->name('spmb.pengumuman.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::prefix('siswa')->name('siswa.')->middleware(['auth', 'role:siswa'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profil', [ProfilController::class, 'show'])->name('profil');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::get('/kelas', [RiwayatController::class, 'kelas'])->name('kelas');
    Route::get('/absensi', [RiwayatController::class, 'absensi'])->name('absensi');
    Route::get('/materi', [SiswaMateriController::class, 'index'])->name('materi.index');
    Route::get('/materi/{materi}', [SiswaMateriController::class, 'show'])->name('materi.show');
    Route::get('/tugas', [SiswaTugasController::class, 'index'])->name('tugas.index');
    Route::get('/tugas/rekap', [SiswaTugasController::class, 'rekap'])->name('tugas.rekap');
    Route::get('/tugas/{tugas}', [SiswaTugasController::class, 'show'])->name('tugas.show');
    Route::post('/tugas/{tugas}/kumpul', [SiswaTugasController::class, 'kumpul'])->name('tugas.kumpul');
});

Route::prefix('ortu')->name('ortu.')->middleware(['auth', 'role:orang-tua'])->group(function () {
    Route::get('/dashboard', [OrtuDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profil', [OrtuProfilController::class, 'show'])->name('profil');
    Route::put('/profil', [OrtuProfilController::class, 'update'])->name('profil.update');
    Route::get('/anak/{waliMurid}', [OrtuDashboardController::class, 'show'])->name('anak');
    Route::get('/materi', [App\Http\Controllers\Ortu\MateriController::class, 'index'])->name('materi.index');
    Route::get('/materi/{materi}', [App\Http\Controllers\Ortu\MateriController::class, 'show'])->name('materi.show');
    Route::get('/tugas', [App\Http\Controllers\Ortu\TugasController::class, 'index'])->name('tugas.index');
    Route::get('/tugas/rekap', [App\Http\Controllers\Ortu\TugasController::class, 'rekap'])->name('tugas.rekap');
    Route::get('/tugas/{tugas}', [App\Http\Controllers\Ortu\TugasController::class, 'show'])->name('tugas.show');
});

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/', function () {
        $user = auth()->user();

        if ($user->hasRole('siswa')) {
            return redirect()->route('siswa.dashboard');
        }

        if ($user->hasRole('orang-tua')) {
            return redirect()->route('ortu.dashboard');
        }

        return view('admin.dashboard');
    })->name('dashboard');
    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('roles', RoleController::class)->except(['show']);
    Route::resource('menus', MenuController::class)->except(['show']);
    Route::resource('calon-siswas', CalonSiswaController::class);
    Route::patch('calon-siswas/{calon_siswa}/status', [CalonSiswaController::class, 'updateStatus'])->name('calon-siswas.status');
    Route::patch('calon-siswas/{calon_siswa}/berkas', [CalonSiswaController::class, 'verifyBerkas'])->name('calon-siswas.berkas');
    Route::delete('calon-siswas/sertifikat/{sertifikat}', [CalonSiswaController::class, 'destroySertifikat'])->name('calon-siswas.sertifikat.destroy');
    Route::resource('biaya-pendaftarans', BiayaPendaftaranController::class)->except(['show']);
    Route::resource('pengumumans', PengumumanController::class)->except(['show']);
    Route::resource('brosurs', BrosurController::class)->except(['show']);
    Route::resource('galeris', GaleriController::class)->except(['show']);
    Route::resource('gelombangs', GelombangController::class)->except(['show']);
    Route::resource('tahun-ajarans', TahunAjaranController::class)->except(['show']);
    Route::resource('jalur-pendaftarans', JalurPendaftaranController::class)->except(['show']);
    Route::resource('jadwal-ppdbs', JadwalPpdbController::class)->except(['show']);
    Route::resource('kuota-pendaftarans', KuotaPendaftaranController::class)->except(['show']);
    Route::resource('gurus', GuruController::class)->except(['show']);
    Route::resource('mata-pelajarans', MataPelajaranController::class)->except(['show']);
    Route::resource('kelas', KelasController::class)->except(['show'])->parameters(['kelas' => 'kelas']);
    Route::resource('pengampus', PengampuController::class)->except(['show']);
    Route::post('rombels/{rombel}/pengampus/batch', [RombelController::class, 'storePengampuBatch'])->name('rombels.pengampus.batch');
    Route::get('rombels/salin', [RombelController::class, 'salin'])->name('rombels.salin');
    Route::post('rombels/salin', [RombelController::class, 'prosesSalin'])->name('rombels.salin.proses');
    Route::resource('rombels', RombelController::class);
    Route::resource('materis', MateriController::class);
    Route::get('tugas/rekap', [TugasController::class, 'rekap'])->name('tugas.rekap');
    Route::get('tugas/rekap/excel', [TugasController::class, 'exportExcel'])->name('tugas.rekap.excel');
    Route::get('tugas/rekap/pdf', [TugasController::class, 'exportPdf'])->name('tugas.rekap.pdf');
    Route::resource('tugas', TugasController::class);
    Route::get('tugas/{tuga}/nilai', [TugasController::class, 'nilai'])->name('tugas.nilai');
    Route::post('tugas/{tuga}/nilai', [TugasController::class, 'simpanNilai'])->name('tugas.nilai.simpan');
    Route::get('pembayarans/export/excel', [PembayaranController::class, 'exportExcel'])->name('pembayarans.export.excel');
    Route::get('pembayarans/export/pdf', [PembayaranController::class, 'exportPdf'])->name('pembayarans.export.pdf');
    Route::get('pembayarans/{pembayaran}/kwitansi', [PembayaranController::class, 'kwitansi'])->name('pembayarans.kwitansi');
    Route::resource('pembayarans', PembayaranController::class);
    Route::patch('pembayarans/{pembayaran}/status', [PembayaranController::class, 'updateStatus'])->name('pembayarans.status');
    Route::get('pembayaran-lainnyas/export/excel', [PembayaranLainnyaController::class, 'exportExcel'])->name('pembayaran-lainnyas.export.excel');
    Route::get('pembayaran-lainnyas/export/pdf', [PembayaranLainnyaController::class, 'exportPdf'])->name('pembayaran-lainnyas.export.pdf');
    Route::resource('pembayaran-lainnyas', PembayaranLainnyaController::class);
    Route::patch('pembayaran-lainnyas/{pembayaran_lainnya}/status', [PembayaranLainnyaController::class, 'updateStatus'])->name('pembayaran-lainnyas.status');
    Route::post('pembayarans/{pembayaran}/rencana', [RencanaAngsuranController::class, 'store'])->name('rencana.store');
    Route::patch('rencana-angsuran/{rencana}/batal', [RencanaAngsuranController::class, 'batal'])->name('rencana.batal');
    Route::patch('detail-angsuran/{detail}/denda', [RencanaAngsuranController::class, 'updateDenda'])->name('rencana.denda');
    Route::get('profil-sekolah', [ProfilSekolahController::class, 'edit'])->name('profil-sekolah.edit');
    Route::put('profil-sekolah', [ProfilSekolahController::class, 'update'])->name('profil-sekolah.update');
    Route::get('kenaikan-kelas', [KenaikanKelasController::class, 'index'])->name('kenaikan.index');
    Route::post('kenaikan-kelas/proses', [KenaikanKelasController::class, 'proses'])->name('kenaikan.proses');
    Route::get('siswas/template', [SiswaController::class, 'template'])->name('siswas.template');
    Route::post('siswas/import', [SiswaController::class, 'import'])->name('siswas.import');
    Route::get('siswas/{siswa}/kartu', [SiswaController::class, 'kartu'])->name('siswas.kartu');
    Route::post('siswas/{siswa}/qr', [SiswaController::class, 'regenerateQr'])->name('siswas.qr');
    Route::resource('siswas', SiswaController::class);
    Route::get('absensis', [AbsensiController::class, 'index'])->name('absensis.index');
    Route::post('absensis/batch', [AbsensiController::class, 'storeBatch'])->name('absensis.batch');
    Route::get('absensis/scan', [AbsensiController::class, 'scan'])->name('absensis.scan');
    Route::post('absensis/scan', [AbsensiController::class, 'storeScan'])->name('absensis.scan.store');
    Route::get('absensis/rekap', [AbsensiController::class, 'rekap'])->name('absensis.rekap');
    Route::get('absensis/rekap/excel', [AbsensiController::class, 'exportExcel'])->name('absensis.rekap.excel');
    Route::get('absensis/rekap/pdf', [AbsensiController::class, 'exportPdf'])->name('absensis.rekap.pdf');
    Route::get('pengaturans', [PengaturanController::class, 'index'])->name('pengaturans.index');
    Route::put('pengaturans', [PengaturanController::class, 'update'])->name('pengaturans.update');
    Route::get('whatsapp', [WhatsappController::class, 'index'])->name('whatsapp.index');
    Route::get('whatsapp/qr', [WhatsappController::class, 'qr'])->name('whatsapp.qr');
    Route::get('whatsapp/status', [WhatsappController::class, 'status'])->name('whatsapp.status');
    Route::post('whatsapp/disconnect', [WhatsappController::class, 'disconnect'])->name('whatsapp.disconnect');
    Route::post('pengaturans/reset', [PengaturanController::class, 'reset'])->name('pengaturans.reset');

    Route::prefix('cbt')->name('cbt.')->group(function () {
        Route::resource('banks', QuestionBankController::class);
        Route::post('banks/{bank}/questions', [QuestionBankQuestionController::class, 'store'])->name('banks.questions.store');
        Route::delete('banks/questions/{question}', [QuestionBankQuestionController::class, 'destroy'])->name('banks.questions.destroy');
        Route::post('exams/{exam}/banks/{bank}/import', [CbtExamController::class, 'importFromBank'])->name('exams.banks.import');
        Route::resource('exams', CbtExamController::class);
        Route::post('exams/{exam}/tokens', [CbtTokenController::class, 'store'])->name('exams.tokens.store');
        Route::delete('tokens/{token}', [CbtTokenController::class, 'destroy'])->name('tokens.destroy');
        Route::post('exams/{exam}/questions', [CbtQuestionController::class, 'store'])->name('exams.questions.store');
        Route::delete('questions/{question}', [CbtQuestionController::class, 'destroy'])->name('questions.destroy');
        Route::get('exams/{exam}/monitoring', [CbtMonitoringController::class, 'index'])->name('monitoring.index');
        Route::get('exams/{exam}/monitoring/data', [CbtMonitoringController::class, 'data'])->name('monitoring.data');
        Route::post('exams/{exam}/sessions/{session}/force', [CbtMonitoringController::class, 'force'])->name('sessions.force');
        Route::get('exams/{exam}/violations.csv', [CbtMonitoringController::class, 'violationsCsv'])->name('violations.csv');
        Route::get('exams/{exam}/results', [ExamResultController::class, 'index'])->name('results.index');
        Route::get('exams/{exam}/results.csv', [ExamResultController::class, 'export'])->name('results.export');
        Route::post('answers/{answer}/nilai', [ExamAnswerController::class, 'update'])->name('answers.nilai');
    });
});
