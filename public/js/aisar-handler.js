/**
 * AISAR HANDLER v1.9 — renderer khusus metode Aisar.
 * ------------------------------------------------------------------
 * WAJIB dimuat SETELAH iqro-handler.js dan aisar-data.js.
 *
 * Pembagian tanggung jawab:
 *   - iqro-handler.js : dropdown jilid/halaman, toggleMetode, navigasi
 *                       (iqroNavGo), capture PDF, layout halaman IQRO.
 *   - aisar-handler.js: layout halaman AISAR (basmallah, judul, header
 *                       2 kolom, baris N kolom, catatan bawah).
 *
 * Cara kerja: wrap window.loadIqro → bila metode form = 'Aisar',
 * render pakai buildAisarPageHtml; selain itu diteruskan ke handler asli.
 *
 * ------------------------------------------------------------------
 * PERUBAHAN v1.9
 *
 *   Badge (sel hl biru) kini menempel penuh atas–bawah baris. Padding
 *   vertikal N kolom dipindah dari wadah baris ke tiap sel, sehingga
 *   background sel ikut menutupi area padding. Jarak tanda mad jilid 5
 *   tidak berubah.
 *
 * ------------------------------------------------------------------
 * PERUBAHAN v1.8
 *
 *   BUG: baris non-wrap (mis. Jilid 2 hlm 29, baris badge ـَوْ) turun
 *   ke baris kedua padahal seharusnya satu baris.
 *
 *   Penyebab 1 — deteksi mode wrap memakai
 *       getComputedStyle(span).display === 'block'
 *   Wadah .aisar-fit adalah anak dari .aisar-cell (display:flex), dan
 *   browser otomatis mem-"blockify" anak flex. Hasilnya sel non-wrap
 *   terdeteksi sebagai wrap → white-space:normal → turun baris.
 *   Sekarang mode wrap ditandai EKSPLISIT lewat atribut data-wrap.
 *
 *   Penyebab 2 — lebar tersedia (avail) diukur dari SELURUH baris,
 *   termasuk sel badge. Sekarang dihitung per sel: jatah persen kolom
 *   untuk grid biasa, atau lebar baris dikurangi sel saudara untuk
 *   baris yang punya sel narrow/badge.
 *
 * ------------------------------------------------------------------
 * GRANULARITAS MARKING (v1.7)
 *
 *   Sel dipecah per kata bila:
 *     - baris berisi SATU kolom penuh (kalimat panjang), atau
 *     - teks sel memakai pemisah "•"
 *   Sel grid pendek (2–5 kolom) TIDAK dipecah: satu sel = satu unit latihan.
 *
 *   Token yang isinya hanya pemisah / tanda akhir ayat (۝٦, ۞, tanda hubung)
 *   dirender pasif — tidak bisa diklik, tidak terhitung sebagai kesalahan.
 *
 *   PENTING soal CSS: token memakai display:inline, dan wadahnya TIDAK
 *   boleh flex. Inline-block/flex memutus alur teks Arab.
 *
 *   Efek samping: countTotalWords() menghitung lebih banyak unit di
 *   jilid 2–5, sehingga nilai setoran lama & baru tidak sebanding.
 *
 * ------------------------------------------------------------------
 * STRATEGI UKURAN TEKS
 *
 *   1. Halaman ber-`wrap: true` (talqin) TIDAK di-auto-fit. Teks dibiarkan
 *      mengalir supaya seluruh ayat muncul utuh di PDF. Ukuran font diatur
 *      manual lewat opsi `fontSize` di aisar-data.js.
 *
 *   2. Halaman NON-wrap di-auto-fit: font disusutkan sampai AISAR_MIN_FONT.
 *      Kalau masih meluber, wadah dialihkan ke mode mengalir.
 *
 *   3. Auto-fit mengukur WADAH sel (.aisar-fit), bukan token per kata.
 *
 *   4. Sel `narrow` (nama surat, badge) dilewati auto-fit.
 *
 * ------------------------------------------------------------------
 * OPSI PER-HALAMAN (aisar-data.js):
 *   fontSize, noDot, wrap, noLine, titik, bulatBaris / pola
 *
 * OPSI PER-SEL:
 *   { text, hl, size, span, narrow, label, labelPos:'below', surah }
 *
 * OPSI BARIS KHUSUS:
 *   { divider: "teks" }
 *   { blockLabel: "١", rows: [[..],[..]] }
 * ------------------------------------------------------------------
 */
