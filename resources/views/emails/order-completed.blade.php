<x-emails.layout :title="__('mail.order_completed.html.title')">
    <h1 style="font-size:18px;font-weight:700;border-bottom:2px solid #0ea5e9;padding-bottom:10px;margin:0 0 20px;color:#0f172a;">
        {{ __('mail.order_completed.html.heading') }}
    </h1>

    <p style="margin:0 0 12px;">{{ __('mail.order_completed.html.greeting_format', ['name' => $order->user->name]) }}</p>

    <p style="margin:0 0 20px;">
        {!! __('mail.order_completed.html.intro_html', ['tenant' => '<strong>'.e($order->tenant->name).'</strong>']) !!}
    </p>

    <table style="width:100%;border-collapse:collapse;margin:0 0 20px;">
        <tr>
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;width:140px;font-weight:600;">{{ __('mail.order_completed.html.label_order_number') }}</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;">{{ $order->order_code }}</td>
        </tr>
        <tr>
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;">{{ __('mail.order_completed.html.label_tenant') }}</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;">{{ $order->tenant->name }}</td>
        </tr>
        <tr>
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;">{{ __('mail.order_completed.html.label_completed_at') }}</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;">{{ $order->completed_at->format(__('mail.order_completed.html.date_format')) }}</td>
        </tr>
    </table>

    <h2 style="font-size:15px;font-weight:600;margin:0 0 12px;color:#0f172a;">{{ __('mail.order_completed.html.heading_items') }}</h2>

    <table style="width:100%;border-collapse:collapse;margin:0 0 20px;">
        <thead>
            <tr>
                <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;">{{ __('mail.order_completed.html.col_item_name') }}</th>
                <th style="text-align:center;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;width:60px;">{{ __('mail.order_completed.html.col_quantity') }}</th>
                <th style="text-align:right;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;width:100px;">{{ __('mail.order_completed.html.col_subtotal') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td style="padding:8px 12px;border:1px solid #e2e8f0;">
                    {{ $item->name }}
                    @if($item->options->isNotEmpty())
                    <br>
                    <span style="font-size:12px;color:#64748b;">
                        {{ $item->options->pluck('name')->join(__('mail.order_completed.html.options_separator')) }}
                    </span>
                    @endif
                </td>
                <td style="text-align:center;padding:8px 12px;border:1px solid #e2e8f0;">{{ $item->quantity }}</td>
                <td style="text-align:right;padding:8px 12px;border:1px solid #e2e8f0;">{!! __('mail.order_completed.html.currency_symbol') !!}{{ number_format($item->subtotal) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:right;padding:10px 12px;border:1px solid #e2e8f0;font-weight:700;background-color:#f1f5f9;">{{ __('mail.order_completed.html.label_total') }}</td>
                <td style="text-align:right;padding:10px 12px;border:1px solid #e2e8f0;font-weight:700;background-color:#f1f5f9;">{!! __('mail.order_completed.html.currency_symbol') !!}{{ number_format($order->total_amount) }}</td>
            </tr>
        </tfoot>
    </table>

    <p style="margin:0 0 4px;">
        {!! __('mail.order_completed.html.outro_html') !!}
    </p>

    <hr style="border:none;border-top:1px solid #e2e8f0;margin:28px 0;">

    <p style="font-size:12px;color:#64748b;margin:0;">
        {!! __('mail.order_completed.html.footer_disclaimer_html') !!}
    </p>
</x-emails.layout>
