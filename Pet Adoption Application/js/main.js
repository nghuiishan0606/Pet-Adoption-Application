// Petopia Global JavaScript Interactivity

document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Mobile Hamburger Menu Toggle
    const hamburger = document.querySelector('.hamburger-toggle');
    const navLinks = document.querySelector('.nav-links');
    
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            
            // Optional hamburger animation
            const spans = hamburger.querySelectorAll('span');
            if (navLinks.classList.contains('active')) {
                spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translate(6px, -6px)';
            } else {
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });
    }

    // Close mobile menu on clicking any link
    const links = document.querySelectorAll('.nav-links a');
    links.forEach(link => {
        link.addEventListener('click', () => {
            if (navLinks && navLinks.classList.contains('active')) {
                navLinks.classList.remove('active');
                if (hamburger) {
                    const spans = hamburger.querySelectorAll('span');
                    spans[0].style.transform = 'none';
                    spans[1].style.opacity = '1';
                    spans[2].style.transform = 'none';
                }
            }
        });
    });

    // 1.5. User Notification Dropdown Toggle (Popout Box)
    const triggerBtn = document.getElementById('notificationMenuBtn');
    const popoutWindow = document.getElementById('notificationPopoutBox');

    if (triggerBtn && popoutWindow) {
        // Toggle the popup box visibility
        triggerBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            popoutWindow.classList.toggle('hidden-window');
        });

        // Hide window if user clicks elsewhere on screen
        document.addEventListener('click', function(e) {
            if (!popoutWindow.contains(e.target) && e.target !== triggerBtn) {
                popoutWindow.classList.add('hidden-window');
            }
        });
    }

    // 2. Wishlist Heart Toggles via Async Fetch API
    const wishlistBtns = document.querySelectorAll('.wishlist-heart-btn');
    wishlistBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const petId = this.getAttribute('data-pet-id');
            if (!petId) return;
            
            // Check if user is logged in (normally checked server side, but safe check here)
            fetch('wishlist_toggle.php?pet_id=' + petId, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.action === 'added') {
                        this.classList.add('active');
                        this.innerHTML = '❤️';
                    } else if (data.action === 'removed') {
                        this.classList.remove('active');
                        this.innerHTML = '🤍';
                        
                        // If we are currently on the profile page in the Wishlist tab, reload that panel or page
                        const wishlistTab = document.querySelector('.profile-menu-item[data-tab="wishlist"]');
                        if (wishlistTab && wishlistTab.classList.contains('active')) {
                            location.reload();
                        }
                    }
                } else if (data.status === 'unauthorized') {
                    // Redirect to login if unauthorized
                    window.location.href = 'login.php';
                } else {
                    alert(data.message || 'An error occurred.');
                }
            })
            .catch(error => {
                console.error('Error toggling wishlist:', error);
            });
        });
    });

    // 3. Tab Navigation (e.g., Profile Page tabs)
    const tabMenuItems = document.querySelectorAll('.profile-menu-item[data-tab]');
    const tabPanels = document.querySelectorAll('.tab-panel');
    
    if (tabMenuItems.length > 0 && tabPanels.length > 0) {
        // Function to activate a specific tab
        function activateTab(tabId) {
            // Update active menu item
            tabMenuItems.forEach(item => {
                if (item.getAttribute('data-tab') === tabId) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });
            
            // Update active tab panel
            tabPanels.forEach(panel => {
                if (panel.id === 'tab-' + tabId) {
                    panel.style.display = 'block';
                } else {
                    panel.style.display = 'none';
                }
            });
            
            // Store tab selection in hash URL
            window.location.hash = tabId;
        }

        // Add click listener to tabs
        tabMenuItems.forEach(item => {
            item.addEventListener('click', function(e) {
                const tabId = this.getAttribute('data-tab');
                if (tabId) {
                    activateTab(tabId);
                }
            });
        });

        // Initialize active tab from URL Hash (or default to edit-profile)
        const hash = window.location.hash.substring(1);
        const validTabs = Array.from(tabMenuItems).map(i => i.getAttribute('data-tab'));
        if (hash && validTabs.includes(hash)) {
            activateTab(hash);
        } else {
            // default to first tab
            activateTab(validTabs[0]);
        }
    }
});

// 4. Modal Overlay Open/Close Helpers
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent body scrolling
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = ''; // Re-enable body scrolling
    }
}
