<?php

declare(strict_types=1);

use Filament\Panel;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Liberu\Accounting\Core\Models\LegalEntity;
use Liberu\Accounting\FinancialMasterData\Models\Party;
use Liberu\Accounting\SupplierBillsFilament\Resources\Pages\CreateSupplierBill;
use Liberu\Accounting\SupplierBillsFilament\Resources\Pages\EditSupplierBill;
use Liberu\Accounting\SupplierBillsFilament\Resources\Pages\ListSupplierBills;
use Liberu\Accounting\SupplierBillsFilament\Resources\SupplierBillResource;
use Liberu\Accounting\SupplierBillsFilament\SupplierBillsFilamentPlugin;
use Liberu\Accounting\SupplierBillsFilament\SupplierBillsFilamentServiceProvider;

it('registers and configures accounting-supplier-bills-filament filament resources', function (): void {
    $provider = $this->app->register(SupplierBillsFilamentServiceProvider::class, force: true);
    if (method_exists($provider, 'boot')) {
        $provider->boot();
    }
    expect($provider)->toBeInstanceOf(SupplierBillsFilamentServiceProvider::class);
    $plugin = SupplierBillsFilamentPlugin::make();
    $panel = Panel::make()->id('test-accounting-supplier-bills-filament');
    $plugin->register($panel);
    $plugin->boot($panel);
    expect($plugin->getId())->toBeString()->not->toBeEmpty();
    $host = new ListSupplierBills();
    expect(SupplierBillResource::getPages())->toBeArray();
    expect(SupplierBillResource::form(Schema::make($host)))->toBeInstanceOf(Schema::class);
    expect(SupplierBillResource::table(Table::make($host)))->toBeInstanceOf(Table::class);
});

it('covers CreateSupplierBill and EditSupplierBill record handlers', function (): void {
    $entity = LegalEntity::query()->create(['name' => 'SBF Ent', 'currency_code' => 'GBP', 'accounting_basis' => 'accrual']);
    $party = Party::query()->create(['legal_entity_id' => $entity->getKey(), 'type' => 'supplier', 'name' => 'Sup F']);

    $createPage = new CreateSupplierBill();
    $bill = Closure::bind(fn () => $this->handleRecordCreation([
        'party_id' => $party->getKey(),
        'bill_date' => '2026-01-15',
        'due_on' => '2026-02-15',
        'currency' => 'GBP',
        'lines' => [
            ['description' => 'Service', 'quantity' => 1, 'unit_price' => 100],
        ],
    ]), $createPage, $createPage::class)();
    expect($bill)->not->toBeNull();

    $editPage = new EditSupplierBill();
    $updated = Closure::bind(fn () => $this->handleRecordUpdate($bill, [
        'party_id' => $party->getKey(),
        'bill_date' => '2026-01-15',
        'due_on' => '2026-02-20',
        'currency' => 'GBP',
        'lines' => [
            ['description' => 'Service Updated', 'quantity' => 2, 'unit_price' => 100],
        ],
    ]), $editPage, $editPage::class)();
    expect($updated)->not->toBeNull();

    expect(fn () => Closure::bind(fn () => $this->handleRecordUpdate($entity, []), $editPage, $editPage::class)())
        ->toThrow(LogicException::class);
});
