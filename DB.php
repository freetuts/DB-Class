<?php

/**
 * DB class file.
 *
 * @author Nenad Zivkovic <nenad@freetuts.org>
 * @link https://github.com/freetuts/DB-Class
 * @copyright 2014-present freetuts.org
 * @license http://www.freetuts.org/site/page?view=licensing
 */

/**
 * Database class with PDO connection and SQL abstraction functions for easier 
 * database manipulations.
 * 
 * To use this class you just have to include it in your scripts using 
 * require_once or auto load, set up your host, database name, username and 
 * password and that would be it.
 *
 * You can find tutorial on how to use this class at: 
 * @link http://freetuts.org/tutorial/2?title=How+to+use+freetuts.org+DB+class
 * Class documentation is at Github: 
 * @link https://github.com/freetuts/DB-Class/wiki
 * 
 */
//------------------------------------------------------------------------------

class DB
{
    //-------- your database setup --------//
    private static $host     = 'localhost';
    private static $database = 'example_db';
    private static $charset  = 'utf8';
    private static $username = 'root';
    private static $password = 'root';
    //-------------------------------------//
    
    //-----------------------------------------------------------------------
    // Class specific properties, modify only if you know what you are doing.
    //-----------------------------------------------------------------------
    private static $dbh;            // Database handle.
    private static $error;          // Exception errors.
    private static $sth;            // Statement handle.
    protected static $table = '';   // Table we will query. Comes from models 
                                    // that will override this property.
    private static $id;             // The id of the selected row.
    private static $columns;        // User submitted column names
    private static $placeholder;    // Placeholder for binding the parameters.
    private static $where;          // WHERE clause.
    private static $group;          // GROUP BY clause.
    private static $having;         // HAVING clause.
    private static $order;          // ORDER BY clause, default is ASC.
    private static $limit;          // LIMIT clause.
    private static $offset;         // Offset part of the LIMIT clause.
    private static $leftTable;      // Left table in JOIN query.
    private static $rightTable;     // Right table in JOIN query.
    private static $joinType;       // Type of the JOIN... LEFT, RIGHT...
    private static $lastQuery;      // The last executed SQL query.
                                    // See showQuery();

    /**
     * Initializes the PDO database connection
     */
    function __construct()
    {
        // tries to connect
        try
        {
            self::$dbh = new PDO("mysql:host=" . self::$host . ";
                                  dbname="     . self::$database . ";
                                  charset="    . self::$charset . "", 
                                                 self::$username, 
                                                 self::$password,
                                  // Return the number of found (matched) rows, 
                                  // not the number of changed rows.
                                  array(PDO::MYSQL_ATTR_FOUND_ROWS => true));

            // PDO can use exceptions to handle errors.
            self::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Use MySQL prepared statements not PDO emulated.
            self::$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            // Persistent database connections can increase performance.
            self::$dbh->setAttribute(PDO::ATTR_PERSISTENT, true);
        } 
        // Catch any errors
        catch (PDOException $e)
        {
            self::$error = $e->getMessage();      
        }
    }

//------------------------------------------------------------------------------

    /**
     ***************************************************************************
     * Helper methods used by DB abstraction functions.
     **************************************************************************/

    /******************
     * Select helpers *
     ******************/

    /**
     * Checks to see if submitted $columns is an array or string.
     *
     * @param   mixed   $columns        Can be array or string with 
     *                                  comma-separated values.
     *
     * @provide string  self::$columns  String of column names.
     */
    private static function checkColumns($columns = null)
    {
        // make sure $columns property is clean before we use it
        self::$columns = '';

        // columns are submitted as an array
        if (is_array($columns))
        {
            // sets the column names as an string with comma-separated values
            self::$columns = join(", ", array_values($columns));
        } 
        // columns are submitted as an comma-separated string
        else
        {
            self::$columns = $columns;
        }
    }

    /**
     * Checks to see if user has submitted columns. If he hasn't, $id will get 
     * the value from $columns because left spot will be empty, 
     * and id need to move there to get its value.
     *
     * @param   mixed   $columns        Should be array, or string with 
     *                                  comma-separated values.
     *
     * @param   integer $id             Id value of the row we want to get.
     *
     * @provide mixed                   Either just id, or both id and columns 
     *                                  if both are submitted.
     */
    private static function getId($columns, $id)
    {
        // columns are not listed, set id
        if (is_int($columns))
        {
            self::$columns = null;
            self::$id = $columns;
        } 
        // columns are listed, check them and set id
        else
        {
            self::checkColumns($columns);
            self::$id = $id;
        }
    }

    /**
     * Checks to see if user has submitted WHERE clause, ORDER and LIMIT.
     *
     * @param  array    $where      If user submitted WHERE part of the query 
     *                              it should be an associative array.
     *
     * @param  string   $order      If user submitted order, 
     *                              it should be string.
     *
     * @param  integer  $limit      If user submitted limit, 
     *                              it should be integer.
     *
     * @param  integer  $offset     If user submitted offset, 
     *                              it should be integer value.
     *
     * @provide mixed               WHERE/ORDER BY/LIMIT - depending whether 
     *                              or not user submitted each part.
     */
    private static function checkParams($where, $order, $limit, $offset)
    {
        // there is no WHERE clause, but there is ORDER BY
        if (!is_array($where) && !is_int($where))
        {
            self::$where = null;
            self::$order = $where;
            self::$limit = $order;
            self::$offset = $limit;
        } 
        // there is no WHERE clause and there is no ORDER BY too
        elseif (!is_array($where) && is_int($where))
        {
            self::$where = null;
            self::$order = null;
            self::$limit = $where;
            self::$offset = $order;
        } 
        // there are WHERE and LIMIT but not order
        elseif (is_array($where) && is_int($order))
        {
            self::$where = $where;
            self::$order = null;
            self::$limit = $order;
            self::$offset = $limit;
        } 
        // all are set
        else
        {
            self::$where = $where;
            self::$order = $order;
            self::$limit = $limit;
            self::$offset = $offset;
        }
    }

    /**
     * Checks to see if user has submitted WHERE clause, GROUP BY, HAVING, 
     * ORDER BY and LIMIT.
     *
     * @param  array    $where      If user submitted $where it should be an 
     *                              associative array.
     *
     * @param  string   $group      $group should be string and it 
     *                              should exists, it is not optional.
     *
     * @param  array    $having     If user submitted $having it 
     *                              should be an associative array.
     *
     * @param  string   $order      If user submitted $order it 
     *                              should be string value.
     *
     * @param  integer  $limit      If user submitted $limit it 
     *                              should be integer value.
     *
     * @param  integer  $offset     If user submitted $offset it 
     *                              should be integer value.
     *
     * @provide mixed               Parts of the query that user has 
     *                              submitted with getGroup().
     */
    private static function checkGroup($where, $group, $having, 
                                       $order, $limit, $offset)
    {
        // there is no WHERE clause
        if (!is_array($where))
        {
            self::$where = null;

            // $group will take spot of $where
            self::$group = $where;

            // we don't have HAVING because $having is not on spot of $group, 
            // since $group is on spot of $where.
            if (!is_array($group))
            {
                self::$having = null;

                // there is no ORDER BY
                if (is_int($group))
                {
                    self::$order = null;
                    self::$limit = $group;
                    self::$offset = $having;
                } 
                // there is ORDER BY
                else
                {
                    self::$order = $group;
                    self::$limit = $having;
                    self::$offset = $order;
                }
            } 
            // We have HAVING and it is on spot of $group
            else
            {
                self::$having = $group;

                // there is no ORDER BY
                if (is_int($having))
                {
                    self::$order = null;
                    self::$limit = $having;
                    self::$offset = $order;
                } 
                // there is ORDER BY
                else
                {
                    self::$order = $having;
                    self::$limit = $order;
                    self::$offset = $limit;
                }
            } // there is HAVING
        } 
        // there is WHERE clause
        else
        {
            self::$where = $where;

            // $group is taking his normal spot, now check others
            self::$group = $group;

            // we don't have HAVING
            if (!is_array($having))
            {
                self::$having = null;

                // there is no ORDER BY
                if (is_int($having))
                {
                    self::$order = null;
                    self::$limit = $having;
                    self::$offset = $order;
                } 
                // there is ORDER BY
                else
                {
                    self::$order = $having;
                    self::$limit = $order;
                    self::$offset = $limit;
                }
            } 
            // We have HAVING
            else
            {
                self::$having = $having;

                // there is no ORDER BY
                if (is_int($order))
                {
                    self::$order = null;
                    self::$limit = $order;
                    self::$offset = $limit;
                } 
                // there is ORDER BY
                else
                {
                    self::$order = $order;
                    self::$limit = $limit;
                    self::$offset = $offset;
                }
            } // there is HAVING
        } // there is WHERE
    }

