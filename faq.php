<?php
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$pageTitle = 'Pertanyaan Umum (FAQ)';
$pageDesc = 'Temukan jawaban atas pertanyaan yang sering diajukan seputar Program Studi Informatika.';

// LOGIC ASLI DIPERTAHANKAN 100%
try {
    $db = getDB();
    $faqList = $db->query("SELECT * FROM faq WHERE status = 'aktif' ORDER BY urutan ASC, created_at DESC")->fetchAll();
    
    // Tambahan statistik untuk hero
    $totalFAQ = count($faqList);
    $totalChars = 0;
    foreach ($faqList as $f) {
        $totalChars += strlen($f['pertanyaan'] ?? '') + strlen($f['jawaban'] ?? '');
    }
    $totalWords = str_word_count(implode(' ', array_column($faqList, 'pertanyaan')) . ' ' . implode(' ', array_column($faqList, 'jawaban')));
    
} catch (Throwable $e) {
    $faqList = [];
    $totalFAQ = 0;
    $totalWords = 0;
}
?>

<!-- ===== READING PROGRESS ===== -->
<div class="nn-reading-progress">
    <div class="nn-progress-fill" id="nnProgressFill"></div>
    <div class="nn-progress-brain">
        <i class="fas fa-brain"></i>
    </div>
</div>

<!-- ===== HERO WITH NEURAL CANVAS ===== -->
<section class="nn-hero">
    <canvas id="nnNeuralCanvas" class="nn-neural-canvas"></canvas>
    <div class="nn-hero-bg">
        <div class="nn-orb nn-orb-1"></div>
        <div class="nn-orb nn-orb-2"></div>
        <div class="nn-orb nn-orb-3"></div>
        <div class="nn-grid-pattern"></div>
        <div class="nn-synapse-particles"></div>
    </div>
    
    <!-- Floating Brain Elements -->
    <div class="nn-floating-elements">
        <span class="nn-float-icon" style="--delay: 0s; --x: 8%; --y: 20%;">🧠</span>
        <span class="nn-float-icon" style="--delay: 1.2s; --x: 88%; --y: 25%;">💭</span>
        <span class="nn-float-icon" style="--delay: 2.5s; --x: 15%; --y: 75%;">⚡</span>
        <span class="nn-float-icon" style="--delay: 0.8s; --x: 82%; --y: 70%;">🔮</span>
        <span class="nn-float-icon" style="--delay: 3s; --x: 50%; --y: 12%;">💡</span>
        <span class="nn-float-icon" style="--delay: 1.8s; --x: 45%; --y: 88%;">🎯</span>
    </div>
    
    <div class="container nn-hero-content">
        <span class="nn-hero-badge fade-up">
            <span class="nn-badge-pulse"></span>
            <span class="nn-ai-status">
                <span class="nn-status-dot"></span>
                AI ACTIVE
            </span>
            <i class="fas fa-brain"></i> NEURAL NEXUS
        </span>
        
        <h1 class="nn-hero-title fade-up">
            <span class="nn-title-line">Pertanyaan</span>
            <span class="nn-title-line nn-glitch-text" data-text="Umum">Umum</span>
        </h1>
        
        <p class="nn-hero-subtitle fade-up">
            <span id="nnTypingText"></span><span class="nn-cursor">|</span>
        </p>
        
        <!-- Brain Stats -->
        <div class="nn-brain-stats fade-up">
            <div class="nn-brain-stat">
                <div class="nn-bstat-icon">
                    <i class="fas fa-question-circle"></i>
                    <span class="nn-stat-pulse"></span>
                </div>
                <div class="nn-bstat-info">
                    <span class="nn-bstat-num" data-count="<?= $totalFAQ ?>">0</span>
                    <span class="nn-bstat-label">Neurons Loaded</span>
                </div>
            </div>
            
            <div class="nn-brain-stat">
                <div class="nn-bstat-icon nn-bstat-cyan">
                    <i class="fas fa-comments"></i>
                    <span class="nn-stat-pulse"></span>
                </div>
                <div class="nn-bstat-info">
                    <span class="nn-bstat-num"><?= number_format($totalWords) ?></span>
                    <span class="nn-bstat-label">Words Indexed</span>
                </div>
            </div>
            
            <div class="nn-brain-stat">
                <div class="nn-bstat-icon nn-bstat-pink">
                    <i class="fas fa-check-double"></i>
                    <span class="nn-stat-pulse"></span>
                </div>
                <div class="nn-bstat-info">
                    <span class="nn-bstat-num">100%</span>
                    <span class="nn-bstat-label">Answered</span>
                </div>
            </div>
            
            <div class="nn-brain-stat">
                <div class="nn-bstat-icon nn-bstat-gold">
                    <i class="fas fa-bolt"></i>
                    <span class="nn-stat-pulse"></span>
                </div>
                <div class="nn-bstat-info">
                    <span class="nn-bstat-num">24/7</span>
                    <span class="nn-bstat-label">Available</span>
                </div>
            </div>
        </div>
        
        <!-- Brain Activity Indicator -->
        <div class="nn-brain-activity fade-up">
            <span class="nn-activity-label">
                <i class="fas fa-wave-square"></i> BRAIN ACTIVITY
            </span>
            <div class="nn-activity-bars">
                <?php for ($i = 0; $i < 30; $i++): ?>
                    <span style="--bar-delay: <?= $i * 0.05 ?>s; --bar-height: <?= rand(20, 100) ?>%"></span>
                <?php endfor; ?>
            </div>
        </div>
    </div>
    
    <div class="nn-scroll-indicator">
        <span>Query the Brain</span>
        <div class="nn-scroll-arrow">
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>
</section>

