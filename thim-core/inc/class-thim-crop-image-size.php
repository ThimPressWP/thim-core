<?php
/**
 * Simple but effectively resizes images on the fly. Doesn't upsize, just downsizes like how WordPress likes it.
 * If the image already exists, it's served. If not, the image is resized to the specified size, saved for
 * future use, then served.
 *
 * @author  Benjamin Intal - Gambit Technologies Inc
 * @see     https://wordpress.stackexchange.com/questions/53344/how-to-generate-thumbnails-when-needed-only/124790#124790
 * @see     http://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
 */

if ( ! function_exists( 'func_otf_regen_thumbs_media_downsize' ) ) {
	/**
	 * The downsizer. This only does something if the existing image size doesn't exist yet.
	 *
	 * @param mixed        $out  Whether to short-circuit the image downsize.
	 * @param int          $id   Attachment ID.
	 * @param string|array $size Registered size name, or array( width, height, crop ).
	 *
	 * @return array|false Array of image data (url, width, height, is_intermediate), or false to bail.
	 */
	function func_otf_regen_thumbs_media_downsize( $out, $id, $size ) {
		static $is_running    = false;
		static $cropped_count = 0;

		// Respect existing short-circuit or prevent recursive filter loops.
		if ( $out || $is_running ) {
			return $out;
		}

		// Skip in WP Admin screens, but allow frontend AJAX requests (e.g., load more posts/courses).
		if ( is_admin() && ! wp_doing_ajax() ) {
			return false;
		}

		// Only handle custom array sizes: array( width, height [, crop] ).
		if ( ! is_array( $size ) || count( $size ) < 2 ) {
			return false;
		}

		$id         = (int) $id;
		$new_width  = isset( $size[0] ) ? absint( $size[0] ) : 0;
		$new_height = isset( $size[1] ) ? absint( $size[1] ) : 0;
		$crop       = array_key_exists( 2, $size ) ? (bool) $size[2] : true;

		if ( $id <= 0 || ( $new_width <= 0 && $new_height <= 0 ) ) {
			return false;
		}

		$imagedata = wp_get_attachment_metadata( $id );
		if ( ! is_array( $imagedata ) || empty( $imagedata['width'] ) || empty( $imagedata['height'] ) ) {
			return false;
		}

		$orig_w = (int) $imagedata['width'];
		$orig_h = (int) $imagedata['height'];

		// 1. Validate source dimensions before any file/disk operations:
		// When cropping ($crop = true), BOTH width and height of the original image must be >= requested size
		// (fixes the legacy `&&` bug where a narrower/shorter image was partially cropped on only one axis).
		if ( $crop ) {
			if ( ( $new_width > 0 && $orig_w < $new_width ) || ( $new_height > 0 && $orig_h < $new_height ) ) {
				return false;
			}
			if ( $orig_w === $new_width && $orig_h === $new_height ) {
				return false;
			}
		}

		// 2. Pure math dimension calculation via WP Core (0 RAM / 0 Disk I/O).
		// Replaces legacy manual aspect-ratio math and prevents calling GD/Imagick when image cannot be downsized.
		$dims = image_resize_dimensions( $orig_w, $orig_h, $new_width, $new_height, $crop );
		if ( ! $dims ) {
			return false;
		}

		$dst_w = (int) $dims[4];
		$dst_h = (int) $dims[5];

		$att_url = wp_get_attachment_url( $id );
		if ( ! $att_url ) {
			return false;
		}

		$image_path = get_attached_file( $id );
		if ( ! $image_path ) {
			return false;
		}

		$size_key     = $new_width . 'x' . $new_height;
		$checked_path = '';

		// 3. Fast path: Check attachment metadata cache first.
		if ( ! empty( $imagedata['sizes'][ $size_key ]['file'] ) ) {
			$cached_size = $imagedata['sizes'][ $size_key ];
			$cached_w    = isset( $cached_size['width'] ) ? (int) $cached_size['width'] : 0;
			$cached_h    = isset( $cached_size['height'] ) ? (int) $cached_size['height'] : 0;

			if ( $cached_w === $dst_w && $cached_h === $dst_h ) {
				$checked_path = path_join( dirname( $image_path ), $cached_size['file'] );
				if ( file_exists( $checked_path ) ) {
					return array(
						dirname( $att_url ) . '/' . $cached_size['file'],
						$cached_w,
						$cached_h,
						true,
					);
				}
			}
		}

		// Verify source file extension is a supported raster format (skip SVG, ICO, etc.).
		$info = pathinfo( $image_path );
		if ( empty( $info['extension'] ) || empty( $info['filename'] ) || empty( $info['dirname'] ) ) {
			return false;
		}

		$ext = strtolower( $info['extension'] );
		if ( ! in_array( $ext, array( 'jpg', 'jpeg', 'jpe', 'png', 'gif', 'webp', 'avif' ), true ) ) {
			return false;
		}

		// 4. Fast path: Check if the resized file already exists on disk (0 DB writes).
		$resized_filename = sprintf( '%s-%dx%d.%s', $info['filename'], $dst_w, $dst_h, $info['extension'] );
		$resized_filepath = path_join( $info['dirname'], $resized_filename );

		if ( $resized_filepath !== $checked_path && file_exists( $resized_filepath ) ) {
			return array(
				dirname( $att_url ) . '/' . $resized_filename,
				$dst_w,
				$dst_h,
				true,
			);
		}

		// 5. Server 503 protection (in-memory checks first, before filesystem stat calls):
		// - Limit heavy crops per single HTTP request (default 10 to cover standard blog archive pages).
		// - Skip on-the-fly frontend decoding for huge source images (> 3000px or > 5MB).
		// - Check available PHP memory directly (even if ini_set is allowed, LVE/PMEM limits still kill processes).
		$max_crops_per_request = (int) apply_filters( 'thim_core_max_otf_crops_per_request', 10 );
		if ( $cropped_count >= $max_crops_per_request || $orig_w > 3000 || $orig_h > 3000 ) {
			return false;
		}

		$memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
		if ( $memory_limit > 0 ) {
			$estimated_bytes = $orig_w * $orig_h * 6;
			$available_bytes = $memory_limit - memory_get_usage( true );
			if ( $available_bytes > 0 && $estimated_bytes > $available_bytes ) {
				return false;
			}
		}

		if ( ! is_file( $image_path ) || ! is_readable( $image_path ) ) {
			return false;
		}

		$filesize = @filesize( $image_path );
		if ( $filesize && $filesize > 5 * 1024 * 1024 ) {
			return false;
		}

		$is_running = true;
		++$cropped_count;

		// Prevent Imagick OpenMP thread crashes (SIGABRT / 503 on CloudLinux/LiteSpeed).
		if ( class_exists( 'Imagick' ) && method_exists( 'Imagick', 'setResourceLimit' ) ) {
			@Imagick::setResourceLimit( 6, 1 ); // 6 = Imagick::RESOURCETYPE_THREAD.
		}

		// 6. Generate intermediate image on the fly.
		$resized = image_make_intermediate_size( $image_path, $new_width, $new_height, $crop );

		$is_running = false;

		if ( ! $resized || empty( $resized['file'] ) ) {
			return false;
		}

		// Save the new size in WP attachment metadata.
		$imagedata['sizes'][ $size_key ] = $resized;
		wp_update_attachment_metadata( $id, $imagedata );

		return array(
			dirname( $att_url ) . '/' . $resized['file'],
			(int) $resized['width'],
			(int) $resized['height'],
			true,
		);
	}

	add_filter( 'image_downsize', 'func_otf_regen_thumbs_media_downsize', 20, 3 );
}
