<?php

/**
 * Filters content and prepare the post content for extraction of post images.
 *
 * @params all the parameters are passed from the wp_insert_post_data filter
 *
 * @since 1.0.0
 */
function seopic_filter_post_content($data, $post_arr, $unsanitized_postarr)
{
    $original_data = $data;
    $post_id = $post_arr["ID"];

    update_post_meta($post_id, "_seopic_current_media_copies", "");
    if ($post_arr["ID"] !== 0 and get_post_status($post_arr["ID"]) !== "inherit" and get_post_type($post_arr["ID"]) !== "post-new-src") {
        if (isset($unsanitized_postarr["post_content"])) {
            $content = $unsanitized_postarr["post_content"] ?? "";
            if ($data["post_content"] !== "") {
                $data["post_content"] = seopic_process_media($content, "post_content", $post_id);
            }
        }
        seopic_filter_featured_image($post_id);
        update_post_meta($post_arr["ID"], "_seopic_post_update_status", array("message" => __("Post update is almost completed", "seopic"), "progress" => "completed", "all_images" => 0));
        $new_post_edited = array();
        $args = array("post_type" => "post-new-src", "post_parent" => $post_arr["ID"], "post_status" => "inherit");
        $the_query = new WP_Query($args);
        while ($the_query->have_posts()) {
            $the_query->the_post();
            $new_post_edited["ID"] = $the_query->post->ID;
        }
        $new_post_edited["post_status"] = "inherit";
        $new_post_edited["post_type"] = "post-new-src";
        $new_post_edited["post_parent"] = $post_arr["ID"];
        $new_post_edited["post_content"] = $data["post_content"];
        $new_post_edited["title"] = "post-new-src-" . $post_arr["ID"];
        wp_insert_post($new_post_edited);
    }

    return $original_data;
}

/**
 * Filters the content in the post or page to show modified content.
 *
 * @since 1.0.0
 */
add_filter("the_content", "seopic_filter_content");

function seopic_filter_content($content)
{
    $post_id = get_the_ID();
    $args = array("post_type" => "post-new-src", "post_parent" => $post_id, "post_status" => "inherit");
    $the_query = new WP_Query($args);

    while ($the_query->have_posts()) {
        $the_query->the_post();
        $content = get_the_content(null, false, $the_query->post->ID);
        $content = apply_filters('the_content', $content);
        $content = str_replace(']]>', ']]&gt;', $content);
        return $content;
    }

    return $content;
}
