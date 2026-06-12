jQuery(document).ready(function($) {
    
    console.log('Exit Intent Script Loaded');
    console.log('Checkout screen present:', $('.checkout-screen').length > 0);
    
    // ============================================================================
    // TOGGLE BETWEEN VERSIONS
    // ============================================================================
    // Set to 'browser' for native browser warning (CURRENTLY ACTIVE)
    // Set to 'swal2' for custom SweetAlert2 modal on hover
    const EXIT_INTENT_MODE = 'browser';
    // ============================================================================
    
    // Flag to allow navigation
    let allowNavigation = false;
    
    /**
     * Check if we should show exit warning
     */
    function shouldShowExitWarning() {
        return $('.checkout-screen').length > 0 && !allowNavigation;
    }
    
    /**
     * Disable exit warning when clicking .next-btn or other internal navigation
     */
    $(document).on('click', '.next-btn, .checkout-btn', function() {
        console.log('Next button clicked - disabling exit warning');
        allowNavigation = true;
    });
    
    
    // ============================================================================
    // VERSION 1: BROWSER WARNING (CURRENTLY ACTIVE)
    // ============================================================================
    if (EXIT_INTENT_MODE === 'browser') {
        /**
         * Browser Unload Protection
         * Triggers on: back button, tab close, browser close, typing new URL
         * Shows browser's native "Leave site?" dialog
         */
        window.addEventListener('beforeunload', function(e) {
            if (shouldShowExitWarning()) {
                console.log('Beforeunload - showing browser warning');
                e.preventDefault();
                e.returnValue = '';
                return '';
            }
        });
    }
    
    
    // ============================================================================
    // VERSION 2: SWEETALERT2 HOVER MODAL (CURRENTLY DISABLED)
    // ============================================================================
    if (EXIT_INTENT_MODE === 'swal2') {
        /**
         * Exit Intent Detection - Hover Only
         * Only triggers when mouse leaves through TOP of viewport
         */
        document.documentElement.addEventListener('mouseout', function(e) {
            // Only trigger when mouse exits near top
            if (e.clientY <= 50 && e.relatedTarget == null && shouldShowExitWarning()) {
                console.log('✅ EXIT INTENT TRIGGERED! clientY:', e.clientY);
                showExitIntentModal();
            }
        });
        
        /**
         * Show custom exit intent modal using SweetAlert2
         */
        function showExitIntentModal() {
            console.log('🚨 Showing SweetAlert2 modal 🚨');
            
            Swal.fire({
                title: 'Warning!',
                html: 'Closing the window will lose your order.<br>Are you sure you want to leave?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, leave page',
                cancelButtonText: 'Stay and continue',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log('User chose to leave');
                    window.location.href = '/';
                } else {
                    console.log('User chose to stay');
                }
            });
        }
    }
    
    
    /**
     * Optional: Clear exit intent when order completes
     */
    window.clearExitIntent = function() {
        allowNavigation = true;
        console.log('Exit intent disabled');
    };
    
});