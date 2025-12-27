@if($server->enabled)
    <span class="badge bg-success">
        <i class="ph ph-check-circle me-1"></i>
        {{ __('def.yes') }}
    </span>
@else
    <span class="badge bg-secondary">
        <i class="ph ph-x-circle me-1"></i>
        {{ __('def.no') }}
    </span>
@endif
