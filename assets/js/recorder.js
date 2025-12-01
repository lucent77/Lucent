/**
 * Voice Recorder Module
 * Handles audio recording, preview, and upload
 */

class VoiceRecorder {
    constructor(options = {}) {
        this.mediaRecorder = null;
        this.audioChunks = [];
        this.stream = null;
        this.isRecording = false;
        this.isPaused = false;
        this.recordingStartTime = null;
        this.timerInterval = null;

        // Configuration
        this.options = {
            mimeType: this.getSupportedMimeType(),
            audioBitsPerSecond: 128000,
            onStart: options.onStart || (() => {}),
            onStop: options.onStop || (() => {}),
            onData: options.onData || (() => {}),
            onError: options.onError || ((error) => console.error('Recording error:', error)),
            onTimer: options.onTimer || (() => {})
        };

        // Bind methods
        this.start = this.start.bind(this);
        this.stop = this.stop.bind(this);
        this.pause = this.pause.bind(this);
        this.resume = this.resume.bind(this);
    }

    /**
     * Get supported MIME type for recording
     */
    getSupportedMimeType() {
        const types = [
            'audio/webm;codecs=opus',
            'audio/webm',
            'audio/ogg;codecs=opus',
            'audio/mp4',
            'audio/wav'
        ];

        for (const type of types) {
            if (MediaRecorder.isTypeSupported(type)) {
                return type;
            }
        }

        return 'audio/webm';
    }

    /**
     * Request microphone access and start recording
     */
    async start() {
        try {
            // Request microphone access
            this.stream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true,
                    sampleRate: 16000
                }
            });

            // Create MediaRecorder
            this.mediaRecorder = new MediaRecorder(this.stream, {
                mimeType: this.options.mimeType,
                audioBitsPerSecond: this.options.audioBitsPerSecond
            });

            // Clear previous chunks
            this.audioChunks = [];

            // Handle data available
            this.mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    this.audioChunks.push(event.data);
                    this.options.onData(event.data);
                }
            };

            // Handle recording stop
            this.mediaRecorder.onstop = () => {
                this.stopTimer();
                const audioBlob = new Blob(this.audioChunks, { type: this.options.mimeType });
                this.options.onStop(audioBlob);
            };

            // Handle errors
            this.mediaRecorder.onerror = (event) => {
                this.options.onError(event.error);
            };

            // Start recording
            this.mediaRecorder.start(1000); // Collect data every second
            this.isRecording = true;
            this.isPaused = false;
            this.recordingStartTime = Date.now();
            this.startTimer();
            this.options.onStart();

            return true;
        } catch (error) {
            this.options.onError(error);
            return false;
        }
    }

    /**
     * Stop recording
     */
    stop() {
        if (this.mediaRecorder && this.isRecording) {
            this.mediaRecorder.stop();
            this.isRecording = false;
            this.isPaused = false;

            // Stop all tracks
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
        }
    }

    /**
     * Pause recording
     */
    pause() {
        if (this.mediaRecorder && this.isRecording && !this.isPaused) {
            this.mediaRecorder.pause();
            this.isPaused = true;
            this.stopTimer();
        }
    }

    /**
     * Resume recording
     */
    resume() {
        if (this.mediaRecorder && this.isRecording && this.isPaused) {
            this.mediaRecorder.resume();
            this.isPaused = false;
            this.startTimer();
        }
    }

    /**
     * Start recording timer
     */
    startTimer() {
        this.timerInterval = setInterval(() => {
            const elapsed = Date.now() - this.recordingStartTime;
            this.options.onTimer(this.formatTime(elapsed));
        }, 100);
    }

    /**
     * Stop recording timer
     */
    stopTimer() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
            this.timerInterval = null;
        }
    }

    /**
     * Format milliseconds to MM:SS
     */
    formatTime(ms) {
        const seconds = Math.floor(ms / 1000);
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
    }

    /**
     * Get current recording status
     */
    getStatus() {
        return {
            isRecording: this.isRecording,
            isPaused: this.isPaused,
            duration: this.recordingStartTime ? Date.now() - this.recordingStartTime : 0
        };
    }

    /**
     * Check if browser supports recording
     */
    static isSupported() {
        return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder);
    }
}

/**
 * Audio Upload Handler
 * Handles uploading audio to the server
 */
class AudioUploader {
    constructor(apiBaseUrl = 'api') {
        this.apiBaseUrl = apiBaseUrl;
    }

