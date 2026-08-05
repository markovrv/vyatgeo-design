<?php

add_action('widgets_init', function(){ return register_widget('lc_taxonomy'); });

class lc_taxonomy extends WP_Widget {
	function __construct() {
		parent::__construct( 'lc_taxonomy', $name = 'Список из таксономии', ['customize_selective_refresh' => false]);
	}

	function widget( $args, $instance ) {
		global $post;
		extract($args);

		// Widget options
		if ( array_key_exists( 'title', $instance ) ) { 
			$title = apply_filters('widget_title', $instance['title'] ); // Title
		} else {
			$title = '';
		}
		if ( array_key_exists( 'post_types', $instance ) ) { 
			$post_types = $instance['post_types'];
		} else {
			$post_types = 'geopoints';
		}
		if ( array_key_exists( 'taxonomy', $instance ) ) {
			$this_taxonomy = $instance['taxonomy']; // Taxonomy to show
		} else {
			$this_taxonomy = '';
		}
		$hierarchical = !empty( $instance['hierarchical'] ) ? '1' : '0';
		$inv_empty = !empty( $instance['empty'] ) ? '0' : '1'; // invert to go from UI's "show empty" to WP's "hide empty"
		$showcount = !empty( $instance['count'] ) ? '1' : '0';
		if( array_key_exists('orderby',$instance) ){
			$orderby = $instance['orderby'];
		}
		else{
			$orderby = 'count';
		}
		if( array_key_exists('ascdsc',$instance) ){
			$ascdsc = $instance['ascdsc'];
		}
		else{
			$ascdsc = 'desc';
		}
		if( array_key_exists('exclude',$instance) ){
			$exclude = $instance['exclude'];
		}
		else {
			$exclude = '';
		}
		if( array_key_exists('childof',$instance) ){
			$childof = $instance['childof'];
		}
		else {
			$childof = '';
		}
		if( array_key_exists('dropdown',$instance) ){
			$dropdown = $instance['dropdown'];
		}
		else {
			$dropdown = false;
		}
		// Dropdown doesn't work for built-in taxonomies.
		$builtin = array( 'post_tag', 'post_format', 'category' );
		if ( $dropdown && in_array( $this_taxonomy, $builtin ) ) {
			$dropdown = false;
		}
        // Output
		$tax = $this_taxonomy;
		if (!$post->post_type || in_array($post->post_type, explode(',', $post_types))) {
			echo $before_widget;
			echo '<div id="lct-widget-'.$tax.'-container" class="list-custom-taxonomy-widget">';
			if ( $title ) echo $before_title . $title . $after_title;
			if($dropdown){
				$taxonomy_object = get_taxonomy( $tax );
				if( in_array( $tax, array( 'category', 'post_tag', 'post_format' ) ) )
					$walker = '';
				else
					$walker = new lctwidget_Taxonomy_Dropdown_Walker();
				$args = array(
					'show_option_all'    => false,
					'show_option_none'   => '',
					'orderby'            => 'RANDOM()',//$orderby,
					'order'              => $ascdsc,
					'show_count'         => $showcount,
					'hide_empty'         => $inv_empty,
					'child_of'           => $childof,
					'exclude'            => $exclude,
					'echo'               => 1,
					//'selected'           => 0,
					'hierarchical'       => $hierarchical,
					'name'               => $taxonomy_object->query_var,
					'id'                 => 'lct-widget-'.$tax,
					//'class'              => 'postform',
					'depth'              => 0,
					//'tab_index'          => 0,
					'taxonomy'           => $tax,
					'hide_if_empty'      => true,
					'walker'			=> $walker,
				);
				echo '<form action="'. get_bloginfo('url'). '" method="get">';
				wp_dropdown_categories($args);
				echo '<input type="submit" value="Применить" /></form>';
			}
			else {
				$args = array(
						'show_option_all'    => false,
						'orderby'            => $orderby,
						'order'              => $ascdsc,
						'style'              => 'list',
						'show_count'         => $showcount,
						'hide_empty'         => $inv_empty,
						'use_desc_for_title' => 1,
						'child_of'           => $childof,
						//'feed'               => '',
						//'feed_type'          => '',
						//'feed_image'         => '',
						'exclude'            => $exclude,
						//'exclude_tree'       => '',
						//'include'            => '',
						'hierarchical'       => $hierarchical,
						'title_li'           => '',
						'show_option_none'   => 'Здесь пока пусто',
						'number'             => null,
						'echo'               => 1,
						'depth'              => 0,
						//'current_category'   => 0,
						//'pad_counts'         => 0,
						'taxonomy'           => $tax,
						'walker'             => null
					);
				echo '<ul id="lct-widget-'.$tax.'">';
				wp_list_categories($args);
				echo '</ul>';
			}
			echo '</div>';
			echo $after_widget;
		}
	}


