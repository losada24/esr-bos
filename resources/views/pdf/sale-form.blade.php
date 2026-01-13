<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link rel="stylesheet" href="{{ base_path('resources/css/sell-form-pdf-styles.css') }}">
</head>
<body>
@php
    $clientAddressModel = isset($clientAddress) ? $clientAddress : null;
    $orderModel = $order ?? ($clientAddressModel?->order ?? null);
    $clientModel = $orderModel->client ?? ($clientAddressModel?->client ?? null);
    $saleForm = $orderModel->saleForm ?? null;
    $ownersCollection = $orderModel && isset($orderModel->owners)
        ? collect($orderModel->owners)
        : collect();
    $ownerNames = $ownersCollection->pluck('name')->filter()->implode(', ');
    $createdBy = $ownerNames !== ''
        ? $ownerNames
        : ($orderModel?->user->name ?? ($clientModel?->user->name ?? ''));
    $scheduleAppointment = '';
    if ($orderModel && $orderModel->schedule_appointment) {
        $scheduleAppointment = \Carbon\Carbon::parse($orderModel->schedule_appointment)
            ->locale('en')
            ->translatedFormat('l, F j, Y h:i A');
    } elseif (!empty($clientAddressModel?->appointment_date)) {
        $scheduleAppointment = \Carbon\Carbon::parse($clientAddressModel->appointment_date)
            ->locale('en')
            ->translatedFormat('l, F j, Y h:i A');
    }
    $notes = $orderModel->description ?? ($clientAddressModel?->notes ?? '');

    $typeOfWorkFlags = [
        'Sale' => $saleForm?->sale ?? false,
        'Installation' => $saleForm?->installation ?? false,
        'Permit' => $saleForm?->permit ?? false,
        'Financing' => $saleForm?->financing ?? false,
        'HOA' => $saleForm?->hoa ?? false,
    ];
    $languageDisplay = $saleForm?->language
        ? ucwords(strtolower($saleForm->language))
        : '';
    /*$projectOptionFlags = [
        'Screens' => $saleForm?->screen ?? false,
        'Door design' => $saleForm?->design ?? false,
        'Mountins' => $saleForm?->mountin ?? false,
        'Bars' => $saleForm?->bar ?? false,
        'Shutters holes' => $saleForm?->shutter_hole ?? false,
        'Floor Cutting' => $saleForm?->floor_cutting ?? false,
        'Interior Finish' => $saleForm?->interior_finish ?? false,
    ];*/