    /**
     * Convert Blob to base64
     */
    async blobToBase64(blob) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onloadend = () => {
                const base64 = reader.result.split(',')[1];
                resolve(base64);
            };
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
    }

    /**
     * Upload audio blob to server
     */
    async upload(audioBlob, options = {}) {
        try {
            const base64Data = await this.blobToBase64(audioBlob);

            const formData = {
                audio_data: base64Data,
                mime_type: audioBlob.type,
                audio_type: options.audioType || 'initial'
            };

            // Add optional parameters
            if (options.caseId) {
                formData.case_id = options.caseId;
            }
            if (options.doctorId) {
                formData.doctor_id = options.doctorId;
            }
            if (options.patientName) {
                formData.patient_name = options.patientName;
            }

            const response = await fetch(`${this.apiBaseUrl}/upload_audio.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Upload failed');
            }

            return data;
        } catch (error) {
            console.error('Upload error:', error);
            throw error;
        }
    }

    /**
     * Transcribe uploaded audio
     */
    async transcribe(audioId, language = 'ko-KR') {
        try {
            const response = await fetch(`${this.apiBaseUrl}/transcribe.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    audio_id: audioId,
                    language: language
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Transcription failed');
            }

            return data;
        } catch (error) {
            console.error('Transcription error:', error);
            throw error;
        }
    }

    /**
     * Analyze transcript
     */
    async analyze(caseId) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/analyze.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    case_id: caseId
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Analysis failed');
            }

            return data;
        } catch (error) {
            console.error('Analysis error:', error);
            throw error;
        }
    }

    /**
     * Get follow-up question
     */
    async getFollowupQuestion(caseId, targetField = null) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/followup.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    case_id: caseId,
                    target_field: targetField
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Failed to get follow-up question');
            }

            return data;
        } catch (error) {
            console.error('Follow-up error:', error);
            throw error;
        }
    }

    /**
     * Submit follow-up answer
     */
    async submitFollowupAnswer(caseId, questionId, answer, answerAudioId = null) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/followup.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    case_id: caseId,
                    question_id: questionId,
                    answer: answer,
                    answer_audio_id: answerAudioId
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Failed to submit answer');
            }

            return data;
        } catch (error) {
            console.error('Answer submission error:', error);
            throw error;
        }
    }

    /**
     * Get TTS audio for question
     */
    async getTtsAudio(caseId, questionId) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/tts.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    case_id: caseId,
                    question_id: questionId
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'TTS generation failed');
            }

            return data;
        } catch (error) {
            console.error('TTS error:', error);
            throw error;
        }
    }

    /**
     * Save prescription
     */
    async savePrescription(caseId, prescriptionData = {}) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/save_prescription.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    case_id: caseId,
                    ...prescriptionData
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Failed to save prescription');
            }

            return data;
        } catch (error) {
            console.error('Save prescription error:', error);
            throw error;
        }
    }
}

/**
 * Audio Player Helper
 */
class AudioPlayer {
    constructor(audioElement) {
        this.audio = audioElement || new Audio();
        this.isPlaying = false;
    }

    /**
     * Play audio from URL
     */
    play(url) {
        return new Promise((resolve, reject) => {
            this.audio.src = url;
            this.audio.onended = () => {
                this.isPlaying = false;
                resolve();
            };
            this.audio.onerror = (error) => {
                this.isPlaying = false;
                reject(error);
            };
            this.audio.play()
                .then(() => {
                    this.isPlaying = true;
                })
                .catch(reject);
        });
    }

    /**
     * Play audio from Blob
     */
    playBlob(blob) {
        const url = URL.createObjectURL(blob);
        return this.play(url).finally(() => {
            URL.revokeObjectURL(url);
        });
    }

    /**
     * Stop playback
     */
    stop() {
        this.audio.pause();
        this.audio.currentTime = 0;
        this.isPlaying = false;
    }

    /**
     * Pause playback
     */
    pause() {
        this.audio.pause();
        this.isPlaying = false;
    }

    /**
     * Resume playback
     */
    resume() {
        this.audio.play();
        this.isPlaying = true;
    }

    /**
     * Set volume (0-1)
     */
    setVolume(volume) {
        this.audio.volume = Math.max(0, Math.min(1, volume));
    }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { VoiceRecorder, AudioUploader, AudioPlayer };
}
