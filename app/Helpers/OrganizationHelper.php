<?php

namespace App\Helpers;

class OrganizationHelper
{
    /**
     * Lookup organization ID by name (case-insensitive)
     *
     * @param string $name
     * @param array|null $orgs Optional array of organizations, fallback to cache
     * @return string|null
     */
    public static function getOrganizationIdByName(string $name, ?array $orgs = null): ?string
    {
        $name = strtolower(trim($name));
        if (!$orgs) {
            $orgs = cache('wso2_organizations', []);
        }
        foreach ($orgs as $org) {
            $orgName = isset($org['name']) ? strtolower(trim($org['name'])) : '';
            if ($orgName === $name) {
                return $org['id'];
            }
        }
        return null;
    }
}
