<x-guest-layout>

<style>
    :root {
        --bg-page: #f1f5f9;
        --bg-card: #ffffff;
        --bg-card-grad: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        --border-card: #e2e8f0;
        --border-accent: rgba(99,102,241,0.4);
        --bg-input: #f8fafc;
        --border-input: #e2e8f0;
        --text-input: #1e293b;
        --text-title: #0f172a;
        --text-sub: #64748b;
        --text-label: #475569;
        --icon-muted: #94a3b8;
        --placeholder: #94a3b8;
        --logo-bg: linear-gradient(135deg, #eef2ff, #e0e7ff);
        --logo-border: #c7d2fe;
        --footer-border: #e2e8f0;
        --footer-text: #94a3b8;
        --ambient-1: rgba(79,70,229,0.06);
        --ambient-2: rgba(79,70,229,0.04);
    }
    html.dark {
        --bg-page: #1a1f2e;
        --bg-card: #232838;
        --bg-card-grad: linear-gradient(180deg, #252b3d 0%, rgba(35,40,56,0.9) 100%);
        --border-card: #353b50;
        --border-accent: rgba(99,102,241,0.5);
        --bg-input: #1a1f2e;
        --border-input: #353b50;
        --text-input: #e5e7eb;
        --text-title: #f3f4f6;
        --text-sub: #8b93a7;
        --text-label: #9ca3af;
        --icon-muted: #6b7280;
        --placeholder: #6b7280;
        --logo-bg: linear-gradient(135deg, #2a2d4f, #353b6b);
        --logo-border: #4338ca;
        --footer-border: #353b50;
        --footer-text: #6b7280;
        --ambient-1: rgba(99,102,241,0.12);
        --ambient-2: rgba(99,102,241,0.07);
    }

    body, html { background: var(--bg-page) !important; min-height: 100vh; margin: 0; }

    .wrap {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--bg-page);
        padding: 16px;
        position: relative;
        overflow: hidden;
    }
    .wrap::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at 20% 30%, var(--ambient-1), transparent 45%),
            radial-gradient(circle at 80% 70%, var(--ambient-2), transparent 45%);
        pointer-events: none;
        z-index: 0;
    }

    /* Theme toggle */
    .theme-toggle {
        position: absolute;
        top: 18px; right: 18px;
        z-index: 5;
        width: 52px; height: 28px;
        border-radius: 999px;
        border: 1px solid var(--border-card);
        background: var(--bg-card);
        cursor: pointer;
        display: flex;
        align-items: center;
        padding: 0 4px;
        transition: background 0.2s, border-color 0.2s;
    }
    .theme-toggle .knob {
        width: 20px; height: 20px;
        border-radius: 50%;
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        display: flex; align-items: center; justify-content: center;
        color: #fff;
        transition: transform 0.25s ease;
        transform: translateX(0);
    }
    html.dark .theme-toggle .knob {
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        transform: translateX(24px);
    }
    .theme-toggle .knob svg { width: 12px; height: 12px; }
    .theme-toggle .sun  { display: flex; }
    .theme-toggle .moon { display: none; }
    html.dark .theme-toggle .sun  { display: none; }
    html.dark .theme-toggle .moon { display: flex; }

    .card {
        position: relative;
        z-index: 1;
        background: var(--bg-card-grad);
        border: 1px solid var(--border-card);
        border-radius: 16px;
        width: 100%;
        max-width: 360px;
        padding: 28px 26px 22px;
        box-shadow: 0 20px 50px -20px rgba(0,0,0,0.18);
    }
    html.dark .card { box-shadow: 0 20px 50px -20px rgba(0,0,0,0.5); }

    .card::before {
        content: '';
        position: absolute;
        top: -1px;
        left: 20%;
        right: 20%;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(99,102,241,0.5), transparent);
    }

    .header {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        margin-bottom: 24px;
    }
    .logo {
        width: 72px; height: 72px;
        background: var(--logo-bg);
        border: 1px solid var(--logo-border);
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 14px;
        flex-shrink: 0;
    }
    .header h1 { color: var(--text-title); font-size: 17px; font-weight: 600; margin: 0 0 4px; }
    .header p  { color: var(--text-sub); font-size: 12px; margin: 0; }

    .field { margin-bottom: 14px; }
    .field label {
        display: block;
        font-size: 11px;
        color: var(--text-label);
        margin-bottom: 6px;
        font-weight: 500;
        letter-spacing: 0.3px;
        text-transform: uppercase;
    }

    .input-wrap { position: relative; }
    .input-wrap .icon-left {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--icon-muted);
        display: flex;
        pointer-events: none;
        transition: color 0.15s;
    }
    .field input {
        width: 100%;
        background: var(--bg-input);
        border: 1px solid var(--border-input);
        border-radius: 10px;
        color: var(--text-input);
        font-size: 13px;
        padding: 11px 12px 11px 38px;
        outline: none;
        box-sizing: border-box;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .field input:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79,70,229,0.15);
    }
    .input-wrap:focus-within .icon-left { color: #818cf8; }
    .field input::placeholder { color: var(--placeholder); }
    .field input.is-error { border-color: rgba(220,38,38,0.6); }

    .pw-wrap input { padding-right: 40px; }
    .pw-toggle {
        position: absolute; right: 10px; top: 50%;
        transform: translateY(-50%);
        background: none; border: none;
        color: var(--icon-muted); cursor: pointer;
        display: flex; align-items: center; padding: 4px;
        border-radius: 6px;
        transition: color 0.15s, background 0.15s;
    }
    .pw-toggle:hover { color: var(--text-sub); }

    .alert-err {
        display: flex;
        align-items: center;
        gap: 7px;
        background: rgba(220, 38, 38, 0.08);
        border: 1px solid rgba(220, 38, 38, 0.25);
        border-radius: 7px;
        padding: 7px 10px;
        margin-top: 6px;
        font-size: 11px;
        color: #dc2626;
    }
    html.dark .alert-err { color: #fca5a5; }
    .alert-err svg { flex-shrink: 0; }

    .btn-submit {
        width: 100%;
        padding: 11px;
        background: linear-gradient(180deg, #4f46e5, #4338ca);
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        border-radius: 10px;
        border: 1px solid #4338ca;
        cursor: pointer;
        margin-top: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: background 0.15s, transform 0.05s;
    }
    .btn-submit:hover { background: linear-gradient(180deg, #4338ca, #3730a3); }
    .btn-submit:active { transform: scale(0.99); }
    .btn-submit svg { transition: transform 0.15s; }
    .btn-submit:hover svg { transform: translateX(2px); }

    .status-ok {
        background: rgba(16,185,129,0.08);
        border: 1px solid rgba(16,185,129,0.2);
        border-radius: 8px;
        padding: 7px 10px;
        font-size: 11px;
        color: #059669;
        margin-bottom: 12px;
    }
    html.dark .status-ok { color: #6ee7b7; }

    .footer-card {
        text-align: center;
        margin-top: 18px;
        padding-top: 16px;
        border-top: 1px solid var(--footer-border);
    }
    .footer-card p {
        color: var(--footer-text);
        font-size: 10px;
        margin: 0;
        letter-spacing: 0.3px;
        text-transform: uppercase;
    }
</style>

<div class="wrap">

    <button type="button" class="theme-toggle" onclick="toggleTheme()" aria-label="Ganti tema">
        <span class="knob">
            <svg class="sun" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
            <svg class="moon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
        </span>
    </button>

    <div class="card">

        <div class="header">
            <div class="logo">
                <img src="/favicon.ico" alt="Logo" width="44" height="44" style="object-fit:contain;">
            </div>
            <h1>Selamat Datang</h1>
            <p>Sistem Management Tahfidz</p>
        </div>

        @if(session('status'))
        <div class="status-ok">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            {{-- USERNAME --}}
            <div class="field">
                <label for="name">Username</label>
                <div class="input-wrap">
                    <input id="name" name="name" type="text" value="{{ old('name') }}"
                        placeholder="Masukkan username" required autofocus autocomplete="username"
                        class="{{ $errors->has('name') ? 'is-error' : '' }}">
                    <span class="icon-left">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                        </svg>
                    </span>
                </div>
                @if($errors->has('name'))
                <div class="alert-err">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    {{ $errors->first('name') }}
                </div>
                @endif
            </div>

            {{-- PASSWORD --}}
            <div class="field">
                <label for="password">Password</label>
                <div class="input-wrap pw-wrap">
                    <input id="password" name="password" type="password"
                        placeholder="Masukkan password" required autocomplete="current-password"
                        class="{{ $errors->has('password') ? 'is-error' : '' }}">
                    <span class="icon-left">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                        </svg>
                    </span>
                    <button type="button" class="pw-toggle" onclick="togglePw()">
                        <svg id="eye-show" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <svg id="eye-hide" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                        </svg>
                    </button>
                </div>
                @if($errors->has('password'))
                <div class="alert-err">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    {{ $errors->first('password') }}
                </div>
                @endif
            </div>

            <button type="submit" class="btn-submit">
                <span>Masuk</span>
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                </svg>
            </button>
        </form>

        <div class="footer-card">
            <p>© {{ date('Y') }} Sistem Management Tahfidz</p>
        </div>

    </div>
</div>

<script>
    // Terapkan tema tersimpan saat halaman dimuat
    (function () {
        try {
            var t = localStorage.getItem('theme') || 'light';
            document.documentElement.classList.toggle('dark', t === 'dark');
        } catch (e) {}
    })();

    function toggleTheme() {
        var isDark = document.documentElement.classList.toggle('dark');
        try { localStorage.setItem('theme', isDark ? 'dark' : 'light'); } catch (e) {}
    }

    function togglePw() {
        const input = document.getElementById('password');
        const show = document.getElementById('eye-show');
        const hide = document.getElementById('eye-hide');
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        show.style.display = isPassword ? 'none' : '';
        hide.style.display = isPassword ? '' : 'none';
    }

    document.addEventListener('DOMContentLoaded', () => {
        const alerts = document.querySelectorAll('.alert-err, .status-ok');
        const errorInputs = document.querySelectorAll('.is-error');

        if (alerts.length || errorInputs.length) {
            setTimeout(() => {
                alerts.forEach(alert => {
                    alert.style.transition = 'opacity 0.4s ease, max-height 0.4s ease, margin-top 0.4s ease, padding 0.4s ease';
                    alert.style.opacity = '0';
                    alert.style.maxHeight = '0';
                    alert.style.marginTop = '0';
                    alert.style.padding = '0';
                    alert.style.overflow = 'hidden';
                });
                errorInputs.forEach(input => {
                    input.style.transition = 'border-color 0.4s ease';
                    input.style.borderColor = 'var(--border-input)';
                });
            }, 5000);
        }
    });
</script>

</x-guest-layout>