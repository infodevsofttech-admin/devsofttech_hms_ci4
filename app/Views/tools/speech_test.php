<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Speech To Text Test</title>
    <style>
        :root {
            --bg1: #f0f9ff;
            --bg2: #ecfeff;
            --card: #ffffff;
            --text: #1f2937;
            --muted: #64748b;
            --line: #dbeafe;
            --focus: #0369a1;
            --focus-soft: #e0f2fe;
            --danger: #dc2626;
            --danger-soft: #fee2e2;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            color: var(--text);
            background: linear-gradient(130deg, var(--bg1), var(--bg2));
            min-height: 100vh;
        }

        .page {
            max-width: 900px;
            margin: 0 auto;
            padding: 24px 16px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
            padding: 18px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 24px;
        }

        .note {
            margin: 0 0 16px;
            color: var(--muted);
            font-size: 14px;
        }

        .field {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px;
            margin-bottom: 12px;
        }

        .field input {
            width: 100%;
            height: 44px;
            border-radius: 10px;
            border: 1px solid var(--line);
            padding: 0 12px;
            font-size: 14px;
            outline: none;
        }

        .field input:focus {
            border-color: var(--focus);
            box-shadow: 0 0 0 3px var(--focus-soft);
        }

        .mic-btn {
            width: 44px;
            height: 44px;
            border: 1px solid var(--line);
            background: #fff;
            border-radius: 10px;
            font-size: 18px;
            line-height: 1;
            cursor: pointer;
        }

        .mic-btn:hover {
            border-color: var(--focus);
        }

        .mic-btn.mode-server {
            border-color: #059669;
            background: #ecfdf5;
            color: #065f46;
        }

        .mic-btn.mode-browser {
            border-color: #1d4ed8;
            background: #eff6ff;
            color: #1e40af;
        }

        .mic-btn.mode-off {
            border-color: #94a3b8;
            background: #f8fafc;
            color: #64748b;
            cursor: not-allowed;
        }

        .mic-btn.listening {
            border-color: var(--danger);
            background: var(--danger-soft);
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.35);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(220, 38, 38, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(220, 38, 38, 0);
            }
        }

        .status {
            min-height: 20px;
            margin-top: 6px;
            font-size: 13px;
            color: var(--muted);
        }

        .status.error {
            color: var(--danger);
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .toolbar select {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            padding: 8px 10px;
            font-size: 13px;
        }

        .hint-wrap {
            margin-bottom: 12px;
        }

        .hint-wrap label {
            display: block;
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 6px;
        }

        .hint-wrap input {
            width: 100%;
            height: 40px;
            border-radius: 10px;
            border: 1px solid var(--line);
            padding: 0 12px;
            font-size: 13px;
            outline: none;
        }

        .hint-wrap input:focus {
            border-color: var(--focus);
            box-shadow: 0 0 0 3px var(--focus-soft);
        }

        .legend {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 12px;
        }

        .legend-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            display: inline-block;
        }

        .legend-dot.server {
            background: #059669;
        }

        .legend-dot.browser {
            background: #1d4ed8;
        }

        .legend-dot.off {
            background: #94a3b8;
        }

        @media (max-width: 560px) {
            .field input {
                height: 42px;
            }

            .mic-btn {
                width: 42px;
                height: 42px;
            }
        }
    </style>
</head>
<body>
<div class="page">
    <div class="card">
        <h1>Speech To Text Test</h1>
        <p class="note">Click the mic beside any textbox and speak. That textbox will be updated.</p>

        <div class="toolbar">
            <div>
                <label for="speech-lang">Language:</label>
                <select id="speech-lang">
                    <option value="en-IN">English (India)</option>
                    <option value="hi-IN">Hindi (India)</option>
                    <option value="en-US">English (US)</option>
                </select>
            </div>
        </div>

        <div class="legend">
            <span class="legend-item"><span class="legend-dot server"></span>Server API mode</span>
            <span class="legend-item"><span class="legend-dot browser"></span>Browser fallback mode</span>
            <span class="legend-item"><span class="legend-dot off"></span>Speech unavailable</span>
        </div>

        <div class="hint-wrap">
            <label for="medical-context">Optional medical terms hint (comma separated):</label>
            <input id="medical-context" type="text" placeholder="Example: Paracetamol, Cefixime, Amoxicillin clavulanate, Hypertension, Diabetes Mellitus">
        </div>

        <div class="field">
            <input id="speech-input-1" type="text" placeholder="Textbox 1">
            <button type="button" class="mic-btn" data-target="speech-input-1" aria-label="Speak for textbox 1">🎤</button>
        </div>

        <div class="field">
            <input id="speech-input-2" type="text" placeholder="Textbox 2">
            <button type="button" class="mic-btn" data-target="speech-input-2" aria-label="Speak for textbox 2">🎤</button>
        </div>

        <div class="field">
            <input id="speech-input-3" type="text" placeholder="Textbox 3">
            <button type="button" class="mic-btn" data-target="speech-input-3" aria-label="Speak for textbox 3">🎤</button>
        </div>

        <div class="field">
            <input id="speech-input-4" type="text" placeholder="Textbox 4">
            <button type="button" class="mic-btn" data-target="speech-input-4" aria-label="Speak for textbox 4">🎤</button>
        </div>

        <div class="field">
            <input id="speech-input-5" type="text" placeholder="Textbox 5">
            <button type="button" class="mic-btn" data-target="speech-input-5" aria-label="Speak for textbox 5">🎤</button>
        </div>

        <div id="speech-status" class="status"></div>
    </div>
