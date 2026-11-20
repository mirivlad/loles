@extends('layouts.app')
@php($editing = $item !== null)
@section('title', $editing ? 'Редактировать материал — ' . $item->title : 'Новый материал')
@section('content')
<div class="container-fluid px-3 px-lg-5 py-3 py-lg-4">
    <main class="course-editor-shell mx-auto">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
            <div>
                <a href="{{ route('admin.courses.show', $course) }}" class="text-decoration-none small">
                    <i class="bi bi-arrow-left me-1"></i> Вернуться к содержанию
                </a>
                <div class="small text-secondary mt-2">{{ $course->title }}</div>
                <h1 class="h2 mb-0">{{ $editing ? 'Редактировать материал' : 'Новый материал' }}</h1>
            </div>
            @if($editing && $item->type !== 'section')
                <a href="{{ route('admin.courses.preview.material', [$course, $item]) }}"
                   class="btn btn-outline-secondary">
                    <i class="bi bi-eye me-1"></i> Как увидит учащийся
                </a>
            @endif
        </div>

        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Не удалось сохранить материал.</strong>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div id="course-library-root"
             data-search-url="{{ route('admin.courses.library.search', $course) }}"></div>
        <div id="course-attachment-manager"
             data-upload-url="{{ route('admin.courses.attachments.store', $course) }}"
             data-list-url="{{ route('admin.courses.attachments.index', $course) }}"
             data-delete-url-template="{{ route('admin.courses.attachments.destroy', [$course, '__ID__']) }}"
             data-module-id="{{ $item?->id }}"
             data-draft-token="{{ $draftToken }}"></div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-3 p-md-4 p-xl-5">
                @include('admin.courses._item_form')
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="editorAttachmentsDialog" tabindex="-1"
     aria-labelledby="editorAttachmentsTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5" id="editorAttachmentsTitle">Файлы материала</h2>
                    <p class="text-secondary small mb-0">Загрузите файл, вставьте ссылку в текст или используйте изображение.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <label for="courseEditorFileInput" class="form-label">Добавить изображения или документы</label>
                <input class="form-control" type="file" id="courseEditorFileInput" multiple
                       accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.mp4,.webm,.zip">
                <div class="form-text mb-3">
                    До 50 МБ на файл. Доступ к файлам получат только пользователи с доступом к курсу.
                </div>
                <div class="alert alert-danger d-none" id="courseEditorUploadError" role="alert"></div>
                <div class="small text-secondary mb-2" id="courseEditorUploadStatus" aria-live="polite"></div>
                <div class="list-group" id="courseEditorAttachmentsList">
                    <div class="list-group-item text-secondary">Загружаем список…</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script src="{{ asset('tinymce/js/tinymce/tinymce.min.js') }}"></script>
@vite('resources/js/course-builder.js')
@endpush
@endsection
