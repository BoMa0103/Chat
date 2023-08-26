<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Chat
 *
 * @property int $id
 * @property int $user_id_first
 * @property int $user_id_second
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Database\Factories\ChatFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Chat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat query()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereUserIdFirst($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereUserIdSecond($value)
 * @mixin \Eloquent
 */
class Chat extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