</div>

<script>
    (function () {
        const statusEl = document.getElementById('speech-status');
        const micButtons = document.querySelectorAll('.mic-btn');
        const langEl = document.getElementById('speech-lang');
        const medicalContextEl = document.getElementById('medical-context');

        const SERVER_TRANSCRIBE_URL = 'http://139.59.13.39:8000/stt/transcribe';
        const SERVER_HEALTH_URL = 'http://139.59.13.39:8000/health';
        const SERVER_TIMEOUT_MS = 4000;

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        const hasBrowserSTT = !!SpeechRecognition;
        const hasMediaRecorder = typeof window.MediaRecorder !== 'undefined';

        let mode = 'off';
        let recognition = null;
        let activeInput = null;
        let activeButton = null;

        let mediaStream = null;
        let mediaRecorder = null;
        let mediaChunks = [];
        let isServerRecording = false;

        function setStatus(text, isError) {
            statusEl.textContent = text;
            if (isError) {
                statusEl.classList.add('error');
            } else {
                statusEl.classList.remove('error');
            }
        }

        function setListeningButton(button) {
            micButtons.forEach((item) => item.classList.remove('listening'));
            if (button) {
                button.classList.add('listening');
            }
        }

        function applyMode(newMode, message, isError) {
            mode = newMode;

            micButtons.forEach((button) => {
                button.classList.remove('mode-server', 'mode-browser', 'mode-off');
                if (mode === 'server') {
                    button.classList.add('mode-server');
                    button.disabled = false;
                } else if (mode === 'browser') {
                    button.classList.add('mode-browser');
                    button.disabled = false;
                } else {
                    button.classList.add('mode-off');
                    button.disabled = true;
                }
            });

            setStatus(message, !!isError);
        }

        function setupBrowserRecognition() {
            if (!hasBrowserSTT) {
                return;
            }

            recognition = new SpeechRecognition();
            recognition.lang = langEl ? langEl.value : 'en-IN';
            recognition.interimResults = true;
            recognition.continuous = false;

            recognition.onresult = function (event) {
                let transcript = '';
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    transcript += event.results[i][0].transcript;
                }

                if (activeInput) {
                    activeInput.value = transcript.trim();
                }
            };

            recognition.onerror = function (event) {
                setListeningButton(null);
                activeButton = null;
                setStatus('Speech error: ' + event.error, true);
            };

            recognition.onend = function () {
                setListeningButton(null);
                activeButton = null;
                setStatus('Done.', false);
            };
        }

        async function checkServerAvailability() {
            if (!window.fetch || !window.AbortController) {
                return false;
            }

            const controller = new AbortController();
            const timeoutId = window.setTimeout(function () {
                controller.abort();
            }, SERVER_TIMEOUT_MS);

            try {
                const response = await fetch(SERVER_HEALTH_URL, {
                    method: 'GET',
                    mode: 'cors',
                    signal: controller.signal,
                });

                return response.ok;
            } catch (error) {
                return false;
            } finally {
                window.clearTimeout(timeoutId);
            }
        }

        async function sendAudioToServer(audioBlob) {
            const formData = new FormData();
            formData.append('audio', audioBlob, 'speech.webm');
            formData.append('lang', langEl ? langEl.value : 'en-IN');
            formData.append('medical_context', medicalContextEl ? medicalContextEl.value : '');

            const response = await fetch(SERVER_TRANSCRIBE_URL, {
                method: 'POST',
                mode: 'cors',
                body: formData,
            });

            if (!response.ok) {
                let detail = '';
                try {
                    const errorPayload = await response.json();
                    detail = String(errorPayload.detail || '').trim();
                } catch (ignoreError) {
                    detail = '';
                }

                const err = new Error(detail || ('Server response: ' + response.status));
                err.statusCode = response.status;
                throw err;
            }

            const payload = await response.json();
            const text = String(payload.text || payload.transcript || payload.result || '').trim();
            if (!text) {
                throw new Error('Empty transcript from server');
            }

            return text;
        }

        function stopServerStream() {
            if (mediaStream) {
                mediaStream.getTracks().forEach(function (track) {
                    track.stop();
                });
            }

            mediaStream = null;
            mediaRecorder = null;
            mediaChunks = [];
            isServerRecording = false;
        }

        async function startServerRecording(button, input) {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                applyMode(hasBrowserSTT ? 'browser' : 'off', hasBrowserSTT
                    ? 'Microphone recording not available for server mode. Switched to browser fallback.'
                    : 'Microphone recording is not available in this browser.', !hasBrowserSTT);
                return;
            }

            if (isServerRecording && activeButton === button && mediaRecorder) {
                mediaRecorder.stop();
                setStatus('Processing audio on server...', false);
                return;
            }

            if (isServerRecording && mediaRecorder) {
                mediaRecorder.stop();
            }

            activeInput = input;
            activeButton = button;
            setListeningButton(button);

            try {
                mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(mediaStream);
                mediaChunks = [];

                mediaRecorder.ondataavailable = function (event) {
                    if (event.data && event.data.size > 0) {
                        mediaChunks.push(event.data);
                    }
                };

                mediaRecorder.onstop = async function () {
                    const audioBlob = new Blob(mediaChunks, { type: mediaRecorder && mediaRecorder.mimeType ? mediaRecorder.mimeType : 'audio/webm' });
                    stopServerStream();
                    setListeningButton(null);

                    if (!activeInput) {
                        return;
                    }

                    try {
                        const transcript = await sendAudioToServer(audioBlob);
                        activeInput.value = transcript;
                        setStatus('Transcription complete using server API.', false);
                    } catch (error) {
                        if (error && error.statusCode && error.statusCode >= 400 && error.statusCode < 500) {
                            setStatus('Server validation error: ' + (error.message || 'Invalid request'), true);
                        } else if (hasBrowserSTT) {
                            applyMode('browser', 'Server API unavailable. Switched to browser fallback mode.', false);
                        } else {
                            applyMode('off', 'Server API unavailable and browser fallback is not supported.', true);
                        }
                    } finally {
                        activeButton = null;
                    }
                };

                mediaRecorder.start();
                isServerRecording = true;
                setStatus('Recording... click same mic again to stop and transcribe.', false);
            } catch (error) {
                stopServerStream();
                setListeningButton(null);
                if (hasBrowserSTT) {
                    applyMode('browser', 'Could not start server recording. Switched to browser fallback mode.', false);
                } else {
                    applyMode('off', 'Could not access microphone and fallback is unavailable.', true);
                }
            }
        }

        function startBrowserRecognition(button, input) {
            if (!recognition) {
                applyMode('off', 'Browser speech recognition is not supported in this browser.', true);
                return;
            }

            if (activeButton === button) {
                recognition.stop();
                setStatus('Stopped listening.', false);
                return;
            }

            activeInput = input;
            activeButton = button;
            setListeningButton(button);
            setStatus('Listening with browser fallback... please speak now.', false);

            try {
                recognition.start();
            } catch (error) {
                setStatus('Could not start browser speech recognition. Please try again.', true);
            }
        }

        if (langEl) {
            langEl.addEventListener('change', function () {
                if (recognition) {
                    recognition.lang = langEl.value;
                }
            });
        }

        micButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const targetId = button.getAttribute('data-target');
                const input = targetId ? document.getElementById(targetId) : null;
                if (!input) {
                    return;
                }

                if (mode === 'server') {
                    startServerRecording(button, input);
                    return;
                }

                if (mode === 'browser') {
                    startBrowserRecognition(button, input);
                }
            });
        });

        (async function init() {
            setupBrowserRecognition();

            const serverAvailable = hasMediaRecorder && await checkServerAvailability();
            if (serverAvailable) {
                applyMode('server', 'Server API mode active. Mic color indicates server transcription.', false);
                return;
            }

            if (hasBrowserSTT) {
                applyMode('browser', 'Server API unavailable. Browser fallback mode is active (medical-term accuracy may be lower).', false);
                return;
            }

            applyMode('off', 'Speech is unavailable: server API is down and browser fallback is not supported.', true);
        })();
    })();
</script>
</body>
</html>
