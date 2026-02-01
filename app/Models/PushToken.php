<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'push_token',
        'device_type',
        'user_type',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getAdminTokens()
    {
        return self::where('user_type', 'admin')
            ->where('active', true)
            ->pluck('push_token')
            ->toArray();
    }
}
