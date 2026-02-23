<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\{
    DashboardController,
    PTKController,
    ApprovalController,
    ExportController,
    AuditController,
    PTKAttachmentController,
    ApprovalLogController
};

use App\Http\Controllers\Settings\CategorySettingsController;
use App\Models\{PTK, Attachment};

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn() => redirect()->route('dashboard'));

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | PTK CRUD
    |--------------------------------------------------------------------------
    */
    Route::resource('ptk', PTKController::class);

    Route::get('ptk-kanban', [PTKController::class, 'kanban'])
        ->name('ptk.kanban');

    Route::post('ptk/{ptk}/status', [PTKController::class, 'quickStatus'])
        ->name('ptk.status');

    Route::get('ptk-queue/{stage?}', [PTKController::class, 'queue'])
        ->whereIn('stage', ['approver', 'director'])
        ->name('ptk.queue')
        ->middleware('permission:menu.queue');

    Route::get('ptk-recycle', [PTKController::class, 'recycle'])
        ->name('ptk.recycle')
        ->middleware('permission:menu.recycle');

    Route::post('ptk/{id}/restore', [PTKController::class, 'restore'])
        ->name('ptk.restore');

    Route::delete('ptk/{id}/force', [PTKController::class, 'forceDelete'])
        ->name('ptk.force');

    Route::post('ptk-import', [PTKController::class, 'import'])
        ->name('ptk.import')
        ->middleware('throttle:uploads');


    /*
    |--------------------------------------------------------------------------
    | Approval & Submit
    |--------------------------------------------------------------------------
    */
    Route::post('ptk/{ptk}/approve', [ApprovalController::class, 'approve'])
        ->name('ptk.approve')
        ->middleware('permission:ptk.approve');

    Route::post('ptk/{ptk}/reject', [ApprovalController::class, 'reject'])
        ->name('ptk.reject')
        ->middleware('permission:ptk.reject');

    Route::post('ptk/{ptk}/submit', [PTKController::class, 'submit'])
        ->name('ptk.submit');


    /*
    |--------------------------------------------------------------------------
    | Approval Log (Route Baru)
    |--------------------------------------------------------------------------
    */
    Route::get('/approval-log', [ApprovalLogController::class, 'index'])
        ->name('approval.log');


    /*
    |--------------------------------------------------------------------------
    | Settings: Kategori & Subkategori
    |--------------------------------------------------------------------------
    */
    Route::prefix('settings')
        ->name('settings.')
        ->controller(CategorySettingsController::class)
        ->group(function () {

            Route::get('categories', 'index')->name('categories');
            Route::post('categories', 'storeCategory')->name('categories.store');
            Route::patch('categories/{category}', 'updateCategory')->name('categories.update');
            Route::delete('categories/{category}', 'deleteCategory')->name('categories.delete');

            Route::post('subcategories', 'storeSubcategory')->name('subcategories.store');
            Route::patch('subcategories/{subcategory}', 'updateSubcategory')->name('subcategories.update');
            Route::delete('subcategories/{subcategory}', 'deleteSubcategory')->name('subcategories.delete');
        });


    /*
    |--------------------------------------------------------------------------
    | API Dropdown Dinamis
    |--------------------------------------------------------------------------
    */
    Route::get('api/subcategories', [CategorySettingsController::class, 'apiSubcategories'])
        ->name('api.subcategories');


    /*
    |--------------------------------------------------------------------------
    | Export
    |--------------------------------------------------------------------------
    */
    Route::get('exports/preview/{ptk}', [ExportController::class, 'preview'])
        ->name('exports.preview');

    Route::get('exports/ptk/{id}/preview', [ExportController::class, 'previewPdf'])
        ->name('exports.pdf.preview');

    Route::get('exports/pdf/{ptk}', [ExportController::class, 'pdf'])
        ->name('exports.pdf');

    Route::prefix('exports')->name('exports.')->group(function () {

        Route::get('range', [ExportController::class, 'rangeForm'])
            ->name('range.form');

        Route::post('range', [ExportController::class, 'rangeReport'])
            ->name('range.report');

        Route::get('excel', [ExportController::class, 'excel'])
            ->name('excel');

        Route::post('range/excel', [ExportController::class, 'rangeExcel'])
            ->name('range.excel');

        Route::post('range/pdf', [ExportController::class, 'rangePdf'])
            ->name('range.pdf');

        Route::get('audits', [AuditController::class, 'index'])
            ->name('audits.index')
            ->middleware('permission:menu.audit');
    });


    /*
    |--------------------------------------------------------------------------
    | Attachment
    |--------------------------------------------------------------------------
    */
    Route::patch('attachments/{attachment}/caption', function (Request $request, Attachment $attachment) {

        abort_unless(auth()->user()->can('update', $attachment->ptk), 403);

        $data = $request->validate([
            'caption' => 'nullable|string|max:255'
        ]);

        $attachment->update($data);

        return back()->with('ok', 'Caption tersimpan.');
    })->name('attachments.caption');

    Route::delete('ptk-attachment/{id}', [PTKAttachmentController::class, 'destroy'])
        ->name('ptk.attachment.delete');

    Route::post('ptk/{ptk}/attachments-bulk', [PTKController::class, 'updateAttachments'])
        ->name('ptk.attachments.bulk');

});

require __DIR__ . '/auth.php';


/*
|--------------------------------------------------------------------------
| Verifikasi Dokumen (Publik)
|--------------------------------------------------------------------------
*/
Route::get('/verify/{ptk}/{hash}', function (PTK $ptk, string $hash) {

    $expected = hash('sha256', json_encode([
        'id' => $ptk->id,
        'number' => $ptk->number,
        'status' => $ptk->status,
        'due' => $ptk->due_date?->format('Y-m-d'),
        'approved_at' => $ptk->approved_at?->format('c'),
        'updated_at' => $ptk->updated_at?->format('c'),
    ]));

    return view('verify.result', [
        'ptk' => $ptk,
        'valid' => hash_equals($expected, $hash),
        'expected' => $expected,
        'hash' => $hash,
    ]);

})->name('verify.show');
