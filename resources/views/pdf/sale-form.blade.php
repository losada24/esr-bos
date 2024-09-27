<html>
<header>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <link rel="stylesheet" href="{{ base_path('resources/css/sell-form-pdf-styles.css') }}">
  <style>
    .page-break {
      page-break-after: always;
    }
  </style>
</header>
<body>
  <div class="content">
    <header class="clearfix">
      <table class="table-container">
        <tr>
          <td class="column-50">
            <div id="logo">
              <img src="{{ base_path('resources/assets/images/logo-reylos.jpg') }}">
            </div>
          </td>
          <td class="column-50">
            <table class="table-container">
              <tr>
                <td class="column-35 text-right pr-10">
                  <span class="strong-labels">Date:</span>
                </td>
                <td class="bottom-line column-65">{{ date('m/d/Y')}}</td>
              </tr>
              <tr>
                <td class="column-35 text-right pr-10">
                  <span class="strong-labels">Customer Name:</span>
                </td>
                <td class="bottom-line column-65">{{ $clientAddress->client->name }}</td>
              </tr>
              <tr>
                <td class="column-35 text-right pr-10">
                  <span class="strong-labels">Address:</span>
                </td>
                <td class="bottom-line column-65">{{ $clientAddress->address }}</td>
              </tr>
              <tr>
                <td class="column-35 text-right pr-10">
                  <span class="strong-labels">Phone #:</span>
                </td>
                <td class="bottom-line column-65">{{ $clientAddress->client->phone }}</td>
              </tr>
              <tr>
                <td class="column-35 text-right pr-10">
                  <span class="strong-labels">E-mail:</span>
                </td>
                <td class="bottom-line column-65">{{ $clientAddress->client->email }}</td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </header>
    <table class="table-container">
      <tr>
        <td colspan="6">
          <span class="strong-labels">Type of Work and / or Service</span>
        </td>
      </tr>
      <tr>
        <td class="pt-10">
          <span class="regular-labels pr-10">Sale</span><span class="checkbox"></span>
        </td>
        <td class="pt-10">
          <span class="regular-labels pr-10">Installation</span><span class="checkbox"></span>
        </td>
        <td class="pt-10">
          <span class="regular-labels pr-10">Permit</span><span class="checkbox"></span>
        </td>
        <td class="pt-10">
          <span class="regular-labels pr-10">Replacement</span><span class="checkbox"></span>
        </td>
        <td class="pt-10">
          <span class="regular-labels pr-10">New Construction</span><span class="checkbox"></span>
        </td>
        <td class="pt-10">
          <span class="regular-labels pr-10">Financing</span><span class="checkbox"></span>
        </td>
      </tr>
      <tr>
        <td class="pt-10 text-right">
          <span class="regular-labels pr-10">Floor:</span>
        </td>
        <td colspan="2" class="bottom-line pt-10"></td>
        <td class="pt-10 text-right">
          <span class="regular-labels pr-10">Reference:</span>
        </td>
        <td colspan="2" class="bottom-line pt-10"></td>
      </tr>
    </table>
    <table class="table-container mt-10">
      <tr>
        <td colspan="8">
          <span class="strong-labels">Project Specifications</span>
        </td>
      </tr>
      <tr>
        <td class="pt-10 wp-14">
          <span class="regular-labels pr-10">Frame Color:</span>
        </td> 
        <td class="pt-10 bottom-line wp-10">&nbsp;</td>
        <td class="pt-10 wp-12 text-right">
          <span class="regular-labels pr-14">Glass Color:</span>
        </td> 
        <td class="pt-10 bottom-line wp-10">&nbsp;</td>
        <td class="pt-10 wp-12 text-right">
          <span class="regular-labels pr-14">Glass Type:</span>
        </td> 
        <td class="pt-10 bottom-line wp-10">&nbsp;</td>
        <td class="pt-10 wp-12 text-right">
          <span class="regular-labels pr-14">Glass Coating:</span>
        </td> 
        <td class="pt-10 bottom-line wp-10">&nbsp;</td>
      </tr>
    </table>
    <table class="table-container mt-10">
      <tr>
        <td class="pt-10 column-18">
          <span class="regular-labels pr-10">Doors Quantity:</span>
        </td>
        <td class="pt-10 bottom-line column-6">&nbsp;</td>
        <td class="pt-10 column-18">
          <span class="regular-labels pr-10">Windows Quantity:</span>
        </td>
        <td class="pt-10 bottom-line column-6">&nbsp;</td>
        <td class="pt-10 column-48">
          <table class="table-container">
            <tr>
              <td>
                <span class="regular-labels pr-10">Screens</span><span class="checkbox"></span>
              </td>
              <td>
                <span class="regular-labels pr-10">Door Design</span><span class="checkbox"></span>
              </td>
              <td>
                <span class="regular-labels pr-10">Mountins</span><span class="checkbox"></span>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
    <table class="table-container mt-10">
      <tr>
        <td>
          <span class="regular-labels pr-10">Bars</span><span class="checkbox"></span>
        </td>
        <td>
          <span class="regular-labels pr-10">Shutters holes</span><span class="checkbox"></span>
        </td>
        <td>
          <span class="regular-labels pr-10">Floor Cutting</span><span class="checkbox"></span>
        </td>
        <td>
          <span class="regular-labels pr-10">Interior Finish</span><span class="checkbox"></span>
        </td>
        <td>
          <table class="table-container">
            <tr>
              <td class="column-35">
                <span class="regular-labels pr-10">Other:</span>
              </td>
              <td class="bottom-line column-65">&nbsp;</td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
    <footer class="mt-10"></footer>
    @if (!empty($clientAddress->notes))
      <table class="table-container">
        <tr>
          <td>
            <span class="strong-labels">Notes</span>
            <p>{{ $clientAddress->notes }}</p>
          </td>  
        </tr>    
      </table>
    @endif
    @if (!empty($clientAddress->appointment_date))
      <table class="table-container">
        <tr>
          <td>
            <span class="strong-labels">Appointment Date</span>
            <p>{{ $clientAddress->appointment_date }}</p>
          </td>  
        </tr>    
      </table>
    @endif
  </div>
  </body>
</html>
