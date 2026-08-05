<?php
/**
 * Template Name: Yandex map template
 * Template Post Type: page
 */

get_header();
?>
<script>
  mymappath = '../wp-content/plugins/geopoints/vue/';
  startpage = 656;
  api_key = '';
</script>

<style>
  @font-face {
      font-family: feather;
      font-size: normal;
      font-weight: 400;
      src: url(../wp-content/plugins/geopoints/vue/fonts/feather.eot);
      src: url(../wp-content/plugins/geopoints/vue/fonts/feather.eot)
          format("embedded-opentype"),
        url(../wp-content/plugins/geopoints/vue/fonts/feather.ttf)
          format("truetype"),
        url(../wp-content/plugins/geopoints/vue/fonts/feather.woff)
          format("woff"),
          url(../wp-content/plugins/geopoints/vue/fonts/feather.svg) format("svg");
  }

   .ymap-container {
    height: calc(100vh - var(--admin-bar, 0px) - var(--theme-frame-size, 0px)*2 - 120px);
  }

  #itemlist {
    height: calc(100vh - var(--admin-bar, 0px) - var(--theme-frame-size, 0px)*2 - 300px);
  }

.at-table table {
    font-size: inherit;
    margin: 0;
}

.timeline-box {
    height: 60px!important;
	bottom: 30px!important;
}
.timeline-dots li button {
  padding: 0 0 13px 0 !important;
	font-weight: 100;
	line-height:1;
	text-transform:none;
}
.timeline-dots li {
	margin: 0;
}
.at-btn--default {
	color: black;
	font-weight: 100;
}

@media (max-width: 1000px) {
    .ymap-container {
        height: calc(100vh - var(--admin-bar, 0px) - var(--theme-frame-size, 0px)*2 - 70px)!important;
    }
    #itemlist {
        height: calc(100vh - 250px);
    }
}

@media (max-width: 500px) {
	
    #itemlist {
      height: 180px;
			width: 100%;
    }
	.title p {
		font-size:smaller;
	}
    .timeline-box {
        width: 100%!important;
        left: 0!important;
        margin: 0!important;
			border-radius:0!important;
			height: 105px!important;
			bottom:0px!important
    }
	
	.at-modal {
			width: 100%!important;
			top: 70px;
			height: 100%;
			border-radius: 0;
	}
	.at-modal__body > :last-child {
			height: auto!important;
	}
	
		.at-modal__body  {
			overflow-y: scroll;
			height:calc(100vh - 220px);
	}
	
	.at-modal__mask {
		background-color: rgb(0 0 0 / 25%);
	}
	
	
	.wrapper {
		left: 0px!important;
		bottom: 104px;
		top:auto!important;
		width: 100%!important;
	}

	.panel {
		display: flex;
		width: 100%!important;
	}
	
	.at-collapse__header {
    padding: 5px 32px;
	}
	
	.at-collapse__header > .icon {top: 10px;
    left: 10px;}
	.at-table table td {
		border-right: 0;
		border-left: 0;
	}
	
	
	
}

.at-btn {
	min-width: fit-content;
}

.panel .close, .at-modal__close {
	  top: 6px;
    right: 6px;
    font-size: 30px;
}
</style>

<div id="app"></div>

<?php 
//get_template_part( 'template-parts/footer-menus-widgets' ); 
get_footer();
?>

</body>

</html>