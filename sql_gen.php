<pre><?php
/* 
 Author : Ben Blease
 Date   : 9/3/17
 
 Generate SQL code using a class structure 
 Cleaner code and better syntax checking
 Note: the "..." operator, or "splat" operator, allows for an arbitrary number of arguments to be passed to the function.
*/

require_once("syntax_check.php");

//allow debugging even if the file isn't included
define("debug", true);
define("non_distinct", false);
define("distinct", true);
define("temp", true);
define("perma", false);

abstract class Query {
	/* Convert the query to a string */
 	abstract public function to_string($offset = 0);
 	
 	/* Check the query for potential syntax errors */
	abstract public function check_syntax_errors($check); //TODO grow for basic name and boolean checking
}

/* -- SQL Generator -- */

/*
 Populate a list (in string form), with an arbitrary number of values
*/
function populate($args){
	$out = "";
	foreach($args as $k=>$field){
		if (is_subclass_of($field, "Query"))
			$field = $field->to_string();
		elseif (gettype($field) != "string")
			$field = strval($field);
		if ($k < count($args) - 1){
			$out .= $field.", ";
			if (strlen($field) > 20){
				$out .= "\n";
			}
		}
 		else
 			$out .= $field;
 	}
 	return $out;
 }

/* 
 Create insert statement
*/
class INSERT extends Query {
 	private $insert;

 	public function __construct(){
 		$this->insert = null;
 	}

 	/*
 	 Make the insert statement, values may be either strings or Query objects
 	*/
 	public function make_insert($table, $arg, $values = null){
 		$this->insert = "INSERT INTO ".$table;
 		if ($values != null)
 			$this->insert .= " VALUES ".$values;

 		if (is_subclass_of($arg, "Query"))
 			$arg = $arg->to_string();

 		$this->insert .= " ".$arg.";\n";
 		return $this->insert;
 	}

 	public function insert($table, $arg, $values = null){
 		$this->make_insert($values, $table, $arg);
 		return $this;
 	}

 	public function to_string($offset = 0){
 		$out = str_repeat("\t", $offset).$insert;
 		if (debug)
 			$this->check_syntax_errors($out);
 		return $out;
 	}

 	public function check_syntax_errors($check){
 		check_parens($check);
 	}
}

/*
 SELECT statement with support for grouping and ordering
*/
class SELECT extends Query {
	private $delete;
 	private $select;
 	private $from;
 	private $where;
 	private $group_by;
 	private $order_by;
 	private $alias;

 	/*
 	 Alias is assigned to the statement at startup
 	*/
 	public function __construct($a = null){
 		$this->delete = null;
 		$this->select = null;
 		$this->from = null;
 		$this->where = null;
 		$this->group_by = null;
 		$this->order_by = null;
 		$this->alias = $a;
 	}

 	/* Make a delete statement instead of a select statement */
 	public function make_delete(){
 		$this->delete = "DELETE ";
 		return $this->delete;
 	}

 	/* Make select statement */
 	public function make_select($distinct = false, ... $args){
 		$stat = "SELECT ";
 		if ($distinct) $stat .= "DISTINCT ";
 		$this->select = $stat.populate($args);
 		return $this->select;
 	}	

 	/*
 	Create a from statement
 	Arguments may either be strings or query objects
 	*/
 	public function make_from(... $args){
 		foreach($args as $a){
 			if (is_subclass_of($a, "Query"))
 				$a = $a->to_string(0);
 		}
 		$out = populate($args);
 		$this->from = "FROM ".$out;
 		return $this->from;
 	}

 	/* Make group by statement attached to the select clause */
 	public function make_group_by(... $args){
 		$this->group_by = "GROUP BY ".populate($args);
 		return $this->group_by;
 	}

 	/* Make order by statement attached to the select clause */
 	public function make_order_by(... $args){
 		$this->order_by = "ORDER BY ".populate($args);
 		return $this->order_by;
 	}

 	/*
 	 Make the where statement from boolean arguments
 	*/
 	public function make_where(... $args){
 		$out = "WHERE ";
 		foreach($args as $k=>$a){
 			if ($k == count($args) - 1) $out .= $a;
 			else $out .= $a."\n";
 		}

 		$this->where = $out;
 		return $this->where;
 	}

 	public function delete(){
 		$this->make_delete();
 		return $this;
 	}

 	public function select($distinct = false, ... $args){
 		$this->make_select($distinct, ...$args);
 		return $this;
 	}

 	public function from(... $args){
 		$this->make_from(...$args);
 		return $this;
 	}

 	public function group_by(... $args){
 		$this->make_group_by(...$args);
 		return $this;
 	}

 	public function order_by(... $args){
 		$this->make_order_by(...$args);
 		return $this;
 	}

 	public function where(... $args){
 		$this->make_where(...$args);
 		return $this;
 	}

 	/* Add an alias to the statement after construction */
 	public function alias($a){
 		$this->alias = $a;
 		return $this;
 	}

 	public function to_string($offset = 0){
 		$ws = str_repeat("\t", $offset);
 		$out = "";
 		if ($this->delete != null) $out .= $ws.$this->delete."\n";
 		if ($this->select != null) $out .= $ws.$this->select."\n";
 		if ($this->from != null) $out .= $ws.$this->from."\n";
 		if ($this->where != null) $out .= $ws.$this->where."\n";
 		if ($this->group_by != null) $out .= $ws.$this->group_by."\n";
 		if ($this->order_by != null) $out .= $ws.$this->order_by."\n";
 		if ($this->alias != null) $out = "(\n".$out."\n) ".$this->alias;
 		if (debug)
 			$this->check_syntax_errors($out);
 		return $out;
 	}

