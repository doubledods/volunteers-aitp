<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="robots" content="noindex, nofollow">
    <title>Event Rosters</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 16px; line-height: 1.4; color: #222; background: #fff; border-top: 6px solid #001a00; }
        main { max-width: 640px; margin: 0 auto; }
        h1 { font-size: 1.6rem; margin: 8px 0 16px; color: #030; }
        h2 { font-size: 1.2rem; margin: 28px 0 8px; color: #001a00; border-bottom: 2px solid #030; padding-bottom: 4px; }
        ul { list-style: none; margin: 0; padding: 0; }
        li a { display: block; padding: 12px 8px; border-bottom: 1px solid #e0e0e0; color: #030; text-decoration: none; font-size: 1.05rem; }
        li a:hover { background: #eef4ee; }
        li small { display: block; color: #555; font-size: .85rem; }
        p.empty { color: #555; }
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
