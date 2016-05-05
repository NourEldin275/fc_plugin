<?php
/*
Plugin Name: FCP Form Builder
Plugin URI: http://cape-east.co/fcp-plugin-user-guide/
Description: This plugin builds multi-featured forms that provides lots of backend forms control and managment and absolute easiness in useage.
Author: Cape East Technologies
Version: 1.0
Author URI: http://cape-east.co/
*/
require_once(plugin_dir_path(__FILE__).'fcp_functions.php');

function fcpStylesRegAdmin()
{
	global $pagenow;

	if($pagenow == 'admin.php' && substr($_GET['page'], 0, 3) == "fcp")
	{
		wp_enqueue_style('fcp_bootstrap_styles','https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css');
		wp_enqueue_style('fcp_style.css',plugin_dir_url(__FILE__).'style/fcp_style.css');
	}
}

function fcpStylesRegShortcodes()
{
	wp_enqueue_style('fcp_bootstrap_styles','https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css');
	wp_enqueue_style('fcp_style.css',plugin_dir_url(__FILE__).'style/fcp_style.css');
}



function fcpScriptsRegAdmin()
{
	global $pagenow;

	if($pagenow == 'admin.php' && substr($_GET['page'], 0, 3) == "fcp")
	{
		wp_enqueue_script('jquery');
 		wp_enqueue_script('fcp_bootstrap_scripts','https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js',array('jquery'));
	}
}

function fcpScriptsRegShortcodes()
{
	wp_enqueue_script('jquery');
 	wp_enqueue_script('fcp_bootstrap_scripts','https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js',array('jquery'));
}

add_action('admin_enqueue_scripts','fcpStylesRegAdmin');
add_action('admin_enqueue_scripts','fcpScriptsRegAdmin');

add_action('wp_enqueue_scripts','fcpStylesRegShortcodes');
add_action('wp_enqueue_scripts','fcpScriptsRegShortcodes');


/*
 * Some constant to denote the application type
 */
define("APPLICATION_FORM_FCP","Application Form");
define("CONTACT_FORM_FCP","Contact Form");
define("Survey_FORM_FCP","Survey Form");
define("CONTENT_SUBMISSION_FORM_FCP","Content Submission Form");
define("REGISTRATION_FORM_FCP","Registration Form");
define("BOOKING_FORM_FCP","Booking Form");
define("NEWSLETTER_FORM_FCP","Newsletter Form");
define("EVENT_FORM_FCP","Event Form");
define("CUSTOM_FORM_FCP","Custom Form");
define("EVENT_ALREADY_SUBMITTED_FCP","You can only submit once for this event");
define("EVENT_CAPACITY_REACHED_FCP","Event capacity reached");
define("EVENT_DEADLINE_REACHED_FCP","Event deadline reached");

function fcpPluginActivation()
{
    Global $wpdb;
    /** @var wpdb $wpdb */
	$fcp_form_table = $wpdb->prefix."fcp_formbuilder";
	$fcp_submission_table = $wpdb->prefix."fcp_submissions";
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	$charset_collate = $wpdb->get_charset_collate();

	 if($wpdb->get_var('SHOW TABLES LIKE '.$fcp_form_table) != $fcp_form_table)
	 {
	 	$fcp_sql_form =

	 		'CREATE TABLE '.$fcp_form_table.'(form_id INTEGER(10) UNSIGNED AUTO_INCREMENT,
	 		form_body TEXT NOT NULL,
	 		form_type VARCHAR(30) NOT NULL,
	 		form_settings TEXT,
	 		PRIMARY KEY (form_id)) '.$charset_collate;

	 	dbDelta($fcp_sql_form);
	 }

	if($wpdb->get_var('SHOW TABLES LIKE '.$fcp_submission_table) != $fcp_submission_table)
	{
		$fcp_sql_submission =

			'CREATE TABLE '.$fcp_submission_table.'(submission_id INTEGER(10) UNSIGNED AUTO_INCREMENT,
		 	submission TEXT NOT NULL,
		 	sub_date DATE NOT NULL,
		 	form_id INTEGER(10) UNSIGNED,
		 	form_type VARCHAR(30) NOT NULL,
            attachment_path TEXT,
            password VARCHAR(64),
		 	FOREIGN KEY (form_id) REFERENCES '.$fcp_form_table.'(form_id) ON DELETE CASCADE ON UPDATE NO ACTION,
		 	PRIMARY KEY (submission_id)) '.$charset_collate;

		dbDelta($fcp_sql_submission);

	}
}

register_activation_hook(__FILE__,'fcpPluginActivation');

/**
 * @param $atts
 * @return string represents the form itself when found or NULL if save form was terminated
 */
