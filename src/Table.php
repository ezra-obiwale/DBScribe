<?php

namespace DBScribe;

/**
 * This holds all information concerning a database table and methods to operate
 * on the table and it's columns and rows
 * 
 * @author Ezra Obiwale <contact@ezraobiwale.com>
 */
class Table {

    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';
    const INDEX_REGULAR = 'INDEX';
    const INDEX_UNIQUE = 'UNIQUE';
    const INDEX_FULLTEXT = 'FULLTEXT';
    const OP_SELECT = 'select';
    const OP_INSERT = 'insert';
    const OP_UPDATE = 'update';
    const OP_DELETE = 'delete';
    const RETURN_DEFAULT = 0;
    const RETURN_MODEL = 1;
    const RETURN_JSON = 2;

    /**
     * Table name
     * @var string
     */
    protected $name;

    /**
     * Connection object
     * @var \DBScribe\Connection
     */
    protected $connection;

    /**
     * Table Description
     * @var string
     */
    protected $description;

    /**
     * New Table Description
     * @var string 
     */
    protected $newDescription;

    /**
     * Array of columns and some of their properties
     * @var array
     */
    protected $columns;

    /**
     * Array of new columns and their definitions
     * @var array
     */
    protected $newColumns;

    /**
     * Array of references from this table to other tables
     * @var array
     */
    protected $references;

    /**
     * Array of references from other tables to this table
     * @var array
     */
    protected $backReferences;

    /**
     * Array of indexes
     * @var string
     */
    protected $indexes;

    /**
     * Array of new references from this table to others
     * @var array
     */
    protected $newReferences;

    /**
     * The primary key of the table
     * @var string
     */
    protected $primaryKey;

    /**
     * Indicates whether to remove the primary key
     * @var boolean
     */
    protected $dropPrimaryKey;

    /**
     * The new primary key to replace the old
     * @var string
     */
    protected $newPrimaryKey;

    /**
     * Array of columns to remove from the table
     * @var array
     */
    protected $dropColumns;

    /**
     * Array of existing columns with new definitions
     * @var array
     */
    protected $alterColumns;

    /**
     * Array of columns whose references should be dropped
     * @var array
     */
    protected $dropReferences;

    /**
     * Array of columns whose references need be changed
     * @var array
     */
    protected $alterReferences;

    /**
     * Array of columns to add indexes to
     * @var array
     */
    protected $newIndexes;

    /**
     * Array of columns whose indexes should be dropped
     * @var array
     */
    protected $dropIndexes;

    /**
     * The query string to be executed
     * @var string
     */
    protected $query;

    /**
     * The query that serves to join results with referenced tables' rows
     * @var string
     */
    protected $joinQuery;

    /**
     * Array of prepared values to be saved to the database
     * @var array
     */
    protected $values;

    /**
     * Array of referenced tables from which to draw more rows
     * @var array
     */
    protected $joins;

    /**
     * Indicates whether multiple values are prepared for the query
     * @var boolean
     */
    protected $multiple;

    /**
     * Indicates whether to return results with a class model
     * @var boolean
     */
    protected $withModel;

    /**
     * The class that extends \DBScribe\Row which to map results to
     * @var \DBScribe\Row
     */
    protected $rowModel;

    /**
     * Holds the current model the class is working with
     * @var \DBScribe\Row
     */
    protected $rowModelInUse;

    /**
     * Array of columns and options to order the result by
     * @var array
     */
    protected $orderBy;

    /**
     * The limit part of the query
     * @var string 
     */
    protected $limit;

    /**
     * Indicates whether to delay the execution of the query until @method execute() is called
     * @var boolean
     */
    protected $delayExecute;

    /**
     * Indicates whether to run the postSave method of the Row after operating the query
     * @var boolean
     */
    protected $doPost;

    /**
     * Currenct operation: one of the OP_ constants of this class
     * @var string
     */
    protected $current;

    /**
     * Additional conditions to attach to the query
     * @var string
     */
    protected $customWhere;

    /**
     * Indicates conditions have been attached to the query. This does not take
     * the customWhere into cognizance
     * @var boolean 
     */
    protected $where;

    /**
     * Array of columns to group query results by
     * @var array 
     */
    protected $groups;

    /**
     * The having portion of the query
     * @var string
     */
    protected $having;

    /**
     * Array holding the relationship information with all other tables
     * @var array 
     */
    protected $relationshipData;

    /**
     * Used with customWhere(). No relationship with join()
     * @var string AND|OR
     */
    protected $customWhereJoin;

    /**
     * Indicates the type of results expected
     * @var int One of the RETURN_* constants of this class
     */
    protected $return;

    /**
     * Class contructor
     * @param string $name Name of the table, without the prefix if already 
     * supplied in the connection object
     * @param \DBScribe\Connection $connection
     * @param \DBScribe\Row $rowModel
     */
    public function __construct($name, Connection $connection = null, Row $rowModel = null) {
        $this->name = $connection->getTablePrefix() . strtolower(Util::camelTo_($name));
        $this->connection = $connection;
        $this->rowModel = ($rowModel) ? $rowModel : new Row();
        $this->multiple = false;
        $this->doPost = false;
        $this->return = Table::RETURN_MODEL;
        $this->delayExecute = false;
        $this->where = false;
        $this->groups = array();
        $this->orderBy = array();
        $this->joins = array();

        $this->columns = array();
        $this->references = array();
        $this->backReferences = array();
        $this->indexes = array();
        $this->foreignKeys = array();

        $this->newColumns = array();
        $this->newReferences = array();
        $this->alterColumns = array();
        $this->dropColumns = array();
        $this->dropReferences = array();
        $this->dropIndexes = array();
        $this->alterReferences = array();

        $this->init();
    }

