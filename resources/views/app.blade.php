<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>SIMRS RS LNG Badak - Sistem Informasi Manajemen Rumah Sakit</title>
    <meta name="description" content="Sistem Informasi Manajemen Rumah Sakit RS LNG Badak - Rawat Jalan">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Init tema sebelum Vue mount agar tidak ada flash --}}
    <script>
        (function () {
            var t = localStorage.getItem('simrs-theme') || 'dark';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
</head>
<body>
    <div id="app">
        {{-- Vue SPA will mount here --}}
    </div>

    {{-- Inject data user yang sedang login ke window global --}}
    <script>
        window.__AUTH_USER__ = {!! json_encode([
            'id'    => auth()->id(),
            'name'  => auth()->user()?->name,
            'email' => auth()->user()?->email,
            'role'  => auth()->user()?->role,
            'no_hp' => auth()->user()?->no_hp,
        ]) !!};
    </script>
</body>
</html>
