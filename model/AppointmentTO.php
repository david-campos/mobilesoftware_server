<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 14/01/2017
 * Time: 15:47
 */

namespace model;


class AppointmentTO extends AbstractTO
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $description;
    /**
     * @var bool
     */
    private $closed;
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $typeName;
    /**
     * @var int
     */
    private $creatorId;
    /**
     * @var array(Invitation)
     */
    private $invitations;
    /**
     * @var IInvitationsUpdater
     */
    private $invitationsUpdater;
    /**
     * @var PropositionTO
     */
    private $currentProposition;

    /**
     * AppointmentTO constructor.
     * @param string $name
     * @param string $description
     * @param bool $closed
     * @param int $id
     * @param string $typeName
     * @param int $creatorId
     * @param PropositionTO $currentProposition
     * @param array $invitations
     * @param IInvitationsUpdater $invitationsUpdater
     * @param ISyncDAO $dao
     */
    public function __construct(string $name, string $description, bool $closed, int $id, string $typeName,
                                int $creatorId, PropositionTO $currentProposition, array $invitations,
                                IInvitationsUpdater $invitationsUpdater, ISyncDAO $dao) {
        parent::__construct($dao);
        $this->invitationsUpdater = $invitationsUpdater;
        $this->name = $name;
        $this->description = $description;
        $this->closed = $closed;
        $this->id = $id;
        $this->typeName = $typeName;
        $this->creatorId = $creatorId;
        $this->currentProposition = $currentProposition;
        $this->invitations = array_filter($invitations, function ($v) {
            return ($v instanceof Invitation);
        });
    }

    public function toAssociativeArray(): array {
        $invitations = array();
        foreach ($this->getInvitations() as $inv) {
            $invitations[] = $inv->toAssociativeArray();
        }
        return array(
            "name" => $this->getName(),
            "description" => $this->getDescription(),
            "closed" => $this->isClosed(),
            "id" => $this->getId(),
            "typeName" => $this->getTypeName(),
            "creator" => $this->getCreatorId(),
            "currentProposition" => $this->getCurrentProposition()->toAssociativeArray(),
            "invitations" => $invitations
        );
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return $this->description;
    }

    /**
     * @return boolean
     */
    public function isClosed(): bool {
        return $this->closed;
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTypeName(): string {
        return $this->typeName;
    }

    /**
     * @return int
     */
    public function getCreatorId(): int {
        return $this->creatorId;
    }

    /**
     * @param string $name
     */
    public function setName(string $name) {
        $this->name = $name;
        $this->synchronized = false;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description) {
        $this->description = $description;
        $this->synchronized = false;
    }

    public function open() {
        if ($this->closed) {
            $this->closed = false;
            $this->synchronized = false;
        }
    }

    public function close() {
        if (!$this->closed) {
            $this->closed = true;
            $this->synchronized = false;
        }
    }

    /**
     * @param string $typeName
     */
    public function setTypeName(string $typeName) {
        $this->typeName = $typeName;
        $this->synchronized = false;
    }

    /**
     * @param int $creatorId
     */
    public function setCreatorId(int $creatorId) {
        $this->creatorId = $creatorId;
        $this->synchronized = false;
    }

    /**
     * @return PropositionTO
     */
    public function getCurrentProposition(): PropositionTO {
        return $this->currentProposition;
    }

    /**
     * @param PropositionTO $proposition
     */
    public function setCurrentProposition(PropositionTO $proposition) {
        if ($proposition->getAppointmentId() === $this->getId()) {
            $this->currentProposition = $proposition;
            $this->synchronized = false;
        }
    }

    /**
     * @returns Invitation[]
     */
    public function getInvitations(): array {
        return $this->invitations;
    }

    public function updateInvitationsFromBD() {
        $this->invitationsUpdater->loadInvitationsFromBD($this);
    }

    public function deleteInvitations() {
        $this->invitations = array();
    }

    /**
     * @param Invitation[] $invitations
     */
    public function addInvitations(array $invitations) {
        foreach ($invitations as $inv) {
            if ($inv instanceof Invitation) {
                $this->invitations[] = $inv;
            }
        }
    }
}