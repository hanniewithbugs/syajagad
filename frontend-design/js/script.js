// [KODE SEBELUMNYA TETAP, TAMBAH INI]

// Progress Bar Animation
function animateProgressBar() {
    const progressFill = document.querySelector('.progress-fill');
    if (progressFill) {
        progressFill.style.width = '0%';
        progressFill.offsetHeight;
        progressFill.style.width = '75%';
    }
}

// Notification Animation Loop
function animateNotifications() {
    const notifContainer = document.querySelector('.floating-notif');
    if (notifContainer) {
        const messages = [
            'Bayar sukses!',
            'Santri 001 lunas',
            'Transfer BCA masuk',
            'QRIS verified'
        ];
        
        let index = 0;
        setInterval(() => {
            const notifItem = notifContainer.querySelector('.notif-item span');
            if (notifItem) {
                notifItem.textContent = messages[index];
                index = (index + 1) % messages.length;
            }
        }, 3000);
    }
}

// Bill Status Toggle (demo)
function toggleBillStatus() {
    const status = document.querySelector('.status');
    setTimeout(() => {
        if (status) {
            status.className = 'status paid';
            status.textContent = 'Lunas';
            status.style.background = 'rgba(16, 185, 129, 0.2)';
            status.style.color = '#10b981';
        }
    }, 5000);
}

// Initialize PondokPay Animations
document.addEventListener('DOMContentLoaded', () => {
    animateCounters();
    handleNavbar();
    initHeroAnimations();
    enhanceFloatingAnimations();
    animateProgressBar();
    animateNotifications();
    toggleBillStatus();
    
    console.log('🏛️ PondokPay - Sistem Internal Pondok Pesantren Loaded! ✅');
});