    /**
     * Sets the model to use with fetched rows
     * @param \DBScribe\Row $model
     * @return \DBScribe\Table
     */
    public function setRowModel(Row $model) {
        $this->rowModel = $model;
        return $this;
    }

    /**
     * Fetches the connection used in the table
     * @return Connection
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Initialiazes the table
     */
    public function init() {
        if ($this->connection)
            $this->defineRelationships();
    }

    /**
     * Fetches the name of the table
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set the table description
     * @param string $tableDescription
     * @return \DBScribe\Table
     */
    public function setDescription($tableDescription = 'ENGINE=InnoDB') {
        if (!$this->description)
            $this->description = $tableDescription;
        return $this;
    }

    /**
     * Change the table description
     * @param string $tableDescription
     * @return \DBScribe\Table
     */
    public function changeDescription($tableDescription = 'ENGINE=InnoDB') {
        $this->newDescription = $tableDescription;
        return $this;
    }

    /**
     * Fetches the description of the table
     * @return string|null
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Fetches the new description for the table
     * @return string|null
     */
    public function getNewDescription($reset = false) {
        $return = $this->newDescription;
        if ($reset)
            $this->newDescription = null;
        return $return;
    }

    /**
     * Sets the primary key
     * @param string $pk
     * @return \DBScribe\Table
     */
    public function setPrimaryKey($pk) {
        if ($this->primaryKey === $pk)
            return $this;

        if ($this->primaryKey) {
            $this->dropPrimaryKey();
        }
        $this->newPrimaryKey = $pk;

        return $this;
    }

    /**
     * Fetches the primary key
     * @return string|null
     */
    public function getPrimaryKey() {
        return $this->primaryKey;
    }

    /**
     * Removes the primary key
     * @return \DBScribe\Table
     */
    public function dropPrimaryKey() {
        $this->dropPrimaryKey = true;
        return $this;
    }

    /**
     * Checks if to drop the primary key
     * @return boolean|null
     */
    public function shouldDropPrimaryKey($reset = false) {
        $return = $this->dropPrimaryKey;
        if ($reset)
            $this->dropPrimaryKey = false;
        return $return;
    }

    /**
     * Fetches the new primary key
     * @return string|null
     */
    public function getNewPrimarykey($reset = false) {
        $return = $this->newPrimaryKey;
        if ($reset)
            $this->newPrimaryKey = null;
        return $return;
    }

    /**
     * Fetches the indexes
     * @param string $columnName Name of column to return the index
     * @return array
     */
    public function getIndexes($columnName = null) {
        if (!$this->indexes) {
            $this->indexes = array();
        }
        return ($columnName) ? $this->indexes[$columnName] : $this->indexes;
    }

    /**
     * Adds an index to a column
     * @param string $columnName
     * @param string $type Should be one of \DBScribe\Table::INDEX_REGULAR,  \DBScribe\Table::INDEX_UNIQUE,
     * or  \DBScribe\Table::INDEX_FULLTEXT
     * @return \DBScribe\Table
     */
    public function addIndex($columnName, $type = Table::INDEX_REGULAR) {
        if (!array_key_exists($columnName, $this->getIndexes()) && !array_key_exists($columnName, $this->newIndexes))
            $this->newIndexes[$columnName] = $type;
        return $this;
    }

    /**
     * Fetches the indexes to create
     * @return array
     */
    public function getNewIndexes($reset = false) {
        $return = $this->newIndexes;
        if ($reset)
            $this->newIndexes = array();
        return $return;
    }

    /**
     * Removes index from a column
     * @param string $columnName
     * @return \DBScribe\Table
     */
    public function dropIndex($columnName) {
        if (!in_array($columnName, $this->dropIndexes))
            $this->dropIndexes[] = $columnName;

        if (array_key_exists($columnName, $this->getReferences()))
            $this->dropReference($columnName);
        return $this;
    }

    /**
     * Fetches the indexes to remove
     * @param bool $reset Reset the indexes
     * @return array
     */
    public function getDropIndexes($reset = false) {
        $return = $this->dropIndexes;
        if ($reset)
            $this->dropIndexes = array();
        return $return;
    }

    /**
     * Add a column to the table
     * @param string $columnName
     * @param string $columnDescription
     * @return \DBScribe\Table
     */
    public function addColumn($columnName, $columnDescription) {
        $this->newColumns[$columnName] = $columnDescription;
        return $this;
    }

    /**
     * Gets available columns in table
     * @return array
     */
    public function getColumns($justNames = false) {
        return ($justNames) ? array_keys($this->columns) : $this->columns;
    }

    /**
     * Removes a column
     * @param string $columnName
     * @return \DBScribe\Table
     */
    public function dropColumn($columnName) {
        $this->dropColumns[] = $columnName;
        if (array_key_exists($columnName, $this->references)) {
            $this->dropReference($columnName);
        }
        return $this;
    }

    /**
     * Fetches columns to remove
     * @param bool $reset Reset the columns
     * @return array
     */
    public function getDropColumns($reset = false) {
        $return = $this->dropColumns;
        if ($reset)
            $this->dropColumns = array();
        return $return;
    }

