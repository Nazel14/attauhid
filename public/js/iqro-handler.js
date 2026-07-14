/**
 * IQRO HANDLER — integrasi metode Iqro (1 HALAMAN tampil + Next/Prev).
 * ------------------------------------------------------------------
 * v4.5 Ukuran font layar diturunkan & dijadikan konstanta:
 *   - IQRO_SCREEN_FONT_SIZE (default 34, dari 40) dan IQRO_COMBO_FONT_SIZE
 *     (default 38, dari 44) di atas file → tinggal ubah angka untuk
 *     perbesar/perkecil. PDF tetap pakai IQRO_PDF_FONT_SIZE (50) + auto-fit.
 *
 * v4.4 FIX "font kekecilan di layar":
 *   - Auto-fit DIBUANG dari layar. Penyebab font mengecil adalah salah ukur
 *     lebar saat panel transisi / drawer tersembunyi / font belum termuat.
 *   - Layar: font SELALU penuh (40px), satu baris lurus. Bila layar sempit &
 *     pasangan terlalu lebar → kotak bisa digeser ke samping (overflow-x:auto),
 *     BUKAN mengecil, BUKAN menumpuk.
 *   - Auto-fit HANYA dipakai untuk PDF (lebar tetap 760px → ukur pasti benar),
 *     sehingga PDF tetap rapi satu baris.
 *
 * v4.3 FIX auto-fit terlalu kecil:
 *   - Fit pakai rasio sekali (min 50%), BUKAN loop ×0.95 yang bisa lari ke
 *     ~9px saat lebar sempat terukur kecil (panel transisi).
 *   - Lewati fit bila lebar < 200px (belum ter-layout / masih transisi).
 *   - ResizeObserver pada area tampilan → font dihitung ulang dgn lebar
 *     yang benar begitu panel/drawer selesai membuka.
 *
 * v4.2 LAYOUT FIX (1 BARIS LURUS / auto-fit):
 *   - Baris TIDAK lagi menumpuk: flex-wrap dibuang, tiap baris = satu garis
 *     lurus (nowrap). Frasa kanan & kiri tetap sebaris.
 *   - AUTO-FIT per baris: baris yang muat tetap font penuh (layar 40px /
 *     PDF 50px); HANYA baris yang kepanjangan yang fontnya dikecilkan
 *     otomatis secukupnya supaya pas satu baris (menggantikan tumpukan).
 *   - Fit dipanggil saat tampil halaman, navigasi, setelah font Amiri load,
 *     saat resize, dan saat capture PDF (clone).
 *   - Sel pakai min-height + height:auto + white-space:nowrap.
 *
 * v4.1: white-space:nowrap + min-height (frasa tidak patah di tengah).
 *
 * v4.0 THEME AWARE:
 *   - Warna sel/judul/border/navbar pakai CSS var (--q-fg, --q-line,
 *     --q-box-border, --q-nav-*) DENGAN fallback putih. Di layar bisa
 *     terang/gelap; saat PDF (wrapper .iqro-cap) var dipaksa gelap → PDF
 *     tetap putih di latar #3E3D3A apa pun tema layar.
 *   - Rename label statistik (Mushaf<->Iqro) tidak lagi bergantung pada
 *     class .bg-gray-900 (blok kini theme-aware).
 *
 * Butuh: iqro-data.js dimuat lebih dulu (window.IQRO_DATA).
 * ------------------------------------------------------------------
 */
