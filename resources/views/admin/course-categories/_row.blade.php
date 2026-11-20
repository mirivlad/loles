@php
    $children = $category->children ?? collect();
@endphp
<tr>
    <td style="padding-left: {{ ($level * 30) + 12 }}px">
        @if($level > 0)<i class="bi bi-arrow-return-right text-muted"></i>@endif
        <strong>{{ $category->name }}</strong>
    </td>
    <td>{{ $category->courses_count }}</td>
    <td>@if($category->is_active)<span class="badge bg-success">Активна</span>@else<span class="badge bg-secondary">Не активна</span>@endif</td>
    <td>
        <div class="btn-group btn-group-sm">
            <a href="{{ route('admin.course-categories.show', $category) }}" class="btn btn-outline-primary" title="Открыть категорию" aria-label="Открыть {{ $category->name }}"><i class="bi bi-eye"></i></a>
            @can('update', $category)
            <a href="{{ route('admin.course-categories.edit', $category) }}" class="btn btn-outline-warning" title="Редактировать категорию" aria-label="Редактировать {{ $category->name }}"><i class="bi bi-pencil"></i></a>
            @endcan
            @can('delete', $category)
            <form action="{{ route('admin.course-categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Удалить?')">@csrf @method('DELETE')<button class="btn btn-outline-danger" type="submit" title="Удалить категорию" aria-label="Удалить {{ $category->name }}"><i class="bi bi-trash"></i></button></form>
            @endcan
        </div>
    </td>
</tr>
@foreach($children as $child)
    @include('admin.course-categories._row', ['category' => $child, 'level' => $level + 1])
@endforeach
