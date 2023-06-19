<?php

namespace App;

class User
{
    private $id;
    private $name;
    private $email;
    private $created;
    private $deleted;
    private $notes;

    public function __construct(string $name = null, string $email = null)
    {
        if ($name) {
            $this->name = $name;
        }

        if ($email) {
            $this->email = $email;
        }

        $this->created = date('Y-m-d H:i:s');
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @return User
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return User
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * @return User
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @return User
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    // Геттеры и сеттеры для всех полей
}
