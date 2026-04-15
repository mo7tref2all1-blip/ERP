<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // الموارد والعمليات
        $resources = [
            'branches',
            'users',
            'product_categories',
            'products',
            'branch_stock',
            'stock_movements',
            'suppliers',
            'supplier_invoices',
            'supplier_payments',
            'customers',
            'customer_quotes',
            'customer_invoices',
            'customer_payments',
            'bank_accounts',
            'account_transactions',
            'settings',
            'reports',
        ];

        $actions = ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore'];

        // إنشاء الصلاحيات
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$action}_{$resource}",
                    'guard_name' => 'web',
                ]);
            }
        }

        // ============ الأدوار ============

        // 1. مدير النظام - كل الصلاحيات
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // 2. المحاسب
        $accountant = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);
        $accountantPermissions = [];
        $accountantResources = ['bank_accounts', 'account_transactions', 'supplier_invoices', 'supplier_payments', 'customer_invoices', 'customer_payments', 'customer_quotes', 'suppliers', 'customers', 'reports', 'products', 'branches'];
        foreach ($accountantResources as $resource) {
            foreach (['view_any', 'view', 'create', 'update'] as $action) {
                $accountantPermissions[] = "{$action}_{$resource}";
            }
        }
        $accountant->syncPermissions($accountantPermissions);

        // 3. أمين المخزن
        $warehouse = Role::firstOrCreate(['name' => 'warehouse', 'guard_name' => 'web']);
        $warehousePermissions = [];
        $warehouseResources = ['products', 'product_categories', 'branch_stock', 'stock_movements', 'branches'];
        foreach ($warehouseResources as $resource) {
            foreach (['view_any', 'view', 'create', 'update'] as $action) {
                $warehousePermissions[] = "{$action}_{$resource}";
            }
        }
        // فواتير التوريد فقط (عرض وإنشاء)
        $warehousePermissions = array_merge($warehousePermissions, [
            'view_any_supplier_invoices', 'view_supplier_invoices', 'create_supplier_invoices',
        ]);
        $warehouse->syncPermissions($warehousePermissions);

        // 4. البائع
        $salesperson = Role::firstOrCreate(['name' => 'salesperson', 'guard_name' => 'web']);
        $salespersonPermissions = [];
        $salespersonResources = ['customers', 'customer_quotes', 'customer_invoices', 'customer_payments'];
        foreach ($salespersonResources as $resource) {
            foreach (['view_any', 'view', 'create', 'update'] as $action) {
                $salespersonPermissions[] = "{$action}_{$resource}";
            }
        }
        // عرض المخزون فقط (بدون أسعار التكلفة)
        $salespersonPermissions = array_merge($salespersonPermissions, [
            'view_any_products', 'view_products',
            'view_any_branch_stock', 'view_branch_stock',
        ]);
        $salesperson->syncPermissions($salespersonPermissions);
    }
}
