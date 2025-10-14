/**
 * Terms & Conditions Confirmation Handler - PERFORMANCE OPTIMIZED
 * File: js/confirm-terms.js
 * 
 * This script handles the terms and conditions confirmation flow and integrates
 * with the Moneris payment button to ensure users confirm T&C before payment.
 */

jQuery(document).ready(function ($) {

    // State variables to track validation status
    let termsConfirmed = false;
    let monthlyBillingValidated = false;
    let lastButtonState = null; // Track last button state to prevent unnecessary updates

    // Initialize the enhanced terms and conditions functionality
    initializeTermsAndConditions();
    initializePopupFunctionality();
    setupMonthlyBillingEventListeners();

    /**
     * Initialize Terms and Conditions confirmation system
     */
    function initializeTermsAndConditions() {
        console.log('Initializing Terms & Conditions confirmation system...');

        // Check if terms were previously confirmed in this session
        restoreTermsState();

        // Monitor for dynamically loaded Moneris buttons (ONE TIME SETUP)
        setupMonerisButtonMonitoring();

        // Initial check on page load (ONE TIME)
        setTimeout(function () {
            checkMonthlyBillingValidation();
            updateButtonState();
        }, 1000);

        console.log('Terms & Conditions system initialized');
    }

    /**
     * Initialize popup functionality
     */
    function initializePopupFunctionality() {
        console.log('Initializing popup functionality...');

        // Wrap popup content
        $('.dl-popup-content').each(function () {
            $(this).wrap('<div class="dl-popup-wrapper"><div class="dl-popup-inside">');
        });

        // Handle popup triggers
        $('.dl-popup-trigger, .dl-menu-popup > a').off().click(function (e) {
            e.preventDefault();
            var SectionID = $(this).attr('href');
            $(SectionID).closest('.dl-popup-wrapper').addClass('popup-is-visible');
            $(SectionID).closest('.et_builder_inner_content').addClass('popup-is-visible');
            $('body').addClass('dl-noscroll');
        });

        // Handle standard popup close
        $('.dl-popup-close').click(function (e) {
            e.preventDefault();
            closePopup($(this));
        });

        console.log('Popup functionality initialized');
    }

    /**
     * Setup the confirm terms button handler
     */
    $(document).on('click', '.confirm-terms', function (e) {
        e.preventDefault();
        console.log('Terms confirmation button clicked');

        closePopup($(this));
        confirmTermsAndConditions();

        e.stopPropagation();
        return false;
    });

    /**
     * Check monthly billing validation state
     */
    function checkMonthlyBillingValidation() {
        const ccValidated = $('.validate-card-btn').hasClass('confirmed');
        const bankValidated = $('.validate-bank-btn').hasClass('confirmed');
        const payAfterValidated = $('.confirm-payafter-btn').hasClass('confirmed');

        monthlyBillingValidated = ccValidated || bankValidated || payAfterValidated;

        console.log('Monthly billing validation check:', {
            cc: ccValidated,
            bank: bankValidated,
            payAfter: payAfterValidated,
            overall: monthlyBillingValidated
        });

        return monthlyBillingValidated;
    }

    /**
     * PERFORMANCE OPTIMIZED: Update button state only when necessary
     */
    /**
 * PERFORMANCE OPTIMIZED: Update button state only when necessary
 * UPDATED: Now targets all Moneris buttons by class instead of ID
 */
    function updateButtonState() {
        const termsOk = termsConfirmed;
        const monthlyBillingOk = checkMonthlyBillingValidation();
        const bothValid = termsOk && monthlyBillingOk;

        // Create state signature to avoid unnecessary DOM updates
        const currentState = `${termsOk}-${monthlyBillingOk}-${bothValid}`;

        // PERFORMANCE FIX: Only update DOM if state actually changed
        if (lastButtonState === currentState) {
            console.log('Button state unchanged, skipping DOM update');
            return;
        }

        console.log('Button state changed from', lastButtonState, 'to', currentState);
        lastButtonState = currentState;

        // UPDATED: Find ALL Moneris buttons by class (supports multiple buttons on page)
        const $monerisButtons = $('.moneris-payment-button');
        if ($monerisButtons.length === 0) {
            console.log('Moneris button not found');
            return;
        }

        console.log('Updating ' + $monerisButtons.length + ' Moneris button(s)');

        // Update each button individually
        $monerisButtons.each(function () {
            const $btn = $(this);
            if (bothValid) {
                enableMonerisButton($btn);
            } else {
                disableMonerisButton($btn, termsOk, monthlyBillingOk);
            }
        });
    }

    /**
     * Enable the Moneris payment button (OPTIMIZED - only updates when needed)
     */
    function enableMonerisButton($monerisBtn) {
        console.log('Enabling Moneris payment button - both validations passed');

        $monerisBtn
            .prop('disabled', false)
            .removeClass('terms-not-confirmed')
            .css({
                'opacity': '1',
                'cursor': 'pointer',
                'pointer-events': 'auto',
                'transition': 'opacity 0.3s ease'
            });

        // Check if validation messages should be shown
        const $container = $monerisBtn.closest('.moneris-complete-payment-container');
        const showValidation = $container.attr('data-show-validation') !== 'false';

        // PERFORMANCE FIX: Remove messages without recreating
        $container.find('.validation-requirement-message').remove();

        // Show brief success message only if validation is enabled
        if (showValidation && !$container.find('.payment-enabled-message').length) {
            $container.append(
                '<div class="payment-enabled-message" style="color: #28a745; font-size: 14px; margin-top: 10px; text-align: center; opacity: 0;">' +
                '✓ Payment button enabled' +
                '</div>'
            );
            $('.payment-enabled-message').animate({ opacity: 1 }, 500).delay(3000).animate({ opacity: 0 }, 500, function () {
                $(this).remove(); // Clean up after animation
            });
        }
    }

    /**
   * Disable the Moneris payment button (OPTIMIZED - only updates when needed)
   * UPDATED: Added different styling for success vs error messages
   */
    function disableMonerisButton($monerisBtn, termsOk, monthlyBillingOk) {
        console.log('Disabling Moneris payment button - requirements not met');

        $monerisBtn
            .prop('disabled', true)
            .addClass('terms-not-confirmed')
            .css({
                'opacity': '0.6',
                'cursor': 'not-allowed',
                'pointer-events': 'none'
            });

        const $container = $monerisBtn.closest('.moneris-complete-payment-container');

        // Check if validation messages should be shown
        const showValidation = $container.attr('data-show-validation') !== 'false';

        // Always remove any existing validation messages first
        $container.find('.validation-requirement-message').remove();

        // If validation messages are disabled, return early (but button styling already applied above)
        if (!showValidation) {
            return;
        }

        // PERFORMANCE FIX: Only update message if it doesn't exist or content changed
        let $existingMessage = $container.find('.validation-requirement-message');

        let messages = [];
        if (!monthlyBillingOk) {
            messages.push({
                text: '✗ Select and validate a monthly billing method (Credit Card, Bank Account, or Pay After)',
                type: 'error'
            });
        } else {
            messages.push({
                text: '✓ Monthly billing method validated',
                type: 'success'
            });
        }

        if (!termsOk) {
            messages.push({
                text: '✗ Confirm Terms & Conditions',
                type: 'error'
            });
        } else {
            messages.push({
                text: '✓ Terms & Conditions confirmed',
                type: 'success'
            });
        }

        // Create content signature for comparison (just the text)
        const newMessageContent = messages.map(msg => msg.text).join('');

        // Only update if message doesn't exist or content is different
        if ($existingMessage.length === 0 || $existingMessage.data('content') !== newMessageContent) {
            $existingMessage.remove(); // Remove old message

            // Build the message HTML with individual styling for each message
            let messagesHtml = messages.map(msg => {
                const color = msg.type === 'success' ? '#28a745' : '#e74c3c';
                return `<div style="color: ${color}; font-size: 14px; margin-top: 8px;">${msg.text}</div>`;
            }).join('');

            // Append the new message with content signature
            $container.append(
                `<div class="validation-requirement-message" data-content="${newMessageContent}" style="text-align: center;">${messagesHtml}</div>`
            );

            console.log('Updated validation message:', newMessageContent);
        }
    }

    /**
     * Event-driven monthly billing monitoring (NO POLLING)
     */
    function setupMonthlyBillingEventListeners() {
        console.log('Setting up monthly billing event listeners...');

        // Listen for clicks on validation buttons
        $(document).on('click', '.validate-card-btn, .validate-bank-btn, .confirm-payafter-btn', function () {
            const $button = $(this);
            console.log('Monthly billing button clicked:', $button.attr('class'));

            // Check validation state after a short delay
            setTimeout(function () {
                const previousState = monthlyBillingValidated;
                const currentState = checkMonthlyBillingValidation();

                if (previousState !== currentState) {
                    console.log('Monthly billing validation state changed:', currentState);
                    updateButtonState(); // This now prevents unnecessary DOM updates
                }
            }, 500);
        });

        // Monitor class changes on validation buttons
        const observer = new MutationObserver(function (mutations) {
            let shouldUpdate = false;

            mutations.forEach(function (mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const $target = $(mutation.target);
                    if ($target.hasClass('validate-card-btn') ||
                        $target.hasClass('validate-bank-btn') ||
                        $target.hasClass('confirm-payafter-btn')) {
                        shouldUpdate = true;
                    }
                }
            });

            if (shouldUpdate) {
                console.log('Monthly billing button class changed');
                setTimeout(function () {
                    const previousState = monthlyBillingValidated;
                    const currentState = checkMonthlyBillingValidation();

                    if (previousState !== currentState) {
                        console.log('Monthly billing validation state changed via mutation:', currentState);
                        updateButtonState();
                    }
                }, 100);
            }
        });

        observer.observe(document.body, {
            attributes: true,
            attributeFilter: ['class'],
            subtree: true
        });

        console.log('Monthly billing event listeners configured');
    }

    /**
     * PERFORMANCE OPTIMIZED: Setup Moneris button monitoring (NO CONTINUOUS POLLING)
     */
    function setupMonerisButtonMonitoring() {
        // ONE-TIME mutation observer for new Moneris buttons
        const buttonObserver = new MutationObserver(function (mutations) {
            let foundNewButton = false;

            mutations.forEach(function (mutation) {
                if (mutation.type === 'childList') {
                    const $newMonerisBtn = $(mutation.target).find('.moneris-payment-button');
                    if ($newMonerisBtn.length > 0) {
                        foundNewButton = true;
                    }
                }
            });

            if (foundNewButton) {
                console.log('New Moneris button detected');
                setTimeout(function () {
                    updateButtonState(); // Check state once for new button
                }, 100);
            }
        });

        buttonObserver.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     * Enhanced popup close function
     */
    function closePopup($trigger) {
        $('.popup-is-visible').removeClass('popup-is-visible');
        $('body').removeClass('dl-noscroll');

        var PopupVideoIframe = $trigger.closest('.dl-popup-content').find('.et_pb_video_box iframe');
        var PopupVideoSrc = PopupVideoIframe.attr("src");
        if (PopupVideoSrc) {
            PopupVideoIframe.attr("src", PopupVideoSrc);
        }

        var PopupVideoHTML = $trigger.closest('.dl-popup-content').find('.et_pb_video_box video');
        if (PopupVideoHTML.length) {
            PopupVideoHTML.trigger('pause');
        }
    }

    /**
     * Handle terms and conditions confirmation
     */
    function confirmTermsAndConditions() {
        console.log('Processing terms confirmation...');

        termsConfirmed = true;
        styleConfirmedTcButton();
        updateButtonState(); // Use optimized update function

        sessionStorage.setItem('termsConfirmed', 'true');
        $(document).trigger('termsConfirmed');

        console.log('Terms confirmed successfully');
    }

    /**
     * Style the tc-button to show it's been confirmed
     */
    function styleConfirmedTcButton() {
        const $tcButton = $('.tc-button');

        if ($tcButton.length > 0 && !$tcButton.hasClass('terms-confirmed')) {
            console.log('Styling tc-button as confirmed');

            $tcButton
                .addClass('terms-confirmed')
                .css({
                    'background-color': '#28a745',
                    'color': '#ffffff',
                    'border-color': '#28a745',
                    'transition': 'all 0.3s ease'
                });

            if (!$tcButton.find('.terms-checkmark').length) {
                $tcButton.prepend('<span class="terms-checkmark">✓ </span>');
            }

            const originalText = $tcButton.text().replace('✓ ', '');
            if (!originalText.includes('Confirmed')) {
                $tcButton.html('<span class="terms-checkmark">✓ </span>' + originalText + ' (Confirmed)');
            }

            $tcButton.fadeOut(200).fadeIn(400);
        }
    }

    /**
     * Restore terms confirmation state from session storage
     */
    function restoreTermsState() {
        const wasConfirmed = sessionStorage.getItem('termsConfirmed') === 'true';

        if (wasConfirmed) {
            console.log('Restoring previously confirmed terms state');
            termsConfirmed = true;

            setTimeout(function () {
                const $tcButton = $('.tc-button');
                if ($tcButton.length > 0) {
                    styleConfirmedTcButton();
                }
                updateButtonState();
            }, 500);
        }
    }

    /**
     * Handle clicks on disabled Moneris button
     */
    $(document).on('click', '.moneris-payment-button', function (e) {
        const $btn = $(this);

        if ($btn.prop('disabled')) {
            e.preventDefault();
            e.stopPropagation();

            console.log('Disabled Moneris button clicked');

            // Show what's missing without recreating DOM
            updateButtonState();

            // Open Terms popup if terms not confirmed
            if (!termsConfirmed) {
                const $tcButton = $('.tc-button');
                if ($tcButton.length > 0) {
                    const popupTarget = $tcButton.attr('href');
                    if (popupTarget) {
                        $(popupTarget).closest('.dl-popup-wrapper').addClass('popup-is-visible');
                        $(popupTarget).closest('.et_builder_inner_content').addClass('popup-is-visible');
                        $('body').addClass('dl-noscroll');
                    }
                }
            }

            return false;
        }
    });

    /**
     * Reset terms confirmation
     */
    function resetTermsConfirmation() {
        console.log('Resetting terms confirmation');

        termsConfirmed = false;
        lastButtonState = null; // Reset state tracking

        const $tcButton = $('.tc-button');
        $tcButton
            .removeClass('terms-confirmed')
            .css({
                'background-color': '',
                'color': '',
                'border-color': ''
            })
            .find('.terms-checkmark').remove();

        updateButtonState();
        sessionStorage.removeItem('termsConfirmed');
    }

    /**
     * Handle page navigation
     */
    $(window).on('beforeunload', function () {
        const currentUrl = window.location.href;
        sessionStorage.setItem('lastCheckoutUrl', currentUrl);

        if (termsConfirmed && currentUrl.includes('checkout')) {
            sessionStorage.setItem('termsConfirmed', 'true');
        }
    });

    const lastUrl = sessionStorage.getItem('lastCheckoutUrl');
    const currentUrl = window.location.href;
    if (lastUrl && lastUrl !== currentUrl && !currentUrl.includes('checkout')) {
        sessionStorage.removeItem('termsConfirmed');
    }

    /**
     * Public API for other scripts
     */
    window.TermsAndConditions = {
        isConfirmed: function () {
            return termsConfirmed;
        },
        reset: resetTermsConfirmation,
        confirm: function () {
            confirmTermsAndConditions();
        },
        getState: function () {
            return {
                confirmed: termsConfirmed,
                monthlyBillingValidated: monthlyBillingValidated,
                tcButtonExists: $('.tc-button').length > 0,
                monerisButtonExists: $('.moneris-payment-button').length > 0,
                monerisButtonEnabled: !$('.moneris-payment-button').prop('disabled')
            };
        }
    };

    // Debug function
    window.debugTermsAndConditions = function () {
        console.log('=== Terms & Conditions Debug Info ===');
        console.log('Terms Confirmed:', termsConfirmed);
        console.log('Monthly Billing Validated:', monthlyBillingValidated);
        console.log('Last Button State:', lastButtonState);
        console.log('Current State Signature:', `${termsConfirmed}-${monthlyBillingValidated}-${termsConfirmed && monthlyBillingValidated}`);
        console.log('===============================');

        return window.TermsAndConditions.getState();
    };

}); // End jQuery document ready