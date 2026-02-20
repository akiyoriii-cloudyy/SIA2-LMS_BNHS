@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>Database Tables (System Map)</h2>
        <p>This table shows how each database table supports a specific LMS function.</p>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Database Table</th>
                        <th>Purpose</th>
                        <th>Used In</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tables as $row)
                        <tr>
                            <td><strong>{{ $row['table'] }}</strong></td>
                            <td>{{ $row['purpose'] }}</td>
                            <td>{{ $row['used_in'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

