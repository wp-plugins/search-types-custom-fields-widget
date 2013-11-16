<?php

/*
Plugin Name: Search Types Custom Fields Widget
Plugin URI: http://alttypes.wordpress.com/
Description: Widget for searching Types custom fields and custom taxonomies.
Version: 0.4.2
Author: Magenta Cuda
Author URI: http://magentacuda.wordpress.com
License: GPL2
Documentation: http://alttypes.wordpress.com/
 */
 
/*
    Copyright 2013  Magenta Cuda

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class Search_Types_Custom_Fields_Widget extends WP_Widget {
    
    # start of user configurable constants
    const DATE_FORMAT = DATE_RSS;                                      # how to display date/time values
    const SQL_LIMIT = 16;                                              # maximum number of items to display
    #const SQL_LIMIT = 2;                                              # TODO: this limit for testing only replace with above
    # end of user configurable constants
    
    const OPTIONAL_TEXT_VALUE_SUFFIX = '-stcfw-optional-text-value';   # suffix to append to optional text input for a search field
    const GET_FORM_FOR_POST_TYPE = 'get_form_for_post_type';

	public function __construct() {
		parent::__construct(
            'search_types_custom_fields_widget',
            __( 'Search Types Custom Fields' ),
            array(
                'classname' => 'search_types_custom_fields_widget',
                'description' => __( "Search Types Custom Fields" )
            )
        );
	}

    # widget() emits a form to select a post type
    # this form then sends back an AJAX request for the specific search form for the selected post type
    
	public function widget( $args, $instance ) {
        global $wpdb;
        extract( $args );
        #error_log( '##### Search_Types_Custom_Fields_Widget::widget():$instance=' . print_r( $instance, TRUE ) );
?>
<form id="search-types-custom-fields-widget-<?php echo $this->number; ?>" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
<input id="search_types_custom_fields_form" name="search_types_custom_fields_form" type="hidden" value="types-fields-search">
<input id="search_types_custom_fields_widget_option" name="search_types_custom_fields_widget_option" type="hidden"
    value="<?php echo $this->option_name; ?>">
<input id="search_types_custom_fields_widget_number" name="search_types_custom_fields_widget_number" type="hidden"
    value="<?php echo $this->number; ?>">
<h2>Search:</h2>
<div class="search-types-custom-fields-widget-parameter" style="padding:5px 10px;border:2px solid black;margin:5px;">
<h3>post type:</h3>
<select id="post_type" name="post_type" required style="width:100%;">
<option value="no-selection">--select post type--</option>
<?php
        $results = $wpdb->get_results( <<<EOD
            SELECT post_type, COUNT(*) count FROM $wpdb->posts WHERE post_status = "publish"
                GROUP BY post_type ORDER BY count DESC
EOD
            , OBJECT );
        foreach ( $results as $result ) {
            $name = $result->post_type;
            # skip irrelevant post types
            #if ( $name === 'attachment' || $name === 'revision' || $name === 'nav_menu_item' || $name === 'content_macro' ) {
            #    continue;
            #}
            if ( !in_array( $name, array_keys( $instance ) ) ) { continue; }
?>      
<option value="<?php echo $name; ?>"><?php echo "$name ($result->count)"; ?></option>
<?php
        }   # foreach ( $results as $result ) {
?>
</select>
</div>
<div id="search-types-custom-fields-parameters"></div>
Results should satisfy 
<input type="radio" name="search_types_custom_fields_and_or" value="and" checked><strong>All</strong>
<input type="radio" name="search_types_custom_fields_and_or" value="or"><strong>Any</strong>
of the selected search conditions.
<input id="search-types-custom-fields-submit" type="submit" value="Search" disabled>
</form>
<script>
jQuery("form#search-types-custom-fields-widget-<?php echo $this->number; ?> select#post_type").change(function(){
    // console.log("select#post_type change");
    // send an AJAX request for the specific search form for the selected post type
    jQuery.post(
        "<?php echo admin_url( 'admin-ajax.php' ); ?>",
        {
            action:
                "<?php echo Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE; ?>",
            post_type:
                jQuery("form#search-types-custom-fields-widget-<?php echo $this->number; ?>"
                    +" select#post_type option:selected").val(),
            search_types_custom_fields_widget_option:
                jQuery("form#search-types-custom-fields-widget-<?php echo $this->number; ?>"
                    +" input#search_types_custom_fields_widget_option").val(),
            search_types_custom_fields_widget_number:
                jQuery("form#search-types-custom-fields-widget-<?php echo $this->number; ?>"
                    +" input#search_types_custom_fields_widget_number").val()
        },
        function(response){
            //console.log(response);
            // show the form returned by AJAX
            jQuery("form#search-types-custom-fields-widget-<?php echo $this->number; ?>"
                +" div#search-types-custom-fields-parameters").html(response);
            jQuery("form#search-types-custom-fields-widget-<?php echo $this->number; ?>"
                +" input#search-types-custom-fields-submit").prop("disabled",false);
        }
    );
});
</script>
<?php
	}
    
    public function update( $new, $old ) {
        #error_log( '##### Search_Types_Custom_Fields_Widget::update():backtrace='
        #    . print_r( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), TRUE ) );
        #error_log( '##### Search_Types_Custom_Fields_Widget::update():$_POST=' . print_r( $_POST, TRUE ) );    
        #error_log( '##### Search_Types_Custom_Fields_Widget::update():$old=' . print_r( $old, TRUE ) );
        #error_log( '##### Search_Types_Custom_Fields_Widget::update():$new=' . print_r( $new, TRUE ) );
        return array_map( function( $values ) { return array_map( strip_tags, $values ); }, $new );
    }
    
    # form() is for the administrator to specify the post types and custom fields that will be searched
    
    public function form( $instance ) {
        global $wpdb;
        $SQL_LIMIT = self::SQL_LIMIT;
        #error_log( '##### Search_Types_Custom_Fields_Widget::form():$instance=' . print_r( $instance, TRUE ) );
        $wpcf_types  = get_option( 'wpcf-custom-types', array() );
        $wpcf_fields = get_option( 'wpcf-fields',       array() );
        #error_log( '##### Search_Types_Custom_Fields_Widget::form():$wpcf_types=' . print_r( $wpcf_types, TRUE ) );
?>
<h4>Select Search Fields for:</h4>
<?php
        # use all Types custom post types and the WordPress built in "post" and "page"
        $wpcf_types_keys = '"' . implode( '", "', array_keys( $wpcf_types ) ) . '", "post", "page"';
        $sql = <<<EOD
            SELECT post_type, COUNT(*) count FROM $wpdb->posts
                WHERE post_type IN ( $wpcf_types_keys ) AND post_status = "publish" 
                GROUP BY post_type ORDER BY count DESC LIMIT $SQL_LIMIT
EOD;
        #error_log( '##### Search_Types_Custom_Fields_Widget::form():$sql=' . $sql );
        $types = $wpdb->get_results( $sql, OBJECT_K );
        #error_log( '##### Search_Types_Custom_Fields_Widget::form():$types=' . print_r( $types, TRUE ) );
        
        # the sql below gives the number of posts tagged, since a single post may be tagged with multiple tags
        # the sql is somewhat complicated
        $sql = <<<EOD
            SELECT post_type, taxonomy, count(*) count
                FROM (SELECT p.post_type, tt.taxonomy, r.object_id
                    FROM wp_term_relationships r, wp_term_taxonomy tt, wp_terms t, wp_posts p
                    WHERE r.term_taxonomy_id = tt.term_taxonomy_id AND tt.term_id = t.term_id AND r.object_id = p.ID
                        AND post_type IN ( $wpcf_types_keys )
                    GROUP BY p.post_type, tt.taxonomy, r.object_id) d 
                GROUP BY post_type, taxonomy
EOD;
        $db_taxonomies = $wpdb->get_results( $sql, OBJECT );
        #error_log( '##### Search_Types_Custom_Fields_Widget::form():$db_taxonomies=' . print_r( $db_taxonomies, TRUE ) );
        $wp_taxonomies = get_taxonomies( '', 'objects' );
        #error_log( '##### Search_Types_Custom_Fields_Widget::form():$wp_taxonomies=' . print_r( $wp_taxonomies, TRUE ) );
        
        foreach ( $types as $name => $type ) {
            $selected = $instance[$name];
?>
<div class="scpbcfw-search-fields">
<span style="font-size=16px;font-weight:bold;float:left;"><?php echo "$name ($type->count)"; ?></span>
<button class="scpbcfw-display-button" style="font-size:12px;font-weight:bold;padding:3px;float:right;">Open</button>
<div style="clear:both;"></div>
<div class="scpbcfw-search-field-values" style="display:none;">
<?php
            # do taxonomies first
            foreach ( $db_taxonomies as $db_taxonomy ) {
                #error_log( '##### Search_Types_Custom_Fields_Widget::form():$name=' . $name
                #    . ', $db_taxonomy=' . print_r( $db_taxonomy, TRUE ) );
                if ( $db_taxonomy->post_type != $name ) { continue; }
                $wp_taxonomy = $wp_taxonomies[$db_taxonomy->taxonomy];
                #error_log( '##### $taxonomy=' . print_r( $taxonomy, TRUE ) );
                #error_log( '##### Search_Types_Custom_Fields_Widget::form():$name=' . $name
                #    . ', $wp_taxonomy=' . print_r( $wp_taxonomy, TRUE ) );
                $tax_type = ( $wp_taxonomy->hierarchical ) ? 'tax-cat-' : 'tax-tag-';
                $tax_label = ( $wp_taxonomy->hierarchical ) ? ' (category)' : ' (tag)';
?>
<input type="checkbox"
    id="<?php echo $this->get_field_id( $name ); ?>"
    name="<?php echo $this->get_field_name( $name ); ?>[]"
    value="<?php echo $tax_type . $wp_taxonomy->name; ?>"
    <?php if ( $selected && in_array( $tax_type . $wp_taxonomy->name, $selected ) ) { echo ' checked'; } ?>>
    <?php echo "{$wp_taxonomy->label}{$tax_label} ($db_taxonomy->count)"; ?><br>
<?php
            }
            
            # now do custom fields and post content
            $sql = <<<EOD
                SELECT m.meta_key, COUNT(*) count FROM $wpdb->postmeta m, $wpdb->posts p
                WHERE m.post_id = p.ID AND p.post_type = "$name" AND m.meta_key LIKE 'wpcf-%'
                GROUP BY m.meta_key ORDER BY count DESC LIMIT $SQL_LIMIT
EOD;
            #error_log( '##### Search_Types_Custom_Fields_Widget::form():$sql=' . $sql );
            $fields = $wpdb->get_results( $sql, OBJECT_K );
            foreach ( $fields as $meta_key => &$field ) {
                $field_name = substr( $meta_key, 5 );
                if ( array_key_exists( $field_name, $wpcf_fields ) ) {
                    $field->label = $wpcf_fields[$field_name]['name'];
                } else {
                    $field = NULL;   # not a valid Types custom field so tag it for skipping.
                }
            }
            unset( $field );
            #error_log( '##### Search_Types_Custom_Fields_Widget::form():$fields=' . print_r( $fields, TRUE ) );
            # add fields for parent of, child of, post_content and attachment
            # TODO: collapsing all parent post types together may not be the best idea - consider splitting
            $sql = <<<EOD
                SELECT COUNT( DISTINCT m.post_id ) FROM $wpdb->postmeta m, $wpdb->posts p
                WHERE m.post_id = p.ID AND p.post_type = "$name" AND m.meta_key LIKE '_wpcf_belongs_%'
EOD;
            #error_log( '##### Search_Types_Custom_Fields_Widget::form():$sql=' . $sql );
            $fields['belongs-child_of']     = (object) array( 'label' => 'Child Of', 'count' => $wpdb->get_var( $sql ) );
            # TODO: collapsing all child post types together may not be the best idea - consider splitting
            $sql = <<<EOD
                SELECT COUNT( DISTINCT m.meta_value ) FROM $wpdb->postmeta m, $wpdb->posts p
                WHERE m.meta_value = p.ID AND p.post_type = "$name" AND m.meta_key LIKE '_wpcf_belongs_%'
EOD;
            #error_log( '##### Search_Types_Custom_Fields_Widget::form():$sql=' . $sql );
            $fields['belongs-parent_of']    = (object) array( 'label' => 'Parent Of', 'count' => $wpdb->get_var( $sql ) );
            $fields['pst-std-post_content'] = (object) array( 'label' => 'Post Content', 'count' => $type->count );
            $sql = <<<EOD
                SELECT COUNT( DISTINCT a.post_parent ) FROM $wpdb->posts a, $wpdb->posts p
                WHERE a.post_type = "attachment" AND a.post_parent = p.ID AND p.post_type = "$name" AND p.post_status = "publish"
EOD;
            #error_log( '##### Search_Types_Custom_Fields_Widget::form():$sql=' . $sql );
            $fields['pst-std-attachment']   = (object) array( 'label' => 'Attachment', 'count' => $wpdb->get_var( $sql ) );
            #error_log( '##### Search_Types_Custom_Fields_Widget::form():$fields=' . print_r( $fields, TRUE ) );
            # now display all fields with checkboxes
            foreach ( $fields as $meta_key => $field ) {
                if ( $field === NULL ) { continue; }   # This item was not a valid Types custom field so skip it.
                #error_log( '##### Search_Types_Custom_Fields_Widget::form():$meta_key=' . $meta_key );
                #error_log( '##### Search_Types_Custom_Fields_Widget::form():$field='    . print_r( $field, TRUE ) );
?>
<input type="checkbox"
    id="<?php echo $this->get_field_id( $name ); ?>"
    name="<?php echo $this->get_field_name( $name ); ?>[]"
    value="<?php echo $meta_key; ?>"
    <?php if ( $selected && in_array( $meta_key, $selected ) ) { echo ' checked'; } ?>>
    <?php echo "$field->label ($field->count)"; ?><br>
<?php
            }
?>
</div>
</div>
<?php
        }
?>
<script type="text/javascript">
jQuery("button.scpbcfw-display-button").click(function(event){
    if(jQuery(this).text()=="Open"){
        jQuery(this).text("Close");
        jQuery("div.scpbcfw-search-field-values",this.parentNode).css("display","block");
    }else{
        jQuery(this).text("Open");
        jQuery("div.scpbcfw-search-field-values",this.parentNode).css("display","none");
    }
    return false;
});
</script>
<?php
    }
    
    # helper functions
    
    public static function search_wpcf_field_options( &$options, $option, $value ) {
        foreach ( $options as $k => $v ) {
            if ( $v[$option] == $value ) { return $k; }
        }
        return NULL;
    }
    
    public static function get_timestamp_from_string( $value ) {
        $t0 = strtotime( $value );
        $t1 = getdate( $t0 );
        if ( $t1['seconds'] ) { return array( $t0, $t0 ); }
        if ( !$t1['minutes'] && !$t1['hours'] ) { return array( $t0, $t0 + 86399 ); }
        return array( $t0, $t0 + 59 );
    }
    
    public static function &join_arrays( $op, &$arr0, &$arr1 ) {
        $is_arr0 = is_array( $arr0 );
        $is_arr1 = is_array( $arr1 );
        if ( $is_arr0 || $is_arr1 ) {
            if ( $op == 'AND' ) {
                if ( $is_arr0 && $is_arr1 ) { $arr = array_intersect( $arr0, $arr1 ); }
                else if ( $is_arr0 ) { $arr = $arr0; } else { $arr = $arr1; }
            } else {
                if ( $is_arr0 && $is_arr1 ) { $arr = array_unique( array_merge( $arr0, $arr1 ) ); }
                else if ( $is_arr0 ) { $arr = $arr0; } else { $arr = $arr1; }
            }
            return $arr;
        }
        return FALSE;
    }
}

add_action( 'widgets_init', function() {
    register_widget( 'Search_Types_Custom_Fields_Widget' );
} );

if ( is_admin() ) {
    add_action( 'wp_ajax_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE, function() {
        do_action( 'wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE );
    } );
    add_action( 'wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE, function() {
        # build the search form for the post type in the AJAX request
        global $wpdb;
        $SQL_LIMIT = Search_Types_Custom_Fields_Widget::SQL_LIMIT;
?>
<h4>Please specify search criteria:<h4>
<?php
        #error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
        #    . ':$_REQUEST=' . print_r( $_REQUEST, TRUE ) );
        $option = get_option( $_REQUEST['search_types_custom_fields_widget_option'] );
        #error_log( '##### wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
        #    . ':$option=' . print_r( $option, TRUE ) );
        $widget_number = $_REQUEST['search_types_custom_fields_widget_number'];
        $selected = $option[$widget_number][$_REQUEST['post_type']];
        #error_log( '##### wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
        #    . ':$selected=' . print_r( $selected, TRUE ) );
        # get all terms for all taxonomies for the selected post type
        $sql = <<<EOD
            SELECT x.taxonomy, r.term_taxonomy_id, t.name, COUNT(*) count
                FROM $wpdb->term_relationships r, $wpdb->term_taxonomy x, $wpdb->terms t, $wpdb->posts p
                WHERE r.term_taxonomy_id = x.term_taxonomy_id AND x.term_id = t.term_id AND r.object_id = p.ID
                    AND p.post_type = "$_REQUEST[post_type]"
                GROUP BY x.taxonomy, r.term_taxonomy_id ORDER BY x.taxonomy, r.term_taxonomy_id
EOD;
        #error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
        #    . ':$sql=' . $sql );
        $results = $wpdb->get_results( $sql, OBJECT );
        #error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
        #    . ':$results=' . print_r( $results, TRUE ) );
        $taxonomies = get_taxonomies( '', 'objects' );
        # restructure the results for displaying by taxonomy
        $terms = array();
        foreach ( $results as $result ) {
            $taxonomy = $taxonomies[$result->taxonomy];
            $tax_type = ( $taxonomy->hierarchical ) ? 'tax-cat-' : 'tax-tag-';
            if ( !in_array( $tax_type . $taxonomy->name, $selected ) ) { continue; }
            $terms[$result->taxonomy]['values'][$result->term_taxonomy_id]['name' ] = $result->name;
            $terms[$result->taxonomy]['values'][$result->term_taxonomy_id]['count'] = $result->count;
        }
        # now display the taxonomy results
        foreach ( $terms as $tax_name => &$values ) {
            $taxonomy = $taxonomies[$tax_name];
?>
<div class="scpbcfw-search-fields" style="padding:5px 10px;border:2px solid black;margin:5px;">
<span style="font-size=16px;font-weight:bold;float:left;"><?php echo $taxonomy->label ?>:</span>
<button class="scpbcfw-display-button" style="font-size:12px;font-weight:bold;padding:3px;float:right;">Open</button>
<div style="clear:both;"></div>
<div class="scpbcfw-search-field-values" style="display:none;">
<?php
            $count = -1;
            foreach ( $values['values'] as $term_id => &$result ) {
                if ( ++$count == $SQL_LIMIT ) { break; }
?>
<input type="checkbox" id="<?php echo $tax_type . $taxonomy->name ?>" name="<?php echo $tax_type . $taxonomy->name ?>[]"
    value="<?php echo $term_id; ?>"><?php echo "$result[name]($result[count])"; ?><br>
<?php
            }   # foreach ( $values['values'] as $term_id => $result ) {
            unset( $result );
            if ( $count == $SQL_LIMIT ) {
?>
<input type="text" id="<?php echo $tax_type . $taxonomy->name; ?>" name="<?php echo $tax_type . $taxonomy->name; ?>[]"
    class="for-select" style="width:90%;" placeholder="--Enter New Search Value--">
<?php
            }
?>
</div>
</div>
<?php
        }   # foreach ( $terms as $tax_name => &$values ) {
        unset( $values );
        # get all meta_values for the selected custom fields in the selected post type
        $selected_imploded = '( "' . implode( '", "', $selected ) . '" )';
        $sql = <<<EOD
            SELECT m.meta_key, m.meta_value, COUNT(*) count
                FROM $wpdb->postmeta m, $wpdb->posts p
                WHERE m.post_id = p.ID
                    AND meta_key IN $selected_imploded AND p.post_type = "$_REQUEST[post_type]"
                GROUP BY m.meta_key, m.meta_value
EOD;
        #error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
        #    . ':$sql=' . $sql );
        $results = $wpdb->get_results( $sql, OBJECT );
        #error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
        #    . ':$results=' . print_r( $results, TRUE ) );
        $wpcf_fields = get_option( 'wpcf-fields', array() );
        # prepare the results for use in checkboxes - need value, count of value and field labels
        foreach ( $results as $result ) {
            if ( !$result->meta_value ) { continue; }
            $wpcf_field =& $wpcf_fields[substr( $result->meta_key, 5 )];
            if ( is_serialized( $result->meta_value ) ) {
                # serialized meta_value contains multiple values so need to unpack them and process them individually
                $unserialized = unserialize( $result->meta_value );
                 if ( is_array( $unserialized ) ) {
                    if ( array_reduce( $unserialized, function( $sum, $value ) {
                        return $sum = $sum && is_scalar( $value );
                    }, TRUE ) ) {
                        foreach( $unserialized as $key => $value ) {
                            if ( $wpcf_field['type'] == 'checkboxes' ) {
                                # for checkboxes use the unique option key as the value of the checkbox
                                if ( $value ) { $fields[$result->meta_key]['values'][$key] += $result->count; }
                            } else {
                                $fields[$result->meta_key]['values'][$value] += $result->count;
                            }
                        }
                    } else {
                        continue;
                    }
                }
            } else {
                if ( $wpcf_field['type'] == 'radio' || $wpcf_field['type'] == 'select' ) {
                    # for radio and select use the unique option key as the value of the radio or select
                    $key = Search_Types_Custom_Fields_Widget::search_wpcf_field_options( $wpcf_field['data']['options'], 'value',
                        $result->meta_value );
                    if ( !$key ) { continue; }
                    $fields[$result->meta_key]['values'][$key] += $result->count;
                } else {
                    $fields[$result->meta_key]['values'][$result->meta_value] = $result->count;
                }
            }
            $fields[$result->meta_key]['type']  = $wpcf_field['type'];
            $fields[$result->meta_key]['label'] = $wpcf_field['name'];
        }   # foreach ( $results as $result ) {
        if ( in_array( 'belongs-child_of', $selected ) ) {
            $sql = <<<EOD
                SELECT m.meta_value, COUNT(*) count
                    FROM $wpdb->postmeta m, $wpdb->posts pi, $wpdb->posts pv
                    WHERE m.post_id = pi.ID and m.meta_value = pv.ID
                        AND m.meta_key LIKE "_wpcf_belongs_%" and pi.post_type = "$_REQUEST[post_type]"
                        GROUP BY m.meta_value
EOD;
            $results = $wpdb->get_resultS( $sql, OBJECT_K );
            #error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
            #    . ':$sql=' . $sql );
            #error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
            #    . ':$results=' . print_r( $results, TRUE ) );
            if ( $results ) {
                $fields['belongs-child_of'] = array( 'type' => 'child_of', 'label' => 'Child Of',
                    'values' => array_map( function( $v ) { return $v->count; }, $results ) );
            }
        }   # if ( in_array( 'belongs-child_of', $selected ) ) {
        if ( in_array( 'belongs-parent_of', $selected ) ) {
            $sql = <<<EOD
                SELECT m.post_id
                    FROM $wpdb->postmeta m, $wpdb->posts pi, $wpdb->posts pv
                    WHERE m.post_id = pi.ID and m.meta_value = pv.ID
                        AND m.meta_key LIKE "_wpcf_belongs_%" and pv.post_type = "$_REQUEST[post_type]"
EOD;
            $results = $wpdb->get_col( $sql );
            #error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
            #    . ':$sql=' . $sql );
            #error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
            #    . ':$results=' . print_r( $results, TRUE ) );
            if ( $results ) {
                $fields['belongs-parent_of'] = array( 'type' => 'parent_of', 'label' => 'Parent Of',
                    'values' => array_fill_keys( $results, 1 ) );
            }
        }   # if ( in_array( 'belongs-parent_of', $selected ) ) {
        if ( in_array( 'pst-std-post_content', $selected ) ) {
            $fields['pst-std-post_content'] = array( 'type' => 'textarea',   'label' => 'Post Content' );
        }
        if ( in_array( 'pst-std-attachment', $selected ) ) {
            $fields['pst-std-attachment']   = array( 'type' => 'attachment', 'label' => 'Attachment'   );
        }
        #error_log( 'action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE . ':$fields='
        #    . print_r( $fields, TRUE ) );
        $posts = NULL;
        foreach ( $fields as $meta_key => $field ) {
            #error_log( 'action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE . ':$meta_key='
            #    . $meta_key );
            #error_log( 'action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE . ':$field='
            #    . print_r( $field, TRUE ) );
            $wpcf_field =& $wpcf_fields[substr( $meta_key, 5 )];
?>
<div class="scpbcfw-search-fields" style="padding:5px 10px;border:2px solid black;margin:5px;">
<span style="font-size=16px;font-weight:bold;float:left;"><?php echo $field['label'] ?>:</span>
<button class="scpbcfw-display-button" style="font-size:12px;font-weight:bold;padding:3px;float:right;">Open</button>
<div style="clear:both;"></div>
<div class="scpbcfw-search-field-values" style="display:none;">
<?php
            if ( $field['type'] == 'textarea' || $field['type'] == 'wysiwyg' ) {
?>
<input id="<?php echo $meta_key ?>" name="<?php echo $meta_key ?>" class="for-input" type="text" style="width:90%;"
    placeholder="--Enter Search Value--">
</div>
</div>
<?php
                continue;
            }
            if ( $meta_key === 'pst-std-attachment' ) {
                $results = $wpdb->get_results( <<<EOD
                    SELECT a.ID, a.post_title FROM $wpdb->posts a, $wpdb->posts p
                        WHERE a.post_parent = p.ID AND a.post_type = "attachment" AND p.post_type = "$_REQUEST[post_type]"
                            AND p.post_status = "publish"
                        LIMIT $SQL_LIMIT
EOD
                    , OBJECT );
                foreach ( $results as $result ) {
?>
<input type="checkbox" id="<?php echo $meta_key ?>" name="<?php echo $meta_key ?>[]"
    value="<?php echo $result->ID; ?>"> <?php echo $result->post_title; ?><br>
<?php
                }
?>
</div>
</div>
<?php
                continue;
            }   # if ( $meta_key === 'pst-std-attachment' ) {
            #error_log( '##### wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
            #    . ':$field[values]=' . print_r( $field['values'], TRUE ) );
            # now output the checkboxes
            $number = -1;
            foreach ( $field['values'] as $value => $count ) {
                if ( ++$number == $SQL_LIMIT ) { break; }
                #error_log( '##### wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
                #    . ':$value=' . $value . ',   $count=' . $count );
                if ( $field['type'] == 'child_of' || $field['type'] == 'parent_of' ) {
                    # for child of and parent of use post title instead of post id for label
                    if ( $posts === NULL ) {
                        $posts = $wpdb->get_results( "SELECT ID, post_type, post_title FROM $wpdb->posts ORDER BY ID", OBJECT_K );
                    }    
                    $label = $posts[$value]->post_title;
                } else if ( $field['type'] == 'radio' ) {
                    # for radio replace option key with something more user friendly
                    $label = $wpcf_field['data']['options'][$value]['value']
                        . '(' . $wpcf_field['data']['options'][$value]['display_value'] .')';
                } else if ( $field['type'] == 'select' ) {
                    # for select replace option key with something more user friendly
                    $label = $wpcf_field['data']['options'][$value]['value']
                        . '(' . $wpcf_field['data']['options'][$value]['title'] . ')';
                } else if ( $field['type'] == 'checkboxes' ) {
                    # checkboxes are handled very differently from radio and select 
                    # Why? seems that the radio/select way would work here also and be simpler
                    $label = $wpcf_field['data']['options'][$value]['title'];
                     if ( $wpcf_field['data']['options'][$value]['display'] == 'db' ) {
                        $label .= ' ' . $wpcf_field['data']['options'][$value]['set_value'];
                    } else {
                        $label .= ' ' . $wpcf_field['data']['options'][$value]['display_value_selected'];
                    }
               } else if ( $field['type'] == 'checkbox' ) {
                    if ( $wpcf_field['data']['display'] == 'db' ) {
                        $label = $value;
                    } else {
                        $label = $wpcf_field['data']['display_value_selected'];
                    }
                } else if ( $field['type'] == 'image' || $field['type'] == 'file' ) {
                    # use only filename for images and files
                    $label = ( $i = strrpos( $value, '/' ) ) !== FALSE ? substr( $value, $i + 1 ) : $value;
                } else if ( $field['type'] == 'date' ) {
                    $label = date( Search_Types_Custom_Fields_Widget::DATE_FORMAT, $value );
                } else if ( $field['type'] == 'url' ) {
                    # for URLs chop off http://
                    if ( substr_compare( $value, 'http://', 0, 7 ) === 0 ) { $label = substr( $value, 7 ); }
                    else if ( substr_compare( $value, 'https://', 0, 8 ) === 0 ) { $label = substr( $value, 8 ); }
                    else { $label = $value; }
                    # and provide line break hints
                    $label = str_replace( '/', '/&#8203;', $label );
                } else {
                    $label = $value;
                }
?>
<input type="checkbox"
    id="<?php echo $meta_key; ?>"
    name="<?php echo $meta_key; ?>[]"
    value="<?php echo $value; ?>">
    <?php echo "$label ($count)"; ?><br>
<?php
            }   # foreach ( $field['values'] as $value => $count ) {
            if ( $number == $SQL_LIMIT && ( $field['type'] != 'child_of'
                && $field['type'] != 'parent_of' && $field['type'] != 'checkboxes' && $field['type'] != 'radio'
                && $field['type'] != 'select' ) ) {
                # only show optional input textbox if there are more than SQL_LIMIT items for fields with user specified values
?>
<input id="<?php echo $meta_key. Search_Types_Custom_Fields_Widget::OPTIONAL_TEXT_VALUE_SUFFIX; ?>"
    name="<?php echo $meta_key . Search_Types_Custom_Fields_Widget::OPTIONAL_TEXT_VALUE_SUFFIX; ?>"
    class="for-select" type="text" style="width:90%;" placeholder="--Enter Search Value--">
<?php
            }
?>
</div>
</div>
<?php
        }   # foreach ( $fields as $meta_key => $field ) {
?>
<script type="text/javascript">
jQuery("button.scpbcfw-display-button").click(function(event){
    if(jQuery(this).text()=="Open"){
        jQuery(this).text("Close");
        jQuery("div.scpbcfw-search-field-values",this.parentNode).css("display","block");
    }else{
        jQuery(this).text("Open");
        jQuery("div.scpbcfw-search-field-values",this.parentNode).css("display","none");
    }
    return false;
});
</script>
<?php
        die();
    } );   # add_action( 'wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE, function() {   
} else {
    add_action( 'wp_enqueue_scripts', function() {
        wp_enqueue_script( 'jquery' );
    } );
	add_filter( 'posts_where', function( $where, $query ) {
        global $wpdb;
        if ( !$query->is_main_query() || !array_key_exists( 'search_types_custom_fields_form', $_REQUEST ) ) { return $where; }
        # this is a Types search request so modify the SQL where clause
        #error_log( '##### posts_where:$_REQUEST=' . print_r( $_REQUEST, TRUE ) );
        $and_or = $_REQUEST['search_types_custom_fields_and_or'] == 'and' ? 'AND' : 'OR';
        # first get taxonomy name to term_taxonomy_id transalation table in case we need the translations
        $results = $wpdb->get_results( <<<EOD
            SELECT x.taxonomy, t.name, x.term_taxonomy_id
                FROM $wpdb->term_taxonomy x, $wpdb->terms t
                WHERE x.term_id = t.term_id
EOD
            , OBJECT );
        $term_taxonomy_ids = array();
        foreach ( $results as $result ) {
            $term_taxonomy_ids[$result->taxonomy][strtolower( $result->name)] = $result->term_taxonomy_id;
        }
        #error_log( '##### filter:posts_where:$term_taxonomy_ids=' . print_r( $term_taxonomy_ids, TRUE ) );
        # merge optional text values into the checkboxes array
        $suffix_len = strlen( Search_Types_Custom_Fields_Widget::OPTIONAL_TEXT_VALUE_SUFFIX );
        foreach ( $_REQUEST as $index => &$request ) {
            if ( $request && substr_compare( $index, Search_Types_Custom_Fields_Widget::OPTIONAL_TEXT_VALUE_SUFFIX,
                -$suffix_len ) === 0 ) {
                $index = substr( $index, 0, strlen( $index ) - $suffix_len );
                if ( is_array( $_REQUEST[$index] ) || !array_key_exists( $index, $_REQUEST ) ) {
                    if ( substr_compare( $index, 'tax-', 0, 4 ) === 0 ) {
                        # for taxonomy values must replace the value with the corresponding term_taxonomy_id
                        $tax_name = substr( $index, 8 );
                        if ( !array_key_exists( $tax_name, $term_taxonomy_ids )
                            || !array_key_exists( strtolower( $request ), $term_taxonomy_ids[$tax_name] ) ) {
                            # kill the original request
                            $request = NULL;
                            continue;
                        }
                        $request = $term_taxonomy_ids[$tax_name][strtolower( $request )];
                    }
                    $_REQUEST[$index][] = $request;
                    # kill the original request
                    $request = NULL;
                }
            }
        }   # foreach ( $_REQUEST as $index => &$request ) {
        unset( $request );
        #error_log( '##### filter:posts_where:$_REQUEST=' . print_r( $_REQUEST, TRUE ) );
        $wpcf_fields = get_option( 'wpcf-fields', array() );    
        #error_log( 'posts_where:$where=' . $where );
        $non_field_keys = array( 'search_types_custom_fields_form', 'search_types_custom_fields_widget_option',
            'search_types_custom_fields_widget_number', 'search_types_custom_fields_and_or', 'post_type' );
        $sql = '';
        foreach ( $_REQUEST as $key => $values ) {
            # here only searches on the table $wpdb->postmeta are processed; everything is done later.
            if ( in_array( $key, $non_field_keys ) ) { continue; }
            $prefix = substr( $key, 0, 8 );
            if ( $prefix === 'tax-cat-' || $prefix === 'tax-tag-' || $prefix === 'pst-std-' ) {
                continue;
            }
            #error_log( '##### filter:posts_where:$key=' . $key );
            #error_log( '##### filter:posts_where:$values=' . print_r( $values, TRUE) );
            if ( !is_array( $values) ) {
                if ( $values ) { $values = array( $values ); }
                else { continue; }
            }
            $values = array_filter( $values ); 
            if ( !$values ) { continue; }
            $sql2 = '';
            foreach ( $values as $value ) {
                if ( $sql2 ) { $sql2 .= ' OR '; }
                if ( $key == 'belongs-parent_of' ) {
                    # parent of is the inverse of child of so ...
                    $sql2 .= "( w.meta_key LIKE '_wpcf_belongs_%' AND w.post_id = $value )";
                } else if ( $key == 'belongs-child_of' ) {
                    # child of is like a custom field except the name is special so ...
                    $sql2 .= "( w.meta_key LIKE '_wpcf_belongs_%' AND w.meta_value = $value )";
                } else {
                    $wpcf_field =& $wpcf_fields[substr( $key, 5 )];
                    if ( $wpcf_field['type'] == 'date' ) {
                        # date can be tricky if user did not enter a complete - to the second - timestamp
                        # need to search on range in that case
                        if ( is_numeric( $value ) ) {
                            $sql2 .= "( w.meta_key = '$key' AND w.meta_value = $value )";
                        } else {
                            list( $t0, $t1 ) = Search_Types_Custom_Fields_Widget::get_timestamp_from_string( $value );    
                           if ( $t1 != $t0 ) {
                                $sql2 .= "( w.meta_key = '$key' AND w.meta_value >= $t0 AND w.meta_value <= $t1 )";
                            } else {
                                $sql2 .= "( w.meta_key = '$key' AND w.meta_value = $t0 )";
                            }
                        }
                    } else {
                        if ( $wpcf_field['type'] == 'radio' || $wpcf_field['type'] == 'select' ) {
                            # for radio and select change value from option key to its value
                            $value = $wpcf_field['data']['options'][$value]['value'];
                        } else if ( $wpcf_field['type'] == 'checkboxes' ) {
                            # checkboxes are tricky since the value bound to 0 means unchecked so must also check the bound value
                            $options =& $wpcf_field['data']['options'];
                            $value = 's:' . strlen($value) .':"' .$value . '";s:' . strlen( $options[$value]['set_value'] ) . ':"'
                                . $options[$value]['set_value'] . '";';
                        } else if ( $wpcf_field['type'] == 'checkbox' ) {
                            # checkbox is tricky since the value bound to 0 means unchecked so must also check the bound value
                            $value = $wpcf_field['data']['set_value'];
                        }
                        # TODO: LIKE may match more than we want on serialized array of numeric values - false match on numeric indices
                        $sql2 .= "( w.meta_key = '$key' AND w.meta_value LIKE '%$value%' )";
                    }
                }
            }   # foreach ( $values as $value ) {
            if ( $key == 'belongs-parent_of' ) {
                # parent of is the inverse of child of so ...
                $sql2 = "( $sql2 ) AND w.meta_value = p.ID";
            } else {
                $sql2 = "( $sql2 ) AND w.post_id = p.ID";
            }
            if ( $sql ) { $sql .= " $and_or "; }
            $sql .= " EXISTS ( SELECT * FROM $wpdb->postmeta w WHERE $sql2 ) ";
        }   # foreach ( $_REQUEST as $key => $values ) {
        if ( $sql ) {
            $sql = "SELECT p.ID FROM $wpdb->posts p WHERE p.post_type = '$_REQUEST[post_type]' AND ( $sql )";
            #error_log( '##### posts_where:meta $sql=' . $sql );
            $ids0 = $wpdb->get_col( $sql );
            if ( $and_or == 'AND' && !$ids0 ) { return ' AND 1 = 2 '; }
        } else {
            $ids0 = FALSE;
        }
        $sql = '';
        foreach ( $_REQUEST as $key => $values ) {
            # here only taxonomies are processed
            if ( in_array( $key, $non_field_keys ) ) { continue; }
            $prefix = substr( $key, 0, 8 );
            if ( $prefix !== 'tax-cat-' && $prefix !== 'tax-tag-' ) {
                continue;
            }
            if ( !is_array( $values) ) {
                if ( $values ) { $values = array( $values ); }
                else { continue; }
            }
            $values = array_filter( $values ); 
            if ( !$values ) { continue; }
            $taxonomy = substr( $key, 8 );
            if ( $sql ) { $sql .= " $and_or "; }
            $sql .= " EXISTS ( SELECT * FROM $wpdb->term_relationships WHERE ( ";
            foreach ( $values as $value ) {
                if ( $value !== $values[0] ) { $sql .= ' OR '; }
                $sql .= 'term_taxonomy_id = ' . $value; 
            }
            $sql .= ') AND object_id = p.ID )';
        }   # foreach ( $_REQUEST as $key => $values ) {
        if ( $sql ) {
            $sql = "SELECT ID FROM $wpdb->posts p WHERE p.post_type = '$_REQUEST[post_type]' AND ( $sql ) ";
            #error_log( '##### posts_where:tax $sql=' . $sql );
            $ids1 = $wpdb->get_col( $sql );
            if ( $and_or == 'AND' && !$ids1 ) { return ' AND 1 = 2 '; }
       } else {
            $ids1 = FALSE;
        }
        $ids = Search_Types_Custom_Fields_Widget::join_arrays( $and_or, $ids0, $ids1 );
        if ( $and_or == 'AND' && $ids !== FALSE && !$ids ) { return ' AND 1 = 2 '; }
        if ( array_key_exists( 'pst-std-attachment', $_REQUEST ) && $_REQUEST['pst-std-attachment'] ) {
            $sql = "SELECT post_parent FROM $wpdb->posts WHERE ID IN ( " . implode( ',', $_REQUEST['pst-std-attachment'] ) . " )";
            $ids2 = $wpdb->get_col( $sql );
            if ( $and_or == 'AND' && !$ids2 ) { return ' AND 1 = 2 '; }
        } else {
            $ids2 = FALSE;
        }
        $ids = Search_Types_Custom_Fields_Widget::join_arrays( $and_or, $ids, $ids2 );
        if ( $and_or == 'AND' && $ids !== FALSE && !$ids ) { return ' AND 1 = 2 '; }
        # finally handle post_content - post_title and post_excerpt are included in the search of post_content
        if ( array_key_exists( 'pst-std-post_content', $_REQUEST ) && $_REQUEST['pst-std-post_content'] ) {
            $sql = <<<EOD
                SELECT ID FROM $wpdb->posts WHERE post_type = "$_REQUEST[post_type]" AND post_status = "publish"
                    AND ( post_content  LIKE "%{$_REQUEST['pst-std-post_content']}%"
                        OR post_title   LIKE "%{$_REQUEST['pst-std-post_content']}%"
                        OR post_excerpt LIKE "%{$_REQUEST['pst-std-post_content']}%" )
EOD;
            $ids3 = $wpdb->get_col( $sql );
            if ( $and_or == 'AND' && !$ids3 ) { return ' AND 1 = 2 '; }
        } else {
            $ids3 = FALSE;
        }
        $ids = Search_Types_Custom_Fields_Widget::join_arrays( $and_or, $ids, $ids3 );
        if ( $and_or == 'AND' && $ids !== FALSE && !$ids ) { return ' AND 1 = 2 '; }
        if ( $ids ) {
            $ids = implode( ', ', $ids );
            $where = " AND ID IN ( $ids ) ";
        } else {
            $where = " AND post_type = '$_REQUEST[post_type]' AND post_status = 'publish' ";
        }
        #error_log( '##### posts_where:$where=' . $where );
        return $where;
    }, 10, 2 );
}

?>