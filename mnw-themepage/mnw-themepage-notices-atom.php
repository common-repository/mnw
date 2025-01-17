<?php
/**
 * This file is part of mnw.
 *
 * mnw - an OpenMicroBlogging compatible Microblogging plugin for Wordpress
 * Copyright (C) 2009, Adrian Lang
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

function mnw_notices_atom($type, $paged, $total, $notices) {
    if ($type == 'sent') {
        $title = __('Microblog posts by %s', 'mnw');
    } else {
        $title = __('Microblog posts to %s', 'mnw');
    }

    $updated = ($notices !== null) ? mysql2date('Y-m-d\TH:i:s\Z', $notices[0]['created']) : date('Y-m-d\TH:i:s\Z');
    $baseurl = mnw_set_action('notices') . "&type=$type" . ($paged != 1 ? "&paged=$paged" : '');
    $my_url = $baseurl . "&format=atom";
    $html_url = $baseurl . "&format=html";

    header('Content-Type: application/atom+xml; charset=' . get_option('blog_charset'), true);
    echo '<?xml version="1.0" encoding="' . get_option('blog_charset') . '" ?' . '>';
?>

<feed
    xmlns="http://www.w3.org/2005/Atom"
    xml:lang="<?php echo get_option('rss_language'); ?>"
    xmlns:thr="http://purl.org/syndication/thread/1.0"
    <?php do_action('atom_ns'); ?>
>
    <id><?php echo attribute_escape($my_url); ?></id>
    <link rel="self" href="<?php echo attribute_escape($my_url); ?>" type="application/atom+xml"/>
    <link rel="alternate" href="<?php echo attribute_escape($html_url); ?>" type="<?php bloginfo_rss('html_type'); ?>" />

    <title type="text"><?php echo ent2ncr(sprintf($title, get_bloginfo('title')));?></title>
    <subtitle type="text"><?php bloginfo_rss('description'); ?></subtitle>
<?php if ($type == 'sent') {?>
    <author><name><?php echo get_option('omb_nickname');?></name></author>
<?php } ?>

    <updated><?php echo $updated; ?></updated>
    <?php the_generator( 'atom' ); ?>

<?php
if ($notices !== null) {
foreach($notices as $notice) {
    $title = $notice['content'];
    if (mb_strlen($title) > 140) {
        $title = mb_substr($title, 0, 139) . '…';
    }
?>
    <entry>
        <id><?php echo htmlspecialchars($notice['noticeurl']); ?></id>
        <link rel="alternate" href="<?php echo attribute_escape($notice['noticeurl']); ?>" type="<?php bloginfo_rss('html_type'); ?>" />
        <title><?php echo htmlspecialchars($title); ?></title>
<?php if ($type != 'sent') {?>
        <author><name><?php echo htmlspecialchars($notice['nickname']); ?></name><uri><?php echo htmlspecialchars($notice['authorurl']); ?></uri></author>
<?php } ?>
        <content><?php echo htmlspecialchars($notice['content']); ?></content>
        <updated><?php echo mysql2date('Y-m-d\TH:i:s\Z', $notice['created'], false); ?></updated>
        <published><?php echo mysql2date('Y-m-d\TH:i:s\Z', $notice['created'], false); ?></published>
    </entry>
<?php }
} ?>
</feed>
<?php
    return mnw_No_Themepage();
}
?>
