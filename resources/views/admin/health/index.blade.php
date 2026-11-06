@extends('layouts.admin')

@section('title', 'System health')

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">System health</h1>
        <p class="admin-page-subtitle text-muted-bp">Queue, scheduler, storage, and environment checks.</p>
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach($checks as $check)
        <div class="col-md-6 col-xl-4">
            <div class="admin-card h-100">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div class="text-cream fw-semibold">{{ $check['label'] }}</div>
                    <span class="badge {{ $check['ok'] ? 'text-bg-success' : 'text-bg-danger' }}">{{ $check['ok'] ? 'OK' : 'Attention' }}</span>
                </div>
                <p class="small text-muted-bp mt-2 mb-0">{{ $check['detail'] }}</p>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-xl-6">
        <div class="admin-card mb-3">
            <div class="footer-title">Runtime</div>
            <div class="summary-row"><span>Environment</span><span class="text-cream">{{ $environment }}</span></div>
            <div class="summary-row"><span>Debug mode</span><span class="{{ $debug ? 'text-danger' : 'text-success' }}">{{ $debug ? 'Enabled' : 'Disabled' }}</span></div>
            <div class="summary-row"><span>App URL</span><span class="text-cream small text-break">{{ $appUrl }}</span></div>
            <div class="summary-row"><span>Queue connection</span><span class="text-cream">{{ $queueConnection }}</span></div>
            <div class="summary-row"><span>Pending jobs</span><span class="text-cream">{{ number_format($pendingJobs) }}</span></div>
            <div class="summary-row"><span>Mailer</span><span class="text-cream">{{ $mailer }}</span></div>
            <div class="summary-row"><span>Scheduler heartbeat</span><span class="text-cream">{{ $schedulerLastRun ? $schedulerLastRun->format('M d, Y · h:i A') : 'Not recorded' }}</span></div>
            <div class="summary-row"><span>PHP</span><span class="text-cream">{{ $phpVersion }}</span></div>
            <div class="summary-row"><span>Laravel</span><span class="text-cream">{{ $laravelVersion }}</span></div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="admin-card">
            <div class="admin-card-head">
                <div>
                    <span class="admin-card-title">Failed jobs</span>
                    <span class="admin-card-note">{{ $failedJobs->count() }} shown</span>
                </div>
                <div class="d-flex gap-2">
                    <form action="{{ route('admin.health.retry') }}" method="POST" data-confirm="Retry all failed jobs?">
                        @csrf
                        <button class="btn btn-soft btn-sm" type="submit">Retry all</button>
                    </form>
                    <form action="{{ route('admin.health.flush') }}" method="POST" data-confirm="Delete all failed jobs? This cannot be undone.">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm" type="submit">Clear</button>
                    </form>
                </div>
            </div>

            @if($failedJobs->isEmpty())
                <div class="empty-state"><i class="bi bi-check2-circle"></i><p class="mb-0">No failed jobs. Queues are healthy.</p></div>
            @else
                <div class="d-flex flex-column gap-2">
                    @foreach($failedJobs as $job)
                        <div class="border-bottom border-secondary-subtle pb-2">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <span class="small text-cream">#{{ $job->id }} · {{ $job->name }}</span>
                                <form action="{{ route('admin.health.retry') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $job->id }}">
                                    <button class="btn btn-soft btn-sm" type="submit">Retry</button>
                                </form>
                            </div>
                            <div class="small text-muted-bp mt-1">{{ $job->exception }}</div>
                            <div class="admin-meta text-muted-bp">{{ $job->failed_at }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
