<?php
require_once('../../../../wp-load.php');
 get_header(); 

 $code = @$_GET['code'];
 echo $code;
 ?>

<div>

	your custom php code goes here

</div>

<?php get_footer(); ?>