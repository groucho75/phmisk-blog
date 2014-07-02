<?php



/** -----------------------------------------------------------------
 * Blog routes
 * ------------------------------------------------------------------ */


/**
 * Init php session
 */ 
$ph4->sess->start();


/**
 * Init the Blog controller
 */
$blog = new App\Controllers\Blog($ph4);


/**
 * Secure the admin area
 */
$ph4->router->before('GET|POST', '/admin/.*', function() use ( $ph4 ) {

	$auth = new App\Libraries\Httpauth('Admin area');
	
	// Set you allowed users: username => password
	$users = array('editor' => 'secret123');
	
	if ( ! $auth->checkAuth($users, true) )
	{
		$data = array(
			'msg' 		=> 'Ehi! Your username/password are wrong',
			'text'		=> 'For the session you are not authorised: please close your browser and retry.',
			'glyphicon' => 'glyphicon-thumbs-down'
		);
		
		$ph4->tpl->assign( $data );
		$ph4->tpl->draw( 'message' );
		
		exit();
	}
});


/**
 * Blog admin side
 */
$ph4->router->get('/admin', function() {
	
	redirect('/admin/news/index');
});

$ph4->router->mount('/admin/news', function() use ($ph4, $blog) {
	
	$ph4->router->get('/', function() {
		
		redirect('/admin/news/index');
    });


	/**
	 * Show the post list
	 */    
    $ph4->router->get('/index(/\d+)?', function( $page=NULL ) use ( $blog ) {

		$blog->adminIndex($page);
    });


	/**
	 * Init the edit form validator
	 */
	$blog->adminInitValidator();

    
    /**
     * Show the edit/add form
     */
    $ph4->router->get('/edit(/\d+)?', function( $id=NULL ) use ($blog) {

		$blog->adminEditGet($id);
    });


    /**
     * Check submitted form
     */
	$ph4->router->post('/edit(/\d+)?', function( $id=NULL ) use ($blog) {   

		$blog->adminEditPost($id);
    });
    

    /**
     * Delete a post
     */    
    $ph4->router->get('/delete/(\d+)/([a-zA-Z0-9]+)', function( $id, $token ) use ($blog) {

		$blog->adminDelete($id, $token);
    });
    
});


/**
 * Blog front side
 */
$ph4->router->mount('/news', function() use ($ph4, $blog) {

	$ph4->router->get('/', function() {
		
		redirect('/news/index/1');
    });
    	
	/**
	 * The post list
	 */
    $ph4->router->get('/index(/\d+)?', function( $page=NULL ) use ($blog) {

		$blog->frontIndex($page);
    });


	/**
	 * The post single page
	 */
    $ph4->router->get('/read/([a-z0-9_-]+)', function( $post_nicename=NULL ) use ($blog) {

		$blog->frontRead($post_nicename);
    });


	/**
	 * The RSS feed
	 */
	$ph4->router->get('/feed', function() use ($blog) {
		
		$blog->feed();
    });
});


/* EOF */
