/**
 * Course form tabs management
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

(function() {
    'use strict';

    /**
     * Initialize course form tabs
     */
    function initCourseTabs() {
        const tabButtons = document.querySelectorAll('.nav-link');
        const tabPanes = document.querySelectorAll('.tab-pane');

        if (tabButtons.length === 0) {
            return;
        }

        // Handle tab button clicks
        tabButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();

                const targetId = this.getAttribute('data-target') || this.getAttribute('data-target');
                if (!targetId) {
                    return;
                }

                // Remove active class from all tabs
                tabButtons.forEach(btn => {
                    btn.classList.remove('active');
                    btn.setAttribute('aria-selected', 'false');
                });

                // Remove show class from all panes
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
                }
            });
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCourseTabs);
    } else {
        initCourseTabs();
    }
})();
