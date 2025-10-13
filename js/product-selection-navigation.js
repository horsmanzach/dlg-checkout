jQuery(document).ready(function ($) {
    // Track current screen and whether user came from checkout
    let currentScreen = 1;
    const totalScreens = 4;
    const finalSlideRedirectUrl = 'https://diallog.magnaprototype.com/checkout';
    let cameFromCheckout = false;
    let isScrolling = false;

    // Only re-validate button states when specific events occur that should affect them
    function revalidateButtonStates() {
        setTimeout(updateButtonStates, 10); // Very short delay to let DOM settle
    }

    // Bind revalidation only to legitimate state-changing events
    $(document).on('click', '.modem-0, .modem-1, .modem-2, .modem-3, .modem-4', revalidateButtonStates);
    $(document).on('change', '.preferred-date-radio, .secondary-date-radio', revalidateButtonStates);
    $(document).on('input', '.own-modem-input', revalidateButtonStates);

    // Ultra-aggressive scroll protection - immediate blocking with zero delay
    $(window).on('scroll touchstart touchmove touchend', function (e) {
        isScrolling = true;

        // IMMEDIATELY force button states without any delay
        forceMaintainButtonStates();

        // Use multiple timeouts to catch different momentum phases
        clearTimeout(window.scrollTimeout1);
        clearTimeout(window.scrollTimeout2);
        clearTimeout(window.scrollTimeout3);

        window.scrollTimeout1 = setTimeout(forceMaintainButtonStates, 16); // Next frame
        window.scrollTimeout2 = setTimeout(forceMaintainButtonStates, 50); // iOS momentum start
        window.scrollTimeout3 = setTimeout(function () {
            isScrolling = false;
        }, 200); // Longer delay for momentum end
    });

    // Dedicated function to force correct button states
    function forceMaintainButtonStates() {
        // Force correct states immediately without any validation delays
        if (currentScreen === 1) {
            $('.back-btn, .mobile-back-btn').addClass('disabled').css('visibility', 'hidden');
            const installationSelected = $('.installation-row-selected').length;
            const preferredChecked = $('.preferred-date-radio:checked').length;
            const secondaryChecked = $('.secondary-date-radio:checked').length;
            if (!installationSelected && !(preferredChecked && secondaryChecked)) {
                $('.next-btn, .mobile-next-btn').addClass('disabled');
            }
        }
        else if (currentScreen === 2) {
            $('.back-btn, .mobile-back-btn').removeClass('disabled').css('visibility', 'visible');
            const modemCount = $('.modem-row-selected').length;
            const ownModemValid = validateOwnModemInput();
            if (modemCount === 0 && !ownModemValid) {
                $('.next-btn, .mobile-next-btn').addClass('disabled');
            }
        }
        else if (currentScreen === 3) {
            $('.back-btn, .mobile-back-btn').removeClass('disabled').css('visibility', 'visible');
            const tvSelected = $('.tv-row-selected').length;
            if (tvSelected === 0) {
                $('.next-btn, .mobile-next-btn').addClass('disabled');
            }
        }
        else if (currentScreen === 4) {
            $('.back-btn, .mobile-back-btn').removeClass('disabled').css('visibility', 'visible');
            const phoneSelected = $('.phone-row-selected').length;
            if (phoneSelected === 0) {
                $('.next-btn, .mobile-next-btn').addClass('disabled');
            }
        }
    }


    // Initialize functions
    initializeProgressBarClicks();
    adjustContainerHeight();
    updateButtonStates();
    updateCheckoutButtonVisibility();
    updateCheckoutButtonState();

    // Handle hash-based navigation on page load
    function initializeFromHash() {
        const hash = window.location.hash;
        if (hash) {
            // Extract screen number from hash (e.g., #screen2 -> 2)
            const match = hash.match(/^#screen(\d+)$/);
            if (match) {
                const targetScreen = parseInt(match[1]);
                if (targetScreen >= 1 && targetScreen <= totalScreens) {
                    console.log('Navigating to screen from hash:', targetScreen);

                    // Only set cameFromCheckout if actually coming from checkout
                    // Check if referrer contains checkout URL or if there are checkout-specific URL parameters
                    const referrer = document.referrer || '';
                    const urlParams = new URLSearchParams(window.location.search);

                    if (referrer.includes('checkout') ||
                        urlParams.has('from_checkout') ||
                        hash.includes('checkout')) {
                        cameFromCheckout = true;
                        console.log('Detected navigation from checkout');
                    } else {
                        cameFromCheckout = false;
                        console.log('Hash navigation detected, but not from checkout');
                    }

                    jumpToScreen(targetScreen);
                    // Immediately update button visibility after setting the flag
                    setTimeout(() => updateCheckoutButtonVisibility(), 50);
                    return;
                }
            }
        }
    }

    // Function to jump directly to a specific screen without animation (for hash navigation)
    function jumpToScreen(screenNumber) {
        console.log('Jumping to screen:', screenNumber);

        // Kill all existing animations
        gsap.killTweensOf('.checkout-container [id^="screen"]');

        // Hide all screens first
        for (let i = 1; i <= totalScreens; i++) {
            const screen = $(`#screen${i}`);

            // Completely reset the element
            gsap.set(screen, {
                x: 0,
                y: 0,
                opacity: 0,
                scale: 1,
                rotation: 0,
                clearProps: "transform",
                display: 'none'
            });
        }

        // Use a timeout to ensure the reset is complete
        setTimeout(() => {
            // Now show and position screens one by one
            for (let i = 1; i <= totalScreens; i++) {
                const screen = $(`#screen${i}`);

                // Make sure the screen is visible in DOM
                screen.css('display', 'block');

                if (i === screenNumber) {
                    // Target screen - center it and make visible
                    gsap.set(screen, {
                        x: '0%',
                        opacity: 1
                    });
                    console.log(`Screen ${i} positioned at center (0%)`);
                } else if (i < screenNumber) {
                    // Screens before target - position to the left
                    gsap.set(screen, {
                        x: '-100%',
                        opacity: 0
                    });
                    console.log(`Screen ${i} positioned to left (-100%)`);
                } else {
                    // Screens after target - position to the right
                    gsap.set(screen, {
                        x: '100%',
                        opacity: 0
                    });
                    console.log(`Screen ${i} positioned to right (100%)`);
                }
            }

            currentScreen = screenNumber;
            updateButtonStates();
            updateProgressBar(currentScreen);

            // Final adjustments
            setTimeout(() => {
                adjustContainerHeight();
                scrollToTop();

                // Log the final state for debugging
                console.log('Final screen positions:');
                for (let i = 1; i <= totalScreens; i++) {
                    const screen = $(`#screen${i}`);
                    const transform = screen.css('transform');
                    const opacity = screen.css('opacity');
                    console.log(`Screen ${i}: transform=${transform}, opacity=${opacity}`);
                }
            }, 150);
        }, 100);
    }

    // Check for hash navigation after the page loads
    setTimeout(function () {
        initializeFromHash();
        // Ensure checkout button visibility is set immediately after hash initialization
        updateCheckoutButtonVisibility();
    }, 100);

    // Check for selected cards when page loads
    setTimeout(function () {
        // Check cart and highlight selected cards
        checkCartAndHighlight();

        // Update button states after cart is checked
        setTimeout(updateButtonStates, 300);
    }, 500);

    // Handle browser back/forward buttons
    $(window).on('popstate', function (e) {
        console.log('Browser navigation detected');
        setTimeout(initializeFromHash, 100);
    });

    // Forward navigation using event delegation
    $(document).on('click', '.next-btn, .mobile-next-btn', function () {
        console.log('Button clicked - class:', $(this).attr('class'));
        console.log('Button has disabled class:', $(this).hasClass('disabled'));

        if ($(this).hasClass('disabled')) {
            return; // Don't proceed if button is disabled
        }

        if (currentScreen === totalScreens) {
            // On the final slide, redirect instead of animating
            window.location.href = finalSlideRedirectUrl;
            return;
        }

        if (currentScreen < totalScreens) {
            const currentEl = $(`#screen${currentScreen}`);
            const nextEl = $(`#screen${currentScreen + 1}`);

            // Scroll to top first
            scrollToTop();

            // Animate current screen out
            gsap.to(currentEl, {
                duration: 0.5,
                x: '-100%',
                opacity: 0,
                ease: 'power2.inOut'
            });

            // Animate next screen in
            gsap.fromTo(nextEl,
                {
                    x: '100%',
                    opacity: 0
                },
                {
                    duration: 0.5,
                    x: '0%',
                    opacity: 1,
                    ease: 'power2.inOut',
                    onComplete: function () {
                        // Adjust container height after animation completes
                        adjustContainerHeight();
                    }
                }
            );

            currentScreen++;
            updateButtonStates();
            updateProgressBar(currentScreen);
            updateCheckoutButtonVisibility();

            // Update URL hash
            updateHash(currentScreen);
        }
    });

    // Backward navigation using event delegation
    $(document).on('click', '.back-btn, .mobile-back-btn', function () {
        console.log('Back button clicked - class:', $(this).attr('class'));
        console.log('Back button has disabled class:', $(this).hasClass('disabled'));

        if ($(this).hasClass('disabled')) {
            return; // Don't proceed if button is disabled
        }

        if (currentScreen > 1) {
            const currentEl = $(`#screen${currentScreen}`);
            const prevEl = $(`#screen${currentScreen - 1}`);

            // Scroll to top first
            scrollToTop();

            // Animate current screen out
            gsap.to(currentEl, {
                duration: 0.5,
                x: '100%',
                opacity: 0,
                ease: 'power2.inOut'
            });

            // Animate previous screen in
            gsap.fromTo(prevEl,
                {
                    x: '-100%',
                    opacity: 0
                },
                {
                    duration: 0.5,
                    x: '0%',
                    opacity: 1,
                    ease: 'power2.inOut',
                    onComplete: function () {
                        // Adjust container height after animation completes
                        adjustContainerHeight();
                    }
                }
            );

            currentScreen--;
            updateButtonStates();
            updateProgressBar(currentScreen);
            updateCheckoutButtonVisibility();

            // Update URL hash
            updateHash(currentScreen);
        }
    });

    // Update hash function
    function updateHash(screenNumber) {
        const newHash = `#screen${screenNumber}`;
        if (window.location.hash !== newHash) {
            history.replaceState(null, '', newHash);
        }
    }

    // ---- Update SVG Progress Bar
    function updateProgressBar(currentStep) {
        console.log('Updating progress bar for step:', currentStep);

        // Get the SVG element
        const $progressBar = $('#progress-bar');
        if (!$progressBar.length) {
            console.log('Progress bar SVG not found');
            return;
        }

        // Reset all steps first
        $progressBar.find('.step-group').each(function () {
            const $step = $(this);
            const stepNumber = parseInt($step.attr('data-step'));
            const $circle = $step.find('circle');
            const $text = $step.find('text');

            if (stepNumber < currentStep) {
                // Previous steps - completed and clickable
                $step.removeClass('current-step disabled-step').addClass('clickable-step');
                $circle.attr('fill', '#81C784').attr('stroke', '#2E7D32');
                $text.attr('fill', 'white');
            } else if (stepNumber === currentStep) {
                // Current step - active
                $step.removeClass('clickable-step disabled-step').addClass('current-step');
                $circle.attr('fill', '#4CAF50').attr('stroke', '#2E7D32');
                $text.attr('fill', 'white');
            } else {
                // Future steps - disabled
                $step.removeClass('current-step clickable-step').addClass('disabled-step');
                $circle.attr('fill', '#DDDDDD').attr('stroke', '#BBBBBB');
                $text.attr('fill', '#666666');
            }
        });

        // Update connecting lines
        $progressBar.find('line').each(function () {
            const $line = $(this);
            const x1 = parseInt($line.attr('x1'));
            const x2 = parseInt($line.attr('x2'));

            // Determine which step this line connects based on x coordinates
            let lineStep = 0;
            if (x1 === 90 && x2 === 170) lineStep = 1; // Line 1-2
            else if (x1 === 210 && x2 === 290) lineStep = 2; // Line 2-3
            else if (x1 === 330 && x2 === 410) lineStep = 3; // Line 3-4

            if (lineStep < currentStep) {
                // Line before current step - green
                $line.attr('stroke', '#4CAF50');
            } else {
                // Line at or after current step - grey
                $line.attr('stroke', '#DDDDDD');
            }
        });
    }

    // Add click handlers for progress bar navigation
    function initializeProgressBarClicks() {
        $(document).on('click', '.step-group.clickable-step', function () {
            const targetStep = parseInt($(this).attr('data-step'));
            console.log('Progress bar clicked, navigating to step:', targetStep);

            if (targetStep < currentScreen) {
                // Allow navigation to previous completed steps
                navigateToScreen(targetStep);
            }
        });
    }

    // Function to navigate directly to a specific screen
    function navigateToScreen(targetScreen) {
        if (targetScreen === currentScreen) return;

        const currentEl = $(`#screen${currentScreen}`);
        const targetEl = $(`#screen${targetScreen}`);

        // Scroll to top first
        scrollToTop();

        // Determine animation direction
        const direction = targetScreen > currentScreen ? 1 : -1;

        // Animate current screen out
        gsap.to(currentEl, {
            duration: 0.5,
            x: direction > 0 ? '-100%' : '100%',
            opacity: 0,
            ease: 'power2.inOut'
        });

        // Animate target screen in
        gsap.fromTo(targetEl,
            {
                x: direction > 0 ? '100%' : '-100%',
                opacity: 0
            },
            {
                duration: 0.5,
                x: '0%',
                opacity: 1,
                ease: 'power2.inOut',
                onComplete: function () {
                    adjustContainerHeight();
                }
            }
        );

        currentScreen = targetScreen;
        updateButtonStates();
        updateProgressBar(currentScreen);
        updateCheckoutButtonVisibility();
        updateHash(currentScreen);
    }

    // Function to update button states based on current screen and selections
    function updateButtonStates() {
        if (isScrolling) {
            // Don't update button states while scrolling is active
            return;
        }
        console.log('=== UPDATE BUTTON STATES ===');
        console.log('Current screen:', currentScreen);

        // Handle back button visibility
        if (currentScreen === 1) {
            $('.back-btn, .mobile-back-btn').addClass('disabled').css('visibility', 'hidden');
        } else {
            $('.back-btn, .mobile-back-btn').removeClass('disabled').css('visibility', 'visible');
        }

        // Handle next button state based on category selection
        const $nextBtn = $('.next-btn, .mobile-next-btn');

        switch (currentScreen) {
            case 1: // Installation screen
                const installationSelected = $('.installation-row-selected').length;
                const preferredChecked = $('.preferred-date-radio:checked').length;
                const secondaryChecked = $('.secondary-date-radio:checked').length;

                console.log('Installation selected:', installationSelected);
                console.log('Preferred checked:', preferredChecked);
                console.log('Secondary checked:', secondaryChecked);

                if (installationSelected || (preferredChecked && secondaryChecked)) {
                    console.log('Installation requirements met - enabling button');
                    $nextBtn.removeClass('disabled');
                } else {
                    console.log('Installation requirements NOT met - disabling button');
                    $nextBtn.addClass('disabled');
                }
                break;

            case 2: // Modems screen
                const modemSelected = $('.modem-row-selected').length;
                const ownModemValid = validateOwnModemInput();
                const hasPendingOwn = $('.own-modem-pending').length > 0;

                console.log('Modem selected count:', modemSelected);
                console.log('Own modem valid:', ownModemValid);
                console.log('Has pending own modem:', hasPendingOwn);

                // Valid if any modem is selected AND no own modem is in pending state
                if ((modemSelected || ownModemValid) && !hasPendingOwn) {
                    console.log('Modem requirements met - enabling button');
                    $nextBtn.removeClass('disabled');
                } else {
                    console.log('Modem requirements not met - disabling button');
                    $nextBtn.addClass('disabled');
                }
                break;

            case 3: // TV screen
                const tvSelected = $('.tv-row-selected').length;
                console.log('TV selected count:', tvSelected);

                if (tvSelected) {
                    console.log('TV requirements met - enabling button');
                    $nextBtn.removeClass('disabled');
                } else {
                    console.log('TV requirements not met - disabling button');
                    $nextBtn.addClass('disabled');
                }
                break;

            case 4: // Phone screen
                const phoneSelected = $('.phone-row-selected').length;
                console.log('Phone selected count:', phoneSelected);

                if (phoneSelected) {
                    console.log('Phone requirements met - enabling button');
                    $nextBtn.removeClass('disabled');
                } else {
                    console.log('Phone requirements not met - disabling button');
                    $nextBtn.addClass('disabled');
                }
                break;

            default:
                console.log('Unknown screen - disabling button');
                $nextBtn.addClass('disabled');
                break;
        }

        updateCheckoutButtonState();
        updateProgressBar(currentScreen);
        console.log('=== END UPDATE BUTTON STATES ===');
    }

    // Function to validate own modem input
    function validateOwnModemInput() {
        let isValid = false;
        $('.own-modem-input').each(function () {
            const $input = $(this);
            const $row = $input.closest('[class*="modem-"]');

            // Only consider valid if the card is fully selected (green) AND has valid input
            // Don't count pending state (red) as valid
            if ($row.hasClass('modem-row-selected') &&
                !$row.hasClass('own-modem-pending') &&
                $input.val().trim().length >= 5) {
                isValid = true;
                return false; // Break out of each loop
            }
        });
        return isValid;
    }

   

    // Function to show/hide the Skip to Checkout button based on whether user came from checkout
    function updateCheckoutButtonVisibility() {
        const $checkoutBtn = $('.checkout-btn');

        if (cameFromCheckout) {
            console.log('User came from checkout - showing Skip to Checkout button');
            $checkoutBtn.show();
        } else {
            console.log('User did not come from checkout - hiding Skip to Checkout button');
            $checkoutBtn.hide();
        }
    }

    // Function to validate all product selection requirements
    function validateAllSelections() {
        console.log('=== VALIDATING ALL SELECTIONS ===');

        // Check Installation screen (screen 1)
        const installationSelected = $('.installation-row-selected').length;
        const preferredChecked = $('.preferred-date-radio:checked').length;
        const secondaryChecked = $('.secondary-date-radio:checked').length;
        const installationValid = installationSelected || (preferredChecked && secondaryChecked);

        console.log('Installation validation:', {
            installationSelected,
            preferredChecked,
            secondaryChecked,
            valid: installationValid
        });

        if (!installationValid) {
            console.log('❌ Installation requirements not met');
            return false;
        }

        // Check Modems screen (screen 2) 
        const modemSelected = $('.modem-row-selected').length;
        const ownModemValid = validateOwnModemInput();
        const modemValid = modemSelected || ownModemValid;

        console.log('Modem validation:', {
            modemSelected,
            ownModemValid,
            valid: modemValid
        });

        if (!modemValid) {
            console.log('❌ Modem requirements not met');
            return false;
        }

        // Check TV screen (screen 3)
        const tvSelected = $('.tv-row-selected').length;

        console.log('TV validation:', {
            tvSelected,
            valid: tvSelected > 0
        });

        if (!tvSelected) {
            console.log('❌ TV requirements not met');
            return false;
        }

        // Check Phone screen (screen 4)
        const phoneSelected = $('.phone-row-selected').length;

        console.log('Phone validation:', {
            phoneSelected,
            valid: phoneSelected > 0
        });

        if (!phoneSelected) {
            console.log('❌ Phone requirements not met');
            return false;
        }

        console.log('✅ All selection requirements met');
        return true;
    }

    // Also update the checkout button state based on validation
    function updateCheckoutButtonState() {
        const $checkoutBtn = $('.checkout-btn');

        if (cameFromCheckout && $checkoutBtn.is(':visible')) {
            // Only check validation if the button is visible
            const allValid = validateAllSelections();

            if (allValid) {
                $checkoutBtn.removeClass('disabled');
                console.log('Checkout button enabled - all selections valid');
            } else {
                $checkoutBtn.addClass('disabled');
                console.log('Checkout button disabled - missing selections');
            }
        }
    }

    // Updated Skip to Checkout button click handler with validation
    $('.checkout-btn').click(function () {
        console.log('Skip to Checkout button clicked');

        // Validate all selections before proceeding
        if (!validateAllSelections()) {
            // Show user-friendly error message
            alert('Please make sure you have selected an option for each category (Installation, Modem, TV, and Phone) before proceeding to checkout.');
            return; // Don't proceed if validation fails
        }

        // If validation passes, proceed to checkout
        console.log('All validations passed - proceeding to checkout');
        window.location.href = finalSlideRedirectUrl;
    });

    // Make sure to call updateCheckoutButtonState when selections change
    $(document).on('click', '.modem-0, .modem-1, .modem-2, .modem-3, .modem-4, .installation-row, .tv-0, .tv-1, .tv-2, .phone-0, .phone-1, .phone-2', function () {
        setTimeout(function () {
            updateButtonStates(); // This now includes checkout button validation
            updateCheckoutButtonState(); // Extra call for safety
        }, 300);
    });

    // Also update on radio button changes
    $(document).on('change', '.preferred-date-radio, .secondary-date-radio', function () {
        console.log('Installation date radio changed');
        setTimeout(function () {
            updateButtonStates();
            updateCheckoutButtonState();
        }, 500);
    });

    // Update on own modem input changes
    $(document).on('input', '.own-modem-input', function () {
        setTimeout(function () {
            updateButtonStates();
            updateCheckoutButtonState();
        }, 300);
    });

    // Alternative: Use MutationObserver for class changes
    const installationRows = document.querySelectorAll('.installation-row');
    installationRows.forEach(row => {
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    console.log('Installation row class changed');
                    if (currentScreen === 1) { // Updated to screen 1
                        updateButtonStates();
                    }
                }
            });
        });
        observer.observe(row, { attributes: true });
    });

    // Function to scroll to top of the page smoothly
    function scrollToTop() {
        $('html, body').animate({
            scrollTop: $('.checkout-container').offset().top - 20
        }, 300);
    }

    // Function to adjust container height based on active screen

    function adjustContainerHeight() {
        const activeScreen = $(`#screen${currentScreen}`);
        let totalHeight = 0;
        let cards;

        // Get all cards based on the current screen
        if (currentScreen === 1) {
            // Screen 1: Installation cards
            cards = activeScreen.find('.installation-row').filter(':visible');
        } else if (currentScreen === 2) {
            // Screen 2: Modem cards
            cards = activeScreen.find('.modem-0, .modem-1, .modem-2, .modem-3, .modem-4').filter(':visible');
        } else if (currentScreen === 3) {
            // Screen 3: TV cards
            cards = activeScreen.find('.tv-0, .tv-1, .tv-2').filter(':visible');
        } else if (currentScreen === 4) {
            // Screen 4: Phone cards
            cards = activeScreen.find('.phone-0, .phone-1, .phone-2').filter(':visible');
        }

        if (cards && cards.length) {
            // Calculate total height of all cards including their margins
            cards.each(function () {
                totalHeight += $(this).outerHeight(true); // includes margins
            });

            // Add buffer for navigation buttons and spacing
            const buffer = 500;
            const finalHeight = totalHeight + buffer;

            $('.checkout-container').animate({
                height: finalHeight
            }, 300);
            console.log('Adjusting container height to:', finalHeight, '(total cards height:', totalHeight, '+ buffer:', buffer, ') for screen', currentScreen);
        } else {
            // Fallback to current method if no cards found
            const screenHeight = activeScreen.outerHeight(true);
            $('.checkout-container').animate({
                height: screenHeight
            }, 300);
            console.log('Fallback: Adjusting container height to:', screenHeight);
        }
    }

    // Add CSS for disabled buttons
    $('<style>')
        .prop('type', 'text/css')
        .html(`
             .next-btn.disabled, .back-btn.disabled, .mobile-next-btn.disabled, .mobile-back-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        /* Force buttons to maintain disabled state via CSS specificity on mobile */
        @media (max-width: 768px) {
            .next-btn.disabled, .mobile-next-btn.disabled {
                opacity: 0.5 !important;
                pointer-events: none !important;
                cursor: not-allowed !important;
            }
            .back-btn.disabled, .mobile-back-btn.disabled {
                opacity: 0.5 !important;
                pointer-events: none !important;
                cursor: not-allowed !important;
                visibility: hidden !important;
            }
            
            /* Completely disable momentum scrolling on iOS */
            body, html, .checkout-container, .et_pb_section, * {
             -webkit-overflow-scrolling: auto !important;
             overflow-scrolling: auto !important;
            }

            /* Prevent scroll events during momentum */
            @supports (-webkit-overflow-scrolling: touch) {
    .checkout-container {
        -webkit-overflow-scrolling: auto !important;
    }
}
        `)
        .appendTo('head');
});