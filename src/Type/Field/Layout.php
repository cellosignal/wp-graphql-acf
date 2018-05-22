<?php

namespace WPGraphQL\Extensions\ACF\Type\Field;

use \WPGraphQL\Extensions\ACF\Types as ACFTypes;
use WPGraphQL\Extensions\ACF\Utils as ACFUtils;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQLRelay\Relay;
use WPGraphQL\AppContext;
use WPGraphQL\Type\WPObjectType;
use WPGraphQL\Types;

class Layout extends WPObjectType {

	private static $fields;
	private static $type_name;
	private static $type;

	public function __construct( $type ) {

		/**
		 * Set the name of the field
		 */
		self::$type = $type;
		self::$type_name = ! empty( self::$type['graphql_label'] ) ? 'acf' . ucwords( self::$type['graphql_label'] ) : null;

		$to_json = json_encode($type);
		file_put_contents('why-1.json', $type);

		/**
		 * Merge the fields passed through the config with the default fields
		 */
		$config = [
			'name' => self::$type_name,
			'fields' => self::fields( self::$type ),
			// Translators: the placeholder is the name of the ACF Field type
			'description' => sprintf( __( 'ACF Field of the %s type', 'wp-graphql-acf' ), self::$type_name ),
			// 'interfaces'  => [ self::node_interface() ],
		];

		parent::__construct( $config );

	}

	private function fields( $type ) {

		if ( null === self::$fields ) {
			self::$fields = [];
		}

		if ( empty( self::$fields[ $type['graphql_label'] ] ) ) {

			self::$fields[ $type['graphql_label'] ] = function() use ( $type ) {

				$layoutFields = [];

				foreach( $type['sub_fields'] as $field ) {

					$layoutFields[ $field['graphql_label'] ] = [
						'type'        => ACFTypes::field_type( $field ),
						// Translators: The placeholder is the type of object (post_type, taxonomy, etc) being filtered
						'description' => sprintf( __( 'The %1$s field', 'wp-graphql-acf' ), $field['label'] ),
						'resolve'     => function( $resolving_object ) use ( $field, $type ) {
							return $resolving_object[$field['name']];
						},
					];
				}

				return $layoutFields;

			};

		} // End if().

		return ! empty( self::$fields[ $type['graphql_label'] ] ) ? self::$fields[ $type['graphql_label'] ] : null;

	}

}
