<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://unpkg.com/htmx.org@1.9.2"></script>
    <!-- Bootstrap is loaded through app.scss with our custom dark theme -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        /* These styles are now defined in app.scss with dark theme colors */
        .table-rankings td, .table-rankings th {
            padding: .5rem;
            vertical-align: middle;
        }
    </style>
    <title>@yield('title', 'Galaxy Hall of Fame')</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    @yield('styles')
    @if(isset($page))
        @inertiaHead
    @endif
</head>
<body class="dark-theme">
<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="display-5">Galaxy Hall of Fame</h1>
            <p class="lead">@yield('subtitle', 'Spieler-Rankings')</p>
        </div>
        <div class="col-auto">
            <div class="btn-group">
                <a href="{{ route('ranking.index') }}" class="btn btn-outline-primary">
                    <i class="bi bi-list-ol"></i> Runden-Rankings
                </a>
                <a href="{{ route('ranking.eternal.index') }}" class="btn btn-outline-primary">
                    <i class="bi bi-trophy"></i> Ewige Bestenliste
                </a>
            </div>
        </div>
    </div>
    @if(isset($page))
        @inertia
    @else
        @yield('content')
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')
</body>
</html>
