<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    public const RESEARCH_STUDY = 'research_study';

    public const TERMINAL_REPORT = 'terminal_report';

    public const PROJECT_ACCOMPLISHMENT_REPORT = 'project_accomplishment_report';

    protected $fillable = [
        'agency_id',
        'uploaded_by',
        'document_type',
        'title',
        'description',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'status',
        'year',
        'category',
        'access_mode',
        'embargo_until',
        'external_url',
        'owner_name',
        'owner_email',
        'notify_access_requests',
        'notify_research_inquiries',
        'send_copy_to_agency_admin',
        'is_ai_tagged',
        'completion_score',
        'digital_library_score',
        'submitted_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'embargo_until' => 'date',
            'notify_access_requests' => 'boolean',
            'notify_research_inquiries' => 'boolean',
            'send_copy_to_agency_admin' => 'boolean',
            'is_ai_tagged' => 'boolean',
            'submitted_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function metadata()
    {
        return $this->hasOne(DocumentMetadata::class);
    }

    public function publicFields()
    {
        return $this->hasMany(PublicMetadataField::class);
    }

    public function sdgTags()
    {
        return $this->belongsToMany(SdgTag::class, 'document_sdg')->withPivot(['source', 'confidence'])->withTimestamps();
    }

    public function accessRequests()
    {
        return $this->hasMany(AccessRequest::class);
    }

    public function performanceRows()
    {
        return $this->hasMany(ReportPerformanceRow::class);
    }

    public function financial()
    {
        return $this->hasOne(ReportFinancial::class);
    }

    public function papClassifications()
    {
        return $this->hasMany(PapClassification::class);
    }

    public function highlights()
    {
        return $this->hasMany(Highlight::class);
    }

    public function isResearchStudy(): bool
    {
        return $this->document_type === self::RESEARCH_STUDY;
    }

    public function usesReportFlow(): bool
    {
        return in_array($this->document_type, [self::TERMINAL_REPORT, self::PROJECT_ACCOMPLISHMENT_REPORT], true);
    }

    public function documentTypeLabel(): string
    {
        return match ($this->document_type) {
            self::TERMINAL_REPORT => 'Terminal Report',
            self::PROJECT_ACCOMPLISHMENT_REPORT => 'Project Accomplishment Report',
            default => 'Research Study',
        };
    }

    public function statusLabel(): string
    {
        return str($this->status)->replace('_', ' ')->title()->toString();
    }

    public function accessModeLabel(): string
    {
        return match ($this->access_mode) {
            'request_access' => 'Request Access',
            'restricted_admin' => 'Restricted (Admin Only)',
            'embargo_until_date' => 'Embargo Until Date',
            'external_link_only' => 'External Link Only',
            default => 'Public Download',
        };
    }

    public function publicFieldNames(): array
    {
        return $this->publicFields()
            ->where('is_public', true)
            ->pluck('field_name')
            ->all();
    }
}
