<?php

class Kkd_Pff_Paystack_webhook{
	
	/**
	*	@var string Pug Bomb Headquarters
	*/
	
	
	/** Hook WordPress
	*	@return void
	*/
	public function __construct(){
		add_filter('query_vars', array($this, 'add_query_vars'), 0);
		add_action('parse_request', array($this, 'sniff_requests'), 0);
		add_action('init', array($this, 'add_endpoint'), 0);
	}	
	
	/** Add public query vars
	*	@param array $vars List of current public query vars
	*	@return array $vars 
	*/
	public function add_query_vars($vars){
		$vars[] = '__api';
		$vars[] = 'pugs';
		return $vars;
	}
	
	/** Add API Endpoint
	*	This is where the magic happens - brush up on your regex skillz
	*	@return void
	*/
	public function add_endpoint(){
		add_rewrite_rule('/ kkd/wpffp/webhook/','index.php?__api=1&pugs=$matches[1]','top');
	}
	/**	Sniff Requests
	*	This is where we hijack all API requests
	* 	If $_GET['__api'] is set, we kill WP and serve up pug bomb awesomeness
	*	@return die if API request
	*/
	public function sniff_requests(){
		global $wp;
		if(isset($wp->query_vars['__api'])){
			$this->handle_request();
			exit;
		}
	}
	
	/** Handle Requests
	*	This is where we send off for an intense pug bomb package
	*	@return void 
	*/
	protected function handle_request(){
		global $wp;
		$pugs = $wp->query_vars['pugs'];
		if(!$pugs)
			$this->send_response('Please tell us how many pugs to send.');
		
		$pugs = file_get_contents(get_site_url().'/kkd/wpffp/webhook/'.$pugs);
		if($pugs)
			$this->send_response('200 OK', json_decode($pugs));
		else
			$this->send_response('Something went wrong with the pug bomb factory');
	}
	
	/** Response Handler
	*	This sends a JSON response to the browser
	*/
	protected function send_response($msg, $pugs = ''){
		$response['message'] = $msg;
		if($pugs)
			$response['pugs'] = $pugs;
		header('content-type: application/json; charset=utf-8');
	    echo json_encode($response)."\n";
	    exit;
	}
}
new Kkd_Pff_Paystack_webhook();
