<?php

namespace SatoshiPay\Utils;

/**
 * Checks if Gutenberg editor is enabled
 * @return bool
 */
function isGutenberg()
{
    global $wp_version;

    if(
        is_plugin_active( 'gutenberg/gutenberg.php' )
        || ( version_compare( $wp_version, '5.0', '>=' ) && !is_plugin_active( 'classic-editor/classic-editor.php' ) )
    ) {
        return true;
    } else {
        return false;
    }
}
