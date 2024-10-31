<?php

/**
 * Clear unused attachments from wp_posts table.
 *
 * @return void
 *
 * @since 1.0.0
 */
add_action("admin_init", "seopic_clear_unused_media");

function seopic_clear_unused_media()
{
    global $pagenow;
    if ($pagenow === "post.php" and $_GET["action"] === "edit") {
        // Sanitize the $_GET['post'] variable using sanitize_key()
        $clean_post = sanitize_key($_GET["post"]);
        // Get the post meta using the sanitized variable
        $very_old_ids = get_post_meta($clean_post, '_seopic_very_old_ids', true);

        if ($very_old_ids !== "" && $very_old_ids !== false) {
            foreach ($very_old_ids as $id) {
                wp_delete_post($id, true);
            }
        }

        // Sanitize the $_GET['post'] variable again when deleting the post meta
        delete_post_meta(sanitize_key($_GET["post"]), '_seopic_very_old_ids');
    }
}

/**
 * Soft delete the attachment.
 *
 * @param int $post_id
 * @param int $attachment_id
 * @return void
 *
 * @since 1.0.0
 */
function seopic_soft_delete_attachment($post_id, $attachment_id)
{
    $very_old_ids = get_post_meta($post_id, "_seopic_very_old_ids", true);
    $post_data = wp_delete_attachment($attachment_id);
    if ($post_data !== null and $post_data !== false) {
        $post_arr = array(
            "post_date" => $post_data->post_date,
            "post_date_gmt" => $post_data->post_date_gmt,
            "post_title" => $post_data->post_title,
            "post_status" => "trash",
            "post_name" => $post_data->post_name,
            "post_modified" => $post_data->post_modified,
            "post_parent" => $post_data->post_parent,
            "guid" => $post_data->guid,
            "post_type" => $post_data->post_type,
            "post_mime_type" => $post_data->post_mime_type
        );
        $new_attach_id = wp_insert_post($post_arr);
        if ($very_old_ids === "" || $very_old_ids === false) {
            $very_old_ids = [];
            $very_old_ids[] = $new_attach_id;
            update_post_meta($post_id, "_seopic_very_old_ids", $very_old_ids);
        } else {
            $very_old_ids[] = $new_attach_id;
            update_post_meta($post_id, "_seopic_very_old_ids", $very_old_ids);
        }
    }
}