function formBuilderShortcode($atts){

    /** @var wpdb $wpdb */
    Global $wpdb;
    $attributes = shortcode_atts(array('form' => null), $atts,'form-builder');

    // include the js file
    wp_enqueue_script('fcp_js',plugin_dir_url(__FILE__).'js/fcp_front_end.js',
        array('jquery','jquery-ui-core','jquery-ui-datepicker','jquery-ui-dialog'));
    wp_enqueue_style('jquery-ui-css','http://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css');
    //wp_enqueue_script('fcp_bootstrap_scripts','https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js',array('jquery'));
    //wp_enqueue_style('fcp_bootstrap_styles','https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css');
    $form_id = explode("fcp_",$attributes['form']); // this hold the id of the form to be loaded
	$form_id = $form_id[1];

	/**
	 * @var $passed_form_name : will hold the name of the form that the user passed in the hsortcode
	 * to be checked later on against the stored name
	 */
	$passed_form_name = trim (str_replace("fcp_" . $form_id, "", $attributes['form']));


	$forms_table = $wpdb->prefix."fcp_formbuilder";
    $query =  "SELECT `form_body` FROM `".$forms_table."` WHERE `form_id`=".$form_id;
    $form = $wpdb->get_col($query); // getting the form

	$query = "SELECT `form_settings` FROM `".$forms_table."` WHERE `form_id`=".$form_id;
	$settings = $wpdb->get_col($query); // getting the form settings

	$query = "SELECT `form_type` FROM `".$forms_table."` WHERE `form_id`=".$form_id;
	$form_type = $wpdb->get_col($query);
	
	if(isset($form_type[0]))
	{
		$form_type =  $form_type[0];
	}

	$submissions_table = $wpdb->prefix."fcp_submissions";

	// now check for event form settings and set appropriate flags
	$deadline_flag = false;
	$attendees_max_flag = false;

	if ($form_type == EVENT_FORM_FCP || $form_type == BOOKING_FORM_FCP){

		$query = "SELECT COUNT(*) FROM `".$submissions_table."` WHERE form_id=".$form_id;
		$number_of_submitted_attendees = $wpdb->get_var($query);

		$current_date = date('m/d/Y');
		$event_deadline = unserialize($settings[0])['event_form_deadline'];
		$deadline = date_diff(date_create($current_date),date_create($event_deadline));
		$deadline = $deadline->format("%R%a"); // deadline with -/+ depending on the difference between the two dates

		$event_attendess = unserialize($settings[0])['event_form_max_attendees'];
		if ($number_of_submitted_attendees >= $event_attendess && $event_attendess != "unlimited"){
			$attendees_max_flag = true;
		}

		if ($deadline < 0){
			$deadline_flag = true;
		}
	}

	if(isset($settings[0]))
	{
		$form_name = trim (unserialize($settings[0])['form-name']);
	}

	if ( !empty($form) ){

		if ( !strcasecmp($form_name,$passed_form_name) ){
            $nonce = wp_create_nonce($form_name.$form_id);
            if (wp_verify_nonce($nonce,$form_name.$form_id)){

                if ( isset( $_POST['fcp_submission_state'] ) && $_POST['fcp_submission_state'] == "True" ){
                    $condition = fcpSaveSubmission($form_id);
					if ($condition === true){
						return "Form is currently unavailable";
					}
                    else if ($condition === EVENT_ALREADY_SUBMITTED_FCP){
                        return $condition;
                    }
                }

            }
			if (!$attendees_max_flag && !$deadline_flag) {
				return "<form method='POST' action='' class='form-horizontal fcp_form' id='" . $form_name . $form_id . "' enctype='multipart/form-data'>"
				. html_entity_decode($form[0]) .
				"<div class ='col-sm-12 hidden' id='fcp-form-messages'>
                <div class='col-sm-3'>
                </div>
                <div class='col-sm-6 bg-warning' id='fcp_message' style='border-radius: 10px;font-weight: bold;'>
                </div>
                <div class='col-sm-3'>
                </div>
            </div>" .
				"<input type='hidden' name='fcp_submission_state'><input type='hidden' name='fcp_submission'></form>";
			}
			else {
                $event_message = "";
				if ($attendees_max_flag){
					$capacity_message = "Event capacity reached!<br>Better luck next time.";
					$message = unserialize($settings[0])['capacity_message'];
					$event_message = !empty($message) ? $message : $capacity_message  ;
				}
				if ( $deadline_flag ){
					$deadline_message = "Event deadline reached!<br>Better luck next time";
					$message = unserialize($settings[0])['deadline_message'];
					$event_message = !empty($message) ? $message : $deadline_message;
				}

				echo $event_message;
			}

		}
		else {
			return "The form name you passed is incorrect";
		}

	}
	else {
		return "No Form Found";
	}

}

	add_shortcode('form-builder', 'formBuilderShortcode');


