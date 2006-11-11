<?php

require_once(dirname(__FILE__).'/../../../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'TemplateEngines'.DS.'AkSintags.php');


class Test_of_AkSintags extends  UnitTestCase
{
    function Test_for_AkSintags_to_PHP_Conversion()
    {
        
$SINTAGS_TEXT = <<<AKTAX
{comment}
{post.Comments}
{post.Comments.latest}
{people-members}
{people-0-member}
{people.members-0.name}
{posts-latest.created_at}
{?post-comments}
    {post.comment.details}
{end}
{Post.comment.details?}
{loop posts}
    <q> {post.comment?} {post.author?} </q>
{end}
{_post.comment}
<h1>_{Ain't easy to translate text?}</h1>
_{<p>It's really simple even to add 
<a href='http://google.co.uk'>Localized links</a>
</p>}
_{I need to print \\{something_inside_curly_brackets\\}. _\\{Maybe a multilingual text example\\} }
AKTAX;


$EXPECTED_PHP = '<?php 
 echo $comment;
?>
<?php 
 echo $post->Comments;
?>
<?php 
 echo $post->Comments->latest;
?>
<?php 
 echo $people[\'members\'];
?>
<?php 
 echo $people[0][\'member\'];
?>
<?php 
 echo $people->members[0]->name;
?>

<?php 
 echo $posts[\'latest\']->created_at;
?>
<?php 
 if(!empty($post[\'comments\'])) { 
?>
    <?php 
 echo $post->comment->details;
?>
<?php } ?>
<?php 
 echo isset($Post->comment->details) ? $Post->comment->details : \'\';
?>



<?php 
 empty($posts) ? null : $post_loop_counter = 0;
 empty($posts) ? null : $posts_available = count($posts);
 if(!empty($posts))
     foreach ($posts as $post_loop_key=>$post){
         $post_loop_counter++;
         $post_is_first = $post_loop_counter === 1;
         $post_is_last = $post_loop_counter === $posts_available;
         $post_odd_position = $post_loop_counter%2;
?>
    <q> <?php 
 echo isset($post->comment) ? $post->comment : \'\';
?> <?php 
 echo isset($post->author) ? $post->author : \'\';
?> </q>
<?php } ?>
<?php 
 echo empty($post->comment) || !is_array($post->comment) ? \'\' : $text_helper->translate($post->comment);
?>
<h1><?php echo $text_helper->translate(\'Ain\\\'t easy to translate text?\', array()); ?></h1>
<?php echo $text_helper->translate(\'<p>It\\\'s really simple even to add 

<a href=\\\'http://google.co.uk\\\'>Localized links</a>
</p>\', array()); ?>
<?php echo $text_helper->translate(\'I need to print {something_inside_curly_brackets}. _{Maybe a multilingual text example} \', array()); ?>';
        $AkSintags = new AkSintags();
        $AkSintags->init(array('code'=>$SINTAGS_TEXT));
        //echo $AkSintags->toPhp();
        $this->assertEqual(trim(str_replace(array("\n","\r","\t",'  ',' '),array(''), $AkSintags->toPhp())), trim(str_replace(array("\n","\r","\t",'  ',' '),array(''), $EXPECTED_PHP)));

    }
}

Ak::test('Test_of_AkSintags');

?>