@endphp
  <div class="page sale-form">
    @if($clientModel?->vip_clients)
      <div class="vip-badge">VIP</div>
    @endif
    <table class="header-table">
      <tr>
        <td class="logo-cell">
          <img src="{{ base_path('resources/assets/images/logo-reylos.jpg') }}" alt="Reylos Glass">
        </td>
        <td class="info-cell">
          <div class="customer-summary">
            <div class="customer-summary__title">Customer Information</div>
            <div class="customer-summary__grid">
              <div class="customer-summary__item customer-summary__item--name">{{ $clientModel->name ?? '' }}</div>
              <div class="customer-summary__row">
                <div class="customer-summary__col customer-summary__col--details">
                  <div class="customer-summary__item customer-summary__item--address">
                    <span class="customer-summary__value customer-summary__value--address">{{ $orderModel->job_address ?? ($clientAddressModel?->address ?? '') }}</span>
                  </div>
                  <div class="customer-summary__item customer-summary__item--left">{{ trim(($orderModel->city ?? '') . ' ' . ($orderModel->job_state ?? '') . ' ' . ($orderModel->job_zip ?? '')) }}</div>
                </div>
                <div class="customer-summary__col customer-summary__col--contact">
                  <div class="customer-summary__item">
                    <span class="customer-summary__value customer-summary__value--phone">{{ $clientModel->phone ?? '' }}</span>
                  </div>
                  <div class="customer-summary__item">
                    <span class="customer-summary__value customer-summary__value--email">{{ $clientModel->email ?? '' }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </td>
      </tr>
    </table>

    <table class="consultant-table">
      <tr>
        <td>
          <span class="consultant-table__label">Consultant:</span>
          <span class="consultant-table__value">{{ $createdBy }}</span>
          <span class="consultant-table__label consultant-table__label--separator">APPT:</span>
          <span class="consultant-table__value">{{ $scheduleAppointment }}</span>
        </td>
      </tr>
    </table>

    <div class="section section-type">
      <div class="section-title-inline">
        <span class="section-title section-title--inline">Type of Work:</span>
        @if(count($typeOfWorkFlags) > 0)
          <span class="type-of-work-inline">
            @foreach($typeOfWorkFlags as $label => $flag)
              <span class="type-of-work-inline__item">
                <span class="checkbox checkbox--inline">{!! $flag ? '&#10003;' : '&nbsp;' !!}</span>
                <span class="checkbox-text">{{ $label }}</span>
              </span>
            @endforeach
            <span class="type-of-work-inline__language">
              Language:
              <span class="type-of-work-inline__language-value">{{ $languageDisplay !== '' ? $languageDisplay : 'N/A' }}</span>
            </span>
          </span>
        @else
          <span class="type-of-work-inline__values">N/A</span>
        @endif
      </div>
     {{--<table class="inline-table">
        <tr>
          <td>
            <div class="inline-pair">
              <span class="label">Floor:</span>
              <span class="value-line">{{ $saleForm->floor ?? '' }}</span>
            </div>
          </td>
          <td>
            <div class="inline-pair">
              <span class="label">Reference:</span>
              <span class="value-line">{{ $clientModel->source ? $clientModel->source : '' }}</span>
            </div>
          </td>
        </tr>
      </table>
      --}}
    </div>

    <div class="section">
      <div class="section-title section-title--sm">Project Specifications</div>
      <table class="inline-table project-spec">
        <tr>
          <td>
            <div class="inline-pair">
              <span class="label">Frame Color:</span>
              <span class="value-inline">{{ $saleForm->frame_color ?? '' }}</span>
            </div>
          </td>
          <td>
            <div class="inline-pair">
              <span class="label">Glass Color:</span>
              <span class="value-inline">{{ $saleForm->glass_color ?? '' }}</span>
            </div>
          </td>
        </tr>
       {{-- <tr>
          <td>
            <div class="inline-pair">
              <span class="label">Glass Type:</span>
              <span class="value-line">{{ $saleForm->glass_type ?? '' }}</span>
            </div>
          </td>
          <td>
            <div class="inline-pair">
              <span class="label">Glass Coating:</span>
              <span class="value-line">{{ $saleForm->glass_coating ?? '' }}</span>
            </div>
          </td>
        </tr>
      --}}
      </table>
      <table class="inline-table">
        <tr>
          <td>
            <div class="inline-pair">
              <span class="label">Doors Quantity:</span>
              <span class="value-inline">{{ $saleForm->door_quantity ?? '' }}</span>
            </div>
          </td>
          <td>
            <div class="inline-pair">
              <span class="label">Windows Quantity:</span>
              <span class="value-inline">{{ $saleForm->window_quantity ?? '' }}</span>
            </div>
          </td>
        </tr>
      </table>
      <table class="checkbox-table project-options">
        <tr>
        {{--
          @foreach($projectOptionFlags as $label => $flag)
            <td>
              <span class="checkbox">{!! $flag ? '&#10003;' : '&nbsp;' !!}</span>
              <span class="checkbox-text">{{ $label }}</span>
            </td>
          @endforeach
        --}}
        </tr>
      </table>
    </div>

    <div class="drawing-area">
      @if(!empty($notes))
        <div class="notes-content">{{ $notes }}</div>
      @endif
    </div>
  </div>
</body>
</html>
