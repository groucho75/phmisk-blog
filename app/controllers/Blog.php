<?php

namespace App\Controllers;

/**
 * Blog controller
 *
 * This file contains a controller class to create a simple blog/news
 * section . It includes: public news archive, admin side, rss feed.
 *
 * @package     Phmisk
 * @subpackage  Controllers
 * @author      Alessandro Massasso <alo@eventualo.net>
 * @license     GPL
 * @link        https://github.com/groucho75/phmisk-blog
 */
class Blog extends Base
{

	var $ph4;

	var $validator;
	var $jquery_validator;
	var $jquery;
	
	
   /**
    * _construct
    *
    * @param    obj     $ph4
    */    	
	function __construct($ph4) 
	{
		$this->ph4 = $ph4;
	}


   /**
    * Show the post list in admin side 
    *
    * @param	int		page number
    */    
	function adminIndex($page=NULL) 
	{
		$alert = $this->ph4->sess->getFlash('form_alert', FALSE);
		$alert_class = $this->ph4->sess->getFlash('form_alert_class', 'info');

		$total_posts = $count = $this->ph4->db->from('posts')->count();
		
		if ( $total_posts > 0 )
		{
			// Posts per page
			$limit = 10;

			$max_page = ceil( $total_posts / $limit );

			if ( empty($page) ) $page = 1;
			
			// Cannot ask page greater than max
			$page = min( $max_page, (int)$page);
			
			$offset = $limit * ($page-1);
						
			$posts =	$this->ph4->db->from('posts')
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
			'token' => \NoCSRF::generate( 'csrf_token' ),
			'pagination'	=> $pagination,
		);
		
		$this->ph4->tpl->assign( $data );
		$this->ph4->tpl->draw( 'news.admin.list' );
	}


   /**
    * Init the form validator for add/edit news form
    */    
	function adminInitValidator() 
	{
		$this->validator = new \HybridLogic\Validation\Validator();
		$this->validator
			->set_label('title', 'Title')
			->set_label('nicename', 'SEO nicename')
			->set_label('summary', 'Summary')
			->set_label('content', 'Content')
			->set_label('published', 'Published')
			->set_label('csrf_token', 'Security token')
			
			->add_filter('nicename', 'strtolower')
			
			->add_rule('title', new \HybridLogic\Validation\Rule\NotEmpty())
			->add_rule('title', new \HybridLogic\Validation\Rule\MaxLength(100))
			
			->add_rule('nicename', new \HybridLogic\Validation\Rule\NotEmpty())
			->add_rule('nicename', new \HybridLogic\Validation\Rule\AlphaSlug())
			->add_rule('nicename', new \HybridLogic\Validation\Rule\MaxLength(100))

			->add_rule('summary', new \HybridLogic\Validation\Rule\NotEmpty())
			->add_rule('summary', new \HybridLogic\Validation\Rule\MaxLength(250))

			->add_rule('content', new \HybridLogic\Validation\Rule\NotEmpty())
			
			->add_rule('published', new \HybridLogic\Validation\Rule\NumRange(0, 1))

			->add_rule('csrf_token', new \HybridLogic\Validation\Rule\NotEmpty())
			->add_rule('csrf_token', new \App\Libraries\CsrfRule())
		;

		$this->jquery_validator = new \HybridLogic\Validation\ClientSide\jQueryValidator($this->validator);
		$this->jquery = $this->jquery_validator->generate();
	}


   /**
    * Show the edit/add form
    *
    * @param	int		post ID
    */  	
	function adminEditGet($id=NULL)
	{
		$prev_values = NULL;
		
		if ( $id )
		{
			$post =	$this->ph4->db->from('posts')
						->where('id', $id)
						->one();

			if ( $post )
			{
				$prev_values = $post;
			}
			else
			{
				$this->ph4->sess->setFlash('form_alert', 'Post not found');
				$this->ph4->sess->setFlash('form_alert_class', 'danger');
				
				redirect('/admin/news/index');			
			}
		}
		
        // Looking for previous errors
        $errors = NULL;
        $form_errors = $this->ph4->sess->getFlash('form_errors', FALSE);
        if ( $form_errors )
        {
            foreach ( $form_errors as $field => $msg )
            {
                $errors[$field] = array( 'field' => $field, 'msg' => $msg );
            }
        }

        // Looking for previous submitted values
        $form_prev_values = $this->ph4->sess->getFlash('form_prev_values', FALSE);
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
            'jquery_validator'  => $this->jquery_validator,
            'jquery'    => $this->jquery,
            'errors'    => $errors,
            'prev_values' => $prev_values,
            'token' => \NoCSRF::generate( 'csrf_token' ),
        );

