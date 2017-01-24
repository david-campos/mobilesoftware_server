<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 17/01/2017
 * Time: 18:42
 */

namespace model;


class SessionsDAO
{
    /**
     * @var ISessionsDAO
     */
    private $realDao;

    /**
     * SessionsDAO constructor.
     * @param $realDao ISessionsDAO
     */
    public function __construct(ISessionsDAO $realDao) {
        $this->realDao = $realDao;
    }


    public function createNewSession(string $phone): array {
        $key = $this->generateSessionKey();
        $mixedKey = $this->mixSessionKey($key, $phone);
        $id = $this->insertSession($phone, $mixedKey);
        return array('key' => $key, 'id' => $id);
    }

    public function checkSessionKey(string $key, int $id, string $phone): bool {
        return ($this->mixSessionKey($key, $phone) === $this->getSessionKey($id, $phone));
    }

    /**
     * Generates a key for a new session
     * @return string
     */
    private function generateSessionKey(): string {
        $token = bin2hex(openssl_random_pseudo_bytes(32));
        return hash('sha512', uniqid(true) . $token);
    }

    private function mixSessionKey(string $key, string $phone): string {
        return hash('sha512', $key . $phone);
    }

    private function insertSession(string $phone, string $key): int {
        return $this->realDao->insertSession($phone, $key);
    }

    private function getSessionKey(int $id, string $phone): string {
        return $this->realDao->getSessionKey($id, $phone);
    }

    public function closeSession(int $id): void {
        $this->realDao->closeSession($id);
    }
}