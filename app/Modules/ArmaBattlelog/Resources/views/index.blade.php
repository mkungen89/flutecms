@extends('Core/Template/layout')

@section('title', __('battlelog.title'))

@push('header')
    @at('Modules/ArmaBattlelog/Resources/assets/scss/battlelog.scss')
@endpush

@section('content')
<div class="battlelog-container">
    <!-- Hero Section -->
    <div class="battlelog-hero">
        <div class="hero-content">
            <h1 class="hero-title">
                <i class="ph ph-crosshair"></i>
                {{ __('battlelog.arma_battlelog') }}
            </h1>
            <p class="hero-subtitle">{{ __('battlelog.hero_subtitle') }}</p>
        </div>
        
        <!-- Global Stats -->
        <div class="global-stats">
            <div class="stat-card">
                <div class="stat-icon"><i class="ph ph-users"></i></div>
                <div class="stat-info">
                    <span class="stat-value">{{ number_format($globalStats['total_players']) }}</span>
                    <span class="stat-label">{{ __('battlelog.total_players') }}</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="ph ph-game-controller"></i></div>
                <div class="stat-info">
                    <span class="stat-value">{{ number_format($globalStats['total_sessions']) }}</span>
                    <span class="stat-label">{{ __('battlelog.total_sessions') }}</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="ph ph-skull"></i></div>
                <div class="stat-info">
                    <span class="stat-value">{{ number_format($globalStats['total_kills']) }}</span>
                    <span class="stat-label">{{ __('battlelog.total_kills') }}</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="ph ph-clock"></i></div>
                <div class="stat-info">
                    <span class="stat-value">{{ $globalStats['total_playtime_formatted'] }}</span>
                    <span class="stat-label">{{ __('battlelog.total_playtime') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="battlelog-content">
        <div class="content-grid">
            <!-- Top Players -->
            <div class="content-card top-players">
                <div class="card-header">
                    <h2><i class="ph ph-trophy"></i> {{ __('battlelog.top_players') }}</h2>
                    <a href="{{ url('battlelog/leaderboard') }}" class="btn btn-sm btn-outline">
                        {{ __('battlelog.view_all') }}
                    </a>
                </div>
                <div class="card-body">
                    <div class="player-list">
                        @forelse($topPlayers as $index => $player)
                            <a href="{{ url('battlelog/player/' . $player->id) }}" class="player-item">
                                <span class="player-rank rank-{{ $index + 1 }}">#{{ $index + 1 }}</span>
                                <div class="player-avatar">
                                    @if($player->avatar_url)
                                        <img src="{{ $player->avatar_url }}" alt="{{ $player->name }}">
                                    @else
                                        <i class="ph ph-user"></i>
                                    @endif
                                </div>
                                <div class="player-info">
                                    <span class="player-name">{{ $player->name }}</span>
                                    <span class="player-rank-name">{{ $player->rank_name }}</span>
                                </div>
                                <div class="player-stats">
                                    <span class="stat">
                                        <i class="ph ph-skull"></i>
                                        {{ number_format($player->total_kills) }}
                                    </span>
                                    <span class="stat">
                                        <i class="ph ph-chart-line-up"></i>
                                        {{ $player->getKDRatio() }}
                                    </span>
                                </div>
                            </a>
                        @empty
                            <div class="empty-state">
                                <i class="ph ph-user-circle-dashed"></i>
                                <p>{{ __('battlelog.no_players_yet') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Recent Sessions -->
            <div class="content-card recent-sessions">
                <div class="card-header">
                    <h2><i class="ph ph-clock-counter-clockwise"></i> {{ __('battlelog.recent_battles') }}</h2>
                </div>
                <div class="card-body">
                    <div class="session-list">
                        @forelse($recentSessions as $session)
                            <a href="{{ url('battlelog/battlereport/' . $session->id) }}" class="session-item">
                                <div class="session-map">
                                    <i class="ph ph-map-trifold"></i>
                                    <span>{{ $session->map?->name ?? 'Unknown' }}</span>
                                </div>
                                <div class="session-score">
                                    <span class="score us {{ $session->winner_faction === 'us' ? 'winner' : '' }}">
                                        {{ $session->us_score }}
                                    </span>
                                    <span class="vs">VS</span>
                                    <span class="score ussr {{ $session->winner_faction === 'ussr' ? 'winner' : '' }}">
                                        {{ $session->ussr_score }}
                                    </span>
                                </div>
                                <div class="session-info">
                                    <span class="duration">{{ $session->getFormattedDuration() }}</span>
                                    <span class="players">{{ $session->total_players }} players</span>
                                </div>
                            </a>
                        @empty
                            <div class="empty-state">
                                <i class="ph ph-game-controller"></i>
                                <p>{{ __('battlelog.no_sessions_yet') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Recent Achievements -->
            <div class="content-card recent-achievements">
                <div class="card-header">
                    <h2><i class="ph ph-medal"></i> {{ __('battlelog.recent_achievements') }}</h2>
                </div>
                <div class="card-body">
                    <div class="achievement-feed">
                        @forelse($recentAchievements as $achievement)
                            <div class="achievement-item">
                                <div class="achievement-icon" style="border-color: {{ $achievement['rarity_color'] }}">
                                    <i class="ph ph-medal"></i>
                                </div>
                                <div class="achievement-info">
                                    <span class="player-name">{{ $achievement['player_name'] }}</span>
                                    <span class="achievement-name">{{ $achievement['achievement_name'] }}</span>
                                </div>
                                <span class="achievement-time">{{ $achievement['unlocked_at'] }}</span>
                            </div>
                        @empty
                            <div class="empty-state">
                                <i class="ph ph-medal"></i>
                                <p>{{ __('battlelog.no_achievements_yet') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="battlelog-links">
        <a href="{{ url('battlelog/leaderboard') }}" class="link-card">
            <i class="ph ph-ranking"></i>
            <span>{{ __('battlelog.leaderboards') }}</span>
        </a>
        <a href="{{ url('battlelog/weapons') }}" class="link-card">
            <i class="ph ph-crosshair-simple"></i>
            <span>{{ __('battlelog.weapons') }}</span>
        </a>
        <a href="{{ url('battlelog/vehicles') }}" class="link-card">
            <i class="ph ph-jeep"></i>
            <span>{{ __('battlelog.vehicles') }}</span>
        </a>
    </div>
</div>
@endsection
