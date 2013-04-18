<?php
/*
	Plugin Name: eHive Objects Tag Cloud widget
	Plugin URI: http://developers.ehive.com/wordpress-plugins/
	Author: Vernon Systems Limited
	Description: Displays a cloud of tags for eHive objects. The <a href="http://developers.ehive.com/wordpress-plugins#ehiveaccess" target="_blank">eHiveAccess plugin</a> must be installed.
	Version: 2.1.1
	Author URI: http://vernonsystems.com
	License: GPL2+
*/
/*
	Copyright (C) 2012 Vernon Systems Limited

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
add_action( 'widgets_init', 'ehive_objects_tag_cloud_widget' );

function ehive_objects_tag_cloud_widget() {
	register_widget( 'EHiveObjectsTagCloud_Widget' );
}

class eHiveObjectsTagCloud_Widget extends WP_Widget {
	
	public function __construct() {
		parent::__construct('ehiveobjectstagcloud_widget',
							'eHive Objects Tag Cloud', 
							array( 'description' => __('Displays a cloud of tags for eHive objects.', 'text_domain'))
						   );
	}
		
	function widget($args, $instance) {

		if (isset($instance['widget_css_enabled'])) {
			wp_register_style($handle = 'eHiveObjectsTagCloudWidgetCSS', $src = plugins_url('eHiveObjectsTagCloud_Widget.css', '/ehive-objects-tag-cloud-widget/css/eHiveObjectsTagCloud_Widget.css'), $deps = array(), $ver = '0.0.1', $media = 'all');
			wp_enqueue_style( 'eHiveObjectsTagCloudWidgetCSS');
		}
		
		echo $args['before_widget'];
		echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];

		global $eHiveAccess, $eHiveSearch;
		
		$eHiveApi = $eHiveAccess->eHiveApi();
		
		$siteType = $eHiveAccess->getSiteType();
		$accountId = $eHiveAccess->getAccountId();
		$communityId = $eHiveAccess->getCommunityId();
		
		try {
			switch($siteType) {
			case 'Account':
				$tagCloud = $eHiveApi->getTagCloudInAccount($accountId, $instance['count']);
				break;
			case 'Community':
				$tagCloud = $eHiveApi->getTagCloudInCommunity($communityId, $instance['count']);
				break;
			default:
				$tagCloud = $eHiveApi->getTagCloudInEHive($instance['count']);
				break;
			}
			                 
			if ($instance['css_class'] == "") {
				echo '<div class="ehive-tag-cloud-widget">';
			} else {
				echo '<div class="ehive-tag-cloud-widget '.$instance['css_class'].'">';
			}		
			
			foreach ($tagCloud->tagCloudTags as $tagCloudTag) {    	
				switch ($tagCloudTag->percentage) {
				case (int) $tagCloudTag->percentage > 95:
					$level ="10";
					break;
				case (int) $tagCloudTag->percentage > 90:
					$level ="9";
					break;
				case (int)$tagCloudTag->percentage > 80:
					$level ="8";
					break;
				case (int)$tagCloudTag->percentage > 70:
					$level ="7";
					break;
				case (int)$tagCloudTag->percentage > 60:
					$level ="6";
					break;
				case (int)$tagCloudTag->percentage > 50:
					$level ="5";
					break;
				case (int)$tagCloudTag->percentage > 40:
					$level ="4";
					break;
				case (int)$tagCloudTag->percentage > 30:
					$level ="3";
					break;
				case (int)$tagCloudTag->percentage > 20:
					$level ="2";
					break;
				case (int)$tagCloudTag->percentage <= 20:
					$level ="1";
					break;
				}
	
				if (isset($eHiveSearch)) {
					$searchOptions = $eHiveSearch->getSearchOptions();
					$link = $eHiveAccess->getSearchPageLink( "?{$searchOptions['query_var']}=tag:{$tagCloudTag->cleanTagName}" );
				} else {
					$link = '#';
				}
				echo  "<a class='ehive-tag-{$level}' href='{$link}'>{$tagCloudTag->cleanTagName}</a> ";
			}
			echo '</div>';
			
			echo $args['after_widget'];
		} catch (Exception $exception) {
			error_log('EHive Tag Cloud widget returned and error while accessing the eHive API: ' . $exception->getMessage());
			$eHiveApiErrorMessage = " ";
			if ($eHiveAccess->getIsErrorNotificationEnabled()) {
				$eHiveApiErrorMessage = $eHiveAccess->getErrorMessage();
			}
// 			echo "<div><p class='ehive-error-message ehive-account-details-error'>$eHiveApiErrorMessage</p></div>";
		}
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;

		$instance['title'] = $new_instance['title'];
		$instance['count'] = $new_instance['count'];
		$instance['widget_css_enabled'] = $new_instance['widget_css_enabled'];
		$instance['css_class'] = $new_instance['css_class'];
		
		return $instance;
	}
		
	function form($instance) {
		
		$defaults = array(
				'title' => 'Objects Tag Cloud', 
				'count' => 30,
				'widget_css_enabled' => true,
				'css_class' => '');
		
		$instance = wp_parse_args( $instance, $defaults );
	
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" type="text" value="<?php echo $instance['title']; ?>" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" />
		</p>		
		<p>
			<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Number of tags:' ); ?></label>
			<input class="small-text" type="number" value="<?php echo $instance['count']; ?>" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" />
		</p>	
		<hr class="div"/>				
        <p>
	        <input class="checkbox" type="checkbox" value="1" <?php checked( $instance['widget_css_enabled'], true ); ?> id="<?php echo $this->get_field_id('widget_css_enabled'); ?>" name = "<?php echo $this->get_field_name('widget_css_enabled'); ?>" />
			<label for="<?php echo $this->get_field_id('widget_css_enabled'); ?>"><?php _e( 'Enable widget stylesheet' ); ?></label>        
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'css_class' ); ?>"><?php _e( 'Custom CSS Class:' ); ?></label>
			<input class="widefat" type="text" value="<?php echo $instance['css_class']; ?>" id="<?php echo $this->get_field_id( 'css_class' ); ?>" name="<?php echo $this->get_field_name( 'css_class' ); ?>" />
		</p>				
		<?php 		
	}
}