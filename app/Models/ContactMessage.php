<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A submission from the public /contact form. Deliberately plain (no relation to
 * Customer/User — most senders won't have an account) so staff have somewhere to
 * see these; see App\Filament\Resources\ContactMessageResource.
 */
class ContactMessage extends Model
{
    protected $fillable = ['name', 'email', 'message', 'status'];

    public function scopeNew(Builder $q): Builder
    {
        return $q->where('status', 'new');
    }
}
