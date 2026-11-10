<?php
/**
 * Core functions
 *
 * @package   Thim_Core
 * @since     1.0.0
 */

/**
 * Get instance Thim_Core_Customizer.
 *
 * @return Thim_Core_Customizer
 * @since 0.1.0
 */
if ( ! function_exists( 'thim_customizer' ) ) {
	function thim_customizer() {
		return Thim_Core_Customizer::instance();
	}
}

/**
 * Trigger compile custom css theme.
 *
 * @require add_filter `tc_variables_compile_scss_theme` before call this function.
 *
 * @since   1.0.1
 */
if ( ! function_exists( 'thim_compile_custom_css_theme' ) ) {
	function thim_compile_custom_css_theme() {
		$customizer = Thim_Core_Customizer::instance();
		$customizer->after_save_customize();
	}
}

/**
 * Show entry format images, video, gallery, audio, etc.
 *
 * @return void
 */
if ( ! function_exists( 'thim_post_formats' ) ) :
	function thim_post_formats( $size = 'full' ) {
		if ( empty( $size ) || is_array( $size ) ) {
			$size = 'full';
		}

		$html = '';
		switch ( get_post_format() ) {
			case 'image':
				$image_id = get_post_meta( get_the_ID(), 'thim_image', true );

				if ( $image_id ) {
					$image = wp_get_attachment_image_url( $image_id, $size );
				} else {
					$image = thim_get_image(
						array(
							'size'   => $size,
							'format' => 'src',
							'echo'   => false,
						)
					);
				}
				if ( ! $image ) {
					break;
				}

				if ( is_single() ) {
					$html .= sprintf( '<img src="%2$s" alt="%1$s">', esc_attr( the_title_attribute( 'echo=0' ) ), $image );
				} else {
					$html .= sprintf( '<a class="post-image" href="%1$s" title="%2$s"><img src="%3$s" alt="%2$s"></a>', esc_url( get_permalink() ), esc_attr( the_title_attribute( 'echo=0' ) ), $image );
				}

				$html = apply_filters( 'thim_post_format_image_html', $html, $image );

				break;
			case 'gallery':
				$images = thim_meta( 'thim_gallery', "type=image&single=false&size=$size" );
				if ( empty( $images ) ) {
					break;
				}

				$html .= '<div class="flexslider">';
				$html .= '<ul class="slides">';
				foreach ( $images as $key => $image ) {
					if ( ! empty( $image['url'] ) ) {
						if ( is_single() ) {
							$html .= sprintf( '<li><span class="hover-gradient"><img src="%s" alt="gallery"></span></li>', esc_url( $image['url'] ) );
						} else {
							$html .= sprintf( '<li><a href="%s" class="hover-gradient"><img src="%s" alt="gallery"></a></li>', esc_url( get_permalink() ), esc_url( $image['url'] ) );
						}
					}
				}
				$html .= '</ul>';
				$html .= '</div>';

				$html = apply_filters( 'thim_post_format_gallery_html', $html, $images );
				break;
			case 'audio':
				$audio = thim_meta( 'thim_audio' );
				if ( ! $audio ) {
					break;
				}

				if ( filter_var( $audio, FILTER_VALIDATE_URL ) ) {
					$oembed = wp_oembed_get( $audio );
					if ( $oembed ) {
						$html .= $oembed;
					}
				} else {
					$html .= $audio;
				}

				$html = apply_filters( 'thim_post_format_audio_html', $html, $audio );
				break;
			case 'video':
				$video = thim_meta( 'thim_video' );
				if ( ! $video ) {
					break;
				}

				// If URL: show oEmbed HTML
				if ( filter_var( $video, FILTER_VALIDATE_URL ) ) {
					$oembed = wp_oembed_get( $video );
					if ( $oembed ) {
						$html .= $oembed;
					}
				} else {
					// If embed code: sanitize before display
					$html .= $video;
				}

				$html = apply_filters( 'thim_post_format_video_html', $html, $video );
				break;
			default:
				$thumb = get_the_post_thumbnail( get_the_ID(), $size );
				if ( empty( $thumb ) ) {
					return;
				}
				
				if ( is_single() ) {
					$html .= $thumb;
				} else {
					$html .= '<a class="post-image" href="' . esc_url( get_permalink() ) . '">' . $thumb . '</a>';
				}

				$html = apply_filters( 'thim_post_format_thumbnail_html', $html, $thumb );
		}
		if ( $html ) {
			echo "<div class='post-formats-wrapper'>$html</div>";
		}
	}
endif;
add_action( 'thim_entry_top', 'thim_post_formats', 10, 1 );


/**
 * Get image features
 *
 * @param $args
 *
 * @return array|void
 */
