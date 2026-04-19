<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class SystemUpdate extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-circle';
    protected static ?string $navigationGroup = 'الإعدادات';
    protected static ?string $navigationLabel = 'تحديث النظام';
    protected static string $view = 'filament.pages.system-update';
    protected static ?int $navigationSort = 99;

    public ?array $data = [];
    public array $log = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('رفع ملف التحديث')->schema([
                Forms\Components\FileUpload::make('zip_file')
                    ->label('ملف التحديث (ZIP)')
                    ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed', 'application/octet-stream'])
                    ->maxSize(51200)
                    ->required()
                    ->disk('local')
                    ->directory('updates'),
            ]),
        ])->statePath('data');
    }

    public function update(): void
    {
        $data = $this->form->getState();
        $this->log = [];

        if (empty($data['zip_file'])) {
            Notification::make()->danger()->title('يجب رفع ملف ZIP')->send();
            return;
        }

        $zipPath = storage_path('app/' . $data['zip_file']);

        if (!file_exists($zipPath)) {
            Notification::make()->danger()->title('لم يتم العثور على الملف')->send();
            return;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            Notification::make()->danger()->title('لا يمكن فتح ملف ZIP - تأكد أن الملف صحيح')->send();
            return;
        }

        $tempDir = storage_path('app/update_temp_' . time());
        mkdir($tempDir, 0755, true);
        $zip->extractTo($tempDir);
        $zip->close();
        $this->log[] = '✓ تم فك ضغط الملف بنجاح';

        // نسخ الملفات مع تجاهل الملفات الحساسة
        $protected = ['.env', 'storage/', 'vendor/', 'css/', 'js/'];
        $subDirs = glob($tempDir . '/*', GLOB_ONLYDIR);
        $sourceDir = !empty($subDirs) ? $subDirs[0] : $tempDir;
        $this->copyFiles($sourceDir, base_path(), $protected);
        $this->log[] = '✓ تم نسخ الملفات الجديدة';

        $this->deleteDirectory($tempDir);
        @unlink($zipPath);
        $this->log[] = '✓ تم تنظيف الملفات المؤقتة';

        foreach ([
            'migrate'         => ['--force' => true],
            'optimize'        => [],
            'filament:assets' => [],
        ] as $command => $args) {
            try {
                Artisan::call($command, $args);
                $this->log[] = "✓ تم تنفيذ: php artisan {$command}";
            } catch (\Throwable $e) {
                $this->log[] = "✗ خطأ في {$command}: " . $e->getMessage();
            }
        }

        Notification::make()->success()->title('تم التحديث بنجاح')->send();
    }

    private function copyFiles(string $from, string $to, array $protected): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($from, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relative = ltrim(str_replace($from, '', $item->getPathname()), '/\\');

            foreach ($protected as $guard) {
                if (str_starts_with($relative, $guard)) {
                    continue 2;
                }
            }

            $target = $to . DIRECTORY_SEPARATOR . $relative;

            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                copy($item->getPathname(), $target);
            }
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($dir);
    }
}
