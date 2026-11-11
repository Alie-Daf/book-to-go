<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeSetting extends Model
{
    protected $primaryKey = 'setting_key';
    public $incrementing = false;
    protected $keyType = 'string';
    const CREATED_AT = null;            // the table only has updated_at
    protected $guarded = [];

    // get_setting('commission_rate'): every feature reads its fees from here
    public static function get(string $key, $default = null)
    {
        return static::find($key)?->setting_value ?? $default;
    }
}