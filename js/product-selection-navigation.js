jQuery(document).ready(function ($) {
    // Track current screen and whether user came from checkout
    let currentScreen = 1;
    const totalScreens = 4;
    const finalSlideRedirectUrl = 'https://diallog.magnaprototype.com/checkout';
    let cameFromCheckout = false;

    // Initialize container height based on first screen
    adjustContainerHeight();
    updateButtonStates();
    updateCheckoutButtonVisibility();

    // Handle hash-based navigation on page load
    function initializeFromHash() {
        const hash = window.location.hash;
        if (hash) {
            // Extract screen number from hash (e.g., #screen2 -> 2)
            const match = hash.match(/^#screen(\d+)$/);
            if (match) {
                const targetScreen = parseInt(match[1]);
                if (targetScreen >= 1 && targetScreen <= totalScreens && targetScreen !== 1) {
                    console.log('Navigating to screen from hash:', targetScreen);
                    // User came from checkout if they have a hash URL
                    cameFromCheckout = true;
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

    // Forward navigation (keeping original logic)
    $('.next-btn').click(function () {
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
            updateCheckoutButtonVisibility();

            // Update URL hash
            updateHash(currentScreen);
        }
    });

    // Backward navigation (keeping original logic)
    $('.back-btn').click(function () {
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

    // Function to update button states based on current screen and selections (keeping original logic)
    function updateButtonStates() {
        console.log('=== UPDATE BUTTON STATES ===');
        console.log('Current screen:', currentScreen);

        // Handle back button visibility
        if (currentScreen === 1) {
            $('.back-btn').addClass('disabled').css('visibility', 'hidden');
        } else {
            $('.back-btn').removeClass('disabled').css('visibility', 'visible');
        }

        // Handle next button state based on category selection
        const $nextBtn = $('.next-btn');

        switch (currentScreen) {
            case 1: // Installation screen (reordered from case 2)
                const installationSelected = $('.installation-row-selected').length;
                const preferredChecked = $('.preferred-date-radio:checked').length;
                const secondaryChecked = $('.secondary-date-radio:checked').length;

                console.log('Installation row selected count:', installationSelected);
                console.log('Preferred date checked:', preferredChecked);
                console.log('Secondary date checked:', secondaryChecked);
                console.log('All installation rows:', $('.installation-row').length);

                if (installationSelected || (preferredChecked && secondaryChecked)) {
                    console.log('Installation requirements met - enabling button');
                    $nextBtn.removeClass('disabled');
                } else {
                    console.log('Installation requirements not met - disabling button');
                    $nextBtn.addClass('disabled');
                }
                break;

            case 2: // Modems screen
                const modemSelected = $('.modem-row-selected').length;
                const ownModemValid = validateOwnModemInput();

                console.log('Modem row selected count:', modemSelected);
                console.log('Own modem validation passed:', ownModemValid);

                if (modemSelected || ownModemValid) {
                    console.log('Modem requirements met - enabling button');
                    $nextBtn.removeClass('disabled');
                } else {
                    console.log('Modem requirements not met - disabling button');
                    $nextBtn.addClass('disabled');
                }
                break;

            case 3: // TV screen
                const tvSelected = $('.tv-row-selected').length;
                console.log('TV row selected count:', tvSelected);

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
                console.log('Phone row selected count:', phoneSelected);

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

        console.log('Next button disabled state:', $nextBtn.hasClass('disabled'));
        console.log('=== END UPDATE BUTTON STATES ===');
    }

    // Function to validate own modem input
    function validateOwnModemInput() {
        let isValid = false;
        $('.own-modem-input').each(function () {
            const $input = $(this);
            const $row = $input.closest('[class*="modem-"]');

            if ($row.hasClass('modem-row-selected') && $input.val().trim().length >= 5) {
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

    // Handle Skip to Checkout button click
    $('.checkout-btn').click(function () {
        console.log('Skip to Checkout button clicked');
        window.location.href = finalSlideRedirectUrl;
    });

    // Check for card selections and update button states (keeping original logic)
    $(document).on('click', '.modem-0, .modem-1, .modem-2, .modem-3, .modem-4, .installation-row, .tv-0, .tv-1, .tv-2, .phone-0, .phone-1, .phone-2', function () {
        setTimeout(updateButtonStates, 300); // Small delay to ensure classes are updated
    });

    // Listen for changes to radio buttons for installation
    $(document).on('change', '.preferred-date-radio, .secondary-date-radio', function () {
        console.log('Installation date radio changed');
        setTimeout(updateButtonStates, 500);
    });

    // Listen for input changes on own modem field
    $(document).on('input', '.own-modem-input', function () {
        const $input = $(this);
        const $row = $input.closest('[class*="modem-"]');

        // Clear validation errors if input is valid
        if ($input.val().trim().length >= 5) {
            $input.removeClass('error');
            $row.find('.own-modem-error').hide();
        }

        // Update button states when input changes
        updateButtonStates();
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
        const screenHeight = activeScreen.outerHeight(true);
        $('.checkout-container').animate({
            height: screenHeight
        }, 300);
        console.log('Adjusting container height to:', screenHeight);
    }

    // Also adjust height when window resizes
    $(window).on('resize', function () {
        adjustContainerHeight();
    });

    // Force recalculation when images load (if you have images in your screens)
    $('img').on('load', function () {
        adjustContainerHeight();
    });

    // Add CSS for disabled buttons
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .next-btn.disabled, .back-btn.disabled {
                opacity: 0.5;
                cursor: not-allowed;
                pointer-events: none;
            }
        `)
        .appendTo('head');
});