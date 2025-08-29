<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RolePermissionService;

class RefreshRolePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wso2:refresh-permissions {--clear : Clear cache without refreshing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh role-permission mappings from WSO2 Identity Server';

    /**
     * Execute the console command.
     */
    public function handle(RolePermissionService $rolePermissionService)
    {
        if ($this->option('clear')) {
            $this->info('Clearing role-permission cache...');
            $rolePermissionService->clearCache();
            $this->info('✅ Role-permission cache cleared successfully.');
            return 0;
        }

        $this->info('Refreshing role-permission mappings from WSO2...');
        
        try {
            $rolePermissions = $rolePermissionService->refreshCache();
            
            $roleCount = count($rolePermissions);
            $permissionCount = 0;
            
            foreach ($rolePermissions as $permissions) {
                $permissionCount += count($permissions);
            }
            
            $this->info("✅ Successfully refreshed role-permission mappings:");
            $this->info("   - Roles: {$roleCount}");
            $this->info("   - Total permissions: {$permissionCount}");
            
            if ($this->option('verbose')) {
                $this->line('');
                $this->info('Role-Permission Mappings:');
                foreach ($rolePermissions as $role => $data) {
                    $this->line("Role: {$role}");
                    // Users
                    if (!empty($data['users'])) {
                        $this->line("  Users:");
                        foreach ($data['users'] as $user) {
                            $this->line("    - {$user['name']} ({$user['id']})");
                        }
                    } else {
                        $this->line("  Users: none");
                    }
                    // Permissions
                    if (!empty($data['permissions'])) {
                        $this->line("  Permissions:");
                        foreach ($data['permissions'] as $perm) {
                            if (is_array($perm)) {
                                $this->line("    - {$perm['name']} ({$perm['id']})");
                            } else {
                                $this->line("    - {$perm}");
                            }
                        }
                    } else {
                        $this->line("  Permissions: none");
                    }
                    $this->line("");
                }
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Failed to refresh role-permission mappings:');
            $this->error('   ' . $e->getMessage());
            return 1;
        }
    }
}
