<div class="server-status-card mb-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <span class="text-muted">{{ __('arma-reforger.admin.servers.fields.status') }}</span>
        @include('arma-reforger::cells.status', ['server' => $server])
    </div>

    @if($status['running'])
        <div class="alert alert-success mb-3">
            <i class="ph ph-check-circle me-2"></i>
            {{ __('arma-reforger.admin.status.running') }}
            @if($server->pid)
                <small class="d-block mt-1">PID: {{ $server->pid }}</small>
            @endif
        </div>
    @else
        <div class="alert alert-secondary mb-3">
            <i class="ph ph-stop-circle me-2"></i>
            {{ __('arma-reforger.admin.status.stopped') }}
        </div>
    @endif

    <div class="status-details">
        @if($status['installed'])
            <div class="mb-2">
                <i class="ph ph-check text-success me-2"></i>
                <span class="text-muted">{{ __('arma-reforger.admin.status.installed') }}</span>
            </div>
            @if($server->installedVersion)
                <div class="mb-2">
                    <small class="text-muted">Build: {{ $server->installedVersion }}</small>
                </div>
            @endif
        @else
            <div class="mb-2">
                <i class="ph ph-x text-danger me-2"></i>
                <span class="text-muted">{{ __('arma-reforger.admin.status.not_installed') }}</span>
            </div>
        @endif

        @if($server->lastStarted)
            <div class="mb-2">
                <small class="text-muted">
                    {{ __('arma-reforger.admin.servers.fields.created_at') }}: {{ $server->lastStarted->format('Y-m-d H:i') }}
                </small>
            </div>
        @endif
    </div>

    <hr>

    <div class="d-grid gap-2">
        @if($status['running'])
            <button type="button" class="btn btn-warning" data-turbo-method="post" data-turbo-frame="_top">
                <i class="ph ph-stop-circle me-2"></i>
                {{ __('arma-reforger.admin.servers.stop') }}
            </button>
            <button type="button" class="btn btn-outline-primary">
                <i class="ph ph-arrows-clockwise me-2"></i>
                {{ __('arma-reforger.admin.servers.restart') }}
            </button>
        @else
            <button type="button" class="btn btn-success" data-turbo-method="post" data-turbo-frame="_top">
                <i class="ph ph-play-circle me-2"></i>
                {{ __('arma-reforger.admin.servers.start') }}
            </button>
        @endif
    </div>
</div>
