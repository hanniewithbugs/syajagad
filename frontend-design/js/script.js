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

// Universal Auth Functions
document.addEventListener('DOMContentLoaded', function() {
    initPasswordToggle();
    initFormValidation();
    initFormSubmit();
    initAnimations();
});

// Password Toggle
function initPasswordToggle() {
    document.querySelectorAll('.toggle-password').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const input = document.getElementById(targetId);
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    });
}

// Form Validation
function initFormValidation() {
    // Real-time validation
    document.querySelectorAll('input[required]').forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            validateField(this);
        });
    });
}

function validateField(input) {
    const wrapper = input.closest('.input-wrapper');
    const value = input.value.trim();
    
    wrapper.classList.remove('success', 'error');
    
    if (!value) {
        wrapper.classList.add('error');
        return false;
    }
    
    if (input.type === 'email' && !isValidEmail(value)) {
        wrapper.classList.add('error');
        return false;
    }
    
    if (input.id === 'confirmPassword') {
        const password = document.getElementById('password').value;
        if (value !== password) {
            wrapper.classList.add('error');
            return false;
        }
    }
    
    if (input.minLength && value.length < input.minLength) {
        wrapper.classList.add('error');
        return false;
    }
    
    wrapper.classList.add('success');
    return true;
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// Form Submit
function initFormSubmit() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', handleLoginSubmit);
    }
    
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegisterSubmit);
    }
}

function handleLoginSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const btn = form.querySelector('.auth-btn');
    const username = document.getElementById('loginUsername').value;
    const password = document.getElementById('loginPassword').value;
    
    if (!validateLogin(username, password)) return;
    
    showLoading(btn);
    
    // Simulate API call
    setTimeout(() => {
        hideLoading(btn);
        showSuccess('Login berhasil! Redirecting...');
        // window.location.href = '/dashboard';
    }, 2000);
}

function handleRegisterSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const btn = form.querySelector('.auth-btn');
    
    if (!validateRegisterForm()) return;
    
    showLoading(btn);
    
    // Simulate API call
    setTimeout(() => {
        hideLoading(btn);
        showSuccess('Akun berhasil dibuat! Silahkan login.');
        setTimeout(() => {
            window.location.href = '{{ route("login") }}';
        }, 1500);
    }, 2500);
}

function validateLogin(username, password) {
    if (!username || !password) {
        showError('Mohon lengkapi semua field');
        return false;
    }
    return true;
}

function validateRegisterForm() {
    const fields = ['nis', 'nama', 'tglLahir', 'alamat', 'email', 'username', 'password', 'confirmPassword'];
    let isValid = true;
    
    fields.forEach(field => {
        const input = document.getElementById(field);
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

// Loading States
function showLoading(btn) {
    btn.disabled = true;
    btn.querySelector('.btn-text').style.opacity = '0';
    btn.querySelector('.btn-loader').style.display = 'inline-flex';
}

function hideLoading(btn) {
    btn.disabled = false;
    btn.querySelector('.btn-text').style.opacity = '1';
    btn.querySelector('.btn-loader').style.display = 'none';
}

// Notifications
function showSuccess(message) {
    showNotification(message, 'success');
}

function showError(message) {
    showNotification(message, 'error');
}

function showNotification(message, type) {
    // Remove existing notification
    const existing = document.querySelector('.notification');
    if (existing) existing.remove();
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

// Forgot Password Modal
document.getElementById('forgotPassword')?.addEventListener('click', function(e) {
    e.preventDefault();
    showForgotPasswordModal();
});

function showForgotPasswordModal() {
    const modal = document.createElement('div');
    modal.className = 'forgot-modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Lupa Password?</h3>
                <button class="modal-close">&times;</button>
            </div>
            <p>Masukkan email terkait akun Anda. Kami akan kirim link reset password.</p>
            <div class="input-group">
                <label>Email</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="resetEmail" placeholder="santri@pondok.ac.id">
                </div>
            </div>
            <button class="auth-btn primary">Kirim Link Reset</button>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    modal.querySelector('.modal-close').addEventListener('click', () => modal.remove());
    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.remove();
    });
}

// Animations
function initAnimations() {
    // Entrance animation
    const form = document.querySelector('.auth-form');
    form.style.opacity = '0';
    form.style.transform = 'translateY(30px)';
    
    setTimeout(() => {
        form.style.transition = 'all 0.8s cubic-bezier(0.34, 1.56, 0.64, 1)';
        form.style.opacity = '1';
        form.style.transform = 'translateY(0)';
    }, 200);
}

// Add notification styles
const style = document.createElement('style');
style.textContent = `
    .notification {
        position: fixed;
        top: 20px;
        right: -400px;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        padding: 1.25rem 1.5rem;
        border-radius: 15px;
        box-shadow: 0 20px 50px rgba(16, 185, 129, 0.4);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-weight: 600;
        z-index: 10000;
        transform: translateX(0);
        transition: all 0.3s ease;
    }
    
    .notification.error {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        box-shadow: 0 20px 50px rgba(239, 68, 68, 0.4);
    }
    
    .notification.show {
        right: 20px;
    }
    
    .notification i {
        font-size: 1.3rem;
    }
    
    .input-wrapper.success {
        border-color: rgba(16, 185, 129, 0.5);
        background: rgba(16, 185, 129, 0.08);
    }
    
    .input-wrapper.error {
        border-color: rgba(239, 68, 68, 0.5);
        background: rgba(239, 68, 68, 0.08);
    }
    
    .forgot-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    }
    
    .modal-content {
        background: rgba(13, 15, 37, 0.95);
        backdrop-filter: blur(30px);
        border: 1px solid rgba(255, 227, 26, 0.2);
        border-radius: 25px;
        padding: 2.5rem;
        max-width: 450px;
        width: 90%;
        position: relative;
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    
    .modal-header h3 {
        color: #FFE31A;
        font-size: 1.5rem;
        font-weight: 700;
    }
    
    .modal-close {
        background: none;
        border: none;
        font-size: 1.8rem;
        color: rgba(255, 255, 255, 0.7);
        cursor: pointer;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
                transition: all 0.3s ease;
    }
    
    .modal-close:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #FFE31A;
    }
`;
document.head.appendChild(style);