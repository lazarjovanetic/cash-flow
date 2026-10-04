<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = ['supplier_id', 'number', 'issued_on', 'term_days', 'amount'];

    protected $casts = [
        'issued_on' => 'date',
        'due_on' => 'date',
        'term_days' => 'integer',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // Rok plaćanja = datum fakture + valuta (dana); uvek se računa, nikad ne unosi ručno.
        static::saving(function (Invoice $invoice) {
            $invoice->due_on = $invoice->issued_on->copy()->addDays($invoice->term_days);
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('paid_on')->orderBy('id');
    }
}
