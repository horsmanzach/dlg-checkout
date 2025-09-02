/**
 * Terms & Conditions Confirmation Handler
 * File: js/confirm-terms.js
 * 
 * This script handles the terms and conditions confirmation flow and integrates
 * with the Moneris payment button to ensure users confirm T&C before payment.
 */

jQuery(document).ready(function($) {
    
    // State variable to track if terms are confirmed
    let termsConfirmed = false;
    
    // Initialize the enhanced terms and conditions functionality
    initializeTermsAndConditions();
    initializePopupFunctionality();
    
    /**
     * Initialize Terms and Conditions confirmation system
     */
    function initializeTermsAndConditions() {
        console.log('Initializing Terms & Conditions confirmation system...');
        
        // Set initial state of Moneris button to disabled
        disableMonerisButton();
        
        // Monitor for dynamically loaded Moneris buttons
        monitorForMonerisButton();
        
        // Check if terms were previously confirmed in this session
        restoreTermsState();
        
        console.log('Terms & Conditions system initialized');
    }
    
    /**
     * Initialize popup functionality (enhanced version of your original script)
     */
    function initializePopupFunctionality() {
        console.log('Initializing popup functionality...');
        
        // Wrap popup content (your existing functionality)
        $('.dl-popup-content').each(function(){
            $(this).wrap('<div class="dl-popup-wrapper"><div class="dl-popup-inside">');
        });
        
        // Handle popup triggers (your existing functionality)
        $('.dl-popup-trigger, .dl-menu-popup > a').off().click(function(e){
            e.preventDefault();
            var SectionID = $(this).attr('href');
            $(SectionID).closest('.dl-popup-wrapper').addClass('popup-is-visible');
            $(SectionID).closest('.et_builder_inner_content').addClass('popup-is-visible');
            $('body').addClass('dl-noscroll');
        });	
        
        // Handle standard popup close (your existing functionality)
        $('.dl-popup-close').click(function(e){
            e.preventDefault();
            closePopup($(this));
        });
        
        console.log('Popup functionality initialized');
    }
    
    /**
     * Setup the confirm terms button handler
     */
    $(document).on('click', '.confirm-terms', function(e){
        e.preventDefault();
        
        console.log('Terms confirmation button clicked');
        
        // Close the popup
        closePopup($(this));
        
        // Mark terms as confirmed and update UI
        confirmTermsAndConditions();
        
        // Prevent event bubbling
        e.stopPropagation();
        return false;
    });
    
    /**
     * Enhanced popup close function
     */
    function closePopup($trigger) {
        $('.popup-is-visible').removeClass('popup-is-visible');
        $('body').removeClass('dl-noscroll');
        
        // Handle video pause (your existing functionality)
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
        
        // Mark terms as confirmed
        termsConfirmed = true;
        
        // Update the tc-button appearance
        styleConfirmedTcButton();
        
        // Enable the Moneris payment button
        enableMonerisButton();
        
        // Store confirmation state
        sessionStorage.setItem('termsConfirmed', 'true');
        
        // Fire events for other scripts
        $(document).trigger('termsConfirmed');
        
        console.log('Terms confirmed successfully');
    }
    
    /**
     * Style the tc-button to show it's been confirmed
     */
    function styleConfirmedTcButton() {
        const $tcButton = $('.tc-button');
        
        if ($tcButton.length > 0) {
            console.log('Styling tc-button as confirmed');
            
            // Add confirmed styling with animation
            $tcButton
                .addClass('terms-confirmed')
                .css({
                    'background-color': '#28a745',
                    'color': '#ffffff',
                    'border-color': '#28a745',
                    'transition': 'all 0.3s ease'
                });
            
            // Add checkmark if it doesn't exist
            if (!$tcButton.find('.terms-checkmark').length) {
                $tcButton.prepend('<span class="terms-checkmark">✓ </span>');
            }
            
            // Update button text
            const originalText = $tcButton.text().replace('✓ ', '');
            if (!originalText.includes('Confirmed')) {
                $tcButton.html('<span class="terms-checkmark">✓ </span>' + originalText + ' (Confirmed)');
            }
            
            // Add a subtle animation
            $tcButton.fadeOut(200).fadeIn(400);
        }
    }
    
    /**
     * Disable the Moneris payment button and add visual indication
     */
    function disableMonerisButton() {
        const $monerisBtn = $('#moneris-complete-payment-btn');
        
        if ($monerisBtn.length > 0) {
            console.log('Disabling Moneris payment button - terms not confirmed');
            
            $monerisBtn
                .prop('disabled', true)
                .addClass('terms-not-confirmed')
                .css({
                    'opacity': '0.6',
                    'cursor': 'not-allowed',
                    'pointer-events': 'none'
                });
            
            // Add message if it doesn't exist
            const $container = $monerisBtn.closest('.moneris-complete-payment-container');
            if ($container.length > 0 && !$container.find('.terms-requirement-message').length) {
                $container.append(
                    '<div class="terms-requirement-message" style="color: #dc3545; font-size: 14px; margin-top: 10px; text-align: center;">' +
                    '<i>Please confirm Terms & Conditions to enable payment</i>' +
                    '</div>'
                );
            }
        }
    }
    
    /**
     * Enable the Moneris payment button
     */
    function enableMonerisButton() {
        const $monerisBtn = $('#moneris-complete-payment-btn');
        
        if ($monerisBtn.length > 0) {
            console.log('Enabling Moneris payment button - terms confirmed');
            
            // Remove disabled state
            $monerisBtn
                .prop('disabled', false)
                .removeClass('terms-not-confirmed')
                .css({
                    'opacity': '1',
                    'cursor': 'pointer',
                    'pointer-events': 'auto',
                    'transition': 'opacity 0.3s ease'
                });
            
            // Update or remove requirement message
            const $message = $('.terms-requirement-message');
            if ($message.length > 0) {
                $message
                    .css('color', '#28a745')
                    .html('<i>✓ Terms & Conditions confirmed</i>')
                    .fadeOut(4000);
            }
            
            // Show success message briefly
            const $container = $monerisBtn.closest('.moneris-complete-payment-container');
            if ($container.length > 0) {
                if (!$container.find('.payment-enabled-message').length) {
                    $container.append(
                        '<div class="payment-enabled-message" style="color: #28a745; font-size: 14px; margin-top: 10px; text-align: center; opacity: 0;">' +
                        '✓ Payment button enabled' +
                        '</div>'
                    );
                    
                    $('.payment-enabled-message').animate({opacity: 1}, 500).delay(3000).animate({opacity: 0}, 500);
                }
            }
        }
        
        // Also look for buttons that might be added dynamically
        setTimeout(function() {
            const $laterButtons = $('#moneris-complete-payment-btn').not('.terms-enabled');
            if ($laterButtons.length > 0) {
                $laterButtons.addClass('terms-enabled').prop('disabled', false).css({
                    'opacity': '1',
                    'cursor': 'pointer',
                    'pointer-events': 'auto'
                });
            }
        }, 1000);
    }
    
    /**
     * Restore terms confirmation state from session storage
     */
    function restoreTermsState() {
        // Check if terms were previously confirmed
        const wasConfirmed = sessionStorage.getItem('termsConfirmed') === 'true';
        
        if (wasConfirmed) {
            console.log('Restoring previously confirmed terms state');
            termsConfirmed = true;
            
            // Restore confirmed state
            setTimeout(function() {
                const $tcButton = $('.tc-button');
                if ($tcButton.length > 0 && !$tcButton.hasClass('terms-confirmed')) {
                    styleConfirmedTcButton();
                }
                
                enableMonerisButton();
            }, 500);
        } else {
            // Ensure Moneris button is disabled initially
            setTimeout(function() {
                const $monerisBtn = $('#moneris-complete-payment-btn');
                if ($monerisBtn.length > 0 && !$monerisBtn.prop('disabled')) {
                    disableMonerisButton();
                }
            }, 500);
        }
    }
    
    /**
     * Monitor for dynamically loaded Moneris buttons
     */
    function monitorForMonerisButton() {
        // Use a mutation observer to watch for dynamically added buttons
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    const $newMonerisBtn = $(mutation.target).find('#moneris-complete-payment-btn');
                    if ($newMonerisBtn.length > 0) {
                        console.log('New Moneris button detected');
                        if (!termsConfirmed) {
                            setTimeout(function() {
                                disableMonerisButton();
                            }, 100);
                        } else {
                            setTimeout(function() {
                                enableMonerisButton();
                            }, 100);
                        }
                    }
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        // Also check periodically in case mutation observer misses something
        setInterval(function() {
            const $monerisBtn = $('#moneris-complete-payment-btn');
            if ($monerisBtn.length > 0) {
                const isCurrentlyDisabled = $monerisBtn.prop('disabled');
                const shouldBeDisabled = !termsConfirmed;
                
                if (isCurrentlyDisabled !== shouldBeDisabled) {
                    if (shouldBeDisabled) {
                        disableMonerisButton();
                    } else {
                        enableMonerisButton();
                    }
                }
            }
        }, 2000);
    }
    
    /**
     * Handle clicks on disabled Moneris button to show Terms & Conditions popup
     */
    $(document).on('click', '#moneris-complete-payment-btn', function(e) {
        const $btn = $(this);
        
        if ($btn.hasClass('terms-not-confirmed') || ($btn.prop('disabled') && !termsConfirmed)) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Disabled Moneris button clicked - opening Terms & Conditions popup');
            
            // Find and trigger the Terms & Conditions popup
            const $tcButton = $('.tc-button');
            if ($tcButton.length > 0) {
                // Get the href from tc-button to open the right popup
                const popupTarget = $tcButton.attr('href');
                if (popupTarget) {
                    $(popupTarget).closest('.dl-popup-wrapper').addClass('popup-is-visible');
                    $(popupTarget).closest('.et_builder_inner_content').addClass('popup-is-visible');
                    $('body').addClass('dl-noscroll');
                }
            }
            
            // Show message in the Moneris validation area
            const $container = $btn.closest('.moneris-complete-payment-container');
            let $validationDiv = $container.find('.moneris-payment-validation-message');
            
            // If no validation div exists, create one
            if (!$validationDiv.length) {
                $validationDiv = $('<div class="moneris-payment-validation-message"></div>');
                $container.append($validationDiv);
            }
            
            $validationDiv
                .html('<div style="color: #dc3545; margin-top: 10px; text-align: center; font-size: 14px;">Please read and confirm the Terms & Conditions to continue with payment.</div>')
                .fadeIn()
                .delay(5000)
                .fadeOut();
            
            return false;
        }
    });
    
    /**
     * Reset terms confirmation (useful for testing or if user navigates back)
     */
    function resetTermsConfirmation() {
        console.log('Resetting terms confirmation');
        
        termsConfirmed = false;
        
        // Reset tc-button styling
        const $tcButton = $('.tc-button');
        $tcButton
            .removeClass('terms-confirmed')
            .css({
                'background-color': '',
                'color': '',
                'border-color': ''
            })
            .find('.terms-checkmark').remove();
        
        // Disable Moneris button
        disableMonerisButton();
        
        // Clear session storage
        sessionStorage.removeItem('termsConfirmed');
    }
    
    /**
     * Handle page navigation - manage terms confirmation state
     */
    $(window).on('beforeunload', function() {
        // Store current URL to check if we're staying on checkout
        const currentUrl = window.location.href;
        sessionStorage.setItem('lastCheckoutUrl', currentUrl);
        
        // Only preserve terms confirmation if staying on checkout pages
        if (termsConfirmed && currentUrl.includes('checkout')) {
            sessionStorage.setItem('termsConfirmed', 'true');
        }
    });
    
    // Check if we're on a different page and should reset terms
    const lastUrl = sessionStorage.getItem('lastCheckoutUrl');
    const currentUrl = window.location.href;
    if (lastUrl && lastUrl !== currentUrl && !currentUrl.includes('checkout')) {
        sessionStorage.removeItem('termsConfirmed');
    }
    
    /**
     * Public API for other scripts
     */
    window.TermsAndConditions = {
        isConfirmed: function() {
            return termsConfirmed;
        },
        reset: resetTermsConfirmation,
        confirm: function() {
            confirmTermsAndConditions();
        },
        getState: function() {
            return {
                confirmed: termsConfirmed,
                tcButtonExists: $('.tc-button').length > 0,
                monerisButtonExists: $('#moneris-complete-payment-btn').length > 0,
                monerisButtonEnabled: !$('#moneris-complete-payment-btn').prop('disabled')
            };
        }
    };
    
    // Debug function for troubleshooting
    window.debugTermsAndConditions = function() {
        console.log('=== Terms & Conditions Debug Info ===');
        console.log('Terms Confirmed:', termsConfirmed);
        console.log('TC Button exists:', $('.tc-button').length > 0);
        console.log('TC Button classes:', $('.tc-button').attr('class'));
        console.log('Moneris Button exists:', $('#moneris-complete-payment-btn').length > 0);
        console.log('Moneris Button disabled:', $('#moneris-complete-payment-btn').prop('disabled'));
        console.log('Moneris Button classes:', $('#moneris-complete-payment-btn').attr('class'));
        console.log('Session Storage:', sessionStorage.getItem('termsConfirmed'));
        console.log('===============================');
        
        return window.TermsAndConditions.getState();
    };
    
}); // End jQuery document ready