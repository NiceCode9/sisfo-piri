/**
 * Design tokens CBT — diturunkan dari mockup di
 * D:\Ilham\desain-halaman-cbt (code.html + DESIGN.md).
 *
 * Semua nilai ditambahkan lewat `extend`, bukan menimpa scale bawaan
 * Tailwind. Konsekuensinya `rounded-lg` tetap 8px dan `p-4` tetap 1rem,
 * persis seperti yang terlihat di screen.png — kartu besar memakai
 * `rounded-xl` (12px), tombol & opsi memakai `rounded-lg` (8px).
 *
 * Catatan: DESIGN.md (prosa) menyebut answered = #10B981 dan tombol submit
 * hijau emerald, tapi code.html + screen.png memakai hijau tua #003d27 dan
 * merah #ba1a1a. Yang di sini mengikuti mock + screenshot karena itu yang
 * benar-benar dirender.
 */
export default {
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        // --- Primary: saffron navy, inti aksi utama & header ---
        primary: '#00288e',
        'on-primary': '#ffffff',
        'primary-container': '#1e40af',
        'on-primary-container': '#a8b8ff',
        'primary-fixed': '#dde1ff',
        'primary-fixed-dim': '#b8c4ff',
        'on-primary-fixed': '#001453',
        'on-primary-fixed-variant': '#173bab',
        'inverse-primary': '#b8c4ff',

        // --- Secondary: amber, dedicated untuk status "Ragu-ragu" ---
        secondary: '#855300',
        'on-secondary': '#ffffff',
        'secondary-container': '#fea619',
        'on-secondary-container': '#684000',
        'secondary-fixed': '#ffddb8',
        'secondary-fixed-dim': '#ffb95f',
        'on-secondary-fixed': '#2a1700',
        'on-secondary-fixed-variant': '#653e00',

        // --- Tertiary: hijau, dedicated untuk status "Terjawab" ---
        tertiary: '#003d27',
        'on-tertiary': '#ffffff',
        'tertiary-container': '#00563a',
        'on-tertiary-container': '#3fd298',
        'tertiary-fixed': '#6ffbbe',
        'tertiary-fixed-dim': '#4edea3',
        'on-tertiary-fixed': '#002113',
        'on-tertiary-fixed-variant': '#005236',

        // --- Error / danger ---
        error: '#ba1a1a',
        'on-error': '#ffffff',
        'error-container': '#ffdad6',
        'on-error-container': '#93000a',

        // --- Surface: kedalaman lewat lapisan tonal, bukan drop shadow berat ---
        background: '#f8f9ff',
        'on-background': '#0b1c30',
        surface: '#f8f9ff',
        'surface-bright': '#f8f9ff',
        'surface-dim': '#cbdbf5',
        'surface-variant': '#d3e4fe',
        'surface-container-lowest': '#ffffff',
        'surface-container-low': '#eff4ff',
        'surface-container': '#e5eeff',
        'surface-container-high': '#dce9ff',
        'surface-container-highest': '#d3e4fe',
        'on-surface': '#0b1c30',
        'on-surface-variant': '#444653',
        'inverse-surface': '#213145',
        'inverse-on-surface': '#eaf1ff',
        outline: '#757684',
        'outline-variant': '#c4c5d5',
        'surface-tint': '#3755c3',
      },
      fontFamily: {
        // Plus Jakarta Sans untuk heading & teks soal (x-height besar,
        // mengurangi kelelahan mata saat baca teks panjang).
        jakarta: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
        // Inter untuk elemen operasional padat: nomor grid, digit timer,
        // badge, label tombol. Angka tabular-nya mencegah digit "bergeser"
        // saat hitung mundur berjalan.
        inter: ['Inter', 'system-ui', 'sans-serif'],
        'headline-lg': ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
        'headline-lg-mobile': ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
        'headline-md': ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
        'headline-sm': ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
        'body-lg': ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
        'body-md': ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
        'body-sm': ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
        'label-timer': ['Inter', 'system-ui', 'sans-serif'],
        'label-grid-num': ['Inter', 'system-ui', 'sans-serif'],
        'label-md': ['Inter', 'system-ui', 'sans-serif'],
        'label-sm': ['Inter', 'system-ui', 'sans-serif'],
      },
      fontSize: {
        'headline-lg': ['32px', { lineHeight: '40px', letterSpacing: '-0.02em', fontWeight: '700' }],
        'headline-lg-mobile': ['24px', { lineHeight: '32px', letterSpacing: '-0.01em', fontWeight: '700' }],
        'headline-md': ['24px', { lineHeight: '32px', fontWeight: '600' }],
        'headline-sm': ['20px', { lineHeight: '28px', fontWeight: '600' }],
        'body-lg': ['18px', { lineHeight: '28px', fontWeight: '400' }],
        'body-md': ['16px', { lineHeight: '26px', fontWeight: '400' }],
        'body-sm': ['14px', { lineHeight: '20px', fontWeight: '400' }],
        'label-timer': ['22px', { lineHeight: '28px', letterSpacing: '0.04em', fontWeight: '700' }],
        'label-grid-num': ['14px', { lineHeight: '18px', fontWeight: '600' }],
        'label-md': ['14px', { lineHeight: '20px', fontWeight: '600' }],
        'label-sm': ['12px', { lineHeight: '16px', fontWeight: '500' }],
      },
      spacing: {
        gutter: '1.5rem',
        margin: '1.5rem',
        'space-xs': '0.25rem',
        'space-sm': '0.5rem',
        'space-md': '1rem',
        'space-lg': '1.5rem',
        'space-xl': '2rem',
      },
    },
  },
  plugins: [],
};