	/** Widget control update */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		$instance['title']  = strip_tags( $new_instance['title'] );
		$instance['post_types']  = strip_tags( $new_instance['post_types'] );
		$instance['taxonomy'] = strip_tags( $new_instance['taxonomy'] );
		$instance['orderby'] = $new_instance['orderby'];
		$instance['ascdsc'] = $new_instance['ascdsc'];
		$instance['exclude'] = $new_instance['exclude'];
		$instance['expandoptions'] = $new_instance['expandoptions'];
		$instance['childof'] = $new_instance['childof'];
		$instance['hierarchical'] = !empty($new_instance['hierarchical']) ? 1 : 0;
		$instance['empty'] = !empty($new_instance['empty']) ? 1 : 0;
        $instance['count'] = !empty($new_instance['count']) ? 1 : 0;
        $instance['dropdown'] = !empty($new_instance['dropdown']) ? 1 : 0;

		return $instance;
	}
	
	/**
	* Widget settings
	**/
	function form( $instance ) {
		    if ( $instance ) {
				$title  = $instance['title'];
				$post_types  = $instance['post_types'];
				$this_taxonomy = $instance['taxonomy'];
				$orderby = $instance['orderby'];
				$ascdsc = $instance['ascdsc'];
				$exclude = $instance['exclude'];
				$expandoptions = $instance['expandoptions'];
				$childof = $instance['childof'];
                $showcount = isset($instance['count']) ? (bool) $instance['count'] :false;
                $hierarchical = isset( $instance['hierarchical'] ) ? (bool) $instance['hierarchical'] : false;
                $empty = isset( $instance['empty'] ) ? (bool) $instance['empty'] : false;
                $dropdown = isset( $instance['dropdown'] ) ? (bool) $instance['dropdown'] : false;
		    } else {
			    //These are our defaults
				$title  = '';
				$post_types  = 'post,page';
				$orderby  = 'count';
				$ascdsc  = 'desc';
				$exclude  = '';
				$expandoptions  = 'contract';
				$childof  = '';
				$this_taxonomy = 'category';//this will display the category taxonomy, which is used for normal, built-in posts
				$hierarchical = true;
				$showcount = true;
				$empty = false;
				$dropdown = false;
		    }
			
		// The widget form ?>
			<p>
				<label for="<?php echo $this->get_field_id('title'); ?>">Заголовок</label>
				<input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" class="widefat" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('title'); ?>">Страницы для показа</label>
				<input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('post_types'); ?>" type="text" value="<?php echo $post_types; ?>" class="widefat" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('taxonomy'); ?>">Выбор таксономии:</label>
				<select name="<?php echo $this->get_field_name('taxonomy'); ?>" id="<?php echo $this->get_field_id('taxonomy'); ?>" class="widefat" style="height: auto;" size="4">
			<?php 
			$args=array(
			  'public'   => true,
			  '_builtin' => false //these are manually added to the array later
			); 
			$output = 'names'; // or objects
			$operator = 'and'; // 'and' or 'or'
			$taxonomies=get_taxonomies($args,$output,$operator); 
			$taxonomies[] = 'category';
			$taxonomies[] = 'post_tag';
			$taxonomies[] = 'post_format';
			foreach ($taxonomies as $taxonomy ) { ?>
				<option value="<?php echo $taxonomy; ?>" <?php if( $taxonomy == $this_taxonomy ) { echo 'selected="selected"'; } ?>><?php echo $taxonomy;?></option>
			<?php }	?>
			</select>
			</p>
			<div class="lctw-all-options">
				<input type="hidden" value="<?php echo $expandoptions; ?>" id="<?php echo $this->get_field_id('expandoptions'); ?>" name="<?php echo $this->get_field_name('expandoptions'); ?>" />
				<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>"<?php checked( $showcount ); ?> />
				<label for="<?php echo $this->get_field_id('count'); ?>">Показать количество записей</label><br />
				<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('hierarchical'); ?>" name="<?php echo $this->get_field_name('hierarchical'); ?>"<?php checked( $hierarchical ); ?> />
				<label for="<?php echo $this->get_field_id('hierarchical'); ?>">Показать иерархию</label><br/>
				<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('empty'); ?>" name="<?php echo $this->get_field_name('empty'); ?>"<?php checked( $empty ); ?> />
				<label for="<?php echo $this->get_field_id('empty'); ?>">Показывать таксономии без записей</label></p>
				
				<p>
					<label for="<?php echo $this->get_field_id('orderby'); ?>">Упорядочить по:</label>
					<select name="<?php echo $this->get_field_name('orderby'); ?>" id="<?php echo $this->get_field_id('orderby'); ?>" class="widefat" >
						<option value="ID" <?php if( $orderby == 'ID' ) { echo 'selected="selected"'; } ?>>ID</option>
						<option value="name" <?php if( $orderby == 'name' ) { echo 'selected="selected"'; } ?>>Name</option>
						<option value="slug" <?php if( $orderby == 'slug' ) { echo 'selected="selected"'; } ?>>Slug</option>
						<option value="count" <?php if( $orderby == 'count' ) { echo 'selected="selected"'; } ?>>Count</option>
						<option value="term_group" <?php if( $orderby == 'term_group' ) { echo 'selected="selected"'; } ?>>Term Group</option>
					</select>
				</p>
				<p>
					<label><input type="radio" name="<?php echo $this->get_field_name('ascdsc'); ?>" value="asc" <?php if( $ascdsc == 'asc' ) { echo 'checked'; } ?>/> по возрастанию</label><br/>
					<label><input type="radio" name="<?php echo $this->get_field_name('ascdsc'); ?>" value="desc" <?php if( $ascdsc == 'desc' ) { echo 'checked'; } ?>/> по убыванию</label>
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('exclude'); ?>">Исключить записи по id</label><br/>
					<input type="text" class="widefat" name="<?php echo $this->get_field_name('exclude'); ?>" value="<?php echo $exclude; ?>" />
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('exclude'); ?>">Показывать только дочерние Таксономии категории [id]</label><br/>
					<input type="text" class="widefat" name="<?php echo $this->get_field_name('childof'); ?>" value="<?php echo $childof; ?>" />
				</p>
				<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('dropdown'); ?>" name="<?php echo $this->get_field_name('dropdown'); ?>"<?php checked( $dropdown ); ?> />
				<label for="<?php echo $this->get_field_id('dropdown'); ?>"> отображать выпадающим списком</label></p>
			</div>
<?php 
	}
}


class lctwidget_Taxonomy_Dropdown_Walker extends Walker {
	var $tree_type = 'category';
	var $db_fields = array ( 'id' => 'term_id', 'parent' => 'parent' );

	function start_el( &$output, $term, $depth = 0, $args = array(), $current_object_id = 0 ) {
		$term = get_term( $term, $term->taxonomy );
		$term_slug = $term->slug;

		$text = str_repeat( '&nbsp;', $depth * 3 ) . $term->name;
		if ( $args['show_count'] ) {
			$text .= '&nbsp;('. $term->count .')';
		}

		$class_name = 'level-' . $depth;

		$output.= "\t" . '<option' . ' class="' . esc_attr( $class_name ) . '" value="' . esc_attr( $term_slug ) . '">' . esc_html( $text ) . '</option>' . "\n";
	}
}
?>