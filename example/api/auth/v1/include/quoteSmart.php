<?php
	// Quote variable to make safe
	function quote_smart($input) {
		$result = $input;
		// Stripslashes
		if (get_magic_quotes_gpc()) {
			$result = stripslashes($input);
		}
		// Quote if not a number or a numeric string
		if (!is_numeric($input)) {
			$result = mysql_real_escape_string($input);
		}
		return $result;
	}

	// Put this in value=
	function quote_dumb($input) {
		$result = str_replace('+', ' ', urlencode($input));
		return $result;
	}
?>