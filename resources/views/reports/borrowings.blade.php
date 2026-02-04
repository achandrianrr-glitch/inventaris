@extends('reports.layout', ['title' => 'Peminjaman'])

@section('content')
    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Peminjam</th>
                <th>Tipe</th>
                <th>Barang</th>
                <th>Qty</th>
                <th>Jenis</th>
                <th>Tgl Pinjam</th>
                <th>Jatuh Tempo</th>
                <th>Kembali</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($borrowings as $b)
                <tr>
                    <td>{{ $b->code }}</td>
                    <td>{{ $b->borrower?->name ?? '-' }}</td>
                    <td>{{ $b->borrower?->type ?? '-' }}</td>
                    <td>{{ $b->item?->code ?? '-' }} — {{ $b->item?->name ?? '-' }}</td>
                    <td>{{ (int) $b->qty }}</td>
                    <td>{{ $b->borrow_type }}</td>
                    <td>{{ $b->borrow_date }}</td>
                    <td>{{ $b->return_due }}</td>
                    <td>{{ $b->return_date ?? '' }}</td>
                    <td>{{ $b->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
