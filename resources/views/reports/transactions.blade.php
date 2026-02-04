@extends('reports.layout', ['title' => 'Transaksi'])

@section('content')
    <div style="font-weight:700; margin-bottom:6px;">Transaksi Masuk</div>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Kode</th>
                <th>Barang</th>
                <th>Dari</th>
                <th>Kepada</th>
                <th>Qty</th>
                <th>Admin</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($in as $t)
                <tr>
                    <td>{{ $t->transaction_date }}</td>
                    <td>{{ $t->code }}</td>
                    <td>{{ $t->item?->code ?? '-' }} — {{ $t->item?->name ?? '-' }}</td>
                    <td>{{ $t->from_location ?? '-' }}</td>
                    <td>{{ $t->to_location ?? '-' }}</td>
                    <td>{{ (int) $t->qty }}</td>
                    <td>{{ $t->admin?->name ?? '-' }}</td>
                    <td>{{ $t->notes ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="height:14px;"></div>

    <div style="font-weight:700; margin-bottom:6px;">Transaksi Keluar</div>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Kode</th>
                <th>Barang</th>
                <th>Dari</th>
                <th>Kepada</th>
                <th>Qty</th>
                <th>Admin</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($out as $t)
                <tr>
                    <td>{{ $t->transaction_date }}</td>
                    <td>{{ $t->code }}</td>
                    <td>{{ $t->item?->code ?? '-' }} — {{ $t->item?->name ?? '-' }}</td>
                    <td>{{ $t->from_location ?? '-' }}</td>
                    <td>{{ $t->to_location ?? '-' }}</td>
                    <td>{{ (int) $t->qty }}</td>
                    <td>{{ $t->admin?->name ?? '-' }}</td>
                    <td>{{ $t->notes ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
