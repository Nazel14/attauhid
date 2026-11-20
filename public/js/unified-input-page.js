// ============================================================
// unified-input-page.js
// Dibersihkan dari sintaks Blade ({{ }}, {{-- --}}, <script>)
// agar bisa dipakai sebagai file JS murni / diimport ke JSX.
// ============================================================

// ============================================================
// FIX UTAMA: syncStatistikToDrawer
// Menyalin nilai statistik dari elemen asli (desktop hidden)
// ke elemen drawer (dengan suffix Drawer) agar tampil di mobile
// ============================================================
function syncStatistikToDrawer() {
    var pairs = [
        ['displayTotalKata',  'displayTotalKataDrawer'],
        ['displayJuzRange',   'displayJuzRangeDrawer'],
        ['displayTotalJuz',   'displayTotalJuzDrawer'],
        ['displayPageStart',  'displayPageStartDrawer'],
        ['displayPageEnd',    'displayPageEndDrawer'],
        ['displayTotalPage',  'displayTotalPageDrawer'],
    ];
    pairs.forEach(function(pair) {
        var src = document.getElementById(pair[0]);
        var dst = document.getElementById(pair[1]);
        if (src && dst && dst.textContent !== src.textContent) {
            dst.textContent = src.textContent;
        }
    });
}

