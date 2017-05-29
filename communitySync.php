<?php
	//=========================================================
	// Functions for processing Trello webhooks
	//
	// Author: Alex McLean
	// Author: Rhys May
	//=========================================================

	/**
	 * Adds an attachment to the valid row in community document
	 *
	 * @param $post_array		decoded POST data array
	 * @param $json_array		decoded JSON card data array
	 * @tested
	 */
	function addAttachmentToCard($post_array, $json_array) {
		$data = $post_array->action->data;
		$card = findCard($post_array->action->data->card->id, $json_array);
		$newDocument = array(
			"name" => $data->attachment->name,
			"link" => $data->attachment->url
		);
		array_push($card->documents, $newDocument);
		saveCards($json_array);
	}

	/**
	 * Copies a row in community document
	 *
	 * @param $post_array		decoded POST data array
	 * @param $json_array		decoded JSON card data array
	 * @tested
	 */
	function deleteAttachmentFromCard($post_array, $json_array) {
		$card = findCard($post_array->action->data->card->id, $json_array);
		$documentName = $post_array->action->data->attachment->name;
		foreach ($card->documents as $key => $document) {
			if ($document->name == $documentName) {
				unset($card->documents[$key]);
			}
		}
		saveCards($json_array);
	}

	/**
	 * Adds a label to the valid row in community document
	 * Labels will be added to a specific column based on label colour
	 *
	 * @param $post_array		decoded POST data array
	 * @param $json_array		decoded JSON card data array
	 * @tested
	 */
	function addLabelToCard($post_array, $json_array) {
		$card = findCard($post_array->action->data->card->id, $json_array);
		$labelName = $post_array->action->data->label->name;
		$labelColor = isset($post_array->action->data->label->color) ? $post_array->action->data->label->color : NULL;
		switch ($labelColor) {
			case NULL:
				$labelLink = "https://community.rea-group.com/tags/#/?tags=" . urlencode($labelName);
				break;
			case 'black':
				$labelLink = "https://community.rea-group.com/tags/#/?tags=" . urlencode($labelName);
				break;
			default:
				$labelLink = "https://community.rea-group.com/tags/#/?tags=" . urlencode($labelName);
				break;
		}
		$newLabel = array(
			"name" => $labelName,
			"link" => $labelLink,
			"color" => $labelColor,
			"id" => $post_array->action->data->label->id
		);
		array_push($card->labels, $newLabel);
		saveCards($json_array);
	}

	/**
	 * Removes a label from the valid row in community document
	 *
	 * @param $post_array		decoded POST data array
	 * @param $json_array		decoded JSON card data array
	 * @tested
	 */
	function removeLabelFromCard($post_array, $json_array) {
		$card = findCard($post_array->action->data->card->id, $json_array);
		$labelName = $post_array->action->data->label->name;
		foreach ($card->labels as $key => $label) {
			if ($label->name == $labelName) {
				unset($card->labels[$key]);
			}
		}
		saveCards($json_array);
	}

	/**
	 * Adds a member to the valid row in community document
	 *
	 * @param $post_array		decoded POST data array
	 * @param $json_array		decoded JSON card data array
	 * @tested
	 */
	function addMemberToCard($post_array, $json_array) {
		$card = findCard($post_array->action->data->card->id, $json_array);
		$fullName = $post_array->action->member->fullName;
		$nameLink = strtolower(str_replace(" ", ".", $fullName));
		if ($nameLink == 'mick.chmielewski') {
			$nameLink = 'michael.chmielewski';
		} else if ($nameLink == 'peter.grierson') {
			$nameLink = 'peter_grierson';
		}
		$newMember = array(
			"name" => $fullName,
			"link" => "https://community.rea-group.com/people/" . urlencode($nameLink),
			"creator" => false,
			"active" => true
		);
		foreach ($card->owners as $owner) {
			if ($owner->creator && $owner->active) {
				$owner->active = false;
			}
		}
		array_push($card->owners, $newMember);
		saveCards($json_array);
	}

	/**
	 * Removes a member from the valid row in community document
	 *
	 * @param $post_array		decoded POST data array
	 * @param $json_array		decoded JSON card data array
	 * @tested
	 */
	function removeMemberFromCard($post_array, $json_array) {
		$card = findCard($post_array->action->data->card->id, $json_array);
		$fullName = $post_array->action->member->fullName;
		foreach ($card->owners as $key => $owner) {
			if ($owner->name == $fullName) {
				unset($card->owners[$key]);
			}
		}
		if (sizeof($card->owners) == 1) {
			$card->owners[0]->active = true;
		}
		saveCards($json_array);
	}

	/**
	 * Copies a row in community document
	 *
	 * @param $post_array		decoded POST data array
	 * @param $json_array		decoded JSON card data array
	 * @tested
	 */
	function copyCard($post_array, $json_array) {
		$data = $post_array->action->data;
		$card = findCard($data->cardSource->id, $json_array);
		$newCard = array(
			"id" => $data->card->id,
			"list" => $card->list,
			"title" => $card->title,
			"labels" => $card->labels,
			"segments" => $card->segments,
			"owners" => $card->owners,
			"documents" => $card->documents
		);
		array_push($json_array->cards, $newCard);
		saveCards($json_array);
	}

	/**
	 * Creates a new row in community document
	 *
	 * @param $post_array		decoded POST data array
	 * @param $json_array		decoded JSON card data array
	 * @tested
	 */
	function createCard($post_array, $json_array) {
		$data = $post_array->action->data;
		$fullName = $post_array->action->memberCreator->fullName;
		$newCard = array(
			"id" => $data->card->id,
			"list" => $data->list->name,
			"title" => $data->card->name,
			"labels" => array(),
			"segments" => array(),
			"owners" => array(
				array(
					"name" => $fullName,
					"link" => "https://community.rea-group.com/people/" . strtolower(str_replace(" ", ".", $fullName)),
					"creator" => true,
					"active" => true
				)
			),
			"documents" => array()
		);
		array_push($json_array->cards, $newCard);
		saveCards($json_array);
	}

	/**
	 * Removes a row from community document
	 *
	 * @param $post_array		decoded POST data array
	 * @param $json_array		decoded JSON card data array
	 * @tested
	 */
	function deleteCard($post_array, $json_array) {
		$id = $post_array->action->data->card->id;
		foreach ($json_array->cards as $key => $card) {
			if ($id == $card->id) {
				unset($json_array->cards[$key]);
				$json_array->cards = array_values($json_array->cards);
				saveCards($json_array);
				return;
			}
		}
		http_response_code(500);
		die();
	}

	/**
	 * Updates the valid row in community document
	 *
	 * @param $post_array		decoded POST data array
	 * @param $json_array		decoded JSON card data array
	 * @tested
	 */
	function updateCard($post_array, $json_array) {
		$card = findCard($post_array->action->data->card->id, $json_array);
		if (isset($post_array->action->data->old->name)) {
			$card->title = $post_array->action->data->card->name;
		}
		if (isset($post_array->action->data->listAfter)) {
			$card->list = $post_array->action->data->listAfter->name;
		}
		if (isset($post_array->action->data->old->desc)) {
			$card->description = $post_array->action->data->card->desc;
		}
		saveCards($json_array);
	}

	/**
	 * Deletes a label from all valid rows row in community document
	 *
	 * @param $post_array		decoded POST data array
	 * @param $json_array		decoded JSON card data array
	 * @tested
	 */
	function deleteLabel($post_array, $json_array) {
		$deletedLabelId = $post_array->action->data->label->id;
		foreach ($json_array->cards as &$card) {
			foreach ($card->labels as $key => $label) {
				if ($label->id == $deletedLabelId) {
					unset($card->labels[$key]);
				}
			}
	 	}
	 	saveCards($json_array);
	}

	/**
	 * Updates a label in all valid rows row in community document
	 *
	 * @param $post_array		decoded POST data array
	 * @param $json_array		decoded JSON card data array
	 * @tested
	 */
	function updateLabel($post_array, $json_array) {
		$data = $post_array->action->data;
		$oldLabelName = $data->old->name;
		$newLabelName = $data->label->name;
		$newLabelId = $data->label->id;
		$newLabelColor = isset($data->label->color) ? $data->label->color : NULL;
		switch ($newLabelColor) {
			case NULL:
				$newLabelLink = "https://community.rea-group.com/tags/#/?tags=" . urlencode($newLabelName);
				break;
			case 'black':
				$newLabelLink = "https://community.rea-group.com/tags/#/?tags=" . urlencode($newLabelName);
				break;
			default:
				$newLabelLink = "https://community.rea-group.com/tags/#/?tags=" . urlencode($newLabelName);
				break;
		}
		foreach ($json_array->cards as &$card) {
			foreach ($card->labels as &$label) {
				if ($label->name == $oldLabelName) {
					$label->name = $newLabelName;
					$label->link = $newLabelLink;
					$label->color = $newLabelColor;
					$label->id = $newLabelId;
				}
			}
		}
		saveCards($json_array);
	}

	/**
	 * Encodes card array and saves to file if valid
	 *
	 * @param $card_array 		array of card data
	 */
	function saveCards($card_array) {
		$json_string = json_encode($card_array);
		if ($json_string !== FALSE) {
			file_put_contents('../researchRegister.json', $json_string);
		} else {
			http_response_code(500);
			die();
		}
	}

	/**
	 * Finds a card from the given ID and returns a pointer to the object
	 *
	 * @param $id 			id of the card to find
	 * @param $card_array 	the card array to search for the id
	 */
	function findCard($id, $card_array) {
		foreach ($card_array->cards as &$card) {
			if ($card->id == $id) {
				return $card;
			}
		}
		http_response_code(500);
		die();
	}

?>