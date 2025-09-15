<?php

namespace App\Models;

use App\Enums\BrazilianState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'document',
        'city',
        'state',
        'send_notification',
        'start_date',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'user_id' => 'integer',
        'state' => BrazilianState::class,
        'send_notification' => 'boolean',
        'start_date' => 'date',
    ];

    /**
     * Get the user that owns the employee.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Format document for display
     */
    public function getFormattedDocumentAttribute(): string
    {
        $document = $this->document;
        if (strlen($document) === 11) {
            return substr($document, 0, 3) . '.' .
                   substr($document, 3, 3) . '.' .
                   substr($document, 6, 3) . '-' .
                   substr($document, 9, 2);
        }
        return $document;
    }

    /**
     * Set document attribute (remove formatting)
     */
    public function setDocumentAttribute($value): void
    {
        $this->attributes['document'] = preg_replace('/[^0-9]/', '', $value);
    }
}
