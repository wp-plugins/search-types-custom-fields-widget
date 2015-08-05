<?php
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
    const SQL_LIMIT = 16;                                              # maximum number of post types/custom fields to display
    #const SQL_LIMIT = 2;                                              # TODO: this limit for testing only replace with above
    # end of user configurable constants
    
    const OPTIONAL_TEXT_VALUE_SUFFIX = '-stcfw-optional-text-value';   # suffix to append to optional text input for a search field
    const OPTIONAL_MINIMUM_VALUE_SUFFIX = '-stcfw-minimum-value';      # suffix to append to optional minimum/maximum value text 
    const OPTIONAL_MAXIMUM_VALUE_SUFFIX = '-stcfw-maximum-value';      #     inputs for a numeric search field
    const GET_FORM_FOR_POST_TYPE = 'get_form_for_post_type';
    const LANGUAGE_DOMAIN = 'search-types-custom-fields-widget';       # for .pot file
    
    public static $PARENT_OF = 'For ';                                 # label for parent of relationship
    public static $CHILD_OF = 'Of ';                                   # label for child of relationship
    
	public function __construct( ) {
		parent::__construct(
            'search_types_custom_fields_widget',
            __( 'Search Types Custom Fields', self::LANGUAGE_DOMAIN ),
            [ 'classname' => 'search_types_custom_fields_widget', 'description' => __( "Search Types Custom Fields", self::LANGUAGE_DOMAIN ) ]
        );
        self::$PARENT_OF = __( 'For ', self::LANGUAGE_DOMAIN );
        self::$CHILD_OF  = __( 'Of ',  self::LANGUAGE_DOMAIN );
	}

    # widget() emits a form to select a post type which sends an AJAX request for the search form for the selected post type
    
	public function widget( $args, $instance ) {
        global $wpdb;
        extract( $args );
?>
<form id="search-types-custom-fields-widget-<?php echo $this->number; ?>" class="scpbcfw-search-fields-form" method="get"
    action="<?php echo esc_url( home_url( '/' ) ); ?>">
<input id="search_types_custom_fields_form" name="search_types_custom_fields_form" type="hidden" value="types-fields-search">
<input id="search_types_custom_fields_widget_option" name="search_types_custom_fields_widget_option" type="hidden"
    value="<?php echo $this->option_name; ?>">
<input id="search_types_custom_fields_widget_number" name="search_types_custom_fields_widget_number" type="hidden"
    value="<?php echo $this->number; ?>">
<h2><?php _e( 'Search:', self::LANGUAGE_DOMAIN ); ?></h2>
<div class="scpbcfw-search-post-type">
<h3><?php _e( 'post type:', self::LANGUAGE_DOMAIN ); ?></h3>
<select id="post_type" name="post_type" class="post_type scpbcfw-search-select-post-type" required>
<option value="no-selection"><?php _e( '--select post type--', self::LANGUAGE_DOMAIN ); ?></option>
<?php
        $results = $wpdb->get_results( <<<EOD
SELECT post_type, COUNT(*) count FROM $wpdb->posts WHERE post_status = "publish" GROUP BY post_type ORDER BY count DESC
EOD
            , OBJECT );
        $select_post_types = array_diff( array_filter( array_keys( $instance ), function( $key ) {
            return substr_compare( $key, "scpbcfw-", 0, 8 ) !== 0;
        } ), [ 'maximum_number_of_items', 'set_is_search', 'use_simplified_labels_for_select', 'enable_table_view_option', 'search_table_width' ] );
        foreach ( $results as $result ) {
            $name = $result->post_type;
            # skip unselected post types
            if ( !in_array( $name, $select_post_types ) ) {
                continue;
            }      
            $labels = get_post_type_object( $name )->labels;
            $label = isset( $labels->singular_name ) ? $labels->singular_name : $labels->name;
?>      
<option class="real_post_type" value="<?php echo $name; ?>"><?php echo "$label ($result->count)"; ?></option>
<?php
        }   # foreach ( $results as $result ) {
?>
</select>
</div>
<div id="search-types-custom-fields-parameters"></div>
<div id="scpbcfw-search-fields-submit-container" style="display:none">
<div class="scpbcfw-search-fields-option-box">
<div class="scpbcfw-search-fields-and-or-box">
<?php _e( 'Results should satisfy', self::LANGUAGE_DOMAIN ); ?><br> 
<input type="radio" name="search_types_custom_fields_and_or" value="and" checked><strong><?php _e( 'All', self::LANGUAGE_DOMAIN ); ?></strong>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="search_types_custom_fields_and_or" value="or"><strong><?php _e( 'Any', self::LANGUAGE_DOMAIN ); ?></strong></br>
<?php _e( 'of the search conditions.', self::LANGUAGE_DOMAIN ); ?>
</div>
<?php
        if ( $instance['enable_table_view_option'] === 'table view option enabled' ) {
?>
<hr>
<div class="scpbcfw-search-fields-checkbox-box">
<input type="checkbox" name="search_types_custom_fields_show_using_macro" class="scpbcfw-search-fields-checkbox" value="use macro">
<?php _e( 'Show search results in table format:', self::LANGUAGE_DOMAIN ); ?>
</div>
<?php
        }
?>
</div>
<div class="scpbcfw-search-fields-submit-box">
<input id="scpbcfw-search-fields-nonce" type="hidden" value="<?php echo wp_create_nonce( Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE ); ?>">
<input id="scpbcfw-search-fields-submit" type="submit" value="<?php _e( 'Start Search', self::LANGUAGE_DOMAIN ); ?>" disabled>
&nbsp;&nbsp;
</div>
</div>
</form>
<?php
	}   # public function widget( $args, $instance ) {

    public function update( $new, $old ) {
        return array_map( function( $values ) {
            return is_array( $values) ? array_map( 'strip_tags', $values ) : strip_tags( $values );
        }, $new );
    }
    
    # form() is for the administrator to specify the post types and custom fields that will be searched
    
    public function form( $instance ) {
        global $wpdb;
        $wpcf_types  = get_option( 'wpcf-custom-types', [ ] );
        $wpcf_fields = get_option( 'wpcf-fields',       [ ] );
?>
<div class="scpbcfw-admin-button">
<a href="http://alttypes.wordpress.com/#administrator" target="_blank"><?php _e( 'Help', self::LANGUAGE_DOMAIN ); ?></a>
</div>
<h4 class="scpbcfw-admin-heading"><?php _e( 'Select Fields for:', self::LANGUAGE_DOMAIN ); ?></h4>
<p style="clear:both;margin:0px;">
<?php
        # use all Types custom post types and the WordPress built in "post" and "page"
        $wpcf_types_keys = '"' . implode( '", "', array_keys( $wpcf_types ) ) . '", "post", "page"';
        $types = $wpdb->get_results( <<<EOD
SELECT post_type, COUNT(*) count FROM $wpdb->posts WHERE post_type IN ( $wpcf_types_keys ) AND post_status = "publish" GROUP BY post_type ORDER BY count DESC
EOD
        , OBJECT_K );
        # the sql below gives the number of posts tagged, since a single post may be tagged with multiple tags
        # the sql is somewhat complicated
        $db_taxonomies = $wpdb->get_results( <<<EOD
SELECT post_type, taxonomy, count(*) count
    FROM (SELECT p.post_type, tt.taxonomy, r.object_id
        FROM wp_term_relationships r, wp_term_taxonomy tt, wp_terms t, wp_posts p
        WHERE r.term_taxonomy_id = tt.term_taxonomy_id AND tt.term_id = t.term_id AND r.object_id = p.ID AND post_type IN ( $wpcf_types_keys )
        GROUP BY p.post_type, tt.taxonomy, r.object_id) d 
    GROUP BY post_type, taxonomy
EOD
        , OBJECT );
        $wp_taxonomies = get_taxonomies( '', 'objects' );
        foreach ( $types as $name => $type ) {
            # get selected search fields and selected table fields for post type
            $selected = !empty( $instance[$name] ) ? $instance[ $name ] : [ ];
            $show_selected = !empty( $instance['scpbcfw-show-' . $name] ) ? $instance['scpbcfw-show-' . $name]
                : ( !empty( $instance['show-' . $name] ) ? $instance['show-' . $name] : [] );
?>
<div class="scpbcfw-admin-search-fields">
<span class="scpbcfw-admin-post-type"><?php echo "$name ($type->count)"; ?></span>
<div class="scpbcfw-admin-display-button"><?php _e( 'Open', self::LANGUAGE_DOMAIN ); ?></div>
<div style="clear:both;"></div>
<div class="scpbcfw-search-field-values" style="display:none;">
<?php
            # convert old separated order data format into the new merged order data format
            if ( empty( $instance['scpbcfw-merged-order-' . $name] ) ) {
                if ( !empty( $instance['tax-order-' . $name] ) ) {
                    $previous_tax_order = explode( ';', $instance['tax-order-' . $name] );
                } else {
                    $previous_tax_order = [ ];
                }
                if ( !empty( $instance['order-' . $name] ) ) {
                    $previous_order = explode( ';', $instance['order-' . $name] );
                } else {
                    $previous_order = [ ];
                }
                $instance['scpbcfw-merged-order-' . $name] = implode( ';', array_merge( $previous_tax_order, $previous_order ) );
            }
            $previous = !empty( $instance['scpbcfw-merged-order-' . $name] ) ? explode( ';', $instance['scpbcfw-merged-order-' . $name] ) : [ ];
            # find all current fields
            # do taxonomies first
            $the_taxonomies = [ ];
            foreach ( $db_taxonomies as &$db_taxonomy ) {
                if ( $db_taxonomy->post_type != $name ) { continue; }
                $wp_taxonomy = $wp_taxonomies[ $db_taxonomy->taxonomy ];
                $the_taxonomies[ ( $wp_taxonomy->hierarchical ? 'tax-cat-' : 'tax-tag-' ) . $wp_taxonomy->name ] = $db_taxonomy;
            }
            unset( $db_taxonomy );
            # now do types custom fields, post content and author
            # again the sql is complicated since a single post may have multiple values for a custom field
            $fields = $wpdb->get_results( $wpdb->prepare( <<<EOD
SELECT field_name, COUNT(*) count
    FROM ( SELECT m.meta_key field_name, m.post_id FROM $wpdb->postmeta m, $wpdb->posts p
        WHERE m.post_id = p.ID AND p.post_type = %s AND m.meta_key LIKE 'wpcf-%%' AND m.meta_value IS NOT NULL AND m.meta_value != ''
            AND m.meta_value != 'a:0:{}'
        GROUP BY m.meta_key, m.post_id ) fields
    GROUP BY field_name ORDER BY count DESC
EOD
            , $name ), OBJECT_K );
            $fields_for_type = $wpdb->get_col( $wpdb->prepare( <<<EOD
SELECT gf.meta_value FROM $wpdb->postmeta pt, $wpdb->postmeta gf
    WHERE pt.post_id = gf.post_id AND pt.meta_key = "_wp_types_group_post_types" AND pt.meta_value LIKE %s AND gf.meta_key = "_wp_types_group_fields"
EOD
            , "%,$name,%" ) );
            $fields_for_type = array_reduce( $fields_for_type, function( $result, $item ) {
                return array_merge( $result, explode( ',', trim( $item, ',' ) ) );
            }, array() );         
            foreach ( $fields as $meta_key => &$field ) {
                $field_name = substr( $meta_key, 5 );
                if ( array_key_exists( $field_name, $wpcf_fields ) && in_array( $field_name, $fields_for_type ) ) {
                    $wpcf_field = $wpcf_fields[ $field_name ];
                    $field->label = $wpcf_field[ 'name' ];
                    $field->large = in_array( $wpcf_field[ 'type' ], [ 'textarea', 'wysiwyg' ] );
                } else {
                    $field = NULL;   # not a valid Types custom field so tag it for skipping.
                }
            }
            unset( $field );
            # add fields for parent of, child of, post_content and attachment
            $results = $wpdb->get_results( <<<EOD
                SELECT m.meta_key, COUNT( DISTINCT m.post_id ) count FROM $wpdb->postmeta m, $wpdb->posts p
                    WHERE m.post_id = p.ID AND p.post_type = "$name" AND m.meta_key LIKE '_wpcf_belongs_%'
                    GROUP BY m.meta_key
EOD
            , OBJECT );
            foreach ( $results as $result ) {
                $post_type = substr( $result->meta_key, 14, strlen( $result->meta_key ) - 17 );
                $fields[$result->meta_key] = (object) array(
                    'label' => self::$CHILD_OF . ( $post_type === 'post' || $post_type === 'page' ? $post_type
                        : $wpcf_types[$post_type]['labels']['name'] ), 
                    'count' => $result->count
                );
            }
            unset( $results, $result );
            $results = $wpdb->get_results( <<<EOD
                SELECT pi.post_type, m.meta_key, COUNT( DISTINCT m.meta_value ) count
                    FROM $wpdb->postmeta m, $wpdb->posts pv, $wpdb->posts pi
                    WHERE m.meta_value = pv.ID AND pv.post_type = "$name" AND pv.post_status = "publish"
                        AND m.post_id = pi.ID AND pi.post_status = "publish"
                        AND m.meta_key LIKE '_wpcf_belongs_%'
                    GROUP BY pi.post_type
EOD
            , OBJECT );
            foreach ( $results as $result ) {
                $fields["inverse_{$result->post_type}_{$result->meta_key}"] = (object) [
                    'label' => self::$PARENT_OF . ( $result->post_type === 'post' || $result->post_type === 'page'
                        ? $result->post_type : $wpcf_types[ $result->post_type ][ 'labels' ][ 'name' ] ), 
                    'count' => $result->count
                ];
            }
            # setup post content entry
            $fields[ 'pst-std-post_content' ] = (object) [ 'label' => 'Post Content', 'count' => $type->count ];
            $fields[ 'pst-std-attachment' ] = (object) [ 'label' => 'Attachment', 'count' => $wpdb->get_var( <<<EOD
                SELECT COUNT( DISTINCT a.post_parent ) FROM $wpdb->posts a, $wpdb->posts p
                    WHERE a.post_type = "attachment" AND a.post_parent = p.ID AND p.post_type = "$name" AND p.post_status = "publish"
EOD
            ) ];
            #setup post author entry
            $fields['pst-std-post_author']  = (object) [ 'label' => 'Author', 'count' => $wpdb->get_var( <<<EOD
                SELECT COUNT(*) FROM $wpdb->posts p
                    WHERE p.post_type = "$name" AND p.post_status = "publish" AND p.post_author IS NOT NULL
EOD
            ) ];
            # remove all invalid custom fields.
            $fields = array_filter( $fields );
            $current = array_merge( array_keys( $the_taxonomies ), array_keys( $fields ) );
            $previous = array_intersect( $previous, $current );
            $new = array_diff( $current, $previous );
            $current = array_merge( $previous, $new ); 
?>
<!-- before drop point -->
<div><div class="scpbcfw-selectable-field-after"></div></div>
<?php
            foreach ( $current as $field_name ) {
?>
<div class="scpbcfw-selectable-field">
<?php
                if ( substr_compare( $field_name, 'tax-tag-', 0, 8 ) ==0 || substr_compare( $field_name, 'tax-cat-', 0, 8 ) == 0 ) {
                    $tax_name = $field_name;
                    $db_taxonomy = $the_taxonomies[ $tax_name ];
                    $wp_taxonomy = $wp_taxonomies[ $db_taxonomy->taxonomy ];
                    $tax_label = ( $wp_taxonomy->hierarchical ) ? ' (category)' : ' (tag)';
?>
    <input type="checkbox"
        class="scpbcfw-selectable-field" 
        id="<?php echo $this->get_field_id( $name ); ?>"
        name="<?php echo $this->get_field_name( $name ); ?>[]"
        value="<?php echo $tax_name; ?>"
        <?php if ( $selected && in_array(  $tax_name, $selected ) ) { echo ' checked'; } ?>>
    <input type="checkbox"
        id="<?php echo $this->get_field_id( 'scpbcfw-show-' . $name ); ?>"
        name="<?php echo $this->get_field_name( 'scpbcfw-show-' . $name ); ?>[]"
        class="scpbcfw-select-content-macro-display-field"
        value="<?php echo $tax_name; ?>"
        <?php if ( $show_selected && in_array( $tax_name, $show_selected ) ) { echo ' checked'; } ?>
        <?php if ( $instance && !isset( $instance['enable_table_view_option'] ) ) { echo 'disabled'; } ?>>
<?php echo "{$wp_taxonomy->label}{$tax_label} ($db_taxonomy->count)"; ?>
<?php
                } else {   # if ( substr_compare( $field_name, 'tax-tag-', 0, 8 ) ==0 || substr_compare( $field_name, 'tax-cat-', 0, 8 ) == 0 ) {
                    # display a field with checkboxes
                    $meta_key = $field_name;
                    $field = $fields[ $meta_key ];
?>
    <input type="checkbox"
        class="scpbcfw-selectable-field"
        id="<?php echo $this->get_field_id( $name ); ?>"
        name="<?php echo $this->get_field_name( $name ); ?>[]"
        value="<?php echo $meta_key; ?>"
        <?php if ( $selected && in_array( $meta_key, $selected ) ) { echo ' checked'; } ?>>
    <input type="checkbox"
        id="<?php echo $this->get_field_id( 'scpbcfw-show-' . $name ); ?>"
        name="<?php echo $this->get_field_name( 'scpbcfw-show-' . $name ); ?>[]"
        <?php if ( empty( $field->large ) ) { echo 'class="scpbcfw-select-content-macro-display-field"'; } ?>
        value="<?php echo $meta_key; ?>" <?php if ( $show_selected && in_array( $meta_key, $show_selected ) ) { echo ' checked'; } ?>
        <?php if ( $instance && !isset( $instance['enable_table_view_option'] ) || !empty( $field->large ) ) { echo 'disabled'; } ?>>
    <?php echo "$field->label ($field->count)"; ?>
<?php
                }   # else {
?>
    <!-- a drop point -->
    <div class="scpbcfw-selectable-field-after"></div>
</div>
<?php
            }   # foreach ( $current as $field_name ) {
?>
<input type="hidden" class="scpbcfw-selectable-field-order" id="<?php echo $this->get_field_id( 'scpbcfw-merged-order-' . $name ); ?>"
    name="<?php echo $this->get_field_name( 'scpbcfw-merged-order-' . $name ); ?>"
    value="<?php echo !empty( $instance['scpbcfw-merged-order-' . $name] ) ? $instance['scpbcfw-merged-order-' . $name] : ''; ?>">
</div>
</div>
<?php
        }   # foreach ( $types as $name => $type ) {
?>
<div class="scpbcfw-admin-option-box-container">
<div class="scpbcfw-admin-option-box">
<input type="number" min="4" max="1024" 
    id="<?php echo $this->get_field_id( 'maximum_number_of_items' ); ?>"
    name="<?php echo $this->get_field_name( 'maximum_number_of_items' ); ?>"
    class="scpbcfw-admin-option-number"
    value="<?php echo !empty( $instance['maximum_number_of_items'] ) ? $instance['maximum_number_of_items'] : 16; ?>"
    size="4">
<?php _e( 'Maximum number of items to display per custom field:', self::LANGUAGE_DOMAIN ); ?>
<div style="clear:both;"></div>
</div>
<div class="scpbcfw-admin-option-box">
<input type="checkbox"
    id="<?php echo $this->get_field_id( 'set_is_search' ); ?>"
    name="<?php echo $this->get_field_name( 'set_is_search' ); ?>"
    class="scpbcfw-admin-option-checkbox"
    value="is search" <?php if ( isset( $instance['set_is_search'] ) ) { echo 'checked'; } ?>>
<?php _e( 'Display search results using excerpts (if it is supported by your theme):', self::LANGUAGE_DOMAIN ); ?>
<div style="clear:both;"></div>
</div>
<div class="scpbcfw-admin-option-box">
<input type="checkbox"
    id="<?php echo $this->get_field_id( 'use_simplified_labels_for_select' ); ?>"
    name="<?php echo $this->get_field_name( 'use_simplified_labels_for_select' ); ?>"
    class="scpbcfw-admin-option-checkbox"
    value="use simplified labels" <?php if ( isset( $instance[ 'use_simplified_labels_for_select' ] ) ) { echo 'checked'; } ?>>
<?php _e( 'Use simplified labels for the values of select, checkboxes and radio button fields:', self::LANGUAGE_DOMAIN ); ?>
<div style="clear:both;"></div>
</div><div class="scpbcfw-admin-option-box">
<input type="checkbox"
    id="<?php echo $this->get_field_id( 'enable_table_view_option' ); ?>"
    name="<?php echo $this->get_field_name( 'enable_table_view_option' ); ?>"
    class="scpbcfw-admin-option-checkbox scpbcfw-enable-table-view-option"
    value="table view option enabled"
    <?php if ( !$instance || isset( $instance['enable_table_view_option'] ) ) { echo 'checked'; } ?>>
<?php _e( 'Enable option to display search results using a table of posts:', self::LANGUAGE_DOMAIN ); ?>
<div style="clear:both;"></div>
</div>
<div class="scpbcfw-admin-option-box">
<input type="number" min="256" max="8192" 
    id="<?php echo $this->get_field_id( 'search_table_width' ); ?>"
    name="<?php echo $this->get_field_name( 'search_table_width' ); ?>"
    class="scpbcfw-admin-option-number scpbcfw-search-table-width"
    <?php if ( !empty( $instance['search_table_width'] ) ) { echo "value=\"$instance[search_table_width]\""; } ?>
    <?php if ( $instance && !isset( $instance['enable_table_view_option'] ) ) { echo 'disabled'; } ?>
    placeholder="<?php _e( 'from css', self::LANGUAGE_DOMAIN ); ?>"
    size="5">
<?php _e( 'Width in pixels of the table of search results:', self::LANGUAGE_DOMAIN ); ?>
<div style="clear:both;"></div>
</div>
</div>
<?php
    }   # public function form( $instance ) {

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
        $arr = FALSE;
        return $arr;
    }
    
}   # class Search_Types_Custom_Fields_Widget extends WP_Widget {
    
