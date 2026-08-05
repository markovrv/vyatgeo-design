<?php
/*
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Blocksy
 */

 get_header();

if (have_posts()) {
	the_post();
}

if (
	function_exists('blc_get_content_block_that_matches')
	&&
	blc_get_content_block_that_matches([
		'template_type' => 'single',
		'template_subtype' => 'canvas'
	])
) {
	echo blc_render_content_block(
		blc_get_content_block_that_matches([
			'template_type' => 'single',
			'template_subtype' => 'canvas'
		])
	);
	have_posts();
	wp_reset_query();
	return;
}

/**
 * Note to code reviewers: This line doesn't need to be escaped.
 * Function blocksy_output_hero_section() used here escapes the value properly.
 */
if (apply_filters('blocksy:single:has-default-hero', true)) {
	echo blocksy_output_hero_section([
		'type' => 'type-2'
	]);
}

$page_structure = blocksy_get_page_structure();

$container_class = 'ct-container-full';
$data_container_output = '';

if ($page_structure === 'none' || blocksy_post_uses_vc()) {
	$container_class = 'ct-container';

	if ($page_structure === 'narrow') {
		$container_class = 'ct-container-narrow';
	}
} else {
	$data_container_output = 'data-content="' . $page_structure . '"';
}


?>

	<div
		class="<?php echo trim($container_class) ?>"
		<?php 
			echo wp_kses_post(blocksy_sidebar_position_attr());
		    echo $data_container_output;
		    echo blocksy_get_v_spacing(); 
		?>
	>

		<?php do_action('blocksy:single:container:top'); ?>

			<style>
				.ct-container-full {
					padding-top: 0;
				}
			</style>
		<?php
			$coord = esc_html( get_post_meta( get_the_ID(), 'attraction_coord', true ) );
			$name = esc_html( get_post_meta( get_the_ID(), 'attraction_place', true ) );
			echo do_shortcode("[yamap center='{$coord}' height='20rem' zoom='16' type='yandex#map' controls='typeSelector;zoomControl' mobiledrag='0']
										 [yaplacemark coord='{$coord}' icon='islands' name='{$name}' color='green'][/yamap]");
		?>
			<br>
		<?php
			$content = apply_filters('the_content', get_the_content());
            $out = "";
			if ($name) $out .= "<p><b>Место:</b> " . $name . "</p>";
			if ($content) $out .= "<div>" . $content . "</div>";
			$out .= '<br><center><button type="submit" class="submit" onclick="location.href=`../../attraction-map/#point/'.$post->ID.'`">На общую карту</button></center>';

			echo blocksy_single_content($out);
		
		get_sidebar(); 
		
		do_action('blocksy:single:container:bottom'); ?>

	</div>

<?php

blocksy_display_page_elements('separated');

have_posts();
wp_reset_query();

get_footer();

