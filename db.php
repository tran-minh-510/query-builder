<?php
require_once "./connect.php";

class DB
{
    private $is_select = false;
    private $is_create = false;
    private $is_update = false;
    private $is_delete = false;
    private $is_insert = false;
    private $columns;
    private $table_name;
    private $joins;
    private $wheres;
    private $group_by;
    private $havings;
    private $order_by;
    private $limit;
    private $creat_column = [];
    private $update_column = [];
    private $insert_column = [];
    public function __construct($tableName)
    {
        $this->table_name = $tableName;
    }

    public static function table($tableName)
    {
        return new self($tableName);
    }

    public function select($columns = '')
    {
        if (!$this->is_create || !$this->is_update || !$this->is_delete || !$this->is_insert) {
            $this->is_select = true;
            if (empty($columns) || trim($columns) === '*') {
                $this->columns = null;
            } else {
                $this->columns = is_array($columns) ? $columns : func_get_args();
            }
        } else {
            return false;
        }
        return $this;
    }

    public function create($column, $type)
    {
        if ($this->is_select || $this->is_insert || $this->is_update || $this->is_delete || !isset($this->table_name) || empty($this->table_name) || empty($column) || empty($type)) {
            return false;
        } else {
            $this->is_create = true;
            $this->creat_column[] = [$column, $type];
        }
        return $this;
    }

    public function update($column, $value)
    {
        if ($this->is_select || $this->is_insert || $this->is_create || $this->is_delete || !isset($this->table_name) || empty($this->table_name) || empty($column) || empty($value)) {
            return false;
        } else {
            $this->is_update = true;
            $this->update_column[] = [$column, $value];
        }
        return $this;
    }

    public function delete()
    {
        if ($this->is_select || $this->is_insert || $this->is_create || $this->is_update) {
            return false;
        } else {
            $this->is_delete = true;
        }
        return $this;
    }

    public function insert($column, $value)
    {
        if ($this->is_select || $this->is_update || $this->is_create || $this->is_delete || !isset($this->table_name) || empty($this->table_name) || empty($column) || empty($value)) {
            return false;
        } else {
            $this->is_insert = true;
            $this->insert_column[] = [$column => $value];
        }
        return $this;
    }

    public function join($table, $first, $operator, $second, $type = 'inner')
    {
        $this->joins[] = [$table, $first, $operator, $second, $type];
        return $this;
    }

    public function leftJoin($table, $first, $operator, $second)
    {
        $this->joins[] = [$table, $first, $operator, $second, 'left'];
        return $this;
    }

    public function rightJoin($table, $first, $operator, $second)
    {
        $this->joins[] = [$table, $first, $operator, $second, 'right'];
        return $this;
    }

    public function where($column, $operator, $value, $type = 'and')
    {
        $this->wheres[] = [$column, $operator, $value, $type];
        return $this;
    }

    public function orWhere($column, $operator, $value)
    {
        $this->where($column, $operator, $value, 'or');
        return $this;
    }

    public function groupBy($columns)
    {
        $this->group_by = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function having($column, $operator, $value, $type = 'and')
    {
        $this->havings[] = [$column, $operator, $value, $type];
        return $this;
    }

    public function orHaving($column, $operator, $value)
    {
        $this->having($column, $operator, $value, 'or');
        return $this;
    }

    public function orderBy($column, $order = 'asc')
    {
        $this->order_by[] = [$column, $order];
        return $this;
    }

    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public function get()
    {
        if (!isset($this->table_name) || empty($this->table_name)) {
            return false;
        }
        if (!$this->is_select || !$this->is_insert || !$this->is_update || !$this->is_delete) {
            $sql = "CREATE TABLE $this->table_name (";
            foreach ($this->creat_column as $key => $column) {
                $sql .= "$column[0] $column[1]";
                if ($key < count($this->creat_column) - 1) {
                    $sql .= ' , ';
                }
            }
            $sql .= ")";
        }
        if (!$this->is_create || !$this->is_insert || !$this->is_update || !$this->is_delete) {
            $sql = 'SELECT ';
            if (isset($this->columns) && is_array($this->columns)) {
                $sql .= implode(',', $this->columns);
            } else {
                $sql .= '*';
            }
            $sql .= ' FROM ' . $this->table_name;
        }
        if (!$this->is_create || !$this->is_insert || !$this->is_select || !$this->is_delete) {
            $sql = "UPDATE $this->table_name SET ";
            foreach ($this->update_column as $key => $column) {
                $sql .= "$column[0] = $column[1]";
                if ($key < count($this->update_column) - 1) {
                    $sql .= ' , ';
                }
            }
        }
        if (!$this->is_create || !$this->is_insert || !$this->is_select || !$this->is_update) {
            $sql = "DELETE FROM $this->table_name ";
        }
        if (!$this->is_create || !$this->is_delete || !$this->is_select || !$this->is_update) {
            $arr = call_user_func_array('array_merge',$this->insert_column);
            $arr_key = array_keys($arr);
            $arr_value = array_values($arr);
            $sql = "INSERT INTO $this->table_name (".implode(',',$arr_key).") VALUES (".implode(',',$arr_value).")";
        }
        if (isset($this->joins) && is_array($this->joins)) {
            foreach ($this->joins as $join) {
                switch (trim(strtolower($join[4]))) {
                    case 'inner':
                        $sql .= ' INNER JOIN';
                        break;
                    case 'left':
                        $sql .= ' LEFT JOIN';
                        break;
                    case 'right':
                        $sql .= ' RIGHT JOIN';
                        break;
                    default:
                        $sql .= ' INNER JOIN';
                        break;
                }
                $sql .= " $join[0] ON $join[1] $join[2] $join[3]";
            }
        }

        if (isset($this->wheres) && is_array($this->wheres)) {
            $sql .= ' WHERE';
            foreach ($this->wheres as $index => $where) {
                $sql .= " $where[0] $where[1] $where[2] ";
                if ($index < count($this->wheres) - 1) {
                    $sql .= strtoupper($where[3]);
                }
            }
        }

        if (isset($this->group_by) && is_array($this->group_by)) {
            $sql .= ' GROUP BY ' . implode(',', $this->group_by);
        }

        if (isset($this->havings) && is_array($this->havings)) {
            $sql .= ' HAVING';
            foreach ($this->havings as $index => $having) {
                $sql .= " $having[0] $having[1] $having[2] ";
                if ($index < count($this->havings) - 1) {
                    $sql .= strtoupper($having[3]);
                }
            }
        }

        if (isset($this->order_by) && is_array($this->order_by)) {
            $sql .= ' ORDER BY ';
            foreach ($this->order_by as $index => $order) {
                $sql .= $order[0];
                $sql .= " ";
                $sql .= strtoupper($order[1]);
                if ($index < count($this->order_by) - 1) {
                    $sql .= " , ";
                }
            }
        }

        if (isset($this->limit)) {
            $sql .= " LIMIT ";
            $sql .= $this->limit;
        }
        return $sql;
    }
}
