@extends('Core/Template/layout')

@section('title', __('battlelog.leaderboards') . ' - ' . __('battlelog.title'))

@push('header')
    @at('Modules/ArmaBattlelog/Resources/assets/scss/battlelog.scss')
@endpush

@section('content')
<div class="battlelog-container">
    <div class="page-header">
        <h1><i class="ph ph-ranking"></i> {{ __('battlelog.leaderboards') }}</h1>
    </div>

    <!-- Filters -->
    <div class="leaderboard-filters">
        <div class="filter-group categories">
            @foreach($categories as $cat)
                <a href="{{ url('battlelog/leaderboard/' . $cat['key'] . '?period=' . $currentPeriod) }}" 
                   class="filter-btn {{ $currentCategory === $cat['key'] ? 'active' : '' }}">
                    {{ $cat['name'] }}
                </a>
            @endforeach
        </div>
        <div class="filter-group periods">
            @foreach($periods as $period)
                <a href="{{ url('battlelog/leaderboard/' . $currentCategory . '?period=' . $period['key']) }}" 
                   class="filter-btn {{ $currentPeriod === $period['key'] ? 'active' : '' }}">
                    {{ $period['name'] }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Leaderboard Table -->
    <div class="leaderboard-table">
        <div class="table-header">
            <div class="col rank">Rank</div>
            <div class="col player">Player</div>
            <div class="col score">{{ \Flute\Modules\ArmaBattlelog\Database\Entities\Leaderboard::getCategoryName($currentCategory) }}</div>
        </div>
        <div class="table-body">
            @forelse($leaderboard as $entry)
                <a href="{{ url('battlelog/player/' . $entry['player_id']) }}" class="table-row {{ $entry['rank'] <= 3 ? 'top-' . $entry['rank'] : '' }}">
                    <div class="col rank">
                        <span class="rank-number">#{{ $entry['rank'] }}</span>
                        @if($entry['rank_change'] === 'up')
                            <span class="rank-change up"><i class="ph ph-arrow-up"></i></span>
                        @elseif($entry['rank_change'] === 'down')
                            <span class="rank-change down"><i class="ph ph-arrow-down"></i></span>
                        @elseif($entry['rank_change'] === 'new')
                            <span class="rank-change new">NEW</span>
                        @endif
                    </div>
                    <div class="col player">
                        <div class="player-avatar">
                            @if($entry['player_avatar'])
                                <img src="{{ $entry['player_avatar'] }}" alt="{{ $entry['player_name'] }}">
                            @else
                                <i class="ph ph-user"></i>
                            @endif
                        </div>
                        <span class="player-name">{{ $entry['player_name'] }}</span>
                    </div>
                    <div class="col score">{{ $entry['formatted_score'] }}</div>
                </a>
            @empty
                <div class="empty-state">
                    <i class="ph ph-chart-line-down"></i>
                    <p>No data available for this leaderboard yet.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Pagination -->
    @if($page > 1 || count($leaderboard) === 50)
        <div class="pagination">
            @if($page > 1)
                <a href="{{ url('battlelog/leaderboard/' . $currentCategory . '?period=' . $currentPeriod . '&page=' . ($page - 1)) }}" class="btn btn-outline">
                    <i class="ph ph-arrow-left"></i> Previous
                </a>
            @endif
            <span class="page-info">Page {{ $page }}</span>
            @if(count($leaderboard) === 50)
                <a href="{{ url('battlelog/leaderboard/' . $currentCategory . '?period=' . $currentPeriod . '&page=' . ($page + 1)) }}" class="btn btn-outline">
                    Next <i class="ph ph-arrow-right"></i>
                </a>
            @endif
        </div>
    @endif
</div>
@endsection
