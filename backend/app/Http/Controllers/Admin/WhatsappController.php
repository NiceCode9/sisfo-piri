<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WhatsappController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:whatsapp.view', only: ['index', 'qr', 'status']),
            new Middleware('permission:whatsapp.manage', only: ['disconnect']),
        ];
    }

    public function index(): View
    {
        $status = $this->fetchStatus();

        return view('admin.whatsapp.index', compact('status'));
    }

    public function qr(): StreamedResponse|Response
    {
        $url = config('services.whatsapp.url');
        $base = $url ? preg_replace('#/kirim$#', '', $url) : 'http://127.0.0.1:3001';
        $qrUrl = rtrim($base, '/').'/qr';

        $request = Http::timeout(5);
        if (config('services.whatsapp.token')) {
            $request = $request->withToken(config('services.whatsapp.token'));
        }

        $response = $request->get($qrUrl);

        if (! $response->successful()) {
            abort($response->status(), $response->body() ?: 'QR tidak tersedia');
        }

        return response($response->body(), 200, ['Content-Type' => 'image/png']);
    }

    public function status(): JsonResponse
    {
        return response()->json($this->fetchStatus());
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $url = config('services.whatsapp.url');
        $base = $url ? preg_replace('#/kirim$#', '', $url) : 'http://127.0.0.1:3001';

        $http = Http::timeout(10);
        if (config('services.whatsapp.token')) {
            $http = $http->withToken(config('services.whatsapp.token'));
        }

        $http->post(rtrim($base, '/').'/logout');

        return redirect()->route('admin.whatsapp.index')->with('success', 'Disconnect dikirim. Jika perlu, scan ulang QR.');
    }

    protected function fetchStatus(): array
    {
        $url = config('services.whatsapp.url');
        $base = $url ? preg_replace('#/kirim$#', '', $url) : 'http://127.0.0.1:3001';
        $statusUrl = rtrim($base, '/').'/status';

        try {
            $request = Http::timeout(5);
            if (config('services.whatsapp.token')) {
                $request = $request->withToken(config('services.whatsapp.token'));
            }

            $response = $request->get($statusUrl);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Throwable) {
            //
        }

        return ['ok' => false, 'siap' => false, 'qrTersedia' => false, 'nomor' => null];
    }
}
