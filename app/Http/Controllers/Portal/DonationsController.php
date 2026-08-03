<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Receipts\Generate80GReceipt;
use App\Actions\Receipts\GenerateAnnualStatement;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\LinksDonorToUser;
use App\Models\Receipt;
use App\Support\FinancialYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Donor self-service — donation history + receipt downloads. Mirrors
 * Portal\DocumentsController's shape (M04) for members.
 */
class DonationsController extends Controller
{
    use LinksDonorToUser;

    public function index(Request $request): View
    {
        $donor = $this->linkedDonor($request);

        return view('portal.donations.index', [
            'donor' => $donor,
            'donations' => $donor
                ? $donor->donations()->with(['receipts' => fn ($q) => $q->where('is_cancelled', false)])->latest('created_at')->get()
                : collect(),
        ]);
    }

    public function downloadReceipt(Request $request, Receipt $receipt): StreamedResponse|RedirectResponse
    {
        $donor = $this->linkedDonor($request);

        abort_unless($donor && $receipt->donor_id === $donor->id, Response::HTTP_FORBIDDEN);
        abort_unless($receipt->file_path !== null, Response::HTTP_NOT_FOUND);

        $receipt->increment('download_count');

        // receipt_number contains "/" (e.g. "VGWGF/RCP/2026-27/00001") — a
        // legal, valid receipt number, but not a legal Content-Disposition
        // filename. Sanitize for the download name only; the stored number
        // itself is untouched.
        $filename = str_replace('/', '-', $receipt->receipt_number);

        return Storage::disk('local')->download($receipt->file_path, "{$filename}.pdf");
    }

    /**
     * On-demand summary PDF of a donor's non-cancelled receipts for one FY
     * (docs/modules/M07-receipts-80g.md's "Annual consolidated statement").
     * Not a stored, numbered document — rendered fresh on every request.
     */
    public function downloadAnnualStatement(Request $request, string $financialYear, GenerateAnnualStatement $action): Response
    {
        $donor = $this->linkedDonor($request);
        abort_unless($donor !== null, Response::HTTP_FORBIDDEN);

        $pdf = $action->handle($donor, $financialYear);

        return response($pdf, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="annual-statement-'.$financialYear.'.pdf"',
        ]);
    }

    /**
     * Retroactive PAN capture — see docs/modules/M07-receipts-80g.md's "Donor
     * adds PAN after donating" edge case: allow 80G generation for donations
     * in the current and previous FY once PAN/address arrive.
     */
    public function updatePan(Request $request, Generate80GReceipt $generate80g): RedirectResponse
    {
        $donor = $this->linkedDonor($request);
        abort_unless($donor !== null, Response::HTTP_FORBIDDEN);

        $data = $request->validate([
            'pan' => ['required', 'string', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/i'],
            'address_line1' => ['required', 'string', 'max:190'],
            'address_line2' => ['nullable', 'string', 'max:190'],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['required', 'string', 'max:80'],
            'pincode' => ['required', 'string', 'max:10'],
        ]);

        $donor->update($data);

        $eligibleYears = [FinancialYear::current()->toString(), FinancialYear::for(now()->subYear())->toString()];
        $issued = 0;

        foreach ($donor->donations()->where('status', 'succeeded')->where('eligible_for_80g', true)->whereIn('financial_year', $eligibleYears)->get() as $donation) {
            try {
                $generate80g->handle($donation);
                $issued++;
            } catch (InvalidArgumentException) {
                // Some other gate still blocks it (e.g. 80G registration
                // expired) — the donor already got the acknowledgement
                // receipt regardless.
            }
        }

        return back()->with('status', $issued > 0
            ? "Thanks! {$issued} 80G receipt(s) have been generated and emailed to you."
            : 'Your details have been saved.');
    }
}