    /**
     * Fetches columns to add
     * @param bool $reset Reset the columns
     * @return array
     */
    public function getNewColumns($reset = false) {
        $return = $this->newColumns;
        if ($reset)
            $this->newColumns = array();
        return $return;
    }

    /**
     * Alter column description
     * @param string $columnName
     * @param string $columnDescription
     * @return \DBScribe\Table
     */
    public function alterColumn($columnName, $columnDescription) {
        $this->alterColumns[$columnName] = $columnDescription;
        return $this;
    }

    /**
     * Fetches the columns to change
     * @return array
     */
    public function getAlterColumns($reset = false) {
        $return = $this->alterColumns;
        if ($reset)
            $this->alterColumns = array();
        return $return;
    }

    /**
     * Fetches the references in the table
     * @return array
     */
    public function getReferences() {
        return $this->references;
    }

    /**
     * Fetches the tables and columns that reference this table
     * @return array
     */
    public function getBackReferences() {
        return $this->backReferences;
    }

    /**
     * Removes a reference from a column
     * @param string $columnName
     * @return \DBScribe\Table
     */
    public function dropReference($columnName) {
        $this->dropReferences[] = $columnName;
        return $this;
    }

    /**
     * Fetches all columns from which references should be dropped
     * @return array
     */
    public function getDropReferences($reset = false) {
        $return = array_unique($this->dropReferences);
        if ($reset)
            $this->dropReferences = array();
        return $return;
    }

    /**
     * Add reference to a column
     * @param string $columnName
     * @param string $refTable
     * @param string $refColumn
     * @return \DBScribe\Table
     */
    public function addReference($columnName, $refTable, $refColumn, $onDelete = 'RESTRICT', $onUpdate = 'RESTRICT') {
        $this->addIndex($columnName);
        $this->newReferences[$columnName] = array(
            'table' => $refTable,
            'column' => $refColumn,
            'onDelete' => $onDelete,
            'onUpdate' => $onUpdate,
        );
        return $this;
    }

    /**
     * Fetches all new references
     * @return array
     */
    public function getNewReferences($reset = false) {
        $return = $this->newReferences;
        if ($reset)
            $this->newReferences = array();
        return $return;
    }

    /**
     * Alter references of the table column
     * @param string $columnName
     * @param string $refTable
     * @param string $refColumn
     * @return \DBScribe\Table
     */
    public function alterReference($columnName, $refTable, $refColumn, $onDelete = 'RESTRICT', $onUpdate = 'RESTRICT') {
        $this->dropReference($columnName);
        $this->addReference($columnName, $refTable, $refColumn, $onDelete, $onUpdate);
        return $this;
    }

    /**
     * Sets the model to map the table to
     * @param \DBScribe\Row $model
     * @return \DBScribe\Table
     */
    public function setModel(Row $model) {
        $this->rowModel = $model;
        return $this;
    }

    /**
     * Fetches the model set for the table
     * @return DBScribe\Row
     */
    public function getModel() {
        return $this->rowModel;
    }

    /**
     * Defines the relationships of the table
     * @return void
     */
    private function defineRelationships() {
        $this->fetchColumns();
        $this->fetchReferences();
        $this->fetchBackReferences();
    }

    /**
     * Fetches the tables that reference this table, and their columns
     */
    private function fetchBackReferences() {
        $qry = "SELECT k.COLUMN_NAME as refColumn, k.TABLE_SCHEMA as refDB, k.TABLE_NAME as refTable,
			k.REFERENCED_COLUMN_NAME as columnName" .
                " FROM information_schema.KEY_COLUMN_USAGE k" .
                " WHERE k.TABLE_SCHEMA = '" . $this->connection->getDBName() .
                "' AND k.REFERENCED_TABLE_NAME = '" . $this->name . "'";

        $backRef = $this->connection->doPrepare($qry);
        if (is_bool($backRef))
            return;

        foreach ($backRef as &$info) {
            $name = $info['columnName'];
            unset($info['columnName']);
            $this->backReferences[$name][] = $info;
        }
    }

    /**
     * Fetches all tables and columns that this table references
     */
    private function fetchReferences() {
        $qry = "SELECT i.CONSTRAINT_NAME as constraintName, i.CONSTRAINT_TYPE as constraintType,
			j.COLUMN_NAME as columnName, j.REFERENCED_TABLE_SCHEMA as refDB, j.REFERENCED_TABLE_NAME as refTable,
			j.REFERENCED_COLUMN_NAME as refColumn, k.UPDATE_RULE as onUpdate, k.DELETE_RULE as onDelete" .
                " FROM information_schema.TABLE_CONSTRAINTS i" .
                " LEFT JOIN information_schema.KEY_COLUMN_USAGE j
                    ON i.CONSTRAINT_NAME = j.CONSTRAINT_NAME AND j.TABLE_SCHEMA = '" . $this->connection->getDBName() . "'
			AND j.TABLE_NAME = '" . $this->name . "'" .
                " LEFT JOIN information_schema.REFERENTIAL_CONSTRAINTS k
                    ON i.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND j.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
			AND k.TABLE_NAME = '" . $this->name . "'" .
                " WHERE i.TABLE_SCHEMA = '" . $this->connection->getDBName() . "'
								AND i.TABLE_NAME = '" . $this->name . "'";

        $define = $this->connection->doPrepare($qry);
        if (is_bool($define))
            return;

        foreach ($define as $info) {
            if (isset($info['constraintType']) && $info['constraintType'] === 'PRIMARY KEY') {
                if (isset($info['columnName']))
                    $this->primaryKey = $info['columnName'];
            } else if ($info['refTable']) {
                if (isset($info['constraintType']))
                    unset($info['constraintType']);
                if (isset($info['columnName'])) {
                    $name = $info['columnName'];
                    unset($info['columnName']);
                }
                $this->references[$name] = $info;
            }
        }
    }

