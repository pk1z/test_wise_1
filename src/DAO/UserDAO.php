<?php

namespace App\DAO;

use App\Exception\UserEmailAlreadyExistsException;
use App\Exception\UserEmailInvalidException;
use App\Exception\UserException;
use App\Exception\UsernameAlreadyExistsException;
use App\Exception\UsernameBadWordsException;
use App\Exception\UsernameTooShortException;
use App\User;

class UserDAO
{
    private $connection;
    private $forbiddenWords = ['admin', 'root', 'superuser']; // Список запрещенных слов
    private $temporaryEmailDomains = ['10minutesmail', 'examplemail', 'tempmail']; // Список доменов временных электронных почтовых сервисов

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws UserException
     */
    public function createUser(User $user): void
    {
        if (!$this->isNameValid($user->getName())) {
            throw new UserException('Invalid name.');
        }

        if (!$this->isEmailValid($user->getEmail())) {
            throw new UserException('Invalid email.');
        }

        $sql = 'INSERT INTO users (name, email, created, notes) VALUES (?, ?, ?, ?)';
        $statement = $this->connection->prepare($sql);
        $name = $user->getName();
        $email = $user->getEmail();
        $created = $user->getCreated();
        $notes = $user->getNotes();
        $statement->bind_param('ssss',
            $name,
            $email,
            $created,
            $notes,
        );
        $statement->execute();
        $user->setId($statement->insert_id);
        $statement->close();
    }

    /**
     * @throws UserException
     */
    public function updateUser(User $user): void
    {
        if (!$this->isNameValid($user->getName())) {
            throw new UserException('Invalid name.');
        }

        if (!$this->isEmailValid($user->getEmail())) {
            throw new UserException('Invalid email.');
        }

        $sql = 'UPDATE users SET name = ?, email = ?, notes = ? WHERE id = ?';
        $statement = $this->connection->prepare($sql);
        $name = $user->getName();
        $email = $user->getEmail();
        $id = $user->getId();
        $notes = $user->getNotes();
        $statement->bind_param('sssi', $name, $email, $notes, $id);
        $statement->execute();
        $statement->close();
    }

    public function deleteUser(int $userId): void
    {
        $user = $this->getUserById($userId);
        if (null === $user) {
            throw new UserException('User not found.');
        }

        $user->setDeleted(date('Y-m-d H:i:s'));

        $sql = 'UPDATE users SET deleted = ? WHERE id = ?';
        $statement = $this->connection->prepare($sql);
        $deleted = $user->getDeleted();
        $statement->bind_param('si', $deleted, $userId);
        $statement->execute();
        $statement->close();
    }

    public function getAllUsers(): array
    {
        $sql = 'SELECT * FROM users WHERE deleted IS NULL';
        $result = $this->connection->query($sql);
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $user = $this->createUserFromRow($row);
            $users[] = $user;
        }
        $result->close();

        return $users;
    }

    public function isNameValid(string $name): bool
    {
        // Проверка длины имени
        if (strlen($name) < 8) {
            throw new UsernameTooShortException('user name too short. Must be at least 8 chars.');
        }

        // Проверка наличия запрещенных слов в имени
        foreach ($this->forbiddenWords as $forbiddenWord) {
            if (str_contains($name, $forbiddenWord)) {
                throw new UsernameBadWordsException('user name contains bad words');
            }
        }

        // Проверка, что имя уникально
        $sql = 'SELECT COUNT(*) as count FROM users WHERE name = ?';
        $statement = $this->connection->prepare($sql);
        $statement->bind_param('s', $name);
        $statement->execute();
        $count = $statement->get_result()->fetch_assoc()['count'];
        $statement->close();

        if ($count > 0) {
            throw new UsernameAlreadyExistsException('user already exists');
        }

        return true;
    }

    public function isEmailValid(string $email): bool
    {
        // Проверка корректности формата email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new UserEmailInvalidException('email has bad format');
        }

        // Проверка наличия домена в списке ненадежных доменов
        $domain = explode('@', $email)[1];
        foreach ($this->temporaryEmailDomains as $temporaryDomain) {
            if (str_contains($domain, $temporaryDomain)) {
                throw new UserEmailInvalidException('email has bad banned domain');
            }
        }

        // Проверка, что email уникальный
        $sql = 'SELECT COUNT(*) as count FROM users WHERE email = ?';
        $statement = $this->connection->prepare($sql);
        $statement->bind_param('s', $email);
        $statement->execute();
        $count = $statement->get_result()->fetch_assoc()['count'];
        $statement->close();

        if ($count > 0) {
            throw new UserEmailAlreadyExistsException('email already exists');
        }

        return true;
    }

    public function getUserById(int $userId): ?User
    {
        $sql = 'SELECT * FROM users WHERE id = ? AND deleted is null';
        $statement = $this->connection->prepare($sql);
        $statement->bind_param('i', $userId);
        $statement->execute();
        $result = $statement->get_result();
        $user = null;
        if ($row = $result->fetch_assoc()) {
            $user = $this->createUserFromRow($row);
        }
        $result->close();
        $statement->close();

        return $user;
    }

    private function createUserFromRow(array $row): User
    {
        $user = new User();
        $user->setId($row['id']);
        $user->setName($row['name']);
        $user->setEmail($row['email']);
        $user->setCreated($row['created']);
        $user->setDeleted($row['deleted']);
        $user->setNotes($row['notes']);

        return $user;
    }
}
