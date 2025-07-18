jQuery(document).ready(function ($) {
    // Track current screen
    let currentScreen = 1;
    const totalScreens = 4;
    const finalSlideRedirectUrl = 'https://diallog.magnaprototype.com/checkout'; // Change this to your desired URL

    // Initialize container height based on first screen
    adjustContainerHeight();
    updateButtonStates();

    // Check for selected cards when page loads
    setTimeout(function () {
        // Check cart and highlight selected cards
        checkCartAndHighlight();

        // Update button states after cart is checked
        setTimeout(updateButtonStates, 300);
    }, 500);

    // Forward navigation
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
        }
    });

    // Backward navigation
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
        }
    });

    // Function to update button states based on current screen and selections
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
                    console.log('Installation requirements NOT met - disabling button');
                    $nextBtn.addClass('disabled');
                }
                break;

            case 2: // Modem screen (reordered from case 1)
                const modemSelected = $('.modem-row-selected').length;
                
                // Special check for "own modem" selection
                let ownModemValid = true;
                const $selectedOwnModem = $('.modem-4.modem-row-selected');
                if ($selectedOwnModem.length) {
                    const $input = $selectedOwnModem.find('.own-modem-input');
                    const inputValue = $input.val().trim();
                    ownModemValid = inputValue.length >= 5 && inputValue.length <= 100;
                }
                
                console.log('Modem selected count:', modemSelected);
                console.log('Own modem valid:', ownModemValid);
                
                if (modemSelected && ownModemValid) {
                    $nextBtn.removeClass('disabled');
                } else {
                    $nextBtn.addClass('disabled');
                }
                break;

            case 3: // TV screen
                const tvSelected = $('.tv-row-selected').length;
                console.log('TV selected count:', tvSelected);
                if (tvSelected) {
                    $nextBtn.removeClass('disabled');
                } else {
                    $nextBtn.addClass('disabled');
                }
                break;

            case 4: // Phone screen
                const phoneSelected = $('.phone-row-selected').length;
                console.log('Phone selected count:', phoneSelected);
                if (phoneSelected) {
                    $nextBtn.removeClass('disabled');
                } else {
                    $nextBtn.addClass('disabled');
                }
                break;
        }

        console.log('Next button disabled?', $nextBtn.hasClass('disabled'));
        console.log('=== END UPDATE BUTTON STATES ===');
    }

    // Check for card selections and update button states
    $(document).on('click', '.modem-0, .modem-1, .modem-2, .modem-3, .modem-4, .installation-row, .tv-0, .tv-1, .tv-2, .phone-0, .phone-1, .phone-2', function () {
        setTimeout(updateButtonStates, 300); // Small delay to ensure classes are updated
    });

    // Listen for changes to radio buttons for installation
    $(document).on('change', '.preferred-date-radio, .secondary-date-radio', function () {
        console.log('Installation date radio changed');
        setTimeout(updateButtonStates, 500);
    });

    // Listen for input changes on own modem field
    $(document).on('input', '.own-modem-input', function() {
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