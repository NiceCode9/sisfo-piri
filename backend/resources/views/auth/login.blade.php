<!doctype html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Masuk - {{ config('app.name', 'Sisfo Sekolah') }}</title>
    <meta name="description" content="Masuk untuk mengakses dashboard dan layanan sekolah." />
    <meta name="robots" content="noindex, nofollow" />

    <script>
        (function () {
            try {
                document.documentElement.setAttribute('data-theme', localStorage.getItem('nexus-theme') || 'light');
            } catch (e) {}
        })();
    </script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="{{ asset('assets/style.css') }}" />
    <style>
        .login-shell { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; background: var(--bg-canvas, #f0f2f7); }
        .login-card { width: 100%; max-width: 380px; background: var(--bg-surface, #fff); border: 1px solid var(--border-color, #e2e8f0); border-radius: 12px; padding: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .login-brand { text-align: center; margin-bottom: 1.5rem; }
        .login-brand-mark { display: inline-grid; place-items: center; width: 44px; height: 44px; border-radius: 10px; background: var(--accent-primary, #4f46e5); color: #fff; font-weight: 800; font-size: 1.1rem; }
        .login-brand-name { margin-top: .75rem; font-size: 1.1rem; font-weight: 700; color: var(--text-primary, #0f172a); }
        .login-brand-sub { font-size: .8rem; color: var(--text-muted, #94a3b8); }
        .login-field { margin-bottom: 1rem; }
        .login-field label { display: block; font-size: .85rem; font-weight: 500; color: var(--text-secondary, #64748b); margin-bottom: .35rem; }
        .login-field input { width: 100%; padding: .6rem .75rem; border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; font-size: .9rem; color: var(--text-primary, #0f172a); background: var(--bg-surface, #fff); transition: border-color .15s, box-shadow .15s; }
        .login-field input:focus { outline: none; border-color: var(--accent-primary, #4f46e5); box-shadow: 0 0 0 3px rgba(79,70,229,.12); }
        .login-field input.is-invalid { border-color: #ef4444; }
        .login-submit { width: 100%; padding: .65rem; border: none; border-radius: 8px; background: var(--accent-primary, #4f46e5); color: #fff; font-size: .9rem; font-weight: 600; cursor: pointer; transition: background .15s; }
        .login-submit:hover { background: var(--accent-hover, #4338ca); }
        .login-alert { display: flex; align-items: center; gap: .5rem; padding: .65rem .85rem; margin-bottom: 1rem; border-radius: 8px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-size: .85rem; }
        .login-footer { text-align: center; margin-top: 1.5rem; font-size: .75rem; color: var(--text-muted, #94a3b8); }
        .login-remember { display: flex; align-items: center; gap: .4rem; margin-bottom: 1rem; font-size: .85rem; color: var(--text-secondary, #64748b); }
        .login-remember input { width: 15px; height: 15px; }
        .password-wrap { position: relative; }
        .password-toggle { position: absolute; right: .5rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted, #94a3b8); cursor: pointer; padding: .25rem; }
        .password-toggle:hover { color: var(--text-secondary, #64748b); }
    </style>
</head>
<body class="login-shell">
    <div class="login-card">
        <div class="login-brand">
            <div class="login-brand-mark">{{ mb_substr(config('app.name', 'S'), 0, 1) }}</div>
            <div class="login-brand-name">{{ config('app.name', 'Sisfo Sekolah') }}</div>
            <div class="login-brand-sub">Masuk ke dashboard</div>
        </div>

        @if ($errors->any())
            <div class="login-alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="login-field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="{{ old('username') }}"
                       placeholder="Masukkan username" autocomplete="username" required autofocus
                       class="@error('username') is-invalid @enderror" />
            </div>
            <div class="login-field">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.35rem;">
                    <label for="password" style="margin-bottom:0;">Password</label>
                </div>
                <div class="password-wrap">
                    <input type="password" id="password" name="password" placeholder="Masukkan password"
                           autocomplete="current-password" required class="@error('password') is-invalid @enderror" />
                    <button type="button" class="password-toggle" onclick="togglePassword()" tabindex="-1" aria-label="Tampilkan password">
                        <i class="fa-solid fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>
            <div class="login-remember">
                <input type="checkbox" id="remember" name="remember" />
                <label for="remember">Ingat saya</label>
            </div>
            <button type="submit" class="login-submit">Masuk</button>
        </form>

        <div class="login-footer">
            &copy; {{ date('Y') }} {{ config('app.name', 'Sisfo Sekolah') }}
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fa-solid fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fa-solid fa-eye';
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>