<!-- ===== MAIN CONTENT ===== -->
<section class="nn-section">
    <div class="container">
        <div class="nn-layout">
            
            <!-- LEFT: Sidebar -->
            <aside class="nn-sidebar fade-up">
                
                <!-- AI Assistant Card -->
                <div class="nn-ai-card nn-tilt">
                    <div class="nn-ai-glow"></div>
                    <div class="nn-ai-avatar">
                        <i class="fas fa-robot"></i>
                        <span class="nn-ai-pulse"></span>
                    </div>
                    <h3>AI Assistant</h3>
                    <p>Saya siap membantu Anda 24/7. Tanyakan apa saja tentang Informatika Pro!</p>
                    <div class="nn-ai-status-info">
                        <span class="nn-ai-online">
                            <span class="nn-online-dot"></span>
                            Online Now
                        </span>
                        <span class="nn-ai-response">
                            <i class="fas fa-stopwatch"></i> &lt; 1min response
                        </span>
                    </div>
                    <a href="<?= defined('SITE_URL') ? SITE_URL : '' ?>/#contact" class="nn-btn nn-btn-primary">
                        <i class="fas fa-paper-plane"></i> Chat with AI
                    </a>
                </div>
                
                <!-- Quick Topics -->
                <div class="nn-topics-card">
                    <h4><i class="fas fa-tags"></i> Quick Topics</h4>
                    <div class="nn-topics-list">
                        <button class="nn-topic-btn" data-topic="pendaftaran">
                            <i class="fas fa-user-plus"></i>
                            <span>Pendaftaran</span>
                            <span class="nn-topic-count">?</span>
                        </button>
                        <button class="nn-topic-btn" data-topic="beasiswa">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Beasiswa</span>
                            <span class="nn-topic-count">?</span>
                        </button>
                        <button class="nn-topic-btn" data-topic="kurikulum">
                            <i class="fas fa-book"></i>
                            <span>Kurikulum</span>
                            <span class="nn-topic-count">?</span>
                        </button>
                        <button class="nn-topic-btn" data-topic="akademik">
                            <i class="fas fa-university"></i>
                            <span>Akademik</span>
                            <span class="nn-topic-count">?</span>
                        </button>
                        <button class="nn-topic-btn" data-topic="karir">
                            <i class="fas fa-briefcase"></i>
                            <span>Karir</span>
                            <span class="nn-topic-count">?</span>
                        </button>
                        <button class="nn-topic-btn" data-topic="fasilitas">
                            <i class="fas fa-building"></i>
                            <span>Fasilitas</span>
                            <span class="nn-topic-count">?</span>
                        </button>
                    </div>
                </div>
                
                <!-- Stats Widget -->
                <div class="nn-stats-widget nn-tilt">
                    <h4><i class="fas fa-chart-line"></i> Brain Analytics</h4>
                    <div class="nn-analytics">
                        <div class="nn-analytics-item">
                            <span class="nn-analytics-label">Accuracy</span>
                            <div class="nn-analytics-bar">
                                <div class="nn-analytics-fill" style="width: 98%;"></div>
                            </div>
                            <span class="nn-analytics-value">98%</span>
                        </div>
                        <div class="nn-analytics-item">
                            <span class="nn-analytics-label">Satisfaction</span>
                            <div class="nn-analytics-bar">
                                <div class="nn-analytics-fill nn-fill-cyan" style="width: 95%;"></div>
                            </div>
                            <span class="nn-analytics-value">95%</span>
                        </div>
                        <div class="nn-analytics-item">
                            <span class="nn-analytics-label">Response Time</span>
                            <div class="nn-analytics-bar">
                                <div class="nn-analytics-fill nn-fill-pink" style="width: 88%;"></div>
                            </div>
                            <span class="nn-analytics-value">Fast</span>
                        </div>
                    </div>
                </div>
                
                <!-- Help Card -->
                <div class="nn-help-card">
                    <div class="nn-help-icon">
                        <i class="fas fa-life-ring"></i>
                    </div>
                    <h4>Butuh Bantuan?</h4>
                    <p>Tim admisi kami siap membantu Anda langsung</p>
                    <div class="nn-help-contacts">
                        <a href="mailto:info@informatikapro.ac.id" class="nn-help-link">
                            <i class="fas fa-envelope"></i>
                            <span>Email</span>
                        </a>
                        <a href="tel:+6281234567890" class="nn-help-link">
                            <i class="fas fa-phone"></i>
                            <span>Call</span>
                        </a>
                        <a href="https://wa.me/6281234567890" target="_blank" class="nn-help-link">
                            <i class="fab fa-whatsapp"></i>
                            <span>WhatsApp</span>
                        </a>
                    </div>
                </div>
            </aside>
            
            <!-- RIGHT: Main Content -->
            <div class="nn-main">
                
                <!-- Neural Query Console -->
                <div class="nn-console fade-up">
                    <div class="nn-console-header">
                        <div class="nn-console-dots">
                            <span class="dot red"></span>
                            <span class="dot yellow"></span>
                            <span class="dot green"></span>
                        </div>
                        <i class="fas fa-terminal"></i>
                        <span>neural_query.exe</span>
                        <span class="nn-console-status">● PROCESSING</span>
                    </div>
                    
                    <div class="nn-console-body">
                        <div class="nn-query-wrap">
                            <i class="fas fa-search"></i>
                            <input type="text" id="nnSearchInput" placeholder="Ask the AI anything..." autocomplete="off">
                            <span class="nn-query-hint">
                                <kbd>Ctrl</kbd>+<kbd>K</kbd>
                            </span>
                            <button class="nn-query-clear" id="nnSearchClear" style="display: none;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="nn-query-meta">
                            <span class="nn-query-info">
                                <i class="fas fa-brain"></i>
                                <strong id="nnResultsCount"><?= $totalFAQ ?></strong> neurons ready
                            </span>
                            <span class="nn-query-suggestions">
                                Try: <button class="nn-suggestion" data-q="pendaftaran">pendaftaran</button>
                                <button class="nn-suggestion" data-q="beasiswa">beasiswa</button>
                                <button class="nn-suggestion" data-q="kuliah">kuliah</button>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- FAQ Accordion (Neurons) -->
                <?php if (!empty($faqList)): ?>
                    <div class="nn-neurons" id="nnNeurons">
                        <?php foreach ($faqList as $index => $f): 
                            $neuronId = 'nn-neuron-' . $f['id'];
                            $colors = ['var(--nn-primary)', '#06B6D4', '#EC4899', '#F59E0B', '#8B5CF6', '#10B981'];
                            $neuronColor = $colors[$index % count($colors)];
                        ?>
                        <div class="nn-neuron fade-up" 
                             id="<?= $neuronId ?>" 
                             style="animation-delay: <?= $index * 0.05 ?>s; --neuron-color: <?= $neuronColor ?>;"
                             data-question="<?= strtolower(clean($f['pertanyaan'])) ?>" 
                             data-answer="<?= strtolower(clean($f['jawaban'])) ?>">
                            
                            <!-- Synapse Connector -->
                            <div class="nn-synapse-connector">
                                <span class="nn-synapse-dot"></span>
                                <span class="nn-synapse-line"></span>
                            </div>
                            
                            <!-- Neuron Question (Header) -->
                            <button class="nn-neuron-head" aria-expanded="false" onclick="nnToggleNeuron(this)">
                                <div class="nn-neuron-icon">
                                    <i class="fas fa-question"></i>
                                    <span class="nn-icon-pulse"></span>
                                </div>
                                <span class="nn-neuron-text"><?= clean($f['pertanyaan']) ?></span>
                                <div class="nn-neuron-toggle">
                                    <span class="nn-toggle-line"></span>
                                    <span class="nn-toggle-line"></span>
                                </div>
                            </button>
                            
                            <!-- Neuron Answer (Body) -->
                            <div class="nn-neuron-body" id="<?= $neuronId ?>-body">
                                <div class="nn-neuron-answer">
                                    <div class="nn-answer-header">
                                        <i class="fas fa-robot"></i>
                                        <span>AI Response</span>
                                        <span class="nn-answer-time">
                                            <i class="fas fa-clock"></i> Instant
                                        </span>
                                    </div>
                                    <div class="nn-answer-content">
                                        <?= nl2br(clean($f['jawaban'])) ?>
                                    </div>
                                    <div class="nn-answer-footer">
                                        <span class="nn-helpful-label">Was this helpful?</span>
                                        <div class="nn-helpful-btns">
                                            <button class="nn-helpful-btn nn-yes" onclick="nnHelpful(this, 'yes', <?= $f['id'] ?>)">
                                                <i class="fas fa-thumbs-up"></i> Yes
                                            </button>
                                            <button class="nn-helpful-btn nn-no" onclick="nnHelpful(this, 'no', <?= $f['id'] ?>)">
                                                <i class="fas fa-thumbs-down"></i> No
                                            </button>
                                        </div>
                                        <button class="nn-copy-btn" onclick="nnCopyAnswer(this)" title="Copy answer">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- No Results State -->
                    <div class="nn-no-results" id="nnNoResults" style="display: none;">
                        <div class="nn-no-results-icon">
                            <i class="fas fa-brain"></i>
                            <div class="nn-no-pulse"></div>
                        </div>
                        <h3>Neural Network Offline</h3>
                        <p>Tidak ada neuron yang cocok dengan query "<strong id="nnSearchQuery"></strong>". Coba kata kunci lain atau hubungi kami langsung.</p>
                        <div class="nn-no-actions">
                            <button class="nn-btn nn-btn-primary" onclick="nnResetSearch()">
                                <i class="fas fa-redo"></i> Reset Query
                            </button>
                            <a href="<?= defined('SITE_URL') ? SITE_URL : '' ?>/#contact" class="nn-btn nn-btn-ghost">
                                <i class="fas fa-headset"></i> Contact Support
                            </a>
                        </div>
                    </div>
                    
                    <!-- Expand All / Collapse All -->
                    <div class="nn-bulk-actions fade-up">
                        <button class="nn-bulk-btn" onclick="nnExpandAll()">
                            <i class="fas fa-expand-alt"></i> Expand All
                        </button>
                        <button class="nn-bulk-btn" onclick="nnCollapseAll()">
                            <i class="fas fa-compress-alt"></i> Collapse All
                        </button>
                        <span class="nn-bulk-info">
                            <i class="fas fa-info-circle"></i>
                            <span id="nnOpenCount">0</span> neurons active
                        </span>
                    </div>

                <?php else: ?>
                    <div class="nn-empty-state fade-up">
                        <div class="nn-empty-icon">
                            <i class="fas fa-brain"></i>
                            <div class="nn-empty-pulse"></div>
                        </div>
                        <h3>Neural Network Loading...</h3>
                        <p>AI kami sedang dilatih. Silakan hubungi kami langsung untuk pertanyaan Anda.</p>
                        <a href="<?= defined('SITE_URL') ? SITE_URL : '' ?>/#contact" class="nn-btn nn-btn-primary">
                            <i class="fas fa-headset"></i> Contact Support
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ===== CTA SECTION ===== -->
<section class="nn-cta-section">
    <div class="container">
        <div class="nn-cta-card fade-up">
            <div class="nn-cta-bg">
                <div class="nn-cta-orb"></div>
                <div class="nn-cta-grid"></div>
            </div>
            <div class="nn-cta-content">
                <div class="nn-cta-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <h2>Masih Punya Pertanyaan?</h2>
                <p>Tim admisi kami siap membantu Anda secara personal. Jangan ragu untuk menghubungi kami kapan saja!</p>
                <div class="nn-cta-actions">
                    <a href="<?= defined('SITE_URL') ? SITE_URL : '' ?>/#contact" class="nn-btn nn-btn-white">
                        <i class="fas fa-paper-plane"></i> Kirim Pertanyaan
                    </a>
                    <a href="https://wa.me/6281234567890" target="_blank" class="nn-btn nn-btn-outline">
                        <i class="fab fa-whatsapp"></i> WhatsApp Kami
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Toast -->
<div id="nnToast" class="nn-toast">
    <i class="fas fa-check-circle"></i>
    <span id="nnToastMsg">Success!</span>
</div>

<!-- Back to Top -->
<button class="nn-back-top" id="nnBackTop" title="Back to top">
    <i class="fas fa-arrow-up"></i>
</button>

<style>
:root {
    --nn-primary: #10B981;
    --nn-primary-dark: #047857;
    --nn-primary-light: #D1FAE5;
    --nn-cyan: #06B6D4;
    --nn-cyan-light: #CFFAFE;
    --nn-pink: #EC4899;
    --nn-pink-light: #FCE7F3;
    --nn-purple: #8B5CF6;
    --nn-gold: #F59E0B;
    --nn-dark: #0F172A;
    --nn-deeper: #020617;
    --nn-gray-50: #F8FAFC;
    --nn-gray-100: #F1F5F9;
    --nn-gray-200: #E2E8F0;
    --nn-gray-600: #475569;
    --nn-gray-700: #374151;
    --nn-shadow-sm: 0 4px 12px rgba(15,23,42,.06);
    --nn-shadow-md: 0 12px 32px rgba(15,23,42,.1);
    --nn-shadow-lg: 0 20px 50px rgba(15,23,42,.15);
    --nn-shadow-glow: 0 0 30px rgba(16,185,129,0.3);
    --nn-font-mono: 'JetBrains Mono', 'Courier New', monospace;
    --nn-font-display: 'Space Grotesk', 'Inter', sans-serif;
}

body.gl-dark {
    --nn-gray-50: #0B1120;
    --nn-gray-100: rgba(255,255,255,.08);
    --nn-gray-200: rgba(255,255,255,.1);
    --nn-gray-600: #94A3B8;
    --nn-gray-700: #CBD5E1;
}

* { box-sizing: border-box; }
html { scroll-behavior: smooth; }

::-webkit-scrollbar { width: 10px; }
::-webkit-scrollbar-track { background: var(--nn-gray-50); }
::-webkit-scrollbar-thumb { 
    background: linear-gradient(180deg, var(--nn-primary), var(--nn-purple)); 
    border-radius: 5px; 
}

/* ===== READING PROGRESS ===== */
.nn-reading-progress {
    position: fixed; top: 0; left: 0; right: 0; height: 4px;
    background: rgba(16,185,129,0.1); z-index: 9998;
}
.nn-progress-fill {
    height: 100%; width: 0;
    background: linear-gradient(90deg, var(--nn-primary), var(--nn-cyan), var(--nn-pink));
    background-size: 200% 100%;
    animation: nnGradientMove 3s ease infinite;
    box-shadow: 0 0 10px rgba(16,185,129,0.6);
    transition: width 0.1s ease-out;
}
@keyframes nnGradientMove {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}
.nn-progress-brain {
    position: absolute; top: 10px; right: 20px;
    width: 30px; height: 30px; border-radius: 50%;
    background: linear-gradient(135deg, var(--nn-primary), var(--nn-cyan));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem; box-shadow: 0 4px 12px rgba(16,185,129,0.4);
    animation: nnBrainPulse 2s infinite;
}
@keyframes nnBrainPulse {
    0%, 100% { transform: scale(1); box-shadow: 0 4px 12px rgba(16,185,129,0.4); }
    50% { transform: scale(1.1); box-shadow: 0 4px 20px rgba(16,185,129,0.6); }
}

