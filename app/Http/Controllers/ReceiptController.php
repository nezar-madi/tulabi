<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use App\Support\Currency;
use App\Support\TulabiClock;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * A payment receipt as PDF (mPDF, as in rqmana). Only the instructor who recorded it and
 * the student it is for can open it.
 */
class ReceiptController extends Controller
{
    public function show(Request $request, Payment $payment): Response
    {
        $payment->load(['enrollment.user', 'enrollment.teachingClass', 'instructor']);
        // The website's session or the app's token (/api/v1/receipts/{payment}).
        abort_unless($this->canSee($request->user(), $payment), 403);

        $html = view('receipts.payment', $this->context($payment))->render();
        $binary = $this->pdf($html);

        return new Response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="receipt-'.$payment->receipt_number.'.pdf"',
            'Content-Length' => (string) strlen($binary),
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function canSee(?User $user, Payment $payment): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $payment->enrollment->user_id === $user->getKey()
            || ($user->instructor !== null && $user->instructor->getKey() === $payment->instructor_id);
    }

    /**
     * Paid and remaining as they stood with this payment (earlier and this one, not voided),
     * so a printed receipt keeps saying the same thing.
     *
     * @return array<string, mixed>
     */
    private function context(Payment $payment): array
    {
        $enrollment = $payment->enrollment;
        $paidUpTo = $enrollment->payments()
            ->notVoided()
            ->where('id', '<=', $payment->id)
            ->get(['amount'])
            ->sum(fn (Payment $row) => Currency::toMillis($row->amount));
        $fee = $enrollment->fee === null ? null : Currency::toMillis($enrollment->fee);

        return [
            'payment' => $payment,
            'instructorName' => $payment->instructor->display_name,
            'studentName' => $enrollment->user?->name ?? '—',
            'classTitle' => $enrollment->teachingClass->title,
            'amount' => Currency::format($payment->amount),
            'currency' => $payment->currency_code === Currency::CODE ? Currency::SYMBOL : $payment->currency_code,
            'date' => $payment->paidOn(),
            'method' => __('payments.methods.'.$payment->method),
            'fee' => $fee === null ? null : Currency::format(Currency::fromMillis($fee)),
            'paidTotal' => Currency::format(Currency::fromMillis($paidUpTo)),
            'remaining' => $fee === null ? null : Currency::format(Currency::fromMillis(max(0, $fee - $paidUpTo))),
            'voidedAt' => $payment->voided_at?->copy()->setTimezone(TulabiClock::timezone())->toDateString(),
        ];
    }

    private function pdf(string $html): string
    {
        $tempDir = storage_path('app/mpdf-tmp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $fontDirs = array_merge((new ConfigVariables)->getDefaults()['fontDir'], [resource_path('fonts')]);
        $fontdata = (new FontVariables)->getDefaults()['fontdata'];
        // OTL does the Arabic letter joining.
        $fontdata['tajawal'] = ['R' => 'Tajawal-Regular.ttf', 'B' => 'Tajawal-Bold.ttf', 'useOTL' => 0xFF];

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A5',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 12,
            'margin_bottom' => 12,
            'tempDir' => $tempDir,
            'fontDir' => $fontDirs,
            'fontdata' => $fontdata,
            'default_font' => 'tajawal',
            'useSubstitutions' => true,
            'backupSubsFont' => ['dejavusans'],
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }
}
