<?php
require_once '../app/auth/session.php';
require_once '../app/middlewares/AuthMiddleware.php';

// Initialize auth middleware
$authMiddleware = new AuthMiddleware();
$authMiddleware->requireLogin();

// Initialize session
$session = new Session();

// Check if user is already premium
$isPremium = $session->isPremium();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Features | Ricordella</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header id="redirect-top">
        <nav id="nav-bar">
            <div id="nav-logo">
                <a href="#redirect-top" id="link-logo">
                    <img src="assets/img/logo-nobg.png" id="logo" fetchpriority="high" loading="eager" alt="Logo"/>
                </a>
            </div>
            <div id="nav-links">
                <ul>
                    <li><a href="dashboard.php">Home</a></li>
                    <li><a href="today.php">Today</a></li>
                    <li><a href="shared.php">Shared</a></li>
                    <li><a href="premium.php" class="active">Premium</a></li>
                    <?php if ($session->isAdmin()): ?>
                        <li><a href="admin.php">Admin</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="user-icon">
                <div class="user-dropdown">
                    <button class="user-dropbtn" id="user-menu-btn">
                        <i class="fas fa-user-circle"></i>
                        <span id="username-display"><?= htmlspecialchars($session->getUsername()) ?></span>
                        <i class="fas fa-caret-down"></i>
                    </button>
                    <div class="user-dropdown-content">
                        <a href="../app/auth/logout.php" id="logout-btn">Logout</a>
                        <a href="profile.php" id="profile-link">Profile</a>
                        <a href="settings.php" id="settings-link">Settings</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="premium-banner">
            <div class="premium-icon">
                <i class="fas fa-crown"></i>
            </div>
            <div class="premium-title">
                <h1>Ricordella Premium</h1>
                <?php if ($isPremium): ?>
                    <div class="premium-badge">You are a Premium User</div>
                <?php else: ?>
                    <p>Unlock the full potential of Ricordella</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="premium-content">
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <h3>Urgent Priority Level</h3>
                    <p>Use the "Immediate" priority level for your most important tasks and notes.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <h3>Custom Note Themes</h3>
                    <p>Personalize your notes with custom colors and backgrounds.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <h3>Unlimited Storage</h3>
                    <p>Store as many notes as you want with our unlimited storage plan.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-export"></i>
                    </div>
                    <h3>Export Options</h3>
                    <p>Export your notes in various formats (PDF, Word, Markdown).</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h3>Advanced Task Lists</h3>
                    <p>Convert your notes to actionable task lists with reminders.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>Priority Support</h3>
                    <p>Get faster responses from our dedicated support team.</p>
                </div>
            </div>

            <?php if (!$isPremium): ?>
                <div class="pricing-section">
                    <h2>Choose Your Plan</h2>
                    <div class="pricing-options">
                        <div class="pricing-card">
                            <div class="pricing-header">
                                <h3>Monthly</h3>
                                <div class="price">€4.99 <span>/month</span></div>
                            </div>
                            <div class="pricing-features">
                                <ul>
                                    <li><i class="fas fa-check"></i> All premium features</li>
                                    <li><i class="fas fa-check"></i> Cancel anytime</li>
                                    <li><i class="fas fa-check"></i> Email support</li>
                                </ul>
                            </div>
                            <button class="btn-subscribe" data-plan="monthly">Subscribe</button>
                        </div>

                        <div class="pricing-card recommended">
                            <div class="recommendation-badge">Best Value</div>
                            <div class="pricing-header">
                                <h3>Annual</h3>
                                <div class="price">€49.99 <span>/year</span></div>
                                <div class="savings">Save 17%</div>
                            </div>
                            <div class="pricing-features">
                                <ul>
                                    <li><i class="fas fa-check"></i> All premium features</li>
                                    <li><i class="fas fa-check"></i> Priority support</li>
                                    <li><i class="fas fa-check"></i> Early access to new features</li>
                                </ul>
                            </div>
                            <button class="btn-subscribe" data-plan="annual">Subscribe</button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="premium-status-section">
                    <h2>Your Premium Status</h2>
                    <div class="premium-info-card">
                        <div class="premium-info-header">
                            <i class="fas fa-crown"></i>
                            <h3>Premium Active</h3>
                        </div>
                        <div class="premium-info-content">
                            <p>Thank you for being a premium member! You have full access to all premium features.</p>
                            <p>If you have any questions or need assistance, please contact our premium support team.</p>
                        </div>
                        <button class="btn-contact-support">Contact Support</button>
                    </div>
                </div>
            <?php endif; ?>

            <div class="faq-section">
                <h2>Frequently Asked Questions</h2>
                <div class="faq-container">
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>What is Ricordella Premium?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Ricordella Premium is our subscription service that gives you access to advanced features, including the Immediate priority level, custom themes, unlimited storage, and more.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>How do I cancel my subscription?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>You can cancel your subscription at any time from your account settings. Your premium features will remain active until the end of your billing cycle.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>Is there a free trial?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Yes! New users can try Ricordella Premium for 14 days. Contact our support team to activate your free trial.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>What happens to my notes if I cancel?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>All your notes will remain accessible, but notes with the Immediate priority level will be automatically changed to High priority. Other premium features will no longer be available.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="footer-col">
                    <h4>company</h4>
                    <ul>
                        <li><a href="#">about us</a></li>
                        <li><a href="#">our services</a></li>
                        <li><a href="#">privacy policy</a></li>
                        <li><a href="#">affiliate program</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>get help</h4>
                    <ul>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">help</a></li>
                        <li><a href="#">AI chat</a></li>
                        <li><a href="#">recovery data</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Premium</h4>
                    <ul>
                        <li><a href="#">info</a></li>
                        <li><a href="#">offers</a></li>
                        <li><a href="#">account</a></li>
                        <li><a href="#">payments</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>follow us</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <hr class="footer-line">
            <p class="footer-text-line">Copyright © 2025 All rights reserved.</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // FAQ Toggle
            const faqItems = document.querySelectorAll('.faq-item');

            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question');
                const answer = item.querySelector('.faq-answer');

                question.addEventListener('click', () => {
                    // Close all other answers
                    faqItems.forEach(otherItem => {
                        if (otherItem !== item) {
                            otherItem.classList.remove('active');
                            otherItem.querySelector('.faq-answer').style.maxHeight = null;
                            otherItem.querySelector('.faq-question i').classList.replace('fa-chevron-up', 'fa-chevron-down');
                        }
                    });

                    // Toggle current answer
                    item.classList.toggle('active');
                    const icon = question.querySelector('i');

                    if (item.classList.contains('active')) {
                        answer.style.maxHeight = answer.scrollHeight + 'px';
                        icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
                    } else {
                        answer.style.maxHeight = null;
                        icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
                    }
                });
            });

            // Subscription buttons
            const subscribeButtons = document.querySelectorAll('.btn-subscribe');

            subscribeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const plan = this.getAttribute('data-plan');
                    handleSubscription(plan);
                });
            });

            // Support button
            const supportButton = document.querySelector('.btn-contact-support');
            if (supportButton) {
                supportButton.addEventListener('click', function() {
                    window.location.href = 'mailto:support@ricordella.com';
                });
            }

            function handleSubscription(plan) {
                // In a real app, this would connect to a payment processor
                alert(`Thank you for choosing the ${plan} plan! This would redirect to a payment page in a real application.`);

                // Example of simulating a redirect to a payment gateway
                // window.location.href = `checkout.php?plan=${plan}`;
            }
        });
    </script>
</body>
</html>