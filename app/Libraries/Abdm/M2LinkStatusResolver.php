<?php

namespace App\Libraries\Abdm;

final class M2LinkStatusResolver
{
    /**
     * @param array<string, mixed> $payload
     * @return array{request_id:string,abha_id:string,care_context_reference:string,linked_at:string,status:string}
     */
    public static function parse(array $payload, string $headerRequestId = ''): array
    {
        return [
            'request_id' => trim((string) ($payload['request_id'] ?? $payload['requestId'] ?? $headerRequestId)),
            'abha_id' => trim((string) ($payload['abha_id'] ?? $payload['abha_address'] ?? '')),
            'care_context_reference' => trim((string) ($payload['care_context_reference'] ?? $payload['careContextReference'] ?? '')),
            'linked_at' => trim((string) ($payload['linked_at'] ?? $payload['linkedAt'] ?? '')),
            'status' => self::normalize((string) ($payload['status'] ?? '')),
        ];
    }

    public static function normalize(string $status): string
    {
        $status = strtolower(trim($status));
        if (in_array($status, ['linked', 'success', 'completed'], true)) {
            return 'linked';
        }
        if (in_array($status, ['failed', 'error', 'rejected'], true)) {
            return 'failed';
        }

        return 'pending';
    }

    public static function merge(string $currentStatus, string $incomingStatus): string
    {
        $currentStatus = self::normalize($currentStatus);
        $incomingStatus = self::normalize($incomingStatus);
        if ($currentStatus === 'linked' || ($currentStatus === 'failed' && $incomingStatus === 'pending')) {
            return $currentStatus;
        }

        return $incomingStatus;
    }
}
