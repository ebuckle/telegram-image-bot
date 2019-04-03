<?php
    //Telegram bot API Token.
    $apiToken = '';

    //Telegram bot URL.
    $botURL = '';

    //ID of Chat Channel.
    $chatID = '';

    //ID of Gallery Channel.
    $galleryID = '';

    //The text string used to flag unwanted images.
    $imageFlag = '#nobot';

    //Optional caption to be put on all gallery images.
    $galleryCaption = '';

    /*
    * Offset. Used to only generate new images when calling getUpdates.
    * Has no function in this version of the script as it was adapted to run on google
    * app engine. If the main code block below is wrapped in a while(1) loop, then it
    * will function properly to only get new messages.
    */
    $offset = '0';

    //Array of telegram image IDs.
    $imageIDArray = array();

    //Start of bot function.

    //While loop is currently commented out for reasons explained in the offset comments above.
    //while(1) {

    /*
    * Create a json object from the getUpdates API call. Uses offset to
    * ensure only new messages are retrieved.
    */
    $json = file_get_contents("{$botURL}getUpdates?offset={$offset}");
    $jsonArr = json_decode($json);


    
    //Enters if the returned json object is valid and contains messages to be read.
    if($jsonArr -> ok == true && !empty($jsonArr -> result)) {

        //Iterates through each message in the object.
        foreach($jsonArr -> result as $item) {
            //C-style boolean (0/1) to determine if a message has been edited.
            $editedMessage = 0;
            //Updates message offset to be the most recent message ID.
            $offset = $item -> update_id;

            /*
            * Checks the edited_message property of a message to see if it has been
            * edited. If so, properly flags using the editedMessage var and uses
            * the edited text of the message as the one to be read.
            */
	        if(property_exists($item -> edited_message)) {
		        $message = $item -> edited_message;
		        $editedMessage = 1;
	        } else {
		        $message = $item -> message;
            }

            /*
            * Checks that the message being read is from the chat that images are to be
            * taken from and that the message has an image attached to it.
            *
            * Otherwise, checks if the message contains the correct flag to mark an 
            * unwanted image and if it is in response to another message in the chat.
            * If so, it marks the responded image ID in the array as one not to be sent.
            */
            if($message -> chat -> id == $chatID && !empty($message -> photo)) {
                /*
                * Checks whether the image has a caption and could potentially be 
                * flagged to not be put in the gallery. If not flagged, it is
                * added to the array of image IDs to be added once every
                * message has been checked.
                *
                * The elseif checks if the flagged image has been edited. If so,
                * it checks the array and flags the image ID to not be sent.
                */
                if(checkCaption($message)) {                      
                    $imageIDArray[end($message -> photo) -> file_id] = 1;
                } elseif($editedMessage == 1) {
		            $imageIDArray[end($message -> photo) -> file_id] = 0;
		        }
            } elseif(strpos($message -> text, $imageFlag) !== FALSE && !empty($message -> reply_to_message)) {
		        if(!empty($message -> reply_to_message -> photo)) {
		            $imageIDArray[end($message -> reply_to_message -> photo) -> file_id] = 0;
		        }
	        }
        }
        $offset++;
    }
    file_get_contents($botURL . "getUpdates?offset=" . $offset);

    //Iterates through the array of image IDs and sends each one to the gallery channel.
    foreach(array_keys($imageIDArray, 1) as $photo) {
	    file_get_contents($botURL . "sendPhoto?chat_id=" . $galleryID . "&photo=" . $photo . "&caption=" . $galleryCaption);
    }

    ob_flush();
    flush();

    //}
    //End of while loop.

    /*
    * Function for checking if an image caption contains the string to flag it as an unwanted image.
    * Takes a message json object and returns TRUE if the image is wanted and FALSE if it is unwanted.
    */
    function checkCaption(& $message) {
        //Checks if image caption is empty.
        if(!empty($message -> caption)) {
            $caption = $message -> caption;
            if(strpos($caption, $imageFlag) !== FALSE) {
                //Caption contains flag, image is rejected.
                return FALSE;
            } else {
                //Caption does not contain flag, image is accepted.
                return TRUE;
            }
        } else {
            //Caption is empty, therefore image is accepted.
            return TRUE;
        }
    }
?>
