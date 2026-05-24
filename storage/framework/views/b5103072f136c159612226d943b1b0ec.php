

<?php $__env->startSection('content'); ?>
<div class="min-h-screen bg-gray-950 py-3 sm:py-6 lg:py-8"
     x-data="{
        isOpen: false,
        isEdit: false,
        formAction: '<?php echo e(route('admin.admins.store')); ?>',
        formData: {
            name: '',
            password: '',
            password_confirmation: '',
            is_super_admin: 0
        },
        currentAdminId: null,
        showNotification: false,
        notificationMessage: '',
        notificationType: '',

        openCreateModal() {
            this.isEdit = false;
            this.formAction = '<?php echo e(route('admin.admins.store')); ?>';
            this.formData = {
                name: '',
                password: '',
                password_confirmation: '',
                is_super_admin: 0
            };
            this.isOpen = true;
        },

        openEditModal(admin) {
            this.isEdit = true;
            this.currentAdminId = admin.id;
            this.formAction = `/admin/admins/${admin.id}`;
            this.formData = {
                name: admin.name,
                is_super_admin: admin.is_super_admin ? 1 : 0,
                password: '',
                password_confirmation: ''
            };
            this.isOpen = true;
        },

        closeModal() {
            this.isOpen = false;
        },

        displayNotification(message, type) {
            this.notificationMessage = message;
            this.notificationType = type;
            this.showNotification = true;
            setTimeout(() => { this.showNotification = false; }, 5000);
        },

        smartCapitalizeInput(event) {
            const input = event.target;
            let value = input.value;
            if (value.length > 0) {
                value = value.charAt(0).toUpperCase() + value.slice(1);
            }
            input.value = value;
            this.formData.name = value;
        }
     }"
     x-init="
        <?php if(session('success')): ?>
            displayNotification('<?php echo e(session('success')); ?>', 'success')
        <?php elseif(session('error')): ?>
            displayNotification('<?php echo e(session('error')); ?>', 'error')
        <?php endif; ?>
     "
     x-effect="if (isOpen) { document.body.classList.add('overflow-hidden') } else { document.body.classList.remove('overflow-hidden') }">

    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8">

        
        <div x-show="showNotification"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             :class="{
                 'bg-emerald-950 border-emerald-700/50 text-emerald-300': notificationType === 'success',
                 'bg-red-950 border-red-700/50 text-red-300': notificationType === 'error'
             }"
             class="border rounded-xl px-4 py-3 mb-4 flex items-center justify-between gap-3"
             role="alert"
             style="display: none;">
            <div class="flex items-center gap-2">
                <svg x-show="notificationType === 'success'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <svg x-show="notificationType === 'error'" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium" x-html="notificationMessage"></span>
            </div>
            <button @click="showNotification = false" class="opacity-70 hover:opacity-100 transition-opacity flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        
        <div class="mb-4 sm:mb-5">
            <a href="<?php echo e(route('admin.dashboard')); ?>"
            class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 mb-3 bg-gray-800 hover:bg-gray-700 border border-gray-700 rounded-xl text-xs font-semibold text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-white flex items-center gap-2">
                        <span class="text-yellow-400">👑</span> Management Admin
                    </h1>
                    <p class="text-xs text-gray-500 mt-0.5">Kelola akun administrator sistem</p>
                    <div class="flex items-center gap-3 mt-1.5 flex-wrap">
                        <span class="inline-flex items-center gap-1 text-[10px] font-medium text-yellow-400">
                            <span class="w-1.5 h-1.5 bg-yellow-400 rounded-full"></span>
                            Super Admin: <?php echo e(\App\Models\User::where('role', 'admin')->permission('kelola-admin')->count()); ?>/3
                        </span>
                        <span class="inline-flex items-center gap-1 text-[10px] font-medium text-blue-400">
                            <span class="w-1.5 h-1.5 bg-blue-400 rounded-full"></span>
                            Admin: <?php echo e(\App\Models\User::where('role', 'admin')->whereDoesntHave('permissions', fn($q) => $q->where('name', 'kelola-admin'))->count()); ?>/5
                        </span>
                    </div>
                </div>
                <button @click="openCreateModal()"
                    class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-500 border border-indigo-500 rounded-xl text-xs font-semibold text-white transition-all active:scale-95 shadow-lg shadow-indigo-900/30">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span class="hidden sm:inline">Tambah Admin</span>
                    <span class="sm:hidden">Tambah</span>
                </button>
            </div>
        </div>

        
        <div class="hidden sm:block bg-gray-900 border border-gray-800 rounded-xl overflow-hidden mb-4">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-800/80">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider w-10">No</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Nama Admin</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Password</th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-bold text-gray-500 uppercase tracking-wider">Level</th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-bold text-gray-500 uppercase tracking-wider w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/50">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $admins; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $admin): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-gray-800/30 transition-colors">
                            <td class="px-4 py-3 text-xs text-gray-600"><?php echo e($admins->firstItem() + $index); ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    
                                    <div>
                                        <p class="text-xs font-semibold text-gray-200">
                                            <?php echo e($admin->name); ?>

                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($admin->id === auth()->id()): ?>
                                                <span class="text-indigo-400">(Anda)</span>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2" x-data="{ show: false }">
                                    <span x-show="!show" class="font-mono text-xs text-gray-500">••••••••</span>
                                    <span x-show="show" class="font-mono text-xs text-gray-300"><?php echo e($admin->plain_password ?? '-'); ?></span>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($admin->plain_password): ?>
                                    <button @click="show = !show" type="button" class="text-gray-600 hover:text-gray-400 transition-colors">
                                        <svg x-show="!show" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <svg x-show="show" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                        </svg>
                                    </button>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($admin->hasPermissionTo('kelola-admin')): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-yellow-950 border border-yellow-700/50 text-yellow-400">
                                        👑 Super Admin
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-950 border border-blue-700/50 text-blue-400">
                                        ⚡ Admin
                                    </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="openEditModal(<?php echo e(json_encode([
                                        'id' => $admin->id,
                                        'name' => $admin->name,
                                        'is_super_admin' => $admin->hasPermissionTo('kelola-admin')
                                    ])); ?>)"
                                        class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-indigo-950 border border-indigo-800/50 text-indigo-400 hover:bg-indigo-900/50 transition-colors">
                                        Edit
                                    </button>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$admin->hasPermissionTo('kelola-admin') && $admin->id !== auth()->id()): ?>
                                    <form action="<?php echo e(route('admin.admins.destroy', $admin)); ?>" method="POST" class="inline"
                                          onsubmit="return confirm('Hapus admin <?php echo e($admin->name); ?>?')">
                                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                        <button type="submit"
                                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-red-950 border border-red-800/50 text-red-400 hover:bg-red-900/50 transition-colors">
                                            Hapus
                                        </button>
                                    </form>
                                    <?php elseif($admin->hasPermissionTo('kelola-admin')): ?>
                                    <span class="text-[10px] text-gray-700 italic">Terlindungi</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center">
                                <svg class="mx-auto h-10 w-10 text-gray-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p class="text-xs text-gray-600">Tidak ada data admin</p>
                            </td>
                        </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        
        <div class="sm:hidden space-y-2 mb-4">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $admins; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $admin): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-3">
                <div class="flex items-start gap-3">
                    
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-xs font-semibold text-gray-200">
                                <?php echo e($admin->name); ?>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($admin->id === auth()->id()): ?>
                                    <span class="text-indigo-400 text-[10px]">(Anda)</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </p>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($admin->hasPermissionTo('kelola-admin')): ?>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-yellow-950 border border-yellow-700/50 text-yellow-400">👑 Super</span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-blue-950 border border-blue-700/50 text-blue-400">⚡ Admin</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        
                        <div class="flex items-center gap-1.5 mt-1" x-data="{ show: false }">
                            <span class="text-[10px] text-gray-600">Password:</span>
                            <span x-show="!show" class="font-mono text-[10px] text-gray-500">••••••••</span>
                            <span x-show="show" class="font-mono text-[10px] text-gray-400"><?php echo e($admin->plain_password ?? '-'); ?></span>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($admin->plain_password): ?>
                            <button @click="show = !show" type="button" class="text-gray-600 hover:text-gray-400">
                                <svg x-show="!show" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="show" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <button @click="openEditModal(<?php echo e(json_encode([
                            'id' => $admin->id,
                            'name' => $admin->name,
                            'is_super_admin' => $admin->hasPermissionTo('kelola-admin')
                        ])); ?>)"
                            class="px-2.5 py-1.5 rounded-lg text-[10px] font-bold bg-indigo-950 border border-indigo-800/50 text-indigo-400 active:scale-95 transition-all">
                            Edit
                        </button>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$admin->hasPermissionTo('kelola-admin') && $admin->id !== auth()->id()): ?>
                        <form action="<?php echo e(route('admin.admins.destroy', $admin)); ?>" method="POST"
                              onsubmit="return confirm('Hapus admin <?php echo e($admin->name); ?>?')">
                            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                            <button type="submit"
                                class="px-2.5 py-1.5 rounded-lg text-[10px] font-bold bg-red-950 border border-red-800/50 text-red-400 active:scale-95 transition-all">
                                Hapus
                            </button>
                        </form>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-8 text-center">
                <p class="text-xs text-gray-600">Tidak ada data admin</p>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($admins->hasPages()): ?>
        <div class="mt-2">
            <?php echo e($admins->links()); ?>

        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    </div>

    
    <div x-show="isOpen"
         class="fixed inset-0 z-[60] overflow-y-auto"
         style="display: none;"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="closeModal()"></div>

        <div class="relative min-h-screen flex items-end sm:items-center justify-center p-0 sm:p-4">
            <div class="bg-gray-900 border border-gray-700 rounded-t-2xl sm:rounded-2xl shadow-2xl w-full sm:max-w-lg overflow-hidden"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0">

                
                <div class="bg-gray-800 border-b border-gray-700 px-4 py-3 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <div class="w-6 h-6 bg-indigo-600/30 border border-indigo-600/50 rounded-lg flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <span x-text="isEdit ? 'Edit Admin' : 'Tambah Admin Baru'"></span>
                    </h3>
                    <button @click="closeModal()" class="w-7 h-7 rounded-lg bg-gray-700 hover:bg-gray-600 flex items-center justify-center transition-colors">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form :action="formAction" method="POST" class="p-4 space-y-4">
                    <?php echo csrf_field(); ?>
                    <template x-if="isEdit">
                        <?php echo method_field('PUT'); ?>
                    </template>

                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 mb-1.5">
                            Nama Lengkap <span class="text-red-400">*</span>
                        </label>
                        <input name="name" type="text" required x-model="formData.name"
                            @input="smartCapitalizeInput($event)"
                            placeholder="Ahmad Zainudin"
                            class="w-full px-3 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-sm text-white placeholder-gray-600 outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition" />
                        <p class="text-[10px] text-gray-600 mt-1">Nama yang akan ditampilkan di sistem</p>
                    </div>

                    
                    <template x-if="!isEdit">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-400 mb-1.5">
                                    Password <span class="text-red-400">*</span>
                                </label>
                                <div class="relative" x-data="{ showPass: false }">
                                    <input name="password" :type="showPass ? 'text' : 'password'" required
                                        x-model="formData.password"
                                        placeholder="Min. 8 karakter"
                                        class="w-full px-3 py-2.5 pr-9 bg-gray-800 border border-gray-700 rounded-xl text-sm text-white placeholder-gray-600 outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition" />
                                    <button @click="showPass = !showPass" type="button" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-400 transition-colors">
                                        <svg x-show="!showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <svg x-show="showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-400 mb-1.5">
                                    Konfirmasi <span class="text-red-400">*</span>
                                </label>
                                <div class="relative" x-data="{ showConfirm: false }">
                                    <input name="password_confirmation" :type="showConfirm ? 'text' : 'password'" required
                                        x-model="formData.password_confirmation"
                                        placeholder="Ulangi password"
                                        class="w-full px-3 py-2.5 pr-9 bg-gray-800 border border-gray-700 rounded-xl text-sm text-white placeholder-gray-600 outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition" />
                                    <button @click="showConfirm = !showConfirm" type="button" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-400 transition-colors">
                                        <svg x-show="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <svg x-show="showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 mb-2">
                            Level Admin <span class="text-red-400">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-start gap-2.5 cursor-pointer p-3 border-2 rounded-xl transition-all"
                                   :class="formData.is_super_admin === 1 ? 'border-yellow-600/60 bg-yellow-950/40' : 'border-gray-700 bg-gray-800/50 hover:border-gray-600'">
                                <input type="radio" name="is_super_admin" value="1"
                                    x-model.number="formData.is_super_admin"
                                    class="w-3.5 h-3.5 mt-0.5 text-yellow-500 bg-gray-700 border-gray-600 focus:ring-yellow-500 focus:ring-offset-gray-900 flex-shrink-0">
                                <div>
                                    <span class="text-xs font-bold text-gray-200 block">👑 Super Admin</span>
                                    <p class="text-[10px] text-gray-500 mt-0.5">Akses penuh, maks 3 akun</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2.5 cursor-pointer p-3 border-2 rounded-xl transition-all"
                                   :class="formData.is_super_admin === 0 ? 'border-blue-600/60 bg-blue-950/40' : 'border-gray-700 bg-gray-800/50 hover:border-gray-600'">
                                <input type="radio" name="is_super_admin" value="0"
                                    x-model.number="formData.is_super_admin"
                                    class="w-3.5 h-3.5 mt-0.5 text-blue-500 bg-gray-700 border-gray-600 focus:ring-blue-500 focus:ring-offset-gray-900 flex-shrink-0">
                                <div>
                                    <span class="text-xs font-bold text-gray-200 block">⚡ Admin Biasa</span>
                                    <p class="text-[10px] text-gray-500 mt-0.5">Akses terbatas, maks 5 akun</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    
                    <div class="bg-indigo-950/40 border border-indigo-800/40 rounded-xl p-3 flex items-start gap-2">
                        <svg class="w-3.5 h-3.5 text-indigo-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-[10px] text-indigo-300 leading-relaxed">
                            Super Admin (maks 3) tidak dapat dihapus. Admin Biasa (maks 5) dapat dikelola sepenuhnya.
                        </p>
                    </div>

                    
                    <div x-show="isEdit" class="bg-amber-950/40 border border-amber-800/40 rounded-xl p-3 flex items-start gap-2">
                        <svg class="w-3.5 h-3.5 text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-[10px] text-amber-300 leading-relaxed">
                            Password tidak dapat diubah melalui form ini demi keamanan sistem.
                        </p>
                    </div>

                    
                    <div class="flex gap-2 pt-2 border-t border-gray-800">
                        <button type="button" @click="closeModal()"
                            class="flex-1 px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-xl text-xs font-semibold text-gray-300 hover:bg-gray-700 transition-colors">
                            Batal
                        </button>
                        <button type="submit"
                            class="flex-1 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 border border-indigo-500 rounded-xl text-xs font-semibold text-white transition-colors shadow-lg shadow-indigo-900/30">
                            <span x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Admin'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\raport-tahfizh\resources\views/admin/admins/index.blade.php ENDPATH**/ ?>