<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PapClassification extends Model
{
    protected $fillable = [
        'document_id',
        'category',
        'description',
        'beneficiary_government',
        'beneficiary_academe',
        'beneficiary_business',
        'beneficiary_civil_society',
        'beneficiary_media',
    ];

    protected function casts(): array
    {
        return [
            'beneficiary_government' => 'boolean',
            'beneficiary_academe' => 'boolean',
            'beneficiary_business' => 'boolean',
            'beneficiary_civil_society' => 'boolean',
            'beneficiary_media' => 'boolean',
        ];
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
