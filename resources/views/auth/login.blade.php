<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk — SIMRS RS LNG Badak</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:      hsl(217, 91%, 60%);
            --primary-dark: hsl(217, 91%, 45%);
            --danger:       hsl(0, 84%, 60%);
            --success:      hsl(142, 71%, 45%);
            --warning:      hsl(38, 92%, 50%);

            --bg-base:      hsl(222, 25%, 9%);
            --bg-surface:   hsl(222, 20%, 13%);
            --bg-elevated:  hsl(222, 18%, 17%);
            --border:       hsl(222, 15%, 24%);

            --text-primary:   hsl(220, 20%, 96%);
            --text-secondary: hsl(220, 15%, 65%);
            --text-muted:     hsl(220, 10%, 42%);

            --font-base:    'Inter', system-ui, sans-serif;
            --font-heading: 'Plus Jakarta Sans', 'Inter', sans-serif;
            --radius:       0.75rem;
            --transition:   0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        [data-theme="light"] {
            --bg-base:      hsl(210, 28%, 93%);
            --bg-surface:   hsl(0, 0%, 100%);
            --bg-elevated:  hsl(210, 20%, 96%);
            --border:       hsl(210, 15%, 84%);
            --text-primary:   hsl(220, 25%, 10%);
            --text-secondary: hsl(220, 15%, 38%);
            --text-muted:     hsl(220, 10%, 56%);
        }

        html, body {
            height: 100%;
            font-family: var(--font-base);
            -webkit-font-smoothing: antialiased;
        }

        body {
            background: var(--bg-base);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.25rem;
            position: relative;
            transition: background var(--transition), color var(--transition);
            overflow: hidden;
        }

        /* ── Animated background blobs ── */
        .bg-blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.12;
            pointer-events: none;
            animation: drift 12s ease-in-out infinite alternate;
        }
        .bg-blob-1 {
            width: 500px; height: 500px;
            background: hsl(217, 91%, 60%);
            top: -150px; left: -100px;
            animation-duration: 14s;
        }
        .bg-blob-2 {
            width: 400px; height: 400px;
            background: hsl(250, 80%, 65%);
            bottom: -120px; right: -80px;
            animation-duration: 10s;
            animation-delay: -5s;
        }
        .bg-blob-3 {
            width: 300px; height: 300px;
            background: hsl(190, 80%, 50%);
            top: 50%; right: 20%;
            animation-duration: 16s;
            animation-delay: -3s;
        }

        [data-theme="light"] .bg-blob { opacity: 0.07; }

        @keyframes drift {
            0%   { transform: translate(0, 0) scale(1); }
            100% { transform: translate(40px, 30px) scale(1.08); }
        }

        /* ── Card ── */
        .auth-card {
            width: 100%;
            max-width: 430px;
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: calc(var(--radius) * 1.5);
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.35);
            padding: 2.25rem 2rem;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(12px);
        }

        [data-theme="light"] .auth-card {
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.1);
        }

        /* Theme toggle */
        .theme-btn {
            position: absolute;
            top: 1.1rem; right: 1.1rem;
            width: 34px; height: 34px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--bg-elevated);
            color: var(--text-muted);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: all var(--transition);
        }
        .theme-btn:hover { color: var(--text-primary); background: var(--bg-base); }

        /* Brand */
        .auth-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.75rem;
        }
        .auth-logo {
            width: 46px; height: 46px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), hsl(250, 80%, 65%));
            display: flex; align-items: center; justify-content: center;
            font-family: var(--font-heading);
            font-weight: 800; font-size: 0.95rem;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 0 20px rgba(59,130,246,0.4);
        }
        .auth-brand-name {
            font-family: var(--font-heading);
            font-size: 1.15rem; font-weight: 800;
            color: var(--text-primary);
            line-height: 1.1;
        }
        .auth-brand-sub {
            font-size: 0.72rem;
            color: var(--text-muted);
            margin-top: 0.1rem;
        }

        /* Divider */
        .auth-divider {
            height: 1px;
            background: var(--border);
            margin-bottom: 1.5rem;
        }

        /* Title */
        .auth-title {
            font-family: var(--font-heading);
            font-size: 1.35rem; font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        .auth-subtitle {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }

        /* Alerts */
        .auth-alert {
            padding: 0.65rem 0.875rem;
            border-radius: var(--radius);
            font-size: 0.82rem;
            display: flex; align-items: center; gap: 0.6rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
        }
        .auth-alert-success { background: hsla(142,71%,45%,0.1); color: var(--success); border-color: hsla(142,71%,45%,0.25); }
        .auth-alert-error   { background: hsla(0,84%,60%,0.1);   color: var(--danger);  border-color: hsla(0,84%,60%,0.25); }

        /* Form */
        .form-group { margin-bottom: 1rem; }
        .form-label {
            display: block;
            font-size: 0.795rem; font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.375rem;
        }
        .form-input {
            width: 100%;
            padding: 0.6rem 0.875rem;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            color: var(--text-primary);
            font-size: 0.875rem; font-family: var(--font-base);
            transition: all var(--transition);
            outline: none;
        }
        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.14);
        }
        .form-input::placeholder { color: var(--text-muted); }
        .form-input.is-invalid { border-color: var(--danger); }

        /* Password wrap */
        .input-wrap { position: relative; }
        .pwd-eye {
            position: absolute; right: 0.7rem; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--text-muted); display: flex; align-items: center;
            transition: color var(--transition); padding: 0;
        }
        .pwd-eye:hover { color: var(--text-primary); }

        .invalid-msg { font-size: 0.74rem; color: var(--danger); margin-top: 0.25rem; }

        /* Remember & forgot */
        .auth-row {
            display: flex; align-items: center;
            justify-content: space-between;
            margin-bottom: 1.375rem; gap: 0.5rem;
        }
        .checkbox-label { display: flex; align-items: center; gap: 0.45rem; cursor: pointer; }
        .checkbox-label input[type=checkbox] { width: 15px; height: 15px; accent-color: var(--primary); cursor: pointer; }
        .checkbox-label span { font-size: 0.8rem; color: var(--text-secondary); }
        .forgot-link { font-size: 0.8rem; color: var(--primary); text-decoration: none; transition: opacity var(--transition); }
        .forgot-link:hover { opacity: 0.75; text-decoration: underline; }

        /* Submit button */
        .btn-submit {
            width: 100%;
            padding: 0.7rem 1rem;
            background: linear-gradient(135deg, var(--primary), hsl(250,80%,65%));
            color: white;
            border: none; border-radius: var(--radius);
            font-family: var(--font-heading);
            font-size: 0.9rem; font-weight: 700;
            cursor: pointer;
            transition: all var(--transition);
            display: flex; align-items: center; justify-content: center; gap: 0.45rem;
            box-shadow: 0 4px 16px rgba(59,130,246,0.35);
            letter-spacing: 0.01em;
        }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(59,130,246,0.45); }
        .btn-submit:active { transform: translateY(0); }
        .btn-submit:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

        /* Demo creds */
        .demo-section {
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border);
        }
        .demo-label {
            font-size: 0.7rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.07em;
            color: var(--text-muted); margin-bottom: 0.625rem;
            text-align: center;
        }
        .demo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; }
        .demo-item {
            padding: 0.6rem 0.75rem;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: calc(var(--radius) - 2px);
            cursor: pointer;
            transition: all var(--transition);
            text-align: left;
        }
        .demo-item:hover { border-color: var(--primary); background: rgba(59,130,246,0.06); transform: translateY(-1px); }
        .demo-item:active { transform: translateY(0); }
        .demo-role { font-size: 0.72rem; font-weight: 700; margin-bottom: 0.2rem; }
        .demo-cred { font-size: 0.73rem; color: var(--text-muted); font-family: monospace; line-height: 1.6; }

        /* Footer */
        .auth-footer {
            margin-top: 1.25rem;
            text-align: center;
            font-size: 0.73rem;
            color: var(--text-muted);
        }

        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <!-- Background blobs -->
    <div class="bg-blob bg-blob-1"></div>
    <div class="bg-blob bg-blob-2"></div>
    <div class="bg-blob bg-blob-3"></div>

    <!-- Center Card -->
    <div class="auth-card">

        <!-- Theme Toggle -->
        <button class="theme-btn" id="themeToggle" title="Toggle tema">
            <svg id="iconSun" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display:none">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"/>
            </svg>
            <svg id="iconMoon" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
        </button>

        <!-- Brand -->
        {{-- <div class="auth-brand">
            <div class="auth-logo">RS</div>
            <div>
                <div class="auth-brand-name">SIMRS</div>
                <div class="auth-brand-sub">RS LNG Badak — Rawat Jalan</div>
            </div>
        </div> --}}

        <div class="auth-divider"></div>

        <div class="auth-title">Selamat Datang</div>
        <div class="auth-subtitle">Masuk untuk mengakses sistem manajemen rumah sakit</div>

        <!-- Status -->
        @if (session('status'))
            <div class="auth-alert auth-alert-success">✅ {{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="auth-alert auth-alert-error">❌ {{ $errors->first() }}</div>
        @endif

        <!-- Form -->
        <form method="POST" action="{{ route('login') }}" id="loginForm">
            @csrf

            <div class="form-group">
                <label class="form-label" for="email">Alamat Email</label>
                <input id="email" name="email" type="email"
                       class="form-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                       value="{{ old('email') }}"
                       placeholder="email@simrs.id"
                       required autofocus autocomplete="username"/>
                @error('email')<div class="invalid-msg">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-wrap">
                    <input id="password" name="password" type="password"
                           class="form-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                           placeholder="Masukkan password"
                           style="padding-right:2.5rem"
                           required autocomplete="current-password"/>
                    <button type="button" class="pwd-eye" id="pwdToggle">
                        <svg id="eyeOpen" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg id="eyeClosed" width="17" height="17" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display:none">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>
                @error('password')<div class="invalid-msg">{{ $message }}</div>@enderror
            </div>

            <div class="auth-row">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" id="remember_me" {{ old('remember') ? 'checked' : '' }}>
                    <span>Ingat saya</span>
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="forgot-link">Lupa password?</a>
                @endif
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                Masuk ke Sistem
            </button>
        </form>

        <!-- Demo Credentials -->
        {{-- <div class="demo-section">
            <div class="demo-label">Akun Demo</div>
            <div class="demo-grid">
                <button class="demo-item" type="button" onclick="fillCredential('admin@simrs.id','admin123')">
                    <div class="demo-role" style="color:hsl(217,91%,65%)">⚡ Administrator</div>
                    <div class="demo-cred">admin@simrs.id<br>admin123</div>
                </button>
                <button class="demo-item" type="button" onclick="fillCredential('petugas@simrs.id','petugas123')">
                    <div class="demo-role" style="color:hsl(142,71%,50%)">👤 Petugas</div>
                    <div class="demo-cred">petugas@simrs.id<br>petugas123</div>
                </button>
            </div>
        </div> --}}

    <script>
        // Theme
        (function () {
            var t = localStorage.getItem('simrs-theme') || 'dark';
            applyTheme(t);
        })();

        function applyTheme(t) {
            document.documentElement.setAttribute('data-theme', t);
            localStorage.setItem('simrs-theme', t);
            document.getElementById('iconSun').style.display  = t === 'dark'  ? 'block' : 'none';
            document.getElementById('iconMoon').style.display = t === 'light' ? 'block' : 'none';
        }

        document.getElementById('themeToggle').addEventListener('click', function () {
            applyTheme(document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
        });

        // Password toggle
        document.getElementById('pwdToggle').addEventListener('click', function () {
            var inp = document.getElementById('password');
            var show = inp.type === 'text';
            inp.type = show ? 'password' : 'text';
            document.getElementById('eyeOpen').style.display  = show ? 'block' : 'none';
            document.getElementById('eyeClosed').style.display = show ? 'none'  : 'block';
        });

        // Fill demo
        function fillCredential(email, pass) {
            document.getElementById('email').value    = email;
            document.getElementById('password').value = pass;
            document.getElementById('email').focus();
        }

        // Loading
        document.getElementById('loginForm').addEventListener('submit', function () {
            var btn = document.getElementById('submitBtn');
            btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="animation:spin .7s linear infinite"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Memproses...';
            btn.disabled = true;
        });
    </script>
</body>
</html>
