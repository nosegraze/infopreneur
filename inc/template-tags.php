<?php
/**
 * Template Tags
 *
 * Functions used within the template files.
 *
 * @package   infopreneur
 * @copyright Copyright (c) 2016, Nose Graze Ltd.
 * @license   GPL2+
 */

if ( ! function_exists( 'infopreneur_navigation' ) ) {
	/**
	 * Navigation Menu
	 *
	 * Displays a navigation menu with surrounding markup.
	 *
	 * @param int   $number Which number navigation to display.
	 * @param array $args   Arguments to override the defaults.
	 *
	 * @hooks :
	 *       `infopreneur/navigation/before`
	 *       `infopreneur/navigation/after`
	 * Use these hooks to add extra content before/after the navigation.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function infopreneur_navigation( $id = 'menu_1', $args = array() ) {
		// If this menu isn't set - bail.
		if ( ! has_nav_menu( $id ) ) {
			return;
		}

		$args = wp_parse_args( $args, array(
			'container'      => false,
			'theme_location' => $id,
			'menu_id'        => 'menu-' . $id
		) );

		do_action( 'infopreneur/navigation/before', $id );
		?>
		<nav id="site-navigation-<?php esc_attr_e( $id ); ?>" class="navigation" role="navigation">
			<button class="layout-toggle" aria-controls="menu-<?php esc_attr_e( $id ); ?>" aria-expanded="false"><?php esc_html_e( 'Menu', 'infopreneur' ); ?></button>
			<?php wp_nav_menu( $args ); ?>
		</nav>
		<?php
		do_action( 'infopreneur/navigation/after', $id );
	}
}

if ( ! function_exists( 'infopreneur_maybe_show_sidebar' ) ) {
	/**
	 * Maybe Show Sidebar
	 *
	 * Sidebar is included if it's turned on in the settings panel.
	 *
	 * @uses  infopreneur_get_current_view()
	 *
	 * @param string $location
	 *
	 * @since 1.0
	 * @return void
	 */
	function infopreneur_maybe_show_sidebar( $location = 'right' ) {
		// Get the view.
		$view = infopreneur_get_current_view();

		// Get the option in the Customizer.
		$show_sidebar = get_theme_mod( 'sidebar_' . $location . '_' . $view, Infopreneur_Customizer::defaults( 'sidebar_' . $location . '_' . $view ) );

		if ( $show_sidebar ) {
			get_sidebar( $location );
		}
	}
}

/**
 * Get Featured Image
 *
 * Retrieves the thumbnail for a given post using the following priorities:
 *
 *      1) Featured image
 *      2) UBB book cover image
 *      3) First image in the post text
 *
 * @param array $args Arguments to override the defaults.
 *
 * @since 1.0
 * @return bool|string False if no image is found
 */
function infopreneur_get_post_thumbnail( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'width'     => 700,
		'height'    => 400,
		'crop'      => true,
		'class'     => 'post-thumbnail',
		'alignment' => get_theme_mod( 'thumbnail_align', Infopreneur_Customizer::defaults( 'thumbnail_align' ) ),
		'post'      => null
	) );

	if ( empty( $args['post'] ) ) {
		$args['post'] = get_post();
	}

	// Add alignmcne to class.
	$args['class'] .= ' ' . sanitize_html_class( $args['alignment'] );

	if ( $args['alignment'] != 'aligncenter' ) {
		$args['width']  = 500;
		$args['height'] = 400;
	}

	// Now allow those args to be filtered.
	$args = apply_filters( 'infopreneur/post-thumbnail/args', $args );

	$image_url = '';

	// Pre-emptively check to see if an UBB book cover exists.
	$ubb_book_cover = get_post_meta( $args['post']->ID, '_ubb_book_image', true );

	// Pre-emptively get the featured image.
	$featured = wp_get_attachment_image_src( get_post_thumbnail_id( $args['post']->ID ), 'full' );

	// Now let's try to get an image URL!
	if ( has_post_thumbnail( $args['post']->ID ) && ! empty( $featured ) ) {
		$image_url = $featured[0];
	} elseif ( ! empty( $ubb_book_cover ) ) {
		$image_url = $ubb_book_cover;
	} elseif ( apply_filters( 'infopreneur/post-thumbnail/auto-grab-first-image', true ) && preg_match_all( '|<img.*?src=[\'"](.*?)[\'"].*?>|i', $args['post']->post_content, $matches ) ) {
		$image_url = $matches[1][0];
	}

	// If we don't have an image, bail.
	if ( empty( $image_url ) ) {
		return false;
	}

	// Now let's resize the image, woot woot..
	$resized_image = false;

	// If Photon is activated, we'll try to use that first.
	if ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'photon' ) ) {
		$args = array(
			'resize' => array( absint( $args['width'] ), absint( $args['height'] ) )
		);

		$resized_image = jetpack_photon_url( $image_url, $args );
	} elseif ( function_exists( 'aq_resize' ) ) {
		// Otherwise, we'll use aq_resizer.
		$resized_image = aq_resize( $image_url, absint( $args['width'] ), absint( $args['height'] ), $args['crop'], true, true );
	}

	$final_image = $resized_image ? $resized_image : $image_url;

	$final_html = '<a href="' . esc_url( get_permalink( $args['post'] ) ) . '" title="' . esc_attr( strip_tags( get_the_title( $args['post'] ) ) ) . '"><img src="' . esc_url( apply_filters( 'infopreneur/post-thumbnail/final-url', $final_image, $args ) ) . '" alt="' . esc_attr( strip_tags( get_the_title( $args['post'] ) ) ) . '" class="' . esc_attr( $args['class'] ) . '" width="' . esc_attr( $args['width'] ) . '" height="' . esc_attr( $args['height'] ) . '"></a>';

	return apply_filters( 'infopreneur/post-thumbnail/final-html', $final_html, $args );

}

