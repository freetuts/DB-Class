<?php
require_once('loader.php');
        
/**
 * Info example class file.
 *
 * @author Nenad Zivkovic <nenad@freetuts.org>
 * @link http://www.freetuts.org
 * @copyright 2014-present freetuts.org
 * @license http://www.freetuts.org/site/page?view=licensing
 */


/**
 * Info model, representing database info table.
 */
class Info extends DB
{
    protected static $table = 'info';

    private $title = "This is some title";
    private $body  = "This is some main text";
    private $extra = "This is something extra";

/**
 *******************************************************************************
 * Save examples
 ******************************************************************************/

    /**
     * Save() example ( INSERT )
     */
    public function actionCreate()
    {
        $values = ['title' => $this->title, 
                   'body'  => $this->body, 
                   'extra' => $this->extra];

        return Info::save($values);
    }

    /**
     * Save() example ( UPDATE )
     */
    public function actionUpdate()
    {
        // $values    = ['title' => 'Updated title',
        //               'body'  => 'Updated main text'];

        $values    = ['title' => 'Updated title'];
        $condition = ['id =' => 6];

        return Info::save($values, $condition);
    }

/**
 *******************************************************************************
 * Delete examples
 ******************************************************************************/

    /**
     * Delete() example
     */
    public function actionDelete($id = null)
    {
        return Info::delete($id);
        //return Info::delete(['title = '=>'This is some title']);
        //return Info::deleteAll();
    }
}