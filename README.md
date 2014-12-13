MySQL-PDO-Database-Class (DB)
=============================

Freetuts.org "DB" class is a class that offers MySQL PDO connection and SQL 
abstraction functions for easier working with your database. 

On our web site you can read our tutorial that is explaining how to use this class step by step:  
[Click to learn](http://www.freetuts.org/tutorial/view?id=1)

Crash course
============

First thing that you should know about this class is that it is meant to be simple to use. This class is not meant to provide you will all cool features that modern frameworks have, but it is still very good and useful tool that can reduce the time of your development work considerably. For example, in many cases you will be able to reduce the database manipulation code you need to write from 20 lines to just few.

DB class provides you with 12 public static methods that you can use to work with your database. They can be divided into 3 groups:

1. First group is made from get() methods that are doing all kinds of SELECT queries plus join() and search() methods. For example with getAll() you can get all records from the specified table, or with join() you can join 2 tables using MySQL JOIN's.
2. Second group is represented by save() method that can do both INSERT and UPDATE using internal create() and update() methods.
3. And third group are delete() methods that are doing DELETE queries.

Let us introduce these methods to you:

### get() method:

Queries the given table by provided parameters. It can use WHERE clause to specify the selection.

*Required parameters:*

- $columns - you need to pass the names of the columns you want to select, you can pass them as an array or as an string with comma-separated values. Also if you want to select all columns in table you can pass *. Examples: `$columns = array('id', 'name');` `$columns = 'id, name';` `$columns = " * ";`

*Optional parameters:*

- *$where* - you can specify the WHERE part of the query using an associative array, where keys will be the string parts of the statements, like: `'name ='`, and array values will be values for those conditions.  
Example: `['id >' => $id, ' AND price<' => $price]` will produce: `WHERE id > :value1 AND price < :value2`, where :value1 and :value2 will be replaced with bound parameters.

- *$order* - you can specify the ORDER BY clause.

- *$limit* - you can specify the LIMIT clause.

- *$offset* - you can specify the OFFSET part of the LIMIT clause.


### getAll() method:

Selects everything from the given table.

*Required parameters*: none.

*Optional parameters*:

- *$where* - see get() method for explanation.

- *$order*

- *$limit*

- *$offset*


### getById() method:

Queries the given table by provided $id value. Id is considered to be an integer id value.

*Required parameters*: 

- *$id* - you need to pass the id as a criteria for selection.

*Optional parameters*:

- *$columns* - column names you want to select, ( in case you do not want all *). You can write them as an array or string with comma-separated values.  
Examples: `$columns = array('id', 'name');` `$columns = 'id, name';`


### getGrouped() method:

Queries the given table and groups the results using the GROUP BY clause. User can use WHERE and HAVING too.

*Required parameters*: 

- *$columns* - you need to pass the names of the columns you want to select, you can pass them as an array or as an string with comma-separated values. Also if you want to select all columns in table you can pass *.  
Examples: `$columns = array('id', 'name');` `$columns = 'id, name';` `$columns = '*';`

- *$group* - you have to pass the name of the column you are grouping by your results.

*Optional parameters*:

- *$where* - see get() method for explanation.

- *$having* - you can specify the HAVING part of the query using an associative array, where keys will be the string parts of the statements, like: `'SUM(tax) <='`, and array values will be values for those conditions.  
Example: `['SUM(tax) <' => $tax,' AND SUM(price) >' => $price]` will produce: `HAVING tax < :value1 AND price > :value2` , where :value1 and :value2 will be replaced with bound parameters.

- *$order*

- *$limit*

- *$offset*


### join() method:

Queries the given table using the JOIN clause. Users can specify type of JOIN, default is INNER.

*Required parameters*: 

- *$table* - you need to pass the name of the table you are joining and the kind of the join as an comma-separated string. Left table will be the one of the current model you are using, and the right table will be the one you specify using this parameter. This means that the left table will be used in FROM part of the query and second  will be used in INNER JOIN part of the query. If you need other type of joins, just  add it as second value of the table string. Example: Lets say we are working with Author model, that means author table. If we want to join News table with the current one, we would write our $table part of the query like this : `$table = 'news, LEFT';`. This will join author and news tables using LEFT JOIN. If you want INNER JOIN, you do not have to write the kind of the join, since INNER join is the default one. In that case, this example would be: `$table = 'news';`.

- *$columns* - see getGrouped() for explanation.

- *$on* - This is the ON part of the join query. You write it as a string. Example: `$on = 'country.code = country_language.country_code';`

*Optional parameters*:

- *$where*

- *$order*

- *$limit*

- *$offset*


### search() method:

Searches the given table by provided parameters. You can use WHERE clause to specify the selection.

*Required parameters*: 

- *$columns* - see getGrouped() for explanation.

*Optional parameters*:

- *$where*

- *$order*

- *$limit*

- *$offset*


### getCount() method:

Counts the number of rows in specified table by specified conditions. THIS is COUNT() abstraction method.

*Required parameters*: 

- *$columns*

*Optional parameters*:

- *$where*


### count() method:

Counts the number of affected rows by get*() methods, as well as join and search methods, when LIMIT is used.


### delete() method:

Deletes the row by provided $id or custom WHERE clause.


### deleteAll() method:

Deletes all records from the specified table.


### save() method:

Function that is calling create/update methods depending if condition is passed to it. If $condition in not passed then it will do the create method, otherwise it will update.

*Required parameters*: 

- *$columns* - array of key=>value pairs representing columns and values that will be updated/inserted  Example: `$columns = ['username' => $username];` will produce: `SET username = johndoe;`, or whatever was inside $username.

*Optional parameters*:

- *$condition* - array of key=>value pairs representing the WHERE clause (condition for updating).
Example: `$condition = ['username = ' => $username];` will produce: `WHERE username = johndoe;`, or whatever was inside $username.


### showQuery() method:

Method that is displaying last executed query. Please use only for debug/learn purpose. You should never display this information to end users.
