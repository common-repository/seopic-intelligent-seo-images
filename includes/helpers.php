<?php

/**
 * Add correct image size to url.
 * This function uses $seopic_image_size global variable.
 *
 * @param string $url
 * @return string
 * @return string the url after adding the size
 *
 * @since 1.0.0
 */
if (!function_exists("seopic_add_image_size_to_url")) {
    function seopic_add_image_size_to_url($url)
    {
        global $seopic_image_size;

        if ($seopic_image_size === "") return $url;

        $explode = explode(".", $url);
        $ext = $explode[count($explode) - 1];

        $new_url = "";
        $explode_count = count($explode);

        for ($i = 0; $i < $explode_count; $i++) {
            if ($i === ($explode_count - 1)) {
                $new_url .= "-" . $seopic_image_size;
            }
            if ($i === 0) {
                $new_url .= $explode[$i];
            } else {
                $new_url .= "." . $explode[$i];
            }
        }

        return $new_url;
    }
}

/**
 * Get the base url without size of image.
 * This function uses $seopic_image_size global variable.
 *
 * @param string $url
 * @return string the url without the size or with "-scaled" size
 *
 * @since 1.0.0
 */
if (!function_exists("seopic_get_base_url")) {
    function seopic_get_base_url($url)
    {
        global $seopic_image_size;
        $seopic_image_size = "";
        $media_url = $url;

        if (($attachment_id = seopic_get_id_from_guid($url)) === 0) {

            // Get first string.
            if (substr($url, 0, 1) === "-") {
                $firstchar = "-";
            } else {
                $firstchar = "";
            }

            $explode = explode(".", $url);
            $extension = $explode[count($explode) - 1];
            $explode2 = explode("-", $url);
            $media_url = $firstchar;

            for ($i = 0; $i < count($explode2) - 1; $i++) {
                $media_url .= $explode2[$i];

                if ($i !== count($explode2) - 2) {
                    $media_url .= "-";
                }
            }

            $media_url_without_extension = $media_url;
            $media_url .= "." . $extension;
            $seopic_image_size_extension = $explode2[count($explode2) - 1];

            // Remove extension from image size extension.
            $seopic_image_size = str_replace("." . $extension, "", $seopic_image_size_extension);
        }

        if (seopic_get_id_from_guid($media_url) === 0) {
            $scaled_image_url = $media_url_without_extension . "-scaled." . $extension;
            if (attachment_url_to_postid($scaled_image_url) === 0) {
                return false;
            } else {
                $seopic_image_size = "scaled";
                return $scaled_image_url;
            }
        }

        return $media_url;
    }
}

/**
 * Retrieves the media elements copies.
 *
 * @param int $post_id
 * @param int $media_id for main image id
 * @return int|false
 * @return int image id
 * @return false when no copies of image found
 *
 * @since 1.0.0
 */
if (!function_exists("seopic_get_media_copy")) {
    function seopic_get_media_copy($post_id, $media_id, $alt)
    {
        $media_id = seopic_get_media_attachment_post_id($media_id);
        $media_copies = get_post_meta($post_id, "_seopic_current_media_copies", true);

        if ($media_copies === "") {
            return false;
        }

        // Search for main image id in meta data.
        foreach ($media_copies as $copy) {
            if ((+$copy["original_attachment_id"] === +$media_id || +$copy["new_attachment_id"] === +$media_id) && $copy["alt"] === $alt) {
                return $copy["new_attachment_id"];
            }
        }

        return false;
    }
}

/**
 * Update media attachment ID with current media copy.
 *
 * @param int $post_id
 * @param int $original_attachment_id : the id of old attachment
 * @param int $new_attachment_id : the id of new attachment
 * @param string $alt
 * @return void
 *
 * @since 1.0.0
 */
