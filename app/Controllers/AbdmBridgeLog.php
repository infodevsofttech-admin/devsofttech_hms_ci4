<?php

namespace App\Controllers;

class AbdmBridgeLog extends BaseController
{
    // Route filter (permission:abdm.*) handles auth — no secondary check needed.

    public function index()
    {
        return view('abdm/bridge_log');
    }

    /**
     * AJAX: paginated log rows with filters.
     * GET AbdmBridgeLog/list
     */
    public function list()
    {
        $db      = \Config\Database::connect();
        $builder = $db->table('abdm_api_logs');

        $channel   = trim((string) $this->request->getGet('channel'));
        $status    = trim((string) $this->request->getGet('status'));
        $search    = trim((string) $this->request->getGet('search'));
        $dateFrom  = trim((string) $this->request->getGet('date_from'));
        $dateTo    = trim((string) $this->request->getGet('date_to'));
        $page      = max(1, (int) $this->request->getGet('page'));
        $perPage   = 50;

        if ($channel !== '') {
            $builder->where('channel', $channel);
        }
        if ($status !== '') {
            $builder->where('status', $status);
        }
        if ($search !== '') {
            $builder->groupStart()
                ->like('event_type', $search)
                ->orLike('entity_id', $search)
                ->orLike('endpoint', $search)
                ->groupEnd();
        }
        if ($dateFrom !== '') {
            $builder->where('created_at >=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo !== '') {
            $builder->where('created_at <=', $dateTo . ' 23:59:59');
        }

        $total = (clone $builder)->countAllResults(false);

        $rows = $builder
            ->select('id, channel, event_type, endpoint, http_method, entity_type, entity_id, response_code, status, error_message, created_at')
            ->orderBy('id', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'ok'       => 1,
            'rows'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => (int) ceil($total / $perPage),
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    /**
     * AJAX: full request + response JSON for one log entry.
     * GET AbdmBridgeLog/detail/(:num)
     */
    public function detail(int $id)
    {
        $db  = \Config\Database::connect();
        $row = $db->table('abdm_api_logs')
            ->select('id, channel, event_type, endpoint, http_method, entity_type, entity_id, request_json, response_code, response_json, status, error_message, created_at')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if ($row === null) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => 0, 'error_text' => 'Log entry not found']);
        }

        // Pretty-print JSON strings for the UI
        foreach (['request_json', 'response_json'] as $field) {
            if ($row[$field] !== null) {
                $decoded = json_decode($row[$field], true);
                $row[$field] = is_array($decoded)
                    ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : $row[$field];
            }
        }

        return $this->response->setJSON(['ok' => 1, 'row' => $row]);
    }
}
