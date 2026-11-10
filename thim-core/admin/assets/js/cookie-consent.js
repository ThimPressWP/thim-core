jQuery(document).ready(function ($) {
    // On change cookie category: action update_cookie_manager_form
    $('#cookie_category').on('change', function () {
        const selectedCategory = $(this).val();

        // Update the URL parameter
        const url = new URL(window.location.href);
        url.searchParams.set('cookie-category', selectedCategory);
        window.history.replaceState(null, '', url.toString());
    
        // Perform AJAX request to fetch updated form content
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'update_cookie_manager_form',
                cookie_category: selectedCategory,
                nonce: thimCookieConsent.nonce
            },
            success: function (response) {
                if (response.success) {
                    var $form = $('#cookie-manager-form');
                    $form.empty();
                    $form.append( $(response.data.form_html) );
                } else {
                    console.error(response.data.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
            },
        });
    });
    

    // Submit form: action cookie_consent_settings
    $('.cookie-consent-form').on('submit', function (e) {
        e.preventDefault();

        const formData = $(this).serialize();

        // Convert serialized form data into an object
        const formDataObject = {};
        formData.split('&').forEach(function (item) {
            const [key, value] = item.split('=');
            formDataObject[decodeURIComponent(key)] = decodeURIComponent(value);
        });

        // Add the action and nonce to the form data object
        formDataObject.action = 'cookie_consent_settings';
        formDataObject.cookie_consent_nonce = thimCookieConsent.nonce;

        // Include HTML content from wp_editor (if applicable)
        const editorContent = tinyMCE.get('consent_message')?.getContent() || $('#consent_message').val();
        const editorContentMess = tinyMCE.get('customise_consent_mess')?.getContent() || $('#customise_consent_mess').val();
        formDataObject.customise_consent_mess = editorContentMess;
        formDataObject.consent_message = editorContent;

        // Send the AJAX request
        $.post(ajaxurl, formDataObject)
            .done(function (response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    location.reload();
                }
            })
            .fail(function () {
                alert('Failed to save settings.');
            });
    });

    
    // Delete cookie list item: action thim_edit_cookie_list
    $(document).on('click', '.thim-wrapper .delete-cookie-button', function () {
        const button = $(this);
        const cookieId = button.data('cookie-id');
        const cookieCategory = button.data('cookie-category');
        const nonce = button.data('nonce');

        if (!confirm('Are you sure you want to delete this cookie?')) {
            return;
        }

        // Perform AJAX request to delete the cookie
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'thim_edit_cookie_list',
                cookie_id: cookieId,
                cookie_category: cookieCategory,
                nonce: nonce
            },
            success: function (response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Failed to delete cookie.');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('An error occurred while deleting the cookie.');
            }
        });
    });

    // Render cookie details in the table
    function renderCookieTable() {
        const tableBody = $('#cookie-scan-list-table tbody');
        tableBody.empty(); 

        if (thimCookieConsent.cookieDetails.length > 0) {
            thimCookieConsent.cookieDetails.forEach(function (cookie) {
                const row = `
                    <tr>
                        <td>${cookie.name}</td>
                        <td>${cookie.domain}</td>
                        <td>${cookie.type}</td>
                    </tr>
                `;
                tableBody.append(row);
            });
        } else {
            tableBody.append('<tr><td colspan="3">No cookies found.</td></tr>');
        }
    }
    renderCookieTable();
    
});