(function () {
    'use strict';

    // ==== UKURAN FONT LAYAR (ubah di sini untuk perbesar/perkecil) ====
    const IQRO_SCREEN_FONT_SIZE = 40;   // sebelumnya 40 — font sel di layar
    const IQRO_COMBO_FONT_SIZE  = 44;   // sebelumnya 44 — font sel combo di layar
    // ==================================================================

    const IQRO_PDF_FONT_SIZE    = 50;
    const IQRO_PDF_CELL_HEIGHT  = 150;
    const IQRO_PDF_CELL_PADBOT  = 18;
    const IQRO_PDF_LINE_HEIGHT  = 1.3;
    const IQRO_PDF_RENDER_WIDTH = 900;
    const IQRO_PDF_VALIGN       = 'center';

    function fillHalamanDropdown(mode) {
        const jilidEl = document.getElementById('iqroJilid' + mode);
        const startEl = document.getElementById('iqroHalamanStart' + mode);
        const endEl   = document.getElementById('iqroHalamanEnd' + mode);
        if (!jilidEl || !startEl || !endEl) return;

        const jilid = jilidEl.value;
        const src   = getJilidSource(metodeForMode(mode));
        const total = src.jumlah[jilid] || 0;

        const optsStart = ['<option value="">-- Mulai --</option>'];
        const optsEnd   = ['<option value="">-- Selesai --</option>'];
        for (let i = 1; i <= total; i++) {
            optsStart.push('<option value="' + i + '">Halaman ' + i + '</option>');
            optsEnd.push('<option value="' + i + '">Halaman ' + i + '</option>');
        }
        startEl.innerHTML = optsStart.join('');
        endEl.innerHTML   = optsEnd.join('');

       if (typeof window._csSyncFromNative === 'function') {
            window._csSyncFromNative('iqroHalamanStart' + mode);
            window._csSyncFromNative('iqroHalamanEnd' + mode);
        }
    }

    function refillHalamanEnd(mode) {
        const jilidEl = document.getElementById('iqroJilid' + mode);
        const startEl = document.getElementById('iqroHalamanStart' + mode);
        const endEl   = document.getElementById('iqroHalamanEnd' + mode);
        if (!jilidEl || !startEl || !endEl) return;

        const jilid = jilidEl.value;
        const src   = getJilidSource(metodeForMode(mode));
        const total = src.jumlah[jilid] || 0;
        const start = parseInt(startEl.value, 10);
        const from = isNaN(start) ? 1 : start;

        const prevEnd = parseInt(endEl.value, 10);
        const opts = ['<option value="">-- Selesai --</option>'];
        for (let i = from; i <= total; i++) {
            opts.push('<option value="' + i + '">Halaman ' + i + '</option>');
        }
        endEl.innerHTML = opts.join('');

        if (!isNaN(prevEnd) && prevEnd >= from && prevEnd <= total) {
            endEl.value = String(prevEnd);
        } else if (!isNaN(start)) {
            endEl.value = String(start);
        }

        if (typeof window._csSyncFromNative === 'function') {
            window._csSyncFromNative('iqroHalamanEnd' + mode);
        }
    }

    function toggleMetode(mode) {
        const metodeEl = document.getElementById('metode' + mode);
        if (!metodeEl) return;
        const isIqro = isJilidMetode(metodeEl.value);

        const rangeBlock = document.getElementById('rangeAyatBlock' + mode);
        const iqroBlock  = document.getElementById('iqroBlock' + mode);
        if (rangeBlock) rangeBlock.classList.toggle('hidden', isIqro);
        if (iqroBlock)  iqroBlock.classList.toggle('hidden', !isIqro);

        ['suratStart', 'ayatStart', 'suratEnd', 'ayatEnd'].forEach(prefix => {
            const el = document.getElementById(prefix + mode);
            if (el) {
                if (isIqro) {
                    el.dataset._wasRequired = el.required ? '1' : '0';
                    el.required = false;
                } else if (el.dataset._wasRequired === '1') {
                    el.required = true;
                }
            }
        });

        const disp = document.getElementById('quranDisplay');
        if (disp && !isIqro) {
            disp.style.setProperty('max-height', '380px', 'important');
            disp.style.setProperty('overflow-y', 'auto', 'important');
            if (typeof resetStatistikLabels === 'function') resetStatistikLabels();
        }

        window.currentFormContext = mode;
        window.currentMetode = metodeEl.value;
        if (isIqro) fillHalamanDropdown(mode);
    }

    function buildIqroPageHtml(data, pageIdx) {
        const isWaqaf = data.pola === 'waqaf';
        const titik   = (data.titik !== undefined) ? data.titik : '…';

        const wordSpan = (txt) =>
            '<span class="word-item" data-word="' + txt + '" data-mark-state="0" ' +
            'onclick="markWord(this)" ' +
            'style="display:inline-flex;align-items:center;justify-content:center;white-space:nowrap;' +
            'cursor:pointer;padding:6px 12px;border-radius:8px;min-height:100px;height:auto;line-height:1.5;text-align:center;box-sizing:border-box;' +
            "font-family:'Amiri Quran','Amiri',serif;font-size:" + IQRO_SCREEN_FONT_SIZE + "px;color:var(--q-fg,#fff);" +
            'transition:all 0.25s cubic-bezier(0.4,0,0.2,1);">' + txt + '</span>';

        const bulatSpan = () =>
            '<span class="iqro-bulat" aria-label="isi waqaf" style="display:inline-block;width:34px;height:34px;' +
                'border:2px solid var(--q-fg,#fff);border-radius:50%;opacity:.7;flex:0 0 auto;"></span>';

        const cell = (txt, withBulat) => {
            // Penanda bulat tunggal: tulis "◯" / "○" sebagai sel di data →
            // dirender lingkaran ber-border yang SAMA dgn bulat waqaf (bukan glyph kecil).
            if (txt === '◯' || txt === '○') {
                return '<span class="iqro-waqaf-cell" style="display:inline-flex;align-items:center;justify-content:center;">' +
                           bulatSpan() +
                       '</span>';
            }
            if (!withBulat) return wordSpan(txt);
            const decoHtml = titik
                ? '<span class="iqro-deco" style="font-family:\'Amiri\',serif;font-size:30px;line-height:1;' +
                      'color:var(--q-fg,#fff);opacity:.55;letter-spacing:2px;">' + titik + '</span>'
                : '';
            return '<span class="iqro-waqaf-cell" style="display:inline-flex;align-items:center;gap:12px;">' +
                       wordSpan(txt) + decoHtml + bulatSpan() +
                   '</span>';
        };

        const renderRow = (rowCells, rowIdx) => {
        const withBulat = isWaqaf ||
            (Array.isArray(data.bulatBaris) && data.bulatBaris.indexOf(rowIdx) !== -1);
        const grouped = rowCells.length >= 6 && rowCells.length % 3 === 0;
        let inner;
        if (grouped) {
            const groups = [];
            for (let i = 0; i < rowCells.length; i += 3) groups.push(rowCells.slice(i, i + 3));
            inner = groups.map(g =>
                '<div class="iqro-row-inner" style="flex:1;display:flex;justify-content:space-evenly;align-items:center;white-space:nowrap;">' + g.map(c => cell(c, withBulat)).join('') + '</div>'
            ).join('');
        } else {
            inner = '<div class="iqro-row-inner" style="flex:1;display:flex;justify-content:space-evenly;align-items:center;white-space:nowrap;">' + rowCells.map(c => cell(c, withBulat)).join('') + '</div>';
        }

            
            let labelHtml = '';
            const lbl = (data.labelBaris && data.labelBaris[rowIdx]) || null;
            if (lbl) {
                labelHtml =
                    '<div style="flex:0 0 auto;display:flex;align-items:center;gap:10px;padding:0 26px 0 10px;">' +
                        '<span style="font-family:\'Amiri Quran\',\'Amiri\',serif;font-size:34px;font-weight:700;color:var(--q-fg,#fff);line-height:1.5;">' + lbl + '</span>' +
                        '<span style="color:var(--q-fg,#fff);font-size:24px;line-height:1;">&#8592;</span>' +
                    '</div>';
            }

            return '' +
                '<div class="iqro-row-scroll" style="display:flex;direction:rtl;align-items:center;border-top:1px solid var(--q-line,rgba(255,255,255,0.3));padding:6px 0;overflow-x:auto;overflow-y:hidden;scrollbar-width:thin;">' +
                    labelHtml + inner +
                '</div>';
        };

        const renderJudulRow = (cells) => {
            const inner = cells.map(t =>
                t === '|'
                    ? '<span style="display:inline-block;width:28px;"></span>'
                    : '<span>' + t + '</span>'
            ).join('');
            return '<div style="display:flex;justify-content:center;align-items:center;gap:14px;direction:rtl;padding:18px 10px;line-height:1.6;' +
                   "font-family:'Amiri Quran','Amiri',serif;font-size:42px;font-weight:700;color:var(--q-fg,#fff);flex-wrap:wrap;\">" + inner + '</div>';
        };

        const renderJudul = (judul) => {
            if (!judul || !judul.length) return '';
            const rows = Array.isArray(judul[0]) ? judul : [judul];
            return rows.map((row, i) =>
                renderJudulRow(row) +
                (i < rows.length - 1
                    ? '<div style="border-top:1px solid var(--q-line,rgba(255,255,255,0.3));"></div>'
                    : '')
            ).join('');
        };

        const rowsHtml = data.baris.map(renderRow).join('');

        const comboCell = (txt) =>
            '<span class="word-item iqro-combo-cell" data-word="' + txt + '" data-mark-state="0" ' +
            'onclick="markWord(this)" ' +
            'style="display:inline-flex;align-items:center;justify-content:center;' +
            'cursor:pointer;padding:0 12px;border-radius:8px;height:auto;min-height:100px;line-height:1.5;' +
            "font-family:'Amiri Quran','Amiri',serif;font-size:" + IQRO_COMBO_FONT_SIZE + "px;color:var(--q-fg,#fff);" +
            'transition:all 0.25s cubic-bezier(0.4,0,0.2,1);">' + txt + '</span>';

        let comboHtml = '';
        if (data.combo && data.combo.length) {
            comboHtml = '' +
                '<div style="display:flex;flex-wrap:wrap;direction:rtl;justify-content:space-evenly;align-items:center;gap:8px 0;border-top:1px solid var(--q-line,rgba(255,255,255,0.3));padding:8px 6px;">' +
                    data.combo.map(comboCell).join('') +
                '</div>';
        }

        const displayStyle = pageIdx === 0 ? '' : 'display:none;';
        return '' +
            '<div class="iqro-page" data-idx="' + pageIdx + '" data-halaman="' + data.halaman + '" style="margin-bottom:8px;' + displayStyle + '">' +
                '<div style="text-align:center;margin-bottom:10px;color:var(--q-fg,#fff);">' +
                    '<p style="font-size:14px;opacity:.65;font-family:system-ui;">' +
                    // Aisar dirender oleh aisar-handler.js; baris ini fallback bila handler gagal load
                    (window.currentMetode === 'Aisar' ? 'Aisar ' : 'اقرأ ') + data.jilid +
                    ' &nbsp;·&nbsp; Halaman ' + data.halaman + '</p>' +
                    (data.instruksi ? '<p style="font-size:18px;font-family:\'Amiri\',serif;direction:rtl;margin-top:6px;opacity:.85;">' + data.instruksi + '</p>' : '') +
                '</div>' +
                '<div style="border:1.5px solid var(--q-box-border,rgba(255,255,255,0.85));margin:8px auto 16px auto;max-width:760px;overflow-x:auto;overflow-y:visible;border-radius:4px;">' +
                    renderJudul(data.judul) +
                    rowsHtml +
                    comboHtml +
                '</div>' +
            '</div>';
    }

    // ---- AUTO-FIT: kecilkan font baris yang kepanjangan; bila masih tak muat
    //      pada minFactor, izinkan teks membungkus (wrap) supaya TIDAK terpotong.
    function fitInnerToWidth(inner, minFactor, allowWrap) {
        minFactor = (typeof minFactor === 'number') ? minFactor : 0.5;
        const cells = Array.from(inner.querySelectorAll('.word-item'));
        if (!cells.length) return;
        // reset dulu → baris yang muat kembali ke font penuh & nowrap
        cells.forEach(c => {
            c.style.removeProperty('font-size');
            c.style.removeProperty('white-space');
            c.style.removeProperty('max-width');
            c.style.removeProperty('text-align');
        });

        const avail = inner.clientWidth;
        if (avail < 200) return;

        let needed = inner.scrollWidth;
        if (needed <= avail + 1) return; // sudah muat → font penuh

        // rasio pertama (boleh sampai minFactor)
        let factor = Math.max(minFactor, (avail - 4) / needed);
        cells.forEach(c => {
            const fs = parseFloat(getComputedStyle(c).fontSize) || 40;
            c.style.setProperty('font-size', (fs * factor) + 'px', 'important');
        });

        // koreksi kedua bila masih sedikit lebih
        if (inner.scrollWidth > avail + 1) {
            const f2 = Math.max(minFactor, (avail - 4) / inner.scrollWidth);
            cells.forEach(c => {
                const fs = parseFloat(getComputedStyle(c).fontSize) || 40;
                c.style.setProperty('font-size', (fs * f2) + 'px', 'important');
            });
        }

        // FALLBACK: sudah di font minimum tapi MASIH lebih lebar (kalimat jilid
        // tinggi yang sangat panjang) → izinkan teks membungkus di dalam frasa,
        // dengan batas lebar = lebar baris tersedia. Tidak ada huruf terpotong.
        if (allowWrap && inner.scrollWidth > avail + 1) {
            cells.forEach(c => {
                c.style.setProperty('white-space', 'normal', 'important');
                c.style.setProperty('max-width', (avail - 8) + 'px', 'important');
                c.style.setProperty('line-height', '1.5', 'important');
                c.style.setProperty('text-align', 'center', 'important');
                c.style.setProperty('word-break', 'normal', 'important');
            });
        }
    }

    function fitPageRowsScreen(container) {
        if (!container) return;
        // Auto-fit layar DIMATIKAN. Sebelumnya font-size DI-REMOVE, padahal 44px
        // dipasang sebagai INLINE STYLE di tiap .word-item → removeProperty membuat
        // font jatuh ke nilai warisan (~16px) = huruf mengecil.
        // FIX: set ulang ke ukuran penuh, jangan dihapus.
        container.querySelectorAll('.iqro-row-inner .word-item').forEach(c => {
            c.style.setProperty('font-size', IQRO_SCREEN_FONT_SIZE + 'px', 'important');
        });
    }

    function buildNavBar(pageNumbers) {
        if (pageNumbers.length <= 1) return '';
        const btnStyle = 'background:var(--q-nav-btn,#374151);color:var(--q-nav-btn-fg,#e5e7eb);border:none;border-radius:6px;' +
                         'padding:5px 10px;font-size:11px;cursor:pointer;white-space:nowrap;font-weight:600;line-height:1.2;';
        return '' +
            '<div class="iqro-nav" data-idx="0" data-total="' + pageNumbers.length + '" data-html2canvas-ignore="true"' +
            ' style="position:sticky;top:0;z-index:5;display:flex;align-items:center;justify-content:space-between;' +
            'gap:6px;background:var(--q-nav-bg,rgba(17,24,39,0.96));padding:5px 8px;border-radius:6px;margin-bottom:10px;">' +
                '<button type="button" class="iqro-prev" onclick="iqroNavGo(this,-1)" style="' + btnStyle + 'opacity:0.45;cursor:not-allowed;">←</button>' +
                '<span class="iqro-nav-label" style="font-size:11px;color:var(--q-nav-label,#cbd5e1);font-weight:700;white-space:nowrap;">' +
                    'Hal ' + pageNumbers[0] + ' (1/' + pageNumbers.length + ')' +
                '</span>' +
                '<button type="button" class="iqro-next" onclick="iqroNavGo(this,1)" style="' + btnStyle + '">→</button>' +
            '</div>';
    }

    function iqroShowPageIn(container, idx) {
        if (!container) return;
        const bar   = container.querySelector('.iqro-nav');
        const pages = Array.from(container.querySelectorAll('.iqro-page'));
        if (!pages.length) return;

        const total = pages.length;
        idx = Math.max(0, Math.min(idx, total - 1));

        pages.forEach((p, i) => { p.style.display = (i === idx) ? '' : 'none'; });

        if (bar) {
            bar.dataset.idx = idx;
            const label = bar.querySelector('.iqro-nav-label');
            const hal   = pages[idx].getAttribute('data-halaman') || (idx + 1);
            if (label) label.innerHTML = 'Hal ' + hal + ' (' + (idx + 1) + '/' + total + ')';

            const prevBtn = bar.querySelector('.iqro-prev');
            const nextBtn = bar.querySelector('.iqro-next');
            if (prevBtn) {
                const dis = idx === 0;
                prevBtn.style.opacity = dis ? '0.45' : '1';
                prevBtn.style.cursor  = dis ? 'not-allowed' : 'pointer';
            }
            if (nextBtn) {
                const dis = idx === total - 1;
                nextBtn.style.opacity = dis ? '0.45' : '1';
                nextBtn.style.cursor  = dis ? 'not-allowed' : 'pointer';
            }
        }

        if (typeof container.scrollTo === 'function') {
            try { container.scrollTo({ top: 0, behavior: 'smooth' }); } catch (e) { container.scrollTop = 0; }
        }

        // Re-fit baris halaman yang baru ditampilkan (navigasi Prev/Next)
        setTimeout(() => {
            if (typeof fitPageRowsScreen === 'function') fitPageRowsScreen(container);
        }, 100);
    }
    // ---- Fit SEMUA baris di satu halaman (dipakai saat capture PDF).
    //      Meneruskan ke fitInnerToWidth per .iqro-row-inner: baris yang muat
    //      tetap font penuh, baris kepanjangan dikecilkan sampai minFactor,
    //      lalu boleh wrap kalau masih lebih (allowWrap).
    function fitPageRows(pageEl, minFactor, allowWrap) {
        if (!pageEl) return;
        pageEl.querySelectorAll('.iqro-row-inner').forEach(inner => {
            fitInnerToWidth(inner, minFactor, allowWrap);
        });
        // Combo cell tidak dibungkus .iqro-row-inner → tangani sebagai satu grup
        const comboWrap = pageEl.querySelector('.iqro-combo-cell')?.parentElement;
        if (comboWrap) fitInnerToWidth(comboWrap, minFactor, allowWrap);
    }

    function iqroNavGo(btn, dir) {
        const container = btn.closest('#quranDisplay, #quranDisplayDrawer')
                       || document.getElementById('quranDisplay');
        const bar = btn.closest('.iqro-nav');
        if (!container || !bar) return;
        const cur = parseInt(bar.dataset.idx || '0', 10);
        iqroShowPageIn(container, cur + dir);
    }

    function renderIqroRange(jilid, halStart, halEnd) {
        const display = document.getElementById('quranDisplay');
        if (!display) return false;

        let start = parseInt(halStart, 10);
        let end   = parseInt(halEnd, 10);
        if (isNaN(start)) return false;
        if (isNaN(end) || end < start) end = start;

        let pagesHtml = '';
        const rendered = [];
        const missing  = [];
        let pageIdx = 0;

        const src = getJilidSource(window.currentMetode);

        for (let h = start; h <= end; h++) {
            const data = src.data[String(jilid)] && src.data[String(jilid)][String(h)];
            if (!data) { missing.push(h); continue; }
            pagesHtml += buildIqroPageHtml(data, pageIdx);
            rendered.push(h);
            pageIdx++;
        }

        if (rendered.length === 0) {
            display.innerHTML = '<p style="color:#ef4444;text-align:center;padding:30px;font-family:system-ui;">⚠️ Halaman belum tersedia. Hubungi admin untuk menambah data.</p>';
            return false;
        }

        let warnHtml = '';
        if (missing.length) {
            warnHtml = '<p data-html2canvas-ignore="true" style="color:#f59e0b;text-align:center;font-size:12px;font-family:system-ui;margin-bottom:8px;">' +
                '⚠️ Halaman ' + missing.join(', ') + ' belum tersedia, dilewati.</p>';
        }

        display.innerHTML = buildNavBar(rendered) + warnHtml + pagesHtml;

        display.style.setProperty('max-height', 'none', 'important');
        display.style.setProperty('overflow-y', 'visible', 'important');

        iqroShowPageIn(display, 0);

        // Auto-fit ringan untuk layar — turunkan font baris kepanjangan secukupnya,
        // sisanya biar bisa di-scroll horizontal (.iqro-row-scroll).
        // Tunggu font Amiri & layout settle dulu baru ukur.
        const fitScreenSoon = (el) => {
            setTimeout(() => fitPageRowsScreen(el), 150);
            setTimeout(() => fitPageRowsScreen(el), 500);
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(() => fitPageRowsScreen(el)).catch(() => {});
            }
        };
        fitScreenSoon(display);

        const drawerDisplay = document.getElementById('quranDisplayDrawer');
        if (drawerDisplay) {
            drawerDisplay.innerHTML = display.innerHTML;
            drawerDisplay.style.setProperty('max-height', 'none', 'important');
            drawerDisplay.style.setProperty('overflow-y', 'visible', 'important');
            iqroShowPageIn(drawerDisplay, 0);
            fitScreenSoon(drawerDisplay);
        }

        return true;
    }

    function renderIqroPage(jilid, halaman) {
        return renderIqroRange(jilid, halaman, halaman);
    }

    // Rename judul blok halaman (theme-independent): cari span berdasar TEKS.
    function setReaderPageTitle(toIqro) {
        ['#quranPanel', '#quranPanelDrawer'].forEach(sel => {
            document.querySelectorAll(sel + ' span').forEach(s => {
                if (toIqro && /Mushaf|Madinah/i.test(s.textContent)) {
                    s.textContent = '📗 Halaman Iqro';
                } else if (!toIqro && /Halaman Iqro/i.test(s.textContent)) {
                    s.textContent = '📄 Halaman Mushaf Madinah';
                }
            });
        });
    }

    function updateIqroStatistik(jilid, halStart, halEnd) {
        const start = parseInt(halStart, 10);
        const end   = parseInt(halEnd, 10) || start;
        const jumlahHal = (end >= start) ? (end - start + 1) : 1;

        const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };

        setText('displayJuzRange', 'Jilid ' + jilid);
        setText('displayTotalJuz', jumlahHal);

        const lblJuzRange = document.querySelector('#displayJuzRange') && document.querySelector('#displayJuzRange').previousElementSibling;
        const lblTotalJuz = document.querySelector('#displayTotalJuz') && document.querySelector('#displayTotalJuz').previousElementSibling;
        if (lblJuzRange) lblJuzRange.textContent = 'Jilid';
        if (lblTotalJuz) lblTotalJuz.textContent = 'Jml Halaman';

        setText('displayPageStart', start);
        setText('displayPageEnd', end);
        setText('displayTotalPage', jumlahHal);

        setReaderPageTitle(true);
    }

    function resetStatistikLabels() {
        const lblJuzRange = document.querySelector('#displayJuzRange') && document.querySelector('#displayJuzRange').previousElementSibling;
        const lblTotalJuz = document.querySelector('#displayTotalJuz') && document.querySelector('#displayTotalJuz').previousElementSibling;
        if (lblJuzRange) lblJuzRange.textContent = 'Juz Range';
        if (lblTotalJuz) lblTotalJuz.textContent = 'Total Juz';
        setReaderPageTitle(false);
    }

    function iqroIsActive() {
        const d = document.getElementById('quranDisplay');
        return !!(d && d.querySelector('.iqro-page'));
    }

    function resetCounters() {
        if (window.kesalahanCount) {
            window.kesalahanCount.kelancaran = 0;
            window.kesalahanCount.tajwid     = 0;
            window.kesalahanCount.pelafalan  = 0;
        }
        ['Combined', 'SetoranOnly'].forEach(m => {
            const setVal  = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };
            const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
            setVal('kesalahanKelancaran' + m, 0); setText('displayKelancaran' + m, '0');
            setVal('kesalahanTajwid' + m, 0);     setText('displayTajwid' + m, '0');
            setVal('kesalahanPelafalan' + m, 0);  setText('displayPelafalan' + m, '0');
            setVal('nilai' + m, 100);             setText('displayNilai' + m, '100');
        });
    }

    function loadIqro(mode) {
        const jilid    = document.getElementById('iqroJilid' + mode) && document.getElementById('iqroJilid' + mode).value;
        const halStart = document.getElementById('iqroHalamanStart' + mode) && document.getElementById('iqroHalamanStart' + mode).value;
        let   halEnd   = document.getElementById('iqroHalamanEnd' + mode) && document.getElementById('iqroHalamanEnd' + mode).value;

        if (!jilid || !halStart) { alert('⚠️ Pilih jilid dan halaman mulai dulu.'); return; }
        if (!halEnd) halEnd = halStart;
        if (parseInt(halEnd, 10) < parseInt(halStart, 10)) {
            alert('⚠️ Halaman selesai tidak boleh lebih kecil dari halaman mulai.');
            return;
        }

        window.currentFormContext = mode;
        window.currentMetode = (document.getElementById('metode' + mode) && document.getElementById('metode' + mode).value) || 'Iqro';

        resetCounters();
        const ok = renderIqroRange(jilid, halStart, halEnd);
        if (!ok) return;

        if (typeof window.showQuranPanel === 'function') window.showQuranPanel();
        else if (typeof window.showPanel === 'function') window.showPanel('quranPanel');

        const setVal = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };
        setVal('iqroJilidHidden' + mode, jilid);
        setVal('iqroHalamanHidden' + mode, halStart);
        setVal('iqroHalamanEndHidden' + mode, halEnd);

        setTimeout(() => {
            if (typeof window.countTotalWords === 'function') window.countTotalWords();
            if (typeof window.updateKesalahanDisplay === 'function') window.updateKesalahanDisplay(mode);
            else if (typeof window.updateErrorCounters === 'function') window.updateErrorCounters();
            updateIqroStatistik(jilid, halStart, halEnd);
        }, 200);

        if (window.innerWidth < 1024 && typeof window.openDrawer === 'function') openDrawer('quran');

        console.log('✅ Iqro dimuat: Jilid ' + jilid + ' Hal ' + halStart + '–' + halEnd + ' (mode ' + mode + ')');
    }

    async function ensureHtml2Canvas() {
        if (typeof html2canvas !== 'undefined') return;
        await new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
            s.onload = resolve;
            s.onerror = () => reject(new Error('html2canvas load failed'));
            document.head.appendChild(s);
        });
    }

    function applyIqroMarks(srcPage, clonePage) {
        const markColors = {
            '1': 'rgba(34,197,94,0.55)',
            '2': 'rgba(234,179,8,0.60)',
            '3': 'rgba(239,68,68,0.55)',
        };
        const srcCells   = srcPage.querySelectorAll('[data-mark-state]');
        const cloneCells = clonePage.querySelectorAll('[data-mark-state]');
        srcCells.forEach((src, i) => {
            const cl = cloneCells[i];
            if (!cl) return;
            const st = src.getAttribute('data-mark-state') || '0';
            cl.style.removeProperty('background');
            cl.style.removeProperty('background-image');
            cl.style.removeProperty('background-color');
            cl.style.removeProperty('box-shadow');
            cl.style.removeProperty('transform');
            // PDF SELALU GELAP: paksa teks sel putih (override var theme) supaya
            // PDF tetap putih walau layar sedang light mode.
            cl.style.setProperty('color', '#ffffff', 'important');
            if (markColors[st]) {
                cl.style.setProperty('background-color', markColors[st], 'important');
                cl.style.setProperty('border-radius', '8px', 'important');
            }
        });
    }

    async function captureIqroPerPage() {
        await ensureHtml2Canvas();
        if (document.fonts && document.fonts.ready) {
            try { await document.fonts.ready; } catch (e) {}
        }

        const display = document.getElementById('quranDisplay');
        if (!display) return [];
        const pages = Array.from(display.querySelectorAll('.iqro-page'));
        if (!pages.length) return [];

        const RENDER_WIDTH = IQRO_PDF_RENDER_WIDTH;
        const SCALE        = 2;
        const A4_RATIO     = 297 / 210;
        const slices = [];

        for (let idx = 0; idx < pages.length; idx++) {
            const srcPage = pages[idx];

            const wrapper = document.createElement('div');
            wrapper.className = 'iqro-cap';
            // PDF SELALU GELAP: set var theme ke gelap di wrapper + latar #3E3D3A.
            wrapper.style.cssText = [
                'position:absolute', 'left:-9999px', 'top:0', 'overflow:visible',
                'width:' + RENDER_WIDTH + 'px', 'background-color:#3E3D3A',
                'padding:24px 12px', 'box-sizing:border-box',
                "font-family:'Amiri Quran','Amiri',serif",
                '--q-fg:#ffffff', '--q-line:rgba(255,255,255,0.3)',
                '--q-box-border:rgba(255,255,255,0.85)'
            ].join(';');

            const styleEl = document.createElement('style');
            styleEl.textContent =
                // Kotak teks ikut melebar penuh (buang batas 760px) saat PDF.
                '.iqro-cap .iqro-page > div:nth-child(2) { max-width:none !important; width:100% !important; overflow:visible !important; }' +
                '.iqro-cap .word-item { font-size:' + IQRO_PDF_FONT_SIZE + 'px !important; ' +
                'min-height:' + IQRO_PDF_CELL_HEIGHT + 'px !important; height:auto !important; line-height:' + IQRO_PDF_LINE_HEIGHT + ' !important; white-space:nowrap !important; ' +
                'padding-bottom:' + IQRO_PDF_CELL_PADBOT + 'px !important; box-sizing:border-box !important; }' +
                '.iqro-cap .word-item.iqro-combo-cell { font-size:' + IQRO_PDF_FONT_SIZE + 'px !important; ' +
                'height:auto !important; min-height:' + IQRO_PDF_CELL_HEIGHT + 'px !important; ' +
                'padding-bottom:' + IQRO_PDF_CELL_PADBOT + 'px !important; }' +
                // ── AISAR PDF FIX: judul & basmallah "tenggelam" ke bawah ──
                // Glyph Amiri ukuran besar punya descender dalam; beri ruang
                // ekstra bawah + line-height longgar supaya tidak menimpa band.
                '.iqro-cap .aisar-judul { line-height:2 !important; padding:20px 10px 52px 10px !important; overflow:visible !important; }' +
                '.iqro-cap .aisar-basmallah { line-height:2.4 !important; margin:0 !important; padding:12px 0 28px 0 !important; overflow:visible !important; }' +
                // Sel Aisar (2 huruf pendek) tidak butuh 150px seperti Iqro;
                // line-height dinaikkan agar harakat atas & descender aman.
                '.iqro-cap .aisar-page .word-item { min-height:120px !important; line-height:1.9 !important; padding-bottom:26px !important; white-space:nowrap !important; }';
            wrapper.appendChild(styleEl);

            const clonePage = srcPage.cloneNode(true);
            clonePage.style.display      = '';
            clonePage.style.marginBottom = '0';
            wrapper.appendChild(clonePage);
            document.body.appendChild(wrapper);

            applyIqroMarks(srcPage, clonePage);
            await new Promise(r => setTimeout(r, 500));

            // PDF: shrink baris panjang; floor lebih rendah + wrap fallback supaya
            // kalimat jilid 4–6 yang panjang tidak terpotong.
            fitPageRows(clonePage, 0.42, true);   // ← sekarang terdefinisi (lihat fungsi baru di bawah)
            await new Promise(r => setTimeout(r, 60));

            const pageCanvas = await html2canvas(wrapper, {
                scale: SCALE, useCORS: true, allowTaint: true,
                backgroundColor: '#3E3D3A', logging: false,
                width: RENDER_WIDTH, height: wrapper.scrollHeight,
                windowWidth: RENDER_WIDTH, windowHeight: wrapper.scrollHeight,
                scrollX: 0, scrollY: 0
            });

            if (wrapper.parentNode) wrapper.parentNode.removeChild(wrapper);

            const cW  = pageCanvas.width;
            const a4H = Math.floor(cW * A4_RATIO);

            const a4  = document.createElement('canvas');
            a4.width  = cW;
            a4.height = a4H;
            const ctx = a4.getContext('2d');
            ctx.fillStyle = '#3E3D3A';
            ctx.fillRect(0, 0, cW, a4H);

            let drawW = cW, drawH = pageCanvas.height;
            if (drawH > a4H) {
                const s = a4H / drawH;
                drawW = Math.floor(cW * s);
                drawH = a4H;
            }
            const dx = Math.floor((cW - drawW) / 2);
            const dy = (IQRO_PDF_VALIGN === 'top') ? 0 : Math.floor((a4H - drawH) / 2);
            ctx.drawImage(pageCanvas, 0, 0, pageCanvas.width, pageCanvas.height, dx, dy, drawW, drawH);

            slices.push(a4.toDataURL('image/jpeg', 0.82));
            console.log('✂️ Iqro page ' + (idx + 1) + '/' + pages.length + ' -> 1 halaman A4');
        }

        console.log('✅ Iqro per-page: ' + slices.length + ' halaman = ' + slices.length + ' A4');
        return slices;
    }

    function wrapCaptureForIqro() {
        const checkAndWrap = setInterval(() => {
            const orig = window.captureQuranScreenshots;
            if (typeof orig !== 'function') return;
            if (orig._iqroWrapped) { clearInterval(checkAndWrap); return; }

            window.captureQuranScreenshots = async function (deviceType) {
                if (!iqroIsActive()) {
                    return orig.apply(this, arguments);
                }
                return await captureIqroPerPage();
            };
            window.captureQuranScreenshots._iqroWrapped = true;
            console.log('✅ captureQuranScreenshots di-wrap: Iqro = 1 halaman per A4');
            clearInterval(checkAndWrap);
        }, 200);
        setTimeout(() => clearInterval(checkAndWrap), 10000);
    }

    function ensureAmiriFont() {
        if (document.getElementById('iqro-amiri-font')) return;
        const pre1 = document.createElement('link');
        pre1.rel = 'preconnect'; pre1.href = 'https://fonts.googleapis.com';
        document.head.appendChild(pre1);
        const pre2 = document.createElement('link');
        pre2.rel = 'preconnect'; pre2.href = 'https://fonts.gstatic.com'; pre2.crossOrigin = 'anonymous';
        document.head.appendChild(pre2);
        const link = document.createElement('link');
        link.id   = 'iqro-amiri-font';
        link.rel  = 'stylesheet';
        link.href = 'https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Amiri+Quran&display=swap';
        document.head.appendChild(link);
        console.log('✅ Font Amiri di-inject (iqro-handler)');
    }

    function init() {
        ensureAmiriFont();
        ['Combined', 'SetoranOnly'].forEach(mode => {
            const metodeEl = document.getElementById('metode' + mode);
            if (metodeEl) {
                metodeEl.addEventListener('change', () => toggleMetode(mode));
                toggleMetode(mode);
            }
            const jilidEl = document.getElementById('iqroJilid' + mode);
            if (jilidEl) {
                jilidEl.addEventListener('change', () => fillHalamanDropdown(mode));
                fillHalamanDropdown(mode);
            }
            const startEl = document.getElementById('iqroHalamanStart' + mode);
            const endEl   = document.getElementById('iqroHalamanEnd' + mode);
            if (startEl && endEl) {
                startEl.addEventListener('change', () => refillHalamanEnd(mode));
            }
        });
        wrapCaptureForIqro();

        console.log('✅ iqro-handler.js v4.5 (font layar ' + IQRO_SCREEN_FONT_SIZE + 'px, konstanta) ready');
    }
    // ==== SUMBER DATA PER METODE (Iqro / Aisar) ====
    const JILID_METODES = ['Iqro', 'Ummi', 'Aisar'];

    function isJilidMetode(val) {
        return JILID_METODES.indexOf(val) !== -1;
    }

    function getJilidSource(metode) {
        if (metode === 'Aisar') {
            return {
                data:   window.AISAR_DATA || {},
                jumlah: window.AISAR_JUMLAH_HALAMAN || {},
                nama:   'Aisar',
            };
        }
        return {
            data:   window.IQRO_DATA || {},
            jumlah: window.IQRO_JUMLAH_HALAMAN || {},
            nama:   'Iqro',
        };
    }

    function metodeForMode(mode) {
        const el = document.getElementById('metode' + mode);
        return (el && el.value) || window.currentMetode || 'Iqro';
    }

    document.readyState === 'loading'
        ? document.addEventListener('DOMContentLoaded', () => setTimeout(init, 500))
        : setTimeout(init, 500);

    window.loadIqro        = loadIqro;
    window.toggleMetode    = toggleMetode;
    window.renderIqroPage  = renderIqroPage;
    window.renderIqroRange = renderIqroRange;
    window.iqroNavGo       = iqroNavGo;
})();