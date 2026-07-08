<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('region')->nullable();
            $table->string('contact_email')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('agency_admin')->after('password');
            $table->foreignId('agency_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->string('avatar')->nullable()->after('agency_id');
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('document_type');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('category')->nullable();
            $table->string('access_mode')->default('public_download');
            $table->date('embargo_until')->nullable();
            $table->string('external_url')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('owner_email')->nullable();
            $table->boolean('notify_access_requests')->default(true);
            $table->boolean('notify_research_inquiries')->default(false);
            $table->boolean('send_copy_to_agency_admin')->default(false);
            $table->boolean('is_ai_tagged')->default(false);
            $table->unsignedTinyInteger('completion_score')->default(0);
            $table->unsignedTinyInteger('digital_library_score')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('document_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('abstract')->nullable();
            $table->text('methodology')->nullable();
            $table->text('review_of_related_literature')->nullable();
            $table->text('theoretical_framework')->nullable();
            $table->text('results_and_discussion')->nullable();
            $table->json('keywords')->nullable();
            $table->json('authors')->nullable();
            $table->string('doi')->nullable();
            $table->decimal('ai_confidence', 5, 2)->nullable();
            $table->json('raw_ai_json')->nullable();
            $table->timestamps();
        });

        Schema::create('public_metadata_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('field_name');
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            $table->unique(['document_id', 'field_name']);
        });

        Schema::create('sdg_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('number')->unique();
            $table->string('name');
            $table->string('short_name');
            $table->string('color');
            $table->timestamps();
        });

        Schema::create('document_sdg', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sdg_tag_id')->constrained()->cascadeOnDelete();
            $table->string('source')->nullable();
            $table->decimal('confidence', 4, 2)->nullable();
            $table->timestamps();
            $table->unique(['document_id', 'sdg_tag_id']);
        });

        Schema::create('access_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('requester_name')->nullable();
            $table->string('requester_email')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });

        Schema::create('report_performance_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('activity_output_indicator');
            $table->decimal('target', 12, 2)->nullable();
            $table->decimal('actual', 12, 2)->nullable();
            $table->decimal('accomplishment_percentage', 6, 2)->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('report_financials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('allotted_budget', 14, 2)->nullable();
            $table->decimal('released_amount', 14, 2)->nullable();
            $table->decimal('obligated_amount', 14, 2)->nullable();
            $table->decimal('utilized_amount', 14, 2)->nullable();
            $table->decimal('remaining_balance', 14, 2)->nullable();
            $table->decimal('budget_utilization_percentage', 6, 2)->nullable();
            $table->date('financial_as_of_date')->nullable();
            $table->timestamps();
        });

        Schema::create('pap_classifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->boolean('beneficiary_government')->default(false);
            $table->boolean('beneficiary_academe')->default(false);
            $table->boolean('beneficiary_business')->default(false);
            $table->boolean('beneficiary_civil_society')->default(false);
            $table->boolean('beneficiary_media')->default(false);
            $table->timestamps();
        });

        Schema::create('highlights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->json('details')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('highlights');
        Schema::dropIfExists('pap_classifications');
        Schema::dropIfExists('report_financials');
        Schema::dropIfExists('report_performance_rows');
        Schema::dropIfExists('access_requests');
        Schema::dropIfExists('document_sdg');
        Schema::dropIfExists('sdg_tags');
        Schema::dropIfExists('public_metadata_fields');
        Schema::dropIfExists('document_metadata');
        Schema::dropIfExists('documents');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['agency_id']);
            $table->dropColumn(['role', 'agency_id', 'avatar']);
        });

        Schema::dropIfExists('agencies');
    }
};
