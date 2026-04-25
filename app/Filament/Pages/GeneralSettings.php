<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GeneralSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'الإعدادات';
    protected static ?string $navigationLabel = 'إعدادات الشركة';
    protected static string $view = 'filament.pages.general-settings';
    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'company_name'    => Setting::get('company_name', 'شركة الأخشاب'),
            'business_type'   => Setting::get('business_type', 'تجارة عامة'),
            'primary_color'   => Setting::get('primary_color', '#8B6914'),
            'currency'        => Setting::get('currency', 'EGP'),
            'company_address' => Setting::get('company_address'),
            'company_phone'   => Setting::get('company_phone'),
            'company_email'   => Setting::get('company_email'),
            'tax_number'      => Setting::get('tax_number'),
            'invoice_footer'  => Setting::get('invoice_footer', 'شكراً لتعاملكم معنا'),
            'logo_url'        => Setting::get('logo_url'),
            'logo_upload'     => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الشركة')->schema([
                Forms\Components\TextInput::make('company_name')
                    ->label('اسم الشركة')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('business_type')
                    ->label('نوع النشاط')
                    ->maxLength(255),
                Forms\Components\Select::make('currency')
                    ->label('العملة الافتراضية')
                    ->options(['EGP' => 'جنيه مصري (EGP)', 'USD' => 'دولار أمريكي (USD)', 'EUR' => 'يورو (EUR)'])
                    ->default('EGP'),
                Forms\Components\ColorPicker::make('primary_color')
                    ->label('اللون الرئيسي للنظام'),
                Forms\Components\TextInput::make('company_phone')
                    ->label('رقم الهاتف')
                    ->tel(),
                Forms\Components\TextInput::make('company_email')
                    ->label('البريد الإلكتروني')
                    ->email(),
                Forms\Components\TextInput::make('tax_number')
                    ->label('الرقم الضريبي'),
                Forms\Components\Textarea::make('company_address')
                    ->label('عنوان الشركة')
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('الفواتير والشعار')->schema([
                Forms\Components\FileUpload::make('logo_upload')
                    ->label('رفع شعار الشركة (PNG/JPG)')
                    ->image()
                    ->disk('public')
                    ->directory('logo')
                    ->visibility('public')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('logo_url')
                    ->label('أو رابط الشعار الحالي (URL)')
                    ->placeholder('https://example.com/logo.png')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('invoice_footer')
                    ->label('نص تذييل الفواتير')
                    ->rows(2)
                    ->columnSpanFull(),
            ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('company_name',    $data['company_name'],    'general');
        Setting::set('business_type',   $data['business_type'] ?? '', 'general');
        Setting::set('primary_color',   $data['primary_color'] ?? '#8B6914', 'appearance');
        Setting::set('currency',        $data['currency'] ?? 'EGP', 'general');
        Setting::set('company_address', $data['company_address'] ?? '', 'general');
        Setting::set('company_phone',   $data['company_phone'] ?? '', 'general');
        Setting::set('company_email',   $data['company_email'] ?? '', 'general');
        Setting::set('tax_number',      $data['tax_number'] ?? '', 'general');
        Setting::set('invoice_footer',  $data['invoice_footer'] ?? '', 'invoices');
        if (!empty($data['logo_upload'])) {
            Setting::set('logo_url', Storage::disk('public')->url($data['logo_upload']), 'appearance');
        } else {
            Setting::set('logo_url', $data['logo_url'] ?? '', 'appearance');
        }

        Cache::flush();

        Notification::make()->success()->title('تم حفظ الإعدادات بنجاح')->send();

        $this->redirect(static::getUrl());
    }
}
