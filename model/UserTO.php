<?php

namespace model;


use controller\ImageManager;

class UserTO extends AbstractTO
{
    private $_id;
    private $phone;
    private $name;
    private $picture_id;
    private $blocked_ids;

    function __construct(int $id, string $phone, string $name, int $pictureId, array $blockedIds, ISyncDAO $dao) {
        parent::__construct($dao);
        $this->_id = $id;
        $this->phone = $phone;
        $this->name = $name;
        $this->picture_id = $pictureId;
        $this->blocked_ids = $blockedIds;
    }

    public function toAssociativeArray(bool $withBlockedOnes) {
        return $this->_toAssociativeArray($withBlockedOnes, true);
    }

    public function toAssociativeArrayDetailedBlockedOnes() {
        return $this->_toAssociativeArray(true, false);
    }

    private function _toAssociativeArray(bool $withBlockedOnes, bool $blockedOnesOnlyId): array {
        $array = array(
            "id" => $this->getId(),
            "name" => $this->getName(),
            "phone" => $this->getPhone(),
            "picture_id" => $this->getPictureId());
        if ($withBlockedOnes) {
            if ($blockedOnesOnlyId) {
                $array["blocked_ids"] = $this->getBlockedIds();
            } else {
                $array["blocked_ids"] = array();
                $dao = DAOFactory::getInstance()->obtainUsersDAO();
                foreach ($this->getBlockedIds() as $blockedId) {
                    // We pass false, the blocked ones of our blocked ones should be irrelevant
                    // WARNING: to call toAssociativeArrayDetailedBlockedOnes would be equivalent to call
                    // this same method with the same parameters, this could cause infinite recursion if
                    // two users have blocked each one the other.
                    $array["blocked_ids"][] =
                        $dao->obtainUserTOById($blockedId)->toAssociativeArray(false);
                }
            }
        }
        return $array;
    }

    public function getPhone(): string {
        return $this->phone;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getId(): int {
        return $this->_id;
    }

    public function getPictureId(): int {
        return $this->picture_id;
    }

    public function getPicture(): string {
        // We add /../model to start from where the ImageManager is saved
        $dir = dirname(__FILE__) . '/../model' . ImageManager::IMAGE_DIR;
        foreach (preg_split('/\,/', ImageManager::IMAGE_EXTENSIONS) as $extension) {
            if (file_exists($dir . '.' . $extension))
                return $dir . '.' . $extension;
        }
        return null;
    }

    public function getBlockedIds(): array {
        return $this->blocked_ids;
    }

    public function setName(string $name) {
        $this->name = $name;
        $this->synchronized = false;
    }

    public function setPictureId(int $id) {
        $this->picture_id = $id;
        $this->synchronized = false;
    }

    public function addBlockedId(int $userId) {
        $this->blocked_ids[] = $userId;
        $this->synchronized = false;
    }

    public function remBlockedId(int $userId) {
        $idx = array_search($userId, $this->blocked_ids);
        if ($idx !== false) {
            $this->blocked_ids = array_splice($this->blocked_ids, $idx, 1);
            $this->synchronized = false;
        }
    }
}