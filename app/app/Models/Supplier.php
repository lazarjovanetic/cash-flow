<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = ['name', 'pib', 'term_days'];

    protected $casts = ['term_days' => 'integer'];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }
}