function openDrawer(type) {
    // FIX: cegah pemanggilan ganda dalam window 300ms
    const now = Date.now();
    if (window._lastOpenDrawerCall && (now - window._lastOpenDrawerCall.time) < 300 && window._lastOpenDrawerCall.type === type) {
        console.warn('openDrawer("' + type + '") dipanggil dua kali dalam <300ms — skip duplikat');
        return;
    }
    window._lastOpenDrawerCall = { time: now, type: type };

    const overlay = document.getElementById('mobileDrawerOverlay');
    const drawer  = document.getElementById('mobileDrawer');
    const title   = document.getElementById('drawerTitle');
    const content = document.getElementById('drawerContent');

    if (type === 'presensi') {
        title.textContent = 'Presensi Hari Ini';
        content.innerHTML = document.getElementById('defaultView').innerHTML;
        const btn = content.querySelector('[onclick="loadPresensiHariIni()"]');
        if (btn) btn.onclick = () => loadPresensiHariIniToTarget(content);
        loadPresensiHariIniToTarget(content);

    } else if (type === 'quran') {
        title.innerHTML = 'Penilaian';

        // Clone quranPanel
        const quranPanelEl = document.getElementById('quranPanel');
        const quranClone   = quranPanelEl.cloneNode(true);
        quranClone.id      = 'quranPanelDrawer';
        quranClone.classList.remove('hidden');
        quranClone.style.display = '';

        // Ubah ID quranDisplay di dalam clone agar tidak duplikat
        const clonedQuranDisplay = quranClone.querySelector('#quranDisplay');
        if (clonedQuranDisplay) {
            clonedQuranDisplay.id = 'quranDisplayDrawer';

            const originalDisplay = document.getElementById('quranDisplay');

            // Fungsi sync konten Al-Quran dari elemen asli ke drawer
            function syncContentToDrawer() {
                if (!originalDisplay) return false;
                const html = originalDisplay.innerHTML.trim();
                // Belum siap kalau kosong atau masih loading
                if (html.length < 100 || html.includes('Memuat')) {
                    return false;
                }
                clonedQuranDisplay.innerHTML = html;

                // FIX MARKING DI MOBILE:
                // Klik di clone drawer akan diproxy ke elemen asli di #quranDisplay,
                // sehingga markWord() bekerja pada elemen asli (yang di-scope counter).
                // Setelah itu, style elemen asli disalin ke clone agar tampak ter-mark.
                const originalWords = originalDisplay.querySelectorAll('.word-item');
                const clonedWords   = clonedQuranDisplay.querySelectorAll('.word-item');

                

                // Fungsi helper: terapkan style marking ke clone berdasarkan state
                function applyMarkStyleToClone(targetEl, state) {
                    // Reset dulu
                    targetEl.style.cssText = targetEl.style.cssText
                        .replace(/background[^;]*;?/gi, '')
                        .replace(/border-radius[^;]*;?/gi, '')
                        .replace(/padding[^;]*;?/gi, '')
                        .replace(/box-shadow[^;]*;?/gi, '')
                        .replace(/transform[^;]*;?/gi, '');

                    // Pastikan cursor & transition tetap
                    targetEl.style.cursor     = 'pointer';
                    targetEl.style.transition = 'all 0.25s cubic-bezier(0.4, 0, 0.2, 1)';

                    if (state === '1') {
                        targetEl.style.setProperty('background', 'linear-gradient(135deg, rgba(34, 197, 94, 0.25) 0%, rgba(34, 197, 94, 0.35) 100%)', 'important');
                        targetEl.style.setProperty('border-radius', '8px', 'important');
                        targetEl.style.setProperty('padding', '3px 8px', 'important');
                        targetEl.style.setProperty('box-shadow', '0 2px 8px rgba(34, 197, 94, 0.3), inset 0 1px 2px rgba(255, 255, 255, 0.1)', 'important');
                        targetEl.style.setProperty('transform', 'translateY(-1px)', 'important');
                    } else if (state === '2') {
                        targetEl.style.setProperty('background', 'linear-gradient(135deg, rgba(234, 179, 8, 0.3) 0%, rgba(234, 179, 8, 0.4) 100%)', 'important');
                        targetEl.style.setProperty('border-radius', '8px', 'important');
                        targetEl.style.setProperty('padding', '3px 8px', 'important');
                        targetEl.style.setProperty('box-shadow', '0 2px 8px rgba(234, 179, 8, 0.35), inset 0 1px 2px rgba(255, 255, 255, 0.15)', 'important');
                        targetEl.style.setProperty('transform', 'translateY(-1px)', 'important');
                    } else if (state === '3') {
                        targetEl.style.setProperty('background', 'linear-gradient(135deg, rgba(239, 68, 68, 0.25) 0%, rgba(239, 68, 68, 0.35) 100%)', 'important');
                        targetEl.style.setProperty('border-radius', '8px', 'important');
                        targetEl.style.setProperty('padding', '3px 8px', 'important');
                        targetEl.style.setProperty('box-shadow', '0 2px 8px rgba(239, 68, 68, 0.3), inset 0 1px 2px rgba(255, 255, 255, 0.1)', 'important');
                        targetEl.style.setProperty('transform', 'translateY(-1px)', 'important');
                    }
                }

                clonedWords.forEach(function(clonedWord, idx) {
                    const originalWord = originalWords[idx];
                    if (!originalWord) return;

                    // FIX KRITIS: removeAttribute BENAR-BENAR menghapus onclick inline.
                    // setAttribute('onclick','') TIDAK efektif — onclick handler tetap aktif sebagai DOM property.
                    clonedWord.removeAttribute('onclick');
                    clonedWord.onclick = null;

                    clonedWord.style.cursor = 'pointer';

                    // State awal: ikuti original (kalau sudah pernah di-mark)
                    const initState = originalWord.getAttribute('data-mark-state') || '0';
                    clonedWord.setAttribute('data-mark-state', initState);
                    applyMarkStyleToClone(clonedWord, initState);

                    // Handler klik: trigger markWord di original, lalu apply style ke clone
                    clonedWord.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();  // cegah onclick inline yang mungkin masih nyangkut

                        console.log('PROXY CLICK fired for word #' + idx);

                        // Manual cycle state agar konsisten (tidak tergantung markWord mana yang aktif)
                        const currentState = parseInt(originalWord.getAttribute('data-mark-state') || '0');
                        const newState = ((currentState + 1) % 4).toString();

                        // Update state di ORIGINAL
                        originalWord.setAttribute('data-mark-state', newState);
                        originalWord.dataset.markState = newState;

                        console.log('Original word #' + idx + ' state: ' + originalWord.getAttribute('data-mark-state'));

                        // Bersihkan style lama di original (background, classes flat marking)
                        originalWord.classList.remove(
                            'bg-green-200', 'bg-yellow-200', 'bg-red-200',
                            'dark:bg-green-800', 'dark:bg-yellow-800', 'dark:bg-red-800',
                            'border-l-4', 'border-green-500', 'border-yellow-500', 'border-red-500'
                        );
                        originalWord.style.backgroundColor = '';
                        originalWord.style.background      = '';
                        originalWord.style.borderRadius    = '';
                        originalWord.style.padding         = '';
                        originalWord.style.boxShadow       = '';
                        originalWord.style.transform       = '';

                        // Apply style GRADIENT SMOOTH (sama persis dengan desktop) ke original
                        applyMarkStyleToClone(originalWord, newState);

                        // Apply style yang sama ke clone (visual sinkron)
                        clonedWord.setAttribute('data-mark-state', newState);
                        clonedWord.dataset.markState = newState;
                        applyMarkStyleToClone(clonedWord, newState);

                        // Update counter SECARA MANUAL berdasarkan state di #quranDisplay
                        updateCounterFromOriginal();
                    });

                    // Fungsi helper: hitung counter dari #quranDisplay & update display + hidden inputs + global kesalahanCount
                    function updateCounterFromOriginal() {
                        const quranDisplay = document.getElementById('quranDisplay');
                        if (!quranDisplay) return;

                        const kelancaranCount = quranDisplay.querySelectorAll('[data-mark-state="1"]').length;
                        const tajwidCount     = quranDisplay.querySelectorAll('[data-mark-state="2"]').length;
                        const pelafalanCount  = quranDisplay.querySelectorAll('[data-mark-state="3"]').length;
                        const totalKesalahan  = kelancaranCount + tajwidCount + pelafalanCount;

                        // FIX: Samakan rumus dengan desktop (setoran-interactive.js -> updateKesalahanDisplay)
                        // - Total kata < 100 -> setiap kesalahan = -1 poin (fix)
                        // - Total kata >= 100 -> kesalahan dihitung sebagai persentase dari total kata
                        const totalKata = quranDisplay.querySelectorAll('.word-item').length;
                        let nilai = 100;
                        if (totalKata > 0) {
                            nilai = totalKata < 100
                                ? 100 - (totalKesalahan * 4)
                                : 100 - ((totalKesalahan * 4) / totalKata) * 100;
                            nilai = Math.max(0, Math.min(100, Math.round(nilai)));
                        }
                        console.log(`Mobile nilai calc: totalKata=${totalKata}, kesalahan=${totalKesalahan}, nilai=${nilai}`);

                        // FIX KRITIS: Update juga variabel GLOBAL `kesalahanCount` di setoran-interactive.js
                        // generatePDFContent() membaca dari variable global ini untuk render tabel "Detail Koreksi".
                        // Tanpa update ini, PDF akan selalu tampilkan "-" di kolom Jumlah & Nilai = 100.
                        if (typeof window.kesalahanCount !== 'undefined') {
                            window.kesalahanCount.kelancaran = kelancaranCount;
                            window.kesalahanCount.tajwid     = tajwidCount;
                            window.kesalahanCount.pelafalan  = pelafalanCount;
                            console.log('Global kesalahanCount synced:', window.kesalahanCount);
                        }

                        // Update untuk kedua mode (Combined & SetoranOnly) — yang aktif saja yang berdampak
                        ['Combined', 'SetoranOnly'].forEach(function(mode) {
                            const setVal  = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };
                            const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };

                            setVal('kesalahanKelancaran' + mode, kelancaranCount);
                            setVal('kesalahanTajwid'     + mode, tajwidCount);
                            setVal('kesalahanPelafalan'  + mode, pelafalanCount);
                            setVal('nilai'               + mode, nilai);

                            setText('displayKelancaran' + mode, kelancaranCount);
                            setText('displayTajwid'     + mode, tajwidCount);
                            setText('displayPelafalan'  + mode, pelafalanCount);
                            setText('displayNilai'      + mode, nilai);
                        });
                    }
                });

                console.log('Drawer: content synced + click proxy attached for', clonedWords.length, 'words');
                return true;
            }

            // Referensi drawer untuk timer management
            const drawerEl = document.getElementById('mobileDrawer');

            // Coba sync langsung (Quran sudah dimuat sebelumnya)
            if (syncContentToDrawer()) {
                // Sync statistik langsung
                syncStatistikToDrawer();
                // Jalankan interval sync statistik selama drawer terbuka (realtime update)
                if (drawerEl) {
                    drawerEl._statSyncTimer = setInterval(syncStatistikToDrawer, 500);
                }
            } else {
                // Belum siap — tampilkan loading dan polling
                clonedQuranDisplay.innerHTML = '<p style="color:#A8A29E;text-align:center;padding:40px 20px;font-family:system-ui;font-size:13px;">Memuat Al-Qur\'an...</p>';

                console.log('Drawer: waiting for #quranDisplay to be populated...');
                let attempts = 0;
                const maxAttempts = 60; // 30 detik max
                const pollTimer = setInterval(() => {
                    attempts++;
                    if (syncContentToDrawer()) {
                        clearInterval(pollTimer);
                        if (drawerEl) drawerEl._pollTimer = null;
                        // Sync statistik setelah konten berhasil dimuat
                        syncStatistikToDrawer();
                        // Mulai interval sync statistik
                        if (drawerEl) {
                            drawerEl._statSyncTimer = setInterval(syncStatistikToDrawer, 500);
                        }
                    } else if (attempts >= maxAttempts) {
                        clearInterval(pollTimer);
                        if (drawerEl) drawerEl._pollTimer = null;
                        console.warn('Drawer: timeout waiting for Quran content');
                        clonedQuranDisplay.innerHTML = '<p style="color:#EF4444;text-align:center;padding:20px;font-family:system-ui;font-size:13px;">Gagal memuat Al-Qur\'an. Tutup drawer dan coba lagi.</p>';
                    }
                }, 500);

                // Simpan timer ke drawer agar bisa dibatalkan saat ditutup
                if (drawerEl) drawerEl._pollTimer = pollTimer;
            }
        }

        // Ubah ID elemen statistik di dalam clone agar tidak duplikat dengan desktop
        var drawerOnlyIds = [
            'colorGuideContent', 'colorGuideIcon',
            'displayTotalKata', 'displayJuzRange', 'displayTotalJuz',
            'displayPageStart', 'displayPageEnd', 'displayTotalPage'
        ];
        drawerOnlyIds.forEach(function(id) {
            var el = quranClone.querySelector('#' + id);
            if (el) el.id = id + 'Drawer';
        });

        // Hapus header desktop (tombol Kembali & Reset di dalam clone)
        const desktopHeader = quranClone.querySelector('.hidden.md\\:flex');
        if (desktopHeader) desktopHeader.remove();

        // Padding panel desktop (p-5) dibuang — #drawerContent sudah ber-padding.
        quranClone.classList.remove('p-5');

        // FIX: Hapus blok Statistik Setoran dari clone agar tidak double.
        // Ditandai [data-stat-block] di InputSetoran.jsx. JANGAN pakai
        // closest('.rounded-xl') — kartu kecil "Total Kata" juga rounded-xl,
        // jadi yang terhapus hanya kartu itu dan statistik tampil dobel.
        const statBlockInClone = quranClone.querySelector('[data-stat-block]')
            || quranClone.querySelector('#displayTotalKataDrawer')?.closest('[data-stat-block]');
        if (statBlockInClone) statBlockInClone.remove();

        // Tentukan mode aktif
        const activeMode = (lastSelectedMode === 'setoran_only') ? 'SetoranOnly' : 'Combined';

        // Ambil nilai kesalahan & nilai dari hidden inputs form utama
        const valKelancaran = document.getElementById('kesalahanKelancaran' + activeMode)?.value || '0';
        const valTajwid     = document.getElementById('kesalahanTajwid'     + activeMode)?.value || '0';
        const valPelafalan  = document.getElementById('kesalahanPelafalan'  + activeMode)?.value || '0';
        const valNilai      = document.getElementById('nilai'               + activeMode)?.value || '100';
        const valTotalKata  = document.getElementById('totalKata'           + activeMode)?.value || '0';

        // Ambil nilai statistik dari elemen display asli untuk ditampilkan langsung
        const valTotalKataDisplay = document.getElementById('displayTotalKata')?.textContent || valTotalKata || '0';
        const valJuzRange         = document.getElementById('displayJuzRange')?.textContent   || '-';
        const valTotalJuz         = document.getElementById('displayTotalJuz')?.textContent   || '0.00';
        const valPageStart        = document.getElementById('displayPageStart')?.textContent  || '-';
        const valPageEnd          = document.getElementById('displayPageEnd')?.textContent    || '-';
        const valTotalPage        = document.getElementById('displayTotalPage')?.textContent  || '0.00';

        // Ambil nilai catatan guru yang sudah ada
        const valCatatan = document.getElementById('catatanGuru' + activeMode)?.value || '';

        const isSetoranOnly = activeMode === 'SetoranOnly';
        const submitLabel   = isSetoranOnly ? 'Simpan Setoran Tambahan' : 'Simpan Presensi &amp; Setoran';

        // ------------------------------------------------------------
        // Markup drawer — SAMA dengan kartu di InputSetoran.jsx.
        // PENTING: file ini tidak dipindai Tailwind. Setiap class di
        // bawah sengaja disalin persis dari InputSetoran.jsx supaya
        // pasti ikut ter-generate saat build. Jangan menambah class
        // Tailwind baru di sini tanpa memakainya juga di JSX.
        // ------------------------------------------------------------
        const CARD = 'bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl';
        const SUB  = 'bg-cream-100 dark:bg-coal-600/40 ring-1 ring-cream-300 dark:ring-coal-600 rounded-xl';
        const TILE = 'w-6 h-6 rounded-lg bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 text-stone-500 dark:text-stone-400 flex items-center justify-center flex-shrink-0';
        const BTN_SOFT = 'inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 hover:bg-cream-300 dark:hover:bg-coal-500 text-stone-600 dark:text-stone-300 text-xs font-semibold transition active:scale-95';
        const ico = (d, cls) => `<svg class="${cls || 'w-3.5 h-3.5'}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${d}</svg>`;
        const P_MIC    = '<path d="M19 11a7 7 0 01-14 0m7 7v3m-4 0h8m-4-6a3 3 0 01-3-3V5a3 3 0 016 0v4a3 3 0 01-3 3z"/>';
        const P_TRASH  = '<path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M4 7h16m-3 0V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3"/>';
        const P_CHART  = '<path d="M3 3v18h18M9 17V9m4 8V5m4 12v-6"/>';
        const P_DOC    = '<path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>';
        const P_PENCIL = '<path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828z"/>';
        const P_SAVE   = '<path d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-8 0V4a1 1 0 011-1h6a1 1 0 011 1v3m-8 0h8M9 21v-6h6v6"/>';
        const P_INFO   = '<path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>';
        const skorRow = (dot, label, id, val, valCls, span2) => `
                        <div class="${SUB} p-2.5 flex items-center justify-between ${span2 ? 'col-span-2' : ''}">
                            <span class="flex items-center gap-1.5 text-xs text-stone-600 dark:text-stone-400"><span class="w-2 h-2 rounded-full ${dot}"></span>${label}</span>
                            <strong id="${id}" class="text-base font-bold tabular-nums ${valCls}">${val}</strong>
                        </div>`;

        // Buat section Audio & Penilaian + Statistik Setoran + Catatan Guru + Submit untuk drawer mobile
        const audioWrapper = document.createElement('div');
        audioWrapper.className = 'space-y-3 mt-3';
        audioWrapper.innerHTML = `
            <div class="${CARD} p-4 sm:p-5">
                <div class="flex items-center gap-2 mb-4">
                    <span class="${TILE}">${ico(P_MIC)}</span>
                    <h3 class="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200">Audio &amp; Penilaian</h3>
                    <button type="button" onclick="clearMarking()" class="ml-auto ${BTN_SOFT}">${ico(P_TRASH)} Reset Marking</button>
                </div>
                <div class="space-y-3">
                    <div class="flex gap-2">
                        <button type="button" id="startRecording${activeMode}" onclick="startRecording('${activeMode}')" class="inline-flex flex-1 items-center justify-center gap-1.5 py-2.5 bg-red-600 hover:bg-red-500 text-white text-xs font-semibold rounded-xl transition active:scale-95"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="6"/></svg> Rekam</button>
                        <button type="button" id="pauseRecording${activeMode}" onclick="pauseRecording('${activeMode}')" class="hidden inline-flex flex-1 items-center justify-center gap-1.5 py-2.5 bg-amber-100 hover:bg-amber-200 text-amber-800 ring-1 ring-amber-200 text-xs font-semibold rounded-xl transition active:scale-95 dark:bg-amber-500/15 dark:hover:bg-amber-500/25 dark:text-amber-300 dark:ring-amber-500/25"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><rect x="8" y="6" width="3.2" height="12" rx="1"/><rect x="12.8" y="6" width="3.2" height="12" rx="1"/></svg> Jeda</button>
                        <button type="button" id="stopRecording${activeMode}" onclick="stopRecording('${activeMode}')" class="hidden inline-flex flex-1 items-center justify-center gap-1.5 py-2.5 bg-stone-700 hover:bg-stone-600 text-white text-xs font-semibold rounded-xl transition active:scale-95 dark:bg-coal-600 dark:hover:bg-coal-500 dark:ring-1 dark:ring-coal-500"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><rect x="7" y="7" width="10" height="10" rx="2"/></svg> Stop</button>
                    </div>
                    <div id="recordingTimer${activeMode}" class="hidden text-xs font-semibold tabular-nums text-red-600 dark:text-red-400 text-center py-2 bg-red-50 dark:bg-red-500/10 ring-1 ring-red-100 dark:ring-red-500/20 rounded-xl">Durasi: 0:00</div>
                    <audio id="audioPlayback${activeMode}" controls class="w-full hidden rounded-lg"></audio>
                    <button type="button" id="clearRecording${activeMode}" onclick="clearRecording('${activeMode}')" class="w-full py-2 ${BTN_SOFT} justify-center hidden">${ico(P_TRASH)} Hapus &amp; Rekam Ulang</button>
                    <input type="hidden" id="audioData${activeMode}">
                    <p class="mt-1.5 flex items-start gap-1 text-[11px] leading-snug text-stone-400 dark:text-stone-500">${ico(P_INFO, 'w-3 h-3 mt-px flex-shrink-0')}<span>Gunakan Jeda untuk berhenti sementara. Marking kata di atas untuk catat kesalahan.</span></p>

                    <div class="${SUB} p-3">
                        <p class="text-xs font-semibold text-stone-600 dark:text-stone-300 mb-2.5 flex items-center gap-1.5">${ico(P_CHART, 'w-3.5 h-3.5 text-stone-400')} Statistik Setoran</p>
                        <div class="grid grid-cols-3 gap-2 mb-2">
                            <div class="bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 p-2.5 rounded-xl"><p class="text-[11px] text-stone-500 dark:text-stone-400">Total Kata</p><p class="text-lg font-bold tracking-tight text-stone-900 dark:text-stone-100 tabular-nums" id="displayTotalKataDrawer">${valTotalKataDisplay}</p></div>
                            <div class="bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 p-2.5 rounded-xl"><p class="text-[11px] text-stone-500 dark:text-stone-400">Juz Range</p><p class="text-lg font-bold tracking-tight text-claude-600 dark:text-claude-400" id="displayJuzRangeDrawer">${valJuzRange}</p></div>
                            <div class="bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 p-2.5 rounded-xl"><p class="text-[11px] text-stone-500 dark:text-stone-400">Total Juz</p><p class="text-lg font-bold tracking-tight text-stone-900 dark:text-stone-100 tabular-nums" id="displayTotalJuzDrawer">${valTotalJuz}</p></div>
                        </div>
                        <div class="bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 p-2.5 rounded-xl">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[11px] text-stone-500 dark:text-stone-400 font-medium flex items-center gap-1">${ico(P_DOC, 'w-3 h-3 text-stone-400')} Halaman Mushaf Madinah</span>
                                <span class="text-[10px] text-stone-400">Otomatis</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="flex-1 ${SUB} px-2.5 py-2"><p class="text-[10px] text-stone-500">Mulai</p><p class="text-base font-bold text-stone-800 dark:text-stone-100 tabular-nums" id="displayPageStartDrawer">${valPageStart}</p></div>
                                <span class="text-claude-500">→</span>
                                <div class="flex-1 ${SUB} px-2.5 py-2"><p class="text-[10px] text-stone-500">Selesai</p><p class="text-base font-bold text-stone-800 dark:text-stone-100 tabular-nums" id="displayPageEndDrawer">${valPageEnd}</p></div>
                                <div class="flex-1 rounded-xl ring-1 ring-claude-200 dark:ring-claude-500/20 bg-claude-50 dark:bg-claude-500/10 px-2.5 py-2"><p class="text-[10px] text-stone-500">Total</p><p class="text-base font-bold text-claude-600 dark:text-claude-400 tabular-nums" id="displayTotalPageDrawer">${valTotalPage}</p></div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-1">
                        ${skorRow('bg-emerald-500', 'Kelancaran', 'displayKelancaran' + activeMode, valKelancaran, 'text-emerald-600 dark:text-emerald-400', true)}
                        ${skorRow('bg-amber-500', 'Tajwid', 'displayTajwid' + activeMode, valTajwid, 'text-amber-600 dark:text-amber-400', false)}
                        ${skorRow('bg-red-500', 'Pelafalan', 'displayPelafalan' + activeMode, valPelafalan, 'text-red-600 dark:text-red-400', false)}
                        <div class="col-span-2 rounded-xl ring-1 ring-claude-200 dark:ring-claude-500/20 bg-claude-50 dark:bg-claude-500/10 px-3 py-2.5 flex items-center justify-between">
                            <span class="text-xs font-bold text-stone-700 dark:text-stone-300">Nilai</span>
                            <strong id="displayNilai${activeMode}" class="text-2xl font-bold tracking-tight tabular-nums text-claude-600 dark:text-claude-400">${valNilai}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="${CARD} p-4 sm:p-5">
                <div class="flex items-center gap-2 mb-4">
                    <span class="${TILE}">${ico(P_PENCIL)}</span>
                    <h3 class="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200">Catatan Guru</h3>
                </div>
                <textarea id="drawerCatatanGuru${activeMode}" rows="3" class="form-textarea w-full" placeholder="Tambahkan catatan...">${valCatatan}</textarea>
            </div>

            <button type="button" onclick="submitFromDrawer('${activeMode}')"
                    class="w-full py-3 bg-claude-500 hover:bg-claude-600 text-white font-semibold text-sm rounded-xl transition active:scale-[.98] flex items-center justify-center gap-2">
                ${ico(P_SAVE, 'w-4 h-4')} ${submitLabel}
            </button>`;

        content.innerHTML = '';
        content.appendChild(quranClone);
        content.appendChild(audioWrapper);

        // Simpan mode untuk sync saat drawer ditutup
        drawer._syncAudioMode = activeMode;

        // Jalankan sync statistik sekali lagi setelah DOM drawer selesai dirender
        setTimeout(syncStatistikToDrawer, 100);
        setTimeout(syncStatistikToDrawer, 600);
        setTimeout(syncStatistikToDrawer, 1500);
    }

    overlay.classList.remove('hidden');
    drawer.classList.remove('hidden');
    requestAnimationFrame(() => drawer.classList.add('open'));
    document.body.style.overflow = 'hidden';
}

