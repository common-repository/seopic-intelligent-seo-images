<?php

/**
 * Filters featured image and rename it if needed.
 *
 * @param int $post_id : the post id
 * @return void
 *
 * @since 1.0.0
 */
function seopic_filter_featured_image($post_id)
{
    global $seopic_all_media_count;
    $data = json_decode(file_get_contents('php://input'), true);

    if ($post_id == 0 || get_post_status($post_id) === "inherit") {
        return;
    }

    if (get_post_type($post_id) === "attachment" || get_post_type($post_id) === "revision") {
        return;
    }

    update_post_meta($post_id, "_seopic_featured_image_is_created", "created");
    $old_id = $data['featured_media'] ?? get_post_meta($post_id, "_thumbnail_id", true);

    if ($old_id === "") {
        $old_media_copies = get_post_meta($post_id, "_seopic_old_media_copies", true);
        if ($old_media_copies !== "" && $old_media_copies !== false) {
            foreach ($old_media_copies as $copy) {
                if (seopic_get_media_copy($post_id, $copy["new_attachment_id"], $copy["alt"]) === false) {
                    seopic_soft_delete_attachment($post_id, $copy["new_attachment_id"]);
                }
            }
        }

        // Clearing previous attachments link.
        $current_media_copies = get_post_meta($post_id, "_seopic_current_media_copies", true);
        if ($current_media_copies !== "")
            update_post_meta($post_id, "_seopic_old_media_copies", $current_media_copies);
        return;
    }

    $original_media_id = seopic_get_media_attachment_post_id($old_id);
    $alt = get_post_meta($old_id, "_wp_attachment_image_alt", true);
    $media_copy = seopic_get_media_copy($post_id, $original_media_id, $alt);

    if ($alt === "" || $alt === false) {
        $old_media_copies = get_post_meta($post_id, "_seopic_old_media_copies", true);
        if ($old_media_copies !== "" && $old_media_copies !== false) {
            foreach ($old_media_copies as $copy) {
                if (seopic_get_media_copy($post_id, $copy["new_attachment_id"], $copy["alt"]) === false) {
                    seopic_soft_delete_attachment($post_id, $copy["new_attachment_id"]);
                }
            }
        }

        // Clearing previous attachments link.
        $current_media_copies = get_post_meta($post_id, "_seopic_current_media_copies", true);
        if ($current_media_copies !== "")
            update_post_meta($post_id, "_seopic_old_media_copies", $current_media_copies);
        set_post_thumbnail($post_id, $original_media_id);
        return;
    }

    if ($media_copy !== false) {
        if ($alt !== "" and $alt !== false and get_the_post_thumbnail($post_id) !== "") {
            seopic_is_the_same_alt($post_id, $media_copy, $alt);
            update_post_meta($post_id, "_thumbnail_id", $media_copy);
            update_post_meta($media_copy, "_wp_attachment_image_alt", $alt);
        }
    } else {
        if ($alt !== "" and $alt !== false) {
            update_post_meta($post_id, "_seopic_post_update_status", array("message" => __("Renaming media", "seopic"), "progress" => $seopic_all_media_count, "all_images" => $seopic_all_media_count));
            $url = seopic_process_media($original_media_id, "_thumbnail_id", $post_id, $alt);
            $id = seopic_get_id_from_guid($url);
            $current_attachment = get_post_meta($post_id, "_seopic_current_media_copies", true);
            if (seopic_get_media_copy($post_id, $original_media_id, $alt) === false) {
                if ($current_attachment === "") {
                    $arr = [
                        [
                            "original_attachment_id" => $original_media_id,
                            "new_attachment_id" => $id,
                            "alt" => $alt
                        ]
                    ];
                    update_post_meta($post_id, "_seopic_current_media_copies", $arr);
                } else {
                    $current_attachment[] = [
                        "original_attachment_id" => $original_media_id,
                        "new_attachment_id" => $id,
                        "alt" => $alt
                    ];

                    update_post_meta($post_id, "_seopic_current_media_copies", $current_attachment);
                }
            }
            update_post_meta($post_id, "_thumbnail_id", $id);
            update_post_meta($id, "_wp_attachment_image_alt", $alt);
        }
    }

    $old_media_copies = get_post_meta($post_id, "_seopic_old_media_copies", true);

    if ($old_media_copies !== "" && $old_media_copies !== false) {
        foreach ($old_media_copies as $copy) {
            if (seopic_get_media_copy($post_id, $copy["new_attachment_id"], $copy["alt"]) === false) {
                seopic_soft_delete_attachment($post_id, $copy["new_attachment_id"]);
            }
        }
    }

    // Clearing previous attachments link.
    $current_media_copies = get_post_meta($post_id, "_seopic_current_media_copies", true);
    if ($current_media_copies !== "")
        update_post_meta($post_id, "_seopic_old_media_copies", $current_media_copies);
    delete_post_meta($post_id, "_seopic_featured_image_is_created");
}

/**
 * Prevents the post featured image to be saved using standard WordPress functions.
 *
 * @since 1.0.0
 */
add_filter('update_post_metadata', 'seopic_prevent_featured_image', 10, 3);

function seopic_prevent_featured_image($check, $post_id, $meta_key)
{
    if ($meta_key === "_thumbnail_id" and get_post_meta($post_id, "_seopic_featured_image_is_created" !== "created")) {
        return false;
    } else {
        return $check;
    }
}
