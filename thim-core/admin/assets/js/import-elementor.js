(function (wp, $) {
    'use strict';

    var apiFetch = wp.apiFetch;

    $(document).ready(function () {
        var container = $( '.tc-import-elementor' );
        if ( ! container.length ) {
            return;
        }

        var listContainer = $( '<div class="thim-import-list" style="opacity:0"></div>' ).appendTo( container );

        // Loading spinner overlay for initial fetch
        var loading = $( '<div class="thim-import-loading"><div class="thim-spinner" aria-hidden="true"></div></div>' ).appendTo( container );

        // Processing overlay shown during create+import
        var processingOverlay = $( '<div class="thim-import-processing"></div>' ).appendTo( container );
        var processingInner = $( '<div class="thim-import-processing-inner"><div class="thim-spinner" aria-hidden="true"></div></div>' ).appendTo( processingOverlay );

        var showProcessing = function () {
            processingOverlay.css('display','flex');
            listContainer.find('button').prop('disabled', true ).addClass('disabled');
        };

        var hideProcessing = function () {
            processingOverlay.hide();
            listContainer.find('button').prop('disabled', false ).removeClass('disabled');
        };

        // Fetch templates from thim-ekit (pages only)
        apiFetch( { path: 'thim-ekit/get-templates' } ).then( function ( data ) {
            if ( ! data ) {
                loading.remove();
                listContainer.html( '<div class="thim-import-one-row">No templates available.</div>' );
                listContainer.animate({opacity:1}, 300);
                return;
            }

            var theme = ( typeof ThimImportElementor !== 'undefined' && ThimImportElementor.theme ) ? ThimImportElementor.theme : 'thim-kit-free';

            var pagesFree = data.free && data.free.page ? data.free.page : {};
            var pagesTheme = data.theme && data.theme.page ? data.theme.page : {};

            // Check whether theme is active via Thim_Core; default to true if function absent
            var themeActive = ( typeof Thim_Core !== 'undefined' && typeof Thim_Core.check_active === 'function' ) ? Thim_Core.check_active() : true;

            var buildItems = function ( items, isPro ) {
                var html = '';
                Object.entries(items).sort(([, a], [, b]) => (a.priority ?? Infinity) - (b.priority ?? Infinity)).forEach(([key, item]) => {;
                    var title = item.title || key;
                    var el_v4 = item.el_v4 ?? false;
                    var defaultSrc = isPro ?
                        'https://raw.githubusercontent.com/ThimPressWP/demo-data/master/' + theme + '/thim-kit/page/' + key + '.jpg' :
                        'https://raw.githubusercontent.com/ThimPressWP/demo-data/master/thim-kit-free/page/' + key + '.jpg';

                    // Prefer thumbnail provided by JSON if available
                    // var src = item.thumbnail && item.thumbnail !== false ? item.thumbnail : defaultSrc;
                    var src = defaultSrc;
                    var previewUrl = item.url && item.url !== false ? item.url : '';

                    html += '<div class="thim-import-item">';
                    html += '<div class="thim-import-item-thumb" style="background-image: url(' + src + ');">';
                    // overlay anchor with plus icon (only used if previewUrl exists)
                    if ( previewUrl ) {
                        html += '<a class="thim-import-thumb-overlay" href="' + previewUrl + '" target="_blank" rel="noopener">';
                        html += '<span class="thim-import-thumb-icon" aria-hidden="true" title="Preview"><svg fill="currentColor" width="18px" height="18px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M21.92,11.6C19.9,6.91,16.1,4,12,4S4.1,6.91,2.08,11.6a1,1,0,0,0,0,.8C4.1,17.09,7.9,20,12,20s7.9-2.91,9.92-7.6A1,1,0,0,0,21.92,11.6ZM12,18c-3.17,0-6.17-2.29-7.9-6C5.83,8.29,8.83,6,12,6s6.17,2.29,7.9,6C18.17,15.71,15.17,18,12,18ZM12,8a4,4,0,1,0,4,4A4,4,0,0,0,12,8Zm0,6a2,2,0,1,1,2-2A2,2,0,0,1,12,14Z"/></svg></span>';
                        html += '</a>';
                    }
                    html += '</div>';
                    html += '<div class="thim-import-item-body">';
                    html += '<div class="thim-import-item-title">' + title + '</div>';
                    if ( themeActive ) { 
                        if ( ( ThimImportElementor.el_v4_status !== 'active' ) && el_v4  ) { // tag required active v4
                            html += '<a class="button v4-required" href="' + ThimImportElementor.adminUrl + 'admin.php?page=elementor-settings#tab-editor-v4-opt-in' + '" target="_blank">EL V4 Required</button>';
                        } else {
                            html += '<button class="button thim-import-item-actions" data-id="' + key + '" data-type="pages" data-pro="' + (isPro ? 1 : 0) + '" data-title="' + title + '">Create Page</button>';
                        }
                    }
                    html += '</div></div>';
                } );

                return html;
            };

            var html = '';
            if ( Object.keys( pagesFree ).length ) {
                html += '<div class="thim-import-one-row"><h2>Free Elementor Pages</h2></div>' + buildItems( pagesFree, false );
            }
            if ( Object.keys( pagesTheme ).length ) {
                html += '<div class="thim-import-one-row"><h2>Elementor Pages</h2></div>' + buildItems( pagesTheme, true );
            }

            if ( ! html ) {
                loading.remove();
                listContainer.html( '<div class="thim-import-one-row">No pages found.</div>' );
                listContainer.animate({opacity:1}, 300);
                return;
            }

            loading.remove();
            listContainer.html( html );
            listContainer.animate({opacity:1}, 300);

            // Click handler for insert
            listContainer.on( 'click', '.thim-import-item-actions', function ( e ) {
                e.preventDefault();
                
                var id = $( this ).data( 'id' );
                var type = $( this ).data( 'type' );
                var isPro = $( this ).data( 'pro' );
                var title = $( this ).data( 'title' );

                // Scroll to top to reveal processing overlay, then show processing
                $('html, body').animate({
                    scrollTop: container.offset().top - 50
                }, 300);

                // create a page and import directly into it
                showProcessing();
                $.post( ThimImportElementor.ajaxUrl, {
                    action: 'thim_core_create_elementor_library',
                    title: title,
                    post_type: 'page',
                    security: ThimImportElementor.security
                } ).done( function ( res ) {
                    if ( res && res.success && res.data && res.data.post_id ) {
                        var pageID = res.data.post_id;

                        // call thim-ekit import endpoint with the page post ID
                        apiFetch( {
                            path: 'thim-ekit/import',
                            method: 'POST',
                            data: {
                                type: type,
                                id: id,
                                postID: pageID,
                                theme: isPro ? ( ThimImportElementor.theme || '' ) : ''
                            }
                        } ).then( function ( result ) {
                            hideProcessing();
                            if ( result && result.success ) {
                                var viewLink  = ThimImportElementor.siteUrl  + '/?page_id=' + pageID;
                                var editLink  = ThimImportElementor.adminUrl + 'post.php?post=' + pageID + '&action=elementor';
                                var $msg = $( '<div class="thim-import-success">✅ ' + title + ' has been successfully created. <br/>  <a href="' + viewLink + '" target="_blank" rel="noopener">View Page</a> | <a href="' + editLink + '" target="_blank" rel="noopener">Edit Page</a></div>' );
                                $('.thim-import-one-row').find('.thim-import-success').remove();
                                $('.thim-import-one-row').append( $msg );
                            } else {
                                var reason = (result && (result.message || (result.data && result.data.message))) || JSON.stringify(result);
                                console.error('Import failed response:', result);
                                alert( 'Import failed: ' + reason );
                            }
                        } ).catch( function ( err ) {
                            hideProcessing();
                            console.error('Import error:', err);
                            var msg = (err && (err.message || (err.data && err.data.message) || err.statusText)) || JSON.stringify(err);
                            alert( 'Import error: ' + msg );
                        } );
                    } else {
                        hideProcessing();
                        console.error('Create page AJAX failed:', res);
                        var reason = (res && (res.data && res.data.message)) || (res && res.message) || JSON.stringify(res);
                        alert( 'Failed to create page: ' + reason );
                    }
                } ).fail( function ( jqXHR, textStatus, errorThrown ) {
                    hideProcessing();
                    console.error('AJAX create page failed:', jqXHR, textStatus, errorThrown );
                    var msg = (jqXHR && jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message) || errorThrown || textStatus;
                    alert( 'Create page error: ' + msg );
                } );
            } );

        } ).catch( function ( err ) {
            loading.remove();
            listContainer.html( '<div class="thim-import-one-row">Error loading templates.</div>' );
            listContainer.animate({opacity:1}, 300);
            console.error( err );
        } );
    });

})(window.wp, jQuery);
