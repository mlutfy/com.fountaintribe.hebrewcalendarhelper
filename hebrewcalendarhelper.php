<?php

require_once 'hebrewcalendarhelper.civix.php';

function hebrewcalendarhelper_civicrm_post( $op, $objectName, $objectId, &$objectRef ){
	// if a deceased individual is being created or edited, rebuild yahrzeit data.


	if(   $objectRef->is_deceased == "1" && $objectName == 'Individual' && ($op == 'create' || $op == 'edit' || $op == 'restore' ) ){
		 
		// Recalculate Hebrew demographic dates, such as next yahrzeit date for this contact.
		 
		//  AllHebrewDates.Calculate
		$params = array(
				'version' => 3,
				'sequential' => 1,
				'contact_ids' => $objectId,
		);
		$result = civicrm_api('AllHebrewDates', 'Calculate', $params);

		 
		 
	}


}



function hebrewcalendarhelper_civicrm_summary( $contactID, &$content, &$contentPlacement ) {
	
	
		// Add Hebrew date of death, Hebrew birthday and add this info 
		// to the back-office summary tab.
		require_once 'utils/HebrewCalendar.php';
		
		$contentPlacement = CRM_Utils_Hook::SUMMARY_BELOW; 
		$tmpHebCal = new HebrewCalendar();
		$hebrew_data = $tmpHebCal->retrieve_hebrew_demographic_dates( $contactID);

		
		$tmp_error_message  = $hebrew_data['error_message'] ;
		if( strlen($tmp_error_message) > 0 ){
				
			$begin_content = contact_summary_determine_beginning_content();
			$middle_content = "<tr><td>Error Occured: ".$hebrew_data['error_message']."</td></tr>";
			$end_content = contact_summary_determine_ending_content();
				
			$content = $begin_content.$middle_content.$end_content;
				
		}else if( isset( $hebrew_data['contact_type']  ) && $hebrew_data['contact_type'] == 'Individual' ){
		
	
			$begin_content = contact_summary_determine_beginning_content();
			$middle_content = contact_summary_determine_middle_content( $hebrew_data  ) ;
			$end_content = contact_summary_determine_ending_content();

			$content = $begin_content.$middle_content.$end_content;
        

		}else{
		
			$content = "";

		 } // end of else
	
	
}


function hebrewcalendarhelper_civicrm_alterContent(  &$content, $context, $tplName, &$object ){

	

	if( $tplName ==  'CRM/Mailing/Form/Upload.tpl' ){
		
			$extra = "<h3>This area <b>CANNOT</b> be used to send personalized <b>yahrzeit</b> reminders</h3>
	 	     If you want to send personalized yahrzeit reminders using email, then use the 'Send Email to Contacts' action
	 	     from the 'Upcoming Yahrzeits' screen instead. ";

			$content = $extra.$content;
			 
		


	}
}


function hebrewcalendarhelper_civicrm_tokens( &$tokens ){


		$tokens['dates']['dates.today___hebrew_trans'] =  'Dates: Today (Hebrew transliterated)';
		$tokens['dates']['dates.today___hebrew'] = 'Dates: Today (Hebrew)' ;
		$tokens['dates']['dates.birth_date_hebrew_trans'] = 'Birth Date (Hebrew - transliterated)' ;
		$tokens['dates']['dates.birth_date_hebrew'] = 'Birth Date (Hebrew)' ;
		 
		 
		$tokens['yahrzeit'] = array(
				'yahrzeit.all' => 'Yahrzeit: All Yahrzeits',
				'yahrzeit.deceased_name' => 'Yahrzeit: Name of Deceased',
				'yahrzeit.english_date' => 'Yahrzeit: English Date of the evening to light the candle (based on mourner preference)',
				'yahrzeit.hebrew_date' => 'Yahrzeit: Hebrew Date (based on mourner preference)',
				'yahrzeit.dec_death_english_date' => 'Yahrzeit: English Date of Death',
				'yahrzeit.dec_death_hebrew_date' => 'Yahrzeit: Hebrew Date of Death',
				'yahrzeit.relationship_name'  => 'Yahrzeit: Relationship to Mourner',
				'yahrzeit.erev_shabbat_before' => 'Yahrzeit: Erev (evening) of the Shabbat Before',
				'yahrzeit.shabbat_morning_before' => 'Yahrzeit: Morning of the Shabbat Before',
				'yahrzeit.erev_shabbat_after' => 'Yahrzeit: Erev (evening) of the Shabbat After',
				'yahrzeit.shabbat_morning_after' => 'Yahrzeit: Morning of the Shabbat After',
				'yahrzeit.morning_format_english' => 'Yahrzeit: English Date of the morning after candle is lit',
				 
		);

	


}
 
