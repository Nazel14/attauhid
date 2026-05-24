<?php if (isset($component)) { $__componentOriginal69dc84650370d1d4dc1b42d016d7226b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal69dc84650370d1d4dc1b42d016d7226b = $attributes; } ?>
<?php $component = App\View\Components\GuestLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('guest-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\GuestLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>

<style>
    body, html { background: #0f1117 !important; min-height: 100vh; margin: 0; }

    .wrap {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #0f1117;
        padding: 16px;
        position: relative;
        overflow: hidden;
    }

    /* Ambient gradient halus untuk kedalaman, tetap minimalis */
    .wrap::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at 20% 30%, rgba(79,70,229,0.10), transparent 45%),
            radial-gradient(circle at 80% 70%, rgba(79,70,229,0.06), transparent 45%);
        pointer-events: none;
        z-index: 0;
    }

    .card {
        position: relative;
        z-index: 1;
        background: linear-gradient(180deg, #1a1d27 0%, rgba(22,25,33,0.85) 100%);
        border: 1px solid #2a2d3a;
        border-radius: 16px;
        width: 100%;
        max-width: 360px;
        padding: 28px 26px 22px;
        box-shadow: 0 20px 50px -20px rgba(0,0,0,0.5);
    }

    /* Garis aksen tipis di atas card */
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
        width: 52px; height: 52px;
        background: linear-gradient(135deg, #1e2035, #2a2d4f);
        border: 1px solid #3730a3;
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 14px;
        flex-shrink: 0;
    }

    .header h1 { color: #f3f4f6; font-size: 17px; font-weight: 600; margin: 0 0 4px; }
    .header p  { color: #6b7280; font-size: 12px; margin: 0; }

    .field { margin-bottom: 14px; }
    .field label {
        display: block;
        font-size: 11px;
        color: #9ca3af;
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
        color: #4b5563;
        display: flex;
        pointer-events: none;
        transition: color 0.15s;
    }
    .field input {
        width: 100%;
        background: #0f1117;
        border: 1px solid #2a2d3a;
        border-radius: 10px;
        color: #e5e7eb;
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
    .field input:focus + .icon-left,
    .input-wrap:focus-within .icon-left {
        color: #818cf8;
    }
    .field input::placeholder { color: #3d4154; }
    .field input.is-error { border-color: rgba(220,38,38,0.6); }

    .pw-wrap input { padding-right: 40px; }
    .pw-toggle {
        position: absolute; right: 10px; top: 50%;
        transform: translateY(-50%);
        background: none; border: none;
        color: #4b5563; cursor: pointer;
        display: flex; align-items: center; padding: 4px;
        border-radius: 6px;
        transition: color 0.15s, background 0.15s;
    }
    .pw-toggle:hover { color: #9ca3af; background: rgba(255,255,255,0.03); }

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
        color: #fca5a5;
    }
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
        color: #6ee7b7;
        margin-bottom: 12px;
    }

    .footer-card {
        text-align: center;
        margin-top: 18px;
        padding-top: 16px;
        border-top: 1px solid #2a2d3a;
    }
    .footer-card p {
        color: #4b5563;
        font-size: 10px;
        margin: 0;
        letter-spacing: 0.3px;
        text-transform: uppercase;
    }
</style>

<div class="wrap">
    <div class="card">

        <div class="header">
            <div class="logo">
                <img src="/favicon.ico" alt="Logo" width="28" height="28" style="object-fit:contain;">
            </div>
            <h1>Selamat Datang</h1>
            <p>Sistem Management Tahfidz</p>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
        <div class="status-ok"><?php echo e(session('status')); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <form method="POST" action="<?php echo e(route('login')); ?>">
            <?php echo csrf_field(); ?>

            
            <div class="field">
                <label for="name">Username</label>
                <div class="input-wrap">
                    <input id="name" name="name" type="text" value="<?php echo e(old('name')); ?>"
                        placeholder="Masukkan username" required autofocus autocomplete="username"
                        class="<?php echo e($errors->has('name') ? 'is-error' : ''); ?>">
                    <span class="icon-left">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                        </svg>
                    </span>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->has('name')): ?>
                <div class="alert-err">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <?php echo e($errors->first('name')); ?>

                </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            
            <div class="field">
                <label for="password">Password</label>
                <div class="input-wrap pw-wrap">
                    <input id="password" name="password" type="password"
                        placeholder="Masukkan password" required autocomplete="current-password"
                        class="<?php echo e($errors->has('password') ? 'is-error' : ''); ?>">
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
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->has('password')): ?>
                <div class="alert-err">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <?php echo e($errors->first('password')); ?>

                </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <button type="submit" class="btn-submit">
                <span>Masuk</span>
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                </svg>
            </button>
        </form>

        <div class="footer-card">
            <p>© <?php echo e(date('Y')); ?> Sistem Management Tahfidz</p>
        </div>

    </div>
</div>

<script>
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
                input.style.borderColor = '#2a2d3a';
            });
        }, 5000);
    }
});
</script>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal69dc84650370d1d4dc1b42d016d7226b)): ?>
<?php $attributes = $__attributesOriginal69dc84650370d1d4dc1b42d016d7226b; ?>
<?php unset($__attributesOriginal69dc84650370d1d4dc1b42d016d7226b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal69dc84650370d1d4dc1b42d016d7226b)): ?>
<?php $component = $__componentOriginal69dc84650370d1d4dc1b42d016d7226b; ?>
<?php unset($__componentOriginal69dc84650370d1d4dc1b42d016d7226b); ?>
<?php endif; ?><?php /**PATH C:\laragon\www\raport-tahfizh\resources\views/auth/login.blade.php ENDPATH**/ ?>