function closeDrawer() {
    const overlay = document.getElementById('mobileDrawerOverlay');
    const drawer  = document.getElementById('mobileDrawer');

    // Hentikan polling timer konten Al-Quran
    if (drawer._pollTimer) {
        clearInterval(drawer._pollTimer);
        drawer._pollTimer = null;
        console.log('Drawer poll timer cleared on close');
    }

    // Hentikan interval sync statistik
    if (drawer._statSyncTimer) {
        clearInterval(drawer._statSyncTimer);
        drawer._statSyncTimer = null;
        console.log('Drawer stat sync timer cleared on close');
    }

    // Sync audioData dari drawer ke hidden input form utama sebelum tutup
    if (drawer._syncAudioMode) {
        const mode = drawer._syncAudioMode;
        const drawerAudio = document.getElementById('audioData' + mode);
        const formId = mode === 'SetoranOnly' ? 'formSetoranOnly' : 'formCombined';
        const formAudio = document.querySelector('#' + formId + ' [name="audio_data"]');
        if (drawerAudio && formAudio && drawerAudio.value) {
            formAudio.value = drawerAudio.value;
        }
        drawer._syncAudioMode = null;
    }

    drawer.classList.remove('open');
    setTimeout(() => {
        drawer.classList.add('hidden');
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
    }, 300);
}

// ============================================================
// CSRF: ambil token terbaru dari server sebelum submit native.
// Cookie XSRF-TOKEN TIDAK bisa dipakai sebagai nilai _token (isinya terenkripsi),
// jadi harus tanya server.
// ============================================================
async function refreshCsrfToken() {
    try {
        const res = await fetch('/csrf-token', {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        });
        const data = await res.json();
        if (data?.token) {
            document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', data.token);
            document.querySelectorAll('input[name="_token"]').forEach((i) => { i.value = data.token; });
            console.log('CSRF token disegarkan');
        }
    } catch (e) {
        console.warn('Gagal menyegarkan CSRF token:', e);
    }
}
window.refreshCsrfToken = refreshCsrfToken;

// Submit dari drawer: drawer TETAP TERBUKA + loading overlay sampai server response.
// f.submit() native akan navigate halaman, jadi user lihat loading sebelum redirect.
async function submitFromDrawer(mode) {
    const formId = mode === 'SetoranOnly' ? 'formSetoranOnly' : 'formCombined';
    const drawer = document.getElementById('mobileDrawer');

    showSubmitLoadingOverlay();

    const drawerCatatan = document.getElementById('drawerCatatanGuru' + mode);
    const formCatatan   = document.querySelector('#' + formId + ' [name="catatan_guru"]');
    if (drawerCatatan && formCatatan) {
        formCatatan.value = drawerCatatan.value;
    }

    const drawerAudio = document.getElementById('audioData' + mode);
    const formAudio   = document.querySelector('#' + formId + ' [name="audio_data"]');
    if (drawerAudio && formAudio && drawerAudio.value) {
        formAudio.value = drawerAudio.value;
    }

    const submitBtnDrawer = document.querySelector(`button[onclick="submitFromDrawer('${mode}')"]`);
    if (submitBtnDrawer) {
        submitBtnDrawer.innerHTML = 'Menyimpan...';
        submitBtnDrawer.disabled  = true;
    }

    updateSubmitLoadingText('Membuat PDF dengan marking...');

    const deviceType = window.innerWidth <= 768  ? 'mobile'
                     : window.innerWidth <= 1024 ? 'tablet'
                     : 'desktop';

    try {
        if (typeof window.generateAndSavePDFToInput === 'function') {
            await window.generateAndSavePDFToInput(mode, deviceType);
        }
    } catch (err) {
        console.warn('PDF regenerate failed:', err.message);
    }

    updateSubmitLoadingText('Menyimpan ke server...');

    // FIX v4.0: Schedule notifikasi LANGSUNG SEBELUM form.submit()
    // Ini PASTI jalan karena tidak bergantung pada submit event listener.
    const successMsg = mode === 'SetoranOnly'
        ? 'Setoran tambahan berhasil disimpan!'
        : 'Presensi & setoran berhasil disimpan!';
    if (typeof window.scheduleNotification === 'function') {
        window.scheduleNotification(successMsg, 'success');
    }

    // Stop timer drawer
    if (drawer._pollTimer) { clearInterval(drawer._pollTimer); drawer._pollTimer = null; }
    if (drawer._statSyncTimer) { clearInterval(drawer._statSyncTimer); drawer._statSyncTimer = null; }
    drawer._syncAudioMode = null;

    // Tutup drawer
    drawer.classList.remove('open');
    const drawerOverlay = document.getElementById('mobileDrawerOverlay');
    if (drawerOverlay) drawerOverlay.classList.add('hidden');
    drawer.classList.add('hidden');
    document.body.style.overflow = '';

    setTimeout(async () => {
        const form = document.getElementById(formId);
        if (form) {
            await refreshCsrfToken();
            if (typeof window.guardPdfPayloadSize === 'function') window.guardPdfPayloadSize(form, mode);
            form.submit();
        } else {
            hideSubmitLoadingOverlay();
            alert('Form tidak ditemukan');
        }
    }, 300);
}

