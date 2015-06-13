<?php
// Sets whether or not to generate passwords with special characters (non-alpha-numeric)
// since a bug exists in SolusVM <= v1.14 that may cause the server's IP address and user password to not be updated
Configure::set("Dsm.password.allow_special_characters", false);
?>
