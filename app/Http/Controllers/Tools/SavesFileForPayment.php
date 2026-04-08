<?php

namespace App\Http\Controllers\Tools;

use App\Models\UserFile;
use Illuminate\Http\RedirectResponse;

/**
 * Shared logic for saving an uploaded file when the guest has hit the limit.
 * The file is persisted to disk immediately; a job is dispatched after payment.
 */
trait SavesFileForPayment
{
    /**
     * Create an "awaiting_payment" UserFile record and redirect to pricing.
     * The pending_file_uuid is stored in the session so the checkout controller
     * can pass it to Stripe, and the success handler can dispatch the right job.
     *
     * @param string  $batchId           UUID of this batch
     * @param array   $ownerField        ["user_id" => x] or ["guest_id" => x]
     * @param string  $operationType     e.g. "merge_pdf"
     * @param array   $originalFilenames
     * @param int     $totalInputSize    bytes
     * @param array   $inputPaths        absolute paths to temp files
     * @param string  $tempPath          relative temp path (for cleanup after job)
     * @param array   $extraMeta         any extra metadata to merge (e.g. groups for split)
     */
    protected function saveForPayment(
        string $batchId,
        array  $ownerField,
        string $operationType,
        array  $originalFilenames,
        int    $totalInputSize,
        array  $inputPaths,
        string $tempPath,
        array  $extraMeta = [],
    ): RedirectResponse {
        UserFile::create([
            "uuid"               => $batchId,
            ...$ownerField,
            "operation_type"     => $operationType,
            "original_filenames" => $originalFilenames,
            "input_size_bytes"   => $totalInputSize,
            "status"             => "awaiting_payment",
            "metadata"           => array_merge($extraMeta, [
                "batch_id"    => $batchId,
                "pending_job" => [
                    "input_paths" => $inputPaths,
                    "temp_path"   => $tempPath,
                ],
            ]),
        ]);

        session(["pending_file_uuid" => $batchId]);

        return redirect()->route("pricing")
            ->with("info", "Your file is saved. Complete your upgrade and it will be processed automatically.");
    }
}
