/**
 * ✅ AUDIO RECORDER - VERSION 4.1 FIXED
 * Fixes:
 * - Prevented double recording initialization
 * - Fixed stop recording functionality
 * - Added proper cleanup on stop
 * - Added recording state validation
 */

console.log('🔄 Loading audio-recorder.js v4.1 FIXED...');

const MAX_RECORDING_DURATION = 15;

// =====================================================
// GLOBAL STATE
// =====================================================
let mediaRecorders = {
    Combined: null,
    SetoranOnly: null
};
let audioChunksStore = {
    Combined: [],
    SetoranOnly: []
};
let recordingTimers = {
    Combined: null,
    SetoranOnly: null
};
let recordingStartTimes = {
    Combined: null,
    SetoranOnly: null
};
let pausedDurations = {
    Combined: 0,
    SetoranOnly: 0
};
let pauseStartTimes = {
    Combined: null,
    SetoranOnly: null
};
let isPaused = {
    Combined: false,
    SetoranOnly: false
};
let isRecording = {
    Combined: false,
    SetoranOnly: false
};
let mediaStreams = {
    Combined: null,
    SetoranOnly: null
};
let autoStopTimers = {
    Combined: null,
    SetoranOnly: null
};

// =====================================================
// FORMAT TIME HELPER
// =====================================================
function formatTime(seconds) {
    const hrs = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    
    if (hrs > 0) {
        return `${hrs}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    return `${mins}:${secs.toString().padStart(2, '0')}`;
}

// =====================================================
// UPDATE TIMER DISPLAY
// =====================================================
function updateTimerDisplay(mode) {
    if (!recordingStartTimes[mode]) return;
    
    let elapsed;
    if (isPaused[mode]) {
        elapsed = Math.floor((pauseStartTimes[mode] - recordingStartTimes[mode] - pausedDurations[mode]) / 1000);
    } else {
        elapsed = Math.floor((Date.now() - recordingStartTimes[mode] - pausedDurations[mode]) / 1000);
    }
    
    const timerElement = document.getElementById(`recordingTimer${mode}`);
    
    if (timerElement) {
        const pauseIndicator = isPaused[mode] ? ' ⏸️ JEDA' : '';
        
        // ✅ FIX: Tampilkan countdown sisa waktu dari MAX_RECORDING_DURATION
        const remaining = Math.max(0, MAX_RECORDING_DURATION - elapsed);
        
        // Warna berubah saat <= 5 detik tersisa (peringatan)
        const warningStyle = remaining <= 5 && !isPaused[mode] ? ' color: #ef4444; font-weight: 700;' : '';
        
        timerElement.innerHTML = `<span style="${warningStyle}">⏱️ ${formatTime(elapsed)} / ${formatTime(MAX_RECORDING_DURATION)} (sisa ${remaining}s)${pauseIndicator}</span>`;
        timerElement.classList.remove('hidden');
    }
}

// =====================================================
// GET ELEMENTS WITH NULL CHECK
// =====================================================
function getRecordingElements(mode) {
    const elements = {
        startBtn: document.getElementById(`startRecording${mode}`),
        pauseBtn: document.getElementById(`pauseRecording${mode}`),
        stopBtn: document.getElementById(`stopRecording${mode}`),
        clearBtn: document.getElementById(`clearRecording${mode}`),
        audioPlayer: document.getElementById(`audioPlayback${mode}`),
        timerDisplay: document.getElementById(`recordingTimer${mode}`),
        audioDataInput: document.getElementById(`audioData${mode}`)
    };
    
    return elements;
}

// =====================================================
// CLEANUP MEDIA RESOURCES
// =====================================================
function cleanupMediaResources(mode) {
    console.log(`🧹 Cleaning up media resources for ${mode}...`);
    
    // Stop and cleanup media stream
    if (mediaStreams[mode]) {
        mediaStreams[mode].getTracks().forEach(track => {
            track.stop();
            console.log(`🛑 Track stopped: ${track.kind}`);
        });
        mediaStreams[mode] = null;
    }
    
    // Stop timer
    if (recordingTimers[mode]) {
        clearInterval(recordingTimers[mode]);
        recordingTimers[mode] = null;
    }
    
    // ✅ FIX: Bersihkan auto-stop timer juga
    if (autoStopTimers[mode]) {
        clearTimeout(autoStopTimers[mode]);
        autoStopTimers[mode] = null;
        console.log(`🛑 Auto-stop timer cleared for ${mode}`);
    }
}

// =====================================================
// START RECORDING
// =====================================================
async function startRecording(mode) {
    console.log('🎤 startRecording() called for mode:', mode);
    
    // ✅ PREVENT DOUBLE RECORDING
    if (isRecording[mode]) {
        console.warn(`⚠️ Already recording for ${mode}, ignoring duplicate call`);
        return;
    }
    
    const elements = getRecordingElements(mode);
    
    if (!elements.startBtn || !elements.pauseBtn || !elements.stopBtn) {
        console.error('❌ Required buttons not found for mode:', mode);
        alert('⚠️ Error: Recording buttons not found. Please refresh the page.');
        return;
    }
    
    // Clear previous chunks and reset states
    audioChunksStore[mode] = [];
    pausedDurations[mode] = 0;
    isPaused[mode] = false;
    isRecording[mode] = true;
    console.log('🗑️ Audio chunks cleared for mode:', mode);
    
    try {
        console.log('🎙️ Requesting microphone access...');
        const stream = await navigator.mediaDevices.getUserMedia({ 
            audio: {
                echoCancellation: true,
                noiseSuppression: true,
                sampleRate: 44100
            } 
        });
        
        // Store stream reference
        mediaStreams[mode] = stream;
        console.log('✅ Microphone access granted');
        
        // Detect best MIME type
        let mimeType = 'audio/webm';
        if (MediaRecorder.isTypeSupported('audio/webm;codecs=opus')) {
            mimeType = 'audio/webm;codecs=opus';
        } else if (MediaRecorder.isTypeSupported('audio/ogg;codecs=opus')) {
            mimeType = 'audio/ogg;codecs=opus';
        } else if (MediaRecorder.isTypeSupported('audio/mp4')) {
            mimeType = 'audio/mp4';
        }
        
        console.log('🎬 Creating MediaRecorder with mimeType:', mimeType);
        
        mediaRecorders[mode] = new MediaRecorder(stream, { 
            mimeType: mimeType,
            audioBitsPerSecond: 128000
        });
        
        // ✅ Handle data available
        mediaRecorders[mode].ondataavailable = (event) => {
            if (event.data.size > 0) {
                audioChunksStore[mode].push(event.data);
                console.log(`📊 Audio chunk recorded (${mode}):`, event.data.size, 'bytes');
            }
        };
        
        // ✅ Handle recording stop
        mediaRecorders[mode].onstop = () => {
            console.log(`⏹️ Recording stopped (${mode}), processing audio...`);
            
            // Cleanup resources
            cleanupMediaResources(mode);
            
            // Reset state
            isRecording[mode] = false;
            isPaused[mode] = false;
            
            // Create blob and process
            if (audioChunksStore[mode].length > 0) {
                const audioBlob = new Blob(audioChunksStore[mode], { type: mimeType });
                console.log(`✅ Audio blob created (${mode}):`, audioBlob.size, 'bytes');
                
                processRecordedAudio(audioBlob, mode);
            } else {
                console.warn(`⚠️ No audio chunks recorded for ${mode}`);
            }
        };
        
        // ✅ Handle errors
        mediaRecorders[mode].onerror = (event) => {
            console.error(`❌ MediaRecorder error (${mode}):`, event.error);
            cleanupMediaResources(mode);
            isRecording[mode] = false;
        };
        
        // ✅ Start recording
        mediaRecorders[mode].start(1000); // Collect data every 1 second
        recordingStartTimes[mode] = Date.now();
        
        // ✅ Update UI
        if (elements.startBtn) {
            elements.startBtn.classList.add('hidden');
        }
        
        if (elements.pauseBtn) {
            elements.pauseBtn.classList.remove('hidden');
            elements.pauseBtn.disabled = false;
            elements.pauseBtn.innerHTML = '<span class="text-base">⏸️</span><span>Jeda</span>';
            elements.pauseBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
            elements.pauseBtn.classList.add('bg-yellow-600', 'hover:bg-yellow-700');
        }
        
        if (elements.stopBtn) {
            elements.stopBtn.classList.remove('hidden');
            elements.stopBtn.disabled = false;
            elements.stopBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
            elements.stopBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
        }
        
        
        // ✅ Start timer display
        recordingTimers[mode] = setInterval(() => {
            updateTimerDisplay(mode);
        }, 1000);
        
        updateTimerDisplay(mode);
        
        // ✅ FIX: Auto-stop rekaman setelah MAX_RECORDING_DURATION detik
        autoStopTimers[mode] = setTimeout(() => {
            console.log(`⏰ Auto-stop triggered: ${MAX_RECORDING_DURATION}s limit reached for ${mode}`);
            
            // Tampilkan notifikasi singkat ke user
            const timerElement = document.getElementById(`recordingTimer${mode}`);
            if (timerElement) {
                timerElement.innerHTML = `<span style="color:#22c55e;font-weight:700;">✅ Batas ${MAX_RECORDING_DURATION} detik tercapai — rekaman otomatis berhenti</span>`;
            }
            
            // Stop rekaman otomatis
            stopRecording(mode);
        }, MAX_RECORDING_DURATION * 1000);
        
        console.log(`🎤 Recording started successfully for ${mode}! (auto-stop in ${MAX_RECORDING_DURATION}s)`);
        
    } catch (error) {
        console.error('❌ Error starting recording:', error);
        isRecording[mode] = false;
        
        let errorMessage = 'Gagal mengakses mikrofon. ';
        
        if (error.name === 'NotAllowedError') {
            errorMessage += 'Izinkan akses mikrofon di browser Anda.';
        } else if (error.name === 'NotFoundError') {
            errorMessage += 'Mikrofon tidak ditemukan.';
        } else {
            errorMessage += error.message;
        }
        
        alert(errorMessage);
    }
}

// =====================================================
// PAUSE/RESUME RECORDING
// =====================================================
function pauseRecording(mode) {
    console.log('⏸️ pauseRecording() called for mode:', mode);
    
    const elements = getRecordingElements(mode);
    
    if (!mediaRecorders[mode]) {
        console.warn(`⚠️ No active MediaRecorder for ${mode}`);
        return;
    }
    
    if (isPaused[mode]) {
        // RESUME
        console.log(`▶️ Resuming recording for ${mode}...`);
        
        if (mediaRecorders[mode].state === 'paused') {
            mediaRecorders[mode].resume();
            
            // Calculate paused duration
            const pauseDuration = Date.now() - pauseStartTimes[mode];
            pausedDurations[mode] += pauseDuration;
            
            isPaused[mode] = false;
            
            if (elements.pauseBtn) {
                elements.pauseBtn.innerHTML = '<span class="text-base">⏸️</span><span>Jeda</span>';
                elements.pauseBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
                elements.pauseBtn.classList.add('bg-yellow-600', 'hover:bg-yellow-700');
            }
            
            // ✅ FIX: Restart auto-stop timer dengan sisa waktu yang ada
            const elapsedSec = Math.floor((Date.now() - recordingStartTimes[mode] - pausedDurations[mode]) / 1000);
            const remainingMs = Math.max(0, (MAX_RECORDING_DURATION - elapsedSec) * 1000);
            
            if (remainingMs > 0) {
                autoStopTimers[mode] = setTimeout(() => {
                    console.log(`⏰ Auto-stop (after resume): limit reached for ${mode}`);
                    const timerElement = document.getElementById(`recordingTimer${mode}`);
                    if (timerElement) {
                        timerElement.innerHTML = `<span style="color:#22c55e;font-weight:700;">✅ Batas ${MAX_RECORDING_DURATION} detik tercapai — rekaman otomatis berhenti</span>`;
                    }
                    stopRecording(mode);
                }, remainingMs);
                console.log(`✅ Recording resumed for ${mode} (auto-stop in ${Math.ceil(remainingMs/1000)}s)`);
            } else {
                // Sudah lewat batas saat resume → langsung stop
                stopRecording(mode);
            }
        }
    } else {
        // PAUSE
        console.log(`⏸️ Pausing recording for ${mode}...`);
        
        if (mediaRecorders[mode].state === 'recording') {
            mediaRecorders[mode].pause();
            pauseStartTimes[mode] = Date.now();
            isPaused[mode] = true;
            
            // ✅ FIX: Pause auto-stop timer juga (clear, akan di-restart saat resume)
            if (autoStopTimers[mode]) {
                clearTimeout(autoStopTimers[mode]);
                autoStopTimers[mode] = null;
            }
            
            if (elements.pauseBtn) {
                elements.pauseBtn.innerHTML = '<span class="text-base">▶️</span><span>Lanjutkan</span>';
                elements.pauseBtn.classList.remove('bg-yellow-600', 'hover:bg-yellow-700');
                elements.pauseBtn.classList.add('bg-green-600', 'hover:bg-green-700');
            }
            
            updateTimerDisplay(mode);
            
            console.log(`✅ Recording paused for ${mode}`);
        }
    }
}

// =====================================================
// STOP RECORDING - FIXED VERSION
// =====================================================
function stopRecording(mode) {
    console.log('⏹️ stopRecording() called for mode:', mode);
    
    const elements = getRecordingElements(mode);
    
    if (!mediaRecorders[mode]) {
        console.warn(`⚠️ No active MediaRecorder for ${mode}`);
        return;
    }
    
    const state = mediaRecorders[mode].state;
    console.log(`📊 Current MediaRecorder state: ${state}`);
    
    if (state === 'recording' || state === 'paused') {
        console.log(`🛑 Stopping MediaRecorder for ${mode}...`);
        
        // If paused, resume first to ensure proper stop
        if (state === 'paused') {
            console.log('▶️ Resuming from pause before stop...');
            mediaRecorders[mode].resume();
            
            // Small delay to ensure state change
            setTimeout(() => {
                if (mediaRecorders[mode] && mediaRecorders[mode].state === 'recording') {
                    mediaRecorders[mode].stop();
                    console.log('✅ MediaRecorder stopped after resume');
                }
            }, 100);
        } else {
            // Direct stop if recording
            mediaRecorders[mode].stop();
            console.log('✅ MediaRecorder stopped');
        }
        
        // Update UI immediately
        if (elements.pauseBtn) {
            elements.pauseBtn.classList.add('hidden');
        }
        
        if (elements.stopBtn) {
            elements.stopBtn.classList.add('hidden');
        }
        
    } else {
        console.warn(`⚠️ MediaRecorder not in recording/paused state for ${mode}: ${state}`);
        // Force cleanup if in invalid state
        cleanupMediaResources(mode);
        isRecording[mode] = false;
    }
}

// =====================================================
// PROCESS RECORDED AUDIO
// =====================================================
function processRecordedAudio(audioBlob, mode) {
    console.log(`🎵 Processing recorded audio for mode: ${mode}`);
    
    const elements = getRecordingElements(mode);
    
    // Create audio URL
    const audioUrl = URL.createObjectURL(audioBlob);
    console.log('🔗 Audio URL created');
    
    // Update audio player
    if (elements.audioPlayer) {
        elements.audioPlayer.src = audioUrl;
        elements.audioPlayer.classList.remove('hidden');
        elements.audioPlayer.load();
        console.log('✅ Audio player updated');
    }
    
    // Show clear button
    if (elements.clearBtn) {
        elements.clearBtn.classList.remove('hidden');
    }
    
    // Update timer to show final duration
    if (elements.timerDisplay && recordingStartTimes[mode]) {
        const duration = Math.floor((Date.now() - recordingStartTimes[mode] - pausedDurations[mode]) / 1000);
        elements.timerDisplay.textContent = `✅ Durasi rekaman: ${formatTime(duration)}`;
    }
    
    // Convert to base64 and store
    const reader = new FileReader();
    reader.readAsDataURL(audioBlob);
    reader.onloadend = function() {
        const base64Audio = reader.result;
        
        if (elements.audioDataInput) {
            elements.audioDataInput.value = base64Audio;
            console.log('✅ Audio data stored in hidden input');
        }
    };
    
    // Update start button to "Rekam Ulang"
    if (elements.startBtn) {
        elements.startBtn.classList.remove('hidden');
        elements.startBtn.disabled = false;
        elements.startBtn.innerHTML = '<span class="text-base">🔄</span><span>Rekam Ulang</span>';
    }
    
    console.log(`✅ Audio processing complete for ${mode}`);
}

// =====================================================
// CLEAR RECORDING
// =====================================================
function clearRecording(mode) {
    console.log(`🗑️ clearRecording() called for mode: ${mode}`);
    
    const elements = getRecordingElements(mode);
    
    // Stop recording if active
    if (mediaRecorders[mode]) {
        const state = mediaRecorders[mode].state;
        if (state === 'recording' || state === 'paused') {
            mediaRecorders[mode].stop();
        }
        mediaRecorders[mode] = null;
    }
    
    // Cleanup resources
    cleanupMediaResources(mode);
    
    // Clear state
    audioChunksStore[mode] = [];
    pausedDurations[mode] = 0;
    isPaused[mode] = false;
    isRecording[mode] = false;
    recordingStartTimes[mode] = null;
    pauseStartTimes[mode] = null;
    
    // Update UI
    if (elements.audioPlayer) {
        elements.audioPlayer.src = '';
        elements.audioPlayer.classList.add('hidden');
    }
    
    if (elements.clearBtn) {
        elements.clearBtn.classList.add('hidden');
    }
    
    if (elements.audioDataInput) {
        elements.audioDataInput.value = '';
    }
    
    if (elements.timerDisplay) {
        elements.timerDisplay.textContent = '';
        elements.timerDisplay.classList.add('hidden');
    }
    
    if (elements.startBtn) {
        elements.startBtn.classList.remove('hidden');
        elements.startBtn.disabled = false;
        elements.startBtn.innerHTML = '<span class="text-base">⏺️</span><span>Rekam</span>';
    }
    
    if (elements.pauseBtn) {
        elements.pauseBtn.classList.add('hidden');
    }
    
    if (elements.stopBtn) {
        elements.stopBtn.classList.add('hidden');
    }
    
    console.log(`✅ Recording cleared for ${mode}`);
}

// =====================================================
// EXPORT TO WINDOW
// =====================================================
window.startRecording = startRecording;
window.pauseRecording = pauseRecording;
window.stopRecording = stopRecording;
window.clearRecording = clearRecording;

console.log('✅ Audio Recording Functions exported to window');

// =====================================================
// DOM READY
// =====================================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 DOM loaded - Audio Recording System v4.1 FIXED initializing...');
    
    const hasCombinedForm = document.getElementById('startRecordingCombined');
    const hasSetoranOnlyForm = document.getElementById('startRecordingSetoranOnly');
    
    if (!hasCombinedForm && !hasSetoranOnlyForm) {
        console.log('ℹ️ Not on setoran form page');
        return;
    }
    
    if (hasCombinedForm) {
        console.log('✅ Combined form audio controls detected');
    }
    
    if (hasSetoranOnlyForm) {
        console.log('✅ Setoran Only form audio controls detected');
    }
    
    console.log('✅ Audio Recording System v4.1 FIXED ready');
});

console.log('✅ audio-recorder.js v4.1 FIXED loaded successfully');