/**
 * Terms & Conditions Confirmation Handler - PERFORMANCE OPTIMIZED
 * File: js/confirm-terms.js
 * 
 * This script handles the terms and conditions confirmation flow and integrates
 * with the Moneris payment button to ensure users confirm T&C before payment.
 * 
 * FIXED: Now properly integrates with shipping address validation without removing it
 */

jQuery(document).ready(function ($) {

    // State variables to track validation status
    let termsConfirmed = false;
    let monthlyBillingValidated = false;
    let lastButtonState = null; // Track last button state to prevent unnecessary updates
    let termsTimestamp = null; // Store the timestamp of terms confirmation

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
     * FIXED: Now properly integrates with shipping validation
     */
    function updateButtonState() {
        const termsOk = termsConfirmed;
        const monthlyBillingOk = checkMonthlyBillingValidation();

        // FIX: Check shipping validation state
        const shippingOk = checkShippingValidation();

        // FIX: All three validations must pass
        const allValid = termsOk && monthlyBillingOk && shippingOk;

        // Create state signature to avoid unnecessary DOM updates
        const currentState = `${termsOk}-${monthlyBillingOk}-${shippingOk}-${allValid}`;

        // PERFORMANCE FIX: Only update DOM if state actually changed
        if (lastButtonState === currentState) {
            console.log('Button state unchanged, skipping DOM update');
            return;
        }

        console.log('Button state changed from', lastButtonState, 'to', currentState);
        lastButtonState = currentState;

        // Find ALL Moneris buttons by class (supports multiple buttons on page)
        const $monerisButtons = $('.moneris-payment-button');
        if ($monerisButtons.length === 0) {
            console.log('Moneris button not found');
            return;
        }

        console.log('Updating ' + $monerisButtons.length + ' Moneris button(s)');

        // Update each button individually
        $monerisButtons.each(function () {
            const $btn = $(this);
            if (allValid) {
                enableMonerisButton($btn);
            } else {
                disableMonerisButton($btn, termsOk, monthlyBillingOk, shippingOk);
            }
        });
    }

    /**
     * FIX: Check shipping validation state
     */
    function checkShippingValidation() {
        const $checkbox = $('#ship-to-different-checkbox');

        // If checkbox doesn't exist or is unchecked, validation passes
        if ($checkbox.length === 0 || !$checkbox.is(':checked')) {
            return true;
        }

        // If checkbox is checked, verify address is populated
        const $addressInput = $('#shipping-address-input');
        const address = $addressInput.val().trim();
        const isValid = address.length >= 10;

        console.log('Shipping validation check from confirm-terms:', isValid);
        return isValid;
    }

    /**
     * Enable the Moneris payment button (OPTIMIZED - only updates when needed)
     */
    function enableMonerisButton($monerisBtn) {
        console.log('Enabling Moneris payment button - all validations passed');

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
     * FIXED: Now includes shipping validation in messages
     */
    function disableMonerisButton($monerisBtn, termsOk, monthlyBillingOk, shippingOk) {
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

        // FIX: Find and preserve existing shipping validation message
        const $existingShippingValidation = $container.find('.shipping-address-validation');
        let shippingValidationHtml = '';
        if ($existingShippingValidation.length > 0) {
            shippingValidationHtml = $existingShippingValidation[0].outerHTML;
        }

        // Remove existing validation messages
        $container.find('.validation-requirement-message').remove();

        // If validation messages are disabled, return early (but button styling already applied above)
        if (!showValidation) {
            return;
        }

        // Build validation messages array
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

        // FIX: Check if shipping validation is required
        const $checkbox = $('#ship-to-different-checkbox');
        if ($checkbox.length > 0 && $checkbox.is(':checked')) {
            if (!shippingOk) {
                messages.push({
                    text: '✗ Shipping address must be populated',
                    type: 'error'
                });
            } else {
                messages.push({
                    text: '✓ Shipping address validated',
                    type: 'success'
                });
            }
        }

        // Create content signature for comparison
        const newMessageContent = messages.map(msg => msg.text).join('');

        // Build the message HTML
        let messagesHtml = messages.map(msg => {
            const color = msg.type === 'success' ? '#28a745' : '#e74c3c';
            return `<div style="color: ${color}; font-size: 14px; margin-top: 8px;">${msg.text}</div>`;
        }).join('');

        // Append the new message container
        $container.append(
            `<div class="validation-requirement-message" data-content="${newMessageContent}" style="text-align: center;">${messagesHtml}</div>`
        );

        // FIX: If there was a shipping validation element with special styling, preserve it
        if (shippingValidationHtml && $checkbox.is(':checked')) {
            const $validationMessage = $container.find('.validation-requirement-message');
            // Check if we need to add the shipping-address-validation class styling
            if ($validationMessage.length > 0 && !$validationMessage.find('.shipping-address-validation').length) {
                // Recreate the shipping validation item inside the container if shipping checkbox is checked
                const shippingValid = checkShippingValidation();
                const shippingValidationItem = `
                    <div class="validation-item shipping-address-validation ${shippingValid ? 'valid' : 'invalid'}">
                        <span class="validation-icon">${shippingValid ? '✓' : '✗'}</span>
                        <span class="validation-text">Shipping address must be populated</span>
                    </div>
                `;
                $validationMessage.append(shippingValidationItem);
            }
        }

        console.log('Updated validation message:', newMessageContent);
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
                    updateButtonState();
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

                        console.log('Validation button class changed');
                        shouldUpdate = true;
                    }
                }
            });

            if (shouldUpdate) {
                console.log('Monthly billing state detected, updating button');
                setTimeout(function () {
                    updateButtonState();
                }, 300);
            }
        });

        // Observe all validation buttons
        $('.validate-card-btn, .validate-bank-btn, .confirm-payafter-btn').each(function () {
            observer.observe(this, {
                attributes: true,
                attributeFilter: ['class']
            });
        });

        console.log('Monthly billing event listeners configured');
    }

    /**
     * Setup monitoring for Moneris button appearance
     */
    function setupMonerisButtonMonitoring() {
        console.log('Setting up Moneris button monitoring...');

        const checkForMonerisButton = function () {
            const $monerisButtons = $('.moneris-payment-button');
            if ($monerisButtons.length > 0) {
                console.log('Moneris button(s) detected, applying initial state');
                updateButtonState();
            }
        };

        // Check immediately
        checkForMonerisButton();

        // Check after a delay (for dynamically loaded content)
        setTimeout(checkForMonerisButton, 500);
        setTimeout(checkForMonerisButton, 1500);

        // Set up MutationObserver to watch for new Moneris buttons
        const bodyObserver = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(function (node) {
                        if (node.nodeType === 1) { // Element node
                            if ($(node).hasClass('moneris-payment-button') || $(node).find('.moneris-payment-button').length > 0) {
                                console.log('New Moneris button detected');
                                setTimeout(updateButtonState, 100);
                            }
                        }
                    });
                }
            });
        });

        bodyObserver.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     * Close popup helper
     */
    function closePopup($element) {
        $element.closest('.dl-popup-wrapper').removeClass('popup-is-visible');
        $element.closest('.et_builder_inner_content').removeClass('popup-is-visible');
        $('body').removeClass('dl-noscroll');
    }

    /**
     * Confirm terms and conditions
     */
    function confirmTermsAndConditions() {
        console.log('Terms & Conditions confirmed');

        termsConfirmed = true;
        termsTimestamp = new Date().toISOString();

        // Store in session storage
        sessionStorage.setItem('termsConfirmed', 'true');
        sessionStorage.setItem('termsTimestamp', termsTimestamp);

        // Style the tc-button
        styleConfirmedTcButton();

        // Update button state
        updateButtonState();

        // Store timestamp in database via AJAX
        storeTermsTimestamp();

        console.log('Terms confirmation complete at:', termsTimestamp);
    }

    /**
     * Store terms timestamp via AJAX
     */
    function storeTermsTimestamp() {
        if (typeof confirmTerms === 'undefined') {
            console.warn('confirmTerms object not found');
            return;
        }

        $.ajax({
            url: confirmTerms.ajaxUrl,
            type: 'POST',
            data: {
                action: 'store_terms_timestamp',
                timestamp: termsTimestamp,
                nonce: confirmTerms.nonce
            },
            success: function (response) {
                if (response.success) {
                    console.log('Terms timestamp stored successfully');
                } else {
                    console.error('Failed to store terms timestamp:', response);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error storing terms timestamp:', error);
            }
        });
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
        const storedTimestamp = sessionStorage.getItem('termsTimestamp');

        if (wasConfirmed) {
            console.log('Restoring previously confirmed terms state');
            termsConfirmed = true;
            termsTimestamp = storedTimestamp;

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
        termsTimestamp = null;
        lastButtonState = null;

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
        sessionStorage.removeItem('termsTimestamp');
    }

    /**
     * Handle page navigation
     */
    $(window).on('beforeunload', function () {
        const currentUrl = window.location.href;
        sessionStorage.setItem('lastCheckoutUrl', currentUrl);

        if (termsConfirmed && currentUrl.includes('checkout')) {
            sessionStorage.setItem('termsConfirmed', 'true');
            sessionStorage.setItem('termsTimestamp', termsTimestamp);
        }
    });

    const lastUrl = sessionStorage.getItem('lastCheckoutUrl');
    const currentUrl = window.location.href;
    if (lastUrl && lastUrl !== currentUrl && !currentUrl.includes('checkout')) {
        sessionStorage.removeItem('termsConfirmed');
        sessionStorage.removeItem('termsTimestamp');
    }

    // FIX: Listen for shipping validation changes
    $(document).on('shippingValidationChanged', function () {
        console.log('Shipping validation changed - updating button state');
        updateButtonState();
    });

    /**
     * Public API for other scripts
     */
    window.TermsAndConditions = {
        isConfirmed: function () {
            return termsConfirmed;
        },
        getTimestamp: function () {
            return termsTimestamp;
        },
        reset: resetTermsConfirmation,
        confirm: function () {
            confirmTermsAndConditions();
        },
        updateButton: function () {
            updateButtonState();
        },
        getState: function () {
            return {
                confirmed: termsConfirmed,
                timestamp: termsTimestamp,
                monthlyBillingValidated: monthlyBillingValidated,
                shippingValidated: checkShippingValidation(),
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
        console.log('Terms Timestamp:', termsTimestamp);
        console.log('Monthly Billing Validated:', monthlyBillingValidated);
        console.log('Shipping Validated:', checkShippingValidation());
        console.log('Last Button State:', lastButtonState);
        console.log('Current State Signature:', `${termsConfirmed}-${monthlyBillingValidated}-${checkShippingValidation()}`);
        console.log('===============================');

        return window.TermsAndConditions.getState();
    };

}); // End jQuery document ready