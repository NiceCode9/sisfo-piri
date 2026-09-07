{{-- Item navigasi sidebar; dipakai desktop & mobile. Variabel: $menus, $idPrefix (agar id collapse unik) --}}
@foreach ($menus as $menu)
    @if ($menu->is_header)
        <li><div class="sidebar-label">{{ $menu->name }}</div></li>
    @elseif ($menu->children->isNotEmpty())
        @php
            $open = $menu->isActive() ? 'show' : '';
            $expanded = $menu->isActive() ? 'true' : 'false';
        @endphp
        <li class="nav-item">
            <a href="#submenu-{{ $idPrefix ?? '' }}{{ $menu->id }}" class="nav-link {{ $menu->isActive() ? 'active' : '' }}"
               data-bs-toggle="collapse" role="button" aria-expanded="{{ $expanded }}" aria-controls="submenu-{{ $idPrefix ?? '' }}{{ $menu->id }}">
                @if ($menu->icon)<i class="{{ $menu->icon }} nav-icon"></i>@endif
                <span class="nav-label">{{ $menu->name }}</span>
                <i class="fa-solid fa-chevron-down menu-arrow"></i>
            </a>
            <div class="collapse {{ $open }}" id="submenu-{{ $idPrefix ?? '' }}{{ $menu->id }}">
                <ul class="nav flex-column ms-3">
                    @foreach ($menu->children as $child)
                        @php
                            $childHref = '#';
                            if ($child->route && Route::has($child->route)) {
                                $childHref = route($child->route);
                            } elseif ($child->url) {
                                $childHref = url($child->url);
                            }
                        @endphp
                        <li class="nav-item">
                            <a href="{{ $childHref }}" class="nav-link {{ $child->isActive() ? 'active' : '' }}">
                                @if ($child->icon)<i class="{{ $child->icon }} nav-icon"></i>@endif
                                <span class="nav-label">{{ $child->name }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </li>
    @else
        @php
            $href = '#';
            if ($menu->route && Route::has($menu->route)) {
                $href = route($menu->route);
            } elseif ($menu->url) {
                $href = url($menu->url);
            }
        @endphp
        <li class="nav-item">
            <a href="{{ $href }}" class="nav-link {{ $menu->isActive() ? 'active' : '' }}">
                @if ($menu->icon)<i class="{{ $menu->icon }} nav-icon"></i>@endif
                <span class="nav-label">{{ $menu->name }}</span>
            </a>
        </li>
    @endif
@endforeach
