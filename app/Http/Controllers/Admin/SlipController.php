<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use Illuminate\Support\Facades\Storage;

/**
 * Streams a bank-transfer payment slip to an authenticated admin
 * (Filament "View slip" action links here). Slips live on the private
 * local disk — this route is the only way to see them.
 */
class SlipController extends Controller
{
    public function show(Purchase $purchase)
    {
        abort_unless($purchase->proof_image_path, 404);

        abort_unless(Storage::disk('local')->exists($purchase->proof_image_path), 404);

        return Storage::disk('local')->response($purchase->proof_image_path);
    }
}
