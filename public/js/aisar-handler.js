/**
 * AISAR HANDLER v1.7 — renderer khusus metode Aisar.
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
 * GRANULARITAS MARKING (v1.7)
 *
 *   Sebelumnya SATU sel = SATU .word-item, sehingga baris kalimat panjang
 *   (jilid 2–5, terutama halaman talqin) hanya bisa ditandai sekaligus —
 *   guru tidak bisa menunjuk kata mana yang salah.
 *
 *   Sekarang sel dipecah per kata bila:
 *     - baris berisi SATU kolom penuh (kalimat panjang), atau
 *     - teks sel memakai pemisah "•"
 *   Sel grid pendek (2–5 kolom) TIDAK dipecah: di situ satu sel memang
 *   satu unit latihan, memecahnya justru mengaburkan maksud halaman.
 *
 *   Token yang isinya hanya pemisah / tanda akhir ayat (۝٦, ۞, tanda hubung)
 *   dirender pasif — tidak bisa diklik, supaya tidak ikut terhitung sebagai
 *   kesalahan.
 *
 *   PENTING soal CSS: token memakai display:inline, dan wadahnya TIDAK
 *   boleh flex. Inline-block/flex memutus alur teks Arab — spasi antar kata
 *   hilang dan titik pecah baris jadi kacau saat membungkus.
 *
 *   Efek samping: countTotalWords() menghitung jauh lebih banyak unit di
 *   jilid 2–5, sehingga bobot tiap kesalahan pada nilai akhir mengecil di
 *   halaman panjang. Nilai setoran lama & baru tidak sebanding.
 *
 * ------------------------------------------------------------------
 * STRATEGI UKURAN TEKS
 *
 *   1. Halaman ber-`wrap: true` (talqin hlm 40–48) TIDAK di-auto-fit.
 *      Baris di aisar-data.js memang meniru pemenggalan cetakan, tapi
 *      kesesuaian itu TIDAK wajib — yang wajib adalah seluruh teks ayat
 *      muncul UTUH di PDF. Teks dibiarkan mengalir (white-space:normal)
 *      sehingga turun baris sendiri saat kepanjangan.
 *
 *      Riwayat: pernah dicoba memaksa satu baris (white-space:nowrap +
 *      penyusutan font). Hasilnya teks meluber ke KIRI lalu TERPOTONG di
 *      tepi canvas html2canvas. Pengukuran juga tidak dapat diandalkan:
 *      scrollWidth tidak menghitung luapan RTL ke kiri, dan pengukuran
 *      sebelum font Amiri termuat memberi angka yang salah. Ukuran font
 *      per halaman diatur manual lewat opsi `fontSize` di aisar-data.js.
 *
 *   2. Halaman NON-wrap tetap di-auto-fit: font disusutkan sampai batas
 *      AISAR_MIN_FONT (22px). Kalau di 22px masih meluber, font berhenti
 *      dikecilkan dan span dialihkan ke mode mengalir. Alasan: teks Arab
 *      yang menyusut sampai 13px tidak terbaca.
 *
 *   3. Auto-fit mengukur WADAH sel (.aisar-fit), bukan token per kata.
 *      Kalau yang diukur token, tiap kata pasti "muat" sendiri-sendiri
 *      sehingga baris panjang tidak pernah disusutkan dan meluber.
 *
 *   4. Sel `narrow` (nama surat, badge) dilewati auto-fit supaya tidak
 *      ikut menyusut.
 *
 * ------------------------------------------------------------------
 * OPSI PER-HALAMAN (ditulis di aisar-data.js, level objek halaman):
 *   fontSize : number  → ukuran font sel (halaman wrap: ini FINAL)
 *   noDot    : true    → separator "•" dirender sebagai spasi (bukan bulatan)
 *   wrap     : true    → baris jadi blok penuh, teks mengalir turun baris
 *   noLine   : true    → HILANGKAN garis pemisah antar baris (border-top).
 *                        Band divider/judul/instruksi/catatan TIDAK terpengaruh,
 *                        jadi pemisah antar surat tetap ada (cetakan hlm 37–48).
 *   titik    : string  → karakter deco sebelum bulat waqaf
 *   bulatBaris / pola  → baris berbulat waqaf
 *
 * OPSI PER-SEL (objek di dalam array baris):
 *   { text, hl, size, span, narrow, label, labelPos:'below', surah }
 *
 * OPSI BARIS KHUSUS:
 *   { divider: "teks" }  → band pembatas ("" = garis polos)
 *   { blockLabel: "١", rows: [[..],[..]] } → blok berlabel samping
 * ------------------------------------------------------------------
 */
