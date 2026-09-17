export default {
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        primary: { DEFAULT: '#1E40AF', dark: '#00288e', fixed: '#1e40af', 'on-fixed': '#ffffff' },
        secondary: { DEFAULT: '#F59E0B', fixed: '#fea619', 'on-fixed': '#1a1200' },
        tertiary: { DEFAULT: '#10B981', dark: '#00563a' },
        surface: { DEFAULT: '#f8f9ff', container: '#f1f5f9', 'container-lowest': '#ffffff' },
        'on-surface': '#0B1C30',
        outline: { DEFAULT: '#757684', variant: '#E2E8F0' },
        error: { DEFAULT: '#dc2626', 'on-error': '#ffffff' },
      },
      fontFamily: {
        jakarta: ['Plus Jakarta Sans', 'sans-serif'],
        inter: ['Inter', 'sans-serif'],
      },
    },
  },
  plugins: [],
};
