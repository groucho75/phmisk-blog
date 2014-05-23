{include="header"}

	{* You can insert here page js/css *}

    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.11.1/jquery.validate.min.js"></script>

    <script type="text/javascript">
        $(function() {

            <?php foreach($jquery['methods'] as $method_name => $method_function): ?>
            jQuery.validator.addMethod("<?php echo $method_name; ?>", <?php echo $method_function; ?>);
            <?php endforeach; ?>
			
			// http://jqueryvalidation.org/validate
            $("form").validate({
				errorElement : 'p',
				errorClass: 'text-danger', 
				validClass: 'text-success',
				errorPlacement: function(error, element) {
					error.appendTo( element.parents("div.form-group").find('div.input-wrapper') );
				},
				highlight: function(element, errorClass, validClass) {
					$(element).parents("div.form-group").addClass('has-error');
				},
				unhighlight: function(element, errorClass, validClass) {
					$(element).parents("div.form-group").removeClass('has-error');
				},
				
                rules: <?php echo json_encode($jquery['rules']); ?>,
                messages: <?php echo json_encode($jquery['messages']); ?>

            });
        });
    </script>
	
	<style text="type/css">
		.form-group p.text-danger { font-weight: bold }
	</style>
	
    </head>
    <body>

	   {include="top_navbar"}

		<div class="container">
			
            <div class="page-header">
                <h1><span class="glyphicon glyphicon-asterisk"></span> {$msg}</h1>
            </div>

            {if="$errors"}
                <div class="alert alert-danger">
					{if="isset($errors.csrf_token)"}
						The page is expired, please retry.
					{else}
						There are errors, please check and retry.
					{/if}
                </div>
            {/if}

            <form class="form-horizontal" role="form" action="" method="post">

            <input type="hidden" name="csrf_token" id="csrf_token" value="{$token}">

			<div class="form-group {if="isset($errors.title)"}has-error{/if}">
				<label for="title" class="col-sm-2 control-label">Title</label>
				<div class="col-sm-10 input-wrapper">
					<input type="text" class="form-control" id="title" name="title" value="{if="isset($prev_values.title)"}{$prev_values.title}{/if}">
					<p class="help-block">The post title. Max 100 chars.</p>
				</div>
			</div>

			<div class="form-group {if="isset($errors.nicename)"}has-error{/if}">
				<label for="nicename" class="col-sm-2 control-label">SEO Nicename</label>
				<div class="col-sm-10 input-wrapper">
					<input type="text" class="form-control" id="nicename" name="nicename" value="{if="isset($prev_values.nicename)"}{$prev_values.nicename}{/if}">
					<p class="help-block">The post nicename for url. Use only: a-z, 0-9, -, _. Max 100 chars.</p>
				</div>
			</div>

			<div class="form-group {if="isset($errors.summary)"}has-error{/if}">
				<label for="summary" class="col-sm-2 control-label">Summary</label>
				<div class="col-sm-10 input-wrapper">
					<input type="text" class="form-control" id="summary" name="summary" value="{if="isset($prev_values.summary)"}{$prev_values.summary}{/if}">
					<p class="help-block">A quick post description, used in post list. Max 250 chars.</p>
				</div>
			</div>

			<div class="form-group {if="isset($errors.content)"}has-error{/if}">
				<label for="content" class="col-sm-2 control-label">Main content</label>
				<div class="col-sm-10 input-wrapper">
					<textarea class="form-control" rows="5" id="content" name="content">{if="isset($prev_values.content)"}{$prev_values.content}{/if}</textarea>
					<p class="help-block">The main post content. 
					You can use <a href="https://github.com/adam-p/markdown-here/wiki/Markdown-Cheatsheet" target="_blank">Markdown</a> syntax to write formatted text.
					</p>
				</div>
			</div>

			<div class="form-group {if="isset($errors.published)"}has-error{/if}">
				<label class="col-sm-2 control-label">Publish</label>
				<div class="col-sm-10 input-wrapper">
					<label class="radio-inline">
						<input type="radio" id="published-0" name="published" value="0" {if="$prev_values.published == 0"}checked="checked"{/if}> No, keep it draft
					</label>
					<label class="radio-inline">
						<input type="radio" id="published-1" name="published" value="1" {if="$prev_values.published == 1"}checked="checked"{/if}> You, make it public
					</label>
				</div>
			</div>
									
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-10">
					<button type="submit" class="btn btn-primary">Submit</button>
					<a href="{#BASE_URL#}admin/news/index" class="btn btn-link">Cancel</a>
				</div>
			</div>

            </form>

        </div>

{include="footer"}
