@php
    $children = $category->children ?? collect();
@endphp
<tr>
    <td style="padding-left: {{ ($level * 30) + 12 }}px">
        @if($level > 0)<i class="bi bi-arrow-return-right text-muted"></i>@endif
        <strong>{{ $category->name }}</strong>
    </td>
    <td><code>{{ $category->slug }}</code></td>
    <td>{{ $category->courses_count }}</td>
    <td>@if($category->is_active)<span class="badge bg-success">Активна</span>@else<span class="badge bg-secondary">Не активна</span>@endif</td>
    <td>
        <div class="btn-group btn-group-sm">
            <a href="{{ route('admin.course-categories.show', $category) }}" class="btn btn-outline-primary"><i class="bi bi-eye"></i></a>
            @can('update', $category)
            <a href="{{ route('admin.course-categories.edit', $category) }}" class="btn btn-outline-warning"><i class="bi bi-pencil"></i></a>
            @endcan
            @can('delete', $category)
            <form action="{{ route('admin.course-categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Удалить?')">@csrf @method('DELETE')<button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button></form>
            @endcan
        </div>
    </td>
</tr>
@foreach($children as $child)
    @include('admin.course-categories._row', ['category' => $child, 'level' => $level + 1])
@endforeach
