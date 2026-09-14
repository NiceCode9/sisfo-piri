<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pemeliharaan — {{ $pesan }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">
    <div class="text-center p-4">
        <h1 class="h4">Sistem Dalam Pemeliharaan</h1>
        <p class="text-muted">{{ $pesan }}</p>
        <a href="{{ route('login') }}" class="btn btn-primary btn-sm">Login Super-Admin</a>
    </div>
</body>
</html>
