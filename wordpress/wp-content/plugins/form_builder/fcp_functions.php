<?php

function fcp_stylesheets()
{
	?>
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
	<?php
}

function fcp_scripts()
{
	?>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
	<?php
}

function fcp_fields_panel()
{
	?>
	<div class="fcp_panel col-md-3 text-center col-md-push-4" style="margin-left: 350px; padding: 5px; border-top: 1px solid grey; border-right: 1px solid grey; border-left: 1px solid grey; margin-top: 360px; position: absolute">
		<h4><strong>Available Fields</strong></h4>
	</div>
	<div class="col-md-3 col-md-push-4" id="fields-panel" style="margin-left: 350px; padding: 5px; border: 1px solid grey; background-color: #D2D2D2; margin-top: 410px; position: absolute">
		<button type="button" class="btn btn-primary" style="margin: 3px" onclick="">Text</button>
		<button type="button" class="btn btn-primary" style="margin: 3px" onclick="">Numeric</button>
		<button type="button" class="btn btn-primary" style="margin: 3px" onclick="">Date Picker</button>
		<button type="button" class="btn btn-primary" style="margin: 3px" onclick="">Time Picker</button>
		<button type="button" class="btn btn-primary" style="margin: 3px" onclick="">Select Menu</button>
		<button type="button" class="btn btn-primary" style="margin: 3px" onclick="">Checkbox</button>
		<button type="button" class="btn btn-primary" style="margin: 3px" onclick="">Radio Button</button>
		<button type="button" class="btn btn-primary" style="margin: 3px" onclick="">File</button>
		<button type="button" class="btn btn-primary" style="margin: 3px" onclick="">Email</button>
		<button type="button" class="btn btn-primary" style="margin: 3px" onclick="">Password</button>
		<button type="button" class="btn btn-primary" style="margin: 3px" onclick="">Text Area</button>

	</div>
	<?php
}

function fcp_fields_options()
{
	?>
	<div class="col-md-4 text-center col-md-push-4 hidden" id="edit_field_title">
	</div>
	<div class="col-md-4 col-md-push-4 hidden" id="edit_field_content">
		<div id="fieldOptions" class="form-horizontal"></div><div><button type="button" class="btn btn-primary" id="saveButton" style="margin: 15px" onclick="">Save</button><button type="button" class="btn btn-danger" id="discardButton" style="margin: 15px" onclick="">Discard</button></div>
	</div>
	<?php
}

/**
 * @param $form_type :represents the string value of the form type
 * form type should be passed in all lower case and separated with underscores between each word
 */

function fcp_display_created_forms($form_type){

	Global $wpdb;

  $form_table = $wpdb->prefix."fcp_formbuilder";
	$app_created_forms = $wpdb -> get_results("SELECT `form_id`, `form_settings` FROM `{$form_table}` WHERE `form_type`= '".$form_type."'",ARRAY_A);

    if (!empty($app_created_forms)){
        $form_count = 1;
        foreach ($app_created_forms as $form) {
            $form_name = unserialize($form['form_settings'])['form-name'];
            $form_id = $form['form_id'];
            echo "<tr class='fcp-table-head'>
			    <td class='col-sm-1'><input class='form-select-checkbox' type='checkbox' id='checkbox_form_id_".$form_id."' style='margin-right:5px;'>".$form_count."</td class='col-sm-1'><td>".$form_name."</td>"
                ."<td>[form-builder form=\"".$form_name." fcp_".$form_id."\"]</td>
				<td><a href='".$_SERVER['REQUEST_URI'].'&id='.$form_id."' class='fcp-edit-selected-form' id='fcp_form_".$form_id."' >Edit</a></td>
				<td><a href='javascript:void(0);' class='fcp-delete-selected-form' id='fcp_form_id_".$form_id."'>Delete</a></td>
			</tr>" ;
            $form_count++;
        }
    }
    else {
        echo "<tr><td id='no_forms_to_display'>No forms to display. Start creating now</td></tr>";
    }

}

