{{-- AI ჩატ-ვიჯეტი: ჩასვი layout-ის ბოლოს </body>-მდე: <x-ai-chat-widget /> --}}
{{-- ფერების მოსარგებად გადააწერე --aic-accent შენი საიტის CSS-ში. --}}

<div id="aic-root">
    <button id="aic-toggle" aria-label="ჩატის გახსნა">
        <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 11.5a8.38 8.38 0 0 1-8.5 8.5 8.5 8.5 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 8.5-8.5 8.38 8.38 0 0 1 8.5 8.5z"/>
        </svg>
    </button>

    <div id="aic-panel" hidden>
        <header class="aic-header">
            <div>
                <strong>ასისტენტი</strong>
                <span class="aic-sub">გკითხავთ პროდუქტებზე და საიტზე</span>
            </div>
            <div class="aic-header-actions">
                <button id="aic-tts" class="aic-icon-btn" title="პასუხების გახმოვანება" aria-pressed="false">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 5 6 9H2v6h4l5 4V5z"/><path d="M15.5 8.5a5 5 0 0 1 0 7"/>
                    </svg>
                </button>
                <button id="aic-close" class="aic-icon-btn" aria-label="დახურვა">✕</button>
            </div>
        </header>

        <div id="aic-messages" class="aic-messages">
            <div class="aic-msg aic-msg-bot">
                გამარჯობა! მკითხეთ პროდუქტებზე, ფასებზე ან საიტის ნებისმიერ ინფორმაციაზე — ტექსტით ან ხმით 🎙
            </div>
        </div>

        <form id="aic-form" class="aic-form">
            <button type="button" id="aic-mic" class="aic-icon-btn" title="ხმით კითხვა">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                    <path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/>
                </svg>
            </button>
            <input id="aic-input" type="text" placeholder="დაწერეთ კითხვა…" autocomplete="off" maxlength="2000">
            <button type="submit" id="aic-send" class="aic-icon-btn aic-send" aria-label="გაგზავნა">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
            </button>
        </form>
    </div>
</div>