    /**
     * Fetches all columns of the table and their information
     */
    private function fetchColumns() {
        $qry = 'SELECT c.column_name as colName, c.column_default as colDefault,
			c.is_nullable as nullable, c.column_type as colType, c.extra, c.column_key as colKey,
                        c.character_set_name as charset, c.collation_name as collation';
        $qry .= ', d.index_name as indexName';
        $qry .=' FROM INFORMATION_SCHEMA.COLUMNS c ' .
                'LEFT JOIN INFORMATION_SCHEMA.STATISTICS d'
                . ' ON c.column_name = d.column_name AND d.table_schema="' . $this->connection->getDBName() . '" AND d.table_name="' . $this->name . '" ' .
                'WHERE c.table_schema="' . $this->connection->getDBName() . '" AND c.table_name="' . $this->name . '"';

        $columns = $this->connection->doPrepare($qry);
        if (is_bool($columns))
            return;

        foreach ($columns as $column) {
            $this->columns[$column['colName']] = $column;
            if (in_array($column['colKey'], array('MUL', 'UNI', 'PRI', 'SPA', 'FUL'))) {
                $this->indexes[$column['colName']] = $column['indexName'];
            }
        }
    }

    public function getConstraintName($column) {
        if (array_key_exists($column, $this->references)) {
            return $this->references[$column]['constraintName'];
        }

        return null;
    }

    /**
     * Checks if the table exists
     * @return boolean
     */
    public function exists() {
        return (count($this->columns));
    }

    /**
     * Checks if a connection exists
     * @return boolean
     * @throws \Exception
     */
    private function checkReady() {
        if (!$this->connection)
            throw new \Exception('Invalid action. No connection found');

        return $this->exists();
    }

    /**
     * Inserts the given row(s) into the table<br />
     * Many rows can be inserted at once.
     * @param array $values Array with values \DBScribe\Row or array of [column => value]
     * @return \DBScribe\Table
     */
    public function insert(array $values) {
        if (!$this->checkReady())
            return false;

        $this->current = self::OP_INSERT;
        $this->query = 'INSERT INTO `' . $this->name . '` (';
        $columns = array();
        $noOfColumns = 0;
        foreach (array_values($values) as $ky => $row) {
            $rowArray = $this->checkModel($row, true);
            if ($ky === 0)
                $noOfColumns = count($rowArray);

            if (count($rowArray) !== $noOfColumns) {
                throw new \Exception('All rows must have the same number of columns in table "' . $this->name .
                '". Set others as null');
            }

            if (count($rowArray) === 0)
                throw new \Exception('You cannot insert an empty row into table "' . $this->name . '"');

            foreach ($rowArray as $column => &$value) {
                if (empty($value) && $value != 0)
                    continue;

                if (!in_array($column, $columns))
                    $columns[] = $column;
                $this->values[$ky][':' . $column] = $value;
            }
        }

        $this->query .= '`' . join('`, `', $columns) . '`';
        $this->query .= ') VALUES (';
        $this->query .= ':' . join(', :', $columns);
        $this->query .= ')';

        $this->multiple = true;
        $this->doPost = self::OP_INSERT;
        if ($this->delayExecute) {
            return $this;
        }

        return $this->execute();
    }

    /**
     * Sets the relationships into each row for future references
     * @todo Remove relationships from rows and use the one in the table instead
     */
    private function setRowRelationships() {
        if ($this->rowModel === NULL)
            $this->rowModel = new Row();

        $relationships = array();
        foreach ($this->references as $columnName => $info) {
            if ($info['constraintName'] == 'PRIMARY' || empty($info['refTable']))
                continue;
            $relationships[$info['refTable']][] = array(
                'column' => $columnName,
                'refColumn' => $info['refColumn'],
                'push' => false
            );
        }
        foreach ($this->backReferences as $columnName => $infoArray) {
            foreach ($infoArray as $info) {
                if (empty($info['refTable']))
                    continue;
                $relationships[$info['refTable']][] = array(
                    'column' => $columnName,
                    'refColumn' => $info['refColumn'],
                    'push' => true,
                );
            }
        }
        $this->rowModel->setRelationships($relationships);
    }

    private function prepareColumns(Table $table = null, $alias = null) {
        $ignoreJoins = false;
        if (!$table) {
            $table = $this;
            $ignoreJoins = true;
        }
        $return = '';
        if ($table->getModel() !== null && count($table->getModel()->toArray())) {
            $columns = array_keys($this->rowModel->toArray(true));
        }
        else {
            $columns = $table->getColumns(true);
        }

        foreach ($columns as $column) {
            if ($return)
                $return .= ', ';

            $return .= '`' . (($alias) ? $alias : $table->getName()) . '`.`' . $column . '`';
            if ($this->joins && !$ignoreJoins) {
                $return .= ' as ' . Util::_toCamel($table->getName()) . '_' . Util::_toCamel($column);
            }
            else if ($ignoreJoins) {
                $return .= ' as ' . Util::_toCamel($column);
            }
        }
        return $return;
    }

