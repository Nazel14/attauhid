@extends('layouts.app')

@section('content')
<div class="pb-10">

    {{-- NOTIFICATIONS --}}
    @if(session('success'))
    <div id="notif-ok" class="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-80 z-[300] animate-slide-in">
        <div class="bg-emerald-600 border border-emerald-500/40 text-white px-4 py-3 rounded-xl shadow-2xl flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="flex-1 text-sm font-medium">{{ session('success') }}</p>
            <button onclick="document.getElementById('notif-ok').remove()" class="opacity-70 hover:opacity-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
    </div>
    @endif
    @if(session('error'))
    <div id="notif-err" class="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-80 z-[300] animate-slide-in">
        <div class="bg-red-600 border border-red-500/40 text-white px-4 py-3 rounded-xl shadow-2xl flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="flex-1 text-sm font-medium">{{ session('error') }}</p>
            <button onclick="document.getElementById('notif-err').remove()" class="opacity-70 hover:opacity-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
    </div>
    @endif

    <div class="max-w-7xl mx-auto px-3 sm:px-5 lg:px-8 pt-4 sm:pt-6 lg:pt-10">

        {{-- TOP BAR --}}
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('guru.dashboard') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <div class="flex-1 min-w-0">
                <h1 class="text-base sm:text-xl font-bold text-gray-900 dark:text-white truncate"> Data Presensi Santri</h1>
                <p class="text-[11px] text-gray-500 mt-0.5">{{ $kelas->nama }} • Guru: <span class="text-indigo-600 dark:text-indigo-400">{{ $guru->name }}</span></p>
            </div>
            <a href="{{ route('guru.input.create') }}"
               class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-500 border border-indigo-600 text-white text-xs font-bold rounded-xl transition-all active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span class="hidden sm:inline">Input Presensi</span>
                <span class="sm:hidden">Input</span>
            </a>
        </div>

        {{-- BADGE TAHUN AJARAN --}}
        @if(isset($tahunAjaranAktif) && $tahunAjaranAktif)
        <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-800/40 rounded-xl text-xs mb-4">
            <svg class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="text-gray-900 dark:text-white font-medium">{{ $tahunAjaranAktif->nama ?? $tahunAjaranAktif->name }}</span>
            <span class="px-1.5 py-0.5 bg-emerald-500/20 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 rounded-full text-[10px] font-semibold">Aktif</span>
        </div>
        @endif

        {{-- SANTRI CARDS GRID --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
            @forelse($santriList as $santri)
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm dark:shadow-none">

                {{-- Card Header --}}
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-2.5 min-w-0">
                        @if($santri->user && $santri->user->profile_photo)
                            <img src="{{ asset('storage/' . $santri->user->profile_photo) }}" alt="Foto" class="w-9 h-9 rounded-full object-cover object-top border border-gray-200 dark:border-gray-700 flex-shrink-0">
                        @else
                            <span class="w-9 h-9 rounded-full {{ $santri->gender === 'Perempuan' ? 'bg-pink-100 dark:bg-pink-950 border-pink-200 dark:border-pink-800 text-pink-600 dark:text-pink-400' : 'bg-blue-100 dark:bg-blue-950 border-blue-200 dark:border-blue-800 text-blue-600 dark:text-blue-400' }} border flex items-center justify-center text-xs font-bold flex-shrink-0">{{ strtoupper(substr($santri->name, 0, 1)) }}</span>
                        @endif
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $santri->name }}</p>
                            <p class="text-[10px] text-gray-500 dark:text-gray-600 font-mono">{{ $santri->nis }}</p>
                        </div>
                    </div>
                    <a href="{{ route('guru.presensis.detail', $santri->id) }}"
                       class="flex-shrink-0 inline-flex items-center gap-1 px-2.5 py-1.5 bg-indigo-100 hover:bg-indigo-200 border border-indigo-300 text-indigo-700 dark:bg-indigo-600/20 dark:hover:bg-indigo-600/40 dark:border-indigo-600/30 dark:text-indigo-400 text-[10px] font-bold rounded-lg transition-colors">
                        Detail
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

                {{-- Calendar Navigation --}}
                <div class="flex items-center justify-between px-4 py-2 bg-gray-50 dark:bg-gray-900/40">
                    <button onclick="changeCalendarView({{ $santri->id }}, 'prev')"
                            class="w-6 h-6 rounded flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300" id="calendar-title-{{ $santri->id }}">
                        {{ now()->locale('id')->isoFormat('MMMM YYYY') }}
                    </p>
                    <button onclick="changeCalendarView({{ $santri->id }}, 'next')"
                            class="w-6 h-6 rounded flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>

                {{-- Calendar Grid --}}
                <div class="px-3 py-2">
                    <div id="calendar-{{ $santri->id }}" class="calendar-grid" data-santri-id="{{ $santri->id }}"></div>
                </div>

                {{-- Legend --}}
                <div class="px-4 py-2.5 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                    <div class="grid grid-cols-4 gap-1">
                        <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500 flex-shrink-0"></span><span class="text-[10px] text-gray-500">Hadir</span></div>
                        <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-500 flex-shrink-0"></span><span class="text-[10px] text-gray-500">Alpha</span></div>
                        <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500 flex-shrink-0"></span><span class="text-[10px] text-gray-500">Izin</span></div>
                        <div class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-orange-500 flex-shrink-0"></span><span class="text-[10px] text-gray-500">Sakit</span></div>
                    </div>
                </div>

            </div>
            @empty
            <div class="col-span-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl py-16 text-center shadow-sm dark:shadow-none">
                <div class="text-4xl mb-3"></div>
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Belum ada data santri</p>
            </div>
            @endforelse
        </div>

    </div>