    /**
     * If user submitted WHERE part of the query, we make sure it is built 
     * in the right format as string with appropriate column names, operator 
     * and value placeholder. Also, we are using this method to build up the 
     * WHERE part of the query for our search() function.
     *
     * @param  array    $where          If user submitted WHERE clause it will 
     *                                  be an associative array.
     *
     * @param  boolean  $search         Whether we are building WHERE with LIKE 
     *                                  for searching or not
     *
     * @provide mixed   self::$where    String - if WHERE was successfully 
     *                                  built, or null if not.
     */
    private static function buildWhere($where, $search = false)
    {
        // WHERE is not submitted
        if (!is_array($where))
        {
            self::$where = null;
        } 
        // WHERE is submitted
        else
        {
            // make sure self::$where is clean before use.
            self::$where = '';

            $i = 1;

            // we are doing searching
            if ($search === true)
            {
                // build WHERE condition with parameter binding placeholders.
                foreach ($where as $key => $value)
                {
                    self::$placeholder = " :search" . $i++;

                    self::$where .= $key . " LIKE " . self::$placeholder." ";
                }
            } 
            // we are doing normal SELECT
            else
            {
                // build WHERE condition with parameter binding placeholders.
                foreach ($where as $key => $value)
                {
                    self::$placeholder = " :value" . $i++;

                    self::$where .= $key . self::$placeholder." ";
                }
            }
        }
    }

    /**
     * If user submitted HAVING part of the query, we make sure it is built 
     * in the right format as string with appropriate column names, operator 
     * and value placeholder.
     *
     * @param  array    $having         If user submitted HAVING clause it will 
     *                                  be and associative array.
     *
     * @provide mixed   self::$having   String - if HAVING was successfully 
     *                                  built, or null if not.
     */
    private static function buildHaving($having = null)
    {
        // HAVING is not submitted
        if (!is_array($having))
        {
            self::$having = null;
        } 
        // HAVING is submitted
        else
        {
            // make sure self::$having is clean before use.
            self::$having = '';

            // build HAVING condition with parameter binding placeholders.
            $i = 1;
            foreach ($having as $key => $value)
            {
                self::$placeholder = " :having" . $i++;

                self::$having .= $key . self::$placeholder." ";
            }
        }
    }

    /******************
     * Global helpers *
     ******************/

    /**
     * Binds parameters to named value placeholders. This method can do 
     * parameter binding for normal WHERE clauses and for LIKE clauses in our 
     * search() method.
     *
     * @param  array    $columns    An associative array of column names and 
     *                              values that should be inserted into them.
     *
     * @param string    $kind       In case we are doing several bindings like 
     *                              bindings for WHERE clause and for HAVING 
     *                              clause, we can specify $kind parameter to 
     *                              differentiate between two bindings. 
     *                              For example, for WHERE clause we do not 
     *                              specify it, and it will take the default 
     *                              value of :value, and for HAVING we pass it 
     *                              as 'having', so it will take value of 
     *                              :having. It is also doing binding for 
     *                              search() method.
     *
     * @provide                     Parameter bindings.
     */
    private static function bindParams($columns, $kind = "value")
    {
        $i = 1;

        // get all values
        foreach ($columns as $key => &$value)
        {
            self::$placeholder = ":" . $kind . $i++;

            // we are doing searching
            if ($kind === 'search')
            {
                $value = "%" . $value . "%";

                // value is an integer, use PARAM_INT
                if (is_int($value))
                {
                    // bind values to values placeholders with same names as in 
                    // self::condition
                    self::$sth->bindParam(self::$placeholder, $value, 
                                          PDO::PARAM_INT);
                } 
                // value is a string, user PARAM_STR
                else
                {
                    // bind values to values placeholders with same names as in 
                    // self::condition
                    self::$sth->bindParam(self::$placeholder, $value, 
                                          PDO::PARAM_STR);
                }
            } 
            // do normal binding
            else
            {
                // value is an integer, use PARAM_INT
                if (is_int($value))
                {
                    // bind values to values placeholders with same names as in 
                    // self::condition
                    self::$sth->bindParam(self::$placeholder, $value, 
                                          PDO::PARAM_INT);
                } 
                // value is a string, user PARAM_STR
                else
                {
                    // bind values to values placeholders with same names as in 
                    // self::condition
                    self::$sth->bindParam(self::$placeholder, $value, 
                                          PDO::PARAM_STR);
                }
            }
        }
    }

    /**
     * Helper method that is setting the fetch mode to FETCH_CLASS. It also 
     * executes the query and binds the SQL query string to $lastQuery property,
     * and at the end returns statement handle.
     *
     * @return object   self::$sth      Statement handle.
     */
    private static function execute()
    {
        self::$sth->setFetchMode(PDO::FETCH_CLASS, ucfirst(static::$table));
        self::$sth->execute();
        self::$lastQuery = self::$sth;
        return self::$sth; 
    }    

    /******************
     * Delete helpers *
     ******************/

    /**
     * If user submitted WHERE part of the query, we make sure it is built in 
     * the right format as string with appropriate column names, operator and 
     * value placeholder, otherwise we set the id to the value user specified
     * as the first parameter.
     *
     * @param  array    $where          If user submitted WHERE clause it will 
     *                                  be an associative array.
     *
     * @param integer   $id             If user submitted id of the row to be 
     *                                  deleted it will be an integer.
     *
     * @provide string  self::$where    String - if WHERE was successfully 
     *                                  built, or integer if $id is used.
     */
    private static function buildWhereDelete($where, $id)
    {
        // $where is not submitted
        if (!is_array($where))
        {
            self::$where = null;
            self::$id = $where;
        } 
        // $where is submitted
        else
        {
            // make sure condition is clean before use.
            self::$where = '';

            // since user is using custom WHERE, we mark $id as null.
            self::$id = null;

            // build where condition with parameter binding placeholders.
            $i = 1;

            foreach ($where as $key => $value)
            {
                self::$placeholder = ":value" . $i++;

                self::$where .= $key . self::$placeholder;
            }
        }
    }

    /******************
     * Insert helpers *
     ******************/

    /**
     * Creates placeholders for INSERT part of the query.
     *
     * @param  array    $columns            An associative array of column names
     *                                      and values that should be inserted
     *                                      into them.
     *
     * @provide string  self::$placeholder  values named placeholders for 
     *                                      parameter bindings.
     */
    private static function buildPlaceholders($columns)
    {
        // make sure placeholders are clean before use.
        self::$placeholder = '';

        $i = 1;

        foreach ($columns as $key => $value)
        {
            self::$placeholder .= ":value" . $i++ . ", ";
        }

        // cleans ending comma, and sets placeholder
        self::$placeholder = substr(self::$placeholder, 0, -2);
    }

