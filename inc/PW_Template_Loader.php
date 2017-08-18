<?php
 
/**
 * Template loader for PW Sample Plugin.
 *
 * Only need to specify class properties here.
 *
 */
class PW_Template_Loader extends Gamajo_Template_Loader
{
	/**
	 * Prefix for filter names.
	 *
	 * @since 1.0.0
	 * @type string
	 */
	// protected $filter_prefix = 'pw';
	protected $filter_prefix = 'de-bam-pass';
 
	/**
	 * Directory name where custom templates for this plugin should be found in the theme.
	 *
	 * @since 1.0.0
	 * @type string
	 */
	// protected $theme_template_directory = 'pw-templates';
	protected $theme_template_directory = 'templates';
 
	/**
	 * Reference to the root directory path of this plugin.
	 *
	 * @since 1.0.0
	 * @type string
	 */
	// protected $plugin_directory = PW_SAMPLE_PLUGIN_DIR;
	protected $plugin_directory = '';
	
	public function __construct($pluginDir)
	{
		$this->plugin_directory = $pluginDir;
	}
}
