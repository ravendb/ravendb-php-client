<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Type\ValueObjectInterface;

class SecurityClearance implements ValueObjectInterface
{
    public const UNAUTHENTICATED_CLIENTS = 'UnauthenticatedClients';
    public const CLUSTER_ADMIN = 'ClusterAdmin';
    public const CLUSTER_NODE = 'ClusterNode';
    public const OPERATOR = 'Operator';
    public const VALID_USER = 'ValidUser';

    private string $value;

    public function __construct(string $value = '')
    {
        $this->setValue($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function isUnauthenticatedClients(): bool
    {
        return $this->value == self::UNAUTHENTICATED_CLIENTS;
    }

    public function isClusterAdmin(): bool
    {
        return $this->value == self::CLUSTER_ADMIN;
    }

    public function isClusterNode(): bool
    {
        return $this->value == self::CLUSTER_NODE;
    }

    public function isOperator(): bool
    {
        return $this->value == self::OPERATOR;
    }

    public function isValidUser(): bool
    {
        return $this->value == self::VALID_USER;
    }

    public static function unauthenticatedClients(): SecurityClearance
    {
        return new SecurityClearance(self::UNAUTHENTICATED_CLIENTS);
    }

    public static function clusterAdmin(): SecurityClearance
    {
        return new SecurityClearance(self::CLUSTER_ADMIN);
    }

    public static function clusterNode(): SecurityClearance
    {
        return new SecurityClearance(self::CLUSTER_NODE);
    }

    public static function operator(): SecurityClearance
    {
        return new SecurityClearance(self::OPERATOR);
    }

    public static function validUser(): SecurityClearance
    {
        return new SecurityClearance(self::VALID_USER);
    }
}