/* ===== HERO ===== */
.nn-hero {
    background: linear-gradient(135deg, #0F172A 0%, #1E1B4B 50%, #047857 100%);
    padding: 160px 20px 120px; position: relative; overflow: hidden;
    min-height: 90vh; display: flex; align-items: center; justify-content: center;
}

.nn-neural-canvas {
    position: absolute; inset: 0; width: 100%; height: 100%;
    z-index: 1; opacity: 0.6;
}

.nn-hero-bg { position: absolute; inset: 0; pointer-events: none; z-index: 2; }
.nn-orb {
    position: absolute; border-radius: 50%; filter: blur(100px);
    opacity: 0.4; animation: nnFloat 15s infinite ease-in-out;
}
.nn-orb-1 { width: 500px; height: 500px; background: var(--nn-primary); top: -150px; right: -150px; }
.nn-orb-2 { width: 350px; height: 350px; background: var(--nn-purple); bottom: -100px; left: -100px; animation-delay: -5s; }
.nn-orb-3 { width: 250px; height: 250px; background: var(--nn-cyan); top: 40%; left: 40%; animation-delay: -10s; }
@keyframes nnFloat { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(30px, -30px); } }

.nn-grid-pattern {
    position: absolute; inset: 0;
    background-image: 
        linear-gradient(rgba(16,185,129,0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(16,185,129,0.05) 1px, transparent 1px);
    background-size: 50px 50px;
    mask-image: radial-gradient(ellipse at center, black 30%, transparent 80%);
}

.nn-synapse-particles {
    position: absolute; inset: 0;
    background-image: radial-gradient(circle, rgba(255,255,255,0.4) 1px, transparent 1px);
    background-size: 40px 40px;
    opacity: 0.3;
    animation: nnParticles 20s linear infinite;
}
@keyframes nnParticles {
    to { background-position: 40px 40px; }
}

/* Floating Elements */
.nn-floating-elements { position: absolute; inset: 0; z-index: 3; pointer-events: none; }
.nn-float-icon {
    position: absolute; font-size: 2.5rem;
    animation: nnFloatIcon 8s infinite ease-in-out;
    animation-delay: var(--delay);
    left: var(--x); top: var(--y);
    opacity: 0.4; filter: drop-shadow(0 0 15px rgba(16,185,129,0.5));
}
@keyframes nnFloatIcon {
    0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0.4; }
    50% { transform: translateY(-30px) rotate(15deg); opacity: 0.8; }
}

.nn-hero-content {
    position: relative; z-index: 4; text-align: center;
    color: #fff; max-width: 1000px; margin: 0 auto;
}

.nn-hero-badge {
    display: inline-flex; align-items: center; gap: 12px;
    padding: 10px 24px; background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.15); border-radius: 50px;
    font-size: .85rem; margin-bottom: 24px; backdrop-filter: blur(10px);
    font-weight: 700; letter-spacing: 2px; position: relative;
    font-family: var(--nn-font-mono);
}
.nn-badge-pulse {
    position: absolute; inset: 0; border-radius: 50px;
    border: 2px solid var(--nn-cyan);
    animation: nnBadgePulse 2s infinite;
}
@keyframes nnBadgePulse { 
    0% { transform: scale(1); opacity: 1; } 
    100% { transform: scale(1.3); opacity: 0; } 
}

.nn-ai-status {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 3px 10px; background: var(--nn-primary);
    border-radius: 4px; font-size: 0.7rem; font-weight: 800;
    letter-spacing: 1px;
}
.nn-status-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: #fff; animation: nnStatusPulse 1.5s infinite;
}
@keyframes nnStatusPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.nn-hero-title {
    font-size: clamp(3rem, 9vw, 6rem); font-weight: 900;
    margin-bottom: 24px; letter-spacing: -0.03em; line-height: 0.95;
    font-family: var(--nn-font-display);
}
.nn-title-line { display: block; }

