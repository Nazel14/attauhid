/**
 * ✅ UNIFIED INPUT JAVASCRIPT - FIXED VERSION v3.7
 * PERUBAHAN v3.7:
 * - HAPUS submit handler untuk formCombined & formSetoranOnly
 *   (biarkan setoran-interactive.js yang handle, agar tidak konflik)
 * - Presensi Only: submit native tanpa intercept quranDisplay
 * - Tidak ada fungsi yang dihapus, hanya submit handler yang konflik dihilangkan
 * - switchMode() tetap di create.blade.php
 */

console.log('✅ Unified Input JS v3.7 loading...');

// =====================================================
// ✅ NOTE: switchMode() is defined in create.blade.php
// We do NOT define it here to avoid conflicts
// =====================================================

// =====================================================
// GLOBAL STATE
// =====================================================
let currentMode = 'combined';
let quranLoaded = {
    combined: false,
    setoran_only: false
};

// =====================================================
// ✅ SIMPLIFIED DEPENDENCY CHECK - ONLY CHECK CRITICAL
// =====================================================
let dependenciesReady = false;
let checkAttempts = 0;
const MAX_ATTEMPTS = 40; // 20 seconds max wait

function checkDependencies() {
    checkAttempts++;
    
    // ✅ Only check the most critical functions
    const critical = ['loadQuran', 'countTotalWords', 'markWord'];
    const missing = critical.filter(fn => typeof window[fn] !== 'function');
    
    if (missing.length > 0) {
        if (checkAttempts >= MAX_ATTEMPTS) {
            console.error('❌ Failed to load dependencies after 20 seconds');
            console.error('❌ Missing:', missing);
            console.log('💡 Try: Hard refresh (Ctrl+Shift+R) or clear browser cache');
            
            // Show user-friendly error
            const errorDiv = document.createElement('div');
            errorDiv.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg z-50 max-w-md';
            errorDiv.innerHTML = `
                <div class="font-bold mb-2">⚠️ System Error</div>
                <div class="text-sm">Gagal memuat komponen sistem. Silakan:</div>
                <ol class="text-sm mt-2 list-decimal list-inside">
                    <li>Hard refresh: tekan <kbd class="bg-red-700 px-1 rounded">Ctrl+Shift+R</kbd></li>
                    <li>Atau clear browser cache</li>
                    <li>Refresh halaman lagi</li>
                </ol>
            `;
            document.body.appendChild(errorDiv);
            return false;
        }
        
        // Only log every 5th attempt to reduce console spam
        if (checkAttempts % 5 === 0) {
            console.warn(`⏳ Attempt ${checkAttempts}/${MAX_ATTEMPTS}: Still waiting...`, missing);
        }
        
        setTimeout(checkDependencies, 500);
        return false;
    }
    
    dependenciesReady = true;
    console.log(`✅ All dependencies loaded (took ${checkAttempts * 0.5}s)`);
    
    return true;
}

// Start checking immediately
checkDependencies();

