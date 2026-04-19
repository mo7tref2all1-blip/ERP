<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ============ الأدوار والصلاحيات ============
        $this->call(RolesAndPermissionsSeeder::class);

        // ============ الإعدادات ============
        $this->call(SettingsSeeder::class);

        // ============ الفرع الرئيسي ============
        $mainBranch = Branch::create([
            'name' => 'الفرع الرئيسي',
            'address' => 'القاهرة، مصر',
            'phone' => '01000000000',
            'is_active' => true,
        ]);

        // ============ المستخدم الأدمن ============
        $admin = User::create([
            'name' => 'مدير النظام',
            'email' => 'admin@wood-erp.com',
            'password' => Hash::make('password'),
            'phone' => '01000000000',
            'branch_id' => $mainBranch->id,
            'is_active' => true,
        ]);
        $admin->assignRole('super_admin');

        // ============ مستخدمين للاختبار ============
        $accountant = User::create([
            'name' => 'المحاسب',
            'email' => 'accountant@wood-erp.com',
            'password' => Hash::make('password'),
            'branch_id' => $mainBranch->id,
            'is_active' => true,
        ]);
        $accountant->assignRole('accountant');

        $warehouse = User::create([
            'name' => 'أمين المخزن',
            'email' => 'warehouse@wood-erp.com',
            'password' => Hash::make('password'),
            'branch_id' => $mainBranch->id,
            'is_active' => true,
        ]);
        $warehouse->assignRole('warehouse');

        $salesperson = User::create([
            'name' => 'البائع',
            'email' => 'sales@wood-erp.com',
            'password' => Hash::make('password'),
            'branch_id' => $mainBranch->id,
            'is_active' => true,
        ]);
        $salesperson->assignRole('salesperson');

        // ============ تصنيفات الخشب ============
        $categories = [
            ['name' => 'خشب صنوبر', 'description' => 'أنواع خشب الصنوبر المختلفة'],
            ['name' => 'خشب بلوط', 'description' => 'أنواع خشب البلوط'],
            ['name' => 'خشب زان', 'description' => 'أنواع خشب الزان'],
            ['name' => 'خشب مضغوط', 'description' => 'ألواح الخشب المضغوط والـ MDF'],
            ['name' => 'خشب خام', 'description' => 'خشب خام غير مصنع'],
        ];
        foreach ($categories as $cat) {
            ProductCategory::create($cat);
        }

        // ============ الحسابات البنكية ============
        BankAccount::create([
            'name' => 'الخزينة الرئيسية',
            'type' => 'cash',
            'opening_balance' => 0,
            'is_active' => true,
        ]);
        BankAccount::create([
            'name' => 'البنك الأهلي',
            'type' => 'bank',
            'bank_name' => 'البنك الأهلي المصري',
            'opening_balance' => 0,
            'is_active' => true,
        ]);

    }
}