function fcp_update_form($form_type_update)
{
	//if (wp_verify_nonce($nonce_edit,'form-builder-sub')) {

				if ($_POST['fcp_edit']){

					$form_settings = array('form-name' => $_POST['form-name']);

					if($_POST['send-to-backend']) // if the user enabled backend notification
					{
                        if ($_POST['backend_users_list'] == "Other ...") // to check if the user wanted to email a non WordPress user
                        {
                            $backend_notification_settings = array('To' => $_POST['other_backend_email'], 'From' => $_POST['backend-from'], 'Subject' => $_POST['backend-subject'], 'Body' => $_POST['backend-body']);
                        }
                        else
                        {
                            $backend_notification_settings = array('To' => $_POST['backend_users_list'], 'From' => $_POST['backend-from'], 'Subject' => $_POST['backend-subject'], 'Body' => $_POST['backend-body']);
                        }
						$form_settings["backend-notification"] = $backend_notification_settings;
					}

					else
					{
						$form_settings["backend-notification"] = NULL;
					}

					if($_POST['send-to-user'])
					{
						$user_notification_settings = array('From' => $_POST['user-from'], 'Subject' => $_POST['user-subject'], 'Body' => $_POST['user-body']);
						$form_settings["user-notification"] = $user_notification_settings;
					}

					else
					{
						$form_settings["user-notification"] = NULL;
					}

					$form_settings = serialize($form_settings); // serialize the array to be able to insert it into the database

					Global $wpdb;

					$wpdb->update($wpdb -> prefix."fcp_formbuilder", array('form_body' => $_POST['fcp_edit'], 'form_type'=> $form_type_update ,'form_settings' => $form_settings), array('form_id' => $_GET['id']));


				}
			//}

}

/**
 * @param $form_ids :represents the $_POST['selected_forms_ids']
 * The function takes the super global value and converts it to an array of characters, then removes the
 * array elements which hold the '-' dashes and keeps the form ids themselves
 * After that the function then deletes each form using its id
 */
function fcp_delete_forms($form_ids){

    Global $wpdb;
    $form_ids = str_split($form_ids);
    $array_of_ids = array();
    $index = 0;

    /**
     * The following loop creates array elements after scanning for dashes. It takes the elements between the
     * dashes and adds them to a new array element
     */
    for($i=0;$i<sizeof($form_ids); $i++){
        if ($form_ids[$i] == "-"){
            if ($i > 0){
                $index++;
            }
            $i++;
            $array_of_ids[$index] = $form_ids[$i];
        }
        else {
            $array_of_ids[$index] .= $form_ids[$i];
        }

    }


    foreach($array_of_ids as $id){
        $wpdb->delete($wpdb -> prefix."fcp_formbuilder",array('form_id' => $id));
    }

}

/**
 * fcp_save_form saves the html of the form to the database
 * @param $form_type
 *  $form_type the string of the form type
 */
function fcp_save_form($form_type){

    Global $wpdb;

    $form_settings = array('form-name' => $_POST['form-name']);

    if($_POST['send-to-backend']) // if the user enabled backend notification
    {
        if ($_POST['backend_users_list'] == "Other ...") // to check if the user wanted to email a non WordPress user
        {
            $backend_notification_settings = array('To' => $_POST['other_backend_email'], 'From' => $_POST['backend-from'], 'Subject' => $_POST['backend-subject'], 'Body' => $_POST['backend-body']);
        }
        else
        {
            $backend_notification_settings = array('To' => $_POST['backend_users_list'], 'From' => $_POST['backend-from'], 'Subject' => $_POST['backend-subject'], 'Body' => $_POST['backend-body']);
        }
        $form_settings["backend-notification"] = $backend_notification_settings;
    }

    else
    {
        $form_settings["backend-notification"] = NULL;
    }

    if($_POST['send-to-user'])
    {
        $user_notification_settings = array('From' => $_POST['user-from'], 'Subject' => $_POST['user-subject'], 'Body' => $_POST['user-body']);
        $form_settings["user-notification"] = $user_notification_settings;
    }

    else
    {
        $form_settings["user-notification"] = NULL;
    }

    $form_settings = serialize($form_settings); // serialize the array to be able to insert it into the database

    //var_dump( $_POST['fcp']);
    $wpdb->insert($wpdb -> prefix."fcp_formbuilder", array('form_body' => $_POST['fcp'], 'form_type'=> $form_type ,'form_settings' => $form_settings));
}



/**
 * @param $form_id : will hold the id of the form submitted
 * The function builds an associative array with keys representing the field names, and the values represent an array
 * of the values. This is because there is an odd case when storing checkboxes there might be more than one value.
 *
 */

