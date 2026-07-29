<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * 5 roles, granular `{action}_{resource}` permissions. See docs/modules/M01-foundation-auth.md
 * and docs/modules/M11-manager-panel.md for the capability tables this mirrors.
 *
 * super-admin and admin bypass permission checks entirely via a Gate::before hook
 * (AppServiceProvider) rather than being assigned every permission — the
 * conventional Spatie pattern, and it means a newly added permission is
 * automatically available to both without touching this seeder.
 */
class RolePermissionSeeder extends Seeder
{
    /** @var string[] */
    private const PERMISSIONS = [
        // Members & departments — M03
        'view_members', 'create_members', 'update_members', 'delete_members', 'import_members',
        'manage_departments', 'manage_designations',

        // Document engine — M04
        'view_documents', 'issue_documents', 'revoke_documents', 'regenerate_documents',
        'manage_document_templates',

        // Donations & payments — M05
        'view_donations', 'create_offline_donations', 'refund_donations', 'export_donations',
        'view_donors', 'update_donors',

        // Recurring — M06
        'view_subscriptions', 'cancel_subscriptions',

        // Receipts & 80G — M07
        'view_receipts', 'cancel_receipts', 'regenerate_receipts', 'export_form_10bd',

        // Campaigns — M08
        'view_campaigns', 'create_campaigns', 'update_campaigns', 'publish_campaigns',
        'delete_campaigns', 'manage_campaign_products', 'manage_campaign_categories',
        'view_fundraiser_requests', 'review_fundraiser_requests',

        // Notices & communication — M09
        'view_notices', 'publish_notices', 'manage_email_templates', 'manage_subscribers',

        // Public site & CMS — M10, 07-SEO
        'manage_pages', 'manage_posts', 'manage_testimonials', 'manage_press_mentions',
        'manage_impact_stats', 'manage_banners', 'view_contact_messages', 'manage_redirects',

        // Manager panel & permissions — M11
        'manage_users', 'manage_roles',

        // Reports & analytics — M12
        'view_reports', 'export_reports', 'view_manager_dashboard',

        // Foundation & settings — M01, M02
        'manage_settings', 'view_activity_log',
    ];

    public function run(): void
    {
        // Spatie caches permissions in memory per-request; a fresh seed run needs
        // this cleared or newly created permissions/roles silently fail to attach.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => UserRole::SuperAdmin->value, 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => UserRole::Admin->value, 'guard_name' => 'web']);
        $manager = Role::firstOrCreate(['name' => UserRole::Manager->value, 'guard_name' => 'web']);

        // member and donor intentionally get no permissions: /portal access is
        // route-level (auth + a policy on the user's own records), not
        // permission-gated resource actions. See docs/modules/M01-foundation-auth.md.
        Role::firstOrCreate(['name' => UserRole::Member->value, 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => UserRole::Donor->value, 'guard_name' => 'web']);

        // super-admin and admin bypass via Gate::before — see AppServiceProvider.
        // Assigning permissions to them anyway keeps Filament's navigation and any
        // direct `->can()` check correct even if the Gate::before hook is ever removed.
        $superAdmin->syncPermissions(Permission::all());
        $admin->syncPermissions(Permission::whereNotIn('name', ['manage_roles'])->get());

        // Manager panel capability table — docs/modules/M11-manager-panel.md.
        // Scoping to the manager's OWN department happens in the panel's query
        // scopes and policies, not here; these permissions only gate which
        // actions exist at all.
        $manager->syncPermissions([
            'view_members', 'create_members', 'update_members',
            'issue_documents', 'revoke_documents',
            'view_notices', 'publish_notices',
            'view_donations', 'view_campaigns',
            'view_manager_dashboard', 'view_reports',
        ]);
    }
}