(function () {
    'use strict';

    // ==== UKURAN FONT LAYAR ====
    const AISAR_SCREEN_FONT_SIZE = 34;  // sel baris (default global)
    const AISAR_JUDUL_FONT_SIZE  = 48;  // judul besar (أَبَ)
    const AISAR_BASMALLAH_SIZE   = 26;
    // Batas bawah auto-fit (halaman NON-wrap). Turunkan ke 18–20 bila ingin
    // baris panjang tetap satu baris di layar sempit (mobile).
    const AISAR_MIN_FONT         = 22;

    const LINE   = 'var(--q-line,rgba(255,255,255,0.3))';
    const FG     = 'var(--q-fg,#fff)';
    const BORDER = 'var(--q-box-border,rgba(255,255,255,0.85))';

    // AYAH_MARK_RE: HANYA tanda yang berdiri sendiri —
    //   06D6–06DB = tanda waqaf (ۖ ۗ ۘ ۙ ۚ ۛ)
    //   06DD      = ۝ akhir ayat
    //   06DE      = ۞ rub' el-hizb
    // SENGAJA TIDAK termasuk 06DC dan 06DF–06E8 (tanda COMBINING).
    const AYAH_MARK_RE = /([\u06D6-\u06DB\u06DD\u06DE][\u0660-\u0669\d]*)/g;

    // Token yang isinya cuma pemisah / tanda ayat / angka — dirender pasif.
    const SEP_ONLY_RE = /^[\s\-–—.\u06DD\u06DE\u0660-\u0669\d]+$/;

    /** Satu potongan teks → span markable, atau span pasif bila hanya pemisah. */
    function tokenSpan(raw, wrap, fontSize) {
        const ayahMarkSize = wrap ? fontSize : Math.round(fontSize * 0.90);
        const inner = String(raw).replace(AYAH_MARK_RE,
            '<span class="aisar-ayah-mark" style="display:inline-block;font-size:' + ayahMarkSize + 'px;' +
            'line-height:1;vertical-align:middle;margin:0 4px;white-space:nowrap;">$1</span>');

        if (SEP_ONLY_RE.test(raw)) {
            return '<span class="aisar-sep">' + inner + '</span>';
        }

        // display:inline WAJIB — inline-block/flex memutus alur teks Arab.
        return '<span class="word-item" data-word="' + raw + '" data-mark-state="0" ' +
            'onclick="markWord(this)" ' +
            'style="display:inline;cursor:pointer;border-radius:6px;padding:2px 4px;' +
            'transition:background-color .25s ease;">' + inner + '</span>';
    }

    function dotSpan() {
        return '<span class="aisar-dot" style="display:inline-block;width:20px;height:20px;' +
            'border:2px solid currentColor;border-radius:50%;opacity:.6;margin:0 14px;' +
            'vertical-align:middle;"></span>';
    }

    /**
     * splittable = true → sel dipecah per kata.
     * data-wrap ditulis eksplisit supaya auto-fit tidak perlu menebak mode
     * dari computed display (lihat PERUBAHAN v1.8).
     */
    function cellSpan(txt, hl, size, noDot, wrap, splittable) {
        const fontSize = size || AISAR_SCREEN_FONT_SIZE;
        const fgStyle  = hl ? 'color:var(--q-accent-fg,#fff);font-weight:700;' : 'color:' + FG + ';';
        const wrapAttr = 'data-wrap="' + (wrap ? '1' : '0') + '" ';

        const hasAyahMark = AYAH_MARK_RE.test(String(txt));
        AYAH_MARK_RE.lastIndex = 0; // reset karena regex global dipakai test()
        const extraPad = hasAyahMark ? 'padding-top:22px;padding-bottom:14px;' : 'padding:6px 12px;';
        const minH = wrap ? '0' : '90px';
        const lh   = wrap ? '2.1' : '1.6';

        // ── Sel utuh: satu sel = satu unit marking ────────────────────
        if (!splittable) {
            let disp = noDot
                ? String(txt).replace(/\s*•\s*/g, '&nbsp;&nbsp;')
                : String(txt).replace(/\s*•\s*/g,
                    '<span class="aisar-dot" style="display:inline-block;width:20px;height:20px;' +
                    'border:2px solid currentColor;border-radius:50%;opacity:.6;' +
                    'margin:0 14px;flex:0 0 auto;align-self:center;"></span>');

            const ayahMarkSize = wrap ? fontSize : Math.round(fontSize * 0.90);
            disp = disp.replace(AYAH_MARK_RE,
                '<span class="aisar-ayah-mark" style="display:inline-block;font-size:' + ayahMarkSize + 'px;' +
                'line-height:1;vertical-align:middle;margin:0 4px;white-space:nowrap;">$1</span>');

            const boxStyle = wrap
                ? 'display:block;width:100%;white-space:normal;word-break:normal;text-align:center;'
                : 'display:inline-flex;align-items:center;justify-content:center;white-space:nowrap;text-align:center;';

            return '<span class="word-item aisar-fit" data-word="' + txt + '" data-mark-state="0" ' +
                wrapAttr +
                'onclick="markWord(this)" ' +
                'style="' + boxStyle +
                'cursor:pointer;' + extraPad + 'border-radius:8px;min-height:' + minH + ';height:auto;line-height:' + lh + ';' +
                'box-sizing:border-box;overflow:visible;' +
                "font-family:'Amiri Quran','Amiri',serif;font-size:" + fontSize + 'px;' + fgStyle +
                'transition:all 0.25s cubic-bezier(0.4,0,0.2,1);">' + disp + '</span>';
        }

        // ── Sel dipecah per kata ──────────────────────────────────────
        const potongan = [];
        String(txt).split(/(\s*•\s*)/).forEach(function (bagian) {
            if (!bagian) return;
            if (/•/.test(bagian)) {
                potongan.push(noDot ? '&nbsp;&nbsp;' : dotSpan());
                return;
            }
            bagian.trim().split(/\s+/).forEach(function (kata) {
                if (kata) potongan.push(tokenSpan(kata, wrap, fontSize));
            });
        });

        const boxStyle = wrap
            ? 'display:block;width:100%;white-space:normal;word-break:normal;text-align:center;'
            : 'display:inline-block;white-space:nowrap;text-align:center;';

        return '<span class="aisar-fit" ' + wrapAttr +
            'style="' + boxStyle + extraPad +
            'min-height:' + minH + ';height:auto;line-height:' + lh + ';' +
            'box-sizing:border-box;overflow:visible;' +
            "font-family:'Amiri Quran','Amiri',serif;font-size:" + fontSize + 'px;' + fgStyle +
            '">' + potongan.join(' ') + '</span>';
    }

    // ---- bulat waqaf (replika iqro-handler) ----
    function bulatSpan() {
        return '<span class="iqro-bulat" aria-label="isi waqaf" style="display:inline-block;width:30px;height:30px;' +
            'border:2px solid ' + FG + ';border-radius:50%;opacity:.7;flex:0 0 auto;"></span>';
    }

    function cellHtml(txt, hl, size, withBulat, titik, noDot, wrap, splittable) {
        if (txt === '◯' || txt === '○') {
            return '<span class="iqro-waqaf-cell" style="display:inline-flex;align-items:center;justify-content:center;">' + bulatSpan() + '</span>';
        }
        if (!withBulat || hl) return cellSpan(txt, hl, size, noDot, wrap, splittable);
        const decoHtml = titik
            ? '<span class="iqro-deco" style="font-family:\'Amiri\',serif;font-size:26px;line-height:1;color:' + FG + ';opacity:.55;letter-spacing:2px;">' + titik + '</span>'
            : '';
        return '<span class="iqro-waqaf-cell" style="display:inline-flex;align-items:center;gap:10px;">' +
            cellSpan(txt, hl, size, noDot, wrap, splittable) + decoHtml + bulatSpan() +
        '</span>';
    }

    // ---- band teks (instruksi / pembatas tengah / catatan) ----
    function bandHtml(text) {
        return '<div style="border-top:1px solid ' + LINE + ';padding:7px 10px;text-align:center;' +
            "font-family:system-ui,'Amiri Quran','Amiri',serif;font-size:12px;font-weight:700;letter-spacing:.4px;color:" + FG + ';' +
            'opacity:.9;line-height:1.9;background:var(--q-band,rgba(127,127,127,0.14));">' + text + '</div>';
    }

    // ---- baris N kolom, RTL: index 0 = paling kanan ----
    function renderRow(row, rowIdx, page) {
        const isWaqaf   = !!(page && page.pola === 'waqaf');
        const withBulat = isWaqaf ||
            (page && Array.isArray(page.bulatBaris) && page.bulatBaris.indexOf(rowIdx) !== -1);
        const titik    = (page && page.titik !== undefined) ? page.titik : '…';
        const pageSize = (page && page.fontSize) ? page.fontSize : null;
        const noDot    = !!(page && page.noDot);
        const wrap     = !!(page && page.wrap);
        const noLine   = !!(page && page.noLine);
        const rowBorder = noLine ? '' : 'border-top:1px solid ' + LINE + ';';

        const bolehPecah = function (t, barisPenuh) {
            const s = String(t);
            if (s === '◯' || s === '○') return false;
            if (/•/.test(s)) return true;
            return barisPenuh && /\s/.test(s.trim());
        };

        // band pembatas: { divider: "teks" }
        if (row && !Array.isArray(row) && typeof row === 'object' && 'divider' in row) {
            return bandHtml(row.divider || '&nbsp;');
        }

        // blok berlabel samping: { blockLabel: "١", rows: [[..],[..]] }
        if (row && !Array.isArray(row) && typeof row === 'object' && row.blockLabel) {
            const ovfRow  = wrap ? 'overflow:visible;' : 'overflow-x:auto;scrollbar-width:thin;';
            const ovfCell = wrap ? '' : 'overflow-x:auto;';
            const inner = (row.rows || []).map(function (r, ri) {
                const cells = Array.isArray(r) ? r : [r];
                const top = (ri > 0 && !noLine) ? 'border-top:1px solid ' + LINE + ';' : '';
                const barisPenuh = cells.length === 1;
                return '<div class="iqro-row-scroll" style="display:flex;direction:rtl;align-items:stretch;' + ovfRow + top + '">' +
                    cells.map(function (c, i) {
                        const sep = (i < cells.length - 1) ? 'border-left:1px solid ' + LINE + ';' : '';
                        return '<div class="aisar-cell" style="flex:1;min-width:0;display:flex;justify-content:center;align-items:center;padding:2px 4px;' + ovfCell + sep + '">' +
                            cellHtml(c, false, pageSize, withBulat, titik, noDot, wrap, bolehPecah(c, barisPenuh)) +
                        '</div>';
                    }).join('') +
                '</div>';
            }).join('');

            return '' +
                '<div style="display:flex;direction:rtl;align-items:stretch;' + rowBorder + '">' +
                    '<div class="aisar-narrow" style="flex:0 0 72px;max-width:72px;display:flex;justify-content:center;align-items:center;border-left:1px solid ' + LINE + ';">' +
                        cellSpan(row.blockLabel, false, pageSize, noDot, false, false) +
                    '</div>' +
                    '<div style="flex:1;min-width:0;display:flex;flex-direction:column;">' + inner + '</div>' +
                '</div>';
        }

        const cells = Array.isArray(row) ? row : [row];

        // ===== 1 sel = baris rangkuman penuh =====
        if (cells.length === 1) {
            const c0     = cells[0];
            const isObj0 = c0 && typeof c0 === 'object';
            const txt0   = isObj0 ? c0.text : c0;
            const surahHtml = (isObj0 && c0.surah)
                ? '<span style="float:left;margin-top:4px;margin-inline-start:2px;' +
                  'font-family:\'Amiri\',serif;font-size:12px;color:' + FG + ';opacity:.6;' +
                  'line-height:1;white-space:nowrap;">' + c0.surah + '</span>'
                : '';
            const inner = cellHtml(
                txt0, false,
                (isObj0 && c0.size) ? c0.size : pageSize,
                withBulat, titik, noDot, wrap,
                bolehPecah(txt0, true)
            );

            if (wrap) {
                return '' +
                    '<div class="iqro-row-scroll" style="display:block;direction:rtl;' + rowBorder +
                    'padding:6px 10px;overflow:visible;width:100%;box-sizing:border-box;">' +
                        inner + surahHtml +
                    '</div>';
            }

            return '' +
                '<div class="iqro-row-scroll" style="display:flex;direction:rtl;justify-content:flex-start;align-items:center;' + rowBorder + 'padding:2px 4px;overflow-x:auto;overflow-y:visible;scrollbar-width:thin;">' +
                    '<div style="margin-inline:auto;min-width:0;display:flex;align-items:flex-start;">' +
                        inner + surahHtml +
                    '</div>' +
                '</div>';
        }

        // ===== N kolom =====
        const rowOvfN = wrap ? 'overflow:visible;' : 'overflow-x:auto;scrollbar-width:thin;';
        return '' +
            '<div class="iqro-row-scroll" style="display:flex;direction:rtl;align-items:stretch;' + rowBorder + rowOvfN + '">' +
                (function () {
                    const totalUnits = cells.reduce(function (t, c) {
                        return t + ((c && typeof c === 'object' && c.span) ? c.span : 1);
                    }, 0);
                    const hasNarrow = cells.some(function (c) {
                        return c && typeof c === 'object' && c.narrow;
                    });
                    return cells.map(function (c, i) {
                        const sep   = (i < cells.length - 1) ? 'border-left:1px solid ' + LINE + ';' : '';
                        const isObj = c && typeof c === 'object';
                        const isHl  = isObj && c.hl;
                        const txt   = isHl ? c.hl : (isObj ? c.text : c);
                        const size  = (isObj && c.size) ? c.size : pageSize;

                        const labelBelow = isObj && c.labelPos === 'below';
                        const label = (isObj && c.label)
                            ? '<div style="font-family:\'Amiri\',serif;font-size:13px;color:' + FG + ';opacity:.65;text-align:center;' +
                              (labelBelow ? 'padding-top:2px;padding-bottom:4px;' : 'padding-top:4px;') + 'line-height:1.4;">' + c.label + '</div>'
                            : '';

                        const span  = (isObj && c.span) ? c.span : 1;
                        const bg    = isHl ? 'background:var(--q-accent,#2E86AB);' : '';
                        const isNarrow = isObj && c.narrow;

                        const pecah = !isHl && !isNarrow && bolehPecah(txt, false);

                        let widthStyle;
                        if (isNarrow) {
                            widthStyle = 'flex:0 0 auto;min-width:60px;max-width:90px;';
                        } else if (hasNarrow) {
                            widthStyle = 'flex:1 1 auto;min-width:0;';
                        } else {
                            const pct = (span / totalUnits * 100).toFixed(4) + '%';
                            widthStyle = 'flex:0 0 auto;min-width:' + pct + ';';
                        }

                        const cellInner = labelBelow
                            ? cellHtml(txt, isHl, size, withBulat, titik, noDot, wrap, pecah) + label
                            : label + cellHtml(txt, isHl, size, withBulat, titik, noDot, wrap, pecah);

                        const narrowCls = isNarrow ? ' aisar-narrow' : '';
                        return '<div class="aisar-cell' + narrowCls + '" style="' + widthStyle + 'box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;align-items:center;' +
                            // Padding vertikal ada di SEL (bukan baris) → background badge (hl)
                            // mengisi penuh dari garis atas ke garis bawah. 14px atas tetap
                            // memberi ruang tanda mad (ٓ) seperti di jilid 5.
                            'padding:14px 4px 6px 4px;align-self:stretch;' + bg + sep + '">' +
                            cellInner +
                        '</div>';
                    }).join('');
                })() +
            '</div>';
    }

    // ---- header kolom (teks latin kecil) ----
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
            const isArabJudul = /[\u0600-\u06FF]/.test(judulText) &&
                !/[A-Za-z]/.test(judulText);
            judulHtml = isArabJudul
                ? '<div class="aisar-judul" style="text-align:center;direction:rtl;padding:16px 10px;border-top:1px solid ' + LINE + ';font-family:\'Amiri Quran\',\'Amiri\',serif;font-size:' + AISAR_JUDUL_FONT_SIZE + 'px;font-weight:700;color:' + FG + ';line-height:1.5;">' + judulText + '</div>'
                : '<div style="text-align:center;padding:8px 10px;border-top:1px solid ' + LINE + ';font-family:system-ui;font-size:13px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:' + FG + ';background:var(--q-band,rgba(127,127,127,0.14));">' + judulText + '</div>';
        }

        const rowsHtml = (data.baris || []).map(function (r, i) { return renderRow(r, i, data); }).join('');

        const catatanHtml = data.catatan ? bandHtml(data.catatan) : '';

        const headerHtml = basmallahHtml + judulHtml + instruksiHtml;

        const displayStyle = pageIdx === 0 ? '' : 'display:none;';
        return '' +
            '<div class="iqro-page aisar-page" data-idx="' + pageIdx + '" data-halaman="' + data.halaman + '" style="margin-bottom:8px;' + displayStyle + '">' +
                '<div style="text-align:center;margin-bottom:8px;color:' + FG + ';">' +
                    '<p style="font-size:14px;opacity:.65;font-family:system-ui;">Aisar ' + data.jilid + ' &nbsp;·&nbsp; Halaman ' + data.halaman + '</p>' +
                '</div>' +
                '<div style="border:1.5px solid ' + BORDER + ';margin:8px auto 16px auto;max-width:760px;overflow:visible;border-radius:6px;">' +
                    headerHtml +
                    renderKolomHeader(data.kolomHeader) +
                    rowsHtml +
                    catatanHtml +
                '</div>' +
            '</div>';
    }

    // ==================================================================
    // AUTO-FIT FONT
    // ------------------------------------------------------------------
    // Mengecilkan font tiap WADAH sel (.aisar-fit) sampai muat di lebar
    // SEL-nya sendiri, dengan batas bawah AISAR_MIN_FONT. Jika di batas itu
    // masih meluber, wadah dialihkan ke mode mengalir.
    //
    // DILEWATI untuk sel narrow dan wadah bertanda data-wrap="1".
    // ==================================================================
    function fitOneSpan(span) {
        if (span.closest('.aisar-narrow')) return;

        const syncMarks = function (size) {
            span.querySelectorAll('.aisar-ayah-mark').forEach(function (m) {
                m.style.fontSize = size + 'px';
            });
        };

        // ── Halaman wrap (talqin) ────────────────────────────────────────
        // Ditandai EKSPLISIT lewat data-wrap. JANGAN memakai
        // getComputedStyle(span).display === 'block': anak dari .aisar-cell
        // (display:flex) otomatis di-blockify, sehingga sel non-wrap ikut
        // terdeteksi sebagai wrap lalu turun baris (bug Jilid 2 hlm 29).
        if (span.dataset.wrap === '1') {
            span.style.whiteSpace = 'normal';
            span.style.width      = '100%';
            span.style.lineHeight = '2.1';
            span.style.minHeight  = '0';
            return;
        }

        const cell = span.closest('.aisar-cell');
        const row  = span.closest('.iqro-row-scroll') || cell || span.parentElement;
        if (!row) return;

        const rowCS = window.getComputedStyle(row);
        const rowInner = row.clientWidth
                       - (parseFloat(rowCS.paddingLeft)  || 0)
                       - (parseFloat(rowCS.paddingRight) || 0);

        // Lebar yang benar-benar tersedia untuk SEL ini, bukan seluruh baris.
        let avail = rowInner;
        if (cell && cell.parentElement === row) {
            const mw = cell.style.minWidth || '';
            if (/%$/.test(mw)) {
                // sel grid biasa: jatahnya = persentase kolom
                avail = rowInner * parseFloat(mw) / 100;
            } else {
                // baris dengan badge narrow / blok berlabel: kurangi sel saudara
                Array.prototype.forEach.call(row.children, function (sib) {
                    if (sib !== cell) avail -= sib.offsetWidth;
                });
            }
            const cCS = window.getComputedStyle(cell);
            avail -= (parseFloat(cCS.paddingLeft)     || 0)
                   + (parseFloat(cCS.paddingRight)    || 0)
                   + (parseFloat(cCS.borderLeftWidth) || 0);
        }
        avail -= 4; // toleransi pembulatan
        if (avail <= 0) return;

        let size  = parseFloat(window.getComputedStyle(span).fontSize) || AISAR_SCREEN_FONT_SIZE;
        let guard = 40; // pengaman anti infinite loop

        while (span.scrollWidth > avail && size > AISAR_MIN_FONT && guard-- > 0) {
            size -= 1;
            span.style.fontSize = size + 'px';
            syncMarks(size);
        }

        // Masih meluber di ukuran minimum → berhenti mengecil, biarkan mengalir.
        if (span.scrollWidth > avail) {
            span.style.whiteSpace = 'normal';
            span.style.display    = 'block';
            span.style.width      = '100%';
            span.style.lineHeight = '2.1';
            span.style.minHeight  = '0';
        }
    }

    function aisarAutoFit(root) {
        if (!root) return;
        root.querySelectorAll('.aisar-page').forEach(function (pageEl) {
            const hidden = window.getComputedStyle(pageEl).display === 'none';
            if (hidden) {
                pageEl.style.visibility = 'hidden';
                pageEl.style.display    = 'block';
            }

            pageEl.querySelectorAll('.aisar-fit').forEach(fitOneSpan);

            if (hidden) {
                pageEl.style.display    = 'none';
                pageEl.style.visibility = '';
            }
        });
    }

    // ---- style nav bar (di-inject sekali, theme-aware) ----
    function ensureNavStyle() {
        if (document.getElementById('iqro-nav-style')) return;
        const st = document.createElement('style');
        st.id = 'iqro-nav-style';
        st.textContent =
            // ── LIGHT MODE ──
            '.iqro-nav{background:rgba(245,241,234,0.96) !important;' +
            'box-shadow:0 2px 8px rgba(0,0,0,0.12) !important;}' +
            '.iqro-nav .iqro-prev,.iqro-nav .iqro-next{' +
            'background:#e7e5e4 !important;color:#292524 !important;}' +
            '.iqro-nav .iqro-nav-label{color:#57534e !important;}' +
            // ── DARK MODE ──
            '.dark .iqro-nav{background:rgba(38,36,34,0.96) !important;' +
            'box-shadow:0 2px 8px rgba(0,0,0,0.35) !important;}' +
            '.dark .iqro-nav .iqro-prev,.dark .iqro-nav .iqro-next{' +
            'background:#44403c !important;color:#f5f1ea !important;}' +
            '.dark .iqro-nav .iqro-nav-label{color:#d6d3d1 !important;}';
        document.head.appendChild(st);
    }

    // ---- nav bar (markup sama dgn iqro, tombol pakai iqroNavGo global) ----
    function buildNavBar(pageNumbers) {
        if (pageNumbers.length <= 1) return '';
        ensureNavStyle();
        const btnStyle = 'border:none;border-radius:9999px;' +
                         'width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;' +
                         'font-size:16px;line-height:1;cursor:pointer;font-weight:700;flex:0 0 auto;' +
                         'transition:opacity .15s ease;';
        return '' +
            '<div class="iqro-nav" data-idx="0" data-total="' + pageNumbers.length + '" data-html2canvas-ignore="true"' +
            ' style="position:sticky;top:0;z-index:5;display:flex;align-items:center;justify-content:center;' +
            'gap:16px;padding:7px 12px;border-radius:9999px;' +
            'margin:0 auto 12px auto;width:fit-content;max-width:100%;">' +
                '<button type="button" class="iqro-prev" onclick="iqroNavGo(this,-1)" style="' + btnStyle + 'opacity:0.4;cursor:not-allowed;">←</button>' +
                '<span class="iqro-nav-label" style="font-size:13px;font-weight:700;white-space:nowrap;min-width:110px;text-align:center;">' +
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

        // AUTO-FIT setelah layout; salin ke drawer SETELAH auto-fit.
        const runFit = function () {
            aisarAutoFit(display);

            const drawerDisplay = document.getElementById('quranDisplayDrawer');
            if (drawerDisplay) {
                drawerDisplay.innerHTML = display.innerHTML;
                drawerDisplay.style.setProperty('max-height', 'none', 'important');
                drawerDisplay.style.setProperty('overflow-y', 'visible', 'important');
            }
        };

        // Tunggu Amiri Quran termuat sebelum mengukur.
        if (document.fonts && document.fonts.ready) {
            Promise.resolve(document.fonts.load("34px 'Amiri Quran'"))
                .catch(function () {})
                .then(function () { return document.fonts.ready; })
                .then(function () { requestAnimationFrame(runFit); })
                .catch(function () { requestAnimationFrame(runFit); });
        } else {
            setTimeout(function () { requestAnimationFrame(runFit); }, 400);
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

    // ---- sinkron label "Iqro"/"Aisar" ----
    function getMetodeValue(mode) {
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
        const IQRO_CLS  = ['bg-emerald-600', 'hover:bg-emerald-700'];
        const AISAR_CLS = ['bg-blue-600', 'hover:bg-blue-700'];

        setInterval(() => {
            ['Combined', 'SetoranOnly'].forEach(mode => {
                const isAisar = getMetodeValue(mode).includes('aisar');
                const label   = isAisar ? 'Aisar' : 'Iqro';
                const block   = document.getElementById('iqroBlock' + mode);
                if (!block) return;

                block.querySelectorAll('.lbl-metode-jilid').forEach(el => {
                    if (el.textContent !== label) el.textContent = label;
                });

                const btn = block.querySelector('button[onclick*="loadIqro"]');
                if (btn) {
                    const addCls    = isAisar ? AISAR_CLS : IQRO_CLS;
                    const removeCls = isAisar ? IQRO_CLS  : AISAR_CLS;
                    removeCls.forEach(c => btn.classList.remove(c));
                    addCls.forEach(c => btn.classList.add(c));
                    btn.innerHTML = btn.innerHTML.replace(isAisar ? '📗' : '📘', isAisar ? '📘' : '📗');
                }
            });
        }, 500);
    }

    wrapLoadIqro();
    syncMetodeLabels();

    window.loadAisar        = loadAisar;
    window.renderAisarRange = renderAisarRange;
    window.aisarAutoFit     = aisarAutoFit;

    console.log('✅ aisar-handler.js v1.9 ready (badge penuh setinggi baris)');
})();