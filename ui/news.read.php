{include="header"}

	{* You can insert here page js/css *}

    </head>
    <body>

	   {include="top_navbar"}

		<div class="container">
			<div class="page-header">
				<h1><span class="glyphicon glyphicon-asterisk"></span> {$post.title}</h1>
			</div>
			
			<p class="lead">{$post.summary}</p>
			
			<?php echo $post['content'] ?>
			
		</div>

{include="footer"}
