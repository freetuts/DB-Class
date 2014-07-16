<?php
require_once('loader.php');
        
/**
 * Author example class file.
 *
 * @author Nenad Zivkovic <nenad@freetuts.org>
 * @link http://www.freetuts.org
 * @copyright 2014-present freetuts.org
 * @license http://www.freetuts.org/site/page?view=licensing
 */


/**
 * Author model, representing database author table.
 */
class Author extends DB
{
    protected static $table = 'author';

    /**
     * These properties are automatically created with get(), join() 
     * and search() methods when they query the database, since they are using 
     * PDO::FETCH_CLASS fetching method by default. 
     * You can overwrite them if you need to manipulate their values. 
     * See example below.
     * 
     * @var property $username
     * @var property $title
     * @var property $body
     * And maybe others that are not used in our view
     */
    
    public $title = ''; // This is example that you can declare 
                        // properties yourself to use them for further 
                        // processing, if you need to.

    function __construct() 
    {
        // we are changing one title before we output it to the users.
        $this->changeTitle();
    }

    /**
     ***************************************************************************
     * getGrouped() examples
     **************************************************************************/

    /**
     * getGrouped() example.
     */
    public static function getAuthor($id)
    {
        $result = Author::getById('username', $id);

        foreach ($result as $author) 
        {
            return $author->username;
        }
    }

    /**
     ***************************************************************************
     * join() example
     **************************************************************************/

    /**
     * join() example
     */
    public static function joinTables()
    {
        $table   = 'news, LEFT';
        $columns = 'author.username, news.title, news.body';
        $on      = 'author.id = news.authorId';
        //$where   = ['news.rating >=' => 2];
        //$order   = 'author.id';
        //$limit   = 10;
        //$offset  = 2;

        return Author::join($table, $columns, $on);

        //return Author::join($table, $columns, $on, $where, $order, $limit);        
    }

    /**
     * Just an example on how we can change the data comming from database
     * before we output it to users.
     *
     * We need to invoke this function inside our constructor.
     */
    private function changeTitle()
    {
        if ($this->title == 'Live together die alone!') 
        {
            $this->title = 'Changed title';
        }
    }  
}