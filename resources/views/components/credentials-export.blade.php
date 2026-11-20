@props(['organizationId' => null, 'groupId' => null, 'search' => null])

<form method="POST" action="{{ route('admin.users.credentials.export') }}"
      autocomplete="off"
      onsubmit="return confirm('Ведомость содержит действующие пароли учащихся. Передавайте её только доверенному куратору по защищённому каналу.')">
    @csrf
    @if($organizationId)
        <input type="hidden" name="organization_id" value="{{ $organizationId }}">
    @endif
    @if($groupId)
        <input type="hidden" name="group_id" value="{{ $groupId }}">
    @endif
    @if($search)
        <input type="hidden" name="search" value="{{ $search }}">
    @endif

    <div class="dropdown">
        <button type="button" class="btn btn-outline-success btn-sm dropdown-toggle"
                data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-download me-1"></i> Скачать ведомость
        </button>
        <ul class="dropdown-menu">
            <li>
                <button type="submit" name="format" value="xlsx" class="dropdown-item">
                    <i class="bi bi-file-earmark-spreadsheet me-2"></i> Excel (.xlsx)
                </button>
            </li>
            <li>
                <button type="submit" name="format" value="docx" class="dropdown-item">
                    <i class="bi bi-file-earmark-word me-2"></i> Word (.docx)
                </button>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <button type="submit" name="format" value="csv" class="dropdown-item">
                    <i class="bi bi-filetype-csv me-2"></i> CSV (.csv)
                </button>
            </li>
        </ul>
    </div>
</form>
