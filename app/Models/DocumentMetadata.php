<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentMetadata extends Model
{
    protected $table = 'document_metadata';

    protected $fillable = [
        'document_id',
        'title',
        'abstract',
        'methodology',
        'review_of_related_literature',
        'theoretical_framework',
        'results_and_discussion',
        'keywords',
        'authors',
        'doi',
        'ai_confidence',
        'raw_ai_json',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'authors' => 'array',
            'raw_ai_json' => 'array',
        ];
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