function seopic_update_media_attachment($post_id, $attachment_id, $new_attachment_id, $alt)
{
    $current_attachment = get_post_meta($post_id, "_seopic_current_media_copies", true);

    if (seopic_get_media_copy($post_id, $attachment_id, $alt) === false) {
        if ($current_attachment === "") {
            $arr = [
                [
                    "original_attachment_id" => $attachment_id, "new_attachment_id" => $new_attachment_id,
                    "alt" => $alt
                ]
            ];
            update_post_meta($post_id, "_seopic_current_media_copies", $arr);
        } else {
            $current_attachment[] = [
                "original_attachment_id" => $attachment_id, "new_attachment_id" => $new_attachment_id,
                "alt" => $alt
            ];
            update_post_meta($post_id, "_seopic_current_media_copies", $current_attachment);
        }
    }
}

/**
 * Generate all image sizes and create attachment.
 *
 * @param string $upload_dir the directory to save the media
 * @param int $post_id
 * @param int $insert_attachment
 * @param string $time
 * @return int|WP_Error
 * @return int the new attachment id
 * @return WP_Error on failure
 *
 * @since 1.0.0
 */
if (!function_exists("seopic_generate_media_files")) {
    function seopic_generate_media_files($upload_dir, $post_id, $attachment_id, $time)
    {
        global $wpdb;

        // Path to media files in upload directory.
        $filename = $upload_dir;

        // The ID of the post this media is attached to.
        $attached_post_id = seopic_get_media_attachment_post_id($attachment_id);

        // Check the type of file. We'll use this as the 'post_mime_type'.
        $filetype = wp_check_filetype(basename($filename), null);

        // Get the path to upload directory.
        $wp_upload_dir = wp_upload_dir($time);

        // Prepare an array of post data for the attachment.
        $attachment = array(
            'guid' => $wp_upload_dir['url'] . '/' . basename($filename),
            'post_mime_type' => $filetype['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        // Insert the attachment.
        $attach_id = wp_insert_attachment($attachment, $filename, $attached_post_id);

        // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Generate the metadata for the attachment, and update the database record.
        $attachment_data = wp_generate_attachment_metadata($attach_id, $filename);

        wp_update_attachment_metadata($attach_id, $attachment_data);

        // Change the image status as draft so it is not repeated in media.
        if (is_numeric($attach_id)) {
            $wpdb->query("UPDATE $wpdb->posts SET `post_status`='draft' WHERE id='{$attach_id}'");
        }

        return $attach_id;
    }
}

// Filter post content after saving the post.
add_filter("wp_insert_post_data", "seopic_filter_post_content", 10, 3);

/**
 * Generate media copy.
 *
 * @param int $post_id
 * @param string $media_url the url of the image to be copied
 * @param string $time yyyy-mm
 * @param int $insert_attachment the attachment id needed to be copied
 * @param string $alt
 * @return string the new url
 *
 * @since 1.0.0
 */
function seopic_generate_media_copy($post_id, $media_url, $time, $attachment_id, $alt)
{
    $dirname = wp_upload_dir($time)["path"] . "/";
    $urlname = wp_upload_dir($time)["url"] . "/";
    $explode = explode(".", $media_url);
    $extension = $explode[count($explode) - 1];
    while (($new_image_name = seopic_generate_media_name($attachment_id, $dirname, $extension, $alt)) === false) {
    }

    $media_copy = seopic_get_media_copy($post_id, $attachment_id, $alt);

    if ($media_copy !== false) {
        return get_the_guid($media_copy);
    }

    $insert_attachment = seopic_get_media_attachment_post_id($attachment_id);
    $explode = explode(".", $media_url);
    $extension = $explode[count($explode) - 1];
    $new_src = $dirname . $new_image_name . "." . $extension;
    $new_url = $urlname . $new_image_name . "." . $extension;
    copy($media_url, $new_src);
    $new_attachment_id = seopic_generate_media_files($new_src, $post_id, $attachment_id, $time);
    seopic_update_media_attachment($post_id, $attachment_id, $new_attachment_id, $alt);

    return $new_url;
}

/**
 * Generate media name.
 *
 * @param int $old_media_id the original image id
 * @param string $dir the directory to the image
 * @param string $extension image extension
 * @param string $alt alternate text
 * @return false|string
 * @return false if the image name exists
 * @return string the new image name
 *
 * @since 1.0.0
 */
if (!function_exists("seopic_generate_media_name")) {
    function seopic_generate_media_name($old_media_id, $dir, $extension, $alt)
    {
        $alt = remove_accents($alt);
        $alt = trim($alt);
        $alt = str_replace(" ", "-", $alt);
        $alt = str_replace(array('\\', '/', ':', '*', '?', '"', '<', '>', '|', "."), '', $alt);
        $media_name = $alt . "-" . seopic_generate_random_string();
        $media_name = strtolower($media_name);
        $media_meta = (get_post_meta($old_media_id, "_wp_attachment_metadata"));
        $new_src = $dir . $media_name . "." . $extension;

        if (file_exists($new_src)) {
            return false;
        }

        foreach ($media_meta[0]["sizes"] as $size) {
            $new_src = $dir . $media_name . "-" . $size["width"] . "x" . $size["height"] . "." . $extension;
            if (file_exists($new_src)) {
                return false;
            }
        }

        return $media_name;
    }
}

/**
 * Returns the post id of media attachment.
 *
 * @param int $post_parent_id
 * @return int the parent id
 *
 * @since 1.0.0
 */
function seopic_get_media_attachment_post_id($attachment_id)
{
    $post_id = wp_get_post_parent_id($attachment_id);

    if (get_post_type($post_id) === "attachment") {
        return $post_id;
    }

    return $attachment_id;
}

/**
 * Generates a random string used for media file name.
 *
 * @param int $length length of chars needed
 * @return string the new string
 *
 * @since 1.0.0
 */
if (!function_exists("seopic_generate_random_string")) {
    function seopic_generate_random_string($length = 14)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $characters_length = strlen($characters);
        $random_string = '';
        for ($i = 0; $i < $length; $i++) {
            $random_string .= $characters[rand(0, $characters_length - 1)];
        }
        return $random_string;
    }
}

/**
 * Detect if the alt exists and update the database.
 *
 * @param int $post_id
 * @param int $media_id
 * @param string $alt
 * @param bool $update_meta
 * @return true|false
 *
 * @since 1.0.0
 */

function seopic_is_the_same_alt($post_id, $media_id, $alt, $update_meta = true)
{
    $media_id = seopic_get_media_attachment_post_id($media_id);
    $media_copies = get_post_meta($post_id, "_seopic_old_media_copies", true);

    if ($media_copies === "") {
        return false;
    }

    // Search for the main media id in the meta data.
    foreach ($media_copies as $copy) {
        if (+$copy["original_attachment_id"] === +$media_id || +$copy["new_attachment_id"] === +$media_id) {
            if (seopic_get_media_copy($post_id, seopic_get_media_attachment_post_id($media_id), $alt) === false) {

                if ($copy["alt"] === $alt) {
                    $current_attachment = get_post_meta($post_id, "_seopic_current_media_copies", true);

                    if (seopic_get_media_copy($post_id, $media_id, $alt) === false) {
                        if ($current_attachment === "") {
                            $arr = [
                                [
                                    "original_attachment_id" => $copy["original_attachment_id"], "new_attachment_id" => $copy["new_attachment_id"],
                                    "alt" => $alt
                                ]
                            ];
                            if ($update_meta) {
                                update_post_meta($post_id, "_seopic_current_media_copies", $arr);
                            }
                        } else {
                            $current_attachment[] = [
                                "original_attachment_id" => $copy["original_attachment_id"], "new_attachment_id" => $copy["new_attachment_id"],
                                "alt" => $alt
                            ];
                            if ($update_meta) {
                                update_post_meta($post_id, "_seopic_current_media_copies", $current_attachment);
                            }
                        }
                    }
                    return true;
                }
            }
        }
    }

    return false;
}

/**
 * Returns attachment id or post id from guid.
 *
 * @param string $guid the url
 * @return int attachment id or post id
 *
 * @since 1.0.0
 */
function seopic_get_id_from_guid($guid)
{
    global $wpdb;

    if (($id = attachment_url_to_postid($guid)) === 0) {
        $id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid=%s", $guid));
        if ($id === null) {
            return 0;
        } else {
            return $id;
        }
    } else {
        return $id;
    }
}