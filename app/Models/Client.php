<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

final class Client extends Model
{
    protected $guarded = [];

    protected $appends = ['logo_url'];

    private string $uploadPath = 'uploads/clients/';

    public function events(): HasMany
    {
        return $this->hasMany(ClientEvent::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(ClientUser::class);
    }

    public function getLogoUrlAttribute(): string|UrlGenerator|null
    {
        if (! isset($this->attributes['logo']) || ! $this->attributes['logo']) {
            return url('images/default-placeholder.png');
        }

        $fileurl = $this->uploadPath.trim($this->attributes['logo']);

        return Storage::url($fileurl);
    }

    public static function dropdownSearch($search = null): array
    {
        $items = static::query()->select(['id', 'name as text'])
            ->where(function ($query) use ($search) {
                if ($search) {
                    $query->where('name', 'like', "%{$search}%");
                }

                return $query;
            })
            ->paginate();

        return ['results' => $items->items(), 'pagination' => ['more' => (bool) $items->nextPageUrl(), 'current_page' => $items->getPageName()]];

    }
}