		$this->ph4->tpl->assign( $data );
		$this->ph4->tpl->draw( 'news.admin.edit' );
	}


   /**
    * Check submitted add/edit form
    *
    * @param	int		post ID
    */  	
	function adminEditPost($id=NULL)
	{
		$_POST = array_map_recursive( 'trim', $_POST );
		$_POST = array_map_recursive( 'strip_tags', $_POST );

        if( $this->validator->is_valid($_POST) )
        {
            $posted = $this->validator->get_data();
            
			unset($posted['csrf_token']);
			$posted['updated'] = now();
			
			// To be sure nicename is unique
			$proposed_nicename = $posted['nicename'];
			do {
				$this->ph4->db->from('posts')->where('nicename', $proposed_nicename);
						
				if ( $id ) $this->ph4->db->where('id != ?', $id);
				
				$existing_nicename = $this->ph4->db->one();
				
				if ( ! $existing_nicename )
				{
					$posted['nicename'] = $proposed_nicename;
					break;
				}
				
				$proposed_nicename = $posted['nicename'] . '-'. (string)rand( 1000, 9999 );
				
			} while (true);
			
			if ( $id )
			{
				$post =	$this->ph4->db->from('posts')
						->where("id", $id)
						->update($posted)
						->execute();											
			}
			else
			{
				$post = $this->ph4->db->from('posts')
						->insert($posted)
						->execute();				
			}

			if ( $this->ph4->db->affected_rows == 1 )
			{
				$this->ph4->sess->setFlash('form_alert', 'Post saved');
				$this->ph4->sess->setFlash('form_alert_class', 'success');				
			}
			else
			{
				$this->ph4->sess->setFlash('form_alert', 'Impossible to save the post');
				$this->ph4->sess->setFlash('form_alert_class', 'danger');					
			}


            redirect('/admin/news/index');
        }
        else
        {
            $this->ph4->sess->setFlash('form_errors', $this->validator->get_errors() );
			$this->ph4->sess->setFlash('form_prev_values', $this->validator->get_data() );
			
            redirect('/admin/news/edit/'.(string)$id);
        }		
	}


   /**
    * Delete a post
    *
    * @param	int		post ID
    * @param	str		token
    */  	
	function adminDelete($id, $token)
	{
		// Run CSRF check, on POST data, in bool mode, with a validity of 2 hours, in one-time mode.
		if ( \NoCSRF::check( 'csrf_token', array( 'csrf_token' => $token ), false, 60*60*2, false ) )
		{
			$this->ph4->db->from('posts')->where("id", $id)->delete()->execute();

			if ( $this->ph4->db->affected_rows == 1 )
			{
				$this->ph4->sess->setFlash('form_alert', 'Post '.$id.' deleted');
				$this->ph4->sess->setFlash('form_alert_class', 'success');					
			}
			else
			{
				$this->ph4->sess->setFlash('form_alert', 'Impossible to delete the selected post');
				$ph4->sess->setFlash('form_alert_class', 'danger');					
			}
		
            redirect('/admin/news/index');			
        }
        else
        {
			$this->ph4->sess->setFlash('form_alert', 'The page is expired, please retry');
			$this->ph4->sess->setFlash('form_alert_class', 'danger');			
						
            redirect('/admin/news/index');
        }
		
		$this->ph4->tpl->assign( $data );
		$this->ph4->tpl->draw( 'message' );		
	}


	/**
	 * The post list in front side
	 * 
	 * @param	int		page number
	 */
	function frontIndex($page=NULL)
	{
		if ( empty($page) ) $page = 1;
			
		// Posts per page
		$limit = 3;
		
		//sleep(2); // DEBUG show loading delay for infite scroll

		$posts = FALSE;
		$next_page = FALSE;
				
		$total_posts = $count = $this->ph4->db->from('posts')->count();

		$max_page = ceil( $total_posts / $limit );
		
		// Cannot ask page greater than max
		if ( $page > $max_page ) redirect('/404');
		
		if ( $total_posts > 0 )
		{
			$offset = $limit * ($page-1);
						
			$posts =	$this->ph4->db->from('posts')
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
		
		$this->ph4->tpl->assign( $data );
		$this->ph4->tpl->draw( 'news.list' );		
	}


	/**
	 * The post single page
	 * 
	 * @param	str		the post url-nicename
	 */
	function frontRead($post_nicename=NULL)
	{
		$parsedown = new \Parsedown();
		
		$post =	$this->ph4->db->from('posts')
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
		
		$this->ph4->tpl->assign( $data );
		$this->ph4->tpl->draw( 'news.read' ); 		
	}


	/**
	 * The RSS feed
	 */		
	function feed()
	{
		$feed = new \Suin\RSSWriter\Feed();

		$channel = new \Suin\RSSWriter\Channel();
		$channel
			->title( $this->ph4->get('config')['site_title'] )
			->description( $this->ph4->get('config')['site_description'] )
			->url( BASE_URL )
			->appendTo($feed);
			
		$posts =	$this->ph4->db->from('posts')
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
	}
}

/* EOF */