    /******************
     * Update helpers *
     ******************/

    /**
     * Build columns for UPDATE part of the query.
     *
     * @param  array    $columns            An associative array of column names
     *                                      and values that should be inserted
     *                                      into them.
     *
     * @provide string  $self::$columns     UPDATE part of the query, with 
     *                                      column names and named placeholders.
     */
    private static function buildUpdate($columns)
    {
        // make sure columns are clean before use.
        self::$columns = '';

        $i = 1;

        foreach ($columns as $key => $value)
        {
            self::$placeholder = ":update" . $i++ . ", ";

            self::$columns .= $key . " = " . self::$placeholder;
        }

        // cleans ending comma, and sets update columns
        self::$columns = substr(self::$columns, 0, -2);
    }

    /****************
     * Join helpers *
     ****************/

    /**
     * Sets left and right table names to correct values in JOIN queries.
     *
     * @param  string   $tables         User should provide comma-separated 
     *                                  string with left and right table names.
     *
     * @provide  string                 Table names
     */
    private static function buildTables($table)
    {
        $table = explode(", ", $table);

        // set right table
        self::$rightTable = $table[0];

        if (!empty($table[1]))
        {
            self::$joinType = $table[1];
        } 
        else
        {
            self::$joinType = 'INNER ';
        }
    }

//------------------------------------------------------------------------------
    /**
     * *************************************************************************
     * DB abstraction functions - get collection ( SELECT )
     * ************************************************************************/

    /*********
     * query *
     *********/

    /**
     * Query by SQL function. You have to pass the raw SQL to this function 
     * using the $query parameter.
     *
     * @param  string   $query      Sql query.
     *
     * @return object               Database handle.
     */
    public static function query($query)
    {
        return self::$dbh->prepare($query);
    }

    /*********
     * get() *
     *********/

