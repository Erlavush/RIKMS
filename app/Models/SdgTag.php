<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SdgTag extends Model
{
    protected $fillable = ['number', 'name', 'short_name', 'color'];

    public function documents()
    {
        return $this->belongsToMany(Document::class, 'document_sdg')->withPivot(['source', 'confidence'])->withTimestamps();
    }

    public function label(): string
    {
        return 'SDG '.$this->number;
    }
}
