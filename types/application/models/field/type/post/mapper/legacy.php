<?php

/**
 * Class Types_Field_Type_Post_Mapper_Legacy
 *
 * Mapper for "Post" field
 *
 * @since 2.3
 */
class Types_Field_Type_Post_Mapper_Legacy extends Types_Field_Mapper_Abstract {

	/**
	 * @var Types_Field_Type_Post_Factory
	 */
	protected $field_factory;


	/** @var \OTGS\Toolset\Common\PostStatus */
	protected $post_status;


	/** @var Toolset_Relationship_Definition_Repository  */
	protected $relationship_repository;


	/** @var Toolset_Relationship_Query_Factory */
	protected $query_factory;


	/**
	 * Types_Field_Type_Post_Mapper_Legacy constructor.
	 *
	 * @param Types_Field_Factory_Interface $factory
	 * @param Types_Field_Gateway_Interface $gateway
	 * @param \OTGS\Toolset\Common\PostStatus $post_status
	 * @param Toolset_Relationship_Definition_Repository $relationship_repository
	 * @param Toolset_Relationship_Query_Factory $query_factory
	 */
	public function __construct(
		Types_Field_Factory_Interface $factory,
		Types_Field_Gateway_Interface $gateway,
		\OTGS\Toolset\Common\PostStatus $post_status,
		Toolset_Relationship_Definition_Repository $relationship_repository,
		Toolset_Relationship_Query_Factory $query_factory
	) {
		parent::__construct( $factory, $gateway );
		$this->post_status = $post_status;
		$this->relationship_repository = $relationship_repository;
		$this->query_factory = $query_factory;
	}


	/**
	 * @param $id
	 * @param $id_post
	 *
	 * @return null|Types_Field_Type_Post
	 * @throws Exception
	 */
	public function find_by_id( $id, $id_post ) {
		if( ! $field = $this->database_get_field_by_id( $id ) ) {
			return null;
		};

		if( $field['type'] !== 'post' ) {
			throw new Exception( 'Types_Field_Type_Post_Mapper_Legacy can not map type: ' . $field['type'] );
		}

		$field = $this->map_common_field_properties( $field );

		if( isset( $field['data'] ) && isset( $field['data']['post_reference_type'] ) ) {
			$field['post_reference_type'] = $field['data']['post_reference_type'];
		}

		if( $relationship = $this->relationship_repository->get_definition( $id ) ) {
			$associations_query = $this->query_factory->associations_v2();
			$user_selected_post = $associations_query
				->add( $associations_query->child_id( $id_post ) )
				->add( $associations_query->relationship( $relationship ) )
				->add( $associations_query->element_status(
					// We'll show a trashed post when it's already set to provide the full information to the user
					// and to allow them clearing the field if necessary.
					array_merge( $this->post_status->get_available_post_statuses(), [ 'trash' ] ),
					new Toolset_Relationship_Role_Parent() ) )
				->limit( 1 )
				->return_element_ids( new Toolset_Relationship_Role_Parent() )
				->get_results();

			if( ! empty( $user_selected_post ) ) {
				$field['value'] =  strval( $user_selected_post[0] );
			}
		}

		$entity = $this->field_factory->get_field( $field );
		return $entity;
	}
}
