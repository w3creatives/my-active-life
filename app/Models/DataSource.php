<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class DataSource extends Model
{
    use HasFactory;

    protected $appends = ['image_url'];

    public static function authUrls($key = null)
    {
        $authUrls = [
            'fitbit' => route('fitbit.oauth', 'app'),
            'strava' => route('strava.oauth'),
            'garmin' => route('garmin.oauth', 'app'),
        ];

        if (! $key) {
            return $authUrls;
        }

        if (isset($authUrls[$key])) {
            return $authUrls[$key];
        }

        return null;
    }

    public function scopeExceptManual($query)
    {
        return $query->where('short_name', '!=', 'manual');
    }

    public function getImageUrlAttribute(): string|\Illuminate\Contracts\Routing\UrlGenerator|null
    {
        if ($this->short_name === 'manual') {
            return null;
        }

        return url(sprintf('static/sources/%s.png', $this->short_name));

    }

    public function getOauthUrl22Attribute()
    {
        return self::authUrls($this->short_name);
    }

    public function sourceProfile(): HasOne
    {
        return $this->hasOne(DataSourceProfile::class, 'data_source_id', 'id');
    }

    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, DataSourceProfile::class, 'data_source_id', 'id','id','user_id');
    }
}
