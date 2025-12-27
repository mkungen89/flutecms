@php
    $statusClasses = [
        'running' => 'bg-success',
        'stopped' => 'bg-secondary',
        'starting' => 'bg-info',
        'stopping' => 'bg-warning',
        'installing' => 'bg-info',
    ];
    $statusClass = $statusClasses[$server->status] ?? 'bg-secondary';
@endphp

<span class="badge {{ $statusClass }}">
    @if($server->status === 'running')
        <i class="ph ph-play-circle me-1"></i>
    @elseif($server->status === 'stopped')
        <i class="ph ph-stop-circle me-1"></i>
    @elseif($server->status === 'starting' || $server->status === 'installing')
        <i class="ph ph-spinner me-1"></i>
    @endif
    {{ __('arma-reforger.admin.status.' . $server->status) }}
</span>
