@extends('Core/Template/layout')

@section('title', $player->name . ' - ' . __('battlelog.title'))

@push('header')
    @at('Modules/ArmaBattlelog/Resources/assets/scss/battlelog.scss')
@endpush

@section('content')
<div class="battlelog-container">
    <!-- Player Header -->
    <div class="player-header">
        <div class="player-profile">
            <div class="player-avatar-large">
                @if($player->avatar_url)
                    <img src="{{ $player->avatar_url }}" alt="{{ $player->name }}">
                @else
                    <i class="ph ph-user"></i>
                @endif
            </div>
            <div class="player-details">
                <h1 class="player-name">{{ $player->name }}</h1>
                <div class="player-rank-badge">
                    <i class="ph ph-star"></i>
                    <span>{{ $player->rank_name }}</span>
                </div>
                <div class="player-meta">
                    <span><i class="ph ph-clock"></i> {{ $player->getFormattedPlaytime() }} played</span>
                    <span><i class="ph ph-game-controller"></i> {{ $player->games_played }} games</span>
                    @if($player->last_seen)
                        <span><i class="ph ph-calendar"></i> Last seen: {{ $player->last_seen->format('M j, Y') }}</span>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="player-quick-stats">
            <div class="quick-stat">
                <span class="value">{{ number_format($player->total_kills) }}</span>
                <span class="label">Kills</span>
            </div>
            <div class="quick-stat">
                <span class="value">{{ number_format($player->total_deaths) }}</span>
                <span class="label">Deaths</span>
            </div>
            <div class="quick-stat highlight">
                <span class="value">{{ $player->getKDRatio() }}</span>
                <span class="label">K/D</span>
            </div>
            <div class="quick-stat">
                <span class="value">{{ $player->getWinRate() }}%</span>
                <span class="label">Win Rate</span>
            </div>
            <div class="quick-stat">
                <span class="value">{{ number_format($stats['overview']['spm']) }}</span>
                <span class="label">SPM</span>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="player-tabs">
        <a href="{{ url('battlelog/player/' . $player->id) }}" class="tab active">Overview</a>
        <a href="{{ url('battlelog/player/' . $player->id . '/weapons') }}" class="tab">Weapons</a>
        <a href="{{ url('battlelog/player/' . $player->id . '/vehicles') }}" class="tab">Vehicles</a>
        <a href="{{ url('battlelog/player/' . $player->id . '/sessions') }}" class="tab">Match History</a>
        <a href="{{ url('battlelog/player/' . $player->id . '/achievements') }}" class="tab">Achievements</a>
    </div>

    <!-- Content Grid -->
    <div class="player-content">
        <div class="content-grid-2">
            <!-- Combat Stats -->
            <div class="stat-card-detailed">
                <h3><i class="ph ph-crosshair"></i> Combat</h3>
                <div class="stat-grid">
                    <div class="stat-item">
                        <span class="label">Kills</span>
                        <span class="value">{{ number_format($stats['overview']['kills']) }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Deaths</span>
                        <span class="value">{{ number_format($stats['overview']['deaths']) }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Assists</span>
                        <span class="value">{{ number_format($stats['overview']['assists']) }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Headshots</span>
                        <span class="value">{{ number_format($stats['overview']['headshots']) }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Headshot %</span>
                        <span class="value">{{ $stats['overview']['headshot_percentage'] }}%</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Accuracy</span>
                        <span class="value">{{ $stats['overview']['accuracy'] }}%</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Longest Kill</span>
                        <span class="value">{{ number_format($stats['combat']['longest_kill']) }}m</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Best Killstreak</span>
                        <span class="value">{{ $stats['combat']['best_killstreak'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Teamplay Stats -->
            <div class="stat-card-detailed">
                <h3><i class="ph ph-users-three"></i> Teamplay</h3>
                <div class="stat-grid">
                    <div class="stat-item">
                        <span class="label">Revives</span>
                        <span class="value">{{ number_format($stats['teamplay']['revives']) }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Heals</span>
                        <span class="value">{{ number_format($stats['teamplay']['heals']) }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Repairs</span>
                        <span class="value">{{ number_format($stats['teamplay']['repairs']) }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Objectives Captured</span>
                        <span class="value">{{ number_format($stats['objectives']['captured']) }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Objectives Defended</span>
                        <span class="value">{{ number_format($stats['objectives']['defended']) }}</span>
                    </div>
                </div>
            </div>

            <!-- Vehicle Stats -->
            <div class="stat-card-detailed">
                <h3><i class="ph ph-jeep"></i> Vehicles</h3>
                <div class="stat-grid">
                    <div class="stat-item">
                        <span class="label">Vehicle Kills</span>
                        <span class="value">{{ number_format($stats['vehicles']['kills']) }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Vehicles Destroyed</span>
                        <span class="value">{{ number_format($stats['vehicles']['destroyed']) }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Roadkills</span>
                        <span class="value">{{ number_format($stats['vehicles']['roadkills']) }}</span>
                    </div>
                </div>
            </div>

            <!-- Games Stats -->
            <div class="stat-card-detailed">
                <h3><i class="ph ph-trophy"></i> Games</h3>
                <div class="stat-grid">
                    <div class="stat-item">
                        <span class="label">Games Played</span>
                        <span class="value">{{ number_format($stats['games']['played']) }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Wins</span>
                        <span class="value win">{{ number_format($stats['games']['wins']) }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Losses</span>
                        <span class="value loss">{{ number_format($stats['games']['losses']) }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Win Rate</span>
                        <span class="value">{{ $stats['games']['win_rate'] }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Section -->
        <div class="content-grid-3">
            <!-- Top Weapons -->
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="ph ph-crosshair-simple"></i> Top Weapons</h3>
                    <a href="{{ url('battlelog/player/' . $player->id . '/weapons') }}">View All</a>
                </div>
                <div class="card-body">
                    @forelse($topWeapons as $ws)
                        <div class="weapon-item">
                            <div class="weapon-icon">
                                <i class="ph ph-crosshair-simple"></i>
                            </div>
                            <div class="weapon-info">
                                <span class="weapon-name">{{ $ws->weapon->name }}</span>
                                <span class="weapon-category">{{ $ws->weapon->getCategoryName() }}</span>
                            </div>
                            <div class="weapon-stats">
                                <span>{{ $ws->kills }} kills</span>
                            </div>
                        </div>
                    @empty
                        <p class="empty">No weapon data yet</p>
                    @endforelse
                </div>
            </div>

            <!-- Top Vehicles -->
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="ph ph-jeep"></i> Top Vehicles</h3>
                    <a href="{{ url('battlelog/player/' . $player->id . '/vehicles') }}">View All</a>
                </div>
                <div class="card-body">
                    @forelse($topVehicles as $vs)
                        <div class="vehicle-item">
                            <div class="vehicle-icon">
                                <i class="ph ph-jeep"></i>
                            </div>
                            <div class="vehicle-info">
                                <span class="vehicle-name">{{ $vs->vehicle->name }}</span>
                                <span class="vehicle-category">{{ $vs->vehicle->getCategoryName() }}</span>
                            </div>
                            <div class="vehicle-stats">
                                <span>{{ $vs->kills }} kills</span>
                            </div>
                        </div>
                    @empty
                        <p class="empty">No vehicle data yet</p>
                    @endforelse
                </div>
            </div>

            <!-- Nemesis & Victim -->
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="ph ph-swords"></i> Rivalries</h3>
                </div>
                <div class="card-body">
                    @if($nemesisVictim['nemesis'])
                        <div class="rivalry-item nemesis">
                            <span class="label">Nemesis</span>
                            <span class="name">{{ $nemesisVictim['nemesis']['name'] }}</span>
                            <span class="count">Killed you {{ $nemesisVictim['nemesis']['times'] }} times</span>
                        </div>
                    @endif
                    @if($nemesisVictim['victim'])
                        <div class="rivalry-item victim">
                            <span class="label">Favorite Victim</span>
                            <span class="name">{{ $nemesisVictim['victim']['name'] }}</span>
                            <span class="count">You killed them {{ $nemesisVictim['victim']['times'] }} times</span>
                        </div>
                    @endif
                    @if(!$nemesisVictim['nemesis'] && !$nemesisVictim['victim'])
                        <p class="empty">No rivalry data yet</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Sessions -->
        <div class="content-card full-width">
            <div class="card-header">
                <h3><i class="ph ph-clock-counter-clockwise"></i> Recent Matches</h3>
                <a href="{{ url('battlelog/player/' . $player->id . '/sessions') }}">View All</a>
            </div>
            <div class="card-body">
                <div class="session-table">
                    @forelse($recentSessions as $session)
                        <a href="{{ url('battlelog/battlereport/' . $session->session_id) }}" class="session-row {{ $session->is_winner ? 'win' : 'loss' }}">
                            <div class="session-result">
                                <span class="result-badge">{{ $session->is_winner ? 'WIN' : 'LOSS' }}</span>
                            </div>
                            <div class="session-map">
                                {{ $session->session->map?->name ?? 'Unknown' }}
                            </div>
                            <div class="session-kd">
                                <span class="kills">{{ $session->kills }}</span>
                                <span class="separator">/</span>
                                <span class="deaths">{{ $session->deaths }}</span>
                            </div>
                            <div class="session-score">{{ number_format($session->score) }} pts</div>
                            <div class="session-date">{{ $session->joined_at->format('M j, H:i') }}</div>
                        </a>
                    @empty
                        <p class="empty">No match history yet</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
