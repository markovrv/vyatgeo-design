<?php
/**
 * Template Name: Yandex map template
 * Template Post Type: page
 */

get_header();
?>

<style>

body {
    overflow-y: hidden;
}

#app > div {
    height: calc(100vh - 70px)!important;
}

@media screen and (min-width: 1000px) {
    #app > div {
        height: calc(100vh - 120px)!important;
    }
}
@media screen and (max-width: 500px) {
    .va-modal__dialog {
        max-height: 100vh!important;
    }
}

:root[class=p-dark] {
    .va-modal__header h3 {
        color: white!important;
    }

    [data-header*="type-1"] .ct-header [data-row*="middle"] {
        background: linear-gradient(180deg, black, #343e4e);
    }

    --theme-palette-color-8: black;
    --theme-link-initial-color: white;
    --theme-palette-color-4: white;
    --theme-text-color: white;
}

.va-modal-overlay-background--blurred>:not(div[class*=va-]) {
    filter: none;
}

.va-modal__overlay {
    opacity: 1!important;
    background-color: rgb(0 0 0 / 22%)!important;
    backdrop-filter: blur(15px);
}

</style>

<script>
    window.map = {
        api: '../wp-json/attraction/v1/objects',
        mapKey: 'e951357a-eba8-475e-b058-94799302e5bb',
		theme: 'light'
    };
    window.polygon = {link: '../../ymaps/polygons/'};
    window.apiConfig = {gptOn: false};
</script>

<div id="app"></div>

<?php 
//get_template_part( 'template-parts/footer-menus-widgets' ); 
get_footer();
?>



</body>

</html>