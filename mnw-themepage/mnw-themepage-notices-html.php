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

function mnw_notices_html($type, $paged, $total, $notices) {
    return new mnw_Themepage_Notices($type, $paged, $total, $notices);
}

class mnw_Themepage_Notices extends mnw_Themepage{
    protected $type;
    protected $paged;
    protected $total;
    protected $notices;

    public function __construct($type, $paged, $total, $notices) {
        $this->type = $type;
        $this->paged = $paged;
        $this->total = $total;
        $this->notices = $notices;
    }

    public function render() {
        parent::render();
        $show_sent = ($this->type === 'sent');
?>
        <h3><?php if ($show_sent) { _e('Sent notices', 'mnw'); } else { _e('Received notices', 'mnw');} ?></h3>
        <div>
            <ul>
<?php
            if (!is_null($this->notices)) {
                foreach($this->notices as $notice) {
                echo '<li>';
                if ($show_sent) {
                printf('„%s“ @ <a href="%s">%s</a>', $notice['content'], $notice['noticeurl'], $notice['created']);
                } else {
                printf('„%s“<div><a href="%s" title="%s">%s</a> @ <a href="%s">%s</a></div>', $notice['content'], $notice['authorurl'], $notice['fullname'], $notice['nickname'], $notice['noticeurl'], $notice['created']);
                }
                echo '</li>';
                }
            }

?>
        </ul>
        <div style="float: right;">
            <span>
<?php
                printf(__('Displaying %s–%s of %s', 'mnw'),
                    number_format_i18n(($this->paged - 1) * 15 + 1),
                    number_format_i18n(min($this->paged * 15, $this->total)),
                    number_format_i18n($this->total));
?>
            </span>
<?php
            echo paginate_links(array(
                'base' => add_query_arg( 'paged', '%#%' ),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => ceil($this->total / 15.0),
                'current' => $this->paged));
?>
  </div>
               <a href="<?php echo attribute_escape(mnw_set_action('notices') . "&type=$this->type&format=atom"); ?>" title="<?php _e('Display Atom feed of notices', 'mnw');?>"><?php _e('Atom feed', 'mnw'); ?></a>
            </div>
<?php
    }
}
?>
