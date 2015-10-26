<?php

require_once(DIGIPASS_DIR . 'includes/curl/curl.php');
require_once(DIGIPASS_DIR . 'includes/curl/curl_response.php');


require_once(DIGIPASS_DIR . 'includes/netlicensing/RestController/NetLicensingAPI.php');
require_once(DIGIPASS_DIR.'includes/netlicensing/NetLicensingException.php');

//entities
require_once(DIGIPASS_DIR.'includes/netlicensing/Entities/BaseEntity.php');
require_once(DIGIPASS_DIR.'includes/netlicensing/Entities/ProductModule.php');

//Services
require_once(DIGIPASS_DIR.'includes/netlicensing/Services/BaseService.php');
require_once(DIGIPASS_DIR.'includes/netlicensing/Services/ProductModuleService.php');