    /**
     * Selects rows from database
     * Many rows can be passed in as criteria
     * @param array $criteria Array with values \DBScribe\Row or array of [column => value]
     * @param int $return Indicates the type of result expected
     * @return \DBScribe\Table|ArrayCollection
     */
    public function select(array $criteria = array(), $return = Table::RETURN_MODEL) {
        if (!$this->checkReady()) {
            return ($this->delayExecute) ? $this : new ArrayCollection();
        }

        $this->setRowRelationships();
        $this->current = self::OP_SELECT;

        $this->query = 'SELECT ' . $this->prepareColumns();
        $this->query .= ' FROM `' . $this->name . '`' . $this->processJoins();
        $this->queryWhere($criteria);

        if ($this->groups) {
            $this->query .= ' GROUP BY ';
            foreach ($this->groups as $ky => $column) {
                if ($ky)
                    $this->query .= ', ';
                $this->query .= '`' . $this->name . '`.`' . $column . '`';
            }
        }

        if ($this->having) {
            $this->query .= ' HAVING ' . $this->having;
        }
        $this->return = $return;
        if ($this->delayExecute) {
            return $this;
        }
        return $this->execute();
    }

    private function queryWhere(array $criteria) {
        if (!empty($criteria))
            $this->where = true;

        foreach ($criteria as $ky => $row) {
            if ($ky == 0)
                $this->query .= ' WHERE (';
            else
                $this->query .= ' OR (';

            $rowArray = $this->checkModel($row);
            $cnt = 1;
            foreach ($rowArray as $column => $value) {
                if (!is_array($value)) {
                    $this->query .= '`' . $this->name . '`.`' . Util::camelTo_($column) . '` = ?';
                    if (count($rowArray) > $cnt)
                        $this->query .= ' AND ';
                    $this->values[] = $value;
                }
                else {
                    $this->query .= '`' . $this->name . '`.`' . Util::camelTo_($column) . '` IN (\'' . join('\', \'', $value) . '\')';
                }
                $cnt++;
            }
            $this->query .= ')';
        }
    }

    private function returnSelect($return) {
        if (!is_array($return)) {
            $return = array();
        }
        $forThis = $this->relationshipData = array();

        foreach ($return as &$ret) {
            $imm = array();
            foreach ($this->getColumns(true) as $col) {
                $imm[Util::_toCamel($col)] = @$ret[Util::_toCamel($col)];
                unset($ret[Util::_toCamel($col)]);
            }

            if ($this->getPrimaryKey() && !empty($imm[Util::_toCamel($this->getPrimaryKey())])) {
                $forThis[$imm[Util::_toCamel($this->getPrimaryKey())]] = $imm;
            }
            else
                $forThis[] = $imm;

            if (!empty($ret)) {
                $this->relationshipData[] = $ret;
            }
        }

        switch ($this->return) {
            case self::RETURN_JSON:
                $return = json_encode($forThis);
                break;
            case self::RETURN_MODEL:
                $return = $this->createReturnModels($forThis);
                break;
            case self::RETURN_DEFAULT:
                $return = $forThis;
                break;
        }

        return $return;
    }

    private function createReturnModels(array $forThis) {
        $rows = new ArrayCollection();

        foreach ($forThis as $valueArray) {
            $row = clone $this->rowModel;
            foreach ($valueArray as $name => $value) {
                if (method_exists($row, 'set' . $name))
                    $row->{'set' . $name}($value);
                else
                    $row->{$name} = $value;
            }
            $row->postFetch();
            $row->setTable($this);
            $rows->append($row);
        }

        return $rows;
    }

    /**
     * Select a column where it is LIKE the value, i.e. it contains the given
     * value     * 
     * @param string $column
     * @param mixed $value
     * @param boolean $logicalAnd Indicates whether to use logical AND (TRUE) or OR (FALSE)
     * @return \DBScribe\Table
     */
    public function like($column, $value, $logicalAnd = true) {
        $this->customWhere('`:TBL:`.`' . Util::camelTo_($column) . '` LIKE "' . $value . '"', $logicalAnd ? 'AND' : 'OR');
        return $this;
    }

    /**
     * Adds a custom query to the existing query. If no query exists, it serves as
     * the query.
     * @param string $custom
     * @param string $logicalConnector Logical operator to link the <i><b>custom where</b></i>
     * with the <i><b>regular where</b></i> if available
     * @param string $tablePlaceholder A string within the custom where to be 
     * replaced with the table name. Useful when a table prefix might have been 
     * used
     * @return \DBScribe\Table
     */
    public function customWhere($custom, $logicalConnector = 'AND', $tablePlaceholder = ':TBL:') {
        if (!$this->customWhere) {
            $this->customWhereJoin = $logicalConnector;
            $this->customWhere = trim(str_replace($tablePlaceholder, $this->name, $custom));
        }
        else {
            $this->customWhere .= ' ' . $logicalConnector . ' ' . trim(str_replace($tablePlaceholder, $this->name, $custom));
        }
        return $this;
    }

