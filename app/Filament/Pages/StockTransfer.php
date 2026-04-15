<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Services\InventoryService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class StockTransfer extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'المخزون والمنتجات';
    protected static ?string $navigationLabel = 'تحويل بين الفروع';
    protected static string $view = 'filament.pages.stock-transfer';
    protected static ?int $navigationSort = 3;

    public ?array $data = [];
    public ?float $available_quantity = null;

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('تفاصيل التحويل')->schema([
                Forms\Components\Select::make('product_id')
                    ->label('الصنف')
                    ->options(Product::where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, Get $get) => $this->updateAvailableQty($state, $get('from_branch_id'))),
                Forms\Components\Select::make('from_branch_id')
                    ->label('من فرع')
                    ->options(Branch::where('is_active', true)->pluck('name', 'id'))
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, Get $get) => $this->updateAvailableQty($get('product_id'), $state)),
                Forms\Components\Select::make('to_branch_id')
                    ->label('إلى فرع')
                    ->options(Branch::where('is_active', true)->pluck('name', 'id'))
                    ->required()
                    ->different('from_branch_id'),
                Forms\Components\TextInput::make('quantity')
                    ->label('الكمية المحولة')
                    ->numeric()
                    ->required()
                    ->minValue(0.001)
                    ->hint(fn () => $this->available_quantity !== null
                        ? 'المتاح: ' . number_format($this->available_quantity, 2)
                        : null),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(2),
        ])->statePath('data');
    }

    protected function updateAvailableQty(?int $productId, ?int $branchId): void
    {
        if ($productId && $branchId) {
            $this->available_quantity = BranchStock::where('product_id', $productId)
                ->where('branch_id', $branchId)
                ->value('quantity') ?? 0;
        }
    }

    public function transfer(): void
    {
        $data = $this->form->getState();

        try {
            app(InventoryService::class)->transferBetweenBranches(
                productId: $data['product_id'],
                fromBranchId: $data['from_branch_id'],
                toBranchId: $data['to_branch_id'],
                quantity: $data['quantity'],
                notes: $data['notes'] ?? null,
            );

            $this->form->fill();
            $this->available_quantity = null;

            Notification::make()
                ->success()
                ->title('تم التحويل بنجاح')
                ->body("تم تحويل {$data['quantity']} وحدة بين الفروع.")
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('خطأ في التحويل')
                ->body($e->getMessage())
                ->send();
        }
    }
}