// =====================================================
// ✅ PRESENSI ONLY FORM VALIDATION - STANDALONE
// Tidak intercept formCombined & formSetoranOnly
// karena sudah dihandle oleh setoran-interactive.js
// =====================================================
function setupPresensiOnlyValidation() {
    console.log('✅ Setting up Presensi Only validation...');
    
    const formPresensiOnly = document.getElementById('formPresensiOnly');
    if (!formPresensiOnly) {
        console.warn('⚠️ formPresensiOnly not found');
        return;
    }

    // ✅ Cegah double-attach: cek apakah sudah ada listener
    if (formPresensiOnly._presensiValidationAttached) {
        console.log('ℹ️ Presensi Only validation already attached, skip');
        return;
    }
    formPresensiOnly._presensiValidationAttached = true;

    formPresensiOnly.addEventListener('submit', function(e) {
        console.log('📝 Form Presensi Only submit triggered');
        
        // Ambil nilai dari native select (bukan custom select widget)
        const santriId = formPresensiOnly.querySelector('[name="santri_id"]')?.value;
        const tanggal = formPresensiOnly.querySelector('[name="tanggal"]')?.value;
        const sesi = formPresensiOnly.querySelector('[name="sesi"]')?.value;
        const status = formPresensiOnly.querySelector('[name="status"]:checked');
        
        console.log('📋 Presensi Only data:', { santriId, tanggal, sesi, status: status?.value });
        
        if (!santriId || santriId === '') {
            e.preventDefault();
            showToast('⚠️ Pilih santri terlebih dahulu!', 'error');
            return;
        }
        
        if (!tanggal) {
            e.preventDefault();
            showToast('⚠️ Pilih tanggal terlebih dahulu!', 'error');
            return;
        }
        
        if (!sesi || sesi === '') {
            e.preventDefault();
            showToast('⚠️ Pilih sesi terlebih dahulu!', 'error');
            return;
        }
        
        if (!status) {
            e.preventDefault();
            showToast('⚠️ Pilih status kehadiran!', 'error');
            return;
        }
        
        // ✅ Validasi passed - disable tombol & submit
        console.log('✅ Presensi Only validation passed, submitting...');
        
        const submitBtn = formPresensiOnly.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.innerHTML = '⏳ Menyimpan...';
            submitBtn.disabled = true;
        }
        
        // Biarkan form submit secara native (tidak e.preventDefault())
    });
    
    console.log('✅ Presensi Only validation setup complete');
}

// =====================================================
// ✅ LOAD PRESENSI HARI INI
// =====================================================
async function loadPresensiHariIni() {
    console.log('📊 Loading presensi hari ini...');
    
    try {
        // Ambil tanggal dari form mana pun yang tersedia
        const tanggalInput = 
            document.getElementById('tanggalCombined') ||
            document.getElementById('tanggalSetoranOnly') ||
            document.querySelector('[name="tanggal"]');
        
        const tanggal = tanggalInput ? tanggalInput.value : new Date().toISOString().split('T')[0];
        
        const response = await fetch(`/api/presensi/hari-ini?tanggal=${tanggal}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const data = await response.json();
        
        // Update statistics
        const statHadir = document.getElementById('statHadir');
        const statSakit = document.getElementById('statSakit');
        const statIzin = document.getElementById('statIzin');
        const statAlpha = document.getElementById('statAlpha');
        
        if (statHadir) statHadir.textContent = data.hadir || 0;
        if (statSakit) statSakit.textContent = data.sakit || 0;
        if (statIzin) statIzin.textContent = data.izin || 0;
        if (statAlpha) statAlpha.textContent = data.alpha || 0;
        
        // Update table headers
        const sesiHeaders = data.sesi_aktif || [];
        const tableHeaderRow = document.getElementById('tableHeaderRow');
        
        if (tableHeaderRow) {
            while (tableHeaderRow.children.length > 2) {
                tableHeaderRow.removeChild(tableHeaderRow.lastChild);
            }
            
            sesiHeaders.forEach(sesi => {
                const th = document.createElement('th');
                th.className = 'px-2 py-1 text-center text-gray-700 dark:text-gray-300 border-b dark:border-gray-600 w-16';
                th.textContent = sesi;
                tableHeaderRow.appendChild(th);
            });
        }
        
        // Update table body
        const tableBody = document.getElementById('tableBodyPresensi');
        
        if (tableBody) {
            const isDark = document.documentElement.classList.contains('dark');
            const setoranBg = isDark ? '#312e81' : '#e0e7ff';
            const setoranFg = isDark ? '#a5b4fc' : '#4338ca';
            if (data.santri_list && data.santri_list.length > 0) {
                tableBody.innerHTML = data.santri_list.map((santri, index) => {
                    const sesiCells = sesiHeaders.map(sesi => {
                        const p = santri.sesi_data && santri.sesi_data[sesi];
                        if (p) {
                            // Inline hex agar warna titik status TIDAK ter-purge Tailwind
                            const dotColor = { Hadir:'#10b981', Sakit:'#f97316', Izin:'#f59e0b', Alpha:'#ef4444' }[p.status] || '#9ca3af';
                            const jumlahSetoran = p.jumlah_setoran || 0;
                            const waktu = p.waktu || '';
                            
                            return `<td class="px-1 py-2 border-b dark:border-gray-700">
                                <div class="flex flex-col items-center justify-center gap-1">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <div class="w-5 h-5 rounded-full flex-shrink-0 cursor-help" 
                                            style="background-color:${dotColor};"
                                            title="${p.status}${waktu ? ' - ' + waktu : ''}"></div>
                                        ${jumlahSetoran > 0 ? 
                                            `<span class="setoran-badge px-1 rounded text-[9px]" 
                                            title="${jumlahSetoran} setoran">📖${jumlahSetoran}</span>` 
                                            : ''
                                        }
                                    </div>
                                    ${waktu ? 
                                        `<span class="text-[9px] text-gray-500 dark:text-gray-400 font-mono whitespace-nowrap">${waktu}</span>` 
                                        : ''
                                    }
                                </div>
                            </td>`;
                        }
                        return `<td class="px-1 py-2 border-b dark:border-gray-700"><div class="flex items-center justify-center"><div class="w-5 h-5 rounded-full flex-shrink-0" style="border:2px solid #9ca3af;"></div></div></td>`;
                    }).join('');

                    return `<tr style="transition: background-color 0.15s;" onmouseover="this.style.backgroundColor='rgba(99,102,241,0.06)'" onmouseout="this.style.backgroundColor=''">
                        <td class="px-2 py-2.5 border-b dark:border-gray-700 text-gray-700 dark:text-gray-300 text-center font-medium">${index + 1}</td>
                        <td class="px-2 py-2.5 border-b dark:border-gray-700 text-gray-700 dark:text-gray-300 font-medium">${santri.name}</td>
                        ${sesiCells}
                    </tr>`;
                }).join('');
            } else {
                const colspan = 2 + sesiHeaders.length;
                tableBody.innerHTML = `<tr><td colspan="${colspan}" class="text-center py-4 text-gray-500 dark:text-gray-400 italic">Tidak ada santri di kelas ini.</td></tr>`;
            }
        }
        
        console.log('✅ Presensi hari ini loaded');
        
    } catch (error) {
        console.error('❌ Error loading presensi:', error);
        const tableBody = document.getElementById('tableBodyPresensi');
        if (tableBody) {
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-red-500">❌ Error memuat data presensi</td></tr>`;
        }
    }
}

