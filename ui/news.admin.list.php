{include="header"}

	{* You can insert here page js/css *}

    </head>
    <body>

	   {include="top_navbar"}

		<div class="container">
			<div class="page-header">
				<h1><span class="glyphicon glyphicon-asterisk"></span> {$msg}

				<a role="button" href="{#BASE_URL#}admin/news/edit" class="btn btn-primary pull-right">
					<span class="glyphicon glyphicon-plus"></span>
					Add new</a>
				</h1>
			</div>
			
			{if="$alert"}
			<div class="alert alert-{$alert_class} alert-dismissable">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				{$alert}
			</div>
			{/if}

			<div class="row">   
				
                <div class="col-sm-12">
                    <table class="table table-condensed table-hover table-striped stats-table">
                        <thead>
                            <tr>
                                <th>Updated</th>
                                <th>Title</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            {loop="$posts"}
                                <tr rel="{$counter}">
                                    <td>{$value.updated}</td>
                                    <td {if="$value.published == 0"}class="text-muted"{/if}>{$value.title}</td>
                                    <td>
										<a class="btn btn-default btn-xs" href="{#BASE_URL#}admin/news/edit/{$value.id}" role="button">
											<span class="glyphicon glyphicon-pencil"></span>
											Edit</a>
										<a class="btn btn-default btn-xs" href="{#BASE_URL#}admin/news/delete/{$value.id}/{$token}" role="button" onclick="return confirm('Are you sure to delete it?')">
											<span class="glyphicon glyphicon-trash"></span>
											Delete</a>
										{if="$value.published == 1"}
										<a class="btn btn-default btn-xs" href="{#BASE_URL#}news/read/{$value.nicename}" role="button" target="preview-news-{$value.id}">
											<span class="glyphicon glyphicon-eye-open"></span>
											View</a>
										{else}
										<a class="btn btn-default btn-xs disabled" href="#" role="button">
											<span class="glyphicon glyphicon-eye-close"></span>
											Draft</a>
										{/if}
                                    </td>
                                </tr>
							{else}
								<tr>
									<td colspan=3>No posts yet.</td>
								</tr>
                            {/loop}
                        </tbody>

                    </table>
                </div>

            </div>

			{if="$pagination && $pagination.total > 1"}
			<ul class="pager">
				<li class="previous {if="$pagination.current == 1"}disabled{/if}"><a href="{#BASE_URL#}admin/news/index/{$pagination.newer}">&larr; Newer</a></li>
				<span class="text-center text-muted">Page of {$pagination.current} of {$pagination.total}</span>
				<li class="next {if="$pagination.current >= $pagination.total"}disabled{/if}"><a href="{#BASE_URL#}admin/news/index/{$pagination.older}">Older &rarr;</a></li>
			</ul>
			{/if}

		</div>

{include="footer"}
