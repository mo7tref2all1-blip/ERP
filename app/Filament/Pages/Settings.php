<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'الإعدادات';
    protected static ?string $navigationLabel = 'إعدادات النظام';
    protected static string $view = 'filament.pages.settings';
    protected static ?int $navigationSort = 10;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'company_name' => Setting::get('company_name', 'شركة الأخشاب'),
            'company_phone' => Setting::get('company_phone'),
            'company_address' => Setting::get('company_address'),
            'salesperson_max_discount' => Setting::get('salesperson_max_discount', 10),
            'default_dollar_rate' => Setting::get('default_dollar_rate', 50),
            'low_stock_notification_email' => Setting::get('low_stock_notification_email'),
            'invoice_footer_text' => Setting::get('invoice_footer_text', 'شكراً لتعاملكم معنا'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الشركة')->schema([
                Forms\Components\TextInput::make('company_name')->label('اسم الشركة')->required(),
                Forms\Components\TextInput::make('company_phone')->label('هاتف الشركة'),
                Forms\Components\Textarea::make('company_address')->label('عنوان الشركة')->rows(2),
            ])->columns(2),

            Forms\Components\Section::make('إعدادات المبيعات')->schema([
                Forms\Components\TextInput::make('salesperson_max_discount')
                    ->label('الحد الأقصى لخصم البائع (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->required(),
                Forms\Components\TextInput::make('default_dollar_rate')
                    ->label('سعر الدولار الافتراضي (جنيه)')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('ج.م'),
            ])->columns(2),

            Forms\Components\Section::make('إعدادات الفواتير')->schema([
                Forms\Components\Textarea::make('invoice_footer_text')
                    ->label('نص ذيل الفاتورة')
                    ->rows(2),
            ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }
        Notification::make()->success()->title('تم حفظ الإعدادات بنجاح')->send();
    }
}