// Helper: tampilkan loading overlay full screen di atas drawer
function showSubmitLoadingOverlay() {
    // Hapus dulu kalau sudah ada (cegah double)
    const existing = document.getElementById('submitLoadingOverlay');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'submitLoadingOverlay';
    overlay.style.cssText = `
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: rgba(28, 28, 26, 0.88);
        backdrop-filter: blur(4px);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 16px;
        color: #F5F4EF;
        font-family: system-ui, -apple-system, sans-serif;
    `;
    overlay.innerHTML = `
        <div style="width:56px;height:56px;border:4px solid rgba(217,119,87,0.25);border-top-color:#D97757;border-radius:50%;animation:spin 0.8s linear infinite;"></div>
        <div id="submitLoadingText" style="font-size:14px;font-weight:500;text-align:center;max-width:280px;padding:0 20px;">
            Memproses...
        </div>
        <div style="font-size:11px;color:#A8A29E;text-align:center;padding:0 20px;">
            Mohon tunggu, jangan tutup halaman
        </div>
        <style>
            @keyframes spin { to { transform: rotate(360deg); } }
        </style>
    `;
    document.body.appendChild(overlay);
}

// Helper: update teks loading
function updateSubmitLoadingText(text) {
    const el = document.getElementById('submitLoadingText');
    if (el) el.textContent = text;
}

// Helper: hapus loading overlay (untuk fallback error)
function hideSubmitLoadingOverlay() {
    const overlay = document.getElementById('submitLoadingOverlay');
    if (overlay) overlay.remove();
}

let lastSelectedMode = null;

function toggleMode(radio) {
    if (lastSelectedMode === radio.value) {
        setTimeout(() => { radio.checked = false; }, 0);
        lastSelectedMode = null;
        resetToInitialState();
    } else {
        lastSelectedMode = radio.value;
    }
}

function resetToInitialState() {
    ['formCombined', 'formPresensiOnly', 'formSetoranOnly'].forEach(id => {
        const el = document.getElementById(id); if (el) el.classList.add('hidden');
    });
    const hint    = document.getElementById('hintBelumPilih');
    const divider = document.getElementById('hrDivider');
    if (hint)    hint.classList.remove('hidden');
    if (divider) divider.classList.add('hidden');
    showPanel('panelPetunjuk');
    document.getElementById('mobileFab')?.classList.add('hidden');
}

function switchMode(mode) {
    lastSelectedMode = mode;
    ['formCombined', 'formPresensiOnly', 'formSetoranOnly'].forEach(id => {
        const el = document.getElementById(id); if (el) el.classList.add('hidden');
    });
    const hint    = document.getElementById('hintBelumPilih');
    const divider = document.getElementById('hrDivider');
    if (hint)    hint.classList.add('hidden');
    if (divider) divider.classList.remove('hidden');
    const formMap = { combined: 'formCombined', presensi_only: 'formPresensiOnly', setoran_only: 'formSetoranOnly' };
    const formEl = document.getElementById(formMap[mode]);
    if (formEl) formEl.classList.remove('hidden');
    showPanel('defaultView');
    loadPresensiHariIni();
    document.getElementById('mobileFab')?.classList.remove('hidden');
}

function showPanel(panelId) {
    ['panelPetunjuk', 'defaultView', 'quranPanel'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        if (id === panelId) { el.style.display = ''; el.classList.remove('hidden'); }
        else { el.style.display = 'none'; el.classList.add('hidden'); }
    });
}

function showQuranPanel() {
    showPanel('quranPanel');
}

// Ganti judul panel kanan sesuai metode aktif (Quran / Iqro)
function setQuranPanelTitle(type) {
    const el = document.getElementById('quranPanelTitle');
    if (!el) return;
    const span = el.querySelector('span');
    if (span) span.textContent = type === 'iqro'
    ? ((window.currentMetode === 'Aisar' ? 'Aisar' : 'Iqro') + ' Digital')
    : "Al-Qur'an Digital";
}
window.setQuranPanelTitle = setQuranPanelTitle;

function backToDefaultView() {
    // FIX: JANGAN clear #quranDisplay & statistik.
    // Marking & konten Al-Qur'an HARUS tetap tersimpan biar saat user balik
    // tidak perlu fetch ulang. loadQuranCombined/SetoranOnly akan tetap reload
    // kalau surat+ayat berubah (lihat fix #2 di bawah).
    showPanel('defaultView');
}

// Helper: ambil "fingerprint" range Quran aktif (surat & ayat start-end)
// Dipakai untuk deteksi apakah user request surat+ayat yang sama atau berbeda.
function getCurrentQuranKey(mode) {
    const s1 = document.getElementById('suratStart' + mode)?.value || '';
    const a1 = document.getElementById('ayatStart'  + mode)?.value || '';
    const s2 = document.getElementById('suratEnd'   + mode)?.value || '';
    const a2 = document.getElementById('ayatEnd'    + mode)?.value || '';
    return `${s1}|${a1}|${s2}|${a2}`;
}

// Helper: cek apakah #quranDisplay sudah berisi range yang sama
function isQuranAlreadyLoaded(mode) {
    const quranDisplay = document.getElementById('quranDisplay');
    if (!quranDisplay) return false;
    const html = quranDisplay.innerHTML.trim();

    // Belum ada konten sama sekali
    if (html.length < 100 || html.includes('Memuat')) return false;

    // Cek apakah range yang sekarang dipilih = range yang terakhir di-load
    const currentKey = getCurrentQuranKey(mode);
    const lastKey    = quranDisplay.dataset.loadedKey || '';
    return currentKey === lastKey && currentKey !== '|||';
}

function toggleSection(id) {
    const section = document.getElementById(id);
    const btn = section?.previousElementSibling;
    if (!section) return;
    const isHidden = section.classList.contains('hidden');
    section.classList.toggle('hidden', !isHidden);
    btn?.classList.toggle('section-open', isHidden);
}

// ============================================================
// Badge metode & bar progress — meniru tabel di Dashboard guru.
// Sengaja memakai inline style, BUKAN class Tailwind: file ini ada
// di public/js yang tidak dipindai Tailwind, jadi class baru akan
// terbuang saat build dan warnanya hilang.
// ============================================================
function metaSantri(id) {
    return (window.__SANTRI_META__ || {})[String(id)] || null;
}

// Warna diambil dari CSS variable (didefinisikan di blok <style> InputSetoran.jsx),
// BUKAN hex inline. Alasannya: tabel hanya dirender ulang tiap 30 detik, jadi
// warna inline akan tertinggal saat pengguna menekan toggle light/dark.
function htmlBadgeMetode(meta) {
    if (!meta) return '<span class="pm-empty">–</span>';

    const tone = ['quran', 'iqro', 'aisar'].includes(meta.tone) ? meta.tone : 'quran';
    const plus = meta.hasQuran2 ? ' <span class="pm-plus">+</span> Qur\'an' : '';

    return `<span class="pm-badge" data-tone="${tone}">${meta.primary}${plus}</span>`;
}

function htmlProgress(meta) {
    if (!meta) return '<span class="pm-empty">–</span>';

    const bar = (v, c) => {
        const w = Math.max(0, Math.min(Number(v) || 0, 100));
        return `<span class="pm-prog-row">`
             + `<span class="pm-prog-track"><span class="pm-prog-fill" data-c="${c}" style="width:${w}%"></span></span>`
             + `<span class="pm-prog-val">${w}%</span></span>`;
    };

    let out = bar(meta.progress, '1');
    if (meta.hasQuran2) out += bar(meta.progress2, '2');

    return `<span class="pm-prog">${out}</span>`;
}

async function loadPresensiHariIni() {
    await loadPresensiHariIniToTarget(null);
}

