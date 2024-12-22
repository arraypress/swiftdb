<?php

namespace SwiftDB\Queries;

use SwiftDB\Base;

/**
 * Class Aggregate
 *
 * Handles SQL aggregate functions for database queries. This class is responsible for
 * generating the appropriate SQL clauses for aggregate operations like SUM, AVG, MAX, etc.
 *
 * @since   1.0.0
 *
 * @package BerlinDB\Database\Queries
 */
class Aggregate extends Base {

	/**
	 * The aggregate function to apply (SUM, AVG, MAX, MIN, etc.).
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected string $function = '';

	/**
	 * Fields to apply the aggregate function to.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected array $fields = array();

	/**
	 * Operator to use when combining multiple fields.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected string $operator = '+';

	/**
	 * List of allowed SQL aggregate functions.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected array $allowed_functions = array(
		'SUM',
		'AVG',
		'MAX',
		'MIN',
		'COUNT',
		'GROUP_CONCAT',
		'STDDEV',
		'VAR_SAMP',
		'VAR_POP'
	);

	/**
	 * List of allowed arithmetic operators.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected array $allowed_operators = array(
		'+',
		'-',
		'*',
		'/',
		'%'
	);

	/**
	 * Constructor.
	 *
	 * @param array       $args     {
	 *                              Optional. Arguments for configuring the aggregate query.
	 *
	 * @type string       $function The aggregate function to use (SUM, AVG, etc.)
	 * @type string|array $fields   The field(s) to aggregate
	 * @type string       $operator The operator to use when combining multiple fields
	 *                              }
	 * @since 1.0.0
	 *
	 */
	public function __construct( array $args = array() ) {
		if ( ! empty( $args['function'] ) ) {
			$this->function = $this->sanitize_function( $args['function'] );
		}

		if ( ! empty( $args['fields'] ) ) {
			$this->fields = $this->sanitize_fields( $args['fields'] );
		}

		if ( ! empty( $args['operator'] ) ) {
			$this->operator = $this->sanitize_operator( $args['operator'] );
		}
	}

	/**
	 * Generates SQL clauses for the aggregate query.
	 *
	 * @param string $table       The database table name
	 * @param string $table_alias The alias for the table
	 * @param string $primary     Primary column name
	 * @param object $parent      Parent query object
	 *
	 * @return array|false SQL clauses or false on error. Array contains keys:
	 *                     'select' - The SELECT clause
	 *                     'join'   - Any JOIN clauses (empty in base implementation)
	 *                     'where'  - Any WHERE clauses (empty in base implementation)
	 * @since 1.0.0
	 *
	 */
	public function get_sql( $table, $table_alias, $primary = '', $parent = null ) {
		$sql = array(
			'select' => '',
			'join'   => '',
			'where'  => ''
		);

		// Bail if required components are missing
		if ( empty( $this->function ) || empty( $this->fields ) || empty( $this->operator ) ) {
			return false;
		}

		// Validate fields
		$validated_fields = $this->validate_aggregate_fields( $this->fields, $parent );

		// Bail if no valid fields remain after validation
		if ( empty( $validated_fields ) ) {
			return false;
		}

		// Update fields with validated ones
		$this->fields = $validated_fields;

		// Get groupby from parent query if it exists
		$groupby = ! empty( $parent->query_vars['groupby'] )
			? $parent->query_vars['groupby']
			: '';

		// Build the appropriate SELECT clause
		if ( 'GROUP_CONCAT' === $this->function ) {
			$sql['select'] = $this->build_group_concat_sql( $table_alias, $groupby );
		} else {
			$sql['select'] = $this->build_aggregate_sql( $table_alias, $groupby );
		}

		return $sql;
	}

