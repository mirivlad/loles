@props(['name', 'url', 'placeholder' => 'Начните вводить...', 'value' => null, 'required' => false])

<input type="hidden" name="{{ $name }}" id="{{ $name }}" value="{{ $value }}" {{ $required ? 'required' : '' }}>
<select id="{{ $name }}-select" class="form-select @error($name) is-invalid @enderror" {{ $required ? 'required' : '' }}></select>

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
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
                return '<div>' + escape(data.text) + '</div>';
            }
        },
        onChange: function(value) {
            document.getElementById('{{ $name }}').value = value;
        }
    });
    
    @if($value)
    fetch('{{ $url }}?q=')
        .then(response => response.json())
        .then(items => {
            const item = items.find(i => i.id == '{{ $value }}');
            if (item) {
                select.addOption(item);
                select.setValue('{{ $value }}');
            }
        });
    @endif
});
</script>
@endpush
