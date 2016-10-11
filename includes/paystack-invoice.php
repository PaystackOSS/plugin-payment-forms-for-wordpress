<?php
require_once('../../../../wp-load.php');

$code = @$_GET['code'];

$mode =  esc_attr( get_option('mode') );
if ($mode == 'test') {
	$key = esc_attr( get_option('tpk') );
}else{
	$key = esc_attr( get_option('lpk') );
}
 ?>

<?php
/**
 * Template Name: Alphabetical Posts
 */
 
get_header(); ?>
<div class="content-area main-content" id="primary">
	<main role="main" class="site-main" id="main">
		<div class="blog_post">
			<article class="post-4 page type-page status-publish hentry" id="post-4">
				<form action="" method="" class="j-forms" id="pf-form" novalidate="">
					<div class="content">

						<div class="divider-text gap-top-20 gap-bottom-45">
							<span>Input</span>
						</div>

						<div class="j-row">
							<div class="span12 unit">
								<label class="label">Text input without icon <span>*</span></label>
								<div class="input">
									<input type="text" placeholder="placeholder text...">
								</div>
							</div>
							<div class="span12 unit">
								<label class="label">Text input without icon</label>
								<label class="select">
									<select autocomplete="off">
										<option value="none">Select fruit</option>
										<option value="0">Apple</option>
										<option value="1">Banana</option>
										<option value="2">Coconut</option>
										<option value="3">Mango</option>
										<option value="4">Melon</option>
										<option value="5">Orange</option>
										<option value="6">Pear</option>
										<option value="7">Watermelon</option>
									</select>
									<i></i>
								</label>
							</div>
							<div class="span12 unit">
								<label class="input append-small-btn">
									<div class="file-button">
										Browse
										<input type="file" onchange="document.getElementById('append-small-btn').value = this.value;">
									</div>
									<input type="text" id="append-small-btn" readonly="" placeholder="no file selected">
								</label>
							</div>
							<div class="span12 unit">
								<label class="label">Textarea</label>
								<div class="input">
									<textarea placeholder="your message..." spellcheck="false" id="textarea"></textarea>
								</div>
							</div>
						</div>
					</div>

					<div class="footer">
						<small><span style="color: red;">*</span> are compulsory</small><br>
							<img class="paystack-cardlogos size-full wp-image-1096" alt="cardlogos" src="http://localhost/wordpress/wp-content/plugins/paystack-forms/public/../images/logos@2x.png">
						<button type="submit" class="primary-btn">Submit</button>
					</div>
				</form>
			</article>
		</div>
	</main>
</div>
<?php
get_footer();