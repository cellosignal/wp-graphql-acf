<?php
namespace WPGraphQL\Extensions\ACF;

use WPGraphQL\Extensions\ACF\Type\Field\FieldType;
use WPGraphQL\Extensions\ACF\Type\Field\RepeaterRow;
use WPGraphQL\Extensions\ACF\Type\Field\Layout;
use WPGraphQL\Extensions\ACF\Type\FieldGroup\FieldGroupType;
use WPGraphQL\Extensions\ACF\Type\LocationRule\LocationRuleType;
use WPGraphQL\Extensions\ACF\Type\Union\FieldUnionType;
use WPGraphQL\Extensions\ACF\Type\Union\LayoutUnionType;

class Types {

	private static $field_group_type;
	private static $location_rule_type;
	private static $field_type;
	private static $field_union_type;
	private static $layout_union_type;
	private static $repeater_row_type;
	private static $layout;

	public static function field_group_type() {
		return self::$field_group_type ? : ( self::$field_group_type = new FieldGroupType() );
	}

	public static function location_rule_type() {
		return self::$location_rule_type ? : ( self::$location_rule_type = new LocationRuleType() );
	}

	public static function field_type( $type ) {

		if ( null === self::$field_type ) {
			self::$field_type = [];
		}

		if ( ! empty( $type['graphql_label'] ) && empty( self::$field_type[ $type['graphql_label'] ] ) ) {
			self::$field_type[ $type['graphql_label'] ] = new FieldType( $type );
		}

		return ! empty( self::$field_type[ $type['graphql_label'] ] ) ? self::$field_type[ $type['graphql_label'] ] : null;

	}

	public static function layout( $layout ) {

		if ( null === self::$layout ) {
			self::$layout = [];
		}

		if ( ! empty( $layout['name'] ) && empty( self::$layout[ $layout['name'] ] ) ) {

			self::$layout[ $layout['name'] ] = new Layout( $layout );
		}

		return ! empty( self::$layout[ $layout['name'] ] ) ? self::$layout[ $layout['name'] ] : null;

	}

	public static function field_union_type() {
		return self::$field_union_type ? : ( self::$field_union_type = new FieldUnionType() );
	}

	public static function layout_union_type() {
		return self::$layout_union_type ? : ( self::$layout_union_type = new LayoutUnionType() );
	}

	public static function repeater_row( $type ) {
		if ( null === self::$repeater_row_type ) {
			self::$repeater_row_type = [];
		}

		if ( ! empty( $type['graphql_label'] ) && empty( self::$repeater_row_type[ $type['graphql_label'] ] ) ) {
			self::$repeater_row_type[ $type['graphql_label'] ] = new RepeaterRow( $type );
		}

		return ! empty( self::$repeater_row_type[ $type['graphql_label'] ] ) ? self::$repeater_row_type[ $type['graphql_label'] ] : null;
	}
}
