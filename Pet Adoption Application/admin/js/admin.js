// Petopia Admin Portal JavaScript

document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Sidebar Collapse Functionality
    const sidebar = document.querySelector('.admin-sidebar');
    const mainWrapper = document.querySelector('.admin-main-wrapper');
    const toggleBtn = document.querySelector('.sidebar-toggle-btn');
    
    if (sidebar && mainWrapper && toggleBtn) {
        // Load state from localStorage
        const isCollapsed = localStorage.getItem('admin_sidebar_collapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            mainWrapper.classList.add('expanded');
        }
        
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainWrapper.classList.toggle('expanded');
            
            // Save state
            const collapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('admin_sidebar_collapsed', collapsed);
        });
    }

    // 2. Profile Dropdown & Inquiries Dropdown Toggle
    const avatarTrigger = document.getElementById('admin-avatar-trigger');
    const avatarDropdown = document.getElementById('admin-avatar-dropdown');
    
    const triggerBtn = document.getElementById('notificationMenuBtn');
    const popoutWindow = document.getElementById('notificationPopoutBox');
    
    if (avatarTrigger && avatarDropdown) {
        avatarTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            avatarDropdown.classList.toggle('active');
            if (popoutWindow) popoutWindow.classList.add('hidden-window');
        });
    }
    
    if (triggerBtn && popoutWindow) {
        triggerBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            popoutWindow.classList.toggle('hidden-window');
            if (avatarDropdown) avatarDropdown.classList.remove('active');
        });
    }
    
    document.addEventListener('click', function(e) {
        if (avatarDropdown && !avatarTrigger.contains(e.target) && !avatarDropdown.contains(e.target)) {
            avatarDropdown.classList.remove('active');
        }
        if (popoutWindow && !triggerBtn.contains(e.target) && !popoutWindow.contains(e.target)) {
            popoutWindow.classList.add('hidden-window');
        }
    });
});

/**
 * Confirm delete/block actions
 */
function confirmAction(message) {
    return confirm(message || "Are you sure you want to perform this action? This cannot be undone.");
}
