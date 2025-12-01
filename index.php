<?php
/**
 * Voice Prescription System - Main Entry Point
 * Hostinger PHP + Google AI Integration
 */

define('APP_ROOT', __DIR__);

// Load configuration
require_once APP_ROOT . '/config/env.php';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Home</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .hero-section {
            padding: 100px 0;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero-subtitle {
            font-size: 1.5rem;
            color: rgba(255,255,255,0.9);
        }

        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            height: 100%;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
        }

        .feature-icon.blue {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .feature-icon.green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }

        .feature-icon.orange {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .btn-start {
            background: white;
            color: #667eea;
            padding: 15px 40px;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 30px;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-start:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(255,255,255,0.3);
            color: #764ba2;
        }

        .tech-badge {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: inline-block;
            margin: 5px;
        }

        .footer {
            background: rgba(0,0,0,0.1);
            padding: 30px 0;
            color: rgba(255,255,255,0.8);
        }

        .hover-shadow:hover {
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-transparent">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">
                <i class="bi bi-mic-fill me-2"></i><?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="record.html">
                            <i class="bi bi-record-circle me-1"></i>Record
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="frontdesk.html">
                            <i class="bi bi-clipboard2-pulse me-1"></i>Front Desk
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="hero-title mb-4">
                <i class="bi bi-mic-fill me-3"></i>Voice Prescription System
            </h1>
            <p class="hero-subtitle mb-5">
                AI-powered voice-to-prescription workflow<br>
                Powered by Google Speech-to-Text & Gemini AI
            </p>

            <div class="mb-5">
                <span class="tech-badge"><i class="bi bi-google me-1"></i>Google Cloud AI</span>
                <span class="tech-badge"><i class="bi bi-mic me-1"></i>Speech-to-Text</span>
                <span class="tech-badge"><i class="bi bi-robot me-1"></i>Gemini AI</span>
                <span class="tech-badge"><i class="bi bi-volume-up me-1"></i>Text-to-Speech</span>
            </div>

            <a href="record.html" class="btn btn-start">
                <i class="bi bi-play-fill me-2"></i>Start Recording
            </a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <!-- Feature 1 -->
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon blue">
                            <i class="bi bi-mic-fill"></i>
                        </div>
                        <h4 class="text-center mb-3">Voice Recording</h4>
                        <p class="text-muted text-center">
                            Record doctor-patient consultations directly in the browser.
                            High-quality audio capture with real-time visualization.
                        </p>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon green">
                            <i class="bi bi-robot"></i>
                        </div>
                        <h4 class="text-center mb-3">AI Analysis</h4>
                        <p class="text-muted text-center">
                            Automatic transcription and intelligent extraction of medical information
                            using Google's advanced AI models.
                        </p>
                    </div>
                </div>

                <!-- Feature 3 -->
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon orange">
                            <i class="bi bi-file-medical"></i>
                        </div>
                        <h4 class="text-center mb-3">Smart Prescriptions</h4>
                        <p class="text-muted text-center">
                            Auto-generated prescriptions with missing field detection
                            and intelligent follow-up questions.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Workflow Section -->
    <section class="py-5">
        <div class="container">
            <div class="bg-white rounded-4 p-5">
                <h3 class="text-center mb-5">How It Works</h3>
                <div class="row align-items-center">
                    <div class="col-md-3 text-center mb-4 mb-md-0">
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <span class="fw-bold">1</span>
                        </div>
                        <h5 class="mt-3">Record</h5>
                        <p class="text-muted small">Doctor records consultation</p>
                    </div>
                    <div class="col-md-3 text-center mb-4 mb-md-0">
                        <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <span class="fw-bold">2</span>
                        </div>
                        <h5 class="mt-3">Transcribe</h5>
                        <p class="text-muted small">AI converts speech to text</p>
                    </div>
                    <div class="col-md-3 text-center mb-4 mb-md-0">
                        <div class="rounded-circle bg-warning text-white d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <span class="fw-bold">3</span>
                        </div>
                        <h5 class="mt-3">Analyze</h5>
                        <p class="text-muted small">Extract prescription data</p>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="rounded-circle bg-info text-white d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <span class="fw-bold">4</span>
                        </div>
                        <h5 class="mt-3">Confirm</h5>
                        <p class="text-muted small">Review and save</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Links -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-6">
                    <a href="record.html" class="text-decoration-none">
                        <div class="bg-white rounded-4 p-4 d-flex align-items-center hover-shadow">
                            <div class="bg-primary bg-opacity-10 rounded-3 p-3 me-4">
                                <i class="bi bi-record-circle text-primary fs-3"></i>
                            </div>
                            <div>
                                <h5 class="mb-1 text-dark">New Recording</h5>
                                <p class="mb-0 text-muted">Start a new voice prescription</p>
                            </div>
                            <i class="bi bi-arrow-right ms-auto text-primary fs-4"></i>
                        </div>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="frontdesk.html" class="text-decoration-none">
                        <div class="bg-white rounded-4 p-4 d-flex align-items-center hover-shadow">
                            <div class="bg-success bg-opacity-10 rounded-3 p-3 me-4">
                                <i class="bi bi-clipboard2-pulse text-success fs-3"></i>
                            </div>
                            <div>
                                <h5 class="mb-1 text-dark">Front Desk Dashboard</h5>
                                <p class="mb-0 text-muted">View prescription queue</p>
                            </div>
                            <i class="bi bi-arrow-right ms-auto text-success fs-4"></i>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container text-center">
            <p class="mb-2">
                <i class="bi bi-shield-check me-2"></i>
                Secure & HIPAA-compliant voice prescription system
            </p>
            <p class="mb-0 small">
                &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Powered by Google Cloud AI.
            </p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