    /**
     * Group result by data in given column
     * @param string $columnName
     * @return \DBScribe\Table
     */
    public function groupBy($columnName) {
        $this->groups[] = $columnName;
        return $this;
    }

    /**
     * Fetch rows that fulfill the given condition
     * @param string $condition Ready-made query e.g `:TBL:`.`id` > 2
     * @return \DBScribe\Table
     */
    public function having($condition, $tablePlaceholder = ':TBL:') {
        $this->having = trim(str_replace($tablePlaceholder, $this->name, $condition));
        return $this;
    }

    /**
     * Fetch results that whose data in the given column is in the given array
     * of values
     * @param string $column
     * @param array $values
     * @param boolean $logicalAnd Indicates whether to join the in query to the
     * rest of the query with an AND (TRUE) or an OR (FALSE)
     * @return \DBScribe\Table
     */
    public function in($column, array $values, $logicalAnd = true) {
        $this->customWhere('`:TBL:`.`' . Util::camelTo_($column) . '` IN ("' . join('","', $values) . '")', $logicalAnd ? 'AND' : 'OR');
        return $this;
    }

    /**
     * Joins with the given table
     * @param string|Table $table
     * @param array $options Keys include [rowModel]
     */
    public function join($table, array $options = array()) {
        $this->joins[$table] = $options;
        return $this;
    }

    private function processJoins() {
        $this->joinQuery = '';
        $superStart = false;
        foreach ($this->joins as $table => $options) {
            if (!$relationship = $this->rowModel->getRelationship($table))
                continue;

            if (!is_object($table)) {
                $table = new Table($table, $this->connection);
            }
            $this->query .= ', ' . $this->prepareColumns($table, ($table->getName() == $this->name) ? 't' : null);

            $this->joinQuery .= ' LEFT OUTER JOIN `' . $table->getName() . '`' .
                    (($table->getName() == $this->name) ? ' t' : null);

            $started = false;
            foreach ($relationship as $ky => $rel) {
                if (($rel['push'] && isset($options['pull']) && @$options['push']) || (!$rel['push'] && isset($options['pull']) && !$options['pull']))
                    continue;

                if ($ky && $started)
                    $this->joinQuery .= 'OR ';
                if (!$started)
                    $this->joinQuery .= ' ON ';
                $started = true;
                $superStart = true;
                $this->joinQuery .= '`' . $this->name . '`.`' . $rel['column'] . '` = ' . (($table->getName() == $this->name) ? 't' : '`' . $table->getName() . '`') . '.`' . $rel['refColumn'] . '` ';

                if (isset($options['where'])) {
                    foreach ($options['where'] as $column => $value) {
                        $this->joinQuery .= 'AND `' . $table->getName() . '`.`' . Util::camelTo_($column) . '` = ? ';
                        $this->values[] = $value;
                    }
                }
            }
        }

        if ($this->joinQuery && !$superStart)
            throw new \Exception('Joined table(s) must have something in common with the current table "' . $this->name . '"');

        $this->joins = array();

        return $this->joinQuery;
    }

    /**
     * Checks the joined data for rows that have the value needed in a column
     * @param string $tableName
     * @param array $columns Key to value of column to value
     * @param \DBScribe\Row $object
     * @param array $options
     * @return \DBScribe\ArrayCollection
     */
    final public function seekJoin($tableName, array $columns, Row $object = null, array $options = array()) {
        if (!$this->joinQuery)
            return false;

        $prefix = $this->connection->getTablePrefix() . $tableName . '_';

        if (!$object) {
            $object = new Row();
        }

        $array = array();
        foreach ($this->relationshipData as $data) {
            foreach ($columns as $column => $value) {
                $compare = $prefix . $column;
                if ($data[$compare] === null)
                    continue;
                $found = true;
                if ((!is_array($value) && @$data[$compare] != $value) ||
                        (is_array($value) && !in_array(@$data[$compare], $value))) {
                    $found = false;
                }
                if (!$found)
                    break;
            }

            if ($found) {
                $d = array();
                foreach ($data as $col => $val) {
                    $d[str_replace($prefix, '', $col)] = $val;
                }

                $ob = clone $object;

                $array[] = $ob->populate($d);
            }
        }

        $this->parseWithOptions($array, $options);

        return count($array) ? new ArrayCollection($array) : false;
    }

    private function parseWithOptions(array &$array, array $options) {
        if (isset($options[0]['orderBy'])) {
            usort($array, function($a, $b) use($options) {
                if (is_array($options[0]['orderBy'])) {
                    foreach ($options[0]['orderBy'] as $order) {
                        $comp = is_array($order) ?
                                $this->compareOrder($order['position'], $a, $b) :
                                $this->compareOrder($order, $a, $b);
                        if ($comp) {
                            return $comp;
                        }
                    }
                }
                else {
                    return $this->compareOrder($options[0]['orderBy'], $a, $b);
                }
            });
        }

        $array = array_values($array);

        if (isset($options[0]['limit'])) {
            if (!isset($options[0]['limit']['start'])) {
                $options[0]['limit']['start'] = 0;
            }
            if (!isset($options[0]['limit']['count'])) {
                $options[0]['limit']['count'] = count($array) - (int) $options['0']['limit']['start'];
            }

            $array = array_slice($array, $options[0]['limit']['start'], $options[0]['limit']['count']);
        }

        return $array;
    }