if ( ! function_exists( 'thim_get_image' ) ) {
	function thim_get_image( $args = array() ) {
		$default = apply_filters(
			'thim_get_image_default_args',
			array(
				'post_id'  => get_the_ID(),
				'size'     => 'thumbnail',
				'format'   => 'html', // html or src
				'attr'     => '',
				'meta_key' => '',
				'scan'     => true,
				'default'  => '',
				'echo'     => true,
			)
		);

		$args = wp_parse_args( $args, $default );

		if ( ! $args['post_id'] ) {
			$args['post_id'] = get_the_ID();
		}

		// Get image from cache
		$key         = md5( serialize( $args ) );
		$image_cache = wp_cache_get( $args['post_id'], 'thim_get_image' );

		if ( ! is_array( $image_cache ) ) {
			$image_cache = array();
		}

		if ( empty( $image_cache[ $key ] ) ) {
			// Get post thumbnail
			if ( has_post_thumbnail( $args['post_id'] ) ) {
				$id          = get_post_thumbnail_id();
				$html        = wp_get_attachment_image( $id, $args['size'], false, $args['attr'] );
				list( $src ) = wp_get_attachment_image_src( $id, $args['size'], false, $args['attr'] );
			}

			// Get the first image in the custom field
			if ( ! isset( $html, $src ) && $args['meta_key'] ) {
				$id = get_post_meta( $args['post_id'], $args['meta_key'], true );

				// Check if this post has attached images
				if ( $id ) {
					$html        = wp_get_attachment_image( $id, $args['size'], false, $args['attr'] );
					list( $src ) = wp_get_attachment_image_src( $id, $args['size'], false, $args['attr'] );
				}
			}

			// Get the first attached image
			if ( ! isset( $html, $src ) ) {
				$image_ids = array_keys(
					get_children(
						array(
							'post_parent'    => $args['post_id'],
							'post_type'      => 'attachment',
							'post_mime_type' => 'image',
							'orderby'        => 'menu_order',
							'order'          => 'ASC',
						)
					)
				);

				// Check if this post has attached images
				if ( ! empty( $image_ids ) ) {
					$id          = $image_ids[0];
					$html        = wp_get_attachment_image( $id, $args['size'], false, $args['attr'] );
					list( $src ) = wp_get_attachment_image_src( $id, $args['size'], false, $args['attr'] );
				}
			}

			// Get the first image in the post content
			if ( ! isset( $html, $src ) && ( $args['scan'] ) ) {
				preg_match(
					'|<img.*?src=[\'"](.*?)[\'"].*?>|i',
					get_post_field( 'post_content', $args['post_id'] ),
					$matches
				);

				if ( ! empty( $matches ) ) {
					$html = $matches[0];
					$src  = $matches[1];
				}
			}

			// Use default when nothing found
			if ( ! isset( $html, $src ) && ! empty( $args['default'] ) ) {
				if ( is_array( $args['default'] ) ) {
					$html = @$args['html'];
					$src  = @$args['src'];
				} else {
					$html = $src = $args['default'];
				}
			}

			// Still no images found?
			if ( ! isset( $html, $src ) ) {
				return false;
			}

			$output = 'html' === strtolower( $args['format'] ) ? $html : $src;

			$image_cache[ $key ] = $output;
			wp_cache_set( $args['post_id'], $image_cache, 'thim_get_image' );
		} // If image already cached
		else {
			$output = $image_cache[ $key ];
		}

		$output = apply_filters( 'thim_get_image', $output, $args );

		if ( ! $args['echo'] ) {
			return $output;
		}

		echo ent2ncr( $output );
	}
}

/**
 * Get post meta
 *
 * @param $key
 * @param $args
 * @param $post_id
 *
 * @return string
 * @return bool
 */
if ( ! function_exists( 'thim_meta' ) ) {
	function thim_meta( $key, $args = array(), $post_id = null ) {
		$post_id = empty( $post_id ) ? get_the_ID() : $post_id;

		$args = wp_parse_args(
			$args,
			array(
				'type' => 'text',
			)
		);

		// Image
		if ( $args['type'] === 'image' ) {
			if ( isset( $args['single'] ) && $args['single'] == 'false' ) {
				// Gallery
				$temp          = array();
				$data          = array();
				$attachment_id = get_post_meta( $post_id, $key, false );
				if ( ! $attachment_id ) {
					return $data;
				}

				if ( empty( $attachment_id ) ) {
					return $data;
				}

				foreach ( $attachment_id as $k => $v ) {
					$image_attributes = wp_get_attachment_image_src( $v, $args['size'] );

					if ( $image_attributes ) {
						$temp['url'] = $image_attributes[0];
						$data[]      = $temp;
					}
				}

				return $data;
			} else {
				// Single Image
				$attachment_id    = get_post_meta( $post_id, $key, true );
				$image_attributes = wp_get_attachment_image_src( $attachment_id, $args['size'] );

				return $image_attributes;
			}
		}

		return get_post_meta( $post_id, $key, $args );
	}
}

/**
 * Get page id by path. If not found return false.
 *
 * @param $page_slug
 *
 * @return bool|int
 * @since 0.5.0
 *
 */