(function () {
    'use strict';

    // ==== UKURAN FONT LAYAR ====
    const AISAR_SCREEN_FONT_SIZE = 34;  // sel baris (default global)
    const AISAR_JUDUL_FONT_SIZE  = 48;  // judul besar (أَبَ)
    const AISAR_BASMALLAH_SIZE   = 26;
    // Batas bawah auto-fit (halaman NON-wrap). Di bawah ini teks Arab sulit
    // dibaca, jadi lebih baik dibiarkan turun baris daripada terus dikecilkan.
    const AISAR_MIN_FONT         = 22;

    const LINE   = 'var(--q-line,rgba(255,255,255,0.3))';
    const FG     = 'var(--q-fg,#fff)';
    const BORDER = 'var(--q-box-border,rgba(255,255,255,0.85))';

    // ---- sel frasa (klik = marking, sama dengan Iqro) ----
    // hl = true → teks putih tebal (background biru ada di DIV sel, bukan di span,
    // supaya siklus marking (markWord) tidak menimpa/menghapus warna highlight)
    //
    // AYAH_MARK_RE: HANYA tanda yang berdiri sendiri —
    //   06D6–06DB = tanda waqaf (ۖ ۗ ۘ ۙ ۚ ۛ)
    //   06DD      = ۝ akhir ayat
    //   06DE      = ۞ rub' el-hizb
    // SENGAJA TIDAK termasuk 06DC (ۜ saktah/sin kecil) dan 06DF–06E8
    // (۟ ۠ ۡ ۢ ۣ dst) karena itu tanda COMBINING yang menempel ke huruf —
    // kalau ikut dibungkus span, sambungan huruf Arab putus & kata terbelah.
    const AYAH_MARK_RE = /([\u06D6-\u06DB\u06DD\u06DE][\u0660-\u0669\d]*)/g;

    // Token yang isinya cuma pemisah / tanda ayat / angka — dirender pasif
    // supaya tidak bisa diklik dan tidak terhitung sebagai unit kesalahan.
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

        // display:inline WAJIB — inline-block/flex memutus alur teks Arab
        // dan menghilangkan spasi antar kata saat baris membungkus.
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
     * splittable = true → sel dipecah per kata, tiap kata jadi unit marking
     * sendiri. Lihat catatan GRANULARITAS MARKING di header file.
     */
    function cellSpan(txt, hl, size, noDot, wrap, splittable) {
        const fontSize = size || AISAR_SCREEN_FONT_SIZE;
        const fgStyle  = hl ? 'color:var(--q-accent-fg,#fff);font-weight:700;' : 'color:' + FG + ';';

        const hasAyahMark = AYAH_MARK_RE.test(String(txt));
        AYAH_MARK_RE.lastIndex = 0; // reset karena regex global dipakai test()
        const extraPad = hasAyahMark ? 'padding-top:22px;padding-bottom:14px;' : 'padding:6px 12px;';
        const minH = wrap ? '0' : '90px';
        const lh   = wrap ? '2.1' : '1.6';

        // ── Sel utuh: satu sel = satu unit marking ────────────────────
        if (!splittable) {
            // pemisah • → bulatan kecil ber-border (default) atau spasi bila noDot
            let disp = noDot
                ? String(txt).replace(/\s*•\s*/g, '&nbsp;&nbsp;')
                : String(txt).replace(/\s*•\s*/g,
                    '<span class="aisar-dot" style="display:inline-block;width:20px;height:20px;' +
                    'border:2px solid currentColor;border-radius:50%;opacity:.6;' +
                    'margin:0 14px;flex:0 0 auto;align-self:center;"></span>');

            // Bungkus tanda waqaf/akhir-ayat BESERTA angkanya (mis. ۝٦) dalam satu
            // span supaya ligature lingkaran+angka tetap utuh & tidak terputus saat wrap.
            const ayahMarkSize = wrap ? fontSize : Math.round(fontSize * 0.90);
            disp = disp.replace(AYAH_MARK_RE,
                '<span class="aisar-ayah-mark" style="display:inline-block;font-size:' + ayahMarkSize + 'px;' +
                'line-height:1;vertical-align:middle;margin:0 4px;white-space:nowrap;">$1</span>');

            // wrap = true → ALUR TEKS BIASA (block), lebar penuh, boleh turun baris.
            // Flexbox + flex-wrap dihindari di sini karena tiap potongan teks jadi
            // flex item → pemutusan baris kacau & kotak menyusut ke lebar konten.
            const boxStyle = wrap
                ? 'display:block;width:100%;white-space:normal;word-break:normal;text-align:center;'
                : 'display:inline-flex;align-items:center;justify-content:center;white-space:nowrap;text-align:center;';

            return '<span class="word-item aisar-fit" data-word="' + txt + '" data-mark-state="0" ' +
                'onclick="markWord(this)" ' +
                'style="' + boxStyle +
                'cursor:pointer;' + extraPad + 'border-radius:8px;min-height:' + minH + ';height:auto;line-height:' + lh + ';' +
                'box-sizing:border-box;overflow:visible;' +
                "font-family:'Amiri Quran','Amiri',serif;font-size:" + fontSize + 'px;' + fgStyle +
                'transition:all 0.25s cubic-bezier(0.4,0,0.2,1);">' + disp + '</span>';
        }

        // ── Sel dipecah per kata ──────────────────────────────────────
        // Wadah TIDAK boleh flex: anak inline akan berubah jadi flex item
        // dan alur teks Arab-nya hancur.
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

        return '<span class="aisar-fit" ' +
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

    // sel "◯"/"○" = lingkaran tunggal; withBulat = titik + bulat di belakang frasa
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

    // ---- band teks (instruksi di bawah judul / pembatas tengah / catatan) ----
    // font-family menyertakan Amiri sebagai fallback agar teks ARAB di dalam
    // catatan/instruksi (mis. "لا", tanda waqaf) ter-shaping dengan benar —
    // system-ui tidak punya tabel shaping Arab sehingga lam-alif putus.
    // line-height 1.9 mencegah baris kedua saling menumpuk.
    function bandHtml(text) {
        return '<div style="border-top:1px solid ' + LINE + ';padding:7px 10px;text-align:center;' +
            "font-family:system-ui,'Amiri Quran','Amiri',serif;font-size:12px;font-weight:700;letter-spacing:.4px;color:" + FG + ';' +
            'opacity:.9;line-height:1.9;background:var(--q-band,rgba(127,127,127,0.14));">' + text + '</div>';
    }

    // ---- baris N kolom, RTL: index 0 = paling kanan, garis pemisah antar kolom ----
    function renderRow(row, rowIdx, page) {
        const isWaqaf   = !!(page && page.pola === 'waqaf');
        const withBulat = isWaqaf ||
            (page && Array.isArray(page.bulatBaris) && page.bulatBaris.indexOf(rowIdx) !== -1);
        const titik    = (page && page.titik !== undefined) ? page.titik : '…';
        const pageSize = (page && page.fontSize) ? page.fontSize : null;
        const noDot    = !!(page && page.noDot);
        const wrap     = !!(page && page.wrap);
        const noLine   = !!(page && page.noLine);
        // Garis pemisah antar baris. Kosong pada halaman ber-noLine (hlm 37–48)
        // supaya tampilannya menyatu seperti cetakan; band divider tetap bergaris.
        const rowBorder = noLine ? '' : 'border-top:1px solid ' + LINE + ';';

        // Dipecah bila: baris 1-kolom penuh, ATAU sel memakai pemisah "•".
        // Sel grid pendek dibiarkan utuh — satu sel = satu unit latihan.
        const bolehPecah = function (t, barisPenuh) {
            const s = String(t);
            if (s === '◯' || s === '○') return false;
            if (/•/.test(s)) return true;
            return barisPenuh && /\s/.test(s.trim());
        };

        // band pembatas di tengah halaman: { divider: "teks" }
        // pakai 'in' supaya { divider: "" } (garis pemisah polos) tetap terdeteksi
        // dan tidak jatuh ke jalur lain → render "undefined"
        if (row && !Array.isArray(row) && typeof row === 'object' && 'divider' in row) {
            return bandHtml(row.divider || '&nbsp;');
        }

        // blok berlabel samping: { blockLabel: "١", rows: [[..],[..],[..]] }
        // label vertikal di sisi KANAN membentang setinggi seluruh rows
        if (row && !Array.isArray(row) && typeof row === 'object' && row.blockLabel) {
            const ovfRow  = wrap ? 'overflow:visible;' : 'overflow-x:auto;scrollbar-width:thin;';
            const ovfCell = wrap ? '' : 'overflow-x:auto;';
            const inner = (row.rows || []).map(function (r, ri) {
                const cells = Array.isArray(r) ? r : [r];
                const top = (ri > 0 && !noLine) ? 'border-top:1px solid ' + LINE + ';' : '';
                // Blok berlabel sering berisi kalimat panjang (mis. daftar saktah
                // jilid 5 hlm 30) → sel satu-satunya di barisnya diperlakukan
                // sebagai baris penuh supaya ikut dipecah per kata.
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
                    // kolom label kanan (sempit, vertikal tengah)
                    '<div style="flex:0 0 72px;max-width:72px;display:flex;justify-content:center;align-items:center;border-left:1px solid ' + LINE + ';">' +
                        cellSpan(row.blockLabel, false, pageSize, noDot, false, false) +
                    '</div>' +
                    // grid isi blok
                    '<div style="flex:1;min-width:0;display:flex;flex-direction:column;">' + inner + '</div>' +
                '</div>';
        }

        const cells = Array.isArray(row) ? row : [row];

        // ===== 1 sel = baris rangkuman penuh =====
        // Dukungan { text, surah } → nama surat kecil menempel setelah nomor ayat.
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

            // WRAP → baris jadi BLOK penuh, bukan flex.
            // Flex membuat anaknya menyusut (flex-shrink:1) meski width:100%,
            // sehingga kotak terlihat sempit & teks terpotong.
            if (wrap) {
                return '' +
                    '<div class="iqro-row-scroll" style="display:block;direction:rtl;' + rowBorder +
                    'padding:6px 10px;overflow:visible;width:100%;box-sizing:border-box;">' +
                        inner + surahHtml +
                    '</div>';
            }

            // Non-wrap: justify flex-start (RTL = mulai kanan) + scroll horizontal.
            // center + overflow membuat ujung kanan terpotong & tak terjangkau scroll.
            return '' +
                '<div class="iqro-row-scroll" style="display:flex;direction:rtl;justify-content:flex-start;align-items:center;' + rowBorder + 'padding:2px 4px;overflow-x:auto;overflow-y:visible;scrollbar-width:thin;">' +
                    '<div style="margin-inline:auto;min-width:0;display:flex;align-items:flex-start;">' +
                        inner + surahHtml +
                    '</div>' +
                '</div>';
        }

        // ===== N kolom =====
        // border-left di semua sel kecuali sel terakhir (paling kiri).
        // sel bisa: "teks" | { hl: "teks" } | { text: "teks", span: 2 }
        // padding-top 14px memberi ruang untuk tanda mad (ٓ) di atas huruf —
        // tanpa itu tanda terpotong karena overflow-x:auto memaksa sumbu-Y ikut auto.
        const rowOvfN = wrap ? 'overflow:visible;' : 'overflow-x:auto;scrollbar-width:thin;';
        return '' +
            '<div class="iqro-row-scroll" style="display:flex;direction:rtl;align-items:stretch;' + rowBorder + rowOvfN + 'padding-top:14px;padding-bottom:6px;">' +
                (function () {
                    // total unit kolom baris ini (span dihitung)
                    const totalUnits = cells.reduce(function (t, c) {
                        return t + ((c && typeof c === 'object' && c.span) ? c.span : 1);
                    }, 0);
                    // baris yang punya sel narrow: sel lain mengisi sisa lebar
                    const hasNarrow = cells.some(function (c) {
                        return c && typeof c === 'object' && c.narrow;
                    });
                    return cells.map(function (c, i) {
                        const sep   = (i < cells.length - 1) ? 'border-left:1px solid ' + LINE + ';' : '';
                        const isObj = c && typeof c === 'object';
                        const isHl  = isObj && c.hl;
                        const txt   = isHl ? c.hl : (isObj ? c.text : c);
                        const size  = (isObj && c.size) ? c.size : pageSize;

                        // label kecil: default DI ATAS, labelPos:'below' → DI BAWAH
                        const labelBelow = isObj && c.labelPos === 'below';
                        const label = (isObj && c.label)
                            ? '<div style="font-family:\'Amiri\',serif;font-size:13px;color:' + FG + ';opacity:.65;text-align:center;' +
                              (labelBelow ? 'padding-top:2px;padding-bottom:4px;' : 'padding-top:4px;') + 'line-height:1.4;">' + c.label + '</div>'
                            : '';

                        const span  = (isObj && c.span) ? c.span : 1;
                        const bg    = isHl ? 'background:var(--q-accent,#2E86AB);' : '';
                        const isNarrow = isObj && c.narrow;

                        // Sel badge (hl) & narrow tidak pernah dipecah — keduanya
                        // penanda, bukan bacaan yang dinilai.
                        const pecah = !isHl && !isNarrow && bolehPecah(txt, false);

                        let widthStyle;
                        if (isNarrow) {
                            // class aisar-narrow dipakai auto-fit untuk melewati sel ini
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
                        return '<div class="aisar-cell' + narrowCls + '" style="' + widthStyle + 'box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;align-items:center;padding:2px 4px;' + bg + sep + '">' +
                            cellInner +
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
            const isArabJudul = /[\u0600-\u06FF]/.test(judulText) &&
                !/[A-Za-z]/.test(judulText); // campuran (mis. "HUKUM ... (نْ) KE DALAM (ي)") = Latin
            judulHtml = isArabJudul
                ? '<div class="aisar-judul" style="text-align:center;direction:rtl;padding:16px 10px;border-top:1px solid ' + LINE + ';font-family:\'Amiri Quran\',\'Amiri\',serif;font-size:' + AISAR_JUDUL_FONT_SIZE + 'px;font-weight:700;color:' + FG + ';line-height:1.5;">' + judulText + '</div>'
                : '<div style="text-align:center;padding:8px 10px;border-top:1px solid ' + LINE + ';font-family:system-ui;font-size:13px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:' + FG + ';background:var(--q-band,rgba(127,127,127,0.14));">' + judulText + '</div>';
        }

        const rowsHtml = (data.baris || []).map(function (r, i) { return renderRow(r, i, data); }).join('');

        const catatanHtml = data.catatan ? bandHtml(data.catatan) : '';

        // Urutan header SESUAI CETAKAN: basmallah dulu, baru band judul, lalu
        // instruksi. Halaman tanpa basmallah tidak terpengaruh (string kosong).
        const headerHtml = basmallahHtml + judulHtml + instruksiHtml;

        const displayStyle = pageIdx === 0 ? '' : 'display:none;';
        // class iqro-page DIPERTAHANKAN → navigasi & PDF capture existing tetap jalan.
        // overflow:visible pada kotak halaman → isi yang melebihi tinggi tidak
        // dipotong saat html2canvas merekam (penyebab baris hilang di PDF).
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
    // Mengukur lebar nyata tiap WADAH sel (.aisar-fit) terhadap lebar kotak
    // barisnya, lalu mengecilkan font SAMPAI BATAS AISAR_MIN_FONT. Jika di
    // batas itu masih meluber, font berhenti dikecilkan dan wadah dialihkan
    // ke mode mengalir (turun baris) — bukan terus menyusut sampai tak terbaca.
    //
    // Yang diukur WADAH, bukan token per kata: tiap kata pasti "muat"
    // sendiri-sendiri, sehingga baris panjang tidak akan pernah disusutkan
    // kalau pengukurannya per token.
    //
    // DILEWATI untuk:
    //   - sel narrow (nama surat / badge)
    //   - wadah halaman `wrap` (talqin) → lihat catatan di dalam fitOneSpan
    //
    // Halaman tersembunyi (display:none) punya clientWidth 0, jadi
    // ditampilkan sementara (visibility:hidden) saat pengukuran.
    // ==================================================================
    function fitOneSpan(span) {
        // sel narrow (nama surat, badge) jangan diutak-atik
        if (span.closest('.aisar-narrow')) return;

        // Font token mengikuti wadah (inherit), jadi cukup ubah wadahnya.
        // Fungsi ini menyesuaikan .aisar-ayah-mark di dalamnya agar proporsional.
        const syncMarks = function (size) {
            span.querySelectorAll('.aisar-ayah-mark').forEach(function (m) {
                m.style.fontSize = size + 'px';
            });
        };

        // ── Halaman wrap (talqin hlm 40–48) ──────────────────────────────
        // TIDAK di-auto-fit. Baris di aisar-data.js memang meniru pemenggalan
        // cetakan, tapi kesesuaian itu tidak wajib — yang wajib adalah SELURUH
        // teks ayat muncul utuh di PDF. Membiarkan teks mengalir (turun baris
        // sendiri) menjamin itu; memaksa satu baris lewat white-space:nowrap
        // justru membuat teks meluber ke kiri lalu TERPOTONG di tepi canvas.
        // Ukuran font halaman ini diatur manual lewat opsi `fontSize`.
        if (window.getComputedStyle(span).display === 'block') {
            span.style.whiteSpace = 'normal';
            span.style.width      = '100%';
            span.style.lineHeight = '2.1';
            span.style.minHeight  = '0';
            return;
        }

        const row = span.closest('.iqro-row-scroll') ||
                    span.closest('.aisar-cell') ||
                    span.parentElement;
        if (!row) return;

        // Lebar isi kotak baris = clientWidth dikurangi padding kiri-kanan
        // baris itu sendiri, plus sedikit toleransi anti-pembulatan.
        const rowCS = window.getComputedStyle(row);
        const avail = row.clientWidth
                    - (parseFloat(rowCS.paddingLeft)  || 0)
                    - (parseFloat(rowCS.paddingRight) || 0)
                    - 4;
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
            // halaman tersembunyi tidak punya dimensi → tampilkan sementara
            if (hidden) {
                pageEl.style.visibility = 'hidden';
                pageEl.style.display    = 'block';
            }

            // .aisar-fit = WADAH sel (baik yang utuh maupun yang dipecah).
            // Jangan pakai .word-item: setelah pemecahan per kata, itu token.
            pageEl.querySelectorAll('.aisar-fit').forEach(fitOneSpan);

            if (hidden) {
                pageEl.style.display    = 'none';
                pageEl.style.visibility = '';
            }
        });
    }

        // ---- style nav bar (di-inject sekali, theme-aware) ----
    // Warna TIDAK lagi inline/var(--q-nav-*): variabel itu didefinisikan CSS tema
    // dengan nilai slate/biru, dan kalau di-hardcode gelap nav ikut gelap di light
    // mode. Aturan .dark membuat warna mengikuti toggle tema secara live.
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
        // Inline style HANYA untuk layout. Semua WARNA diserahkan ke
        // #iqro-nav-style supaya bisa berbeda antara light & dark mode.
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

        // AUTO-FIT dijalankan setelah browser menghitung layout.
        // Penyalinan ke drawer DIPINDAH ke dalam callback ini supaya yang
        // tersalin adalah HTML yang SUDAH disesuaikan — kalau di luar,
        // drawer mendapat versi sebelum auto-fit.
        const runFit = function () {
            aisarAutoFit(display);

            const drawerDisplay = document.getElementById('quranDisplayDrawer');
            if (drawerDisplay) {
                drawerDisplay.innerHTML = display.innerHTML;
                drawerDisplay.style.setProperty('max-height', 'none', 'important');
                drawerDisplay.style.setProperty('overflow-y', 'visible', 'important');
            }
        };

        // Menunggu Amiri Quran benar-benar termuat sebelum mengukur.
        // requestAnimationFrame menyala hanya beberapa ms setelah innerHTML
        // diisi — saat itu browser masih memakai font fallback (serif) yang
        // metrik lebarnya jauh berbeda, sehingga auto-fit halaman non-wrap
        // bisa salah menyusutkan.
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

                // CATATAN: judul "Pilih Halaman <span.lbl-metode-jilid>" SUDAH
                // ditangani oleh loop di atas. Dulu di sini ada kode yang menulis
                // ulang node teks <p> menjadi "Pilih Halaman Aisar" — padahal
                // span-nya masih ada di belakang, sehingga muncul "Aisar" DUA KALI.
                // Kode itu peninggalan sebelum span .lbl-metode-jilid ada di markup.

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
    window.aisarAutoFit     = aisarAutoFit;   // bisa dipanggil ulang manual

    console.log('✅ aisar-handler.js v1.7 ready (marking per kata di baris panjang)');
})();