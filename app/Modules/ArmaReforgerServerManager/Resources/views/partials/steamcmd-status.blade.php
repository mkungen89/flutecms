<div class="steamcmd-status mb-3">
    @if($available)
        <div class="alert alert-success mb-3">
            <div class="d-flex align-items-center">
                <i class="ph ph-check-circle me-2" style="font-size: 1.5rem;"></i>
                <div>
                    <strong>{{ __('arma-reforger.admin.status.available') }}</strong>
                    @if($version)
                        <div class="small">{{ $version }}</div>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-danger mb-3">
            <div class="d-flex align-items-center">
                <i class="ph ph-x-circle me-2" style="font-size: 1.5rem;"></i>
                <div>
                    <strong>{{ __('arma-reforger.admin.status.unavailable') }}</strong>
                    <div class="small">SteamCMD is not installed or not found at the configured path.</div>
                </div>
            </div>
        </div>
    @endif
</div>
