<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Print') — {{ config('app.name', 'BNHS LMS') }}</title>
    <link rel="stylesheet" href="{{ asset('styles.css') }}?v={{ filemtime(public_path('styles.css')) }}">
    <style>
        body { margin: 0; padding: 24px; background: #fff; color: #111; }
        .print-doc-header { margin-bottom: 20px; border-bottom: 2px solid #0b1f44; padding-bottom: 12px; }
        .print-doc-header h1 { margin: 0 0 8px 0; font-size: 22px; color: #0b1f44; }
        .print-doc-meta { font-size: 14px; color: #374151; line-height: 1.5; }
        .print-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .print-table th, .print-table td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: left; }
        .print-table th { background: #f3f4f6; font-weight: 700; }
        .print-table td.num { text-align: center; }
        .print-table td.absent { font-weight: 700; }
        .print-actions { margin-bottom: 16px; }
        @media print {
            .print-actions { display: none !important; }
            body { padding: 0; }
        }
    </style>
    @stack('head')
</head>
<body>
    @yield('content')
    <script>
        if (new URLSearchParams(window.location.search).get('auto_print') === '1') {
            window.addEventListener('load', () => window.print());
        }
    </script>
</body>
</html>
