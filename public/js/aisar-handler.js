/**
 * AISAR HANDLER v1.0 — renderer khusus metode Aisar.
 * ------------------------------------------------------------------
 * WAJIB dimuat SETELAH iqro-handler.js dan aisar-data.js.
 *
 * Pembagian tanggung jawab:
 *   - iqro-handler.js : dropdown jilid/halaman, toggleMetode, navigasi
 *                       (iqroNavGo), capture PDF, layout halaman IQRO.
 *   - aisar-handler.js: layout halaman AISAR (basmallah, judul, header
 *                       2 kolom, baris 2 kolom, catatan bawah).
 *
 * Cara kerja: wrap window.loadIqro → bila metode form = 'Aisar',
 * render pakai buildAisarPageHtml; selain itu diteruskan ke handler asli.
 * Halaman tetap ber-class .iqro-page & sel .word-item agar navigasi,
 * marking (markWord), dan PDF capture existing tetap kompatibel.
 * ------------------------------------------------------------------
 */
(function () {
    'use strict';

    // ==== UKURAN FONT LAYAR ====
    const AISAR_SCREEN_FONT_SIZE = 40;  // sel baris
    const AISAR_JUDUL_FONT_SIZE  = 52;  // judul besar (أَبَ)
    const AISAR_BASMALLAH_SIZE   = 26;

    const LINE   = 'var(--q-line,rgba(255,255,255,0.3))';
    const FG     = 'var(--q-fg,#fff)';
    const BORDER = 'var(--q-box-border,rgba(255,255,255,0.85))';

    // ---- sel frasa (klik = marking, sama dengan Iqro) ----
    // hl = true → teks putih tebal (background biru ada di DIV sel, bukan di span,
    // supaya siklus marking (markWord) tidak menimpa/menghapus warna highlight)
    function cellSpan(txt, hl, size) {
    const fontSize = size || AISAR_SCREEN_FONT_SIZE;
    const fgStyle = hl ? 'color:var(--q-accent-fg,#fff);font-weight:700;' : 'color:' + FG + ';';
    return '<span class="word-item" data-word="' + txt + '" data-mark-state="0" ' +
        'onclick="markWord(this)" ' +
        'style="display:inline-flex;align-items:center;justify-content:center;white-space:nowrap;' +
        'cursor:pointer;padding:6px 12px;border-radius:8px;min-height:90px;height:auto;line-height:1.6;' +
        'text-align:center;box-sizing:border-box;' +
        "font-family:'Amiri Quran','Amiri',serif;font-size:" + fontSize + 'px;' + fgStyle +
        'transition:all 0.25s cubic-bezier(0.4,0,0.2,1);">' + txt + '</span>';
}

    // ---- band teks (instruksi di bawah judul / pembatas tengah) ----
    function bandHtml(text) {
        return '<div style="border-top:1px solid ' + LINE + ';padding:7px 10px;text-align:center;' +
            'font-family:system-ui;font-size:12px;font-weight:700;letter-spacing:.4px;color:' + FG + ';' +
            'opacity:.9;background:var(--q-band,rgba(127,127,127,0.14));">' + text + '</div>';
    }

    // ---- baris N kolom, RTL: index 0 = paling kanan, garis pemisah antar kolom ----
    function renderRow(row) {
        // band pembatas di tengah halaman: { divider: "teks" }
        if (row && !Array.isArray(row) && typeof row === 'object' && row.divider) {
            return bandHtml(row.divider);
        }
        // blok berlabel samping: { blockLabel: "أَ", rows: [[..],[..],[..]] }
        // label vertikal di sisi KANAN membentang setinggi seluruh rows
        if (row && !Array.isArray(row) && typeof row === 'object' && row.blockLabel) {
            const inner = (row.rows || []).map(function (r, ri) {
                const cells = Array.isArray(r) ? r : [r];
                const top = ri > 0 ? 'border-top:1px solid ' + LINE + ';' : '';
                return '<div style="display:flex;direction:rtl;align-items:stretch;' + top + '">' +
                    cells.map(function (c, i) {
                        const sep = (i < cells.length - 1) ? 'border-left:1px solid ' + LINE + ';' : '';
                        return '<div class="aisar-cell" style="flex:1;display:flex;justify-content:center;align-items:center;padding:2px 4px;' + sep + '">' + cellSpan(c) + '</div>';
                    }).join('') +
                '</div>';
            }).join('');

            return '' +
                '<div style="display:flex;direction:rtl;align-items:stretch;border-top:1px solid ' + LINE + ';">' +
                    // kolom label kanan (sempit, vertikal tengah)
                    '<div style="flex:0 0 72px;max-width:72px;display:flex;justify-content:center;align-items:center;border-left:1px solid ' + LINE + ';">' +
                        cellSpan(row.blockLabel) +
                    '</div>' +
                    // grid isi blok
                    '<div style="flex:1;display:flex;flex-direction:column;">' + inner + '</div>' +
                '</div>';
        }

        const cells = Array.isArray(row) ? row : [row];

        // 1 sel = baris rangkuman penuh
        if (cells.length === 1) {
            return '' +
                '<div style="display:flex;direction:rtl;justify-content:center;align-items:center;border-top:1px solid ' + LINE + ';padding:2px 4px;">' +
                    cellSpan(cells[0]) +
                '</div>';
        }

        // N kolom: border-left di semua sel kecuali sel terakhir (paling kiri)
        // sel bisa: "teks" | { hl: "teks" } | { text: "teks", span: 2 } (lebar setara N kolom)
        return '' +
            '<div style="display:flex;direction:rtl;align-items:stretch;border-top:1px solid ' + LINE + ';">' +
                (function () {
                    // total unit kolom baris ini (span dihitung)
                    const totalUnits = cells.reduce(function (t, c) {
                        return t + ((c && typeof c === 'object' && c.span) ? c.span : 1);
                    }, 0);
                    return cells.map(function (c, i) {
                        const sep   = (i < cells.length - 1) ? 'border-left:1px solid ' + LINE + ';' : '';
                        const isObj = c && typeof c === 'object';
                        const isHl  = isObj && c.hl;
                        const txt   = isHl ? c.hl : (isObj ? c.text : c);
                        const size  = (isObj && c.size) ? c.size : null;   // ← tambahkan baris ini
                        const label = (isObj && c.label)
                            ? '<div style="font-family:\'Amiri\',serif;font-size:13px;color:' + FG + ';opacity:.65;text-align:center;padding-top:4px;line-height:1;">' + c.label + '</div>'
                            : '';
                        const span  = (isObj && c.span) ? c.span : 1;
                        const bg    = isHl ? 'background:var(--q-accent,#2E86AB);' : '';
                        const pct   = (span / totalUnits * 100).toFixed(4) + '%';
                        return '<div class="aisar-cell" style="flex:0 0 ' + pct + ';max-width:' + pct + ';box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;align-items:center;padding:2px 4px;' + bg + sep + '">' +
                            label + cellSpan(txt, isHl, size) +
                        '</div>';
                    }).join('');
                })() +
            '</div>';
    }

    // ---- header kolom (teks latin kecil), rtl → index 0 di kanan ----
    function renderKolomHeader(headers) {
        if (!headers || headers.length !== 2) return '';
        return '' +
            '<div style="display:flex;direction:rtl;border-top:1px solid ' + LINE + ';">' +
                '<div style="flex:1;text-align:center;padding:8px 6px;font-family:system-ui;font-size:12px;font-weight:700;letter-spacing:.4px;color:' + FG + ';opacity:.85;border-left:1px solid ' + LINE + ';">' + headers[0] + '</div>' +
                '<div style="flex:1;text-align:center;padding:8px 6px;font-family:system-ui;font-size:12px;font-weight:700;letter-spacing:.4px;color:' + FG + ';opacity:.85;">' + headers[1] + '</div>' +
            '</div>';
    }

    function buildAisarPageHtml(data, pageIdx) {
        const basmallahHtml = data.basmallah
            ? '<p class="aisar-basmallah" style="text-align:center;direction:rtl;font-family:\'Amiri Quran\',\'Amiri\',serif;font-size:' + AISAR_BASMALLAH_SIZE + 'px;color:' + FG + ';margin:4px 0 6px 0;line-height:1.8;">﴿ ' + data.basmallah + ' ﴾</p>'
            : '';

        const instruksiHtml = data.instruksi ? bandHtml(data.instruksi) : '';

        let judulHtml = '';
        if (data.judul && data.judul.length) {
            const judulText = data.judul.join(' ');
            const isArabJudul = /[\u0600-\u06FF]/.test(judulText);
            judulHtml = isArabJudul
                ? '<div class="aisar-judul" style="text-align:center;direction:rtl;padding:16px 10px;border-top:1px solid ' + LINE + ';font-family:\'Amiri Quran\',\'Amiri\',serif;font-size:' + AISAR_JUDUL_FONT_SIZE + 'px;font-weight:700;color:' + FG + ';line-height:1.5;">' + judulText + '</div>'
                : '<div style="text-align:center;padding:10px;border-top:1px solid ' + LINE + ';font-family:system-ui;font-size:16px;font-weight:700;letter-spacing:.3px;color:' + FG + ';">' + judulText + '</div>';
        }

        const rowsHtml = (data.baris || []).map(renderRow).join('');

        const catatanHtml = data.catatan ? bandHtml(data.catatan) : '';

        const displayStyle = pageIdx === 0 ? '' : 'display:none;';
        // class iqro-page DIPERTAHANKAN → navigasi & PDF capture existing tetap jalan
        return '' +
            '<div class="iqro-page aisar-page" data-idx="' + pageIdx + '" data-halaman="' + data.halaman + '" style="margin-bottom:8px;' + displayStyle + '">' +
                '<div style="text-align:center;margin-bottom:8px;color:' + FG + ';">' +
                    '<p style="font-size:14px;opacity:.65;font-family:system-ui;">Aisar ' + data.jilid + ' &nbsp;·&nbsp; Halaman ' + data.halaman + '</p>' +
                '</div>' +
                '<div style="border:1.5px solid ' + BORDER + ';margin:8px auto 16px auto;max-width:760px;overflow:hidden;border-radius:6px;">' +
                    basmallahHtml +
                    judulHtml +
                    instruksiHtml +
                    renderKolomHeader(data.kolomHeader) +
                    rowsHtml +
                    catatanHtml +
                '</div>' +
            '</div>';
    }

    // ---- nav bar (markup sama dgn iqro, tombol pakai iqroNavGo global) ----
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

    function renderAisarRange(jilid, halStart, halEnd) {
        const display = document.getElementById('quranDisplay');
        if (!display) return false;

        let start = parseInt(halStart, 10);
        let end   = parseInt(halEnd, 10);
        if (isNaN(start)) return false;
        if (isNaN(end) || end < start) end = start;

        const data   = window.AISAR_DATA || {};
        let pagesHtml = '';
        const rendered = [];
        const missing  = [];
        let pageIdx = 0;

        for (let h = start; h <= end; h++) {
            const page = data[String(jilid)] && data[String(jilid)][String(h)];
            if (!page) { missing.push(h); continue; }
            pagesHtml += buildAisarPageHtml(page, pageIdx);
            rendered.push(h);
            pageIdx++;
        }

        if (rendered.length === 0) {
            display.innerHTML = '<p style="color:#ef4444;text-align:center;padding:30px;font-family:system-ui;">⚠️ Halaman Aisar belum tersedia. Hubungi admin untuk menambah data.</p>';
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

        // sinkron ke drawer mobile (sama seperti iqro-handler)
        const drawerDisplay = document.getElementById('quranDisplayDrawer');
        if (drawerDisplay) {
            drawerDisplay.innerHTML = display.innerHTML;
            drawerDisplay.style.setProperty('max-height', 'none', 'important');
            drawerDisplay.style.setProperty('overflow-y', 'visible', 'important');
        }

        return true;
    }

    // ---- statistik (replika updateIqroStatistik, label Aisar) ----
    function updateAisarStatistik(jilid, halStart, halEnd) {
        const start = parseInt(halStart, 10);
        const end   = parseInt(halEnd, 10) || start;
        const jumlahHal = (end >= start) ? (end - start + 1) : 1;
        const setText = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };

        setText('displayJuzRange', 'Jilid ' + jilid);
        setText('displayTotalJuz', jumlahHal);
        const lblJuzRange = document.querySelector('#displayJuzRange')?.previousElementSibling;
        const lblTotalJuz = document.querySelector('#displayTotalJuz')?.previousElementSibling;
        if (lblJuzRange) lblJuzRange.textContent = 'Jilid';
        if (lblTotalJuz) lblTotalJuz.textContent = 'Jml Halaman';
        setText('displayPageStart', start);
        setText('displayPageEnd', end);
        setText('displayTotalPage', jumlahHal);

        // judul blok: "Halaman Aisar"
        ['#quranPanel', '#quranPanelDrawer'].forEach(sel => {
            document.querySelectorAll(sel + ' span').forEach(s => {
                if (/Mushaf|Madinah|Halaman Iqro/i.test(s.textContent)) {
                    s.textContent = '📘 Halaman Aisar';
                }
            });
        });
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

    // ---- loadAisar: replika alur loadIqro tapi render Aisar ----
    function loadAisar(mode) {
        const jilid    = document.getElementById('iqroJilid' + mode)?.value;
        const halStart = document.getElementById('iqroHalamanStart' + mode)?.value;
        let   halEnd   = document.getElementById('iqroHalamanEnd' + mode)?.value;

        if (!jilid || !halStart) { alert('⚠️ Pilih jilid dan halaman mulai dulu.'); return; }
        if (!halEnd) halEnd = halStart;
        if (parseInt(halEnd, 10) < parseInt(halStart, 10)) {
            alert('⚠️ Halaman selesai tidak boleh lebih kecil dari halaman mulai.');
            return;
        }

        window.currentFormContext = mode;
        window.currentMetode = 'Aisar';

        resetCounters();
        const ok = renderAisarRange(jilid, halStart, halEnd);
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
            updateAisarStatistik(jilid, halStart, halEnd);
        }, 200);

        if (window.innerWidth < 1024 && typeof window.openDrawer === 'function') openDrawer('quran');

        console.log('✅ Aisar dimuat: Jilid ' + jilid + ' Hal ' + halStart + '–' + halEnd + ' (mode ' + mode + ')');
    }

    // ---- WRAP window.loadIqro: metode Aisar → loadAisar, selain itu → asli ----
    function wrapLoadIqro() {
        const checkAndWrap = setInterval(() => {
            const orig = window.loadIqro;
            if (typeof orig !== 'function') return;
            if (orig._aisarWrapped) { clearInterval(checkAndWrap); return; }

            window.loadIqro = function (mode) {
                const metodeEl = document.getElementById('metode' + mode);
                if (metodeEl && metodeEl.value === 'Aisar') {
                    return loadAisar(mode);
                }
                return orig.apply(this, arguments);
            };
            window.loadIqro._aisarWrapped = true;
            console.log('✅ loadIqro di-wrap: metode Aisar → aisar-handler');
            clearInterval(checkAndWrap);
        }, 200);
        setTimeout(() => clearInterval(checkAndWrap), 10000);
    }

    // ---- sinkron label "Iqro"/"Aisar" pada tombol & judul blok ----
    // ---- sinkron label "Iqro"/"Aisar" pada tombol & judul blok ----
    function getMetodeValue(mode) {
        // coba beberapa kemungkinan elemen metode (select / hidden / display input)
        const ids = ['metode' + mode, 'metodeHidden' + mode, 'metodeDisplay' + mode];
        for (const id of ids) {
            const el = document.getElementById(id);
            if (el) {
                const v = (el.value !== undefined && el.value !== '') ? el.value : el.textContent;
                if (v) return String(v).trim().toLowerCase();
            }
        }
        return '';
    }

    // ---- sinkron label & warna tombol "Iqro"/"Aisar" per form ----
function syncMetodeLabels() {
    // pasangan class warna: emerald (Iqro) ↔ blue (Aisar)
    const IQRO_CLS  = ['bg-emerald-600', 'hover:bg-emerald-700'];
    const AISAR_CLS = ['bg-blue-600', 'hover:bg-blue-700'];

    setInterval(() => {
        ['Combined', 'SetoranOnly'].forEach(mode => {
            const isAisar = getMetodeValue(mode).includes('aisar');
            const label   = isAisar ? 'Aisar' : 'Iqro';
            const block   = document.getElementById('iqroBlock' + mode);
            if (!block) return;

            // teks label di dalam blok mode ini saja
            block.querySelectorAll('.lbl-metode-jilid').forEach(el => {
                if (el.textContent !== label) el.textContent = label;
            });

            // judul blok "Pilih Halaman Iqro/Aisar" bila ada
            const heading = block.querySelector('p');
            if (heading && /Pilih Halaman/i.test(heading.textContent)) {
                const want = 'Pilih Halaman ' + label;
                // hanya ganti node teks, biarkan ikon svg
                heading.childNodes.forEach(n => {
                    if (n.nodeType === 3 && n.textContent.trim()) n.textContent = ' ' + want;
                });
            }

            // warna tombol "Tampilkan Halaman ..."
            const btn = block.querySelector('button[onclick*="loadIqro"]');
            if (btn) {
                const addCls    = isAisar ? AISAR_CLS : IQRO_CLS;
                const removeCls = isAisar ? IQRO_CLS  : AISAR_CLS;
                removeCls.forEach(c => btn.classList.remove(c));
                addCls.forEach(c => btn.classList.add(c));
                // emoji ikon: 📗 hijau untuk Iqro, 📘 biru untuk Aisar
                btn.innerHTML = btn.innerHTML.replace(isAisar ? '📗' : '📘', isAisar ? '📘' : '📗');
            }
        });
    }, 500);
}

    wrapLoadIqro();
    syncMetodeLabels();

    window.loadAisar        = loadAisar;
    window.renderAisarRange = renderAisarRange;

    console.log('✅ aisar-handler.js v1.0 ready');
})();