async function loadPresensiHariIniToTarget(target) {
    try {
        const tanggalInput = document.getElementById('tanggalCombined');
        // FIX: nilai default tanggal hari ini tidak bisa lagi diambil dari Blade ({{ $tanggalHariIni }}).
        // Gunakan window.APP_CONFIG.tanggalHariIni jika disediakan oleh halaman (misal dari props React),
        // atau fallback ke tanggal hari ini dari browser (format YYYY-MM-DD).
        const tanggal = tanggalInput
            ? tanggalInput.value
            : (window.APP_CONFIG?.tanggalHariIni || new Date().toISOString().slice(0, 10));
        const response = await fetch(`/api/presensi/hari-ini?tanggal=${tanggal}`, {
            method: 'GET', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        const getEl = (id) => target ? target.querySelector(`#${id}`) : document.getElementById(id);
        const setText = (id, v) => { const el = getEl(id); if (el) el.textContent = v; };
        setText('statHadir', data.hadir || 0);
        setText('statSakit', data.sakit || 0);
        setText('statIzin',  data.izin  || 0);
        setText('statAlpha', data.alpha || 0);
        const sesiHeaders    = data.sesi_aktif || [];
        const tableHeaderRow = getEl('tableHeaderRow');
        if (!tableHeaderRow) return;   // panel presensi belum ada di DOM → skip diam-diam, jangan crash
        // 4 kolom tetap: No, Nama, Metode, Progress — sisanya kolom sesi
        while (tableHeaderRow.children.length > 4) tableHeaderRow.removeChild(tableHeaderRow.lastChild);
        sesiHeaders.forEach(sesi => {
            const th = document.createElement('th');
            th.className = 'pt-th-sesi';
            th.textContent = sesi;
            tableHeaderRow.appendChild(th);
        });
        const tableBody = getEl('tableBodyPresensi');
        // Ikon buku untuk badge setoran (sama dengan Dashboard) — inline SVG,
        // bukan emoji. Warna badge diatur CSS .setoran-badge di InputSetoran.jsx.
        const bookSvg = '<svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>';
        if (data.santri_list && data.santri_list.length > 0) {
            tableBody.innerHTML = data.santri_list.map((santri, index) => {
                const sesiCells = sesiHeaders.map(sesi => {
                    const p = santri.sesi_data && santri.sesi_data[sesi];
                    if (p) {
                        // Inline hex agar warna titik status TIDAK bergantung pada purge Tailwind
                        const colorMap = { Hadir:'#10b981', Sakit:'#f97316', Izin:'#f59e0b', Alpha:'#ef4444' };
                        const dotColor = colorMap[p.status] || '#6b7280';
                        const jumlahSetoran = p.jumlah_setoran || 0;
                        const waktu = p.waktu || '';
                        return `<td class="pt-cell"><div class="pt-stack"><div class="pt-line"><span class="pt-dot" style="background-color:${dotColor};" title="${p.status}${waktu ? ' - ' + waktu : ''}"></span>${jumlahSetoran > 0 ? `<span class="setoran-badge">${bookSvg}${jumlahSetoran}</span>` : ''}</div>${waktu ? `<span class="pt-waktu">${waktu}</span>` : ''}</div></td>`;
                    }
                    return `<td class="pt-cell"><div class="pt-stack"><span class="pt-dot pt-dot-empty" title="Belum presensi"></span></div></td>`;
                }).join('');
                const meta = metaSantri(santri.id);
                // Nama dibungkus <div> ber-max-width: pada table-layout auto,
                // max-width di <td> sering diabaikan browser, tapi di elemen
                // blok di dalamnya tetap dihormati.
                return `<tr class="pt-row"><td class="pt-no">${index + 1}</td><td class="pt-td"><div class="pt-name">${santri.name}</div></td><td class="pt-td pt-nowrap">${htmlBadgeMetode(meta)}</td><td class="pt-td">${htmlProgress(meta)}</td>${sesiCells}</tr>`;
            }).join('');
        } else {
            tableBody.innerHTML = `<tr><td colspan="${4 + sesiHeaders.length}" class="pt-empty">Tidak ada santri di kelas ini.</td></tr>`;
        }
    } catch (error) {
        console.error('Error loading presensi:', error);
        const el = target ? target.querySelector('#tableBodyPresensi') : document.getElementById('tableBodyPresensi');
        if (el) el.innerHTML = `<tr><td colspan="7" class="pt-empty pt-error">Gagal memuat data presensi</td></tr>`;
    }
}

document.addEventListener('DOMContentLoaded', function () {

    // FIX v3.8: Hapus loading overlay yang mungkin masih nyangkut dari submit sebelumnya
    // (kalau browser cache rendering, overlay bisa muncul sebentar dan menutupi notifikasi)
    const staleOverlay = document.getElementById('submitLoadingOverlay');
    if (staleOverlay) {
        staleOverlay.remove();
        console.log('Stale submit loading overlay removed on page load');
    }

    // FIX v3.8: Tutup drawer mobile yang mungkin masih state "open" dari sesi sebelumnya
    const drawer  = document.getElementById('mobileDrawer');
    const overlay = document.getElementById('mobileDrawerOverlay');
    if (drawer)  { drawer.classList.add('hidden');  drawer.classList.remove('open'); }
    if (overlay) { overlay.classList.add('hidden'); }
    document.body.style.overflow = '';

    // ============================================================
    // NOTIFICATION HANDLER — SINGLE SOURCE OF TRUTH
    // Cegah double-init dengan flag _notifHandlerAttached
    // ============================================================
    function setupNotificationHandler() {
        document.querySelectorAll('.success-notification, .error-notification, .warning-notification').forEach(alert => {
            if (alert._notifHandlerAttached) return;
            alert._notifHandlerAttached = true;

            alert.style.display = '';
            alert.style.opacity = '1';
            alert.style.visibility = 'visible';
            alert.style.zIndex = '300';

            // Auto-hide SEMUA notif setelah 10 detik
            alert._autoHideTimer = setTimeout(() => {
                if (!alert.isConnected) return;
                alert.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => { if (alert.isConnected) alert.remove(); }, 500);
            }, 10000);

            const closeBtn = alert.querySelector('.close-notification');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    if (alert._autoHideTimer) clearTimeout(alert._autoHideTimer);
                    alert.style.transition = 'opacity 0.3s';
                    alert.style.opacity = '0';
                    setTimeout(() => { if (alert.isConnected) alert.remove(); }, 300);
                }, { once: true });
            }
        });
    }

    setupNotificationHandler();
    setTimeout(setupNotificationHandler, 100);
    setTimeout(setupNotificationHandler, 500);

    // Handler tanggal change untuk refresh presensi
    document.getElementById('tanggalCombined')?.addEventListener('change', () => {
        const dv = document.getElementById('defaultView');
        if (dv && !dv.classList.contains('hidden')) loadPresensiHariIni();
    });

    // CSRF: hanya untuk formPresensiOnly (tidak punya handler PDF).
    // formCombined & formSetoranOnly di-handle setupNativeFormValidation()
    // di setoran-interactive.js — jangan pasang listener kedua di sana,
    // karena akan submit duluan sebelum PDF selesai dibuat.
    document.getElementById('formPresensiOnly')?.addEventListener('submit', function (e) {
        if (this.dataset.csrfReady === '1') return;
        e.preventDefault();
        refreshCsrfToken().then(() => {
            this.dataset.csrfReady = '1';
            this.submit();
        });
    });

    console.log('Notification handler v3.8 attached');
});

function toggleColorGuide(triggerBtn) {
    // FIX: cari elemen panduan warna berdasarkan tombol yang diklik (context-aware).
    // Drawer mobile pakai ID 'colorGuideContentDrawer', desktop pakai 'colorGuideContent'.
    // Tanpa fix ini, klik di drawer akan toggle elemen desktop (hidden) -> user lihat nothing happens.
    let content, icon;

    if (triggerBtn) {
        // Cari di container yang sama dengan tombol (drawer ATAU desktop)
        const container = triggerBtn.closest('[data-color-guide]')
                       || triggerBtn.parentElement;
        if (container) {
            content = container.querySelector('[id^="colorGuideContent"]');
            icon    = container.querySelector('[id^="colorGuideIcon"]');
        }
    }

    // Fallback: deteksi drawer terbuka, prioritaskan elemen drawer
    if (!content || !icon) {
        const drawer = document.getElementById('mobileDrawer');
        const drawerOpen = drawer && drawer.classList.contains('open');

        if (drawerOpen) {
            content = document.getElementById('colorGuideContentDrawer');
            icon    = document.getElementById('colorGuideIconDrawer');
        }

        // Fallback terakhir: elemen desktop
        if (!content || !icon) {
            content = document.getElementById('colorGuideContent');
            icon    = document.getElementById('colorGuideIcon');
        }
    }

    if (content && icon) {
        content.classList.toggle('hidden');
        icon.style.transform = content.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
    } else {
        console.warn('toggleColorGuide: elemen tidak ditemukan');
    }
}

function clearMarking() {
    // FIX: Bersihkan SEMUA style marking di SEMUA elemen (asli + clone drawer mobile)
    // Selector mencakup #quranDisplay (desktop) DAN #quranDisplayDrawer (mobile clone)
    document.querySelectorAll('.word-item, .quran-word').forEach(word => {
        // Reset state
        word.setAttribute('data-mark-state', '0');
        word.dataset.markState = '0';

        // Hapus class warna lama (legacy)
        word.classList.remove(
            'bg-green-400', 'bg-yellow-400', 'bg-red-400', 'text-white',
            'bg-green-200', 'bg-yellow-200', 'bg-red-200',
            'dark:bg-green-800', 'dark:bg-yellow-800', 'dark:bg-red-800',
            'border-l-4', 'border-green-500', 'border-yellow-500', 'border-red-500'
        );

        // Hapus SEMUA inline style gradient marking (yang dipasang markWord & applyMarkStyleToClone)
        word.style.removeProperty('background');
        word.style.removeProperty('background-color');
        word.style.removeProperty('border-radius');
        word.style.removeProperty('padding');
        word.style.removeProperty('box-shadow');
        word.style.removeProperty('transform');

        // Pastikan cursor & transition tetap (biar bisa di-klik lagi)
        word.style.cursor     = 'pointer';
        word.style.transition = 'all 0.25s cubic-bezier(0.4, 0, 0.2, 1)';
    });

    // Reset counter di kedua mode
    [
        ['kesalahanKelancaranCombined',    'displayKelancaranCombined'],
        ['kesalahanTajwidCombined',        'displayTajwidCombined'],
        ['kesalahanPelafalanCombined',     'displayPelafalanCombined'],
        ['kesalahanKelancaranSetoranOnly', 'displayKelancaranSetoranOnly'],
        ['kesalahanTajwidSetoranOnly',     'displayTajwidSetoranOnly'],
        ['kesalahanPelafalanSetoranOnly',  'displayPelafalanSetoranOnly'],
    ].forEach(([inputId, displayId]) => {
        const input   = document.getElementById(inputId);   if (input)   input.value = 0;
        const display = document.getElementById(displayId); if (display) display.textContent = '0';
    });

    // Reset nilai
    ['displayNilaiCombined', 'displayNilaiSetoranOnly'].forEach(id => {
        const el = document.getElementById(id); if (el) el.textContent = '100';
    });
    ['nilaiCombined', 'nilaiSetoranOnly'].forEach(id => {
        const el = document.getElementById(id); if (el) el.value = '100';
    });

    // Reset variabel global kesalahanCount (dipakai PDF)
    if (typeof window.kesalahanCount !== 'undefined') {
        window.kesalahanCount.kelancaran = 0;
        window.kesalahanCount.tajwid     = 0;
        window.kesalahanCount.pelafalan  = 0;
    }

    if (typeof updateStatistikSetoran === 'function') updateStatistikSetoran();

    console.log('Semua marking & style telah dibersihkan dari quranDisplay + quranDisplayDrawer');
    alert('Semua marking telah dihapus');
}

// ============================================================
// FIX: Reset counter, display, hidden inputs, dan global
// kesalahanCount saat user pindah ke range setoran berbeda.
// ============================================================
function resetMarkingCounterForMode(mode) {
    console.log(`Reset marking counter untuk mode: ${mode}`);

    // 1. Reset variabel global kesalahanCount di setoran-interactive.js
    if (typeof window.kesalahanCount !== 'undefined') {
        window.kesalahanCount.kelancaran = 0;
        window.kesalahanCount.tajwid     = 0;
        window.kesalahanCount.pelafalan  = 0;
    }

    // 2. Reset hidden inputs untuk kedua mode (jaga-jaga)
    ['Combined', 'SetoranOnly'].forEach(function(m) {
        const setVal  = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };
        const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };

        setVal('kesalahanKelancaran' + m, 0);
        setVal('kesalahanTajwid'     + m, 0);
        setVal('kesalahanPelafalan'  + m, 0);
        setVal('nilai'               + m, 100);

        // 3. Reset display di form kiri (desktop)
        setText('displayKelancaran' + m, '0');
        setText('displayTajwid'     + m, '0');
        setText('displayPelafalan'  + m, '0');
        setText('displayNilai'      + m, '100');
    });

    console.log('Counter direset — semua kembali ke 0, nilai 100');
}

// ============================================================
// FIX: Wrap loadQuranCombined & loadQuranSetoranOnly agar
// SKIP refresh ketika surat+ayat sama dengan yang sudah dimuat.
// Marking tetap utuh, tidak perlu fetch ulang dari API.
// ============================================================
(function() {
    function wrapLoader(fnName, mode) {
        // Tunggu sampai fungsi asli ter-define (dari mode-handler.js)
        const checkAndWrap = setInterval(() => {
            const originalFn = window[fnName];
            if (typeof originalFn !== 'function') return;
            if (originalFn._wrapped) { clearInterval(checkAndWrap); return; }

            window[fnName] = async function(...args) {
                // Cek: apakah surat+ayat sekarang = yang sudah ter-load?
                if (isQuranAlreadyLoaded(mode)) {
                    console.log(`${fnName}: skip refresh — surat+ayat sama, marking tetap`);
                    // Pastikan panel Al-Qur'an aktif (kalau user dari panel lain)
                    showPanel('quranPanel');
                    return; // Skip pemanggilan API
                }

                console.log(`${fnName}: load baru — surat+ayat berbeda -> reset counter`);

                // FIX: Reset counter & display SEBELUM load Quran baru
                // Karena range berbeda = setoran baru, jadi semua marking harus mulai dari 0
                resetMarkingCounterForMode(mode);

                // Panggil fungsi asli
                const result = await originalFn.apply(this, args);

                // Setelah loaded, simpan key range yang baru ke dataset
                setTimeout(() => {
                    const quranDisplay = document.getElementById('quranDisplay');
                    if (quranDisplay) {
                        const newKey = getCurrentQuranKey(mode);
                        quranDisplay.dataset.loadedKey = newKey;
                        console.log(`Quran loaded key saved: ${newKey}`);
                    }
                    // HAPUS: reset kedua di sini menyebabkan marking user hilang
                    // resetMarkingCounterForMode(mode);
                }, 500);

                return result;
            };
            window[fnName]._wrapped = true;
            console.log(`Wrapped: ${fnName}`);
            clearInterval(checkAndWrap);
        }, 200);

        // Stop checking setelah 10 detik
        setTimeout(() => clearInterval(checkAndWrap), 10000);
    }

    wrapLoader('loadQuranCombined',    'Combined');
    wrapLoader('loadQuranSetoranOnly', 'SetoranOnly');
})();