if ( ! function_exists( 'thim_get_page_id_by_path' ) ) {
	function thim_get_page_id_by_path( $page_slug ) {
		$page = get_page_by_path( $page_slug );

		if ( $page ) {
			return $page->ID;
		}

		return false;
	}
}

/**
 * Add log.
 *
 * @param        $message
 * @param string $handle
 * @param bool   $clear
 *
 * @since 0.8.3
 *
 */
if ( ! function_exists( 'thim_add_log' ) ) {
	function thim_add_log( $message, $handle = 'log', $clear = false ) {
		if ( ! TP::is_debug() ) {
			return;
		}

		if ( version_compare( phpversion(), '5.6', '<' ) ) {
			return;
		}

		$thim_log = Thim_Logger::instance();
		$thim_log->add( $message, $handle, $clear );
	}
}

/**
 * let_to_num function.
 *
 * This function transforms the php.ini notation for numbers (like '2M') to an integer.
 *
 * @param $size
 *
 * @return int
 * @since 1.1.1
 *
 */
function thim_core_let_to_num( $size ) {
	$l   = substr( $size, - 1 );
	$ret = substr( $size, 0, - 1 );
	switch ( strtoupper( $l ) ) {
		case 'P':
			$ret *= 1024; // fall-through intentional
		case 'T':
			$ret *= 1024; // fall-through intentional
		case 'G':
			$ret *= 1024; // fall-through intentional
		case 'M':
			$ret *= 1024; // fall-through intentional
		case 'K':
			$ret *= 1024;
	}

	return $ret;
}

add_action( 'after_setup_theme', 'thim_core_add_option_disale_gutenberg' );
function thim_core_add_option_disale_gutenberg() {
	global $wp_version;
	if ( $wp_version >= 5.8 ) {
		thim_customizer()->add_section(
			array(
				'id'       => 'advanced',
				'panel'    => 'general',
				'priority' => 90,
				'title'    => esc_html__( 'Extra Features', 'thim-core' ),
			)
		);
		thim_customizer()->add_field(
			array(
				'id'       => 'thim_enable_gutenberg_widget_editor',
				'type'     => 'switch',
				'label'    => esc_html__( 'Enable Gutenberg widget editor', 'thim-core' ),
				'section'  => 'advanced',
				'default'  => false,
				'priority' => 1,
				'choices'  => array(
					true  => esc_html__( 'On', 'thim-core' ),
					false => esc_html__( 'Off', 'thim-core' ),
				),
			)
		);
	}
}

if ( get_theme_mod( 'thim_enable_gutenberg_widget_editor', 0 ) == '0' ) {
	// Disables the block editor from managing widgets in the Gutenberg plugin.
	add_filter( 'gutenberg_use_widgets_block_editor', '__return_false' );
	// Disables the block editor from managing widgets.
	add_filter( 'use_widgets_block_editor', '__return_false' );
}
/**
 * thim_core_breadcrumbs
 *
 * @author  tuanta
 * @since   2.1.2
 * @version 1.0.0
 */
if ( ! function_exists( 'thim_core_breadcrumbs' ) ) {

	/**
	 * Output the Thim Breadcrumb.
	 *
	 */
	function thim_core_breadcrumbs( $args = array() ) {
		$show_url = false;
		$args     = wp_parse_args(
			$args,
			apply_filters(
				'thim_breadcrumb_defaults',
				array(
					'delimiter'   => '',
					'wrap_before' => '<ul class="breadcrumbs" id="breadcrumbs">',
					'wrap_after'  => '</ul>',
					'before'      => '<li>',
					'after'       => '</li>',
				)
			)
		);

		$breadcrumbs = new ThimCoreBreadcrumbs\Thim_Breadcrumbs();

		$breadcrumb = $breadcrumbs->render();
		if ( ! empty( $breadcrumb ) ) {
			echo $args['wrap_before'];

			foreach ( $breadcrumb as $key => $crumb ) {
				if ( is_single() && apply_filters( 'breadcrumbs_hide_single_title', false ) ) {
					$show_url = true;
				}

				echo $args['before'];

				if ( ( ! empty( $crumb[1] ) && sizeof( $breadcrumb ) !== $key + 1 ) || $show_url ) {
					echo '<a href="' . esc_url( $crumb[1] ) . '">' . esc_html( $crumb[0] ) . '</a>';
				} else {
					echo esc_html( $crumb[0] );
				}

				echo $args['after'];
				if ( sizeof( $breadcrumb ) !== $key + 1 ) {
					echo $args['delimiter'];
				}
			}

			echo $args['wrap_after'];
		}
	}
}

add_action( 'thim_breadcrumbs', 'thim_core_breadcrumbs', 5 );
if ( TP::theme_version_compare( 'Eduma', '5.1.2', '=' ) ) {
	if ( ! function_exists( 'thim_breadcrumbs' ) ) {
		function thim_breadcrumbs( $args = array() ) {
			thim_core_breadcrumbs();
		}
	}
}
