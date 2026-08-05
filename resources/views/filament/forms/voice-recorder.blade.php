@php
    $statePath = $getStatePath();
@endphp

<div
    class="qaari-recorder"
    x-data="{
        state: $wire.$entangle('{{ $statePath }}'),
        recording: false,
        elapsed: 0,
        error: null,
        recorder: null,
        stream: null,
        chunks: [],
        timer: null,
        get hasRecording() {
            return !! (this.state && this.state.data);
        },
        get elapsedLabel() {
            const s = Math.floor(this.elapsed || 0);
            return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
        },
        get savedLabel() {
            const s = Math.floor((this.state && this.state.duration) || 0);
            return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
        },
        async start() {
            this.error = null;

            if (! navigator.mediaDevices || ! window.MediaRecorder) {
                this.error = 'Recording is not supported in this browser. Upload a file instead.';
                return;
            }

            try {
                this.stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            } catch (e) {
                this.error = 'Microphone permission denied or unavailable.';
                return;
            }

            this.chunks = [];
            const mime = MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : 'audio/mp4';
            this.recorder = new MediaRecorder(this.stream, { mimeType: mime });

            this.recorder.ondataavailable = (event) => {
                if (event.data && event.data.size) {
                    this.chunks.push(event.data);
                }
            };

            this.recorder.onstop = () => {
                const seconds = this.elapsed;
                const blob = new Blob(this.chunks, { type: mime });
                const reader = new FileReader();

                reader.onloadend = () => {
                    this.state = { data: reader.result, duration: seconds, mime: mime };
                };

                reader.readAsDataURL(blob);

                if (this.stream) {
                    this.stream.getTracks().forEach((track) => track.stop());
                    this.stream = null;
                }
            };

            this.recorder.start();
            this.recording = true;
            this.elapsed = 0;
            this.timer = setInterval(() => { this.elapsed++; }, 1000);
        },
        stop() {
            if (this.recorder && this.recording) {
                this.recorder.stop();
            }

            this.recording = false;

            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },
        toggle() {
            if (this.recording) {
                this.stop();
            } else {
                this.start();
            }
        },
        clear() {
            this.state = null;
            this.elapsed = 0;
            this.error = null;
        },
    }"
>
    <div class="qaari-recorder__row">
        <button
            type="button"
            class="qaari-recorder__btn"
            x-bind:class="recording ? 'is-recording' : ''"
            x-on:click="toggle()"
        >
            <span x-show="! recording">Record voice note</span>
            <span x-show="recording" x-cloak>Stop recording</span>
        </button>

        <span class="qaari-recorder__timer" x-show="recording" x-cloak>
            <span class="qaari-recorder__dot"></span>
            <span x-text="elapsedLabel"></span>
        </span>

        <template x-if="hasRecording && ! recording">
            <span class="qaari-recorder__saved">
                Recorded <span x-text="savedLabel"></span>
            </span>
        </template>
    </div>

    <template x-if="hasRecording && ! recording">
        <div class="qaari-recorder__preview">
            <audio controls class="qaari-recorder__audio" x-bind:src="state.data"></audio>
            <button type="button" class="qaari-recorder__clear" x-on:click="clear()">Discard</button>
        </div>
    </template>

    <p class="qaari-recorder__hint">
        Record feedback with your microphone, or upload an audio file below. Either one works.
    </p>

    <p class="qaari-recorder__error" x-show="error" x-text="error" x-cloak></p>
</div>
