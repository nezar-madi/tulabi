@php
    // Navy green (resources/css/tokens.css), as rqmana's receipt in its blue.
    $primary = '#00613E';
    $dark = '#003A23';
    $tint = '#EDF6F0';
    $plate = '#D8EDE1';
    $isVoided = $payment->isVoided();
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('payments.receipt.title') }} {{ $payment->receipt_number }}</title>
    <style>
        body { font-family: 'tajawal', 'DejaVu Sans', sans-serif; font-size: 12px; color: #33393F; line-height: 1.6; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: middle; }

        .head td { padding: 0 0 10px 0; }
        .brand { font-size: 22px; font-weight: bold; color: {{ $primary }}; text-align: right; }
        .brand-sub { font-size: 12px; color: #5B6570; text-align: right; }
        .doc { text-align: left; }
        .doc-title { font-size: 14px; font-weight: bold; color: {{ $dark }}; }
        .doc-meta { font-size: 11px; color: #5B6570; }
        .rule { height: 4px; background-color: {{ $primary }}; margin: 0 0 16px 0; }

        .amount { background-color: {{ $tint }}; border: 1px solid {{ $plate }}; margin: 0 0 16px 0; }
        .amount td { padding: 12px 14px; }
        .amount-label { font-size: 12px; color: #5B6570; text-align: right; }
        .amount-label strong { font-size: 14px; color: #33393F; }
        .amount-value { font-size: 24px; font-weight: bold; color: {{ $primary }}; text-align: left; }
        .void-amount { text-decoration: line-through; color: #B42318; }

        .details th, .details td { text-align: right; padding: 7px 4px; border-bottom: 1px solid #E2ECE6; }
        .details th { width: 40%; font-weight: normal; color: #5B6570; }
        .details td { font-weight: bold; }
        .details tr.owed td { color: {{ $primary }}; }

        .void-banner { margin: 0 0 14px 0; padding: 8px 12px; background-color: #FCEBEA; border: 1px solid #B42318; color: #B42318; font-weight: bold; text-align: center; font-size: 13px; }
        .foot { margin-top: 22px; padding-top: 8px; border-top: 1px solid {{ $plate }}; font-size: 10px; color: #78838C; }
    </style>
</head>
<body>
    <table class="head">
        <tr>
            <td>
                <div class="brand">طلابي</div>
                <div class="brand-sub">{{ $instructorName }}</div>
            </td>
            <td class="doc">
                <div class="doc-title">{{ __('payments.receipt.title') }}@if($isVoided) — {{ __('payments.receipt.voided') }}@endif</div>
                <div class="doc-meta"><span dir="ltr">{{ $payment->receipt_number }}</span></div>
                <div class="doc-meta"><span dir="ltr">{{ $date }}</span></div>
            </td>
        </tr>
    </table>
    <div class="rule"></div>

    @if($isVoided)
        <div class="void-banner">{{ __('payments.receipt.voided') }} — {{ __('payments.receipt.voided_notice') }}</div>
    @endif

    <table class="amount">
        <tr>
            <td class="amount-label">
                {{ __('payments.receipt.student') }}<br>
                <strong>{{ $studentName }}</strong>
            </td>
            <td class="amount-value">
                @if($isVoided)<span class="void-amount">{{ $amount }} {{ $currency }}</span>@else{{ $amount }} {{ $currency }}@endif
            </td>
        </tr>
    </table>

    <table class="details">
        <tr><th>{{ __('payments.receipt.class') }}</th><td>{{ $classTitle }}</td></tr>
        <tr><th>{{ __('payments.receipt.instructor') }}</th><td>{{ $instructorName }}</td></tr>
        <tr><th>{{ __('payments.receipt.method') }}</th><td>{{ $method }}</td></tr>
        @if(! $isVoided && $fee !== null)
            <tr><th>{{ __('payments.receipt.fee') }}</th><td>{{ $fee }} {{ $currency }}</td></tr>
            <tr><th>{{ __('payments.receipt.paid_total') }}</th><td>{{ $paidTotal }} {{ $currency }}</td></tr>
            <tr class="owed"><th>{{ __('payments.receipt.remaining') }}</th><td>{{ $remaining }} {{ $currency }}</td></tr>
        @endif
        @if($payment->notes)
            <tr><th>{{ __('payments.receipt.notes') }}</th><td>{{ $payment->notes }}</td></tr>
        @endif
        @if($isVoided)
            <tr><th>{{ __('payments.receipt.voided_at') }}</th><td><span dir="ltr">{{ $voidedAt }}</span></td></tr>
            <tr><th>{{ __('payments.receipt.void_reason') }}</th><td>{{ $payment->void_reason }}</td></tr>
        @endif
    </table>

    <div class="foot">{{ __('payments.receipt.footer') }}</div>
</body>
</html>
