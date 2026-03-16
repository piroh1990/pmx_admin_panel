<?php
// Prevent direct access
if (count(get_included_files()) === 1) {
    http_response_code(403);
    die('Direct access not permitted');
}

require_once __DIR__ . '/../config/config.php';

function proxmoxRequest(string $endpoint, string $method = 'POST')
{
    $url = PVE_HOST . $endpoint;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => VERIFY_SSL,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER     => [
            'Authorization: PVEAPIToken=' . PVE_TOKEN_ID . '=' . PVE_TOKEN_SECRET
        ],
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        throw new Exception(curl_error($ch));
    }

    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code >= 400) {
        throw new Exception("Proxmox API error ($code): $response");
    }

    return json_decode($response, true);
}

function vmAction(int $vmid, string $action)
{
    $endpoint = "/api2/json/nodes/" . PVE_NODE . "/qemu/$vmid/status/$action";
    return proxmoxRequest($endpoint, 'POST');
}

/**
 * Get the current status of a VM
 * @param int $vmid VM ID
 * @return array Status information including status (running/stopped), cpu, memory, etc.
 */
function getVmStatus(int $vmid)
{
    $endpoint = "/api2/json/nodes/" . PVE_NODE . "/qemu/$vmid/status/current";
    return proxmoxRequest($endpoint, 'GET');
}

/**
 * Get status for multiple VMs
 * @param array $vmids Array of VM IDs
 * @return array Associative array with vmid as key and status data as value
 */
function getMultipleVmStatus(array $vmids)
{
    $statuses = [];
    foreach ($vmids as $vmid) {
        try {
            $result = getVmStatus($vmid);
            $statuses[$vmid] = $result['data'] ?? null;
        } catch (Exception $e) {
            $safeError = str_replace(array("\r", "\n", "%0d", "%0a"), ' ', $e->getMessage());
            $safeVmid = (int)$vmid;
            error_log("VM Status Error ($safeVmid): " . $safeError);
            $statuses[$vmid] = [
                'status' => 'error',
                'error' => 'Failed to retrieve status'
            ];
        }
    }
    return $statuses;
}