<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Inventaris Barang</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
        }

        h2 {
            margin: 0 0 6px 0;
        }

        .muted {
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
            vertical-align: top;
        }

        th {
            background: #f3f5f7;
        }
    </style>
</head>

<body>
    <h2>Laporan Inventaris Barang</h2>
    <div class="muted">Dicetak: {{ now()->format('d-m-Y H:i') }}</div>
    <br>

    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Merek</th>
                <th>Lokasi</th>
                <th>Stok</th>
                <th>Kondisi</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $it)
                <tr>
                    <td>{{ $it->code }}</td>
                    <td>{{ $it->name }}</td>
                    <td>{{ $it->category?->name }}</td>
                    <td>{{ $it->brand?->name }}</td>
                    <td>{{ $it->location?->name }}</td>
                    <td>
                        T: {{ $it->stock_total }}<br>
                        A: {{ $it->stock_available }}<br>
                        B: {{ $it->stock_borrowed }}<br>
                        D: {{ $it->stock_damaged }}
                    </td>
                    <td>{{ $it->condition }}</td>
                    <td>{{ $it->status }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
