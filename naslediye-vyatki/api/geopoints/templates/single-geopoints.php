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
		<?php echo wp_kses_post(blocksy_sidebar_position_attr()); ?>
		<?php echo $data_container_output; ?>
		<?php echo blocksy_get_v_spacing() ?>>

		<?php do_action('blocksy:single:container:top'); ?>

			<style>
				.ct-container-full {
					padding-top: 0;
				}
			</style>
		<?php
			$coord = esc_html( get_post_meta( get_the_ID(), 'geopoints_coord', true ) );
			echo do_shortcode("[yamap center='{$coord}' height='20rem' zoom='16' type='yandex#map' controls='typeSelector;zoomControl']
										 [yaplacemark coord='{$coord}' icon='islands' color='green'][/yamap]");
		?>
			<br>
		<?php
			$geopoints_pages = get_post_meta($post->ID, 'geopoints_pages', true);
			$post_content = $out = "";
			if ($geopoints_pages){
				$all_articles = get_posts(['include'=>$geopoints_pages]);
				$articles = [];
				foreach ($all_articles as $article) $articles[$article->ID] = esc_html($article->post_title);
				foreach($articles as $id=>$article) $post_content .= '<li><a href="../../?p='.$id.'">' . $article . '</a></li>';
			}
			$pre_content = get_post_meta($post->ID, 'address', true);

			$content = get_the_content(null, null, $post->ID);

			if ($pre_content)  $out .= "<p><b>Адрес:</b> " . $pre_content . "</p>";
			if ($content) $out .= "<p>" . $content . "</p>";
			if ($post_content) $out .= "<h6>Связанные статьи</h6><ul>" . $post_content . '</ul>';
			$out .= '<br><center><button type="submit" class="submit" onclick="location.href=`../../map/#/point/'.$post->ID.'`">На общую карту</button></center>';


			// Добавляем плашку с данными таксономии Руководитель
			$headman_terms = get_the_terms($post->ID, 'headman');
			if ($headman_terms && !is_wp_error($headman_terms)) {
				$out .= '<div class="headman-info" style="margin-top: 30px; padding: 20px; width: 100%; display: flex; flex-direction: column; align-items: center;">';
				
				foreach ($headman_terms as $term) {
					// Получаем thumbnail (если есть)
					$thumbnail_id = get_term_meta($term->term_id, 'headman-image-id', true);
					if ($thumbnail_id) {
						$image = wp_get_attachment_image($thumbnail_id, 'thumbnail', false, array('style' => 'border-radius:50%; margin-bottom:10px;'));
						$out .= '<a href="../../headman/'. $term->slug .'">'.$image.'</a>';
					}
					
					$out .= 'Руководитель <b>' . esc_html($term->name) . '</b>';
					$description = term_description($term->term_id, 'headman');
					if ($description) {
						$out .= wpautop($description);
					}
				}
				
				$out .= '</div>';
			}


			echo blocksy_single_content($out);

		?>

		<?php get_sidebar(); ?>

		<?php do_action('blocksy:single:container:bottom'); ?>
	</div>

<?php

blocksy_display_page_elements('separated');

have_posts();
wp_reset_query();

get_footer();

