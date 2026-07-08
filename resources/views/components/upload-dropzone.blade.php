@props(['name' => 'document_file'])

<label class="upload-dropzone" data-file-dropzone>
    <input type="file" name="{{ $name }}" accept="application/pdf" class="sr-only" data-file-input>
    <span class="upload-icon"><x-icon name="upload" /></span>
    <strong data-file-title>Drag & drop your document here</strong>
    <span class="btn-primary small">Browse File</span>
    <small>PDF · Max 10 MB</small>
</label>