add_action( 'plugins_loaded', function( ) {
    load_plugin_textdomain( Search_Types_Custom_Fields_Widget::LANGUAGE_DOMAIN, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

add_action( 'widgets_init', function( ) {
    register_widget( 'Search_Types_Custom_Fields_Widget' );
} );

if ( is_admin( ) ) {
    add_action( 'admin_enqueue_scripts', function( ) {
        wp_enqueue_style(  'stcfw-admin', plugins_url( 'stcfw-admin.css', __FILE__ ) );
        wp_enqueue_script( 'stcfw-admin', plugins_url( 'stcfw-admin.js',  __FILE__ ), [ 'jquery' ]  );
        wp_localize_script( 'stcfw-admin', 'stcfwAdminTranslations', [
            'open' => __( 'Open', Search_Types_Custom_Fields_Widget::LANGUAGE_DOMAIN ),
            'close' => __( 'Close', Search_Types_Custom_Fields_Widget::LANGUAGE_DOMAIN )
        ] );
        wp_enqueue_script( 'jquery-ui-draggable' );
        wp_enqueue_script( 'jquery-ui-droppable' );
    } );
    add_action( 'wp_ajax_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE, function( ) {
        do_action( 'wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE );
    } );
    add_action( 'wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE, function( ) {
        # build the search form for the post type in the AJAX request
        global $wpdb;
        if ( !isset( $_POST[ 'stcfw_get_form_nonce' ] ) || !wp_verify_nonce( $_POST[ 'stcfw_get_form_nonce' ],
            Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE ) ) {
            error_log( '##### action:wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE . ':nonce:die' );
            die;
        }
        if ( $_REQUEST[ 'post_type' ] === 'no-selection' ) {
            # this is the no selection place holder so do nothing
            die;
        }
        $wpcf_types  = get_option( 'wpcf-custom-types', array() );
?>
<div class="scpbcfw-search-fields-head">
<div id="scpbcfw-search-fields-help">
<a href="http://alttypes.wordpress.com/#user"
    target="_blank"><?php _e( 'Help', Search_Types_Custom_Fields_Widget::LANGUAGE_DOMAIN ); ?></a>
</div>
<h4 class="scpbcfw-search-fields-title"><?php _e( 'Search Conditions:', Search_Types_Custom_Fields_Widget::LANGUAGE_DOMAIN ); ?></h4>
<br style="clear:both;">
</div>
<?php
        $widget_number = $_REQUEST[ 'search_types_custom_fields_widget_number' ];
        $option        = get_option( $_REQUEST[ 'search_types_custom_fields_widget_option' ] )[ $widget_number ];
        $selected      = $option[ $_REQUEST[ 'post_type' ] ];
        $SQL_LIMIT     = $option[ 'maximum_number_of_items' ];
        # get all terms for all taxonomies for the selected post type
        $results = $wpdb->get_results( $wpdb->prepare( <<<EOD
SELECT x.taxonomy, r.term_taxonomy_id, t.name, COUNT(*) count
    FROM $wpdb->term_relationships r, $wpdb->term_taxonomy x, $wpdb->terms t, $wpdb->posts p
    WHERE r.term_taxonomy_id = x.term_taxonomy_id AND x.term_id = t.term_id AND r.object_id = p.ID AND p.post_type = %s
    GROUP BY x.taxonomy, r.term_taxonomy_id ORDER BY x.taxonomy, r.term_taxonomy_id
EOD
            , $_REQUEST[ 'post_type' ] ), OBJECT );
        $taxonomies = get_taxonomies( '', 'objects' );
        # restructure the results for displaying by taxonomy
        $terms = [ ];
        foreach ( $results as $result ) {
            $taxonomy = $taxonomies[ $result->taxonomy ];
            $tax_type = ( $taxonomy->hierarchical ) ? 'tax-cat-' : 'tax-tag-';
            if ( !in_array( $tax_type . $taxonomy->name, $selected ) ) {
                continue;
            }
            $terms[ $result->taxonomy ][ 'values' ][ $result->term_taxonomy_id ][ 'name' ]  = $result->name;
            $terms[ $result->taxonomy ][ 'values' ][ $result->term_taxonomy_id ][ 'count' ] = $result->count;
        }
        # get all meta_values for the selected custom fields in the selected post type
        if ( $selected_imploded = array_filter( $selected, function( $v ) { return strpos( $v, '_wpcf_belongs_' ) !== 0; } ) ) {
            $selected_imploded = '("' . implode( '","', $selected_imploded ) . '")';
            $results = $wpdb->get_results( $wpdb->prepare( <<<EOD
SELECT m.meta_key, m.meta_value, COUNT(*) count FROM $wpdb->postmeta m, $wpdb->posts p
    WHERE m.post_id = p.ID AND meta_key IN $selected_imploded AND p.post_type = %s GROUP BY m.meta_key, m.meta_value
EOD
                , $_REQUEST[ 'post_type' ] ), OBJECT );
            $wpcf_fields = get_option( 'wpcf-fields', [ ] );
            # prepare the results for use in checkboxes - need value, count of value and field labels
            foreach ( $results as $result ) {
                $wpcf_field = $wpcf_fields[ substr( $result->meta_key, 5 ) ];
                # skip false values except for single checkbox
                if ( $wpcf_field['type'] !== 'checkbox' && !$result->meta_value ) {
                    continue;
                }
                if ( is_serialized( $result->meta_value ) ) {
                    # serialized meta_value contains multiple values so need to unpack them and process them individually
                    $unserialized = unserialize( $result->meta_value );
                    if ( is_array( $unserialized ) ) {
                        if ( array_reduce( $unserialized, function( $sum, $value ) {
                            return $sum = $sum && ( is_array( $value ) || is_scalar( $value ) );
                        }, TRUE ) ) {
                            foreach( $unserialized as $key => $value ) {
                                if ( $wpcf_field[ 'type' ] === 'checkboxes' ) {
                                    # for checkboxes use the unique option key as the value of the checkbox
                                    if ( $value ) {
                                        if ( !isset( $fields[ $result->meta_key ][ 'values' ][ $key ] ) ) {
                                            $fields[ $result->meta_key ][ 'values' ][ $key ] = 0;
                                        }
                                        $fields[ $result->meta_key ][ 'values' ][ $key ] += $result->count;
                                    }
                                } else {
                                    if ( !isset( $fields[ $result->meta_key ][ 'values' ][ $value ] ) ) {
                                        $fields[ $result->meta_key ][ 'values' ][ $value ] = 0;
                                    }
                                    $fields[ $result->meta_key ][ 'values' ][ $value ] += $result->count;
                                }
                            }
                        } else {
                            continue;
                        }
                    }
                } else {
                    if ( $wpcf_field[ 'type' ] === 'radio' || $wpcf_field[ 'type' ] === 'select' ) {
                        # for radio and select use the unique option key as the value of the radio or select
                        $key = Search_Types_Custom_Fields_Widget::search_wpcf_field_options( $wpcf_field[ 'data' ][ 'options' ], 'value', $result->meta_value );
                        if ( !$key ) {
                            continue;
                        }
                        $fields[$result->meta_key][ 'values' ][ $key ] = $result->count;
                    } else {
                        $fields[$result->meta_key][ 'values' ][ $result->meta_value ] = $result->count;
                    }
                }
                $fields[$result->meta_key][ 'type' ]  = $wpcf_field[ 'type' ];
                $fields[$result->meta_key][ 'label' ] = $wpcf_field[ 'name' ];
            }   # foreach ( $results as $result ) {
            unset( $selected_imploded );
        }   # if ( $selected_imploded = array_filter( $selected, function( $v ) { return strpos( $v, '_wpcf_belongs_' ) !== 0; } ) ) {
        # get childs of selected parents
        if ( $selected_child_of = array_filter( $selected, function( $v ) { return strpos( $v, '_wpcf_belongs_' ) === 0; } ) ) {
            $selected_imploded = '("' . implode( '","', $selected_child_of ) . '")';
            # do all parent types with one sql query and filter the results later
            $results = $wpdb->get_results( $wpdb->prepare( <<<EOD
SELECT m.meta_key, m.meta_value, COUNT(*) count FROM $wpdb->postmeta m, $wpdb->posts pi, $wpdb->posts pv
    WHERE m.post_id = pi.ID AND m.meta_value = pv.ID AND m.meta_key IN $selected_imploded AND pi.post_type = %s GROUP BY m.meta_key, m.meta_value
EOD
            , $_REQUEST[ 'post_type' ] ) );
            foreach ( $selected_child_of as $parent ) {
                # do each parent type but results need to be filtered to this parent type
                if ( $selected_results = array_filter( $results, function( $result ) use ( $parent ) { 
                    return $result->meta_key == $parent; 
                } ) ) {
                    $post_type = substr( $parent, 14, strlen( $parent ) - 17 );
                    $fields[$parent] = [
                        'type' => 'child_of',
                        'label' => Search_Types_Custom_Fields_Widget::$CHILD_OF
                            . ( $post_type === 'post' || $post_type === 'page' ? $post_type : $wpcf_types[$post_type]['labels']['name'] ), 
                        'values' => array_reduce( $selected_results, function( $new_results, $result ) {
                                $new_results[$result->meta_value] = $result->count;
                                return $new_results;
                            }, [ ] )
                    ];
                }
            }
            unset( $selected_imploded );
        }   # if ( $selected_child_of = array_filter( $selected, function( $v ) { return strpos( $v, '_wpcf_belongs_' ) === 0; } ) ) {
        # get parents of selected childs
        if ( $selected_parent_of = array_filter( $selected, function( $v ) { return strpos( $v, 'inverse_' ) === 0; } ) ) {
            # get all the child post types
            $post_types = array_map( function( $v ) { return substr( $v, 8, strpos( $v, '__wpcf_belongs_' ) - 8 ); },
                $selected_parent_of );
            # get the '_wpcf_belongs_' meta_key - they are all identical so just use the first one
            $selected_parent_of = array_pop( $selected_parent_of );
            $selected_parent_of = substr( $selected_parent_of, strpos( $selected_parent_of, '_wpcf_belongs_' ) );
            # we can use just one sql query to do all the post types together and filter the results later
            $results = $wpdb->get_results( $wpdb->prepare( <<<EOD
SELECT pi.post_type, m.post_id, COUNT(*) count FROM $wpdb->postmeta m, $wpdb->posts pi, $wpdb->posts pv
    WHERE m.post_id = pi.ID AND m.meta_value = pv.ID AND m.meta_key = "$selected_parent_of" AND pv.post_type = %s GROUP BY pi.post_type, m.post_id
EOD
            , $_REQUEST[ 'post_type' ] ) );
            foreach ( $post_types as $post_type ) {
                # do each post type but the results need to be filtered this post type
                if ( $selected_results = array_filter( $results, function( $result ) use ( $post_type ) { 
                    return $result->post_type == $post_type; 
                } ) ) {
                    $fields[ "inverse_{$post_type}_{$selected_parent_of}" ] = [
                        'type' => 'parent_of',
                        'label' => Search_Types_Custom_Fields_Widget::$PARENT_OF
                            . ( $post_type === 'post' || $post_type === 'page' ? $post_type : $wpcf_types[$post_type]['labels']['name'] ), 
                        'values' => array_reduce( $selected_results, function( $new_results, $result ) {
                                $new_results[$result->post_id] = $result->count;
                                return $new_results;
                            }, [ ] )
                    ];
                }
            }
        }   # if ( $selected_parent_of = array_filter( $selected, function( $v ) { return strpos( $v, 'inverse_' ) === 0; } ) ) {
        if ( in_array( 'pst-std-post_content', $selected ) ) {
            $fields[ 'pst-std-post_content' ] = [ 'type' => 'textarea',   'label' => 'Post Content' ];
        }
        if ( in_array( 'pst-std-attachment', $selected ) ) {
            $fields[ 'pst-std-attachment' ]   = [ 'type' => 'attachment', 'label' => 'Attachment'   ];
        }
        if ( in_array( 'pst-std-post_author', $selected ) ) {
            $fields[ 'pst-std-post_author' ]  = [ 'type' => 'author',     'label' => 'Author'       ];
        }
        $posts = NULL;
        foreach ( $selected as $selection ) {
            if ( substr_compare( $selection, 'tax-cat-', 0, 8 ) === 0 || substr_compare( $selection, 'tax-tag-', 0, 8 ) === 0 ) {
                # do a taxonomy
                $tax_name = substr( $selection, 8 );
                $values = $terms[ $tax_name ];
                $taxonomy = $taxonomies[ $tax_name ];
?>
<div class="scpbcfw-search-fields stcfw-nohighlight">
<span class="scpbcfw-search-fields-field-label"><?php echo $taxonomy->label ?>:</span>
<div class="scpbcfw-display-button"><?php _e( 'Open', Search_Types_Custom_Fields_Widget::LANGUAGE_DOMAIN ); ?></div>
<div style="clear:both;"></div>
<div class="scpbcfw-search-field-values" style="display:none;">
<?php
                $count = -1;
                foreach ( $values['values'] as $term_id => &$result ) {
                    if ( ++$count == $SQL_LIMIT ) {
                        break;
                    }
?>
<input type="checkbox" id="<?php echo $tax_type . $taxonomy->name ?>" name="<?php echo $tax_type . $taxonomy->name ?>[]"
    value="<?php echo $term_id; ?>"><?php echo "$result[name]($result[count])"; ?><br>
<?php
                }   # foreach ( $values['values'] as $term_id => $result ) {
                unset( $result );
                if ( $count == $SQL_LIMIT ) {
?>
<input type="text"
    id="<?php echo $tax_type . $taxonomy->name . Search_Types_Custom_Fields_Widget::OPTIONAL_TEXT_VALUE_SUFFIX; ?>"
    name="<?php echo $tax_type . $taxonomy->name . Search_Types_Custom_Fields_Widget::OPTIONAL_TEXT_VALUE_SUFFIX; ?>"
    class="scpbcfw-search-fields-for-input" placeholder="<?php _e( '--Enter New Search Value--', Search_Types_Custom_Fields_Widget::LANGUAGE_DOMAIN ); ?>">
<?php
                }
?>
</div>
</div>
<?php
            } else {   # if ( substr_compare( $selection, 'tax-cat-', 0, 8 ) === 0 || substr_compare( $selection, 'tax-tag-', 0, 8 ) === 0 ) {
                # do a custom field, post_content or author
                $meta_key = $selection;
                $field = $fields[ $meta_key ];
                $field_type = $field[ 'type' ];
                $wpcf_field = substr_compare( $meta_key, 'wpcf-', 0, 5 ) === 0 ? $wpcf_fields[ substr( $meta_key, 5 ) ] : NULL;
                $wpcf_field_data = $wpcf_field && array_key_exists( 'data', $wpcf_field ) ? $wpcf_field[ 'data' ] : NULL;
                $wpcf_field_data_options = $wpcf_field_data && array_key_exists( 'options', $wpcf_field_data ) ? $wpcf_field_data[ 'options' ] : NULL;
?>
<div class="scpbcfw-search-fields stcfw-nohighlight">
<span class="scpbcfw-search-fields-field-label"><?php echo $field[ 'label' ] ?>:</span>
<div class="scpbcfw-display-button"><?php _e( 'Open', Search_Types_Custom_Fields_Widget::LANGUAGE_DOMAIN ); ?></div>
<div style="clear:both;"></div>
<div class="scpbcfw-search-field-values" style="display:none;">
<?php
                if ( $field_type === 'textarea' || $field_type === 'wysiwyg' ) {
?>
<input id="<?php echo $meta_key ?>" name="<?php echo $meta_key ?>" class="scpbcfw-search-fields-for-input" type="text"
    placeholder="<?php _e( '--Enter Search Value--', Search_Types_Custom_Fields_Widget::LANGUAGE_DOMAIN ); ?>">
</div>
</div>
<?php
                    continue;
                }
                if ( $meta_key === 'pst-std-attachment' ) {
                    $results = $wpdb->get_results( $wpdb->prepare( <<<EOD
SELECT a.ID, a.post_title FROM $wpdb->posts a, $wpdb->posts p
    WHERE a.post_parent = p.ID AND a.post_type = "attachment" AND p.post_type = %s AND p.post_status = "publish" LIMIT $SQL_LIMIT
EOD
                        , $_REQUEST[ 'post_type' ] ), OBJECT );
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
                if ( $meta_key === 'pst-std-post_author' ) {
                    # use author display name in place of author id
                    $results = $wpdb->get_results( $wpdb->prepare( <<<EOD
SELECT p.post_author, u.display_name, COUNT(*) count FROM $wpdb->posts p, $wpdb->users u
    WHERE p.post_author = u.ID AND p.post_type = %s AND p.post_status = "publish" AND p.post_author IS NOT NULL GROUP BY p.post_author
EOD
                        , $_REQUEST[ 'post_type' ] ), OBJECT );
                    foreach ( $results as $result ) {
?>
<input type="checkbox" id="<?php echo $meta_key ?>" name="<?php echo $meta_key ?>[]"
    value="<?php echo $result->post_author; ?>"> <?php echo $result->display_name . " ($result->count)"; ?><br>
<?php
                    }
?>
</div>
</div>
<?php
                    continue;
                }   # if ( $meta_key === 'pst-std-post_author' ) {
                # now output the checkboxes
                $number = -1;
                foreach ( $field[ 'values' ] as $value => $count ) {
                    if ( ++$number == $SQL_LIMIT ) {
                        break;
                    }
                    if ( $field_type === 'child_of' || $field_type === 'parent_of' ) {
                        # for child of and parent of use post title instead of post id for label
                        if ( $posts === NULL ) {
                            $posts = $wpdb->get_results( "SELECT ID, post_type, post_title FROM $wpdb->posts ORDER BY ID", OBJECT_K );
                        }    
                        $label = $posts[ $value ]->post_title;
                    } else if ( $field_type === 'radio' ) {
                        # for radio replace option key with something more user friendly
                        $wpcf_field_data_options_value = $wpcf_field_data_options[ $value ];
                        if ( isset( $option[ 'use_simplified_labels_for_select' ] ) ) {
                            $label = $wpcf_field_data[ 'display' ] === 'value' ? $wpcf_field_data_options_value[ 'display_value' ]
                                : $wpcf_field_data_options_value[ 'title' ];
                        } else {
                            $label = $wpcf_field_data_options_value[ 'title' ] . ( $wpcf_field_data[ 'display' ] == 'value'
                                ? ( '(' . $wpcf_field_data_options_value[ 'display_value' ] . ')' )
                                : ( '(' . $wpcf_field_data_options_value[ 'value' ] . ')' ) );
                        }
                    } else if ( $field_type === 'select' ) {
                        # for select replace option key with something more user friendly
                        $wpcf_field_data_options_value = $wpcf_field_data_options[ $value ]; 
                        if ( isset( $option[ 'use_simplified_labels_for_select' ] ) ) {
                            $label = $wpcf_field_data_options_value[ 'title' ];
                        } else {
                            $label = $wpcf_field_data_options_value[ 'value' ] . '(' . $wpcf_field_data_options_value[ 'title' ] . ')';
                        }
                    } else if ( $field_type === 'checkboxes' ) {
                        # checkboxes are handled very differently from radio and select 
                        # Why? seems that the radio/select way would work here also and be simpler
                        $wpcf_field_data_options_value = $wpcf_field_data_options[ $value ];
                        if ( isset( $option[ 'use_simplified_labels_for_select' ] ) ) {
                            if ( $wpcf_field_data_options_value[ 'display' ] == 'value' ) {
                                $label = $wpcf_field_data_options_value[ 'display_value_selected' ];
                            } else {
                                $label = $wpcf_field_data_options_value[ 'title' ];
                            }
                        } else {
                            $label = $wpcf_field_data_options_value[ 'title' ];
                             if ( $wpcf_field_data_options_value[ 'display' ] == 'db' ) {
                                $label .= ' (' . $wpcf_field_data_options_value[ 'set_value' ] . ')';
                            } else if ( $wpcf_field_data_options_value[ 'display' ] == 'value' ) {
                                $label .= ' (' . $wpcf_field_data_options_value[ 'display_value_selected' ] . ')';
                            }
                        }
                    } else if ( $field_type === 'checkbox' ) {
                        if ( $wpcf_field_data[ 'display' ] === 'db' ) {
                            $label = $value;
                        } else {
                            if ( $value ) {
                                $label = $wpcf_field_data[ 'display_value_selected' ];
                            } else {
                                $label = $wpcf_field_data[ 'display_value_not_selected' ];
                            }
                        }
                    } else if ( $field_type === 'image' || $field_type === 'file' || $field_type === 'audio' || $field_type === 'video' ) {
                        # use only filename for images and files
                        $label = ( $i = strrpos( $value, '/' ) ) !== FALSE ? substr( $value, $i + 1 ) : $value;
                    } else if ( $field_type === 'date' ) {
                        $label = date( Search_Types_Custom_Fields_Widget::DATE_FORMAT, $value );
                    } else if ( $field_type === 'url' ) {
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
                if ( $number == $SQL_LIMIT && ( $field_type !== 'child_of' && $field_type !== 'parent_of' && $field_type !== 'checkboxes'
                    && $field_type !== 'radio' && $field_type !== 'select' ) ) {
                    # only show optional input textbox if there are more than SQL_LIMIT items for fields with user specified values
?>
<input id="<?php echo $meta_key . Search_Types_Custom_Fields_Widget::OPTIONAL_TEXT_VALUE_SUFFIX; ?>"
    name="<?php echo $meta_key . Search_Types_Custom_Fields_Widget::OPTIONAL_TEXT_VALUE_SUFFIX; ?>"
    class="scpbcfw-search-fields-for-input" type="text"
    placeholder="<?php _e( '--Enter Search Value--', Search_Types_Custom_Fields_Widget::LANGUAGE_DOMAIN ); ?>">
<?php
                }
                if ( $field_type === 'numeric' || $field_type === 'date' ) {
                    # only show minimum/maximum input textbox for numeric and date custom fields
?>
<h4>Range Search</h4>
<input id="<?php echo $meta_key . Search_Types_Custom_Fields_Widget::OPTIONAL_MINIMUM_VALUE_SUFFIX; ?>"
    name="<?php echo $meta_key . Search_Types_Custom_Fields_Widget::OPTIONAL_MINIMUM_VALUE_SUFFIX; ?>"
    class="scpbcfw-search-fields-for-input" type="text"
    placeholder="<?php _e( '--Enter Minimum Value--', Search_Types_Custom_Fields_Widget::LANGUAGE_DOMAIN ); ?>">
<input id="<?php echo $meta_key . Search_Types_Custom_Fields_Widget::OPTIONAL_MAXIMUM_VALUE_SUFFIX; ?>"
    name="<?php echo $meta_key . Search_Types_Custom_Fields_Widget::OPTIONAL_MAXIMUM_VALUE_SUFFIX; ?>"
    class="scpbcfw-search-fields-for-input" type="text"
    placeholder="<?php _e( '--Enter Maximum Value--', Search_Types_Custom_Fields_Widget::LANGUAGE_DOMAIN ); ?>">
<?php
                }
?>
</div>
</div>
<?php
            }
        }   # foreach ( $selected as $selection ) {
        die();
    } );   # add_action( 'wp_ajax_nopriv_' . Search_Types_Custom_Fields_Widget::GET_FORM_FOR_POST_TYPE, function() {
} else {   # if ( is_admin() ) {
    
    add_action( 'wp_head', function( ) {
?>
<script type="text/javascript">
var ajaxurl="<?php echo admin_url( 'admin-ajax.php' ); ?>";
</script>
<?php
    } );
    
    add_action( 'wp_enqueue_scripts', function( ) {
        wp_enqueue_style(  'stcfw-search', plugins_url( 'stcfw-search.css', __FILE__ ) );
        wp_enqueue_script( 'stcfw-search', plugins_url( 'stcfw-search.js',  __FILE__ ), [ 'jquery' ] );
        wp_localize_script( 'stcfw-search', 'stcfwSearchTranslations', [
            'open' => __( 'Open', Search_Types_Custom_Fields_Widget::LANGUAGE_DOMAIN ),
            'close' => __( 'Close', Search_Types_Custom_Fields_Widget::LANGUAGE_DOMAIN )
        ] );
    } );
    
    add_action( 'parse_query', function( &$query ) {
        if ( !$query->is_main_query() || !array_key_exists( 'search_types_custom_fields_form', $_REQUEST ) ) {
            return;
        }
        $option = get_option( $_REQUEST[ 'search_types_custom_fields_widget_option' ] );
        $number = $_REQUEST[ 'search_types_custom_fields_widget_number' ];
        if ( isset( $option[ $number ][ 'set_is_search' ] ) ) {
            # depending on the theme this may display excerpts instead of the full post
            $query->is_search = true;
        }
    } );
    
    add_filter( 'posts_where', function( $where, $query ) {
        global $wpdb;
        if ( !$query->is_main_query() || !array_key_exists( 'search_types_custom_fields_form', $_REQUEST ) ) {
            return $where;
        }
        # this is a Types search request so modify the SQL where clause
        $and_or = $_REQUEST['search_types_custom_fields_and_or'] == 'and' ? 'AND' : 'OR';
        # first get taxonomy name to term_taxonomy_id transalation table in case we need the translations
        $results = $wpdb->get_results( <<<EOD
SELECT x.taxonomy, t.name, x.term_taxonomy_id FROM $wpdb->term_taxonomy x, $wpdb->terms t WHERE x.term_id = t.term_id
EOD
            , OBJECT );
        $term_taxonomy_ids = array();
        foreach ( $results as $result ) {
            $term_taxonomy_ids[$result->taxonomy][strtolower( $result->name)] = $result->term_taxonomy_id;
        }
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
                if ( !array_key_exists( $index, $_REQUEST ) || is_array( $_REQUEST[$index] ) ) {
                    $_REQUEST[$index][] = array( 'operator' => $is_min ? 'minimum' : 'maximum', 'value' => $request );
                }
                # kill the original request
                $request = NULL;
            }
        }
        unset( $request );
        $wpcf_fields = get_option( 'wpcf-fields', [ ] );    
        $non_field_keys = [ 'search_types_custom_fields_form', 'search_types_custom_fields_widget_option', 'search_types_custom_fields_widget_number',
            'search_types_custom_fields_and_or', 'search_types_custom_fields_show_using_macro', 'post_type', 'paged' ];
        $sql = '';
        foreach ( $_REQUEST as $key => $values ) {
            # here only searches on the table $wpdb->postmeta are processed; everything is done later.
            if ( in_array( $key, $non_field_keys ) ) {
                continue;
            }
            $prefix = substr( $key, 0, 8 );
            if ( $prefix === 'tax-cat-' || $prefix === 'tax-tag-' || $prefix === 'pst-std-' ) {
                continue;
            }
            if ( !is_array( $values) ) {
                if ( $values ) {
                    $values = [ $values ];
                } else {
                    continue;
                }
            }
            $sql2 = '';   # holds meta_value = sql
            $sql3 = '';   # holds meta_value min/max sql
            foreach ( $values as $value ) {
                if ( $sql2 ) {
                    $sql2 .= ' OR ';
                }
                if ( strpos( $key, 'inverse_' ) === 0 ) {
                    # parent of is the inverse of child of so ...
                    if ( !$value ) {
                        continue;
                    }
                    $sql2 .= '( w.meta_key = "' . substr( $key, strpos( $key, '_wpcf_belongs_' ) ) . "\" AND w.post_id = $value )";
                } else if ( strpos( $key, '_wpcf_belongs_' ) === 0 ) {
                    # child of is like a custom field except the name is special so ...
                    if ( !$value ) {
                        continue;
                    }
                    $sql2 .= "( w.meta_key = '$key' AND w.meta_value = $value )";
                } else {
                    $wpcf_field = $wpcf_fields[ substr( $key, 5 ) ];
                    $wpcf_field_type = $wpcf_field[ 'type' ];
                    if ( is_array( $value ) ) {
                        if ( $sql2 ) { $sql2 = substr( $sql2, 0, -4 ); }
                        # check for minimum/maximum operation
                        if ( ( $is_min = $value[ 'operator' ] === 'minimum' ) || ( $is_max = $value[ 'operator' ] === 'maximum' ) ) {
                            if ( $wpcf_field_type === 'date' ) {
                                # for dates convert to timestamp range
                                list( $t0, $t1 ) = Search_Types_Custom_Fields_Widget::get_timestamp_from_string( $value[ 'value' ] );
                                if ( $is_min ) {
                                    # for minimum use start of range
                                    $value[ 'value' ] = $t0;
                                } else {
                                    # for maximum use end of range
                                    $value[ 'value' ] = $t1;
                                }
                            }
                            if ( $sql3 ) {
                                $sql3 .= ' AND ';
                            }
                            if ( $is_min ) {
                                $sql3 .= $wpdb->prepare( "( w.meta_key = %s AND w.meta_value >= %d )", $key, $value[ 'value' ] );
                            } else if ( $is_max ) {
                                $sql3 .= $wpdb->prepare( "( w.meta_key = %s AND w.meta_value <= %d )", $key, $value[ 'value' ] );
                            }
                        }
                    } else if ( $wpcf_field_type !== 'checkbox' && !$value ) {
                        # skip false values except for single checkbox
                    } else if ( $wpcf_field_type === 'date' ) {
                        # date can be tricky if user did not enter a complete - to the second - timestamp
                        # need to search on range in that case
                        if ( is_numeric( $value ) ) {
                            $sql2 .= $wpdb->prepare( "( w.meta_key = %s AND w.meta_value = %d )", $key, $value );
                        } else {
                            list( $t0, $t1 ) = Search_Types_Custom_Fields_Widget::get_timestamp_from_string( $value );    
                           if ( $t1 != $t0 ) {
                                $sql2 .= $wpdb->prepare( "( w.meta_key = %s AND w.meta_value >= %d AND w.meta_value <= %d )",
                                    $key, $t0, $t1 );
                            } else {
                                $sql2 .= $wpdb->prepare( "( w.meta_key = %s AND w.meta_value = %d )", $key, $t0 );
                            }
                        }
                    } else {
                        $wpcf_field_data = $wpcf_field[ 'data' ];
                        if ( $wpcf_field_type === 'radio' || $wpcf_field_type === 'select' ) {
                            # for radio and select change value from option key to its value
                            $value = $wpcf_field_data[ 'options' ][ $value ][ 'value' ];
                        } else if ( $wpcf_field_type === 'checkboxes' ) {
                            # checkboxes are tricky since the value bound to 0 means unchecked so must also check the bound value
                            $options_value_set_value = $wpcf_field_data[ 'options' ][ $value ][ 'set_value' ];
                            $value = 's:' . strlen( $value ) .':"' .$value . '";a:1:{i:0;s:' . strlen( $options_value_set_value ) . ':"'
                                . $options_value_set_value . '";}';
                        } else if ( $wpcf_field_type === 'checkbox' ) {
                            # checkbox is tricky since the value bound to 0 means unchecked so must also check the bound value
                            if ( $value ) {
                                $value = $wpcf_field_data[ 'set_value' ];
                            }
                        }
                        # TODO: LIKE may match more than we want on serialized array of numeric values - false match on numeric indices
                        $sql2 .= $wpdb->prepare( "( w.meta_key = %s AND w.meta_value LIKE %s )", $key, "%%$value%%" );
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
            if ( $sql ) {
                $sql .= " $and_or ";
            }
            $sql .= " EXISTS ( SELECT * FROM $wpdb->postmeta w WHERE $sql2 ) ";
        }   # foreach ( $_REQUEST as $key => $values ) {
        if ( $sql ) {
            $ids0 = $wpdb->get_col( $wpdb->prepare( "SELECT p.ID FROM $wpdb->posts p WHERE p.post_type = %s AND ( $sql )", $_REQUEST['post_type'] ) );
            if ( $and_or === 'AND' && !$ids0 ) {
                return ' AND 1 = 2 ';
            }
        } else {
            $ids0 = FALSE;
        }
        $sql = '';
        foreach ( $_REQUEST as $key => $values ) {
            # here only taxonomies are processed
            if ( in_array( $key, $non_field_keys ) ) {
                continue;
            }
            $prefix = substr( $key, 0, 8 );
            if ( $prefix !== 'tax-cat-' && $prefix !== 'tax-tag-' ) {
                continue;
            }
            if ( !is_array( $values) ) {
                if ( $values ) {
                    $values = array( $values );
                } else {
                    continue;
                }
            }
            $values = array_filter( $values ); 
            if ( !$values ) {
                continue;
            }
            $taxonomy = substr( $key, 8 );
            if ( $sql ) {
                $sql .= " $and_or ";
            }
            $sql .= " EXISTS ( SELECT * FROM $wpdb->term_relationships WHERE ( ";
            foreach ( $values as $value ) {
                if ( $value !== $values[0] ) {
                    $sql .= ' OR ';
                }
                $sql .= $wpdb->prepare( 'term_taxonomy_id = %d', $value ); 
            }
            $sql .= ') AND object_id = p.ID )';
        }   # foreach ( $_REQUEST as $key => $values ) {
        if ( $sql ) {
            $ids1 = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts p WHERE p.post_type = %s AND ( $sql ) ", $_REQUEST['post_type'] ) );
            if ( $and_or === 'AND' && !$ids1 ) {
                return ' AND 1 = 2 ';
            }
       } else {
            $ids1 = FALSE;
        }
        $ids = Search_Types_Custom_Fields_Widget::join_arrays( $and_or, $ids0, $ids1 );
        if ( $and_or === 'AND' && $ids !== FALSE && !$ids ) {
            return ' AND 1 = 2 ';
        }
        # do post attachments
        if ( array_key_exists( 'pst-std-attachment', $_REQUEST ) && $_REQUEST['pst-std-attachment'] ) {
            $post_attachments = implode( ',', array_map( function( $attachment ) {
                global $wpdb;
                return $wpdb->prepare( '%d', $attachment );
            }, $_REQUEST['pst-std-attachment'] ) );
            $ids2 = $wpdb->get_col( "SELECT post_parent FROM $wpdb->posts WHERE ID IN ( $post_attachments )" );
            if ( $and_or === 'AND' && !$ids2 ) { return ' AND 1 = 2 '; }
        } else {
            $ids2 = FALSE;
        }
        $ids = Search_Types_Custom_Fields_Widget::join_arrays( $and_or, $ids, $ids2 );
        if ( $and_or === 'AND' && $ids !== FALSE && !$ids ) {
            return ' AND 1 = 2 ';
        }
        # handle post_content - post_title and post_excerpt are included in the search of post_content
        if ( array_key_exists( 'pst-std-post_content', $_REQUEST ) && $_REQUEST['pst-std-post_content'] ) {
            $sql = $wpdb->prepare( <<<EOD
SELECT ID FROM $wpdb->posts WHERE post_type = %s AND post_status = "publish" AND ( post_content LIKE %s OR post_title LIKE %s OR post_excerpt LIKE %s )
EOD
                , $_REQUEST[ 'post_type' ], "%{$_REQUEST['pst-std-post_content']}%", "%{$_REQUEST['pst-std-post_content']}%",
                "%{$_REQUEST['pst-std-post_content']}%" );
            $ids3 = $wpdb->get_col( $sql );
            if ( $and_or === 'AND' && !$ids3 ) {
                return ' AND 1 = 2 ';
            }
        } else {
            $ids3 = FALSE;
        }
        $ids = Search_Types_Custom_Fields_Widget::join_arrays( $and_or, $ids, $ids3 );
        if ( $and_or === 'AND' && $ids !== FALSE && !$ids ) { return ' AND 1 = 2 '; }
        # filter on post_author
        if ( array_key_exists( 'pst-std-post_author', $_REQUEST ) && $_REQUEST['pst-std-post_author'] ) {
            $post_authors = implode( ',', array_map( function( $author ) {
                global $wpdb;
                return $wpdb->prepare( "%d", $author );
            }, $_REQUEST['pst-std-post_author'] ) );
            $ids4 = $wpdb->get_col( $wpdb->prepare( <<<EOD
                SELECT ID FROM $wpdb->posts WHERE post_type = %s AND post_status = 'publish' AND post_author IN ( $post_authors )
EOD
                , $_REQUEST[ 'post_type' ] ) );
            if ( $and_or === 'AND' && !$ids4 ) {
                return ' AND 1 = 2 ';
            }
        } else {
            $ids4 = FALSE;
        }
        $ids = Search_Types_Custom_Fields_Widget::join_arrays( $and_or, $ids, $ids4 );
        if ( $and_or === 'AND' && $ids !== FALSE && !$ids ) {
            return ' AND 1 = 2 ';
        }        
        if ( $ids ) {
            $ids = implode( ', ', $ids );
            $where = " AND ID IN ( $ids ) ";
        } else {
            #$where = " AND post_type = "$_REQUEST[post_type]" AND post_status = 'publish' ";
            $where = ' AND 1 = 2 ';
        }
        return $where;
    }, 10, 2 );   # add_filter( 'posts_where', function( $where, $query ) {

    if ( isset( $_REQUEST[ 'search_types_custom_fields_show_using_macro' ] )
        && $_REQUEST[ 'search_types_custom_fields_show_using_macro' ] === 'use macro' ) {
        # for alternate output format do not page output
        add_filter( 'post_limits', function( $limit, &$query ) {
            if ( !$query->is_main_query( ) ) {
                return $limit;
            }
            return ' ';
        }, 10, 2 );
        add_action( 'wp_enqueue_scripts', function( ) {
            # use post type specific css file if it exists otherwise use the default css file
            if ( file_exists( dirname( __FILE__ ) . "/search-results-table-$_REQUEST[post_type].css") ) {
                wp_enqueue_style( 'search_results_table', plugins_url( "search-results-table-$_REQUEST[post_type].css", __FILE__ ) );
            } else {
                wp_enqueue_style( 'search_results_table', plugins_url( 'search-results-table.css', __FILE__ ) );
            }
        } );
        add_action( 'template_redirect', function( ) {
            global $wp_query;
            global $wpdb;
            # in this case a template is dynamically constructed and returned
            # get the list of posts
            $posts = array_map( function( $post ) { return $post->ID; }, $wp_query->posts );
            $posts_imploded = implode( ', ', $posts );
            $number = $_REQUEST[ 'search_types_custom_fields_widget_number' ];
            $option = get_option( $_REQUEST[ 'search_types_custom_fields_widget_option' ] )[ $number ];
            # get the applicable fields from the options for this widget
            if ( array_key_exists( 'scpbcfw-show-' . $_REQUEST[ 'post_type' ], $option ) ) {
                # display fields explicitly specified for post type
                $fields = $option[ 'scpbcfw-show-' . $_REQUEST[ 'post_type' ] ];
            } else {
                # display fields not explicitly specified so just use the search fields for post type
                $fields = $option[ $_REQUEST[ 'post_type' ] ];
            }
            if ( $container_width = $option[ 'search_table_width' ] ) {
                $container_style = "style=\"width:{$container_width}px\"";
            } else {
                $container_style = '';
            }
            # build the main content from the above parts
            # the macro has parameters: posts - a list of post ids, fields - a list of field names, a_post - any valid post id,
            # and post_type - the post type
            # finally output all the HTML
            # first do the header
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'jquery.tablesorter.min', plugins_url( 'jquery.tablesorter.min.js', __FILE__ ),
                array( 'jquery' ) );
            add_action( 'wp_head', function( ) {
?>
<script type="text/javascript">
    jQuery(document).ready(function(){jQuery("table.tablesorter").tablesorter();}); 
</script>
<?php
            } );
            get_header( );
            # then do the body content
            $wpcf_fields = get_option( 'wpcf-fields', [ ] );
            $labels = get_post_type_object( $_REQUEST[ 'post_type' ] )->labels;
            $label = isset( $labels->singular_name ) ? $labels->singular_name : $labels->name;
            $content = <<<EOD
<div style="width:99%;overflow:auto;">
    <div class="scpbcfw-result-container"$container_style>
        <table class="scpbcfw-result-table tablesorter">
            <thead><tr><th class="scpbcfw-result-table-head-post">$label</th>
EOD;
            # fix taxonomy names for use as titles
            foreach ( $fields as $field ) {
                if ( substr_compare( $field, 'tax-cat-', 0, 8, FALSE ) === 0 || substr_compare( $field, 'tax-tag-', 0, 8, FALSE ) === 0 ) {
                    $field = substr( $field, 8 );
                    $labels = get_taxonomy( $field )->labels;
                    $field = isset( $labels->singular_name ) ? $labels->singular_name : $labels->name;                    
                } else if ( $field === 'pst-std-attachment' ) {
                    $field = 'Attachment';
                } else if ( $field === 'pst-std-post_author' ) {
                    $field = 'Author';
                } else if ( $field === 'pst-std-post_content' ) {
                    $field = 'Excerpt';
                } else if ( substr_compare( $field, 'wpcf-', 0, 5, FALSE ) === 0 ) {
                    $field = $wpcf_fields[ substr( $field, 5 ) ][ 'name' ];
                } else if ( substr_compare( $field, '_wpcf_belongs_', 0, 14 ) === 0 ) {
                    $field = substr( $field, 14, -3 );
                    $labels = get_post_type_object( $field )->labels;
                    $field = isset( $labels->singular_name ) ? $labels->singular_name : $labels->name;
                } else if ( substr_compare( $field, 'inverse_', 0, 8 ) === 0 ) {
                    $field = substr( $field, 8, strpos( $field, '__wpcf_belongs_' ) - 8 );
                    $labels = get_post_type_object( $field )->labels;
                    $field = isset( $labels->singular_name ) ? $labels->singular_name : $labels->name;
                }
                $content .= "<th class=\"scpbcfw-result-table-head-$field\">$field</th>";
            }
            unset( $field );
            $content .= '</tr></thead><tbody>';
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
                    $td = '<td></td>';
                    if ( substr_compare( $field, 'tax-cat-', 0, 8, FALSE ) === 0 || substr_compare( $field, 'tax-tag-', 0, 8, FALSE ) === 0 ) {
                        $taxonomy = substr( $field, 8 );
                        # TODO: may be more efficient to get the terms for all the posts in one query
                        if ( is_array( $terms = get_the_terms( $post, $taxonomy ) ) ) {
                            $terms = array_map( function( $term ) { return $term->name; }, $terms );
                            $td = "<td class=\"scpbcfw-result-table-detail-$taxonomy\">" . implode( ', ', $terms ) . '</td>';
                        }
                    } else if ( ( $child_of = strpos( $field, '_wpcf_belongs_' ) === 0 )
                        || ( $parent_of = strpos( $field, 'inverse_' ) === 0 ) ) {
                        if ( $child_of ) {
                            if ( !isset( $child_of_values[$field] ) ) {
                                # Do one query for all posts on first post and save the result for later posts
                                $child_of_values[$field] = $wpdb->get_results( <<<EOD
                                    SELECT m.post_id, m.meta_value FROM $wpdb->postmeta m, $wpdb->posts p
                                        WHERE m.meta_value = p.ID AND p.post_status = 'publish'
                                            AND m.meta_key = '$field' AND m.post_id IN ( $posts_imploded )
EOD
                                    , OBJECT_K );
                            }
                            $value = array_key_exists( $post, $child_of_values[ $field ] ) ? $child_of_values[ $field ][ $post ]->meta_value : '';
                        } else if ( $parent_of ) {
                            if ( !isset( $parent_of_values[$field] ) ) {
                                # Do one query for all posts on first post and save the result for later posts
                                # This case is more complex since a parent can have multiple childs
                                $post_type = substr( $field, 8, strpos( $field, '_wpcf_belongs_' ) - 9 );
                                $meta_key = substr( $field, strpos( $field, '_wpcf_belongs_' ) );
                                $results = $wpdb->get_results( <<<EOD
                                    SELECT m.meta_value, m.post_id FROM $wpdb->postmeta m, $wpdb->posts p
                                        WHERE m.post_id = p.ID AND p.post_status = 'publish' AND p.post_type = '$post_type'
                                            AND m.meta_key = '$meta_key' AND m.meta_value IN ( $posts_imploded )
EOD
                                    , OBJECT );
                                $values = [ ];
                                foreach ( $results as $result ) {
                                    $values[ $result->meta_value ][ ] = $result->post_id;
                                }
                                $parent_of_values[ $field ] = $values;
                                unset( $values );
                            }
                            $value = array_key_exists( $post, $parent_of_values[$field] ) ? $parent_of_values[$field][$post] : NULL;
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
                        # for efficiency on first iteration get all relevant attachments for all posts for use by later iterations
                        if ( !isset( $attachments ) ) {
                            $results = $wpdb->get_results( <<<EOD
                                SELECT ID, post_parent FROM $wpdb->posts
                                    WHERE post_type = 'attachment' AND post_parent IN ( $posts_imploded )
EOD
                                , OBJECT );
                            $attachments = array();
                            foreach ( $results as $result ) {
                                $attachments[ $result->post_parent ][ ] = $result->ID;
                            }
                        }
                        if ( array_key_exists( $post, $attachments ) ) {
                            $label = implode( ', ', array_map( function( $v ) use ( &$post_titles ) {
                                return "<a href=\"{$post_titles[$v]->guid}\">{$post_titles[$v]->post_title}</a>";
                            }, $attachments[ $post ] ) );
                            $td = "<td class=\"scpbcfw-result-table-detail-$field\">$label</td>";
                        }
                    } else if ( $field === 'pst-std-post_author' ) {
                        # use user display name in place of user id
                        # for efficiency on first iteration get all relevant user data for all posts for use by later iterations
                        if ( !isset( $authors ) ) {
                            $authors = $wpdb->get_results( <<<EOD
                                SELECT p.ID, u.display_name, u.user_url FROM $wpdb->posts p, $wpdb->users u
                                    WHERE p.post_author = u.ID AND p.ID IN ( $posts_imploded )
EOD
                                , OBJECT_K );
                        }
                        if ( array_key_exists( $post, $authors ) ) {
                            $author = $authors[ $post ];
                            # if author has a url then display author name as a link to his url
                            if ( $author->user_url ) {
                                $label = "<a href=\"$author->user_url\">$author->display_name</a>";
                            } else {
                                $label = $author->display_name;
                            }
                            $td = "<td class=\"scpbcfw-result-table-detail-$field\">$label</td>";
                        }
                    } else if ( $field === 'pst-std-post_content' ) {
                        # use post excerpt in place of post content
                        # for efficiency on first iteration get all relevant post excerpts for all posts for use by later iterations
                        if ( !isset( $excerpts ) ) {
                            $excerpts = $wpdb->get_results(
                                "SELECT ID, post_excerpt FROM $wpdb->posts WHERE ID IN ( $posts_imploded )", OBJECT_K );
                        }
                        if ( array_key_exists( $post, $excerpts ) ) {
                            $label = $excerpts[$post]->post_excerpt;
                            if ( !$label ) {
                                # use auto generated excerpt if there is no user supplied excerpt 
                                if ( $post_for_excerpt = get_post( $post ) ) {
                                    if ( !post_password_required( $post ) ) {
                                        # copied and modified from wp_trim_excerpt() of wp-includes/formatting.php
                                        $label = $post_for_excerpt->post_content;
                                        $label = strip_shortcodes( $label );
                                        $label = apply_filters( 'the_content', $label );
                                        $label = str_replace(']]>', ']]&gt;', $label);
                                        $label = wp_trim_words( $label, 8, ' ' . '&hellip;' );
                                    }
                                }
                            }
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
                                $values[ $result->post_id ][ ] = $result->meta_value;
                            }
                            $field_values[ $field ] = $values;
                            unset( $values );
                        }
                        if ( array_key_exists( $post, $field_values[ $field ] ) && ( $field_values = $field_values[ $field ][ $post ] ) ) {
                            $wpcf_field = $wpcf_fields[ substr( $field, 5 ) ];
                            $wpcf_field_type = $wpcf_field[ 'type' ];
                            $wpcf_field_data = array_key_exists( 'data', $wpcf_field ) ? $wpcf_field['data'] : NULL;
                            $wpcf_field_data_options = array_key_exists( 'options', $wpcf_field_data ) ? $wpcf_field_data[ 'options' ] : NULL;
                            $class = '';
                            $labels = [ ];
                            foreach ( $field_values as $value ) {
                                if ( !$value && $wpcf_field_type !== 'checkbox' ) {
                                    continue;
                                }
                                if ( is_serialized( $value ) ) {
                                    # serialized meta_value contains multiple values so need to unpack them and process them individually
                                    $unserialized = unserialize( $value );
                                     if ( is_array( $unserialized ) ) {
                                        if ( $wpcf_field_type === 'checkboxes' ) {
                                            # for checkboxes use the unique option key as the value of the checkbox
                                            $values = array_keys( $unserialized );
                                        } else {
                                            $values = array_values( $unserialized );
                                        }
                                    } else {
                                        error_log( '##### action:template_redirect()[UNEXPECTED!]:$unserialized='
                                            . print_r( $unserialized, true ) );
                                        $values = [ $unserialized ];
                                    }
                                } else {
                                    if ( $wpcf_field_type === 'radio' || $wpcf_field_type === 'select' ) {
                                        # for radio and select use the unique option key as the value of the radio or select
                                        $values = [ Search_Types_Custom_Fields_Widget::search_wpcf_field_options(
                                            $wpcf_field_data_options, 'value', $value ) ];
                                    } else {
                                        $values = [ $value ];
                                    }
                                }
                                unset( $value );
                                $label = [ ];
                                foreach ( $values as $value ) {
                                    if ( strlen( $value ) > 7 && ( substr_compare( $value, 'http://', 0, 7, true ) === 0
                                        || substr_compare( $value, 'https://', 0, 8, true ) === 0 ) ) {
                                        $url = $value;
                                    }
                                    $current =& $label[ ];
                                    if ( $wpcf_field_type === 'radio' ) {
                                        # for radio replace option key with something more user friendly
                                        $wpcf_field_data_options_value = $wpcf_field_data_options[ $value ];
                                        if ( isset( $option[ 'use_simplified_labels_for_select' ] ) ) {
                                            $current = $wpcf_field_data[ 'display' ] === 'value' ? $wpcf_field_data_options_value[ 'display_value' ]
                                                : $wpcf_field_data_options_value[ 'title' ];
                                        } else {
                                            $current = $wpcf_field_data_options_value[ 'title' ]
                                                . ( $wpcf_field_data[ 'display' ] === 'value' ? ( '(' . $wpcf_field_data_options_value[ 'display_value' ] . ')' )
                                                    : ( '(' . $wpcf_field_data_options_value[ 'value' ] . ')' ) );
                                        }
                                    } else if ( $wpcf_field_type === 'select' ) {
                                        $wpcf_field_data_options_value = $wpcf_field_data_options[ $value ];
                                        # for select replace option key with something more user friendly
                                        if ( isset( $option[ 'use_simplified_labels_for_select' ] ) ) {
                                            $current = $wpcf_field_data_options_value[ 'title' ];
                                        } else {
                                            $current = $wpcf_field_data_options_value[ 'value' ]
                                                . '(' . $wpcf_field_data_options_value[ 'title' ] . ')';
                                        }
                                    } else if ( $wpcf_field_type === 'checkboxes' ) {
                                        # checkboxes are handled very differently from radio and select 
                                        # Why? seems that the radio/select way would work here also and be simpler
                                        $wpcf_field_data_options_value = $wpcf_field_data_options[ $value ];
                                        if ( isset( $option[ 'use_simplified_labels_for_select' ] ) ) {
                                            if ( $wpcf_field_data_options_value[ 'display' ] === 'value' ) {
                                                $current = $wpcf_field_data_options_value[ 'display_value_selected' ];
                                            } else {
                                                $current = $wpcf_field_data_options_value[ 'title' ];
                                            }
                                        } else {
                                            $current = $wpcf_field_data_options_value[ 'title' ];
                                             if ( $wpcf_field_data_options_value[ 'display' ] === 'db' ) {
                                                $current .= ' (' . $wpcf_field_data_options_value[ 'set_value' ] . ')';
                                            } else if ( $wpcf_field_data_options_value[ 'display' ] === 'value' ) {
                                                $current .= ' (' . $wpcf_field_data_options_value[ 'display_value_selected' ] . ')';
                                            }
                                        }
                                    } else if ( $wpcf_field_type === 'checkbox' ) {
                                        if ( $wpcf_field_data[ 'display' ] === 'db' ) {
                                            $current = $value;
                                        } else {
                                            if ( $value ) {
                                                $current = $wpcf_field_data[ 'display_value_selected' ];
                                            } else {
                                                $current = $wpcf_field_data[ 'display_value_not_selected' ];
                                            }
                                        }
                                    } else if ( $wpcf_field_type === 'image' || $wpcf_field_type === 'file' || $wpcf_field_type === 'audio'
                                        || $wpcf_field_type === 'video' ) {
                                        # use only filename for images and files
                                        $current = ( $i = strrpos( $value, '/' ) ) !== FALSE ? substr( $value, $i + 1 ) : $value;
                                    } else if ( $wpcf_field_type === 'date' ) {
                                        $current = date( Search_Types_Custom_Fields_Widget::DATE_FORMAT, $value );
                                    } else if ( $wpcf_field_type === 'url' ) {
                                        # for URLs chop off http://
                                        if ( substr_compare( $value, 'http://', 0, 7 ) === 0 ) {
                                            $current = substr( $value, 7 );
                                        } else if ( substr_compare( $value, 'https://', 0, 8 ) === 0 ) {
                                            $current = substr( $value, 8 );
                                        } else {
                                            $current = $value;
                                        }
                                        # and provide line break hints
                                        $current = str_replace( '/', '/&#8203;', $current );
                                    } else if ( $wpcf_field_type === 'numeric' ) {
                                        $class = ' scpbcfw-result-table-detail-numeric';
                                        $current = $value;
                                    } else {
                                        $current = $value;
                                    }
                                    # if it is a link then embed in an <a> html element
                                    if ( !empty( $url ) ) {
                                        $current = "<a href=\"$url\">$current</a>";
                                    }
                                    unset( $url, $current );
                                }
                                $labels[ ] = implode( ', ', $label );
                                unset( $value, $values, $label );
                            }
                            $labels = implode( ', ', $labels );
                            $td = "<td class=\"scpbcfw-result-table-detail-{$field}{$class}\">$labels</td>";
                        }   # if ( array_key_exists( $post, $field_values[$field] ) && ( $field_values = $field_values[$field][$post] ) ) {
                    }
                    $content .= $td;
                }
                $content .= '</tr>';
            }
            $content .= '</tbody></table></div></div>';
            echo $content;
            get_footer( );
            exit( );
        } );
    }   # if ( isset( $_REQUEST['search_types_custom_fields_show_using_macro'] )
}   # } else {   # if ( is_admin() ) {

?>