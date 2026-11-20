/**
 * LoLeS course material editor.
 * The same form powers new lessons and edits of existing materials.
 */
document.addEventListener('DOMContentLoaded', () => {
    const libraryUrl = document.getElementById('course-library-root')?.dataset.searchUrl;
    if (!libraryUrl) return;

    const createElement = (tag, className, content) => {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (content !== undefined) element.textContent = content;
        return element;
    };

    const initEditor = (form) => {
        if (!window.tinymce) return;

        const field = form.querySelector('.course-rich-editor');
        if (!field || field.disabled || !field.getClientRects().length) return;
        if (field.dataset.editorReady === '1') return;
        field.dataset.editorReady = '1';

        window.tinymce.init({
            target: field,
            license_key: 'gpl',
            language: 'ru',
            height: 420,
            menubar: 'edit insert format table tools',
            plugins: 'lists link table image code wordcount autoresize',
            toolbar: 'undo redo | blocks | bold italic underline strikethrough | ' +
                'alignleft aligncenter alignright | bullist numlist | outdent indent | ' +
                'link image table | removeformat code',
            toolbar_mode: 'wrap',
            automatic_uploads: false,
            paste_data_images: false,
            convert_urls: false,
            link_default_target: '_blank',
            promotion: false,
            branding: false,
            setup: (editor) => {
                editor.on('change input undo redo', () => editor.save());
            }
        }).catch(() => {
            // The plain textarea remains fully usable if the editor fails.
            field.dataset.editorReady = '0';
        });
    };

    document.querySelectorAll('.course-item-form').forEach((form) => {
        const kind = form.querySelector('.course-type-select');
        const title = form.querySelector('.course-item-title');
        const results = form.querySelector('.course-gallery-results');
        const query = form.querySelector('.course-gallery-search');
        const packageId = form.querySelector('.course-package-id');
        const packageUpload = form.querySelector('.course-package-upload');
        const selected = form.querySelector('.course-gallery-selected');
        const formatBadge = form.querySelector('.course-gallery-format');

        let requestSequence = 0;
        let debounce = null;
        let lastFormat = null;
        let controller = null;

        const status = (message) => {
            results.replaceChildren(createElement('div', 'col-12 small text-secondary', message));
        };

        const render = (entries) => {
            results.replaceChildren();
            if (entries.length === 0) {
                status(query.value.trim() ? 'Ничего не найдено. Попробуйте другой запрос или загрузите новый пакет.'
                    : 'В библиотеке пока нет пакетов этого формата. Загрузите первый.');
                return;
            }

            for (const entry of entries) {
                const wrapper = createElement('div', 'col-lg-6');
                const card = createElement('button', 'btn btn-outline-secondary text-start w-100 h-100 p-3 course-library-card');
                card.type = 'button';
                card.setAttribute('aria-pressed', String(String(entry.id) === packageId.value));

                const row = createElement('div', 'd-flex gap-2 align-items-center mb-1');
                const glyph = createElement('i', entry.format === 'h5p' ? 'bi bi-puzzle text-primary' : 'bi bi-box-seam text-primary');
                row.append(glyph, createElement('strong', '', entry.title));
                card.append(row);

                const info = [entry.version, (entry.size / 1048576).toFixed(1) + ' МБ'].filter(Boolean).join(' · ');
                card.append(createElement('div', 'small text-secondary', info));

                if (Array.isArray(entry.tags) && entry.tags.length) {
                    const tags = createElement('div', 'mt-2 d-flex flex-wrap gap-1');
                    for (const tag of entry.tags) {
                        tags.append(createElement('span', 'badge rounded-pill text-bg-light border', tag));
                    }
                    card.append(tags);
                }

                if (String(entry.id) === packageId.value) {
                    card.classList.add('active');
                }

                card.addEventListener('click', () => {
                    packageId.value = String(entry.id);
                    packageUpload.value = '';
                    if (!title.value.trim()) title.value = entry.title;
                    selected.textContent = 'Выбран материал: ' + entry.title;
                    results.querySelectorAll('button.course-library-card').forEach(other => {
                        const active = other === card;
                        other.classList.toggle('active', active);
                        other.setAttribute('aria-pressed', String(active));
                    });
                });

                wrapper.append(card);
                results.append(wrapper);
            }
        };

        const search = async () => {
            if (!['scorm', 'h5p'].includes(kind.value)) return;

            const current = ++requestSequence;
            controller?.abort();
            controller = new AbortController();
            status('Ищем материалы…');

            try {
                const url = new URL(libraryUrl, location.href);
                url.searchParams.set('format', kind.value);
                url.searchParams.set('q', query.value.trim());
                const response = await fetch(url.toString(), {
                    signal: controller.signal,
                    headers: {Accept: 'application/json'},
                    cache: 'no-store',
                    credentials: 'same-origin'
                });
                if (!response.ok) throw new Error('Не получилось загрузить библиотеку.');
                const data = await response.json();
                if (current === requestSequence) render(data.items || []);
            } catch (error) {
                if (error.name !== 'AbortError' && current === requestSequence) {
                    status('Не удалось найти материалы. Проверьте соединение и повторите поиск.');
                }
            }
        };

        query.addEventListener('input', () => {
            clearTimeout(debounce);
            debounce = setTimeout(search, 180);
        });

        packageUpload.addEventListener('change', () => {
            if (!packageUpload.files.length) return;
            packageId.value = '';
            const fileName = packageUpload.files[0].name.replace(/\.(zip|h5p)$/i, '');
            if (!title.value.trim()) title.value = fileName;
            selected.textContent = 'Будет загружен новый пакет: ' + packageUpload.files[0].name;
            results.querySelectorAll('button').forEach(button => {
                button.classList.remove('active');
                button.setAttribute('aria-pressed', 'false');
            });
        });

        const refresh = () => {
            const type = kind.value;
            const usesPackage = ['scorm', 'h5p'].includes(type);

            form.querySelectorAll('.course-type-field').forEach(group => {
                const shown = group.dataset.types.split(',').includes(type);
                group.hidden = !shown;
                group.querySelectorAll('input, textarea, select').forEach(field => {
                    field.disabled = !shown;
                });
            });
            const parent = form.querySelector('.course-parent-selector');
            if (parent) {
                parent.hidden = type === 'section';
                parent.querySelector('select').disabled = type === 'section';
            }
            title.required = !usesPackage;
            formatBadge.textContent = type.toUpperCase();

            if (usesPackage && type !== lastFormat) {
                packageId.value = type === (form.dataset.existingFormat || '') ? packageId.defaultValue : '';
                packageUpload.value = '';
                query.value = '';
                selected.textContent = packageId.value ? 'Ранее выбранный пакет сохранён' : '';
                search();
            }
            lastFormat = type;
            if (type === 'lesson') requestAnimationFrame(() => initEditor(form));
        };

        kind.addEventListener('change', refresh);
        form.addEventListener('submit', () => window.tinymce?.triggerSave());
        refresh();

        // A textarea within a collapsed edit/create form cannot be initialized
        // until Bootstrap makes it visible.
        form.closest('.collapse')?.addEventListener('shown.bs.collapse', () => refresh());
    });

    document.addEventListener('shown.bs.collapse', event => {
        event.target.querySelectorAll('.course-item-form').forEach(form => {
            if (form.querySelector('.course-type-select')?.value === 'lesson') {
                initEditor(form);
            }
        });
    });
});
