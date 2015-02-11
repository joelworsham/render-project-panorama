<?php
/*
 * Plugin Name: Render Project Panorama
 * Description: Integrates Render with Project Panorama.
 * Version: 1.0.0
 * Author: Joel Worsham
 * Author URI: http://realbigmarketing.com
 * Plugin URI: http://realbigplugins.com/plugins/render-project-panorama/
 * Text Domain: Render_PSP
 * Domain Path: /languages/
 */

// Exit if loaded directly
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

// Define all plugin constants.

/**
 * The version of Render.
 *
 * @since 1.0.0
 */
define( 'RENDER_PSP_VERSION', '1.0.0' );

/**
 * The absolute server path to Render's root directory.
 *
 * @since 1.0.0
 */
define( 'RENDER_PSP_PATH', plugin_dir_path( __FILE__ ) );

/**
 * The URI to Render's root directory.
 *
 * @since 1.0.0
 */
define( 'RENDER_PSP_URL', plugins_url( '', __FILE__ ) );

/**
 * Class Render_PSP
 *
 * Initializes and loads the plugin.
 *
 * @since   1.0.0
 *
 * @package Render_PSP
 */
class Render_PSP {

	/**
	 * The reason for deactivation.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public $deactivate_reasons = array();

	/**
	 * The plugin text domain.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public static $text_domain = 'Render_PSP';

	/**
	 * Constructs the plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, '_init' ) );
	}

	/**
	 * Initializes the plugin.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	public function _init() {

		// Requires Render
		if ( ! defined( 'RENDER_ACTIVE' ) ) {
			$this->deactivate_reasons[] = __( 'Render is not active', self::$text_domain );
		}

		// Requires Project Panorama
		if ( ! defined( 'PROJECT_PANORAMA_STORE_URL' ) ) {
			$this->deactivate_reasons[] = __( 'Project Panorama is not active', self::$text_domain );
		}

		// 1.0.3 is when extension integration was introduced
		if ( defined( 'RENDER_VERSION' ) && version_compare( RENDER_VERSION, '1.0.3', '<' ) ) {
			$this->deactivate_reasons[] = sprintf(
				__( 'This plugin requires at least Render version %s. You have version %s installed.', self::$text_domain ),
				'1.0.3',
				RENDER_VERSION
			);
		}

		// Bail if issues
		if ( ! empty( $this->deactivate_reasons ) ) {
			add_action( 'admin_notices', array( $this, '_notice' ) );

			return;
		}

		// Add the shortcodes to Render
		$this->_add_shortcodes();

		// Translation ready
		load_plugin_textdomain( self::$text_domain, false, RENDER_PSP_PATH . '/languages' );

		// Add dynamic shortcode styles to the frontend
		add_action( 'wp_enqueue_scripts', array( $this, 'dynamic_styles' ) );

		// Licensing
		render_setup_license( 'render_psp', 'Project Panorama', RENDER_VERSION, plugin_dir_path( __FILE__ ) );

		// Disable TinyMCE buttons
		render_disable_tinymce_button( 'currentprojects', 'Project List' );
		render_disable_tinymce_button( 'singleproject', 'Embed a Project' );
	}

	/**
	 * Add data and inputs for all Project Panorama shortcodes and pass them through Render's function.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function _add_shortcodes() {

		foreach (
			array(
				array(
					'code'        => 'project_list',
					'function'    => 'psp_current_projects',
					'title'       => __( 'Project List', self::$text_domain ),
					'description' => __( 'Displays a list of all projects.', self::$text_domain ),
					'atts'        => array(
						// I don't think this is yet finished in Project Panorama
//						'type'   => render_sc_attr_template(
//							'terms_list',
//							array(
//								'label'       => __( 'Type', self::$text_domain ),
//								'placeholder' => __( 'All project types.', self::$text_domain ),
//								'description' => __( 'The type of project to list. Leave blank to list all types.', self::$text_domain ),
//							),
//							array(
//								'taxonomies' => array(
//									'psp_tax',
//								),
//							)
//						),
						'status' => array(
							'label'      => __( 'Status', self::$text_domain ),
							'type'       => 'selectbox',
							'default'    => 'all',
							'properties' => array(
								'options' => array(
									'all'       => __( 'All', self::$text_domain ),
									'active'    => __( 'Active', self::$text_domain ),
									'completed' => __( 'Completed', self::$text_domain ),
								)
							),
						),
						'count'  => array(
							'label'       => __( 'Count', self::$text_domain ),
							'description' => __( 'Number of projects to list (-1 for all projects).', self::$text_domain ),
							'type'        => 'counter',
							'default'     => 10,
							'properties'  => array(
								'min'        => - 1,
								'max'        => 20,
								'shift_step' => 5,
							),
						),
					),
					'render'      => array(
						'displayBlock' => true,
					),
				),
				array(
					'code'        => 'project_status',
					'function'    => 'psp_single_project',
					'title'       => __( 'Project Status', self::$text_domain ),
					'description' => __( 'Embed an entire project into your page or post.', self::$text_domain ),
					'atts'        => array(
						'id'         => render_sc_attr_template( 'post_list', array(
							'label'      => __( 'Project', self::$text_domain ),
							'required'   => true,
							'properties' => array(
								'placeholder' => __( 'Select a project', self::$text_domain ),
							),
						), array(
							'post_type' => 'psp_projects',
						) ),
						'progress'   => array(
							'label'      => __( 'Progress Bar', self::$text_domain ),
							'type'       => 'toggle',
							'properties' => array(
								'deselectStyle' => true,
								'flip'          => true,
								'values'        => array(
									'no'  => __( 'Hide', self::$text_domain ),
									'yes' => __( 'Show', self::$text_domain ),
								),
							),
						),
						'overview'   => array(
							'label'      => __( 'Overview', self::$text_domain ),
							'type'       => 'toggle',
							'properties' => array(
								'deselectStyle' => true,
								'flip'          => true,
								'values'        => array(
									'no'  => __( 'Hide', self::$text_domain ),
									'yes' => __( 'Show', self::$text_domain ),
								),
							),
						),
						'phases'     => array(
							'label'      => __( 'Phases', self::$text_domain ),
							'type'       => 'toggle',
							'properties' => array(
								'deselectStyle' => true,
								'flip'          => true,
								'values'        => array(
									'no'  => __( 'Hide', self::$text_domain ),
									'yes' => __( 'Show', self::$text_domain ),
								),
							),
						),
						'tasks'      => array(
							'label'       => __( 'Tasks', self::$text_domain ),
							'description' => __( 'Phases must be set to "Show"', self::$text_domain ),
							'type'        => 'selectbox',
							'properties'  => array(
								'options' => array(
									'yes'        => __( 'Yes', self::$text_domain ),
									'complete'   => __( 'Complete', self::$text_domain ),
									'incomplete' => __( 'Incomplete', self::$text_domain ),
									'no'         => __( 'No', self::$text_domain ),
								),
							),
						),
						'milestones' => array(
							'label'      => __( 'Milestones', self::$text_domain ),
							'type'       => 'selectbox',
							'properties' => array(
								'options' => array(
									'full'      => __( 'Full', self::$text_domain ),
									'condensed' => __( 'Condensed', self::$text_domain ),
									'no'        => __( 'No', self::$text_domain ),
								),
							),
						),
					),
					'render'      => array(
						'displayBlock' => true,
					),
				),
				array(
					'code'        => 'project_status_part',
					'function'    => 'psp_project_part',
					'title'       => __( 'Project Part', self::$text_domain ),
					'description' => __( 'Embed a portion of a project.', self::$text_domain ),
					'atts'        => array(
						'id'      => render_sc_attr_template( 'post_list', array(
							'label'      => __( 'Project', self::$text_domain ),
							'required'   => true,
							'properties' => array(
								'placeholder' => __( 'Select a project', self::$text_domain ),
							),
						), array(
							'post_type' => 'psp_projects',
						) ),
						'display' => array(
							'label'       => __( 'Display', self::$text_domain ),
							'description' => __( 'What portion of the project you would like to display', self::$text_domain ),
							'required'    => true,
							'type'        => 'selectbox',
							'properties'  => array(
								'options' => array(
									'documents' => __( 'Documents', self::$text_domain ),
									'overview'  => __( 'Overview', self::$text_domain ),
									'progress'  => __( 'Progress', self::$text_domain ),
									'phases'    => __( 'Phases', self::$text_domain ),
									'tasks'     => __( 'Tasks', self::$text_domain ),
								),
							),
						),
						'style'   => array(
							'label'       => __( 'Style', self::$text_domain ),
							'description' => __( 'This is optional, and dependent on which Display is set', self::$text_domain ),
							'type'        => 'selectbox',
							'properties'  => array(
								'groups' => array(
									array(
										'label'   => __( 'Progress', self::$text_domain ),
										'options' => array(
											'full'      => __( 'Full', self::$text_domain ),
											'condensed' => __( 'Condensed', self::$text_domain ),
										),
									),
									array(
										'label'   => __( 'Phases', self::$text_domain ),
										'options' => array(
											'yes'        => __( 'Yes', self::$text_domain ),
											'complete'   => __( 'Complete', self::$text_domain ),
											'incomplete' => __( 'Incomplete', self::$text_domain ),
											'no'         => __( 'No', self::$text_domain ),
										),
									),
									array(
										'label'   => __( 'Tasks', self::$text_domain ),
										'options' => array(
											'complete'   => __( 'Complete', self::$text_domain ),
											'incomplete' => __( 'Incomplete', self::$text_domain ),
										),
									),
								),
							),
						),
					),
					'render'      => array(
						'displayBlock' => true,
					),
				),
				// Publicly private shortcode
				array(
					'code'      => 'panorama_dashboard',
					'noDisplay' => true,
				),
				// Publicly private shortcode
				array(
					'code'      => 'acf',
					'noDisplay' => true,
				),
			) as $shortcode
		) {

			$shortcode['category'] = 'project';
			$shortcode['source']   = 'Project Panorama';

			render_add_shortcode( $shortcode );
			render_add_shortcode_category( array(
				'id'    => 'project',
				'label' => __( 'Project', self::$text_domain ),
				'icon'  => 'dashicons-analytics',
			) );
		}
	}

	/**
	 * Display a notice in the admin if something went wrong.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	public function _notice() {
		?>
		<div class="error">
			<p>
				<?php _e( 'Render Project Panorama is not active due to the following errors:', self::$text_domain ); ?>
			</p>

			<ul>
				<?php foreach ( $this->deactivate_reasons as $reason ) : ?>
					<li>
						<?php echo "&bull; $reason"; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php
	}
}

new Render_PSP();