function hebrewcalendarhelper_civicrm_tokenValues( &$values, &$contactIDs, $job = null, $tokens = array(), $context = null) {
	if(!empty($tokens['dates'])){
		require_once 'utils/HebrewCalendar.php';
		$hebrew_format = 'dd MM yy';

		$tmpHebCal = new HebrewCalendar();
		$today_hebrew = $tmpHebCal->util_convert_today2hebrew_date($hebrew_format );

		$tmp_hebrew_format = 'hebrew';
		$today_hebrew_hebrew = $tmpHebCal->util_convert_today2hebrew_date($tmp_hebrew_format );

		foreach ( $contactIDs as $cid ) {
			 
			$values[$cid]['dates.today___hebrew_trans'] = $today_hebrew;
			$values[$cid]['dates.today___hebrew'] = $today_hebrew_hebrew;


		}


		// CiviCRM is buggy here, if token is being used in CiviMail, we need to use the key
		// as the token. Otherwise ( PDF Letter, one-off email, etc) we
		// need to use the value.
		while( $cur_token_raw = current( $tokens['dates'] )){
			$tmp_key = key($tokens['dates']);

			$cur_token = '';
			if(  is_numeric( $tmp_key)){
				$cur_token = $cur_token_raw;
			}else{
				// Its being used by CiviMail.
				$cur_token = $tmp_key;
			}

			$token_to_fill = 'dates.'.$cur_token;
			//print "<br><br>Token to fill: ".$token_to_fill."<br>";

			$token_as_array = explode("___",  $cur_token );



			$partial_token =  $token_as_array[0];

			if( $partial_token ==  'birth_date_hebrew_trans' || $partial_token ==  'birth_date_hebrew' ){
				require_once 'utils/HebrewCalendar.php';

				$tmpHebCal = new HebrewCalendar();



				foreach ( $contactIDs as $cid ) {
					$hebrew_data = $tmpHebCal::retrieve_hebrew_demographic_dates( $cid);
					//print_r($hebrew_data );
					$heb_date_of_birth =  $hebrew_data['hebrew_date_of_birth'];
					$heb_date_of_birth_hebrew =  $hebrew_data['hebrew_date_of_birth_hebrew'];
					$bar_bat_mitzvah_label = $hebrew_data['bar_bat_mitzvah_label'] ;
					$earliest_bar_bat_mitzvah_date = $hebrew_data['earliest_bar_bat_mitzvah_date'];
					$full_token = 'dates.'.$partial_token ;
					if(  $partial_token ==  "birth_date_hebrew_trans" ){
						$values[$cid][$full_token] =  $heb_date_of_birth;
					}else if( $partial_token == 'birth_date_hebrew' ){
						$values[$cid][$full_token] = $heb_date_of_birth_hebrew;
					}


				}

			}


			// $tokens['dates']['dates.birth_date___hebrew']
			next($tokens['dates']);
		}

	}

	if(!empty($tokens['yahrzeit']) ){
		 
		$token_yahrzeits_all = 'yahrzeit.all';
		$token_yahrzeits_short = 'yahrzeit.all' ; // Try to eliminate this variable.
		$token_yah_dec_name  = 'yahrzeit.deceased_name' ;
		$token_yah_english_date = 'yahrzeit.english_date';
		$token_yah_hebrew_date = 'yahrzeit.hebrew_date' ;
		$token_yah_dec_death_english_date = 'yahrzeit.dec_death_english_date';
		$token_yah_dec_death_hebrew_date = 'yahrzeit.dec_death_hebrew_date';
		$token_yah_relationship_name = 'yahrzeit.relationship_name';
		$token_yah_erev_shabbat_before = 'yahrzeit.erev_shabbat_before';
		$token_yah_shabbat_morning_before = 'yahrzeit.shabbat_morning_before';
		$token_yah_erev_shabbat_after = 'yahrzeit.erev_shabbat_after' ;
		$token_yah_shabbat_morning_after = 'yahrzeit.shabbat_morning_after' ;
		$token_yah_english_date_morning = 'yahrzeit.morning_format_english';

		require_once('utils/HebrewCalendar.php');
		$tmpHebCal = new HebrewCalendar();
		$tmpHebCal->process_yahrzeit_tokens( $values, $contactIDs ,  $token_yahrzeits_all,  $token_yahrzeits_short, $token_yah_dec_name, $token_yah_english_date, $token_yah_hebrew_date, $token_yah_dec_death_english_date,  $token_yah_dec_death_hebrew_date ,   $token_yah_relationship_name,
				$token_yah_erev_shabbat_before ,
				$token_yah_shabbat_morning_before ,
				$token_yah_erev_shabbat_after ,
				$token_yah_shabbat_morning_after,
				$token_yah_english_date_morning  ) ;

	}
	 
}
 
