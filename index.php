<?php
header('Cache-Control: no-cache');
header('Pragma: no-cache');

define('API_RUNNING', true);
/*
 * NOTES
 *
 * PetFinders has a limit of 10,000 requests per day
 * 
 * @todo Retrieve 10,000 animals each day at midnight and store in cache
 * @todo Turn display into a list
 * @todo When API is called if there are no animals in the cache use a default file
 * @todo On animal click change background color
 * @todo When method = display build a list of animals. Store captcha_id, serialized_array(animal_list), animal_type_to_find
 * @todo Generate a random string for captcha id STORE ID
 * @todo Create cleanup() that removes any captcha entries more than 10 minutes old
 * @todo Implement removeCaptchaId() code
 *
 *
 * API USAGE:
 *
 * All calls must provide the method parameter in the request
 *
 * Fetch Captcha
 * - Parameters
 *		method = display - REQUIRED - Action you wish the API to undertake
 *		count - OPTIONAL - Default: 8 - Number of animals to display
 *		columns - OPTIONAL - Default: 4 - Number of columns to display animals in
 *		css - OPTIONAL - Custom CSS class to attach to captcha div
 * - Output
 *		Form fields:
 *			pt_captcha_id - Generated ID to uniquely identify this captcha test set
 *			pt_captcha_animals[] - Inside of custom form checkboxes. Each is set to an animal ID
 *
 * Verify Captcha
 * - Parameters
 * 		method = verify - REQUIRED - Action you wish the API to undertake
 * 		pt_captcha_id - REQUIRED - Unique captcha test identifier
 *		pt_captcha_animals[] - REQUIRED - List of checked animals. Each is set to an animal ID
 * - Output
 *		JSON: passed = BOOLEAN
 *
 * API Docs
 * - Output
 *		API Docs file
 *
 */


define('LOCAL_FEED', true);
define('PUBLIC_API_KEY', '');
define('ANIMALS_TO_RETRIEVE', 10000); // RUNS AT MIDNIGHT. Only 10,000
define('DEFAULT_ANIMAL_COUNT', 8);
define('DEFAULT_DISPLAY_COLUMNS', 4);

require_once('Timer.class.php');
$t = new Timer();




// **** START API CALL CHECK
if(isset($_REQUEST['method'])) {
	switch ($_REQUEST['method']) {
		case 'display':
			//echo '<form action="" method="get"><input type="hidden" name="method" value="verify" />'; // dev only REMOVE THIS LINE
			echo buildDisplay();
			//echo '<br/><input type="submit"/></form>'; // dev only REMOVE THIS LINE
			break;
		case 'verify':
			if(isset($_REQUEST['pt_captcha_id']) && isset($_REQUEST['pt_captcha_animals'])) {
				echo verifyAttempt();
				break;
			}
			// Otherwise go to default and display API Docs
		default:
			// Show API Docs
			require_once('docs.php');
	}
} else { require_once('docs.php'); }
// **** END API CALL CHECK

// Run some cleanup code
cleanup();
$t->mark('end_API');
//echo $t;


function cleanup() { }
function removeCaptchaId() {}


/**
 * Given a captcha attempt, verify the captcha is valid
 *
 * Verify Captcha
 * - Parameters
 * 		method = verify - REQUIRED - Action you wish the API to undertake
 * 		pt_captcha_id - REQUIRED - Unique captcha test identifier
 *		pt_captcha_animals[] - REQUIRED - List of checked animals. Each is set to an animal ID
 * - Output
 *		JSON: passed = BOOLEAN
 *
 *
 *
 * Failure conditions:
 *	- Captcha in REQUEST does not match a valid captcha
 *	- Differing number of animals in REQUEST and captcha
 *	- Any of the animals ids in REQUEST are not on captcha an vice-versa
 *	- Any of the animals in REQUEST are not the animal type looked for
 **/
function verifyAttempt() {
	$fail = array('attempt' => 'invalid');
	$success = array('attempt' => 'valid', 'message' => 'Valid Captcha attempt');
	require_once('captchas.php');
		
	// Make sure captcha id actually exists
	if(!isset($captcha[$_REQUEST['pt_captcha_id']])) {
		$fail['message'] = 'Invalid Captcha ID';
		return json_encode($fail);
	}
	
	// What animal are we looking for?
	$type = $captcha[$_REQUEST['pt_captcha_id']]['animal'];

	// Is list of passed in animals the same size as the animals in the captcha?
	if(count($captcha[$_REQUEST['pt_captcha_id']]['pt_captcha_animals']) != count($_REQUEST['pt_captcha_animals'])) {
		$fail['message'] = 'Incorrect number of animals';
		return json_encode($fail);
	}
	
	// Check through the list of ids in the captcha and make sure all passed in match
	foreach($captcha[$_REQUEST['pt_captcha_id']]['pt_captcha_animals'] AS $k => $v) {
		if(!in_array($k, $_REQUEST['pt_captcha_animals'])) {
			$fail['message'] = "Input Animal IDs do not match Captcha Animal IDs";
			return json_encode($fail);
		}
		if($captcha[$_REQUEST['pt_captcha_id']]['pt_captcha_animals'][$k] != $type) {
			$fail['message'] = "An animal is not of the required type";
			return json_encode($fail);
		}
	}
	
	// Each captcha test can only be used once. Remove it after it has been called
	removeCaptchaId($_REQUEST['pt_captcha_id']);
		
	// All the tests passed, must be a valid captcha
	return json_encode($success);	
}