<style>
#aic-root {
    --aic-accent: #1f5c4d;
    --aic-accent-soft: #e8f2ef;
    --aic-bg: #ffffff;
    --aic-text: #1d2422;
    --aic-muted: #6b7672;
    --aic-radius: 14px;
    position: fixed; right: 22px; bottom: 22px; z-index: 9999;
    font-family: inherit; color: var(--aic-text);
}
#aic-toggle {
    width: 56px; height: 56px; border-radius: 50%; border: none; cursor: pointer;
    background: var(--aic-accent); color: #fff;
    box-shadow: 0 6px 24px rgba(0,0,0,.18);
    display: grid; place-items: center;
    transition: transform .15s ease;
}
#aic-toggle:hover { transform: scale(1.06); }
#aic-panel {
    position: absolute; right: 0; bottom: 70px;
    width: min(370px, calc(100vw - 32px)); height: 520px; max-height: 75vh;
    background: var(--aic-bg); border-radius: var(--aic-radius);
    box-shadow: 0 12px 48px rgba(0,0,0,.22);
    display: flex; flex-direction: column; overflow: hidden;
}
.aic-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 14px 16px; background: var(--aic-accent); color: #fff;
}
.aic-header .aic-sub { display: block; font-size: 12px; opacity: .8; }
.aic-header-actions { display: flex; gap: 6px; }
.aic-icon-btn {
    border: none; background: transparent; color: inherit; cursor: pointer;
    width: 34px; height: 34px; border-radius: 8px; display: grid; place-items: center;
}
.aic-header .aic-icon-btn:hover { background: rgba(255,255,255,.15); }
.aic-header .aic-icon-btn[aria-pressed="true"] { background: rgba(255,255,255,.25); }
.aic-messages {
    flex: 1; overflow-y: auto; padding: 14px;
    display: flex; flex-direction: column; gap: 10px;
}
.aic-msg {
    max-width: 85%; padding: 10px 13px; border-radius: 12px;
    font-size: 14px; line-height: 1.5; white-space: pre-wrap; word-wrap: break-word;
}
.aic-msg-bot  { background: var(--aic-accent-soft); align-self: flex-start; border-bottom-left-radius: 4px; }
.aic-msg-user { background: var(--aic-accent); color: #fff; align-self: flex-end; border-bottom-right-radius: 4px; }
.aic-sources { font-size: 12px; margin-top: 6px; }
.aic-sources a { color: var(--aic-accent); }
.aic-escalate {
    margin-top: 8px; font-size: 12px; border: 1px solid var(--aic-accent);
    color: var(--aic-accent); background: transparent; border-radius: 8px;
    padding: 4px 10px; cursor: pointer;
}
.aic-escalate:hover { background: var(--aic-accent-soft); }
.aic-typing { color: var(--aic-muted); font-style: italic; }
.aic-form {
    display: flex; align-items: center; gap: 6px;
    padding: 10px; border-top: 1px solid #e7e9e8;
}
.aic-form .aic-icon-btn { color: var(--aic-muted); }
.aic-form .aic-icon-btn:hover { background: var(--aic-accent-soft); color: var(--aic-accent); }
#aic-mic.aic-recording { color: #c0392b; animation: aic-pulse 1.2s infinite; }
@keyframes aic-pulse { 50% { opacity: .4; } }
#aic-input {
    flex: 1; border: 1px solid #d8dcda; border-radius: 10px;
    padding: 9px 12px; font-size: 14px; outline: none;
}
#aic-input:focus { border-color: var(--aic-accent); }
.aic-send { background: var(--aic-accent) !important; color: #fff !important; }
@media (prefers-reduced-motion: reduce) {
    #aic-toggle, #aic-mic { transition: none; animation: none; }
}
</style>

<script>
(function () {
    const API       = '/api/ai-chat';
    const csrf      = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const panel     = document.getElementById('aic-panel');
    const toggle    = document.getElementById('aic-toggle');
    const closeBtn  = document.getElementById('aic-close');
    const form      = document.getElementById('aic-form');
    const input     = document.getElementById('aic-input');
    const micBtn    = document.getElementById('aic-mic');
    const ttsBtn    = document.getElementById('aic-tts');
    const messages  = document.getElementById('aic-messages');

    const sessionId = sessionStorage.getItem('aic-session')
        || crypto.randomUUID();
    sessionStorage.setItem('aic-session', sessionId);

    let history    = [];
    let ttsEnabled = false;
    let recorder   = null;
    let recording  = false;

    toggle.addEventListener('click', () => { panel.hidden = !panel.hidden; if (!panel.hidden) input.focus(); });
    closeBtn.addEventListener('click', () => { panel.hidden = true; });

    ttsBtn.addEventListener('click', () => {
        ttsEnabled = !ttsEnabled;
        ttsBtn.setAttribute('aria-pressed', String(ttsEnabled));
        if (!ttsEnabled) speechSynthesis.cancel();
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const text = input.value.trim();
        if (text) { input.value = ''; send(text, false, false); }
    });

    function addMsg(text, who) {
        const el = document.createElement('div');
        el.className = 'aic-msg aic-msg-' + who;
        el.textContent = text;
        messages.appendChild(el);
        messages.scrollTop = messages.scrollHeight;
        return el;
    }

    async function send(text, escalate, voice) {
        addMsg(text, 'user');
        const typing = addMsg('ვფიქრობ…', 'bot');
        typing.classList.add('aic-typing');

        try {
            const res = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ message: text, history, session_id: sessionId, escalate, voice }),
            });
            const data = await res.json();
            typing.remove();

            const botEl = addMsg(data.answer, 'bot');

            // წყაროების ლინკები
            const links = (data.sources || []).filter(s => s.url);
            if (links.length) {
                const src = document.createElement('div');
                src.className = 'aic-sources';
                src.append('იხილეთ: ');
                links.slice(0, 3).forEach((s, i) => {
                    const a = document.createElement('a');
                    a.href = s.url; a.textContent = s.title || 'ბმული'; a.target = '_blank';
                    if (i) src.append(' · ');
                    src.appendChild(a);
                });
                botEl.appendChild(src);
            }

            // escalation ღილაკი — მხოლოდ უფასო ჯაჭვის პასუხზე (reason === 'default')
            if (data.provider && data.reason === 'default') {
                const btn = document.createElement('button');
                btn.className = 'aic-escalate';
                btn.textContent = 'უკეთესი პასუხი მინდა';
                btn.addEventListener('click', () => { btn.remove(); send(text, true, voice); }, { once: true });
                botEl.appendChild(btn);
            }

            history.push({ role: 'user', content: text }, { role: 'assistant', content: data.answer });
            history = history.slice(-16);

            if (ttsEnabled && data.answer) speak(data.answer);
        } catch {
            typing.textContent = 'შეცდომა — სცადეთ ხელახლა.';
        }
    }

    function speak(text) {
        speechSynthesis.cancel();
        const u = new SpeechSynthesisUtterance(text);
        u.lang = 'ka-GE';
        const ka = speechSynthesis.getVoices().find(v => v.lang.startsWith('ka'));
        if (ka) u.voice = ka;
        speechSynthesis.speak(u);
    }

    // ── ხმოვანი ჩაწერა ──────────────────────────────────────
    micBtn.addEventListener('click', async () => {
        if (recording) { recorder.stop(); return; }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            const chunks = [];
            recorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });

            recorder.ondataavailable = (e) => chunks.push(e.data);
            recorder.onstop = async () => {
                recording = false;
                micBtn.classList.remove('aic-recording');
                stream.getTracks().forEach(t => t.stop());

                const blob = new Blob(chunks, { type: 'audio/webm' });
                const fd = new FormData();
                fd.append('audio', blob, 'audio.webm');

                input.placeholder = 'ვამუშავებ ხმას…';
                const res = await fetch(API + '/transcribe', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf },
                    body: fd,
                });
                const data = await res.json();
                input.placeholder = 'დაწერეთ კითხვა…';

                if (data.text) send(data.text, false, true);
                else addMsg(data.error || 'ხმის ამოცნობა ვერ მოხერხდა.', 'bot');
            };

            recorder.start();
            recording = true;
            micBtn.classList.add('aic-recording');
        } catch {
            addMsg('მიკროფონზე წვდომა ვერ მოხერხდა — შეამოწმეთ ბრაუზერის ნებართვა.', 'bot');
        }
    });
})();
</script>
