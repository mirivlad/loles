@props(['name', 'url', 'placeholder' => 'Начните вводить...', 'value' => [], 'user_id' => null])

<div class="tags-input-container">
    <input type="text" class="form-control" id="{{ $name }}-input" placeholder="{{ $placeholder }}" autocomplete="off">
    <input type="hidden" name="{{ $name }}" id="{{ $name }}" value="{{ is_array($value) ? implode(',', $value) : '' }}">
    <div id="{{ $name }}-tags" class="tags-container mt-2"></div>
</div>

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
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
    background: #0d6efd;
    color: white;
    padding: 0.35rem 0.65rem;
    border-radius: 0.25rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}
.tag-badge .remove-tag {
    cursor: pointer;
    font-size: 1.1rem;
    line-height: 1;
    opacity: 0.7;
}
.tag-badge .remove-tag:hover {
    opacity: 1;
    color: #ffc107;
}
</style>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('{{ $name }}-input');
    const tagsContainer = document.getElementById('{{ $name }}-tags');
    const hiddenInput = document.getElementById('{{ $name }}');
    let selectedTags = new Set({!! json_encode(array_map('strval', is_array($value) ? $value : [])) !!});
    
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
            if (query.length < 2) return callback();
            
            let url = '{{ $url }}?q=' + encodeURIComponent(query);
            @if(isset($user_id) && $user_id)url += '&user_id={{ $user_id }}';@endif
            
            fetch(url)
                .then(response => response.json())
                .then(json => {
                    // Фильтруем уже выбранные
                    const filtered = json.filter(item => !selectedTags.has(String(item.id)));
                    callback(filtered);
                }).catch(() => {
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
            const data = this.options[value];
            if (data) {
                addTag(value, data);
            }
        }
    });

    // Добавление тега
    function addTag(id, data) {
        if (!data || selectedTags.has(String(id))) return;

        selectedTags.add(String(id));
        updateHiddenInput();

        const tag = document.createElement('div');
        tag.className = 'tag-badge';
        tag.dataset.id = id;
        tag.innerHTML = `
            <span>${escapeHtml(data.text)}</span>
            <span class="remove-tag" onclick="removeTag(${id}, this)">&times;</span>
        `;
        tagsContainer.appendChild(tag);
        
        // Очищаем поле ввода
        setTimeout(() => {
            select.clear();
        }, 100);
    }
    
    // Удаление тега
    window.removeTag = function(id, element) {
        selectedTags.delete(String(id));
        updateHiddenInput();
        element.parentElement.remove();
        select.clear();
    };
    
    // Обновление скрытого input
    function updateHiddenInput() {
        hiddenInput.value = Array.from(selectedTags).join(',');
    }
    
    // Экранирование HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Загрузка существующих тегов
    @if(count($value) > 0)
    fetch('{{ $url }}?q=')
        .then(response => response.json())
        .then(items => {
            selectedTags.forEach(id => {
                const item = items.find(i => String(i.id) === id);
                if (item) {
                    select.addOption(item);
                    addTag(id, item);
                }
            });
        });
    @endif
});
</script>
@endpush
