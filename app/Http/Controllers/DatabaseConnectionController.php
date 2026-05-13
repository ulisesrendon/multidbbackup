<?php

namespace App\Http\Controllers;

use App\Models\BackupSchedule;
use App\Models\DatabaseConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DatabaseConnectionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'alias'             => ['required', 'string', 'max:100', 'unique:database_connections,alias'],
            'host'              => ['required', 'string', 'max:255'],
            'port'              => ['required', 'integer', 'min:1', 'max:65535'],
            'database_name'     => ['required', 'string', 'max:255'],
            'username'          => ['required', 'string', 'max:255'],
            'password'          => ['required', 'string'],
            'schedules'         => ['required', 'array', 'min:1'],
            'schedules.*.frequency_hours'  => ['required', 'integer', 'in:4,12,24'],
            'schedules.*.retention_amount' => ['required', 'integer', 'min:1'],
            'schedules.*.retention_unit'   => ['required', 'string', 'in:hours,days,weeks,months,years'],
        ]);

        $connection = DatabaseConnection::create([
            'alias'                   => $data['alias'],
            'host_encrypted'          => $data['host'],
            'port_encrypted'          => (string) $data['port'],
            'database_name_encrypted' => $data['database_name'],
            'username_encrypted'      => $data['username'],
            'password_encrypted'      => $data['password'],
            'status'                  => 'active',
        ]);

        foreach ($data['schedules'] as $scheduleData) {
            BackupSchedule::create([
                'database_connection_id' => $connection->id,
                'frequency_hours'        => $scheduleData['frequency_hours'],
                'retention_amount'       => $scheduleData['retention_amount'],
                'retention_unit'         => $scheduleData['retention_unit'],
            ]);
        }

        return redirect()->route('dashboard')->with('success', "Connection \"{$connection->alias}\" added successfully.");
    }

    public function toggle(DatabaseConnection $connection): JsonResponse
    {
        $connection->status = $connection->status === 'active' ? 'paused' : 'active';
        $connection->save();

        return response()->json(['status' => $connection->status]);
    }

    public function destroy(DatabaseConnection $connection): RedirectResponse
    {
        $connection->delete();

        return redirect()->route('dashboard')->with('success', "Connection \"{$connection->alias}\" deleted.");
    }
}
