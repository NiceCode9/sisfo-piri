<!doctype html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Nexus Admin — Login" />
    <title>Nexus Admin — Login</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="{{ asset('assets/style.css') }}" />
</head>
<body class="auth-body">
    <canvas id="particleCanvas"></canvas>

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo"><i class="fa-solid fa-hexagon"></i></div>
                <div class="auth-title">Welcome Back</div>
                <div class="auth-subtitle">Sign in to your Nexus account</div>
            </div>

            @if ($errors->any())
                <div class="auth-alert" id="loginAlert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3 form-group">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" class="form-control @error('username') is-invalid @enderror"
                        id="username" name="username" value="{{ old('username') }}"
                        placeholder="Enter your username" autocomplete="username" required autofocus />
                </div>
                <div class="mb-3 form-group">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <label class="form-label mb-0" for="password">Password</label>
                    </div>
                    <div class="position-relative">
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                            id="password" name="password" placeholder="Enter your password"
                            autocomplete="current-password" required />
                        <button type="button" class="btn btn-sm position-absolute end-0 top-50 translate-middle-y border-0 bg-transparent text-muted" onclick="togglePassword('password', this)" tabindex="-1">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember" />
                        <label class="form-check-label" for="remember" style="font-size: 13px; color: var(--text-secondary);">Remember me</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 mb-0">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In
                </button>
            </form>
        </div>
    </div>

    <script>
        function togglePassword(id, btn) {
            const input = document.getElementById(id);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fa-solid fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fa-solid fa-eye';
            }
        }
    </script>

    <script src="{{ asset('assets/particles.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
