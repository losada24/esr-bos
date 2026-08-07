@php
  $statusCount = $totals['statuses'] ?? 0;
  $orderCount = $totals['overdue_orders'] ?? 0;
  $amount = (float) ($totals['amount'] ?? 0);
@endphp

<div style="margin: 0; padding: 0; background: #f6f8fb; font-family: Arial, Helvetica, sans-serif; color: #1f2937;">
  <div style="max-width: 760px; margin: 0 auto; padding: 32px 24px;">
    <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden;">
      <div style="background: #2563eb; color: #ffffff; padding: 28px 32px;">
        <div style="font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: .08em;">Scheduled Report</div>
        <div style="font-size: 26px; font-weight: bold; margin-top: 10px;">Overdue Stage Orders</div>
        <div style="font-size: 14px; margin-top: 12px; color: #dbeafe;">Generated at {{ $generatedAt }}</div>
      </div>

      <div style="padding: 30px 32px 34px;">
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse: separate; border-spacing: 0; margin-bottom: 28px;">
          <tr>
            <td width="33.33%" style="padding-right: 10px;">
              <div style="padding: 18px 18px 20px; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px;">
                <div style="font-size: 12px; color: #9a3412; font-weight: bold; text-transform: uppercase;">Statuses</div>
                <div style="font-size: 28px; font-weight: bold; color: #7c2d12; margin-top: 8px;">{{ $statusCount }}</div>
              </div>
            </td>
            <td width="33.33%" style="padding-left: 5px; padding-right: 5px;">
              <div style="padding: 18px 18px 20px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px;">
                <div style="font-size: 12px; color: #1d4ed8; font-weight: bold; text-transform: uppercase;">Overdue Orders</div>
                <div style="font-size: 28px; font-weight: bold; color: #1e40af; margin-top: 8px;">{{ $orderCount }}</div>
              </div>
            </td>
            <td width="33.33%" style="padding-left: 10px;">
              <div style="padding: 18px 18px 20px; background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 8px;">
                <div style="font-size: 12px; color: #3730a3; font-weight: bold; text-transform: uppercase;">Amount</div>
                <div style="font-size: 28px; font-weight: bold; color: #312e81; margin-top: 8px;">${{ number_format($amount, 2) }}</div>
              </div>
            </td>
          </tr>
        </table>

        @if ($orderCount === 0)
          <div style="padding: 20px; border: 1px solid #bbf7d0; background: #f0fdf4; color: #166534; border-radius: 8px; font-weight: bold;">
            No overdue orders found.
          </div>
        @else
          <div style="font-size: 15px; line-height: 1.7; margin-bottom: 24px; color: #374151;">
            The attached PDF and Excel include the full order detail grouped by status and seller.
          </div>

          @foreach ($groups as $group)
            <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 18px 20px; margin-bottom: 18px; background: #ffffff;">
              <div style="font-size: 16px; font-weight: bold; color: #111827; margin-bottom: 8px;">{{ $group['status'] }}</div>
              <div style="font-size: 14px; color: #6b7280; margin-bottom: 14px;">
                {{ $group['count'] }} overdue orders | ${{ number_format((float) ($group['amount_total'] ?? 0), 2) }}
              </div>

              <div style="line-height: 2.5;">
                @foreach (($group['seller_groups'] ?? []) as $sellerGroup)
                  <span style="display: inline-block; margin: 0 10px 10px 0; padding: 8px 12px; border-radius: 999px; background: #f3f4f6; color: #374151; font-size: 13px; font-weight: bold;">
                    {{ $sellerGroup['seller_name'] }}: {{ $sellerGroup['count'] }}
                  </span>
                @endforeach
              </div>
            </div>
          @endforeach
        @endif
      </div>
    </div>
  </div>
</div>
