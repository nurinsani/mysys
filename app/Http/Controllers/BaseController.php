<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Support\Facades\Cache;

abstract class BaseController extends Controller
{
    /**
     * Ambil menu sidebar sesuai role user yang login.
     * Menggunakan cache per role_id selama 5 menit (300 detik)
     * untuk mengurangi query ke database.
     */
    protected function getMenus()
    {
        $roleId = auth()->user()->role_id;

        return Cache::remember("menus_role_{$roleId}", 300, function () use ($roleId) {
            return Menu::whereNull('parent_id')
                ->where(function ($query) use ($roleId) {
                    $query->where('role_id', $roleId)
                          ->orWhereNull('role_id');
                })
                ->with(['children' => function ($query) use ($roleId) {
                    $query->where('role_id', $roleId)
                          ->orWhereNull('role_id');
                }])
                ->orderBy('order')
                ->get();
        });
    }

    /**
     * Hapus cache menu untuk role tertentu.
     * Dipanggil jika ada perubahan data menu di database.
     */
    public static function clearMenuCache(?int $roleId = null): void
    {
        if ($roleId !== null) {
            Cache::forget("menus_role_{$roleId}");
        } else {
            // Hapus cache untuk semua role yang ada (1 s.d. 10)
            foreach (range(1, 10) as $id) {
                Cache::forget("menus_role_{$id}");
            }
        }
    }
}
