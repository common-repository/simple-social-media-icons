<?php
/**
 * Plugin Name: Simple Social Media Icons
 * Plugin URI: https://github.com/MIGHTYminnow/simple-social-media-icons
 * Description: Adds a widget and shortcode that displays social media icon links using FontAwesome icons.
 * Version: 1.1.3
 * Author: MIGHTYminnow & Braad Martin
 * Author URI: https://mightyminnow.org
 * Text Domain: simple-social-media-icons
 * License: GPL2
 */

add_action( 'plugins_loaded', 'simple_social_media_icons_start' );
/**
 * Initialize the Simple Social Media Icons plugin.
 *
 * @since  1.0.0
 */
function simple_social_media_icons_start() {
    global $simple_social_media_icons;
    $simple_social_media_icons = Simple_Social_Media_Icons_Plugin::get_instance();
}

/**
 * Simple Social Media Icons plugin class
 *
 * @since  1.0.0
 */
class Simple_Social_Media_Icons_Plugin {

    /**
     * Plugin slug.
     *
     * @since  1.0.0
     *
     * @var    string
     */
    const SLUG = 'simple-social-media-icons';

    /**
     * Plugin display name.
     *
     * @since  1.0.0
     *
     * @var    string
     */
    private $plugin_display_name;

    /**
     * Plugin option name.
     *
     * @since  1.0.0
     *
     * @var    string
     */
    protected $option_name;

    /**
     * Plugin options.
     *
     * @since  1.0.0
     *
     * @var    string
     */
    protected $options;

    /**
     * Default options.
     *
     * Used for setting uninitialized plugin options.
     *
     * @since  1.0.0
     *
     * @var    array
     */
    protected $option_defaults = array(
        'icon_style'                => '1',
        'include_facebook_icon'     => true,
        'include_twitter_icon'      => true,
        'include_pinterest_icon'    => true,
        'include_instagram_icon'    => true,
        'include_google_plus_icon'  => false,
        'include_youtube_icon'      => true,
        'include_linkedin_icon'     => false,
        'include_flickr_icon'       => false,
        'include_github_icon'       => false,
        'include_codepen_icon'      => false,
        'include_wordpress_icon'    => false,
        'include_medium_icon'       => false,
    );

    /**
     * Instance of this class.
     *
     * @since  1.0.0
     *
     * @var    object
     */
    protected static $instance = false;

    /**
     * Returns the instance of this class, and initializes the instance if it
     * doesn't already exist.
     *
     * @since   1.0.0
     *
     * @return  Simple_Social_Media_Icons  The SSMI object.
     */
    public static function get_instance() {
        if ( ! self::$instance ) {
	      self::$instance = new self();
	    }
	    return self::$instance;
    }

