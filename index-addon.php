<?php



/** -----------------------------------------------------------------
 * Blog routes
 * ------------------------------------------------------------------ */


/**
 * Init php session
 */ 
$ph4->sess->start();


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

$ph4->router->mount('/admin/news', function() use ($ph4) {
	
	$ph4->router->get('/', function() {
		redirect('/admin/news/index');
    });


	/**
	 * Show the post list
	 */    
    $ph4->router->get('/index(/\d+)?', function( $page=NULL ) use ( $ph4 ) {
	
		$alert = $ph4->sess->getFlash('form_alert', FALSE);
		$alert_class = $ph4->sess->getFlash('form_alert_class', 'info');

		$total_posts = $count = $ph4->db->from('posts')->count();
		
		if ( $total_posts > 0 )
		{
			// Posts per page
			$limit = 10;

			$max_page = ceil( $total_posts / $limit );

			if ( empty($page) ) $page = 1;
			
			// Cannot ask page greater than max
			$page = min( $max_page, (int)$page);
			
			$offset = $limit * ($page-1);
						
			$posts =	$ph4->db->from('posts')
						->sortDesc('updated')
						->limit( $limit )
						->offset( $offset )
						->many();

			$pagination = array('newer' => max($page-1,1), 'older' => min($page+1, $max_page), 'current' => $page, 'total' => $max_page);
		}
		else
		{
			$posts = FALSE;
			$pagination = FALSE;
		}
		
		$data = array(
			'msg'     	=> 'Latest posts',
			'posts'   	=> $posts,
			'alert'		=> $alert,
			'alert_class'	=> $alert_class,
			'token' => NoCSRF::generate( 'csrf_token' ),
			'pagination'	=> $pagination,
		);
		
		$ph4->tpl->assign( $data );
		$ph4->tpl->draw( 'news.admin.list' );
    });


	/**
	 * Init the edit form
	 */
    $validator = new HybridLogic\Validation\Validator();
    $validator
        ->set_label('title', 'Title')
        ->set_label('nicename', 'SEO nicename')
        ->set_label('summary', 'Summary')
        ->set_label('content', 'Content')
        ->set_label('published', 'Published')
        ->set_label('csrf_token', 'Security token')
        
        ->add_filter('nicename', 'strtolower')
        
        ->add_rule('title', new HybridLogic\Validation\Rule\NotEmpty())
        ->add_rule('title', new HybridLogic\Validation\Rule\MaxLength(100))
        
        ->add_rule('nicename', new HybridLogic\Validation\Rule\NotEmpty())
        ->add_rule('nicename', new HybridLogic\Validation\Rule\AlphaSlug())
        ->add_rule('nicename', new HybridLogic\Validation\Rule\MaxLength(100))

        ->add_rule('summary', new HybridLogic\Validation\Rule\NotEmpty())
        ->add_rule('summary', new HybridLogic\Validation\Rule\MaxLength(250))

        ->add_rule('content', new HybridLogic\Validation\Rule\NotEmpty())
        
        ->add_rule('published', new HybridLogic\Validation\Rule\NumRange(0, 1))

		->add_rule('csrf_token', new HybridLogic\Validation\Rule\NotEmpty())
        ->add_rule('csrf_token', new App\Libraries\CsrfRule())
    ;

    $jquery_validator = new HybridLogic\Validation\ClientSide\jQueryValidator($validator);
    $jquery = $jquery_validator->generate();
    
    
    /**
     * Show the edit/add form
     */
    $ph4->router->get('/edit(/\d+)?', function( $id=NULL ) use ($ph4, $validator, $jquery_validator, $jquery) {
		
		$prev_values = NULL;
		
		if ( $id )
		{
			$post =	$ph4->db->from('posts')
						->where('id', $id)
						->one();

			if ( $post )
			{
				$prev_values = $post;
			}
			else
			{
				$ph4->sess->setFlash('form_alert', 'Post not found');
				$ph4->sess->setFlash('form_alert_class', 'danger');
				
				redirect('/admin/news/index');			
			}
		}
		
        // Looking for previous errors
        $errors = NULL;
        $form_errors = $ph4->sess->getFlash('form_errors', FALSE);
        if ( $form_errors )
        {
            foreach ( $form_errors as $field => $msg )
            {
                $errors[$field] = array( 'field' => $field, 'msg' => $msg );
            }
        }

        // Looking for previous submitted values
        $form_prev_values = $ph4->sess->getFlash('form_prev_values', FALSE);
        if ( $form_prev_values )
        {
            foreach ( $form_prev_values as $field => $value )
            {
                $prev_values[$field] = $value;
            }
        }

        // Send all to template     
        $data = array(
            'msg'       => ( $id ) ? 'Edit: '.$post['title'] : 'Add new',
            'jquery_validator'  => $jquery_validator,
            'jquery'    => $jquery,
            'errors'    => $errors,
            'prev_values' => $prev_values,
            'token' => NoCSRF::generate( 'csrf_token' ),
        );

		$ph4->tpl->assign( $data );
		$ph4->tpl->draw( 'news.admin.edit' );             
    });


    /**
     * Check submitted form
     */
	$ph4->router->post('/edit(/\d+)?', function( $id=NULL ) use ($ph4, $validator) {   

		$_POST = array_map_recursive( 'trim', $_POST );
		$_POST = array_map_recursive( 'strip_tags', $_POST );

        if( $validator->is_valid($_POST) )
        {
            $posted = $validator->get_data();
            
			unset($posted['csrf_token']);
			$posted['updated'] = now();
			
			// To be sure nicename is unique
			$proposed_nicename = $posted['nicename'];
			do {
				$ph4->db->from('posts')->where('nicename', $proposed_nicename);
						
				if ( $id ) $ph4->db->where('id != ?', $id);
				
				$existing_nicename = $ph4->db->one();
				
				if ( ! $existing_nicename )
				{
					$posted['nicename'] = $proposed_nicename;
					break;
				}
				
				$proposed_nicename = $posted['nicename'] . '-'. (string)rand( 1000, 9999 );
				
			} while (true);
			
			if ( $id )
			{
				$post =	$ph4->db->from('posts')
						->where("id", $id)
						->update($posted)
						->execute();											
			}
			else
			{
				$post = $ph4->db->from('posts')
						->insert($posted)
						->execute();				
			}

			if ( $ph4->db->affected_rows == 1 )
			{
				$ph4->sess->setFlash('form_alert', 'Post saved');
				$ph4->sess->setFlash('form_alert_class', 'success');				
			}
			else
			{
				$ph4->sess->setFlash('form_alert', 'Impossible to save the post');
				$ph4->sess->setFlash('form_alert_class', 'danger');					
			}


            redirect('/admin/news/index');
        }
        else
        {
            $ph4->sess->setFlash('form_errors', $validator->get_errors() );
			$ph4->sess->setFlash('form_prev_values', $validator->get_data() );
			
            redirect('/admin/news/edit/'.(string)$id);
        }       
    });


    /**
     * Show positive result: thank-you page
     */
    $ph4->router->get('/thankyou', function() use ($ph4) {

        $data = array(
            'msg'       => 'Thank you!',
            'text'      => 'Your data is important for us.',
        );

        $ph4->tpl->assign( $data );
        $ph4->tpl->draw( 'message' );  
    });
    

    /**
     * Delete a post
     */    
    $ph4->router->get('/delete/(\d+)/([a-zA-Z0-9]+)', function( $id, $token ) use ($ph4) {

        try
        {
            // Run CSRF check, on POST data, in exception mode, with a validity of 2 hours, in one-time mode.
            NoCSRF::check( 'csrf_token', array( 'csrf_token' => $token ), true, 60*60*2, false );
			
			$ph4->db->from('posts')->where("id", $id)->delete()->execute();

			if ( $ph4->db->affected_rows == 1 )
			{
				$ph4->sess->setFlash('form_alert', 'Post '.$id.' deleted');
				$ph4->sess->setFlash('form_alert_class', 'success');					
			}
			else
			{
				$ph4->sess->setFlash('form_alert', 'Impossible to delete the selected post');
				$ph4->sess->setFlash('form_alert_class', 'danger');					
			}
		
            redirect('/admin/news/index');			
        }
        catch ( Exception $e )
        {
			$ph4->sess->setFlash('form_alert', $e->getMessage());
			$ph4->sess->setFlash('form_alert_class', 'danger');			
						
            redirect('/admin/news/index');
        }
		
		$ph4->tpl->assign( $data );
		$ph4->tpl->draw( 'message' );        
    });
    
});