// =====================================================
// TOAST NOTIFICATION
// =====================================================
function showToast(message, type = 'info') {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500',
        warning: 'bg-yellow-500'
    };
    
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 ${colors[type] || 'bg-gray-500'} text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-opacity duration-300`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}



// =====================================================
// ✅ EXPORT TO WINDOW SCOPE
// =====================================================
window.showToast = showToast;
window.loadPresensiHariIni = loadPresensiHariIni;

// ✅ NOTE: loadQuranCombined and loadQuranSetoranOnly are defined in mode-handler.js
// setupFormSubmitHandler is defined in setoran-interactive.js
// Do NOT override them here

// =====================================================
// DOM READY INITIALIZATION
// =====================================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM Ready - Initializing Unified Input v3.7...');
    
    // ✅ Hanya setup validasi presensi-only
    // formCombined & formSetoranOnly dihandle oleh setoran-interactive.js
    setupPresensiOnlyValidation();
    
   
    
    // ✅ Load presensi on page load
    loadPresensiHariIni();
    
    // ✅ Auto-refresh presensi every 30 seconds
    setInterval(loadPresensiHariIni, 30000);
    
    // ✅ Watch for tanggal changes (semua form)
    document.querySelectorAll('[name="tanggal"]').forEach(input => {
        input.addEventListener('change', loadPresensiHariIni);
    });
    
    console.log('✅ Unified Input v3.7 initialization complete');
    console.log('ℹ️  switchMode() → create.blade.php');
    console.log('ℹ️  loadQuranCombined/SetoranOnly() → mode-handler.js');
    console.log('ℹ️  setupFormSubmitHandler() → setoran-interactive.js');
    console.log('ℹ️  Presensi Only validation → unified-input.js (this file)');
});

console.log('✅ Unified Input Script v3.7 LOADED - NO CONFLICT, PRESENSI-ONLY FIXED');