/**
 * Post Meta
 *
 * Converts the Customizer template into real, dynamic values.
 *
 * @since 1.0
 * @return void
 */
function infopreneur_entry_meta( $mod_name = '' ) {
	$template = get_theme_mod( $mod_name, Infopreneur_Customizer::defaults( $mod_name ) );

	$time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
	if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
		$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated" datetime="%3$s">%4$s</time>';
	}
	$time_string = sprintf( $time_string,
		esc_attr( get_the_date( 'c' ) ),
		esc_html( get_the_date() ),
		esc_attr( get_the_modified_date( 'c' ) ),
		esc_html( get_the_modified_date() )
	);

	$find    = array(
		'[date]',
		'[author]',
		'[category]',
		'[comments]'
	);
	$replace = array(
		'<span class="entry-date">' . $time_string . '</span>',
		'<span class="entry-author">' . get_the_author() . '</span>',
		'<span class="entry-category">' . get_the_category_list( ', ' ) . '</span>'
	);

	// It takes some more work to get the comments number...
	$num_comments   = get_comments_number(); // get_comments_number returns only a numeric value
	$write_comments = '';

	if ( comments_open() ) {
		if ( $num_comments == 0 ) {
			$comments = __( 'Leave a Comment', 'infopreneur' );
		} elseif ( $num_comments > 1 ) {
			$comments = sprintf( __( '%s Comments', 'infopreneur' ), $num_comments );
		} else {
			$comments = __( '1 Comment', 'infopreneur' );
		}
		$write_comments = '<a href="' . esc_url( get_comments_link() ) . '" class="entry-comments">' . $comments . '</a>';
	}

	$replace[] = $write_comments;

	do_action( 'infopreneur/before-post-meta', get_post() );
	?>
	<div class="entry-meta">
		<?php echo str_replace( $find, $replace, $template ); ?>
	</div>
	<?php
	do_action( 'infopreneur/after-post-meta', get_post() );
}

/**
 * Post Footer
 *
 * Displays the list of tags.
 *
 * @since 1.0
 * @return void
 */
function infopreneur_entry_footer() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	?>
	<footer class="entry-footer">
		<?php the_tags( '<span class="post-tags"><i class="fa fa-tags"></i> ', ', ', '</span>' ); ?>
	</footer>
	<?php
}

/**
 * Categorized Blog
 *
 * Returns true if a blog has more than one category.
 *
 * @since 1.0.0
 * @return bool
 */
function infopreneur_categorized_blog() {
	if ( false === ( $all_the_cool_cats = get_transient( 'infopreneur_categories' ) ) ) {
		// Create an array of all the categories that are attached to posts.
		$all_the_cool_cats = get_categories( array(
			'fields'     => 'ids',
			'hide_empty' => 1,
			'number'     => 2,
		) );

		// Count the number of categories that are attached to the posts.
		$all_the_cool_cats = count( $all_the_cool_cats );

		set_transient( 'infopreneur_categories', $all_the_cool_cats );
	}

	if ( $all_the_cool_cats > 1 ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Copyright Text
 *
 * Displays the footer copyright text, as specified in the settings panel.
 *
 * @since 1.0.0
 * @return string
 */
function infopreneur_get_copyright_message() {
	$find    = array(
		'[site-url]',
		'[site-title]',
		'[current-year]',
	);
	$replace = array(
		get_bloginfo( 'url' ),
		get_bloginfo( 'name' ),
		date( 'Y' ),
	);

	return str_replace( $find, $replace, get_theme_mod( 'copyright_message', Infopreneur_Customizer::defaults( 'copyright_message' ) ) );
}

/**
 * Theme URI
 *
 * Gets the URL to the theme's product page with the affiliate ID
 * appended, if entered.
 *
 * @since 1.0.0
 * @return string
 */
function infopreneur_theme_uri() {
	return apply_filters( 'infopreneur/theme-uri', '#' );
}