// ============================================================
// CUSTOM SELECT WIDGET
// ============================================================
(function () {
    'use strict';
    const ALL_SELECTS = {
        'santriSelectCombined':    { searchable: false, placeholder: '-- Pilih Santri --' },
        'sesiSelectCombined':      { searchable: false, placeholder: '-- Pilih Sesi --' },
        'kategoriSetoranCombined': { searchable: false, placeholder: '-- Pilih --' },
        'suratStartCombined':      { searchable: true,  placeholder: '-- Pilih --',    noResultText: 'Tidak ditemukan' },
        'suratEndCombined':        { searchable: true,  placeholder: '-- Otomatis --', noResultText: 'Tidak ditemukan' },
        'ayatStartCombined':       { searchable: false, placeholder: '-- Pilih Surat --',  live: true },
        'ayatEndCombined':         { searchable: false, placeholder: '-- Pilih Ayat --',   live: true },
        'santriSelectSetoranOnly': { searchable: false, placeholder: '-- Pilih Santri --' },
        'sesiSelectSetoranOnly':   { searchable: false, placeholder: '-- Pilih Sesi --' },
        'kategoriSetoranOnly':     { searchable: false, placeholder: '-- Pilih --' },
        'suratStartSetoranOnly':   { searchable: true,  placeholder: '-- Pilih --',    noResultText: 'Tidak ditemukan' },
        'suratEndSetoranOnly':     { searchable: true,  placeholder: '-- Otomatis --', noResultText: 'Tidak ditemukan' },
        'ayatStartSetoranOnly':    { searchable: false, placeholder: '-- Pilih Surat --', live: true },
        'ayatEndSetoranOnly':      { searchable: false, placeholder: '-- Pilih Ayat --',  live: true },
        'iqroHalamanStartCombined':{ searchable: false, placeholder: '-- Mulai --',   live: true },
        'iqroHalamanEndCombined':  { searchable: false, placeholder: '-- Selesai --', live: true },
        'iqroHalamanStartSetoranOnly':{ searchable: false, placeholder: '-- Mulai --',   live: true },
        'iqroHalamanEndSetoranOnly':  { searchable: false, placeholder: '-- Selesai --', live: true },
        'iqroJilidCombined':       { searchable: false, placeholder: 'Jilid 1', live: true },
        'iqroJilidSetoranOnly':    { searchable: false, placeholder: 'Jilid 1', live: true },
    };
    const PLAIN_SELECTORS = ['#formPresensiOnly select[name="santri_id"]','#formPresensiOnly select[name="sesi"]'];
    const widgets = {};
    let openWidget = null;

    function buildWidget(selectEl, cfg) {
        const id = selectEl.id || ('cs_' + Math.random().toString(36).slice(2));
        if (!selectEl.id) selectEl.id = id;
        if (widgets[id]) return;
        const isDisabled = selectEl.disabled;
        const wrap = document.createElement('div'); wrap.className = 'cs-wrap';
        selectEl.parentNode.insertBefore(wrap, selectEl); wrap.appendChild(selectEl); selectEl.style.display = 'none';
        const btn = document.createElement('button'); btn.type = 'button';
        btn.className = 'cs-btn' + (isDisabled ? ' cs-disabled' : '');
        const txt = document.createElement('span'); txt.className = 'cs-txt ph'; txt.textContent = cfg.placeholder || '-- Pilih --';
        const arr = document.createElement('span'); arr.className = 'cs-arr'; arr.textContent = '▼';
        btn.appendChild(txt); btn.appendChild(arr); wrap.insertBefore(btn, selectEl);
        const drop = document.createElement('div'); drop.className = 'cs-drop';
        let searchEl = null;
        if (cfg.searchable) {
            searchEl = document.createElement('input');
            searchEl.type = 'text'; searchEl.className = 'cs-search'; searchEl.placeholder = 'Ketik...';
            drop.appendChild(searchEl);
        }
        const list = document.createElement('ul'); list.className = 'cs-list'; drop.appendChild(list);
        const noRes = document.createElement('div'); noRes.className = 'cs-nores'; noRes.textContent = cfg.noResultText || 'Tidak ditemukan'; drop.appendChild(noRes);
        wrap.appendChild(drop);
        const w = { id, selectEl, btn, txt, drop, list, noRes, searchEl, cfg };
        widgets[id] = w; rebuildList(w);
        btn.addEventListener('click', (e) => {
            e.stopPropagation(); if (selectEl.disabled) return;
            if (openWidget && openWidget !== w) closeWidget(openWidget);
            drop.classList.contains('open') ? closeWidget(w) : openWidgetFn(w);
        });
        list.addEventListener('click', (e) => {
            const li = e.target.closest('li'); if (!li || li.classList.contains('cs-gone')) return;
            pickItem(w, li.dataset.value, li.textContent.trim());
        });
        if (searchEl) {
            searchEl.addEventListener('input', () => filterList(w, searchEl.value));
            searchEl.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') { closeWidget(w); btn.focus(); return; }
                if (e.key === 'Enter') { const vis = visibleItems(w); const hi = vis.find(l => l.classList.contains('cs-hi')) || vis[0]; if (hi) pickItem(w, hi.dataset.value, hi.textContent.trim()); return; }
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault(); const vis = visibleItems(w); const cur = vis.findIndex(l => l.classList.contains('cs-hi'));
                    vis.forEach(l => l.classList.remove('cs-hi'));
                    const nxt = Math.max(0, Math.min(e.key === 'ArrowDown' ? cur + 1 : cur - 1, vis.length - 1));
                    if (vis[nxt]) { vis[nxt].classList.add('cs-hi'); vis[nxt].scrollIntoView({ block: 'nearest' }); }
                }
            });
        }
        new MutationObserver(() => {
            btn.classList.toggle('cs-disabled', selectEl.disabled);
            if (selectEl.disabled) { closeWidget(w); txt.textContent = cfg.placeholder || '-- Pilih --'; txt.classList.add('ph'); }
        }).observe(selectEl, { attributes: true, attributeFilter: ['disabled'] });
        if (cfg.live) {
            new MutationObserver(() => { rebuildList(w); txt.textContent = cfg.placeholder || '-- Pilih --'; txt.classList.add('ph'); })
                .observe(selectEl, { childList: true, subtree: true });
        }
    }
    function rebuildList(w) {
        w.list.innerHTML = '';
        Array.from(w.selectEl.options).forEach((opt, idx) => {
            const li = document.createElement('li'); li.textContent = opt.text; li.dataset.value = opt.value;
            if (idx === 0 || opt.value === '') li.classList.add('cs-ph'); w.list.appendChild(li);
        });
    }
    function openWidgetFn(w) { if (w.selectEl.disabled) return; w.drop.classList.add('open'); w.btn.classList.add('open'); if (w.searchEl) { w.searchEl.value = ''; filterList(w, ''); setTimeout(() => w.searchEl.focus(), 50); } openWidget = w; }
    function closeWidget(w) { if (!w) return; w.drop.classList.remove('open'); w.btn.classList.remove('open'); if (openWidget === w) openWidget = null; }
    function pickItem(w, value, text) {
        w.selectEl.value = value;
        const selOpt = Array.from(w.selectEl.options).find(o => o.value === value);
        w.txt.textContent = selOpt ? selOpt.text : (text || w.cfg.placeholder || '-- Pilih --');
        w.txt.classList.toggle('ph', !value); closeWidget(w);
        w.selectEl.dispatchEvent(new Event('change', { bubbles: true }));
    }
    function filterList(w, query) {
        const q = query.trim().toLowerCase(); let anyVis = false;
        Array.from(w.list.querySelectorAll('li')).forEach(li => {
            li.classList.remove('cs-hi');
            if (li.classList.contains('cs-ph')) { li.classList.toggle('cs-gone', q.length > 0); return; }
            const match = q === '' || li.textContent.toLowerCase().includes(q);
            li.classList.toggle('cs-gone', !match); if (match) anyVis = true;
        });
        if (q.length > 0) { const first = w.list.querySelector('li:not(.cs-gone):not(.cs-ph)'); if (first) first.classList.add('cs-hi'); }
        w.noRes.style.display = (q.length > 0 && !anyVis) ? 'block' : 'none';
    }
    function visibleItems(w) { return Array.from(w.list.querySelectorAll('li:not(.cs-gone):not(.cs-ph)')); }
    function syncFromNative(id) { const w = widgets[id]; if (!w) return; const opt = w.selectEl.options[w.selectEl.selectedIndex]; if (opt) { w.txt.textContent = opt.text; w.txt.classList.toggle('ph', !opt.value); } }
    window._csSyncFromNative = syncFromNative;
    function patchAutoFill() {
        ['Combined', 'SetoranOnly'].forEach(mode => {
            const startEl = document.getElementById(`suratStart${mode}`);
            if (startEl) startEl.addEventListener('change', () => setTimeout(() => syncFromNative(`suratEnd${mode}`), 80));
        });
    }
    function init() {
        Object.entries(ALL_SELECTS).forEach(([id, cfg]) => { const el = document.getElementById(id); if (el) buildWidget(el, cfg); });
        PLAIN_SELECTORS.forEach(sel => { document.querySelectorAll(sel).forEach(el => { buildWidget(el, { searchable: false, placeholder: el.options[0]?.text || '-- Pilih --' }); }); });
        patchAutoFill();
        document.addEventListener('click', (e) => { if (openWidget && !openWidget.drop.contains(e.target) && !openWidget.btn.contains(e.target)) closeWidget(openWidget); });
        console.log('Custom Select ready');
    }
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', () => setTimeout(init, 100)) : setTimeout(init, 100);
    window._csWidgets = widgets;
})();


