# General
SQL_Gen.php holds classes and functions for making mySQL queries automatically for easier development and maintenance.

There's a lot of options for making the queries.
Functions with the prefix make_ return a string, while functions without return the object.
The to_string method is called both implicitly when passing a Query object to a creator function, or explicitly.

PHP objects are treated very similarly to JS object functions. However, the operator "->" is used instead of "." for accessing a class' mathods and attributes.

# Usage
All of the following code snippets are syntactically correct:

    //$sel is an object
    $sel = new SELECT();
    $sel->select(distinct, arg1, arg2, ... arg_n);

    //$sel is a string, and any more functions may not be called on it
    $sel = (new SELECT())->make_select(distinct, arg1, arg2, ... arg_n);

    //$sel is an object
    $sel = (new SELECT())->select(distinct, arg1, arg2, ... arg_n);

    //$sel is a string
    $sel = (new SELECT())->select(distinct, arg1, arg2, ... arg_n)->to_string();

Some improvements for output readability are needed.
