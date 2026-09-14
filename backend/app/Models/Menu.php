<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;

class Menu extends Model
{
    protected $fillable = [
        'name',
        'icon',
        'route',
        'permission',
        'parent_id',
        'order',
        'is_active',
        'is_header',
        'group',
        'url',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_header' => 'boolean',
    ];

    /**
     * Relasi ke parent menu
     */
    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    /**
     * Relasi ke child menus
     */
    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')
            ->where('is_active', true)
            ->orderBy('order');
    }

    /**
     * Scope untuk menu parent saja
     */
    public function scopeParents(Builder $query)
    {
        return $query->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('order');
    }

    /**
     * Scope untuk menu header
     */
    public function scopeHeaders(Builder $query)
    {
        return $query->where('is_header', true)
            ->where('is_active', true)
            ->orderBy('order');
    }

    /**
     * Relasi ke permissions (Spatie) via pivot menu_permission.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'menu_permission');
    }

    /**
     * Scope untuk menu yang user memiliki permissionnya.
     * Menu tanpa permission apa pun dianggap publik.
     */
    public function scopeUserCanAccess(Builder $query, ?User $user = null)
    {
        $user ??= auth()->user();

        return $query->where(function ($q) use ($user) {
            $q->whereDoesntHave('permissions')->whereNull('permission');

            if ($user) {
                $names = $user->getAllPermissions()->pluck('name');
                $q->orWhere(function ($q) use ($names) {
                    $q->whereHas('permissions', fn ($q) => $q->whereIn('name', $names))
                        ->orWhereIn('permission', $names);
                });
            }
        });
    }

    /**
     * Cek apakah menu memiliki child aktif
     */
    public function hasActiveChildren()
    {
        return $this->children()->exists();
    }

    /**
     * Get full url berdasarkan route atau url
     */
    public function getFullUrlAttribute()
    {
        if ($this->route) {
            return route($this->route);
        }

        if ($this->url) {
            return url($this->url);
        }

        return '#';
    }

    /**
     * Cek apakah menu saat ini aktif
     */
    public function isActive()
    {
        if ($this->route && request()->routeIs($this->route.'*')) {
            return true;
        }

        if ($this->children->count() > 0) {
            foreach ($this->children as $child) {
                if ($child->isActive()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Format icon untuk tampilan
     */
    public function getIconHtmlAttribute()
    {
        if (strpos($this->icon, 'fa-') !== false) {
            return '<i class="'.$this->icon.'"></i>';
        }

        if (strpos($this->icon, 'bi-') !== false) {
            return '<i class="bi '.$this->icon.'"></i>';
        }

        return '<i class="fas '.$this->icon.'"></i>';
    }
}
