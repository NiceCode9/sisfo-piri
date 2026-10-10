import '@testing-library/react';
import { afterEach, vi } from 'vitest';
import { cleanup } from '@testing-library/react';

afterEach(() => {
  cleanup();
  vi.restoreAllMocks();
  localStorage.clear();
});

// jsdom tidak mengimplementasikan API ini; useExamGuard memanggil semuanya
// saat mount, dan tanpa stub, test gagal karena "not a function" alih-alih
// karena logika yang sedang diuji.
if (!document.fullscreenElement) {
  Object.defineProperty(document, 'fullscreenElement', {
    writable: true,
    configurable: true,
    value: document.documentElement,
  });
}

document.documentElement.requestFullscreen = vi.fn().mockResolvedValue(undefined);
document.exitFullscreen = vi.fn().mockResolvedValue(undefined);