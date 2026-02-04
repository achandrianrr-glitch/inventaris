@extends('reports.layout', ['title' => 'Kerusakan'])

@section('content')
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Kode</th>
                <th>Barang</th>
                <th>Level</th>
                <th>Status</th>
                <th>Keluhan</th>
                <th>Solusi</th>
                <th>Selesai</th>
                <th>Admin</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($damages as $d)
                <tr>
                    <td>{{ $d->reported_date }}</td>
                    <td>{{ $d->code }}</td>
                    <td>{{ $d->item?->code ?? '-' }} — {{ $d->item?->name ?? '-' }}</td>
                    <td>{{ $d->damage_level }}</td>
                    <td>{{ $d->status }}</td>
                    <td>{{ $d->description }}</td>
                    <td>{{ $d->solution ?? '' }}</td>
                    <td>{{ $d->completion_date ?? '' }}</td>
                    <td>{{ $d->admin?->name ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