</div>

<style>
    @keyframes slide-in { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
    .animate-slide-in { animation:slide-in .25s ease-out; }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 2px;
    }
    .calendar-day {
        background: #f3f4f6;
        border-radius: 4px;
        padding: 3px;
        min-height: 44px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        transition: all 0.15s;
        cursor: pointer;
    }
    html.dark .calendar-day { background: #1f2937; }
    .calendar-day:hover { background: #e5e7eb; transform: scale(1.05); }
    html.dark .calendar-day:hover { background: #374151; }
    .calendar-day.today { background: #4f46e5; }
    html.dark .calendar-day.today { background: #4f46e5; }
    .calendar-day.other-month { opacity: 0.15; pointer-events: none; }
    .day-number { font-size: 10px; font-weight: 700; color: #374151; margin-bottom: 2px; }
    html.dark .day-number { color: #e5e7eb; }
    .calendar-day.today .day-number { color: #fff; }
    .day-name { font-size: 7px; color: rgba(75,85,99,0.6); text-transform: uppercase; margin-bottom: 1px; }
    html.dark .day-name { color: rgba(255,255,255,0.5); }
    .calendar-day.today .day-name { color: rgba(255,255,255,0.7); }

    .indicators { display: grid; grid-template-columns: repeat(2, 1fr); gap: 2px; justify-content: center; }

    .indicator {
        width: var(--dot, 10px);
        height: var(--dot, 10px);
        border-radius: 50%;
        background: rgba(255,255,255,0.10);
    }
    html.dark .indicator { background: rgba(255,255,255,0.10); }

    /* Indikator status — bulat penuh solid (override aturan dasar) */
    .indicator.hadir,  html.dark .indicator.hadir { background: #22c55e; }
    .indicator.alpha,  html.dark .indicator.alpha { background: #ef4444; }
    .indicator.izin,   html.dark .indicator.izin  { background: #f59e0b; }
    .indicator.sakit,  html.dark .indicator.sakit { background: #f97316; }

    /* Pertegas indikator di kotak hari ini (latar ungu) */
    
    .calendar-day.today .indicator.hadir { background: #4ade80; }
    .calendar-day.today .indicator.alpha { background: #f87171; }
    .calendar-day.today .indicator.izin  { background: #fbbf24; }
    .calendar-day.today .indicator.sakit { background: #fb923c; }

    ::-webkit-scrollbar { width:4px; height:4px; }
    ::-webkit-scrollbar-track { background:transparent; }
    ::-webkit-scrollbar-thumb { background:#9ca3af; border-radius:99px; }
    html.dark ::-webkit-scrollbar-thumb { background:#374151; }
</style>

<script>
const presensiData = @json($presensiData ?? []);
const sesiList = @json($sesiList->pluck('sesi_name') ?? []);
const calendarStates = {};

function initCalendar(santriId) {
    const now = new Date();
    calendarStates[santriId] = { currentMonth: now.getMonth(), currentYear: now.getFullYear() };
    renderCalendar(santriId);
}

function renderAllCalendars() {
    document.querySelectorAll('.calendar-grid').forEach(function (el) {
        const id = Number(el.dataset.santriId);
        if (!calendarStates[id]) {
            const now = new Date();
            calendarStates[id] = { currentMonth: now.getMonth(), currentYear: now.getFullYear() };
        }
        renderCalendar(id);
    });
}

function changeCalendarView(santriId, direction) {
    const state = calendarStates[santriId];
    if (direction === 'prev') {
        state.currentMonth--;
        if (state.currentMonth < 0) { state.currentMonth = 11; state.currentYear--; }
    } else {
        state.currentMonth++;
        if (state.currentMonth > 11) { state.currentMonth = 0; state.currentYear++; }
    }
    renderCalendar(santriId);
}

function renderCalendar(santriId) {
    const state = calendarStates[santriId];
    if (!state) return;
    const calendarEl = document.getElementById(`calendar-${santriId}`);
    const titleEl = document.getElementById(`calendar-title-${santriId}`);
    if (!calendarEl) return;

    const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    if (titleEl) titleEl.textContent = `${months[state.currentMonth]} ${state.currentYear}`;

    const firstDay = new Date(state.currentYear, state.currentMonth, 1);
    const lastDay  = new Date(state.currentYear, state.currentMonth + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startDow = firstDay.getDay();
    const today = new Date(); today.setHours(0,0,0,0);
    const dayNames = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
    const sesiCount = sesiList.length;
    // Kolom: 1-2 sesi → sebanyak sesi; 3-4 → 2 kolom; 5-9 → 3 kolom; 10+ → 4 kolom
    let gridCols;
    if (sesiCount <= 2)      gridCols = Math.max(sesiCount, 1);
    else if (sesiCount <= 4) gridCols = 2;
    else if (sesiCount <= 9) gridCols = 3;
    else                     gridCols = 4;
    // Bulat mengecil saat sesi makin banyak agar muat di kotak hari
    let dotSize;
    if (sesiCount <= 2)      dotSize = 10;
    else if (sesiCount <= 4) dotSize = 8;
    else if (sesiCount <= 6) dotSize = 6;
    else if (sesiCount <= 9) dotSize = 5;
    else                     dotSize = 4;
    const dotGap = dotSize >= 8 ? 2 : 1;

    // Ambil data presensi santri ini (akses dengan key string agar pasti cocok JSON)
    const dataSantri = presensiData[santriId] || presensiData[String(santriId)] || {};

    let html = '';
    for (let i = startDow - 1; i >= 0; i--) html += `<div class="calendar-day other-month"></div>`;

    for (let day = 1; day <= daysInMonth; day++) {
        const date  = new Date(state.currentYear, state.currentMonth, day);
        const dateStr = `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
        const isToday = date.toDateString() === today.toDateString();
        const presensiHari = dataSantri[dateStr] || {};

        const indicators = sesiList.map(sesi => {
            const status = presensiHari[sesi];
            let cls = 'indicator';
            if (status === 'Hadir') cls += ' hadir';
            else if (status === 'Alpha') cls += ' alpha';
            else if (status === 'Izin') cls += ' izin';
            else if (status === 'Sakit') cls += ' sakit';
            return `<div class="${cls}" title="${sesi}: ${status || 'Belum Absen'}"></div>`;
        }).join('');

        html += `<div class="calendar-day ${isToday ? 'today' : ''}"
                     onclick="showDayDetail(${santriId}, '${dateStr}', ${day}, '${months[state.currentMonth]}')">
                    <div class="day-name">${dayNames[date.getDay()]}</div>
                    <div class="day-number">${day}</div>
                    <div class="indicators" style="grid-template-columns: repeat(${gridCols}, 1fr); gap: ${dotGap}px; --dot: ${dotSize}px;">${indicators}</div>
                 </div>`;
    }

    const totalCells = Math.ceil((startDow + daysInMonth) / 7) * 7;
    for (let i = 0; i < totalCells - (startDow + daysInMonth); i++) html += `<div class="calendar-day other-month"></div>`;
    calendarEl.innerHTML = html;
}

function showDayDetail(santriId, dateStr, day, monthName) {
    const dataSantri = presensiData[santriId] || presensiData[String(santriId)] || {};
    const presensi = dataSantri[dateStr];
    if (!presensi || Object.keys(presensi).length === 0) {
        alert(`Belum ada data presensi untuk tanggal ${day} ${monthName}`);
        return;
    }
    let detail = `📅 ${day} ${monthName}\n\n`;
    sesiList.forEach(sesi => {
        const status = presensi[sesi] || 'Belum Absen';
        const emoji = status==='Hadir'?'✅':status==='Alpha'?'❌':status==='Izin'?'📝':status==='Sakit'?'🤒':'⚪';
        detail += `${emoji} ${sesi}: ${status}\n`;
    });
    alert(detail);
}

// Render kalender saat DOM siap, lalu render ulang sekali lagi setelah semua script settle.
document.addEventListener('DOMContentLoaded', function () {
    renderAllCalendars();
    setTimeout(renderAllCalendars, 400);

    const n = document.getElementById('notif-ok');
    if (n) setTimeout(() => { n.style.transition='opacity .4s'; n.style.opacity='0'; setTimeout(()=>n.remove(),400); }, 5000);
});
</script>
@endsection