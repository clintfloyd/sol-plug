<?php

/**
 * Helper class for extending a Twig environment in a standardized way.
 *
 * @since 2.2
 */
class Toolset_Twig_Extensions {

	private static $instance;


	private $last_unique_id = 0;
	
	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	private function __clone() { }
	
	private function __construct() { }

	
	/**
	 * Extend the provided Twig environment.
	 *
	 * @param OTGS\Toolset\Twig\Environment $twig
	 * @return OTGS\Toolset\Twig\Environment
	 * @since 2.2
	 */
	public function extend_twig( $twig ) {

		$twig->addFunction( '__', new \OTGS\Toolset\Twig\TwigFunction( '__', array( $this, 'translate' ) ) );
		$twig->addFunction( 'do_meta_boxes', new \OTGS\Toolset\Twig\TwigFunction( 'do_meta_boxes', array( $this, 'do_meta_boxes' ) ) );
		$twig->addFunction( 'unique_name', new \OTGS\Toolset\Twig\TwigFunction( 'unique_name', array( $this, 'unique_name' ) ) );
		$twig->addFunction( 'printf', new \OTGS\Toolset\Twig\TwigFunction( 'printf', 'printf' ) );
		$twig->addFunction( 'sprintf', new \OTGS\Toolset\Twig\TwigFunction( 'sprintf', 'sprintf' ) );
		$twig->addFunction( 'apply_filters', new \OTGS\Toolset\Twig\TwigFunction( 'apply_filters', 'apply_filters' ) );
		$twig->addFunction( 'do_action', new \OTGS\Toolset\Twig\TwigFunction( 'do_action', 'do_action' ) );

		return $twig;
	}


	public function translate( $text, $domain = 'types' ) {
		return __( $text, $domain );
	}


    public function do_meta_boxes( $context = 'normal', $object = '') {
        do_meta_boxes( get_current_screen(), $context, $object );
	}


	public function unique_name( $prefix = 'toolset_element_' ) {
		$id = ++$this->last_unique_id;

		return $prefix . $id;
	}


}
