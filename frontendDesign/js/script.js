// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
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

// Navbar scroll effect
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.style.background = 'rgba(13, 15, 37, 0.95)';
        navbar.style.backdropFilter = 'blur(25px)';
    } else {
        navbar.style.background = 'rgba(13, 15, 37, 0.9)';
        navbar.style.backdropFilter = 'blur(20px)';
    }
});

// CTA button shimmer effect
document.querySelectorAll('.cta-button').forEach(button => {
    button.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-4px) scale(1.02)';
    });
    
    button.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
    });
});

// Primary CTA button click effect
const primaryCta = document.querySelector('.cta-button.primary');
if (primaryCta) {
    primaryCta.addEventListener('click', function() {
        // Pulse animation
        this.style.transform = 'scale(0.95)';
        setTimeout(() => {
            this.style.transform = 'translateY(-4px) scale(1.02)';
        }, 150);
        
        setTimeout(() => {
            this.style.transform = 'translateY(0) scale(1)';
        }, 300);
        
        // Simulate loading state
        const originalText = this.textContent;
        this.textContent = '🚀 Launching...';
        this.style.background = 'linear-gradient(135deg, #ABBA7C, #3D5300)';
        
        setTimeout(() => {
            this.textContent = originalText;
            this.style.background = 'linear-gradient(135deg, #FFE31A 0%, #F09319 100%)';
        }, 2000);
    });
}

// Floating shapes animation
function animateShapes() {
    const shapes = document.querySelectorAll('.shape');
    shapes.forEach((shape, index) => {
        const duration = 6 + index * 2;
        shape.animate([
            { transform: 'translateY(0px) rotate(0deg)', opacity: 0.6 },
            { transform: 'translateY(-30px) rotate(180deg)', opacity: 0.3 },
            { transform: 'translateY(0px) rotate(360deg)', opacity: 0.6 }
        ], {
            duration: duration * 1000,
            iterations: Infinity,
            easing: 'ease-in-out'
        });
    });
}

// Coin floating animation enhancement
function enhanceCoinAnimations() {
    const coins = document.querySelectorAll('.coin');
    coins.forEach((coin, index) => {
        coin.animate([
            { transform: 'translateY(0) rotate(0deg) scale(1)', opacity: 0.8 },
            { transform: 'translateY(-20px) rotate(180deg) scale(1.1)', opacity: 1 },
            { transform: 'translateY(-10px) rotate(360deg) scale(0.9)', opacity: 0.7 },
            { transform: 'translateY(0) rotate(720deg) scale(1)', opacity: 0.8 }
        ], {
            duration: (4000 + index * 1000),
            iterations: Infinity,
            easing: 'ease-in-out'
        });
    });
}

// Mouse parallax effect for hero section
document.addEventListener('mousemove', (e) => {
    const hero = document.querySelector('.hero');
    const rect = hero.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    
    const centerX = rect.width / 2;
    const centerY = rect.height / 2;
    
    const rotateX = (y - centerY) / 20;
    const rotateY = (centerX - x) / 20;
    
    const illustration = document.querySelector('.illustration-container');
    if (illustration) {
        illustration.style.transform = `translateZ(0) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
    }
});

// Intersection Observer for scroll animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe hero content elements
document.querySelectorAll('.hero-title, .hero-subtitle, .hero-buttons').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(30px)';
    el.style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
    observer.observe(el);
});

// Initialize animations on load
window.addEventListener('load', () => {
    animateShapes();
    enhanceCoinAnimations();
    
    // Staggered entrance animation
    setTimeout(() => {
        document.querySelector('.hero-title').style.opacity = '1';
        document.querySelector('.hero-title').style.transform = 'translateY(0)';
    }, 300);
    
    setTimeout(() => {
        document.querySelector('.hero-subtitle').style.opacity = '1';
        document.querySelector('.hero-subtitle').style.transform = 'translateY(0)';
    }, 600);
    
    setTimeout(() => {
        document.querySelector('.hero-buttons').style.opacity = '1';
        document.querySelector('.hero-buttons').style.transform = 'translateY(0)';
    }, 900);
});

// Mobile menu toggle (for future responsive menu)
function toggleMobileMenu() {
    const navMenu = document.querySelector('.nav-menu');
    navMenu.classList.toggle('active');
}

// Prevent body scroll on mobile menu (if implemented)
document.addEventListener('DOMContentLoaded', () => {
    console.log('PayNova Landing Page Loaded Successfully! 🚀');
    
    // Add click sound effect (optional)
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    
    function playClickSound() {
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.1);
    }
    
    // Add sound to buttons
    document.querySelectorAll('.cta-button, .nav-signin').forEach(btn => {
        btn.addEventListener('click', playClickSound);
    });
});

// Performance optimization
if ('IntersectionObserver' in window) {
    // Use IntersectionObserver for better performance
} else {
    // Fallback for older browsers
    console.warn('IntersectionObserver not supported, using basic animations');
}