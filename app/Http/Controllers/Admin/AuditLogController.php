<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    private const METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function index(Request $request): View
    {
        [$search, $method, $role] = $this->filters($request);

        $logs = AuditLog::filtered($search, $method, $role)
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.audit-logs', [
            'logs' => $logs,
            'search' => $search,
            'method' => $method,
            'role' => $role,
            'methods' => self::METHODS,
            'latestId' => (int) (AuditLog::max('id') ?? 0),
        ]);
    }

    public function export(Request $request): Response
    {
        [$search, $method, $role] = $this->filters($request);

        $rows = [['Time (Asia/Manila)', 'User', 'Role', 'Action', 'Route', 'Method', 'Path', 'Status', 'IP', 'Old values', 'New values']];

        AuditLog::filtered($search, $method, $role)
            ->latest('id')
            ->limit((int) config('ordering.reports.export_row_limit', 5000))
            ->cursor()
            ->each(function (AuditLog $log) use (&$rows) {
                $rows[] = [
                    $log->createdAtInManila()->format('Y-m-d H:i:s'),
                    $log->user_name ?: 'Guest',
                    $log->actor_role ?: 'admin',
                    $log->action,
                    $log->route_name,
                    $log->method,
                    $log->path,
                    $log->status_code,
                    $log->ip_address,
                    $log->old_values ? json_encode($log->old_values, JSON_UNESCAPED_SLASHES) : '',
                    $log->new_values ? json_encode($log->new_values, JSON_UNESCAPED_SLASHES) : '',
                ];
            });

        $handle = fopen('php://temp', 'r+');

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $content = (string) stream_get_contents($handle);
        fclose($handle);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="audit-logs-'.now()->format('Ymd-His').'.csv"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        [$search, $method, $role] = $this->filters($request);
        $after = max(0, $request->integer('after'));

        $logs = AuditLog::filtered($search, $method, $role)
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit(50)
            ->get();

        return response()->json([
            'logs' => $logs->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'user_name' => $log->user_name,
                'actor_role' => $log->actor_role ?: 'admin',
                'action' => $log->action,
                'route_name' => $log->route_name,
                'method' => $log->method,
                'path' => $log->path,
                'status_code' => $log->status_code,
                'ip_address' => $log->ip_address,
                'created_at_label' => $log->createdAtInManila()->format('M d, Y · h:i:s A'),
                'created_at_human' => $log->createdAtInManila()->diffForHumans(),
                'created_at_iso' => $log->createdAtInManila()->toIso8601String(),
            ])->values(),
            'last_id' => (int) ($logs->last()?->id ?? $after),
        ]);
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function filters(Request $request): array
    {
        $search = $request->input('search');
        $method = $request->input('method');
        $role = $request->input('role');

        $search = is_string($search) ? trim($search) : '';
        $method = is_string($method) ? strtoupper(trim($method)) : '';
        $role = is_string($role) ? strtolower(trim($role)) : '';

        if (! in_array($method, self::METHODS, true)) {
            $method = '';
        }

        if (! in_array($role, ['admin', 'customer'], true)) {
            $role = '';
        }

        return [$search, $method, $role];
    }
}