	/**
	 * Validates the provided fields, ensuring each exists and is numeric.
	 * Fields not meeting these criteria are omitted from the returned array,
	 * effectively filtering out invalid fields for aggregation purposes.
	 *
	 * @param array  $fields An array of fields to validate for aggregation.
	 * @param object $parent Parent query object for column validation
	 *
	 * @return array An array of validated field names suitable for inclusion in an SQL query.
	 */
	private function validate_aggregate_fields( array $fields, $parent ): array {
		$validated_fields = [];

		foreach ( $fields as $field ) {
			$field = trim( $field );

			// Skip if field doesn't exist in schema
			if ( ! $parent->column_exists( $field ) ) {
				continue;
			}

			// Skip if field isn't numeric
			if ( ! $parent->is_column_numeric( $field ) ) {
				continue;
			}

			$validated_fields[] = $field;
		}

		return $validated_fields;
	}

	/**
	 * Builds SQL for standard aggregate functions (SUM, AVG, MAX, MIN, etc.).
	 *
	 * @param string       $alias   Table alias
	 * @param string|array $groupby Optional. Group by column(s)
	 *
	 * @return string SQL clause for the aggregate function
	 * @since 1.0.0
	 *
	 */
	private function build_aggregate_sql( $alias, $groupby = '' ) {
		$fields = array_map( function ( $f ) use ( $alias ) {
			return "{$alias}.{$f}";
		}, $this->fields );

		$fields_expr = implode( " {$this->operator} ", $fields );

		// Handle groupby if present
		if ( ! empty( $groupby ) ) {
			$group_cols = is_array( $groupby ) ? $groupby : array( $groupby );
			$group_cols = array_map( function ( $col ) use ( $alias ) {
				return "{$alias}.{$col}";
			}, $group_cols );

			return implode( ', ', $group_cols ) . ", {$this->function}({$fields_expr}) AS aggregated_value";
		}

		return "{$this->function}({$fields_expr}) AS aggregated_value";
	}

	/**
	 * Builds SQL for GROUP_CONCAT aggregate function.
	 *
	 * @param string       $alias   Table alias
	 * @param string|array $groupby Optional. Group by column(s)
	 *
	 * @return string SQL clause for the GROUP_CONCAT function
	 * @since 1.0.0
	 *
	 */
	private function build_group_concat_sql( $alias, $groupby = '' ) {
		$fields = array_map( function ( $f ) use ( $alias ) {
			return "{$alias}.{$f}";
		}, $this->fields );

		$concat_inner = "CONCAT(" . implode( ", '|', ", $fields ) . ")";
		$group_concat = "GROUP_CONCAT(DISTINCT {$concat_inner} SEPARATOR '|') AS aggregated_value";

		// Handle groupby if present
		if ( ! empty( $groupby ) ) {
			$group_cols = is_array( $groupby ) ? $groupby : array( $groupby );
			$group_cols = array_map( function ( $col ) use ( $alias ) {
				return "{$alias}.{$col}";
			}, $group_cols );

			return implode( ', ', $group_cols ) . ", {$group_concat}";
		}

		return $group_concat;
	}

	/**
	 * Sanitizes the aggregate function name.
	 *
	 * @param string $fn Function name to sanitize
	 *
	 * @return string Sanitized function name or empty string if invalid
	 * @since 1.0.0
	 *
	 */
	private function sanitize_function( $fn ) {
		$fn = strtoupper( trim( $fn ) );

		return in_array( $fn, $this->allowed_functions, true ) ? $fn : '';
	}

	/**
	 * Sanitizes the field names.
	 *
	 * @param string|array $fields Field(s) to sanitize
	 *
	 * @return array Sanitized field names
	 * @since 1.0.0
	 *
	 */
	private function sanitize_fields( $fields ) {
		if ( is_string( $fields ) ) {
			$fields = array( trim( $fields ) );
		}

		return array_map( function ( $f ) {
			return preg_replace( '/[^a-zA-Z0-9_]/', '', $f );
		}, (array) $fields );
	}

	/**
	 * Sanitizes the operator.
	 *
	 * @param string $op Operator to sanitize
	 *
	 * @return string Sanitized operator or '+' if invalid
	 * @since 1.0.0
	 *
	 */
	private function sanitize_operator( $op ) {
		$op = trim( $op );

		return in_array( $op, $this->allowed_operators, true ) ? $op : '+';
	}

}