function fcp_save_submission($form_id){

    Global $wpdb;

    if(file_exists("wp-content/plugins/form_builder/attachments"))
        	{
        		$fcp_att_dir = "wp-content/plugins/form_builder/attachments/";
	        	$fcp_att_file = $fcp_att_dir.basename($_FILES["fcp-att"]["name"]);
	        	$fcp_att_type = pathinfo($fcp_att_file, PATHINFO_EXTENSION);

	        	if (file_exists($fcp_att_file)) 
	        	{
	    			echo "This file already exists";
	    		}

	    		else if($fcp_att_type != "doc" && $fcp_att_type != "docx" && $fcp_att_type != "pdf" && $fcp_att_type != "rtf" && $fcp_att_type != "pages" && $fcp_att_type != "png" && $fcp_att_type != "jpeg" && $fcp_att_type != "gif" && $fcp_att_type != "ppf" && $fcp_att_type != "pptx" && $fcp_att_type != "txt")
	    		{
	    			echo "This file format is not supported";
	    		}

	    		else if($_FILES["fcp-att"]["size"] > 20000000)
	    		{
	    			echo "The File size is too large";
	    		}

	        	else
	        	{
	        		if(move_uploaded_file($_FILES['fcp-att']["tmp_name"],$fcp_att_file))
	        		{
	        			//echo "The file "."<a href='".get_site_url()."/".$fcp_att_file."'>".$_FILES['fcp-att']['name']."</a>"." has been uploaded";
	        			//echo "<a href='".get_site_url()."/".$fcp_att_file."'>".$_FILES['fcp-att']['name']."</a>";
	        		}	

	        		else
	        		{
	        			echo "There was a problem uploading the file ".basename($_FILES['fcp-att']["name"]);
	        		}
	        	}
        	}


    if ( isset( $_POST['fcp_submission']) ){
        $form_fields = json_decode(stripslashes($_POST['fcp_submission']));


        $submission_array = array(); // associative array

        // password ops
        $hashed_password = "";
        if ( !empty($_POST['fcp_password']) ){

            $hashed_password = wp_hash_password($_POST['fcp_password']);
        }

        foreach($form_fields as $field_name => $field_value){


            $fields_val_array = array(); // holds the many values of the a field
            $field_val_counter = 0; // used to index the field_val_array


            $field_label_reg = "/_fcp_[0-9]/"; // used to replace the postfix which was used to make JSON keys unique

            //Check for special fields and deal with them separetly



            // (1) checkbox ops variables
            $checkbox_special_reg_exp = "/_fcp_box_field_/";
            $checkbox = strstr($field_name,"_fcp_box_field_"); // used to check if the string exists or not



            // ****************IMPORTANT*********** removing postifix from the field name will be done when displaying only
            $field_name = strip_tags($field_name);



            // checkbox ops
            if ($checkbox != FALSE) {
                $field_name = preg_replace($checkbox_special_reg_exp,"",$field_name,1);


                if (!empty($field_value)) { // checkboxes were ticked
                    foreach ($field_value as $val) { // loop on the checkboxes that were ticked
                        $val = strip_tags($val);


                        array_push($fields_val_array,$val);

                    }

                    $submission_array[$field_name] = $fields_val_array;
                }
                else { // no checkbox is ticked

                    array_push($fields_val_array,"");
                    $submission_array[$field_name] = $fields_val_array;
                }
            }


            // for any other field that does not require special ops
            else {

                $field_value = strip_tags($field_value);
                array_push($fields_val_array,$field_value);
                $submission_array[$field_name] = $fields_val_array;
            }
        }
        //var_dump($submission_array);

        $submission_array = serialize($submission_array); // serializing the array to be stored

        /*
         * (1) Getting the form type based on the id
         * (2) Getting the current date to represent the date of the submission
         * (3) Saving the submission in its table
         */
        $form_table = $wpdb->prefix."fcp_formbuilder";
        $submission_table = $wpdb->prefix."fcp_submissions";
        $form_type_query = "SELECT `form_type` FROM `".$form_table."` WHERE `form_id`=".$form_id;

        $form_type = $wpdb->get_col($form_type_query);
        $form_type = $form_type[0];
        $sub_date = date('Y-m-d');//, strtotime(date("H:i:s")));


        $wpdb->insert($submission_table, array('submission' => $submission_array, 'sub_date' => $sub_date,'form_id' => $form_id,'form_type'=> $form_type,'attachment_path'=>$fcp_att_file, 'password'=> $hashed_password));

    }

    //Sending file attahments



}

/**
 * This function retrieves submissions and displays them
 *
 * The function queries the database and gets all of the submissions which belong to the passed form type.
 * The function then displays them all sequentially and generates view and delete links to each submission.
 * @param $form_type : represents the form type which the function should only get from the table
 *
 */

function fcp_display_submissions($form_type){
    Global $wpdb;
    $sub_table = $wpdb->prefix."fcp_submissions";
    $form_table = $wpdb->prefix."fcp_formbuilder";
    $submissions = $wpdb -> get_results("SELECT `submission_id`, `submission`, `sub_date`, `form_id`, `attachment_path` FROM `{$sub_table}` WHERE `form_type`= '".$form_type."'",ARRAY_A);

    if (!empty($submissions)){
        $submission_count = 1;
        foreach ($submissions as $key => $submission) {
            $form_id = $submission['form_id'];
            $submission_id = $submission['submission_id'];
            $submission_date = $submission['sub_date'];
            $form = $wpdb -> get_col("SELECT `form_settings` FROM `{$form_table}` WHERE `form_id`= '".$form_id."'");
            $form_name = unserialize($form[0])['form-name'];
            echo "<tr class='fcp-table-head'>
			    <td class='col-sm-1'><input class='submission-select-checkbox' type='checkbox' id='checkbox_submission_id_".$submission_id."' style='margin-right:5px;'>".$submission_count."</td><td>".$form_name."</td>"
                ."<td><b>{$submission_date}</b></td>
                <td>{$submission_id}</td>
				<td><a href='".$_SERVER['REQUEST_URI'].'&submission_content_id='.$submission_id."' class='fcp-view-selected-submission' id='fcp_submission_".$submission_id."' >View</a></td>
				<td><a href='javascript:void(0);' class='fcp-delete-selected-submission' id='fcp_submission_id_".$submission_id."'>Delete</a></td>
			</tr>" ;
            $submission_count++;
        }
    }
    else {
        echo "<tr><td id='no_forms_to_display'>No content has been submitted yet.</td></tr>";
    }

}

