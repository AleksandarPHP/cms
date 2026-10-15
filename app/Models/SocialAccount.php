<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    public const FACEBOOK = 'facebook';

    public const INSTAGRAM = 'instagram';

    protected $fillable = [
        'user_id',
        'provider',
        'account_id',
        'account_name',
        'access_token',
    ];

    protected $hidden = [
        'access_token',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
