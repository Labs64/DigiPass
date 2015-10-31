<?php

require_once(DIGIPASS_DIR . 'includes/netlicensing/Libraries/curl/curl.php');
require_once(DIGIPASS_DIR . 'includes/netlicensing/Libraries/curl/curl_response.php');

require_once(DIGIPASS_DIR . 'includes/netlicensing/NetLicensingException.php');
require_once(DIGIPASS_DIR . 'includes/netlicensing/RestController/NetLicensingAPI.php');

//entities
require_once(DIGIPASS_DIR . 'includes/netlicensing/Entities/BaseEntity.php');
require_once(DIGIPASS_DIR . 'includes/netlicensing/Entities/Product.php');
require_once(DIGIPASS_DIR . 'includes/netlicensing/Entities/ProductModule.php');
require_once(DIGIPASS_DIR . 'includes/netlicensing/Entities/Licensee.php');
require_once(DIGIPASS_DIR . 'includes/netlicensing/Entities/Token.php');

//Services
require_once(DIGIPASS_DIR . 'includes/netlicensing/Services/BaseEntityService.php');
require_once(DIGIPASS_DIR . 'includes/netlicensing/Services/ProductService.php');
require_once(DIGIPASS_DIR . 'includes/netlicensing/Services/ProductModuleService.php');
require_once(DIGIPASS_DIR . 'includes/netlicensing/Services/LicenseeService.php');
require_once(DIGIPASS_DIR . 'includes/netlicensing/Services/TokenService.php');