/**
 * The function deletes the submissions based on their passed ids.
 *
 * The function take the ids separated by '-', it extracts the ids individually then deletes them
 * @param $submissions_ids
 */
function fcp_delete_submissions($submissions_ids){

    Global $wpdb;
    $submissions_ids = str_split($submissions_ids);
    $array_of_ids = array();
    $index = 0;

    /**
     * The following loop creates array elements after scanning for dashes. It takes the elements between the
     * dashes and adds them to a new array element
     */
    for($i=0;$i<sizeof($submissions_ids); $i++){
        if ($submissions_ids[$i] == "-"){
            if ($i > 0){
                $index++;
            }
            $i++;
            $array_of_ids[$index] = $submissions_ids[$i];
        }
        else {
            $array_of_ids[$index] .= $submissions_ids[$i];
        }

    }


    foreach($array_of_ids as $id){
        $wpdb->delete($wpdb -> prefix."fcp_submissions",array('submission_id' => $id));
    }

}


/**
 * The function display the content of the submission based on the passed id.
 * The function display all of the field values except for the password fields.
 * @param $submission_id
 */
function fcp_display_submission_content($submission_id){
    Global $wpdb;
    $submissions_table = $wpdb->prefix."fcp_submissions";
    $forms_table = $wpdb->prefix."fcp_formbuilder";

    $submission_query = "SELECT * FROM `".$submissions_table."` WHERE `submission_id`=".$submission_id;
    $submission_row = $wpdb->get_row($submission_query,ARRAY_A);
    $form_id = $submission_row['form_id'];
    $form_query = "SELECT `form_settings` FROM `".$forms_table."` WHERE `form_id`=".$form_id;
    $form_settings = $wpdb->get_col($form_query);
    $form_name = unserialize($form_settings[0])['form-name'];


    $field_label_pattern = "/_fcp_[0-9]/"; // regular expression to match the postfix and remove it
    $password_pattern = "/(\(_fcp_pass)\)/"; // used to match if the field is a password field or not


    $field_counter = 1;




    $content_fields = (unserialize($submission_row['submission']));
    ?>
    <div class="col-sm-9 fcp-submission-content">
        <h1>Submission Information</h1>

        <dl class="dl-horizontal">
            <dt>Form Name:</dt>
            <dd><?php echo $form_name; ?></dd>
            <dt>Form Type:</dt>
            <dd><?php echo $submission_row['form_type']; ?></dd>
            <dt>Submission Date:</dt>
            <dd><?php echo $submission_row['sub_date']; ?></dd>
            <dt>Submission ID: </dt>
            <dd><?php echo $submission_row['submission_id'];?></dd>
        </dl>
        <h2 class="col-sm-9">Submission Content :-</h2>
        <table class="table table-hover">
            <thead>
            <tr>
                <th>#</th>
                <th>Field</th>
                <th>Values</th>
            </tr>
            </thead>

            <tbody>
            <?php
            foreach($content_fields as $field_label => $field_values){
                echo "<tr>";
                $field_label = preg_replace($field_label_pattern,"",$field_label);

                $password = strstr($field_label, "(_fcp_pass)");
                if ($password !== FALSE){
                    $field_label = preg_replace($password_pattern,"",$field_label);
                    $password = TRUE;
                }
                echo "<td>{$field_counter}</td> ";
                echo "<td class='fcp-submission-field-label'>{$field_label}</td> ";
                echo "<td><ul>";
                foreach ($field_values as $key=> $value){

                    if ($password) {
                        $value = "Stored Securely";
                    }
                    echo "<li>{$value}</li>";
                }
                echo "</ul></td>";
                $field_counter++;
                echo "</tr>";
            }
            if(!empty($submission_row['attachment_path']))
            {
            	echo "<td>{$field_counter}</td> ";
                echo "<td>Attachment</td> ";
                echo "<td>"."<a href='".get_site_url()."/".$submission_row['attachment_path']."' download>File</a>"."</td> ";
            }
            ?>
            </tbody>
        </table>
    </div>



    <?php



}
