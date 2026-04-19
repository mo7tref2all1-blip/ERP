<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // عامة
            ['key' => 'company_name',    'value' => 'شركة الأخشاب المتحدة', 'group' => 'general'],
            ['key' => 'business_type',   'value' => 'تجارة أخشاب',           'group' => 'general'],
            ['key' => 'currency',        'value' => 'EGP',                   'group' => 'general'],
            ['key' => 'company_phone',   'value' => '01000000000',           'group' => 'general'],
            ['key' => 'company_address', 'value' => 'القاهرة، مصر',          'group' => 'general'],
            ['key' => 'company_email',   'value' => '',                      'group' => 'general'],
            ['key' => 'tax_number',      'value' => '',                      'group' => 'general'],

            // مظهر
            ['key' => 'primary_color',   'value' => '#8B6914',               'group' => 'appearance'],
            ['key' => 'logo_url',        'value' => '',                      'group' => 'appearance'],

            // فواتير
            ['key' => 'invoice_footer',         'value' => 'شكراً لتعاملكم معنا - يسعدنا خدمتكم دائماً', 'group' => 'invoices'],
            ['key' => 'invoice_footer_text',    'value' => 'شكراً لتعاملكم معنا - يسعدنا خدمتكم دائماً', 'group' => 'invoices'],

            // مبيعات
            ['key' => 'salesperson_max_discount', 'value' => '10', 'group' => 'sales'],
            ['key' => 'default_dollar_rate',      'value' => '50', 'group' => 'finance'],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(['key' => $s['key']], ['value' => $s['value'], 'group' => $s['group']]);
        }
    }
}
