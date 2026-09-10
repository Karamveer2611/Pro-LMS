<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Slug
{
    /**
     * Generate a unique slug for the given table/column, appending -2, -3, ...
     * on collision. Single authoritative slug-generation rule, used by every
     * model that needs one (Category, Course).
     */
    public static function unique(string $table, string $title, ?int $ignoreId = null, string $column = 'slug'): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $suffix = 2;

        while (
            DB::table($table)
                ->where($column, $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