    /**
     * Simple Social Media Icons Plugin constructor.
     *
     * @since  1.0.0
     */
    private function __construct() {

        // Perform plugin initialization actions.
        $this->initialize();

        // Load the plugin text domain.
        add_action( 'init', array( $this, 'load_text_domain' ) );

        // Set up the admin settings page.
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'add_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_page_enqueue' ) );

        // Register the CSS files for the shortcode (they will get enqueued later if the widget or shortcode is being used)
        add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts') );

        // Register the widget.
        add_action( 'widgets_init', array( $this, 'register_widget' ) );

        // Register the shortcode
        add_shortcode( 'ssmi', array( $this, 'ssmi_shortcode' ) );

        // Integrate with Visual Composer
        add_action( 'vc_before_init', array( $this, 'ssmi_visual_composer' ) );
    }

    /**
	 * Register the Simple Social Media Icons CSS, will be enqueued later
	 *
	 * @since   1.0.0
	 */
    public function register_scripts() {

		wp_register_style( 'ssmi-fontawesome-css', '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css' );
		wp_register_style( 'ssmi-css', plugin_dir_url( __FILE__ ) . '/css/simple-social-media-icons.css' );
    }

    /**
     * Register the widget.
     *
     * @since  1.0.0
     */
    public function register_widget() {

        register_widget( 'Simple_Social_Media_Icons' );
    }

    /**
     * Do necessary initialization actions.
     *
     * @since  1.0.0
     */
    private function initialize() {

        // Set display name.
        $this->plugin_display_name = __( 'Simple Social Media Icons', 'simple-social-media-icons' );

        // Set options name.
        $this->option_name = 'simple_social_media_icons_options';

        // Get plugin options, and populate defaults as needed.
        $this->initialize_options( $this->option_name );
    }

    /**
     * Get plugin options, or initialize with default values.
     *
     * @since   1.0.0
     *
     * @param   string  The unique slug for the array containing the global plugin options
     * @return  array   The array of global options for the plugin
     */
    private function initialize_options( $option_name ) {

        $this->options = get_option( $option_name );

        // Initialize the plugin options with defaults if they're not set.
        if ( empty( $this->options ) ) {
            update_option( $option_name, $this->option_defaults );
        }
    }

    /**
     * This is not currently hooked up. Holding out for new easy translations via translate.wordpress.org
     *
     * Load plugin text domain.
     *
     * @since  1.0.0
     */
    public function load_text_domain() {
        load_plugin_textdomain( self::SLUG, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    /**
     * Create the plugin settings page.
     */
    public function add_settings_page() {

        add_options_page(
            $this->plugin_display_name,
            $this->plugin_display_name,
            'manage_options',
            self::SLUG,
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Output the plugin settings page contents.
     *
     * @since  1.0.0
     */
    public function create_admin_page() {
    ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2><?php echo $this->plugin_display_name; ?></h2>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'ssmi_option_group' );
                echo $this->get_icon_styles_demo();
                do_settings_sections( self::SLUG );
                submit_button();
                echo $this->get_usage_text();
            ?>
            </form>
        </div>
    <?php
    }

    /**
     * Enqueue the CSS for the settings page.
     *
     * @since  1.0.0
     *
     * @param  string  $hook  The slug representing the current admin page
     */
    public function admin_page_enqueue( $hook ) {
    	if ( 'settings_page_simple-social-media-icons' === $hook ) {
            wp_enqueue_style( 'ssmi-fontawesome-css', '//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css' );
            wp_enqueue_style( 'ssmi-admin-css', plugin_dir_url( __FILE__ ) . 'css/simple-social-media-icons.css' );
        }
    }

    /**
     * Populate the settings page with specific settings.
     *
     * @since  1.0.0
     */
    public function add_settings() {

        register_setting(
            'ssmi_option_group', // Option group
            $this->option_name, // Option name
            array( $this, 'sanitize' ) // Sanitize
        );
        add_settings_section(
            'ssmi_settings_section_primary', // ID
            __( 'Settings', 'simple-social-media-icons' ), // Title
            null, // Callback
            self::SLUG // Page
        );
        add_settings_field(
            'icon_style', // ID
            __( 'Default Icon Style', 'simple-social-media-icons' ), // Title
            array( $this, 'icon_style_callback' ), // Callback
            self::SLUG, // Page
            'ssmi_settings_section_primary', // Section
            array(
            	'id'			=> 'icon_style',
            	'description'	=> __( 'Select the style of the icons', 'simple-social-media-icons' ),
            	'icon_style'    => array( '1', '2', '3', '4' ),
            )
        );
        add_settings_field(
            'include_facebook_icon',
            __( 'Include Facebook Icon', 'simple-social-media-icons' ),
            array( $this, 'checkbox_callback' ),
            self::SLUG,
            'ssmi_settings_section_primary',
            array(
                'id' => 'include_facebook_icon',
                'description' => '',
            )
        );
        add_settings_field(
            'include_twitter_icon',
            __( 'Include Twitter Icon', 'simple-social-media-icons' ),
            array( $this, 'checkbox_callback' ),
            self::SLUG,
            'ssmi_settings_section_primary',
            array(
                'id' => 'include_twitter_icon',
                'description' => '',
            )
        );
        add_settings_field(
            'include_pinterest_icon',
            __( 'Include Pinterest Icon', 'simple-social-media-icons' ),
            array( $this, 'checkbox_callback' ),
            self::SLUG,
            'ssmi_settings_section_primary',
            array(
                'id' => 'include_pinterest_icon',
                'description' => '',
            )
        );
        add_settings_field(
            'include_instagram_icon',
            __( 'Include Instagram Icon', 'simple-social-media-icons' ),
            array( $this, 'checkbox_callback' ),
            self::SLUG,
            'ssmi_settings_section_primary',
            array(
                'id' => 'include_instagram_icon',
                'description' => '',
            )
        );
        add_settings_field(
            'include_google_plus_icon',
            __( 'Include Google Plus Icon', 'simple-social-media-icons' ),
            array( $this, 'checkbox_callback' ),
            self::SLUG,
            'ssmi_settings_section_primary',
            array(
                'id' => 'include_google_plus_icon',
                'description' => '',
            )
        );
        add_settings_field(
            'include_youtube_icon',
            __( 'Include Youtube Icon', 'simple-social-media-icons' ),
            array( $this, 'checkbox_callback' ),
            self::SLUG,
            'ssmi_settings_section_primary',
            array(
                'id' => 'include_youtube_icon',
                'description' => '',
            )
        );
        add_settings_field(
            'include_linkedin_icon',
            __( 'Include LinkedIn Icon', 'simple-social-media-icons' ),
            array( $this, 'checkbox_callback' ),
            self::SLUG,
            'ssmi_settings_section_primary',
            array(
                'id' => 'include_linkedin_icon',
                'description' => '',
            )
        );
        add_settings_field(
            'include_flickr_icon',
            __( 'Include Flickr Icon', 'simple-social-media-icons' ),
            array( $this, 'checkbox_callback' ),
            self::SLUG,
            'ssmi_settings_section_primary',
            array(
                'id' => 'include_flickr_icon',
                'description' => '',
            )
        );
        add_settings_field(
            'include_github_icon',
            __( 'Include Github Icon', 'simple-social-media-icons' ),
            array( $this, 'checkbox_callback' ),
            self::SLUG,
            'ssmi_settings_section_primary',
            array(
                'id' => 'include_github_icon',
                'description' => '',
            )
        );
        add_settings_field(
            'include_codenpen_icon',
            __( 'Include Codepen Icon', 'simple-social-media-icons' ),
            array( $this, 'checkbox_callback' ),
            self::SLUG,
            'ssmi_settings_section_primary',
            array(
                'id' => 'include_codepen_icon',
                'description' => '',
            )
        );
        add_settings_field(
            'include_wordpress_icon',
            __( 'Include WordPress Icon', 'simple-social-media-icons' ),
            array( $this, 'checkbox_callback' ),
            self::SLUG,
            'ssmi_settings_section_primary',
            array(
                'id' => 'include_wordpress_icon',
                'description' => '',
            )
        );
        add_settings_field(
            'include_medium_icon',
            __( 'Include Medium Icon', 'simple-social-media-icons' ),
            array( $this, 'checkbox_callback' ),
            self::SLUG,
            'ssmi_settings_section_primary',
            array(
                'id' => 'include_medium_icon',
                'description' => '',
            )
        );
    }

	/**
     * Output a <select> for the icon style.
     *
     * @since  1.0.0
     *
     * @param  array  $sizes  All of the available image sizes
     */
    public function icon_style_callback( $args ) {

    	$option_name = esc_attr( $this->option_name ) . '[' . $args['id'] . ']';

    	$icon_styles = $args['icon_style'];

        // Output the <select> element.
        printf( '<select id="icon_style" name="%s">', $option_name );

        foreach ( $icon_styles as $icon_style ) {

        	printf(
                '<option value="%s" %s>%s</option>',
                esc_attr( $icon_style ),
                selected( $icon_style, $this->options['icon_style'], false ),
                $icon_style
            );
        }

        echo '</select>';

        echo '<p class="description">' . $args['description'] . '</p>';

    }

    /**
     * Output a checkbox setting.
     *
     * @since  0.10.0
     */
    public function checkbox_callback( $args ) {
        $option_name = esc_attr( $this->option_name ) . '[' . $args['id'] . ']';
        $option_value = isset( $this->options[ $args['id'] ] ) ? $this->options[ $args['id'] ] : '';
        printf(
            '<label for="%s"><input type="checkbox" value="1" id="%s" name="%s" %s/> %s</label>',
            $args['id'],
            $args['id'],
            $option_name,
            checked( 1, $option_value, false ),
            $args['description']
        );
    }

    /**
     * Generate the icon style demos
     *
     * @todo    Make this a loop to keep it DRY
     *
     * @since   1.0.0
     *
     * @return  string  $output  The HTML for the icon style demos
     */
    public function get_icon_styles_demo() {

    	/* This html is formatted a little funky because we're fighting against the inline-block spacing that certain browsers add. See http://css-tricks.com/fighting-the-space-between-inline-block-elements/ */

        $output = '<table class="form-table ssmi-style-demo-table">
        			<tbody>
        				<tr>
        					<th scope="row">' . __( 'Icon Styles', 'simple-social-media-icons' ) . '</th>
        					<td>' .
			                '<p><strong>' . __( 'Style 1', 'simple-social-media-icons' ) . '</strong></p>' .
			                '<p class="ssmi-icon-row icon-style-1">
			                    <a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-facebook fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-twitter fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-pinterest fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-instagram fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-google-plus fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-youtube fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-linkedin fa-fw ssmi-icon"></i>
			                    </a
                                ><a href="" onclick="return false" class="ssmi-icon-link">
                                    <i class="fa fa-flickr fa-fw ssmi-icon"></i>
                                </a
                                ><a href="" onclick="return false" class="ssmi-icon-link">
                                    <i class="fa fa-github fa-fw ssmi-icon"></i>
                                </a
                                ><a href="" onclick="return false" class="ssmi-icon-link">
                                    <i class="fa fa-codepen fa-fw ssmi-icon"></i>
                                </a
                                ><a href="" onclick="return false" class="ssmi-icon-link">
                                    <i class="fa fa-wordpress fa-fw ssmi-icon"></i>
                                </a
                                ><a href="" onclick="return false" class="ssmi-icon-link">
                                    <i class="fa fa-medium fa-fw ssmi-icon"></i>
                                </a>
			                </p><br>' .
			                '<p><strong>' . __( 'Style 2', 'simple-social-media-icons' ) . '</strong></p>' .
			                '<p class="ssmi-icon-row icon-style-2">
			                    <a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-facebook fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-twitter fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-pinterest fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-instagram fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-google-plus fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-youtube fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-linkedin fa-fw ssmi-icon"></i>
			                    </a
                                ><a href="" onclick="return false" class="ssmi-icon-link">
                                    <i class="fa fa-flickr fa-fw ssmi-icon"></i>
                                </a
                                ><a href="" onclick="return false" class="ssmi-icon-link">
                                    <i class="fa fa-github fa-fw ssmi-icon"></i>
                                </a
                                ><a href="" onclick="return false" class="ssmi-icon-link">
                                    <i class="fa fa-codepen fa-fw ssmi-icon"></i>
                                </a
                                ><a href="" onclick="return false" class="ssmi-icon-link">
                                    <i class="fa fa-wordpress fa-fw ssmi-icon"></i>
                                </a
                                ><a href="" onclick="return false" class="ssmi-icon-link">
                                    <i class="fa fa-medium fa-fw ssmi-icon"></i>
                                </a>
			                </p><br>' .
			                '<p><strong>' . __( 'Style 3', 'simple-social-media-icons' ) . '</strong></p>' .
			                '<p class="ssmi-icon-row icon-style-3">
			                    <a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-facebook fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-twitter fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-pinterest fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-instagram fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-google-plus fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-youtube fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-linkedin fa-fw ssmi-icon"></i>
			                    </a
                                ><a href="" onclick="return false" class="ssmi-icon-link">
                                    <i class="fa fa-flickr fa-fw ssmi-icon"></i>
                                </a
                                ><a href="" onclick="return false" class="ssmi-icon-link">
                                    <i class="fa fa-github fa-fw ssmi-icon"></i>
                                </a
                                ><a href="" onclick="return false" class="ssmi-icon-link">
                                    <i class="fa fa-codepen fa-fw ssmi-icon"></i>
                                </a
                                ><a href="" onclick="return false" class="ssmi-icon-link">
                                    <i class="fa fa-wordpress fa-fw ssmi-icon"></i>
                                </a
                                ><a href="" onclick="return false" class="ssmi-icon-link">
                                    <i class="fa fa-medium fa-fw ssmi-icon"></i>
                                </a>
			                </p><br>' .
			                '<p><strong>' . __( 'Style 4', 'simple-social-media-icons' ) . '</strong></p>' .
			                '<p class="ssmi-icon-row icon-style-4">
			                    <a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-facebook fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-twitter fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-pinterest fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-instagram fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-google-plus fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-youtube fa-fw ssmi-icon"></i>
			                    </a
			                    ><a href="" onclick="return false" class="ssmi-icon-link">
			                        <i class="fa fa-linkedin fa-fw ssmi-icon"></i>
			                    </a
                                ><a href="" onclick="return false" class="ssmi-icon-link">
                                    <i class="fa fa-flickr fa-fw ssmi-icon"></i>
                                </a
                                ><a href="" onclick="return false" class="ssmi-icon-link">
                                    <i class="fa fa-github fa-fw ssmi-icon"></i>
                                </a
                                ><a href="" onclick="return false" class="ssmi-icon-link">
                                    <i class="fa fa-codepen fa-fw ssmi-icon"></i>
                                </a
                                ><a href="" onclick="return false" class="ssmi-icon-link">
                                    <i class="fa fa-wordpress fa-fw ssmi-icon"></i>
                                </a
                                ><a href="" onclick="return false" class="ssmi-icon-link">
                                    <i class="fa fa-medium fa-fw ssmi-icon"></i>
                                </a>
			                </p>' .
                			'</td>
                		</tr>
                	</tbody>
                </table>';

        return $output;
    }

    /**
     * Generate the admin instructions/usage text.
     *
	 * @todo	Need to find a way to make this translatable.
	 *
     * @since   1.0.0
     *
     * @return  string  Usage text
     */
    public function get_usage_text() {
    	$widgets_url = admin_url( 'widgets.php' );
    	$customizer_url = admin_url( 'customize.php' );
    	$usage_text = '<h3>Widget Usage</h3>
                	   <p>Head to the <a href="' . $widgets_url . '">Widgets Page</a> or the <a href="' . $customizer_url . '">Customizer</a> and add the "Simple Social Media Icons" widget to one of your theme\'s widget areas.</p>
                	   <p>Paste in the links to the social media profile pages you want to include.</p>
                	   <p>The widget will show on the front end as a row of icon links in the selected style.</p>
                	   <h3>Shortcode Usage</h3>
                	   <p>The shortcode works just like the widget and provides you with the same options:</p>
                	   <p><strong>[ssmi facebook_link="" twitter_link="" pinterest_link="" instagram_link="" google_plus_link="" youtube_link="" linkedin_link="" flickr_link="" github_link="" codepen_link="" wordpress_link="" medium_link="" icon_style=""]</strong></p>
                	   <p>Simply fill in the link for each icon to make it appear, like so:</p>
                	   <p><strong>[ssmi facebook_link="https://www.facebook.com/Google" twitter_link="https://twitter.com/google" icon_style="4"]</strong></p>
                	   <p>The icon_style option accepts the number of any of the icon styles, from 1 to 4.</p>';

        return '<div class="ssmi-usage-text">' . __( $usage_text, 'simple-social-media-icons' ) . '</div>';
    }

    /**
     * Sanitize each settings field as needed.
     *
     * @since   1.0.0
     *
     * @param   array  $input  Contains all settings fields as array keys
     * @return  array  $new_input  Contains all the sanitized settings
     */
    public function sanitize( $options ) {

        // Currently no need to sanitize because all options are either dropdowns or checkboxes
        $sanitized_options = $options;

        return $sanitized_options;
    }

    /**
     * [ssmi] shortcode
     *
     * Also used to render the widget
     *
     * @since   1.0.0
     *
     * @param   array  $atts  Attributes passed to the shortcode (possibly from the widget)
     * @return  string  $output  HTML for the icon row
     */
    public function ssmi_shortcode( $atts ) {
        $a = shortcode_atts( array(
            'facebook_link'     => '',
            'twitter_link'      => '',
            'pinterest_link'    => '',
            'instagram_link'    => '',
            'google_plus_link'  => '',
            'youtube_link'      => '',
            'linkedin_link'     => '',
            'flickr_link'       => '',
            'github_link'       => '',
            'codepen_link'      => '',
            'wordpress_link'    => '',
            'medium_link'       => '',
            'icon_style'        => 'default',
            'is_widget'         => 'false',
        ), $atts );

        // Enqueue the JS and CSS
        wp_enqueue_style( 'ssmi-fontawesome-css' );
        wp_enqueue_style( 'ssmi-css' );

        // Set up variables to match the widget
        $facebook_link              = $a['facebook_link'];
        $twitter_link               = $a['twitter_link'];
        $pinterest_link             = $a['pinterest_link'];
        $instagram_link             = $a['instagram_link'];
        $google_plus_link           = $a['google_plus_link'];
        $youtube_link               = $a['youtube_link'];
        $linkedin_link              = $a['linkedin_link'];
        $flickr_link                = $a['flickr_link'];
        $github_link                = $a['github_link'];
        $codepen_link               = $a['codepen_link'];
        $wordpress_link             = $a['wordpress_link'];
        $medium_link                = $a['medium_link'];
        $icon_style                 = ( $a['icon_style'] == 'default' ) ? $this->options['icon_style'] : $a['icon_style'];
        $is_widget                  = $a['is_widget'];

        $output = '<div class="simple-social-media-icons ';

        // Add a class to the wrapper to indicate whether we're rendering the widget or the shortcode
        $output .= ( $is_widget === 'true' ) ? 'ssmi-widget' : 'ssmi-shortcode' ;

        $output .= '">';

        // Add a class to the icon row wrapper to indicate the icon style
        $output .= '<div class="ssmi-icon-row icon-style-' . $icon_style . '">';

        if ( ! $facebook_link == '' && isset( $this->options['include_facebook_icon'] ) ) {
            $output .= '<a href="' . esc_url( $facebook_link ) . '" class="ssmi-icon-link" target="_blank">' .
                '<i class="fa fa-facebook fa-fw ssmi-icon"></i>' .
				'<span class="screen-reader-text">Facebook</span>' .
            '</a>';
        }
        if ( ! $twitter_link == '' && isset( $this->options['include_twitter_icon'] ) ) {
            $output .= '<a href="' . esc_url( $twitter_link ) . '" class="ssmi-icon-link" target="_blank">' .
                '<i class="fa fa-twitter fa-fw ssmi-icon"></i>' .
				'<span class="screen-reader-text">Twitter</span>' .
            '</a>';
        }
        if ( ! $pinterest_link == '' && isset( $this->options['include_pinterest_icon'] ) ) {
            $output .= '<a href="' . esc_url( $pinterest_link ) . '" class="ssmi-icon-link" target="_blank">' .
                '<i class="fa fa-pinterest fa-fw ssmi-icon"></i>' .
				'<span class="screen-reader-text">Pinterest</span>' .
            '</a>';
        }
        if ( ! $instagram_link == '' && isset( $this->options['include_instagram_icon'] ) ) {
            $output .= '<a href="' . esc_url( $instagram_link ) . '" class="ssmi-icon-link" target="_blank">' .
                '<i class="fa fa-instagram fa-fw ssmi-icon"></i>' .
				'<span class="screen-reader-text">Instagram</span>' .
            '</a>';
        }
        if ( ! $google_plus_link == '' && isset( $this->options['include_google_plus_icon'] ) ) {
            $output .= '<a href="' . esc_url( $google_plus_link ) . '" class="ssmi-icon-link" target="_blank">' .
                '<i class="fa fa-google-plus fa-fw ssmi-icon"></i>' .
				'<span class="screen-reader-text">Google Plus</span>' .
            '</a>';
        }
        if ( ! $youtube_link == '' && isset( $this->options['include_youtube_icon'] ) ) {
            $output .= '<a href="' . esc_url( $youtube_link ) . '" class="ssmi-icon-link" target="_blank">' .
                '<i class="fa fa-youtube fa-fw ssmi-icon"></i>' .
				'<span class="screen-reader-text">Youtube</span>' .
            '</a>';
        }
        if ( ! $linkedin_link == '' && isset( $this->options['include_linkedin_icon'] ) ) {
            $output .= '<a href="'  . esc_url( $linkedin_link ) . '" class="ssmi-icon-link" target="_blank">' .
                '<i class="fa fa-linkedin fa-fw ssmi-icon"></i>' .
				'<span class="screen-reader-text">LinkedIn</span>' .
            '</a>';
        }
        if ( ! $flickr_link == '' && isset( $this->options['include_flickr_icon'] ) ) {
            $output .= '<a href="'  . esc_url( $flickr_link ) . '" class="ssmi-icon-link" target="_blank">' .
                '<i class="fa fa-flickr fa-fw ssmi-icon"></i>' .
				'<span class="screen-reader-text">Flickr</span>' .
            '</a>';
        }
        if ( ! $github_link == '' && isset( $this->options['include_github_icon'] ) ) {
            $output .= '<a href="'  . esc_url( $linkedin_link ) . '" class="ssmi-icon-link" target="_blank">' .
                '<i class="fa fa-github fa-fw ssmi-icon"></i>' .
				'<span class="screen-reader-text">GitHub</span>' .
            '</a>';
        }
        if ( ! $codepen_link == '' && isset( $this->options['include_codepen_icon'] ) ) {
            $output .= '<a href="'  . esc_url( $codepen_link ) . '" class="ssmi-icon-link" target="_blank">' .
                '<i class="fa fa-codepen fa-fw ssmi-icon"></i>' .
				'<span class="screen-reader-text">CodePen</span>' .
            '</a>';
        }
        if ( ! $wordpress_link == '' && isset( $this->options['include_wordpress_icon'] ) ) {
            $output .= '<a href="'  . esc_url( $wordpress_link ) . '" class="ssmi-icon-link" target="_blank">' .
                '<i class="fa fa-wordpress fa-fw ssmi-icon"></i>' .
				'<span class="screen-reader-text">WordPress</span>' .
            '</a>';
        }
        if ( ! $medium_link == '' && isset( $this->options['include_medium_icon'] ) ) {
            $output .= '<a href="'  . esc_url( $medium_link ) . '" class="ssmi-icon-link" target="_blank">' .
                '<i class="fa fa-medium fa-fw ssmi-icon"></i>' .
				'<span class="screen-reader-text">Medium</span>' .
            '</a>';
        }

        $output .= '</div></div>';

        return $output;
    }

    /**
     * Visual Composer Integration
     *
     * @since  1.0.0
     */
    public function ssmi_visual_composer() {

        $vc_params = array();

        // Check which icons should be included
        if ( isset( $this->options['include_facebook_icon'] ) ) {
            array_push( $vc_params, array(
                'type' => 'textfield',
                'class' => '',
                'heading' => __( 'Facebook Link', 'simple-social-media-icons' ),
                'param_name' => 'facebook_link',
                'value' => '',
                'description' => __( 'URL to your Facebook page', 'simple-social-media-icons' )
                )
            );
        }
        if ( isset( $this->options['include_twitter_icon'] ) ) {
            array_push( $vc_params, array(
                'type' => 'textfield',
                'class' => '',
                'heading' => __( 'Twitter Link', 'simple-social-media-icons' ),
                'param_name' => 'twitter_link',
                'value' => '',
                'description' => __( 'URL to your Twitter page', 'simple-social-media-icons' )
                )
            );
        }
        if ( isset( $this->options['include_pinterest_icon'] ) ) {
            array_push( $vc_params, array(
                'type' => 'textfield',
                'class' => '',
                'heading' => __( 'Pinterest Link', 'simple-social-media-icons' ),
                'param_name' => 'pinterest_link',
                'value' => '',
                'description' => __( 'URL to your Pinterest page', 'simple-social-media-icons' )
                )
            );
        }
        if ( isset( $this->options['include_instagram_icon'] ) ) {
            array_push( $vc_params, array(
                'type' => 'textfield',
                'class' => '',
                'heading' => __( 'Instagram Link', 'simple-social-media-icons' ),
                'param_name' => 'instagram_link',
                'value' => '',
                'description' => __( 'URL to your Instagram page', 'simple-social-media-icons' )
                )
            );
        }
        if ( isset( $this->options['include_google_plus_icon'] ) ) {
            array_push( $vc_params, array(
                'type' => 'textfield',
                'class' => '',
                'heading' => __( 'Google Plus Link', 'simple-social-media-icons' ),
                'param_name' => 'google_plus_link',
                'value' => '',
                'description' => __( 'URL to your Google Plus page', 'simple-social-media-icons' )
                )
            );
        }
        if ( isset( $this->options['include_youtube_icon'] ) ) {
            array_push( $vc_params, array(
                'type' => 'textfield',
                'class' => '',
                'heading' => __( 'Youtube Link', 'simple-social-media-icons' ),
                'param_name' => 'youtube_link',
                'value' => '',
                'description' => __( 'URL to your Youtube page', 'simple-social-media-icons' )
                )
            );
        }
        if ( isset( $this->options['include_linkedin_icon'] ) ) {
            array_push( $vc_params, array(
                'type' => 'textfield',
                'class' => '',
                'heading' => __( 'LinkedIn Link', 'simple-social-media-icons' ),
                'param_name' => 'linkedin_link',
                'value' => '',
                'description' => __( 'URL to your LinkedIn page', 'simple-social-media-icons' )
                )
            );
        }
        if ( isset( $this->options['include_flickr_icon'] ) ) {
            array_push( $vc_params, array(
                'type' => 'textfield',
                'class' => '',
                'heading' => __( 'Flickr Link', 'simple-social-media-icons' ),
                'param_name' => 'flickr_link',
                'value' => '',
                'description' => __( 'URL to your Flickr page', 'simple-social-media-icons' )
                )
            );
        }
        if ( isset( $this->options['include_github_icon'] ) ) {
            array_push( $vc_params, array(
                'type' => 'textfield',
                'class' => '',
                'heading' => __( 'Github Link', 'simple-social-media-icons' ),
                'param_name' => 'github_link',
                'value' => '',
                'description' => __( 'URL to your Github page', 'simple-social-media-icons' )
                )
            );
        }
        if ( isset( $this->options['include_codepen_icon'] ) ) {
            array_push( $vc_params, array(
                'type' => 'textfield',
                'class' => '',
                'heading' => __( 'Codepen Link', 'simple-social-media-icons' ),
                'param_name' => 'codepen_link',
                'value' => '',
                'description' => __( 'URL to your Codepen page', 'simple-social-media-icons' )
                )
            );
        }
        if ( isset( $this->options['include_wordpress_icon'] ) ) {
            array_push( $vc_params, array(
                'type' => 'textfield',
                'class' => '',
                'heading' => __( 'WordPress Link', 'simple-social-media-icons' ),
                'param_name' => 'wordpress_link',
                'value' => '',
                'description' => __( 'URL to your WordPress page', 'simple-social-media-icons' )
                )
            );
        }
        if ( isset( $this->options['include_medium_icon'] ) ) {
            array_push( $vc_params, array(
                'type' => 'textfield',
                'class' => '',
                'heading' => __( 'Medium Link', 'simple-social-media-icons' ),
                'param_name' => 'medium_link',
                'value' => '',
                'description' => __( 'URL to your Medium page', 'simple-social-media-icons' )
                )
            );
        }
        array_push( $vc_params, array(
            'type' => 'dropdown',
            'class' => '',
            'heading' => __( 'Icon Style', 'simple-social-media-icons' ),
            'param_name' => 'icon_style',
            'description' => __( 'View the ', 'simple-social-media-icons' ) . '<a href="' . admin_url( 'options-general.php?page=simple-social-media-icons' ) . '" target="_blank">plugin options page</a>' . __( ' to preview styles', 'simple-social-media-icons' ),
            'admin_label' => true,
            'value' => array(
                '0' => __( 'default', 'simple-social-media-icons' ),
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4'
            )
        ));

        if ( function_exists( 'vc_map' ) ) {
            vc_map( array(
                'name' => __( 'Simple Social Media Icons', 'simple-social-media-icons' ),
                'base' => 'ssmi',
                'class' => 'ssmi-shortcode',
                'category' => 'Social',
                'description' => __( 'A row of social media icons', 'simple-social-media-icons' ),
                'params' => $vc_params,
            ));
        }
    }

}

/**
 * Simple Social Media Icons Widget Class.
 *
 * @since   1.0.0
 */
class Simple_Social_Media_Icons extends WP_Widget {

    /**
	 * Initialize an instance of the widget.
	 *
	 * @since   1.0.0
	 */
    public function __construct() {

    	$this->plugin_url = plugins_url( '', __FILE__ );

        // Options to pass to the widget
        $this->widget_options = array(
            'classname'   => 'simple-social-media-icons',
            'description' => __( 'A row of social media icon links', 'simple-social-media-icons' ),
        );

        $this->plugin_options = get_option( 'simple_social_media_icons_options' );

        // Build the widget
    	parent::__construct(
    		'simple_social_media_icons_widget',
    		__( 'Simple Social Media Icons', 'simple-social-media-icons' ),
    		$this->widget_options
    	);
    }

    /**
	 * Build the widget settings form.
	 *
	 * @since   1.0.0
	 *
	 * @param   array  $instance  The widget options
	 */
    public function form( $instance ) {

        $defaults           = array(
        						'title' 			=> '',
        						'facebook_link' 	=> '',
        						'twitter_link' 		=> '',
        						'pinterest_link' 	=> '',
        						'instagram_link' 	=> '',
        						'google_plus_link' 	=> '',
        						'youtube_link' 		=> '',
        						'linkedin_link' 	=> '',
                                'flickr_link'       => '',
                                'github_link'       => '',
                                'codepen_link'      => '',
                                'wordpress_link'    => '',
                                'medium_link'       => '',
        						'icon_style' 		=> 'default'
        					);
        $instance           = wp_parse_args( $instance, $defaults );
        $title              = $instance['title'];
        $facebook_link      = $instance['facebook_link'];
        $twitter_link       = $instance['twitter_link'];
        $pinterest_link     = $instance['pinterest_link'];
        $instagram_link 	= $instance['instagram_link'];
        $google_plus_link 	= $instance['google_plus_link'];
        $youtube_link       = $instance['youtube_link'];
        $linkedin_link      = $instance['linkedin_link'];
        $flickr_link        = $instance['flickr_link'];
        $github_link        = $instance['github_link'];
        $codepen_link       = $instance['codepen_link'];
        $wordpress_link     = $instance['wordpress_link'];
        $medium_link        = $instance['medium_link'];
        $icon_style         = $instance['icon_style'];

        ?>
        <p>
            <label for="simple_social_media_icons_title"><?php _e( 'Title', 'simple-social-media-icons' ); ?>:</label>
            <input type="text" class="widefat" id="simple_social_media_icons_title" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <?php
        if ( isset( $this->plugin_options['include_facebook_icon'] ) ) {
        ?>
        <p>
            <label for="simple_social_media_icons_facebook_link"><?php _e( 'Facebook Link', 'simple-social-media-icons' ); ?>:</label>
            <input type="text" class="widefat" id="simple_social_media_icons_facebook_link" name="<?php echo $this->get_field_name( 'facebook_link' ); ?>" value="<?php echo esc_url( $facebook_link ); ?>" />
        </p>
        <?php }
        if ( isset( $this->plugin_options['include_twitter_icon'] ) ) {
        ?>
        <p>
            <label for="simple_social_media_icons_twitter_link"><?php _e( 'Twitter Link', 'simple-social-media-icons' ); ?>:</label>
            <input type="text" class="widefat" id="simple_social_media_icons_twitter_link" name="<?php echo $this->get_field_name( 'twitter_link' ); ?>" value="<?php echo esc_url( $twitter_link ); ?>" />
        </p>
        <?php }
        if ( isset( $this->plugin_options['include_pinterest_icon'] ) ) {
        ?>
        <p>
            <label for="simple_social_media_icons_pinterest_link"><?php _e( 'Pinterest Link', 'simple-social-media-icons' ); ?>:</label>
            <input type="text" class="widefat" id="simple_social_media_icons_pinterest_link" name="<?php echo $this->get_field_name( 'pinterest_link' ); ?>" value="<?php echo esc_url( $pinterest_link ); ?>" />
        </p>
        <?php }
        if ( isset( $this->plugin_options['include_instagram_icon'] ) ) {
        ?>
        <p>
            <label for="simple_social_media_icons_instagram_link"><?php _e( 'Instagram Link', 'simple-social-media-icons' ); ?>:</label>
            <input type="text" class="widefat" id="simple_social_media_icons_instagram_link" name="<?php echo $this->get_field_name( 'instagram_link' ); ?>" value="<?php echo esc_url( $instagram_link ); ?>" />
        </p>
        <?php }
        if ( isset( $this->plugin_options['include_google_plus_icon'] ) ) {
        ?>
        <p>
            <label for="simple_social_media_icons_google_plus_link"><?php _e( 'Google Plus Link', 'simple-social-media-icons' ); ?>:</label>
            <input type="text" class="widefat" id="simple_social_media_icons_google_plus_link" name="<?php echo $this->get_field_name( 'google_plus_link' ); ?>" value="<?php echo esc_url( $google_plus_link ); ?>" />
        </p>
        <?php }
        if ( isset( $this->plugin_options['include_youtube_icon'] ) ) {
        ?>
        <p>
            <label for="simple_social_media_icons_youtube_link"><?php _e( 'Youtube Link', 'simple-social-media-icons' ); ?>:</label>
            <input type="text" class="widefat" id="simple_social_media_icons_youtube_link" name="<?php echo $this->get_field_name( 'youtube_link' ); ?>" value="<?php echo esc_url( $youtube_link ); ?>" />
        </p>
        <?php }
        if ( isset( $this->plugin_options['include_linkedin_icon'] ) ) {
        ?>
        <p>
            <label for="simple_social_media_icons_linkedin_link"><?php _e( 'LinkedIn Link', 'simple-social-media-icons' ); ?>:</label>
            <input type="text" class="widefat" id="simple_social_media_icons_linkedin_link" name="<?php echo $this->get_field_name( 'linkedin_link' ); ?>" value="<?php echo esc_url( $linkedin_link ); ?>" />
        </p>
        <?php }
        if ( isset( $this->plugin_options['include_flickr_icon'] ) ) {
        ?>
        <p>
            <label for="simple_social_media_icons_flickr_link"><?php _e( 'Flickr Link', 'simple-social-media-icons' ); ?>:</label>
            <input type="text" class="widefat" id="simple_social_media_icons_flickr_link" name="<?php echo $this->get_field_name( 'flickr_link' ); ?>" value="<?php echo esc_url( $flickr_link ); ?>" />
        </p>
        <?php }
        if ( isset( $this->plugin_options['include_github_icon'] ) ) {
        ?>
        <p>
            <label for="simple_social_media_icons_github_link"><?php _e( 'Github Link', 'simple-social-media-icons' ); ?>:</label>
            <input type="text" class="widefat" id="simple_social_media_icons_github_link" name="<?php echo $this->get_field_name( 'github_link' ); ?>" value="<?php echo esc_url( $github_link ); ?>" />
        </p>
        <?php }
        if ( isset( $this->plugin_options['include_codepen_icon'] ) ) {
        ?>
        <p>
            <label for="simple_social_media_icons_codepen_link"><?php _e( 'Codepen Link', 'simple-social-media-icons' ); ?>:</label>
            <input type="text" class="widefat" id="simple_social_media_icons_codepen_link" name="<?php echo $this->get_field_name( 'codepen_link' ); ?>" value="<?php echo esc_url( $codepen_link ); ?>" />
        </p>
        <?php }
        if ( isset( $this->plugin_options['include_wordpress_icon'] ) ) {
        ?>
        <p>
            <label for="simple_social_media_icons_wordpress_link"><?php _e( 'WordPress Link', 'simple-social-media-icons' ); ?>:</label>
            <input type="text" class="widefat" id="simple_social_media_icons_wordpress_link" name="<?php echo $this->get_field_name( 'wordpress_link' ); ?>" value="<?php echo esc_url( $wordpress_link ); ?>" />
        </p>
        <?php }
        if ( isset( $this->plugin_options['include_medium_icon'] ) ) {
        ?>
        <p>
            <label for="simple_social_media_icons_medium_link"><?php _e( 'Medium Link', 'simple-social-media-icons' ); ?>:</label>
            <input type="text" class="widefat" id="simple_social_media_icons_medium_link" name="<?php echo $this->get_field_name( 'medium_link' ); ?>" value="<?php echo esc_url( $medium_link ); ?>" />
        </p>
        <?php } ?>
        <p>
            <label for="simple_social_media_icons_icon_style"><?php _e( 'Icon Style', 'simple-social-media-icons' ) ?>:</label>
            <select id="simple_social_media_icons_icon_style" name="<?php echo $this->get_field_name( 'icon_style' ); ?>">
                <option<?php if ( 'default' == $instance['icon_style'] ) echo ' selected="selected"'; ?>>default</option>
                <option<?php if ( '1' == $instance['icon_style'] ) echo ' selected="selected"'; ?>>1</option>
                <option<?php if ( '2' == $instance['icon_style'] ) echo ' selected="selected"'; ?>>2</option>
                <option<?php if ( '3' == $instance['icon_style'] ) echo ' selected="selected"'; ?>>3</option>
                <option<?php if ( '4' == $instance['icon_style'] ) echo ' selected="selected"'; ?>>4</option>
            </select>
        </p>
        <p>Visit the <a href="<?php echo admin_url( 'options-general.php?page=simple-social-media-icons' ) ?>">Settings Page</a> to see the icon styles</p>
        <?php
    }

    /**
	 * Update the widget settings.
	 *
	 * @since   1.0.0
	 *
	 * @param   array  $new_instance  The new option values to be saved
	 * @param   array  $old_instance  The previous option values
	 * @return  array  $instance  The updated option values for the widget instance
	 */
    public function update( $new_instance, $old_instance ) {

        $instance                       = $old_instance;
        $instance['title']              = sanitize_text_field( $new_instance['title'] );
        $instance['facebook_link']      = sanitize_text_field( $new_instance['facebook_link'] );
        $instance['twitter_link']		= sanitize_text_field( $new_instance['twitter_link'] );
        $instance['pinterest_link']     = sanitize_text_field( $new_instance['pinterest_link'] );
        $instance['instagram_link'] 	= sanitize_text_field( $new_instance['instagram_link'] );
        $instance['google_plus_link']   = sanitize_text_field( $new_instance['google_plus_link'] );
        $instance['youtube_link']       = sanitize_text_field( $new_instance['youtube_link'] );
        $instance['linkedin_link']      = sanitize_text_field( $new_instance['linkedin_link'] );
        $instance['flickr_link']        = sanitize_text_field( $new_instance['flickr_link'] );
        $instance['github_link']        = sanitize_text_field( $new_instance['github_link'] );
        $instance['codepen_link']       = sanitize_text_field( $new_instance['codepen_link'] );
        $instance['wordpress_link']     = sanitize_text_field( $new_instance['wordpress_link'] );
        $instance['medium_link']        = sanitize_text_field( $new_instance['medium_link'] );
        $instance['icon_style']         = $new_instance['icon_style'];

        return $instance;
    }

    /**
	 * Display the widget.
     *
	 * @since   1.0.0
	 *
	 * @param   array  $args  The arguments passed into the widget
	 * @param   array  $instance  All of the options for the widget instance
	 */
    public function widget( $args, $instance ) {

        // Handle the issue where newly initialized widgets are being previewed in the customizer but don't yet have a proper $instance
        if ( ! $instance ) {
        	$defaults = array(
        		'title' 			=> '',
        		'facebook_link' 	=> '',
        		'pinterest_link' 	=> '',
        		'instagram_link' 	=> '',
        		'twitter_link' 		=> '',
        		'pinterest_link' 	=> '',
        		'google_plus_link' 	=> '',
        		'youtube_link' 		=> '',
        		'linkedin_link' 	=> '',
                'flickr_link'       => '',
                'github_link'       => '',
                'codepen_link'      => '',
                'wordpress_link'    => '',
                'medium_link'       => '',
        		'icon_style' 		=> 'default',
        		'is_widget' 		=> 'true'
        	);
        	$instance           = wp_parse_args( $instance, $defaults );
        }

        $title              = apply_filters( 'widget_title', $instance['title'] );
        $facebook_link      = ( isset( $instance['facebook_link'] ) ) ? $instance['facebook_link'] : '';
        $twitter_link       = ( isset( $instance['twitter_link'] ) ) ? $instance['twitter_link'] : '';
        $pinterest_link     = ( isset( $instance['pinterest_link'] ) ) ? $instance['pinterest_link'] : '';
        $instagram_link 	= ( isset( $instance['instagram_link'] ) ) ? $instance['instagram_link'] : '';
        $google_plus_link   = ( isset( $instance['google_plus_link'] ) ) ? $instance['google_plus_link'] : '';
        $youtube_link       = ( isset( $instance['youtube_link'] ) ) ? $instance['youtube_link'] : '';
        $linkedin_link      = ( isset( $instance['linkedin_link'] ) ) ? $instance['linkedin_link'] : '';
        $flickr_link        = ( isset( $instance['flickr_link'] ) ) ? $instance['flickr_link'] : '';
        $github_link        = ( isset( $instance['github_link'] ) ) ? $instance['github_link'] : '';
        $codepen_link       = ( isset( $instance['codepen_link'] ) ) ? $instance['codepen_link'] : '';
        $wordpress_link     = ( isset( $instance['wordpress_link'] ) ) ? $instance['wordpress_link'] : '';
        $medium_link        = ( isset( $instance['medium_link'] ) ) ? $instance['medium_link'] : '';
        $icon_style         = $instance['icon_style'];

        $shortcode 			= '[ssmi facebook_link="'
                              . $facebook_link . '" twitter_link="' . $twitter_link . '" pinterest_link="'
                              . $pinterest_link . '" instagram_link="' . $instagram_link . '" google_plus_link="'
                              . $google_plus_link . '" youtube_link="' . $youtube_link . '" linkedin_link="'
                              . $linkedin_link . '" flickr_link="' . $flickr_link . '" github_link="'
                              . $github_link . '" codepen_link="' . $codepen_link . '" wordpress_link="'
                              . $wordpress_link . '" medium_link="' . $medium_link . '" icon_style="'
                              . $icon_style . '" is_widget="true"]';

        echo $args['before_widget'];

        if ( !empty( $title ) ) echo $args['before_title'] . $title . $args['after_title'];

        // Use the shortcode to render the widget
        echo do_shortcode( $shortcode );

        echo $args['after_widget'];
    }
}
