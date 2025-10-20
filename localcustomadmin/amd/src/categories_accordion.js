/**
 * Fluent Design System Categories Tree functionality
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Heber
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {
    'use strict';

    /**
     * Initialize the Fluent tree view
     */
    function init() {
        console.log('Fluent Categories Tree initializing...');
        
        // Wait for DOM to be ready
        $(document).ready(function() {
            initializeFluentTree();
            setupTreeControls();
            addFluentAnimations();
        });
    }

    /**
     * Initialize Fluent tree view functionality
     */
    function initializeFluentTree() {
        var $treeContainer = $('.fluent-tree-container');
        
        if ($treeContainer.length === 0) {
            console.warn('Fluent tree container not found');
            return;
        }

        console.log('Initializing Fluent tree with', $('.tree-node').length, 'nodes');
        
        // Setup node click handlers
        setupNodeHandlers();
        
        // Setup node animations
        setupNodeAnimations();
    }

    /**
     * Setup individual node click handlers
     */
    function setupNodeHandlers() {
        // Handle node header clicks for expansion
        $(document).off('click', '.node-header[data-bs-toggle="collapse"]')
                  .on('click', '.node-header[data-bs-toggle="collapse"]', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $header = $(this);
            var targetId = $header.attr('data-bs-target');
            var $target = $(targetId);
            var $toggle = $header.find('.expand-toggle i');
            
            if ($target.length) {
                toggleNode($target, $toggle, $header);
            }
        });
        
        // Prevent action button clicks from triggering node expansion
        $(document).off('click', '.action-btn')
                  .on('click', '.action-btn', function(e) {
            e.stopPropagation();
        });
    }

    /**
     * Toggle individual node expansion
     */
    function toggleNode($target, $toggle, $header) {
        var isExpanded = $target.hasClass('show');
        
        if (isExpanded) {
            // Collapse
            $target.removeClass('show');
            $toggle.removeClass('fa-chevron-down').addClass('fa-chevron-right');
            $header.attr('aria-expanded', 'false');
            
            // Update folder icon
            $header.find('.node-icon i').removeClass('fa-folder-open').addClass('fa-folder');
        } else {
            // Expand
            $target.addClass('show');
            $toggle.removeClass('fa-chevron-right').addClass('fa-chevron-down');
            $header.attr('aria-expanded', 'true');
            
            // Update folder icon
            $header.find('.node-icon i').removeClass('fa-folder').addClass('fa-folder-open');
        }
        
        // Trigger custom events
        var eventName = isExpanded ? 'fluent:node:collapsed' : 'fluent:node:expanded';
        $target.trigger(eventName);
    }

    /**
     * Setup tree control buttons
     */
    function setupTreeControls() {
        // Expand All functionality
        $('#expandAllBtn').off('click').on('click', function(e) {
            e.preventDefault();
            expandAllNodes();
        });

        // Collapse All functionality
        $('#collapseAllBtn').off('click').on('click', function(e) {
            e.preventDefault();
            collapseAllNodes();
        });
    }

    /**
     * Expand all tree nodes
     */
    function expandAllNodes() {
        $('.node-children').each(function() {
            var $node = $(this);
            var $header = $('[data-bs-target="#' + $node.attr('id') + '"]');
            var $toggle = $header.find('.expand-toggle i');
            
            $node.addClass('show');
            $toggle.removeClass('fa-chevron-right').addClass('fa-chevron-down');
            $header.attr('aria-expanded', 'true');
            $header.find('.node-icon i').removeClass('fa-folder').addClass('fa-folder-open');
        });
        
        console.log('Expanded all tree nodes');
        $('.fluent-tree-container').trigger('fluent:tree:expanded');
    }

    /**
     * Collapse all tree nodes
     */
    function collapseAllNodes() {
        $('.node-children').each(function() {
            var $node = $(this);
            var $header = $('[data-bs-target="#' + $node.attr('id') + '"]');
            var $toggle = $header.find('.expand-toggle i');
            
            $node.removeClass('show');
            $toggle.removeClass('fa-chevron-down').addClass('fa-chevron-right');
            $header.attr('aria-expanded', 'false');
            $header.find('.node-icon i').removeClass('fa-folder-open').addClass('fa-folder');
        });
        
        console.log('Collapsed all tree nodes');
        $('.fluent-tree-container').trigger('fluent:tree:collapsed');
    }

    /**
     * Setup node animations
     */
    function setupNodeAnimations() {
        // Add transition classes to collapsible elements
        $('.node-children').addClass('fluent-collapse-transition');
        
        // Add hover effects to interactive elements
        $('.node-header[data-bs-toggle="collapse"]').addClass('fluent-interactive');
        $('.action-btn').addClass('fluent-action');
    }

    /**
     * Add Fluent Design System animations and effects
     */
    function addFluentAnimations() {
        // Add reveal animation to tree nodes on load
        $('.tree-node').each(function(index) {
            var $node = $(this);
            setTimeout(function() {
                $node.addClass('fluent-reveal');
            }, index * 50); // Staggered animation
        });
        
        // Add ripple effect to clickable elements
        $('.fluent-btn, .node-header, .action-btn').on('click', function(e) {
            addRippleEffect($(this), e);
        });
    }

    /**
     * Add ripple effect to element
     */
    function addRippleEffect($element, event) {
        var $ripple = $('<span class="fluent-ripple"></span>');
        var rect = $element[0].getBoundingClientRect();
        var size = Math.max(rect.width, rect.height);
        var x = event.clientX - rect.left - size / 2;
        var y = event.clientY - rect.top - size / 2;
        
        $ripple.css({
            width: size + 'px',
            height: size + 'px',
            left: x + 'px',
            top: y + 'px'
        });
        
        $element.append($ripple);
        
        setTimeout(function() {
            $ripple.remove();
        }, 600);
    }

    /**
     * Public API
     */
    return {
        init: init,
        expandAll: expandAllNodes,
        collapseAll: collapseAllNodes,
        toggleNode: toggleNode
    };
});