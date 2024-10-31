<?php

/**
 * Clears post update status after inserting the post.
 *
 * @param int $post_id
 * @return void
 *
 * @since 1.0.0
 */
add_action('wp_after_insert_post', 'seopic_clear_post_update_status', 10, 1);

function seopic_clear_post_update_status($post_id)
{
    delete_post_meta($post_id, "_seopic_post_update_status");
}

/**
 * Updates the post progress status.
 *
 * @since 1.0.0
 */
add_action('wp_ajax_post_update_status', 'seopic_post_update_status');
add_action('wp_ajax_nopriv_post_update_status', 'seopic_post_update_status');

function seopic_post_update_status()
{
    header('Content-Type: application/json; charset=utf-8');
    // Sanitize the $_POST['the_ID'] variable using intval()
    $post_id = intval($_POST['the_ID']);
    echo json_encode(get_post_meta($post_id, '_seopic_post_update_status', true));
    exit;
}