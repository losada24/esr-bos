@php
  $statusCount = (int) ($totals['configured_statuses'] ?? $totals['statuses'] ?? 0);
  $overdueOrders = (int) ($totals['overdue_orders'] ?? 0);
  $amount = (float) ($totals['amount'] ?? 0);
  $visibleGroups = collect($groups ?? [])
    ->filter(fn ($group) => (int) ($group['overdue_count'] ?? $group['count'] ?? 0) > 0)
    ->values();
  $appName = config('app.name');
@endphp

<div style="margin:0;padding:0;background:#f4f6fa;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;background:#f4f6fa;">
    <tr>
      <td align="left" style="padding:0;">
        <table role="presentation" width="760" cellspacing="0" cellpadding="0" style="width:760px;max-width:100%;border-collapse:collapse;background:#ffffff;">
          <tr>
            <td style="background:#2f63df;padding:28px 32px;color:#ffffff;">
              <div style="font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;margin-bottom:16px;">
                {{ $appName }} | Scheduled Report
              </div>
              <div style="font-size:28px;line-height:34px;font-weight:700;margin-bottom:18px;">
                Overdue Stage Orders
              </div>
              <div style="font-size:14px;line-height:20px;">
                Generated at {{ $generatedAt }}
              </div>
            </td>
          </tr>

          <tr>
            <td style="padding:30px 32px 18px;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate;border-spacing:0 0;">
                <tr>
                  <td width="31%" style="border:1px solid #fdba74;background:#fff7ed;border-radius:7px;padding:20px 18px;">
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#9a3412;margin-bottom:14px;">
                      Statuses
                    </div>
                    <div style="font-size:28px;font-weight:700;color:#7c2d12;">
                      {{ $statusCount }}
                    </div>
                  </td>
                  <td width="3%"></td>
                  <td width="31%" style="border:1px solid #bfdbfe;background:#eff6ff;border-radius:7px;padding:20px 18px;">
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#1d4ed8;margin-bottom:14px;">
                      Overdue Orders
                    </div>
                    <div style="font-size:28px;font-weight:700;color:#1e40af;">
                      {{ number_format($overdueOrders) }}
                    </div>
                  </td>
                  <td width="3%"></td>
                  <td width="32%" style="border:1px solid #c7d2fe;background:#eef2ff;border-radius:7px;padding:20px 18px;">
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#4338ca;margin-bottom:14px;">
                      Amount
                    </div>
                    <div style="font-size:28px;font-weight:700;color:#312e81;">
                      ${{ number_format($amount, 2) }}
                    </div>
                  </td>
                </tr>
              </table>

              <div style="font-size:16px;line-height:24px;color:#374151;margin:30px 0 26px;">
                The attached PDF and Excel include the full order detail grouped by status and seller.
              </div>

              @forelse ($visibleGroups as $group)
                @php
                  $sellerGroups = collect($group['seller_groups'] ?? [])
                    ->filter(fn ($sellerGroup) => (int) ($sellerGroup['count'] ?? 0) > 0)
                    ->values();
                @endphp

                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate;border-spacing:0;margin-bottom:18px;border:1px solid #e5e7eb;border-radius:7px;">
                  <tr>
                    <td style="padding:20px;">
                      <div style="font-size:16px;line-height:22px;font-weight:700;color:#111827;text-transform:uppercase;margin-bottom:12px;">
                        {{ $group['status'] ?? 'Status' }}
                      </div>
                      <div style="font-size:14px;line-height:20px;color:#667085;margin-bottom:18px;">
                        {{ number_format((int) ($group['overdue_count'] ?? $group['count'] ?? 0)) }} overdue orders | ${{ number_format((float) ($group['amount'] ?? 0), 2) }}
                      </div>

                      @foreach ($sellerGroups as $sellerGroup)
                        <span style="display:inline-block;background:#f3f4f6;border-radius:999px;padding:13px 14px;margin:0 8px 8px 0;font-size:13px;line-height:16px;font-weight:700;color:#374151;">
                          {{ $sellerGroup['label'] ?? 'Seller' }}: {{ number_format((int) ($sellerGroup['count'] ?? 0)) }}
                        </span>
                      @endforeach
                    </td>
                  </tr>
                </table>
              @empty
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate;border-spacing:0;border:1px solid #e5e7eb;border-radius:7px;">
                  <tr>
                    <td style="padding:20px;font-size:14px;line-height:20px;color:#667085;">
                      No overdue orders were found for this scheduled report.
                    </td>
                  </tr>
                </table>
              @endforelse
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</div>
