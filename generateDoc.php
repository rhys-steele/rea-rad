<?php
	//=========================================================
	// Generates the REA Research Register HTML documents
	//
	// Author: Alex McLean
	// Author: Rhys May
	//=========================================================

	/**
	 * Finds all lists represented in register and generates a document
	 */
	function generate_docs($list_names) {
		// Get the Trello cards JSON array from file
	    $json_string = file_get_contents('../researchRegister.json');
	    $json_array = json_decode($json_string);
	    if ($json_array === NULL) {
	        http_response_code(400);
	        die();
	    }
		// Get a list of lists by iterating through cards
		foreach ($json_array->cards as $card) {
			if (!in_array($card->list, $list_names)) {
				array_push($list_names, $card->list);
			}
		}
		// Generate doc for each list
		foreach ($list_names as $list_name) {
			generate_doc($list_name, $json_array);
		}
	}

	/**
	 * Generates a HTML file for each list
	 */
	function generate_doc($list_name, $json_array) {
		// Generate table head
		$html_string = 	'<head><base target="_parent"><script type="text/javascript">window.onload = function() { if (parent) { var oHead = document.getElementsByTagName("head")[0]; var arrStyleSheets = parent.document.getElementsByTagName("style"); for (var i = 0; i < arrStyleSheets.length; i++) oHead.appendChild(arrStyleSheets[i].cloneNode(true)); }}</script><link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css"></head><body><div class="jive-rendered-content"><p><strong>Notes on effectively using this register</strong></p><ul><li>Use your browser\'s \'current page search\' functionality (<strong>&#8984; + f</strong> Apple/<strong>Ctrl + f</strong> Windows)&#160;in order to search this register by keyword</li><li>Click on the table header&#160;you\'d like to sort by</li></ul><p style="min-height:">&#160;</p><div class="j-rte-table"><table class="j-table table" style="border:" width="100%"><thead><tr style="background-color:" class="active">';
		$html_string .= '<th style="width:">Description</th>' 
	    		. '<th style="width:">Segment(s)</th>'
	    		. '<th style="width:">Owner(s)</th>'
	    		. '<th style="width:">Agency</th>'
	    		. '<th style="width:">Line of Business</th>'
	    		. '<th style="width:">Documentation</th>'
	  			. '</tr></thead><tbody>';

	  	// Iterate through each card and add a row to HTML table
	  	foreach ($json_array->cards as $index => $card) {
	  		if ($card->list != $list_name) {
	  			continue;
	  		}
	  		// New row for new card
	  		$html_string .= '<tr>';

	  		// Card title and tags
	  		$html_string .= '<td style="width:"><p><strong>' . $card->title . '</strong></p>';
	  		$html_string .= '<p>';
	  		$tag_strings = array();
	  		foreach ($card->labels as $label) {
	  			if ($label->color === NULL) {
		  			array_push($tag_strings, '<a class="jive-link-tag-small" href="' . $label->link . '">' . $label->name . '</a>');
	  			}
	  		}
	  		$html_string .= implode(', ', $tag_strings);
	  		$html_string .= '</p></td>';

	  		// Card segments
	  		$html_string .= '<td style="width:">';
	  		foreach ($card->labels as $label) {
	  			if ($label->color !== NULL) {
			  		$html_string .= '<p><a class="jive-link-tag-small" href="' . $label->link . '">' . $label->name . '</a></p>';
	  			}
	  		}
	  		$html_string .= '</td>';

	  		// Card owners
	  		$html_string .= '<td style="width:">';
	  		foreach ($card->owners as $owner) {
	  			if ($owner->active === TRUE) {
		  			$html_string .= '<p><a class="jive-link-profile-small" data-containerid="-1" data-containertype="-1" data-objecttype="3" href="' . $owner->link . '">' . $owner->name . '</a></p>';	
	  			}
	  		}
	  		$html_string .= '</td>';

	  		// Card agency
	  		$html_string .= '<td style="width:">';
	  		foreach ($card->labels as $label) {
	  			if ($label->color == 'agency color') {
			  		$html_string .= '<p><a class="jive-link-external-small" href="' . $label->link . '" rel="nofollow">' . $label->name . '</a><span></span></p>';
	  			}
	  		}
	  		$html_string .= '</td>';

	  		// Card line of business
	  		$html_string .= '<td style="width:">';
	  		foreach ($card->labels as $label) {
	  			if ($label->color == 'line of business color') {
			  		$html_string .= '<p><a class="jivecontainerTT-hover-container" data-containertype="14" data-objecttype="14" href="' . $label->link . '">' . $label->name . '</a><span></span></p>';
	  			}
	  		}
	  		$html_string .= '</td>';

	  		// Card documentation
	  		$html_string .= '<td style="width:">';							
	  		foreach ($card->documents as $document) {
	  			$html_string .= '<p><a class="jive-link-wiki-small" data-containertype="14" data-objecttype="102" href="' . $document->link . '">' . $document->name . '</a></p>';
	  		}
	  		$html_string .= '</td>';

	  		// End row
	  		$html_string .= '</tr>';

	  	}

	  	// End table
	  	$html_string .= '</tbody></table></div></div></body>';
	  	$directory_name = strtolower(str_replace(" ", "-", $list_name));

	  	// Check if directory exists for the list
	  	if (!file_exists('../' . $directory_name)) {
		    mkdir('../' . $directory_name, 0755, true);
		}

	  	// Save HTML string to file
	  	file_put_contents('../' . $directory_name . '/index.html', $html_string);
	}
?>