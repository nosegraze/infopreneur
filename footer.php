<?php
/**
 * Footer
 *
 * The template for displaying the footer.
 * Contains the closing of the #content div and all content after.
 *
 * @package   infopreneur
 * @copyright Copyright (c) 2016, Nose Graze Ltd.
 * @license   GPL2+
 */

do_action( 'infopreneur/inside-content/bottom' ); ?>

</div><!-- #content -->

<?php
if ( apply_filters( 'infopreneur/show-footer', ! infopreneur_is_plain_page() ) ) :
	/**
	 * Above footer widget area.
	 */
	if ( apply_filters( 'infopreneur/show-above-footer-widget', is_active_sidebar( 'above-footer' ) ) ) {
		?>
		<div id="above-footer-area" class="widget-area">
			<?php dynamic_sidebar( 'above-footer' ); ?>
		</div>
		<?php
	}
	?>

	<footer id="colophon" class="site-footer" role="contentinfo">
		<?php do_action( 'infopreneur/footer/before-site-info' ); ?>

		<div class="container site-info">
			<?php
			// Footer links
			infopreneur_navigation( 'menu_3', array( 'depth' => 1 ) );

			// Copyright message
			?>
			<span id="infopreneur-copyright"><?php echo infopreneur_get_copyright_message(); ?></span>

			<span id="infopreneur-credits">
			<?php
			// Theme credit link.
			printf(
				'<a href="' . esc_url( 'https://github.com/nosegraze/infopreneur' ) . '" target="_blank" rel="nofollow">%1$s</a>',
				__( 'Infopreneur Theme', 'infopreneur' )
			);

			do_action( 'infopreneur/footer/attribution' ); ?>
		</span>
		</div>

		<?php do_action( 'infopreneur/footer/after-site-info' ); ?>
	</footer>
<?php endif; ?>

</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>