<?php

/*
	Plugin Name: LastFM Played for Wordpress
	Plugin URI: https://nicolasbettag.com
	Description: Clean and simple recently played Last.FM Plugin for Wordpress
	Version: 0.92
	Author: Nicolas Bettag
	Author URI: https://nicolasbettag.com
	License: GPLv2
	*/
/*  Copyright 2017 Nicolas Bettag

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    The license for this software can likely be found here:
    http://www.gnu.org/licenses/gpl-2.0.html
*/

class LastWP_plugin extends WP_Widget
{
    function LastWP_plugin()
    {
        parent::WP_Widget(false, $name = __('LastFM Played for Wordpress', 'LastWP_plugin'));
    }

    function form($instance)
    {
        if ($instance) {
            $title    = esc_attr($instance['title']);
            $textarea = $instance['textarea'];
        } else {
            $title    = '';
            $textarea = '';
        }
        ?>

        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'wp_widget_plugin'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>"/></p>
        <p>
            <label for="<?php echo $this->get_field_id('textarea'); ?>"><?php _e('Last.FM Username:', 'wp_widget_plugin'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('textarea'); ?>"
                   name="<?php echo $this->get_field_name('textarea'); ?>" type="text"
                   value="<?php echo $textarea; ?>"/></p>

        <?php
    }

    function update($new_instance, $old_instance)
    {
        $instance             = $old_instance;
        $instance['title']    = strip_tags($new_instance['title']);
        $instance['textarea'] = strip_tags($new_instance['textarea']);

        return $instance;
    }

    protected function _getMainTemplate($widgetTitle, $lastfmUsername)
    {
        // user part
        $lastfm_api_user = 'http://ws.audioscrobbler.com/2.0/?method=user.getinfo&user=' . $lastfmUsername . '&api_key=b3f34d8652bf87d8d1dcbfa5c53d245d';
        $lastfmUser      = @simplexml_load_file($lastfm_api_user);

        $userTemplate = $this->_getUserTemplate($lastfmUser);

        // tracks
        $lastfm_api      = 'http://ws.audioscrobbler.com/2.0/?method=user.getrecenttracks&user=' . $lastfmUsername . '&api_key=b3f34d8652bf87d8d1dcbfa5c53d245d&limit=5';
        $lastfm_response = @simplexml_load_file($lastfm_api);

        $tracks = '';
        $i      = 1;
        foreach ($lastfm_response->recenttracks->track as $track) {
            if ($i <= 5) $tracks .= $this->_getTrackTemplate($track);
            $i++;
        }

        return <<<TPL
<section class="lastfm">
    $widgetTitle
    <div class="widget-textarea">
        $userTemplate
        <div class="lastfm_played">
            $tracks
        </div>
    </div>
</section>
TPL;
    }

    protected function _getUserTemplate($user)
    {
        $userName      = $user->user->name;
        $userRealname  = $user->user->realname;
        $userUrl       = $user->user->url;
        $userPicture   = $user->user->image[1];
        $userScrobbles = $user->user->playcount;

        return <<<TPL
<div class="lastfm_user">
    <div class="lastfm_row">
        <div class="lastfm_col">
            <img class="user_picture" src="$userPicture" />
        </div>
        <div class="lastfm_col">
            <b>$userRealname</b><br/>
            <a target="_blank" href="$userUrl">$userName</a><br>
            <small>$userScrobbles tracks</small>
        </div>
    </div>
</div>
TPL;
    }

    protected function _getTrackTemplate($track)
    {
        $imgUrl        = ($track->image[1]->__toString()) ? $track->image[1] : plugins_url('nocover.png', __FILE__);
        $title         = $track->name;
        $artist        = $track->artist;
        $time          = human_time_diff($track->date['uts']);
        $trackTimeInfo = ('' != $track['nowplaying']) ? '<p class="now-scrobbling">Playing now</p>' : '<p>'.sprintf('%s ago', $time).'</p>';

        return <<<TPL
<div class="lastfm_row lastfm_row_border">
    <div class="lastfm_col_small">
        <img class="cover" src="$imgUrl" />
    </div>
    <div class="lastfm_col lastfm_center">
        <p><b>$title</b></p>
        <p>$artist</p>
        $trackTimeInfo
    </div>
</div>
TPL;
    }

    function widget($args, $instance)
    {
        extract($args);
        echo $before_widget;

        // title
        $widgetTitle = apply_filters('widget_title', $instance['title']);
        if ($widgetTitle) $widgetTitle = $before_title . $widgetTitle . $after_title;

        if ($lastfmUsername = $instance['textarea']) {
            echo $this->_getMainTemplate($widgetTitle, $lastfmUsername);
        }

        echo $after_widget;
    }
}

add_action('widgets_init', create_function('', 'return register_widget("LastWP_plugin");'));
add_action('wp_enqueue_scripts', 'last_stylesheet');

function last_stylesheet()
{
    wp_register_style('prefix-style', plugins_url('style.css', __FILE__));
    wp_enqueue_style('prefix-style');
}

?>