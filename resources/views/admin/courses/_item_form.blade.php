@php($itemEditing = isset($item) && $item !== null)
<form method="POST" enctype="multipart/form-data" class="course-item-form"
      data-existing-format="{{ $itemEditing ? $item->type : '' }}"
      action="{{ $itemEditing
          ? route('admin.courses.modules.update', [$course, $item])
          : route('admin.courses.modules.store', $course) }}">
    @csrf
    @if($itemEditing) @method('PUT') @endif
    <input type="hidden" name="draft_token" value="{{ $draftToken ?? '' }}">
    <div class="row g-3">
        <div class="col-lg-7">
            <label class="form-label">Название *</label>
            <input name="title" required maxlength="255" class="form-control course-item-title"
                   value="{{ old('title', $itemEditing ? $item->title : '') }}"
                   placeholder="Например: Проверка средств индивидуальной защиты">
        </div>
        <div class="col-lg-5">
            <label class="form-label">Что добавляем?</label>
            <select name="type" class="form-select course-type-select">
                @foreach([
                    'lesson' => 'Текст и инструкция',
                    'video' => 'Видео',
                    'file' => 'Документ или файл',
                    'link' => 'Ссылка на страницу',
                    'test' => 'Проверка знаний',
                    'scorm' => 'SCORM — интерактивный курс',
                    'h5p' => 'H5P — интерактивное задание',
                    'section' => 'Раздел (папка)',
                ] as $type => $label)
                    <option value="{{ $type }}" @selected(old('type', $itemEditing ? $item->type : ($initialType ?? 'lesson')) === $type)>{{ $label }}</option>
                @endforeach
                @if($itemEditing && !in_array($item->type, ['lesson', 'video', 'file', 'link', 'test', 'section', 'scorm', 'h5p'], true))
                    <option value="{{ $item->type }}" selected>Старый тип: {{ $item->type }}</option>
                @endif
            </select>
        </div>
        <div class="col-12 course-type-field" data-types="lesson">
            <label class="form-label">Содержание</label>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <label class="form-label mb-0">Текст учебного материала</label>
                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                        data-bs-target="#editorAttachmentsDialog">
                    <i class="bi bi-paperclip me-1"></i> Прикреплённые файлы
                </button>
            </div>
            <p class="text-secondary small mb-2">
                Можно вставлять изображения, ссылки на документы, таблицы и списки.
                Изображение также можно перетащить прямо в текст. Кнопка
                <strong>На весь экран</strong> увеличивает рабочую область.
            </p>
            <textarea name="content" rows="30" class="form-control course-rich-editor"
                      placeholder="Напишите учебный материал понятным языком…">{{ old('content', $itemEditing ? $item->content : '') }}</textarea>
        </div>
        <div class="col-12 course-type-field" data-types="video">
            <label class="form-label">Ссылка на видео</label>
            <input name="video_url" type="url" class="form-control" value="{{ $itemEditing ? $item->video_url : '' }}"
                   placeholder="https://…">
            <div class="form-text">Для видео пока используется ссылка; загруженные видеофайлы можно добавить как файл.</div>
        </div>
        <div class="col-12 course-type-field" data-types="link">
            <label class="form-label">Адрес страницы</label>
            <input name="external_url" type="url" class="form-control" value="{{ $itemEditing ? $item->external_url : '' }}"
                   placeholder="https://…">
        </div>
        <div class="col-12 course-type-field" data-types="file">
            <label class="form-label">Загрузить документ или файл</label>
            <input type="file" name="file" class="form-control">
            @if($itemEditing && $item->file_path)
                <div class="form-text">Сейчас прикреплён файл. Загружать новый необязательно.</div>
            @else
                <div class="form-text">До 100 МБ. PDF, Word, Excel, PowerPoint, изображения или видео.</div>
            @endif
        </div>
        <div class="col-12 course-type-field" data-types="test">
            <label class="form-label">Выберите готовый тест из этого курса</label>
            <select name="test_id" class="form-select">
                <option value="">Выберите тест</option>
                @foreach($course->tests as $test)
                    <option value="{{ $test->id }}" @selected($itemEditing && $item->test_id === $test->id)>{{ $test->title }}</option>
                @endforeach
            </select>
            @if(!$course->tests->count())
                <div class="form-text">Сначала создайте тест в блоке «Проверка знаний» ниже.</div>
            @endif
        </div>
        @include('admin.courses._package_gallery')

        <div class="col-12">
            <details>
                <summary class="text-secondary">Дополнительно: раздел, длительность, обязательность</summary>
                <div class="row g-3 mt-1">
                    <div class="col-md-6 course-parent-selector">
                        <label class="form-label">Разместить внутри раздела</label>
                        <select name="parent_id" class="form-select">
                            <option value="">В общем списке курса</option>
                            @foreach($course->modules->where('type', 'section')->whereNull('parent_id') as $parent)
                                @if(!$itemEditing || $item->id !== $parent->id)
                                    <option value="{{ $parent->id }}" @selected($itemEditing && $item->parent_id === $parent->id)>{{ $parent->title }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Сколько примерно занимает (минут)</label>
                        <input name="duration_minutes" type="number" min="0" class="form-control"
                               value="{{ $itemEditing ? $item->duration_minutes : '' }}">
                    </div>
                    <div class="col-md-6">
                        <input type="hidden" name="is_required" value="0">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_required" value="1"
                                   id="required-{{ $itemEditing ? $item->id : 'new' }}"
                                   @checked($itemEditing ? $item->is_required : true)>
                            <label class="form-check-label" for="required-{{ $itemEditing ? $item->id : 'new' }}">Обязательно для прохождения</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <input type="hidden" name="is_active" value="0">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1"
                                   id="active-{{ $itemEditing ? $item->id : 'new' }}"
                                   @checked($itemEditing ? $item->is_active : true)>
                            <label class="form-check-label" for="active-{{ $itemEditing ? $item->id : 'new' }}">Показывать учащемуся</label>
                        </div>
                    </div>
                </div>
            </details>
        </div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary" type="submit">{{ $itemEditing ? 'Сохранить изменения' : 'Добавить в курс' }}</button>
            <a href="{{ route('admin.courses.show', $course) }}" class="btn btn-outline-secondary">Вернуться к содержанию</a>
        </div>
    </div>
</form>