function fcpAdminMenu()
{
	require_once(plugin_dir_path(__FILE__).'fcp_manage_forms.php');
  	require_once(plugin_dir_path(__FILE__).'fcp_application.php');
  	require_once(plugin_dir_path(__FILE__).'fcp_contact.php');
  	require_once(plugin_dir_path(__FILE__).'fcp_registration.php');
  	require_once(plugin_dir_path(__FILE__).'fcp_booking.php');
  	require_once(plugin_dir_path(__FILE__).'fcp_newsletter.php');
  	require_once(plugin_dir_path(__FILE__).'fcp_event.php');
  	require_once(plugin_dir_path(__FILE__).'fcp_survey.php');
  	require_once(plugin_dir_path(__FILE__).'fcp_custom.php');
  	require_once(plugin_dir_path(__FILE__).'fcp_guide.php');

	add_menu_page('Form Builder','Form Builder','manage_options','fcp-general','fcpGeneralPage');
	add_submenu_page('fcp-general','Add New Form','Add New Form','manage_options','fcp-general','fcpGeneralPage');
	add_submenu_page('fcp-general','Manage Forms','Manage Forms','manage_options','fcp-manage-forms','fcpManageForms');
	add_submenu_page('fcp-general','Contact Form','Contact Form','manage_options','fcp-contact-form','fcpContactPage');
	add_submenu_page('fcp-general','Application Form','Application Form','manage_options','fcp-application-form','fcpApplicationPage');
	add_submenu_page('fcp-general','Booking Form','Booking Form','manage_options','fcp-booking-form','fcpBookingPage');
	add_submenu_page('fcp-general','Newsletter Form','Newsletter Form','manage_options','fcp-newsletter-form','fcpNewsletterPage');
	add_submenu_page('fcp-general','Event Form','Event Form','manage_options','fcp-event-form','fcpEventPage');
	add_submenu_page('fcp-general','Custom Form','Custom Form','manage_options','fcp-custom-form','fcpCustomPage');
	add_submenu_page('fcp-general','Quick Guide','Quick Guide','manage_options','fcp-quick-guide','fcpGuidePage');


}

add_action('admin_menu','fcpAdminMenu');

function fcpEditRedirect()
{
	require_once(plugin_dir_path(__FILE__).'forms_UI.php');
	add_submenu_page('Form Builder',"Edit Application Fomr","Edit Application Form","manage_options","fcp-edit","fcpContactPage");
}
//add_action('admin_menu','fcpEditRedirect');

function fcpGeneralPage()
{
	fcpGetBootstrap();

	Global $wpdb;
    $forms_table = $wpdb->prefix."fcp_formbuilder";
    $query = "SELECT `form_type` FROM `".$forms_table."`";
    $form_type = $wpdb->get_col($query);

    if ($form_type[0] == null){
    	$new_user = true;
    }

    else{
    	$new_user = false;
    }
	
	?>

	<h1>Add New Form</h1>

	<!-- Accordion -->
	<div class="panel-group container col-sm-12" id="accordion" role="tablist" aria-multiselectable="true">
  <div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingOne">
      <h4 class="panel-title">
        <a class="collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
          How To Use Form Creator
        </a>
      </h4>
    </div>
    <div id="collapseTwo" class="<?php if($new_user == false){ echo "panel-collapse collapse";} ?>" role="tabpanel" aria-labelledby="headingTwo">
      <div class="panel-body">
        FCP plugin is a form builder plugin that can be used to build multiple form types with unique settings. To learn how to use it, please visit our <a href=<?php echo admin_url('admin.php?page=fcp-quick-guide');?>>User Guide</a> page.
      </div>
    </div>
  </div>

  <div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingTwo">
      <h4 class="panel-title">
        <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
          Add New Form
        </a>
      </h4>
    </div>
    <div id="collapseOne" class="<?php if($new_user == true){ echo "panel-collapse collapse";} ?>" role="tabpanel" aria-labelledby="headingOne">
      <div class="panel-body">
      	
        <div class="text-center">
          <button type="button" class="btn btn-default" style="margin: 20px" onclick="location.href='<?php echo admin_url('admin.php?page=fcp-newsletter-form');?>'">Newsletter Form</button>
          <button type="button" class="btn btn-default" style="margin: 20px" onclick="location.href='<?php echo admin_url('admin.php?page=fcp-event-form');?>'">Event Form</button>
          <button type="button" class="btn btn-default" style="margin: 20px" onclick="location.href='<?php echo admin_url('admin.php?page=fcp-custom-form');?>'">Custom Form</button>
          <button type="button" class="btn btn-default" style="margin: 20px" onclick="location.href='<?php echo admin_url('admin.php?page=fcp-application-form');?>'">Application Form</button>
        </div>

        <div class="text-center">
        	<button type="button" class="btn btn-default" style="margin: 20px" onclick="location.href='<?php echo admin_url('admin.php?page=fcp-contact-form');?>'">Contact Form</button>
        	<button type="button" class="btn btn-default" style="margin: 20px" onclick="location.href='<?php echo admin_url('admin.php?page=fcp-booking-form');?>'">Booking Form</button>
        </div>

      </div>
    </div>
  </div>
  
</div>
	<?php
}
?>