.nn-glitch-text {
    position: relative; display: inline-block;
    background: linear-gradient(135deg, #34D399 0%, #06B6D4 50%, #EC4899 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; background-size: 200% 200%;
    animation: nnGradientShift 5s ease infinite;
}
.nn-glitch-text::before, .nn-glitch-text::after {
    content: attr(data-text); position: absolute; top: 0; left: 0;
    width: 100%; height: 100%;
    background: inherit; -webkit-background-clip: text;
    -webkit-text-fill-color: transparent; background-clip: text;
}
.nn-glitch-text::before {
    animation: nnGlitch1 3s infinite;
    clip-path: polygon(0 0, 100% 0, 100% 45%, 0 45%);
}
.nn-glitch-text::after {
    animation: nnGlitch2 2.5s infinite;
    clip-path: polygon(0 60%, 100% 60%, 100% 100%, 0 100%);
}
@keyframes nnGradientShift { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
@keyframes nnGlitch1 {
    0%, 90%, 100% { transform: translate(0, 0); }
    92% { transform: translate(-3px, 1px); }
    94% { transform: translate(2px, -2px); }
}
@keyframes nnGlitch2 {
    0%, 90%, 100% { transform: translate(0, 0); }
    91% { transform: translate(2px, 1px); }
    93% { transform: translate(-2px, -1px); }
}

.nn-hero-subtitle {
    font-size: 1.2rem; opacity: .9; max-width: 700px;
    margin: 0 auto 40px; line-height: 1.7;
    display: inline-block; min-height: 1.7em;
}
.nn-cursor { animation: nnBlink 1s infinite; color: var(--nn-primary); font-weight: 300; }
@keyframes nnBlink { 0%, 50% { opacity: 1; } 51%, 100% { opacity: 0; } }

/* Brain Stats */
.nn-brain-stats {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 16px; max-width: 900px; margin: 0 auto 40px;
}
.nn-brain-stat {
    display: flex; align-items: center; gap: 14px;
    padding: 16px 20px; background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1); border-radius: 16px;
    backdrop-filter: blur(12px); transition: all 0.3s;
}
.nn-brain-stat:hover {
    background: rgba(255,255,255,.12); transform: translateY(-4px);
    border-color: var(--nn-primary);
    box-shadow: 0 12px 24px rgba(16,185,129,0.2);
}

.nn-bstat-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: linear-gradient(135deg, var(--nn-primary), var(--nn-primary-dark));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0; position: relative;
    box-shadow: 0 4px 12px rgba(16,185,129,0.3);
}
.nn-bstat-cyan { background: linear-gradient(135deg, var(--nn-cyan), #0891B2); box-shadow: 0 4px 12px rgba(6,182,212,0.3); }
.nn-bstat-pink { background: linear-gradient(135deg, var(--nn-pink), #BE185D); box-shadow: 0 4px 12px rgba(236,72,153,0.3); }
.nn-bstat-gold { background: linear-gradient(135deg, var(--nn-gold), #D97706); box-shadow: 0 4px 12px rgba(245,158,11,0.3); }

.nn-stat-pulse {
    position: absolute; inset: -4px; border-radius: 14px;
    border: 2px solid currentColor; opacity: 0;
    animation: nnStatPulse 2s infinite;
}
@keyframes nnStatPulse {
    0% { transform: scale(0.9); opacity: 0.8; }
    100% { transform: scale(1.3); opacity: 0; }
}

.nn-bstat-info { flex: 1; }
.nn-bstat-num {
    display: block; font-size: 1.6rem; font-weight: 900;
    color: #fff; line-height: 1; font-family: var(--nn-font-display);
}
.nn-bstat-label {
    font-size: 0.7rem; color: rgba(255,255,255,.6);
    text-transform: uppercase; letter-spacing: 0.5px;
    margin-top: 4px; display: block;
}

/* Brain Activity */
.nn-brain-activity {
    display: flex; align-items: center; gap: 16px;
    max-width: 700px; margin: 0 auto;
    padding: 16px 24px; background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1); border-radius: 50px;
    backdrop-filter: blur(12px);
}
.nn-activity-label {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.75rem; font-weight: 700; color: rgba(255,255,255,.8);
    letter-spacing: 1.5px; font-family: var(--nn-font-mono);
    white-space: nowrap;
}
.nn-activity-label i { color: var(--nn-pink); }

.nn-activity-bars {
    flex: 1; display: flex; gap: 3px; align-items: center; height: 30px;
}
.nn-activity-bars span {
    flex: 1; background: linear-gradient(180deg, var(--nn-primary), var(--nn-cyan));
    border-radius: 2px; height: var(--bar-height);
    animation: nnActivityBar 1.2s infinite ease-in-out;
    animation-delay: var(--bar-delay);
    transform-origin: bottom;
}
@keyframes nnActivityBar {
    0%, 100% { transform: scaleY(0.3); }
    50% { transform: scaleY(1); }
}

/* Scroll Indicator */
.nn-scroll-indicator {
    position: absolute; bottom: 30px; left: 50%;
    transform: translateX(-50%); z-index: 4;
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    color: rgba(255,255,255,.6); font-size: 0.75rem;
    letter-spacing: 0.1em; text-transform: uppercase;
}
.nn-scroll-arrow { animation: nnArrowBounce 2s infinite; }
@keyframes nnArrowBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(10px); }
}

/* ===== MAIN SECTION ===== */
.nn-section { padding: 60px 20px 100px; background: var(--nn-gray-50); }
body.gl-dark .nn-section { background: #0B1120; }

.nn-layout {
    display: grid; grid-template-columns: 320px 1fr;
    gap: 40px; align-items: flex-start;
}

/* ===== SIDEBAR ===== */
.nn-sidebar {
    position: sticky; top: 100px;
    display: flex; flex-direction: column; gap: 20px;
}

/* AI Card */
.nn-ai-card {
    background: linear-gradient(135deg, var(--nn-dark), #1E1B4B);
    color: #fff; border-radius: 20px; padding: 28px;
    text-align: center; position: relative; overflow: hidden;
    box-shadow: var(--nn-shadow-lg);
    transform-style: preserve-3d;
}
.nn-ai-glow {
    position: absolute; top: -50%; left: 50%;
    transform: translateX(-50%);
    width: 200%; height: 200%;
    background: radial-gradient(circle, rgba(16,185,129,0.3), transparent 60%);
    filter: blur(40px); pointer-events: none;
}

.nn-ai-avatar {
    width: 80px; height: 80px; border-radius: 50%;
    background: linear-gradient(135deg, var(--nn-primary), var(--nn-cyan));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 2rem; margin: 0 auto 16px;
    position: relative; z-index: 1;
    box-shadow: 0 8px 24px rgba(16,185,129,0.4);
}
.nn-ai-pulse {
    position: absolute; inset: -8px; border-radius: 50%;
    border: 2px solid var(--nn-primary);
    animation: nnPulse 2s infinite;
}
@keyframes nnPulse {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(1.3); opacity: 0; }
}

.nn-ai-card h3 {
    font-size: 1.3rem; margin: 0 0 8px 0; font-weight: 800;
    font-family: var(--nn-font-display); position: relative; z-index: 1;
}
.nn-ai-card p {
    font-size: 0.88rem; opacity: 0.85; line-height: 1.5;
    margin-bottom: 16px; position: relative; z-index: 1;
}

.nn-ai-status-info {
    display: flex; justify-content: center; gap: 12px;
    margin-bottom: 20px; flex-wrap: wrap;
    position: relative; z-index: 1;
}
.nn-ai-online {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 10px; background: rgba(16,185,129,0.2);
    border-radius: 20px; font-size: 0.72rem; font-weight: 700;
    color: var(--nn-primary);
}
.nn-online-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: var(--nn-primary); animation: nnStatusPulse 1.5s infinite;
}
.nn-ai-response {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 0.72rem; color: rgba(255,255,255,0.7);
}
.nn-ai-response i { color: var(--nn-gold); }

/* Buttons */
.nn-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 12px 24px; border-radius: 12px; font-weight: 700;
    font-size: 0.9rem; text-decoration: none; border: none;
    cursor: pointer; transition: all 0.3s; font-family: inherit;
    letter-spacing: 0.3px; width: 100%;
}
.nn-btn-primary {
    background: linear-gradient(135deg, var(--nn-primary), var(--nn-primary-dark));
    color: #fff; box-shadow: 0 4px 16px rgba(16,185,129,0.3);
    position: relative; z-index: 1;
}
.nn-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(16,185,129,0.4); }
.nn-btn-ghost {
    background: transparent; color: var(--nn-gray-700);
    border: 1.5px solid var(--nn-gray-200);
}
body.gl-dark .nn-btn-ghost { color: #E2E8F0; border-color: rgba(255,255,255,.2); }
.nn-btn-ghost:hover { border-color: var(--nn-primary); color: var(--nn-primary); }
.nn-btn-white {
    background: #fff; color: var(--nn-primary-dark);
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
}
.nn-btn-white:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.3); }
.nn-btn-outline {
    background: transparent; color: #fff;
    border: 1.5px solid rgba(255,255,255,0.3);
}
.nn-btn-outline:hover { background: rgba(255,255,255,0.1); border-color: #fff; }

/* Topics Card */
.nn-topics-card {
    background: #fff; border-radius: 20px; padding: 24px;
    box-shadow: var(--nn-shadow-sm); border: 1px solid var(--nn-gray-100);
}
body.gl-dark .nn-topics-card { background: #1E293B; border-color: rgba(255,255,255,.08); }

.nn-topics-card h4 {
    font-size: 0.9rem; font-weight: 800; color: var(--nn-dark);
    margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;
    text-transform: uppercase; letter-spacing: 1px;
}
body.gl-dark .nn-topics-card h4 { color: #F1F5F9; }
.nn-topics-card h4 i { color: var(--nn-cyan); }

.nn-topics-list { display: flex; flex-direction: column; gap: 6px; }
.nn-topic-btn {
    width: 100%; display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; background: var(--nn-gray-50);
    border: 1px solid transparent; border-radius: 10px;
    cursor: pointer; font-family: inherit; font-size: 0.85rem;
    color: var(--nn-gray-700); transition: all 0.3s;
}
body.gl-dark .nn-topic-btn { background: rgba(255,255,255,.03); color: #CBD5E1; }
.nn-topic-btn:hover {
    border-color: var(--nn-primary); background: var(--nn-primary-light);
    color: var(--nn-primary-dark); transform: translateX(4px);
}
body.gl-dark .nn-topic-btn:hover { background: rgba(16,185,129,0.1); color: #34D399; }
.nn-topic-btn.active {
    background: var(--nn-primary); color: #fff;
    border-color: var(--nn-primary);
}
.nn-topic-btn i { font-size: 0.9rem; color: var(--nn-primary); width: 18px; }
.nn-topic-btn.active i { color: #fff; }
.nn-topic-btn span:first-of-type { flex: 1; text-align: left; font-weight: 600; }

.nn-topic-count {
    padding: 2px 8px; background: rgba(16,185,129,0.15);
    color: var(--nn-primary); border-radius: 10px;
    font-size: 0.7rem; font-weight: 700;
    font-family: var(--nn-font-mono);
}
.nn-topic-btn.active .nn-topic-count {
    background: rgba(255,255,255,0.2); color: #fff;
}

/* Stats Widget */
.nn-stats-widget {
    background: #fff; border-radius: 20px; padding: 24px;
    box-shadow: var(--nn-shadow-sm); border: 1px solid var(--nn-gray-100);
    transform-style: preserve-3d;
}
body.gl-dark .nn-stats-widget { background: #1E293B; border-color: rgba(255,255,255,.08); }

.nn-stats-widget h4 {
    font-size: 0.9rem; font-weight: 800; color: var(--nn-dark);
    margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;
    text-transform: uppercase; letter-spacing: 1px;
}
body.gl-dark .nn-stats-widget h4 { color: #F1F5F9; }
.nn-stats-widget h4 i { color: var(--nn-pink); }

.nn-analytics { display: flex; flex-direction: column; gap: 14px; }
.nn-analytics-item {}
.nn-analytics-label {
    font-size: 0.75rem; color: var(--nn-gray-600);
    font-weight: 600; display: block; margin-bottom: 6px;
}
body.gl-dark .nn-analytics-label { color: #94A3B8; }

.nn-analytics-bar {
    height: 6px; background: var(--nn-gray-100);
    border-radius: 3px; overflow: hidden; margin-bottom: 4px;
}
body.gl-dark .nn-analytics-bar { background: rgba(255,255,255,.08); }

.nn-analytics-fill {
    height: 100%; border-radius: 3px;
    background: linear-gradient(90deg, var(--nn-primary), var(--nn-cyan));
    transition: width 1s ease-out;
}
.nn-fill-cyan { background: linear-gradient(90deg, var(--nn-cyan), var(--nn-purple)); }
.nn-fill-pink { background: linear-gradient(90deg, var(--nn-pink), var(--nn-gold)); }

.nn-analytics-value {
    font-family: var(--nn-font-mono); font-size: 0.75rem;
    font-weight: 700; color: var(--nn-dark);
}
body.gl-dark .nn-analytics-value { color: #F1F5F9; }

/* Help Card */
.nn-help-card {
    background: linear-gradient(135deg, var(--nn-pink), #BE185D);
    color: #fff; border-radius: 20px; padding: 24px;
    text-align: center; position: relative; overflow: hidden;
}
.nn-help-card::before {
    content: ''; position: absolute; top: -50%; right: -50%;
    width: 200px; height: 200px; border-radius: 50%;
    background: rgba(255,255,255,0.1);
}
.nn-help-icon {
    width: 60px; height: 60px; margin: 0 auto 16px;
    background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; position: relative; z-index: 1;
}
.nn-help-card h4 {
    font-size: 1.1rem; margin: 0 0 8px 0; font-weight: 800;
    position: relative; z-index: 1;
}
.nn-help-card p {
    font-size: 0.85rem; opacity: 0.9; margin-bottom: 16px;
    position: relative; z-index: 1;
}
.nn-help-contacts {
    display: flex; justify-content: center; gap: 8px;
    position: relative; z-index: 1;
}
.nn-help-link {
    width: 42px; height: 42px; border-radius: 50%;
    background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);
    color: #fff; display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    text-decoration: none; transition: all 0.3s;
    font-size: 0.7rem;
}
.nn-help-link i { font-size: 1rem; margin-bottom: 2px; }
.nn-help-link:hover {
    background: #fff; color: var(--nn-pink);
    transform: translateY(-4px);
}

/* ===== MAIN CONTENT ===== */
.nn-main { min-width: 0; }

/* Console */
.nn-console {
    background: #fff; border-radius: 20px;
    box-shadow: var(--nn-shadow-md); overflow: hidden;
    border: 1px solid var(--nn-gray-100); margin-bottom: 32px;
}
body.gl-dark .nn-console { background: #1E293B; border-color: rgba(255,255,255,.08); }

.nn-console-header {
    background: var(--nn-dark); padding: 12px 20px;
    display: flex; align-items: center; gap: 10px;
    color: #94A3B8; font-family: var(--nn-font-mono); font-size: 0.8rem;
}
.nn-console-dots { display: flex; gap: 5px; }
.nn-console-dots .dot { width: 11px; height: 11px; border-radius: 50%; }
.nn-console-dots .red { background: #FF5F56; }
.nn-console-dots .yellow { background: #FFBD2E; }
.nn-console-dots .green { background: #27C93F; }
.nn-console-header i { color: var(--nn-primary); }
.nn-console-header span { color: #E2E8F0; }
.nn-console-status {
    margin-left: auto; color: var(--nn-primary);
    font-size: 0.7rem; letter-spacing: 1px;
    animation: nnBlink 2s infinite;
}

.nn-console-body { padding: 24px; }

.nn-query-wrap {
    position: relative; display: flex; align-items: center;
    margin-bottom: 16px;
}
.nn-query-wrap > i {
    position: absolute; left: 18px; top: 50%;
    transform: translateY(-50%); color: var(--nn-gray-600);
    z-index: 1; font-size: 1rem;
}
.nn-query-wrap input {
    width: 100%; padding: 16px 110px 16px 50px;
    border: 1.5px solid var(--nn-gray-200); border-radius: 14px;
    background: var(--nn-gray-50); font-family: inherit;
    font-size: 1rem; color: var(--nn-dark);
    transition: all 0.3s;
}
body.gl-dark .nn-query-wrap input {
    background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,.1); color: #E2E8F0;
}
.nn-query-wrap input:focus {
    outline: none; border-color: var(--nn-primary);
    box-shadow: 0 0 0 4px rgba(16,185,129,0.15);
}

.nn-query-hint {
    position: absolute; right: 60px; top: 50%;
    transform: translateY(-50%);
    display: flex; gap: 3px; align-items: center;
    color: var(--nn-gray-600); font-size: 0.75rem;
    pointer-events: none;
}
.nn-query-hint kbd {
    background: var(--nn-gray-100); padding: 2px 6px;
    border-radius: 4px; font-family: var(--nn-font-mono);
    font-size: 0.7rem; border: 1px solid var(--nn-gray-200);
}
body.gl-dark .nn-query-hint kbd {
    background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.1); color: #CBD5E1;
}

.nn-query-clear {
    position: absolute; right: 16px; top: 50%;
    transform: translateY(-50%);
    width: 32px; height: 32px; border-radius: 50%;
    background: var(--nn-gray-100); border: none;
    color: var(--nn-gray-600); cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.3s; font-size: 0.85rem;
}
body.gl-dark .nn-query-clear { background: rgba(255,255,255,.1); color: #CBD5E1; }
.nn-query-clear:hover {
    background: #EF4444; color: #fff;
    transform: translateY(-50%) rotate(90deg);
}

.nn-query-meta {
    display: flex; justify-content: space-between; align-items: center;
    font-size: 0.85rem; flex-wrap: wrap; gap: 12px;
}
.nn-query-info {
    color: var(--nn-gray-600); display: flex; align-items: center; gap: 8px;
}
body.gl-dark .nn-query-info { color: #94A3B8; }
.nn-query-info i { color: var(--nn-primary); }
.nn-query-info strong {
    color: var(--nn-dark); font-family: var(--nn-font-display);
    font-size: 1.1rem; font-weight: 800;
}
body.gl-dark .nn-query-info strong { color: #F1F5F9; }

.nn-query-suggestions {
    color: var(--nn-gray-600); display: flex; align-items: center; gap: 8px;
    flex-wrap: wrap;
}
body.gl-dark .nn-query-suggestions { color: #94A3B8; }

.nn-suggestion {
    padding: 4px 10px; background: var(--nn-gray-100);
    border: 1px solid var(--nn-gray-200); border-radius: 20px;
    color: var(--nn-gray-700); cursor: pointer;
    font-family: inherit; font-size: 0.75rem; font-weight: 600;
    transition: all 0.3s;
}
body.gl-dark .nn-suggestion { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.1); color: #CBD5E1; }
.nn-suggestion:hover {
    background: var(--nn-primary); color: #fff;
    border-color: var(--nn-primary);
}

/* ===== NEURONS (FAQ Items) ===== */
.nn-neurons {
    display: flex; flex-direction: column; gap: 16px;
    margin-bottom: 32px;
}

.nn-neuron {
    background: #fff; border-radius: 18px;
    border: 1px solid var(--nn-gray-100);
    box-shadow: var(--nn-shadow-sm); overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}
body.gl-dark .nn-neuron { background: #1E293B; border-color: rgba(255,255,255,.08); }

.nn-neuron::before {
    content: ''; position: absolute; left: 0; top: 0; bottom: 0;
    width: 4px; background: var(--neuron-color, var(--nn-primary));
    transition: width 0.3s;
}
.nn-neuron.active::before { width: 6px; }

.nn-neuron::after {
    content: ''; position: absolute; inset: 0; border-radius: 18px; padding: 1px;
    background: conic-gradient(from 0deg, transparent, var(--neuron-color, var(--nn-primary)), transparent 40%);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor; mask-composite: exclude;
    opacity: 0; transition: opacity 0.4s; pointer-events: none;
}
.nn-neuron.active::after { opacity: 1; animation: nnRotateBorder 4s linear infinite; }
@keyframes nnRotateBorder { to { transform: rotate(360deg); } }

.nn-neuron:hover {
    transform: translateX(4px);
    box-shadow: var(--nn-shadow-md);
    border-color: var(--neuron-color, var(--nn-primary));
}
.nn-neuron.active {
    box-shadow: 0 12px 32px rgba(16,185,129,0.15);
    border-color: var(--neuron-color, var(--nn-primary));
}

/* Synapse Connector */
.nn-synapse-connector {
    position: absolute; left: -30px; top: 30px;
    display: flex; align-items: center; gap: 8px;
    pointer-events: none;
}
.nn-synapse-dot {
    width: 10px; height: 10px; border-radius: 50%;
    background: var(--neuron-color, var(--nn-primary));
    box-shadow: 0 0 10px var(--neuron-color, var(--nn-primary));
    animation: nnSynapsePulse 2s infinite;
}
@keyframes nnSynapsePulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.3); opacity: 0.6; }
}
.nn-synapse-line {
    width: 20px; height: 2px;
    background: linear-gradient(90deg, var(--neuron-color, var(--nn-primary)), transparent);
}

/* Neuron Head */
.nn-neuron-head {
    width: 100%; display: flex; align-items: center; gap: 16px;
    padding: 20px 24px; background: none; border: none;
    cursor: pointer; text-align: left; font-family: inherit;
    transition: all 0.3s; position: relative; z-index: 1;
}

.nn-neuron-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: color-mix(in srgb, var(--neuron-color, var(--nn-primary)) 15%, transparent);
    color: var(--neuron-color, var(--nn-primary));
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0; position: relative;
    transition: all 0.3s;
}
.nn-neuron.active .nn-neuron-icon {
    background: var(--neuron-color, var(--nn-primary));
    color: #fff;
    transform: rotate(360deg);
}
.nn-icon-pulse {
    position: absolute; inset: -4px; border-radius: 14px;
    border: 2px solid var(--neuron-color, var(--nn-primary));
    opacity: 0; animation: nnIconPulse 2s infinite;
}
.nn-neuron.active .nn-icon-pulse { animation: none; }
@keyframes nnIconPulse {
    0% { transform: scale(0.9); opacity: 0.8; }
    100% { transform: scale(1.3); opacity: 0; }
}

.nn-neuron-text {
    flex: 1; font-size: 1.05rem; font-weight: 700;
    color: var(--nn-dark); line-height: 1.4;
    font-family: var(--nn-font-display);
    transition: color 0.3s;
}
body.gl-dark .nn-neuron-text { color: #F1F5F9; }
.nn-neuron.active .nn-neuron-text { color: var(--neuron-color, var(--nn-primary)); }

.nn-neuron-toggle {
    width: 32px; height: 32px; border-radius: 8px;
    background: var(--nn-gray-100); flex-shrink: 0;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 4px; transition: all 0.3s;
}
body.gl-dark .nn-neuron-toggle { background: rgba(255,255,255,.08); }
.nn-toggle-line {
    width: 14px; height: 2px; background: var(--nn-gray-600);
    border-radius: 1px; transition: all 0.3s;
}
body.gl-dark .nn-toggle-line { background: #CBD5E1; }
.nn-neuron.active .nn-toggle-line:first-child { transform: rotate(45deg) translate(3px, 3px); }
.nn-neuron.active .nn-toggle-line:last-child { transform: rotate(-45deg) translate(3px, -3px); }
.nn-neuron.active .nn-neuron-toggle {
    background: var(--neuron-color, var(--nn-primary));
}
.nn-neuron.active .nn-toggle-line { background: #fff; }

/* Neuron Body */
.nn-neuron-body {
    max-height: 0; overflow: hidden;
    transition: max-height 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.nn-neuron-answer {
    padding: 0 24px 24px;
    border-top: 1px dashed var(--nn-gray-200);
    margin-top: 0;
}
body.gl-dark .nn-neuron-answer { border-color: rgba(255,255,255,.1); }

.nn-answer-header {
    display: flex; align-items: center; gap: 10px;
    padding: 16px 0; font-size: 0.82rem; color: var(--nn-gray-600);
}
body.gl-dark .nn-answer-header { color: #94A3B8; }
.nn-answer-header i { color: var(--nn-primary); font-size: 1rem; }
.nn-answer-header span:first-of-type {
    font-weight: 700; color: var(--nn-primary);
    text-transform: uppercase; letter-spacing: 1px;
    font-family: var(--nn-font-mono); font-size: 0.72rem;
}
.nn-answer-time {
    margin-left: auto; display: flex; align-items: center; gap: 4px;
    font-family: var(--nn-font-mono);
}
.nn-answer-time i { color: var(--nn-gold); font-size: 0.8rem; }

.nn-answer-content {
    font-size: 0.95rem; line-height: 1.8; color: var(--nn-gray-700);
    padding: 16px 20px; background: var(--nn-gray-50);
    border-radius: 12px; border-left: 3px solid var(--nn-primary);
}
body.gl-dark .nn-answer-content { background: rgba(255,255,255,.03); color: #CBD5E1; }
.nn-answer-content a {
    color: var(--nn-primary); text-decoration: underline;
    text-underline-offset: 3px;
}

.nn-answer-footer {
    display: flex; align-items: center; gap: 12px;
    margin-top: 16px; flex-wrap: wrap;
}
.nn-helpful-label {
    font-size: 0.82rem; color: var(--nn-gray-600); font-weight: 600;
}
body.gl-dark .nn-helpful-label { color: #94A3B8; }

.nn-helpful-btns { display: flex; gap: 6px; }
.nn-helpful-btn {
    padding: 6px 12px; background: var(--nn-gray-100);
    border: 1px solid var(--nn-gray-200); border-radius: 20px;
    color: var(--nn-gray-600); cursor: pointer;
    font-family: inherit; font-size: 0.78rem; font-weight: 600;
    transition: all 0.3s; display: inline-flex; align-items: center; gap: 6px;
}
body.gl-dark .nn-helpful-btn { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.1); color: #CBD5E1; }
.nn-helpful-btn:hover { transform: translateY(-2px); }
.nn-helpful-btn.nn-yes:hover, .nn-helpful-btn.nn-yes.active {
    background: var(--nn-primary); color: #fff; border-color: var(--nn-primary);
}
.nn-helpful-btn.nn-no:hover, .nn-helpful-btn.nn-no.active {
    background: #EF4444; color: #fff; border-color: #EF4444;
}

.nn-copy-btn {
    margin-left: auto; width: 32px; height: 32px;
    border-radius: 8px; background: var(--nn-gray-100);
    border: 1px solid var(--nn-gray-200); color: var(--nn-gray-600);
    cursor: pointer; transition: all 0.3s;
    display: flex; align-items: center; justify-content: center;
}
body.gl-dark .nn-copy-btn { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.1); color: #CBD5E1; }
.nn-copy-btn:hover {
    background: var(--nn-primary); color: #fff;
    border-color: var(--nn-primary); transform: rotate(15deg);
}

/* No Results */
.nn-no-results {
    text-align: center; padding: 60px 20px;
    background: #fff; border-radius: 20px;
    border: 2px dashed var(--nn-gray-200);
    margin-bottom: 32px;
}
body.gl-dark .nn-no-results { background: #1E293B; border-color: rgba(255,255,255,.1); }

.nn-no-results-icon {
    position: relative; width: 100px; height: 100px;
    margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;
    font-size: 3rem; color: var(--nn-gray-600);
}
.nn-no-pulse {
    position: absolute; inset: 0; border-radius: 50%;
    border: 2px solid var(--nn-primary);
    animation: nnNoPulse 2s infinite;
}
@keyframes nnNoPulse {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(1.5); opacity: 0; }
}

.nn-no-results h3 {
    font-size: 1.4rem; margin: 0 0 8px 0; color: var(--nn-dark);
    font-weight: 800; font-family: var(--nn-font-display);
}
body.gl-dark .nn-no-results h3 { color: #F1F5F9; }
.nn-no-results p { color: var(--nn-gray-600); margin-bottom: 20px; }
body.gl-dark .nn-no-results p { color: #94A3B8; }
.nn-no-results strong { color: var(--nn-pink); }

.nn-no-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
.nn-no-actions .nn-btn { width: auto; }

/* Bulk Actions */
.nn-bulk-actions {
    display: flex; align-items: center; gap: 12px;
    padding: 16px 20px; background: #fff;
    border-radius: 14px; border: 1px solid var(--nn-gray-100);
    box-shadow: var(--nn-shadow-sm); flex-wrap: wrap;
}
body.gl-dark .nn-bulk-actions { background: #1E293B; border-color: rgba(255,255,255,.08); }

.nn-bulk-btn {
    padding: 8px 16px; background: var(--nn-gray-100);
    border: 1px solid var(--nn-gray-200); border-radius: 10px;
    color: var(--nn-gray-700); cursor: pointer;
    font-family: inherit; font-size: 0.85rem; font-weight: 600;
    transition: all 0.3s; display: inline-flex; align-items: center; gap: 6px;
}
body.gl-dark .nn-bulk-btn { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.1); color: #CBD5E1; }
.nn-bulk-btn:hover {
    background: var(--nn-primary); color: #fff;
    border-color: var(--nn-primary); transform: translateY(-2px);
}

.nn-bulk-info {
    margin-left: auto; display: flex; align-items: center; gap: 8px;
    color: var(--nn-gray-600); font-size: 0.85rem;
}
body.gl-dark .nn-bulk-info { color: #94A3B8; }
.nn-bulk-info i { color: var(--nn-cyan); }
.nn-bulk-info strong { color: var(--nn-dark); font-family: var(--nn-font-display); }
body.gl-dark .nn-bulk-info strong { color: #F1F5F9; }

/* Empty State */
.nn-empty-state {
    text-align: center; padding: 80px 20px;
    background: #fff; border-radius: 24px;
    border: 2px dashed var(--nn-gray-200);
}
body.gl-dark .nn-empty-state { background: #1E293B; border-color: rgba(255,255,255,.1); }

.nn-empty-icon {
    position: relative; width: 120px; height: 120px;
    margin: 0 auto 24px; display: flex; align-items: center; justify-content: center;
    font-size: 4rem; color: var(--nn-gray-600);
    animation: nnEmptySpin 4s ease-in-out infinite;
}
@keyframes nnEmptySpin {
    0%, 100% { transform: rotate(0deg); }
    50% { transform: rotate(180deg); }
}
.nn-empty-pulse {
    position: absolute; inset: 0; border-radius: 50%;
    border: 2px solid var(--nn-primary);
    animation: nnEmptyPulse 2s infinite;
}
@keyframes nnEmptyPulse {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(1.5); opacity: 0; }
}

.nn-empty-state h3 {
    font-size: 1.5rem; margin: 0 0 8px 0; color: var(--nn-dark);
    font-weight: 800; font-family: var(--nn-font-display);
}
body.gl-dark .nn-empty-state h3 { color: #F1F5F9; }
.nn-empty-state p { color: var(--nn-gray-600); margin-bottom: 24px; max-width: 400px; margin-left: auto; margin-right: auto; }
.nn-empty-state .nn-btn { width: auto; margin: 0 auto; }

/* ===== CTA SECTION ===== */
.nn-cta-section {
    padding: 60px 20px;
    background: linear-gradient(135deg, var(--nn-dark), #1E1B4B);
}

.nn-cta-card {
    background: linear-gradient(135deg, var(--nn-primary), var(--nn-cyan), var(--nn-pink));
    background-size: 200% 200%;
    animation: nnCtaGradient 8s ease infinite;
    border-radius: 28px; padding: 60px 40px;
    text-align: center; color: #fff;
    position: relative; overflow: hidden;
    box-shadow: 0 20px 50px rgba(16,185,129,0.3);
}
@keyframes nnCtaGradient {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

.nn-cta-bg { position: absolute; inset: 0; pointer-events: none; }
.nn-cta-orb {
    position: absolute; top: -100px; right: -100px;
    width: 300px; height: 300px; border-radius: 50%;
    background: rgba(255,255,255,0.1); filter: blur(40px);
}
.nn-cta-grid {
    position: absolute; inset: 0;
    background-image: 
        linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
    background-size: 40px 40px;
}

.nn-cta-content { position: relative; z-index: 1; }

.nn-cta-icon {
    width: 80px; height: 80px; margin: 0 auto 20px;
    background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 2rem;
    animation: nnCtaBounce 3s infinite ease-in-out;
}
@keyframes nnCtaBounce {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-15px) rotate(-10deg); }
}

.nn-cta-card h2 {
    font-size: 2.2rem; margin: 0 0 12px 0; font-weight: 900;
    font-family: var(--nn-font-display);
}
.nn-cta-card > .nn-cta-content > p {
    font-size: 1.1rem; opacity: 0.95; margin-bottom: 28px;
    max-width: 600px; margin-left: auto; margin-right: auto;
}

.nn-cta-actions {
    display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;
}
.nn-cta-actions .nn-btn { width: auto; }

/* ===== TOAST ===== */
.nn-toast {
    position: fixed; bottom: 30px; left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: var(--nn-dark); color: #fff;
    padding: 14px 24px; border-radius: 50px;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,.3);
    z-index: 10001; opacity: 0; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    font-size: 0.9rem;
}
body.gl-dark .nn-toast { background: #fff; color: var(--nn-dark); }
.nn-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
.nn-toast i { color: var(--nn-primary); font-size: 1.2rem; }

/* ===== BACK TO TOP ===== */
.nn-back-top {
    position: fixed; bottom: 30px; right: 30px;
    width: 50px; height: 50px; border-radius: 50%;
    background: linear-gradient(135deg, var(--nn-primary), var(--nn-cyan));
    border: none; color: #fff; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; z-index: 998;
    box-shadow: 0 8px 24px rgba(16,185,129,0.4);
    opacity: 0; visibility: hidden; transform: translateY(20px) scale(.8);
    transition: all .4s;
}
.nn-back-top.show { opacity: 1; visibility: visible; transform: none; }
.nn-back-top:hover { transform: translateY(-4px) scale(1.05); }

/* ===== ANIMATIONS ===== */
.fade-up {
    opacity: 0; transform: translateY(30px);
    transition: opacity 0.8s ease, transform 0.8s ease;
}
.fade-up.visible { opacity: 1; transform: translateY(0); }

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .nn-layout { grid-template-columns: 1fr; }
    .nn-sidebar {
        position: static; order: 2;
        display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
    }
    .nn-ai-card, .nn-help-card { grid-column: span 2; }
    .nn-main { order: 1; }
    .nn-brain-stats { grid-template-columns: repeat(2, 1fr); }
    .nn-synapse-connector { display: none; }
}
@media (max-width: 768px) {
    .nn-hero { padding: 120px 20px 80px; min-height: 85vh; }
    .nn-hero-title { font-size: 3rem; }
    .nn-brain-stats { grid-template-columns: 1fr; gap: 10px; }
    .nn-brain-stat { justify-content: center; }
    .nn-brain-activity { flex-direction: column; border-radius: 16px; padding: 14px; }
    .nn-activity-bars { width: 100%; }
    .nn-floating-elements { display: none; }
    .nn-console-body { padding: 16px; }
    .nn-query-hint { display: none; }
    .nn-query-wrap input { padding-right: 50px; }
    .nn-query-meta { flex-direction: column; align-items: flex-start; gap: 8px; }
    .nn-neuron-head { padding: 16px 18px; gap: 12px; }
    .nn-neuron-text { font-size: 0.95rem; }
    .nn-neuron-icon { width: 38px; height: 38px; font-size: 1rem; }
    .nn-neuron-answer { padding: 0 18px 18px; }
    .nn-answer-content { padding: 14px; font-size: 0.9rem; }
    .nn-answer-footer { flex-direction: column; align-items: flex-start; gap: 10px; }
    .nn-copy-btn { margin-left: 0; }
    .nn-sidebar { grid-template-columns: 1fr; }
    .nn-ai-card, .nn-help-card { grid-column: span 1; }
    .nn-bulk-actions { flex-direction: column; align-items: stretch; }
    .nn-bulk-info { margin-left: 0; justify-content: center; }
    .nn-cta-card { padding: 40px 20px; }
    .nn-cta-card h2 { font-size: 1.6rem; }
    .nn-cta-actions { flex-direction: column; }
    .nn-cta-actions .nn-btn { width: 100%; }
    .nn-back-top { width: 44px; height: 44px; bottom: 20px; right: 20px; }
}
@media (max-width: 480px) {
    .nn-hero-title { font-size: 2.3rem; }
    .nn-hero-badge { font-size: 0.7rem; padding: 8px 16px; letter-spacing: 1px; flex-wrap: wrap; justify-content: center; }
    .nn-neuron-head { padding: 14px 16px; }
    .nn-bstat-num { font-size: 1.3rem; }
    .nn-no-actions { flex-direction: column; }
    .nn-no-actions .nn-btn { width: 100%; }
}

/* ============================================================
   PERFORMANCE PATCH - NEURAL NEXUS SMOOTH SCROLL
============================================================ */

/* Browser tidak perlu render penuh section yang jauh dari viewport */
.nn-section,
.nn-cta-section {
    content-visibility: auto;
    contain-intrinsic-size: auto 1600px;
}

/* Elemen dekoratif tidak boleh menangkap pointer */
.nn-hero-bg,
.nn-floating-elements,
.nn-synapse-connector,
.nn-reading-progress,
.nn-progress-brain {
    pointer-events: none;
}

/* Orbs blur mahal: beri hint GPU */
.nn-orb {
    will-change: transform;
    backface-visibility: hidden;
}

/* Pause animasi dekoratif saat user sedang scroll */
body.is-scrolling .nn-orb,
body.is-scrolling .nn-synapse-particles,
body.is-scrolling .nn-float-icon,
body.is-scrolling .nn-activity-bars span,
body.is-scrolling .nn-progress-fill,
body.is-scrolling .nn-badge-pulse,
body.is-scrolling .nn-stat-pulse,
body.is-scrolling .nn-icon-pulse,
body.is-scrolling .nn-synapse-dot,
body.is-scrolling .nn-cta-card,
body.is-scrolling .nn-cta-icon {
    animation-play-state: paused !important;
}

/* Pause animasi CSS untuk section yang tidak terlihat */
.nn-offscreen * {
    animation-play-state: paused !important;
}

/* Optimasi elemen yang sering bergerak */
.nn-progress-fill,
.nn-float-icon,
.nn-activity-bars span,
.fade-up {
    backface-visibility: hidden;
}
.fade-up.visible {
    will-change: auto;
}

/* Mobile / touch device: matikan efek paling mahal */
@media (max-width: 768px), (hover: none), (pointer: coarse) {
    .nn-floating-elements { display: none !important; }
    .nn-orb {
        filter: blur(60px);
        opacity: .25;
        animation: none !important;
    }
    .nn-hero-badge,
    .nn-brain-stat,
    .nn-brain-activity,
    .nn-help-icon,
    .nn-help-link,
    .nn-cta-icon {
        backdrop-filter: none !important;
    }
    .nn-activity-bars span { animation-duration: 2.4s; }
}

/* Accessibility: reduce motion */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    .nn-synapse-particles,
    .nn-floating-elements { display: none !important; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // ===== 0. PERFORMANCE: Scroll & Viewport Control =====
    let scrollTimer;
    const markScrolling = () => {
        document.body.classList.add('is-scrolling');
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(() => {
            document.body.classList.remove('is-scrolling');
        }, 180);
    };
    window.addEventListener('scroll', markScrolling, { passive: true });
    markScrolling();

    if ('IntersectionObserver' in window) {
        const sectionObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                entry.target.classList.toggle('nn-offscreen', !entry.isIntersecting);
            });
        }, { rootMargin: '300px 0px', threshold: 0 });

        document.querySelectorAll('.nn-hero, .nn-section, .nn-cta-section').forEach(section => {
            sectionObserver.observe(section);
        });

        const heroEl = document.querySelector('.nn-hero');
        if (heroEl) {
            window.__nnHeroVisible = true;
            new IntersectionObserver((entries) => {
                const entry = entries[0];
                window.__nnHeroVisible = entry ? entry.isIntersecting : true;
                window.dispatchEvent(new CustomEvent('nn:hero-visibility', {
                    detail: window.__nnHeroVisible
                }));
            }, { threshold: 0.01 }).observe(heroEl);
        }
    }

    // ===== 1. Neural Network Canvas (OPTIMIZED) =====
    const canvas = document.getElementById('nnNeuralCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d', { alpha: true });
        let width, height, neurons = [], time = 0;
        let running = true;
        let lastFrame = 0;
        const frameInterval = 1000 / 30; // Limit 30 FPS

        function resize() {
            width = canvas.width = canvas.parentElement.offsetWidth;
            height = canvas.height = canvas.parentElement.offsetHeight;
            initNeurons();
        }

        function initNeurons() {
            neurons = [];
            // Jumlah neuron dibatasi maksimal 70 demi performa
            const numNeurons = Math.min(Math.floor((width * height) / 18000), 70);
            for (let i = 0; i < numNeurons; i++) {
                neurons.push({
                    x: Math.random() * width,
                    y: Math.random() * height,
                    vx: (Math.random() - 0.5) * 0.5,
                    vy: (Math.random() - 0.5) * 0.5,
                    size: Math.random() * 2 + 1,
                    pulsePhase: Math.random() * Math.PI * 2,
                    color: ['#10B981', '#06B6D4', '#EC4899', '#8B5CF6'][Math.floor(Math.random() * 4)]
                });
            }
        }

        function drawNeuron(n) {
            const pulse = Math.sin(time * 0.03 + n.pulsePhase) * 0.3 + 0.7;
            ctx.beginPath();
            ctx.arc(n.x, n.y, n.size * pulse, 0, Math.PI * 2);
            ctx.fillStyle = n.color;
            ctx.globalAlpha = 0.8;
            // shadowBlur DIHAPUS - penyebab utama lag GPU
            ctx.fill();
            ctx.globalAlpha = 1;
        }

        function drawSynapses() {
            const maxDist = 150;
            const maxDistSq = maxDist * maxDist;
            for (let i = 0; i < neurons.length; i++) {
                for (let j = i + 1; j < neurons.length; j++) {
                    const dx = neurons[i].x - neurons[j].x;
                    const dy = neurons[i].y - neurons[j].y;
                    const distSq = dx * dx + dy * dy; // Hindari sqrt untuk pasangan jauh
                    if (distSq < maxDistSq) {
                        const distance = Math.sqrt(distSq);
                        const opacity = (1 - distance / maxDist) * 0.4;
                        ctx.beginPath();
                        ctx.strokeStyle = 'rgba(16, 185, 129, ' + opacity + ')';
                        ctx.lineWidth = 0.5;
                        ctx.moveTo(neurons[i].x, neurons[i].y);
                        ctx.lineTo(neurons[j].x, neurons[j].y);
                        ctx.stroke();

                        // Synaptic signal (tanpa shadowBlur)
                        if (Math.random() < 0.003) {
                            const signalPos = (time * 0.02) % 1;
                            const sx = neurons[i].x + (neurons[j].x - neurons[i].x) * signalPos;
                            const sy = neurons[i].y + (neurons[j].y - neurons[i].y) * signalPos;
                            ctx.beginPath();
                            ctx.arc(sx, sy, 2, 0, Math.PI * 2);
                            ctx.fillStyle = '#F59E0B';
                            ctx.globalAlpha = 0.9;
                            ctx.fill();
                            ctx.globalAlpha = 1;
                        }
                    }
                }
            }
        }

        function updateNeurons() {
            neurons.forEach(n => {
                n.x += n.vx;
                n.y += n.vy;
                if (n.x < 0 || n.x > width) n.vx *= -1;
                if (n.y < 0 || n.y > height) n.vy *= -1;
            });
        }

        function animate(currentTime) {
            requestAnimationFrame(animate);
            // Stop total saat tab hidden atau hero tidak terlihat
            if (!running || document.hidden || window.__nnHeroVisible === false) return;
            // Frame limiter 30 FPS
            const elapsed = currentTime - lastFrame;
            if (elapsed < frameInterval) return;
            lastFrame = currentTime - (elapsed % frameInterval);

            ctx.clearRect(0, 0, width, height);
            drawSynapses();
            neurons.forEach(drawNeuron);
            updateNeurons();
            time++;
        }

        window.addEventListener('nn:hero-visibility', function(e) {
            running = Boolean(e.detail);
        });

        document.addEventListener('visibilitychange', function() {
            running = !document.hidden && window.__nnHeroVisible !== false;
        });

        resize();
        window.addEventListener('resize', resize, { passive: true });
        requestAnimationFrame(animate);
    }

    // ===== 2. Typing Effect =====
    const textElement = document.getElementById('nnTypingText');
    if (textElement) {
        const phrases = [
            "AI kami siap menjawab semua pertanyaan Anda 24/7.",
            "Temukan jawaban cepat untuk pertanyaan seputar pendaftaran, akademik, dan beasiswa.",
            "Setiap pertanyaan adalah neuron yang siap diaktifkan di otak digital kami.",
            "Knowledge base yang terus berkembang untuk membantu Anda."
        ];
        let phraseIndex = 0, charIndex = 0, isDeleting = false;
        
        function type() {
            const currentPhrase = phrases[phraseIndex];
            if (isDeleting) {
                textElement.textContent = currentPhrase.substring(0, charIndex - 1);
                charIndex--;
            } else {
                textElement.textContent = currentPhrase.substring(0, charIndex + 1);
                charIndex++;
            }
            
            let typeSpeed = isDeleting ? 30 : 50;
            if (!isDeleting && charIndex === currentPhrase.length) {
                typeSpeed = 2500;
                isDeleting = true;
            } else if (isDeleting && charIndex === 0) {
                isDeleting = false;
                phraseIndex = (phraseIndex + 1) % phrases.length;
                typeSpeed = 500;
            }
            setTimeout(type, typeSpeed);
        }
        type();
    }

    // ===== 3. Counter Animation =====
    const counters = document.querySelectorAll('.nn-bstat-num[data-count]');
    const counterObs = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && entry.target) {
                const el = entry.target;
                const target = parseInt(el.getAttribute('data-count')) || 0;
                const duration = 2000;
                const startTime = performance.now();
                const easeOutQuart = t => 1 - Math.pow(1 - t, 4);
                
                function update(now) {
                    const progress = Math.min((now - startTime) / duration, 1);
                    const current = Math.floor(target * easeOutQuart(progress));
                    el.textContent = current;
                    if (progress < 1) requestAnimationFrame(update);
                    else el.textContent = target;
                }
                requestAnimationFrame(update);
                counterObs.unobserve(el);
            }
        });
    }, { threshold: 0.5 });
    counters.forEach(c => counterObs.observe(c));

    // ===== 4. Reading Progress Bar (THROTTLED) =====
    const progressFill = document.getElementById('nnProgressFill');
    let progressTicking = false;
    if (progressFill) {
        window.addEventListener('scroll', () => {
            if (!progressTicking) {
                window.requestAnimationFrame(() => {
                    const scrolled = window.scrollY;
                    const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
                    const percent = maxScroll > 0 ? (scrolled / maxScroll) * 100 : 0;
                    progressFill.style.width = percent + '%';
                    progressTicking = false;
                });
                progressTicking = true;
            }
        }, { passive: true });
    }

    // ===== 5. 3D Tilt Effect (THROTTLED dengan rAF) =====
    const tiltElements = document.querySelectorAll('.nn-tilt');
    tiltElements.forEach(el => {
        if (!el) return;
        let tiltTicking = false;
        let lastX = 0, lastY = 0;

        el.addEventListener('mousemove', (e) => {
            lastX = e.clientX;
            lastY = e.clientY;
            if (!tiltTicking) {
                tiltTicking = true;
                window.requestAnimationFrame(() => {
                    const rect = el.getBoundingClientRect();
                    const x = lastX - rect.left;
                    const y = lastY - rect.top;
                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;
                    const rotateX = ((y - centerY) / centerY) * -3;
                    const rotateY = ((x - centerX) / centerX) * 3;
                    el.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
                    tiltTicking = false;
                });
            }
        }, { passive: true });

        el.addEventListener('mouseleave', () => {
            el.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
        }, { passive: true });
    });

    // ===== 6. Scroll Reveal =====
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && entry.target) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
    
    document.querySelectorAll('.fade-up').forEach(el => {
        if (el) observer.observe(el);
    });

    // ===== 7. Neuron Toggle (FAQ Accordion) =====
    window.nnToggleNeuron = function(btn) {
        const neuron = btn.closest('.nn-neuron');
        if (!neuron) return;
        
        const body = neuron.querySelector('.nn-neuron-body');
        const isActive = neuron.classList.contains('active');
        
        document.querySelectorAll('.nn-neuron.active').forEach(activeNeuron => {
            if (activeNeuron !== neuron) {
                activeNeuron.classList.remove('active');
                const activeBtn = activeNeuron.querySelector('.nn-neuron-head');
                if (activeBtn) activeBtn.setAttribute('aria-expanded', 'false');
                const activeBody = activeNeuron.querySelector('.nn-neuron-body');
                if (activeBody) activeBody.style.maxHeight = null;
            }
        });

        if (isActive) {
            neuron.classList.remove('active');
            btn.setAttribute('aria-expanded', 'false');
            if (body) body.style.maxHeight = null;
        } else {
            neuron.classList.add('active');
            btn.setAttribute('aria-expanded', 'true');
            if (body) body.style.maxHeight = body.scrollHeight + 'px';
            
            const rect = neuron.getBoundingClientRect();
            if (rect.top < 100 || rect.bottom > window.innerHeight) {
                neuron.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        
        updateOpenCount();
    };

    function updateOpenCount() {
        const countEl = document.getElementById('nnOpenCount');
        if (countEl) {
            const count = document.querySelectorAll('.nn-neuron.active').length;
            countEl.textContent = count;
        }
    }

    // ===== 8. Expand All / Collapse All =====
    window.nnExpandAll = function() {
        document.querySelectorAll('.nn-neuron').forEach(neuron => {
            if (!neuron.classList.contains('active') && neuron.style.display !== 'none') {
                neuron.classList.add('active');
                const btn = neuron.querySelector('.nn-neuron-head');
                if (btn) btn.setAttribute('aria-expanded', 'true');
                const body = neuron.querySelector('.nn-neuron-body');
                if (body) body.style.maxHeight = body.scrollHeight + 'px';
            }
        });
        updateOpenCount();
        nnShowToast('🧠 All neurons activated!');
    };

    window.nnCollapseAll = function() {
        document.querySelectorAll('.nn-neuron.active').forEach(neuron => {
            neuron.classList.remove('active');
            const btn = neuron.querySelector('.nn-neuron-head');
            if (btn) btn.setAttribute('aria-expanded', 'false');
            const body = neuron.querySelector('.nn-neuron-body');
            if (body) body.style.maxHeight = null;
        });
        updateOpenCount();
        nnShowToast('💤 All neurons deactivated');
    };

    // ===== 9. Live Search (DEBOUNCED 120ms) =====
    const searchInput = document.getElementById('nnSearchInput');
    const searchClear = document.getElementById('nnSearchClear');
    const neuronsContainer = document.getElementById('nnNeurons');
    const noResults = document.getElementById('nnNoResults');
    const resultsCount = document.getElementById('nnResultsCount');
    const searchQueryEl = document.getElementById('nnSearchQuery');

    if (searchInput && neuronsContainer) {
        let searchDebounce;
        searchInput.addEventListener('input', (e) => {
            const value = e.target.value;
            if (searchClear) searchClear.style.display = value.trim() ? 'flex' : 'none';

            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                const query = value.toLowerCase().trim();
                const neurons = neuronsContainer.querySelectorAll('.nn-neuron');
                let visibleCount = 0;
                
                neurons.forEach(neuron => {
                    const qText = neuron.getAttribute('data-question') || '';
                    const aText = neuron.getAttribute('data-answer') || '';
                    
                    if (!query || qText.includes(query) || aText.includes(query)) {
                        neuron.style.display = '';
                        visibleCount++;
                    } else {
                        neuron.style.display = 'none';
                        neuron.classList.remove('active');
                        const body = neuron.querySelector('.nn-neuron-body');
                        if (body) body.style.maxHeight = null;
                    }
                });

                if (resultsCount) resultsCount.textContent = visibleCount;
                
                if (noResults) {
                    if (query && visibleCount === 0) {
                        noResults.style.display = 'block';
                        if (searchQueryEl) searchQueryEl.textContent = value;
                    } else {
                        noResults.style.display = 'none';
                    }
                }
                
                updateOpenCount();
            }, 120);
        });
    }

    if (searchClear) {
        searchClear.addEventListener('click', () => {
            if (searchInput) {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
                searchInput.focus();
            }
        });
    }

    window.nnResetSearch = function() {
        if (searchInput) {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.focus();
        }
    };

    // ===== 10. Suggestion Buttons =====
    document.querySelectorAll('.nn-suggestion').forEach(btn => {
        btn.addEventListener('click', () => {
            const q = btn.getAttribute('data-q');
            if (searchInput && q) {
                searchInput.value = q;
                searchInput.dispatchEvent(new Event('input'));
                searchInput.focus();
            }
        });
    });

    // ===== 11. Topic Buttons =====
    document.querySelectorAll('.nn-topic-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const topic = btn.getAttribute('data-topic');
            document.querySelectorAll('.nn-topic-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            if (searchInput && topic) {
                searchInput.value = topic;
                searchInput.dispatchEvent(new Event('input'));
                searchInput.focus();
                
                if (neuronsContainer) {
                    neuronsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    });

    function updateTopicCounts() {
        if (!neuronsContainer) return;
        const neurons = neuronsContainer.querySelectorAll('.nn-neuron');
        
        document.querySelectorAll('.nn-topic-btn').forEach(btn => {
            const topic = btn.getAttribute('data-topic');
            const countEl = btn.querySelector('.nn-topic-count');
            if (!countEl || !topic) return;
            
            let count = 0;
            neurons.forEach(neuron => {
                const qText = neuron.getAttribute('data-question') || '';
                const aText = neuron.getAttribute('data-answer') || '';
                if (qText.includes(topic) || aText.includes(topic)) count++;
            });
            countEl.textContent = count;
        });
    }
    setTimeout(updateTopicCounts, 500);

    // ===== 12. Helpful Buttons =====
    window.nnHelpful = function(btn, type, id) {
        const parent = btn.closest('.nn-helpful-btns');
        if (!parent) return;
        
        parent.querySelectorAll('.nn-helpful-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        const key = 'nn_helpful_' + id;
        localStorage.setItem(key, type);
        
        nnShowToast(type === 'yes' ? '👍 Terima kasih atas feedback Anda!' : '📝 Kami akan meningkatkan jawaban ini');
    };

    document.querySelectorAll('.nn-neuron').forEach(neuron => {
        const id = neuron.id.replace('nn-neuron-', '');
        const saved = localStorage.getItem('nn_helpful_' + id);
        if (saved) {
            const btn = neuron.querySelector('.nn-helpful-btn.nn-' + saved);
            if (btn) btn.classList.add('active');
        }
    });

    // ===== 13. Copy Answer =====
    window.nnCopyAnswer = function(btn) {
        const neuron = btn.closest('.nn-neuron');
        if (!neuron) return;
        
        const content = neuron.querySelector('.nn-answer-content');
        if (!content) return;
        
        const text = content.innerText;
        navigator.clipboard.writeText(text).then(() => {
            btn.innerHTML = '<i class="fas fa-check"></i>';
            btn.style.background = 'var(--nn-primary)';
            btn.style.color = '#fff';
            nnShowToast('📋 Jawaban disalin ke clipboard!');
            
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-copy"></i>';
                btn.style.background = '';
                btn.style.color = '';
            }, 2000);
        }).catch(() => {
            nnShowToast('⚠️ Gagal menyalin');
        });
    };

    // ===== 14. Keyboard Shortcuts =====
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (searchInput) searchInput.focus();
        }
        if (e.key === 'Escape') {
            nnCollapseAll();
        }
    });

    // ===== 15. Toast =====
    window.nnShowToast = function(message) {
        const toast = document.getElementById('nnToast');
        if (!toast) return;
        const msgEl = document.getElementById('nnToastMsg');
        if (msgEl) msgEl.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    };

    // ===== 16. Back to Top (THROTTLED) =====
    const backTop = document.getElementById('nnBackTop');
    let backTopTicking = false;
    if (backTop) {
        window.addEventListener('scroll', () => {
            if (!backTopTicking) {
                window.requestAnimationFrame(() => {
                    if (window.scrollY > 600) backTop.classList.add('show');
                    else backTop.classList.remove('show');
                    backTopTicking = false;
                });
                backTopTicking = true;
            }
        }, { passive: true });
        backTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ===== 17. Parallax Orbs (THROTTLED) =====
    const heroSection = document.querySelector('.nn-hero');
    let parallaxTicking = false;
    if (heroSection && window.innerWidth > 768) {
        window.addEventListener('scroll', () => {
            if (!parallaxTicking) {
                window.requestAnimationFrame(() => {
                    const scrolled = window.pageYOffset;
                    const orbs = heroSection.querySelectorAll('.nn-orb');
                    orbs.forEach((orb, index) => {
                        const speed = (index + 1) * 0.25;
                        orb.style.transform = `translateY(${scrolled * speed}px)`;
                    });
                    parallaxTicking = false;
                });
                parallaxTicking = true;
            }
        }, { passive: true });
    }

    // ===== 18. Auto-Open from URL Hash =====
    const hash = window.location.hash;
    if (hash) {
        const targetItem = document.querySelector(hash);
        if (targetItem && targetItem.classList.contains('nn-neuron')) {
            setTimeout(() => {
                targetItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
                const btn = targetItem.querySelector('.nn-neuron-head');
                if (btn) nnToggleNeuron(btn);
            }, 600);
        }
    }

    setTimeout(() => {
        nnShowToast('🧠 Welcome to Neural Nexus!');
    }, 1500);

    console.log('%c╔════════════════════════════════════════╗', 'color:#10B981;font-weight:bold');
    console.log('%c║   NEURAL NEXUS - AI Knowledge Brain    ║', 'color:#10B981;font-weight:bold;font-size:14px');
    console.log('%c╚════════════════════════════════════════╝', 'color:#10B981;font-weight:bold');
    console.log('%c🧠 ' + document.querySelectorAll('.nn-neuron').length + ' neurons loaded (OPTIMIZED)', 'color:#64748b;font-size:11px');
});
</script>

<?php require_once 'includes/footer.php'; ?>