<div class="d-flex align-items-center gap-2">
    @if($mod->imageUrl)
        <img src="{{ $mod->imageUrl }}" alt="{{ $mod->name }}" class="rounded" style="width: 40px; height: 40px; object-fit: cover;">
    @else
        <div class="mod-icon bg-light rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
            <i class="ph ph-puzzle-piece text-muted" style="font-size: 1.25rem;"></i>
        </div>
    @endif
    <div>
        <div class="fw-semibold">{{ $mod->name }}</div>
        @if($mod->author)
            <small class="text-muted">{{ __('arma-reforger.admin.mods.fields.author') }}: {{ $mod->author }}</small>
        @endif
    </div>
</div>
