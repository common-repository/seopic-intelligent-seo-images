<?php

/**
 * Register and enqueue styles and scripts for use in admin area.
 *
 * @since 1.0.0
 */
add_action("enqueue_block_editor_assets", "seopic_styles_scripts", 20);

function seopic_styles_scripts()
{
    global $seopic_plugin_url;
    wp_enqueue_script("seopic", $seopic_plugin_url . "assets/js/main.js", ["jquery"], false, true);
    wp_localize_script(
        "seopic",
        "seopic_langVars",
        array(
            "renamingMedia" => __("Renaming media: %d left", "seopic"),
            "mediaSuccessfullyRenamed" => __("All media renamed successfully", "seopic"),
        )
    );
    wp_enqueue_style("seopic", $seopic_plugin_url . "assets/css/style.css");
}