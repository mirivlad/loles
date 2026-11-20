/**
 * LoLeS editor: large WYSIWYG, secure file uploads and reusable SCORM/H5P
 * library. The editor has one form per page, not a collection of tiny panels.
 */
document.addEventListener('DOMContentLoaded', () => {
    const galleryRoot = document.getElementById('course-library-root');
    if (!galleryRoot) return;

    const attachmentRoot = document.getElementById('course-attachment-manager');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const form = document.querySelector('.course-item-form');
    if (!form) return;

    const kind = form.querySelector('.course-type-select');
    const title = form.querySelector('.course-item-title');
    const results = form.querySelector('.course-gallery-results');
    const query = form.querySelector('.course-gallery-search');
    const packageId = form.querySelector('.course-package-id');
    const packageUpload = form.querySelector('.course-package-upload');
    const selected = form.querySelector('.course-gallery-selected');
    const formatBadge = form.querySelector('.course-gallery-format');
    const field = form.querySelector('.course-rich-editor');

    const make = (tag, className = '', text = undefined) => {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (text !== undefined) element.textContent = text;
        return element;
    };
    const escapeHtml = text => String(text).replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[char]);

    const getEditor = () => window.tinymce?.editors?.find(editor => editor.targetElm === field);
    let bookmark = null;

    const attachModal = document.getElementById('editorAttachmentsDialog');
    const list = document.getElementById('courseEditorAttachmentsList');
    const fileInput = document.getElementById('courseEditorFileInput');
    const errorBox = document.getElementById('courseEditorUploadError');
    const statusBox = document.getElementById('courseEditorUploadStatus');

    const context = () => attachmentRoot?.dataset.moduleId
        ? {module_id: attachmentRoot.dataset.moduleId}
        : {draft_token: attachmentRoot?.dataset.draftToken};

    const errorMessage = async response => {
        try {
            const payload = await response.json();
            return payload.message || Object.values(payload.errors || {}).flat().join('\n') || 'Ошибка загрузки файла.';
        } catch {
            return 'Ошибка загрузки файла (HTTP ' + response.status + ').';
        }
    };

    const upload = async file => {
        if (!attachmentRoot) throw new Error('Загрузка файлов недоступна.');
        const body = new FormData();
        body.append('file', file);
        const ctx = context();
        Object.entries(ctx).forEach(([name, value]) => body.append(name, value));
        const response = await fetch(attachmentRoot.dataset.uploadUrl, {
            method: 'POST',
            body,
            credentials: 'same-origin',
            headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
        });
        if (!response.ok) throw new Error(await errorMessage(response));
        const item = await response.json();
        await reloadFiles();
        return item;
    };

    const insert = (item, asImage = false) => {
        const editor = getEditor();
        if (!editor) {
            alert('Выберите тип «Текст и инструкция», чтобы вставить файл в текст.');
            return;
        }

        editor.focus();
        if (bookmark) {
            try { editor.selection.moveToBookmark(bookmark); } catch {}
        }
        editor.insertContent(asImage
            ? '<img src="' + escapeHtml(item.url) + '" alt="' + escapeHtml(item.name) + '">'
            : '<a href="' + escapeHtml(item.download_url) + '">' + escapeHtml(item.name) + '</a>');
        editor.save();

        if (attachModal && window.bootstrap) {
            window.bootstrap.Modal.getInstance(attachModal)?.hide();
        }
    };

    const reloadFiles = async () => {
        if (!attachmentRoot || !list) return;
        const url = new URL(attachmentRoot.dataset.listUrl, location.href);
        Object.entries(context()).forEach(([name, value]) => url.searchParams.set(name, value));
        const response = await fetch(url.toString(), {
            credentials: 'same-origin', cache: 'no-store', headers: {'Accept': 'application/json'}
        });
        if (!response.ok) throw new Error(await errorMessage(response));
        const payload = await response.json();
        list.replaceChildren();

        if (!payload.items.length) {
            list.append(make('div', 'list-group-item text-secondary',
                'Пока нет файлов. Загрузите изображение или документ выше.'));
            return;
        }

        for (const item of payload.items) {
            const row = make('div', 'list-group-item d-flex flex-wrap align-items-center gap-2');
            const glyph = make('i', item.image
                ? 'bi bi-image fs-4 text-primary' : 'bi bi-file-earmark fs-4 text-secondary');
            const info = make('div', 'flex-grow-1');
            info.append(make('strong', 'd-block text-break', item.name));
            info.append(make('span', 'text-secondary small', (item.size / 1024).toFixed(1) + ' КБ'));
            row.append(glyph, info);

            const link = make('button', 'btn btn-sm btn-outline-primary', 'Вставить ссылку');
            link.type = 'button';
            link.addEventListener('click', () => insert(item));
            row.append(link);

            if (item.image) {
                const imageButton = make('button', 'btn btn-sm btn-outline-primary', 'Вставить картинку');
                imageButton.type = 'button';
                imageButton.addEventListener('click', () => insert(item, true));
                row.append(imageButton);
            }
            const download = make('a', 'btn btn-sm btn-outline-secondary', 'Скачать');
            download.href = item.download_url;
            row.append(download);

            const remove = make('button', 'btn btn-sm btn-outline-danger', 'Удалить');
            remove.type = 'button';
            remove.title = 'Удалить файл из материала';
            remove.addEventListener('click', async () => {
                if (!confirm('Удалить файл? Если он уже вставлен в текст, ссылка перестанет работать.')) return;
                try {
                    const deleteUrl = attachmentRoot.dataset.deleteUrlTemplate.replace('__ID__', item.id);
                    const resp = await fetch(deleteUrl, {
                        method: 'DELETE', credentials: 'same-origin',
                        headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'}
                    });
                    if (!resp.ok) throw new Error(await errorMessage(resp));
                    await reloadFiles();
                } catch (err) {
                    alert(err.message);
                }
            });
            row.append(remove);
            list.append(row);
        }
    };

    const openAttachments = () => {
        if (!attachModal || !window.bootstrap) return;
        const editor = getEditor();
        bookmark = editor ? editor.selection.getBookmark() : null;
        window.bootstrap.Modal.getOrCreateInstance(attachModal).show();
    };

    if (attachModal) {
        attachModal.addEventListener('show.bs.modal', () => {
            reloadFiles().catch(err => {
                if (list) list.replaceChildren(make('div', 'list-group-item text-danger', err.message));
            });
        });
    }

    fileInput?.addEventListener('change', async () => {
        errorBox?.classList.add('d-none');
        const files = [...fileInput.files];
        fileInput.disabled = true;
        try {
            for (let index = 0; index < files.length; index++) {
                statusBox.textContent = 'Загружаем ' + (index + 1) + ' из ' + files.length + ': ' + files[index].name;
                await upload(files[index]);
            }
            statusBox.textContent = 'Все файлы загружены. Можно вставить их в текст.';
        } catch (error) {
            errorBox.textContent = error.message;
            errorBox.classList.remove('d-none');
            statusBox.textContent = '';
        } finally {
            fileInput.disabled = false;
            fileInput.value = '';
        }
    });

    const initEditor = () => {
        if (!field || field.disabled || !field.getClientRects().length || !window.tinymce) return;
        if (field.dataset.editorReady === '1') return;
        field.dataset.editorReady = '1';

        window.tinymce.init({
            target: field,
            license_key: 'gpl',
            language: 'ru',
            height: Math.max(650, window.innerHeight - 210),
            min_height: 620,
            resize: 'both',
            menubar: 'edit insert format table tools',
            plugins: 'lists link table image code wordcount fullscreen',
            toolbar: 'undo redo | blocks | bold italic underline strikethrough | ' +
                'alignleft aligncenter alignright | bullist numlist | outdent indent | ' +
                'link image courseattachments table | removeformat code fullscreen',
            toolbar_mode: 'wrap',
            images_upload_handler: async blobInfo => {
                const file = new File([blobInfo.blob()], blobInfo.filename(), {type: blobInfo.blob().type});
                const item = await upload(file);
                return item.url;
            },
            file_picker_types: 'file image',
            file_picker_callback: (callback, _value, meta) => {
                const picker = document.createElement('input');
                picker.type = 'file';
                picker.accept = meta.filetype === 'image' ? 'image/png,image/jpeg,image/webp,image/gif' : '';
                picker.addEventListener('change', async () => {
                    const file = picker.files?.[0];
                    if (!file) return;
                    try {
                        const item = await upload(file);
                        if (meta.filetype === 'image') {
                            if (!item.image) throw new Error('Выбранный файл не является изображением.');
                            callback(item.url, {alt: item.name, title: item.name});
                        } else {
                            callback(item.download_url, {text: item.name, title: item.name});
                        }
                    } catch (error) {
                        alert(error.message);
                    }
                });
                picker.click();
            },
            automatic_uploads: true,
            paste_data_images: true,
            convert_urls: false,
            link_default_target: '_blank',
            promotion: false,
            branding: false,
            setup: editor => {
                editor.ui.registry.addButton('courseattachments', {
                    text: 'Файлы',
                    tooltip: 'Прикрепить файл или вставить картинку',
                    onAction: openAttachments
                });
                editor.on('change input undo redo', () => editor.save());
            }
        }).catch(error => {
            console.warn('WYSIWYG editor unavailable', error);
            field.dataset.editorReady = '0';
        });
    };

    // Reusable SCORM/H5P gallery with incremental search.
    let requestSequence = 0;
    let debounce = null;
    let lastFormat = null;
    let controller = null;

    const status = message => {
        results.replaceChildren(make('div', 'col-12 small text-secondary', message));
    };

    const render = entries => {
        results.replaceChildren();
        if (entries.length === 0) {
            status(query.value.trim()
                ? 'Ничего не найдено. Попробуйте другой запрос или загрузите новый пакет.'
                : 'В библиотеке пока нет пакетов этого формата. Загрузите первый.');
            return;
        }

        for (const entry of entries) {
            const wrapper = make('div', 'col-lg-6');
            const card = make('button', 'btn btn-outline-secondary text-start w-100 h-100 p-3 course-library-card');
            card.type = 'button';
            card.setAttribute('aria-pressed', String(String(entry.id) === packageId.value));

            const heading = make('div', 'd-flex gap-2 align-items-center mb-1');
            heading.append(make('i', entry.format === 'h5p' ? 'bi bi-puzzle' : 'bi bi-box-seam'));
            heading.append(make('strong', '', entry.title));
            card.append(heading);
            card.append(make('div', 'small text-secondary',
                [entry.version, (entry.size / 1048576).toFixed(1) + ' МБ'].filter(Boolean).join(' · ')));

            if (Array.isArray(entry.tags) && entry.tags.length) {
                const tags = make('div', 'mt-2 d-flex flex-wrap gap-1');
                for (const tag of entry.tags) tags.append(make('span', 'badge text-bg-light border', tag));
                card.append(tags);
            }
            if (String(entry.id) === packageId.value) card.classList.add('active');

            card.addEventListener('click', () => {
                packageId.value = String(entry.id);
                packageUpload.value = '';
                if (!title.value.trim()) title.value = entry.title;
                selected.textContent = 'Выбран материал: ' + entry.title;
                results.querySelectorAll('.course-library-card').forEach(other => {
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
            const url = new URL(galleryRoot.dataset.searchUrl, location.href);
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

    query?.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(search, 180);
    });

    packageUpload?.addEventListener('change', () => {
        if (!packageUpload.files.length) return;
        packageId.value = '';
        if (!title.value.trim()) title.value = packageUpload.files[0].name.replace(/\.(zip|h5p)$/i, '');
        selected.textContent = 'Загрузится новый пакет: ' + packageUpload.files[0].name;
        results.querySelectorAll('.course-library-card').forEach(button => {
            button.classList.remove('active');
            button.setAttribute('aria-pressed', 'false');
        });
    });

    const refresh = () => {
        const type = kind.value;
        const usesPackage = ['scorm', 'h5p'].includes(type);

        form.querySelectorAll('.course-type-field').forEach(group => {
            const show = group.dataset.types.split(',').includes(type);
            group.hidden = !show;
            group.querySelectorAll('input, textarea, select').forEach(control => {
                control.disabled = !show;
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
            packageId.value = type === form.dataset.existingFormat ? packageId.defaultValue : '';
            packageUpload.value = '';
            query.value = '';
            selected.textContent = packageId.value ? 'Ранее выбранный пакет сохранён.' : '';
            search();
        }
        lastFormat = type;
        if (type === 'lesson') requestAnimationFrame(initEditor);
    };

    kind.addEventListener('change', refresh);
    form.addEventListener('submit', () => window.tinymce?.triggerSave());
    refresh();

    // An existing text editor may be hidden at first render if its type changes.
    const observer = new MutationObserver(() => {
        if (kind.value === 'lesson') initEditor();
    });
    observer.observe(form, {attributes: true, attributeFilter: ['style', 'class'], subtree: false});
});
