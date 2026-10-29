/**
 * Nuclear Reset — Admin JS
 *
 * Handles:
 *  - Enabling the reset button only when the user types "RESET"
 *  - Collecting checkbox options
 *  - Sending the AJAX request
 *  - Rendering the server-side log with colour coding
 */
/* global NuclearResetData, jQuery */
( function ( $, data ) {
    'use strict';

    const $input   = $( '#nr-confirm-input' );
    const $btn     = $( '#nr-execute-btn' );
    const $progress = $( '#nr-progress' );
    const $msg     = $( '#nr-progress__message' );
    const $log     = $( '#nr-log' );

    // ── Enable button only when the magic word is typed ────────────────────
    $input.on( 'input', function () {
        const val = $( this ).val().trim().toUpperCase();
        $btn.prop( 'disabled', val !== data.confirmPhrase );
    } );

    // ── Main click handler ──────────────────────────────────────────────────
    $btn.on( 'click', function ( e ) {
        e.preventDefault();

        // Final guard — should already be checked by disabled state.
        if ( $input.val().trim().toUpperCase() !== data.confirmPhrase ) {
            window.alert( data.i18n.confirmError );
            return;
        }

        // Confirm one more time with browser native dialog.
        if ( ! window.confirm(
            'ARE YOU ABSOLUTELY SURE?\n\n' +
            'This will permanently destroy all site content, drop plugin tables, ' +
            'deactivate all plugins, and reset WordPress to factory defaults.\n\n' +
            'This action CANNOT be undone. Click OK only if you are certain.'
        ) ) {
            return;
        }

        // ── Lock UI ───────────────────────────────────────────────────────
        $btn.prop( 'disabled', true );
        $input.prop( 'disabled', true );
        $( '.nr-option input' ).prop( 'disabled', true );
        $progress.removeAttr( 'hidden' );
        $msg.text( data.i18n.resetting );
        $log.attr( 'hidden', true ).empty();

        // ── Collect options ────────────────────────────────────────────────
        const payload = {
            action           : 'nuclear_reset_execute',
            nonce            : data.nonce,
            opt_content      : $( '#nr-opt-content' ).is( ':checked' ) ? 1 : 0,
            opt_db           : $( '#nr-opt-db' ).is( ':checked' ) ? 1 : 0,
            opt_plugins      : $( '#nr-opt-plugins' ).is( ':checked' ) ? 1 : 0,
            opt_theme        : $( '#nr-opt-theme' ).is( ':checked' ) ? 1 : 0,
            opt_options      : $( '#nr-opt-options' ).is( ':checked' ) ? 1 : 0,
            opt_del_plugins  : $( '#nr-opt-del-plugins' ).is( ':checked' ) ? 1 : 0,
            opt_del_themes   : $( '#nr-opt-del-themes' ).is( ':checked' ) ? 1 : 0,
        };

        // ── Send AJAX ──────────────────────────────────────────────────────
        $.ajax( {
            url     : data.ajaxUrl,
            method  : 'POST',
            data    : payload,
            timeout : 300000, // 5 minutes — large sites may be slow.
        } )
        .done( function ( response ) {
            if ( response.success ) {
                $msg.text( data.i18n.done );
                renderLog( response.data.log || [], false );

                // Redirect after a short pause so the user can see the log.
                const redirect = response.data.redirect || window.location.href;
                setTimeout( function () {
                    window.location.href = redirect;
                }, 4000 );
            } else {
                $msg.text( data.i18n.error ).css( 'color', '#f85149' );
                const errLog = [
                    '=== ERROR ===',
                    response.data.message || 'Unknown error.',
                    response.data.trace   || '',
                ];
                renderLog( errLog, true );
            }
        } )
        .fail( function ( jqXHR, textStatus ) {
            $msg.text( data.i18n.error ).css( 'color', '#f85149' );
            renderLog( [
                '=== AJAX FAILURE ===',
                'Status: ' + textStatus,
                'Response: ' + jqXHR.responseText,
            ], true );
        } );
    } );

    // ── Log renderer ────────────────────────────────────────────────────────
    /**
     * Append coloured log lines to #nr-log.
     *
     * @param {string[]} lines    Log lines from the server.
     * @param {boolean}  isError  When true, paint everything red.
     */
    function renderLog( lines, isError ) {
        $log.removeAttr( 'hidden' ).empty();

        lines.forEach( function ( line ) {
            const $span = $( '<div>' ).text( line );

            if ( isError ) {
                $span.addClass( 'log-error' );
            } else if ( /===/.test( line ) ) {
                $span.addClass( 'log-head' );
            } else if ( /^---/.test( line ) ) {
                $span.addClass( 'log-head' );
            } else if ( /warning/i.test( line ) || /WARN/i.test( line ) ) {
                $span.addClass( 'log-warn' );
            } else if ( /error/i.test( line ) ) {
                $span.addClass( 'log-error' );
            } else {
                $span.addClass( 'log-ok' );
            }

            $log.append( $span );
        } );

        // Scroll to bottom.
        $log.scrollTop( $log[ 0 ].scrollHeight );
    }

}( jQuery, NuclearResetData ) );
