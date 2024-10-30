<?php
/*
Template Name: mnw
*/

/* ----------
   DO NOT change the following lines. */

/* Initialize mnw themepage handling, parse request. */
require_once 'mnw-themepage/mnw-themepage-handler.php';
$mnwpage = mnw_Themepage::getHandler();

/* Check if the themepage should be displayed at all.
   Maybe mnw issued a redirect or displayed non-html content. */
if (!$mnwpage->shouldDisplay()) {
    return;
}

/* Start customizing the page HERE to fit with your theme.
   ---------- */

get_header();
?>
<div id="content" class="narrowcolumn">
    <h2><?php wp_title('', true, ''); ?></h2>
<?php
    /* Display the page‘s content. */
    $mnwpage->render();
?>
</div>
<?php
get_sidebar();
get_footer(); 
?>
