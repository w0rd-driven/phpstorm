<?php
	function isPrimary($level) {
		$result = FALSE;
		if ($level > 89)
			$result = TRUE;
		return $result;
	}

	function maskPassword($password) {
		return preg_replace("/./i", "*", $password); // Replace single occurrence characters with *
	}
?>