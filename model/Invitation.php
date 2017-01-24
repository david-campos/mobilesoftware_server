<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 14/01/2017
 * Time: 15:48
 */

namespace model;

require_once dirname(__FILE__) . '/model_exceptions/NotUpdatedDescriptionException.php';

use exceptions\IllegalArgumentException;
use exceptions\NotUpdatedDescriptionException;

class Invitation
{
    const STATE_PENDING = 'pending';
    const STATE_ACCEPTED = 'accepted';
    const STATE_REFUSED = 'refused';

    /**
     * @var int user that created the invitation
     */
    private $user;
    /**
     * @var string the state of the invitation, use de constants STATE_*
     */
    private $state;
    /**
     * @var string|null name of the reason for declining the invitation
     */
    private $reasonName;
    /**
     * @var string|null description of the reason for declining the invitation
     */
    private $reasonDescription;

    /**
     * Invitation constructor.
     * @param int $user
     * @param string $state
     * @param null|string $reasonName
     * @param null|string|bool $reasonDescription
     */
    public function __construct($user, $state, $reasonName = null, $reasonDescription = null) {
        $this->user = $user;
        $this->state = $state;
        if ($reasonDescription !== null && $reasonName !== null) {
            $this->reasonName = $reasonName;
            $this->reasonDescription = $reasonDescription;
        } else {
            $this->reasonName = $this->reasonDescription = null;
        }
    }

    public function toAssociativeArray(): array {
        return array(
            "user" => $this->getUser(),
            "state" => $this->getState(),
            "reasonName" => $this->getReasonName(),
            "reasonDescription" => $this->getReasonDescription()
        );
    }

    /**
     * @param string $state
     * @throws IllegalArgumentException
     */
    public function setState(string $state) {
        if ($state === self::STATE_ACCEPTED || $state === self::STATE_PENDING || $state == self::STATE_REFUSED)
            $this->state = $state;
        else
            throw new IllegalArgumentException('Provided state not valid.');
    }

    /**
     * @param string $reasonName
     */
    public function setReason(string $reasonName) {
        $this->reasonName = $reasonName;
        $this->reasonDescription = false; // Indicates that it's not updated
    }

    /**
     * @return int
     */
    public function getUser(): int {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getState(): string {
        return $this->state;
    }

    /**
     * @return null|string
     */
    public function getReasonName() {
        return $this->reasonName;
    }

    /**
     * @return null|string
     * @throws NotUpdatedDescriptionException
     */
    public function getReasonDescription() {
        if ($this->reasonDescription !== false) {
            return $this->reasonDescription;
        } else {
            throw new NotUpdatedDescriptionException('Trying to get a reason description that is not updated.');
        }
    }
}