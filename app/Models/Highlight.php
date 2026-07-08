<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Highlight extends Model
{
    protected $fillable = ['document_id', 'title', 'description', 'file_path', 'is_featured'];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean'];
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
