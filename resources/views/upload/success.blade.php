<x-app-layout title="Submission Success">
    @if ($document->isResearchStudy())
        <x-success-screen
            title="Research Successfully Submitted!"
            :message="'&quot;'.e($document->title).'&quot; has been submitted to the RIKMS repository and tagged with '.$document->sdgTags->count().' SDG goals.'"
        >
            <div class="tag-row centered">
                @foreach ($document->sdgTags as $sdg)
                    <span class="sdg-pill" style="--sdg-color: {{ $sdg->color }}">SDG {{ $sdg->number }} - {{ $sdg->short_name }}</span>
                @endforeach
            </div>
            <div class="success-actions">
                <a class="btn-primary" href="{{ route('repository') }}">View in Repository</a>
                <a class="btn-primary" href="{{ route('upload.new') }}">Upload Another Document</a>
            </div>
        </x-success-screen>
    @else
        <x-success-screen
            title="Report Submitted Successfully"
            message="Your report has been queued for repository processing and moderation."
        >
            <div class="success-actions">
                <a class="btn-primary" href="{{ route('access-requests.index') }}">View Submission Queue</a>
            </div>
        </x-success-screen>
    @endif
</x-app-layout>
