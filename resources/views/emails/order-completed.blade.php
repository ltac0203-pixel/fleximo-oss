<x-emails.layout title="ご注文完了のお知らせ">
    <h1 style="font-size:18px;font-weight:700;border-bottom:2px solid #0ea5e9;padding-bottom:10px;margin:0 0 20px;color:#0f172a;">
        ご注文が完了しました
    </h1>

    <p style="margin:0 0 12px;">{{ $order->user->name }} 様</p>

    <p style="margin:0 0 20px;">
        この度はご注文いただきありがとうございます。<br>
        <strong>{{ $order->tenant->name }}</strong> でのご注文が完了しました。
    </p>

    <table style="width:100%;border-collapse:collapse;margin:0 0 20px;">
        <tr>
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;width:140px;font-weight:600;">注文番号</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;">{{ $order->order_code }}</td>
        </tr>
        <tr>
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;">店舗名</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;">{{ $order->tenant->name }}</td>
        </tr>
        <tr>
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;">完了日時</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;">{{ $order->completed_at->format('Y年m月d日 H:i') }}</td>
        </tr>
    </table>

    <h2 style="font-size:15px;font-weight:600;margin:0 0 12px;color:#0f172a;">注文内容</h2>

    <table style="width:100%;border-collapse:collapse;margin:0 0 20px;">
        <thead>
            <tr>
                <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;">商品名</th>
                <th style="text-align:center;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;width:60px;">数量</th>
                <th style="text-align:right;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;width:100px;">小計</th>
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
                        {{ $item->options->pluck('name')->join('、') }}
                    </span>
                    @endif
                </td>
                <td style="text-align:center;padding:8px 12px;border:1px solid #e2e8f0;">{{ $item->quantity }}</td>
                <td style="text-align:right;padding:8px 12px;border:1px solid #e2e8f0;">&yen;{{ number_format($item->subtotal) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:right;padding:10px 12px;border:1px solid #e2e8f0;font-weight:700;background-color:#f1f5f9;">合計</td>
                <td style="text-align:right;padding:10px 12px;border:1px solid #e2e8f0;font-weight:700;background-color:#f1f5f9;">&yen;{{ number_format($order->total_amount) }}</td>
            </tr>
        </tfoot>
    </table>

    <p style="margin:0 0 4px;">
        またのご利用をお待ちしております。<br>
        Fleximo をよろしくお願いいたします。
    </p>

    <hr style="border:none;border-top:1px solid #e2e8f0;margin:28px 0;">

    <p style="font-size:12px;color:#64748b;margin:0;">
        このメールは Fleximo から自動送信されました。<br>
        お心当たりがない場合は、このメールを破棄してください。
    </p>
</x-emails.layout>
