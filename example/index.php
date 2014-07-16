<?php
require_once('loader.php');
        
/**
 * index.php example view file.
 *
 * @author Nenad Zivkovic <nenad@freetuts.org>
 * @link http://www.freetuts.org
 * @copyright 2014-present freetuts.org
 * @license http://www.freetuts.org/site/page?view=licensing
 */

?>

<!DOCTYPE html>
<html>
<head>
    <title> DB class usage examples | Read </title>

    <style>
    table,th,td
    {
        border: 2px solid black;
        border-collapse: collapse;
    }
    th,td
    {
        padding:5px;
    }
    .title
    {
        width: 20%;
    }
    .text
    {
        width: 60%;
    }
    .note
    {
        width: 20%;
    }
    </style>    
</head>
<body>

<?php

    /**
     * *************************************************************************
     * get examples ( SELECT )
     **************************************************************************/

    /***************************
     * Example for get() method
     **************************/

    $result = News::getNews();

    echo "<h3> get() example. </h3>";

    DB::showQuery();

    ?>

    <table>

        <tr>
            <th class="title">Title</th> 
            <th class="text">Text</th> 
            <th class="note">Notes</th>
        </tr>

        <?php foreach ($result as $news) : ?>  

        <tr>
            <td><?php echo $news->title ?></td>
            <td><?php echo $news->body ?></td>
            <td><?php echo $news->note ?></td>
        </tr>

        <?php endforeach; ?>

    </table>

    <br>

    <?php

    /******************************
     * Example for getAll() method
     *****************************/

    $result = News::listNews();

    echo "<h3> getAll() example. </h3>";

    $count = $result->rowCount();
    echo "Counted using rowCount() - " . $count . "<br>";

    // If you need to know how many rows were affected by SELECT kind methods 
    // that were using LIMIT, you can just 
    // use count() method and it will show you.
    echo "Number of rows affected 
          by this getAll() method are: " . DB::count()."<br><br>";

    ?>

    <table>

        <tr>
            <th class="title">Title</th> 
            <th class="text">Text</th> 
        </tr>

        <?php foreach ($result as $news) : ?>  

        <tr>
            <td><?php echo $news->title ?></td>
            <td><?php echo $news->body ?></td>
        </tr>

        <?php endforeach; ?>

    </table>

    <br>

    <?php

    /*******************************
     * Example for getById() method
     ******************************/

    $result = News::getOneNews();

    echo "<h3> getById() example. </h3>";

    ?>

    <table>

        <tr>
            <th class="title">Title</th> 
            <th class="text">Text</th> 
            <th class="note">Notes</th>
        </tr>

        <?php foreach ($result as $news) : ?>  

        <tr>
            <td><?php echo $news->title ?></td>
            <td><?php echo $news->body ?></td>
            <td><?php echo $news->note ?></td>
        </tr>

        <?php endforeach; ?>

    </table>

    <br>
    
    <?php

    /**********************************
     * Example for getGrouped() method
     *********************************/

    $result = News::getGroupedRatings();

    echo "<h3> getGrouped() example. </h3>";

    DB::showQuery();

    ?>

    <table>

        <tr>
            <th class="title">Author ID</th> 
            <th class="text">Username</th> 
            <th class="note">Total Rating</th>
        </tr>

        <?php foreach ($result as $news) : ?>  

        <tr>
            <td><?php echo $news->authorId ?></td>
            <td><?php echo Author::getAuthor($news->authorId); ?></td>
            <td><?php echo $news->totalRating ?></td>
        </tr>

        <?php endforeach; ?>

        <?php DB::showQuery(); ?>

    </table>

    <br>

    <?php

    /********************************
     * Example for getCount method
     *******************************/

    echo "<h3> getCount() example. </h3>";

    $counted = News::getCountedAuthors();

    DB::showQuery();

    echo 'Number of authors is: '.$counted;

/**
 * *****************************************************************************
 * JOIN examples
 ******************************************************************************/

    $result = Author::joinTables();

    echo "<h3> join() example. </h3>";

    DB::showQuery();

    echo "<br>";

    echo "Number of rows affected 
          by this join() method are: " . DB::count()."<br>";

    echo "<br>";

    ?>

    <table>

        <tr>
            <th class="title">Username</th> 
            <th class="note">Title</th> 
            <th class="text">Text</th>
        </tr>

        <?php foreach ($result as $data) : ?> 

        <tr>
            <td><?php echo $data->username ?></td>
            <td><?php echo $data->title ?></td>
            <td><?php echo $data->body ?></td>
        </tr>

        <?php endforeach; ?>

    </table>

    <br>

    <?php   


/**
 * *****************************************************************************
 * search examples
 ******************************************************************************/

    $result = News::searchNews();

    echo "<h3> search() example. </h3>";

    DB::showQuery();

    ?>

    <table>

        <tr>
            <th class="title">Title</th> 
            <th class="text">Text</th> 
            <th class="note">Note</th>
        </tr>

        <?php foreach ($result as $news) : ?>  

        <tr>
            <td><?php echo $news->title ?></td>
            <td><?php echo $news->body ?></td>
            <td><?php echo $news->note ?></td>
        </tr>

        <?php endforeach; ?>

    </table>

    <br>
    
</body>
</html>
