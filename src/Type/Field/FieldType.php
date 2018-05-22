<?php
namespace WPGraphQL\Extensions\ACF\Type\Field;

use \WPGraphQL\Extensions\ACF\Types as ACFTypes;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQLRelay\Relay;
use WPGraphQL\AppContext;
use WPGraphQL\Type\WPObjectType;
use WPGraphQL\Types;

class FieldType extends WPObjectType {

	private static $fields;
	private static $type_name;
	private static $type;

	public function __construct( $type ) {

		/**
		 * Set the name of the field
		 */
		self::$type = $type;
		self::$type_name = ! empty( self::$type['graphql_label'] ) ? 'acf' . ucwords( self::$type['graphql_label'] ) . 'Field' : null;

		/**
		 * Merge the fields passed through the config with the default fields
		 */
		$config = [
			'name' => self::$type_name,
			'fields' => self::fields( self::$type ),
			// Translators: the placeholder is the name of the ACF Field type
			'description' => sprintf( __( 'ACF Field of the %s type', 'wp-graphql-acf' ), self::$type_name ),
			'interfaces'  => [ self::node_interface() ],
		];

		parent::__construct( $config );

	}


	private function fields( $type ) {

		if ( null === self::$fields ) {
			self::$fields = [];
		}

		if ( empty( self::$fields[ $type['graphql_label'] ] ) ) {

			self::$fields[ $type['graphql_label'] ] = function() use ( $type ) {

				$fields = [
					'id' => [
						'type' => Types::non_null( Types::id() ),
						'description' => __( 'The global ID for the field', 'wp-graphql-acf' ),
						'resolve' => function( array $field, array $args, AppContext $context, ResolveInfo $info ) {
							return ( ! empty( $field['ID'] ) && absint( $field['ID'] ) ) ? Relay::toGlobalId( self::$type_name, $field['ID'] ) : null;
						},
					],
					$type['graphql_label'] . 'Id' => [
						'type' => Types::non_null( Types::int() ),
						'description' => __( 'The database ID for the field', 'wp-graphql-acf' ),
						'resolve' => function( array $field ) {
							return ( ! empty( $field['ID'] ) && absint( $field['ID'] ) ) ? absint( $field['ID'] ) : null;
						},
					],
					'label' => [
						'type' => Types::non_null( Types::string() ),
						'description' => __( 'This is the name which will appear on the EDIT page', 'wp-graphql-acf' ),
					],
					'name' => [
						'type' => Types::non_null( Types::string() ),
						'description' => __( 'The name of the field. Single word, no spaces. Underscores and dashes allowed.', 'wp-graphql-acf' ),
					],
					'instructions' => [
						'type' => Types::string(),
						'description' => __( 'Instructions for authors. Shown when submitting data', 'wp-graphql-acf' ),
					],
					'prefix' => [
						'type' => Types::string(),
					],
					// 'order' => [],
					'required' => [
						'type' => Types::boolean(),
					],
					'key' => [
						'type' => Types::string(),
					],
					'class' => [
						'type' => Types::string(),
					],
					// @todo: Add conditional logic
					'group' => [
						'type' => ACFTypes::field_group_type(),
						'description' => __( 'The field group this field is part of', 'wp-graphql-acf' ),
						'resolve' => function( array $field ) {
							$field_group = acf_get_field_group( $field['parent'] );
							return ! empty( $field_group ) ? $field_group : null;
						},
					],
				];

				$ftype = isset($type['type']) ? $type['type'] : $type['graphql_label'];

				switch( $ftype ) {
					case "oembed":
					case "oembedField":
						$fields['value'] = [
							'type' => Types::string(),
							'args' => [
								'raw' => [
									'type' => Types::boolean(),
									'description' => __( 'Should it return the value raw', 'wp-graphql' ),
								],
							],
							'resolve' => function( array $field, $args ) {
								if( isset($field['value']) ) {
									if( isset($args['raw']) && $args['raw'] ) {
										$field['value'] = strip_tags($field['value']);
									}
									return $field['value'];
								}
								return get_field( $field['key'], $field['object_id'], true );
							},
						];
						break;

					case "image":
					case "imageField":
						$fields['value'] = [
							'type' => Types::post_object('attachment'),
							'resolve' => function( array $field ) {
								$to_json = json_encode($type);
								file_put_contents('value.json', $to_json);
								if( isset($field['value']) ) {
									$field = $field['value'];
								} else {
									$field = get_field( $field['key'], $field['object_id'], true );
								}
								return \WP_Post::get_instance( $field['ID'] );
							},
						];
						break;

					case "flexible_content":
					case "flexibleContent":
					case "flexibleContentField":
						$fields['value'] = [
							'type' => Types::list_of( ACFTypes::layout_union_type() ),
							'resolve' => function( array $field ) {

								if( isset($field['value']) ) {
									return $field['value'];
								}

								$field = get_field_object( $field['key'], $field['object_id'], true );

								return $field['value'];
							},
						];
						break;

					// case "repeater":
					// case "repeaterField":
					// 	$fields['value'] = [
					// 		'type' => Types::list_of( ACFTypes::repeater_row( $type ) ),
					// 		'resolve' => function( array $field ) {
					// 			if( isset($field['value']) ) {
					// 				return $field['value'];
					// 			}
					// 			return get_field( $field['key'], $field['object_id'], true );
					// 		},
					// 	];
					// 	break;

					case "gallery":
					case "galleryField":
						$fields['value'] = [
							'type' => Types::list_of( Types::post_object('attachment') ),
							'resolve' => function( array $field ) {
								if( isset($field['value']) ) {

									if( !$field['value'] ) return;

									$field['value'] = array_map(function($f){
										return \WP_Post::get_instance( $f['ID'] );
									}, $field['value']);

									return $field['value'];
								}
								$field = get_field_object( $field['key'], $field['object_id'], true );
								return $field['value'];
							},
						];
						break;

					case "relationship":
					case "relationshipField":
						$fields['value'] = [
							'type' => Types::list_of( Types::post_object('post') ),
							'resolve' => function( array $field ) {
								if( isset($field['value']) ) {
									return $field['value'];
								}
								return get_field( $field['key'], $field['object_id'], true );
							},
						];
						break;

                    case "file":
                    case "fileField":
                        $fields['value'] = [
                            'type' => Types::post_object('attachment'),
                            'resolve' => function( array $field ) {
                                if( isset($field['value']) ) {
                                    $field = $field['value'];
                                } else {
                                    $field = get_field( $field['key'], $field['object_id'], true );
                                }
                                return \WP_Post::get_instance( $field['ID'] );
                            },
                        ];
                        break;

                    /**
					 * Default returns an string or number types, fields that will be returned via the default include all Basic fields:
					 * - text
					 * - text area
					 * - number
					 * - range
					 * - email
					 * - url
					 * - password
					 *
					 * Current issues: 'Field Field' breaks.
					 */
					default:
						$fields['value'] = [
							'type' => Types::string(),
							'resolve' => function( array $field ) {
								if( isset($field['value']) ) {
									return $field['value'];
								}
								return get_field( $field['key'], $field['object_id'], true );
							},
						];
				}

				return self::prepare_fields( $fields, $type['graphql_label'] );

			};

		} // End if().

		return ! empty( self::$fields[ $type['graphql_label'] ] ) ? self::$fields[ $type['graphql_label'] ] : null;

	}

}
