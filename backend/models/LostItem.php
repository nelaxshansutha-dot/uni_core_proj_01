<?php

namespace Models;

use DAO\LostItemDAO;
use Exception;

class LostItem
{
    private $dao;

    private $lostID;
    private $userID;
    private $itemName;
    private $LastSeenDate;
    private $lastSeenTime;
    private $itemImage; 
    private $contactNumber;
    private $description;
    private $lastSeenPlace;
    private $status;

    public function __construct()
    {
        $this->dao = new LostItemDAO();
        $this->status = 'lost';
    }

    public function hydrate(array $data = []): static
    {
        if (array_key_exists('lostID', $data)) {
            $this->setLostID($data['lostID']);
        }
        if (array_key_exists('userID', $data)) {
            $this->setUserID($data['userID']);
        }
        if (array_key_exists('lostItemName', $data)) {
            $this->setItemName($data['lostItemName']);
        } elseif (array_key_exists('itemName', $data)) {
            $this->setItemName($data['itemName']);
        }

        if (array_key_exists('last_seen_datetime', $data) && !empty($data['last_seen_datetime'])) {
            $parts = preg_split('/[T ]/', $data['last_seen_datetime'], 2);
            $this->setLastSeenDate($parts[0] ?? null);
            $this->setLastSeenTime($parts[1] ?? null);
        }

        if (array_key_exists('item_image', $data)) {
            $this->setItemImage($data['item_image']);
        } elseif (array_key_exists('itemImage', $data)) {
            $this->setItemImage($data['itemImage']);
        } elseif (array_key_exists('itemLmage', $data)) {
            $this->setItemImage($data['itemLmage']);
        }

        if (array_key_exists('contact_number', $data)) {
            $this->setContactNumber($data['contact_number']);
        } elseif (array_key_exists('contactNumber', $data)) {
            $this->setContactNumber($data['contactNumber']);
        }

        if (array_key_exists('description', $data)) {
            $this->setDescription($data['description']);
        }
        if (array_key_exists('last_seen_place', $data)) {
            $this->setLastSeenPlace($data['last_seen_place']);
        }
        if (array_key_exists('status', $data)) {
            $this->setStatus($data['status']);
        }
        return $this;
    }

    public function hydrateFromRequest(array $data = []): static
    {
        if (array_key_exists('lostItemName', $data)) {
            $this->setItemName($data['lostItemName']);
        } elseif (array_key_exists('itemName', $data)) {
            $this->setItemName($data['itemName']);
        }

        if (array_key_exists('last_seen_datetime', $data) && !empty($data['last_seen_datetime'])) {
            $parts = preg_split('/[T ]/', $data['last_seen_datetime'], 2);
            $this->setLastSeenDate($parts[0] ?? null);
            $this->setLastSeenTime($parts[1] ?? null);
        }

        if (array_key_exists('item_image', $data)) {
            $this->setItemImage($data['item_image']);
        } elseif (array_key_exists('itemImage', $data)) {
            $this->setItemImage($data['itemImage']);
        } elseif (array_key_exists('itemLmage', $data)) {
            $this->setItemImage($data['itemLmage']);
        }

        if (array_key_exists('contact_number', $data)) {
            $this->setContactNumber($data['contact_number']);
        } elseif (array_key_exists('contactNumber', $data)) {
            $this->setContactNumber($data['contactNumber']);
        }

        if (array_key_exists('description', $data)) {
            $this->setDescription($data['description']);
        }
        if (array_key_exists('last_seen_place', $data)) {
            $this->setLastSeenPlace($data['last_seen_place']);
        }
        if (array_key_exists('status', $data)) {
            $this->setStatus($data['status']);
        }
        return $this;
    }

    public function getLostID() { return $this->lostID; }
    public function setLostID($val) { $this->lostID = $val; return $this; }

    public function getUserID() { return $this->userID; }
    public function setUserID($val) { $this->userID = $val; return $this; }

    public function getItemName() { return $this->itemName; }
    public function setItemName($val) { $this->itemName = $val; return $this; }

    public function getLastSeenDate() { return $this->LastSeenDate; }
    public function setLastSeenDate($val) { $this->LastSeenDate = $val; return $this; }

    public function getLastSeenTime() { return $this->lastSeenTime; }
    public function setLastSeenTime($val) { $this->lastSeenTime = $val; return $this; }

   
    public function getItemImage() { return $this->itemImage; }
    public function setItemImage($val) { $this->itemImage = $val; return $this; }

    public function getContactNumber() { return $this->contactNumber; }
    public function setContactNumber($val) { $this->contactNumber = $val; return $this; }

   
    public function getDescription() { return $this->description; }
    public function setDescription($val) { $this->description = $val; return $this; }

    public function getLastSeenPlace() { return $this->lastSeenPlace; }
    public function setLastSeenPlace($val) { $this->lastSeenPlace = $val; return $this; }

    public function getStatus() { return $this->status; }
    public function setStatus($val) { $this->status = $val; return $this; }

    public function create()
    {
        $lsdt = null;
        if ($this->LastSeenDate && $this->lastSeenTime) {
            $lsdt = $this->LastSeenDate . ' ' . $this->lastSeenTime;
        }

        $this->lostID = $this->dao->create(
            $this->userID,
            $this->itemName,
            $lsdt,
            $this->lastSeenPlace,
            $this->description,
            $this->itemImage,
            $this->contactNumber
        );
        return $this->lostID;
    }

    public function update()
    {
        $lsdt = null;
        if ($this->LastSeenDate && $this->lastSeenTime) {
            $lsdt = $this->LastSeenDate . ' ' . $this->lastSeenTime;
        }

        return $this->dao->update(
            $this->lostID,
            $this->userID,
            $this->itemName,
            $lsdt,
            $this->lastSeenPlace,
            $this->description,
            $this->contactNumber,
            $this->status
        );
    }

    public function delete($lostID, $userID)
    {
        return $this->dao->delete($lostID, $userID);
    }

    public function view($lostID = null)
    {
        return $this->dao->view($lostID);
    }
}
