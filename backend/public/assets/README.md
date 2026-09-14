# Nexus Admin — Bootstrap 5 Dashboard Template

## 📁 Struktur Folder

```
admin-dashboard/
├── index.html          # Dashboard utama
├── analytics.html      # Halaman Analytics
├── users.html          # Halaman User Management
├── orders.html         # Halaman Orders
├── settings.html       # Halaman Settings
├── login.html          # Halaman Login
├── register.html       # Halaman Register
├── 404.html            # Page Not Found
├── 419.html            # Session Expired
├── 500.html            # Server Error
├── tables.html         # Halaman DataTable demo (jQuery + DataTables)
├── form-wizard.html    # Halaman Form Wizard multi-step
├── style.css           # Custom CSS (utility-first approach)
├── script.js           # Interaktivitas & charts (Vanilla JS)
├── particles.js        # Interactive particle background (auth & error pages)
└── README.md           # Dokumentasi ini
```

## 🚀 Cara Penggunaan

1. Buka `index.html` di browser modern (Chrome, Firefox, Edge, Safari)
2. Tidak perlu build tools — fully static HTML/CSS/JS
3. CDN Bootstrap 5.3.3 + FontAwesome 6.5.1 sudah terintegrasi

## 🎨 Design System

### Warna
- **Primary**: `#4f46e5` (Indigo)  
- **Secondary**: `#7c3aed` (Violet)  
- **Success**: `#22c55e`  
- **Warning**: `#f59e0b`  
- **Danger**: `#ef4444`  

### Font
- **Display/UI**: Plus Jakarta Sans (Google Fonts)
- **Code**: JetBrains Mono

### CSS Variables (Light/Dark)
```css
--bg-canvas, --bg-surface, --bg-surface-2
--accent-primary, --accent-hover, --accent-soft
--text-primary, --text-secondary, --text-muted
--border-color, --shadow-sm/md/lg
--sidebar-width: 260px
--navbar-height: 64px
```

## 📐 Layout System

### Sidebar
- Fixed 260px (desktop) → Offcanvas (mobile < 992px)
- Collapsible ke 68px icon-only mode
- Multi-level dropdown via Bootstrap Collapse
- State tersimpan di localStorage

### Navbar
- Sticky top, z-index 1030
- Global search dengan keyboard shortcut ⌘K
- Notification dropdown (340px) dengan unread state
- Dark mode toggle (light/dark toggle pill)
- User profile dropdown

### Content Grid
```html
<!-- 4 Stat Cards -->
<div class="row g-3">
  <div class="col-12 col-sm-6 col-xl-3">...</div>
</div>

<!-- Chart: 7/5 split -->
<div class="row g-3">
  <div class="col-12 col-lg-7">Revenue Bar</div>
  <div class="col-12 col-sm-6 col-lg-5 col-xl-3">Donut</div>
</div>

<!-- Tables: 8/4 split -->
<div class="row g-3">
  <div class="col-12 col-xl-8">Orders Table</div>
  <div class="col-12 col-xl-4">Tasks + Timeline</div>
</div>
```

## 🌗 Dark Mode

Dark mode menggunakan `data-theme="dark"` pada `<html>`:

```javascript
document.documentElement.setAttribute('data-theme', 'dark');
```

Preferensi disimpan ke `localStorage` key `nexus-theme`.

## 📊 Charts

Charts dibuat dengan **Canvas API native** (tanpa library eksternal):
- `revenueChart` — Bar Chart (Monthly Revenue)
- `trafficChart` — Donut Chart (Traffic Sources)  
- `usersChart` — Line + Area Chart (User Growth)
- `perfChart` — Dual Area Chart (Performance)

Charts otomatis re-render saat toggle dark mode.

## 🔧 Bootstrap 5 Components Digunakan

| Component | Usage |
|-----------|-------|
| Grid System | `container-fluid, row, col-*` dengan responsive breakpoints |
| Flexbox | `d-flex, justify-content-between, align-items-center, gap-*` |
| Offcanvas | Mobile sidebar (`data-bs-toggle="offcanvas"`) |
| Dropdown | Notification, user menu, action menus |
| Collapse | Multi-level sidebar menus |
| Modal | Add User, Export, Confirm Delete |
| Toast | Notifikasi welcome + triggered |
| Alert | Dismissible alerts dengan variant |
| Form | Floating labels, validation, input-group, switch |
| Table | Responsive table dengan `table-responsive` |
| Badge | Status badges |
| Pagination | Order table pagination |
| Button Group | View mode selector |
| Breadcrumb | Navigation path |

## 📱 Responsive Breakpoints

| Breakpoint | Behavior |
|-----------|----------|
| `<576px` (xs) | Single column, mobile-first |
| `≥576px` (sm) | 2-col stat cards |
| `≥768px` (md) | Breadcrumb visible |
| `≥992px` (lg) | Sidebar visible, 2/3 split charts |
| `≥1200px` (xl) | Full 4-col layout, user meta in navbar |

## ✨ Fitur Interaktif

- **Sidebar collapse** — desktop toggle + mobile offcanvas
- **Dark mode toggle** — localStorage persistent
- **Number counter animation** — IntersectionObserver
- **Task checkboxes** — klik untuk toggle complete state
- **Table row selection** — master checkbox + individual
- **Toast notifications** — auto-show welcome + trigger button
- **Keyboard shortcut** — ⌘K / Ctrl+K untuk focus search
- **Charts redraw** — otomatis saat ganti theme

## 🛠️ Kustomisasi

### Ganti warna primary:
```css
:root {
  --accent-primary: #your-color;
  --accent-hover: #your-hover-color;
}
```

### Tambah menu sidebar:
```html
<li class="nav-item">
  <a href="#" class="nav-link">
    <i class="bi bi-icon-name nav-icon"></i>
    <span class="nav-label">Menu Item</span>
  </a>
</li>
```

### Tambah stat card:
```html
<div class="col-12 col-sm-6 col-xl-3">
  <div class="stat-card animate-in">
    <div class="stat-icon-wrap" style="background:rgba(R,G,B,0.12);color:#HEX;">
      <i class="bi bi-icon"></i>
    </div>
    <div class="stat-value" data-count="1234">0</div>
    <div class="stat-label">Label</div>
    <div class="stat-trend trend-up">↑ 5% growth</div>
  </div>
</div>
```
