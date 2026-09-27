import{p as z,o as E,H as O}from"./app-BMNQg8WS.js";import{G as T}from"./GuruAuthenticatedLayout-Ds-c1d6V.js";import"./AppLogo-CEE6WDXD.js";import"./LaporanNotifier-CNrig2bt.js";const H=["complete-quran-mapping.js","complete-quran-mapping-wrapper.js","auto-fill-page.js","page-juz-calculator.js","mode-handler.js","quran-api.js","setoran-interactive.js","iqro-data.js","aisar-data.js","iqro-handler.js","aisar-handler.js","audio-recorder.js","unified-input.js"],I="unified-input-page.js",$=s=>s.map(a=>`<option value="${a}">${a}</option>`).join(""),F=s=>s.map(a=>`<option value="${a}">${a}</option>`).join(""),R=s=>s.map(a=>`<option value="${a.id}" data-target-metode="${a.target_metode||"quran"}" data-target-metode-2="${a.target_metode_2||""}" data-target-jilid="${a.target_jilid??""}">${a.name}</option>`).join(""),J=s=>s.map(a=>`<option value="${a.id}">${a.name}</option>`).join("");function L({santriList:s,sesiList:a,daftarSurat:C,tanggalHariIni:b,kelasName:f,tahunAjaran:w,csrf:q,flash:i,errors:M}){const m=$(C),k=F(a),h=R(s),S=J(s),v=`<input type="hidden" name="_token" value="${q}">`,x=Object.values(M||{}).flat(),_=`
    ${i!=null&&i.success?`
    <div class="success-notification fixed top-3 right-3 left-3 sm:left-auto z-[300] sm:max-w-sm animate-fade-in">
        <div class="bg-emerald-600 text-white px-4 py-3 rounded-xl shadow-2xl border border-emerald-500/30 flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="flex-1 min-w-0"><p class="font-medium text-sm">${i.success}</p></div>
            <button type="button" class="close-notification flex-shrink-0 opacity-70 hover:opacity-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
    </div>`:""}
    ${i!=null&&i.error?`
    <div class="error-notification fixed top-3 right-3 left-3 sm:left-auto z-[300] sm:max-w-sm animate-fade-in">
        <div class="bg-red-600 text-white px-4 py-3 rounded-xl shadow-2xl border border-red-500/30 flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="flex-1 min-w-0"><p class="font-medium text-sm">${i.error}</p></div>
            <button type="button" class="close-notification flex-shrink-0 opacity-70 hover:opacity-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
    </div>`:""}
    ${i!=null&&i.warning?`
    <div class="warning-notification fixed top-3 right-3 left-3 sm:left-auto z-[300] sm:max-w-sm animate-fade-in">
        <div class="bg-amber-500 text-white px-4 py-3 rounded-xl shadow-2xl border border-amber-400/30 flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div class="flex-1 min-w-0"><p class="font-medium text-sm">${i.warning}</p></div>
            <button type="button" class="close-notification flex-shrink-0 opacity-70 hover:opacity-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
    </div>`:""}
    ${x.length?`
    <div class="error-notification fixed top-3 right-3 left-3 sm:left-auto z-[300] sm:max-w-sm animate-fade-in">
        <div class="bg-red-600 text-white px-4 py-3 rounded-xl shadow-2xl border border-red-500/30">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-sm mb-1">Terdapat kesalahan:</p>
                    <ul class="text-xs space-y-0.5 opacity-90">${x.map(j=>`<li>• ${j}</li>`).join("")}</ul>
                </div>
                <button type="button" class="close-notification flex-shrink-0 opacity-70 hover:opacity-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
        </div>
    </div>`:""}`,A=w?`
        <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-800/40 rounded-xl text-xs mt-2">
            <svg class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span class="text-gray-900 dark:text-white font-medium">${w}</span>
            <span class="px-1.5 py-0.5 bg-emerald-500/20 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 rounded-full text-[10px] font-semibold">Aktif</span>
        </div>`:"";return`
<div id="inputPage" class="min-h-screen">
    ${_}

    <div id="mobileDrawerOverlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[150] hidden lg:hidden" onclick="closeDrawer()"></div>
    <div id="mobileDrawer" class="fixed bottom-0 left-0 right-0 z-[160] lg:hidden translate-y-full transition-transform duration-300 ease-out">
        <div class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700/50 rounded-t-2xl shadow-2xl max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700/50 flex-shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-1 bg-gray-300 dark:bg-gray-600 rounded-full mx-auto absolute left-1/2 -translate-x-1/2 top-2"></div>
                    <span id="drawerTitle" class="font-semibold text-gray-900 dark:text-white text-sm">📊 Presensi Hari Ini</span>
                </div>
                <button onclick="closeDrawer()" class="w-7 h-7 rounded-full bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div id="drawerContent" class="overflow-y-auto flex-1 p-4"></div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto">
        <div class="mb-4 sm:mb-6">
            <h1 class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white leading-tight">Input Presensi &amp; Setoran</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Kelas: <span class="text-indigo-600 dark:text-indigo-400 font-medium">${f}</span>
                <span class="mx-1.5 opacity-30">|</span>
                <span class="text-gray-700 dark:text-gray-300">${b}</span>
            </p>
            ${A}
        </div>

        <div class="lg:grid lg:grid-cols-5 lg:gap-6">
            <div class="lg:col-span-2">
                <div class="lg:sticky lg:top-4 space-y-3">

                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-3 sm:p-4 shadow-sm dark:shadow-none">
                        <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2.5 flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-stone-400 dark:text-stone-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/></svg> Pilih Mode Input</p>
                        <div class="grid grid-cols-3 gap-1.5 sm:gap-2">
                            <label class="mode-card cursor-pointer">
                                <input type="radio" name="input_mode" value="combined" class="sr-only" onclick="toggleMode(this)" onchange="switchMode(this.value)">
                                <div class="mode-card-inner border-2 border-claude-200 dark:border-claude-500/20 bg-claude-50 dark:bg-claude-500/5 rounded-lg p-2 sm:p-3 text-center transition-all hover:border-claude-400 hover:bg-claude-100/60 dark:hover:bg-claude-500/10">
                                    <div class="mb-1 flex justify-center text-claude-600 dark:text-claude-400"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" d="M12 2v2M12 20v2M2 12h2M20 12h2"/></svg></div>
                                    <div class="text-[10px] sm:text-xs font-semibold text-gray-700 dark:text-gray-300 leading-tight">Presensi<br>+ Setoran</div>
                                </div>
                            </label>
                            <label class="mode-card cursor-pointer">
                                <input type="radio" name="input_mode" value="presensi_only" class="sr-only" onclick="toggleMode(this)" onchange="switchMode(this.value)">
                                <div class="mode-card-inner border-2 border-blue-200 dark:border-blue-500/20 bg-blue-50 dark:bg-blue-500/5 rounded-lg p-2 sm:p-3 text-center transition-all hover:border-blue-400 hover:bg-blue-100/60 dark:hover:bg-blue-500/10">
                                    <div class="mb-1 flex justify-center text-blue-600 dark:text-blue-400"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                                    <div class="text-[10px] sm:text-xs font-semibold text-gray-700 dark:text-gray-300 leading-tight">Presensi<br>Saja</div>
                                </div>
                            </label>
                            <label class="mode-card cursor-pointer">
                                <input type="radio" name="input_mode" value="setoran_only" class="sr-only" onclick="toggleMode(this)" onchange="switchMode(this.value)">
                                <div class="mode-card-inner border-2 border-emerald-200 dark:border-emerald-500/20 bg-emerald-50 dark:bg-emerald-500/5 rounded-lg p-2 sm:p-3 text-center transition-all hover:border-emerald-400 hover:bg-emerald-100/60 dark:hover:bg-emerald-500/10">
                                    <div class="mb-1 flex justify-center text-emerald-600 dark:text-emerald-400"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13"/></svg></div>
                                    <div class="text-[10px] sm:text-xs font-semibold text-gray-700 dark:text-gray-300 leading-tight">Setoran<br>Tambahan</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div id="hintBelumPilih" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-8 shadow-sm dark:shadow-none flex flex-col items-center justify-center text-center">
                        <div class="w-12 h-12 bg-cream-200 dark:bg-coal-600 rounded-xl flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-stone-400 dark:text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/></svg>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Pilih mode input di atas</p>
                        <p class="text-xs text-gray-500 dark:text-gray-600 mt-1">Form akan muncul di sini</p>
                    </div>

                    <hr class="border-gray-200 dark:border-gray-800 hidden" id="hrDivider">

                    <form id="formCombined" action="/guru/input/store-combined" method="POST" enctype="multipart/form-data" novalidate class="hidden space-y-3">
                        ${v}
                        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm dark:shadow-none">
                            <h3 class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-cream-200 dark:bg-coal-600 text-stone-500 dark:text-stone-400 flex items-center justify-center"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg></span>
                                Informasi Dasar
                            </h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Nama Santri <span class="text-red-500 dark:text-red-400">*</span></label>
                                    <select name="santri_id" id="santriSelectCombined" required class="form-select w-full">
                                        <option value="">-- Pilih Santri --</option>
                                        ${h}
                                    </select>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-600 italic mt-1.5">💡 Pilih nama santri dulu untuk menampilkan data setoran.</p>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Tanggal <span class="text-red-500 dark:text-red-400">*</span></label>
                                        <input type="date" name="tanggal" id="tanggalCombined" value="${b}" required class="form-input w-full">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Sesi <span class="text-red-500 dark:text-red-400">*</span></label>
                                        <select name="sesi" id="sesiSelectCombined" required class="form-select w-full">
                                            <option value="">-- Pilih --</option>
                                            ${k}
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="status" value="Hadir">

                        <div class="combined-after-santri hidden bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
                            <button type="button" onclick="toggleRiwayatSetoran('Combined')" class="w-full flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                                <span class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-cream-200 dark:bg-coal-600 text-stone-500 dark:text-stone-400 flex items-center justify-center"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                                    Riwayat Setoran
                                </span>
                                <svg id="riwayatIconCombined" class="w-4 h-4 text-gray-500 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div id="riwayatBodyCombined" class="hidden px-4 pb-4 border-t border-gray-200 dark:border-gray-800 pt-3">
                                <div class="flex flex-wrap gap-1.5 mb-2.5" id="riwayatFilterCombined">
                                    <button type="button" class="rs-chip is-active" data-f="">Semua</button>
                                    <button type="button" class="rs-chip" data-f="Hafalan">Hafalan</button>
                                    <button type="button" class="rs-chip" data-f="Murajaah">Murajaah</button>
                                    <button type="button" class="rs-chip" data-f="Tilawah">Tilawah</button>
                                </div>
                                <div id="riwayatListCombined" class="space-y-1.5 max-h-72 overflow-y-auto custom-scrollbar pr-1"></div>
                            </div>
                        </div>

                        <div class="combined-after-santri hidden bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm dark:shadow-none">
                            <h3 class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-cream-200 dark:bg-coal-600 text-stone-500 dark:text-stone-400 flex items-center justify-center"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13"/></svg></span>
                                Data Setoran
                            </h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Metode <span class="text-red-500 dark:text-red-400">*</span></label>
                                    <div class="metode-field">
                                        <span class="metode-dot" id="metodeDotCombined" data-metode="quran"></span>
                                        <select name="metode" id="metodeCombined" required class="form-select w-full has-dot">
                                            <option value="Al-Qur'an">Al-Qur'an</option>
                                            <option value="Iqro">Iqro</option>
                                            <option value="Aisar">Aisar</option>
                                        </select>
                                    </div>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-600 italic mt-1.5">🔒 Otomatis terisi sesuai target santri.</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Kategori <span class="text-red-500 dark:text-red-400">*</span></label>
                                    <select name="kategori_setoran" id="kategoriSetoranCombined" required class="form-select w-full">
                                        <option value="">-- Pilih --</option>
                                        <option value="Hafalan">📚 Hafalan</option>
                                        <option value="Murajaah">🔄 Murajaah</option>
                                        <option value="Tilawah">📖 Tilawah</option>
                                    </select>
                                </div>
                                <div id="rangeAyatBlockCombined">
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700/50 rounded-lg p-3">
                                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253"/></svg> Range Ayat</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-[10px] text-gray-500 mb-1 block">Surat Mulai *</label>
                                            <select name="surat_start" id="suratStartCombined" required class="form-select-sm w-full">
                                                <option value="">-- Pilih --</option>
                                                ${m}
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-[10px] text-gray-500 mb-1 block">Ayat Mulai *</label>
                                            <select name="ayat_start" id="ayatStartCombined" disabled required class="form-select-sm w-full">
                                                <option value="">-- Pilih Surat --</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-[10px] text-gray-500 mb-1 block">Surat Selesai *</label>
                                            <select name="surat_end" id="suratEndCombined" required class="form-select-sm w-full">
                                                <option value="">-- Otomatis --</option>
                                                ${m}
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-[10px] text-gray-500 mb-1 block">Ayat Selesai *</label>
                                            <select name="ayat_end" id="ayatEndCombined" disabled required class="form-select-sm w-full">
                                                <option value="">-- Pilih Ayat --</option>
                                            </select>
                                        </div>
                                    </div>
                                    <button type="button" onclick="setQuranPanelTitle('quran'); loadQuranCombined(); if(window.innerWidth < 1024) openDrawer('quran');"
                                            class="mt-2.5 w-full px-3 py-2 bg-claude-500 hover:bg-claude-600 text-white text-xs font-medium rounded-lg transition flex items-center justify-center gap-1.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253"/></svg> Tampilkan Al-Qur'an</button>
                                </div>
                                </div>

                                <div id="iqroBlockCombined" class="hidden bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700/50 rounded-lg p-3">
                                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5A2.5 2.5 0 016.5 17H20M4 19.5A2.5 2.5 0 006.5 22H20V2H6.5A2.5 2.5 0 004 4.5z"/></svg> Pilih Halaman <span class="lbl-metode-jilid">Iqro</span></p>
                                    <div class="mb-2">
                                        <label class="text-[10px] text-gray-500 mb-1 block">Jilid *</label>
                                        <select id="iqroJilidCombined" class="form-select-sm w-full">
                                            <option value="1">Jilid 1</option><option value="2">Jilid 2</option><option value="3">Jilid 3</option>
                                            <option value="4">Jilid 4</option><option value="5">Jilid 5</option><option value="6">Jilid 6</option>
                                        </select>
                                        <p class="text-[10px] text-gray-500 dark:text-gray-600 italic mt-1">🔒 Otomatis terisi dari target santri.</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-[10px] text-gray-500 mb-1 block">Halaman Mulai *</label>
                                            <select id="iqroHalamanStartCombined" class="form-select-sm w-full"><option value="">-- Mulai --</option></select>
                                        </div>
                                        <div>
                                            <label class="text-[10px] text-gray-500 mb-1 block">Halaman Selesai *</label>
                                            <select id="iqroHalamanEndCombined" class="form-select-sm w-full"><option value="">-- Selesai --</option></select>
                                        </div>
                                    </div>
                                    <button type="button" id="btnJilidCombined" data-metode="iqro" onclick="setQuranPanelTitle('iqro'); loadIqro('Combined'); if(window.innerWidth < 1024) openDrawer('quran');"
                                            class="btn-jilid mt-2.5 w-full px-3 py-2 text-white text-xs font-medium rounded-lg transition flex items-center justify-center gap-1.5">
                                        <span>Tampilkan Halaman <span class="lbl-metode-jilid">Iqro</span></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="page_start" id="pageStartCombined">
                        <input type="hidden" name="page_end" id="pageEndCombined">
                        <input type="hidden" name="page_start_portion" id="pageStartPortionCombined">
                        <input type="hidden" name="page_end_portion" id="pageEndPortionCombined">
                        <input type="hidden" name="total_page" id="totalPageCombined">
                        <input type="hidden" name="juz_start" id="juzStartCombined">
                        <input type="hidden" name="juz_end" id="juzEndCombined">
                        <input type="hidden" name="total_juz" id="totalJuzCombined">
                        <input type="hidden" name="total_kata" id="totalKataCombined">
                        <input type="hidden" name="kesalahan_kelancaran" id="kesalahanKelancaranCombined" value="0">
                        <input type="hidden" name="kesalahan_tajwid" id="kesalahanTajwidCombined" value="0">
                        <input type="hidden" name="kesalahan_pelafalan" id="kesalahanPelafalanCombined" value="0">
                        <input type="hidden" name="nilai" id="nilaiCombined">
                        <input type="hidden" name="pdf_html_content" id="pdfHtmlDataCombined">
                        <input type="hidden" name="iqro_jilid" id="iqroJilidHiddenCombined">
                        <input type="hidden" name="iqro_halaman" id="iqroHalamanHiddenCombined">
                        <input type="hidden" name="iqro_halaman_end" id="iqroHalamanEndHiddenCombined">

                        <div class="combined-after-santri hidden mobile-hide-audio bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
                            <div class="flex items-center p-4 border-b border-gray-200 dark:border-gray-800">
                                <span class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-cream-200 dark:bg-coal-600 text-stone-500 dark:text-stone-400 flex items-center justify-center"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-14 0m7 7v3m-4 0h8m-4-6a3 3 0 01-3-3V5a3 3 0 016 0v4a3 3 0 01-3 3z"/></svg></span>
                                    Audio &amp; Penilaian
                                </span>
                            </div>
                            <div id="sectionAudioCombined" class="px-4 pb-4 pt-3 space-y-3">
                                <div class="flex gap-2">
                                    <button type="button" id="startRecordingCombined" onclick="startRecording('Combined')" class="inline-flex flex-1 items-center justify-center gap-1.5 py-2.5 bg-red-600 hover:bg-red-500 text-white text-xs font-semibold rounded-xl shadow-sm transition active:scale-95"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="6"/></svg> Rekam</button>
                                    <button type="button" id="pauseRecordingCombined" onclick="pauseRecording('Combined')" class="hidden inline-flex flex-1 items-center justify-center gap-1.5 py-2.5 bg-amber-100 hover:bg-amber-200 text-amber-800 ring-1 ring-amber-200 text-xs font-semibold rounded-xl transition active:scale-95 dark:bg-amber-500/15 dark:hover:bg-amber-500/25 dark:text-amber-300 dark:ring-amber-500/25"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><rect x="8" y="6" width="3.2" height="12" rx="1"/><rect x="12.8" y="6" width="3.2" height="12" rx="1"/></svg> Jeda</button>
                                    <button type="button" id="stopRecordingCombined" onclick="stopRecording('Combined')" class="hidden inline-flex flex-1 items-center justify-center gap-1.5 py-2.5 bg-stone-700 hover:bg-stone-600 text-white text-xs font-semibold rounded-xl shadow-sm transition active:scale-95 dark:bg-coal-600 dark:hover:bg-coal-500 dark:ring-1 dark:ring-coal-500"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><rect x="7" y="7" width="10" height="10" rx="2"/></svg> Stop</button>
                                </div>
                                <div id="recordingTimerCombined" class="hidden text-xs font-bold text-blue-600 dark:text-blue-400 text-center py-2 bg-blue-50 dark:bg-blue-950/50 rounded-lg border border-blue-200 dark:border-blue-800/50">⏱️ Durasi: 0:00</div>
                                <audio id="audioPlaybackCombined" controls class="w-full hidden rounded-lg"></audio>
                                <button type="button" id="clearRecordingCombined" onclick="clearRecording('Combined')" class="w-full py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-medium rounded-lg transition hidden flex items-center justify-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M4 7h16m-3 0V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3"/></svg> Hapus & Rekam Ulang</button>
                                <input type="hidden" name="audio_data" id="audioDataCombined">
                                <p class="text-[10px] text-gray-500 dark:text-gray-600 italic">💡 Gunakan Jeda untuk berhenti sementara</p>
                                <div class="grid grid-cols-2 gap-2 pt-1">
                                    <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-2.5 flex items-center justify-between col-span-2"><span class="text-xs text-gray-600 dark:text-gray-400">🟢 Kelancaran</span><strong id="displayKelancaranCombined" class="text-base text-emerald-600 dark:text-emerald-400">0</strong></div>
                                    <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-2.5 flex items-center justify-between"><span class="text-xs text-gray-600 dark:text-gray-400">🟡 Tajwid</span><strong id="displayTajwidCombined" class="text-base text-amber-600 dark:text-amber-400">0</strong></div>
                                    <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-2.5 flex items-center justify-between"><span class="text-xs text-gray-600 dark:text-gray-400">🔴 Pelafalan</span><strong id="displayPelafalanCombined" class="text-base text-red-600 dark:text-red-400">0</strong></div>
                                    <div class="bg-indigo-50 dark:bg-indigo-950 border border-indigo-200 dark:border-indigo-800/50 rounded-lg p-2.5 flex items-center justify-between col-span-2"><span class="text-xs font-bold text-gray-700 dark:text-gray-300 flex items-center gap-1.5">Nilai</span><strong id="displayNilaiCombined" class="text-2xl text-indigo-600 dark:text-indigo-400">100</strong></div>
                                </div>
                            </div>
                        </div>

                        <div class="combined-after-santri hidden mobile-hide-bottom bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm dark:shadow-none">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828z"/></svg> Catatan Guru</label>
                            <textarea name="catatan_guru" id="catatanGuruCombined" rows="2" class="form-textarea w-full" placeholder="Tambahkan catatan..."></textarea>
                        </div>

                        <button type="submit" id="submitBtnCombined" class="combined-after-santri hidden mobile-hide-bottom w-full py-3 bg-gradient-to-r from-claude-500 to-claude-600 hover:from-claude-600 hover:to-claude-700 text-white font-semibold text-sm rounded-xl shadow-lg shadow-claude-500/25 transition flex items-center justify-center gap-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-8 0V4a1 1 0 011-1h6a1 1 0 011 1v3m-8 0h8M9 21v-6h6v6"/></svg> Simpan Presensi &amp; Setoran</button>
                    </form>

                    <form id="formPresensiOnly" action="/guru/input/store-presensi" method="POST" class="hidden space-y-3">
                        ${v}
                        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm dark:shadow-none">
                            <h3 class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-cream-200 dark:bg-coal-600 text-stone-500 dark:text-stone-400 flex items-center justify-center"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                                Presensi Saja
                            </h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Nama Santri <span class="text-red-500 dark:text-red-400">*</span></label>
                                    <select name="santri_id" required class="form-select w-full">
                                        <option value="">-- Pilih Santri --</option>
                                        ${S}
                                    </select>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-600 italic mt-1.5">💡 Pilih nama santri terlebih dahulu untuk menampilkan data setoran.</p>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Tanggal <span class="text-red-500 dark:text-red-400">*</span></label>
                                        <input type="date" name="tanggal" value="${b}" required class="form-input w-full">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Sesi <span class="text-red-500 dark:text-red-400">*</span></label>
                                        <select name="sesi" required class="form-select w-full">
                                            <option value="">-- Pilih --</option>
                                            ${k}
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Status Kehadiran <span class="text-red-500 dark:text-red-400">*</span></label>
                                    <div class="grid grid-cols-2 gap-1.5">
                                        <label class="status-radio-label cursor-pointer"><input type="radio" name="status" value="Hadir" required class="sr-only"><div class="status-radio-inner border-2 border-gray-300 dark:border-gray-700 rounded-lg p-2.5 text-center hover:border-emerald-500/50 transition text-xs font-medium text-gray-700 dark:text-gray-300 flex items-center justify-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background-color:#10b981;"></span>Hadir</div></label>
                                        <label class="status-radio-label cursor-pointer"><input type="radio" name="status" value="Sakit" class="sr-only"><div class="status-radio-inner border-2 border-gray-300 dark:border-gray-700 rounded-lg p-2.5 text-center hover:border-orange-500/50 transition text-xs font-medium text-gray-700 dark:text-gray-300 flex items-center justify-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background-color:#f97316;"></span>Sakit</div></label>
                                        <label class="status-radio-label cursor-pointer"><input type="radio" name="status" value="Izin" class="sr-only"><div class="status-radio-inner border-2 border-gray-300 dark:border-gray-700 rounded-lg p-2.5 text-center hover:border-amber-500/50 transition text-xs font-medium text-gray-700 dark:text-gray-300 flex items-center justify-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background-color:#f59e0b;"></span>Izin</div></label>
                                        <label class="status-radio-label cursor-pointer"><input type="radio" name="status" value="Alpha" class="sr-only"><div class="status-radio-inner border-2 border-gray-300 dark:border-gray-700 rounded-lg p-2.5 text-center hover:border-red-500/50 transition text-xs font-medium text-gray-700 dark:text-gray-300 flex items-center justify-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background-color:#ef4444;"></span>Alpha</div></label>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">📝 Keterangan <span class="text-gray-500 dark:text-gray-600 text-[10px]">(Opsional)</span></label>
                                    <textarea name="keterangan" rows="2" class="form-textarea w-full" placeholder="Contoh: Demam tinggi..."></textarea>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="w-full py-3 bg-gradient-to-r from-claude-500 to-claude-600 hover:from-claude-600 hover:to-claude-700 text-white font-semibold text-sm rounded-xl shadow-lg shadow-claude-500/25 transition flex items-center justify-center gap-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-8 0V4a1 1 0 011-1h6a1 1 0 011 1v3m-8 0h8M9 21v-6h6v6"/></svg> Simpan Presensi</button>
                    </form>

                    <form id="formSetoranOnly" action="/guru/input/store-setoran" method="POST" enctype="multipart/form-data" novalidate class="hidden space-y-3">
                        ${v}
                        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm dark:shadow-none">
                            <h3 class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-cream-200 dark:bg-coal-600 text-stone-500 dark:text-stone-400 flex items-center justify-center"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13"/></svg></span>
                                Setoran Tambahan
                            </h3>
                            <div class="mb-3 px-3 py-2 bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800/30 rounded-lg">
                                <p class="text-[10px] text-amber-700 dark:text-amber-300">⚠️ Santri harus sudah presensi <strong>HADIR</strong> di sesi yang sama.</p>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Nama Santri <span class="text-red-500 dark:text-red-400">*</span></label>
                                    <select name="santri_id" id="santriSelectSetoranOnly" required class="form-select w-full">
                                        <option value="">-- Pilih Santri --</option>
                                        ${h}
                                    </select>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-600 italic mt-1.5">💡 Pilih nama santri dulu untuk menampilkan data setoran.</p>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Tanggal <span class="text-red-500 dark:text-red-400">*</span></label>
                                        <input type="date" name="tanggal" id="tanggalSetoranOnly" value="${b}" required class="form-input w-full">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Sesi <span class="text-red-500 dark:text-red-400">*</span></label>
                                        <select name="sesi" id="sesiSelectSetoranOnly" required class="form-select w-full">
                                            <option value="">-- Pilih --</option>
                                            ${k}
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="setoran-after-santri hidden bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
                            <button type="button" onclick="toggleRiwayatSetoran('SetoranOnly')" class="w-full flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                                <span class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-cream-200 dark:bg-coal-600 text-stone-500 dark:text-stone-400 flex items-center justify-center"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                                    Riwayat Setoran
                                </span>
                                <svg id="riwayatIconSetoranOnly" class="w-4 h-4 text-gray-500 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div id="riwayatBodySetoranOnly" class="hidden px-4 pb-4 border-t border-gray-200 dark:border-gray-800 pt-3">
                                <div class="flex flex-wrap gap-1.5 mb-2.5" id="riwayatFilterSetoranOnly">
                                    <button type="button" class="rs-chip is-active" data-f="">Semua</button>
                                    <button type="button" class="rs-chip" data-f="Hafalan">Hafalan</button>
                                    <button type="button" class="rs-chip" data-f="Murajaah">Murajaah</button>
                                    <button type="button" class="rs-chip" data-f="Tilawah">Tilawah</button>
                                </div>
                                <div id="riwayatListSetoranOnly" class="space-y-1.5 max-h-72 overflow-y-auto custom-scrollbar pr-1"></div>
                            </div>
                        </div>

                        <div class="setoran-after-santri hidden bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm dark:shadow-none">
                            <h3 class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-cream-200 dark:bg-coal-600 text-stone-500 dark:text-stone-400 flex items-center justify-center"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13"/></svg></span>
                                Data Setoran
                            </h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Metode <span class="text-red-500 dark:text-red-400">*</span></label>
                                    <select name="metode" id="metodeSetoranOnly" required class="form-select w-full">
                                        <option value="Al-Qur'an">📖 Al-Qur'an</option>
                                        <option value="Iqro">🟢 Iqro</option>
                                        <option value="Aisar">🔵 Aisar</option>
                                    </select>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-600 italic mt-1.5">🔒 Otomatis terisi sesuai target santri.</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Kategori <span class="text-red-500 dark:text-red-400">*</span></label>
                                    <select name="kategori_setoran" id="kategoriSetoranOnly" required class="form-select w-full">
                                        <option value="">-- Pilih --</option>
                                        <option value="Hafalan">📚 Hafalan</option>
                                        <option value="Murajaah">🔄 Murajaah</option>
                                        <option value="Tilawah">📖 Tilawah</option>
                                    </select>
                                </div>
                                <div id="rangeAyatBlockSetoranOnly">
                                <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700/50 rounded-lg p-3">
                                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253"/></svg> Range Ayat</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-[10px] text-gray-500 mb-1 block">Surat Mulai *</label>
                                            <select name="surat_start" id="suratStartSetoranOnly" required class="form-select-sm w-full">
                                                <option value="">-- Pilih --</option>
                                                ${m}
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-[10px] text-gray-500 mb-1 block">Ayat Mulai *</label>
                                            <select name="ayat_start" id="ayatStartSetoranOnly" disabled required class="form-select-sm w-full"><option value="">-- Pilih Surat --</option></select>
                                        </div>
                                        <div>
                                            <label class="text-[10px] text-gray-500 mb-1 block">Surat Selesai *</label>
                                            <select name="surat_end" id="suratEndSetoranOnly" required class="form-select-sm w-full">
                                                <option value="">-- Otomatis --</option>
                                                ${m}
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-[10px] text-gray-500 mb-1 block">Ayat Selesai *</label>
                                            <select name="ayat_end" id="ayatEndSetoranOnly" disabled required class="form-select-sm w-full"><option value="">-- Pilih Ayat --</option></select>
                                        </div>
                                    </div>
                                    <button type="button" onclick="setQuranPanelTitle('quran'); loadQuranSetoranOnly(); if(window.innerWidth < 1024) openDrawer('quran');"
                                            class="mt-2.5 w-full px-3 py-2 bg-claude-500 hover:bg-claude-600 text-white text-xs font-medium rounded-lg transition flex items-center justify-center gap-1.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253"/></svg> Tampilkan Al-Qur'an</button>
                                </div>
                                </div>

                                <div id="iqroBlockSetoranOnly" class="hidden bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700/50 rounded-lg p-3">
                                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5A2.5 2.5 0 016.5 17H20M4 19.5A2.5 2.5 0 006.5 22H20V2H6.5A2.5 2.5 0 004 4.5z"/></svg> Pilih Halaman <span class="lbl-metode-jilid">Iqro</span></p>
                                    <div class="mb-2">
                                        <label class="text-[10px] text-gray-500 mb-1 block">Jilid *</label>
                                        <select id="iqroJilidSetoranOnly" class="form-select-sm w-full">
                                            <option value="1">Jilid 1</option><option value="2">Jilid 2</option><option value="3">Jilid 3</option>
                                            <option value="4">Jilid 4</option><option value="5">Jilid 5</option><option value="6">Jilid 6</option>
                                        </select>
                                        <p class="text-[10px] text-gray-500 dark:text-gray-600 italic mt-1">🔒 Otomatis terisi dari target santri.</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-[10px] text-gray-500 mb-1 block">Halaman Mulai *</label>
                                            <select id="iqroHalamanStartSetoranOnly" class="form-select-sm w-full"><option value="">-- Mulai --</option></select>
                                        </div>
                                        <div>
                                            <label class="text-[10px] text-gray-500 mb-1 block">Halaman Selesai *</label>
                                            <select id="iqroHalamanEndSetoranOnly" class="form-select-sm w-full"><option value="">-- Selesai --</option></select>
                                        </div>
                                    </div>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-600 italic mt-1.5">💡 Untuk 1 halaman saja, isi Mulai &amp; Selesai sama.</p>
                                    <button type="button" id="btnJilidSetoranOnly" data-metode="iqro" onclick="setQuranPanelTitle('iqro'); loadIqro('SetoranOnly'); if(window.innerWidth < 1024) openDrawer('quran');"
                                            class="btn-jilid mt-2.5 w-full px-3 py-2 text-white text-xs font-medium rounded-lg transition flex items-center justify-center gap-1.5">
                                        <span>Tampilkan Halaman <span class="lbl-metode-jilid">Iqro</span></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="page_start" id="pageStartSetoranOnly">
                        <input type="hidden" name="page_end" id="pageEndSetoranOnly">
                        <input type="hidden" name="page_start_portion" id="pageStartPortionSetoranOnly">
                        <input type="hidden" name="page_end_portion" id="pageEndPortionSetoranOnly">
                        <input type="hidden" name="total_page" id="totalPageSetoranOnly">
                        <input type="hidden" name="juz_start" id="juzStartSetoranOnly">
                        <input type="hidden" name="juz_end" id="juzEndSetoranOnly">
                        <input type="hidden" name="total_juz" id="totalJuzSetoranOnly">
                        <input type="hidden" name="total_kata" id="totalKataSetoranOnly">
                        <input type="hidden" name="kesalahan_kelancaran" id="kesalahanKelancaranSetoranOnly" value="0">
                        <input type="hidden" name="kesalahan_tajwid" id="kesalahanTajwidSetoranOnly" value="0">
                        <input type="hidden" name="kesalahan_pelafalan" id="kesalahanPelafalanSetoranOnly" value="0">
                        <input type="hidden" name="nilai" id="nilaiSetoranOnly">
                        <input type="hidden" name="pdf_html_content" id="pdfHtmlDataSetoranOnly">
                        <input type="hidden" name="iqro_jilid" id="iqroJilidHiddenSetoranOnly">
                        <input type="hidden" name="iqro_halaman" id="iqroHalamanHiddenSetoranOnly">
                        <input type="hidden" name="iqro_halaman_end" id="iqroHalamanEndHiddenSetoranOnly">

                        <div class="setoran-after-santri hidden mobile-hide-audio bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
                            <div class="flex items-center p-4 border-b border-gray-200 dark:border-gray-800">
                                <span class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-cream-200 dark:bg-coal-600 text-stone-500 dark:text-stone-400 flex items-center justify-center"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-14 0m7 7v3m-4 0h8m-4-6a3 3 0 01-3-3V5a3 3 0 016 0v4a3 3 0 01-3 3z"/></svg></span>
                                    Audio &amp; Penilaian
                                </span>
                            </div>
                            <div id="sectionAudioSetoranOnly" class="px-4 pb-4 pt-3 space-y-3">
                                <div class="flex gap-2">
                                    <button type="button" id="startRecordingSetoranOnly" onclick="startRecording('SetoranOnly')" class="inline-flex flex-1 items-center justify-center gap-1.5 py-2.5 bg-red-600 hover:bg-red-500 text-white text-xs font-semibold rounded-xl shadow-sm transition active:scale-95"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="6"/></svg> Rekam</button>
                                    <button type="button" id="pauseRecordingSetoranOnly" onclick="pauseRecording('SetoranOnly')" class="hidden inline-flex flex-1 items-center justify-center gap-1.5 py-2.5 bg-amber-100 hover:bg-amber-200 text-amber-800 ring-1 ring-amber-200 text-xs font-semibold rounded-xl transition active:scale-95 dark:bg-amber-500/15 dark:hover:bg-amber-500/25 dark:text-amber-300 dark:ring-amber-500/25"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><rect x="8" y="6" width="3.2" height="12" rx="1"/><rect x="12.8" y="6" width="3.2" height="12" rx="1"/></svg> Jeda</button>
                                    <button type="button" id="stopRecordingSetoranOnly" onclick="stopRecording('SetoranOnly')" class="hidden inline-flex flex-1 items-center justify-center gap-1.5 py-2.5 bg-stone-700 hover:bg-stone-600 text-white text-xs font-semibold rounded-xl shadow-sm transition active:scale-95 dark:bg-coal-600 dark:hover:bg-coal-500 dark:ring-1 dark:ring-coal-500"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><rect x="7" y="7" width="10" height="10" rx="2"/></svg> Stop</button>
                                </div>
                                <div id="recordingTimerSetoranOnly" class="hidden text-xs font-bold text-blue-600 dark:text-blue-400 text-center py-2 bg-blue-50 dark:bg-blue-950/50 rounded-lg border border-blue-200 dark:border-blue-800/50">⏱️ Durasi: 0:00</div>
                                <audio id="audioPlaybackSetoranOnly" controls class="w-full hidden rounded-lg"></audio>
                                <button type="button" id="clearRecordingSetoranOnly" onclick="clearRecording('SetoranOnly')" class="w-full py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-medium rounded-lg transition hidden flex items-center justify-center gap-1">🗑️ Hapus &amp; Rekam Ulang</button>
                                <input type="hidden" name="audio_data" id="audioDataSetoranOnly">
                                <p class="text-[10px] text-gray-500 dark:text-gray-600 italic">💡 Gunakan Jeda untuk berhenti sementara</p>
                                <div class="grid grid-cols-2 gap-2 pt-1">
                                    <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-2.5 flex items-center justify-between col-span-2"><span class="text-xs text-gray-600 dark:text-gray-400">🟢 Kelancaran</span><strong id="displayKelancaranSetoranOnly" class="text-base text-emerald-600 dark:text-emerald-400">0</strong></div>
                                    <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-2.5 flex items-center justify-between"><span class="text-xs text-gray-600 dark:text-gray-400">🟡 Tajwid</span><strong id="displayTajwidSetoranOnly" class="text-base text-amber-600 dark:text-amber-400">0</strong></div>
                                    <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-2.5 flex items-center justify-between"><span class="text-xs text-gray-600 dark:text-gray-400">🔴 Pelafalan</span><strong id="displayPelafalanSetoranOnly" class="text-base text-red-600 dark:text-red-400">0</strong></div>
                                    <div class="bg-indigo-50 dark:bg-indigo-950 border border-indigo-200 dark:border-indigo-800/50 rounded-lg p-2.5 flex items-center justify-between col-span-2"><span class="text-xs font-bold text-gray-700 dark:text-gray-300 flex items-center gap-1.5">Nilai</span><strong id="displayNilaiSetoranOnly" class="text-2xl text-indigo-600 dark:text-indigo-400">100</strong></div>
                                </div>
                            </div>
                        </div>

                        <div class="setoran-after-santri hidden mobile-hide-bottom bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm dark:shadow-none">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">📝 Catatan Guru</label>
                            <textarea name="catatan_guru" id="catatanGuruSetoranOnly" rows="2" class="form-textarea w-full" placeholder="Tambahkan catatan..."></textarea>
                        </div>

                        <button type="submit" id="submitBtnSetoranOnly" class="setoran-after-santri hidden mobile-hide-bottom w-full py-3 bg-gradient-to-r from-claude-500 to-claude-600 hover:from-claude-600 hover:to-claude-700 text-white font-semibold text-sm rounded-xl shadow-lg shadow-claude-500/25 transition flex items-center justify-center gap-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-8 0V4a1 1 0 011-1h6a1 1 0 011 1v3m-8 0h8M9 21v-6h6v6"/></svg> Simpan Setoran Tambahan</button>                    
                    </form>

                </div>
            </div>

            <div class="hidden lg:block lg:col-span-3">
                <div class="lg:sticky lg:top-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm dark:shadow-none">

                    <div id="panelPetunjuk" class="p-6">
                        <div class="text-center mb-6">
                            <div class="w-14 h-14 bg-cream-200 dark:bg-coal-600 rounded-2xl flex items-center justify-center mx-auto mb-3 text-stone-500 dark:text-stone-400"><svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg></div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Petunjuk Penggunaan</h3>
                            <p class="text-xs text-gray-500">Pilih mode input di kiri untuk memulai</p>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-start gap-3 p-3 bg-claude-50 dark:bg-claude-500/10 rounded-xl border border-claude-200 dark:border-claude-500/20"><div class="w-9 h-9 rounded-xl bg-claude-500/15 text-claude-600 dark:text-claude-400 flex items-center justify-center flex-shrink-0"><svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/></svg></div><div><h4 class="text-sm font-semibold text-gray-900 dark:text-white">Presensi + Setoran</h4><p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">Catat kehadiran &amp; setoran sekaligus dalam 1 submit.</p></div></div>
                            <div class="flex items-start gap-3 p-3 bg-blue-50 dark:bg-blue-500/10 rounded-xl border border-blue-200 dark:border-blue-500/20"><div class="w-9 h-9 rounded-xl bg-blue-500/15 text-blue-600 dark:text-blue-400 flex items-center justify-center flex-shrink-0"><svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div><div><h4 class="text-sm font-semibold text-gray-900 dark:text-white">Presensi Saja</h4><p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">Untuk Hadir tanpa setoran, atau Sakit / Izin / Alpha.</p></div></div>
                            <div class="flex items-start gap-3 p-3 bg-emerald-50 dark:bg-emerald-500/10 rounded-xl border border-emerald-200 dark:border-emerald-500/20"><div class="w-9 h-9 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0"><svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13"/></svg></div><div><h4 class="text-sm font-semibold text-gray-900 dark:text-white">Setoran Tambahan</h4><p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">Setoran ke-2, ke-3, dst. Santri harus sudah hadir.</p></div></div>
                        </div>
                    </div>

                    <div id="defaultView" class="hidden p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2"><span class="w-6 h-6 rounded-lg bg-cream-200 dark:bg-coal-600 text-stone-500 dark:text-stone-400 flex items-center justify-center flex-shrink-0"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></span>Presensi — ${b}</h4>
                        </div>
                        <div class="grid grid-cols-4 gap-2 mb-4">
                            <div class="bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-900/30 rounded-lg p-2.5 text-center"><div class="text-xl font-bold text-emerald-600 dark:text-emerald-400" id="statHadir">0</div><div class="text-[10px] text-gray-500 dark:text-gray-500 flex items-center justify-center gap-1"><span class="w-2 h-2 rounded-full" style="background-color:#10b981;"></span>Hadir</div></div>
                            <div class="bg-orange-50 dark:bg-orange-950/50 border border-orange-200 dark:border-orange-900/30 rounded-lg p-2.5 text-center"><div class="text-xl font-bold text-orange-600 dark:text-orange-400" id="statSakit">0</div><div class="text-[10px] text-gray-500 dark:text-gray-500 flex items-center justify-center gap-1"><span class="w-2 h-2 rounded-full" style="background-color:#f97316;"></span>Sakit</div></div>
                            <div class="bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-900/30 rounded-lg p-2.5 text-center"><div class="text-xl font-bold text-amber-600 dark:text-amber-400" id="statIzin">0</div><div class="text-[10px] text-gray-500 dark:text-gray-500 flex items-center justify-center gap-1"><span class="w-2 h-2 rounded-full" style="background-color:#f59e0b;"></span>Izin</div></div>
                            <div class="bg-red-50 dark:bg-red-950/50 border border-red-200 dark:border-red-900/30 rounded-lg p-2.5 text-center"><div class="text-xl font-bold text-red-600 dark:text-red-400" id="statAlpha">0</div><div class="text-[10px] text-gray-500 dark:text-gray-500 flex items-center justify-center gap-1"><span class="w-2 h-2 rounded-full" style="background-color:#ef4444;"></span>Alpha</div></div>
                        </div>
                        <div class="flex flex-wrap gap-2 mb-3">
                            <div class="flex items-center gap-1 text-[10px] text-gray-500"><div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>Hadir</div>
                            <div class="flex items-center gap-1 text-[10px] text-gray-500"><div class="w-2.5 h-2.5 rounded-full bg-orange-500"></div>Sakit</div>
                            <div class="flex items-center gap-1 text-[10px] text-gray-500"><div class="w-2.5 h-2.5 rounded-full bg-amber-500"></div>Izin</div>
                            <div class="flex items-center gap-1 text-[10px] text-gray-500"><div class="w-2.5 h-2.5 rounded-full bg-red-500"></div>Alpha</div>
                            <div class="flex items-center gap-1 text-[10px] text-gray-500"><span class="px-1 bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400 rounded text-[9px]">📖</span>Setoran</div>
                        </div>
                        <div id="daftarPresensiHariIni" class="max-h-[420px] overflow-y-auto overflow-x-auto tabel-presensi-scroll rounded-lg border border-gray-200 dark:border-gray-800">
                            <!-- min-w-max dihapus: kalau dipertahankan, tabel melebar mengikuti
                                 nama terpanjang sehingga teks tidak pernah turun baris dan
                                 malah memunculkan scroll horizontal. -->
                            <table class="w-full text-xs">
                                <thead class="bg-gray-100 dark:bg-gray-800 sticky top-0 z-10">
                                    <tr id="tableHeaderRow" class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="px-2 py-2 text-center text-gray-600 dark:text-gray-400 font-medium w-10">No</th>
                                        <th class="px-2 py-2 text-left text-gray-600 dark:text-gray-400 font-medium">Nama Santri</th>
                                        <th class="px-2 py-2 text-left text-gray-600 dark:text-gray-400 font-medium whitespace-nowrap">Metode</th>
                                        <th class="px-2 py-2 text-left text-gray-600 dark:text-gray-400 font-medium whitespace-nowrap">Progress</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBodyPresensi" class="divide-y divide-gray-200 dark:divide-gray-800/50">
                                    <tr><td colspan="7" class="text-center py-6 text-gray-500 dark:text-gray-600 italic">Memuat data...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="quranPanel" class="hidden p-4 bg-white dark:bg-gray-900">
                        <div class="hidden md:flex items-center justify-between mb-3 pb-3 border-b border-gray-200 dark:border-gray-800">
                            <h3 id="quranPanelTitle" class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2"><svg class="w-4 h-4 text-stone-400 dark:text-stone-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13"/></svg> <span>Al-Qur'an Digital</span></h3>
                            <div class="flex gap-2">
                                <button type="button" onclick="clearMarking()" class="px-2.5 py-1.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs rounded-lg transition border border-gray-200 dark:border-transparent">🗑️ Reset</button>
                                <button type="button" onclick="backToDefaultView()" class="px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs rounded-lg transition">← Kembali</button>
                            </div>
                        </div>

                        <div class="mb-3 border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
                            <button type="button" onclick="toggleColorGuide(this)" class="w-full px-3 py-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-750 text-left flex justify-between items-center transition">
                                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">🎨 Panduan Warna Marking</span>
                                <svg id="colorGuideIcon" class="w-4 h-4 text-gray-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div id="colorGuideContent" class="hidden bg-white dark:bg-gray-900 p-3 border-t border-gray-200 dark:border-gray-800">
                                <div class="space-y-1.5 text-xs">
                                    <div class="flex items-center gap-2 p-2 bg-emerald-50 dark:bg-emerald-950/50 rounded"><div class="w-3 h-3 bg-green-400 rounded flex-shrink-0"></div><span class="text-gray-700 dark:text-gray-300"><strong>🟢 Kelancaran</strong> — -4 poin/kata</span></div>
                                    <div class="flex items-center gap-2 p-2 bg-amber-50 dark:bg-amber-950/50 rounded"><div class="w-3 h-3 bg-yellow-400 rounded flex-shrink-0"></div><span class="text-gray-700 dark:text-gray-300"><strong>🟡 Tajwid</strong> — -4 poin/kata</span></div>
                                    <div class="flex items-center gap-2 p-2 bg-red-50 dark:bg-red-950/50 rounded"><div class="w-3 h-3 bg-red-400 rounded flex-shrink-0"></div><span class="text-gray-700 dark:text-gray-300"><strong>🔴 Pelafalan</strong> — -4 poin/kata</span></div>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-600 pt-1">💡 Klik kata untuk marking. Klik lagi untuk ganti/hapus.</p>
                                </div>
                            </div>
                        </div>

                        <div id="quranDisplay" class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700/50 p-4 text-right overflow-y-auto custom-scrollbar mb-3 rounded-xl" style="font-family: 'Amiri', 'Traditional Arabic', serif; min-height: 280px; max-height: 380px;"></div>

                        <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700/50 p-3 rounded-xl">
                            <h4 class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-stone-400 dark:text-stone-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg> Statistik Setoran</h4>
                            <div class="grid grid-cols-3 gap-2 mb-3">
                                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-transparent p-2.5 rounded-lg"><div class="text-[10px] text-gray-500 mb-0.5">Total Kata</div><div class="text-xl font-bold text-blue-600 dark:text-blue-400" id="displayTotalKata">0</div></div>
                                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-transparent p-2.5 rounded-lg"><div class="text-[10px] text-gray-500 mb-0.5">Juz Range</div><div class="text-lg font-bold text-purple-600 dark:text-purple-400" id="displayJuzRange">-</div></div>
                                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-transparent p-2.5 rounded-lg"><div class="text-[10px] text-gray-500 mb-0.5">Total Juz</div><div class="text-xl font-bold text-gray-900 dark:text-white" id="displayTotalJuz">0.00</div></div>
                            </div>
                            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-transparent p-2.5 rounded-lg">
                                <div class="flex items-center justify-between mb-2"><span class="text-[10px] text-gray-500 font-medium flex items-center gap-1"><svg class="w-3 h-3 text-stone-400 dark:text-stone-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> Halaman Mushaf Madinah</span><span class="text-[10px] text-gray-500 dark:text-gray-600">⚙️ Otomatis</span></div>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-100 dark:bg-gray-800 px-2.5 py-2 rounded-lg border border-gray-200 dark:border-gray-700"><div class="text-[10px] text-gray-500">Mulai</div><div class="text-base font-bold text-orange-600 dark:text-orange-400" id="displayPageStart">-</div></div>
                                    <div class="text-gray-400 dark:text-gray-600">→</div>
                                    <div class="flex-1 bg-gray-100 dark:bg-gray-800 px-2.5 py-2 rounded-lg border border-gray-200 dark:border-gray-700"><div class="text-[10px] text-gray-500">Selesai</div><div class="text-base font-bold text-orange-600 dark:text-orange-400" id="displayPageEnd">-</div></div>
                                    <div class="flex-1 bg-orange-50 dark:bg-orange-950/50 px-2.5 py-2 rounded-lg border border-orange-200 dark:border-orange-900/30"><div class="text-[10px] text-gray-500">Total</div><div class="text-base font-bold text-orange-600 dark:text-orange-400" id="displayTotalPage">0.00</div></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div id="mobileFab" class="fixed bottom-4 right-4 z-[100] lg:hidden hidden">
        <button onclick="openDrawer('presensi')" class="flex items-center gap-2 px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-full shadow-2xl shadow-indigo-900/50 transition-all active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Presensi
        </button>
    </div>
</div>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Amiri+Quran&display=swap" rel="stylesheet">
<style>
    html.dark #inputPage .dark\\:bg-gray-900 { background-color: #262624 !important; }
    html.dark #inputPage .dark\\:bg-gray-800,
    html.dark #inputPage .dark\\:bg-gray-800\\/50 { background-color: #30302E !important; }
    html.dark #inputPage .dark\\:border-gray-800 { border-color: #3E3D3A !important; }
    .setoran-badge { background-color: #e0e7ff; color: #4338ca; }
    html.dark .setoran-badge { background-color: #312e81; color: #a5b4fc; }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #374151; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #6366f1; }

    /* Tabel presensi: scrollbar diberi jalur sendiri (scrollbar-gutter) supaya
       tidak menindih kolom terakhir. Track dibuat terlihat agar batas antara
       area konten dan scrollbar jelas. */
    .tabel-presensi-scroll { scrollbar-gutter: stable; }
    .tabel-presensi-scroll::-webkit-scrollbar { width: 10px; height: 10px; }
    .tabel-presensi-scroll::-webkit-scrollbar-track { background: #F5F4EF; }
    .tabel-presensi-scroll::-webkit-scrollbar-thumb {
        background: #D6D3D1;
        border-radius: 9999px;
        border: 3px solid #F5F4EF;   /* jarak visual thumb ke tepi */
    }
    .tabel-presensi-scroll::-webkit-scrollbar-thumb:hover { background: #B8B4B0; }
    .tabel-presensi-scroll::-webkit-scrollbar-corner { background: #F5F4EF; }

    html.dark .tabel-presensi-scroll::-webkit-scrollbar-track,
    html.dark .tabel-presensi-scroll::-webkit-scrollbar-corner { background: #262624; }
    html.dark .tabel-presensi-scroll::-webkit-scrollbar-thumb {
        background: #4A4845;
        border-color: #262624;
    }
    html.dark .tabel-presensi-scroll::-webkit-scrollbar-thumb:hover { background: #5C5A56; }

    /* Firefox tidak mengenal ::-webkit-*, jadi diatur terpisah. */
    .tabel-presensi-scroll { scrollbar-width: thin; scrollbar-color: #D6D3D1 #F5F4EF; }
    html.dark .tabel-presensi-scroll { scrollbar-color: #4A4845 #262624; }

    /* ===== Badge metode & bar progress di tabel presensi =====
       Warna WAJIB lewat CSS variable, bukan hex inline dari JS.
       Tabel ini dirender sekali oleh unified-input-page.js dan tidak
       dirender ulang saat tema diganti — kalau warnanya ditanam inline,
       light/dark baru ikut berubah setelah auto-refresh 30 detik. */
    #inputPage {
        --pm-quran-bg: #FBEDE7; --pm-quran-fg: #BE5D3D;
        --pm-iqro-bg:  #FDF3E3; --pm-iqro-fg:  #9A6B14;
        --pm-aisar-bg: #EAF1EC; --pm-aisar-fg: #4A6B54;
        --pm-track:    #E7E5E4; --pm-label:    #78716C;
        --pm-empty:    #A8A29E;
    }
    html.dark #inputPage {
        --pm-quran-bg: rgba(217,119,87,.15);  --pm-quran-fg: #E8A183;
        --pm-iqro-bg:  rgba(212,162,78,.15);  --pm-iqro-fg:  #D9B36A;
        --pm-aisar-bg: rgba(124,152,133,.18); --pm-aisar-fg: #9DBAA6;
        --pm-track:    #3E3D3A;               --pm-label:    #A8A29E;
        --pm-empty:    #78716C;
    }

    .pm-badge {
        display: inline-block; white-space: nowrap;
        padding: 2px 7px; border-radius: 6px;
        font-size: 10px; font-weight: 600;
        transition: background-color .2s, color .2s;
    }
    .pm-badge[data-tone="quran"] { background: var(--pm-quran-bg); color: var(--pm-quran-fg); }
    .pm-badge[data-tone="iqro"]  { background: var(--pm-iqro-bg);  color: var(--pm-iqro-fg); }
    .pm-badge[data-tone="aisar"] { background: var(--pm-aisar-bg); color: var(--pm-aisar-fg); }
    .pm-badge .pm-plus { opacity: .45; }

    .pm-empty { color: var(--pm-empty); font-size: 10px; }

    .pm-prog      { display: flex; flex-direction: column; gap: 3px; min-width: 96px; }
    .pm-prog-row  { display: flex; align-items: center; gap: 5px; }
    .pm-prog-track{
        flex: 1; height: 4px; border-radius: 9999px;
        background: var(--pm-track); overflow: hidden;
        transition: background-color .2s;
    }
    .pm-prog-fill { display: block; height: 100%; border-radius: 9999px; }
    .pm-prog-fill[data-c="1"] { background: #D97757; }
    .pm-prog-fill[data-c="2"] { background: #10B981; }
    .pm-prog-val  {
        font-size: 10px; font-weight: 600; min-width: 26px;
        text-align: right; color: var(--pm-label);
        transition: color .2s;
    }
    .form-input, .form-select, .form-select-sm, .form-textarea { background: #ffffff; border: 1px solid #d1d5db; color: #111827; border-radius: 0.5rem; font-size: 0.8125rem; padding: 0.5rem 0.625rem; transition: border-color 0.15s; }
    html.dark .form-input, html.dark .form-select, html.dark .form-select-sm, html.dark .form-textarea { background: #1C1C1A; border: 1px solid #3E3D3A; color: #e5e7eb; }
    .form-select-sm { padding: 0.4rem 0.5rem; font-size: 0.75rem; }
    .form-select.is-locked {
        pointer-events: none;
        opacity: 0.9;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        padding-right: 2.25rem;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2378716c' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='4.5' y='11' width='15' height='10' rx='2'/%3E%3Cpath d='M8 11V7a4 4 0 0 1 8 0v4'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.85rem center;
        background-size: 15px;
    }
    html.dark .form-select.is-locked {
        background-color: #232321;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23a8a29e' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='4.5' y='11' width='15' height='10' rx='2'/%3E%3Cpath d='M8 11V7a4 4 0 0 1 8 0v4'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.85rem center;
        background-size: 15px;
    }

    /* ===== Panah select METODE =====
       Select metode TIDAK didaftarkan di ALL_SELECTS (unified-input-page.js),
       jadi ia tetap <select> native — beda sendiri dari field lain yang sudah
       jadi widget kustom (.cs-btn dengan panah ▼).
       Saat santri 1 metode, .is-locked menyembunyikan panah native (ganti gembok).
       Saat santri 2 metode, .is-locked TIDAK dipasang → panah bawaan browser
       muncul. Rule di bawah menggantinya dengan segitiga yang identik dengan
       .cs-arr (8px, #6b7280) supaya konsisten di light & dark mode. */
    #metodeCombined:not(.is-locked),
    #metodeSetoranOnly:not(.is-locked) {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        padding-right: 2rem;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 10 10'%3E%3Cpath d='M1 3.2 L5 7.6 L9 3.2 Z' fill='%236b7280'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.72rem center;
        background-size: 9px;
    }
    /* Firefox: hilangkan garis fokus pada tombol dropdown bawaan */
    #metodeCombined:not(.is-locked)::-ms-expand,
    #metodeSetoranOnly:not(.is-locked)::-ms-expand { display: none; }

    .form-input:focus, .form-select:focus, .form-select-sm:focus, .form-textarea:focus { outline: none; border-color: #D97757; box-shadow: 0 0 0 2px rgba(217,119,87,0.18); }
    .form-textarea { resize: vertical; }
    input[type="date"]::-webkit-calendar-picker-indicator { cursor: pointer; opacity: 0.6; }
    html.dark input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); opacity: 0.7; }
    input[type="date"]::-webkit-calendar-picker-indicator:hover { opacity: 1; }
    .mode-card input:checked ~ .mode-card-inner { border-color: #6366f1; background: rgba(99,102,241,0.1); }
    .mode-card input[value="presensi_only"]:checked ~ .mode-card-inner { border-color: #3b82f6; background: rgba(59,130,246,0.08); }
    .mode-card input[value="setoran_only"]:checked ~ .mode-card-inner { border-color: #10b981; background: rgba(16,185,129,0.08); }
    .status-radio-label input:checked ~ .status-radio-inner { border-color: #6366f1; background: rgba(99,102,241,0.1); color: #4f46e5; }
    html.dark .status-radio-label input:checked ~ .status-radio-inner { color: #a5b4fc; }
    @keyframes fade-in { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
    .animate-fade-in { animation: fade-in 0.25s ease-out; }
    .section-open .section-chevron { transform: rotate(180deg); }
    @media (max-width: 1023px) { .mobile-hide-audio { display: none !important; } .mobile-hide-bottom { display: none !important; } }
    .cs-wrap { position: relative; width: 100%; }
    .cs-btn { width: 100%; text-align: left; padding: 7px 10px; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #ffffff; color: #111827; font-size: 0.8125rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center; min-height: 36px; box-sizing: border-box; transition: border-color 0.15s; font-family: inherit; line-height: 1.25; }
    html.dark .cs-btn { background: #1C1C1A; color: #e5e7eb; border-color: #3E3D3A; }
    .cs-btn:hover, .cs-btn.open { border-color: #D97757; }
    .cs-btn.cs-disabled { opacity: 0.4; cursor: not-allowed; }
    .cs-btn .cs-txt { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; font-size: 0.8125rem; }
    .cs-btn .cs-txt.ph { color: #6b7280; font-style: italic; }
    .cs-btn .cs-arr { font-size: 8px; color: #6b7280; flex-shrink: 0; margin-left: 6px; transition: transform 0.2s; }
    .cs-btn.open .cs-arr { transform: rotate(180deg); }
    .cs-drop { display: none; position: absolute; top: calc(100% + 2px); left: 0; right: 0; z-index: 99999; background: #ffffff; border: 1px solid #d1d5db; border-radius: 0.5rem; box-shadow: 0 8px 24px rgba(0,0,0,0.18); overflow: hidden; }
    html.dark .cs-drop { background: #262624; border-color: #3E3D3A; box-shadow: 0 8px 24px rgba(0,0,0,0.6); }
    .cs-drop.open { display: block; }
    .cs-search { width: 100%; padding: 7px 10px; border: none; border-bottom: 1px solid #e5e7eb; outline: none; font-size: 0.75rem; background: #ffffff; color: #111827; box-sizing: border-box; font-family: inherit; }
    html.dark .cs-search { background: #1C1C1A; color: #f3f4f6; border-bottom-color: #3E3D3A; }
    .cs-search::placeholder { color: #6b7280; }
    .cs-list { max-height: 180px; overflow-y: auto; margin: 0; padding: 2px 0; list-style: none; scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
    html.dark .cs-list { scrollbar-color: #374151 transparent; }
    .cs-list li { padding: 6px 10px; font-size: 0.75rem; cursor: pointer; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: background 0.1s; }
    html.dark .cs-list li { color: #e5e7eb; }
    .cs-list li:hover, .cs-list li.cs-hi { background: #D97757 !important; color: white !important; }
    .cs-list li.cs-ph { color: #6b7280; font-style: italic; }
    .cs-list li.cs-gone { display: none; }
    .cs-nores { padding: 8px 10px; font-size: 0.75rem; color: #6b7280; text-align: center; display: none; font-style: italic; }
    #mobileDrawer.open { transform: translateY(0) !important; }
    @media (max-width: 1023px) {
        #quranDisplayDrawer #quranText { font-size: 28px !important; line-height: 3.0 !important; word-spacing: -4px !important; }
        #quranDisplayDrawer .word-item { font-size: 24px !important; padding: 4px 6px !important; margin: 3px 0 !important; display: inline-block !important; }
        #quranDisplayDrawer .iqro-page .word-item { font-size: 32px !important; height: 64px !important; padding: 0 6px !important; margin: 0 !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; line-height: 1.4 !important; }
        #quranDisplayDrawer #quranText > span:not(.word-item) { font-size: 16px !important; margin: 0 4px !important; }
        #quranDisplayDrawer p[style*="font-size: 38px"] { font-size: 26px !important; }
        #quranDisplayDrawer p[style*="font-size: 36px"] { font-size: 22px !important; }
        #quranDisplayDrawer p[style*="font-size: 16px"] { font-size: 12px !important; }
        #quranDisplayDrawer div[style*="border: 1.5px solid"] { padding: 14px !important; margin-top: 8px !important; margin-bottom: 12px !important; }
        #quranDisplayDrawer { min-height: 200px !important; max-height: 50vh !important; }
    }
    #quranPanel, #quranPanelDrawer, #quranDisplay, #quranDisplayDrawer { --q-fg: #ffffff; --q-num: rgba(255,255,255,0.55); --q-box-border: rgba(255,255,255,0.85); --q-line: rgba(255,255,255,0.30); --q-nav-bg: rgba(17,24,39,0.96); --q-nav-btn: #374151; --q-nav-btn-fg: #e5e7eb; --q-nav-label: #cbd5e1; }
    html:not(.dark) #quranPanel, html:not(.dark) #quranPanelDrawer, html:not(.dark) #quranDisplay, html:not(.dark) #quranDisplayDrawer { --q-fg: #1f2937; --q-num: rgba(31,41,55,0.55); --q-box-border: rgba(31,41,55,0.55); --q-line: rgba(31,41,55,0.22); --q-nav-bg: #eef2ff; --q-nav-btn: #e0e7ff; --q-nav-btn-fg: #3730a3; --q-nav-label: #4f46e5; }
    #quranDisplay .iqro-page, #quranDisplay .iqro-page *, #quranDisplayDrawer .iqro-page, #quranDisplayDrawer .iqro-page *, .iqro-cap .iqro-page, .iqro-cap .iqro-page * { font-family: 'Amiri', 'Amiri Quran', serif !important; }
    .success-notification > div, .error-notification > div, .warning-notification > div { position: relative; overflow: hidden; }
    .success-notification > div::after, .error-notification > div::after, .warning-notification > div::after { content: ''; position: absolute; bottom: 0; left: 0; height: 3px; width: 100%; background: rgba(255,255,255,0.5); animation: notifCountdown 10s linear forwards; }
    @keyframes notifCountdown { from { width: 100%; } to { width: 0%; } }
    .iqro-row-scroll::-webkit-scrollbar { height: 4px; }
    .iqro-row-scroll::-webkit-scrollbar-track { background: transparent; }
    .iqro-row-scroll::-webkit-scrollbar-thumb { background: rgba(99,102,241,0.4); border-radius: 4px; }

    /* ===== FIX: warna tombol "Tampilkan Halaman" mengikuti metode aktif =====
       data-metode di-sync lewat syncJilidUI() di InputSetoran.jsx.
       Selektor [data-metode] lebih spesifik dari .btn-jilid polos,
       jadi rule warna lama (biru) otomatis kalah. */
           /* ===== Riwayat Setoran ===== */
    #inputPage {
        --rs-card:  #FFFFFF;  --rs-line:  #E7E5E4;
        --rs-fg:    #292524;  --rs-sub:   #78716C;
        --rs-chip:  #F5F4EF;  --rs-chip-fg: #57534E;
    }
    html.dark #inputPage {
        --rs-card:  #1C1C1A;  --rs-line:  #3E3D3A;
        --rs-fg:    #E7E5E4;  --rs-sub:   #A8A29E;
        --rs-chip:  #2A2A28;  --rs-chip-fg: #A8A29E;
    }
    .rs-chip {
        padding: 3px 10px; border-radius: 9999px; font-size: 10px; font-weight: 600;
        background: var(--rs-chip); color: var(--rs-chip-fg);
        border: 1px solid var(--rs-line); cursor: pointer; transition: all .15s;
    }
    .rs-chip:hover { border-color: #D97757; }
    .rs-chip.is-active { background: #D97757; color: #fff; border-color: #D97757; }

    .rs-item {
        background: var(--rs-card); border: 1px solid var(--rs-line);
        border-radius: 10px; padding: 8px 10px;
    }
    .rs-item-top, .rs-item-bot { display: flex; align-items: center; justify-content: space-between; }
    .rs-item-bot { margin-top: 4px; }
    .rs-kat { font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 6px; }
    .rs-kat[data-k="Hafalan"]  { background: #EAF1EC; color: #4A6B54; }
    .rs-kat[data-k="Murajaah"] { background: #FDF3E3; color: #9A6B14; }
    .rs-kat[data-k="Tilawah"]  { background: #FBEDE7; color: #BE5D3D; }
    html.dark .rs-kat[data-k="Hafalan"]  { background: rgba(124,152,133,.18); color: #9DBAA6; }
    html.dark .rs-kat[data-k="Murajaah"] { background: rgba(212,162,78,.15);  color: #D9B36A; }
    html.dark .rs-kat[data-k="Tilawah"]  { background: rgba(217,119,87,.15);  color: #E8A183; }

    .rs-tgl    { font-size: 10px; color: var(--rs-sub); font-variant-numeric: tabular-nums; }
    .rs-materi { font-size: 12px; color: var(--rs-fg); margin-top: 5px; line-height: 1.35; word-break: break-word; }
    .rs-metode { font-size: 10px; color: var(--rs-sub); }
    .rs-nilai  { font-size: 13px; font-weight: 700; font-variant-numeric: tabular-nums; }
    .rs-nilai[data-n="hi"]  { color: #10B981; }
    .rs-nilai[data-n="mid"] { color: #D4A24E; }
    .rs-nilai[data-n="lo"]  { color: #EF4444; }
    .rs-empty  { font-size: 11px; color: var(--rs-sub); font-style: italic; text-align: center; padding: 14px 0; }
    .btn-jilid                            { background-image: none; background-color: #D4A24E; }
    .btn-jilid[data-metode="iqro"]        { background-image: none; background-color: #D4A24E; }
    .btn-jilid[data-metode="iqro"]:hover  { background-image: none; background-color: #BC8C3D; }
    .btn-jilid[data-metode="aisar"]       { background-image: none; background-color: #7C9885; }
    .btn-jilid[data-metode="aisar"]:hover { background-image: none; background-color: #67836F; }
</style>`}function G(s){var h;const{santriList:a=[],sesiList:C=[],daftarSurat:b=[],tanggalHariIni:f="",kelasName:w="",tahunAjaran:q=null,flash:i={},errors:M={}}=s,m=typeof document<"u"&&((h=document.querySelector('meta[name="csrf-token"]'))==null?void 0:h.content)||"";z.useEffect(()=>{if(window.__INPUT_PAGE_LOADED__){window.location.reload();return}window.__INPUT_PAGE_LOADED__=!0,window.__TANGGAL_HARI_INI__=f,window.__SANTRI_META__=Object.fromEntries(a.map(t=>{const e=String(t.target_metode||"quran").toLowerCase();return[String(t.id),{primary:e==="aisar"?`Aisar ${t.target_jilid??"-"}`:e==="iqro"?`Iqro ${t.target_jilid??"-"}`:"Al-Quran",tone:e==="aisar"?"aisar":e==="iqro"?"iqro":"quran",hasQuran2:String(t.target_metode_2||"").toLowerCase()==="quran",progress:Number(t.progress_persen??0),progress2:Number(t.progress_juz2??0)}]}));function S(t,e){const n=document.getElementById(t),r=document.getElementById(e);if(!n||!r)return;const l=c=>{const d=String(c||"").toLowerCase();return d==="aisar"?"Aisar":d==="iqro"||d==="ummi"?"Iqro":"Al-Qur'an"},u=()=>{const c=n.options[n.selectedIndex];if(!(c!=null&&c.value)){r.classList.remove("is-locked"),Array.from(r.options).forEach(p=>{p.hidden=!1,p.disabled=!1});return}const d=l(c.getAttribute("data-target-metode")||"quran"),y=c.getAttribute("data-target-metode-2")||"",o=y?l(y):"",g=[d];o&&o!==d&&g.push(o),Array.from(r.options).forEach(p=>{const B=g.includes(p.value);p.hidden=!B,p.disabled=!B}),r.value=d,g.length<=1?(r.classList.add("is-locked"),r.setAttribute("aria-readonly","true")):(r.classList.remove("is-locked"),r.removeAttribute("aria-readonly")),r.dispatchEvent(new Event("change",{bubbles:!0}))};n.addEventListener("change",u),u()}setTimeout(()=>{S("santriSelectCombined","metodeCombined"),S("santriSelectSetoranOnly","metodeSetoranOnly")},0);const v={iqro:6,aisar:5};function x(){["Combined","SetoranOnly"].forEach(t=>{var y;const e=document.getElementById("metode"+t);if(!e)return;const n=String(e.value||"").toLowerCase().includes("aisar")?"aisar":"iqro",r=document.getElementById("btnJilid"+t);r&&r.dataset.metode!==n&&(r.dataset.metode=n);const l=document.getElementById("iqroJilid"+t);if(!l)return;const u=v[n];let c=!1;Array.from(l.options).forEach(o=>{const g=parseInt(o.value,10);if(!g)return;const p=g<=u;o.hidden=!p,o.disabled=!p,!p&&l.value===o.value&&(c=!0)});const d=l.closest(".cs-wrap")||((y=l.parentElement)==null?void 0:y.querySelector(".cs-wrap"));if(d&&d.querySelectorAll(".cs-list li").forEach(o=>{const g=parseInt(String(o.dataset.value??o.textContent).replace(/\D+/g,""),10);g&&(o.style.display=g<=u?"":"none")}),c){l.value=String(u);const o=d==null?void 0:d.querySelector(".cs-btn .cs-txt");o&&(o.textContent=`Jilid ${u}`,o.classList.remove("ph")),l.dispatchEvent(new Event("change",{bubbles:!0}))}}),["iqroBlockCombined","iqroBlockSetoranOnly"].forEach(t=>{const e=document.querySelector("#"+t+" > p");if(!e)return;const n=e.querySelectorAll(".lbl-metode-jilid");for(let r=1;r<n.length;r++)n[r].remove()})}["metodeCombined","metodeSetoranOnly"].forEach(t=>{var e;(e=document.getElementById(t))==null||e.addEventListener("change",()=>setTimeout(x,30))}),setTimeout(x,100);const _=setInterval(x,500),A="2026-09-07-1",j=[...H,I];let D=!1;window.__pendingInputMode=null,typeof window.toggleMode!="function"&&(window.toggleMode=function(){},window.switchMode=function(t){window.__pendingInputMode=t});let P=j.length;return j.forEach(t=>{const e=document.createElement("script");e.src=`/js/${t}?v=${A}`,e.async=!1,e.dataset.inputPage=t,e.onload=e.onerror=()=>{if(e.onerror&&!e.dataset.ok&&(e.dataset.ok="1"),P--,P===0&&!D&&(document.dispatchEvent(new Event("DOMContentLoaded")),window.__pendingInputMode&&typeof window.switchMode=="function")){const n=document.querySelector(`input[name="input_mode"][value="${window.__pendingInputMode}"]`);n&&(n.checked=!0),window.switchMode(window.__pendingInputMode),window.__pendingInputMode=null}},document.body.appendChild(e)}),()=>{D=!0,clearInterval(_)}},[]);const k=L({santriList:a,sesiList:C,daftarSurat:b,tanggalHariIni:f,kelasName:w,tahunAjaran:q,csrf:m,flash:i,errors:M});return E.jsxs(T,{children:[E.jsx(O,{title:"Input Presensi & Setoran"}),E.jsx("div",{dangerouslySetInnerHTML:{__html:k}})]})}export{G as default};
