<?php

namespace OTGS\Toolset\Common\Admin;

/**
 * Register sections for the Troubleshooting page.
 *
 * @since Types 3.3.8
 */
class TroubleshootingSections {


	/**
	 * Initialization.
	 */
	public function initialize() {
		add_filter( 'toolset_get_troubleshooting_sections', [ $this, 'add_section' ] );
	}


	/**
	 * Add the troubleshooting sections.
	 *
	 * See Toolset_Page_Troubleshooting::get_sections() for details.
	 *
	 * @param array $sections
	 * @return array
	 */
	public function add_section( $sections ) {
		$sections['clear_post_guid_id_cache'] = [
			'title' => __( 'Clear the GUID to attachment ID cache', 'wpv-views' ),
			'description' => __(
				'Toolset maintains a dedicated database table to speed up the translation of media files. This is necessary, for example, when displaying an image on the front-end and using %%TITLE%% and %%ALT%% placeholders for its attributes. In very rare cases, it is possible for this table to become corrupted or obsolete. Here, you can clear it. That may lead to a temporary decline of performance the first time each image is loaded.',
				'wpv-views'
			),
			'button_label' => __( 'Clear the toolset_post_guid_id table', 'wpv-views' ),
			'is_dangerous' => false,
			'action_name' => \Toolset_Ajax::get_instance()->get_action_js_name( \Toolset_Ajax::CALLBACK_CLEAR_POST_GUID_ID_CACHE ),
			'nonce' => wp_create_nonce( \Toolset_Ajax::CALLBACK_CLEAR_POST_GUID_ID_CACHE ),
		];

		return $sections;
	}

}
