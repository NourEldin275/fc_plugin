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
 * @param $form_type represents the string value of the form type
 * form type should be passed in all lower case and separated with underscores between each word
 */

function fcp_display_created_forms($form_type){

	Global $wpdb;
	$app_created_forms = $wpdb -> get_results("SELECT `form_id`, `form_settings` FROM `wp_fcp_formbuilder` WHERE `form_type`= '".$form_type."'",ARRAY_A);
    if (!empty($app_created_forms)){
        $form_count = 1;
        foreach ($app_created_forms as $form) {
            $form_name = unserialize($form['form_settings'])['form-name'];
            $form_id = $form['form_id'];
            echo "<tr>
			    <td><input class='form-select-checkbox' type='checkbox' id='checkbox_form_id_".$form_id."' style='margin-right:5px;'>".$form_count."</td><td>".$form_name."</td>"
                ."<td>[form-builder form=\"".$form_name." fcp_".$form_id."\"]</td>
				<td><a href='' class='fcp-edit-selected-form' id='fcp_form_".$form_id."' >Edit</a></td>
				<td><a href='javascript:void(0);' class='fcp-delete-selected-form' id='fcp_form_id_".$form_id."'>Delete</a></td>
			</tr>" ;
            $form_count++;
        }
    }
    else {
        echo "<tr><td id='no_forms_to_display'>No forms to display. Start creating now</td></tr>";
    }

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
//        echo "<br>current array of ids<br>";
//        print_r($array_of_ids);
    }
    //echo "<br>Ides to be deleted: <br>";

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

    if ( isset( $_POST['fcp_submission']) ){
        //var_dump($_POST['fcp_submission']);
        $form_fields = json_decode(stripslashes($_POST['fcp_submission']));
        //var_dump($sub);

        $password_flag = FALSE; // to indicate that the password has already been added. In order not to add the confirmation

        $submission_array = array(); // associative array

        foreach($form_fields as $field_name => $field_value){


            $fields_val_array = array(); // holds the many values of the a field
            $field_val_counter = 0; // used to index the field_val_array


            $field_label_reg = "/_fcp_[0-9]/"; // used to replace the postfix which was used to make JSON keys unique

            //Check for special fields and deal with them separetly

            // (1) password ops variables
            $password = strstr($field_name, "(_fcp_pass)");
            $pass_postfix_reg_exp = "/(\(_fcp_pass)\)/";

            // (2) checkbox ops variables
            $checkbox_special_reg_exp = "/_fcp_box_field_/";
            $checkbox = strstr($field_name,"_fcp_box_field_"); // used to check if the string exists or not



            // ****************IMPORTANT*********** removing postifix from the field name will be done when displaying only
            //$field_name = preg_replace($field_label_reg,"",$field_name,1);
            $field_name = strip_tags($field_name);

            // password ops
            if ( $password != FALSE && !$password_flag){
                // $value contains a password and should be hashed before storing it
                $password = $field_value;

                if ($field_value != ""){
                    $field_value = "Password entered";
                }
                else {
                    $field_value = "No password entered";
                }

                $password_flag = TRUE;

                $fields_val_array[$field_val_counter] = $password;

                // saving the password field with the password postfix to recognize it when polling it from the DB
                $submission_array[$field_name] = $fields_val_array; // should be hashed

                $field_name = preg_replace($pass_postfix_reg_exp,"",$field_name,1);

            }

            // checkbox ops
            else if ($checkbox != FALSE) {
                $field_name = preg_replace($checkbox_special_reg_exp,"",$field_name,1);


                if (!empty($field_value)) { // checkboxes were ticked
                    foreach ($field_value as $val) { // loop on the checkboxes that were ticked
                        $val = strip_tags($val);
                        //(notneeded)echo $val . "<br>";

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


        $wpdb->insert($submission_table, array('submission' => $submission_array, 'sub_date' => $sub_date,'form_id' => $form_id,'form_type'=> $form_type));

    }

}
