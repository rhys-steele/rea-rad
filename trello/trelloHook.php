<?php
    //=========================================================
    // Handles the Trello webhook payloads
    //
    // Author: Alex McLean
    // Author: Rhys May
    //=========================================================

    // INCLUDE FOR PHP VERSION COMPATIBILITY
    include '../compat/http_response_code.php';

    // Check for X-Trello-Webhook HTTP header
    $http_headers = getallheaders();
    if (isset($http_headers['x-trello-webhook'])) {
        $trello_webhook_header = $http_headers['x-trello-webhook'];
    } else {
        http_response_code(403);
        die();
    }

    // Get POST data
    $post_data = file_get_contents('php://input');
    $post_array = json_decode($post_data);
    if ($post_array === NULL) {
        http_response_code(400);
        die();
    }

    // Check for Trello API action
    if (!isset($post_array->action) || !isset($post_array->action->type)) {
        http_response_code(400);
        die();
    }

    // Get the Trello cards JSON array from file
    $json_string = file_get_contents('../researchRegister.json');
    $json_array = json_decode($json_string);
    if ($json_array === NULL) {
        http_response_code(400);
        die();
    }

    // Check if cards is empty and add if not
    if (!isset($json_array->cards)) {
        $json_array->cards = array();
    }

    // Get a list of lists by iterating through cards
    $list_names = array();
    foreach ($json_array->cards as $card) {
        if (!in_array($card->list, $list_names)) {
            array_push($list_names, $card->list);
        }
    }

    // Switch on API action
    include '../communitySync.php';
    switch ($post_array->action->type) {
        case 'addAttachmentToCard':
            addAttachmentToCard($post_array, $json_array);
            break;
        case 'addLabelToCard':
            addLabelToCard($post_array, $json_array);
            break;
        case 'addMemberToCard':
            addMemberToCard($post_array, $json_array);
            break;
        case 'copyCard':
            copyCard($post_array, $json_array);
            break;
        case 'createCard':
            createCard($post_array, $json_array);
            break;
        case 'deleteAttachmentFromCard':
            deleteAttachmentFromCard($post_array, $json_array);
            break;       
        case 'deleteCard':
        case 'moveCardFromBoard':
            deleteCard($post_array, $json_array);
            break;
        case 'deleteLabel':
            deleteLabel($post_array, $json_array);
            break;
        case 'moveCardToBoard':
            moveCardToBoard($post_array, $json_array);
            break;
        case 'removeLabelFromCard':
            removeLabelFromCard($post_array, $json_array);
            break;
        case 'removeMemberFromCard':
            removeMemberFromCard($post_array, $json_array);
            break;
        case 'updateCard':
            updateCard($post_array, $json_array);
            break;
        case 'updateLabel':
            updateLabel($post_array, $json_array);
            break;
        default:
            // Not actionable
            http_response_code(200);
            die();
    }

    // Generate HTML doc using update JSON object
    include '../generateDoc.php';
    generate_docs($list_names);

?>
