<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Builder;
use Spatie\Permission\Traits\HasRoles;

class Menu extends Model
{
     use HasRoles;

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
     * Scope untuk menu yang user memiliki permissionnya
     */
    public function scopeUserCanAccess(Builder $query)
    {
        return $query->where(function ($q) {
            $q->whereNull('permission')
                ->orWhere(function ($q) {
                    $q->whereNotNull('permission')
                        ->where(function ($q) {
                            $user = auth()->user();
                            if ($user) {
                                $q->whereIn('permission', $user->getAllPermissions()->pluck('name'));
                            }
                        });
                });
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
        if ($this->route && request()->routeIs($this->route . '*')) {
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
            return '<i class="' . $this->icon . '"></i>';
        }

        if (strpos($this->icon, 'bi-') !== false) {
            return '<i class="bi ' . $this->icon . '"></i>';
        }

        return '<i class="fas ' . $this->icon . '"></i>';
    }
}
