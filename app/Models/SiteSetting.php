<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Shop-wide text staff can change (home headline and subtitle). A missing key means "use the page's own text". */
class SiteSetting extends Model
{
    public const HOME_HEADLINE = 'home.headline';

    public const HOME_SUBTITLE = 'home.subtitle';

    /** JSON list of the shop's social accounts: [{platform, handle, url}], in display order. */
    public const SOCIAL_LINKS = 'social.links';

    public const SOCIAL_PLATFORMS = ['facebook', 'instagram', 'tiktok', 'x', 'youtube', 'telegram'];

    public const CREATED_AT = null;

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value', 'updated_by'];

    /** @return array<string, string> key => value */
    public static function values(array $keys): array
    {
        return static::whereIn('key', $keys)->pluck('value', 'key')->all();
    }

    /** Saves a value, or removes it (back to the built-in text) when empty. */
    public static function put(string $key, ?string $value, ?User $by = null): void
    {
        $value = trim((string) $value);
        if ($value === '') {
            static::whereKey($key)->delete();

            return;
        }
        static::updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => $by?->id]);
    }
}
