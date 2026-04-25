<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use DateTimeImmutable;
use Cycle\ORM\Entity\Behavior;

#[Entity]
#[Behavior\CreatedAt(
    field: 'createdAt',
    column: 'created_at'
)]
#[Behavior\UpdatedAt(
    field: 'updatedAt',
    column: 'updated_at'
)]
class ApiKey extends ActiveRecord
{
    private const HASH_PREFIX = 'sha256:';

    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $key;

    #[Column(type: "string")]
    public string $name;

    #[Column(type: "datetime")]
    public DateTimeImmutable $createdAt;

    #[Column(type: "datetime", nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;

    #[Column(type: "datetime", nullable: true)]
    public ?DateTimeImmutable $lastUsedAt = null;

    #[ManyToMany(target: Permission::class, through: ApiKeyPermission::class)]
    public array $permissions = [];

    public function getPermissions(): array
    {
        return array_map(function (Permission $permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'description' => $permission->desc
            ];
        }, $this->permissions);
    }

    public function addPermission(Permission $permission): void
    {
        if (!in_array($permission, $this->permissions, true)) {
            $this->permissions[] = $permission;
        }
    }

    public function hasPermissionByName(string $permissionName): bool
    {
        if ($permissionName !== 'admin.boss') {
            $isBoss = collect($this->permissions)->contains(fn(Permission $p) => $p->name === 'admin.boss');
            if ($isBoss) {
                return true;
            }
        }

        return collect($this->permissions)->contains(function (Permission $permission) use ($permissionName) {
            return $permission->name === $permissionName;
        });
    }

    public function hasPermission(Permission $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function removePermission(Permission $permission): void
    {
        $this->permissions = array_filter(
            $this->permissions,
            fn($p) => $p !== $permission
        );
    }

    public function updateLastUsed(): void
    {
        $this->lastUsedAt = new DateTimeImmutable();
        $this->saveOrFail();
    }

    public static function hashPlainKey(string $plainKey): string
    {
        return self::HASH_PREFIX . hash('sha256', $plainKey);
    }

    public static function findByPlainKey(string $plainKey, bool $migrateLegacy = false): ?self
    {
        if ($plainKey === '') {
            return null;
        }

        $apiKey = self::findOne(['key' => self::hashPlainKey($plainKey)]);
        if ($apiKey instanceof self) {
            return $apiKey;
        }

        $legacyHash = hash('sha256', $plainKey);
        $apiKey = self::findOne(['key' => $legacyHash]);
        if ($apiKey instanceof self) {
            if ($migrateLegacy) {
                $apiKey->key = self::hashPlainKey($plainKey);
                $apiKey->saveOrFail();
            }

            return $apiKey;
        }

        if (self::looksLikeStoredHash($plainKey)) {
            return null;
        }

        $apiKey = self::findOne(['key' => $plainKey]);
        if ($apiKey instanceof self && $migrateLegacy) {
            $apiKey->key = self::hashPlainKey($plainKey);
            $apiKey->saveOrFail();
        }

        return $apiKey instanceof self ? $apiKey : null;
    }

    public static function looksLikeStoredHash(string $value): bool
    {
        return str_starts_with($value, self::HASH_PREFIX) || preg_match('/^[a-f0-9]{64}$/i', $value) === 1;
    }
}
