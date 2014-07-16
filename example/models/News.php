<?php
require_once('loader.php');
       
/**
 * News example class file.
 *
 * @author Nenad Zivkovic <nenad@freetuts.org>
 * @link http://www.freetuts.org
 * @copyright 2014-present freetuts.org
 * @license http://www.freetuts.org/site/page?view=licensing
 */


/**
 * News model, representing database news table.
 */
class News extends DB
{
    protected static $table = 'news';

    /**
     ***************************************************************************
     * get examples ( SELECT )
     **************************************************************************/

    /**
     * get() example
     */
    public static function getNews()
    {
        $columns = 'title, body, note';
        $where   = ['id BETWEEN' => 4, 'AND' => 7];
        $order   = 'id DESC';
        $limit   = 5;

        return News::get($columns, $where, $order, $limit);
    }

    /**
     * get() example - writing our "queries" withoud assigning query conditions
     * to variables.
     */
    // public static function getNews()
    // {
    //     return News::get(title, body, note', 'id DESC', 5);
    // }


    /**
     * getAll() example
     */
    public static function listNews()
    {
        $where  = ['id <=' => 7];
        $order  = 'title DESC';
        $limit  = 10;
        //$offset = 2;

        return News::getAll($where, $order, $limit);

        // example how you can use rowCount() to get 
        // the number of affected rows inside your model,
        // in case you want to do something with that number;
        // $retval = News::getAll($where, $order, $limit);
        // $number = $retval->rowCount();
        // return $retval; // we are still returning object.

        // example how to write your raw queries using query() method.
        // $sth = DB::query("SELECT * FROM news");
        // $sth->setFetchMode(PDO::FETCH_CLASS, 'News');
        // $sth->execute();
        // return $sth;    
    }    

    /**
     * getById() example
     */
    public static function getOneNews()
    {
        // TIP: you can pass columns as an array or as string 
        // with comma separated values.

        $id = 5;
        $columns = 'title, body, note';
        //$columns = ['id', 'name', 'country_code'];

        return News::getById($columns, $id);
    }   

    /**
     * getGrouped() example
     */
    public static function getGroupedRatings()
    {
        $columns = 'SUM(rating) as totalRating, authorId';
        $where   = ['rating >=' => 2];
        $group   = 'authorId';
        $having  = ['totalRating >=' => 7];
        $order   = 'totalRating DESC';
        $limit   = 5;
        //$offset  = 1;
        
        return News::getGrouped($columns, $where, $group,
                                $having, $order, $limit);
    }

    /**
     * getCount() example
     */
    public static function getCountedAuthors()
    {
        $columns = 'DISTINCT(authorId)';
        //$where   = ['rating >= ' => 2];

        return News::getCount($columns);
    }

    /**
     * *************************************************************************
     * search() examples
     **************************************************************************/

    /**
     * search() example
     */
    public static function searchNews()
    {
        $columns = '*';
        //$where   = ['title' => 'news', 'AND body' => 'text'];
        $where   = ['title' => '15 16', 'OR body' => '15 16'];
        $order   = 'id';
        $limit   = 10;
        //$offset  = 1;

        return News::search($columns, $where, $order, $limit);
    }
}