    private function compareOrder($order, $a, $b) {
        $method = 'get' . ucfirst($order);
        if (method_exists($a, $method)) {
            $value1 = $a->$method();
            $value2 = $b->$method();
        }
        else {
            $value1 = $a->$order;
            $value2 = $b->$order;
        }

        return strcmp($value1, $value2);
    }

    /**
     * Checks if the row is a valid \DBScribe\Row row
     * @param array|object $row
     * @param boolean $preSave Indicates whether to call the presave function of the row
     * @throws \Exception
     * @return array|boolean
     */
    private function checkModel($row, $preSave = false) {
        if (!is_array($row) && !is_object($row))
            throw new \Exception('Each element of param $where must be an object of, or one that extends, "DBScribe\Row", or an array of [column => value]');

        if (empty($this->columns))
            return array();

        if (is_array($row)) {
            return $row;
        }
        elseif (is_object($row) && get_class($row) === 'DBScribe\Row' || in_array('DBScribe\Row', class_parents($row))) {
            if ($preSave)
                $row->preSave();

            $this->rowModelInUse = $row;
            return $row->toArray(($this->current !== self::OP_SELECT));
        }
    }

    /**
     * Counts the number of rows in the table based on a column
     * @param string $column The column to count
     * @return Int
     */
    public function count($column = '*', $criteria = array(), $return = Table::RETURN_MODEL) {
        $this->query = 'SELECT COUNT(' . Util::camelTo_($column) . ') as rows FROM `' . $this->name . '`';
        $this->queryWhere($criteria);

        $this->return = $return;
        if ($ret = $this->execute()) {
            return ($ret) ? $ret[0]['rows'] : 0;
        }
        return 0;
    }

    /**
     * Gets the distinct values of a column
     * @param string $column
     * @param array $criteria Array with values \DBScribe\Row or array of [column => value]
     * @return ArrayCollection
     */
    public function distinct($column, array $criteria = array(), $return = Table::RETURN_MODEL) {
        $this->current = self::OP_SELECT;
        $this->return = $return;
        $this->query = 'SELECT DISTINCT `' . $this->name . '`.`' . Util::camelTo_($column) . '` as ' . Util::_toCamel($column) . ' FROM `' . $this->name . '` ' . $this->joinQuery;
        $this->queryWhere($criteria);

        if ($this->groups) {
            $this->query .= ' GROUP BY ';
            foreach ($this->groups as $ky => $column) {
                if ($ky)
                    $this->query .= ', ';
                $this->query .= '`' . $this->name . '`.`' . $column . '`';
            }
        }

        if ($this->having) {
            $this->query .= ' HAVING ' . $this->having;
        }
        return $this->execute();
    }

    /**
     * Updates the given row(s) in the table<br />
     * Many rows can be updated at once.
     * @param array $values Array with values \DBScribe\Row or array of [column => value]
     * @param string $whereColumn Column name to check. Default is the id column
     * @todo Allow multiple columns as criteria where
     * @return \DBScribe\Table
     */
    public function update(array $values, $whereColumn = 'id') {
        if (!$this->checkReady())
            return false;
        $this->current = self::OP_UPDATE;
        $this->query = 'UPDATE `' . $this->name . '` SET ';

        if (!is_array($whereColumn))
            $whereColumn = array($whereColumn);

        foreach ($whereColumn as &$col) {
            $col = Util::camelTo_($col);
        }

        $nColumns = 0;
        $columns = array();
        foreach ($values as $ky => $row) {
            $rowArray = $this->checkModel($row, true);
            if ($ky == 0)
                $nColumns = array_keys($rowArray);
            if (count($rowArray) !== count($nColumns))
                throw new \Exception('All rows must have the same number of columns in table "' . $this->name .
                '". Set others as null');

            if (count($rowArray) === 0)
                throw new \Exception('You cannot insert an empty row into table "' . $this->name . '"');

            $cnt = 1;
            foreach ($rowArray as $column => &$value) {
                if (empty($value) && $value != 0)
                    continue;

                if ($cnt > 1 && !in_array($column, $nColumns)) {
                    throw new \Exception('All rows must have the same column names.');
                }

                if ($this->getPrimaryKey() == $column) {
                    if (in_array($this->getPrimaryKey(), $whereColumn)) {
                        $this->values[$ky][':' . $column] = $value;
                    }
                    continue;
                }

                $this->values[$ky][':' . $column] = $value;
                if (in_array($column, array_merge($columns, $whereColumn))) {
                    $cnt++;
                    continue;
                }

                $this->query .= '`' . $column . '` = :' . $column;

                if (count($rowArray) > $cnt)
                    $this->query .= ', ';
                $columns[] = $column;

                $cnt++;
            }

            foreach ($whereColumn as $column) {
                $this->value[$ky][':' . $column] = $rowArray[$column];
            }
        }

        $this->query = (substr($this->query, strlen($this->query) - 2) === ', ') ?
                substr($this->query, 0, strlen($this->query) - 2) : $this->query;

        $this->query .= ' WHERE ';
        foreach ($whereColumn as $key => $where) {
            $where = Util::camelTo_($where);

            if ($key)
                $this->query .= ' AND ';
            $this->query .= '`' . $where . '`=:' . $where;
        }

        $this->multiple = true;
        $this->doPost = self::OP_UPDATE;
        if ($this->delayExecute) {
            return $this;
        }
        return $this->execute();
    }

