/**
 * UI Module
 * Handles loading states, multi-step UI, and user interactions
 */

class PrescriptionUI {
    constructor(options = {}) {
        this.apiBaseUrl = options.apiBaseUrl || '/api';
        this.currentStep = 1;
        this.totalSteps = 4;
        this.caseId = null;
        this.caseNumber = null;
        this.audioId = null;
        this.analysisData = null;
        this.currentQuestion = null;

        // Initialize components
        this.recorder = new VoiceRecorder({
            onStart: () => this.onRecordingStart(),
            onStop: (blob) => this.onRecordingStop(blob),
            onTimer: (time) => this.updateRecordingTimer(time),
            onError: (error) => this.showError('Recording error: ' + error.message)
        });

        this.uploader = new AudioUploader(this.apiBaseUrl);
        this.player = new AudioPlayer();

        // Bind methods
        this.init = this.init.bind(this);
    }

    /**
     * Initialize UI
     */
    init() {
        // Check browser support
        if (!VoiceRecorder.isSupported()) {
            this.showError('Your browser does not support audio recording. Please use a modern browser like Chrome or Firefox.');
            return;
        }

        // Setup event listeners
        this.setupEventListeners();

        // Initialize step display
        this.updateStepIndicator();

        console.log('PrescriptionUI initialized');
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Record button
        const recordBtn = document.getElementById('recordBtn');
        if (recordBtn) {
            recordBtn.addEventListener('click', () => this.toggleRecording());
        }

        // Submit button
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.submitRecording());
        }

        // Answer button (for follow-up)
        const answerBtn = document.getElementById('answerBtn');
        if (answerBtn) {
            answerBtn.addEventListener('click', () => this.toggleAnswerRecording());
        }

        // Submit answer button
        const submitAnswerBtn = document.getElementById('submitAnswerBtn');
        if (submitAnswerBtn) {
            submitAnswerBtn.addEventListener('click', () => this.submitAnswer());
        }

        // Play question button
        const playQuestionBtn = document.getElementById('playQuestionBtn');
        if (playQuestionBtn) {
            playQuestionBtn.addEventListener('click', () => this.playQuestion());
        }

        // Skip question button
        const skipBtn = document.getElementById('skipQuestionBtn');
        if (skipBtn) {
            skipBtn.addEventListener('click', () => this.skipQuestion());
        }

        // Confirm button
        const confirmBtn = document.getElementById('confirmBtn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => this.confirmPrescription());
        }

        // New recording button
        const newRecordingBtn = document.getElementById('newRecordingBtn');
        if (newRecordingBtn) {
            newRecordingBtn.addEventListener('click', () => this.resetToStart());
        }
    }

    /**
     * Toggle recording on/off
     */
    async toggleRecording() {
        const btn = document.getElementById('recordBtn');

        if (this.recorder.isRecording) {
            this.recorder.stop();
            btn.classList.remove('recording');
            btn.innerHTML = '<i class="bi bi-mic-fill"></i> Start Recording';
        } else {
            const started = await this.recorder.start();
            if (started) {
                btn.classList.add('recording');
                btn.innerHTML = '<i class="bi bi-stop-fill"></i> Stop Recording';
            }
        }
    }

    /**
     * Handle recording start
     */
    onRecordingStart() {
        this.showElement('recordingIndicator');
        this.hideElement('audioPreview');
        this.hideElement('submitBtn');
        this.updateStatus('Recording...', 'recording');
    }

    /**
     * Handle recording stop
     */
    onRecordingStop(audioBlob) {
        this.currentAudioBlob = audioBlob;
        this.hideElement('recordingIndicator');
        this.showAudioPreview(audioBlob);
        this.showElement('submitBtn');
        this.updateStatus('Recording complete. Review and submit.', 'success');
    }

    /**
     * Update recording timer display
     */
    updateRecordingTimer(time) {
        const timerElement = document.getElementById('recordingTimer');
        if (timerElement) {
            timerElement.textContent = time;
        }
    }

    /**
     * Show audio preview
     */
    showAudioPreview(blob) {
        const previewContainer = document.getElementById('audioPreview');
        const audioElement = document.getElementById('previewAudio');

        if (previewContainer && audioElement) {
            const url = URL.createObjectURL(blob);
            audioElement.src = url;
            this.showElement('audioPreview');
        }
    }

    /**
     * Submit recording for processing
     */
    async submitRecording() {
        if (!this.currentAudioBlob) {
            this.showError('No recording to submit');
            return;
        }

        this.showLoading('Uploading audio...');

        try {
            // Step 1: Upload audio
            const uploadResult = await this.uploader.upload(this.currentAudioBlob, {
                doctorId: document.getElementById('doctorSelect')?.value,
                patientName: document.getElementById('patientName')?.value
            });

            this.caseId = uploadResult.case_id;
            this.caseNumber = uploadResult.case_number;
            this.audioId = uploadResult.audio_id;

            this.updateStatus('Transcribing audio...', 'processing');

            // Step 2: Transcribe
            const transcriptResult = await this.uploader.transcribe(this.audioId);
            this.showTranscript(transcriptResult.transcript);

            this.updateStatus('Analyzing transcript...', 'processing');

            // Step 3: Analyze
            const analysisResult = await this.uploader.analyze(this.caseId);
            this.analysisData = analysisResult;

            this.hideLoading();

            // Check if complete or needs follow-up
            if (analysisResult.analysis.is_complete) {
                this.goToStep(3); // Go to confirmation
                this.showPrescriptionPreview(analysisResult);
            } else {
                this.goToStep(2); // Go to follow-up
                this.showFollowupSection(analysisResult);
            }

        } catch (error) {
            this.hideLoading();
            this.showError('Error processing recording: ' + error.message);
        }
    }

    /**
     * Show transcript
     */
    showTranscript(transcript) {
        const transcriptElement = document.getElementById('transcript');
        if (transcriptElement) {
            transcriptElement.textContent = transcript;
            this.showElement('transcriptSection');
        }
    }

    /**
     * Show follow-up section
     */
    async showFollowupSection(analysisResult) {
        this.showElement('followupSection');
        this.hideElement('recordSection');

        // Show missing fields summary
        const missingFieldsList = document.getElementById('missingFieldsList');
        if (missingFieldsList && analysisResult.analysis.missing_critical) {
            missingFieldsList.innerHTML = analysisResult.analysis.missing_critical
                .map(field => `<li class="list-group-item">${this.formatFieldName(field)}</li>`)
                .join('');
        }

        // Show completeness score
        const scoreElement = document.getElementById('completenessScore');
        if (scoreElement) {
            scoreElement.textContent = `${analysisResult.analysis.completeness_score}%`;
            scoreElement.className = `badge ${analysisResult.analysis.completeness_score >= 70 ? 'bg-warning' : 'bg-danger'}`;
        }

        // Get first follow-up question
        await this.loadNextQuestion();
    }

    /**
     * Load next follow-up question
     */
    async loadNextQuestion() {
        try {
            this.showLoading('Generating question...');

            const questionResult = await this.uploader.getFollowupQuestion(this.caseId);

            this.hideLoading();

            if (questionResult.complete) {
                // All questions answered, go to confirmation
                this.goToStep(3);
                await this.refreshAnalysis();
                return;
            }

            this.currentQuestion = questionResult;
            this.showQuestion(questionResult);

        } catch (error) {
            this.hideLoading();
            this.showError('Error loading question: ' + error.message);
        }
    }

    /**
     * Show follow-up question
     */
    showQuestion(questionData) {
        const questionText = document.getElementById('questionText');
        const questionEnglish = document.getElementById('questionEnglish');

        if (questionText) {
            questionText.textContent = questionData.question;
        }
        if (questionEnglish && questionData.question_english) {
            questionEnglish.textContent = questionData.question_english;
        }

        // Reset answer section
        this.hideElement('answerPreview');
        document.getElementById('textAnswer')?.setAttribute('value', '');

        // Generate TTS for the question
        this.generateQuestionTTS(questionData);
    }

    /**
     * Generate TTS for question
     */
    async generateQuestionTTS(questionData) {
        try {
            const ttsResult = await this.uploader.getTtsAudio(this.caseId, questionData.question_id);

            if (ttsResult.audio_url) {
                this.currentQuestionAudioUrl = ttsResult.audio_url;
                this.showElement('playQuestionBtn');
            }
        } catch (error) {
            console.error('TTS error:', error);
        }
    }

    /**
     * Play question audio
     */
    async playQuestion() {
        if (this.currentQuestionAudioUrl) {
            const btn = document.getElementById('playQuestionBtn');
            btn.disabled = true;

            try {
                await this.player.play(this.currentQuestionAudioUrl);
            } catch (error) {
                this.showError('Error playing audio');
            }

            btn.disabled = false;
        }
    }

    /**
     * Toggle answer recording
     */
    async toggleAnswerRecording() {
        const btn = document.getElementById('answerBtn');

        if (this.recorder.isRecording) {
            this.recorder.stop();
            btn.classList.remove('recording');
            btn.innerHTML = '<i class="bi bi-mic-fill"></i> Record Answer';
        } else {
            // Setup recorder for answer
            this.recorder.options.onStop = (blob) => this.onAnswerRecordingStop(blob);

            const started = await this.recorder.start();
            if (started) {
                btn.classList.add('recording');
                btn.innerHTML = '<i class="bi bi-stop-fill"></i> Stop';
            }
        }
    }

    /**
     * Handle answer recording stop
     */
    onAnswerRecordingStop(audioBlob) {
        this.currentAnswerBlob = audioBlob;

        // Show answer preview
        const previewAudio = document.getElementById('answerPreviewAudio');
        if (previewAudio) {
            previewAudio.src = URL.createObjectURL(audioBlob);
            this.showElement('answerPreview');
        }
    }

    /**
     * Submit follow-up answer
     */
    async submitAnswer() {
        const textAnswer = document.getElementById('textAnswer')?.value;

        if (!textAnswer && !this.currentAnswerBlob) {
            this.showError('Please provide an answer (text or voice)');
            return;
        }

        this.showLoading('Processing answer...');

        try {
            let answerAudioId = null;

            // If voice answer, upload it first
            if (this.currentAnswerBlob) {
                const uploadResult = await this.uploader.upload(this.currentAnswerBlob, {
                    caseId: this.caseId,
                    audioType: 'followup'
                });
                answerAudioId = uploadResult.audio_id;

                // Transcribe the answer
                const transcriptResult = await this.uploader.transcribe(answerAudioId);
                var answerText = transcriptResult.transcript;
            } else {
                var answerText = textAnswer;
            }

            // Submit the answer
            const result = await this.uploader.submitFollowupAnswer(
                this.caseId,
                this.currentQuestion.question_id,
                answerText,
                answerAudioId
            );

            this.hideLoading();
            this.currentAnswerBlob = null;

            // Check if more questions
            if (result.has_more_questions) {
                await this.loadNextQuestion();
            } else {
                // All questions answered
                this.goToStep(3);
                await this.refreshAnalysis();
            }

        } catch (error) {
            this.hideLoading();
            this.showError('Error submitting answer: ' + error.message);
        }
    }

    /**
     * Skip current question
     */
    async skipQuestion() {
        if (confirm('Are you sure you want to skip this question? The prescription may be incomplete.')) {
            await this.loadNextQuestion();
        }
    }

    /**
     * Refresh analysis after follow-up
     */
    async refreshAnalysis() {
        try {
            const analysisResult = await this.uploader.analyze(this.caseId);
            this.analysisData = analysisResult;
            this.showPrescriptionPreview(analysisResult);
        } catch (error) {
            console.error('Refresh error:', error);
        }
    }

    /**
     * Show prescription preview for confirmation
     */
    showPrescriptionPreview(analysisResult) {
        this.showElement('confirmSection');
        this.hideElement('followupSection');
        this.hideElement('recordSection');

        const data = analysisResult.structured_data;

        // Patient info
        this.setFieldValue('confirmPatientName', data.patient_info?.name);
        this.setFieldValue('confirmPatientGender', data.patient_info?.gender);
        this.setFieldValue('confirmPatientAge', data.patient_info?.age);

        // Diagnosis
        this.setFieldValue('confirmDiagnosis', data.diagnosis?.primary);
        this.setFieldValue('confirmChiefComplaint', data.chief_complaint);

        // Symptoms
        const symptomsEl = document.getElementById('confirmSymptoms');
        if (symptomsEl && data.symptoms) {
            symptomsEl.value = Array.isArray(data.symptoms) ? data.symptoms.join(', ') : data.symptoms;
        }

        // Medications
        this.showMedicationsTable(data.treatment_plan?.medications || []);

        // Warnings
        this.setFieldValue('confirmWarnings', (data.warnings || []).join(', '));

        // Instructions
        this.setFieldValue('confirmInstructions',
            Array.isArray(data.treatment_plan?.lifestyle_recommendations)
                ? data.treatment_plan.lifestyle_recommendations.join(', ')
                : data.treatment_plan?.lifestyle_recommendations
        );
    }

    /**
     * Show medications in table
     */
    showMedicationsTable(medications) {
        const tbody = document.getElementById('medicationsTableBody');
        if (!tbody) return;

        tbody.innerHTML = medications.map((med, index) => `
            <tr>
                <td><input type="text" class="form-control form-control-sm" name="med_name_${index}" value="${med.name || ''}"></td>
                <td><input type="text" class="form-control form-control-sm" name="med_dosage_${index}" value="${med.dosage || ''}"></td>
                <td><input type="text" class="form-control form-control-sm" name="med_frequency_${index}" value="${med.frequency || ''}"></td>
                <td><input type="text" class="form-control form-control-sm" name="med_duration_${index}" value="${med.duration || ''}"></td>
                <td><input type="text" class="form-control form-control-sm" name="med_instructions_${index}" value="${med.instructions || ''}"></td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="prescriptionUI.removeMedication(${index})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');

        this.medicationsCount = medications.length;
    }

    /**
     * Add medication row
     */
    addMedication() {
        const tbody = document.getElementById('medicationsTableBody');
        if (!tbody) return;

        const index = this.medicationsCount || 0;

        tbody.innerHTML += `
            <tr>
                <td><input type="text" class="form-control form-control-sm" name="med_name_${index}" value=""></td>
                <td><input type="text" class="form-control form-control-sm" name="med_dosage_${index}" value=""></td>
                <td><input type="text" class="form-control form-control-sm" name="med_frequency_${index}" value=""></td>
                <td><input type="text" class="form-control form-control-sm" name="med_duration_${index}" value=""></td>
                <td><input type="text" class="form-control form-control-sm" name="med_instructions_${index}" value=""></td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="prescriptionUI.removeMedication(${index})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        this.medicationsCount = index + 1;
    }

    /**
     * Remove medication row
     */
    removeMedication(index) {
        const row = document.querySelector(`input[name="med_name_${index}"]`)?.closest('tr');
        if (row) {
            row.remove();
        }
    }

    /**
     * Confirm and save prescription
     */
    async confirmPrescription() {
        this.showLoading('Saving prescription...');

        try {
            // Gather form data
            const prescriptionData = {
                patient_name: document.getElementById('confirmPatientName')?.value,
                patient_gender: document.getElementById('confirmPatientGender')?.value,
                diagnosis: document.getElementById('confirmDiagnosis')?.value,
                warnings: document.getElementById('confirmWarnings')?.value,
                instructions: document.getElementById('confirmInstructions')?.value,
                medications: this.gatherMedications()
            };

            const result = await this.uploader.savePrescription(this.caseId, prescriptionData);

            this.hideLoading();

            // Show success
            this.goToStep(4);
            this.showCompletionMessage(result);

        } catch (error) {
            this.hideLoading();
            this.showError('Error saving prescription: ' + error.message);
        }
    }

    /**
     * Gather medications from form
     */
    gatherMedications() {
        const medications = [];
        const tbody = document.getElementById('medicationsTableBody');
        if (!tbody) return medications;

        tbody.querySelectorAll('tr').forEach((row, index) => {
            const name = row.querySelector(`input[name="med_name_${index}"]`)?.value;
            if (name) {
                medications.push({
                    name: name,
                    dosage: row.querySelector(`input[name="med_dosage_${index}"]`)?.value || '',
                    frequency: row.querySelector(`input[name="med_frequency_${index}"]`)?.value || '',
                    duration: row.querySelector(`input[name="med_duration_${index}"]`)?.value || '',
                    instructions: row.querySelector(`input[name="med_instructions_${index}"]`)?.value || ''
                });
            }
        });

        return medications;
    }

    /**
     * Show completion message
     */
    showCompletionMessage(result) {
        this.showElement('completionSection');
        this.hideElement('confirmSection');

        const rxNumber = document.getElementById('prescriptionNumber');
        if (rxNumber) {
            rxNumber.textContent = result.prescription_number;
        }

        const caseNum = document.getElementById('completedCaseNumber');
        if (caseNum) {
            caseNum.textContent = result.case_number;
        }
    }

    /**
     * Reset to start new recording
     */
    resetToStart() {
        this.caseId = null;
        this.caseNumber = null;
        this.audioId = null;
        this.analysisData = null;
        this.currentQuestion = null;
        this.currentAudioBlob = null;
        this.currentAnswerBlob = null;

        // Hide all sections except record
        this.hideElement('followupSection');
        this.hideElement('confirmSection');
        this.hideElement('completionSection');
        this.hideElement('transcriptSection');
        this.hideElement('audioPreview');
        this.hideElement('submitBtn');

        this.showElement('recordSection');
        this.goToStep(1);
        this.updateStatus('Ready to record', 'ready');
    }

    /**
     * Go to specific step
     */
    goToStep(step) {
        this.currentStep = step;
        this.updateStepIndicator();
    }

    /**
     * Update step indicator UI
     */
    updateStepIndicator() {
        for (let i = 1; i <= this.totalSteps; i++) {
            const stepEl = document.getElementById(`step${i}`);
            if (stepEl) {
                stepEl.classList.remove('active', 'completed');
                if (i < this.currentStep) {
                    stepEl.classList.add('completed');
                } else if (i === this.currentStep) {
                    stepEl.classList.add('active');
                }
            }
        }
    }

    /**
     * Show loading overlay
     */
    showLoading(message = 'Processing...') {
        let overlay = document.getElementById('loadingOverlay');

        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'loadingOverlay';
            overlay.className = 'loading-overlay';
            overlay.innerHTML = `
                <div class="loading-content">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p id="loadingMessage" class="mt-3">${message}</p>
                </div>
            `;
            document.body.appendChild(overlay);
        } else {
            document.getElementById('loadingMessage').textContent = message;
        }

        overlay.style.display = 'flex';
    }

    /**
     * Hide loading overlay
     */
    hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    /**
     * Show error message
     */
    showError(message) {
        const alertContainer = document.getElementById('alertContainer') || document.body;

        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show';
        alert.innerHTML = `
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        alertContainer.prepend(alert);

        // Auto-dismiss after 5 seconds
        setTimeout(() => alert.remove(), 5000);
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        const alertContainer = document.getElementById('alertContainer') || document.body;

        const alert = document.createElement('div');
        alert.className = 'alert alert-success alert-dismissible fade show';
        alert.innerHTML = `
            <i class="bi bi-check-circle-fill me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        alertContainer.prepend(alert);

        setTimeout(() => alert.remove(), 5000);
    }

    /**
     * Update status message
     */
    updateStatus(message, type = 'info') {
        const statusEl = document.getElementById('statusMessage');
        if (statusEl) {
            statusEl.textContent = message;
            statusEl.className = `status-message status-${type}`;
        }
    }

    /**
     * Show element by ID
     */
    showElement(id) {
        const el = document.getElementById(id);
        if (el) {
            el.style.display = '';
            el.classList.remove('d-none');
        }
    }

    /**
     * Hide element by ID
     */
    hideElement(id) {
        const el = document.getElementById(id);
        if (el) {
            el.style.display = 'none';
            el.classList.add('d-none');
        }
    }

    /**
     * Set form field value
     */
    setFieldValue(id, value) {
        const el = document.getElementById(id);
        if (el) {
            el.value = value || '';
        }
    }

    /**
     * Format field name for display
     */
    formatFieldName(fieldName) {
        return fieldName
            .replace(/_/g, ' ')
            .replace(/\./g, ' > ')
            .replace(/\b\w/g, l => l.toUpperCase());
    }
}

// Initialize global instance
let prescriptionUI;

document.addEventListener('DOMContentLoaded', () => {
    prescriptionUI = new PrescriptionUI();
    prescriptionUI.init();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { PrescriptionUI };
}
