<?php
namespace WPGraphQL\Extensions\ACF\Type\Union;

use GraphQL\Type\Definition\UnionType;
use WPGraphQL\Extensions\ACF\Types as ACFTypes;
use WPGraphQL\Extensions\ACF\Utils as ACFUtils;

class LayoutUnionType extends UnionType {

	private static $possible_types;

	public function __construct() {

		$config = [
			'name' => 'layoutUnion',
			'types' => function() {
				return self::getPossibleTypes();
			},
			'resolveType' => function( $field ) {
				if( isset($field['graphql_label']) ) {
					$name = $field['graphql_label'];
				}else{
					$name = ACFUtils::_graphql_label( $field['name'] )."Layout";
				}
				return ! empty( $field ) ? self::$possible_types[ $name ] : null;
			},
		];

		parent::__construct( $config );
	}

	public function getPossibleTypes() {

		if ( null === self::$possible_types ) {
			self::$possible_types = [];
		} else {
			return ! empty( self::$possible_types ) ? self::$possible_types : null;
		}

		$field_groups = acf_get_field_groups();

		if ( ! empty( $field_groups ) && is_array( $field_groups ) ) {
			foreach ( $field_groups as $field_group ) {

				$acf_fields = acf_get_fields( $field_group['ID'] );

				$to_json = json_encode($acf_fields);
				file_put_contents('image-'.$field_group['ID'].'.json', $to_json);

				if ( ! empty( $acf_fields ) && is_array( $acf_fields ) ) {

					foreach ( $acf_fields as $acf_field ) {

						if( $acf_field['type'] == 'flexible_content' ) {

							foreach( $acf_field['layouts'] as $layout ) {

								self::$possible_types[ $layout['graphql_label'] ] = ACFTypes::layout( $layout );

							}

						}

					}
				}

			}
		}

		return ! empty( self::$possible_types ) ? self::$possible_types : null;

	}

	public function getResolveTypeFn() {
		return $this->resolveTypeFn;
	}

}
