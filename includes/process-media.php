<?php

/**
 * Returns and processes the media elements which will be modified.
 *
 * @param int|html $content
 * @param string $type => "post_content" or "_thumbnail_id"
 * @param int $post_id
 * @param string $alt the image alt
 * @return string content after filtering || the new url
 *
 * @since 1.0.0
 */
function seopic_process_media($content, $type, $post_id, $alt = "")
{
    global $seopic_all_media_count;
    $data = json_decode(file_get_contents('php://input'), true);
    $seopic_all_media_count = 0;

    if ($type === "post_content") {

        // Check if a new thumbnail image exists.
        $featured_id = $data["featured_media"] ?? get_post_meta($post_id, "_thumbnail_id", true);
        if ($featured_id !== "" and $featured_id !== false) {
            $featured_alt = get_post_meta($featured_id, "_wp_attachment_image_alt", true);
            if ($featured_alt !== "" and $featured_alt !== false) {
                if (!seopic_is_the_same_alt($post_id, seopic_get_media_attachment_post_id($featured_id), $featured_alt)) {
                    $seopic_all_media_count++;
                }
            }
        }

        $content = str_replace("\\\"", "\"", $content);
        $html = seopic_str_get_html($content);
        $all_src = [];
        $media_counters = array();
        $media_counter = 1;

        // Getting the total number of media to be renamed.
        foreach ($html->find("img") as $img) {
            $class = $img->getAttribute("class");

            if (preg_match("/(wp-image-[0-9]{1,})/", $class) === 0) {
                continue;
            }

            // Check if image alt is not empty.
            if ($img->getAttribute("alt") !== "" && $img->getAttribute("alt") !== '\"\"' && $img->getAttribute("alt") !== "") {

                $the_new_src = [];

                // Get image element src and alt.
                $url = str_replace("\\\"", "", $img->getAttribute("src"));
                $alt = str_replace("\\\"", "", $img->getAttribute("alt"));
                $media_url = seopic_get_base_url($url); // get image base url without size

                // If image is not an attachment don't do anything.
                if ($media_url !== false) {
                    $media_id = seopic_get_id_from_guid($media_url);
                    if ($media_id !== 0) {
                        if (!seopic_is_the_same_alt($post_id, $media_id, $alt)) {
                            $media_counters[] = $media_counter;
                            $seopic_all_media_count++;
                        }
                    }
                }
            }
            $media_counter++;
        }

        if ($seopic_all_media_count > 0) {
            update_post_meta($post_id, "_seopic_post_update_status", array("message" => __("Processing the post content", "seopic"), "progress" => 0));
        }
        $media_counter = 1;
        foreach ($html->find("img") as $img) {
            $class = $img->getAttribute("class");
            if (preg_match("/(wp-image-[0-9]{1,})/", $class) === 0) {
                continue;
            }
            update_post_meta($post_id, "_seopic_post_update_status", array("message" => __("Renaming media", "seopic"), "progress" => $media_counter, "all_images" => $seopic_all_media_count));

            // Check if image alt is not empty.
            if ($img->getAttribute("alt") !== "" && $img->getAttribute("alt") !== '\"\"' && $img->getAttribute("alt") !== "") {

                $the_new_src = [];
                // Get image element src and alt.
                $url = str_replace("\\\"", "", $img->getAttribute("src"));
                $alt = str_replace("\\\"", "", $img->getAttribute("alt"));
                $media_url = seopic_get_base_url($url); // get image base url without size

                // If image is not an attachment don't do anything.
                if ($media_url !== false) {
                    $media_id = seopic_get_id_from_guid($media_url);
                    seopic_is_the_same_alt($post_id, $media_id, $alt);
                    if (in_array($media_counter, $media_counters)) {
                        $media_counter++;
                    }

                    if ($media_id !== 0) {
                        $the_new_src["old_url"] = $media_url;
                        $the_new_src["old_id"] = $media_id;

                        // The original image id and url.
                        $media_id = seopic_get_media_attachment_post_id($media_id);
                        $media_url = get_the_guid($media_id);
                        $time = get_the_date("Y-m", $media_id);

                        if ($post_id !== 0 and get_post_status($post_id) !== "inherit") {
                            $new_url = seopic_generate_media_copy($post_id, $media_url, $time, $media_id, $alt);
                            $new_url = seopic_add_image_size_to_url($new_url);
                            $the_new_src["new_url"] = $new_url;
                            $the_new_src["new_id"] = seopic_get_id_from_guid(seopic_get_base_url($new_url));
                            $img->setAttribute("src", $new_url);
                            $new_class = str_replace("wp-image-" . $the_new_src["old_id"], "", $img->getAttribute("class"));
                            $new_class = preg_replace("/(wp-image-[0-9]{1,})/", "", $new_class);
                            $img->setAttribute("class", $new_class . " wp-image-" . $the_new_src["new_id"]);
                        }
                    }
                }
                $all_src[] = $the_new_src;
            } else {
                $the_new_src = [];
                $url = str_replace("\\\"", "", $img->getAttribute("src"));
                $media_url = seopic_get_base_url($url);
                $old_media_id = seopic_get_id_from_guid($media_url);
                $the_new_src["old_url"] = $media_url;
                $the_new_src["old_id"] = $old_media_id;
                $new_image_id = seopic_get_media_attachment_post_id($old_media_id);
                $new_url = get_the_guid($new_image_id);
                $new_url = seopic_add_image_size_to_url($new_url);
                $the_new_src["new_url"] = $new_url;
                $the_new_src["new_id"] = $new_image_id;
                $img->setAttribute("src", $new_url);
                $new_class = str_replace("wp-image-" . $the_new_src["old_id"], "", $img->getAttribute("class"));
                $new_class = preg_replace("/(wp-image-[0-9]{1,})/", "", $new_class);
                $img->setAttribute("class", $new_class . " wp-image-" . $the_new_src["new_id"]);
                $all_src[] = $the_new_src;
            }
        }

        foreach ($html->find('comment') as $comment) {

            // Null the figure and img variables.
            $figure = $img = null;

            // Get the next element to the comment.
            $figure = $comment->next_sibling();

            // Check if the element in container is image.
            if ($figure !== null) {
                if ($figure->tag !== "figure" and !$figure->hasClass("wp-block-cover") and !$figure->hasClass("wp-block-media-text")) {
                    continue;
                }
            }

            if ($figure !== null) {
                if ($figure->hasClass("wp-block-media-text")) {
                    foreach ($figure->children as $child) {
                        if ($child->tag === "figure") {
                            $figure = $child;
                        }
                    }
                }
                foreach ($figure->children as $child) {
                    if ($child->tag === "img") {
                        $img = $child;
                    }
                }
            }

            if ($img === null) {
                continue;
            }

            foreach ($all_src as $src) {
                if (!isset($src["new_id"])) {
                    continue;
                }
                if ($img->hasClass("wp-image-" . $src["new_id"]) !== false) {

                    // Check if the image is in cover block.
                    if (strpos($comment->innertext, "<!-- wp:cover") !== false) {
                        // Get the json inside.
                        $commentJsonTxt = trim(str_replace("-->", "", str_replace("<!-- wp:cover", "", $comment->innertext)));
                        $commentJson = json_decode($commentJsonTxt);
                        $commentJson->id = +$src["new_id"];
                        $commentJson->url = $src["new_url"];
                        $newComment = "<!-- wp:cover " . json_encode($commentJson) . " -->";
                        $comment->innertext = $newComment;
                    }

                    // Check if the image is in media text block.
                    if (strpos($comment->innertext, "<!-- wp:media-text") !== false) {
                        // Get the json inside.
                        $commentJsonTxt = trim(str_replace("-->", "", str_replace("<!-- wp:media-text", "", $comment->innertext)));
                        $commentJson = json_decode($commentJsonTxt);
                        $commentJson->mediaId = +$src["new_id"];
                        $commentJson->mediaLink = $src["new_url"];
                        $newComment = "<!-- wp:media-text " . json_encode($commentJson) . " -->";
                        $comment->innertext = $newComment;
                    }

                    // Check if the image is in image block.
                    if (strpos($comment->innertext, "<!-- wp:image") !== false) {
                        // Get the json inside.
                        $commentJsonTxt = trim(str_replace("-->", "", str_replace("<!-- wp:image", "", $comment->innertext)));
                        $commentJson = json_decode($commentJsonTxt);
                        $commentJson->id = +$src["new_id"];
                        $newComment = "<!-- wp:image " . json_encode($commentJson) . " -->";
                        $comment->innertext = $newComment;
                    }
                }
            }
        }
        return $html->__toString();
    } else {
        $media_id = seopic_get_media_attachment_post_id($content);
        $time = get_the_date("Y-m", $media_id);
        $media_url = get_the_guid($media_id);

        if ($post_id !== 0 and get_post_status($post_id) !== "inherit") {
            seopic_is_the_same_alt($post_id, $media_id, $alt);
            $new_url = seopic_generate_media_copy($post_id, $media_url, $time, $media_id, $alt);
        }

        return $new_url;
    }
}