document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const menuToggle = document.getElementById('menuToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const breadcrumbCurrent = document.getElementById('breadcrumbCurrent');
    const currentDate = document.getElementById('currentDate');
    const greetingTime = document.getElementById('greetingTime');

    const openSidebar = () => {
        sidebar?.classList.add('active');
        sidebarOverlay?.classList.add('active');
    };

    const closeSidebar = () => {
        sidebar?.classList.remove('active');
        sidebarOverlay?.classList.remove('active');
    };

    menuToggle?.addEventListener('click', openSidebar);
    sidebarClose?.addEventListener('click', closeSidebar);
    sidebarOverlay?.addEventListener('click', closeSidebar);

    document.querySelectorAll('.nav-item[data-page]').forEach((item) => {
        item.addEventListener('click', (event) => {
            event.preventDefault();

            const pageId = item.dataset.page;
            document.querySelectorAll('.nav-item[data-page]').forEach((navItem) => {
                navItem.classList.remove('active');
            });
            document.querySelectorAll('.page').forEach((page) => {
                page.classList.remove('active');
            });

            item.classList.add('active');
            document.getElementById(`page-${pageId}`)?.classList.add('active');

            if (breadcrumbCurrent) {
                const label = item.querySelector('span')?.textContent?.trim();
                breadcrumbCurrent.textContent = label || 'Dashboard';
            }

            closeSidebar();
        });
    });

    if (currentDate) {
        currentDate.textContent = new Intl.DateTimeFormat('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(new Date());
    }

    if (greetingTime) {
        const hour = new Date().getHours();
        greetingTime.textContent = hour < 11
            ? 'Selamat Pagi'
            : hour < 15
                ? 'Selamat Siang'
                : hour < 18
                    ? 'Selamat Sore'
                    : 'Selamat Malam';
    }
});