    /**
     * Queries the given table by provided parameters. 
     * It can use WHERE clause to specify the selection.
     *
     * @param  string   $columns    Required parameter : you need to pass the 
     *                              names of the columns you want to
     *                              select, you can pass them as an array or 
     *                              as an string with comma-separated values. 
     *                              Also if you want to select all columns in 
     *                              table you can pass *.
     *                              Examples: $columns = array('id', 'name');
     *                              $columns = 'id, name'; $columns = '*';
     *
     * @param  string   $where      Optional parameter : you can specify the 
     *                              WHERE part of the query using an associative 
     *                              array, where keys will be the string parts 
     *                              of the statements, like: 'name =', and array 
     *                              values will be values for those conditions.
     *                              Example: 
     *                              ['id >=' => $id, ' AND price=' => $price] 
     *                              will produce:
     *                              WHERE id >= :value1 AND price = :value2 , 
     *                              where :value1 and :value2 will be replaced 
     *                              with bound parameters.
     *
     * @param  string   $order      Optional parameter : you can specify the 
     *                              ORDER BY clause.
     *
     * @param  integer  $limit      Optional parameter : you can specify the 
     *                              LIMIT clause.
     *
     * @param  integer  $offset     Optional parameter : you can specify the 
     *                              OFFSET part of the LIMIT clause.
     *
     * @return object               Statement handle.
     */
    public function get($columns, $where = null, $order = null, 
                        $limit = null, $offset = '')
    {
        // checks to see if submitted $columns is an array or string.
        self::checkColumns($columns);

        // checks to see which optional parameters user has submitted, 
        // and assigns them appropriate values.
        self::checkParams($where, $order, $limit, $offset);

        // if WHERE part was submitted 
        // we make sure that it is in the right format
        self::buildWhere(self::$where);

        /**
         * We do not have WHERE clause, build query without it.
         * **************************************************** */
        if (is_null(self::$where))
        {
            // no ORDER BY, no LIMIT
            if (is_null(self::$order) && is_null(self::$limit))
            {

                self::$sth = self::query("SELECT " . self::$columns . "
                                          FROM "   . static::$table . " ");

                return self::execute();
            } 
            // ORDER BY is listed but not LIMIT
            elseif (!is_null(self::$order) && is_null(self::$limit))
            {
                self::$sth = self::query("SELECT "   . self::$columns . "
                                          FROM "     . static::$table . "
                                          ORDER BY " . self::$order . " ");

                return self::execute();
            } 
            // LIMIT is listed but not ORDER BY
            elseif (is_null(self::$order) && !is_null(self::$limit))
            {
                // if there is offset, we will merge it with limit
                if ($offset !== '')
                {
                    self::$offset = $offset . ',';
                }

                self::$sth = self::query("SELECT SQL_CALC_FOUND_ROWS "
                                                  . self::$columns . "
                                          FROM "  . static::$table . "
                                          LIMIT " . self::$offset
                                                  . self::$limit . " ");

                return self::execute();
            } 
            // both ORDER BY and LIMIT are listed
            elseif (!is_null(self::$order) && !is_null(self::$limit))
            {
                // if there is offset, we will merge it with limit
                if ($offset !== '')
                {
                    self::$offset = $offset . ',';
                }

                self::$sth = self::query("SELECT SQL_CALC_FOUND_ROWS "
                                                     . self::$columns . "
                                          FROM "     . static::$table . "
                                          ORDER BY " . self::$order . "
                                          LIMIT "    . self::$offset 
                                                     . self::$limit . " ");

                return self::execute();
            }
        } 
        /**
         * We have the WHERE clause
         **************************/ 
        else
        {
            // no ORDER BY, no LIMIT
            if (is_null(self::$order) && is_null(self::$limit))
            {
                self::$sth = self::query("SELECT " . self::$columns . "
                                          FROM "   . static::$table . "
                                          WHERE "  . self::$where . " ");

                self::bindParams($where);
                return self::execute();
            } 
            // ORDER BY is listed but not LIMIT
            elseif (!is_null(self::$order) && is_null(self::$limit))
            {
                self::$sth = self::query("SELECT "   . self::$columns . "
                                          FROM "     . static::$table . "
                                          WHERE "    . self::$where . "
                                          ORDER BY " . self::$order . " ");

                self::bindParams($where);
                return self::execute();
            } 
            // LIMIT is listed but not ORDER BY
            elseif (is_null(self::$order) && !is_null(self::$limit))
            {
                // if there is offset, we will merge it with limit
                if ($offset !== '')
                {
                    self::$offset = $offset . ',';
                }

                self::$sth = self::query("SELECT SQL_CALC_FOUND_ROWS "
                                                  . self::$columns . "
                                          FROM "  . static::$table . "
                                          WHERE " . self::$where . "
                                          LIMIT " . self::$offset
                                                  . self::$limit . " ");

                self::bindParams($where);
                return self::execute();
            } 
            // both ORDER BY and LIMIT are listed
            else
            {
                // if there is offset, we will merge it with limit
                if ($offset !== '')
                {
                    self::$offset = $offset . ',';
                }

                self::$sth = self::query("SELECT SQL_CALC_FOUND_ROWS "
                                                     . self::$columns . "
                                          FROM "     . static::$table . "
                                          WHERE "    . self::$where . "
                                          ORDER BY " . self::$order . "
                                          LIMIT "    . self::$offset 
                                                     . self::$limit . " ");

                self::bindParams($where);
                return self::execute();
            }
        }
    }

    /************
     * getAll() *
     ************/

    /**
     * Selects everything from the given table and appends the WHERE, ORDER BY 
     * or LIMIT clauses if you pass them to the function as parameters.
     *
     * @param  string   $where      Optional parameter : you can specify the 
     *                              WHERE part of the query using an associative 
     *                              array, where keys will be the string parts 
     *                              of the statements, like: 'name =', and array 
     *                              values will be values for those conditions. 
     *                              Example: 
     *                              ['id >=' => $id, ' AND price=' => $price] 
     *                              will produce:
     *                              WHERE id >= :value1 AND price = :value2 , 
     *                              where :value1 and :value2 will be replaced 
     *                              with bound parameters.
     *
     * @param  string   $order      Optional parameter : defaults to 'ASC', 
     *                              you can pass DESC if needed.
     *
     * @param  integer  $limit      Optional parameter : limiting the number of 
     *                              rows you get, if you supply the limit, 
     *                              the mysql SQL_CALC_FOUND_ROWS function will 
     *                              be executed, so you will be able to use 
     *                              count() function to count the number of 
     *                              affected rows by select. See count() 
     *                              function.
     *
     * @param  integer  $offset     Optional parameter : you can specify the 
     *                              OFFSET part of the LIMIT clause.
     *                              
     * @return object               Statement handle.
     */
    public static function getAll($where = null, $order = null, 
                                  $limit = null, $offset = '')
    {
        // checks to see which optional parameters user has submitted, 
        // and assigns them appropriate values.
        self::checkParams($where, $order, $limit, $offset);

        // if WHERE part was submitted 
        // we make sure that it is in the right format
        self::buildWhere(self::$where);

        /**
         * We do not have WHERE clause, build query without it.
         * *************************************************** */
        if (is_null(self::$where))
        {
            // no ORDER BY, no LIMIT
            if (is_null(self::$order) && is_null(self::$limit))
            {
                self::$sth = self::query("SELECT *
                                          FROM " . static::$table . " ");

                return self::execute();
            } 
            // ORDER BY is listed but not LIMIT
            elseif (!is_null(self::$order) && is_null(self::$limit))
            {
                self::$sth = self::query("SELECT *
                                          FROM "     . static::$table . "
                                          ORDER BY " . self::$order . " ");

                return self::execute();
            } 
            // LIMIT is listed but not ORDER BY
            elseif (is_null(self::$order) && !is_null(self::$limit))
            {
                // if there is offset, we will merge it with limit
                if ($offset !== '')
                {
                    self::$offset = $offset . ',';
                }

                self::$sth = self::query("SELECT SQL_CALC_FOUND_ROWS *
                                          FROM "  . static::$table . "
                                          LIMIT " . self::$offset 
                                                  . self::$limit . " ");

                return self::execute();
            } 
            // both ORDER BY and LIMIT are listed
            else
            {
                // if there is offset, we will merge it with limit
                if ($offset !== '')
                {
                    self::$offset = $offset . ',';
                }

                self::$sth = self::query("SELECT SQL_CALC_FOUND_ROWS *
                                          FROM "     . static::$table . "
                                          ORDER BY " . self::$order . "
                                          LIMIT "    . self::$offset 
                                                     . self::$limit . " ");

                return self::execute();
            }
        } 
        /**
         * We have the WHERE clause
         **************************/ 
        else
        {
            // no ORDER BY, no LIMIT
            if (is_null(self::$order) && is_null(self::$limit))
            {
                self::$sth = self::query("SELECT *
                                          FROM "  . static::$table . "
                                          WHERE " . self::$where . " ");

                self::bindParams($where);
                return self::execute();
            } 
            // ORDER BY is listed but not LIMIT
            elseif (!is_null(self::$order) && is_null(self::$limit))
            {
                self::$sth = self::query("SELECT *
                                          FROM "     . static::$table . "
                                          WHERE "    . self::$where . "
                                          ORDER BY " . self::$order . " ");

                self::bindParams($where);
                return self::execute();
            } 
            // LIMIT is listed but not ORDER BY
            elseif (is_null(self::$order) && !is_null(self::$limit))
            {
                // if there is offset, we will merge it with limit
                if ($offset !== '')
                {
                    self::$offset = $offset . ',';
                }

                self::$sth = self::query("SELECT SQL_CALC_FOUND_ROWS *
                                          FROM "  . static::$table . "
                                          WHERE " . self::$where . "
                                          LIMIT " . self::$offset 
                                                  . self::$limit . " ");

                self::bindParams($where);
                return self::execute();
            } 
            // both ORDER BY and LIMIT are listed
            else
            {
                // if there is offset, we will merge it with limit
                if ($offset !== '')
                {
                    self::$offset = $offset . ',';
                }

                self::$sth = self::query("SELECT SQL_CALC_FOUND_ROWS *
                                          FROM "     . static::$table . "
                                          WHERE "    . self::$where . "
                                          ORDER BY " . self::$order . "
                                          LIMIT "    . self::$offset 
                                                     . self::$limit . " ");

                self::bindParams($where);
                return self::execute();;
            }
        }
    }

    /*************
     * getById() *
     *************/

    /**
     * Queries the given table by provided $id value. 
     * Id is considered to be an integer id value.
     *
     * @param  array    $columns    Optional parameter : column names you want 
     *                              to select, ( in case you do not want all *). 
     *                              You can write them as an array or string 
     *                              with comma-separated values.
     *                              Examples: $columns = array('id', 'name');
     *                              $columns = 'id, name';
     *
     * @param  integer  $id         Required parameter : you need to pass the 
     *                              id as a criteria for selection.
     *
     * @return object               Statement handle.
     */
    public function getById($columns = null, $id = null)
    {
        // checks to see if user has submitted columns and gets the id value.
        self::getId($columns, $id);

        // user submitted only id without specific columns to select
        if (is_null(self::$columns))
        {
            self::$sth = self::query("SELECT *
                                      FROM " . static::$table . "
                                      WHERE  id = :id");

            self::$sth->bindParam(':id', self::$id, PDO::PARAM_INT);
            return self::execute();
        } 
        // user submitted both column names and id
        else
        {
            self::$sth = self::query("SELECT " . self::$columns . "
                                      FROM "   . static::$table . "
                                      WHERE    id = :id");

            self::$sth->bindParam(':id', self::$id, PDO::PARAM_INT);
            return self::execute();
        }
    }

    /****************
     * getGrouped() *
     ****************/

    /**
     * Queries the given table and groups the results using the GROUP BY clause.
     * User can use WHERE and HAVING too.
     *
     * @param  string   $columns    Required parameter : you need to pass the 
     *                              names of the columns you want to select, 
     *                              you can pass them as an array or as an 
     *                              string with comma-separated values. Also if 
     *                              you want to select all columns in table you 
     *                              can pass *. 
     *                              Examples: $columns = array('id', 'name');
     *                              $columns = 'id, name'; $columns = '*';
     *
     * @param  array    $where      Optional parameter : you can specify the 
     *                              WHERE part of the query using an associative 
     *                              array, where keys will be the string parts 
     *                              of the statements, like: 'name =', and array 
     *                              values will be values for those conditions. 
     *                              Example: 
     *                              ['id >=' => $id, ' AND price=' => $price] 
     *                              will produce:
     *                              WHERE id >= :value1 AND price = :value2 , 
     *                              where :value1 and :value2 will be replaced 
     *                              with bound parameters.
     *
     * @param  string   $group      Required parameter : you have to pass the 
     *                              name of the column you are grouping by
     *                              your results.
     *
     * @param  array    $having     Optional parameter : you can specify the 
     *                              HAVING part of the query using an 
     *                              associative array, where keys will be the 
     *                              string parts of the statements, 
     *                              like: 'SUM(tax) <=', and array values will
     *                              be values for those conditions. 
     *                              Example: ['SUM(tax) <=' => $tax,
     *                              ' AND SUM(price)=' => $price] 
     *                              will produce:  
     *                              HAVING tax <= :value1 AND price = :value2 ,
     *                              where :value1 and :value2 will be replaced 
     *                              with bound parameters.
     *
     * @param  string   $order      Optional parameter : you can specify the 
     *                              ORDER BY clause.
     *
     * @param  string   $limit      Optional parameter : you can specify the 
     *                              LIMIT clause.
     *
     * @param  integer  $offset     Optional parameter : you can specify the 
     *                              OFFSET part of the LIMIT clause.
     *
     * @return object               Statement handle.
     */
    public function getGrouped($columns = "",  $where = null, $group = null, 
                               $having = null, $order = null, 
                               $limit = null,  $offset = '')
    {
        // checks to see if submitted $columns is an array or string.
        self::checkColumns($columns);

        // checks to see which optional parameters user has submitted, 
        // and assigns them appropriate values.
        self::checkGroup($where, $group, $having, $order, $limit, $offset);

        // if WHERE part was submitted 
        // we make sure that it is in the right format
        self::buildWhere(self::$where);

        // if HAVING part was submitted 
        // we make sure that it is in the right format
        self::buildHaving(self::$having);

        /**
         * We do not have WHERE clause and we don't have HAVING, 
         * build query without them.
         * ****************************************************/
        if (is_null(self::$where) && is_null(self::$having))
        {
            // no ORDER BY, no LIMIT
            if (is_null(self::$order) && is_null(self::$limit))
            {
                self::$sth = self::query("SELECT "   . self::$columns . "
                                          FROM "     . static::$table . "
                                          GROUP BY " . self::$group . " ");

                return self::execute();
            } 
            // ORDER BY is listed but not LIMIT
            elseif (!is_null(self::$order) && is_null(self::$limit))
            {
                self::$sth = self::query("SELECT "   . self::$columns . "
                                          FROM "     . static::$table . "
                                          GROUP BY " . self::$group . "
                                          ORDER BY " . self::$order . " ");

                return self::execute();
            } 
            // LIMIT is listed but not ORDER BY
            elseif (is_null(self::$order) && !is_null(self::$limit))
            {
                // if there is offset, we will merge it with limit
                if ($offset !== '')
                {
                    self::$offset = $offset . ',';
                }

                self::$sth = self::query("SELECT SQL_CALC_FOUND_ROWS " 
                                                     . self::$columns . "
                                          FROM "     . static::$table . "
                                          GROUP BY " . self::$group . "
                                          LIMIT "    . self::$offset 
                                                     . self::$limit . " ");
  
                return self::execute();
            } 
            // both ORDER BY and LIMIT are listed
            else
            {
                // if there is offset, we will merge it with limit
                if ($offset !== '')
                {
                    self::$offset = $offset . ',';
                }

                self::$sth = self::query("SELECT SQL_CALC_FOUND_ROWS " 
                                                     . self::$columns . "
                                          FROM "     . static::$table . "
                                          GROUP BY " . self::$group . "
                                          ORDER BY " . self::$order . "
                                          LIMIT "    . self::$offset 
                                                     . self::$limit . " ");

                return self::execute();
            }
        } 
        /**
         * We have the WHERE clause but not HAVING.
         ******************************************/ 
        elseif (!is_null(self::$where) && is_null(self::$having))
        {
            // no ORDER BY, no LIMIT
            if (is_null(self::$order) && is_null(self::$limit))
            {
                self::$sth = self::query("SELECT "   . self::$columns . "
                                          FROM "     . static::$table . "
                                          WHERE "    . self::$where . "
                                          GROUP BY " . self::$group . " ");

                self::bindParams($where);
                return self::execute();
            } 
            // ORDER BY is listed but not LIMIT
            elseif (!is_null(self::$order) && is_null(self::$limit))
            {
                self::$sth = self::query("SELECT "   . self::$columns . "
                                          FROM "     . static::$table . "
                                          WHERE "    . self::$where . "
                                          GROUP BY " . self::$group . "
                                          ORDER BY " . self::$order . " ");

                self::bindParams($where);
                return self::execute();
            } 
            // LIMIT is listed but not ORDER BY
            elseif (is_null(self::$order) && !is_null(self::$limit))
            {
                // if there is offset, we will merge it with limit
                if ($offset !== '')
                {
                    self::$offset = $offset . ',';
                }

                self::$sth = self::query("SELECT SQL_CALC_FOUND_ROWS " 
                                                     . self::$columns . "
                                          FROM "     . static::$table . "
                                          WHERE "    . self::$where . "
                                          GROUP BY " . self::$group . "
                                          LIMIT "    . self::$offset 
                                                     . self::$limit . " ");

                self::bindParams($where);
                return self::execute();
            } 
            // both ORDER BY and LIMIT are listed
            else
            {
                // if there is offset, we will merge it with limit
                if ($offset !== '')
                {
                    self::$offset = $offset . ',';
                }

                self::$sth = self::query("SELECT SQL_CALC_FOUND_ROWS " 
                                                     . self::$columns . "
                                          FROM "     . static::$table . "
                                          WHERE "    . self::$where . "
                                          GROUP BY " . self::$group . "
                                          ORDER BY " . self::$order . "
                                          LIMIT "    . self::$offset 
                                                     . self::$limit . " ");

                self::bindParams($where);
                return self::execute();
            }
        } 
        /**
         * We do not have the WHERE clause but we have HAVING.
         *****************************************************/ 
        elseif (is_null(self::$where) && !is_null(self::$having))
        {
            // no ORDER BY, no LIMIT
            if (is_null(self::$order) && is_null(self::$limit))
            {
                self::$sth = self::query("SELECT "   . self::$columns . "
                                          FROM "     . static::$table . "
                                          GROUP BY " . self::$group . "
                                          HAVING "   . self::$having . " ");

                self::bindParams($group, 'having');
                return self::execute();
            } 
            // ORDER BY is listed but not LIMIT
            elseif (!is_null(self::$order) && is_null(self::$limit))
            {
                self::$sth = self::query("SELECT "   . self::$columns . "
                                          FROM "     . static::$table . "
                                          GROUP BY " . self::$group . "
                                          HAVING "   . self::$having . "
                                          ORDER BY " . self::$order . " ");

                self::bindParams($group, 'having');
                return self::execute();
            } 
            // LIMIT is listed but not ORDER BY
            elseif (is_null(self::$order) && !is_null(self::$limit))
            {
                // if there is offset, we will merge it with limit
                if ($offset !== '')
                {
                    self::$offset = $offset . ',';
                }

                self::$sth = self::query("SELECT SQL_CALC_FOUND_ROWS " 
                                                     . self::$columns . "
                                          FROM "     . static::$table . "
                                          GROUP BY " . self::$group . "
                                          HAVING "   . self::$having . "
                                          LIMIT "    . self::$offset 
                                                     . self::$limit . " ");

                self::bindParams($group, 'having');
                return self::execute();
            } 
            // both ORDER BY and LIMIT are listed
            else
            {
                // if there is offset, we will merge it with limit
                if ($offset !== '')
                {
                    self::$offset = $offset . ',';
                }

                self::$sth = self::query("SELECT SQL_CALC_FOUND_ROWS " 
                                                     . self::$columns . "
                                          FROM "     . static::$table . "
                                          GROUP BY " . self::$group . "
                                          HAVING "   . self::$having . "
                                          ORDER BY " . self::$order . "
                                          LIMIT "    . self::$offset 
                                                     . self::$limit . " ");

                self::bindParams($group, 'having');
                return self::execute();
            }
        } 
        /**
         * We have both the WHERE clause and HAVING.
         *******************************************/ 
        elseif (!is_null(self::$where) && !is_null(self::$having))
        {
            // no ORDER BY, no LIMIT
            if (is_null(self::$order) && is_null(self::$limit))
            {
                self::$sth = self::query("SELECT "   . self::$columns . "
                                          FROM "     . static::$table . "
                                          WHERE "    . self::$where . "
                                          GROUP BY " . self::$group . "
                                          HAVING "   . self::$having . " ");

                self::bindParams($where);
                self::bindParams($having, 'having');
                return self::execute();
            } 
            // ORDER BY is listed but not LIMIT
            elseif (!is_null(self::$order) && is_null(self::$limit))
            {
                self::$sth = self::query("SELECT "   . self::$columns . "
                                          FROM "     . static::$table . "
                                          WHERE "    . self::$where . "
                                          GROUP BY " . self::$group . "
                                          HAVING "   . self::$having . "
                                          ORDER BY " . self::$order . " ");

                self::bindParams($where);
                self::bindParams($having, 'having');
                return self::execute();
            } 
            // LIMIT is listed but not ORDER BY
            elseif (is_null(self::$order) && !is_null(self::$limit))
            {
                // if there is offset, we will merge it with limit
                if ($offset !== '')
                {
                    self::$offset = $offset . ',';
                }

                self::$sth = self::query("SELECT SQL_CALC_FOUND_ROWS " 
                                                     . self::$columns . "
                                          FROM "     . static::$table . "
                                          WHERE "    . self::$where . "
                                          GROUP BY " . self::$group . "
                                          HAVING "   . self::$having . "
                                          LIMIT "    . self::$offset 
                                                     . self::$limit . " ");

                self::bindParams($where);
                self::bindParams($having, 'having');
                return self::execute();
            } 
            // both ORDER BY and LIMIT are listed
            else
            {
                // if there is offset, we will merge it with limit
                if ($offset !== '')
                {
                    self::$offset = $offset . ',';
                }

                self::$sth = self::query("SELECT SQL_CALC_FOUND_ROWS " 
                                                     . self::$columns . "
                                          FROM "     . static::$table . "
                                          WHERE "    . self::$where . "
                                          GROUP BY " . self::$group . "
                                          HAVING "   . self::$having . "
                                          ORDER BY " . self::$order . "
                                          LIMIT "    . self::$offset 
                                                     . self::$limit . " ");

                self::bindParams($where);
                self::bindParams($having, 'having');
                return self::execute();
            }
        }
    }

    /**********
     * join() *
     **********/

    /**
     * Queries the given table using the JOIN clause. Users can specify type of 
     * JOIN plus WHERE, ORDER BY and LIMIT.
     *
     * @param  string   $table      Required parameter : you need to pass the 
     *                              name of the table you are joining and the 
     *                              kind of the join as an comma-separated 
     *                              string. Left table will be the one of the 
     *                              current model you are using, and the right 
     *                              table will be the one you specify using 
     *                              this parameter. This means that the left 
     *                              table will be used  in FROM part of the 
     *                              query and second  will be used in INNER JOIN 
     *                              part of the query. If you need other type of 
     *                              joins, just  add it as second value of the 
     *                              table string. 
     *                              Example: Lets say we are working with Author 
     *                              model, that means author table. If we want 
     *                              to join News table with the current one, we 
     *                              would write our $table part of the query 
     *                              like this : $table = 'news, LEFT'. 
     *                              This will join author and news tables using 
     *                              LEFT JOIN. If you want INNER JOIN, you do 
     *                              not have to write the kind of the join, 
     *                              since INNER join is the default one. In that 
     *                              case, this example would be: $table = 'news'.
     *
     * @param  string   $columns    Required parameter : you need to pass the 
     *                              names of the tables and their columns you 
     *                              want to select, you can pass them as an 
     *                              array or as an string with comma-separated 
     *                              values. Also if you want to select all 
     *                              columns in table you can pass *.
     *                              Examples: $columns = array('id', 'name');
     *                              $columns = 'id, name'; $columns = '*';
     *
     * @param  string   $on         Required parameter : This is the ON part of 
     *                              the join query. You write it as a string. 
     *                              Example: 'country.code = 
     *                              country_language.country_code';
     *
     * @param  array    $where      Optional parameter : you can specify the 
     *                              WHERE part of the query using an associative 
     *                              array, where keys will be the string parts 
     *                              of the statements, like: 'name =', and array 
     *                              values will be values for those conditions. 
     *                              Example:
     *                              ['id >=' => $id, ' AND price=' => $price] 
     *                              will produce:
     *                              WHERE id >= :value1 AND price = :value2 , 
     *                              where :value1 and :value2 will be replaced 
     *                              with bound parameters.
     *
     * @param  string   $order      Optional parameter : you can specify the 
     *                              ORDER BY clause.
     *
     * @param  string   $limit      Optional parameter : you can specify the 
     *                              LIMIT clause.
     *
     * @param  integer  $offset     Optional parameter : you can specify the 
     *                              OFFSET part of the LIMIT clause.
     *
     * @return object               Statement handle.
     */
    public function join($table, $columns, $on, $where = null, 
                         $order = null, $limit = null, $offset = '')
    {
        // builds left and right table names and JOIN type
        self::buildTables($table);

        // checks to see if submitted $columns is an array or string.
        self::checkColumns($columns);

        // checks to see which optional parameters user has submitted, 
        // and assigns them appropriate values.
        self::checkParams($where, $order, $limit, $offset);

        // if WHERE part was submitted we make sure 
        // that it is in the right format
        self::buildWhere(self::$where);

        /**
         * We do not have WHERE clause build query without it.
         ****************************************************/
        if (is_null(self::$where))
        {
            // no ORDER BY, no LIMIT
            if (is_null(self::$order) && is_null(self::$limit))
            {
                self::$sth = self::query("SELECT "  . self::$columns . "
                                          FROM "    . static::$table . "
                                          " . self::$joinType . " JOIN " 
                                                    . self::$rightTable . "
                                          ON "      . $on . " ");

                return self::execute();
            } 
            // ORDER BY is listed but not LIMIT
            elseif (!is_null(self::$order) && is_null(self::$limit))
            {
                self::$sth = self::query("SELECT "   . self::$columns . "
                                          FROM "     . static::$table . "
                                          " . self::$joinType . " JOIN " 
                                                     . self::$rightTable . "
                                          ON "       . $on . "
                                          ORDER BY " . self::$order . " ");

                return self::execute();
            } 
            // LIMIT is listed but not ORDER BY
            elseif (is_null(self::$order) && !is_null(self::$limit))
            {
                // if there is offset, we will merge it with limit
                if ($offset !== '')
                {
                    self::$offset = $offset . ',';
                }

                self::$sth = self::query("SELECT SQL_CALC_FOUND_ROWS " 
                                                  . self::$columns . "
                                          FROM "  . static::$table. "
                                          " . self::$joinType . " JOIN " 
                                                  . self::$rightTable . "
                                          ON "    . $on . "
                                          LIMIT " . self::$offset 
                                                  . self::$limit . " ");

                return self::execute();
            } 
            // both ORDER BY and LIMIT are listed
            else
            {
                // if there is offset, we will merge it with limit
                if ($offset !== '')
                {
                    self::$offset = $offset . ',';
                }

                self::$sth = self::query("SELECT SQL_CALC_FOUND_ROWS " 
                                                     . self::$columns . "
                                          FROM "     . static::$table . "
                                          " . self::$joinType . " JOIN " 
                                                     . self::$rightTable . "
                                          ON "       . $on . "
                                          ORDER BY " . self::$order . "
                                          LIMIT "    . self::$offset 
                                                     . self::$limit . " ");

                return self::execute();
            }
        } 
        /**
         * We have the WHERE clause.
         ***************************/ 
        elseif (!is_null(self::$where))
        {
            // no ORDER BY, no LIMIT
            if (is_null(self::$order) && is_null(self::$limit))
            {
                self::$sth = self::query("SELECT "  . self::$columns . "
                                          FROM "    . static::$table . "
                                          " . self::$joinType . " JOIN " 
                                                    . self::$rightTable . "
                                          ON "      . $on . "
                                          WHERE "   . self::$where . " ");

                self::bindParams($where);
                return self::execute();
            } 
            // ORDER BY is listed but not LIMIT
            elseif (!is_null(self::$order) && is_null(self::$limit))
            {
                self::$sth = self::query("SELECT "   . self::$columns . "
                                          FROM "     . static::$table . "
                                          " . self::$joinType . " JOIN " 
                                                     . self::$rightTable . "
                                          ON "       . $on . "
                                          WHERE "    . self::$where . "
                                          ORDER BY " . self::$order . " ");

                self::bindParams($where);
                return self::execute();
            } 
            // LIMIT is listed but not ORDER BY
            elseif (is_null(self::$order) && !is_null(self::$limit))
            {
                // if there is offset, we will merge it with limit
                if ($offset !== '')
                {
                    self::$offset = $offset . ',';
                }

                self::$sth = self::query("SELECT SQL_CALC_FOUND_ROWS " 
                                                    . self::$columns . "
                                          FROM "    . static::$table . "
                                          " . self::$joinType . " JOIN " 
                                                    . self::$rightTable . "
                                          ON "      . $on . "
                                          WHERE "   . self::$where . "
                                          LIMIT "   . self::$offset 
                                                    . self::$limit . " ");

                self::bindParams($where);
                return self::execute();
            } 
            // both ORDER BY and LIMIT are listed
            else
            {
                // if there is offset, we will merge it with limit
                if ($offset !== '')
                {
                    self::$offset = $offset . ',';
                }

                self::$sth = self::query("SELECT SQL_CALC_FOUND_ROWS " 
                                                     . self::$columns . "
                                          FROM "     . static::$table . "
                                          " . self::$joinType . " JOIN " 
                                                     . self::$rightTable . "
                                          ON "       . $on . "
                                          WHERE "    . self::$where . "
                                          ORDER BY " . self::$order . "
                                          LIMIT "    . self::$offset 
                                                     . self::$limit . " ");

                self::bindParams($where);
                return self::execute();
            }
        }
    }

    /************
     * search() *
     ************/

    /**
     * Searches the given table by provided parameters. 
     * It can use WHERE clause to specify the selection.
     *
     * @param  string   $columns    Required parameter : you need to pass the 
     *                              names of the columns you want to select, 
     *                              you can pass them as an array or as an 
     *                              string with comma-separated values. 
     *                              Also if you want to select all columns in 
     *                              table you can pass *.
     *                              Examples: $columns = array('id', 'name');
     *                              $columns = 'id, name'; $columns = '*';
     *
     * @param  string   $where      Optional parameter : you can specify the 
     *                              WHERE part of the query using an associative 
     *                              array, where keys will be the string parts 
     *                              of the statements, like: 'name', and array 
     *                              values will be the search terms. 
     *                              Example:
     *                              ['code' => $code, ' OR name' => $name] 
     *                              will produce:
     *                              WHERE code LIKE :value1 OR name LIKE :value2 
     *                              , where :value1 and :value2 will be replaced 
     *                              with bound parameters. Please note that you 
     *                              do not have to write the LIKE clause 
     *                              yourself, DB class will do it for you, 
     *                              and that this class is doing full search 
     *                              with % signs on both side of the search term.
     *
     * @param  string   $order      Optional parameter : you can specify the 
     *                              ORDER BY clause.
     *
     * @param  integer  $limit      Optional parameter : you can specify the 
     *                              LIMIT clause.
     *
     * @param  integer  $offset     Optional parameter : you can specify the 
     *                              OFFSET part of the LIMIT clause.
     *
     * @return object               Statement handle.
     */
    public function search($columns, $where, $order = null, 
                           $limit = null, $offset = '')
    {
        // checks to see if submitted $columns is an array or string.
        self::checkColumns($columns);

        // checks to see which optional parameters user has submitted, 
        // and assigns them appropriate values.
        self::checkParams($where, $order, $limit, $offset);

        // set up our where clause optimized for searching
        self::buildWhere(self::$where, true);

        /**
         * Do searching
         **************/

        // no ORDER BY, no LIMIT
        if (is_null(self::$order) && is_null(self::$limit))
        {
            self::$sth = self::query("SELECT " . self::$columns . "
                                      FROM "   . static::$table . "
                                      WHERE "  . self::$where . " ");

            self::bindParams($where, 'search');
            return self::execute();
        } 
        // ORDER BY is listed but not LIMIT
        elseif (!is_null(self::$order) && is_null(self::$limit))
        {
            self::$sth = self::query("SELECT "   . self::$columns . "
                                      FROM "     . static::$table . "
                                      WHERE "    . self::$where . "
                                      ORDER BY " . self::$order . " ");

            self::bindParams($where, 'search');
            return self::execute();
        } 
        // LIMIT is listed but not ORDER BY
        elseif (is_null(self::$order) && !is_null(self::$limit))
        {
            // if there is offset, we will merge it with limit
            if ($offset !== '')
            {
                self::$offset = $offset . ',';
            }

            self::$sth = self::query("SELECT SQL_CALC_FOUND_ROWS " 
                                              . self::$columns . "
                                      FROM "  . static::$table . "
                                      WHERE " . self::$where . "
                                      LIMIT " . self::$offset 
                                              . self::$limit . " ");

            self::bindParams($where, 'search');
            return self::execute();
        } 
        // both ORDER BY and LIMIT are listed
        else
        {
            // if there is offset, we will merge it with limit
            if ($offset !== '')
            {
                self::$offset = $offset . ',';
            }

            self::$sth = self::query("SELECT SQL_CALC_FOUND_ROWS " 
                                                 . self::$columns . "
                                      FROM "     . static::$table . "
                                      WHERE "    . self::$where . "
                                      ORDER BY " . self::$order . "
                                      LIMIT "    . self::$offset 
                                                 . self::$limit . " ");

            self::bindParams($where, 'search');
            return self::execute();
        }
    }

    /**************
     * getCount() *
     **************/

    /**
     * Counts the number of rows in specified table by specified conditions. 
     * THIS is COUNT() abstraction method.
     *
     * @param  string   $columns    Required parameter : you need to pass the 
     *                              names of the columns you want to COUNT, 
     *                              you can pass them as an array or as an 
     *                              string with comma-separated values. 
     *                              Also if you want to COUNT all rows in table 
     *                              you can pass *.
     *                              Examples: $columns = array('id', 'name');
     *                              $columns = 'id, name'; $columns = '*';
     *
     * @param  array    $where      Optional parameter : you can specify the 
     *                              WHERE part of the query using an associative 
     *                              array, where keys will be the string parts 
     *                              of the statements, like: 'name =', and array 
     *                              values will be values for those conditions. 
     *                              Example:
     *                              ['id >=' => $id, ' AND price=' => $price] 
     *                              will produce:
     *                              WHERE id >= :value1 AND price = :value2 , 
     *                              where :value1 and :value2 will be replaced 
     *                              with bound parameters.
     *
     * @return integer              Number of rows affected by COUNT function.
     */
    public static function getCount($columns, $where = null)
    {
        // checks to see if submitted $columns is an array or string.
        self::checkColumns($columns);

        // set up our where clause
        self::buildWhere($where);

        // WHERE part of the query is submitted
        if (!is_null(self::$where))
        {
            self::$sth = self::query("SELECT 
                                      COUNT(" . self::$columns . ")
                                      FROM "  . static::$table . "
                                      WHERE " . self::$where . " ");

            self::bindParams($where);
            self::$sth->execute();
            self::$lastQuery = self::$sth;
            $count = self::$sth->fetch(PDO::FETCH_COLUMN);
            return $count;
        } 
        else
        {
            self::$sth = self::query("SELECT 
                                      COUNT(" . self::$columns . ")
                                      FROM "  . static::$table . " ");

            self::$sth->execute();
            self::$lastQuery = self::$sth;
            $count = self::$sth->fetch(PDO::FETCH_COLUMN);
            return $count;
        }
    }

    /***********
     * count() *
     ***********/

    /**
     * Counts the number of affected rows by get*() methods, as well as join
     * and search methods, when LIMIT is used.
     *
     * @return integer             Number of rows affected by SELECT statements.
     */
    public function count()
    {
        self::$sth = self::query("SELECT FOUND_ROWS()");
        self::$sth->execute();
        self::$lastQuery = self::$sth;
        $count = self::$sth->fetch(PDO::FETCH_COLUMN);
        return $count;
    }

//------------------------------------------------------------------------------

    /**
     ***************************************************************************
     * DB abstraction functions - delete collection ( DELETE )
     **************************************************************************/

    /************
     * delete() *
     ************/

    /**
     * Deletes the row by provided $id or custom WHERE clause.
     *
     * @param  array    $where      Optional parameter : 
     *                              In case you are doing deletion using this 
     *                              parameter, you need to supply your condition 
     *                              as an associative array where array key will 
     *                              contain column name and operator, and array 
     *                              value should be the value 
     *                              ( the actual condition ) for your deletion. 
     *                              Example: ['name = ' => $name], will delete 
     *                              the row WHERE name = whatever the name was 
     *                              in the $name variable. In case you are 
     *                              deleting by $id, $where parameter should NOT 
     *                              be specified.
     *
     * @param  integer  $id         Optional parameter : 
     *                              In case you are doing deletion by $id, you 
     *                              need to pass the id as deletion criteria.
     *                              If you are using custom $where, you should 
     *                              NOT specify the $id.
     *
     * @return integer              Number of rows affected by deletion.
     */
    public function delete($where = null, $id = null)
    {
        // builds WHERE condition
        self::buildWhereDelete($where, $id);

        // we are deleting by id
        if (is_null(self::$where))
        {
            self::$sth = self::query("DELETE 
                                      FROM " . static::$table . "
                                      WHERE  id = :id 
                                      LIMIT  1 ");

            self::$sth->bindParam(':id', self::$id, PDO::PARAM_INT);
            self::$sth->execute();
            self::$lastQuery = self::$sth;
            return self::$sth->rowCount();
        } 
        // we are deleting using custom WHERE
        else
        {
            self::$sth = self::query("DELETE 
                                      FROM "   . static::$table . "
                                      WHERE "  . self::$where . "
                                      LIMIT 1 ");
 
            self::bindParams($where);
            self::$sth->execute();
            self::$lastQuery = self::$sth;
            return self::$sth->rowCount();
        }
    }

    /***************
     * deleteAll() *
     ***************/

    /**
     * Deletes all records from the specified table.
     *
     * @return integer              Number of rows affected by deletion.
     */
    public function deleteAll()
    {
        self::$sth = self::query("DELETE FROM " . static::$table . " ");
        self::$sth->execute();
        self::$lastQuery = self::$sth;
        return self::$sth->rowCount();
    }

//------------------------------------------------------------------------------

    /**
     ***************************************************************************
     * DB abstraction functions - create/update/save collection (INSERT/UPDATE)
     **************************************************************************/

    /************
     * create() *
     ************/

    /**
     * Function that is doing SQL INSERT.
     *
     * @param  array    $columns    Required parameter : associative array of 
     *                              key=>value pairs representing columns
     *                              and values that will be inserted.
     *
     * @return integer              Number of affected rows, if any.
     */
    protected function create($columns)
    {
        // sets the column names
        $columnNames = join(", ", array_keys($columns));

        // sets the values named placeholders
        self::buildPlaceholders($columns);

        // inserts data into database       
        self::$sth = self::query("INSERT INTO " . static::$table . "
                                  ( " . $columnNames . " ) 
                                  VALUES ( " . self::$placeholder . " ) ");

        // bind parameters to placeholder values
        self::bindParams($columns);

        self::$sth->execute();
        self::$lastQuery = self::$sth;

        // returns the number of affected rows.         
        return self::$sth->rowCount();
    }

    /************
     * update() *
     ************/

    /**
     * Function that is doing sql UPDATE.
     *
     * @param  array    $columns    Required parameter : associative array of 
     *                              key=>value pairs representing columns
     *                              and values that will be updated.
     *
     * @param  array    $condition  Required parameter : associative array of 
     *                              key=>value pairs representing the WHERE
     *                              clause (condition for updating).
     *
     * @return int                  Number of affected rows, if any.
     */
    protected function update($columns, $condition)
    {
        // build columns as an string with comma-separated values
        self::buildUpdate($columns);

        // make sure WHERE condition is in the right format
        self::buildWhere($condition);

        // updates      
        self::$sth = self::query("UPDATE " . static::$table . "
                                  SET "    . self::$columns . "
                                  WHERE "  . self::$where . " ");

        // bind parameters to placeholder values for the UPDATE part
        self::bindParams($columns, 'update');

        // bind parameters to placeholder values for the WHERE part
        self::bindParams($condition);

        self::$sth->execute();
        self::$lastQuery = self::$sth;

        // returns the number of affected rows.             
        return self::$sth->rowCount();
    }

    /**********
     * save() *
     **********/

    /**
     * Function that is calling create/update methods depending if condition 
     * is passed to it. If $condition in not passed then it will do the create 
     * method, otherwise it will update.
     *
     * @param  array    $columns    Required parameter : array of key=>value 
     *                              pairs representing columns and values that 
     *                              will be updated/inserted
     *                              Example: ['username' => $username] 
     *                              will produce:
     *                              SET username = johndoe, or whatever was 
     *                              inside $username.
     *
     * @param  array    $condition  Optional parameter : array of key=>value 
     *                              pairs representing the WHERE clause 
     *                              (condition for updating).
     *                              Example: ['username = ' => $username] 
     *                              will produce:
     *                              WHERE username = johndoe, or whatever was 
     *                              inside $username.
     * @return boolean
     */
    public function save(array $columns, $condition = null)
    {
        // $condition is not specified that means creation should be done
        if (is_null($condition))
        {
            $result = self::create($columns);

            // INSERT is done
            if ($result > 0)
            {
                return true;
            } 
            //INSERT failed
            else
            {
                return false;
            }
        } 
        // requests update
        else
        {
            $result = self::update($columns, $condition);

            // UPDATE done
            if ($result > 0)
            {
                return true;
            } 
            // UPDATE failed
            else
            {
                return false;
            }
        }
    }

//------------------------------------------------------------------------------

    /**
     ***************************************************************************
     * DB abstraction functions - helper method ( display query )
     **************************************************************************/

    /***************
     * showQuery() *
     ***************/

    /**
     * Method that is displaying last executed query. 
     * Please use only for debug/learn purpose. 
     * You should never display this information to end users.
     *
     * @provide string      Query string.
     */
    public static function showQuery()
    {
        echo "<hr>";
        echo "<code>";
        var_dump(self::$lastQuery);
        echo "</code>";
        echo "<br>";
        echo "<hr>";
    } 
}

//------------------------------------------------------------------------------

// We need to initialize the object here, because PDO connection was opened in
// the constructor of the class, and we are using static properties and methods
// by default.
$db = new DB();