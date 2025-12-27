@extends('Core/Template/layout')

@section('title', 'Battle Report - ' . __('battlelog.title'))

@push('header')
    @at('Modules/ArmaBattlelog/Resources/assets/scss/battlelog.scss')
@endpush

@section('content')
<div class="battlelog-container">
    <!-- Battle Report Header -->
    <div class="battle-report-header">
        <div class="report-title">
            <h1><i class="ph ph-file-text"></i> Battle Report</h1>
            <span class="report-date">{{ $report['session']['started_at'] }}</span>
        </div>
        
        <div class="battle-info">
            <div class="map-info">
                <i class="ph ph-map-trifold"></i>
                <span>{{ $report['session']['map'] }}</span>
            </div>
            <div class="mode-info">
                <i class="ph ph-game-controller"></i>
                <span>{{ ucfirst($report['session']['game_mode']) }}</span>
            </div>
            <div class="duration-info">
                <i class="ph ph-clock"></i>
                <span>{{ $report['session']['duration'] }}</span>
            </div>
            <div class="players-info">
                <i class="ph ph-users"></i>
                <span>{{ $report['session']['total_players'] }} players</span>
            </div>
        </div>
    </div>

    <!-- Score Board -->
    <div class="battle-scoreboard">
        <div class="team us {{ $report['session']['winner_faction'] === 'us' ? 'winner' : '' }}">
            <div class="team-header">
                <span class="team-name">US Army</span>
                @if($report['session']['winner_faction'] === 'us')
                    <span class="winner-badge"><i class="ph ph-trophy"></i> VICTORY</span>
                @endif
            </div>
            <div class="team-score">{{ $report['us_team']['score'] }}</div>
            <div class="team-kills">{{ $report['us_team']['total_kills'] }} kills</div>
        </div>
        
        <div class="vs-divider">
            <span>VS</span>
        </div>
        
        <div class="team ussr {{ $report['session']['winner_faction'] === 'ussr' ? 'winner' : '' }}">
            <div class="team-header">
                <span class="team-name">Soviet Union</span>
                @if($report['session']['winner_faction'] === 'ussr')
                    <span class="winner-badge"><i class="ph ph-trophy"></i> VICTORY</span>
                @endif
            </div>
            <div class="team-score">{{ $report['ussr_team']['score'] }}</div>
            <div class="team-kills">{{ $report['ussr_team']['total_kills'] }} kills</div>
        </div>
    </div>

    <!-- MVP -->
    @if($report['mvp'])
        <div class="mvp-section">
            <div class="mvp-badge">
                <i class="ph ph-star"></i>
                <span>MVP</span>
            </div>
            <a href="{{ url('battlelog/player/' . $report['mvp']['id']) }}" class="mvp-player">
                <span class="mvp-name">{{ $report['mvp']['name'] }}</span>
                <span class="mvp-stats">{{ $report['mvp']['kills'] }}/{{ $report['mvp']['deaths'] }} - {{ number_format($report['mvp']['score']) }} pts</span>
            </a>
        </div>
    @endif

    <!-- Player Tables -->
    <div class="teams-container">
        <!-- US Team -->
        <div class="team-table us">
            <h3><i class="ph ph-flag"></i> US Army</h3>
            <div class="player-table">
                <div class="table-header">
                    <div class="col player">Player</div>
                    <div class="col kills">K</div>
                    <div class="col deaths">D</div>
                    <div class="col kd">K/D</div>
                    <div class="col score">Score</div>
                    <div class="col spm">SPM</div>
                </div>
                @foreach($report['us_team']['players'] as $player)
                    <a href="{{ url('battlelog/player/' . $player['id']) }}" class="table-row {{ $player['is_mvp'] ? 'mvp' : '' }}">
                        <div class="col player">
                            @if($player['is_mvp'])
                                <i class="ph ph-star mvp-icon"></i>
                            @endif
                            {{ $player['name'] }}
                        </div>
                        <div class="col kills">{{ $player['kills'] }}</div>
                        <div class="col deaths">{{ $player['deaths'] }}</div>
                        <div class="col kd">{{ $player['kd_ratio'] }}</div>
                        <div class="col score">{{ number_format($player['score']) }}</div>
                        <div class="col spm">{{ $player['spm'] }}</div>
                    </a>
                @endforeach
            </div>
        </div>

        <!-- USSR Team -->
        <div class="team-table ussr">
            <h3><i class="ph ph-flag"></i> Soviet Union</h3>
            <div class="player-table">
                <div class="table-header">
                    <div class="col player">Player</div>
                    <div class="col kills">K</div>
                    <div class="col deaths">D</div>
                    <div class="col kd">K/D</div>
                    <div class="col score">Score</div>
                    <div class="col spm">SPM</div>
                </div>
                @foreach($report['ussr_team']['players'] as $player)
                    <a href="{{ url('battlelog/player/' . $player['id']) }}" class="table-row {{ $player['is_mvp'] ? 'mvp' : '' }}">
                        <div class="col player">
                            @if($player['is_mvp'])
                                <i class="ph ph-star mvp-icon"></i>
                            @endif
                            {{ $player['name'] }}
                        </div>
                        <div class="col kills">{{ $player['kills'] }}</div>
                        <div class="col deaths">{{ $player['deaths'] }}</div>
                        <div class="col kd">{{ $player['kd_ratio'] }}</div>
                        <div class="col score">{{ number_format($player['score']) }}</div>
                        <div class="col spm">{{ $player['spm'] }}</div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Kill Timeline -->
    @if(!empty($report['timeline']))
        <div class="timeline-section">
            <h3><i class="ph ph-clock-clockwise"></i> Battle Timeline</h3>
            <div class="kill-timeline">
                @foreach(array_slice($report['timeline'], 0, 20) as $event)
                    <div class="timeline-event">
                        <span class="event-time">{{ $event['timestamp'] }}</span>
                        <span class="event-killer">{{ $event['killer'] }}</span>
                        <span class="event-action">
                            @if($event['is_headshot'])
                                <i class="ph ph-skull headshot" title="Headshot"></i>
                            @else
                                <i class="ph ph-skull"></i>
                            @endif
                        </span>
                        <span class="event-victim">{{ $event['victim'] }}</span>
                        <span class="event-weapon">[{{ $event['weapon'] }}]</span>
                        @if($event['distance'] > 0)
                            <span class="event-distance">{{ number_format($event['distance']) }}m</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
