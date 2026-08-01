<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true);

use mod_lti\local\ltiopenid\registration_exception;
use mod_lti\local\ltiopenid\registration_helper;

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->dirroot . '/mod/lti/locallib.php');
require_once(__DIR__ . '/locallib.php');

$code = 200;
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $doregister = $_SERVER['REQUEST_METHOD'] === 'POST';
    $authheader = moodle\mod\lti\OAuthUtil::get_headers()['Authorization'] ?? '';
    if (!($authheader && substr($authheader, 0, 7) == 'Bearer ')) {
        $message = 'missing_registration_token';
        $code = 401;
    } else {
        try {
            $tokenres = registration_helper::get()->validate_registration_token(trim(substr($authheader, 7)));
            $type = new stdClass();
            $type->state = LTI_TOOL_STATE_CONFIGURED;
            if (array_key_exists('type', $tokenres)) {
                $type = $tokenres['type'];
            }
            if ($doregister) {
                $type->state = LTI_TOOL_STATE_CONFIGURED;
                $registrationpayload = json_decode(file_get_contents('php://input'), true);
                $config = registration_helper::get()->registration_to_config($registrationpayload, $tokenres['clientid']);
                $config = tiny_zoomclassroom_normalize_registered_tool_config($config);
                if ($type->id) {
                    lti_update_type($type, clone $config);
                    $typeid = $type->id;
                } else {
                    $typeid = lti_add_type($type, clone $config);
                }
                set_config('pendingregisteredtoolid', (int)$typeid, 'tiny_zoomclassroom');
                header('Content-Type: application/json; charset=utf-8');
                $message = json_encode(registration_helper::get()->config_to_registration((object)$config, $typeid));
            } else if ($type) {
                $config = lti_get_type_config($type->id);
                $config = tiny_zoomclassroom_normalize_registered_tool_config((object)$config);
                header('Content-Type: application/json; charset=utf-8');
                $message = json_encode(registration_helper::get()->config_to_registration((object)$config, $type->id, $type));
            } else {
                $code = 404;
                $message = 'No registration found.';
            }
        } catch (registration_exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
        }
    }
} else {
    $code = 400;
    $message = 'Unsupported operation';
}

$response = new \mod_lti\local\ltiservice\response();
$response->set_code($code);
$response->set_body($message);
$response->send();
