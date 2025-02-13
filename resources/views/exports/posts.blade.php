<!DOCTYPE html>
<html>
<head>
    <title>Posts Export Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .header h1 {
            color: #2d3748;
            margin: 0 0 10px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
            font-weight: 600;
            color: #4a5568;
        }

        tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .status-published {
            background-color: #def7ec;
            color: #046c4e;
        }

        .status-draft {
            background-color: #e1effe;
            color: #1e429f;
        }

        @page {
            margin: 50px 25px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Posts Export Report</h1>
        <p>Generated on: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                @foreach($columns as $label)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    @foreach(array_keys($columns) as $key)
                        <td>
                            @if($key === 'status')
                                <span class="status-badge status-{{ $item[$key] }}">
                                    {{ ucfirst($item[$key]) }}
                                </span>
                            @elseif(in_array($key, ['created_at', 'updated_at']) && isset($item[$key]))
                                {{ \Carbon\Carbon::parse($item[$key])->format('Y-m-d H:i:s') }}
                            @else
                                {{ $item[$key] ?? '' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>