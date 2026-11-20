<div class="col-12 course-type-field" data-types="scorm,h5p" hidden>
    <div class="border rounded-3 p-3 bg-light">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <div>
                <h4 class="h6 mb-1">Библиотека интерактивных материалов</h4>
                <div class="text-secondary small">Начните вводить название или тег — результаты появятся без перезагрузки.</div>
            </div>
            <span class="badge text-bg-secondary course-gallery-format"></span>
        </div>
        <label class="form-label" for="library-search-{{ $itemEditing ? $item->id : 'new' }}">Найти в библиотеке</label>
        <input id="library-search-{{ $itemEditing ? $item->id : 'new' }}" type="search"
               class="form-control course-gallery-search mb-2"
               placeholder="Например: охрана труда, сварка, безопасность…"
               autocomplete="off">
        <input type="hidden" class="course-package-id" name="course_package_id"
               value="{{ $itemEditing ? $item->course_package_id : '' }}">
        <div class="course-gallery-results row g-2 mb-2"
             role="group" aria-label="Выберите пакет из библиотеки">
            <div class="col-12 text-secondary small">Поиск материалов…</div>
        </div>
        <div class="course-gallery-selected small text-success mb-3" aria-live="polite"></div>
        <details class="course-gallery-upload border rounded-3 bg-white p-3">
            <summary class="fw-semibold"><i class="bi bi-cloud-arrow-up me-1"></i> Не нашли нужный? Загрузить новый</summary>
            <div class="row g-3 mt-1">
                <div class="col-lg-7">
                    <label class="form-label">Пакет SCORM (.zip) или H5P (.h5p)</label>
                    <input type="file" class="form-control course-package-upload"
                           name="package_upload" accept=".zip,.h5p">
                    <div class="form-text">До 100 МБ. Система проверит архив и добавит его в общую библиотеку.</div>
                </div>
                <div class="col-lg-5">
                    <label class="form-label">Теги (через запятую)</label>
                    <input type="text" name="package_tags" class="form-control"
                           placeholder="Безопасность, вахта, электрика"
                           maxlength="300">
                </div>
            </div>
        </details>
        <p class="small text-secondary mt-3 mb-0">
            Материал добавится в содержание курса. Оригинальный пакет останется в библиотеке для других курсов.
        </p>
    </div>
</div>