function contact_summary_determine_middle_content( &$hebrew_data ){

	$heb_date_of_birth =  $hebrew_data['hebrew_date_of_birth'];
	$bar_bat_mitzvah_label = $hebrew_data['bar_bat_mitzvah_label'] ;
	$earliest_bar_bat_mitzvah_date = $hebrew_data['earliest_bar_bat_mitzvah_date'];
	$is_deceased = $hebrew_data['is_deceased'];
	$hebrew_date_of_death = $hebrew_data['hebrew_date_of_death'];
	$yahrzeit_date_observe_hebrew = $hebrew_data['yahrzeit_date_observe_hebrew'];
	$yahrzeit_date_observe_english = $hebrew_data['yahrzeit_date_observe_english'];

	$heb_date_of_birth_html = " <tr> <td class='label'>Hebrew Date of Birth</td> <td class='html-adjust'> $heb_date_of_birth </td>  </tr> \n  ";
	$earliest_bar_bat_date_html  = " <tr> <td class='label'>Earliest Possible $bar_bat_mitzvah_label Date</td><td class='html-adjust'>$earliest_bar_bat_mitzvah_date </td> </tr> \n";
	$hebrew_date_of_death_html   = " <tr> <td class='label'>Hebrew Date of Death</td> <td class='html-adjust'>$hebrew_date_of_death</td> </tr> \n  ";
	$next_yehrzeit_date_html     = " <tr> <td class='label'>Next Hebrew Yahrzeit</td> <td class='html-adjust'>$yahrzeit_date_observe_hebrew</td> </tr> \n
	<tr> <td class='label'>Next English Yahrzeit</td> <td class='html-adjust'>$yahrzeit_date_observe_english</td> </tr> \n ";

	if($is_deceased){
		$middle_html = $heb_date_of_birth_html.$hebrew_date_of_death_html.$next_yehrzeit_date_html ;
	}else{
		$middle_html = $heb_date_of_birth_html.$earliest_bar_bat_date_html;
	}

	return $middle_html;

}


function contact_summary_determine_beginning_content(){

	$html_rtn = "   <div id='customFields'>
		                    <div class='contact_panel'>
		                        <div class='contactCardLeft'>
		                                                        <div class='customFieldGroup ui-corner-all'>
		                <table>

		                  <tr>
		                    <td colspan='2' class='grouplabel'>Hebrew Calendar Demographics</td>
		                  </tr> \n
		            ";

	return $html_rtn;


}




function contact_summary_determine_ending_content(){

	$html_rtn = " </table>
		            </div>
		                        </div><!--contactCardLeft-->

		                        <div class='contactCardRight'>
		                                                                </div>

		                        <div class='clear'></div>
		                    </div>
		                </div>  \n
		   ";

	return $html_rtn;


}


// Everything after this comment was generated by civix.

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function hebrewcalendarhelper_civicrm_config(&$config) {
  _hebrewcalendarhelper_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function hebrewcalendarhelper_civicrm_xmlMenu(&$files) {
  _hebrewcalendarhelper_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function hebrewcalendarhelper_civicrm_install() {
  _hebrewcalendarhelper_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function hebrewcalendarhelper_civicrm_uninstall() {
  _hebrewcalendarhelper_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function hebrewcalendarhelper_civicrm_enable() {
  _hebrewcalendarhelper_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function hebrewcalendarhelper_civicrm_disable() {
  _hebrewcalendarhelper_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function hebrewcalendarhelper_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _hebrewcalendarhelper_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function hebrewcalendarhelper_civicrm_managed(&$entities) {
  _hebrewcalendarhelper_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function hebrewcalendarhelper_civicrm_caseTypes(&$caseTypes) {
  _hebrewcalendarhelper_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function hebrewcalendarhelper_civicrm_angularModules(&$angularModules) {
_hebrewcalendarhelper_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function hebrewcalendarhelper_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _hebrewcalendarhelper_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Functions below this ship commented out. Uncomment as required.
 *

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function hebrewcalendarhelper_civicrm_preProcess($formName, &$form) {

}

*/