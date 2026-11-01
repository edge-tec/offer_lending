/**
 * FWB Connect - High Converting Landing Page Interactive Script
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Asynchronously send Pageview Impression for accurate CTR calculation
    try {
        const currentSearch = window.location.search;
        if (navigator.sendBeacon) {
            navigator.sendBeacon('/track-view' + currentSearch);
        } else {
            fetch('/track-view' + currentSearch, { method: 'POST', keepalive: true }).catch(() => {});
        }
    } catch (e) {
        // Non-blocking
    }

    // 2. Synchronize Incoming Tracking Parameters to all CTA Buttons
    const incomingParams = new URLSearchParams(window.location.search);
    const ctaButtons = document.querySelectorAll('a.cta-btn, a[href*="/go"], a[href*="/click"]');

    ctaButtons.forEach(btn => {
        try {
            const rawHref = btn.getAttribute('href');
            if (!rawHref) return;

            const url = new URL(rawHref, window.location.origin);
            
            // Append all incoming tracking parameters (subid, clickid, utm_*, etc.)
            incomingParams.forEach((value, key) => {
                if (key !== 'preview' && !url.searchParams.has(key)) {
                    url.searchParams.set(key, value);
                }
            });

            // If button has specific campaign override in data-campaign attribute
            const overrideCampaign = btn.getAttribute('data-campaign');
            if (overrideCampaign && !url.searchParams.has('c')) {
                url.searchParams.set('c', overrideCampaign);
            }

            btn.setAttribute('href', url.pathname + url.search + url.hash);
        } catch (err) {
            // fallback gracefully
        }
    });

    // 3. Interactive FAQ Accordion
    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(item => {
        const questionBtn = item.querySelector('.faq-question');
        const answerDiv = item.querySelector('.faq-answer');

        if (!questionBtn || !answerDiv) return;

        questionBtn.addEventListener('click', () => {
            const isActive = item.classList.contains('active');

            // Close other items
            faqItems.forEach(other => {
                if (other !== item) {
                    other.classList.remove('active');
                    const otherAnswer = other.querySelector('.faq-answer');
                    if (otherAnswer) otherAnswer.style.maxHeight = null;
                }
            });

            if (isActive) {
                item.classList.remove('active');
                answerDiv.style.maxHeight = null;
            } else {
                item.classList.add('active');
                answerDiv.style.maxHeight = answerDiv.scrollHeight + 'px';
            }
        });
    });

    // 4. 18+ Age Gate Modal Verification
    const ageModal = document.getElementById('ageGateModal');
    const btnAgeConfirm = document.getElementById('btnAgeConfirm');
    const btnAgeExit = document.getElementById('btnAgeExit');

    if (ageModal) {
        const isVerified = localStorage.getItem('fwb_age_confirmed') === 'true';
        if (!isVerified) {
            ageModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        if (btnAgeConfirm) {
            btnAgeConfirm.addEventListener('click', () => {
                localStorage.setItem('fwb_age_confirmed', 'true');
                ageModal.classList.remove('active');
                document.body.style.overflow = '';
            });
        }

        if (btnAgeExit) {
            btnAgeExit.addEventListener('click', () => {
                window.location.href = 'https://www.google.com';
            });
        }
    }

    // 5. Cookie Consent Banner
    const cookieBanner = document.getElementById('cookieBanner');
    const btnAcceptCookies = document.getElementById('btnAcceptCookies');

    if (cookieBanner) {
        const consent = localStorage.getItem('fwb_cookies_consent');
        if (!consent) {
            setTimeout(() => {
                cookieBanner.classList.add('show');
            }, 1000);
        }

        if (btnAcceptCookies) {
            btnAcceptCookies.addEventListener('click', () => {
                localStorage.setItem('fwb_cookies_consent', 'accepted');
                cookieBanner.classList.remove('show');
            });
        }
    }

    // 6. Smooth Scroll for internal anchors
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId && targetId !== '#') {
                const targetEl = document.querySelector(targetId);
                if (targetEl) {
                    e.preventDefault();
                    targetEl.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    });
});