    /**
     * Updates rows that exist and creates those that don't
     * @param array $values
     * @param string|integer|array $whereColumn
     * @return boolean
     */
    public function upsert(array $values, $whereColumn = 'id') {
        $select = $existing = array();
        if (!is_array($whereColumn))
            $whereColumn = array($whereColumn);

        foreach ($values as $ky => &$vals) {
            $val = $this->checkModel($vals);
            $vals = $this->checkModel($vals, true);
            foreach ($whereColumn as $column) {
                $select[$ky][$column] = $val[$column];
            }
        }
        foreach ($this->select($select)->execute() as $row) {
            foreach ($whereColumn as $column) {
                $method = 'get' . $column;
                if (method_exists($row, $method)) {
                    $existing[] = $row->$method();
                }
                else {
                    $existing[] = $row->$column;
                }
            }
        }
        $update = $insert = array();
        foreach ($values as $ky => $vals) {
            $up = true;
            foreach ($whereColumn as $where) {
                if (!in_array($vals[$where], $existing)) {
                    $up = false;
                    break;
                }
            }
            if ($up) {
                $update[] = $vals;
            }
            else {
                if (!isset($vals[$this->getPrimaryKey()])) {
                    //@todo check type of primary key to determine what to assign
                    // to it
                    $vals[$this->getPrimaryKey()] = Util::createGUID();
                }
                $insert[] = $vals;
            }
        }

        if (!empty($update)) {
            $return = $this->update($update, $whereColumn);
        }
        if ((($update && $return) || !$update) && !empty($insert)) {
            $return = $this->insert(array_values($insert));
        }
        return $return;
    }

    /**
     * Deletes the given row(s) in the table<br />
     * Many rows can be deleted at once.
     * @param array $criteria Array with values \DBScribe\Row or values of [column => value]
     * @return \DBScribe\Table
     */
    public function delete(array $criteria = array()) {
        if (!$this->checkReady())
            return false;

        $this->current = self::OP_DELETE;
        $this->query = 'DELETE FROM `' . $this->name . '`';
        if (!empty($criteria))
            $this->query .= ' WHERE ';
        foreach ($criteria as $ky => $row) {
            $rowArray = $this->checkModel($row, false);
            $cnt = 0;
            foreach ($rowArray as $column => $value) {
                if (!is_object($value) && $value === null) {
                    continue;
                }

                if ($cnt)
                    $this->query .= ' AND ';

                $this->query .= '`' . $column . '` = ?';
                $this->values[] = $value;
                $cnt++;
            }

            if ($ky < (count($criteria) - 1))
                $this->query .= ' OR ';
        }

        if ($this->delayExecute) {
            return $this;
        }
        return $this->execute();
    }

    /**
     * Orders the returned rows
     * @param string $column
     * @param string $direction One of \DBScribe\Table::ORDER_ASC or \DBScribe\Table::ORDER_DESC
     * @return \DBScribe\Table
     */
    public function orderBy($column, $direction = Table::ORDER_ASC) {
        $this->orderBy[] = '`' . Util::camelTo_($column) . '` ' . $direction;
        return $this;
    }

    /**
     * Limits the number of rows to return
     * @param int $count No of rows to return
     * @param int $start Row no to start from
     * @return \DBScribe\Table
     */
    public function limit($count, $start = 0) {
        $this->limit = 'LIMIT ' . $start . ', ' . $count;
        return $this;
    }

    /**
     * Indicates whether to delay database operation until method execute() is called
     * @param boolean $delay
     * @return \DBScribe\Table
     */
    public function delayExecute($delay = true) {
        $this->delayExecute = $delay;
        return $this;
    }

    /**
     * Executes the delayed database operation
     * @return mixed
     */
    public function execute() {
        if (!$this->checkReady()) {
            if ($this->current === self::OP_SELECT) {
                return new ArrayCollection();
            }

            return false;
        }

        if (!empty($this->customWhere)) {
            if ($this->where) {
                $this->query .= ' ' . $this->customWhereJoin . ' ' . $this->customWhere;
            }
            else {
                $this->query .= ' WHERE ' . $this->customWhere;
            }
        }

        if (!empty($this->orderBy)) {
            $this->query .= ' ORDER BY ';
            foreach ($this->orderBy as $ky => $order) {
                $this->query .= $order;
                if ($ky < (count($this->orderBy) - 1))
                    $this->query .= ', ';
            }
        }

        $this->query .= ' ' . $this->limit;

        if (($this->current === self::OP_SELECT || !empty($this->customWhere)) && !stristr($this->query, ' from')) {
            $this->query .= ' FROM `' . $this->name . '`';
        }

        $model = ($this->return) ? $this->rowModel : null;

        $result = $this->connection->doPrepare($this->query, $this->values, array(
            'multipleRows' => $this->multiple,
            'model' => $model
        ));

        if ($this->current === self::OP_SELECT) {
            $result = $this->returnSelect($result);
        }

        $this->resetQuery();
        return $result;
    }

    private function resetQuery() {
        $this->query = null;
        $this->values = null;
        $this->orderBy = array();
        $this->limit = null;
        $this->customWhere = null;
        $this->where = false;
        $this->having = null;
        $this->groups = array();
        $this->current = null;
        $this->multiple = false;
        $this->return = self::RETURN_MODEL;
    }

    /**
     * Fetches the autogenerated id of the last insert statement, if primary key is autogenerated
     * @return mixed
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

}
