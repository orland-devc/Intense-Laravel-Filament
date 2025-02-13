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

        .metadata {
            color: #718096;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .summary {
            margin-bottom: 20px;
            padding: 15px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        th, td {
            border: 1px solid #e2e8f0;
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #f7fafc;
            font-weight: 600;
            color: #4a5568;
        }

        tr:nth-child(even) {
            background-color: #f8fafc;
        }

        tr:hover {
            background-color: #f1f5f9;
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

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 0.8em;
            color: #718096;
        }

        @page {
            margin: 50px 25px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Posts Export Report</h1>
        <div class="metadata">
            <p>Generated on: {{ now()->format('Y-m-d H:i:s') }}</p>
            <p>Total Posts: {{ $items->count() }}</p>
        </div>
    </div>

    <div class="summary">
        <p>Status Distribution:</p>
        <ul>
            @php
                $statusCounts = $items->groupBy('status')->map->count();
            @endphp
            @foreach($statusCounts as $status => $count)
                <li>{{ ucfirst($status) }}: {{ $count }}</li>
            @endforeach
        </ul>
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
                            @elseif(in_array($key, ['created_at', 'updated_at']) && $item[$key])
                                {{ \Carbon\Carbon::parse($item[$key])->format('Y-m-d H:i') }}
                            @else
                                {{ Str::limit($item[$key], 100) }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>This report is generated automatically. Please contact the administrator for any questions.</p>
    </div>
</body>
</html>