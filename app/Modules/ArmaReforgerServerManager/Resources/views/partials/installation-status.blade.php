<div class="installation-status mb-4">
    @if($status['installed'])
        <div class="alert alert-success">
            <div class="d-flex align-items-center">
                <i class="ph ph-check-circle me-3" style="font-size: 2rem;"></i>
                <div>
                    <strong>{{ __('arma-reforger.admin.status.installed') }}</strong>
                    @if($server->installedVersion)
                        <div class="small">Build: {{ $server->installedVersion }}</div>
                    @endif
                    @if($status['lastModified'])
                        <div class="small text-muted">
                            Last modified: {{ date('Y-m-d H:i:s', $status['lastModified']) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-warning">
            <div class="d-flex align-items-center">
                <i class="ph ph-warning-circle me-3" style="font-size: 2rem;"></i>
                <div>
                    <strong>{{ __('arma-reforger.admin.status.not_installed') }}</strong>
                    <div class="small">
                        Server files need to be downloaded before starting the server.
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($server->installPath)
        <div class="card bg-light mb-3">
            <div class="card-body py-2">
                <small class="text-muted">
                    <i class="ph ph-folder me-1"></i>
                    {{ $server->installPath }}
                </small>
            </div>
        </div>
    @endif
</div>