/**
 * Builds the display that will be sent back to the caller
 *
 * Fetch Captcha
 * - Parameters
 *		method = display - REQUIRED - Action you wish the API to undertake
 *		count - OPTIONAL - Default: 8 - Number of animals to display
 *		columns - OPTIONAL - Default: 4 - Number of columns to display animals in
 *		css - OPTIONAL - Custom CSS class to attach to captcha div
 * - Output
 *		Form fields:
 *			pt_captcha_id - Generated ID to uniquely identify this captcha test set
 *			pt_captcha_animals[] - Inside of custom form checkboxes. Each is set to an animal ID
 *
 * @return String
 **/
function buildDisplay() {
	// Check parameters
	if(isset($_REQUEST['count'])) $count = $_REQUEST['count']; else $count = DEFAULT_ANIMAL_COUNT;
	if(isset($_REQUEST['columns'])) $columns = $_REQUEST['columns']; else $columns = DEFAULT_DISPLAY_COLUMNS;
	if(isset($_REQUEST['css'])) $css = 'class="'.$_REQUEST['css'].'"'; else $css = '';
	
	// Get animals
	$animals = getAnimals($count);
	
	// Build display
	$s = "<div $css style='width: 100%; border: 1px solid gray;'>";
	$s .= '<input type="hidden" name="pt_captcha_id" value="'.md5(md5(time()) . md5(time())).'" />';
	$i = 1; // Starts at 1 for %
	foreach($animals as $a) {
           // d($a);
		$s .= "\n\t".'<label style="background-color: lightblue; border: 1px solid red; margin-left: 2px">
                    <img src="'  . $a['photos'][1]['$t'] . '" />
                    <input type="checkbox" style="" name="pt_captcha_animals[]" value="'.$a['id'].'">' . $a['name'] . '</label>';
		//if(!($i % $columns)) $s .= "\n<br/>";
		$i++;
	}
	$s .= "</div>";
	
	return $s;
}
/*
	
} /* else if (CHECKING CAPTCHA SOLVING ATTEMPT) {
	// if captcha id is valid
		// check results
	// else return invalid
	
	// erase captcha attempt - only 1 call allowed
} else {
	// show info on using api
}
*/






/**
 * Gives back only the useful values from the PetFinder feed
 *
 * @param Array $feed - Unprocessed JSON from API call
 * @return Array
 **/
function processFeed($feed) {
	$animals = $feed;
	$animals = $animals['petfinder']['pets']['pet'];
	
	$a = array();
	foreach($animals AS $k => $v) {
		//print_r($v);
		$a[] = array('animal' => $v['animal']['$t'], 'name' => $v['name']['$t'], 'id' => $v['id']['$t'], 'photos' => $v['media']['photos']['photo']);
	}

	return $a;
}

/**
 * Fetches a JSON encoded list of animals from the PetFinder API.
 * Formats and returns the JSON as an array
 *
 * @param Integer $num - Default: 4. Number of animals to retrieve
 * @return Array
 **/
function getAnimals($num) {
	/*
	 * If LOCAL_JSON return the default_feed.php
	 * If !json.php return default_feed.php
	 * Else load feed.php file
	 *
	 * Check to see if it is midnight
	 * 	- If yes unlink json.php and gather 10,000 new animals
	 */
	if(LOCAL_FEED) {
		// Use the local json file
		require_once('default_feed.php');
		return buildArray(processFeed($feed), $num);
	} 
	
	// Feed file does not exist. Load the default file
	if(!is_file('feed.php')) {
		require_once('default_feed.php');
		
		return $feed;
	} else {
		// At this point the cached feed file should exist grab some animals from it
		require_once('feed.php');
		return $feed;
	}
	
	
	
	// If it is midnight get new animals and overwrite the cache file
	
		// Construct URL to retrieve list of animals
		//$num
	
		// Retrieve the feed
	
		// Turn the JSON into an Array
		//return json_decode($json, true);
}

/**
 * Builds a random array of the specified number of animals
 *
 * @param Array $animals
 * @param Integer $num
 * @return Array
 **/
function buildArray($animals, $num) { 
	$keys = array_rand($animals, $num); 
	
	$arr = array();
	if(is_array($keys)) {
		// Multiple animals requested
		foreach($keys AS $k) {
			$arr[] = $animals[$k];
		}
	} else {
		// One animal requested
		$arr = $animals[$keys];
	}
	return $arr;
}

function d($d) {
	echo "<pre>";
	print_r($d);
	echo "</pre>";
}