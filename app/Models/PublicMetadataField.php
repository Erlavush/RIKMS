<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicMetadataField extends Model
{
    protected $fillable = ['document_id', 'field_name', 'is_public'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
