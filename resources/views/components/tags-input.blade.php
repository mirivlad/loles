@props(['name', 'url', 'placeholder' => 'Начните вводить...', 'value' => [], 'user_id' => null, 'badge_color' => 'primary'])

<div class="tags-input-container">
    <input type="text" class="form-control" id="{{ $name }}-input" placeholder="{{ $placeholder }}" autocomplete="off">
    <input type="hidden" name="{{ $name }}" id="{{ $name }}" value="{{ is_array($value) ? implode(',', $value) : '' }}">
    <div id="{{ $name }}-tags" class="tags-container mt-2"></div>
</div>

@push('scripts')

<style>
.tags-input-container {
    position: relative;
}
.tags-container {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    min-height: 40px;
    padding: 0.5rem;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    background: #fff;
}
.tags-container:has(input:focus) {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
.tag-badge {
    padding: 0.35rem 0.65rem;
    border-radius: 0.25rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: white;
}
.tag-badge .remove-tag {
    cursor: pointer;
    font-size: 1.1rem;
    line-height: 1;
    opacity: 0.7;
    color: white;
}
.tag-badge .remove-tag:hover {
    opacity: 1;
    color: #ffc107;
}
.tag-badge.bg-primary { background: #0d6efd; }
.tag-badge.bg-success { background: #198754; }
.tag-badge.bg-info { background: #0dcaf0; }
.tag-badge.bg-warning { background: #ffc107; color: #000; }
.tag-badge.bg-danger { background: #dc3545; }
.tag-badge.bg-secondary { background: #6c757d; }
.tag-badge.bg-dark { background: #212529; }
</style>

<script>
console.log('[TagsInput] Initializing for {{ $name }}');
console.log('[TagsInput] Value:', {!! json_encode($value) !!});
console.log('[TagsInput] User ID:', {{ $user_id ?? 'null' }});

document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('{{ $name }}-input');
    const tagsContainer = document.getElementById('{{ $name }}-tags');
    const hiddenInput = document.getElementById('{{ $name }}');
    let selectedTags = new Set({!! json_encode(array_map('strval', is_array($value) ? $value : [])) !!});
    
    console.log('[TagsInput] DOM loaded');
    console.log('[TagsInput] Input element:', input);
    console.log('[TagsInput] Selected tags:', Array.from(selectedTags));

    // Инициализация TomSelect
    const select = new TomSelect(input, {
        valueField: 'id',
        labelField: 'text',
        searchField: 'text',
        placeholder: '{{ $placeholder }}',
        preload: false,
        maxOptions: null,
        persist: false,
        hideSelected: true,
        create: false,
        load: function(query, callback) {
            console.log('[TagsInput] Load called with query:', query);
            if (query.length < 2) return callback();
            
            let url = '{{ $url }}?q=' + encodeURIComponent(query);
            @if(isset($user_id) && $user_id)url += '&user_id={{ $user_id }}';@endif
            
            console.log('[TagsInput] Fetching URL:', url);
            fetch(url)
                .then(response => {
                    console.log('[TagsInput] Response status:', response.status);
                    return response.json();
                })
                .then(json => {
                    console.log('[TagsInput] Received items:', json);
                    // Фильтруем уже выбранные
                    const filtered = json.filter(item => !selectedTags.has(String(item.id)));
                    console.log('[TagsInput] Filtered items:', filtered);
                    // Добавляем опции в TomSelect
                    filtered.forEach(item => {
                        this.addOption(item);
                    });
                    callback(filtered);
                }).catch(err => {
                    console.error('[TagsInput] Fetch error:', err);
                    callback();
                });
        },
        render: {
            option: function(data, escape) {
                return '<div>' + escape(data.text) + '</div>';
            },
            item: function(data, escape) {
                return '<div>' + escape(data.text) + '</div>';
            }
        },
        onItemAdd: function(value, $item) {
            console.log('[TagsInput] onItemAdd called with value:', value);
            const data = this.options[value];
            console.log('[TagsInput] Option data:', data);
            if (data) {
                addTag(value, data);
            }
        }
    });
    
    console.log('[TagsInput] TomSelect initialized:', select);

    // Добавление тега
    function addTag(id, data) {
        console.log('[TagsInput] addTag called with id:', id, 'data:', data);
        if (!data) {
            console.log('[TagsInput] Skipping tag (no data)');
            return;
        }
        if (selectedTags.has(String(id))) {
            console.log('[TagsInput] Tag already selected, skipping');
            return;
        }

        selectedTags.add(String(id));
        updateHiddenInput();

        const tag = document.createElement('div');
        tag.className = 'tag-badge bg-{{ $badge_color }}';
        tag.dataset.id = id;
        tag.innerHTML = `
            <span>${escapeHtml(data.text)}</span>
            <span class="remove-tag" onclick="removeTag(${id}, this)">&times;</span>
        `;
        console.log('[TagsInput] Appending tag:', tag);
        tagsContainer.appendChild(tag);

        // Очищаем поле ввода
        setTimeout(() => {
            console.log('[TagsInput] Clearing select');
            select.clear();
        }, 100);
    }

    // Удаление тега
    window.removeTag = function(id, element) {
        console.log('[TagsInput] removeTag called with id:', id);
        selectedTags.delete(String(id));
        updateHiddenInput();
        element.parentElement.remove();
        select.clear();
    };

    // Обновление скрытого input
    function updateHiddenInput() {
        hiddenInput.value = Array.from(selectedTags).join(',');
        console.log('[TagsInput] Hidden input value:', hiddenInput.value);
    }

    // Экранирование HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Загрузка существующих тегов
    @if(count($value) > 0)
    console.log('[TagsInput] Loading existing tags:', {!! json_encode(array_map('strval', is_array($value) ? $value : [])) !!});
    const existingIds = {!! json_encode(array_map('strval', is_array($value) ? $value : [])) !!};
    if (existingIds.length > 0) {
        // Загружаем все доступные группы (с user_id для фильтрации)
        let url = '{{ $url }}?q=';
        @if(isset($user_id) && $user_id)url += '&user_id={{ $user_id }}';@endif
        
        console.log('[TagsInput] Fetching existing tags from URL:', url);
        fetch(url)
            .then(response => {
                console.log('[TagsInput] Existing tags response status:', response.status);
                return response.json();
            })
            .then(allItems => {
                console.log('[TagsInput] All items from API:', allItems);
                // Фильтруем только нужные группы
                const items = allItems.filter(item => existingIds.includes(String(item.id)));
                console.log('[TagsInput] Filtered items for existing tags:', items);
                items.forEach(item => {
                    console.log('[TagsInput] Adding existing option:', item);
                    select.addOption(item);
                    // Добавляем в selectedTags и создаём бейдж напрямую
                    selectedTags.add(String(item.id));
                    updateHiddenInput();
                    
                    const tag = document.createElement('div');
                    tag.className = 'tag-badge bg-{{ $badge_color }}';
                    tag.dataset.id = item.id;
                    tag.innerHTML = `<span>${escapeHtml(item.text)}</span><span class="remove-tag" onclick="removeTag(${item.id}, this)">&times;</span>`;
                    tagsContainer.appendChild(tag);
                    console.log('[TagsInput] Tag appended:', tag);
                });
            })
            .catch(err => {
                console.error('[TagsInput] Error loading existing tags:', err);
            });
    }
    @else
    console.log('[TagsInput] No existing tags to load');
    @endif
});
</script>
@endpush
