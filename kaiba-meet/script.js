// Intersection Observer for fade-in animations
document.addEventListener('DOMContentLoaded', function() {
    // Fade-in animation on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, observerOptions);

    // Observe all fade-in-up elements
    const fadeElements = document.querySelectorAll('.fade-in-up');
    fadeElements.forEach(el => observer.observe(el));

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Add scroll indicator hide on scroll
    const scrollIndicator = document.querySelector('.scroll-indicator');
    if (scrollIndicator) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 100) {
                scrollIndicator.style.opacity = '0';
            } else {
                scrollIndicator.style.opacity = '1';
            }
        });
    }

    // Track CTA button clicks (for analytics)
    const ctaButtons = document.querySelectorAll('.cta-button, .cta-button-large');
    ctaButtons.forEach(button => {
        button.addEventListener('click', function() {
            // You can add Google Analytics or other tracking here
            console.log('CTA Button Clicked:', this.textContent);
        });
    });

    // Add parallax effect to hero section (subtle)
    const hero = document.querySelector('.hero');
    if (hero) {
        window.addEventListener('scroll', function() {
            const scrolled = window.scrollY;
            const parallax = scrolled * 0.5;
            hero.style.transform = `translateY(${parallax}px)`;
        });
    }

    // Add hover effect to stat cards with random delay
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });

    // Counter animation for numbers
    function animateCounter(element, target, duration = 2000) {
        let start = 0;
        const increment = target / (duration / 16);
        const timer = setInterval(() => {
            start += increment;
            if (start >= target) {
                element.textContent = target;
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(start);
            }
        }, 16);
    }

    // Observe stat numbers for counter animation
    const statNumbers = document.querySelectorAll('.stat-number');
    const statObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
                entry.target.classList.add('counted');
                const text = entry.target.textContent;
                const number = parseInt(text.replace(/[^0-9]/g, ''));
                if (!isNaN(number) && number > 0) {
                    entry.target.textContent = '0';
                    setTimeout(() => {
                        animateCounter(entry.target, number, 1500);
                    }, 300);
                }
            }
        });
    }, { threshold: 0.5 });

    // Add loading animation
    window.addEventListener('load', function() {
        document.body.classList.add('loaded');
    });

    // Mobile menu toggle (if needed in future)
    const menuToggle = document.querySelector('.menu-toggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            document.body.classList.toggle('menu-open');
        });
    }

    // Prevent layout shift by setting heights
    const sections = document.querySelectorAll('.section');
    sections.forEach(section => {
        section.style.minHeight = 'auto';
    });

    // Add keyboard navigation support
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            // Close any open modals or menus
            document.body.classList.remove('menu-open');
        }
    });

    // Performance optimization: Lazy load images if added later
    if ('loading' in HTMLImageElement.prototype) {
        const images = document.querySelectorAll('img[loading="lazy"]');
        images.forEach(img => {
            img.src = img.dataset.src;
        });
    }

    // Add focus visible for accessibility
    document.body.addEventListener('mousedown', function() {
        document.body.classList.add('using-mouse');
    });

    document.body.addEventListener('keydown', function() {
        document.body.classList.remove('using-mouse');
    });

    // Console message for developers
    console.log('%c한국인공지능협회 AI 정책사업 매칭 공모', 'font-size: 20px; font-weight: bold; color: #2563EB;');
    console.log('%c현장 참여를 환영합니다!', 'font-size: 14px; color: #DC2626;');
});

// Utility function: Check if element is in viewport
function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

// Utility function: Throttle scroll events
function throttle(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Add scroll progress indicator (optional)
function updateScrollProgress() {
    const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
    const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    const scrolled = (winScroll / height) * 100;
    
    // You can add a progress bar element and update it here
    // document.getElementById('scrollProgress').style.width = scrolled + '%';
}

window.addEventListener('scroll', throttle(updateScrollProgress, 100));
