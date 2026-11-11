<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Event Rosters</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 16px; line-height: 1.4; color: #222; background: #fff; }
        main { max-width: 640px; margin: 0 auto; }
        h1 { font-size: 1.6rem; margin: 0 0 16px; }
        h2 { font-size: 1.2rem; margin: 28px 0 8px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        ul { list-style: none; margin: 0; padding: 0; }
        li a { display: block; padding: 12px 4px; border-bottom: 1px solid #eee; color: #0b5cad; text-decoration: none; font-size: 1.05rem; }
        li small { display: block; color: #666; font-size: .85rem; }
        p.empty { color: #666; }
        @media (prefers-color-scheme: dark) {
            body { background: #111; color: #eee; }
            h2, li a { border-color: #333; }
            li a { color: #7db7ff; }
            li small, p.empty { color: #aaa; }
        }
    </style>
</head>
<body>
<main>
    <h1>Event Rosters</h1>

    <h2>Upcoming events</h2>
    @if($upcoming->count())
        <ul>
            @foreach($upcoming as $event)
                <li>
                    <a href="/roster/{{ $event->slug ?: $event->id }}">
                        {{ $event->name }}
                        <small>{{ \Carbon\Carbon::parse($event->start_date)->format('M j, Y') }} – {{ \Carbon\Carbon::parse($event->end_date)->format('M j, Y') }}</small>
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <p class="empty">No upcoming events.</p>
    @endif

    <h2>Past events</h2>
    @if($past->count())
        <ul>
            @foreach($past as $event)
                <li>
                    <a href="/roster/{{ $event->slug ?: $event->id }}">
                        {{ $event->name }}
                        <small>{{ \Carbon\Carbon::parse($event->start_date)->format('M j, Y') }} – {{ \Carbon\Carbon::parse($event->end_date)->format('M j, Y') }}</small>
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <p class="empty">No past events.</p>
    @endif
</main>
</body>
</html>
