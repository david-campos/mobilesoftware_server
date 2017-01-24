<?php
/**
 * Created by PhpStorm.
 * User: ElJüsticieroMisteryo
 * Date: 20/01/2017
 * Time: 15:55
 */

namespace controller;

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

    public function processProfileRequest(array $vars): void {
        $gen = Strings::getConstants()['general_params'];
        $req_name = $vars[$gen['request']];
        switch ($req_name) {
            case Strings::getReqName('block_user'):
                $this->blockUser($vars[Strings::getReqParam('block_user', 'param_blocked_phone')]);
                break;
            case Strings::getReqName('change_user_name'):
                $this->changeUsername($vars[Strings::getReqParam('change_user_name', 'param_name')]);
                break;
            case Strings::getReqName('change_profile_pic'):
                $this->changeProfilePicture();
                break;
            case Strings::getReqName('remove_profile_pic'):
                $this->removeProfilePicture();
                break;
            default:
                throw new WrongRequestException("The request '$req_name' is not a profile request.");
        }
        $this->userTO->synchronize();
        $this->requestProcessor->getOutputter()->printUserTO($this->userTO);
    }

    private function blockUser(string $userPhone): void {
        $blockedGuy = DAOFactory::getInstance()->obtainUsersDAO()->obtainUserTO($userPhone);
        if ($blockedGuy !== null) {
            $this->userTO->addBlockedId($blockedGuy->getId());
        } else
            throw new IllegalArgumentException('The id of the user to block doesn\'t exist');
    }

    private function changeUsername(string $userName): void {
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
}