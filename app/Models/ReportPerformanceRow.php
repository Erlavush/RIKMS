<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportPerformanceRow extends Model
{
    protected $fillable = [
        'document_id',
        'activity_output_indicator',
        'target',
        'actual',
        'accomplishment_percentage',
        'status',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
