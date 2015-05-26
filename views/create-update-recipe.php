<?php
/**
 * Created by PhpStorm.
 * User: gezimhoxha
 * Date: 15-05-25
 * Time: 6:19 PM
 */
?>

<!DOCTYPE html>
<!--suppress HtmlUnknownTarget -->
<head>
	<link rel="stylesheet" href="$plugindir/styles/zlrecipe-dlog.css" type="text/css" media="all" />
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>
    <script type="text/javascript">//<!CDATA[

        function amdZLRecipeSubmitForm() {
            var title = document.forms['recipe_form']['recipe_title'].value;

            if (title==null || title=='') {
            	var jQrecipeTitle = $('#recipe-title');
                jQrecipeTitle.find('input').addClass('input-error');
                jQrecipeTitle.append('<p class="error-message">You must enter a title for your recipe.</p>');

                return false;
            }

            var jQingredients = $('#z_recipe_ingredients');
            var jQingredientsTextarea = jQingredients.find('textarea');
            var ingredients = jQingredientsTextarea.val();
            if (ingredients==null || ingredients=='' || ingredients==undefined) {
                jQingredientsTextarea.addClass('input-error');
                jQingredients.append('<p class="error-message">You must enter at least one ingredient.</p>');

                return false;
            }
            window.parent.amdZLRecipeInsertIntoPostEditor('$recipe_id');
            top.tinymce.activeEditor.windowManager.close(window);
        }

        $(document).ready(function() {
            $('#more-options').hide();
            $('#more-options-toggle').click(function() {
                $('#more-options').toggle(400);
                return false;
            });
        });
    //]]>
    </script>
	<?php if ($post_info): ?>
		<script>window.onload = amdZLRecipeSubmitForm;</script>
	<?php endif ?>
</head>
<body id="amd-zlrecipe-uploader">
    <form enctype='multipart/form-data' method='post' action='' name='recipe_form'>
		<?php if ($registration_required) :?>
			<h3 class="amd-zlrecipe-title">Register Zip Recipes Free</h3>
			<h4>Thank you for installing Zip Recipes plug.
				<a href="javascript:window.top.location = '$settings_page_url';">
					Click here to go to the Zip Recipes plugin settings
				</a> to register the plugin (it\'s free!).
			</h4>;
	    <?php else: ?>
			<h3 class='amd-zlrecipe-title'>$iframe_title</h3>
			<div id='amd-zlrecipe-form-items'>
				<input type='hidden' name='post_id' value='$id' />
				<input type='hidden' name='recipe_id' value='$recipe_id' />
				<p id='recipe-title'><label>Recipe Title <span class='required'>*</span></label> <input type='text' name='recipe_title' value='$recipe_title' /></p>
				<p id='recipe-image'><label>Recipe Image</label> <input type='text' name='recipe_image' value='$recipe_image' /></p>
				<p id='z_recipe_ingredients' class='cls'><label>Ingredients <span class='required'>*</span> <small>Put each ingredient on a separate line.  There is no need to use bullets for your ingredients.</small><small>You can also create labels, hyperlinks, bold/italic effects and even add images! <a href="http://www.ziprecipes.net/wp-content/uploads/2014/12/plugin-instructions-4.0.0.9.pdf" target="_blank">Learn how here</a></small></label><textarea name='ingredients'>$ingredients</textarea></label></p>
				<p id='amd-zlrecipe-instructions' class='cls'><label>Instructions <small>Press return after each instruction. There is no need to number your instructions.</small><small>You can also create labels, hyperlinks, bold/italic effects and even add images! <a href="http://www.ziprecipes.net/wp-content/uploads/2014/12/plugin-instructions-4.0.0.9.pdf" target="_blank">Learn how here</a></small></label><textarea name='instructions'>$instructions</textarea></label></p>
				<p><a href='#' id='more-options-toggle'>More options</a></p>
				<div id='more-options'>
					<p class='cls'><label>Summary</label> <textarea name='summary'>$summary</textarea></label></p>
					<p class='cls'><label>Rating</label>
                	<span class='rating'>
						<select name="rating">
							<option value="0">None</option>
							<option value="1" $ss[1]>1 Star</option>
							<option value="2" $ss[2]>2 Stars</option>
							<option value="3" $ss[3]>3 Stars</option>
							<option value="4" $ss[4]>4 Stars</option>
							<option value="5" $ss[5]>5 Stars</option>
						</select>
					</span>
					</p>
					<p class="cls"><label>Prep Time</label>
						$prep_time_input
                    <span class="time">
                        <span><input type='number' min="0" max="24" name='prep_time_hours' value='$prep_time_hours' /><label>hours</label></span>
                        <span><input type='number' min="0" max="60" name='prep_time_minutes' value='$prep_time_minutes' /><label>minutes</label></span>
                    </span>
					</p>
					<p class="cls"><label>Cook Time</label>
						$cook_time_input
                    <span class="time">
                    	<span><input type='number' min="0" max="24" name='cook_time_hours' value='$cook_time_hours' /><label>hours</label></span>
                        <span><input type='number' min="0" max="60" name='cook_time_minutes' value='$cook_time_minutes' /><label>minutes</label></span>
                    </span>
					</p>
					<p class="cls"><label>Total Time</label>
						$total_time_input
                    <span class="time">
                        <span><input type='number' min="0" max="24" name='total_time_hours' value='$total_time_hours' /><label>hours</label></span>
                        <span><input type='number' min="0" max="60" name='total_time_minutes' value='$total_time_minutes' /><label>minutes</label></span>
                    </span>
					</p>
					<p><label>Yield</label> <input type='text' name='yield' value='$yield' /></p>
					<p><label>Serving Size</label> <input type='text' name='serving_size' value='$serving_size' /></p>
					<p><label>Calories</label> <input type='text' name='calories' value='$calories' /></p>
					<p><label>Carbs</label> <input type='text' name='carbs' value='$carbs' /></p>
					<p><label>Protein</label> <input type='text' name='protein' value='$protein' /></p>
					<p><label>Fiber</label> <input type='text' name='fiber' value='$fiber' /></p>
					<p><label>Sugar</label> <input type='text' name='sugar' value='$sugar' /></p>
					<p><label>Sodium</label> <input type='text' name='sodium' value='$sodium' /></p>
					<p><label>Fat</label> <input type='text' name='fat' value='$fat' /></p>
					<p><label>Saturated fat</label> <input type='text' name='saturated_fat' value='$saturated_fat' /></p>
					<p class='cls'><label>Notes</label> <textarea name='notes'>$notes</textarea></label></p>
				</div>
				<input type='submit' value='$submit' name='add-recipe-button' />
			</div>
		<?php endif ?>
    </form>
</body>