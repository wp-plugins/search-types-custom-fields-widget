<?php

/*
Plugin Name: Search Types Custom Fields Widget
Plugin URI: http://alttypes.wordpress.com/
Description: Widget for searching Types custom fields and custom taxonomies.
Version: 0.4.5
Author: Magenta Cuda (PHP), Black Charger (JavaScript)
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

list( $major, $minor ) = sscanf( phpversion(), '%D.%D' );
#error_log( '##### Magic_Fields_2_Toolkit_Init::__construct():phpversion()=' . $major . ',' . $minor );
$tested_major = 5;
$tested_minor = 4;
if ( !( $major > $tested_major || ( $major == $tested_major && $minor >= $tested_minor ) ) ) {
    add_action( 'admin_notices', function() use ( $major, $minor, $tested_major, $tested_minor ) {
        echo <<<EOD
<div style="padding:10px 20px;border:2px solid red;margin:50px 20px;font-weight:bold;">
    Search Types Custom Fields Widget will not work with PHP version $major.$minor;
    Please uninstall it or upgrade your PHP version to $tested_major.$tested_minor or later.
</div>
EOD;
    } );
    return;
}

if ( is_admin() ) {
    add_action( 'admin_enqueue_scripts', function() {
        wp_enqueue_script( 'jquery-ui-draggable' );
        wp_enqueue_script( 'jquery-ui-droppable' );
    } );
}

class Search_Types_Custom_Fields_Widget extends WP_Widget {
    
    # start of user configurable constants
    const DATE_FORMAT = DATE_RSS;                                      # how to display date/time values
    const SQL_LIMIT = 16;                                              # maximum number of post types/custom fields to display
    #const SQL_LIMIT = 2;                                              # TODO: this limit for testing only replace with above
    # end of user configurable constants
    
    const OPTIONAL_TEXT_VALUE_SUFFIX = '-stcfw-optional-text-value';   # suffix to append to optional text input for a search field
    const OPTIONAL_MINIMUM_VALUE_SUFFIX = '-stcfw-minimum-value';      # suffix to append to optional minimum/maximum value text 
    const OPTIONAL_MAXIMUM_VALUE_SUFFIX = '-stcfw-maximum-value';      #     inputs for a numeric search field
    const GET_FORM_FOR_POST_TYPE = 'get_form_for_post_type';
    const PARENT_OF = 'For ';                                          # label for parent of relationship
    CONST CHILD_OF = 'Of ';                                            # label for child of relationship
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
            if ( !in_array( $name, array_diff( array_keys( $instance ),
                array( 'maximum_number_of_items', 'set_is_search', 'enable_table_view_option', 'search_table_width' ) ) ) ) {
                    continue;
            }      
?>      
<option value="<?php echo $name; ?>"><?php echo "$name ($result->count)"; ?></option>
<?php
        }   # foreach ( $results as $result ) {
?>
</select>
</div>
<div id="search-types-custom-fields-parameters"></div>
<div id="search-types-custom-fields-submit-box" style="display:none">
<div style="border:2px solid black;padding:5px;margin:5px;border-radius:7px;">
<div style="text-align:center;margin:10px;">
Results should satisfy<br> 
<input type="radio" name="search_types_custom_fields_and_or" value="and" checked><strong>All</strong>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="search_types_custom_fields_and_or" value="or"><strong>Any</strong></br>
of the search conditions.
</div>
<?php
        if ( $instance['enable_table_view_option'] === 'table view option enabled' ) {
?>
<hr>
<div style="margin:10px">
<input type="checkbox" name="search_types_custom_fields_show_using_macro" value="use macro"
    style="float:right;margin-top:5px;margin-left:5px;">
Show search results in alternate format:
</div>
<?php
        }
?>
</div>
<div style="text-align:right;">
<input id="search-types-custom-fields-submit" type="submit" value="Start Search" style="color:black;border:2px solid black;" disabled>
&nbsp;&nbsp;
</div>
</div>
</form>
<script>
jQuery("form#search-types-custom-fields-widget-<?php echo $this->number; ?> select#post_type").change(function(){
    jQuery.post(
        "<?php echo admin_url( 'admin-ajax.php' ); ?>",{
            action:"<?php echo Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE; ?>",
            stcfw_get_form_nonce:'<?php echo wp_create_nonce( Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE ); ?>',
            post_type:jQuery("form#search-types-custom-fields-widget-<?php echo $this->number; ?>"
                +" select#post_type option:selected").val(),
            search_types_custom_fields_widget_option:jQuery("form#search-types-custom-fields-widget-<?php echo $this->number; ?>"
                +" input#search_types_custom_fields_widget_option").val(),
            search_types_custom_fields_widget_number:jQuery("form#search-types-custom-fields-widget-<?php echo $this->number; ?>"
                +" input#search_types_custom_fields_widget_number").val()
        },
        function(response){
            jQuery("form#search-types-custom-fields-widget-<?php echo $this->number; ?>"
                +" div#search-types-custom-fields-parameters").html(response);
            jQuery("form#search-types-custom-fields-widget-<?php echo $this->number; ?>"
                +" input#search-types-custom-fields-submit").prop("disabled",false);
            jQuery("form#search-types-custom-fields-widget-<?php echo $this->number; ?>"
                +" div#search-types-custom-fields-submit-box").css("display","block");
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
        return array_map( function( $values ) {
            return is_array( $values) ? array_map( strip_tags, $values ) : strip_tags( $values );
        }, $new );
    }
    
    # form() is for the administrator to specify the post types and custom fields that will be searched
    
    public function form( $instance ) {
        global $wpdb;
        $SQL_LIMIT = self::SQL_LIMIT;
        #error_log( '##### Search_Types_Custom_Fields_Widget::form():$instance=' . print_r( $instance, TRUE ) );
        $wpcf_types  = get_option( 'wpcf-custom-types', array() );
        $wpcf_fields = get_option( 'wpcf-fields',       array() );
        #error_log( '##### Search_Types_Custom_Fields_Widget::form():$wpcf_types='  . print_r( $wpcf_types, TRUE  ) );
        #error_log( '##### Search_Types_Custom_Fields_Widget::form():$wpcf_fields=' . print_r( $wpcf_fields, TRUE ) );
?>
<h4>Select Search Fields and Table Display Fields for:</h4>
<?php
        # use all Types custom post types and the WordPress built in "post" and "page"
        $wpcf_types_keys = '"' . implode( '", "', array_keys( $wpcf_types ) ) . '", "post", "page"';
        $sql = <<<EOD
            SELECT post_type, COUNT(*) count FROM $wpdb->posts
                WHERE post_type IN ( $wpcf_types_keys ) AND post_status = "publish" 
                GROUP BY post_type ORDER BY count DESC
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
            #error_log( '##### Search_Using_Magic_Fields_Widget::form():$name=' . $name );       
            $selected = $instance[$name];
            $show_selected = $instance['show-' . $name];
?>
<div class="scpbcfw-search-fields">
<span style="font-size=16px;font-weight:bold;float:left;"><?php echo "$name ($type->count)"; ?></span>
<button class="scpbcfw-display-button" style="font-size:12px;font-weight:bold;padding:3px;float:right;">Open</button>
<div style="clear:both;"></div>
<div class="scpbcfw-search-field-values" style="display:none;">
<style scoped>
div.mf2tk-selectable-field-after{height:2px;background-color:white;}
div.mf2tk-selectable-field-after.mf2tk-hover{background-color:black;}
div.mf2tk-selectable-taxonomy-after{height:2px;background-color:white;}
div.mf2tk-selectable-taxonomy-after.mf2tk-hover{background-color:black;}
</style>
<!-- before drop point -->
<div><div class="mf2tk-selectable-taxonomy-after"></div></div>
<?php
            # do taxonomies first
            $the_taxonomies = array();
            foreach ( $db_taxonomies as &$db_taxonomy ) {
                if ( $db_taxonomy->post_type != $name ) { continue; }
                $wp_taxonomy =& $wp_taxonomies[$db_taxonomy->taxonomy];
                $the_taxonomies[$wp_taxonomy->name] =& $db_taxonomy;
            }
            unset( $db_taxonomy, $wp_taxonomy );
            $previous = !empty( $instance['tax-order-' . $name] ) ? explode( ';', $instance['tax-order-' . $name] ) : array();
            # remove taxonomy prefixes
            $previous = array_map( function( $value ) {
                $value = str_replace( 'tax-cat-', '', $value, $count );
                if ( !$count ) { $value = str_replace( 'tax-tag-', '', $value ); }
                return $value;
            }, $previous );
            #error_log( '##### Search_Using_Magic_Fields_Widget::form():$previous=' . print_r( $previous, true ) );
            $current = array_keys( $the_taxonomies );
            #error_log( '##### Search_Using_Magic_Fields_Widget::form():$current=' . print_r( $current, true ) );
            $previous = array_intersect( $previous, $current );
            #error_log( '##### Search_Using_Magic_Fields_Widget::form():$previous=' . print_r( $previous, true ) );
            $new = array_diff( $current, $previous );
            #error_log( '##### Search_Using_Magic_Fields_Widget::form():$new=' . print_r( $new, true ) );
            $current = array_merge( $previous, $new );
            #error_log( '##### Search_Using_Magic_Fields_Widget::form():$current=' . print_r( $current, true ) );
            foreach ( $current as $tax_name ) {
                $db_taxonomy =& $the_taxonomies[$tax_name];
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
<div class="mf2tk-selectable-taxonomy">
    <input type="checkbox"
        class="mf2tk-selectable-taxonomy" 
        id="<?php echo $this->get_field_id( $name ); ?>"
        name="<?php echo $this->get_field_name( $name ); ?>[]"
        value="<?php echo $tax_type . $wp_taxonomy->name; ?>"
        <?php if ( $selected && in_array( $tax_type . $wp_taxonomy->name, $selected ) ) { echo ' checked'; } ?>>
    <input type="checkbox"
        id="<?php echo $this->get_field_id( 'show-' . $name ); ?>"
        class="scpbcfw-select-content-macro-display-field"
        name="<?php echo $this->get_field_name( 'show-' . $name ); ?>[]"
        value="<?php echo $tax_type . $wp_taxonomy->name; ?>"
        <?php if ( $show_selected && in_array( $tax_type . $wp_taxonomy->name, $show_selected ) ) { echo ' checked'; } ?>
        <?php if ( $instance && !isset( $instance['enable_table_view_option'] ) ) { echo 'disabled'; } ?>>
<?php echo "{$wp_taxonomy->label}{$tax_label} ($db_taxonomy->count)"; ?>
    <!-- a drop point -->
    <div class="mf2tk-selectable-taxonomy-after"></div>
</div>
<?php
            }   #  foreach ( $db_taxonomies as $db_taxonomy ) {
            # now do custom fields and post content
            # again the sql is complicated since a single post may have multiple values for a custom field
            $sql = <<<EOD
                SELECT field_name, COUNT(*) count
                    FROM ( SELECT m.meta_key field_name, m.post_id FROM $wpdb->postmeta m, $wpdb->posts p
                        WHERE m.post_id = p.ID AND p.post_type = '$name' AND m.meta_key LIKE 'wpcf-%'
                            AND m.meta_value IS NOT NULL AND m.meta_value != '' AND m.meta_value != 'a:0:{}'
                        GROUP BY m.meta_key, m.post_id ) fields
                    GROUP BY field_name ORDER BY count DESC
EOD;
            #error_log( '##### Search_Types_Custom_Fields_Widget::form():$sql=' . $sql );
            $fields = $wpdb->get_results( $sql, OBJECT_K );
            #error_log( '##### Search_Types_Custom_Fields_Widget::form():array_keys( $fields )='
            #    . print_r( array_keys( $fields ), TRUE ) );
            $sql = <<<EOD
                SELECT gf.meta_value FROM $wpdb->postmeta pt, $wpdb->postmeta gf WHERE pt.post_id = gf.post_id
                    AND pt.meta_key = "_wp_types_group_post_types" AND pt.meta_value LIKE "%,$name,%"
                    AND gf.meta_key = "_wp_types_group_fields"
EOD;
            #error_log( '##### Search_Types_Custom_Fields_Widget::form():$sql=' . $sql );
            $fields_for_type = $wpdb->get_col( $sql );
            #error_log( '##### Search_Types_Custom_Fields_Widget::form():$fields_for_type=' . print_r ($fields_for_type, TRUE ) );            
            $fields_for_type = array_reduce( $fields_for_type, function( $result, $item ) {
                return array_merge( $result, explode( ',', trim( $item, ',' ) ) );
            }, array() );
            #error_log( '##### Search_Types_Custom_Fields_Widget::form():$fields_for_type=' . print_r ($fields_for_type, TRUE ) );          
            foreach ( $fields as $meta_key => &$field ) {
                $field_name = substr( $meta_key, 5 );
                if ( array_key_exists( $field_name, $wpcf_fields ) && in_array( $field_name, $fields_for_type ) ) {
                    $wpcf_field =& $wpcf_fields[$field_name];
                    $field->label = $wpcf_field['name'];
                    $field->large = in_array( $wpcf_field['type'], array( 'textarea', 'wysiwyg' ) );
                    unset( $wpcf_field );
                } else {
                    $field = NULL;   # not a valid Types custom field so tag it for skipping.
                }
            }
            unset( $field );
            #error_log( '##### Search_Types_Custom_Fields_Widget::form():$fields=' . print_r( $fields, true ) );
            # add fields for parent of, child of, post_content and attachment
            $sql = <<<EOD
                SELECT m.meta_key, COUNT( DISTINCT m.post_id ) count FROM $wpdb->postmeta m, $wpdb->posts p
                    WHERE m.post_id = p.ID AND p.post_type = "$name" AND m.meta_key LIKE '_wpcf_belongs_%'
                    GROUP BY m.meta_key
EOD;
            #error_log( '##### Search_Types_Custom_Fields_Widget::form():$sql=' . $sql );
            $results = $wpdb->get_results( $sql, OBJECT );
            foreach ( $results as $result ) {
                $fields[$result->meta_key] = (object) array(
                    'label' => self::CHILD_OF
                        . $wpcf_types[substr( $result->meta_key, 14, strlen( $result->meta_key ) - 17 )]['labels']['name'], 
                    'count' => $result->count
                );
            }
            unset( $results, $result );
            $sql = <<<EOD
                SELECT pi.post_type, m.meta_key, COUNT( DISTINCT m.meta_value ) count
                    FROM $wpdb->postmeta m, $wpdb->posts pv, $wpdb->posts pi
                    WHERE m.meta_value = pv.ID AND pv.post_type = "$name" AND m.post_id = pi.ID
                        AND m.meta_key LIKE '_wpcf_belongs_%'
                    GROUP BY pi.post_type
EOD;
            #error_log( '##### Search_Types_Custom_Fields_Widget::form():$sql=' . $sql );
            $results = $wpdb->get_results( $sql, OBJECT );
            #error_log( '##### Search_Types_Custom_Fields_Widget::form():$results=' . print_r( $results, TRUE ) );
            foreach ( $results as $result ) {
                $fields["inverse_{$result->post_type}_{$result->meta_key}"] = (object) array(
                    'label' => self::PARENT_OF . $wpcf_types[$result->post_type]['labels']['name'], 
                    'count' => $result->count
                );
            }
            $fields['pst-std-post_content'] = (object) array( 'label' => 'Post Content', 'count' => $type->count, 'large' => true );
            $sql = <<<EOD
                SELECT COUNT( DISTINCT a.post_parent ) FROM $wpdb->posts a, $wpdb->posts p
                WHERE a.post_type = "attachment" AND a.post_parent = p.ID AND p.post_type = "$name" AND p.post_status = "publish"
EOD;
            #error_log( '##### Search_Types_Custom_Fields_Widget::form():$sql=' . $sql );
            $fields['pst-std-attachment']   = (object) array( 'label' => 'Attachment', 'count' => $wpdb->get_var( $sql ) );
            #error_log( '##### Search_Types_Custom_Fields_Widget::form():$fields=' . print_r( $fields, TRUE ) );
            # remove all invalid custom fields.
            $fields = array_filter( $fields );
            $current = array_keys( $fields );
            #error_log( '##### Search_Using_Magic_Fields_Widget::form():$current=' . print_r( $current, true ) );
            $previous = !empty( $instance['order-' . $name] ) ? explode( ';', $instance['order-' . $name] ) : array();
            $previous = array_intersect( $previous, $current );
            #error_log( '##### Search_Using_Magic_Fields_Widget::form():$previous=' . print_r( $previous, true ) );
            $new = array_diff( $current, $previous );
            #error_log( '##### Search_Using_Magic_Fields_Widget::form():$new=' . print_r( $new, true ) );
            $current = array_merge( $previous, $new );
            #error_log( '##### Search_Using_Magic_Fields_Widget::form():$current=' . print_r( $current, true ) );
?>
<!-- before drop point -->
<div><div class="mf2tk-selectable-field-after"></div></div>
<?php
            # now display all fields with checkboxes
            foreach ( $current as $meta_key ) {
                $field =& $fields[$meta_key];
                #error_log( '##### Search_Types_Custom_Fields_Widget::form():$meta_key=' . $meta_key );
                #error_log( '##### Search_Types_Custom_Fields_Widget::form():$field='    . print_r( $field, TRUE ) );
?>
<div class="mf2tk-selectable-field">
    <input type="checkbox"
        class="mf2tk-selectable-field"
        id="<?php echo $this->get_field_id( $name ); ?>"
        name="<?php echo $this->get_field_name( $name ); ?>[]"
        value="<?php echo $meta_key; ?>"
        <?php if ( $selected && in_array( $meta_key, $selected ) ) { echo ' checked'; } ?>>
    <input type="checkbox" id="<?php echo $this->get_field_id( 'show-' . $name ); ?>"
        name="<?php echo $this->get_field_name( 'show-' . $name ); ?>[]"
        <?php if ( !$field->large ) {
            echo 'class="scpbcfw-select-content-macro-display-field"';
        } ?>
        value="<?php echo $meta_key; ?>" <?php if ( $show_selected && in_array( $meta_key, $show_selected ) ) { echo ' checked'; } ?>
        <?php if ( $instance && !isset( $instance['enable_table_view_option'] ) || $field->large ) {
            echo 'disabled';
        } ?>>
    <?php echo "$field->label ($field->count)"; ?>
    <!-- a drop point -->
    <div class="mf2tk-selectable-field-after"></div>
</div>
<?php
            }   # foreach ( $fields as $meta_key => $field ) {
?>
<input type="hidden" class="mf2tk-selectable-taxonomy-order" id="<?php echo $this->get_field_id( 'tax-order-' . $name ); ?>"
    name="<?php echo $this->get_field_name( 'tax-order-' . $name ); ?>"
    value="<?php echo isset( $instance['tax-order-' . $name] ) ? $instance['tax-order-' . $name] : ''; ?>">
<input type="hidden" class="mf2tk-selectable-field-order" id="<?php echo $this->get_field_id( 'order-' . $name ); ?>"
    name="<?php echo $this->get_field_name( 'order-' . $name ); ?>"
    value="<?php echo isset( $instance['order-' . $name] ) ? $instance['order-' . $name] : ''; ?>">
</div>
</div>
<?php
        }
?>
<div style="border:2px solid gray;padding:5px;margin:5px;border-radius:7px;">
<div style="padding:10px;border:1px solid gray;margin:5px;">
<input type="number" min="4" max="1024" 
    id="<?php echo $this->get_field_id( 'maximum_number_of_items' ); ?>"
    name="<?php echo $this->get_field_name( 'maximum_number_of_items' ); ?>"
    value="<?php echo !empty( $instance['maximum_number_of_items'] ) ? $instance['maximum_number_of_items'] : 16; ?>"
    size="4" style="float:right;text-align:right;">
Maximum number of items to display per custom field:
<div style="clear:both;"></div>
</div>
<div style="padding:10px;border:1px solid gray;margin:5px;">
<input type="checkbox"
    id="<?php echo $this->get_field_id( 'set_is_search' ); ?>"
    name="<?php echo $this->get_field_name( 'set_is_search' ); ?>"
    value="is search" <?php if ( isset( $instance['set_is_search'] ) ) { echo 'checked'; } ?>
    style="float:right;margin-top:5px;margin-left:5px;">
Display search results using the same template as the default WordPress search:
<div style="clear:both;"></div>
</div>
<div style="padding:10px;border:1px solid gray;margin:5px;">
<input type="checkbox"
    id="<?php echo $this->get_field_id( 'enable_table_view_option' ); ?>"
    name="<?php echo $this->get_field_name( 'enable_table_view_option' ); ?>"
    value="table view option enabled"
    <?php if ( !$instance || isset( $instance['enable_table_view_option'] ) ) { echo 'checked'; } ?>
    style="float:right;margin-top:5px;margin-left:5px;">
Enable option to display search results using a table of posts:
<div style="clear:both;"></div>
</div>
<div style="padding:10px;border:1px solid gray;margin:5px;">
<input type="number" min="256" max="8192" 
    id="<?php echo $this->get_field_id( 'search_table_width' ); ?>"
    name="<?php echo $this->get_field_name( 'search_table_width' ); ?>"
    <?php if ( !empty( $instance['search_table_width'] ) ) { echo "value=\"$instance[search_table_width]\""; } ?>
    <?php if ( $instance && !isset( $instance['enable_table_view_option'] ) ) { echo 'disabled'; } ?>
    placeholder="from css"
    size="5" style="float:right;text-align:right;">
Width in pixels of the table of search results:
<div style="clear:both;"></div>
</div>
</div>
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
jQuery("input[type='checkbox']#<?php echo $this->get_field_id( 'enable_table_view_option' ); ?>").change(function(event){
    jQuery("input[type='number']#<?php echo $this->get_field_id( 'search_table_width' ); ?>").prop("disabled",!jQuery(this)
        .prop("checked"));
    jQuery("input[type='checkbox'].scpbcfw-select-content-macro-display-field").prop("disabled",!jQuery(this).prop("checked"));
});
jQuery(document).ready(function(){
    jQuery("div.mf2tk-selectable-field").draggable({cursor:"crosshair",revert:true});
    jQuery("div.mf2tk-selectable-field-after").droppable({accept:"div.mf2tk-selectable-field",tolerance:"touch",
        hoverClass:"mf2tk-hover",drop:function(e,u){
            jQuery(this.parentNode).after(u.draggable);
            var o="";
            jQuery("input.mf2tk-selectable-field[type='checkbox']",this.parentNode.parentNode).each(function(i){
                o+=jQuery(this).val()+";";
            });
            jQuery("input.mf2tk-selectable-field-order[type='hidden']",this.parentNode.parentNode).val(o);
    }});
    jQuery("div.mf2tk-selectable-taxonomy").draggable({cursor:"crosshair",revert:true});
    jQuery("div.mf2tk-selectable-taxonomy-after").droppable({accept:"div.mf2tk-selectable-taxonomy",tolerance:"touch",
        hoverClass:"mf2tk-hover",drop:function(e,u){
            jQuery(this.parentNode).after(u.draggable);
            var o="";
            jQuery("input.mf2tk-selectable-taxonomy[type='checkbox']",this.parentNode.parentNode).each(function(i){
                o+=jQuery(this).val()+";";
            });
            jQuery("input.mf2tk-selectable-taxonomy-order[type='hidden']",this.parentNode.parentNode).val(o);
    }});
});
</script>
<?php
    }
    
    # helper functions
    
    public static function search_wpcf_field_options( &$options, $option, $value ) {
        foreach ( $options as $k => $v ) {
            if ( !empty( $v[$option]) && $v[$option] == $value ) { return $k; }
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
        #error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE . ':$_POST='
        #    . print_r( $_POST, TRUE ) );
        if ( !isset( $_POST['stcfw_get_form_nonce'] ) || !wp_verify_nonce( $_POST['stcfw_get_form_nonce'],
            Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE ) ) {
            error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE . ':nonce:die' );
            die;
        }
        $wpcf_types  = get_option( 'wpcf-custom-types', array() );
        #error_log( '##### Search_Types_Custom_Fields_Widget::form():$wpcf_types=' . print_r( $wpcf_types, TRUE ) );
?>
<h4>Please specify search conditions:<h4>
<?php
        #error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
        #    . ':$_REQUEST=' . print_r( $_REQUEST, TRUE ) );
        $option = get_option( $_REQUEST['search_types_custom_fields_widget_option'] );
        #error_log( '##### wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
        #    . ':$option=' . print_r( $option, TRUE ) );
        $widget_number = $_REQUEST['search_types_custom_fields_widget_number'];
        $selected = $option[$widget_number][$_REQUEST['post_type']];
        $SQL_LIMIT = $option[$widget_number]['maximum_number_of_items'];
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
        # extract taxonomy elements from selected
        $tax_selected = array_filter( array_map( function( $value ) {
            $value = str_replace( 'tax-cat-', '', $value, $count );
            if ( !$count ) {
                $value = str_replace( 'tax-tag-', '', $value, $count );
                if ( !$count ) {
                    $value = false;
                }
            }
            return $value;
        }, $selected ) );
        # now display the taxonomy results
        foreach ( $tax_selected as $tax_name ) {
            $values =& $terms[$tax_name];
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
        if ( $selected_imploded = array_filter( $selected, function( $v ) { return strpos( $v, '_wpcf_belongs_' ) !== 0; } ) ) {
            $selected_imploded = '("' . implode( '","', $selected_imploded ) . '")';
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
                $wpcf_field =& $wpcf_fields[substr( $result->meta_key, 5 )];
                # skip false values except for single checkbox
                if ( $wpcf_field['type'] != 'checkbox' && !$result->meta_value ) { continue; }
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
            unset( $selected_imploded );
        }   # if ( $selected_imploded = array_filter( $selected, function( $v ) { return strpos( $v, '_wpcf_belongs_' ) !== 0; } ) ) {
        # get childs of selected parents
        if ( $selected_child_of = array_filter( $selected, function( $v ) { return strpos( $v, '_wpcf_belongs_' ) === 0; } ) ) {
            $selected_imploded = '("' . implode( '","', $selected_child_of ) . '")';
            # do all parent types with one sql query and filter the results later
            $sql = <<<EOD
                SELECT m.meta_key, m.meta_value, COUNT(*) count
                    FROM $wpdb->postmeta m, $wpdb->posts pi, $wpdb->posts pv
                    WHERE m.post_id = pi.ID AND m.meta_value = pv.ID
                        AND m.meta_key IN $selected_imploded AND pi.post_type = "$_REQUEST[post_type]"
                        GROUP BY m.meta_key, m.meta_value
EOD;
            #error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
            #    . ':$sql=' . $sql );
            $results = $wpdb->get_results( $sql );
            #error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
            #    . ':$results=' . print_r( $results, TRUE ) );
            foreach ( $selected_child_of as $parent ) {
                # do each parent type but results need to be filtered to this parent type
                if ( $selected_results = array_filter( $results, function( $result ) use ( $parent ) { 
                    return $result->meta_key == $parent; 
                } ) ) {
                    #error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
                    #    . ':$new_results=' . print_r( $new_results, TRUE ) );
                    $fields[$parent] = array(
                        'type' => 'child_of',
                        'label' => Search_Types_Custom_Fields_Widget::CHILD_OF
                            . $wpcf_types[substr( $parent, 14, strlen( $parent ) - 17 )]['labels']['name'], 
                        'values' => array_reduce( $selected_results, function( $new_results, $result ) {
                                #error_log( '##### array_reduce():function():$result=' . print_r( $result, TRUE ) );
                                $new_results[$result->meta_value] = $result->count;
                                return $new_results;
                            }, array() )
                    );
                    #error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
                    #    . ':$fields[$parent]=' . print_r( $fields[$parent], TRUE ) );
                }
            }
            unset( $selected_imploded );
        }   # if ( $selected_child_of = array_filter( $selected, function( $v ) { return strpos( $v, '_wpcf_belongs_' ) === 0; } ) ) {
        # get parents of selected childs
        if ( $selected_parent_of = array_filter( $selected, function( $v ) { return strpos( $v, 'inverse_' ) === 0; } ) ) {
            #error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
            #    . ':$selected_parent_of=' . print_r( $selected_parent_of, TRUE ) );
            # get all the child post types
            $post_types = array_map( function( $v ) { return substr( $v, 8, strpos( $v, '__wpcf_belongs_' ) - 8 ); },
                $selected_parent_of );
            #error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
            #    . ':$post_types=' . print_r( $post_types, TRUE ) );
            # get the '_wpcf_belongs_' meta_key - they are all identical so just use the first one
            $selected_parent_of = array_pop( $selected_parent_of );
            #error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
            #    . ':$selected_parent_of=' . $selected_parent_of );
            $selected_parent_of = substr( $selected_parent_of, strpos( $selected_parent_of, '_wpcf_belongs_' ) );
            # we can use just one sql query to do all the post types together and filter the results later
            $sql = <<<EOD
                SELECT pi.post_type, m.post_id, COUNT(*) count
                    FROM $wpdb->postmeta m, $wpdb->posts pi, $wpdb->posts pv
                    WHERE m.post_id = pi.ID AND m.meta_value = pv.ID
                        AND m.meta_key = "$selected_parent_of" AND pv.post_type = "$_REQUEST[post_type]"
                    GROUP BY pi.post_type, m.post_id
EOD;
            #error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
            #    . ':$sql=' . $sql );
            $results = $wpdb->get_results( $sql );
            #error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
            #    . ':$results=' . print_r( $results, TRUE ) );
            foreach ( $post_types as $post_type ) {
                # do each post type but the results need to be filtered this post type
                if ( $selected_results = array_filter( $results, function( $result ) use ( $post_type ) { 
                    return $result->post_type == $post_type; 
                } ) ) {
                    #error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
                    #    . ':$new_results=' . print_r( $new_results, TRUE ) );
                    $fields["inverse_{$post_type}_{$selected_parent_of}"] = array(
                        'type' => 'parent_of',
                        'label' => Search_Types_Custom_Fields_Widget::PARENT_OF . $wpcf_types[$post_type]['labels']['name'], 
                        'values' => array_reduce( $selected_results, function( $new_results, $result ) {
                                #error_log( '##### array_reduce():function():$result=' . print_r( $result, TRUE ) );
                                $new_results[$result->post_id] = $result->count;
                                return $new_results;
                            }, array() )
                    );
                    #error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE
                    #    . ':$fields[\'inverse\' . $child]=' . print_r( $fields['inverse' . $child], TRUE ) );
                }
            }
        }   # if ( $selected_parent_of = array_filter( $selected, function( $v ) { return strpos( $v, 'inverse_' ) === 0; } ) ) {
        if ( in_array( 'pst-std-post_content', $selected ) ) {
            $fields['pst-std-post_content'] = array( 'type' => 'textarea',   'label' => 'Post Content' );
        }
        if ( in_array( 'pst-std-attachment', $selected ) ) {
            $fields['pst-std-attachment']   = array( 'type' => 'attachment', 'label' => 'Attachment'   );
        }
        #error_log( 'action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE . ':$fields='
        #    . print_r( $fields, TRUE ) );
        $posts = NULL;
        $field_selected = array_filter( $selected, function( $value ) {
            return substr_compare( $value, 'tax-cat-', 0, 8 ) !== 0 && substr_compare( $value, 'tax-tag-', 0, 8 ) !== 0;
        } );
        #error_log( 'action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE . ':$field_selected='
        #    . print_r( $field_selected, true ) );
        #error_log( 'action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE . ':array_keys( $fields )='
        #    . print_r( array_keys( $fields ), true ) );
        foreach ( $field_selected as $meta_key ) {
            $field =& $fields[$meta_key];
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
                    $label = $wpcf_field['data']['options'][$value]['title']
                        . ( $wpcf_field['data']['display'] == 'value'
                            ? ( '(' . $wpcf_field['data']['options'][$value]['display_value'] . ')' )
                            : ( '(' . $wpcf_field['data']['options'][$value]['value'] . ')' ) );
                } else if ( $field['type'] == 'select' ) {
                    # for select replace option key with something more user friendly
                    $label = $wpcf_field['data']['options'][$value]['value']
                        . '(' . $wpcf_field['data']['options'][$value]['title'] . ')';
                } else if ( $field['type'] == 'checkboxes' ) {
                    # checkboxes are handled very differently from radio and select 
                    # Why? seems that the radio/select way would work here also and be simpler
                    $label = $wpcf_field['data']['options'][$value]['title'];
                     if ( $wpcf_field['data']['options'][$value]['display'] == 'db' ) {
                        $label .= ' (' . $wpcf_field['data']['options'][$value]['set_value'] . ')';
                    } else if ( $wpcf_field['data']['options'][$value]['display'] == 'value' ) {
                        $label .= ' (' . $wpcf_field['data']['options'][$value]['display_value_selected'] . ')';
                    }
                } else if ( $field['type'] == 'checkbox' ) {
                    if ( $wpcf_field['data']['display'] == 'db' ) {
                        $label = $value;
                    } else {
                        if ( $value ) {
                            $label = $wpcf_field['data']['display_value_selected'];
                        } else {
                            $label = $wpcf_field['data']['display_value_not_selected'];
                        }
                    }
                } else if ( $field['type'] == 'image' || $field['type'] == 'file' || $field['type'] == 'audio'
                    || $field['type'] == 'video' ) {
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
<input id="<?php echo $meta_key . Search_Types_Custom_Fields_Widget::OPTIONAL_TEXT_VALUE_SUFFIX; ?>"
    name="<?php echo $meta_key . Search_Types_Custom_Fields_Widget::OPTIONAL_TEXT_VALUE_SUFFIX; ?>"
    class="for-select" type="text" style="width:90%;" placeholder="--Enter Search Value--">
<?php
            }
            if ( $field['type'] == 'numeric' || $field['type'] == 'date' ) {
                # only show minimum/maximum input textbox for numeric and date custom fields
?>
<h4>Range Search</h4>
<input id="<?php echo $meta_key . Search_Types_Custom_Fields_Widget::OPTIONAL_MINIMUM_VALUE_SUFFIX; ?>"
    name="<?php echo $meta_key . Search_Types_Custom_Fields_Widget::OPTIONAL_MINIMUM_VALUE_SUFFIX; ?>"
    class="for-select" type="text" style="width:90%;" placeholder="--Enter Minimum Value--">
<input id="<?php echo $meta_key . Search_Types_Custom_Fields_Widget::OPTIONAL_MAXIMUM_VALUE_SUFFIX; ?>"
    name="<?php echo $meta_key . Search_Types_Custom_Fields_Widget::OPTIONAL_MAXIMUM_VALUE_SUFFIX; ?>"
    class="for-select" type="text" style="width:90%;" placeholder="--Enter Maximum Value--">
<?php
            }
?>
</div>
</div>
<?php
        }   # foreach ( $fields as $meta_key => $field ) {
        unset( $field, $wpcf_field );
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
    add_action( 'parse_query', function( &$query ) {
        if ( !$query->is_main_query() || !array_key_exists( 'search_types_custom_fields_form', $_REQUEST ) ) { return; }
        #error_log( '##### action:parse_query():$_REQUEST=' . print_r( $_REQUEST, true ) );
        #error_log( '##### action:parse_query():$args=' . print_r( $query, true ) );
        $option = get_option( $_REQUEST['search_types_custom_fields_widget_option'] );
        #error_log( '##### action:parse_query():$option=' . print_r( $option, true ) );
        $number = $_REQUEST['search_types_custom_fields_widget_number'];
        if ( isset( $option[$number]['set_is_search'] ) ) { $query->is_search = true; }
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
                # using raw user input data directly so we need to be careful - possible sql injection
                $request = str_replace( '\'', '', $request );
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
                }
                # kill the original request
                $request = NULL;
            }
        }   # foreach ( $_REQUEST as $index => &$request ) {
        unset( $request );
        # merge optional min/max values for numeric custom fields into the checkboxes array
        $suffix_len = strlen( Search_Types_Custom_Fields_Widget::OPTIONAL_MINIMUM_VALUE_SUFFIX );
        foreach ( $_REQUEST as $index => &$request ) {
            if ( $request && ( ( $is_min
                = substr_compare( $index, Search_Types_Custom_Fields_Widget::OPTIONAL_MINIMUM_VALUE_SUFFIX, -$suffix_len ) === 0 )
                || substr_compare( $index, Search_Types_Custom_Fields_Widget::OPTIONAL_MAXIMUM_VALUE_SUFFIX, -$suffix_len ) === 0
            ) ) {
                $index = substr( $index, 0, strlen( $index ) - $suffix_len );
                if ( is_array( $_REQUEST[$index] ) || !array_key_exists( $index, $_REQUEST ) ) {
                    $_REQUEST[$index][] = array( 'operator' => $is_min ? 'minimum' : 'maximum', 'value' => $request );
                }
                # kill the original request
                $request = NULL;
            }
        }
        unset( $request );
        #error_log( '##### filter:posts_where:$_REQUEST=' . print_r( $_REQUEST, TRUE ) );
        $wpcf_fields = get_option( 'wpcf-fields', array() );    
        #error_log( 'posts_where:$where=' . $where );
        $non_field_keys = array( 'search_types_custom_fields_form', 'search_types_custom_fields_widget_option',
            'search_types_custom_fields_widget_number', 'search_types_custom_fields_and_or',
            'search_types_custom_fields_show_using_macro', 'post_type' );
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
            $sql2 = '';   # holds meta_value = sql
            $sql3 = '';   # holds meta_value min/max sql
            foreach ( $values as $value ) {
                if ( $sql2 ) { $sql2 .= ' OR '; }
                if ( strpos( $key, 'inverse_' ) === 0 ) {
                    # parent of is the inverse of child of so ...
                    if ( !$value ) { continue; }
                    $sql2 .= '( w.meta_key = "' . substr( $key, strpos( $key, '_wpcf_belongs_' ) ) . "\" AND w.post_id = $value )";
                } else if ( strpos( $key, '_wpcf_belongs_' ) === 0 ) {
                    # child of is like a custom field except the name is special so ...
                    if ( !$value ) { continue; }
                    $sql2 .= "( w.meta_key = '$key' AND w.meta_value = $value )";
                } else {
                    $wpcf_field =& $wpcf_fields[substr( $key, 5 )];
                    if ( is_array( $value ) ) {
                        if ( $sql2 ) { $sql2 = substr( $sql2, 0, -4 ); }
                        # check for minimum/maximum operation
                        if ( ( $is_min = $value['operator'] == 'minimum' ) || $value['operator'] == 'maximum' ) {
                            if ( $wpcf_field['type'] == 'date' ) {
                                # for dates convert to timestamp range
                                list( $t0, $t1 ) = Search_Types_Custom_Fields_Widget::get_timestamp_from_string( $value['value'] );
                                if ( $is_min ) {
                                    # for minimum use start of range
                                    $value['value'] = $t0;
                                } else {
                                    # for maximum use end of range
                                    $value['value'] = $t1;
                                }
                            }
                            if ( $sql3 ) { $sql3 .= ' AND '; }
                            if ( $is_min ) {
                                $sql3 .= "( w.meta_key = '$key' AND w.meta_value >= $value[value] )";
                            } else if ( $value['operator'] == 'maximum' ) {
                                $sql3 .= "( w.meta_key = '$key' AND w.meta_value <= $value[value] )";
                            }
                        }
                    } else if ( $wpcf_field['type'] != 'checkbox' && !$value ) {
                        # skip false values except for single checkbox
                    } else if ( $wpcf_field['type'] == 'date' ) {
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
                            if ( $value ) { $value = $wpcf_field['data']['set_value']; }
                        } else {
                            # maybe using raw user input data directly so we need to be careful - possible sql injection
                            $value = str_replace( '\'', '', $value );
                        }
                        # TODO: LIKE may match more than we want on serialized array of numeric values - false match on numeric indices
                        $sql2 .= "( w.meta_key = '$key' AND w.meta_value LIKE '%$value%' )";
                    }
                }
            }   # foreach ( $values as $value ) {
            if ( $sql3 ) {
                # merge in min/max conditions
                if ( $sql2 ) {
                    $sql2 .= " OR ( $sql3 ) ";
                } else {
                    $sql2 = $sql3;
                }
            }
            if ( strpos( $key, 'inverse_' ) === 0 ) {
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
    if ( isset( $_REQUEST['search_types_custom_fields_show_using_macro'] )
        && $_REQUEST['search_types_custom_fields_show_using_macro'] === 'use macro' ) {
        add_action( 'wp_enqueue_scripts', function() {
            wp_enqueue_style( 'search_results_table', plugins_url( 'search-results-table.css', __FILE__ ) );
        } );
        add_action( 'template_redirect', function() {
            global $wp_query;
            global $wpdb;
            #error_log( '##### action:template_redirect():$_REQUEST=' . print_r( $_REQUEST, true ) );
            # in this case a template is dynamically constructed and returned
            #error_log( '##### action:template_redirect():$wp_query=' . print_r( $wp_query, true ) );
            # get the list of posts
            $posts = array_map( function( $post ) { return $post->ID; }, $wp_query->posts );
            $posts_imploded = implode( ', ', $posts );
            #error_log( '##### action:template_redirect():$posts=' . print_r( $posts, true ) );
            $option = get_option( $_REQUEST['search_types_custom_fields_widget_option'] );
            #error_log( '##### action:template_redirect():$option=' . print_r( $option, true ) );
            $number = $_REQUEST['search_types_custom_fields_widget_number'];
            # get the applicable fields from the options for this widget
            $fields = $option[$number]['show-' . $_REQUEST['post_type']];
            #error_log( '##### action:template_redirect():$fields=' . print_r( $fields, true ) );
            if ( !$fields ) {
                $fields = $option[$number][$_REQUEST['post_type']];
            }
            # remove pst-std-content fields
            $fields = array_filter( $fields, function( $field ) { return $field !== 'pst-std-content'; } );
            if ( $container_width = $option[$number]['search_table_width'] ) {
                $container_style .= "style=\"width:{$container_width}px\"";
            }
            # build the main content from the above parts
            # the macro has parameters: posts - a list of post ids, fields - a list of field names, a_post - any valid post id,
            # and post_type - the post type
            # finally output all the HTML
            get_header();
            $wpcf_fields = get_option( 'wpcf-fields', array() );
            $content = <<<EOD
<div style="width:99%;overflow:auto;">
    <div class="scpbcfw-result-container"$container_style>
        <table class="scpbcfw-result-table">
            <tr><th class="scpbcfw-result-table-head-post">post</th>
EOD;
            # fix taxonomy names for use as titles
            foreach ( $fields as $field ) {
                if ( substr_compare( $field, 'tax-cat-', 0, 8, false ) === 0
                    || substr_compare( $field, 'tax-tag-', 0, 8, false ) === 0 ) {
                    $field = substr( $field, 8 );
                } else if ( $field === 'pst-std-attachment' ) {
                    $field = 'attachment';
                } else if ( substr_compare( $field, 'wpcf-', 0, 5, false ) === 0 ) {
                    $field = substr( $field, 5 );
                } else if ( substr_compare( $field, '_wpcf_belongs_', 0, 14 ) === 0 ) {
                    $field = substr( $field, 14, -3 );
                } else if ( substr_compare( $field, 'inverse_', 0, 8 ) === 0 ) {
                    $field = substr( $field, 8, strpos( $field, '__wpcf_belongs_' ) - 8 );
                }
                $content .= "<th class=\"scpbcfw-result-table-head-$field\">$field</th>";
            }
            unset( $field );
            $content .= '</tr>';
            $post_titles = $wpdb->get_results( <<<EOD
                SELECT ID, post_title, guid, post_type FROM $wpdb->posts ORDER BY ID
EOD
                , OBJECT_K );
            $child_of_values = array();
            $parent_of_values = array();
            foreach ( $posts as $post ) {
                $content .= <<<EOD
<tr><td class="scpbcfw-result-table-detail-post"><a href="{$post_titles[$post]->guid}">{$post_titles[$post]->post_title}</a></td>
EOD;
                foreach ( $fields as $field ) {
                    #error_log( '##### action:template_redirect():$field=' . $field );
                    $td = '<td></td>';
                    if ( substr_compare( $field, 'tax-cat-', 0, 8, false ) === 0
                        || substr_compare( $field, 'tax-tag-', 0, 8, false ) === 0 ) {
                        $taxonomy = substr( $field, 8 );
                        # TODO: may be more efficient to get the terms for all the posts in one query
                        if ( is_array( $terms = get_the_terms( $post, $taxonomy ) ) ) {
                            $terms = array_map( function( $term ) { return $term->name; }, $terms );
                            $td = "<td class=\"scpbcfw-result-table-detail-$taxonomy\">" . implode( ', ', $terms ) . '</td>';
                        }
                    } else if ( ( $child_of = strpos( $field, '_wpcf_belongs_' ) === 0 )
                        || ( $parent_of = strpos( $field, 'inverse_' ) === 0 ) ) {
                        #error_log( '##### action:template_redirect():$child_of=' . $child_of );
                        #error_log( '##### action:template_redirect():$parent_of=' . $parent_of );
                        #$field = substr( $field, strpos( $field, '_wpcf_belongs_' ) + 14 );
                        if ( $child_of ) {
                            if ( !isset( $child_of_values[$field] ) ) {
                                # Do one query for all posts on first post and save the result for later posts
                                $child_of_values[$field] = $wpdb->get_results( <<<EOD
                                    SELECT post_id, meta_value FROM $wpdb->postmeta
                                        WHERE meta_key = '$field' AND post_id IN ( $posts_imploded )
EOD
                                    , OBJECT_K );
                            }
                            $value = $child_of_values[$field][$post]->meta_value;
                        } else if ( $parent_of ) {
                            if ( !isset( $parent_of_values[$field] ) ) {
                                # Do one query for all posts on first post and save the result for later posts
                                # This case is more complex since a parent can have multiple childs
                                $meta_key = substr( $field, strpos( $field, '_wpcf_belongs_' ) );
                                $results = $wpdb->get_results( <<<EOD
                                    SELECT meta_value, post_id FROM $wpdb->postmeta
                                        WHERE meta_key = '$meta_key' AND meta_value IN ( $posts_imploded )
EOD
                                    , OBJECT );
                                $values = array();
                                foreach ( $results as $result ) {
                                    $values[$result->meta_value][] = $result->post_id;
                                }
                                $parent_of_values[$field] = $values;
                                #error_log( '##### action:template_redirect():$parent_of_values[$field]='
                                #    . print_r( $values, true ) );
                                unset( $values );
                            }
                            $value = array_key_exists( $post, $parent_of_values[$field] ) ? $parent_of_values[$field][$post] : null;
                            #error_log( '##### action:template_redirect():$parent_of_values[$field][$post]='
                            #    . print_r( $value, true ) );
                        }
                        # for child of and parent of use post title instead of post id for label and embed in an <a> html element
                        if ( $value ) {
                            if ( is_array( $value ) ) {
                                $label = implode( ', ', array_map( function( $v ) use ( &$post_titles ) {
                                    return "<a href=\"{$post_titles[$v]->guid}\">{$post_titles[$v]->post_title}</a>";
                                }, $value ) );
                            } else {
                                $label = "<a href=\"{$post_titles[$value]->guid}\">{$post_titles[$value]->post_title}</a>";
                            }
                            $td = "<td class=\"scpbcfw-result-table-detail-$field\">$label</td>";
                        }
                        unset( $value );
                    } else if ( $field === 'pst-std-attachment' ) {
                        if ( !isset( $attachments ) ) {
                            $results = $wpdb->get_results( <<<EOD
                                SELECT ID, post_parent FROM $wpdb->posts
                                    WHERE post_type = 'attachment' AND post_parent IN ( $posts_imploded )
EOD
                                , OBJECT );
                            $attachments = array();
                            foreach ( $results as $result ) {
                                $attachments[$result->post_parent][] = $result->ID;
                            }
                        }
                        if ( array_key_exists( $post, $attachments ) ) {
                            $label = implode( ', ', array_map( function( $v ) use ( &$post_titles ) {
                                return "<a href=\"{$post_titles[$v]->guid}\">{$post_titles[$v]->post_title}</a>";
                            }, $attachments[$post] ) );
                            $td = "<td class=\"scpbcfw-result-table-detail-$field\">$label</td>";
                        }
                    } else {
                        if ( !isset( $field_values[$field] ) ) {
                            $results = $wpdb->get_results( <<<EOD
                                SELECT post_id, meta_value FROM $wpdb->postmeta
                                    WHERE meta_key = '$field' AND post_id IN ( $posts_imploded )
EOD
                                , OBJECT );
                            $values = array();
                            foreach( $results as $result ) {
                                $values[$result->post_id][] = $result->meta_value;
                            }
                            $field_values[$field] = $values;
                            unset( $values );
                        }
                            
                        if ( array_key_exists( $post, $field_values[$field] )
                            && ( $field_values = $field_values[$field][$post] ) ) {
                            #error_log( '##### action:template_redirect():$field_values=' . print_r( $field_values, true ) );
                            $wpcf_field =& $wpcf_fields[substr( $field, 5 )];
                            #error_log( '##### action:template_redirect():$wpcf_field=' . print_r( $wpcf_field, true ) );
                            $labels = array();
                            foreach ( $field_values as $value ) {
                                if ( !$value ) { continue; }
                                #error_log( '##### action:template_redirect():$value=' . $value );
                                if ( is_serialized( $value ) ) {
                                    # serialized meta_value contains multiple values so need to unpack them and process them individually
                                    $unserialized = unserialize( $value );
                                     if ( is_array( $unserialized ) ) {
                                        if ( $wpcf_field['type'] == 'checkboxes' ) {
                                            # for checkboxes use the unique option key as the value of the checkbox
                                            $values = array_keys( $unserialized );
                                        } else {
                                            $values = array_values( $unserialized );
                                        }
                                    } else {
                                        error_log( '##### action:template_redirect()[UNEXPECTED!]:$unserialized='
                                            . print_r( $unserialized, true ) );
                                        $values = array( $unserialized );
                                    }
                                } else {
                                    if ( $wpcf_field['type'] == 'radio' || $wpcf_field['type'] == 'select' ) {
                                        # for radio and select use the unique option key as the value of the radio or select
                                        $values = array( Search_Types_Custom_Fields_Widget::search_wpcf_field_options(
                                            $wpcf_field['data']['options'], 'value', $value ) );
                                    } else {
                                        $values = array( $value );
                                    }
                                }
                                unset( $value );
                                $label = array();
                                foreach ( $values as $value ) {
                                    if ( strlen( $value ) > 7 && ( substr_compare( $value, 'http://', 0, 7, true ) === 0
                                        || substr_compare( $value, 'https://', 0, 8, true ) === 0 ) ) {
                                        $url = $value;
                                    }
                                    $current =& $label[];
                                    if ( $wpcf_field['type'] == 'radio' ) {
                                        # for radio replace option key with something more user friendly
                                        $current = $wpcf_field['data']['options'][$value]['title']
                                            . ( $wpcf_field['data']['display'] == 'value'
                                                ? ( '(' . $wpcf_field['data']['options'][$value]['display_value'] . ')' )
                                                : ( '(' . $wpcf_field['data']['options'][$value]['value'] . ')' ) );
                                    } else if ( $wpcf_field['type'] == 'select' ) {
                                        # for select replace option key with something more user friendly
                                        $current = $wpcf_field['data']['options'][$value]['value']
                                            . '(' . $wpcf_field['data']['options'][$value]['title'] . ')';
                                    } else if ( $wpcf_field['type'] == 'checkboxes' ) {
                                        # checkboxes are handled very differently from radio and select 
                                        # Why? seems that the radio/select way would work here also and be simpler
                                        $current = $wpcf_field['data']['options'][$value]['title'];
                                         if ( $wpcf_field['data']['options'][$value]['display'] == 'db' ) {
                                            $current .= ' (' . $wpcf_field['data']['options'][$value]['set_value'] . ')';
                                        } else if ( $wpcf_field['data']['options'][$value]['display'] == 'value' ) {
                                            $current .= ' (' . $wpcf_field['data']['options'][$value]['display_value_selected'] . ')';
                                        }
                                    } else if ( $wpcf_field['type'] == 'checkbox' ) {
                                        if ( $wpcf_field['data']['display'] == 'db' ) {
                                            $current = $value;
                                        } else {
                                            if ( $value ) {
                                                $current = $wpcf_field['data']['display_value_selected'];
                                            } else {
                                                $current = $wpcf_field['data']['display_value_not_selected'];
                                            }
                                        }
                                    } else if ( $wpcf_field['type'] == 'image' || $wpcf_field['type'] == 'file'
                                        || $wpcf_field['type'] == 'audio' || $wpcf_field['type'] == 'video' ) {
                                        # use only filename for images and files
                                        $current = ( $i = strrpos( $value, '/' ) ) !== FALSE ? substr( $value, $i + 1 ) : $value;
                                    } else if ( $wpcf_field['type'] == 'date' ) {
                                        $current = date( Search_Types_Custom_Fields_Widget::DATE_FORMAT, $value );
                                    } else if ( $wpcf_field['type'] == 'url' ) {
                                        # for URLs chop off http://
                                        if ( substr_compare( $value, 'http://', 0, 7 ) === 0 ) { $current = substr( $value, 7 ); }
                                        else if ( substr_compare( $value, 'https://', 0, 8 ) === 0 ) { $current = substr( $value, 8 ); }
                                        else { $current = $value; }
                                        # and provide line break hints
                                        $current = str_replace( '/', '/&#8203;', $current );
                                    } else if ( $wpcf_field['type'] === 'numeric' ) {
                                        $style = ' style="text-align:center;"';
                                        $current = $value;
                                    } else {
                                        $current = $value;
                                    }
                                    # if it is a link then embed in an <a> html element
                                    if ( $url ) { $current = "<a href=\"$url\">$current</a>"; }
                                    unset( $url, $current );
                                }
                                $labels[] = implode( ', ', $label );
                                unset( $value, $values, $label, $style );
                            }
                            unset( $wpcf_field );
                            $labels = implode( ', ', $labels );
                            $td = "<td class=\"scpbcfw-result-table-detail-$field\"$style>$labels</td>";
                        }
                    }
                    $content .= $td;
                }
                $content .= '</tr>';
            }
            $content .= '</table></div></div>';
            #error_log( '##### action:template_redirect():$content='  . print_r( $content,  true ) );
            echo $content;
            get_footer();
            exit();
        } );
    }
}

?>