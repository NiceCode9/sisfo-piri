interface IconProps {
  /**
   * Nama ligatur Material Symbols, contoh: 'shield_person', 'check_circle'.
   * Karena webfont ini bekerja lewat ligatur, nama yang salah ketik tidak
   * menghasilkan error — hanya kotak kosong atau teks harfiah. Jadi nama
   * harus dicek terhadap material symbols online saat menambah ikon baru.
   */
  name: string;
  /** Ukuran dalam px. Mockup memakai skala 14/15/16/18/20/22/24/26/28. */
  size?: number;
  /** Set true untuk varian terisi (FILL 1) — mis. flag & check_circle. */
  filled?: boolean;
  className?: string;
}

/**
 * Ikon Material Symbols Outlined via webfont (link di index.html).
 *
 * Dipakai alih-alih menambah dependency icon supaya tidak ada request
 * tambahan per ikon dan tidak ada bundle growth — webfont-nya sendiri
 * sudah di-cache browser setelah halaman pertama.
 */
export default function Icon({ name, size = 20, filled = false, className = '' }: IconProps) {
  return (
    <span
      aria-hidden="true"
      data-filled={filled ? 'true' : undefined}
      className={`material-symbols-outlined shrink-0 ${className}`}
      style={{ fontSize: `${size}px`, width: `${size}px`, height: `${size}px` }}
    >
      {name}
    </span>
  );
}