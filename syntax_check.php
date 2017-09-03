<?php
/* 
 Author : Ben Blease
 Date   : 9/3/17
 
 Check syntax for unopen/unclosed parentheses, quotes, and backticks.
 The default SQL syntax checker does not include line/char positions or descriptions for basic syntax errors
*/

/*
 Make sure all parentheses are closed in the query
*/
function check_parens($str){
	$line = 0;
	$char = -1;
	$q = new Queue();
	$err = false;
	//iterate through the string and add to the queue when needed
	for($i = 0; $i < strlen($str); $i++){
		$char++;
		if ($str[$i] == "\n"){
			$char = -1;
			$line++;
		}
		if ($str[$i] == '('){
			$q->push(new LineChar($line, $char, $str[$i]));
			continue;
		}

		if (($str[$i] == "`" || $str[$i] == "'") && $q->c_isEmpty($str[$i])){
			$q->push(new LineChar($line, $char, $str[$i]));
			continue;
		}

		if (($str[$i] == ')' || 
			$str[$i] == "`" ||
			$str[$i] == "'") && 
			!$q->c_isEmpty($str[$i])){
			$q->c_pop($str[$i]);
			continue;
		}


		if ($str[$i] == ')' || 
			$str[$i] == "`" ||
			$str[$i] == "'"){
			$err = true;
			echo "Unopened ".$str[$i]." at ".$line.", ".$char."\n";
		}
	}
	
	if (!$q->isEmpty()) $err = true;
	while(!$q->isEmpty()){
		$top = $q->pop();
		echo "Unclosed ".$top->val." at ".$top->line.", ".$top->char."<br>";
	}
	if ($err) echo "Examined\n$str<br>";
}

/*
 Queue for checking ) ` ' open and close pairs
*/
class Queue{
	private $arr;

	public function __construct(){
		$this->arr = array();
	}

	public function push($e){
		$this->arr[] = $e;
	}

	/*
	 Pops the first element with the character $c
	*/
	public function c_pop($c){
		if ($c == ')') $c = '(';
		$i = count($this->arr) - 1;
		while($c != null && $i >= 0 && $this->arr[$i]->val != $c)
			$i--;
		$res = $this->arr[$i];
		unset($this->arr[$i]);
		$this->arr = array_values($this->arr);
		return $res;
	}

	public function pop(){
		return array_pop($this->arr);
	}

	public function isEmpty(){
		return (count($this->arr) == 0);
	}

	public function c_isEmpty($c){
		if ($c == ')') $c = '(';
		foreach($this->arr as $e)
			if ($e->val == $c)
				return false;
		return true;
	}
}

/*
 Store the current line, char position, and value for the checker
*/
class LineChar{
	public $line;
	public $char;
	public $val;

	public function __construct($l, $c, $v){
		$this->line = $l;
		$this->char = $c;
		$this->val = $v;
	}
}
?>