// ============================================================
// HAFALAN INDICATOR
// ============================================================
(function () {
    'use strict';
    let hafalanData = {}, currentSantriId = null, fetchPending = false;
    let iqroData = {}, iqroFetchPending = false;
    const TOTAL_AYAT = {
        "1. Al-Fatihah":7,"2. Al-Baqarah":286,"3. Ali 'Imran":200,"4. An-Nisa'":176,
        "5. Al-Ma'idah":120,"6. Al-An'am":165,"7. Al-A'raf":206,"8. Al-Anfal":75,
        "9. At-Taubah":129,"10. Yunus":109,"11. Hud":123,"12. Yusuf":111,
        "13. Ar-Ra'd":43,"14. Ibrahim":52,"15. Al-Hijr":99,"16. An-Nahl":128,
        "17. Al-Isra'":111,"18. Al-Kahf":110,"19. Maryam":98,"20. Taha":135,
        "21. Al-Anbiya'":112,"22. Al-Hajj":78,"23. Al-Mu'minun":118,"24. An-Nur":64,
        "25. Al-Furqan":77,"26. Asy-Syu'ara'":227,"27. An-Naml":93,"28. Al-Qasas":88,
        "29. Al-'Ankabut":69,"30. Ar-Rum":60,"31. Luqman":34,"32. As-Sajdah":30,
        "33. Al-Ahzab":73,"34. Saba'":54,"35. Fatir":45,"36. Yasin":83,
        "37. As-Saffat":182,"38. Sad":88,"39. Az-Zumar":75,"40. Ghafir":85,
        "41. Fussilat":54,"42. Asy-Syura":53,"43. Az-Zukhruf":89,"44. Ad-Dukhan":59,
        "45. Al-Jasiyah":37,"46. Al-Ahqaf":35,"47. Muhammad":38,"48. Al-Fath":29,
        "49. Al-Hujurat":18,"50. Qaf":45,"51. Az-Zariyat":60,"52. At-Tur":49,
        "53. An-Najm":62,"54. Al-Qamar":55,"55. Ar-Rahman":78,"56. Al-Waqi'ah":96,
        "57. Al-Hadid":29,"58. Al-Mujadilah":22,"59. Al-Hasyr":24,"60. Al-Mumtahanah":13,
        "61. As-Saff":14,"62. Al-Jumu'ah":11,"63. Al-Munafiqun":11,"64. At-Taghabun":18,
        "65. At-Talaq":12,"66. At-Tahrim":12,"67. Al-Mulk":30,"68. Al-Qalam":52,
        "69. Al-Haqqah":52,"70. Al-Ma'arij":44,"71. Nuh":28,"72. Al-Jinn":28,
        "73. Al-Muzzammil":20,"74. Al-Muddassir":56,"75. Al-Qiyamah":40,"76. Al-Insan":31,
        "77. Al-Mursalat":50,"78. An-Naba'":40,"79. An-Nazi'at":46,"80. 'Abasa":42,
        "81. At-Takwir":29,"82. Al-Infitar":19,"83. Al-Mutaffifin":36,"84. Al-Insyiqaq":25,
        "85. Al-Buruj":22,"86. At-Tariq":17,"87. Al-A'la":19,"88. Al-Ghasyiyah":26,
        "89. Al-Fajr":30,"90. Al-Balad":20,"91. Asy-Syams":15,"92. Al-Lail":21,
        "93. Ad-Duha":11,"94. Asy-Syarh":8,"95. At-Tin":8,"96. Al-'Alaq":19,
        "97. Al-Qadr":5,"98. Al-Bayyinah":8,"99. Az-Zalzalah":8,"100. Al-'Adiyat":11,
        "101. Al-Qari'ah":11,"102. At-Takasur":8,"103. Al-'Asr":3,"104. Al-Humazah":9,
        "105. Al-Fil":5,"106. Quraisy":4,"107. Al-Ma'un":7,"108. Al-Kausar":3,
        "109. Al-Kafirun":6,"110. An-Nasr":3,"111. Al-Masad":5,"112. Al-Ikhlas":4,
        "113. Al-Falaq":5,"114. An-Nas":6
    };
    async function fetchHafalanSantri(santriId) {
        if (!santriId) { hafalanData[null] = {}; return; }
        if (fetchPending) return;
        if (hafalanData[santriId]) { applyAllIndicators(); return; }
        fetchPending = true;
        try {
            const res = await fetch(`/guru/hafalan-santri?santri_id=${encodeURIComponent(santriId)}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            hafalanData[santriId] = await res.json() || {};
        } catch (e) { hafalanData[santriId] = {}; }
        finally { fetchPending = false; applyAllIndicators(); }
    }
    async function fetchIqroSantri(santriId) {
        if (!santriId) { iqroData[null] = {}; return; }
        if (iqroFetchPending) return;
        if (iqroData[santriId]) { applyAllIqroIndicators(); return; }
        iqroFetchPending = true;
        try {
            const res = await fetch(`/guru/iqro-santri?santri_id=${encodeURIComponent(santriId)}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            iqroData[santriId] = await res.json() || {};
        } catch (e) { iqroData[santriId] = {}; }
        finally { iqroFetchPending = false; applyAllIqroIndicators(); }
    }

    function getMetodeKeyForMode(mode) {
        const v = (document.getElementById('metode' + mode)?.value || '').toLowerCase();
        return v.includes('aisar') ? 'aisar' : 'iqro';
    }

    function isHalamanSelesai(jilid, halaman, santriId, mode) {
        const data = iqroData[santriId];
        if (!data) return false;
        const byMetode = data[getMetodeKeyForMode(mode)] || {};
        const arr = byMetode[String(parseInt(jilid))] || [];
        return arr.map(Number).includes(Number(halaman));
    }

    function getJilidForMode(mode) {
        return document.getElementById('iqroJilid' + mode)?.value || '';
    }

    function applyIqroIndicatorToWidget(widgetId, mode) {
        const w = window._csWidgets?.[widgetId]; if (!w) return;
        const santriId = currentSantriId;
        const jilid    = getJilidForMode(mode);
        const isHafalan = isHafalanMode(mode);
        console.log(`[IQRO-HI] ${widgetId}: santriId=${santriId}, jilid="${jilid}", isHafalan=${isHafalan}, iqroDataReady=${!!iqroData[santriId]}`, iqroData[santriId]);
        Array.from(w.list.querySelectorAll('li')).forEach(li => {
            li.querySelectorAll('.hi-indicator').forEach(el => el.remove());
            if (!isHafalan || !santriId || !jilid || li.classList.contains('cs-ph') || !li.dataset.value) return;
            const halaman = parseInt(li.dataset.value);
            if (!halaman || !isHalamanSelesai(jilid, halaman, santriId, mode)) return;
            const chk = document.createElement('span'); chk.className = 'hi-indicator';
            chk.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>';
            chk.style.cssText = 'display:inline-flex;align-items:center;margin-right:5px;flex-shrink:0;';
            li.style.display = 'flex'; li.style.alignItems = 'center'; li.insertBefore(chk, li.firstChild);
        });
    }

    function applyAllIqroIndicators() {
        ['Combined','SetoranOnly'].forEach(mode => {
            ['iqroHalamanStart','iqroHalamanEnd'].forEach(prefix =>
                applyIqroIndicatorToWidget(`${prefix}${mode}`, mode));
        });
    }
    function getActiveKategori(mode) { const id = mode === 'SetoranOnly' ? 'kategoriSetoranOnly' : 'kategoriSetoranCombined'; return document.getElementById(id)?.value || ''; }
    function isHafalanMode(mode) { return getActiveKategori(mode) === 'Hafalan'; }
    function getSuratStatus(suratName, santriId) {
        const data = hafalanData[santriId]; if (!data) return 'none';
        const ayatSelesai = data[suratName]; if (!ayatSelesai || ayatSelesai.length === 0) return 'none';
        const totalAyat = TOTAL_AYAT[suratName]; if (!totalAyat) return 'none';
        return [...new Set(ayatSelesai.map(Number))].length >= totalAyat ? 'full' : 'partial';
    }
    function isAyatSelesai(suratName, ayatNo, santriId) { const data = hafalanData[santriId]; if (!data) return false; return (data[suratName] || []).map(Number).includes(Number(ayatNo)); }
    function applyIndicatorToWidget(widgetId, mode) {
        const w = window._csWidgets?.[widgetId]; if (!w) return;
        const santriId = currentSantriId, isHafalan = isHafalanMode(mode);
        Array.from(w.list.querySelectorAll('li')).forEach(li => {
            li.querySelectorAll('.hi-indicator').forEach(el => el.remove());
            if (!isHafalan || !santriId || li.classList.contains('cs-ph') || !li.dataset.value) return;
            if (widgetId.startsWith('suratStart') || widgetId.startsWith('suratEnd')) {
                const status = getSuratStatus(li.dataset.value, santriId); if (status === 'none') return;
                const dot = document.createElement('span'); dot.className = 'hi-indicator';
                dot.style.cssText = `display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:5px;flex-shrink:0;vertical-align:middle;background:${status === 'full' ? '#22c55e' : '#f97316'};`;
                li.style.display = 'flex'; li.style.alignItems = 'center'; li.insertBefore(dot, li.firstChild);
            }
            if (widgetId.startsWith('ayatStart') || widgetId.startsWith('ayatEnd')) {
                const suratSelectId = widgetId.startsWith('ayatStart') ? widgetId.replace('ayatStart','suratStart') : widgetId.replace('ayatEnd','suratEnd');
                const suratName = document.getElementById(suratSelectId)?.value;
                const ayatNo = parseInt(li.dataset.value);
                if (!suratName || !ayatNo || !isAyatSelesai(suratName, ayatNo, santriId)) return;
                const chk = document.createElement('span'); chk.className = 'hi-indicator';
                chk.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>';
                chk.style.cssText = 'display:inline-flex;align-items:center;margin-right:5px;flex-shrink:0;';
                li.style.display = 'flex'; li.style.alignItems = 'center'; li.insertBefore(chk, li.firstChild);
            }
        });
    }
    function applyAllIndicators() {
        ['Combined','SetoranOnly'].forEach(mode => {
            ['suratStart','suratEnd','ayatStart','ayatEnd'].forEach(prefix => applyIndicatorToWidget(`${prefix}${mode}`, mode));
        });
    }
    function init() {
       ['santriSelectCombined','santriSelectSetoranOnly'].forEach(id => {
            document.getElementById(id)?.addEventListener('change', function() {
                if (this.value === currentSantriId) return;
                currentSantriId = this.value || null; applyAllIndicators(); applyAllIqroIndicators();
                if (currentSantriId) { fetchHafalanSantri(currentSantriId); fetchIqroSantri(currentSantriId); }
            });
        });
        ['kategoriSetoranCombined','kategoriSetoranOnly'].forEach(id => {
            const mode = id === 'kategoriSetoranOnly' ? 'SetoranOnly' : 'Combined';
            document.getElementById(id)?.addEventListener('change', function() {
                setTimeout(() => {
                    const hafalan = isHafalanMode(mode);
                    console.log(`[HI] kategori ${mode} -> value="${this.value}", isHafalan=${hafalan}`);
                    ['suratStart','suratEnd','ayatStart','ayatEnd'].forEach(prefix => {
                        const w = window._csWidgets?.[`${prefix}${mode}`];
                        if (!w) return;
                        if (!hafalan) {
                            w.list.querySelectorAll('.hi-indicator').forEach(el => el.remove());
                        } else {
                            applyIndicatorToWidget(`${prefix}${mode}`, mode);
                        }
                    });
                    // Refresh indikator Iqro juga — applyIqroIndicatorToWidget sudah cek isHafalan sendiri
                    ['iqroHalamanStart','iqroHalamanEnd'].forEach(prefix =>
                        applyIqroIndicatorToWidget(`${prefix}${mode}`, mode));
                }, 50);
            });
        });

        // Metode berubah (Iqro <-> Aisar) -> refresh centang sesuai metode aktif
        ['metodeCombined', 'metodeSetoranOnly'].forEach(id => {
            document.getElementById(id)?.addEventListener('change',
                () => setTimeout(() => applyAllIqroIndicators(), 100));
        });

        // Iqro: jilid berubah -> centang halaman jilid itu; dropdown halaman
        // diisi dinamis (live) -> pantau perubahan opsi lalu re-apply
        ['Combined','SetoranOnly'].forEach(mode => {
            document.getElementById('iqroJilid' + mode)?.addEventListener('change',
                () => setTimeout(() => applyAllIqroIndicators(), 200));
            ['iqroHalamanStart','iqroHalamanEnd'].forEach(prefix => {
                const el = document.getElementById(`${prefix}${mode}`);
                if (el) new MutationObserver(() => applyIqroIndicatorToWidget(`${prefix}${mode}`, mode))
                            .observe(el, { childList: true, subtree: true });
            });
        });
        ['Combined','SetoranOnly'].forEach(mode => {
            ['suratStart','suratEnd'].forEach(prefix => {
                document.getElementById(`${prefix}${mode}`)?.addEventListener('change', () => setTimeout(() => { applyIndicatorToWidget(`ayatStart${mode}`, mode); applyIndicatorToWidget(`ayatEnd${mode}`, mode); }, 150));
            });
            ['ayatStart','ayatEnd'].forEach(prefix => {
                const el = document.getElementById(`${prefix}${mode}`);
                // Ayat di-isi dinamis (auto-fill) -> pantau perubahan opsi lalu re-apply
                if (el) new MutationObserver(() => applyIndicatorToWidget(`${prefix}${mode}`, mode))
                            .observe(el, { childList: true, subtree: true });
            });
        });
    }
    document.readyState === 'loading'
        ? document.addEventListener('DOMContentLoaded', () => setTimeout(init, 600))
        : setTimeout(init, 600);
})();


// ============================================================
// AUTO-SET METODE & JILID DARI TARGET SANTRI
// ============================================================
(function () {
    'use strict';

    // Tampilkan/sembunyikan blok setelah-santri (Data Setoran, Audio, Submit) di form Combined

    function toggleCombinedSections(show) {
        document.querySelectorAll('#formCombined .combined-after-santri').forEach(el => {
            el.classList.toggle('hidden', !show);
        });
    }

    // Tampilkan/sembunyikan blok setelah-santri di form Setoran Tambahan
    function toggleSetoranSections(show) {
        document.querySelectorAll('#formSetoranOnly .setoran-after-santri').forEach(el => {
            el.classList.toggle('hidden', !show);
        });
    }

     function setJilidForMode(mode, targetJilid) {
        const metodeEl = document.getElementById('metode' + mode);
        const isJilid  = /iqro|aisar|ummi/i.test(metodeEl?.value || '');

        if (isJilid && targetJilid) {
            const jilidEl = document.getElementById('iqroJilid' + mode);
            if (jilidEl) {
                if (![...jilidEl.options].some(o => o.value === String(targetJilid))) {
                    const o = document.createElement('option');
                    o.value = String(targetJilid);
                    o.textContent = 'Jilid ' + targetJilid;
                    jilidEl.appendChild(o);
                }
                jilidEl.value = String(targetJilid);
                jilidEl.dispatchEvent(new Event('change', { bubbles: true }));
                if (typeof window._csSyncFromNative === 'function') window._csSyncFromNative('iqroJilid' + mode);
            }
        }

        if (typeof window.toggleMetode === 'function') window.toggleMetode(mode);
    }

    function applyTargetFor(mode) {
        const santriId = mode === 'SetoranOnly' ? 'santriSelectSetoranOnly' : 'santriSelectCombined';
        const santriEl = document.getElementById(santriId);
        if (!santriEl) return;

        const opt = santriEl.options[santriEl.selectedIndex];

        if (mode === 'Combined')    toggleCombinedSections(!!(opt && opt.value));
        if (mode === 'SetoranOnly') toggleSetoranSections(!!(opt && opt.value));

        if (!opt || !opt.value) return;

        // Pemilihan metode (kunci / 2 opsi) ditangani applyMetodeForSantri() di InputSetoran.jsx.
        // Di sini cukup set jilid mengikuti metode yang sedang aktif.
        setJilidForMode(mode, opt.dataset.targetJilid || '');
    }

    function init() {
        ['Combined', 'SetoranOnly'].forEach(mode => {
            const santriId = mode === 'SetoranOnly' ? 'santriSelectSetoranOnly' : 'santriSelectCombined';
            document.getElementById(santriId)?.addEventListener('change', () => applyTargetFor(mode));

            // Guru ganti metode (santri 2 metode: Iqro/Aisar <-> Al-Qur'an)
            document.getElementById('metode' + mode)?.addEventListener('change', () => {
                const sEl = document.getElementById(santriId);
                const o = sEl?.options[sEl.selectedIndex];
                setJilidForMode(mode, o?.dataset.targetJilid || '');
            });
        });
        console.log('Auto-set metode & jilid (target santri) ready');
    }

    document.readyState === 'loading'
        ? document.addEventListener('DOMContentLoaded', () => setTimeout(init, 650))
        : setTimeout(init, 650);
})();

// ============================================================
// RIWAYAT SETORAN SANTRI
// Panel collapsible di form Combined & SetoranOnly.
// Data diambil dari /guru/riwayat-setoran-santri dan di-cache
// per santri_id supaya ganti filter tidak memicu fetch ulang.
// ============================================================
(function () {
    'use strict';

    const cache   = {};   // santriId -> array
    const pending = {};   // santriId -> true saat fetch berjalan
    const state   = {
        Combined:    { santriId: null, filter: '' },
        SetoranOnly: { santriId: null, filter: '' },
    };

    function esc(s) {
        return String(s ?? '').replace(/[&<>"]/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
        });
    }

    function toneNilai(n) {
        const v = Number(n);
        if (!Number.isFinite(v)) return 'mid';
        return v >= 80 ? 'hi' : v >= 60 ? 'mid' : 'lo';
    }

    function render(mode) {
        const box = document.getElementById('riwayatList' + mode);
        if (!box) return;

        const st = state[mode];

        if (!st.santriId) {
            box.innerHTML = '<p class="rs-empty">Pilih santri terlebih dahulu.</p>';
            return;
        }
        if (pending[st.santriId]) {
            box.innerHTML = '<p class="rs-empty">Memuat riwayat…</p>';
            return;
        }

        const rows = cache[st.santriId];
        if (!rows) {
            box.innerHTML = '<p class="rs-empty">Riwayat belum tersedia.</p>';
            return;
        }

        const list = st.filter ? rows.filter(r => r.kategori === st.filter) : rows;
        if (!list.length) {
            box.innerHTML = '<p class="rs-empty">Belum ada setoran'
                          + (st.filter ? ' ' + st.filter : '') + '.</p>';
            return;
        }

        box.innerHTML = list.map(function (r) {
            return '<div class="rs-item">'
                 +   '<div class="rs-item-top">'
                 +     '<span class="rs-kat" data-k="' + esc(r.kategori) + '">' + esc(r.kategori) + '</span>'
                 +     '<span class="rs-tgl">' + esc(r.tanggal) + '</span>'
                 +   '</div>'
                 +   '<div class="rs-materi">' + esc(r.materi) + '</div>'
                 +   '<div class="rs-item-bot">'
                 +     '<span class="rs-metode">' + esc(r.metode)
                 +       (r.guru ? ' · ' + esc(r.guru) : '') + '</span>'
                 +     '<span class="rs-nilai" data-n="' + toneNilai(r.nilai) + '">'
                 +       (r.nilai ?? '-') + '</span>'
                 +   '</div>'
                 + '</div>';
        }).join('');
    }

    async function load(santriId) {
        if (!santriId || cache[santriId] || pending[santriId]) return;
        pending[santriId] = true;
        ['Combined', 'SetoranOnly'].forEach(function (m) {
            if (state[m].santriId === santriId) render(m);
        });
        try {
            const res = await fetch(
                '/guru/riwayat-setoran-santri?santri_id=' + encodeURIComponent(santriId),
                { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }
            );
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            cache[santriId] = Array.isArray(json.data) ? json.data : [];
        } catch (e) {
            console.warn('Gagal memuat riwayat setoran:', e.message);
            cache[santriId] = [];
        } finally {
            delete pending[santriId];
            ['Combined', 'SetoranOnly'].forEach(function (m) {
                if (state[m].santriId === santriId) render(m);
            });
        }
    }

    window.toggleRiwayatSetoran = function (mode) {
        const body = document.getElementById('riwayatBody' + mode);
        const icon = document.getElementById('riwayatIcon' + mode);
        if (!body) return;
        const willOpen = body.classList.contains('hidden');
        body.classList.toggle('hidden', !willOpen);
        if (icon) icon.style.transform = willOpen ? 'rotate(180deg)' : 'rotate(0deg)';
        if (willOpen) render(mode);
    };

    function init() {
        ['Combined', 'SetoranOnly'].forEach(function (mode) {
            const santriId = mode === 'SetoranOnly'
                ? 'santriSelectSetoranOnly'
                : 'santriSelectCombined';

            document.getElementById(santriId)?.addEventListener('change', function () {
                state[mode].santriId = this.value || null;
                state[mode].filter   = '';

                const filterWrap = document.getElementById('riwayatFilter' + mode);
                filterWrap?.querySelectorAll('.rs-chip').forEach(function (c) {
                    c.classList.toggle('is-active', c.dataset.f === '');
                });

                render(mode);
                if (state[mode].santriId) load(state[mode].santriId);
            });

            document.getElementById('riwayatFilter' + mode)
                ?.addEventListener('click', function (e) {
                    const chip = e.target.closest('.rs-chip');
                    if (!chip) return;
                    state[mode].filter = chip.dataset.f || '';
                    this.querySelectorAll('.rs-chip').forEach(function (c) {
                        c.classList.toggle('is-active', c === chip);
                    });
                    render(mode);
                });

            render(mode);
        });
        console.log('Riwayat setoran santri ready');
    }

    document.readyState === 'loading'
        ? document.addEventListener('DOMContentLoaded', () => setTimeout(init, 700))
        : setTimeout(init, 700);
})();