 	public function check_syntax_errors($check){
 		check_parens($check);
 	}

 	public function terminate($delimeter = ";"){
 		return $this->to_string(0).$delimeter."\n";
 	}
}

/*
 CREATE statement, TEMPORARY capable
*/
class CREATE extends Query {
	private $create;

	public function __construct(){
		$this->create = null;
	}

	/* Make create statement, schema is broken down into name-type pairs */
	public function make_create($temp, $table, $mem = null, $exp = null, ... $schema){
		$this->create = "CREATE ";
		if ($temp)
			$this->create .= "TEMPORARY ";
		$this->create .= "TABLE ".$table;

		if ($schema != null)
			$this->create .= " ".populate($schema);

		if ($mem != null)
			$this->create .= " $mem";

		if ($exp != null){
			if (is_subclass_of($exp, "Query"))
				$exp = $exp->to_string(0);
			if (strlen($exp) > 40)
				$this->create .= "\n";
			$this->create .= " ".$exp;
		}
		
		$this->create .= ";\n";
		return $this->create;
	}

	public function to_string($offset = 0){
		$out = str_repeat("\t", $offset).$this->create;
		if (debug)
			$this->check_syntax_errors($out);
		return $out;
	}

	public function check_syntax_errors($check){
		check_parens($check);
	}
}

/*
 Inner Join queries, supports chaining inner joins
*/
class INNER_JOIN extends Query {
	private $join;

	public function __construct(){
		$this->join = null;
	}

	/* Make inner join, arg1/2 may be either strings or query objects */
	public function make_join($arg1, $arg2, ... $on){
		if (is_subclass_of($arg1, "Query"))
			$arg1 = $arg1->to_string(0);
		if (is_subclass_of($arg2, "Query"))
			$arg2 = $arg2->to_string(0);

		$this->join .= $arg1."\n";
		$this->join .= "\nINNER JOIN\n";
		$this->join .= $arg2."\n";
		$this->join .= "ON";
		foreach($on as $o)
			$this->join .= " ".$o;
		return $this->join;
	}

	public function join($arg1, $arg2, ... $on){
		$this->make_join($arg1, $arg2, ...$on);
		return $this;
	}

	public function to_string($offset = 0){
		$out = str_repeat("\t", $offset).$this->join;
		if (debug)
			$this->check_syntax_errors($out);
		return $out;
	}

	public function check_syntax_errors($check){
		check_parens($check);
	}

	/* Terminate the expression with a delimeter (usualy ';') */
	public function terminate($delimeter = ";"){
		return $this->to_string(0).$delimeter;
	}


}

/* 
 Variable assignment expression
*/
class SET extends Query {
	private $set;

	public function __construct(){
		$this->set = null;
	}

	/* Set the value of a variable to the specified value */
	public function make_set($var, $val){
		$this->set = "SET ".$var." := ".$val.";\n";
		return $this->set;
	}

	public function set($var, $val){
		$this->make_set($var, $val);
		return $this;
	}

	public function to_string($offset = 0){
		$out = str_repeat("\t", $offset).$this->set;
		if (debug)
			$this->check_syntax_errors($out);
		return $out;
	}

	public function check_syntax_errors($check){
		check_parens($check);
	}
}

/*
 Set operations between queries.
 e.g. UNION, INTERSECT, etc.
 Supports chaining an arbitrary number of 
*/
class SET_OP extends Query {
	private $op;

	/* Convert the arguments to strings if they're objects */
	private function convert($args){
		$out = [];
		foreach($args as $a){
			if (is_subclass_of($a, "Query"))
				$out[] = $a->to_string(0);
			else
				$out[] = $a;
		}
		return $out;
	}

	public function __construct(){
		$this->op = null;
	}

	/* 
	 Make union between n sets 
	 args may be either query objects or strings
	*/
	public function make_union(... $args){
		$args = $this->convert($args);
		$this->op = implode("\n\nUNION\n\n", $args);
		return $this->op;
	}

	/* Make intersection between two sets */
	public function make_intersect(... $args){
		$this->convert($args);
		$this->op = implode("\n\nINTERSECT\n\n", $args);
		return $this->op;
	}

	/* Make the natural join between two sets */
	public function make_natural_join(... $args){
		$this->convert($args);
		$this->op = implode("\n\nNATURAL JOIN\n\n", $args);
		return $this->op;
	}

	public function to_string($offset = 0){
		$out = str_repeat("\t", $offset).$this->op;
		if (debug)
			$this->check_syntax_errors($out);
		return $out;
	}

	public function check_syntax_errors($check){
		check_parens($check);
	}

	public function terminate($delimeter = ";"){
		return $this->to_string(0).$delimeter;
	}
}

/* 
 EXISTS statement
*/
class EXISTS extends Query {
	private $exists;

	/* Make exist statement, arg can be either Query object or string */
	public function make_exists($arg){
		if (is_subclass_of($arg, "Query")) $arg = $arg->to_string(0);
		$this->exists = "EXISTS (".$arg.")";
		return $this->exists;
	}

	public function to_string($offset = 0){
		$out = str_repeat("\t", $offset).$this->exists;
		if (debug)
			$this->check_syntax_errors($out);
		return $out;
	}

	public function check_syntax_errors($check){
		check_parens($check);
	}
}

class DROP extends Query{
	private $drop;

	public function make_drop($table){
		$this->drop = "DROP TABLE ".$table.";\n";
		return $this->drop;
	}

	public function to_string($offset = 0){
		$out = str_repeat("\t", $offset).$this->exists;
		if (debug)
			$this->check_syntax_errors($out);
		return $out;
	}

	public function check_syntax_errors($check){
		check_parens($check);
	}
}
?>