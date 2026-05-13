<?php

namespace App\Http\Controllers;

use App\Models\BackupSchedule;
use App\Models\DatabaseConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            'schedules.*.frequency_hours'  => ['required', 'integer', 'in:1,4,12,24,48,168,200'],
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

    public function updateSchedules(Request $request, DatabaseConnection $connection): RedirectResponse
    {
        $data = $request->validate([
            'schedules' => ['required', 'array', 'min:1'],
            'schedules.*.id' => [
                'nullable',
                'integer',
                Rule::exists('backup_schedules', 'id')->where(
                    fn ($query) => $query->where('database_connection_id', $connection->id)
                ),
            ],
            'schedules.*.frequency_hours'  => ['required', 'integer', 'in:1,4,12,24,48,168,200'],
            'schedules.*.retention_amount' => ['required', 'integer', 'min:1'],
            'schedules.*.retention_unit'   => ['required', 'string', 'in:hours,days,weeks,months,years'],
        ]);

        DB::transaction(function () use ($connection, $data): void {
            $existingIds = $connection->schedules()->pluck('id')->all();
            $submittedIds = collect($data['schedules'])
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            $idsToDelete = array_diff($existingIds, $submittedIds);
            if (! empty($idsToDelete)) {
                BackupSchedule::whereIn('id', $idsToDelete)->delete();
            }

            foreach ($data['schedules'] as $scheduleData) {
                $schedule = null;

                if (! empty($scheduleData['id'])) {
                    $schedule = BackupSchedule::where('database_connection_id', $connection->id)
                        ->find($scheduleData['id']);
                }

                if ($schedule) {
                    $schedule->update([
                        'frequency_hours'  => $scheduleData['frequency_hours'],
                        'retention_amount' => $scheduleData['retention_amount'],
                        'retention_unit'   => $scheduleData['retention_unit'],
                    ]);
                } else {
                    BackupSchedule::create([
                        'database_connection_id' => $connection->id,
                        'frequency_hours'        => $scheduleData['frequency_hours'],
                        'retention_amount'       => $scheduleData['retention_amount'],
                        'retention_unit'         => $scheduleData['retention_unit'],
                    ]);
                }
            }
        });

        return redirect()->route('dashboard')->with('success', "Schedules for \"{$connection->alias}\" updated successfully.");
    }

    public function destroy(DatabaseConnection $connection): RedirectResponse
    {
        $connection->delete();

        return redirect()->route('dashboard')->with('success', "Connection \"{$connection->alias}\" deleted.");
    }
}
