/**
 * Course form tabs management - ONLY for localcustomadmin pages
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

(function() {
    'use strict';

    /**
     * Initialize course form tabs - ONLY for elegant-tabs in localcustomadmin
     */
    function initCourseTabs() {
        // ISOLAMENTO: Só executar em páginas do localcustomadmin
        const isLocalCustomAdminPage = document.body.id.includes('page-local-localcustomadmin') ||
                                       document.body.classList.contains('path-local-localcustomadmin') ||
                                       window.location.pathname.includes('/local/localcustomadmin/');
        
        if (!isLocalCustomAdminPage) {
            console.log('LocalCustomAdmin: Not a localcustomadmin page, skipping tabs initialization');
            return;
        }

        // ISOLAMENTO: Apenas pegar tabs dentro de containers específicos do plugin
        const tabButtons = document.querySelectorAll('.elegant-tabs-container .elegant-tab-link');
        const tabPanes = document.querySelectorAll('.elegant-tab-pane');

        if (tabButtons.length === 0) {
            console.log('LocalCustomAdmin: No elegant tabs found on this page');
            return;
        }

        console.log('LocalCustomAdmin: Initializing elegant tabs (' + tabButtons.length + ' found)');

        // Handle tab button clicks
        tabButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();

                // Suportar múltiplos atributos
                const targetId = this.getAttribute('data-bs-target') || 
                                this.getAttribute('data-target') || 
                                this.getAttribute('href');
                                
                if (!targetId || targetId === '#') {
                    console.warn('LocalCustomAdmin: No target found for tab');
                    return;
                }

                console.log('LocalCustomAdmin: Elegant tab clicked, target:', targetId);

                // Remove active class apenas dos elegant-tab-link (não de todos os .nav-link)
                tabButtons.forEach(btn => {
                    btn.classList.remove('active');
                    btn.setAttribute('aria-selected', 'false');
                });

                // Remove show class apenas dos elegant-tab-pane
                tabPanes.forEach(pane => {
                    pane.classList.remove('show', 'active');
                });

                // Add active class to current tab
                this.classList.add('active');
                this.setAttribute('aria-selected', 'true');

                // Show current pane
                const targetPane = document.querySelector(targetId);
                if (targetPane) {
                    targetPane.classList.add('show', 'active');
                    console.log('LocalCustomAdmin: Elegant tab pane shown:', targetId);
                } else {
                    console.warn('LocalCustomAdmin: Target pane not found:', targetId);
                }
            });
        });
        
        console.log('LocalCustomAdmin: Elegant tabs initialized successfully');
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCourseTabs);
    } else {
        initCourseTabs();
    }
})();
