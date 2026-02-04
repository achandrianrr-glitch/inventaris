@extends('reports.layout', ['title' => 'Inventaris'])

@section('content')
    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Merek</th>
                <th>Lokasi</th>
                <th>Stok</th>
                <th>Tersedia</th>
                <th>Dipinjam</th>
                <th>Rusak</th>
                <th>Kondisi</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $i)
                <tr>
                    <td>{{ $i->code }}</td>
                    <td>{{ $i->name }}</td>
                    <td>{{ $i->category?->name ?? '-' }}</td>
                    <td>{{ $i->brand?->name ?? '-' }}</td>
                    <td>{{ $i->location?->name ?? '-' }}</td>
                    <td>{{ (int) $i->stock_total }}</td>
                    <td>{{ (int) $i->stock_available }}</td>
                    <td>{{ (int) $i->stock_borrowed }}</td>
                    <td>{{ (int) $i->stock_damaged }}</td>
                    <td>{{ $i->condition }}</td>
                    <td>{{ $i->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