/**
 * Blog front side
 */
$ph4->router->mount('/news', function() use ($ph4) {

	$ph4->router->get('/', function() {
		redirect('/news/index/1');
    });
    	
	/**
	 * The post list
	 */
    $ph4->router->get('/index(/\d+)?', function( $page=NULL ) use ($ph4) {

		if ( empty($page) ) $page = 1;
			
		// Posts per page
		$limit = 3;
		
		//sleep(2); // DEBUG show loading delay for infite scroll

		$posts = FALSE;
		$next_page = FALSE;
				
		$total_posts = $count = $ph4->db->from('posts')->count();

		$max_page = ceil( $total_posts / $limit );
		
		// Cannot ask page greater than max
		if ( $page > $max_page ) redirect('/404');
		
		if ( $total_posts > 0 )
		{
			$offset = $limit * ($page-1);
						
			$posts =	$ph4->db->from('posts')
								->where('published', 1)
								->sortDesc('updated')
								->limit( $limit )
								->offset( $offset )
								->many();
			
			if ( $posts ) 
			{
				foreach ($posts as $i => $post) 
					$posts[$i]['url'] = BASE_URL.'news/read/'.$post['nicename'];
				
				$next_page = ( ($page+1) <= $max_page ) ? $page+1 : FALSE;
			}

		}
		
		$data = array(
			'msg'     	=> 'Latest posts',
			'posts'    	=> $posts,
			'next'		=> $next_page,
		);
		
		$ph4->tpl->assign( $data );
		$ph4->tpl->draw( 'news.list' );
    });


	/**
	 * The post single page
	 */
    $ph4->router->get('/read/([a-z0-9_-]+)', function( $post_nicename=NULL ) use ($ph4) {
		
		$parsedown = new Parsedown();
		
		$post =	$ph4->db->from('posts')
						->where('nicename', $post_nicename)
						->where('published', 1)
						->one();
		
		if ( $post )
		{
			$post['content'] = $parsedown->parse( $post['content'] );
		}
		else
		{
			redirect('/404');		
		}
		        
		$data = array(
			'post'		=> $post
		);
		
		$ph4->tpl->assign( $data );
		$ph4->tpl->draw( 'news.read' );        
    });


	/**
	 * The RSS feed
	 */
	$ph4->router->get('/feed', function() use ($ph4) {

		$feed = new \Suin\RSSWriter\Feed();

		$channel = new \Suin\RSSWriter\Channel();
		$channel
			->title( $ph4->get('config')['site_title'] )
			->description( $ph4->get('config')['site_description'] )
			->url( BASE_URL )
			->appendTo($feed);
			
		$posts =	$ph4->db->from('posts')
							->where('published', 1)
							->sortDesc('updated')
							->limit( 20 )
							->many();

		if ( $posts )
		{
			foreach( $posts as $post )
			{
				$item = new \Suin\RSSWriter\Item();
				$item
					->title( $post['title'] )
					->description($post['summary'])
					->url( BASE_URL.'news/read/'.$post['nicename'] )
					->appendTo($channel);
			}
		}
		
		echo $feed;		
    });
});


/* EOF */
