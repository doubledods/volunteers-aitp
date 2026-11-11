<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $event->name }} Roster</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 16px; line-height: 1.4; color: #222; background: #fff; }
        main { max-width: 640px; margin: 0 auto; }
        a.back { display: inline-block; padding: 6px 0; color: #0b5cad; text-decoration: none; }
        h1 { font-size: 1.6rem; margin: 8px 0 2px; }
        .dates { color: #666; margin: 0 0 16px; }
        h2 { font-size: 1.25rem; margin: 28px 0 4px; border-bottom: 2px solid #ddd; padding-bottom: 4px; }
        h3 { font-size: 1.05rem; margin: 16px 0 4px; }
        ul { list-style: none; margin: 0; padding: 0; }
        li { padding: 6px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; gap: 12px; }
        .when { color: #666; font-size: .9rem; white-space: nowrap; }
        .name { font-weight: 600; text-align: right; overflow-wrap: anywhere; }
        p.empty { color: #666; }
        @media (prefers-color-scheme: dark) {
            body { background: #111; color: #eee; }
            a.back { color: #7db7ff; }
            h2, li { border-color: #333; }
            .dates, .when, p.empty { color: #aaa; }
        }
    </style>
</head>
<body>
<main>
    <a class="back" href="/roster">&larr; All rosters</a>

    <h1>{{ $event->name }}</h1>
    <p class="dates">{{ \Carbon\Carbon::parse($event->start_date)->format('M j, Y') }} – {{ \Carbon\Carbon::parse($event->end_date)->format('M j, Y') }}</p>

    @forelse($roster as $department)
        <h2>{{ $department['name'] }}</h2>

        @foreach($department['shifts'] as $shift)
            <h3>{{ $shift['name'] }}</h3>
            <ul>
                @foreach($shift['people'] as $person)
                    <li>
                        <span class="when">{{ $person['when'] }}</span>
                        <span class="name">{{ $person['name'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endforeach
    @empty
        <p class="empty">No one has been assigned to a shift yet.</p>
    @endforelse
</main>
</body>
</html>
