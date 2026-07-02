<?php

namespace QUI\Users\Auth\WebAuthn;

use QUI;

use function array_map;
use function base64_decode;
use function base64_encode;
use function bin2hex;
use function hash;
use function hex2bin;
use function is_array;
use function json_decode;
use function json_encode;
use function strlen;
use function time;

class CredentialRepository
{
    public static function table(): string
    {
        return QUI::getDBTableName('users_webauthn_credentials');
    }

    public function create(
        string $userUuid,
        string $userHandle,
        string $credentialId,
        string $publicKey,
        ?int $signCount,
        ?string $aaguid,
        array $transports,
        string $name,
        bool $backupEligible,
        bool $backedUp
    ): void {
        if ($this->findByCredentialId($credentialId)) {
            throw new QUI\Users\Auth\Exception(
                ['quiqqer/core', 'exception.webauthn.credential_already_exists'],
                409
            );
        }

        QUI::getDataBaseConnection()->insert(
            QUI\Utils\Doctrine::quoteIdentifier(self::table()),
            [
                'userUuid' => $userUuid,
                'userHandle' => $userHandle,
                'credentialId' => $this->encodeBinary($credentialId),
                'credentialIdHash' => $this->credentialIdHash($credentialId),
                'publicKey' => $publicKey,
                'signCount' => $signCount,
                'aaguid' => $this->normalizeAaguid($aaguid),
                'name' => $name,
                'transports' => json_encode(array_values($transports)),
                'backupEligible' => $backupEligible ? 1 : 0,
                'backedUp' => $backedUp ? 1 : 0,
                'created' => time(),
                'lastUsed' => null
            ]
        );
    }

    public function findByCredentialId(string $credentialId): ?array
    {
        $QueryBuilder = QUI::getQueryBuilder();

        $data = $QueryBuilder
            ->select('*')
            ->from(QUI\Utils\Doctrine::quoteIdentifier(self::table()))
            ->where($QueryBuilder->expr()->eq('credentialIdHash', ':credentialIdHash'))
            ->setParameter('credentialIdHash', $this->credentialIdHash($credentialId))
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    public function findById(int $id): ?array
    {
        $QueryBuilder = QUI::getQueryBuilder();

        $data = $QueryBuilder
            ->select('*')
            ->from(QUI\Utils\Doctrine::quoteIdentifier(self::table()))
            ->where($QueryBuilder->expr()->eq('id', ':id'))
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    public function findByUserUuid(string $userUuid): array
    {
        $QueryBuilder = QUI::getQueryBuilder();

        $rows = $QueryBuilder
            ->select('*')
            ->from(QUI\Utils\Doctrine::quoteIdentifier(self::table()))
            ->where($QueryBuilder->expr()->eq('userUuid', ':userUuid'))
            ->setParameter('userUuid', $userUuid)
            ->orderBy('created', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map($this->hydrate(...), $rows);
    }

    public function deleteForUser(int $id, string $userUuid): void
    {
        QUI::getDataBaseConnection()->delete(
            QUI\Utils\Doctrine::quoteIdentifier(self::table()),
            [
                'id' => $id,
                'userUuid' => $userUuid
            ]
        );
    }

    public function updateUsage(int $id, ?int $signCount): void
    {
        QUI::getDataBaseConnection()->update(
            QUI\Utils\Doctrine::quoteIdentifier(self::table()),
            [
                'signCount' => $signCount,
                'lastUsed' => time()
            ],
            [
                'id' => $id
            ]
        );
    }

    public function updateName(int $id, string $userUuid, string $name): void
    {
        QUI::getDataBaseConnection()->update(
            QUI\Utils\Doctrine::quoteIdentifier(self::table()),
            [
                'name' => $name
            ],
            [
                'id' => $id,
                'userUuid' => $userUuid
            ]
        );
    }

    public function encodeBinary(string $value): string
    {
        return base64_encode($value);
    }

    public function decodeBinary(string $value): string
    {
        return base64_decode($value, true) ?: '';
    }

    public function credentialIdHash(string $credentialId): string
    {
        return hash('sha256', $credentialId);
    }

    public function normalizeAaguid(?string $aaguid): ?string
    {
        if ($aaguid === null) {
            return null;
        }

        if (strlen($aaguid) === 16) {
            return bin2hex($aaguid);
        }

        return $aaguid;
    }

    public function createUserHandle(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function userHandleToBinary(string $userHandle): string
    {
        return hex2bin($userHandle) ?: '';
    }

    private function hydrate(array $data): array
    {
        $transports = $data['transports'] ?? [];

        if (!is_array($transports)) {
            $transports = json_decode((string)$transports, true);
        }

        if (!is_array($transports)) {
            $transports = [];
        }

        $data['id'] = (int)$data['id'];
        $data['credentialId'] = $this->decodeBinary($data['credentialId']);
        $data['signCount'] = $data['signCount'] === null ? null : (int)$data['signCount'];
        $data['transports'] = $transports;
        $data['backupEligible'] = (bool)(int)$data['backupEligible'];
        $data['backedUp'] = (bool)(int)$data['backedUp'];
        $data['created'] = (int)$data['created'];
        $data['lastUsed'] = $data['lastUsed'] === null ? null : (int)$data['lastUsed'];

        return $data;
    }
}
