<?php

namespace Codelight\GDPR\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * DB base class
 * https://pippinsplugins.com/custom-database-api-the-basic-api-class/
 */
abstract class WordpressDatabase
{

    /**
     * The name of our database table
     *
     * @access  public
     * @since   2.1
     */
    public $tableName;

    /**
     * The version of our database table
     *
     * @access  public
     * @since   2.1
     */
    public $version;

    /**
     * The name of the primary column
     *
     * @access  public
     * @since   2.1
     */
    public $primaryKey;

    /**
     * Get things started
     *
     * @access  public
     * @since   2.1
     */
    public function __construct()
    {
    }

    /**
     * Whitelist of columns
     *
     * @access  public
     * @since   2.1
     * @return  array
     */
    public function getColumns()
    {
        return [];
    }

    /**
     * Default column values
     *
     * @access  public
     * @since   2.1
     * @return  array
     */
    public function getColumnDefaults()
    {
        return [];
    }

    /**
     * Retrieve a row by the primary key
     *
     * @access  public
     * @since   2.1
     * @return  object
     */
    public function get($row_id)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $this->tableName WHERE $this->primary_key = %s LIMIT 1;", $row_id
        ));
    }

    /**
     * Retrieve a row by a specific column / value
     *
     * @access  public
     * @since   2.1
     * @return  object
     */
    public function getBy($column, $row_id)
    {
        global $wpdb;

        // Security fix (SECURITY-AUDIT.md Finding 4): esc_sql() only escapes
        // quote/backslash characters -- it does not protect a value used in
        // an identifier position (e.g. a column name), which can carry an
        // injection payload that needs no quotes at all (UNION, subqueries,
        // comments). $wpdb->prepare()'s %s only protects the value
        // placeholder, not $column. Whitelist against the known columns
        // instead of relying on esc_sql() here.
        if (!array_key_exists($column, $this->getColumns())) {
            return null;
        }

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $this->tableName WHERE $column = %s LIMIT 1;", $row_id
        ));
    }

    /**
     * Retrieve a specific column's value by the primary key
     *
     * @access  public
     * @since   2.1
     * @return  string
     */
    public function getColumn($column, $row_id)
    {
        global $wpdb;

        // Security fix (SECURITY-AUDIT.md Finding 4): see getBy() above --
        // whitelist the column name instead of relying on esc_sql(), which
        // does not protect an identifier position.
        if (!array_key_exists($column, $this->getColumns())) {
            return null;
        }

        return $wpdb->get_var($wpdb->prepare(
            "SELECT $column FROM $this->tableName WHERE $this->primary_key = %s LIMIT 1;", $row_id
        ));
    }

    /**
     * Retrieve a specific column's value by the the specified column / value
     *
     * @access  public
     * @since   2.1
     * @return  string
     */
    public function getColumnBy($column, $column_where, $column_value)
    {
        global $wpdb;

        // Security fix (SECURITY-AUDIT.md Finding 4): see getBy() above --
        // whitelist both column names instead of relying on esc_sql(),
        // which does not protect an identifier position.
        $columns = $this->getColumns();
        if (!array_key_exists($column, $columns) || !array_key_exists($column_where, $columns)) {
            return null;
        }

        return $wpdb->get_var($wpdb->prepare(
            "SELECT $column FROM $this->tableName WHERE $column_where = %s LIMIT 1;", $column_value
        ));
    }

    /**
     * Insert a new row
     *
     * @access  public
     * @since   2.1
     * @return  int
     */
    public function insert($data, $type = '')
    {
        global $wpdb;

        // Set default values
        $data = wp_parse_args($data, $this->getColumnDefaults());

        do_action('bs_db_pre_insert_' . $type, $data);

        // Initialise column format array
        $columnFormats = $this->getColumns();

        // Force fields to lower case
        $data = array_change_key_case($data);

        // White list columns
        $data = array_intersect_key($data, $columnFormats);

        // Reorder $columnFormats to match the order of columns given in $data
        $data_keys = array_keys($data);
        $columnFormats = array_merge(array_flip($data_keys), $columnFormats);

        $wpdb->insert($this->tableName, $data, $columnFormats);

        do_action('bs_db_post_insert_' . $type, $wpdb->insert_id, $data);

        return $wpdb->insert_id;
    }

    /**
     * Update a row
     *
     * @access  public
     * @since   2.1
     * @return  bool
     */
    public function update($row_id, $data = [], $where = '')
    {
        global $wpdb;

        // Row ID must be positive integer
        $row_id = absint($row_id);

        if (empty($row_id)) {
            return false;
        }

        if (empty($where)) {
            $where = $this->primaryKey;
        }

        // Initialise column format array
        $columnFormats = $this->getColumns();

        // Force fields to lower case
        $data = array_change_key_case($data);

        // White list columns
        $data = array_intersect_key($data, $columnFormats);

        // Reorder $columnFormats to match the order of columns given in $data
        $data_keys = array_keys($data);
        $columnFormats = array_merge(array_flip($data_keys), $columnFormats);

        if (false === $wpdb->update($this->tableName, $data, [$where => $row_id], $columnFormats)) {
            return false;
        }

        return true;
    }


    /**
     * Delete a row identified by the primary key
     *
     * @access  public
     * @since   2.1
     * @return  bool
     */
    public function delete($row_id = 0)
    {
        global $wpdb;

        // Row ID must be positive integer
        $row_id = absint($row_id);

        if (empty($row_id)) {
            return false;
        }

        if (false === $wpdb->query($wpdb->prepare(
                "DELETE FROM $this->tableName WHERE $this->primary_key = %d", $row_id
            ))) {
            return false;
        }

        return true;
    }

    /**
     * Check if the given table exists
     *
     * @since  2.4
     * @param  string $table The table name
     * @return bool          If the table name exists
     */
    public function tableExists($table)
    {
        global $wpdb;
        $table = sanitize_text_field($table);

        // Security fix (SECURITY-AUDIT.md Finding 4): %s inside literal
        // quotes is a $wpdb->prepare() misuse -- prepare() already quotes
        // %s placeholders itself, so the literal quotes here just changed
        // what ended up being matched rather than adding protection.
        return $wpdb->get_var($wpdb->prepare(
                'SHOW TABLES LIKE %s', $table
            )) === $table;
    }


}