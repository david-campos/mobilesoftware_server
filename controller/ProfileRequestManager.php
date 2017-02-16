<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 20/01/2017
 * Time: 15:55
 */

namespace controller;

require_once dirname(__FILE__) . '/ImageManager.php';

use exceptions\IllegalArgumentException;
use exceptions\WrongRequestException;
use model\DAOFactory;
use model\UserTO;

class ProfileRequestManager
{
    /**
     * @var RequestProcessor
     */
    private $requestProcessor;
    /**
     * @var UserTO
     */
    private $userTO;

    function __construct(UserTO $to, RequestProcessor $requestProcessor) {
        $this->requestProcessor = $requestProcessor;
        $this->userTO = $to;
    }

    public function processProfileRequest(array $vars) {
        $req_name = Strings::getGenParamValueIn('request', $vars);
        $synchronize = true;
        switch ($req_name) {
            case Strings::getReqIdentifier('who_am_I'):
                // Just print the userTO
                $synchronize = false;
                break;
            case Strings::getReqIdentifier('block_user'):
                $this->blockUser(Strings::getParamValueIn('block_user', 'param_blocked_phone', $vars));
                break;
            case Strings::getReqIdentifier('change_user_name'):
                $this->changeUsername(Strings::getParamValueIn('change_user_name', 'param_name', $vars));
                break;
            case Strings::getReqIdentifier('change_profile_pic'):
                $this->changeProfilePicture();
                break;
            case Strings::getReqIdentifier('remove_profile_pic'):
                $this->removeProfilePicture();
                break;
            case Strings::getReqIdentifier('filter_user_list'):
                $phones = explode(",", Strings::getParamValueIn("filter_user_list", "param_phones", $vars));
                $users = $this->filterUsers($phones);
                $usersArray = array();
                foreach ($users as $usr) {
                    $blocked = in_array($usr->getId(), $this->userTO->getBlockedIds());
                    $usersArray[] = array("blocked" => $blocked, "user" => $usr);
                }
                $this->requestProcessor->getOutputter()->printUsers($usersArray);
                return; //Not out
            default:
                throw new WrongRequestException("The request '$req_name' is not a profile request.");
        }
        if ($synchronize) {
            // Synchronize to DB
            $this->userTO->synchronize();
        }
        // Print userTO
        $this->requestProcessor->getOutputter()->printUserTO($this->userTO);
    }

    private function blockUser(string $userPhone) {
        $blockedGuy = DAOFactory::getInstance()->obtainUsersDAO()->obtainUserTO($userPhone);
        if ($blockedGuy !== null) {
            $this->userTO->addBlockedId($blockedGuy->getId());
        } else
            throw new IllegalArgumentException('The id of the user to block doesn\'t exist');
    }

    private function changeUsername(string $userName) {
        if ($userName !== '' && $userName !== null) {
            $this->userTO->setName($userName);
        } else
            throw new IllegalArgumentException('The new username is not valid.');
    }

    private function changeProfilePicture() {
        $idx = (new ImageManager())->saveUploadedImage();
        if ($idx !== 0) {
            $this->userTO->setPictureId($idx);
        } else
            throw new IllegalArgumentException('The new image is not a valid file');
    }

    private function removeProfilePicture() {
        $this->userTO->setPictureId(0);
    }

    /**
     * @param array $phones
     * @return UserTO[]
     */
    private function filterUsers(array $phones): array {
        return DAOFactory::getInstance()->obtainUsersDAO()->getExistentUsers($phones);
    }
}