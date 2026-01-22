<table>
    <thead>
        <tr>
            <td colspan="7" style="font-weight: bold; font-size: 16px; text-align: left; background-color: #f0f0f0;">
                Uncollected Payments Report
            </td>
        </tr>
        <tr>
            <td colspan="7" style="font-weight: bold; text-align: left; background-color: #f0f0f0;">
                Biweekly: {{$biweeklyTitle}}
            </td>
        </tr>
        <tr></tr>
        <tr>
            <th width="30">Project Name</th>
            <th width="10">Installer Name</th>
            <th width="15">Owner Name</th>
            <th width="15">Supervisor Name</th>
            <th width="10">% Project</th>
            <th width="30">Payments</th>
            <th width="20">Collected Payment</th>
        </tr>
    </thead>
    <tbody>
        @foreach($biweeklys as $biweekly)
            @php
                $lastPayment = count($biweekly['installation_payments']) > 0
                    ? $biweekly['installation_payments'][count($biweekly['installation_payments']) - 1]
                    : null;
            @endphp
            <tr>
                <td>{{ $biweekly['name'] }}</td>
                <td>{{ $biweekly['installer'] }}</td>
                <td>
                    @foreach ($biweekly['owners'] as $owner)
                        {{ $owner['name'] }} <br/>
                    @endforeach
                </td>
                <td>{{ $biweekly['supervisor'] }}</td>
                <td>
                    @if ($lastPayment)
                        {{ number_format($lastPayment['percentage_payment'], 2, '.', ',') . '%' }}
                    @else
                        N/A
                    @endif
                </td>
                <td>{{ '$' . number_format($biweekly['total_payment_amount'], 2, '.', ',') }}</td>
                <td>
                    {{ collect([
                        $biweekly['partial_payment_installation'] ? 'PARTIAL' : '',
                        $biweekly['final_payment_installation'] ? 'FINAL' : '',
                    ])->filter()->join(' , ') }}
                </td>
            </tr>
        @endforeach
        <tr>
            <td colspan="5" style="font-weight: bold; text-align: right;">Total:</td>
            <td style="font-weight: bold;">
                {{ '=IF(ROW()-1<MATCH("Project Name",A:A,0)+1,0,SUMPRODUCT(--SUBSTITUTE(SUBSTITUTE(INDEX(F:F, MATCH("Project Name",A:A,0)+1):INDEX(F:F, ROW()-1),"$",""),",","")))' }}
            </td>
            <td></td>
        </tr>
        <tr></tr>
        <tr>
            <td colspan="7" style="font-weight: bold; text-align: left; background-color: #f0f0f0;">
                Final Payment Pending
            </td>
        </tr>
        <tr>
            <th width="30">Project Name</th>
            <th width="10">Installer Name</th>
            <th width="15">Owner Name</th>
            <th width="15">Supervisor Name</th>
            <th width="10">% Project</th>
            <th width="30">Payments</th>
            <th width="20">Collected Payment</th>
        </tr>
        @foreach($biweeklys1 as $biweekly)
            @php
                $lastPayment = count($biweekly['installation_payments']) > 0
                    ? $biweekly['installation_payments'][count($biweekly['installation_payments']) - 1]
                    : null;
            @endphp
            <tr>
                <td>{{ $biweekly['name'] }}</td>
                <td>{{ $biweekly['installer'] }}</td>
                <td>
                    @foreach ($biweekly['owners'] as $owner)
                        {{ $owner['name'] }} <br/>
                    @endforeach
                </td>
                <td>{{ $biweekly['supervisor'] }}</td>
                <td>
                    @if ($lastPayment)
                        {{ number_format($lastPayment['percentage_payment'], 2, '.', ',') . '%' }}
                    @else
                        N/A
                    @endif
                </td>
                <td>{{ '$' . number_format($biweekly['total_payment_amount'], 2, '.', ',') }}</td>
                <td>
                    {{ collect([
                        $biweekly['partial_payment_installation'] ? 'PARTIAL' : '',
                        $biweekly['final_payment_installation'] ? 'FINAL' : '',
                    ])->filter()->join(' , ') }}
                </td>
            </tr>
        @endforeach
        <tr>
            <td colspan="5" style="font-weight: bold; text-align: right;">Total:</td>
            <td style="font-weight: bold;">
                {{ '=IF(ROW()-1<MATCH("Final Payment Pending",A:A,0)+2,0,SUMPRODUCT(--SUBSTITUTE(SUBSTITUTE(INDEX(F:F, MATCH("Final Payment Pending",A:A,0)+2):INDEX(F:F, ROW()-1),"$",""),",","")))' }}
            </td>
            <td></td>
        </tr>
    </tbody>
</table>
