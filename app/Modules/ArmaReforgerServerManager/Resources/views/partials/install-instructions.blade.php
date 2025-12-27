<div class="install-instructions">
    <div class="accordion" id="installAccordion">
        @foreach($instructions as $platform => $methods)
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $platform }}">
                        <i class="ph ph-{{ $platform === 'linux' ? 'linux-logo' : 'windows-logo' }} me-2"></i>
                        {{ ucfirst($platform) }}
                    </button>
                </h2>
                <div id="collapse{{ $platform }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#installAccordion">
                    <div class="accordion-body">
                        @foreach($methods as $method => $commands)
                            <h6 class="fw-semibold mb-2">{{ $method }}</h6>
                            <div class="bg-dark rounded p-3 mb-3">
                                <code class="text-light">
                                    @foreach($commands as $command)
                                        <div class="mb-1">{{ $command }}</div>
                                    @endforeach
                                </code>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-3">
        <a href="https://developer.valvesoftware.com/wiki/SteamCMD" target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="ph ph-arrow-square-out me-1"></i>
            SteamCMD Documentation
        </a>
    </div>
</div>
