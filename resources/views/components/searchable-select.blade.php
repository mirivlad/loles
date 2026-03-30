@props(['name', 'url', 'placeholder' => 'Начните вводить...', 'value' => null, 'required' => false, 'multiple' => false])

<input type="hidden" name="{{ $name }}" id="{{ $name }}" value="{{ is_array($value) ? implode(',', $value) : $value }}" {{ $required ? 'required' : '' }}>
<select id="{{ $name }}-select" class="form-select @error($name) is-invalid @enderror" {{ $required ? 'required' : '' }} {{ $multiple ? 'multiple' : '' }}></select>

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<style>
.tom-select .item {
    background: #0d6efd !important;
    color: white !important;
    border-radius: 0.25rem !important;
    padding: 0.25rem 0.5rem !important;
    margin: 0.125rem !important;
    display: inline-flex !important;
    align-items: center !important;
}
.tom-select .item .remove {
    color: white !important;
    margin-left: 0.5rem !important;
    font-size: 1.2rem !important;
    line-height: 1 !important;
}
.tom-select .item .remove:hover {
    color: #ffc107 !important;
}
</style>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const select = new TomSelect('#{{ $name }}-select', {
        valueField: 'id',
        labelField: 'text',
        searchField: 'text',
        placeholder: '{{ $placeholder }}',
        preload: false,
        maxOptions: null,
        {{ $multiple ? 'persist: false, hideSelected: true, create: false,' : '' }}
        load: function(query, callback) {
            if (query.length < 2) return callback();
            
            fetch('{{ $url }}?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(json => {
                    callback(json);
                }).catch(() => {
                    callback();
                });
        },
        render: {
            option: function(data, escape) {
                return '<div>' + escape(data.text) + '</div>';
            },
            item: function(data, escape) {
                return '<div class="item">' + 
                       escape(data.text) + 
                       '<a href="javascript:void(0)" class="remove">&times;</a>' +
                       '</div>';
            }
        },
        onChange: function(value) {
            document.getElementById('{{ $name }}').value = {{ $multiple ? 'Array.isArray(value) ? value.join(",") : value' : 'value' }};
        }
    });
    
    @if($value)
    fetch('{{ $url }}?q=')
        .then(response => response.json())
        .then(items => {
            const values = {{ is_array($value) ? json_encode($value) : "['" . $value . "']" }};
            values.forEach(val => {
                const item = items.find(i => i.id == val);
                if (item) {
                    select.addOption(item);
                }
            });
            select.setValue(values);
        });
    @endif
});
</script>
@endpush
