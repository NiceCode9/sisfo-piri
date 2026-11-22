(function () {
    'use strict';

    const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="_token"]')?.value
        || '';

    const formScan = document.getElementById('form-scan');
    const inputKode = document.getElementById('kode-kartu');
    const panel = document.getElementById('daftar-absensi');
    const hasil = document.getElementById('hasil-scan');
    const video = document.getElementById('kamera-absensi');
    const statusKamera = document.getElementById('status-kamera');
    const tombolMulai = document.getElementById('mulai-kamera');

    let stream = null;
    let scanning = false;
    let lastCode = '';
    let lastScanAt = 0;
    let jedaHingga = 0;
    let rafId = null;

    function escapeHtml(teks) {
        return String(teks || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function tampilPesan(teks, ok) {
        if (!hasil) return;
        if (!teks) { hasil.innerHTML = ''; return; }
        hasil.innerHTML =
            '<div class="alert alert-' + (ok ? 'success' : 'danger') + ' py-2 px-3 mb-0 d-flex align-items-center gap-2" role="alert" style="font-size:13px;">' +
            '<i class="fa-solid ' + (ok ? 'fa-circle-check' : 'fa-circle-xmark') + '"></i>' +
            '<span>' + escapeHtml(teks) + '</span></div>';
        clearTimeout(hasil._timer);
        hasil._timer = setTimeout(function () { hasil.innerHTML = ''; }, ok ? 4000 : 6000);
    }

    function kirimScan(kode) {
        if (!formScan) return Promise.resolve();
        const data = new FormData(formScan);
        data.set('kode', kode);
        return fetch(formScan.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
            },
            body: data,
            credentials: 'same-origin',
        }).then(function (res) {
            return res.json().then(function (json) {
                return { ok: res.ok, json: json };
            }).catch(function () {
                return { ok: res.ok, json: {} };
            });
        });
    }

    function perbaruiPanel(html) {
        if (panel && html) {
            panel.innerHTML = html;
        }
    }

    function suksesScan(data) {
        perbaruiPanel(data.html);
        tampilPesan(data.message || 'Tercatat.', true);
        if (inputKode) {
            inputKode.value = '';
            inputKode.focus();
        }
    }

    function gagalScan(data) {
        tampilPesan((data && data.message) ? data.message : 'Gagal mencatat.', false);
    }

    function prosesKode(kode) {
        kode = (kode || '').trim();
        if (!kode) return;

        const now = Date.now();
        if (kode === lastCode && now - lastScanAt < 5000) return;
        if (now < jedaHingga) return;

        lastCode = kode;
        lastScanAt = now;

        kirimScan(kode).then(function (res) {
            if (res.ok && res.json) {
                jedaHingga = Date.now() + 2000;
                suksesScan(res.json);
            } else {
                gagalScan(res.json);
            }
        }).catch(function () {
            tampilPesan('Jaringan bermasalah.', false);
        });
    }

    function decodeFrame() {
        if (!scanning || !video || video.readyState < 2) {
            rafId = requestAnimationFrame(decodeFrame);
            return;
        }
        const now = Date.now();
        if (now < jedaHingga) {
            rafId = requestAnimationFrame(decodeFrame);
            return;
        }

        const canvas = document.createElement('canvas');
        const w = video.videoWidth;
        const h = video.videoHeight;
        if (w && h) {
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, w, h);
            if (window.jsQR) {
                const img = ctx.getImageData(0, 0, w, h);
                const code = window.jsQR(img.data, w, h);
                if (code && code.data) {
                    prosesKode(code.data);
                }
            }
        }

        setTimeout(function () {
            rafId = requestAnimationFrame(decodeFrame);
        }, 200);
    }

    function mulaiKamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            if (statusKamera) statusKamera.textContent = 'Kamera butuh HTTPS atau localhost. Gunakan input kode/NISN.';
            if (tombolMulai) tombolMulai.classList.add('d-none');
            return;
        }
        if (!window.isSecureContext) {
            if (statusKamera) statusKamera.textContent = 'Kamera butuh HTTPS atau localhost. Gunakan input kode/NISN.';
            if (tombolMulai) tombolMulai.classList.add('d-none');
            return;
        }
        if (!window.jsQR) {
            if (statusKamera) statusKamera.textContent = 'Pustaka pemindai gagal dimuat. Gunakan input kode/NISN.';
            return;
        }

        navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false })
            .then(function (s) {
                stream = s;
                if (video) {
                    video.srcObject = s;
                    video.classList.remove('d-none');
                    video.play();
                }
                scanning = true;
                if (statusKamera) statusKamera.textContent = 'Kamera aktif. Arahkan ke QR kartu.';
                if (tombolMulai) tombolMulai.classList.add('d-none');
                rafId = requestAnimationFrame(decodeFrame);
            })
            .catch(function () {
                if (statusKamera) statusKamera.textContent = 'Izin kamera ditolak. Gunakan input kode/NISN.';
            });
    }

    function hentikanKamera() {
        scanning = false;
        if (rafId) cancelAnimationFrame(rafId);
        if (stream) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            stream = null;
        }
        if (video) {
            video.srcObject = null;
            video.classList.add('d-none');
        }
    }

    if (tombolMulai) {
        tombolMulai.addEventListener('click', mulaiKamera);
    }

    if (formScan && inputKode) {
        formScan.addEventListener('submit', function (e) {
            e.preventDefault();
            prosesKode(inputKode.value);
        });
    }

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (!form.matches('[data-manual]')) return;
        e.preventDefault();
        const data = new FormData(form);
        fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,
            },
            body: data,
            credentials: 'same-origin',
        }).then(function (res) {
            return res.json().then(function (json) {
                return { ok: res.ok, json: json };
            }).catch(function () {
                return { ok: res.ok, json: {} };
            });
        }).then(function (res) {
            if (res.ok && res.json && res.json.html) {
                perbaruiPanel(res.json.html);
                tampilPesan(res.json.message || 'Tercatat.', true);
            } else {
                gagalScan(res.json);
            }
        }).catch(function () {
            tampilPesan('Jaringan bermasalah.', false);
        });
    });

    window.addEventListener('pagehide', hentikanKamera);
})();
